<?php
/**
 * Entreprises Wauters — tableau de bord de suivi d'audience (accès privé).
 * Affiche, jour par jour et avec filtres : d'où viennent les visiteurs,
 * combien de temps ils restent, quelles pages ils consultent et quelles
 * actions ils réalisent sur le site.
 */

declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/agregation.php';

function h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/** Construit une URL du tableau de bord en conservant les filtres courants. */
function lien(array $remplace = []): string
{
    $p = array_merge($_GET, $remplace);
    foreach ($p as $k => $v) {
        if ($v === '' || $v === null) {
            unset($p[$k]);
        }
    }
    $q = http_build_query($p);
    return 'index.php' . ($q !== '' ? '?' . $q : '');
}

$hashEnregistre = suivi_hash_enregistre();

// ---------------------------------------------------------------- Déconnexion
if (isset($_GET['deconnexion'])) {
    suivi_deconnexion();
    header('Location: index.php');
    exit;
}

// ------------------------------------------------- Première configuration
$erreur = '';
$info   = '';

if ($hashEnregistre === null && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['nouveau_mdp'])) {
    $mdp  = (string) $_POST['nouveau_mdp'];
    $mdp2 = (string) ($_POST['confirmation'] ?? '');
    if (mb_strlen($mdp) < 10) {
        $erreur = 'Le mot de passe doit contenir au moins 10 caractères.';
    } elseif ($mdp !== $mdp2) {
        $erreur = 'Les deux mots de passe ne sont pas identiques.';
    } elseif (!suivi_definir_mot_de_passe($mdp)) {
        $erreur = "Impossible d'enregistrer le mot de passe : vérifiez les droits d'écriture sur le dossier suivi/data/.";
    } else {
        suivi_connexion_ouvrir();
        header('Location: index.php');
        exit;
    }
}

// ------------------------------------------------------------------ Connexion
if ($hashEnregistre !== null && !suivi_est_connecte()
    && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['mdp'])) {
    $reste = suivi_blocage_restant();
    if ($reste > 0) {
        $erreur = 'Trop de tentatives. Réessayez dans ' . ceil($reste / 60) . ' minute(s).';
    } elseif (password_verify((string) $_POST['mdp'], $hashEnregistre)) {
        suivi_echecs_reinitialiser();
        suivi_connexion_ouvrir();
        header('Location: index.php');
        exit;
    } else {
        suivi_echec_enregistrer();
        $erreur = 'Mot de passe incorrect.';
    }
}

