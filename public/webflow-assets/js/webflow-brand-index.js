(() => {
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const $ = (sel, root = document) => root.querySelector(sel);

  const form = document.querySelector('[fs-list-element="filters"]');
  if (!form) return;

  const list = document.querySelector('[fs-list-element="list"]');
  const dropdowns = $$(".brand-filters_dropdown", form);
  const OPEN_CLASS = "is-open";
  const PRICE_FIELDS = ["price1", "price2", "price3", "price4", "price5"];

  form.setAttribute("autocomplete", "off");
  form.addEventListener("submit", (e) => e.preventDefault());
  if (location.search) history.replaceState(null, "", location.pathname + location.hash);

  const syncWebflowUI = () => {
    $$("label.w-checkbox", form).forEach((lbl) => {
      const inp = $('input[type="checkbox"][fs-list-field]', lbl);
      const custom = $(".w-checkbox-input", lbl);
      if (!inp || !custom) return;
      custom.classList.toggle("w--redirected-checked", inp.checked);
    });
  };

  const syncOnOpen = () => {
    syncWebflowUI();
    requestAnimationFrame(syncWebflowUI);
    setTimeout(syncWebflowUI, 0);
  };

  const forceAllUnchecked = () => {
    $$('input[type="checkbox"][fs-list-field]', form).forEach((inp) => {
      inp.checked = false;
      inp.removeAttribute("checked");
    });
    syncWebflowUI();
  };

  const closeAll = (except = null) => {
    dropdowns.forEach((dd) => {
      if (except && dd === except) return;
      dd.classList.remove(OPEN_CLASS);
      const listEl = $(".brand_dropdown-list", dd);
      if (listEl) listEl.style.display = "none";
    });
  };

  const toggleDropdown = (dd) => {
    const listEl = $(".brand_dropdown-list", dd);
    if (!listEl) return;

    const isOpen = dd.classList.contains(OPEN_CLASS);

    if (isOpen) {
      dd.classList.remove(OPEN_CLASS);
      listEl.style.display = "none";
    } else {
      closeAll(dd);
      dd.classList.add(OPEN_CLASS);
      listEl.style.display = "block";
      syncOnOpen();
    }
  };

  dropdowns.forEach((dd) => {
    const listEl = $(".brand_dropdown-list", dd);
    if (listEl) listEl.style.display = "none";

    dd.addEventListener("click", (e) => e.stopPropagation());

    const toggle = $(".brand_dropdown-toggle", dd);
    if (!toggle) return;

    toggle.style.cursor = "pointer";
    toggle.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggleDropdown(dd);
    });
  });

  document.addEventListener("click", () => closeAll());
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeAll();
  });

  const ensureCheckboxValues = () => {
    $$("label.w-checkbox", form).forEach((lbl) => {
      const input = $('input[type="checkbox"][fs-list-field]', lbl);
      if (!input) return;

      const fromAttr = (input.getAttribute("fs-list-value") || "").trim();
      const fromLabel = ($(".checkbox-label", lbl)?.textContent || "").trim();
      const txt = fromAttr || fromLabel;
      if (!txt) return;

      input.value = txt;
      input.setAttribute("value", txt);
      if (!input.getAttribute("fs-list-value")) {
        input.setAttribute("fs-list-value", txt);
      }
    });
  };

  const initToggleDefaultText = (dd) => {
    const labelEl = $(".filter-btn-txt", dd);
    if (!labelEl) return;

    if (!labelEl.dataset.defaultText) {
      labelEl.dataset.defaultText = (labelEl.textContent || "")
        .replace(/\s*\(\d+\)\s*$/, "")
        .trim();
      labelEl.textContent = labelEl.dataset.defaultText;
    }
  };

  const countCheckedInDropdown = (dd, fields) =>
    fields.reduce((sum, field) => {
      const checked = $$(`input[type="checkbox"][fs-list-field="${field}"]`, dd).filter(
        (i) => i.checked
      ).length;
      return sum + checked;
    }, 0);

  const updateToggleLabel = (dd, fieldOrFields) => {
    const labelEl = $(".filter-btn-txt", dd);
    if (!labelEl) return;

    initToggleDefaultText(dd);
    const base = (labelEl.dataset.defaultText || "").trim();
    const fields = Array.isArray(fieldOrFields) ? fieldOrFields : [fieldOrFields];
    const checkedCount = countCheckedInDropdown(dd, fields);

    labelEl.textContent = checkedCount ? `${base} (${checkedCount})` : base;
  };

  const refreshAllToggles = () => {
    dropdowns.forEach((dd) => {
      initToggleDefaultText(dd);

      if ($('input[fs-list-field="materials"]', dd)) {
        updateToggleLabel(dd, "materials");
      }

      if (PRICE_FIELDS.some((f) => $(`input[fs-list-field="${f}"]`, dd))) {
        updateToggleLabel(dd, PRICE_FIELDS);
      }
    });
  };

  const checkedValuesFor = (field) =>
    $$(`input[type="checkbox"][fs-list-field="${field}"]`, form)
      .filter((i) => i.checked)
      .map((i) => (i.value || i.getAttribute("fs-list-value") || "").trim())
      .filter(Boolean);

  const itemFieldValues = (item, field) =>
    $$(`[fs-list-field="${field}"]`, item)
      .map((el) => {
        const explicit = (el.getAttribute("fs-list-value") || "").trim();
        if (explicit) return explicit;
        return (el.textContent || "").trim();
      })
      .filter(Boolean);

  const itemMatchesGroup = (item, field, selected) => {
    if (!selected.length) return true;
    const values = itemFieldValues(item, field);
    return selected.some((sel) => values.includes(sel));
  };

  const applyFilters = () => {
    if (!list) return;

    const materialSelected = checkedValuesFor("materials");
    const priceSelected = PRICE_FIELDS.flatMap((field) =>
      checkedValuesFor(field).map((value) => ({ field, value }))
    );

    $$(":scope > .w-dyn-item", list).forEach((item) => {
      const matchesMaterials = itemMatchesGroup(item, "materials", materialSelected);

      let matchesPrice = true;
      if (priceSelected.length) {
        matchesPrice = priceSelected.some(({ field, value }) => {
          const values = itemFieldValues(item, field);
          return values.includes(value);
        });
      }

      item.style.display = matchesMaterials && matchesPrice ? "" : "none";
    });

    document.dispatchEvent(new CustomEvent("brand-filters:updated"));
  };

  form.addEventListener("change", (e) => {
    const t = e.target;
    if (!(t instanceof HTMLInputElement)) return;
    if (t.type !== "checkbox") return;

    syncWebflowUI();
    applyFilters();

    const dd = t.closest(".brand-filters_dropdown");
    if (!dd) return;

    const field = t.getAttribute("fs-list-field");

    if (field === "materials") {
      updateToggleLabel(dd, "materials");
    }

    if (field && PRICE_FIELDS.includes(field)) {
      updateToggleLabel(dd, PRICE_FIELDS);
    }
  });

  // Capture phase so this runs before dropdown stopPropagation
  form.addEventListener(
    "click",
    (e) => {
      const label = e.target.closest("label.w-checkbox");
      if (!label || !form.contains(label)) return;
      if (e.target instanceof HTMLInputElement) return;

      const inp = $('input[type="checkbox"][fs-list-field]', label);
      if (!inp) return;

      e.preventDefault();
      e.stopPropagation();
      inp.checked = !inp.checked;
      inp.dispatchEvent(new Event("change", { bubbles: true }));
    },
    true
  );

  const clearBtn = document.querySelector('[fs-list-element="clear"]');

  if (clearBtn) {
    clearBtn.addEventListener("click", (e) => {
      e.preventDefault();
      forceAllUnchecked();
      closeAll();
      refreshAllToggles();
      applyFilters();
    });
  }

  ensureCheckboxValues();
  forceAllUnchecked();
  refreshAllToggles();
  syncWebflowUI();
  applyFilters();

  setTimeout(() => {
    ensureCheckboxValues();
    forceAllUnchecked();
    refreshAllToggles();
    syncWebflowUI();
    applyFilters();
  }, 200);
})();

(() => {
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  const list = document.querySelector('[fs-list-element="list"]');
  if (!list) return;

  const dynList = list.closest(".w-dyn-list") || list.parentElement;
  if (!dynList) return;

  let empty = dynList.querySelector(".fs-empty-state");
  if (!empty) {
    empty = document.createElement("div");
    empty.className = "fs-empty-state";
    empty.textContent = "No results found";
    dynList.appendChild(empty);
  }

  empty.style.display = "none";
  empty.style.opacity = "1";
  empty.style.visibility = "visible";
  empty.style.position = "relative";
  empty.style.padding = "24px 0";
  empty.style.textAlign = "center";
  empty.style.color = "black";

  const hasVisibleItems = () => {
    const items = $$(":scope > .w-dyn-item", list);
    return items.some((el) => el.style.display !== "none");
  };

  const update = () => {
    empty.style.display = hasVisibleItems() ? "none" : "block";
  };

  document.addEventListener("brand-filters:updated", update);
  const form = document.querySelector('[fs-list-element="filters"]');
  if (form) form.addEventListener("change", () => setTimeout(update, 0));

  setTimeout(update, 0);
  setTimeout(update, 200);
})();

