<?php
/**
 * Entreprises Wauters — suivi d'audience
 * Fonctions partagées entre le collecteur et le tableau de bord.
 *
 * Stockage : un fichier JSONL par jour dans suivi/data/AAAA-MM-JJ.jsonl
 * Aucune base de données, aucune dépendance externe.
 */

declare(strict_types=1);

date_default_timezone_set('Europe/Brussels');

const SUIVI_DATA_DIR   = __DIR__ . '/data';
const SUIVI_AUTH_FILE  = __DIR__ . '/data/auth.php';
const SUIVI_MAX_BODY   = 16384;      // octets acceptés par requête
const SUIVI_MAX_DAY    = 33554432;   // 32 Mo max par fichier journalier

/** Crée le dossier de données et le protège d'un accès direct. */
function suivi_ensure_data_dir(): bool
{
    if (!is_dir(SUIVI_DATA_DIR) && !@mkdir(SUIVI_DATA_DIR, 0755, true) && !is_dir(SUIVI_DATA_DIR)) {
        return false;
    }
    $ht = SUIVI_DATA_DIR . '/.htaccess';
    if (!file_exists($ht)) {
        @file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
    }
    return true;
}

/** Sel stable propre à l'installation, utilisé pour anonymiser les IP. */
function suivi_salt(): string
{
    $file = SUIVI_DATA_DIR . '/salt.bin';
    if (is_readable($file)) {
        $s = (string) file_get_contents($file);
        if ($s !== '') {
            return $s;
        }
    }
    $s = bin2hex(random_bytes(32));
    suivi_ensure_data_dir();
    @file_put_contents($file, $s);
    @chmod($file, 0600);
    return $s;
}

/**
 * Empreinte anonyme du visiteur : l'IP n'est jamais stockée en clair et le
 * sel change chaque jour, ce qui rend l'empreinte non réversible et non
 * corrélable d'un jour à l'autre (approche « privacy friendly », pas de
 * consentement cookie requis pour la mesure d'audience anonyme).
 */
function suivi_anon_ip(string $ip): string
{
    return substr(hash('sha256', $ip . '|' . suivi_salt() . '|' . date('Y-m-d')), 0, 16);
}

/** IP du client, en tenant compte des proxies courants. */
function suivi_client_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $v = explode(',', (string) $_SERVER[$k])[0];
            $v = trim($v);
            if (filter_var($v, FILTER_VALIDATE_IP)) {
                return $v;
            }
        }
    }
    return '0.0.0.0';
}

/** Nettoie une chaîne venant du client : pas de caractère de contrôle, longueur bornée. */
function suivi_clean($v, int $max = 300): string
{
    if (!is_scalar($v)) {
        return '';
    }
    $s = (string) $v;
    $s = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s) ?? '';
    $s = trim($s);
    if ($s === '') {
        return '';
    }
    return mb_substr($s, 0, $max, 'UTF-8');
}

/** Robots et outils de monitoring : ignorés pour ne pas fausser les chiffres. */
function suivi_is_bot(string $ua): bool
{
    if ($ua === '') {
        return true;
    }
    return (bool) preg_match(
        '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|preview|monitor|uptime|pingdom|' .
        'lighthouse|headless|phantom|curl|wget|python-requests|axios|scrapy|semrush|ahrefs|mj12|dotbot/i',
        $ua
    );
}

/** Type d'appareil, système et navigateur déduits du User-Agent. */
function suivi_parse_ua(string $ua): array
{
    $appareil = 'ordinateur';
    if (preg_match('/iPad|Tablet|PlayBook|Silk/i', $ua) || (preg_match('/Android/i', $ua) && !preg_match('/Mobile/i', $ua))) {
        $appareil = 'tablette';
    } elseif (preg_match('/Mobi|iPhone|iPod|Android.*Mobile|Windows Phone/i', $ua)) {
        $appareil = 'mobile';
    }

    // L'ordre compte : un iPhone annonce « Mac OS X », un Android annonce « Linux ».
    $os = 'Autre';
    foreach ([
        'Windows'  => '/Windows NT/i',
        'iOS'      => '/iPhone|iPad|iPod/i',
        'Android'  => '/Android/i',
        'macOS'    => '/Mac OS X|Macintosh/i',
        'Linux'    => '/Linux|X11/i',
    ] as $nom => $re) {
        if (preg_match($re, $ua)) {
            $os = $nom;
            break;
        }
    }

    $nav = 'Autre';
    foreach ([
        'Edge'    => '/Edg\//i',
        'Opera'   => '/OPR\/|Opera/i',
        'Samsung' => '/SamsungBrowser/i',
        'Chrome'  => '/Chrome|CriOS/i',
        'Firefox' => '/Firefox|FxiOS/i',
        'Safari'  => '/Safari/i',
    ] as $nom => $re) {
        if (preg_match($re, $ua)) {
            $nav = $nom;
            break;
        }
    }

    return [$appareil, $os, $nav];
}

/**
 * Classe l'origine d'une visite : moteur de recherche, réseau social,
 * campagne UTM, site référent ou accès direct.
 * Retourne [source lisible, canal].
 */
