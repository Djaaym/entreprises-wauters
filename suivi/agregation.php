<?php
/**
 * Entreprises Wauters — reconstruction des visites à partir des événements
 * bruts, et calcul des statistiques affichées dans le tableau de bord.
 */

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

/** Une visite = tous les événements partageant le même identifiant de session. */
function suivi_construire_visites(array $evenements): array
{
    $visites = [];

    foreach ($evenements as $e) {
        $sid = (string) ($e['sid'] ?? '');
        if ($sid === '') {
            continue;
        }

        if (!isset($visites[$sid])) {
            $visites[$sid] = [
                'sid'        => $sid,
                'vid'        => (string) ($e['vid'] ?? ''),
                'debut'      => (int) $e['t'],
                'fin'        => (int) $e['t'],
                'source'     => (string) ($e['src'] ?? 'Accès direct'),
                'canal'      => (string) ($e['can'] ?? 'direct'),
                'campagne'   => (string) ($e['cmp'] ?? ''),
                'referrer'   => (string) ($e['ref'] ?? ''),
                'appareil'   => (string) ($e['dev'] ?? ''),
                'os'         => (string) ($e['os'] ?? ''),
                'navigateur' => (string) ($e['br'] ?? ''),
                'langue'     => (string) ($e['lang'] ?? ''),
                'fuseau'     => (string) ($e['tz'] ?? ''),
                'nouveau'    => (int) ($e['nouv'] ?? 0),
                'pages'      => [],
                'actions'    => [],
                'duree'      => 0,
                'scroll'     => 0,
                'entree'     => (string) ($e['p'] ?? '/'),
                'sortie'     => (string) ($e['p'] ?? '/'),
            ];
        }

        $v = &$visites[$sid];
        $v['fin']    = max($v['fin'], (int) $e['t']);
        $v['sortie'] = (string) ($e['p'] ?? $v['sortie']);

        // La source d'acquisition est celle de l'entrée sur le site : une
        // navigation interne ne doit jamais l'écraser.
        if (($e['can'] ?? '') !== 'interne' && $v['canal'] === 'interne') {
            $v['source']   = (string) ($e['src'] ?? $v['source']);
            $v['canal']    = (string) ($e['can'] ?? $v['canal']);
            $v['referrer'] = (string) ($e['ref'] ?? $v['referrer']);
        }

        $type = (string) ($e['typ'] ?? '');

        if ($type === 'pageview') {
            $v['pages'][] = [
                't'     => (int) $e['t'],
                'p'     => (string) ($e['p'] ?? '/'),
                'titre' => (string) ($e['ti'] ?? ''),
            ];
        } elseif ($type === 'action') {
            $v['actions'][] = [
                't'   => (int) $e['t'],
                'act' => (string) ($e['act'] ?? ''),
                'lbl' => (string) ($e['lbl'] ?? ''),
                'p'   => (string) ($e['p'] ?? '/'),
            ];
        } elseif ($type === 'fin') {
            $v['duree']  += (int) ($e['dur'] ?? 0);
            $v['scroll']  = max($v['scroll'], (int) ($e['scroll'] ?? 0));
        }

        unset($v);
    }

    foreach ($visites as $sid => $v) {
        // Si l'événement de fin s'est perdu (fermeture brutale de l'onglet),
        // on retombe sur l'écart entre le premier et le dernier signal.
        if ($v['duree'] === 0) {
            $visites[$sid]['duree'] = max(0, $v['fin'] - $v['debut']);
        }
        $contact = array_intersect(
            array_column($v['actions'], 'act'),
            suivi_actions_contact()
        );
        $visites[$sid]['contact'] = count($contact) > 0;
        // Rebond : une seule page, aucune prise de contact, moins de 15 s.
        $visites[$sid]['rebond'] = count($v['pages']) <= 1
            && !$visites[$sid]['contact']
            && $visites[$sid]['duree'] < 15;
    }

    uasort($visites, static fn(array $a, array $b): int => $b['debut'] <=> $a['debut']);

    return $visites;
}

/** Applique les filtres du tableau de bord à la liste des visites. */
function suivi_filtrer_visites(array $visites, array $f): array
{
    return array_filter($visites, static function (array $v) use ($f): bool {
        if ($f['source'] !== '' && $v['source'] !== $f['source']) {
            return false;
        }
        if ($f['canal'] !== '' && $v['canal'] !== $f['canal']) {
            return false;
        }
        if ($f['appareil'] !== '' && $v['appareil'] !== $f['appareil']) {
            return false;
        }
        if ($f['page'] !== '') {
            $chemins = array_column($v['pages'], 'p');
            if (!in_array($f['page'], $chemins, true)) {
                return false;
            }
        }
        if ($f['action'] !== '') {
            if (!in_array($f['action'], array_column($v['actions'], 'act'), true)) {
                return false;
            }
        }
        if ($f['contact'] === '1' && !$v['contact']) {
            return false;
        }
        return true;
    });
}

