/* =========================================================
   RÉALISATIONS — liste des photos affichées sur /realisations/
   ---------------------------------------------------------
   Chaque photo = un bloc { img, cat, alt, [titre], [desc], [lieu] }.

   - img : chemin de la photo dans /assets/img/realisations/
   - cat : "chauffage-sanitaire" | "plomberie" | "amenagement" | "toiture"
   - alt : description (SEO / accessibilité)
   - titre, desc, lieu : FACULTATIFS (pour mettre une réalisation en avant)

   Pour AJOUTER une photo : dépose le fichier dans /assets/img/realisations/
   puis copie un bloc ci-dessous en adaptant img / cat / alt.
   ========================================================= */

window.REALISATIONS = [

  /* ===================== ⭐ RÉALISATIONS PHARES (affichées en premier) ===================== */
  { img: "/assets/img/realisations/salle-de-bain-02.jpg", cat: "chauffage-sanitaire", titre: "Salle de bain clé en main", desc: "Baignoire, meuble vasque et douche : rénovation complète de A à Z.", lieu: "Brabant wallon", alt: "Salle de bain moderne avec baignoire et meuble vasque réalisée par Entreprises Wauters" },
  { img: "/assets/img/realisations/douche-italienne-02.jpg", cat: "chauffage-sanitaire", titre: "Douche à l'italienne", desc: "Espace douche de plain-pied avec sol imitation bois et finitions soignées.", lieu: "Brabant wallon", alt: "Douche à l'italienne avec sol imitation bois" },
  { img: "/assets/img/realisations/plomberie-collecteurs-01.jpg", cat: "plomberie", titre: "Installation sanitaire complète", desc: "Pose des collecteurs et du réseau d'eau sur une construction neuve.", lieu: "Brabant wallon", alt: "Installation des collecteurs et tuyaux sanitaires sur un chantier neuf" },
  { img: "/assets/img/realisations/amenagement-combles-01.jpg", cat: "amenagement", titre: "Aménagement de combles", desc: "Transformation de combles en pièce de vie : parquet et fenêtre de toit.", lieu: "Brabant wallon", alt: "Aménagement de combles avec parquet et fenêtre de toit" },

  /* ===================== CHAUFFAGE & SANITAIRE ===================== */
  { img: "/assets/img/realisations/chaudiere-01.jpg", cat: "chauffage-sanitaire", alt: "Installation d'une chaudière avec vase d'expansion par Entreprises Wauters en Brabant wallon" },
  { img: "/assets/img/realisations/chaudiere-02.jpg", cat: "chauffage-sanitaire", alt: "Chaudière gaz murale installée lors d'une rénovation en Brabant wallon" },
  { img: "/assets/img/realisations/salle-de-bain-01.jpg", cat: "chauffage-sanitaire", alt: "Salle de bain en cours de rénovation avec WC et carrelage" },
  { img: "/assets/img/realisations/wc-suspendu-01.jpg", cat: "chauffage-sanitaire", alt: "WC suspendu installé dans une salle de bain rénovée" },
  { img: "/assets/img/realisations/salle-de-bain-03.jpg", cat: "chauffage-sanitaire", alt: "Salle de bain avec double vasque et grand miroir" },
  { img: "/assets/img/realisations/douche-italienne-01.jpg", cat: "chauffage-sanitaire", alt: "Douche à l'italienne avec paroi vitrée" },
  { img: "/assets/img/realisations/salle-de-bain-chantier-01.jpg", cat: "chauffage-sanitaire", alt: "Salle de bain en chantier, étanchéité avant pose du carrelage" },
  { img: "/assets/img/realisations/salle-de-bain-04.jpg", cat: "chauffage-sanitaire", alt: "Salle de bain avec meuble vasque et sèche-serviettes" },
  { img: "/assets/img/realisations/wc-lave-mains-01.jpg", cat: "chauffage-sanitaire", alt: "WC avec lave-mains dans une salle d'eau rénovée" },
  { img: "/assets/img/realisations/salle-de-bain-05.jpg", cat: "chauffage-sanitaire", alt: "Salle de bain en cours d'aménagement avec carrelage" },
  { img: "/assets/img/realisations/chaudiere-03.jpg", cat: "chauffage-sanitaire", alt: "Chaudière murale avec vase d'expansion installée au-dessus d'un WC" },
  { img: "/assets/img/realisations/chaufferie-01.jpg", cat: "chauffage-sanitaire", alt: "Chaufferie avec vase d'expansion et matériel de chauffage" },
  { img: "/assets/img/realisations/salle-de-bain-06.jpg", cat: "chauffage-sanitaire", alt: "Salle de bain avec baignoire, radiateur et carrelage marbré" },
  { img: "/assets/img/realisations/adoucisseur-01.jpg", cat: "chauffage-sanitaire", alt: "Adoucisseur d'eau et vase d'expansion installés en cave" },
  { img: "/assets/img/realisations/salle-de-bain-07.jpg", cat: "chauffage-sanitaire", alt: "Salle de bain avec meuble vasque et douche, sol en bois" },
  { img: "/assets/img/realisations/salle-de-bain-08.jpg", cat: "chauffage-sanitaire", alt: "Pose d'une baignoire sous fenêtre de toit lors d'une rénovation" },
  { img: "/assets/img/realisations/douche-italienne-03.jpg", cat: "chauffage-sanitaire", alt: "Douche à l'italienne avec paroi vitrée, finition soignée" },
  { img: "/assets/img/realisations/chauffe-eau-01.jpg", cat: "chauffage-sanitaire", alt: "Chauffe-eau et raccordements installés en cave" },
  { img: "/assets/img/realisations/salle-de-bain-09.jpg", cat: "chauffage-sanitaire", alt: "Salle de bain avec lavabo et sol en bois" },
  { img: "/assets/img/realisations/salle-de-bain-10.jpg", cat: "chauffage-sanitaire", alt: "Baignoire avec habillage mosaïque dans une salle de bain" },
  { img: "/assets/img/realisations/salle-de-bain-11.jpg", cat: "chauffage-sanitaire", alt: "Salle de bain avec meuble vasque et grand miroir" },
  { img: "/assets/img/realisations/salle-de-bain-12.jpg", cat: "chauffage-sanitaire", alt: "Grande salle de bain avec double vasque" },
  { img: "/assets/img/realisations/salle-de-bain-13.jpg", cat: "chauffage-sanitaire", alt: "Salle de bain en longueur avec meuble vasque" },
  { img: "/assets/img/realisations/salle-de-bain-14.jpg", cat: "chauffage-sanitaire", alt: "Salle de bain sous combles avec WC et sèche-serviettes" },
  { img: "/assets/img/realisations/salle-de-bain-15.jpg", cat: "chauffage-sanitaire", alt: "Salle de bain avec double vasque et grand miroir" },
  { img: "/assets/img/realisations/douche-italienne-04.jpg", cat: "chauffage-sanitaire", alt: "Douche à l'italienne avec sol en mosaïque en cours de pose" },
  { img: "/assets/img/realisations/chaufferie-02.jpg", cat: "chauffage-sanitaire", alt: "Chaufferie : installation de chauffage et raccordements" },
  { img: "/assets/img/realisations/chaudiere-04.jpg", cat: "chauffage-sanitaire", alt: "Installation d'une chaudière dans une buanderie" },

  /* ===================== PLOMBERIE ===================== */
  { img: "/assets/img/realisations/plomberie-evacuation-01.jpg", cat: "plomberie", alt: "Travaux de plomberie : évacuations et canalisations en rénovation" },
  { img: "/assets/img/realisations/plomberie-evacuation-02.jpg", cat: "plomberie", alt: "Canalisations d'évacuation en cave lors d'une rénovation" },
  { img: "/assets/img/realisations/plomberie-chauffe-eau-01.jpg", cat: "plomberie", alt: "Intervention de plomberie sur chauffe-eau et tuyauterie" },
  { img: "/assets/img/realisations/plomberie-tuyauterie-01.jpg", cat: "plomberie", alt: "Réseau de tuyauterie en cuivre et laiton au plafond d'une cave" },
  { img: "/assets/img/realisations/plomberie-conduites-01.jpg", cat: "plomberie", alt: "Pose des conduites d'eau chaude et froide (PER) en rénovation" },
  { img: "/assets/img/realisations/plomberie-conduites-02.jpg", cat: "plomberie", alt: "Réseau de plomberie PER rouge et bleu sur mur de brique" },
  { img: "/assets/img/realisations/plomberie-conduites-03.jpg", cat: "plomberie", alt: "Conduites d'eau installées sur mur de brique en cave" },
  { img: "/assets/img/realisations/plomberie-renovation-01.jpg", cat: "plomberie", alt: "Dépose et reprise de la plomberie sur mur de brique" },
  { img: "/assets/img/realisations/plomberie-tranchee-01.jpg", cat: "plomberie", alt: "Réparation d'une canalisation extérieure en tranchée" },
  { img: "/assets/img/realisations/plomberie-tranchee-02.jpg", cat: "plomberie", alt: "Pose de canalisation d'égouttage en tranchée dans le jardin" },
  { img: "/assets/img/realisations/plomberie-tranchee-03.jpg", cat: "plomberie", alt: "Raccordement d'égout en tranchée extérieure" },
  { img: "/assets/img/realisations/plomberie-terrassement-01.jpg", cat: "plomberie", alt: "Travaux de terrassement pour canalisations dans le jardin" },

  /* ===================== AMÉNAGEMENT ===================== */
  { img: "/assets/img/realisations/amenagement-parquet-01.jpg", cat: "amenagement", alt: "Pose d'un nouveau parquet lors d'une rénovation intérieure" },
  { img: "/assets/img/realisations/amenagement-cuisine-01.jpg", cat: "amenagement", alt: "Évier inox intégré dans un plan de travail de cuisine" },
  { img: "/assets/img/realisations/amenagement-allee-pavee-01.jpg", cat: "amenagement", alt: "Allée pavée réalisée le long d'une habitation" }

];
