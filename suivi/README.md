# Suivi d'audience — Entreprises Wauters

Tableau de bord privé qui montre, jour par jour, **d'où viennent les visiteurs,
combien de temps ils restent et ce qu'ils font sur le site**.

Tout est hébergé sur votre propre serveur : aucune donnée n'est envoyée à
Google Analytics ni à un autre service tiers.

## Mise en service

1. Envoyez le site sur l'hébergement comme d'habitude (le dossier `suivi/` inclus).
2. Ouvrez **https://entreprises-wauters.be/suivi/** dans votre navigateur.
3. À la première visite, la page vous demande de **choisir votre mot de passe**.
   Faites-le tout de suite après la mise en ligne : tant que le mot de passe
   n'est pas défini, la page est accessible à qui connaît l'adresse.
4. C'est terminé. Les statistiques se remplissent au fil des visites.

Le mot de passe se change ensuite depuis la page, section « Paramètres ».

## Ce que vous voyez

| Indicateur | Signification |
|---|---|
| **Visites** | Nombre de sessions. Une session se termine après 30 min d'inactivité. |
| **Visiteurs** | Personnes différentes (un même visiteur qui revient est compté une fois). |
| **Temps moyen** | Temps réellement passé, onglet ouvert et visible — pas le temps d'affichage. |
| **Prises de contact** | Visites où le visiteur a appelé, écrit, cliqué sur « devis » ou envoyé le formulaire. |
| **Taux de rebond** | Visites d'une seule page, moins de 15 s, sans aucun contact. |

**Filtres disponibles** : période (jour, 7 / 30 / 90 jours, dates au choix),
source, canal, appareil, page visitée, action réalisée, et « uniquement les
visites avec prise de contact ».

Cliquez sur une ligne du tableau « Visites détaillées » pour voir le **parcours
complet** d'un visiteur : chaque page consultée et chaque action, minute par minute.
Le bouton **Export CSV** télécharge la sélection courante pour Excel.

## Actions mesurées

Clic sur le numéro de téléphone · clic sur l'e-mail · clic sur un bouton
« devis » · formulaire commencé · formulaire envoyé (ou en échec) · photo
agrandie · filtre des réalisations · lien vers un site externe · ouverture du
menu mobile · profondeur de lecture (25 / 50 / 75 / 100 %).

## Vie privée

- Les adresses IP **ne sont jamais enregistrées** : elles sont remplacées par une
  empreinte anonyme, recalculée avec un sel différent chaque jour.
- Aucun cookie publicitaire, aucun partage avec un tiers.
- Le choix « Ne pas me pister » (Do Not Track) du navigateur est respecté.
- Les robots d'indexation sont exclus des statistiques.

Cette mesure d'audience anonyme n'exige donc pas de bandeau de consentement,
mais mentionnez-la dans vos mentions légales si vous le souhaitez.

## Organisation des fichiers

```
suivi/
├── index.php        tableau de bord (page protégée)
├── collect.php      reçoit les événements du site
├── lib.php          fonctions communes
├── auth.php         connexion et mot de passe
├── agregation.php   calcul des statistiques
├── .htaccess        interdit l'accès direct aux données
└── data/            créé automatiquement — visites, sel, mot de passe
```

Le dossier `data/` est **volontairement exclu de Git** : il appartient au serveur.
Sauvegardez-le si vous tenez à conserver l'historique des visites.

## Prérequis

PHP 8.0 ou plus (disponible par défaut sur l'hébergement Hostinger du site).
Aucune base de données à créer.
