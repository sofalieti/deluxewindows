(() => {
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const $ = (sel, root = document) => root.querySelector(sel);

  const form = document.querySelector('[fs-list-element="filters"]');
  if (!form) return;

  const dropdowns = $$(".brand-filters_dropdown", form);
  const OPEN_CLASS = "is-open";
  const PRICE_FIELDS = ["price1", "price2", "price3", "price4", "price5"];

  form.setAttribute("autocomplete", "off");
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
      const list = $(".brand_dropdown-list", dd);
      if (list) list.style.display = "none";
    });
  };

  const toggleDropdown = (dd) => {
    const list = $(".brand_dropdown-list", dd);
    if (!list) return;

    const isOpen = dd.classList.contains(OPEN_CLASS);

    if (isOpen) {
      dd.classList.remove(OPEN_CLASS);
      list.style.display = "none";
    } else {
      closeAll(dd);
      dd.classList.add(OPEN_CLASS);
      list.style.display = "block";
      syncOnOpen();
    }
  };

  dropdowns.forEach((dd) => {
    const list = $(".brand_dropdown-list", dd);
    if (list) list.style.display = "none";

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

      const txt = ($(".checkbox-label", lbl)?.textContent || "").trim();
      if (!txt) return;

      input.value = txt;
      input.setAttribute("value", txt);
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

  form.addEventListener("change", (e) => {
    const t = e.target;
    if (!(t instanceof HTMLInputElement)) return;
    if (t.type !== "checkbox") return;

    syncWebflowUI();

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

  const clearBtn = document.querySelector('[fs-list-element="clear"]');

  if (clearBtn) {
    clearBtn.addEventListener("click", () => {
      setTimeout(() => {
        forceAllUnchecked();
        closeAll();
        refreshAllToggles();
      }, 0);

      setTimeout(() => {
        forceAllUnchecked();
        refreshAllToggles();
      }, 60);
    });
  }

  ensureCheckboxValues();
  forceAllUnchecked();
  refreshAllToggles();
  syncWebflowUI();

  setTimeout(() => {
    ensureCheckboxValues();
    forceAllUnchecked();
    refreshAllToggles();
    syncWebflowUI();
  }, 200);
})();

(() => {
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const $ = (s, r = document) => r.querySelector(s);

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

  const ensureEmptyVisibleStyles = () => {
    empty.style.display = "none";
    empty.style.opacity = "1";
    empty.style.visibility = "visible";
    empty.style.position = "relative";
    empty.style.padding = "24px 0";
    empty.style.textAlign = "center";
    empty.style.color = "black";
  };
  ensureEmptyVisibleStyles();

  const hasVisibleItems = () => {
    const items = $$(".w-dyn-item", dynList);
    return items.some((el) => el.offsetParent !== null);
  };

  const update = () => {
    empty.style.display = hasVisibleItems() ? "none" : "block";
  };

  const mo = new MutationObserver(() => update());
  mo.observe(dynList, {
    subtree: true,
    childList: true,
    attributes: true,
    attributeFilter: ["style", "class"],
  });

  const form = document.querySelector('[fs-list-element="filters"]');
  if (form) form.addEventListener("change", () => setTimeout(update, 0));

  setTimeout(update, 0);
  setTimeout(update, 200);
})();
