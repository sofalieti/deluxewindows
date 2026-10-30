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
        panel.hidden = true;
        panel.style.position = 'fixed';
        panel.style.zIndex = '2000';
        panel.style.maxHeight = 'min(70vh, 32rem)';
        panel.style.overflow = 'auto';
        panel.style.boxSizing = 'border-box';
        panel.style.padding = '8px';
        panel.style.background = '#fff';
        panel.style.border = '1px solid #e6e8ee';
        panel.style.borderRadius = '12px';
        panel.style.boxShadow = '0 16px 40px rgba(16, 24, 40, 0.14)';
        document.body.appendChild(panel);
        ensureStyles();
        return panel;
    }

    function ensureStyles() {
        if (document.getElementById('dw-admin-search-style')) {
            return;
        }
        var style = document.createElement('style');
        style.id = 'dw-admin-search-style';
        style.textContent = [
            '#dw-admin-search-panel .dw-search-section + .dw-search-section { margin-top: 6px; padding-top: 6px; border-top: 1px solid #eceff3; }',
            '#dw-admin-search-panel .dw-search-label { padding: 8px 12px 4px; font-size: 11px; line-height: 1.2; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #6b7280; }',
            '#dw-admin-search-panel .dw-search-row, #dw-admin-search-panel .dw-search-more { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; width: 100%; margin: 0; padding: 8px 12px; border: 0; border-radius: 8px; background: transparent; color: inherit; text-align: left; text-decoration: none; box-sizing: border-box; }',
            '#dw-admin-search-panel .dw-search-row:hover, #dw-admin-search-panel .dw-search-row.active, #dw-admin-search-panel .dw-search-more:hover, #dw-admin-search-panel .dw-search-more.active { background: #f3f6fb; }',
            '#dw-admin-search-panel .dw-search-main { min-width: 0; flex: 1; }',
            '#dw-admin-search-panel .dw-search-title { display: inline; font-weight: 600; line-height: 1.35; }',
            '#dw-admin-search-panel .dw-search-note { color: #6b7280; font-weight: 500; }',
            '#dw-admin-search-panel .dw-search-nested { margin: 2px 4px 8px 12px; padding-left: 8px; border-left: 2px solid #e6e8ee; }',
            '#dw-admin-search-panel .dw-search-sub { display: block; margin-top: 2px; color: #6b7280; font-size: 12px; line-height: 1.4; word-break: break-word; }',
            '#dw-admin-search-panel .dw-search-badge { flex: none; margin-top: 2px; padding: 2px 8px; border-radius: 999px; font-size: 11px; line-height: 1.4; }',
            '#dw-admin-search-panel .dw-search-more { color: #0f4d89; font-size: 13px; font-weight: 600; }',
            '#dw-admin-search-panel .dw-search-empty, #dw-admin-search-panel .dw-search-hint { padding: 10px 12px; color: #6b7280; font-size: 13px; }',
            '#dw-admin-search-panel mark.dw-search-hit { background: #fde8e8; color: #c4232c; border-radius: 3px; padding: 0 1px; }'
        ].join('');
        document.head.appendChild(style);
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

    function matchRanges(text, query) {
        var ranges = [];
        String(query || '').trim().split(/\s+/).forEach(function (token) {
            if (!token) {
                return;
            }
            var lower = text.toLowerCase();
            var needle = token.toLowerCase();
            var from = 0;
            var found = false;
            while (from < text.length) {
                var at = lower.indexOf(needle, from);
                if (at < 0) {
                    break;
                }
                ranges.push([at, at + token.length]);
                from = at + token.length;
                found = true;
            }
            if (found) {
                return;
            }
            var digits = token.replace(/\D+/g, '');
            if (digits.length < 3) {
                return;
            }
            var digitIndex = 0;
            var start = -1;
            for (var i = 0; i < text.length; i++) {
                var ch = text.charAt(i);
                if (ch === digits.charAt(digitIndex)) {
                    if (start < 0) {
                        start = i;
                    }
                    digitIndex++;
                    if (digitIndex === digits.length) {
                        ranges.push([start, i + 1]);
                        start = -1;
                        digitIndex = 0;
                    }
                    continue;
                }
                if (start >= 0 && /\d/.test(ch)) {
                    start = -1;
                    digitIndex = 0;
                    i--;
                }
            }
        });
        ranges.sort(function (a, b) {
            return a[0] - b[0] || a[1] - b[1];
        });
        var merged = [];
        ranges.forEach(function (range) {
            var last = merged[merged.length - 1];
            if (!last || range[0] > last[1]) {
                merged.push(range);
            } else if (range[1] > last[1]) {
                last[1] = range[1];
            }
        });
        return merged;
    }

    function fillMatch(node, text, query) {
        node.replaceChildren();
        var ranges = matchRanges(text, query);
        if (!ranges.length) {
            node.textContent = text;
            return;
        }
        var cursor = 0;
        ranges.forEach(function (range) {
            if (range[0] > cursor) {
                node.appendChild(document.createTextNode(text.slice(cursor, range[0])));
            }
            var hit = document.createElement('mark');
            hit.className = 'dw-search-hit';
            hit.textContent = text.slice(range[0], range[1]);
            node.appendChild(hit);
            cursor = range[1];
        });
        if (cursor < text.length) {
            node.appendChild(document.createTextNode(text.slice(cursor)));
        }
    }

    function digitsOf(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function samePhone(left, right) {
        if (left.length < 3 || right.length < 3) {
            return false;
        }
        if (left.indexOf(right) >= 0 || right.indexOf(left) >= 0) {
            return true;
        }
        var a = left.charAt(0) === '1' ? left.slice(1) : left;
        var b = right.charAt(0) === '1' ? right.slice(1) : right;
        return a.length >= 3 && b.length >= 3 && (a === b || a.indexOf(b) >= 0 || b.indexOf(a) >= 0);
    }

    function itemRelates(item, suggestion) {
        var blob = (item.title || '') + ' ' + (item.subtitle || '');
        var suggestionDigits = digitsOf(suggestion.value);
        if (suggestion.kind === 'Phone' || suggestionDigits.length >= 7) {
            return samePhone(suggestionDigits, digitsOf(blob));
        }
        return blob.toLowerCase().indexOf(String(suggestion.value || '').toLowerCase()) >= 0;
    }

    function withQuery(url, value) {
        try {
            var next = new URL(url, window.location.origin);
            next.searchParams.set('q', value);
            return next.pathname + next.search + next.hash;
        } catch (error) {
            return url;
        }
    }

    function relatedLabels(suggestion, groups) {
        var order = ['Leads', 'Contacts', 'Phone clicks', 'RingCentral'];
        var have = {};
        (suggestion.sources || []).forEach(function (label) {
            have[label] = true;
        });
        groups.forEach(function (group) {
            if ((group.items || []).some(function (item) { return itemRelates(item, suggestion); })) {
                have[group.label] = true;
            }
        });
        return order.filter(function (label) { return have[label]; });
    }

    function groupsFor(suggestion, groups) {
        var labels = relatedLabels(suggestion, groups);
        return groups.filter(function (group) {
            return labels.indexOf(group.label) >= 0;
        }).map(function (group) {
            return {
                label: group.label,
                more: group.more,
                items: (group.items || []).filter(function (item) { return itemRelates(item, suggestion); })
            };
        });
    }

    function sectionLabel(text) {
        var label = document.createElement('div');
        label.className = 'dw-search-label';
        label.textContent = text;
        return label;
    }

    function render(root, payload, query) {
        var layer = ensurePanel();
        layer.replaceChildren();
        rows = [];
        active = -1;
        var suggestions = payload.suggestions || [];
        var groups = payload.groups || [];
        if (root.dataset.expandedFor !== query) {
            root.dataset.expandedValue = '';
            root.dataset.expandedFor = query;
        }
        var expandedValue = root.dataset.expandedValue || '';
        root.dataset.completion = payload.completion || '';
        paintGhost(root, query, root.dataset.completion);

        function appendSections(parent, sectionGroups, highlight, listValue) {
            var firstList = '';
            sectionGroups.forEach(function (group) {
                var section = document.createElement('section');
                section.className = 'dw-search-section';
                section.appendChild(sectionLabel(group.label));
                var listUrl = group.more ? withQuery(group.more.url, listValue) : '';
                if (!firstList && listUrl) {
                    firstList = listUrl;
                }
                (group.items || []).forEach(function (item) {
                    var link = document.createElement('a');
                    link.className = 'dw-search-row';
                    link.href = item.url;
                    var text = document.createElement('span');
                    text.className = 'dw-search-main';
                    var title = document.createElement('span');
                    title.className = 'dw-search-title';
                    fillMatch(title, item.title, highlight);
                    var subtitle = document.createElement('span');
                    subtitle.className = 'dw-search-sub';
                    fillMatch(subtitle, item.subtitle || '', highlight);
                    text.appendChild(title);
                    if (item.subtitle) {
                        text.appendChild(subtitle);
                    }
                    link.appendChild(text);
                    if (item.badge) {
                        var badge = document.createElement('span');
                        badge.className = 'dw-search-badge ' + (item.badge === 'Client' ? 'bg-success text-white' : 'bg-secondary text-white');
                        badge.textContent = item.badge;
                        link.appendChild(badge);
                    }
                    section.appendChild(link);
                    rows.push({ el: link, fill: '', href: item.url, listUrl: listUrl });
                });
                if (listUrl) {
                    var more = document.createElement('a');
                    more.className = 'dw-search-more';
                    more.href = listUrl;
                    more.textContent = 'Full list';
                    section.appendChild(more);
                    rows.push({ el: more, fill: '', href: listUrl, listUrl: listUrl });
                }
                parent.appendChild(section);
            });
            return firstList;
        }

        if (!suggestions.length && !groups.length) {
            var empty = document.createElement('div');
            empty.className = 'dw-search-empty';
            empty.textContent = 'Nothing found';
            layer.appendChild(empty);
        } else if (!suggestions.length) {
            appendSections(layer, groups, query, query);
        } else {
            suggestions.forEach(function (item) {
                var key = (item.kind || '') + '|' + item.value;
                var block = document.createElement('div');
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'dw-search-row';
                var text = document.createElement('span');
                text.className = 'dw-search-main';
                var value = document.createElement('span');
                value.className = 'dw-search-title';
                fillMatch(value, item.value, query);
                text.appendChild(value);
                var labels = relatedLabels(item, groups);
                var suggestionDigits = digitsOf(item.value);
                if (labels.length && (item.kind === 'Phone' || suggestionDigits.length >= 7)) {
                    var note = document.createElement('span');
                    note.className = 'dw-search-note';
                    note.textContent = ' (' + labels.join(', ') + ')';
                    text.appendChild(note);
                }
                button.appendChild(text);
                var matched = groupsFor(item, groups);
                var listUrl = '';
                matched.forEach(function (group) {
                    if (!listUrl && group.more) {
                        listUrl = withQuery(group.more.url, item.value);
                    }
                });
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    root.dataset.expandedFor = query;
                    root.dataset.expandedValue = expandedValue === key ? '' : key;
                    render(root, payload, query);
                });
                block.appendChild(button);
                rows.push({ el: button, fill: item.value, href: '', listUrl: listUrl });
                if (expandedValue === key) {
                    var nested = document.createElement('div');
                    nested.className = 'dw-search-nested';
                    appendSections(nested, matched, item.value, item.value);
                    block.appendChild(nested);
                }
                layer.appendChild(block);
            });
        }

        if (suggestions.length || groups.length) {
            var hint = document.createElement('div');
            hint.className = 'dw-search-hint';
            hint.textContent = root.dataset.adminSearch === 'filter'
                ? 'Click opens the sections · Enter filters this list'
                : 'Click opens the sections · Enter opens the full list';
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
            failed.className = 'dw-search-empty';
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

    function fullListUrl() {
        if (rows[active] && rows[active].listUrl) {
            return rows[active].listUrl;
        }
        var first = rows.find(function (row) { return row.listUrl; });
        return first ? first.listUrl : '';
    }

    function onEnter(root) {
        if (root.dataset.adminSearch === 'filter') {
            applyFilter(root, inputOf(root).value);
            return;
        }
        var url = fullListUrl();
        if (url) {
            window.location.href = url;
            return;
        }
        if (rows[active] && rows[active].fill) {
            acceptSuggestion(root, rows[active].fill);
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
