(function () {
    if (window.__dwAdminSearch) {
        return;
    }
    window.__dwAdminSearch = true;

    var panel = null;
    var timer = null;
    var controller = null;
    var active = -1;
    var rows = [];
    var requestQuery = '';

    function box(node) {
        return node && node.closest ? node.closest('[data-admin-search]') : null;
    }

    function inputOf(root) {
        return root ? root.querySelector('[data-admin-search-input]') : null;
    }

    function ghostOf(root) {
        return root ? root.querySelector('[data-admin-search-ghost]') : null;
    }

    function ensurePanel() {
        if (panel && document.body.contains(panel)) {
            return panel;
        }
        panel = document.createElement('div');
        panel.id = 'dw-admin-search-panel';
        panel.className = 'bg-white border rounded shadow';
        panel.hidden = true;
        panel.style.position = 'fixed';
        panel.style.zIndex = '2000';
        panel.style.maxHeight = 'min(70vh, 28rem)';
        panel.style.overflow = 'auto';
        document.body.appendChild(panel);
        return panel;
    }

    function place(input) {
        var rect = input.getBoundingClientRect();
        var layer = ensurePanel();
        var margin = 8;
        var preferred = Math.max(rect.width, 420);
        var width = Math.min(preferred, Math.max(rect.width, rect.right - margin));
        var left = rect.right - width;
        if (left < margin) {
            left = margin;
            width = Math.max(rect.width, rect.right - margin);
        }
        layer.style.boxSizing = 'border-box';
        layer.style.top = (rect.bottom + 4) + 'px';
        layer.style.left = left + 'px';
        layer.style.right = 'auto';
        layer.style.width = width + 'px';
    }

    function close() {
        if (panel) {
            panel.hidden = true;
        }
        active = -1;
        rows = [];
        var input = document.activeElement;
        if (input && input.matches && input.matches('[data-admin-search-input]')) {
            input.setAttribute('aria-expanded', 'false');
        }
    }

    function paintGhost(root, typed, completion) {
        var ghost = ghostOf(root);
        var input = inputOf(root);
        if (!ghost || !input) {
            return;
        }
        ghost.replaceChildren();
        var suffix = '';
        if (completion && typed && completion.toLowerCase().indexOf(typed.toLowerCase()) === 0 && completion.length > typed.length) {
            suffix = completion.slice(typed.length);
        }
        if (!suffix) {
            input.style.background = '#fff';
            return;
        }
        var hidden = document.createElement('span');
        hidden.style.visibility = 'hidden';
        hidden.textContent = typed;
        var rest = document.createElement('span');
        rest.textContent = suffix;
        ghost.appendChild(hidden);
        ghost.appendChild(rest);
        input.style.background = 'transparent';
    }

    function mark() {
        rows.forEach(function (row, index) {
            row.el.classList.toggle('active', index === active);
        });
        if (rows[active]) {
            rows[active].el.scrollIntoView({ block: 'nearest' });
        }
    }

    function addLabel(text) {
        var label = document.createElement('div');
        label.className = 'px-3 pt-2 pb-1 small text-muted text-uppercase';
        label.textContent = text;
        panel.appendChild(label);
    }

    function render(root, payload, query) {
        var layer = ensurePanel();
        layer.replaceChildren();
        rows = [];
        active = -1;
        var suggestions = payload.suggestions || [];
        var groups = payload.groups || [];
        root.dataset.completion = payload.completion || '';
        paintGhost(root, query, root.dataset.completion);

        if (!suggestions.length && !groups.length) {
            var empty = document.createElement('div');
            empty.className = 'p-3 text-muted';
            empty.textContent = 'Nothing found';
            layer.appendChild(empty);
        }

        if (suggestions.length) {
            addLabel('From your records');
            suggestions.forEach(function (item) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'dropdown-item d-flex justify-content-between align-items-center gap-2 py-2 text-start';
                var value = document.createElement('span');
                value.textContent = item.value;
                var kind = document.createElement('span');
                kind.className = 'small text-muted';
                kind.textContent = item.kind || '';
                button.appendChild(value);
                button.appendChild(kind);
                button.addEventListener('click', function () {
                    acceptSuggestion(root, item.value);
                });
                layer.appendChild(button);
                rows.push({ el: button, fill: item.value, href: '' });
            });
        }

        groups.forEach(function (group) {
            addLabel(group.label);
            (group.items || []).forEach(function (item) {
                var link = document.createElement('a');
                link.className = 'dropdown-item d-flex justify-content-between align-items-start gap-2 py-2';
                link.href = item.url;
                var text = document.createElement('span');
                var title = document.createElement('span');
                title.className = 'd-block fw-semibold';
                title.textContent = item.title;
                var subtitle = document.createElement('span');
                subtitle.className = 'd-block small text-muted';
                subtitle.textContent = item.subtitle || '';
                text.appendChild(title);
                text.appendChild(subtitle);
                link.appendChild(text);
                if (item.badge) {
                    var badge = document.createElement('span');
                    badge.className = 'badge ' + (item.badge === 'Client' ? 'bg-success' : 'bg-secondary');
                    badge.textContent = item.badge;
                    link.appendChild(badge);
                }
                layer.appendChild(link);
                rows.push({ el: link, fill: '', href: item.url });
            });
            if (group.more) {
                var more = document.createElement('a');
                more.className = 'dropdown-item small text-primary py-2';
                more.href = group.more.url;
                more.textContent = group.more.label;
                layer.appendChild(more);
                rows.push({ el: more, fill: '', href: group.more.url });
            }
        });

        if (suggestions.length || groups.length) {
            var hint = document.createElement('div');
            hint.className = 'px-3 py-2 small text-muted border-top';
            hint.textContent = root.dataset.adminSearch === 'filter'
                ? 'Tab completes · Enter filters the list'
                : 'Tab completes · Enter opens';
            layer.appendChild(hint);
        }

        place(inputOf(root));
        layer.hidden = false;
        var input = inputOf(root);
        if (input) {
            input.setAttribute('aria-expanded', 'true');
        }
    }

    function acceptSuggestion(root, value) {
        var input = inputOf(root);
        if (!input) {
            return;
        }
        input.value = value;
        if (root.dataset.adminSearch === 'filter') {
            applyFilter(root, value);
            return;
        }
        input.focus();
        search(root);
    }

    function applyFilter(root, value) {
        var url = new URL(root.dataset.filterBase || window.location.href, window.location.origin);
        var query = String(value || '').trim();
        if (query) {
            url.searchParams.set('q', query);
        } else {
            url.searchParams.delete('q');
        }
        ['page', 'mine_page', 'spam_page', 'unprocessed_page', 'microsoft_page', 'direct_page', 'google_page', 'seo_page', 'chatgpt_page', 'unhandled_page', 'all_page', 'inbound_page', 'unreturned_page', 'outbound_page', 'other_page'].forEach(function (key) {
            url.searchParams.delete(key);
        });
        if (window.Turbo) {
            window.Turbo.visit(url.toString());
        } else {
            window.location.assign(url.toString());
        }
    }

    function openRow(row) {
        if (!row) {
            return false;
        }
        if (row.href) {
            window.location.href = row.href;
            return true;
        }
        return false;
    }

    function search(root) {
        var input = inputOf(root);
        if (!input) {
            return;
        }
        var query = input.value.trim();
        if (query.length < 2) {
            paintGhost(root, '', '');
            close();
            return;
        }
        if (controller) {
            controller.abort();
        }
        controller = new AbortController();
        requestQuery = query;
        fetch(root.dataset.endpoint + '?q=' + encodeURIComponent(query), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
            signal: controller.signal
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('search failed');
            }
            return response.json();
        }).then(function (payload) {
            if (!inputOf(root) || inputOf(root).value.trim() !== query) {
                return;
            }
            render(root, payload, query);
        }).catch(function (error) {
            if (error.name === 'AbortError') {
                return;
            }
            paintGhost(root, query, '');
            var layer = ensurePanel();
            layer.replaceChildren();
            var failed = document.createElement('div');
            failed.className = 'p-3 text-muted';
            failed.textContent = 'Search is unavailable';
            layer.appendChild(failed);
            place(input);
            layer.hidden = false;
        });
    }

    function schedule(root) {
        clearTimeout(timer);
        timer = setTimeout(function () {
            search(root);
        }, 160);
    }

    function onEnter(root) {
        if (rows[active] && rows[active].fill) {
            acceptSuggestion(root, rows[active].fill);
            return;
        }
        if (openRow(rows[active])) {
            return;
        }
        if (root.dataset.adminSearch === 'filter') {
            applyFilter(root, inputOf(root).value);
            return;
        }
        var firstLink = rows.find(function (row) { return row.href; });
        if (openRow(firstLink)) {
            return;
        }
        if (rows[0] && rows[0].fill) {
            acceptSuggestion(root, rows[0].fill);
        }
    }

    document.addEventListener('input', function (event) {
        var root = box(event.target);
        if (!root || !event.target.matches('[data-admin-search-input]')) {
            return;
        }
        root.dataset.completion = '';
        schedule(root);
    });

    document.addEventListener('focusin', function (event) {
        var root = box(event.target);
        if (!root || !event.target.matches('[data-admin-search-input]')) {
            return;
        }
        if (event.target.value.trim().length >= 2) {
            search(root);
        }
    });

    document.addEventListener('keydown', function (event) {
        var root = box(event.target);
        if (!root || !event.target.matches('[data-admin-search-input]')) {
            return;
        }
        if (event.key === 'Escape') {
            close();
            return;
        }
        if (event.key === 'Tab') {
            var completion = root.dataset.completion || '';
            var typed = event.target.value.trim();
            if (completion && completion.toLowerCase() !== typed.toLowerCase()) {
                event.preventDefault();
                acceptSuggestion(root, completion);
            }
            return;
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            event.stopPropagation();
            onEnter(root);
            return;
        }
        if (!panel || panel.hidden || !rows.length) {
            return;
        }
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            active = Math.min(rows.length - 1, active + 1);
            mark();
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            active = Math.max(0, active - 1);
            mark();
        }
    }, true);

    document.addEventListener('keypress', function (event) {
        if (event.target.matches && event.target.matches('[data-admin-search-input]') && event.key === 'Enter') {
            event.preventDefault();
            event.stopPropagation();
        }
    }, true);

    document.addEventListener('click', function (event) {
        var apply = event.target.closest ? event.target.closest('[data-admin-search-apply]') : null;
        if (apply) {
            var root = box(apply);
            if (root) {
                applyFilter(root, inputOf(root).value);
            }
            return;
        }
        if (panel && panel.contains(event.target)) {
            return;
        }
        if (!box(event.target)) {
            close();
        }
    });

    window.addEventListener('resize', function () {
        var input = document.activeElement;
        if (panel && !panel.hidden && input && input.matches && input.matches('[data-admin-search-input]')) {
            place(input);
        }
    });

    window.addEventListener('scroll', function () {
        var input = document.activeElement;
        if (panel && !panel.hidden && input && input.matches && input.matches('[data-admin-search-input]')) {
            place(input);
        }
    }, true);
})();
