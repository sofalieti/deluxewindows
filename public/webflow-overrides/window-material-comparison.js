(function () {
  "use strict";

  function setText(root, selector, value) {
    var element = root.querySelector(selector);
    if (element) element.textContent = value;
  }

  function replaceList(list, items) {
    if (!list) return;

    var fragment = document.createDocumentFragment();
    items.forEach(function (item) {
      var listItem = document.createElement("li");
      listItem.textContent = item;
      fragment.appendChild(listItem);
    });
    list.replaceChildren(fragment);
  }

  function initComparison(root) {
    var dataElement = root.querySelector("[data-wmc-data]");
    if (!dataElement) return;

    var payload;
    try {
      payload = JSON.parse(dataElement.textContent);
    } catch (error) {
      return;
    }

    var materials = payload.materials || {};
    var selects = Array.from(root.querySelectorAll("[data-wmc-select]"));
    if (selects.length !== 2) return;

    function updateDisabledOptions() {
      selects.forEach(function (select) {
        var other = selects.find(function (candidate) {
          return candidate !== select;
        });

        Array.from(select.options).forEach(function (option) {
          option.disabled = Boolean(other && option.value === other.value);
        });
      });
    }

    function updateSlot(slot, material) {
      var header = root.querySelector('[data-wmc-header][data-slot="' + slot + '"]');
      if (header) {
        setText(header, "[data-wmc-tagline]", material.tagline);
        setText(header, "[data-wmc-best-for]", material.best_for);

        var link = header.querySelector("[data-wmc-link]");
        if (link) {
          link.href = material.url;
          link.textContent = "View " + material.short_name + " details";
        }
      }

      root.querySelectorAll('[data-wmc-value][data-slot="' + slot + '"]').forEach(function (cell) {
        var key = cell.dataset.key;
        var value = material.values[key];
        if (!value) return;

        setText(cell, "[data-wmc-mobile-name]", material.short_name);
        setText(cell, "[data-wmc-value-label]", value.label);
        setText(cell, "[data-wmc-value-detail]", value.detail);

        var rating = cell.querySelector("[data-wmc-rating]");
        if (rating) {
          rating.setAttribute("aria-label", value.rating + " out of 5: " + value.label);
          Array.from(rating.children).forEach(function (bar, index) {
            bar.classList.toggle("is-filled", index < value.rating);
          });
        }
      });

      var tradeoffs = root.querySelector(
        '[data-wmc-tradeoffs][data-slot="' + slot + '"]'
      );
      if (tradeoffs) {
        setText(tradeoffs, "[data-wmc-tradeoff-name]", material.short_name);
        replaceList(tradeoffs.querySelector("[data-wmc-pros]"), material.pros);
        replaceList(tradeoffs.querySelector("[data-wmc-cons]"), material.cons);
      }
    }

    selects.forEach(function (select) {
      select.dataset.previousValue = select.value;

      select.addEventListener("change", function () {
        var material = materials[select.value];
        var other = selects.find(function (candidate) {
          return candidate !== select;
        });

        if (!material || (other && other.value === select.value)) {
          select.value = select.dataset.previousValue;
          return;
        }

        updateSlot(select.dataset.slot, material);
        select.dataset.previousValue = select.value;
        updateDisabledOptions();
      });
    });

    updateDisabledOptions();
  }

  function initAll() {
    document.querySelectorAll("[data-window-material-comparison]").forEach(initComparison);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAll);
  } else {
    initAll();
  }
})();
