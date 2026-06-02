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

  // Filtre du portfolio (page Réalisations)
  var filters = document.querySelector(".filters");
  var portfolio = document.querySelector(".portfolio");
  if (filters && portfolio) {
    var projects = portfolio.querySelectorAll(".project");
    var empty = document.querySelector(".portfolio__empty");
    var buttons = filters.querySelectorAll(".filter-btn");

    filters.addEventListener("click", function (e) {
      var btn = e.target.closest(".filter-btn");
      if (!btn) return;
      var cat = btn.getAttribute("data-filter");

      buttons.forEach(function (b) {
        b.setAttribute("aria-pressed", b === btn ? "true" : "false");
      });

      var shown = 0;
      projects.forEach(function (p) {
        var match = cat === "all" || p.getAttribute("data-category") === cat;
        p.style.display = match ? "" : "none";
        if (match) shown++;
      });

      if (empty) empty.classList.toggle("is-visible", shown === 0);
    });
  }
})();
