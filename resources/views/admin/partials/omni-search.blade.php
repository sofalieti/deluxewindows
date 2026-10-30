<div id="dw-omni-search" class="position-relative" style="width: min(22rem, 72vw)">
    <input
        id="dw-omni-search-input"
        type="search"
        class="form-control"
        placeholder="Search leads, contacts, phones"
        autocomplete="off"
        aria-label="Search the admin"
        aria-expanded="false"
        aria-controls="dw-omni-search-panel"
    >
    <div
        id="dw-omni-search-panel"
        class="bg-white border rounded shadow"
        hidden
        style="position: fixed; z-index: 1080; max-height: 70vh; overflow: auto"
    ></div>
</div>
<script>
(function () {
    function boot() {
        var root = document.getElementById('dw-omni-search');
        var input = document.getElementById('dw-omni-search-input');
        var panel = document.getElementById('dw-omni-search-panel');
        if (!root || !input || !panel || root.dataset.bound === '1') {
            return;
        }
        root.dataset.bound = '1';

        var url = @json(route('platform.omni-search'));
        var timer = null;
        var active = -1;
        var links = [];
        var controller = null;

        function place() {
            var rect = input.getBoundingClientRect();
            panel.style.top = (rect.bottom + 6) + 'px';
            panel.style.width = rect.width + 'px';
            panel.style.left = Math.max(8, rect.right - rect.width) + 'px';
        }

        function close() {
            panel.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            active = -1;
        }

        function mark() {
            links.forEach(function (link, index) {
                link.classList.toggle('active', index === active);
            });
            if (links[active]) {
                links[active].scrollIntoView({ block: 'nearest' });
            }
        }

        function render(groups) {
            panel.replaceChildren();
            links = [];
            if (!groups.length) {
                var empty = document.createElement('div');
                empty.className = 'p-3 text-muted';
                empty.textContent = 'Nothing found';
                panel.appendChild(empty);
            }
            groups.forEach(function (group) {
                var label = document.createElement('div');
                label.className = 'px-3 pt-2 pb-1 small text-muted text-uppercase';
                label.textContent = group.label;
                panel.appendChild(label);
                group.items.forEach(function (item) {
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
                    panel.appendChild(link);
                    links.push(link);
                });
                if (group.more) {
                    var more = document.createElement('a');
                    more.className = 'dropdown-item small text-primary py-2';
                    more.href = group.more.url;
                    more.textContent = group.more.label;
                    panel.appendChild(more);
                    links.push(more);
                }
            });
            place();
            panel.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            active = -1;
        }

        function search() {
            var query = input.value.trim();
            if (query.length < 2) {
                close();
                return;
            }
            if (controller) {
                controller.abort();
            }
            controller = new AbortController();
            fetch(url + '?q=' + encodeURIComponent(query), {
                headers: { 'Accept': 'application/json' },
                signal: controller.signal
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('search failed');
                }
                return response.json();
            }).then(function (payload) {
                if (input.value.trim() !== query) {
                    return;
                }
                render(payload.groups || []);
            }).catch(function (error) {
                if (error.name !== 'AbortError') {
                    close();
                }
            });
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(search, 180);
        });
        input.addEventListener('focus', function () {
            if (input.value.trim().length >= 2 && panel.hidden) {
                search();
            }
        });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                close();
                return;
            }
            if (panel.hidden || !links.length) {
                return;
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                active = Math.min(links.length - 1, active + 1);
                mark();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                active = Math.max(0, active - 1);
                mark();
            } else if (event.key === 'Enter' && links[active]) {
                event.preventDefault();
                window.location.href = links[active].href;
            }
        });
        document.addEventListener('click', function (event) {
            if (!root.contains(event.target) && !panel.contains(event.target)) {
                close();
            }
        });
        window.addEventListener('resize', function () {
            if (!panel.hidden) {
                place();
            }
        });
    }

    document.addEventListener('turbo:load', boot);
    document.addEventListener('DOMContentLoaded', boot);
    boot();
})();
</script>