function suivi_classer_source(string $referrer, array $utm, string $hote): array
{
    if (!empty($utm['source'])) {
        $canal = $utm['medium'] !== '' ? $utm['medium'] : 'campagne';
        return [$utm['source'], $canal];
    }

    if ($referrer === '') {
        return ['Accès direct', 'direct'];
    }

    $h = strtolower((string) parse_url($referrer, PHP_URL_HOST));
    if ($h === '') {
        return ['Accès direct', 'direct'];
    }
    $h = preg_replace('/^www\./', '', $h) ?? $h;

    // Navigation interne au site : ce n'est pas une source d'acquisition.
    $hote = preg_replace('/^www\./', '', strtolower($hote)) ?? $hote;
    if ($hote !== '' && ($h === $hote || str_ends_with($h, '.' . $hote))) {
        return ['Navigation interne', 'interne'];
    }

    $moteurs = [
        'google'     => 'Google',
        'bing'       => 'Bing',
        'yahoo'      => 'Yahoo',
        'duckduckgo' => 'DuckDuckGo',
        'ecosia'     => 'Ecosia',
        'qwant'      => 'Qwant',
        'yandex'     => 'Yandex',
        'brave'      => 'Brave',
    ];
    foreach ($moteurs as $frag => $nom) {
        if (str_contains($h, $frag)) {
            return [$nom, 'recherche'];
        }
    }

    $sociaux = [
        'facebook'  => 'Facebook',
        'fb.'       => 'Facebook',
        'instagram' => 'Instagram',
        'linkedin'  => 'LinkedIn',
        'lnkd.in'   => 'LinkedIn',
        't.co'      => 'X (Twitter)',
        'twitter'   => 'X (Twitter)',
        'x.com'     => 'X (Twitter)',
        'tiktok'    => 'TikTok',
        'pinterest' => 'Pinterest',
        'youtube'   => 'YouTube',
        'whatsapp'  => 'WhatsApp',
        'messenger' => 'Messenger',
        'snapchat'  => 'Snapchat',
        'reddit'    => 'Reddit',
    ];
    foreach ($sociaux as $frag => $nom) {
        if (str_contains($h, $frag)) {
            return [$nom, 'social'];
        }
    }

    $annuaires = [
        'pagesdor'    => 'Pages d\'Or',
        'goldenpages' => 'Pages d\'Or',
        'bativo'      => 'Bativo',
        'solvari'     => 'Solvari',
        'trustoo'     => 'Trustoo',
        'houzz'       => 'Houzz',
        'bobex'       => 'Bobex',
    ];
    foreach ($annuaires as $frag => $nom) {
        if (str_contains($h, $frag)) {
            return [$nom, 'annuaire'];
        }
    }

    return [$h, 'site référent'];
}

/** Chemin du fichier de données d'un jour donné. */
function suivi_fichier_jour(string $jour): string
{
    return SUIVI_DATA_DIR . '/' . $jour . '.jsonl';
}

/** Liste des jours disponibles (AAAA-MM-JJ), du plus ancien au plus récent. */
function suivi_jours_disponibles(): array
{
    $jours = [];
    foreach (glob(SUIVI_DATA_DIR . '/*.jsonl') ?: [] as $f) {
        $nom = basename($f, '.jsonl');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $nom)) {
            $jours[] = $nom;
        }
    }
    sort($jours);
    return $jours;
}

/** Lit et décode les événements d'un intervalle de jours inclusif. */
function suivi_lire_evenements(string $du, string $au): array
{
    $evts = [];
    $jour = $du;
    while ($jour <= $au) {
        $f = suivi_fichier_jour($jour);
        if (is_readable($f)) {
            $fh = fopen($f, 'rb');
            if ($fh) {
                while (($ligne = fgets($fh)) !== false) {
                    $ligne = trim($ligne);
                    if ($ligne === '') {
                        continue;
                    }
                    $e = json_decode($ligne, true);
                    if (is_array($e) && isset($e['t'])) {
                        $evts[] = $e;
                    }
                }
                fclose($fh);
            }
        }
        $jour = date('Y-m-d', strtotime($jour . ' +1 day'));
    }
    usort($evts, static fn(array $a, array $b): int => ($a['t'] <=> $b['t']));
    return $evts;
}

/** Libellé lisible d'une action. */
function suivi_libelle_action(string $action): string
{
    $labels = [
        'appel_telephone'   => '📞 Clic sur le numéro de téléphone',
        'email'             => '✉️ Clic sur l\'adresse e-mail',
        'cta_devis'         => '🎯 Clic sur « Devis / Demander un devis »',
        'formulaire_ouvert' => '📝 Formulaire commencé',
        'formulaire_envoye' => '✅ Formulaire de devis envoyé',
        'formulaire_erreur' => '⚠️ Échec d\'envoi du formulaire',
        'photo_agrandie'    => '🖼️ Photo agrandie',
        'filtre_realisations' => '🔎 Filtre des réalisations',
        'lien_sortant'      => '↗️ Clic vers un site externe',
        'menu_mobile'       => '☰ Ouverture du menu mobile',
        'scroll_25'         => '📜 A lu 25 % de la page',
        'scroll_50'         => '📜 A lu 50 % de la page',
        'scroll_75'         => '📜 A lu 75 % de la page',
        'scroll_100'        => '📜 A lu la page en entier',
        'itineraire'        => '🗺️ Clic sur l\'itinéraire / la carte',
    ];
    return $labels[$action] ?? $action;
}

/** Actions considérées comme des prises de contact (objectifs commerciaux). */
function suivi_actions_contact(): array
{
    return ['appel_telephone', 'email', 'formulaire_envoye', 'cta_devis'];
}

/** Formate une durée en secondes de façon lisible. */
function suivi_duree(int $s): string
{
    if ($s <= 0) {
        return '0 s';
    }
    if ($s < 60) {
        return $s . ' s';
    }
    $m = intdiv($s, 60);
    $r = $s % 60;
    if ($m < 60) {
        return $r ? $m . ' min ' . $r . ' s' : $m . ' min';
    }
    $h = intdiv($m, 60);
    return $h . ' h ' . ($m % 60) . ' min';
}
