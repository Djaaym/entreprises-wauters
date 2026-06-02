/* Entreprises Wauters — interactions minimales */
(function () {
  "use strict";

  // Menu burger mobile
  var burger = document.querySelector(".burger");
  var nav = document.getElementById("primary-nav");
  if (burger && nav) {
    burger.addEventListener("click", function () {
      var open = nav.classList.toggle("is-open");
      burger.setAttribute("aria-expanded", open ? "true" : "false");
    });
    // ferme le menu après un clic sur un lien
    nav.querySelectorAll("a").forEach(function (a) {
      a.addEventListener("click", function () {
        nav.classList.remove("is-open");
        burger.setAttribute("aria-expanded", "false");
      });
    });
  }

  // Ombre du header au scroll
  var header = document.querySelector(".header");
  if (header) {
    var onScroll = function () {
      header.classList.toggle("is-scrolled", window.scrollY > 8);
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  // Année dynamique dans le footer
  var y = document.getElementById("year");
  if (y) y.textContent = new Date().getFullYear();

  // ---------- Formulaire de devis (Web3Forms) ----------
  var devisForm = document.getElementById("devis-form");
  if (devisForm) {
    var status = devisForm.querySelector(".form__status");
    var submitBtn = devisForm.querySelector('button[type="submit"]');

    function setStatus(msg, type) {
      if (!status) return;
      status.textContent = msg;
      status.className = "form__status is-visible form__status--" + type;
    }

    devisForm.addEventListener("submit", function (e) {
      e.preventDefault();

      var key = devisForm.querySelector('[name="access_key"]');
      if (key && key.value.indexOf("VOTRE_CLE") === 0) {
        setStatus("Le formulaire n'est pas encore configuré (clé Web3Forms manquante). Écrivez-nous à info@entreprises-wauters.be.", "error");
        return;
      }

      var data = new FormData(devisForm);
      var original = submitBtn ? submitBtn.textContent : "";
      if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = "Envoi en cours…"; }
      setStatus("Envoi en cours…", "pending");

      fetch("https://api.web3forms.com/submit", {
        method: "POST",
        headers: { "Accept": "application/json" },
        body: data
      })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
        .then(function (res) {
          if (res.ok && res.j.success) {
            devisForm.reset();
            setStatus("✓ Merci ! Votre demande a bien été envoyée. Nous vous recontactons rapidement.", "success");
          } else {
            setStatus("Une erreur est survenue. Réessayez ou appelez-nous au 0472 64 06 45.", "error");
          }
        })
        .catch(function () {
          setStatus("Connexion impossible. Vérifiez votre réseau ou appelez-nous au 0472 64 06 45.", "error");
        })
        .finally(function () {
          if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = original; }
        });
    });
  }

  // ---------- Portfolio (page Réalisations) ----------
  var portfolio = document.getElementById("portfolio");
  if (portfolio) {
    var TAGS = {
      "chauffage-sanitaire": "Chauffage & Sanitaire",
      "plomberie": "Plomberie",
      "amenagement": "Aménagement",
      "toiture": "Toiture"
    };
    var empty = document.querySelector(".portfolio__empty");
    var data = Array.isArray(window.REALISATIONS) ? window.REALISATIONS : [];

    function esc(s) {
      return String(s == null ? "" : s)
        .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }

    function render() {
      portfolio.innerHTML = data.map(function (it, i) {
        var tag = TAGS[it.cat] || "";
        var body = "";
        if (tag) body += '<span class="project__tag">' + esc(tag) + "</span>";
        if (it.titre) body += "<h3>" + esc(it.titre) + "</h3>";
        if (it.desc) body += "<p>" + esc(it.desc) + "</p>";
        if (it.lieu) body += '<span class="project__meta">📍 ' + esc(it.lieu) + "</span>";
        return '<article class="project" data-category="' + esc(it.cat) + '">' +
            '<button class="project__thumb" type="button" data-index="' + i + '" aria-label="Agrandir : ' + esc(it.alt) + '">' +
              '<img class="project__img" src="' + esc(it.img) + '" alt="' + esc(it.alt) + '" loading="lazy" width="600" height="450">' +
            "</button>" +
            (body ? '<div class="project__body">' + body + "</div>" : "") +
          "</article>";
      }).join("");

      // Image manquante -> placeholder propre (au lieu d'une image cassée)
      portfolio.querySelectorAll(".project__img").forEach(function (img) {
        img.addEventListener("error", function () {
          var ph = document.createElement("div");
          ph.className = "project__img project__img--missing";
          ph.textContent = "Photo à ajouter";
          img.replaceWith(ph);
        });
      });

      if (empty) empty.classList.toggle("is-visible", data.length === 0);
    }

    // Filtres (délégation : survit aux re-rendus de la grille)
    var filters = document.querySelector(".filters");
    if (filters) {
      var buttons = filters.querySelectorAll(".filter-btn");
      filters.addEventListener("click", function (e) {
        var btn = e.target.closest(".filter-btn");
        if (!btn) return;
        var cat = btn.getAttribute("data-filter");
        buttons.forEach(function (b) {
          b.setAttribute("aria-pressed", b === btn ? "true" : "false");
        });
        var shown = 0;
        portfolio.querySelectorAll(".project").forEach(function (p) {
          var match = cat === "all" || p.getAttribute("data-category") === cat;
          p.style.display = match ? "" : "none";
          if (match) shown++;
        });
        if (empty) empty.classList.toggle("is-visible", shown === 0);
      });
    }

    // ---------- Lightbox ----------
    var lb = document.createElement("div");
    lb.className = "lightbox";
    lb.setAttribute("aria-hidden", "true");
    lb.innerHTML =
      '<button class="lightbox__close" type="button" aria-label="Fermer">✕</button>' +
      '<button class="lightbox__nav lightbox__prev" type="button" aria-label="Précédent">‹</button>' +
      '<figure class="lightbox__figure">' +
        '<img class="lightbox__img" src="" alt="">' +
        '<figcaption class="lightbox__caption"></figcaption>' +
      "</figure>" +
      '<button class="lightbox__nav lightbox__next" type="button" aria-label="Suivant">›</button>';
    document.body.appendChild(lb);

    var lbImg = lb.querySelector(".lightbox__img");
    var lbCap = lb.querySelector(".lightbox__caption");
    var current = 0;

    function visibleIndexes() {
      var arr = [];
      portfolio.querySelectorAll(".project").forEach(function (p, i) {
        if (p.style.display !== "none") arr.push(i);
      });
      return arr;
    }
    function show(i) {
      var it = data[i];
      if (!it) return;
      current = i;
      lbImg.src = it.img;
      lbImg.alt = it.alt || "";
      lbCap.textContent = [it.titre || TAGS[it.cat] || "", it.lieu].filter(Boolean).join(" — ");
    }
    function open(i) {
      show(i);
      lb.classList.add("is-open");
      lb.setAttribute("aria-hidden", "false");
      document.body.style.overflow = "hidden";
    }
    function close() {
      lb.classList.remove("is-open");
      lb.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
    }
    function step(dir) {
      var vis = visibleIndexes();
      if (!vis.length) return;
      var pos = vis.indexOf(current);
      pos = (pos + dir + vis.length) % vis.length;
      show(vis[pos]);
    }

    portfolio.addEventListener("click", function (e) {
      var thumb = e.target.closest(".project__thumb");
      if (!thumb) return;
      open(parseInt(thumb.getAttribute("data-index"), 10));
    });
    lb.querySelector(".lightbox__close").addEventListener("click", close);
    lb.querySelector(".lightbox__prev").addEventListener("click", function () { step(-1); });
    lb.querySelector(".lightbox__next").addEventListener("click", function () { step(1); });
    lb.addEventListener("click", function (e) { if (e.target === lb) close(); });
    document.addEventListener("keydown", function (e) {
      if (!lb.classList.contains("is-open")) return;
      if (e.key === "Escape") close();
      else if (e.key === "ArrowLeft") step(-1);
      else if (e.key === "ArrowRight") step(1);
    });

    render();
  }
})();