/** Indicateurs clés sur un ensemble de visites. */
function suivi_indicateurs(array $visites): array
{
    $nb          = count($visites);
    $pagesVues   = 0;
    $dureeTotale = 0;
    $rebonds     = 0;
    $contacts    = 0;
    $nouveaux    = 0;
    $visiteurs   = [];

    foreach ($visites as $v) {
        $pagesVues   += count($v['pages']);
        $dureeTotale += $v['duree'];
        $rebonds     += $v['rebond'] ? 1 : 0;
        $contacts    += $v['contact'] ? 1 : 0;
        $nouveaux    += $v['nouveau'] ? 1 : 0;
        if ($v['vid'] !== '') {
            $visiteurs[$v['vid']] = true;
        }
    }

    return [
        'visites'       => $nb,
        'visiteurs'     => count($visiteurs),
        'pages_vues'    => $pagesVues,
        'duree_moyenne' => $nb ? (int) round($dureeTotale / $nb) : 0,
        'duree_totale'  => $dureeTotale,
        'taux_rebond'   => $nb ? (int) round(($rebonds / $nb) * 100) : 0,
        'contacts'      => $contacts,
        'taux_contact'  => $nb ? round(($contacts / $nb) * 100, 1) : 0.0,
        'nouveaux'      => $nouveaux,
        'pages_visite'  => $nb ? round($pagesVues / $nb, 1) : 0.0,
    ];
}

/** Comptage trié décroissant sur une clé de visite. */
function suivi_repartition(array $visites, string $cle, int $limite = 0): array
{
    $c = [];
    foreach ($visites as $v) {
        $k = (string) ($v[$cle] ?? '');
        if ($k === '') {
            $k = '(inconnu)';
        }
        $c[$k] = ($c[$k] ?? 0) + 1;
    }
    arsort($c);
    return $limite > 0 ? array_slice($c, 0, $limite, true) : $c;
}

/** Pages les plus vues, avec temps moyen passé dessus. */
function suivi_top_pages(array $visites, int $limite = 15): array
{
    $stats = [];
    foreach ($visites as $v) {
        $nbPages = max(1, count($v['pages']));
        foreach ($v['pages'] as $p) {
            $k = $p['p'];
            if (!isset($stats[$k])) {
                $stats[$k] = ['vues' => 0, 'visites' => 0, 'titre' => $p['titre'], 'duree' => 0];
            }
            $stats[$k]['vues']++;
            $stats[$k]['duree'] += (int) round($v['duree'] / $nbPages);
        }
        foreach (array_unique(array_column($v['pages'], 'p')) as $k) {
            $stats[$k]['visites']++;
        }
    }
    foreach ($stats as $k => $s) {
        $stats[$k]['duree_moy'] = $s['vues'] ? (int) round($s['duree'] / $s['vues']) : 0;
    }
    uasort($stats, static fn(array $a, array $b): int => $b['vues'] <=> $a['vues']);
    return array_slice($stats, 0, $limite, true);
}

/** Toutes les actions réalisées, comptées par type. */
function suivi_top_actions(array $visites): array
{
    $c = [];
    foreach ($visites as $v) {
        foreach ($v['actions'] as $a) {
            $k = $a['act'];
            if (!isset($c[$k])) {
                $c[$k] = ['nb' => 0, 'visites' => []];
            }
            $c[$k]['nb']++;
            $c[$k]['visites'][$v['sid']] = true;
        }
    }
    foreach ($c as $k => $v) {
        $c[$k]['nb_visites'] = count($v['visites']);
        unset($c[$k]['visites']);
    }
    uasort($c, static fn(array $a, array $b): int => $b['nb'] <=> $a['nb']);
    return $c;
}

/** Nombre de visites par jour sur l'intervalle, jours vides inclus. */
function suivi_par_jour(array $visites, string $du, string $au): array
{
    $serie = [];
    $jour  = $du;
    while ($jour <= $au) {
        $serie[$jour] = ['visites' => 0, 'contacts' => 0, 'pages' => 0];
        $jour = date('Y-m-d', strtotime($jour . ' +1 day'));
    }
    foreach ($visites as $v) {
        $j = date('Y-m-d', $v['debut']);
        if (isset($serie[$j])) {
            $serie[$j]['visites']++;
            $serie[$j]['pages'] += count($v['pages']);
            if ($v['contact']) {
                $serie[$j]['contacts']++;
            }
        }
    }
    return $serie;
}

/** Répartition des visites sur les 24 heures de la journée. */
function suivi_par_heure(array $visites): array
{
    $h = array_fill(0, 24, 0);
    foreach ($visites as $v) {
        $h[(int) date('G', $v['debut'])]++;
    }
    return $h;
}
