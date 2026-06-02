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
  { img: "/assets/img/realisations/IMG_2009-min-scaled.jpeg", cat: "chauffage-sanitaire", titre: "Salle de bain clé en main", desc: "Baignoire, meuble vasque et douche : rénovation complète de A à Z.", lieu: "Brabant wallon", alt: "Salle de bain moderne avec baignoire et meuble vasque réalisée par Entreprises Wauters" },
  { img: "/assets/img/realisations/IMG_2740-min-scaled.jpeg", cat: "chauffage-sanitaire", titre: "Douche à l'italienne", desc: "Espace douche de plain-pied avec sol imitation bois et finitions soignées.", lieu: "Brabant wallon", alt: "Douche à l'italienne avec sol imitation bois" },
  { img: "/assets/img/realisations/IMG_4260-scaled.jpeg", cat: "plomberie", titre: "Installation sanitaire complète", desc: "Pose des collecteurs et du réseau d'eau sur une construction neuve.", lieu: "Brabant wallon", alt: "Installation des collecteurs et tuyaux sanitaires sur un chantier neuf" },
  { img: "/assets/img/realisations/IMG_5127-min-scaled.jpeg", cat: "amenagement", titre: "Aménagement de combles", desc: "Transformation de combles en pièce de vie : parquet et fenêtre de toit.", lieu: "Brabant wallon", alt: "Aménagement de combles avec parquet et fenêtre de toit" },

  /* ===================== CHAUFFAGE & SANITAIRE ===================== */
  { img: "/assets/img/realisations/2F7648BC-CEA8-4D0F-A127-6DAF84EBC858-min-scaled.jpg", cat: "chauffage-sanitaire", alt: "Installation d'une chaudière avec vase d'expansion par Entreprises Wauters en Brabant wallon" },
  { img: "/assets/img/realisations/IMG_1807-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Chaudière gaz murale installée lors d'une rénovation en Brabant wallon" },
  { img: "/assets/img/realisations/IMG_1926-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Salle de bain en cours de rénovation avec WC et carrelage" },
  { img: "/assets/img/realisations/IMG_1927-scaled.jpeg", cat: "chauffage-sanitaire", alt: "WC suspendu installé dans une salle de bain rénovée" },
  { img: "/assets/img/realisations/IMG_2012-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Salle de bain avec double vasque et grand miroir" },
  { img: "/assets/img/realisations/IMG_2013-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Douche à l'italienne avec paroi vitrée" },
  { img: "/assets/img/realisations/IMG_2319-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Salle de bain en chantier, étanchéité avant pose du carrelage" },
  { img: "/assets/img/realisations/IMG_2481-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Salle de bain avec meuble vasque et sèche-serviettes" },
  { img: "/assets/img/realisations/IMG_2483-scaled.jpeg", cat: "chauffage-sanitaire", alt: "WC avec lave-mains dans une salle d'eau rénovée" },
  { img: "/assets/img/realisations/IMG_3530-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Salle de bain en cours d'aménagement avec carrelage" },
  { img: "/assets/img/realisations/IMG_4152-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Chaudière murale avec vase d'expansion installée au-dessus d'un WC" },
  { img: "/assets/img/realisations/IMG_4923-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Chaufferie avec vase d'expansion et matériel de chauffage" },
  { img: "/assets/img/realisations/IMG_4951-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Salle de bain avec baignoire, radiateur et carrelage marbré" },
  { img: "/assets/img/realisations/IMG_4973-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Adoucisseur d'eau et vase d'expansion installés en cave" },
  { img: "/assets/img/realisations/IMG_5011-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Salle de bain avec meuble vasque et douche, sol en bois" },
  { img: "/assets/img/realisations/IMG_5013-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Pose d'une baignoire sous fenêtre de toit lors d'une rénovation" },
  { img: "/assets/img/realisations/IMG_5069-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Douche à l'italienne avec paroi vitrée, finition soignée" },
  { img: "/assets/img/realisations/IMG_5103-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Chauffe-eau et raccordements installés en cave" },
  { img: "/assets/img/realisations/IMG_5109-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Salle de bain avec lavabo et sol en bois" },
  { img: "/assets/img/realisations/IMG_5497-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Baignoire avec habillage mosaïque dans une salle de bain" },
  { img: "/assets/img/realisations/IMG_5713-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Salle de bain avec meuble vasque et grand miroir" },
  { img: "/assets/img/realisations/IMG_5714-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Grande salle de bain avec double vasque" },
  { img: "/assets/img/realisations/IMG_5715-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Salle de bain en longueur avec meuble vasque" },
  { img: "/assets/img/realisations/IMG_5757-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Salle de bain sous combles avec WC et sèche-serviettes" },
  { img: "/assets/img/realisations/IMG_5760-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Salle de bain avec double vasque et grand miroir" },
  { img: "/assets/img/realisations/IMG_5762-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Douche à l'italienne avec sol en mosaïque en cours de pose" },
  { img: "/assets/img/realisations/IMG_5910-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Chaufferie : installation de chauffage et raccordements" },
  { img: "/assets/img/realisations/IMG_5912-min-scaled.jpeg", cat: "chauffage-sanitaire", alt: "Installation d'une chaudière dans une buanderie" },

  /* ===================== PLOMBERIE ===================== */
  { img: "/assets/img/realisations/IMG_3286-scaled.jpeg", cat: "plomberie", alt: "Travaux de plomberie : évacuations et canalisations en rénovation" },
  { img: "/assets/img/realisations/IMG_3288-scaled.jpeg", cat: "plomberie", alt: "Canalisations d'évacuation en cave lors d'une rénovation" },
  { img: "/assets/img/realisations/IMG_3919-min-scaled.jpeg", cat: "plomberie", alt: "Intervention de plomberie sur chauffe-eau et tuyauterie" },
  { img: "/assets/img/realisations/IMG_4836-scaled.jpeg", cat: "plomberie", alt: "Réseau de tuyauterie en cuivre et laiton au plafond d'une cave" },
  { img: "/assets/img/realisations/IMG_4902-scaled.jpeg", cat: "plomberie", alt: "Pose des conduites d'eau chaude et froide (PER) en rénovation" },
  { img: "/assets/img/realisations/IMG_4905-scaled.jpeg", cat: "plomberie", alt: "Réseau de plomberie PER rouge et bleu sur mur de brique" },
  { img: "/assets/img/realisations/IMG_4909-scaled.jpeg", cat: "plomberie", alt: "Conduites d'eau installées sur mur de brique en cave" },
  { img: "/assets/img/realisations/IMG_5042-scaled.jpeg", cat: "plomberie", alt: "Dépose et reprise de la plomberie sur mur de brique" },
  { img: "/assets/img/realisations/IMG_6278-scaled.jpeg", cat: "plomberie", alt: "Réparation d'une canalisation extérieure en tranchée" },
  { img: "/assets/img/realisations/IMG_6279-scaled.jpeg", cat: "plomberie", alt: "Pose de canalisation d'égouttage en tranchée dans le jardin" },
  { img: "/assets/img/realisations/IMG_6280-scaled.jpeg", cat: "plomberie", alt: "Raccordement d'égout en tranchée extérieure" },
  { img: "/assets/img/realisations/IMG_6281-scaled.jpeg", cat: "plomberie", alt: "Travaux de terrassement pour canalisations dans le jardin" },

  /* ===================== AMÉNAGEMENT ===================== */
  { img: "/assets/img/realisations/IMG_5143-min-scaled.jpeg", cat: "amenagement", alt: "Pose d'un nouveau parquet lors d'une rénovation intérieure" },
  { img: "/assets/img/realisations/IMG_5515-scaled.jpeg", cat: "amenagement", alt: "Évier inox intégré dans un plan de travail de cuisine" },
  { img: "/assets/img/realisations/IMG_6282-scaled.jpeg", cat: "amenagement", alt: "Allée pavée réalisée le long d'une habitation" }

];
