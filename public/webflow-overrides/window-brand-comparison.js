(function () {
  "use strict";

  function setText(root, selector, value) {
    var element = root.querySelector(selector);
    if (element) element.textContent = value;
  }

  function replaceList(list, items) {
    if (!list) return;

    list.textContent = "";
    var fragment = document.createDocumentFragment();
    items.forEach(function (item) {
      var listItem = document.createElement("li");
      listItem.textContent = item;
      fragment.appendChild(listItem);
    });
    list.appendChild(fragment);
  }

  function initComparison(root) {
    var dataElement = root.querySelector("[data-wbc-data]");
    if (!dataElement) return;

    var payload;
    try {
      payload = JSON.parse(dataElement.textContent);
    } catch (error) {
      return;
    }

    var brands = payload.brands || {};
    var selects = Array.from(root.querySelectorAll("[data-wbc-select]"));
    var mobileQuery = window.matchMedia("(max-width: 767px)");
    if (selects.length !== 2) return;

    function updateDisabledOptions() {
      selects.forEach(function (select) {
        var other = selects.find(function (candidate) {
          return candidate !== select;
        });

        Array.from(select.options).forEach(function (option) {
          option.disabled = Boolean(
            !mobileQuery.matches && other && option.value === other.value
          );
        });
      });
    }

    function updateSlot(slot, brand) {
      var header = root.querySelector('[data-wbc-header][data-slot="' + slot + '"]');
      if (header) {
        setText(header, "[data-wbc-tagline]", brand.tagline);
        setText(header, "[data-wbc-best-for]", brand.best_for);

        var detailsLink = header.querySelector("[data-wbc-link]");
        if (detailsLink) {
          detailsLink.href = brand.url;
          detailsLink.textContent = "View " + brand.name;
        }

        var sourceLink = header.querySelector("[data-wbc-source]");
        if (sourceLink && brand.sources && brand.sources[0]) {
          sourceLink.href = brand.sources[0].url;
        }
      }

      root.querySelectorAll('[data-wbc-value][data-slot="' + slot + '"]').forEach(function (cell) {
        var value = brand.values[cell.dataset.key];
        if (!value) return;

        setText(cell, "[data-wbc-value-label]", value.label);
        setText(cell, "[data-wbc-value-detail]", value.detail);

        var rating = cell.querySelector("[data-wbc-rating]");
        if (rating && typeof value.rating === "number") {
          rating.setAttribute("aria-label", value.rating + " out of 5: " + value.label);
          Array.from(rating.children).forEach(function (bar, index) {
            bar.classList.toggle("is-filled", index < value.rating);
          });
        }
      });

      var tradeoffs = root.querySelector(
        '[data-wbc-tradeoffs][data-slot="' + slot + '"]'
      );
      if (tradeoffs) {
        setText(tradeoffs, "[data-wbc-tradeoff-name]", brand.name);
        var tradeoffLink = tradeoffs.querySelector("[data-wbc-tradeoff-link]");
        if (tradeoffLink) {
          tradeoffLink.href = brand.url;
          tradeoffLink.textContent = "View " + brand.name + " details";
        }
        replaceList(tradeoffs.querySelector("[data-wbc-pros]"), brand.pros);
        replaceList(
          tradeoffs.querySelector("[data-wbc-considerations]"),
          brand.considerations
        );
      }
    }

    function normalizeDesktopSelections() {
      if (mobileQuery.matches || selects[0].value !== selects[1].value) return;

      var replacement = Array.from(selects[1].options).find(function (option) {
        return option.value !== selects[0].value;
      });
      if (!replacement || !brands[replacement.value]) return;

      selects[1].value = replacement.value;
      selects[1].dataset.previousValue = replacement.value;
      updateSlot(selects[1].dataset.slot, brands[replacement.value]);
    }

    selects.forEach(function (select) {
      select.dataset.previousValue = select.value;
      select.dataset.appliedValue = select.value;

      function applySelection() {
        var brand = brands[select.value];
        var other = selects.find(function (candidate) {
          return candidate !== select;
        });

        if (
          !brand ||
          (!mobileQuery.matches && other && other.value === select.value)
        ) {
          select.value = select.dataset.previousValue;
          return;
        }

        if (select.dataset.appliedValue !== select.value) {
          updateSlot(select.dataset.slot, brand);
          select.dataset.appliedValue = select.value;
        }

        select.dataset.previousValue = select.value;
        updateDisabledOptions();
      }

      select.addEventListener("input", applySelection);
      select.addEventListener("change", applySelection);
    });

    function handleViewportChange() {
      normalizeDesktopSelections();
      selects.forEach(function (select) {
        select.dataset.appliedValue = select.value;
      });
      updateDisabledOptions();
    }

    if (typeof mobileQuery.addEventListener === "function") {
      mobileQuery.addEventListener("change", handleViewportChange);
    } else if (typeof mobileQuery.addListener === "function") {
      mobileQuery.addListener(handleViewportChange);
    }

    updateDisabledOptions();
  }

  function initAll() {
    document.querySelectorAll("[data-window-brand-comparison]").forEach(initComparison);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAll);
  } else {
    initAll();
  }
})();
