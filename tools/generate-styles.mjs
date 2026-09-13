// 4 styles de logo LoginFennec alternatifs — chaque style dans son dossier.
// Usage : node tools/generate-styles.mjs
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';

/* ---------- PNG ---------- */
function crc32(buf) {
	if (!crc32.table) { crc32.table = new Int32Array(256); for (let n = 0; n < 256; n++) { let c = n; for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1; crc32.table[n] = c; } }
	let crc = -1; for (let i = 0; i < buf.length; i++) crc = (crc >>> 8) ^ crc32.table[(crc ^ buf[i]) & 0xff];
	return (crc ^ -1) >>> 0;
}
function chunk(type, data) { const len = Buffer.alloc(4); len.writeUInt32BE(data.length); const t = Buffer.from(type, 'ascii'); const crc = Buffer.alloc(4); crc.writeUInt32BE(crc32(Buffer.concat([t, data]))); return Buffer.concat([len, t, data, crc]); }
function encodePng(img) {
	const sig = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);
	const ihdr = Buffer.alloc(13); ihdr.writeUInt32BE(img.w, 0); ihdr.writeUInt32BE(img.h, 4); ihdr[8] = 8; ihdr[9] = 6;
	const raw = Buffer.alloc((img.w * 4 + 1) * img.h);
	for (let y = 0; y < img.h; y++) { raw[y * (img.w * 4 + 1)] = 0; img.data.copy(raw, y * (img.w * 4 + 1) + 1, y * img.w * 4, (y + 1) * img.w * 4); }
	return Buffer.concat([sig, chunk('IHDR', ihdr), chunk('IDAT', zlib.deflateSync(raw, { level: 9 })), chunk('IEND', Buffer.alloc(0))]);
}
function makeImage(w, h, transparent = false) { const img = { w, h, data: Buffer.alloc(w * h * 4, 0) }; if (!transparent) { for (let i = 3; i < img.data.length; i += 4) img.data[i] = 255; } return img; }
function setPx(img, x, y, [r, g, b, a]) { x = Math.round(x); y = Math.round(y); if (x < 0 || y < 0 || x >= img.w || y >= img.h) return; const i = (y * img.w + x) * 4; const sa = a / 255, da = img.data[i + 3] / 255, oa = sa + da * (1 - sa); if (oa === 0) return; img.data[i] = Math.round((r * sa + img.data[i] * da * (1 - sa)) / oa); img.data[i + 1] = Math.round((g * sa + img.data[i + 1] * da * (1 - sa)) / oa); img.data[i + 2] = Math.round((b * sa + img.data[i + 2] * da * (1 - sa)) / oa); img.data[i + 3] = Math.round(oa * 255); }
function fillCircle(img, cx, cy, rad, color) { for (let yy = Math.floor(cy - rad); yy <= cy + rad; yy++) { for (let xx = Math.floor(cx - rad); xx <= cx + rad; xx++) { const dx = xx - cx, dy = yy - cy; if (dx * dx + dy * dy <= rad * rad) setPx(img, xx, yy, color); } } }
function fillPolygon(img, pts, color) { if (!pts || !Array.isArray(pts) || pts.length < 3) return; const ys = pts.map(p => p[1]); const y0 = Math.max(0, Math.floor(Math.min(...ys))); const y1 = Math.min(img.h - 1, Math.ceil(Math.max(...ys))); for (let y = y0; y <= y1; y++) { const xs = []; for (let i = 0; i < pts.length; i++) { const a = pts[i], b = pts[(i + 1) % pts.length]; if ((a[1] <= y && b[1] > y) || (b[1] <= y && a[1] > y)) xs.push(a[0] + ((y - a[1]) * (b[0] - a[0])) / (b[1] - a[1])); } xs.sort((p, q) => p - q); for (let k = 0; k + 1 < xs.length; k += 2) { for (let x = Math.max(0, Math.round(xs[k])); x <= Math.min(img.w - 1, Math.round(xs[k + 1])); x++) setPx(img, x, y, color); } } }
function gradientDiag(img, c1, c2) { for (let y = 0; y < img.h; y++) { for (let x = 0; x < img.w; x++) { const t = (x + y) / (img.w + img.h); const i = (y * img.w + x) * 4; img.data[i] = Math.round(c1[0] + (c2[0] - c1[0]) * t); img.data[i + 1] = Math.round(c1[1] + (c2[1] - c1[1]) * t); img.data[i + 2] = Math.round(c1[2] + (c2[2] - c1[2]) * t); img.data[i + 3] = 255; } } }