// --------------------------------------------------- Page de connexion / setup
if (!suivi_est_connecte()) {
    $configuration = ($hashEnregistre === null);
    ?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Suivi — Entreprises Wauters</title>
<style>
  :root { --ink:#1F2933; --accent:#E8772E; --line:#E4E7EB; --muted:#8A94A6; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { min-height:100vh; display:grid; place-items:center; padding:24px;
         font-family:system-ui,-apple-system,"Segoe UI",sans-serif; color:var(--ink);
         background:linear-gradient(160deg,#232b34,#1a2129); }
  .box { width:100%; max-width:420px; background:#fff; border-radius:14px; padding:32px;
         box-shadow:0 20px 60px rgba(0,0,0,.35); }
  h1 { font-size:1.3rem; margin-bottom:6px; }
  p.sub { color:var(--muted); font-size:.92rem; margin-bottom:22px; line-height:1.5; }
  label { display:block; font-weight:600; font-size:.88rem; margin-bottom:6px; }
  input { width:100%; padding:12px 14px; font-size:1rem; border:1px solid var(--line);
          border-radius:8px; margin-bottom:16px; font-family:inherit; }
  input:focus { outline:2px solid var(--accent); outline-offset:1px; border-color:var(--accent); }
  button { width:100%; padding:13px; font-size:1rem; font-weight:700; color:#fff;
           background:var(--accent); border:0; border-radius:8px; cursor:pointer; font-family:inherit; }
  button:hover { background:#C75F1B; }
  .err { background:#FBEAE5; color:#B23A1B; border:1px solid #F3C9BC; padding:11px 14px;
         border-radius:8px; font-size:.9rem; margin-bottom:16px; }
</style>
</head>
<body>
  <div class="box">
    <h1><?= $configuration ? 'Protégez votre tableau de bord' : 'Suivi du site' ?></h1>
    <p class="sub"><?= $configuration
      ? "Première visite : choisissez le mot de passe qui protégera cette page. Vous seul pourrez y accéder ensuite."
      : "Cette page est réservée. Entrez votre mot de passe pour consulter les statistiques." ?></p>

    <?php if ($erreur !== ''): ?><div class="err"><?= h($erreur) ?></div><?php endif; ?>

    <form method="post" autocomplete="off">
      <?php if ($configuration): ?>
        <label for="n1">Nouveau mot de passe (10 caractères minimum)</label>
        <input type="password" id="n1" name="nouveau_mdp" required minlength="10" autofocus>
        <label for="n2">Confirmez le mot de passe</label>
        <input type="password" id="n2" name="confirmation" required minlength="10">
        <button type="submit">Enregistrer et accéder au suivi</button>
      <?php else: ?>
        <label for="m">Mot de passe</label>
        <input type="password" id="m" name="mdp" required autofocus>
        <button type="submit">Se connecter</button>
      <?php endif; ?>
    </form>
  </div>
</body>
</html><?php
    exit;
}

// ------------------------------------------------- Changement de mot de passe
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['changer_mdp'])) {
    if (!suivi_jeton_valide($_POST['jeton'] ?? null)) {
        $erreur = 'Session expirée, veuillez réessayer.';
    } elseif (!password_verify((string) ($_POST['mdp_actuel'] ?? ''), (string) $hashEnregistre)) {
        $erreur = 'Le mot de passe actuel est incorrect.';
    } elseif (mb_strlen((string) $_POST['changer_mdp']) < 10) {
        $erreur = 'Le nouveau mot de passe doit contenir au moins 10 caractères.';
    } elseif (suivi_definir_mot_de_passe((string) $_POST['changer_mdp'])) {
        $info = 'Mot de passe modifié.';
    } else {
        $erreur = "Impossible d'enregistrer le nouveau mot de passe.";
    }
}

// ------------------------------------------------------------------- Filtres
$joursDispo = suivi_jours_disponibles();
$aujourdhui = date('Y-m-d');

$periode = (string) ($_GET['periode'] ?? '7j');
$du      = (string) ($_GET['du'] ?? '');
$au      = (string) ($_GET['au'] ?? '');

$bornes = [
    'aujourdhui' => [$aujourdhui, $aujourdhui],
    'hier'       => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))],
    '7j'         => [date('Y-m-d', strtotime('-6 days')), $aujourdhui],
    '30j'        => [date('Y-m-d', strtotime('-29 days')), $aujourdhui],
    '90j'        => [date('Y-m-d', strtotime('-89 days')), $aujourdhui],
    'tout'       => [$joursDispo[0] ?? $aujourdhui, $aujourdhui],
];

if ($periode === 'perso' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $du) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $au)) {
    if ($du > $au) {
        [$du, $au] = [$au, $du];
    }
} else {
    if (!isset($bornes[$periode])) {
        $periode = '7j';
    }
    [$du, $au] = $bornes[$periode];
}

$filtres = [
    'source'   => (string) ($_GET['source'] ?? ''),
    'canal'    => (string) ($_GET['canal'] ?? ''),
    'appareil' => (string) ($_GET['appareil'] ?? ''),
    'page'     => (string) ($_GET['page'] ?? ''),
    'action'   => (string) ($_GET['action'] ?? ''),
    'contact'  => (string) ($_GET['contact'] ?? ''),
];

$evenements    = suivi_lire_evenements($du, $au);
$toutesVisites = suivi_construire_visites($evenements);
$visites       = suivi_filtrer_visites($toutesVisites, $filtres);
$kpi           = suivi_indicateurs($visites);

// ------------------------------------------------------ Export CSV du filtre
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="suivi-' . $du . '_' . $au . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM : Excel ouvre correctement les accents
    fputcsv($out, ['Date', 'Heure', 'Source', 'Canal', 'Appareil', 'Pages vues',
                   'Duree (s)', 'Lecture (%)', 'Page entree', 'Actions', 'Contact'], ';');
    foreach ($visites as $v) {
        fputcsv($out, [
            date('Y-m-d', $v['debut']),
            date('H:i', $v['debut']),
            $v['source'],
            $v['canal'],
            $v['appareil'],
            count($v['pages']),
            $v['duree'],
            $v['scroll'],
            $v['entree'],
            implode(' | ', array_column($v['actions'], 'act')),
            $v['contact'] ? 'oui' : 'non',
        ], ';');
    }
    fclose($out);
    exit;
}

// ------------------------------------------------------------- Agrégations
$parJour     = suivi_par_jour($visites, $du, $au);
$parHeure    = suivi_par_heure($visites);
$topSources  = suivi_repartition($visites, 'source', 12);
$topCanaux   = suivi_repartition($visites, 'canal');
$topAppareil = suivi_repartition($visites, 'appareil');
$topPages    = suivi_top_pages($visites);
$topActions  = suivi_top_actions($visites);

