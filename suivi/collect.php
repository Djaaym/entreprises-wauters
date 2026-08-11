<?php
/**
 * Entreprises Wauters — point de collecte du suivi d'audience.
 * Reçoit les événements envoyés par assets/js/track.js et les enregistre
 * dans un fichier journalier. Répond toujours vite et sans contenu.
 */

declare(strict_types=1);

require __DIR__ . '/lib.php';

/** Termine la requête sans rien renvoyer au visiteur. */
function suivi_fin(int $code = 204): never
{
    http_response_code($code);
    header('Content-Length: 0');
    header('Cache-Control: no-store');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    suivi_fin(405);
}

// La collecte ne sert que ce site : on refuse les envois d'origine étrangère.
// Le port éventuel de HTTP_HOST est retiré, sinon la comparaison échouerait.
$hote   = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$hote   = preg_replace('/:\d+$/', '', $hote) ?? $hote;
$origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '');
if ($origin !== '' && $hote !== '') {
    $oh = strtolower((string) parse_url($origin, PHP_URL_HOST));
    if ($oh !== '' && $oh !== $hote && $oh !== 'www.' . $hote && 'www.' . $oh !== $hote) {
        suivi_fin(403);
    }
}

$ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
if (suivi_is_bot($ua)) {
    suivi_fin();
}

$brut = file_get_contents('php://input', false, null, 0, SUIVI_MAX_BODY + 1);
if ($brut === false || $brut === '' || strlen($brut) > SUIVI_MAX_BODY) {
    suivi_fin(400);
}

$in = json_decode($brut, true);
if (!is_array($in)) {
    suivi_fin(400);
}

$type = suivi_clean($in['typ'] ?? '', 20);
if (!in_array($type, ['pageview', 'action', 'fin'], true)) {
    suivi_fin(400);
}

if (!suivi_ensure_data_dir()) {
    suivi_fin(500);
}

$jour    = date('Y-m-d');
$fichier = suivi_fichier_jour($jour);

// Garde-fou : on ne laisse pas un fichier journalier grossir sans limite.
if (is_file($fichier) && filesize($fichier) > SUIVI_MAX_DAY) {
    suivi_fin();
}

[$appareil, $os, $navigateur] = suivi_parse_ua($ua);

$utm = [
    'source'   => suivi_clean($in['utm_source'] ?? '', 60),
    'medium'   => suivi_clean($in['utm_medium'] ?? '', 60),
    'campaign' => suivi_clean($in['utm_campaign'] ?? '', 80),
];

$referrer = suivi_clean($in['ref'] ?? '', 400);
if ($referrer !== '' && !preg_match('#^https?://#i', $referrer)) {
    $referrer = '';
}
[$source, $canal] = suivi_classer_source($referrer, $utm, $hote);

$evt = [
    't'    => time(),
    'typ'  => $type,
    'sid'  => suivi_clean($in['sid'] ?? '', 40),
    'vid'  => suivi_clean($in['vid'] ?? '', 40),
    'p'    => suivi_clean($in['p'] ?? '/', 200),
    'ti'   => suivi_clean($in['ti'] ?? '', 150),
    'ref'  => $referrer,
    'src'  => $source,
    'can'  => $canal,
    'cmp'  => $utm['campaign'],
    'dev'  => $appareil,
    'os'   => $os,
    'br'   => $navigateur,
    'lang' => suivi_clean($in['lang'] ?? '', 20),
    'tz'   => suivi_clean($in['tz'] ?? '', 60),
    'vw'   => max(0, min(10000, (int) ($in['vw'] ?? 0))),
    'nouv' => !empty($in['nouv']) ? 1 : 0,
    'emp'  => suivi_anon_ip(suivi_client_ip()),
];

if ($type === 'action') {
    $evt['act'] = suivi_clean($in['act'] ?? '', 60);
    $evt['lbl'] = suivi_clean($in['lbl'] ?? '', 200);
    if ($evt['act'] === '') {
        suivi_fin(400);
    }
}

if ($type === 'fin') {
    // Temps réellement passé sur la page, onglet visible, plafonné à 2 h.
    $evt['dur']    = max(0, min(7200, (int) ($in['dur'] ?? 0)));
    $evt['scroll'] = max(0, min(100, (int) ($in['scroll'] ?? 0)));
}

$ligne = json_encode($evt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($ligne === false) {
    suivi_fin(400);
}

@file_put_contents($fichier, $ligne . "\n", FILE_APPEND | LOCK_EX);

suivi_fin();
