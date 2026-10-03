/**
 * Canvas scenes behind the sign-in screen. Each scene is a factory:
 * (ctx, width, height, opts) => draw(timeMs). `opts` is live — the
 * pointer position (mx/my, 0..1), `inside`, `dark`, `anchor` and the
 * colour triplets in `c` — so scenes read it every frame.
 */
const TAU = Math.PI * 2;
const rand = (a, b) => a + Math.random() * (b - a);

export const SCENES = {
  /* Dotted planet turning, with sync arcs flying between branches. */
  globe(ctx, w, h, o) {
    const corner = o.anchor === 'corner';
    const R = corner ? Math.min(w, h) * 0.56 : Math.min(w, h) * 0.36;
    const cx = corner ? w * 0.74 : w * 0.5, cy = corner ? h * 0.7 : h * 0.5;
    const N = Math.round(Math.min(3200, R * R * 0.016)), pts = [];
    for (let i = 0; i < N; i++) {
      const y = 1 - (i / (N - 1)) * 2, r = Math.sqrt(1 - y * y), th = i * 2.399963;
      pts.push([Math.cos(th) * r, y, Math.sin(th) * r]);
    }
    const city = () => { const la = rand(-1, 1.1), lo = rand(0, TAU); return [Math.cos(la) * Math.cos(lo), Math.sin(la), Math.cos(la) * Math.sin(lo)]; };
    const cities = Array.from({ length: 14 }, city);
    const arcs = [];
    const spawn = () => {
      const a = cities[(Math.random() * cities.length) | 0]; let b = a;
      while (b === a) b = cities[(Math.random() * cities.length) | 0];
      arcs.push({ a, b, p: 0, v: rand(0.004, 0.008) });
    };
    let tilt = 0.38, spinBoost = 0;
    const proj = (p, rot) => {
      const ca = Math.cos(rot), sa = Math.sin(rot), cb = Math.cos(tilt), sb = Math.sin(tilt);
      const x = p[0] * ca + p[2] * sa, z = -p[0] * sa + p[2] * ca;
      const y = p[1] * cb - z * sb, z2 = p[1] * sb + z * cb;
      return [cx + x * R, cy - y * R, z2];
    };
    const slerp = (a, b, t) => {
      const d = Math.acos(Math.min(1, a[0] * b[0] + a[1] * b[1] + a[2] * b[2])) || 1e-6, s = Math.sin(d);
      const k1 = Math.sin((1 - t) * d) / s, k2 = Math.sin(t * d) / s, lift = 1 + Math.sin(Math.PI * t) * 0.28;
      return [(a[0] * k1 + b[0] * k2) * lift, (a[1] * k1 + b[1] * k2) * lift, (a[2] * k1 + b[2] * k2) * lift];
    };
    return (t) => {
      ctx.clearRect(0, 0, w, h);
      tilt += ((o.my - 0.5) * 0.5 + 0.38 - tilt) * 0.03;
      spinBoost += ((o.mx - 0.5) * 0.6 - spinBoost) * 0.03;
      const rot = t * 0.00012 + spinBoost;
      const halo = ctx.createRadialGradient(cx, cy, R * 0.85, cx, cy, R * 1.35);
      halo.addColorStop(0, `rgba(${o.c.accent},${o.dark ? 0.4 : 0.18})`); halo.addColorStop(1, `rgba(${o.c.accent},0)`);
      ctx.fillStyle = halo; ctx.beginPath(); ctx.arc(cx, cy, R * 1.35, 0, TAU); ctx.fill();
      for (const p of pts) {
        const [x, y, z] = proj(p, rot);
        const a = z > 0 ? 0.35 + z * 0.65 : 0.08;
        ctx.fillStyle = `rgba(${o.c.line},${a * (o.dark ? 1 : 0.8)})`;
        const d = z > 0 ? 1.4 + z * 0.9 : 1; ctx.fillRect(x, y, d, d);
      }
      for (const c of cities) {
        const [x, y, z] = proj(c, rot); if (z <= 0) continue;
        const pulse = (Math.sin(t * 0.004 + x) + 1) / 2;
        ctx.fillStyle = `rgba(${o.c.hot},${0.15 + pulse * 0.25})`; ctx.beginPath(); ctx.arc(x, y, 4 + pulse * 5, 0, TAU); ctx.fill();
        ctx.fillStyle = `rgb(${o.c.hot})`; ctx.beginPath(); ctx.arc(x, y, 2.2, 0, TAU); ctx.fill();
      }
      if (arcs.length < 6 && Math.random() < 0.03) spawn();
      for (let i = arcs.length - 1; i >= 0; i--) {
        const A = arcs[i]; A.p += A.v; if (A.p > 1.45) { arcs.splice(i, 1); continue; }
        const head = Math.min(1, A.p), tail = Math.max(0, A.p - 0.45);
        ctx.beginPath(); let started = false, front = true;
        for (let s = tail; s <= head; s += 0.02) {
          const [x, y, z] = proj(slerp(A.a, A.b, s), rot); if (z < -0.05) front = false;
          started ? ctx.lineTo(x, y) : (ctx.moveTo(x, y), started = true);
        }
        ctx.strokeStyle = `rgba(${o.c.hot},${front ? 0.85 : 0.2})`; ctx.lineWidth = 1.6; ctx.stroke();
        if (A.p <= 1) { const [x, y] = proj(slerp(A.a, A.b, head), rot); ctx.fillStyle = '#fff'; ctx.beginPath(); ctx.arc(x, y, 2.4, 0, TAU); ctx.fill(); }
      }
    };
  },

  /* Drifting nodes linked by proximity; data pulses travel the links; cursor joins in. */
  network(ctx, w, h, o) {
    const n = Math.min(150, Math.floor((w * h) / 9000)), P = [], pulses = [];
    for (let i = 0; i < n; i++) P.push({ x: rand(0, w), y: rand(0, h), vx: rand(-0.25, 0.25), vy: rand(-0.25, 0.25), r: rand(1, 2.4) });
    const L = 130;
    return () => {
      ctx.clearRect(0, 0, w, h);
      const mx = o.mx * w, my = o.my * h;
      for (const p of P) {
        const dx = mx - p.x, dy = my - p.y, d = Math.hypot(dx, dy);
        if (o.inside && d < 180) { p.vx += dx / d * 0.012; p.vy += dy / d * 0.012; }
        p.vx *= 0.995; p.vy *= 0.995; p.x += p.vx; p.y += p.vy;
        if (p.x < 0 || p.x > w) p.vx *= -1; if (p.y < 0 || p.y > h) p.vy *= -1;
      }
      ctx.lineWidth = 1;
      for (let i = 0; i < n; i++) for (let j = i + 1; j < n; j++) {
        const a = P[i], b = P[j], d = Math.hypot(a.x - b.x, a.y - b.y);
        if (d < L) {
          ctx.strokeStyle = `rgba(${o.c.line},${(1 - d / L) * (o.dark ? 0.35 : 0.28)})`;
          ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
          if (pulses.length < 18 && Math.random() < 0.0004) pulses.push({ a, b, p: 0 });
        }
      }
      if (o.inside) for (const p of P) {
        const d = Math.hypot(mx - p.x, my - p.y);
        if (d < 180) { ctx.strokeStyle = `rgba(${o.c.hot},${(1 - d / 180) * 0.6})`; ctx.beginPath(); ctx.moveTo(mx, my); ctx.lineTo(p.x, p.y); ctx.stroke(); }
      }
      for (const p of P) { ctx.fillStyle = `rgba(${o.c.line},${o.dark ? 0.9 : 0.7})`; ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, TAU); ctx.fill(); }
      for (let i = pulses.length - 1; i >= 0; i--) {
        const q = pulses[i]; q.p += 0.018; if (q.p >= 1) { pulses.splice(i, 1); continue; }
        const x = q.a.x + (q.b.x - q.a.x) * q.p, y = q.a.y + (q.b.y - q.a.y) * q.p;
        const g = ctx.createRadialGradient(x, y, 0, x, y, 9); g.addColorStop(0, `rgba(${o.c.hot},.95)`); g.addColorStop(1, `rgba(${o.c.hot},0)`);
        ctx.fillStyle = g; ctx.beginPath(); ctx.arc(x, y, 9, 0, TAU); ctx.fill();
      }
    };
  },

  /* Perspective floor rolling toward the viewer, light streaks racing down its lanes. */
  grid(ctx, w, h, o) {
    const hz = h * 0.48, lanes = 26, streaks = [];
    return (t) => {
      ctx.clearRect(0, 0, w, h);
      const vx = w / 2 + (o.mx - 0.5) * w * 0.2;
      const glow = ctx.createRadialGradient(vx, hz, 0, vx, hz, w * 0.6);
      glow.addColorStop(0, `rgba(${o.c.hot},${o.dark ? 0.35 : 0.2})`); glow.addColorStop(1, `rgba(${o.c.hot},0)`);
      ctx.fillStyle = glow; ctx.fillRect(0, 0, w, h);
      ctx.lineWidth = 1;
      for (let i = -lanes; i <= lanes; i++) {
        const bx = vx + i * (w / lanes) * 1.6;
        const g = ctx.createLinearGradient(0, hz, 0, h); g.addColorStop(0, `rgba(${o.c.line},0)`); g.addColorStop(1, `rgba(${o.c.line},${o.dark ? 0.35 : 0.25})`);
        ctx.strokeStyle = g; ctx.beginPath(); ctx.moveTo(vx, hz); ctx.lineTo(bx, h); ctx.stroke();
      }
      const off = (t * 0.0006) % 1;
      for (let k = 0; k < 22; k++) {
        const z = 22 - k - off; if (z <= 0.2) continue;
        const y = hz + (h - hz) * (1 / z) * 1.2; if (y > h) continue;
        ctx.strokeStyle = `rgba(${o.c.line},${Math.min(0.4, (y - hz) / (h - hz)) * (o.dark ? 1 : 0.75)})`;
        ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(w, y); ctx.stroke();
      }
      if (streaks.length < 12 && Math.random() < 0.12) streaks.push({ lane: ((Math.random() * lanes * 2) | 0) - lanes, p: 0, v: rand(0.006, 0.014) });
      for (let i = streaks.length - 1; i >= 0; i--) {
        const s = streaks[i]; s.p += s.v; if (s.p > 1.2) { streaks.splice(i, 1); continue; }
        const bx = vx + s.lane * (w / lanes) * 1.6, e = (p) => p * p;
        const p1 = Math.min(1, e(s.p)), p0 = Math.max(0, e(s.p - 0.3));
        const x1 = vx + (bx - vx) * p1, y1 = hz + (h - hz) * p1, x0 = vx + (bx - vx) * p0, y0 = hz + (h - hz) * p0;
        const g = ctx.createLinearGradient(x0, y0, x1, y1); g.addColorStop(0, `rgba(${o.c.hot},0)`); g.addColorStop(1, `rgba(${o.c.hot},.95)`);
        ctx.strokeStyle = g; ctx.lineWidth = 1 + p1 * 2.5; ctx.beginPath(); ctx.moveTo(x0, y0); ctx.lineTo(x1, y1); ctx.stroke();
        ctx.fillStyle = `rgba(${o.c.hot},${0.35 * p1})`; ctx.beginPath(); ctx.arc(x1, y1, 2 + p1 * 6, 0, TAU); ctx.fill();
      }
    };
  },
};
