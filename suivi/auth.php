<?php
/**
 * Entreprises Wauters — accès privé au tableau de bord de suivi.
 * Le mot de passe n'est jamais stocké en clair : seul son hachage est
 * enregistré dans data/auth.php, en dehors du dépôt Git.
 */

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

const SUIVI_MAX_ESSAIS   = 8;
const SUIVI_BLOCAGE_SEC  = 900; // 15 min

function suivi_session_demarrer(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('EW_SUIVI');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/** Hachage du mot de passe enregistré, ou null si l'accès n'est pas encore configuré. */
function suivi_hash_enregistre(): ?string
{
    if (!is_readable(SUIVI_AUTH_FILE)) {
        return null;
    }
    $data = @include SUIVI_AUTH_FILE;
    return (is_array($data) && !empty($data['hash'])) ? (string) $data['hash'] : null;
}

function suivi_definir_mot_de_passe(string $motdepasse): bool
{
    if (!suivi_ensure_data_dir()) {
        return false;
    }
    $hash = password_hash($motdepasse, PASSWORD_DEFAULT);
    $php  = "<?php\n// Généré automatiquement — ne pas modifier à la main.\nreturn " .
            var_export(['hash' => $hash], true) . ";\n";
    $ok = @file_put_contents(SUIVI_AUTH_FILE, $php, LOCK_EX) !== false;
    if ($ok) {
        @chmod(SUIVI_AUTH_FILE, 0600);
    }
    return $ok;
}

/** Compteur d'échecs par empreinte d'IP, pour freiner les tentatives répétées. */
function suivi_fichier_essais(): string
{
    return SUIVI_DATA_DIR . '/login.json';
}

function suivi_essais_lire(): array
{
    $f = suivi_fichier_essais();
    if (!is_readable($f)) {
        return [];
    }
    $d = json_decode((string) file_get_contents($f), true);
    return is_array($d) ? $d : [];
}

function suivi_essais_ecrire(array $d): void
{
    // Purge des entrées expirées pour que le fichier reste minuscule.
    $limite = time() - SUIVI_BLOCAGE_SEC;
    foreach ($d as $k => $v) {
        if (($v['t'] ?? 0) < $limite) {
            unset($d[$k]);
        }
    }
    @file_put_contents(suivi_fichier_essais(), json_encode($d), LOCK_EX);
}

function suivi_cle_essais(): string
{
    return suivi_anon_ip(suivi_client_ip());
}

/** Secondes restantes avant de pouvoir réessayer (0 si non bloqué). */
function suivi_blocage_restant(): int
{
    $d = suivi_essais_lire();
    $e = $d[suivi_cle_essais()] ?? null;
    if (!$e || ($e['n'] ?? 0) < SUIVI_MAX_ESSAIS) {
        return 0;
    }
    $reste = SUIVI_BLOCAGE_SEC - (time() - (int) ($e['t'] ?? 0));
    return $reste > 0 ? $reste : 0;
}

function suivi_echec_enregistrer(): void
{
    $d = suivi_essais_lire();
    $k = suivi_cle_essais();
    $n = (int) ($d[$k]['n'] ?? 0);
    $d[$k] = ['n' => $n + 1, 't' => time()];
    suivi_essais_ecrire($d);
}

function suivi_echecs_reinitialiser(): void
{
    $d = suivi_essais_lire();
    unset($d[suivi_cle_essais()]);
    suivi_essais_ecrire($d);
}

function suivi_est_connecte(): bool
{
    suivi_session_demarrer();
    return !empty($_SESSION['suivi_ok']);
}

function suivi_connexion_ouvrir(): void
{
    suivi_session_demarrer();
    session_regenerate_id(true);
    $_SESSION['suivi_ok'] = true;
}

function suivi_deconnexion(): void
{
    suivi_session_demarrer();
    $_SESSION = [];
    session_destroy();
}

/** Jeton anti-CSRF pour les formulaires du tableau de bord. */
function suivi_jeton(): string
{
    suivi_session_demarrer();
    if (empty($_SESSION['suivi_jeton'])) {
        $_SESSION['suivi_jeton'] = bin2hex(random_bytes(16));
    }
    return (string) $_SESSION['suivi_jeton'];
}

function suivi_jeton_valide(?string $jeton): bool
{
    suivi_session_demarrer();
    return !empty($_SESSION['suivi_jeton']) && is_string($jeton)
        && hash_equals((string) $_SESSION['suivi_jeton'], $jeton);
}
