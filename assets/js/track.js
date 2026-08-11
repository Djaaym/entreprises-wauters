/* Entreprises Wauters — suivi d'audience interne (sans cookie tiers).
   Mesure : pages vues, origine du visiteur, appareil, temps réellement passé,
   profondeur de lecture et actions concrètes (appel, devis, formulaire…).
   Les données partent vers suivi/collect.php, hébergé sur le même domaine. */
(function () {
  "use strict";

  // Racine du site déduite de l'URL de ce script (.../assets/js/track.js) :
  // fonctionne depuis n'importe quelle profondeur de page.
  var BASE = (function () {
    var s = document.currentScript;
    if (!s) {
      var all = document.getElementsByTagName("script");
      s = all[all.length - 1];
    }
    var src = s ? s.src : "";
    var i = src.indexOf("assets/js/");
    return i === -1 ? "" : src.slice(0, i);
  })();

  var ENDPOINT = BASE + "suivi/collect.php";

  // Pas de collecte hors ligne (aperçu local) ni si le visiteur a activé
  // « Do Not Track » : dans ces cas le script ne fait rien.
  if (location.protocol === "file:" || navigator.doNotTrack === "1" || window.doNotTrack === "1") {
    return;
  }

  // ---------- Identifiants ----------
  function uid() {
    try {
      if (window.crypto && crypto.randomUUID) return crypto.randomUUID().replace(/-/g, "").slice(0, 20);
    } catch (e) { /* ignore */ }
    return (Date.now().toString(36) + Math.random().toString(36).slice(2, 10));
  }

  function store(api, cle, dureeMs) {
    try {
      var brut = window[api].getItem(cle);
      if (brut) {
        var o = JSON.parse(brut);
        if (o && o.id && (!dureeMs || Date.now() - o.t < dureeMs)) {
          o.t = Date.now();
          window[api].setItem(cle, JSON.stringify(o));
          return { id: o.id, nouveau: false };
        }
      }
      var id = uid();
      window[api].setItem(cle, JSON.stringify({ id: id, t: Date.now() }));
      return { id: id, nouveau: true };
    } catch (e) {
      // Navigation privée ou stockage refusé : identifiant éphémère.
      return { id: uid(), nouveau: true };
    }
  }

  var visiteur = store("localStorage", "ew_vid", 0);              // durable
  var session  = store("sessionStorage", "ew_sid", 30 * 60 * 1000); // 30 min

  // ---------- Contexte de la page ----------
  var params = new URLSearchParams(location.search);

  function chemin() {
    var p = location.pathname.replace(/index\.html$/, "");
    return p === "" ? "/" : p;
  }

  function contexteBase() {
    return {
      sid: session.id,
      vid: visiteur.id,
      p: chemin(),
      ti: document.title,
      ref: document.referrer || "",
      utm_source: params.get("utm_source") || "",
      utm_medium: params.get("utm_medium") || "",
      utm_campaign: params.get("utm_campaign") || "",
      lang: navigator.language || "",
      tz: (function () {
        try { return Intl.DateTimeFormat().resolvedOptions().timeZone || ""; } catch (e) { return ""; }
      })(),
      vw: window.innerWidth || 0,
      nouv: visiteur.nouveau ? 1 : 0
    };
  }

  function envoyer(charge) {
    var data = contexteBase();
    for (var k in charge) {
      if (Object.prototype.hasOwnProperty.call(charge, k)) data[k] = charge[k];
    }
    var corps = JSON.stringify(data);
    try {
      if (navigator.sendBeacon) {
        // Type texte : évite une requête CORS préalable, qui serait perdue
        // au moment où l'onglet se ferme.
        navigator.sendBeacon(ENDPOINT, new Blob([corps], { type: "text/plain;charset=UTF-8" }));
        return;
      }
    } catch (e) { /* on retombe sur fetch */ }
    try {
      fetch(ENDPOINT, { method: "POST", body: corps, keepalive: true, headers: { "Content-Type": "text/plain;charset=UTF-8" } });
    } catch (e) { /* mesure best effort : jamais bloquante */ }
  }

  function action(nom, libelle) {
    envoyer({ typ: "action", act: nom, lbl: libelle || "" });
  }

  // ---------- Page vue ----------
  envoyer({ typ: "pageview" });

  // ---------- Temps réellement passé (onglet visible uniquement) ----------
  var actifDepuis = document.visibilityState === "visible" ? Date.now() : 0;
  var secondes = 0;

  function cumuler() {
    if (actifDepuis) {
      secondes += Math.round((Date.now() - actifDepuis) / 1000);
      actifDepuis = 0;
    }
  }

  document.addEventListener("visibilitychange", function () {
    if (document.visibilityState === "visible") {
      if (!actifDepuis) actifDepuis = Date.now();
    } else {
      cumuler();
    }
  });

  // ---------- Profondeur de lecture ----------
  var scrollMax = 0;
  var paliers = { 25: false, 50: false, 75: false, 100: false };

  function mesurerScroll() {
    var doc = document.documentElement;
    var hauteur = Math.max(doc.scrollHeight, document.body ? document.body.scrollHeight : 0);
    var vu = (window.scrollY || doc.scrollTop || 0) + window.innerHeight;
    var pct = hauteur > 0 ? Math.min(100, Math.round((vu / hauteur) * 100)) : 0;
    if (pct > scrollMax) scrollMax = pct;
    [25, 50, 75, 100].forEach(function (seuil) {
      if (!paliers[seuil] && scrollMax >= seuil) {
        paliers[seuil] = true;
        action("scroll_" + seuil);
      }
    });
  }

  var tempoScroll = null;
  window.addEventListener("scroll", function () {
    if (tempoScroll) return;
    tempoScroll = setTimeout(function () { tempoScroll = null; mesurerScroll(); }, 250);
  }, { passive: true });
  mesurerScroll();

  // ---------- Fin de visite ----------
  var finEnvoyee = false;
  function envoyerFin() {
    if (finEnvoyee) return;
    finEnvoyee = true;
    cumuler();
    envoyer({ typ: "fin", dur: secondes, scroll: scrollMax });
  }
  window.addEventListener("pagehide", envoyerFin);
  document.addEventListener("visibilitychange", function () {
    if (document.visibilityState === "hidden") envoyerFin();
  });

  // ---------- Actions du visiteur ----------
  var hote = location.hostname;

  document.addEventListener("click", function (e) {
    var cible = e.target && e.target.closest ? e.target : null;
    if (!cible) return;

    var lien = cible.closest("a[href]");
    if (lien) {
      var href = lien.getAttribute("href") || "";
      var texte = (lien.textContent || "").trim().slice(0, 120);

      if (/^tel:/i.test(href)) {
        action("appel_telephone", texte || href.replace(/^tel:/i, ""));
      } else if (/^mailto:/i.test(href)) {
        action("email", texte || href.replace(/^mailto:/i, ""));
      } else if (/^https?:/i.test(href) && lien.hostname && lien.hostname !== hote) {
        action("lien_sortant", lien.hostname + (lien.pathname || ""));
      } else if (/contact\/?(index\.html)?$/i.test(href.split("#")[0]) || /devis/i.test(texte)) {
        action("cta_devis", texte || "Devis");
      } else if (/google\.[a-z.]+\/maps|maps\.app|itin/i.test(href)) {
        action("itineraire", texte || href);
      }
      return;
    }

    if (cible.closest(".filter-btn")) {
      var b = cible.closest(".filter-btn");
      action("filtre_realisations", (b.textContent || "").trim().slice(0, 60));
    } else if (cible.closest(".burger")) {
      action("menu_mobile");
    } else if (cible.closest("img.media-photo") || cible.closest(".project__thumb")) {
      var im = cible.closest("img.media-photo") || cible.querySelector("img");
      action("photo_agrandie", im && im.alt ? im.alt.slice(0, 120) : "");
    }
  }, true);

  // Formulaire de devis : début de saisie, puis résultat de l'envoi.
  var formulaire = document.getElementById("devis-form");
  if (formulaire) {
    var demarre = false;
    formulaire.addEventListener("input", function () {
      if (demarre) return;
      demarre = true;
      action("formulaire_ouvert");
    }, { once: false });

    formulaire.addEventListener("submit", function () {
      var statut = formulaire.querySelector(".form__status");
      if (!statut) return;
      // Le statut est mis à jour par main.js une fois la réponse reçue.
      var observateur = new MutationObserver(function () {
        if (statut.classList.contains("form__status--success")) {
          action("formulaire_envoye", "Demande de devis");
          observateur.disconnect();
        } else if (statut.classList.contains("form__status--error")) {
          action("formulaire_erreur", (statut.textContent || "").slice(0, 120));
          observateur.disconnect();
        }
      });
      observateur.observe(statut, { attributes: true, childList: true, subtree: true });
      setTimeout(function () { observateur.disconnect(); }, 30000);
    });
  }
})();
