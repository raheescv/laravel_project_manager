{{--
    Live alerts pushed through Pusher (App\Events\NotificationCreatedEvent). Include once per page.

    An online order (kind "online_order" — paid on the showcase / landing website) chimes,
    stays on screen until someone opens or dismisses it, chimes again every minute while it
    is waiting (three times at most), flashes the tab title when the tab is in the background
    and raises a desktop notification. Two tabs open on one PC chime once between them.
    Sound can be muted per browser from the alert itself. Anything else is a short toast.

    Only for users with browser notifications switched on.
--}}
@auth
    @if (auth()->user()->is_browser_notification_enabled)
        @once
            <style>
                .lnx-stack {
                    position: fixed;
                    top: 72px;
                    right: 16px;
                    z-index: 2000;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    width: min(360px, calc(100vw - 32px));
                    pointer-events: none;
                }

                .lnx-card {
                    pointer-events: auto;
                    background: var(--bs-body-bg, #fff);
                    color: var(--bs-body-color, #212529);
                    border: 1px solid var(--bs-border-color, #dee2e6);
                    border-left: 4px solid var(--bs-success, #198754);
                    border-radius: 12px;
                    box-shadow: 0 12px 32px rgba(0, 0, 0, .18);
                    padding: 12px 14px;
                    animation: lnx-in .28s ease-out;
                }

                .lnx-card.lnx-info {
                    border-left-color: var(--bs-primary, #0d6efd);
                }

                .lnx-card.lnx-leaving {
                    opacity: 0;
                    transform: translateX(24px);
                    transition: all .2s ease-in;
                }

                @keyframes lnx-in {
                    from {
                        opacity: 0;
                        transform: translateX(24px);
                    }

                    to {
                        opacity: 1;
                        transform: none;
                    }
                }

                .lnx-head {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .lnx-badge {
                    flex: 0 0 36px;
                    height: 36px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 17px;
                    color: var(--bs-success, #198754);
                    background: rgba(var(--bs-success-rgb, 25, 135, 84), .12);
                }

                .lnx-info .lnx-badge {
                    color: var(--bs-primary, #0d6efd);
                    background: rgba(var(--bs-primary-rgb, 13, 110, 253), .12);
                }

                .lnx-heading {
                    flex: 1;
                    min-width: 0;
                }

                .lnx-kicker {
                    font-weight: 700;
                    font-size: 13px;
                    letter-spacing: .02em;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }

                .lnx-dot {
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    background: var(--bs-success, #198754);
                    animation: lnx-pulse 1.4s ease-out infinite;
                }

                @keyframes lnx-pulse {
                    0% {
                        box-shadow: 0 0 0 0 rgba(var(--bs-success-rgb, 25, 135, 84), .6);
                    }

                    100% {
                        box-shadow: 0 0 0 8px rgba(var(--bs-success-rgb, 25, 135, 84), 0);
                    }
                }

                .lnx-sub {
                    font-size: 12px;
                    color: var(--bs-secondary-color, #6c757d);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .lnx-x {
                    border: 0;
                    background: transparent;
                    color: var(--bs-secondary-color, #6c757d);
                    font-size: 20px;
                    line-height: 1;
                    padding: 2px 4px;
                    cursor: pointer;
                }

                .lnx-amount {
                    font-size: 22px;
                    font-weight: 800;
                    margin: 8px 0 2px;
                    font-variant-numeric: tabular-nums;
                }

                .lnx-who {
                    font-size: 13px;
                    font-weight: 600;
                }

                .lnx-chips {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 6px;
                    margin-top: 8px;
                }

                .lnx-chip {
                    font-size: 11px;
                    font-weight: 600;
                    padding: 2px 8px;
                    border-radius: 999px;
                    background: var(--bs-tertiary-bg, #f1f3f5);
                    border: 1px solid var(--bs-border-color, #dee2e6);
                }

                .lnx-chip-delivery {
                    color: var(--bs-warning-text-emphasis, #997404);
                    background: var(--bs-warning-bg-subtle, #fff3cd);
                    border-color: var(--bs-warning-border-subtle, #ffe69c);
                }

                .lnx-msg {
                    font-size: 13px;
                    margin-top: 6px;
                }

                .lnx-actions {
                    display: flex;
                    gap: 8px;
                    margin-top: 10px;
                }

                .lnx-actions .lnx-open {
                    flex: 1;
                }
            </style>

            <script>
                (function () {
                    var CHANNEL = @json(\App\Events\NotificationCreatedEvent::channelFor(auth()->id()));
                    var KEY = @json(config('broadcasting.connections.pusher.key'));
                    var CLUSTER = @json(config('broadcasting.connections.pusher.options.cluster'));
                    var MUTE_KEY = 'qloud.liveAlerts.muted';
                    var REMIND_EVERY = 60000;
                    var MAX_REMINDERS = 3;

                    var audio = null;
                    var stack = null;
                    var waiting = 0;
                    var reminders = 0;
                    var remindTimer = null;
                    var titleTimer = null;
                    var baseTitle = document.title;

                    function store(key, value) {
                        try {
                            if (value === undefined) {
                                return window.localStorage.getItem(key);
                            }
                            if (value === null) {
                                window.localStorage.removeItem(key);
                            } else {
                                window.localStorage.setItem(key, value);
                            }
                        } catch (e) {}
                        return null;
                    }

                    function isMuted() {
                        return store(MUTE_KEY) === '1';
                    }

                    function audioContext() {
                        var Ctx = window.AudioContext || window.webkitAudioContext;
                        if (!Ctx) {
                            return null;
                        }
                        if (!audio) {
                            audio = new Ctx();
                        }
                        if (audio.state === 'suspended') {
                            audio.resume().catch(function () {});
                        }
                        return audio;
                    }

                    /** A bright four-note bell, made in the browser — no sound file to load. */
                    function chime() {
                        if (isMuted()) {
                            return;
                        }
                        var ctx = audioContext();
                        if (!ctx) {
                            return;
                        }
                        var start = ctx.currentTime + 0.02;
                        [[659.25, 0], [830.61, 0.13], [987.77, 0.26], [1318.51, 0.42]].forEach(function (note) {
                            [[1, 0.32], [2, 0.06]].forEach(function (partial) {
                                var osc = ctx.createOscillator();
                                var gain = ctx.createGain();
                                var at = start + note[1];
                                osc.type = 'sine';
                                osc.frequency.value = note[0] * partial[0];
                                gain.gain.setValueAtTime(0.0001, at);
                                gain.gain.exponentialRampToValueAtTime(partial[1], at + 0.015);
                                gain.gain.exponentialRampToValueAtTime(0.0001, at + 1.1);
                                osc.connect(gain);
                                gain.connect(ctx.destination);
                                osc.start(at);
                                osc.stop(at + 1.15);
                            });
                        });
                    }

                    /** Only one open tab on this PC chimes for a given order. */
                    function claimChime(id) {
                        var key = 'qloud.liveAlerts.chimed.' + id;
                        var last = parseInt(store(key) || '0', 10);
                        if (Date.now() - last < 15000) {
                            return false;
                        }
                        store(key, String(Date.now()));
                        return true;
                    }

                    function el(tag, className, text) {
                        var node = document.createElement(tag);
                        if (className) {
                            node.className = className;
                        }
                        if (text !== undefined && text !== null) {
                            node.textContent = text;
                        }
                        return node;
                    }

                    function container() {
                        if (!stack) {
                            stack = el('div', 'lnx-stack');
                            stack.setAttribute('aria-live', 'assertive');
                            document.body.appendChild(stack);
                        }
                        return stack;
                    }

                    function bumpBell() {
                        document.querySelectorAll('[data-live-unread]').forEach(function (badge) {
                            badge.textContent = String((parseInt(badge.textContent, 10) || 0) + 1);
                        });
                    }

                    function flashTitle() {
                        if (titleTimer || !document.hidden) {
                            return;
                        }
                        baseTitle = document.title;
                        var on = false;
                        titleTimer = setInterval(function () {
                            on = !on;
                            document.title = on ? '● New online order' : baseTitle;
                        }, 1000);
                    }

                    function stopTitle() {
                        if (titleTimer) {
                            clearInterval(titleTimer);
                            titleTimer = null;
                            document.title = baseTitle;
                        }
                    }

                    function scheduleReminder() {
                        if (remindTimer) {
                            return;
                        }
                        remindTimer = setInterval(function () {
                            if (waiting < 1 || reminders >= MAX_REMINDERS) {
                                clearInterval(remindTimer);
                                remindTimer = null;
                                return;
                            }
                            reminders++;
                            chime();
                        }, REMIND_EVERY);
                    }

                    function dismiss(card, isOrder) {
                        if (card.dataset.gone) {
                            return;
                        }
                        card.dataset.gone = '1';
                        if (isOrder) {
                            waiting = Math.max(0, waiting - 1);
                            if (waiting === 0) {
                                stopTitle();
                            }
                        }
                        card.classList.add('lnx-leaving');
                        setTimeout(function () {
                            card.remove();
                        }, 220);
                    }

                    function muteButton() {
                        var button = el('button', 'btn btn-sm btn-outline-secondary');
                        button.type = 'button';
                        var paint = function () {
                            var muted = isMuted();
                            button.innerHTML = '';
                            button.appendChild(el('i', 'fa ' + (muted ? 'fa-volume-off' : 'fa-volume-up')));
                            button.title = muted ? 'Sound is off — turn on' : 'Sound is on — mute';
                            button.setAttribute('aria-label', button.title);
                        };
                        paint();
                        button.addEventListener('click', function () {
                            store(MUTE_KEY, isMuted() ? null : '1');
                            document.querySelectorAll('.lnx-stack .lnx-mute').forEach(function (other) {
                                other.dispatchEvent(new Event('lnx:repaint'));
                            });
                            if (!isMuted()) {
                                chime();
                            }
                        });
                        button.classList.add('lnx-mute');
                        button.addEventListener('lnx:repaint', paint);
                        return button;
                    }

                    function orderCard(data) {
                        var meta = data.meta || {};
                        var card = el('div', 'lnx-card');
                        card.setAttribute('role', 'alert');

                        var head = el('div', 'lnx-head');
                        var badge = el('span', 'lnx-badge');
                        badge.appendChild(el('i', 'fa fa-globe'));
                        var heading = el('div', 'lnx-heading');
                        var kicker = el('div', 'lnx-kicker');
                        kicker.appendChild(el('span', 'lnx-dot'));
                        kicker.appendChild(document.createTextNode('New online order'));
                        heading.appendChild(kicker);
                        var at = new Date();
                        heading.appendChild(el('div', 'lnx-sub', 'Paid online · ' + at.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })));
                        var close = el('button', 'lnx-x', '×');
                        close.type = 'button';
                        close.setAttribute('aria-label', 'Dismiss');
                        close.addEventListener('click', function () {
                            dismiss(card, true);
                        });
                        head.appendChild(badge);
                        head.appendChild(heading);
                        head.appendChild(close);
                        card.appendChild(head);

                        if (meta.amount) {
                            card.appendChild(el('div', 'lnx-amount', meta.amount));
                        }
                        var who = [meta.customer, meta.mobile].filter(Boolean).join(' · ');
                        card.appendChild(who ? el('div', 'lnx-who', who) : el('div', 'lnx-msg', data.content));

                        var chips = el('div', 'lnx-chips');
                        if (meta.fulfilment) {
                            var fulfilment = el('span', 'lnx-chip' + (meta.fulfilment === 'Delivery' ? ' lnx-chip-delivery' : ''));
                            fulfilment.appendChild(el('i', 'fa ' + (meta.fulfilment === 'Delivery' ? 'fa-truck' : 'fa-shopping-cart')));
                            fulfilment.appendChild(document.createTextNode(' ' + meta.fulfilment));
                            chips.appendChild(fulfilment);
                        }
                        if (meta.items) {
                            chips.appendChild(el('span', 'lnx-chip', meta.items + (meta.items === 1 ? ' item' : ' items')));
                        }
                        if (meta.invoice_no) {
                            chips.appendChild(el('span', 'lnx-chip', '#' + meta.invoice_no));
                        }
                        if (chips.childNodes.length) {
                            card.appendChild(chips);
                        }

                        var actions = el('div', 'lnx-actions');
                        if (data.link) {
                            var open = el('a', 'btn btn-sm btn-success lnx-open');
                            open.href = data.link;
                            open.appendChild(el('i', 'fa fa-external-link'));
                            open.appendChild(document.createTextNode(' Open order'));
                            open.addEventListener('click', function () {
                                dismiss(card, true);
                            });
                            actions.appendChild(open);
                        }
                        actions.appendChild(muteButton());
                        card.appendChild(actions);

                        return card;
                    }

                    function infoCard(data) {
                        var card = el('div', 'lnx-card lnx-info');
                        var head = el('div', 'lnx-head');
                        var badge = el('span', 'lnx-badge');
                        badge.appendChild(el('i', 'fa fa-bell'));
                        var heading = el('div', 'lnx-heading');
                        heading.appendChild(el('div', 'lnx-kicker', data.title));
                        var close = el('button', 'lnx-x', '×');
                        close.type = 'button';
                        close.setAttribute('aria-label', 'Dismiss');
                        close.addEventListener('click', function (event) {
                            event.stopPropagation();
                            dismiss(card, false);
                        });
                        head.appendChild(badge);
                        head.appendChild(heading);
                        head.appendChild(close);
                        card.appendChild(head);
                        card.appendChild(el('div', 'lnx-msg', data.content));
                        if (data.link) {
                            card.style.cursor = 'pointer';
                            card.addEventListener('click', function () {
                                window.location.href = data.link;
                            });
                        }
                        setTimeout(function () {
                            dismiss(card, false);
                        }, 8000);
                        return card;
                    }

                    function desktop(data, isOrder) {
                        if (!window.Notification || Notification.permission !== 'granted') {
                            return;
                        }
                        var meta = data.meta || {};
                        var body = isOrder
                            ? [meta.amount, meta.customer, meta.fulfilment].filter(Boolean).join(' · ') || data.content
                            : data.content;
                        try {
                            var note = new Notification(isOrder ? 'New online order' : data.title, {
                                body: body,
                                icon: '/favicon.png',
                                tag: isOrder && meta.sale_id ? 'online-order-' + meta.sale_id : undefined,
                                requireInteraction: isOrder,
                                silent: isOrder,
                            });
                            note.onclick = function () {
                                window.focus();
                                if (data.link) {
                                    window.location.href = data.link;
                                }
                                note.close();
                            };
                        } catch (e) {}
                    }

                    function show(data) {
                        var isOrder = data.kind === 'online_order';
                        bumpBell();
                        desktop(data, isOrder);

                        if (!isOrder) {
                            if (window.toastr) {
                                toastr.info(data.content, data.title, {
                                    closeButton: true,
                                    progressBar: true,
                                    timeOut: 7000,
                                    onclick: data.link ? function () { window.location.href = data.link; } : null,
                                });
                            } else {
                                container().prepend(infoCard(data));
                            }
                            return;
                        }

                        container().prepend(orderCard(data));
                        waiting++;
                        reminders = 0;
                        flashTitle();
                        if (claimChime((data.meta || {}).sale_id || data.link || data.content)) {
                            chime();
                        }
                        scheduleReminder();
                    }

                    function connect() {
                        if (!window.Pusher || !KEY) {
                            return;
                        }
                        var pusher = new Pusher(KEY, { cluster: CLUSTER });
                        pusher.subscribe(CHANNEL).bind('user-notification-event', show);
                    }

                    // Browsers only let a page make sound after the user has touched it.
                    ['pointerdown', 'keydown'].forEach(function (type) {
                        document.addEventListener(type, function unlock() {
                            audioContext();
                            document.removeEventListener(type, unlock, true);
                        }, true);
                    });

                    document.addEventListener('click', function askOnce() {
                        if (window.Notification && Notification.permission === 'default') {
                            Notification.requestPermission();
                        }
                    }, { once: true });

                    document.addEventListener('visibilitychange', function () {
                        if (!document.hidden) {
                            stopTitle();
                        }
                    });

                    if (window.Pusher) {
                        connect();
                    } else {
                        var script = document.createElement('script');
                        script.src = 'https://js.pusher.com/8.2.0/pusher.min.js';
                        script.onload = connect;
                        document.head.appendChild(script);
                    }
                })();
            </script>
        @endonce
    @endif
@endauth
