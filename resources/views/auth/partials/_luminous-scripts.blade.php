{{-- ====================================================================
     Luminous Gateway — shared interactive scripts for auth pages.
     All visual/interaction JS that's reused across login, forgot
     password, and any future auth surface lives here.
     ==================================================================== --}}
<script>
    /* ============================================================
       SUBMIT: ripple burst + loading spinner (works for any form
       inside the holo card that has #submitBtn and #submitRipple)
       ============================================================ */
    (function () {
        var btn = document.getElementById('submitBtn');
        var ripple = document.getElementById('submitRipple');
        if (!btn || !ripple) return;
        var form = btn.closest('form');

        btn.addEventListener('click', function (e) {
            var rect = btn.getBoundingClientRect();
            ripple.style.left = (e.clientX - rect.left) + 'px';
            ripple.style.top = (e.clientY - rect.top) + 'px';
            ripple.style.width = ripple.style.height = '50px';
            ripple.classList.remove('is-active');
            void ripple.offsetWidth;
            ripple.classList.add('is-active');
        });

        if (form) {
            form.addEventListener('submit', function () {
                btn.classList.add('is-loading');
            });
        }
    })();

    /* ============================================================
       HOLO CARD: 3D tilt + glare following cursor
       ============================================================ */
    (function () {
        var card = document.getElementById('holoCard');
        var glare = document.getElementById('holoGlare');
        if (!card || !glare) return;

        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var rafId = null;
        var tx = 0, ty = 0, cx = 0, cy = 0;
        var inside = false;

        function onMove(e) {
            var rect = card.getBoundingClientRect();
            var rx = (e.clientX - rect.left) / rect.width;
            var ry = (e.clientY - rect.top) / rect.height;
            glare.style.setProperty('--mx', (rx * 100) + '%');
            glare.style.setProperty('--my', (ry * 100) + '%');

            if (reduced) return;

            tx = Math.max(-1, Math.min(1, (rx - 0.5) * 2));
            ty = Math.max(-1, Math.min(1, (ry - 0.5) * 2));
            inside = true;
            if (!rafId) rafId = requestAnimationFrame(tick);
        }

        function onLeave() {
            inside = false;
            tx = 0; ty = 0;
            if (!rafId) rafId = requestAnimationFrame(tick);
        }

        function tick() {
            cx += (tx - cx) * 0.12;
            cy += (ty - cy) * 0.12;
            var rotY = cx * 5;
            var rotX = -cy * 5;
            card.style.transform = 'perspective(1000px) rotateX(' + rotX.toFixed(2) + 'deg) rotateY(' + rotY.toFixed(2) + 'deg)';

            if (Math.abs(tx - cx) > 0.001 || Math.abs(ty - cy) > 0.001 || inside) {
                rafId = requestAnimationFrame(tick);
            } else {
                rafId = null;
            }
        }

        card.addEventListener('mousemove', onMove);
        card.addEventListener('mouseleave', onLeave);
    })();

    /* ============================================================
       MAGNETIC SUBMIT: pulls button slightly toward cursor nearby
       ============================================================ */
    (function () {
        var btn = document.getElementById('submitBtn');
        if (!btn) return;
        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduced) return;

        function onMove(e) {
            var rect = btn.getBoundingClientRect();
            var dx = e.clientX - (rect.left + rect.width / 2);
            var dy = e.clientY - (rect.top + rect.height / 2);
            var dist = Math.sqrt(dx * dx + dy * dy);
            var radius = 140;
            if (dist < radius) {
                var force = (1 - dist / radius) * 12;
                btn.style.transform = 'translate(' + (dx / dist * force).toFixed(2) + 'px, ' + (dy / dist * force).toFixed(2) + 'px)';
            } else {
                btn.style.transform = '';
            }
        }

        window.addEventListener('mousemove', onMove);
        btn.addEventListener('mouseleave', function () { btn.style.transform = ''; });
    })();

    /* ============================================================
       CURSOR GLOW FOLLOWER (smooth trail)
       ============================================================ */
    (function () {
        var glow = document.getElementById('cursorGlow');
        if (!glow) return;
        if (window.matchMedia('(hover: none)').matches) return;

        var tx = 0, ty = 0, cx = 0, cy = 0;
        var raf = null, active = false;

        function onMove(e) {
            tx = e.clientX; ty = e.clientY;
            if (!active) {
                active = true;
                glow.classList.add('is-active');
            }
            if (!raf) raf = requestAnimationFrame(tick);
        }

        function onLeave() {
            active = false;
            glow.classList.remove('is-active');
        }

        function tick() {
            cx += (tx - cx) * 0.18;
            cy += (ty - cy) * 0.18;
            glow.style.transform = 'translate(' + cx.toFixed(2) + 'px, ' + cy.toFixed(2) + 'px) translate(-50%, -50%)';
            if (Math.abs(tx - cx) > 0.3 || Math.abs(ty - cy) > 0.3) {
                raf = requestAnimationFrame(tick);
            } else {
                raf = null;
            }
        }

        window.addEventListener('mousemove', onMove, { passive: true });
        window.addEventListener('mouseleave', onLeave);
    })();

    /* ============================================================
       GATEWAY CANVAS — dramatic constellation with cursor beams,
       twinkling stars, and periodic pulse waves. Colors derive
       from --bs-primary-rgb so the whole scene reflects the
       user's chosen theme scheme.
       ============================================================ */
    (function () {
        var canvas = document.getElementById('gatewayCanvas');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var DPR = Math.min(window.devicePixelRatio || 1, 2);

        var W, H, nodes, shootingStars, pulses;
        var mouse = { x: -1000, y: -1000, inside: false };
        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function readPrimary() {
            var cs = getComputedStyle(document.documentElement);
            var raw = (cs.getPropertyValue('--bs-primary-rgb') || '').trim();
            if (!raw) raw = (cs.getPropertyValue('--login-primary-rgb') || '').trim();
            if (!raw) raw = '99, 102, 241';
            var parts = raw.split(',').map(function (x) { return parseInt(x.trim(), 10) || 0; });
            if (parts.length < 3) parts = [99, 102, 241];
            return parts;
        }

        function mixWhite(rgb, pct) {
            return [
                Math.round(rgb[0] + (255 - rgb[0]) * pct),
                Math.round(rgb[1] + (255 - rgb[1]) * pct),
                Math.round(rgb[2] + (255 - rgb[2]) * pct)
            ].join(',');
        }
        function mixBlack(rgb, pct) {
            return [
                Math.round(rgb[0] * (1 - pct)),
                Math.round(rgb[1] * (1 - pct)),
                Math.round(rgb[2] * (1 - pct))
            ].join(',');
        }

        function buildThemes() {
            var p = readPrimary();
            var base = p.join(',');
            var light = mixWhite(p, 0.28);
            var lighter = mixWhite(p, 0.5);
            var softer = mixWhite(p, 0.7);
            var dark = mixBlack(p, 0.22);

            return {
                dark: {
                    nodeColors: [base, light, lighter, softer, dark],
                    lineColor: light,
                    mouseLine: lighter,
                    lineAlpha: 0.28,
                    mouseAlpha: 0.55,
                    starAlpha: 0.9,
                    brightCore: 0.85,
                    pulseColor: base
                },
                light: {
                    /* Dropped bright/lighter hues so particles don't fade
                       into the pastel day-mode background. Base + dark
                       variants read clearly against the warm gradient. */
                    nodeColors: [base, dark, base, dark, light],
                    lineColor: base,
                    mouseLine: dark,
                    lineAlpha: 0.22,
                    mouseAlpha: 0.4,
                    starAlpha: 0.75,
                    brightCore: 0.75,
                    pulseColor: base
                }
            };
        }

        var THEMES = buildThemes();
        var theme = THEMES[document.documentElement.getAttribute('data-theme') || 'dark'];

        var CFG = {
            count: 95,
            linkDist: 170,
            mouseRadius: 260,
            baseSpeed: 0.28,
            minSize: 1.4,
            maxSize: 3.6,
            pulseInterval: 9000,
            shootingInterval: 7000
        };

        function resize() {
            W = canvas.width = window.innerWidth * DPR;
            H = canvas.height = window.innerHeight * DPR;
            canvas.style.width = window.innerWidth + 'px';
            canvas.style.height = window.innerHeight + 'px';
        }

        function rand(a, b) { return a + Math.random() * (b - a); }

        function createNode() {
            var color = theme.nodeColors[Math.floor(Math.random() * theme.nodeColors.length)];
            return {
                x: Math.random() * W,
                y: Math.random() * H,
                vx: (Math.random() - 0.5) * CFG.baseSpeed * 2 * DPR,
                vy: (Math.random() - 0.5) * CFG.baseSpeed * 2 * DPR,
                r: (CFG.minSize + Math.random() * (CFG.maxSize - CFG.minSize)) * DPR,
                color: color,
                alpha: rand(0.35, 0.9),
                pulseSpeed: rand(0.006, 0.014),
                pulseOffset: Math.random() * Math.PI * 2
            };
        }

        function init() {
            resize();
            nodes = [];
            for (var i = 0; i < CFG.count; i++) nodes.push(createNode());
            shootingStars = [];
            pulses = [];
        }

        function spawnShootingStar() {
            var fromLeft = Math.random() > 0.5;
            var y = rand(0, H * 0.6);
            var angle = rand(15, 35) * Math.PI / 180;
            var speed = rand(6, 10) * DPR;
            shootingStars.push({
                x: fromLeft ? -50 : W + 50,
                y: y,
                vx: (fromLeft ? 1 : -1) * Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed,
                life: 1
            });
        }

        function spawnPulse() {
            pulses.push({
                x: W / 2,
                y: H / 2,
                radius: 0,
                maxRadius: Math.max(W, H) * 0.6,
                life: 1
            });
        }

        function drawNode(n, time) {
            var p = Math.sin(time * n.pulseSpeed + n.pulseOffset) * 0.3 + 0.7;
            var a = n.alpha * p;
            var s = n.r * (0.8 + p * 0.4);

            ctx.beginPath();
            ctx.arc(n.x, n.y, s * 3.5, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(' + n.color + ',' + (a * 0.08).toFixed(3) + ')';
            ctx.fill();

            ctx.beginPath();
            ctx.arc(n.x, n.y, s, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(' + n.color + ',' + a.toFixed(3) + ')';
            ctx.fill();

            ctx.beginPath();
            ctx.arc(n.x, n.y, s * 0.45, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(255,255,255,' + (a * theme.brightCore).toFixed(3) + ')';
            ctx.fill();
        }

        function drawLinks() {
            var am = theme.lineAlpha;
            var lc = theme.lineColor;
            var link = CFG.linkDist * DPR;
            for (var i = 0; i < nodes.length; i++) {
                for (var j = i + 1; j < nodes.length; j++) {
                    var dx = nodes[i].x - nodes[j].x;
                    var dy = nodes[i].y - nodes[j].y;
                    var d = Math.sqrt(dx * dx + dy * dy);
                    if (d < link) {
                        var alpha = (1 - d / link) * am;
                        ctx.beginPath();
                        ctx.moveTo(nodes[i].x, nodes[i].y);
                        ctx.lineTo(nodes[j].x, nodes[j].y);
                        ctx.strokeStyle = 'rgba(' + lc + ',' + alpha.toFixed(3) + ')';
                        ctx.lineWidth = 0.7 * DPR;
                        ctx.stroke();
                    }
                }
            }
        }

        function drawMouseLinks() {
            if (!mouse.inside) return;
            var mr = CFG.mouseRadius * DPR;
            var ma = theme.mouseAlpha;
            for (var i = 0; i < nodes.length; i++) {
                var dx = nodes[i].x - mouse.x;
                var dy = nodes[i].y - mouse.y;
                var d = Math.sqrt(dx * dx + dy * dy);
                if (d < mr) {
                    var alpha = (1 - d / mr) * ma;
                    var g = ctx.createLinearGradient(nodes[i].x, nodes[i].y, mouse.x, mouse.y);
                    g.addColorStop(0, 'rgba(' + nodes[i].color + ',' + alpha.toFixed(3) + ')');
                    g.addColorStop(1, 'rgba(' + theme.mouseLine + ',' + (alpha * 0.7).toFixed(3) + ')');
                    ctx.beginPath();
                    ctx.moveTo(nodes[i].x, nodes[i].y);
                    ctx.lineTo(mouse.x, mouse.y);
                    ctx.strokeStyle = g;
                    ctx.lineWidth = 1.1 * DPR;
                    ctx.stroke();
                }
            }

            var rg = ctx.createRadialGradient(mouse.x, mouse.y, 0, mouse.x, mouse.y, 80 * DPR);
            rg.addColorStop(0, 'rgba(' + theme.mouseLine + ',0.18)');
            rg.addColorStop(1, 'rgba(' + theme.mouseLine + ',0)');
            ctx.beginPath();
            ctx.arc(mouse.x, mouse.y, 80 * DPR, 0, Math.PI * 2);
            ctx.fillStyle = rg;
            ctx.fill();
        }

        function drawShootingStars() {
            for (var i = shootingStars.length - 1; i >= 0; i--) {
                var s = shootingStars[i];
                s.x += s.vx;
                s.y += s.vy;
                s.life -= 0.008;

                var tailLen = 120 * DPR;
                var g = ctx.createLinearGradient(
                    s.x, s.y,
                    s.x - s.vx * tailLen / 10, s.y - s.vy * tailLen / 10
                );
                g.addColorStop(0, 'rgba(255,255,255,' + Math.max(0, s.life).toFixed(3) + ')');
                g.addColorStop(1, 'rgba(' + theme.nodeColors[2] + ',0)');
                ctx.beginPath();
                ctx.moveTo(s.x, s.y);
                ctx.lineTo(s.x - s.vx * tailLen / 10, s.y - s.vy * tailLen / 10);
                ctx.strokeStyle = g;
                ctx.lineWidth = 2 * DPR;
                ctx.lineCap = 'round';
                ctx.stroke();

                ctx.beginPath();
                ctx.arc(s.x, s.y, 2.5 * DPR, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(255,255,255,' + Math.max(0, s.life).toFixed(3) + ')';
                ctx.fill();

                if (s.life <= 0 || s.x < -100 || s.x > W + 100) {
                    shootingStars.splice(i, 1);
                }
            }
        }

        function drawPulses() {
            for (var i = pulses.length - 1; i >= 0; i--) {
                var p = pulses[i];
                p.radius += Math.max(W, H) * 0.004;
                p.life = 1 - (p.radius / p.maxRadius);
                if (p.life <= 0) { pulses.splice(i, 1); continue; }
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                ctx.strokeStyle = 'rgba(' + theme.pulseColor + ',' + (p.life * 0.35).toFixed(3) + ')';
                ctx.lineWidth = 1.5 * DPR;
                ctx.stroke();
            }
        }

        function update() {
            for (var i = 0; i < nodes.length; i++) {
                var n = nodes[i];
                n.x += n.vx;
                n.y += n.vy;

                if (mouse.inside) {
                    var dx = n.x - mouse.x;
                    var dy = n.y - mouse.y;
                    var d = Math.sqrt(dx * dx + dy * dy);
                    var mr = CFG.mouseRadius * DPR * 0.5;
                    if (d < mr && d > 0) {
                        var f = (1 - d / mr) * 0.03;
                        n.vx += (dx / d) * f;
                        n.vy += (dy / d) * f;
                    }
                }

                var sp = Math.sqrt(n.vx * n.vx + n.vy * n.vy);
                if (sp > CFG.baseSpeed * DPR * 3) {
                    n.vx *= 0.97;
                    n.vy *= 0.97;
                }

                if (n.x < -60) n.x = W + 60;
                if (n.x > W + 60) n.x = -60;
                if (n.y < -60) n.y = H + 60;
                if (n.y > H + 60) n.y = -60;
            }
        }

        function frame(time) {
            ctx.clearRect(0, 0, W, H);
            update();
            drawLinks();
            drawMouseLinks();
            drawPulses();
            drawShootingStars();
            for (var i = 0; i < nodes.length; i++) drawNode(nodes[i], time);
            requestAnimationFrame(frame);
        }

        window.addEventListener('resize', resize);
        window.addEventListener('mousemove', function (e) {
            mouse.x = e.clientX * DPR;
            mouse.y = e.clientY * DPR;
            mouse.inside = true;
        }, { passive: true });
        window.addEventListener('mouseleave', function () {
            mouse.inside = false;
            mouse.x = -1000;
            mouse.y = -1000;
        });
        window.addEventListener('touchmove', function (e) {
            if (e.touches.length > 0) {
                mouse.x = e.touches[0].clientX * DPR;
                mouse.y = e.touches[0].clientY * DPR;
                mouse.inside = true;
            }
        }, { passive: true });
        window.addEventListener('touchend', function () {
            mouse.inside = false;
            mouse.x = -1000;
            mouse.y = -1000;
        });

        function refreshTheme() {
            THEMES = buildThemes();
            var t = document.documentElement.getAttribute('data-theme') || 'dark';
            theme = THEMES[t];
            if (nodes && nodes.length) {
                for (var i = 0; i < nodes.length; i++) {
                    nodes[i].color = theme.nodeColors[Math.floor(Math.random() * theme.nodeColors.length)];
                }
            }
        }
        var obs = new MutationObserver(refreshTheme);
        obs.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme', 'data-scheme', 'class', 'data-bs-theme']
        });

        init();
        requestAnimationFrame(frame);

        if (!reduced) {
            setInterval(spawnShootingStar, CFG.shootingInterval);
            setInterval(spawnPulse, CFG.pulseInterval);
            setTimeout(spawnShootingStar, 1200);
        }
    })();
</script>