/* ---------- Fennec géométrie ---------- */
const FENNEC = {
	earL: [[16, 2], [47, 11], [30, 46]], earR: [[84, 2], [53, 11], [70, 46]],
	tipL: [[16, 2], [47, 11], [40, 27]], tipR: [[84, 2], [53, 11], [60, 27]],
	earInL: [[27, 16], [42, 20], [32, 38]], earInR: [[73, 16], [58, 20], [68, 38]],
	head: [[29, 36], [71, 36], [86, 55], [71, 76], [50, 91], [29, 76], [14, 55]],
	cheekL: [[14, 55], [30, 68], [28, 81], [17, 69]], cheekR: [[86, 55], [70, 68], [72, 81], [83, 69]],
	muzzle: [[50, 58], [65, 72], [50, 91], [35, 72]],
	nose: [[50, 71], [55.5, 74.5], [50, 81], [44.5, 74.5]],
};
function scaleF(s, cx, cy, size) { const o = {}; for (const [k, pts] of Object.entries(s)) { o[k] = pts.map(([x, y]) => [cx + ((x - 50) * size) / 100, cy + ((y - 55) * size) / 100]); } return o; }
function outlineFennec(s) {
	// Contour unifié du fennec (silhouette extérieure)
	return [...s.earL, ...s.earR.slice().reverse(), ...s.head.slice().reverse()];
}

/* ---------- Style 1 : Minimal Line (trait fin) ---------- */
function styleMinimalLine(size) {
	const img = makeImage(size, size, true);
	const s = scaleF(SHAPES, size / 2, size / 2, size * 0.8);
	const stroke = 2.5;
	const c = [43, 36, 26, 255];
	// Dessine chaque arête comme un trait épais
	function drawEdge(a, b) {
		const dx = b[0] - a[0], dy = b[1] - a[1];
		const dist = Math.sqrt(dx * dx + dy * dy);
		const steps = Math.max(2, Math.round(dist));
		for (let i = 0; i <= steps; i++) {
			const t = i / steps;
			fillCircle(img, a[0] + dx * t, a[1] + dy * t, stroke / 2, c);
		}
	}
	const poly = outlineFennec(s);
	for (let i = 0; i < poly.length; i++) {
		drawEdge(poly[i], poly[(i + 1) % poly.length]);
	}
	// Yeux
	const eyeR = size * 0.025;
	fillCircle(img, size / 2 - size * 0.13, size / 2 - size * 0.01, eyeR, c);
	fillCircle(img, size / 2 + size * 0.13, size / 2 - size * 0.01, eyeR, c);
	// Nez
	fillPolygon(img, s.nose, c);
	return img;
}

/* ---------- Style 2 : Badge Cercle ---------- */
function styleCircleBadge(size) {
	const img = makeImage(size, size);
	const r = size / 2;
	// Cercle de fond
	fillCircle(img, r, r, r, [109, 93, 246, 255]);
	// Cercle intérieur blanc fin
	fillCircle(img, r, r, r * 0.92, [255, 255, 255, 255]);
	fillCircle(img, r, r, r * 0.88, [109, 93, 246, 255]);
	// Fennec blanc au centre
	const s = scaleF(SHAPES, r, r * 1.02, size * 0.62);
	for (const name of ['earL', 'earR', 'head']) {
		fillPolygon(img, s[name], WHITE);
	}
	// Yeux violets
	const eyeR = size * 0.022;
	fillCircle(img, r - size * 0.065, r * 0.95, eyeR, [109, 93, 246, 255]);
	fillCircle(img, r + size * 0.065, r * 0.95, eyeR, [109, 93, 246, 255]);
	// Nez
	fillPolygon(img, s.nose, [109, 93, 246, 255]);
	return img;
}

/* ---------- Style 3 : Silhouette Glow (fond sombre) ---------- */
function styleSilhouetteGlow(size) {
	const img = makeImage(size, size);
	// Fond très sombre
	gradientDiag(img, [15, 15, 35, 255], [30, 30, 60, 255]);
	const cx = size / 2, cy = size / 2, fsize = size * 0.68;
	const s = scaleF(SHAPES, cx, cy, fsize);
	// Halo (plusieurs cercles décroissants)
	for (let g = 5; g > 0; g--) {
		const glowR = fsize * 0.52 + g * size * 0.015;
		const alpha = Math.round(6 + g * 4);
		fillCircle(img, cx, cy, glowR, [109, 93, 246, alpha]);
	}
	// Silhouette blanche
	const white = [255, 255, 255, 255];
	for (const name of ['earL', 'earR', 'head']) { fillPolygon(img, s[name], white); }
	// Yeux violets (découpés dans le blanc)
	fillCircle(img, cx - fsize * 0.13, cy - fsize * 0.01, fsize * 0.05, [109, 93, 246, 255]);
	fillCircle(img, cx + fsize * 0.13, cy - fsize * 0.01, fsize * 0.05, [109, 93, 246, 255]);
	// Nez
	fillPolygon(img, s.nose, [200, 190, 255, 255]);
	return img;
}

