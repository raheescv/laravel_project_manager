<!doctype html>
<html lang="en" data-theme="{{ $config->get('ui.theme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="color-scheme" content="{{ $config->get('ui.theme', 'light') }}">
    <title>{{ $config->get('ui.title', config('app.name') . ' - API Docs') }}</title>

    <script src="https://unpkg.com/@stoplight/elements@8.4.2/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements@8.4.2/styles.min.css">

    <script>
        const originalFetch = window.fetch;

        // intercept TryIt requests and add the XSRF-TOKEN header,
        // which is necessary for Sanctum cookie-based authentication to work correctly
        window.fetch = (url, options) => {
            const CSRF_TOKEN_COOKIE_KEY = "XSRF-TOKEN";
            const CSRF_TOKEN_HEADER_KEY = "X-XSRF-TOKEN";
            const getCookieValue = (key) => {
                const cookie = document.cookie.split(';').find((cookie) => cookie.trim().startsWith(key));
                return cookie?.split("=")[1];
            };

            const updateFetchHeaders = (
                headers,
                headerKey,
                headerValue,
            ) => {
                if (headers instanceof Headers) {
                    headers.set(headerKey, headerValue);
                } else if (Array.isArray(headers)) {
                    headers.push([headerKey, headerValue]);
                } else if (headers) {
                    headers[headerKey] = headerValue;
                }
            };
            const csrfToken = getCookieValue(CSRF_TOKEN_COOKIE_KEY);
            if (csrfToken) {
                const { headers = new Headers() } = options || {};
                updateFetchHeaders(headers, CSRF_TOKEN_HEADER_KEY, decodeURIComponent(csrfToken));
                return originalFetch(url, {
                    ...options,
                    headers,
                });
            }

            return originalFetch(url, options);
        };
    </script>

    <style>
        html, body { margin:0; height:100%; }
        body { background-color: var(--color-canvas); }
        /* issues about the dark theme of stoplight/mosaic-code-viewer using web component:
         * https://github.com/stoplightio/elements/issues/2188#issuecomment-1485461965
         */
        [data-theme="dark"] .token.property {
            color: rgb(128, 203, 196) !important;
        }
        [data-theme="dark"] .token.operator {
            color: rgb(255, 123, 114) !important;
        }
        [data-theme="dark"] .token.number {
            color: rgb(247, 140, 108) !important;
        }
        [data-theme="dark"] .token.string {
            color: rgb(165, 214, 255) !important;
        }
        [data-theme="dark"] .token.boolean {
            color: rgb(121, 192, 255) !important;
        }
        [data-theme="dark"] .token.punctuation {
            color: #dbdbdb !important;
        }

        /* ---------------- API search (custom addition) ---------------- */
        :root {
            --apis-bg: #ffffff;
            --apis-fg: #0f172a;
            --apis-muted: #64748b;
            --apis-border: #e2e8f0;
            --apis-hover: #f1f5f9;
            --apis-shadow: 0 20px 60px rgba(15, 23, 42, .22);
            --apis-overlay: rgba(15, 23, 42, .38);
        }
        [data-theme="dark"] {
            --apis-bg: #111826;
            --apis-fg: #e5e7eb;
            --apis-muted: #94a3b8;
            --apis-border: #263043;
            --apis-hover: #1b2434;
            --apis-shadow: 0 20px 60px rgba(0, 0, 0, .6);
            --apis-overlay: rgba(0, 0, 0, .55);
        }

        #apiSearchButton {
            position: fixed;
            top: 12px;
            right: 16px;
            z-index: 40;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border: 1px solid var(--apis-border);
            border-radius: 8px;
            background: var(--apis-bg);
            color: var(--apis-muted);
            font: 500 13px/1 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
        }
        #apiSearchButton:hover { color: var(--apis-fg); border-color: var(--apis-muted); }
        #apiSearchButton kbd {
            padding: 2px 5px;
            border: 1px solid var(--apis-border);
            border-radius: 4px;
            font: 500 11px/1 ui-monospace, SFMono-Regular, Menlo, monospace;
            color: var(--apis-muted);
            background: var(--apis-hover);
        }

        #apiSearchOverlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: none;
            padding: 10vh 16px 16px;
            background: var(--apis-overlay);
            backdrop-filter: blur(2px);
        }
        #apiSearchOverlay.is-open { display: block; }

        #apiSearchPanel {
            max-width: 680px;
            margin: 0 auto;
            background: var(--apis-bg);
            color: var(--apis-fg);
            border: 1px solid var(--apis-border);
            border-radius: 12px;
            box-shadow: var(--apis-shadow);
            overflow: hidden;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        #apiSearchInput {
            width: 100%;
            box-sizing: border-box;
            padding: 16px 18px;
            border: 0;
            border-bottom: 1px solid var(--apis-border);
            outline: none;
            background: transparent;
            color: var(--apis-fg);
            font-size: 16px;
        }
        #apiSearchInput::placeholder { color: var(--apis-muted); }

        #apiSearchResults {
            max-height: 58vh;
            overflow-y: auto;
            margin: 0;
            padding: 6px;
            list-style: none;
        }
        #apiSearchResults li {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            cursor: pointer;
        }
        #apiSearchResults li.is-active { background: var(--apis-hover); }
        #apiSearchResults .apis-method {
            flex: none;
            min-width: 54px;
            padding: 3px 6px;
            border-radius: 5px;
            text-align: center;
            font: 700 10px/1.3 ui-monospace, SFMono-Regular, Menlo, monospace;
            letter-spacing: .04em;
            color: #fff;
            text-transform: uppercase;
        }
        .apis-method.m-get { background: #0ea5e9; }
        .apis-method.m-post { background: #16a34a; }
        .apis-method.m-put { background: #f59e0b; }
        .apis-method.m-patch { background: #d97706; }
        .apis-method.m-delete { background: #dc2626; }
        .apis-method.m-schema { background: #7c3aed; }
        #apiSearchResults .apis-text { min-width: 0; flex: 1; }
        #apiSearchResults .apis-title {
            font-size: 13.5px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        #apiSearchResults .apis-sub {
            font: 400 12px/1.4 ui-monospace, SFMono-Regular, Menlo, monospace;
            color: var(--apis-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        #apiSearchResults .apis-tag {
            flex: none;
            font-size: 11px;
            color: var(--apis-muted);
            border: 1px solid var(--apis-border);
            border-radius: 999px;
            padding: 2px 8px;
        }
        #apiSearchEmpty {
            padding: 26px 18px;
            text-align: center;
            color: var(--apis-muted);
            font-size: 13px;
        }
        #apiSearchFooter {
            display: flex;
            gap: 14px;
            padding: 8px 14px;
            border-top: 1px solid var(--apis-border);
            color: var(--apis-muted);
            font-size: 11.5px;
        }

        @media (max-width: 640px) {
            #apiSearchButton kbd { display: none; }
            #apiSearchOverlay { padding-top: 4vh; }
        }
    </style>
</head>
<body style="height: 100vh; overflow-y: hidden">
<elements-api
    id="docs"
    tryItCredentialsPolicy="{{ $config->get('ui.try_it_credentials_policy', 'include') }}"
    router="hash"
    @if($config->get('ui.hide_try_it')) hideTryIt="true" @endif
    @if($config->get('ui.hide_schemas')) hideSchemas="true" @endif
    @if($config->get('ui.logo')) logo="{{ $config->get('ui.logo') }}" @endif
    @if($config->get('ui.layout')) layout="{{ $config->get('ui.layout') }}" @endif
/>
<button id="apiSearchButton" type="button" title="Search the API docs">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
         stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/></svg>
    <span>Search</span>
    <kbd id="apiSearchHint">⌘K</kbd>
</button>

<div id="apiSearchOverlay" role="dialog" aria-modal="true" aria-label="Search API documentation">
    <div id="apiSearchPanel">
        <input id="apiSearchInput" type="text" autocomplete="off" spellcheck="false"
               placeholder="Search endpoints, paths, tags, schemas…" aria-controls="apiSearchResults">
        <ul id="apiSearchResults" role="listbox"></ul>
        <div id="apiSearchEmpty" hidden>No matching endpoints.</div>
        <div id="apiSearchFooter">
            <span>↑ ↓ to navigate</span><span>↵ to open</span><span>esc to close</span>
        </div>
    </div>
</div>

<script>
    const apiSpec = @json($spec);

    (async () => {
        const docs = document.getElementById('docs');
        docs.apiDescriptionDocument = apiSpec;
    })();

    /* ---------------- API search (custom addition) ---------------- */
    (() => {
        const METHODS = ['get', 'post', 'put', 'patch', 'delete', 'head', 'options', 'trace'];

        // Build the search index from the same OpenAPI document Elements renders.
        // Slugs mirror how Elements builds its hash routes, so navigation stays in sync.
        const index = [];

        Object.entries(apiSpec.paths || {}).forEach(([path, pathItem]) => {
            METHODS.forEach((method) => {
                const operation = pathItem[method];
                if (!operation || !operation.operationId) return;

                const tag = (operation.tags || [])[0] || '';
                index.push({
                    kind: 'operation',
                    method: method,
                    title: operation.summary || path,
                    sub: path,
                    tag: tag,
                    slug: '/operations/' + operation.operationId,
                    haystack: [method, path, operation.summary, operation.description, operation.operationId, (operation.tags || []).join(' ')]
                        .filter(Boolean).join(' ').toLowerCase(),
                });
            });
        });

        Object.entries((apiSpec.components || {}).schemas || {}).forEach(([name, schema]) => {
            index.push({
                kind: 'schema',
                method: 'schema',
                title: schema.title || name,
                sub: 'Schema',
                tag: '',
                slug: '/schemas/' + name,
                haystack: [name, schema.title, schema.description].filter(Boolean).join(' ').toLowerCase(),
            });
        });

        const button = document.getElementById('apiSearchButton');
        const overlay = document.getElementById('apiSearchOverlay');
        const input = document.getElementById('apiSearchInput');
        const list = document.getElementById('apiSearchResults');
        const empty = document.getElementById('apiSearchEmpty');
        const isMac = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent);

        document.getElementById('apiSearchHint').textContent = isMac ? '⌘K' : 'Ctrl K';

        let matches = [];
        let active = 0;

        const search = (query) => {
            const terms = query.toLowerCase().split(/\s+/).filter(Boolean);
            if (!terms.length) return index.slice(0, 40);

            return index
                .map((item) => {
                    if (!terms.every((term) => item.haystack.includes(term))) return null;
                    // Prefer hits in the visible title/path over description-only hits,
                    // and keep endpoints above schemas when they score the same.
                    const title = item.title.toLowerCase();
                    const path = item.sub.toLowerCase();
                    const score = terms.reduce((total, term) => {
                        const inTitle = title.indexOf(term);
                        const inPath = path.indexOf(term);
                        if (inPath !== -1) total += 3;
                        if (inTitle !== -1) total += inTitle === 0 ? 3 : 2;
                        return total;
                    }, 0) + (item.kind === 'operation' ? 1 : 0);
                    return {item: item, score: score};
                })
                .filter(Boolean)
                .sort((a, b) => b.score - a.score)
                .slice(0, 60)
                .map((entry) => entry.item);
        };

        const render = () => {
            list.innerHTML = '';
            empty.hidden = matches.length > 0;

            matches.forEach((item, i) => {
                const li = document.createElement('li');
                li.setAttribute('role', 'option');
                li.setAttribute('aria-selected', String(i === active));
                if (i === active) li.className = 'is-active';

                const method = document.createElement('span');
                method.className = 'apis-method m-' + item.method;
                method.textContent = item.kind === 'schema' ? 'MODEL' : item.method;

                const text = document.createElement('span');
                text.className = 'apis-text';
                const title = document.createElement('div');
                title.className = 'apis-title';
                title.textContent = item.title;
                const sub = document.createElement('div');
                sub.className = 'apis-sub';
                sub.textContent = item.sub;
                text.append(title, sub);

                li.append(method, text);

                if (item.tag) {
                    const tag = document.createElement('span');
                    tag.className = 'apis-tag';
                    tag.textContent = item.tag;
                    li.append(tag);
                }

                li.addEventListener('mouseenter', () => {
                    active = i;
                    highlight();
                });
                li.addEventListener('click', () => go(item));

                list.append(li);
            });
        };

        const highlight = () => {
            Array.from(list.children).forEach((li, i) => {
                li.className = i === active ? 'is-active' : '';
                li.setAttribute('aria-selected', String(i === active));
            });
            const current = list.children[active];
            if (current) current.scrollIntoView({block: 'nearest'});
        };

        const go = (item) => {
            if (!item) return;
            close();
            window.location.hash = '#' + item.slug;
        };

        const open = () => {
            overlay.classList.add('is-open');
            input.value = '';
            matches = search('');
            active = 0;
            render();
            input.focus();
        };

        const close = () => {
            overlay.classList.remove('is-open');
        };

        button.addEventListener('click', open);

        overlay.addEventListener('mousedown', (event) => {
            if (event.target === overlay) close();
        });

        input.addEventListener('input', () => {
            matches = search(input.value.trim());
            active = 0;
            render();
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                active = matches.length ? (active + 1) % matches.length : 0;
                highlight();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                active = matches.length ? (active - 1 + matches.length) % matches.length : 0;
                highlight();
            } else if (event.key === 'Enter') {
                event.preventDefault();
                go(matches[active]);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                close();
            }
        });

        document.addEventListener('keydown', (event) => {
            const isOpen = overlay.classList.contains('is-open');

            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                isOpen ? close() : open();
                return;
            }

            if (event.key === 'Escape' && isOpen) {
                close();
                return;
            }

            const target = event.target;
            const typing = target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable);
            if (event.key === '/' && !isOpen && !typing) {
                event.preventDefault();
                open();
            }
        });
    })();
</script>

@if($config->get('ui.theme', 'light') === 'system')
    <script>
        var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        function updateTheme(e) {
            if (e.matches) {
                window.document.documentElement.setAttribute('data-theme', 'dark');
                window.document.getElementsByName('color-scheme')[0].setAttribute('content', 'dark');
            } else {
                window.document.documentElement.setAttribute('data-theme', 'light');
                window.document.getElementsByName('color-scheme')[0].setAttribute('content', 'light');
            }
        }

        mediaQuery.addEventListener('change', updateTheme);
        updateTheme(mediaQuery);
    </script>
@endif
</body>
</html>
