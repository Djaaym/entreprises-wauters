/* =========================================================
   LISTE DES RÉALISATIONS — c'est le SEUL fichier à modifier
   pour ajouter / retirer des photos sur la page Réalisations.
   ---------------------------------------------------------
   Pour chaque photo, ajoutez un bloc { ... } séparé par une virgule.

   Champs :
   - img   : chemin de la photo (OBLIGATOIRE). Mettez vos fichiers dans
             /assets/img/realisations/  puis indiquez ici le chemin.
   - cat   : catégorie (OBLIGATOIRE) — exactement l'une de :
             "chauffage-sanitaire" | "plomberie" | "amenagement" | "toiture"
   - alt   : description courte de la photo (OBLIGATOIRE, bon pour le SEO
             et l'accessibilité). Ex : "Salle de bain rénovée à Wavre".
   - titre : titre affiché sous la photo (FACULTATIF, laissez "" si inutile).
   - desc  : petite description (FACULTATIF).
   - lieu  : commune (FACULTATIF), affichée avec 📍.

   ⚠️ Noms de fichiers : sans accents ni espaces.
      Bon : salle-bain-01.jpg   —   À éviter : Salle de bain (1).JPG
   ========================================================= */

window.REALISATIONS = [

  /* ----- CHAUFFAGE & SANITAIRE ----- */
  {
    img: "/assets/img/realisations/salle-bain-01.jpg",
    cat: "chauffage-sanitaire",
    alt: "Salle de bain moderne avec douche à l'italienne réalisée à Chaumont-Gistoux",
    titre: "Salle de bain complète",
    desc: "Douche à l'italienne, meuble double vasque et WC suspendu.",
    lieu: "Chaumont-Gistoux"
  },
  {
    img: "/assets/img/realisations/pcompe-chaleur-01.jpg",
    cat: "chauffage-sanitaire",
    alt: "Pompe à chaleur air/eau installée en Brabant wallon",
    titre: "Installation pompe à chaleur",
    desc: "Remplacement d'une ancienne chaudière par une PAC air/eau.",
    lieu: "Grez-Doiceau"
  },

  /* ----- PLOMBERIE ----- */
  {
    img: "/assets/img/realisations/plomberie-01.jpg",
    cat: "plomberie",
    alt: "Rénovation complète du réseau d'eau d'une maison à Wavre",
    titre: "Rénovation réseau d'eau",
    desc: "Remplacement de la tuyauterie et de la robinetterie.",
    lieu: "Wavre"
  },

  /* ----- AMÉNAGEMENT ----- */
  {
    img: "/assets/img/realisations/amenagement-01.jpg",
    cat: "amenagement",
    alt: "Ouverture d'un espace de vie après suppression de cloisons",
    titre: "Ouverture d'un espace de vie",
    desc: "Nouveaux sols et finitions pour un séjour lumineux.",
    lieu: "Ottignies–LLN"
  },

  /* ----- TOITURE ----- */
  {
    img: "/assets/img/realisations/toiture-01.jpg",
    cat: "toiture",
    alt: "Rénovation de toiture avec nouvelle couverture et zinguerie à Walhain",
    titre: "Rénovation de toiture",
    desc: "Remplacement de la couverture, isolation et zinguerie.",
    lieu: "Walhain"
  }

  /* ➕ Pour ajouter une photo, copiez un bloc ci-dessus, mettez une virgule
       après le bloc précédent, et adaptez img / cat / alt.
       Exemple minimal (sans titre ni description) :
  ,{
    img: "/assets/img/realisations/toiture-02.jpg",
    cat: "toiture",
    alt: "Pose de gouttières en zinc à Perwez"
  }
  */

];
