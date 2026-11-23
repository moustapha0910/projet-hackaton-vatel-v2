/* Générateur de QR code autonome : texte UTF-8, correction d'erreur M, versions 1 à 13, aucune dépendance. */
var QRX = (function () {
  var ECC = [-1, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24, 24, 28, 28, 26, 26, 26];
  var BLK = [-1, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5, 5, 8, 9, 9, 10, 10, 11, 13, 14, 16];
  function utf8(s) { var o = [], i; s = unescape(encodeURIComponent(s)); for (i = 0; i < s.length; i++) o.push(s.charCodeAt(i)); return o; }
  function raw(v) { var r = (16 * v + 128) * v + 64; if (v >= 2) { var n = Math.floor(v / 7) + 2; r -= (25 * n - 10) * n - 55; if (v >= 7) r -= 36; } return r; }
  function dcw(v) { return Math.floor(raw(v) / 8) - ECC[v] * BLK[v]; }
  function mul(x, y) { var z = 0; for (var i = 7; i >= 0; i--) { z = (z << 1) ^ ((z >>> 7) * 0x11D); z ^= ((y >>> i) & 1) * x; } return z; }
  function divisor(d) { var r = [], i, j, root = 1; for (i = 0; i < d - 1; i++) r.push(0); r.push(1); for (i = 0; i < d; i++) { for (j = 0; j < r.length; j++) { r[j] = mul(r[j], root); if (j + 1 < r.length) r[j] ^= r[j + 1]; } root = mul(root, 2); } return r; }
  function rem(data, div) { var r = div.map(function () { return 0; }); data.forEach(function (b) { var f = b ^ r.shift(); r.push(0); div.forEach(function (c, i) { r[i] ^= mul(c, f); }); }); return r; }
  function interleave(data, v) {
    var nb = BLK[v], el = ECC[v], rc = Math.floor(raw(v) / 8), ns = nb - rc % nb, sl = Math.floor(rc / nb), blocks = [], div = divisor(el), k = 0, i;
    for (i = 0; i < nb; i++) { var len = sl - el + (i < ns ? 0 : 1), dat = data.slice(k, k + len); k += len; var ecc = rem(dat, div); if (i < ns) dat.push(0); blocks.push(dat.concat(ecc)); }
    var out = []; for (i = 0; i < blocks[0].length; i++) blocks.forEach(function (b, j) { if (i != sl - el || j >= ns) out.push(b[i]); });
    return out;
  }
  function build(text) {
    var d = utf8(text), v, bits = [];
    for (v = 1; v <= 13; v++) { if (4 + (v < 10 ? 8 : 16) + 8 * d.length <= dcw(v) * 8) break; }
    if (v > 13) throw new Error('adresse trop longue pour un QR code');
    function put(val, n) { for (var i = n - 1; i >= 0; i--) bits.push((val >>> i) & 1); }
    put(4, 4); put(d.length, v < 10 ? 8 : 16); d.forEach(function (b) { put(b, 8); });
    var cap = dcw(v) * 8; put(0, Math.min(4, cap - bits.length)); while (bits.length % 8) bits.push(0);
    for (var pad = 0xEC; bits.length < cap; pad ^= 0xEC ^ 0x11) put(pad, 8);
    var cw = []; for (var i = 0; i < bits.length; i += 8) { var x = 0; for (var k = 0; k < 8; k++) x = (x << 1) | bits[i + k]; cw.push(x); }
    return { v: v, cw: interleave(cw, v) };
  }
  function matrix(text) {
    var b = build(text), v = b.v, n = v * 4 + 17, m = [], f = [], i, j;
    for (i = 0; i < n; i++) { m.push(new Array(n).fill(false)); f.push(new Array(n).fill(false)); }
    function set(x, y, c) { m[y][x] = c; f[y][x] = true; }
    for (i = 0; i < n; i++) { set(6, i, i % 2 == 0); set(i, 6, i % 2 == 0); }
    function finder(x, y) { for (var dy = -4; dy <= 4; dy++) for (var dx = -4; dx <= 4; dx++) { var d = Math.max(Math.abs(dx), Math.abs(dy)), xx = x + dx, yy = y + dy; if (xx >= 0 && xx < n && yy >= 0 && yy < n) set(xx, yy, d != 2 && d != 4); } }
    finder(3, 3); finder(n - 4, 3); finder(3, n - 4);
    var na = v == 1 ? 0 : Math.floor(v / 7) + 2, pos = [];
    if (na) { var step = v == 32 ? 26 : Math.ceil((v * 4 + 4) / (na * 2 - 2)) * 2; pos = [6]; for (var p = n - 7; pos.length < na; p -= step) pos.splice(1, 0, p); }
    pos.forEach(function (x, a) { pos.forEach(function (y, c) { if (!((a == 0 && c == 0) || (a == 0 && c == na - 1) || (a == na - 1 && c == 0))) for (var dy = -2; dy <= 2; dy++) for (var dx = -2; dx <= 2; dx++) set(x + dx, y + dy, Math.max(Math.abs(dx), Math.abs(dy)) != 1); }); });
    function fmt(mask) {
      var r = mask; for (var k = 0; k < 10; k++) r = (r << 1) ^ ((r >>> 9) * 0x537);
      var bits = ((mask << 10) | r) ^ 0x5412; function bit(k) { return ((bits >>> k) & 1) != 0; }
      for (k = 0; k <= 5; k++) set(8, k, bit(k)); set(8, 7, bit(6)); set(8, 8, bit(7)); set(7, 8, bit(8)); for (k = 9; k < 15; k++) set(14 - k, 8, bit(k));
      for (k = 0; k < 8; k++) set(n - 1 - k, 8, bit(k)); for (k = 8; k < 15; k++) set(8, n - 15 + k, bit(k)); set(8, n - 8, true);
    }
    fmt(0);
    if (v >= 7) { var r = v; for (i = 0; i < 12; i++) r = (r << 1) ^ ((r >>> 11) * 0x1F25); var vb = (v << 12) | r; for (i = 0; i < 18; i++) { var c = ((vb >>> i) & 1) != 0, a = n - 11 + i % 3, bb = Math.floor(i / 3); set(a, bb, c); set(bb, a, c); } }
    var cw = b.cw, idx = 0;
    for (var right = n - 1; right >= 1; right -= 2) { if (right == 6) right = 5; for (var vert = 0; vert < n; vert++) for (j = 0; j < 2; j++) { var x = right - j, up = ((right + 1) & 2) == 0, y = up ? n - 1 - vert : vert; if (!f[y][x] && idx < cw.length * 8) { m[y][x] = ((cw[idx >>> 3] >>> (7 - (idx & 7))) & 1) != 0; idx++; } } }
    function mask(k) { for (var y = 0; y < n; y++) for (var x = 0; x < n; x++) { if (f[y][x]) continue; var inv; switch (k) { case 0: inv = (x + y) % 2 == 0; break; case 1: inv = y % 2 == 0; break; case 2: inv = x % 3 == 0; break; case 3: inv = (x + y) % 3 == 0; break; case 4: inv = (Math.floor(x / 3) + Math.floor(y / 2)) % 2 == 0; break; case 5: inv = x * y % 2 + x * y % 3 == 0; break; case 6: inv = (x * y % 2 + x * y % 3) % 2 == 0; break; default: inv = ((x + y) % 2 + x * y % 3) % 2 == 0; } if (inv) m[y][x] = !m[y][x]; } }
    function pen() { var p = 0, x, y, run, t; for (t = 0; t < 2; t++) for (y = 0; y < n; y++) { run = 1; for (x = 1; x < n; x++) { var a = t ? m[x][y] : m[y][x], b2 = t ? m[x - 1][y] : m[y][x - 1]; if (a == b2) { run++; if (run == 5) p += 3; else if (run > 5) p++; } else run = 1; } } for (y = 0; y < n - 1; y++) for (x = 0; x < n - 1; x++) { var c = m[y][x]; if (c == m[y][x + 1] && c == m[y + 1][x] && c == m[y + 1][x + 1]) p += 3; } return p; }
    var best = 0, bp = 1e9; for (var k = 0; k < 8; k++) { mask(k); fmt(k); var q = pen(); if (q < bp) { bp = q; best = k; } mask(k); }
    mask(best); fmt(best);
    return m;
  }
  function draw(el, text, px) {
    var m = matrix(text), n = m.length, q = 2, s = Math.max(2, Math.floor(px / (n + 2 * q))), c = document.createElement('canvas');
    c.width = c.height = (n + 2 * q) * s; var g = c.getContext('2d'); g.fillStyle = '#fff'; g.fillRect(0, 0, c.width, c.height); g.fillStyle = '#000';
    for (var y = 0; y < n; y++) for (var x = 0; x < n; x++) if (m[y][x]) g.fillRect((x + q) * s, (y + q) * s, s, s);
    c.style.display = 'block'; c.style.maxWidth = '100%'; c.style.height = 'auto'; el.innerHTML = ''; el.appendChild(c); return c;
  }
  return { matrix: matrix, draw: draw };
})();
if (typeof module !== 'undefined') module.exports = QRX;