/* ---------- Style 4 : Origami (triangles multi-tons) ---------- */
function styleOrigami(size) {
	const img = makeImage(size, size);
	gradientDiag(img, [255, 255, 255, 255], [240, 238, 248, 255]);
	const cx = size / 2, cy = size / 2, fsize = size * 0.65;
	const s = scaleF(SHAPES, cx, cy, fsize);
	// Triangles avec des tons dégradés d'orange pour effet plié
	const shades = [
		[249, 228, 183, 255], // très clair
		[244, 208, 152, 255], // clair
		[235, 185, 120, 255], // moyen
		[220, 160, 90, 255],  // foncé
		[200, 135, 70, 255],  // très foncé
	];
	// Tête découpée en triangles avec des tons différents
	const headTri = [
		[s.head[0], s.head[1], [50, 50]], [s.head[1], s.head[2], [50, 50]],
		[s.head[2], s.head[3], [50, 50]], [s.head[3], s.head[4], [50, 50]],
		[s.head[4], s.head[5], [50, 50]], [s.head[5], s.head[6], [50, 50]],
		[s.head[6], s.head[0], [50, 50]],
	];
	for (let i = 0; i < headTri.length; i++) {
		fillPolygon(img, headTri[i], shades[i % shades.length]);
	}
	// Oreilles
	fillPolygon(img, s.earL, shades[1]);
	fillPolygon(img, s.earR, shades[1]);
	fillPolygon(img, s.tipL, shades[4]);
	fillPolygon(img, s.tipR, shades[4]);
	fillPolygon(img, s.earInL, shades[3]);
	fillPolygon(img, s.earInR, shades[3]);
	// Museau
	fillPolygon(img, s.muzzle, WHITE);
	// Yeux
	const eyeR = fsize * 0.045;
	fillCircle(img, cx - fsize * 0.135, cy - fsize * 0.01, eyeR, [43, 36, 26, 255]);
	fillCircle(img, cx + fsize * 0.135, cy - fsize * 0.01, eyeR, [43, 36, 26, 255]);
	fillCircle(img, cx - fsize * 0.105, cy - fsize * 0.04, eyeR * 0.3, [255, 255, 255, 180]);
	fillCircle(img, cx + fsize * 0.105, cy - fsize * 0.04, eyeR * 0.3, [255, 255, 255, 180]);
	fillPolygon(img, s.nose, [43, 36, 26, 255]);
	return img;
}

/* ---------- Palettes ---------- */
const G1 = [91, 75, 214, 255];
const G2 = [177, 108, 234, 255];
function gradientDiagStub() {}

/* ---------- FENNEC shapes (utilisé par scaleF) ---------- */
const SHAPES = {
	earL: [[16, 2], [47, 11], [30, 46]], earR: [[84, 2], [53, 11], [70, 46]],
	head: [[29, 36], [71, 36], [86, 55], [71, 76], [50, 91], [29, 76], [14, 55]],
	earInL: [[27, 16], [42, 20], [32, 38]], earInR: [[73, 16], [58, 20], [68, 38]],
	nose: [[50, 71], [55.5, 74.5], [50, 81], [44.5, 74.5]],
};

/* ---------- Génération ---------- */
const out = path.join(process.cwd(), 'brand', 'styles');
fs.mkdirSync(out, { recursive: true });
const S = 1024;
const WHITE = [255, 255, 255, 255];
const DARK = [43, 36, 26, 255];

// 1) Minimal Line
fs.writeFileSync(path.join(out, 'style-minimal-line-1024.png'), encodePng(styleMinimalLine(S)));
console.log('OK style-minimal-line-1024.png');

// 2) Badge Cercle
fs.writeFileSync(path.join(out, 'style-circle-badge-1024.png'), encodePng(styleCircleBadge(S)));
console.log('OK style-circle-badge-1024.png');

// 3) Silhouette Glow
fs.writeFileSync(path.join(out, 'style-silhouette-glow-1024.png'), encodePng(styleSilhouetteGlow(S)));
console.log('OK style-silhouette-glow-1024.png');

// 4) Origami
fs.writeFileSync(path.join(out, 'style-origami-1024.png'), encodePng(styleOrigami(S)));
console.log('OK style-origami-1024.png');

console.log('4 styles alternatifs générés dans brand/styles/');