$sourcesDispo  = array_keys(suivi_repartition($toutesVisites, 'source'));
$canauxDispo   = array_keys(suivi_repartition($toutesVisites, 'canal'));
$appareilDispo = array_keys(suivi_repartition($toutesVisites, 'appareil'));
$pagesDispo    = array_keys(suivi_top_pages($toutesVisites, 200));
$actionsDispo  = array_keys(suivi_top_actions($toutesVisites));

$detail = null;
if (!empty($_GET['visite']) && isset($toutesVisites[(string) $_GET['visite']])) {
    $detail = $toutesVisites[(string) $_GET['visite']];
}

$maxJour = max(1, max(array_column($parJour, 'visites') ?: [1]));
$maxHeure = max(1, max($parHeure));
$page = max(1, (int) ($_GET['pg'] ?? 1));
$parPage = 40;
$visitesPage = array_slice($visites, ($page - 1) * $parPage, $parPage, true);
$nbPages = max(1, (int) ceil(count($visites) / $parPage));

$filtreActif = implode('', $filtres) !== '';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Suivi du site — Entreprises Wauters</title>
<style>
  :root {
    --bg:#F5F7FA; --surface:#fff; --ink:#1F2933; --ink-soft:#3E4C59;
    --muted:#8A94A6; --line:#E4E7EB; --accent:#E8772E; --accent-dark:#C75F1B;
    --accent-soft:#FBEDE1; --vert:#2E8B57; --radius:12px;
  }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
         background:var(--bg); color:var(--ink); line-height:1.5; padding-bottom:60px; }
  a { color:inherit; }
  .wrap { max-width:1240px; margin:0 auto; padding:0 20px; }

  .top { background:#1F2933; color:#fff; padding:18px 0; margin-bottom:24px; }
  .top .wrap { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
  .top h1 { font-size:1.15rem; font-weight:800; }
  .top .periode { color:#AEB7C2; font-size:.9rem; }
  .top .spacer { margin-left:auto; }
  .top a { color:#AEB7C2; font-size:.88rem; text-decoration:none; border:1px solid #3E4C59;
           padding:7px 13px; border-radius:8px; }
  .top a:hover { color:#fff; border-color:#8A94A6; }

  .card { background:var(--surface); border:1px solid var(--line); border-radius:var(--radius);
          padding:20px; margin-bottom:20px; }
  .card h2 { font-size:1rem; margin-bottom:14px; display:flex; align-items:center;
             justify-content:space-between; gap:10px; flex-wrap:wrap; }
  .card h2 small { font-weight:400; color:var(--muted); font-size:.82rem; }

  /* --- Filtres --- */
  .presets { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
  .presets a { text-decoration:none; font-size:.88rem; font-weight:600; padding:7px 14px;
               border:1px solid var(--line); border-radius:100px; background:var(--surface); color:var(--ink-soft); }
  .presets a:hover { border-color:var(--accent); color:var(--accent-dark); }
  .presets a.on { background:var(--accent); border-color:var(--accent); color:#fff; }
  .champs { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
  .champ { display:flex; flex-direction:column; gap:4px; }
  .champ label { font-size:.78rem; font-weight:700; color:var(--muted); text-transform:uppercase;
                 letter-spacing:.04em; }
  select, input[type=date], input[type=password] {
    font-family:inherit; font-size:.92rem; padding:8px 10px; border:1px solid var(--line);
    border-radius:8px; background:var(--surface); color:var(--ink); max-width:230px; }
  .btn { font-family:inherit; font-size:.92rem; font-weight:700; padding:9px 16px; border-radius:8px;
         border:0; background:var(--accent); color:#fff; cursor:pointer; text-decoration:none;
         display:inline-block; }
  .btn:hover { background:var(--accent-dark); }
  .btn--ghost { background:transparent; color:var(--ink-soft); border:1px solid var(--line); }
  .btn--ghost:hover { background:var(--bg); color:var(--ink); }

  /* --- KPI --- */
  .kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(165px,1fr)); gap:14px; margin-bottom:20px; }
  .kpi { background:var(--surface); border:1px solid var(--line); border-radius:var(--radius); padding:16px 18px; }
  .kpi .l { font-size:.78rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; }
  .kpi .n { font-size:1.75rem; font-weight:800; margin-top:4px; letter-spacing:-.02em; }
  .kpi .s { font-size:.82rem; color:var(--muted); }
  /* Les durées ("5 min 37 s") sont plus longues qu'un nombre : police réduite. */
  .kpi--accent .n { color:var(--accent-dark); font-size:1.45rem; white-space:nowrap; }
  .kpi--vert .n { color:var(--vert); }

  /* --- Graphiques --- */
  .bars { display:flex; align-items:flex-end; gap:3px; height:170px; margin-top:6px; }
  /* max-width : sur une période courte, les barres ne doivent pas devenir des pavés. */
  .bar { flex:1; min-width:5px; max-width:64px; display:flex; flex-direction:column;
         justify-content:flex-end; height:100%; position:relative; }
  .bar i { display:block; background:var(--accent); border-radius:3px 3px 0 0; min-height:2px; }
  .bar b { display:block; background:var(--vert); border-radius:0 0 3px 3px; min-height:0; }
  .bar:hover i { background:var(--accent-dark); }
  .bar span { position:absolute; bottom:-20px; left:50%; transform:translateX(-50%);
              font-size:.68rem; color:var(--muted); white-space:nowrap; }
  .bars-legende { display:flex; gap:16px; font-size:.8rem; color:var(--muted); margin-top:28px; }
  .bars-legende i { display:inline-block; width:10px; height:10px; border-radius:2px; margin-right:5px; }

  /* --- Tables --- */
  .cols { display:grid; grid-template-columns:repeat(auto-fit,minmax(330px,1fr)); gap:20px; }
  .tableau { overflow-x:auto; -webkit-overflow-scrolling:touch; }
  table { width:100%; border-collapse:collapse; font-size:.9rem; }
  th { text-align:left; font-size:.74rem; text-transform:uppercase; letter-spacing:.05em;
       color:var(--muted); padding:0 8px 8px; border-bottom:1px solid var(--line); font-weight:700; }
  td { padding:9px 8px; border-bottom:1px solid #F2F4F7; vertical-align:top; }
  tr:last-child td { border-bottom:0; }
  td.num, th.num { text-align:right; white-space:nowrap; }
  .lien { color:var(--accent-dark); text-decoration:none; font-weight:600; }
  .lien:hover { text-decoration:underline; }
  .jauge { height:5px; border-radius:3px; background:var(--accent-soft); margin-top:5px; overflow:hidden; }
  .jauge i { display:block; height:100%; background:var(--accent); }
  .pastille { display:inline-block; font-size:.72rem; font-weight:700; padding:2px 8px;
              border-radius:100px; background:var(--accent-soft); color:var(--accent-dark); }
  .pastille--vert { background:#E7F4EC; color:#1E6B43; }
  .pastille--gris { background:#EEF1F5; color:var(--ink-soft); }
  .vide { color:var(--muted); font-size:.92rem; padding:20px 0; text-align:center; }

  /* --- Détail d'une visite --- */
  .frise { list-style:none; border-left:2px solid var(--line); margin-left:8px; padding-left:18px; }
  .frise li { position:relative; padding:8px 0; font-size:.9rem; }
  .frise li::before { content:""; position:absolute; left:-25px; top:15px; width:10px; height:10px;
                      border-radius:50%; background:var(--muted); border:2px solid var(--surface); }
  .frise li.act::before { background:var(--accent); }
  .frise li.pv::before { background:var(--ink-soft); }
  .frise .hh { color:var(--muted); font-variant-numeric:tabular-nums; margin-right:8px; font-size:.84rem; }
  .meta { display:flex; gap:20px; flex-wrap:wrap; font-size:.88rem; color:var(--ink-soft); margin-bottom:16px; }
  .meta b { display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); }

  .msg { padding:11px 14px; border-radius:8px; font-size:.9rem; margin-bottom:16px; }
  .msg--ok { background:#E7F4EC; color:#1E6B43; border:1px solid #BFE3CC; }
  .msg--err { background:#FBEAE5; color:#B23A1B; border:1px solid #F3C9BC; }
  details summary { cursor:pointer; font-weight:600; font-size:.9rem; color:var(--ink-soft); }
  .pagination { display:flex; gap:6px; flex-wrap:wrap; margin-top:14px; }
  .pagination a, .pagination span { padding:6px 11px; border:1px solid var(--line); border-radius:7px;
       font-size:.85rem; text-decoration:none; color:var(--ink-soft); }
  .pagination span { background:var(--accent); border-color:var(--accent); color:#fff; font-weight:700; }
  @media (max-width:640px) {
    .bar span { display:none; }
    .top h1 { font-size:1rem; }
  }
</style>
</head>
<body>

<div class="top">
  <div class="wrap">
    <h1>📊 Suivi du site</h1>
    <span class="periode">
      du <?= h(date('d/m/Y', strtotime($du))) ?> au <?= h(date('d/m/Y', strtotime($au))) ?>
    </span>
    <span class="spacer"></span>
    <a href="<?= h(lien(['export' => 'csv'])) ?>">⬇ Export CSV</a>
    <a href="../index.html">← Le site</a>
    <a href="?deconnexion=1">Déconnexion</a>
  </div>
</div>

<div class="wrap">

<?php if ($info !== ''): ?><div class="msg msg--ok"><?= h($info) ?></div><?php endif; ?>
<?php if ($erreur !== ''): ?><div class="msg msg--err"><?= h($erreur) ?></div><?php endif; ?>

<?php if (!$joursDispo): ?>
  <div class="card">
    <h2>Aucune donnée pour l'instant</h2>
    <p style="color:var(--ink-soft);font-size:.94rem">
      Le suivi est en place mais aucune visite n'a encore été enregistrée.
      Les statistiques apparaîtront ici dès les premières visites sur le site
      (le script de mesure est chargé sur toutes les pages).
    </p>
  </div>
<?php endif; ?>

<!-- ------------------------------------------------------------ Filtres -->
<div class="card">
  <div class="presets">
    <?php
    $libellesPeriodes = [
        'aujourdhui' => "Aujourd'hui", 'hier' => 'Hier', '7j' => '7 jours',
        '30j' => '30 jours', '90j' => '90 jours', 'tout' => 'Tout',
    ];
    foreach ($libellesPeriodes as $cle => $lib):
        $on = ($periode === $cle) ? ' class="on"' : '';
    ?>
      <a<?= $on ?> href="<?= h(lien(['periode' => $cle, 'du' => null, 'au' => null, 'pg' => null, 'visite' => null])) ?>"><?= h($lib) ?></a>
    <?php endforeach; ?>
  </div>

  <form method="get" class="champs">
    <input type="hidden" name="periode" value="perso">
    <div class="champ">
      <label for="f-du">Du</label>
      <input type="date" id="f-du" name="du" value="<?= h($du) ?>">
    </div>
    <div class="champ">
      <label for="f-au">Au</label>
      <input type="date" id="f-au" name="au" value="<?= h($au) ?>">
    </div>
    <div class="champ">
      <label for="f-src">Source</label>
      <select id="f-src" name="source">
        <option value="">Toutes</option>
        <?php foreach ($sourcesDispo as $s): ?>
          <option value="<?= h($s) ?>"<?= $filtres['source'] === $s ? ' selected' : '' ?>><?= h($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="champ">
      <label for="f-can">Canal</label>
      <select id="f-can" name="canal">
        <option value="">Tous</option>
        <?php foreach ($canauxDispo as $c): ?>
          <option value="<?= h($c) ?>"<?= $filtres['canal'] === $c ? ' selected' : '' ?>><?= h($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="champ">
      <label for="f-dev">Appareil</label>
      <select id="f-dev" name="appareil">
        <option value="">Tous</option>
        <?php foreach ($appareilDispo as $d): ?>
          <option value="<?= h($d) ?>"<?= $filtres['appareil'] === $d ? ' selected' : '' ?>><?= h($d) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="champ">
      <label for="f-page">Page visitée</label>
      <select id="f-page" name="page">
        <option value="">Toutes</option>
        <?php foreach ($pagesDispo as $p): ?>
          <option value="<?= h($p) ?>"<?= $filtres['page'] === $p ? ' selected' : '' ?>><?= h($p) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="champ">
      <label for="f-act">Action réalisée</label>
      <select id="f-act" name="action">
        <option value="">Toutes</option>
        <?php foreach ($actionsDispo as $a): ?>
          <option value="<?= h($a) ?>"<?= $filtres['action'] === $a ? ' selected' : '' ?>><?= h(suivi_libelle_action($a)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="champ">
      <label for="f-ct">Prise de contact</label>
      <select id="f-ct" name="contact">
        <option value="">Toutes les visites</option>
        <option value="1"<?= $filtres['contact'] === '1' ? ' selected' : '' ?>>Uniquement avec contact</option>
      </select>
    </div>
    <button class="btn" type="submit">Filtrer</button>
    <?php if ($filtreActif || $periode === 'perso'): ?>
      <a class="btn btn--ghost" href="index.php">Réinitialiser</a>
    <?php endif; ?>
  </form>
</div>

<!-- --------------------------------------------------------------- KPIs -->
<div class="kpis">
  <div class="kpi">
    <div class="l">Visites</div>
    <div class="n"><?= number_format($kpi['visites'], 0, ',', ' ') ?></div>
    <div class="s"><?= number_format($kpi['visiteurs'], 0, ',', ' ') ?> visiteur(s) différent(s)</div>
  </div>
  <div class="kpi">
    <div class="l">Pages vues</div>
    <div class="n"><?= number_format($kpi['pages_vues'], 0, ',', ' ') ?></div>
    <div class="s"><?= h(str_replace('.', ',', (string) $kpi['pages_visite'])) ?> page(s) par visite</div>
  </div>
  <div class="kpi kpi--accent">
    <div class="l">Temps moyen</div>
    <div class="n"><?= h(suivi_duree($kpi['duree_moyenne'])) ?></div>
    <div class="s"><?= h(suivi_duree($kpi['duree_totale'])) ?> au total</div>
  </div>
  <div class="kpi kpi--vert">
    <div class="l">Prises de contact</div>
    <div class="n"><?= number_format($kpi['contacts'], 0, ',', ' ') ?></div>
    <div class="s"><?= h(str_replace('.', ',', (string) $kpi['taux_contact'])) ?> % des visites</div>
  </div>
  <div class="kpi">
    <div class="l">Taux de rebond</div>
    <div class="n"><?= (int) $kpi['taux_rebond'] ?> %</div>
    <div class="s">1 page, &lt; 15 s, sans contact</div>
  </div>
  <div class="kpi">
    <div class="l">Nouveaux visiteurs</div>
    <div class="n"><?= $kpi['visites'] ? (int) round(($kpi['nouveaux'] / $kpi['visites']) * 100) : 0 ?> %</div>
    <div class="s"><?= number_format($kpi['nouveaux'], 0, ',', ' ') ?> première(s) visite(s)</div>
  </div>
</div>

<!-- ------------------------------------------------------ Visites par jour -->
<div class="card">
  <h2>Visites jour par jour <small>orange : visites &nbsp;·&nbsp; vert : visites avec prise de contact</small></h2>
  <div class="bars">
    <?php foreach ($parJour as $jour => $d):
      $hV = (int) round(($d['visites'] / $maxJour) * 150);
      $hC = (int) round(($d['contacts'] / $maxJour) * 150);
      $jl = date('d/m', strtotime($jour));
    ?>
      <div class="bar" title="<?= h(date('D d/m/Y', strtotime($jour))) ?> — <?= (int) $d['visites'] ?> visite(s), <?= (int) $d['pages'] ?> page(s) vue(s), <?= (int) $d['contacts'] ?> contact(s)">
        <i style="height:<?= max(0, $hV - $hC) ?>px"></i>
        <b style="height:<?= $hC ?>px"></b>
        <?php if (count($parJour) <= 31): ?><span><?= h($jl) ?></span><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="bars-legende">
    <span><i style="background:var(--accent)"></i>Visites</span>
    <span><i style="background:var(--vert)"></i>Avec prise de contact</span>
    <span>Maximum : <?= (int) $maxJour ?> visite(s)/jour</span>
  </div>
</div>

<div class="cols">
  <!-- ------------------------------------------------------------ Sources -->
  <div class="card">
    <h2>D'où viennent les visiteurs <small>par visite</small></h2>
    <?php if (!$topSources): ?><div class="vide">Aucune visite sur la période.</div><?php else: ?>
    <div class="tableau">
      <table>
        <thead><tr><th>Source</th><th class="num">Visites</th><th class="num">Part</th></tr></thead>
        <tbody>
        <?php $totSrc = array_sum($topSources); foreach ($topSources as $src => $n): ?>
          <tr>
            <td>
              <a class="lien" href="<?= h(lien(['source' => $src, 'pg' => null, 'visite' => null])) ?>"><?= h($src) ?></a>
              <div class="jauge"><i style="width:<?= $totSrc ? round(($n / $totSrc) * 100) : 0 ?>%"></i></div>
            </td>
            <td class="num"><?= (int) $n ?></td>
            <td class="num"><?= $totSrc ? round(($n / $totSrc) * 100) : 0 ?> %</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ------------------------------------------------------------- Canaux -->
  <div class="card">
    <h2>Type de canal &amp; appareil</h2>
    <?php if (!$topCanaux): ?><div class="vide">Aucune visite sur la période.</div><?php else: ?>
    <div class="tableau">
      <table>
        <thead><tr><th>Canal</th><th class="num">Visites</th></tr></thead>
        <tbody>
        <?php foreach ($topCanaux as $c => $n): ?>
          <tr>
            <td><a class="lien" href="<?= h(lien(['canal' => $c, 'pg' => null, 'visite' => null])) ?>"><?= h($c) ?></a></td>
            <td class="num"><?= (int) $n ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="tableau">
      <table style="margin-top:18px">
        <thead><tr><th>Appareil</th><th class="num">Visites</th></tr></thead>
        <tbody>
        <?php foreach ($topAppareil as $d => $n): ?>
          <tr>
            <td><a class="lien" href="<?= h(lien(['appareil' => $d, 'pg' => null, 'visite' => null])) ?>"><?= h(ucfirst($d)) ?></a></td>
            <td class="num"><?= (int) $n ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="cols">
  <!-- -------------------------------------------------------------- Pages -->
  <div class="card">
    <h2>Pages les plus consultées <small>et temps passé</small></h2>
    <?php if (!$topPages): ?><div class="vide">Aucune page vue sur la période.</div><?php else: ?>
    <div class="tableau">
      <table>
        <thead><tr><th>Page</th><th class="num">Vues</th><th class="num">Temps moyen</th></tr></thead>
        <tbody>
        <?php foreach ($topPages as $chemin => $s): ?>
          <tr>
            <td>
              <a class="lien" href="<?= h(lien(['page' => $chemin, 'pg' => null, 'visite' => null])) ?>"><?= h($chemin) ?></a>
              <?php if ($s['titre'] !== ''): ?>
                <div style="color:var(--muted);font-size:.8rem"><?= h(mb_strimwidth($s['titre'], 0, 58, '…')) ?></div>
              <?php endif; ?>
            </td>
            <td class="num"><?= (int) $s['vues'] ?></td>
            <td class="num"><?= h(suivi_duree((int) $s['duree_moy'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ------------------------------------------------------------ Actions -->
  <div class="card">
    <h2>Ce que font les visiteurs <small>actions sur le site</small></h2>
    <?php if (!$topActions): ?><div class="vide">Aucune action enregistrée sur la période.</div><?php else: ?>
    <div class="tableau">
      <table>
        <thead><tr><th>Action</th><th class="num">Fois</th><th class="num">Visites</th></tr></thead>
        <tbody>
        <?php foreach ($topActions as $act => $s): ?>
          <tr>
            <td>
              <a class="lien" href="<?= h(lien(['action' => $act, 'pg' => null, 'visite' => null])) ?>"><?= h(suivi_libelle_action($act)) ?></a>
              <?php if (in_array($act, suivi_actions_contact(), true)): ?>
                <span class="pastille pastille--vert">contact</span>
              <?php endif; ?>
            </td>
            <td class="num"><?= (int) $s['nb'] ?></td>
            <td class="num"><?= (int) $s['nb_visites'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ------------------------------------------------------- Heures de visite -->
<div class="card">
  <h2>Heures de visite <small>pour savoir quand vos clients consultent le site</small></h2>
  <div class="bars" style="height:110px">
    <?php foreach ($parHeure as $heure => $n): ?>
      <div class="bar" title="<?= sprintf('%02dh — %02dh', $heure, $heure + 1) ?> : <?= (int) $n ?> visite(s)">
        <i style="height:<?= (int) round(($n / $maxHeure) * 95) ?>px"></i>
        <span><?= sprintf('%02d', $heure) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="bars-legende"><span>Heure de début de visite (heure belge)</span></div>
</div>

<!-- ------------------------------------------------------ Détail d'une visite -->
<?php if ($detail): ?>
<div class="card" id="detail">
  <h2>
    Détail de la visite du <?= h(date('d/m/Y à H:i', $detail['debut'])) ?>
    <a class="btn btn--ghost" href="<?= h(lien(['visite' => null])) ?>">Fermer</a>
  </h2>
  <div class="meta">
    <span><b>Origine</b><?= h($detail['source']) ?> (<?= h($detail['canal']) ?>)</span>
    <span><b>Appareil</b><?= h(ucfirst($detail['appareil'])) ?> · <?= h($detail['os']) ?> · <?= h($detail['navigateur']) ?></span>
    <span><b>Durée</b><?= h(suivi_duree($detail['duree'])) ?></span>
    <span><b>Pages vues</b><?= count($detail['pages']) ?></span>
    <span><b>Lecture max</b><?= (int) $detail['scroll'] ?> %</span>
    <span><b>Visiteur</b><?= $detail['nouveau'] ? 'Nouveau' : 'Déjà venu' ?></span>
    <?php if ($detail['langue'] !== ''): ?><span><b>Langue</b><?= h($detail['langue']) ?></span><?php endif; ?>
    <?php if ($detail['fuseau'] !== ''): ?><span><b>Fuseau</b><?= h($detail['fuseau']) ?></span><?php endif; ?>
    <?php if ($detail['campagne'] !== ''): ?><span><b>Campagne</b><?= h($detail['campagne']) ?></span><?php endif; ?>
  </div>
  <?php if ($detail['referrer'] !== ''): ?>
    <p style="font-size:.86rem;color:var(--muted);margin-bottom:14px;word-break:break-all">
      Lien d'arrivée : <?= h($detail['referrer']) ?>
    </p>
  <?php endif; ?>

  <?php
  $frise = [];
  foreach ($detail['pages'] as $p) {
      $frise[] = ['t' => $p['t'], 'type' => 'pv', 'txt' => 'Consulte ' . $p['p'], 'sub' => $p['titre']];
  }
  foreach ($detail['actions'] as $a) {
      $frise[] = ['t' => $a['t'], 'type' => 'act',
                  'txt' => suivi_libelle_action($a['act']),
                  'sub' => trim($a['lbl'] . ($a['p'] !== '' ? '  ·  sur ' . $a['p'] : ''))];
  }
  usort($frise, static fn(array $x, array $y): int => $x['t'] <=> $y['t']);
  ?>
  <ol class="frise">
    <?php foreach ($frise as $f): ?>
      <li class="<?= h($f['type']) ?>">
        <span class="hh"><?= h(date('H:i:s', $f['t'])) ?></span><?= h($f['txt']) ?>
        <?php if (trim((string) $f['sub']) !== ''): ?>
          <div style="color:var(--muted);font-size:.83rem;margin-left:56px"><?= h(mb_strimwidth((string) $f['sub'], 0, 110, '…')) ?></div>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</div>
<?php endif; ?>

<!-- ------------------------------------------------------ Liste des visites -->
<div class="card">
  <h2>
    Visites détaillées
    <small><?= number_format(count($visites), 0, ',', ' ') ?> visite(s) — cliquez sur une ligne pour voir le parcours complet</small>
  </h2>
  <?php if (!$visitesPage): ?>
    <div class="vide">Aucune visite ne correspond aux filtres choisis.</div>
  <?php else: ?>
  <div class="tableau">
    <table>
      <thead>
        <tr>
          <th>Date &amp; heure</th><th>Origine</th><th>Appareil</th>
          <th>Page d'entrée</th><th class="num">Pages</th><th class="num">Durée</th>
          <th class="num">Lecture</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($visitesPage as $sid => $v):
        $actionsCles = array_values(array_unique(array_filter(
            array_column($v['actions'], 'act'),
            static fn(string $a): bool => !str_starts_with($a, 'scroll_')
        )));
      ?>
        <tr>
          <td>
            <a class="lien" href="<?= h(lien(['visite' => $sid])) ?>#detail"><?= h(date('d/m H:i', $v['debut'])) ?></a>
            <?php if ($v['nouveau']): ?><div><span class="pastille pastille--gris">nouveau</span></div><?php endif; ?>
          </td>
          <td><?= h($v['source']) ?><div style="color:var(--muted);font-size:.79rem"><?= h($v['canal']) ?></div></td>
          <td><?= h(ucfirst($v['appareil'])) ?><div style="color:var(--muted);font-size:.79rem"><?= h($v['os']) ?></div></td>
          <td style="max-width:200px;word-break:break-word"><?= h($v['entree']) ?></td>
          <td class="num"><?= count($v['pages']) ?></td>
          <td class="num"><?= h(suivi_duree($v['duree'])) ?></td>
          <td class="num"><?= (int) $v['scroll'] ?> %</td>
          <td>
            <?php if (!$actionsCles): ?>
              <span style="color:var(--muted)">—</span>
            <?php else: foreach (array_slice($actionsCles, 0, 4) as $a): ?>
              <span class="pastille <?= in_array($a, suivi_actions_contact(), true) ? 'pastille--vert' : 'pastille--gris' ?>"><?= h($a) ?></span>
            <?php endforeach; if (count($actionsCles) > 4): ?>
              <span class="pastille pastille--gris">+<?= count($actionsCles) - 4 ?></span>
            <?php endif; endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($nbPages > 1): ?>
    <div class="pagination">
      <?php for ($i = 1; $i <= $nbPages; $i++): ?>
        <?php if ($i === $page): ?><span><?= $i ?></span>
        <?php else: ?><a href="<?= h(lien(['pg' => $i, 'visite' => null])) ?>"><?= $i ?></a><?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ------------------------------------------------------------ Paramètres -->
<div class="card">
  <details>
    <summary>Paramètres — changer le mot de passe</summary>
    <form method="post" class="champs" style="margin-top:16px" autocomplete="off">
      <input type="hidden" name="jeton" value="<?= h(suivi_jeton()) ?>">
      <div class="champ">
        <label for="p1">Mot de passe actuel</label>
        <input type="password" id="p1" name="mdp_actuel" required>
      </div>
      <div class="champ">
        <label for="p2">Nouveau mot de passe (10 caractères min.)</label>
        <input type="password" id="p2" name="changer_mdp" required minlength="10">
      </div>
      <button class="btn" type="submit">Modifier</button>
    </form>
    <p style="margin-top:14px;font-size:.85rem;color:var(--muted);line-height:1.6">
      Les visites sont enregistrées dans <code>suivi/data/</code>, sur votre hébergement.
      Aucune donnée n'est transmise à un service tiers, et les adresses IP ne sont jamais
      stockées en clair : elles sont remplacées par une empreinte anonyme renouvelée chaque jour.
    </p>
  </details>
</div>

</div>
</body>
</html>
