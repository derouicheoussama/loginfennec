// Génère le logo LoginFence : fennec (fennec fox, emblème algérien)
// en versions PNG (icône, transparent, wordmark) et SVG vectoriel.
// Usage : node tools/generate-logo.mjs
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';

/* ---------- Encodage PNG ---------- */

function crc32(buf) {
	if (!crc32.table) {
		crc32.table = new Int32Array(256);
		for (let n = 0; n < 256; n++) {
			let c = n;
			for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
			crc32.table[n] = c;
		}
	}
	let crc = -1;
	for (let i = 0; i < buf.length; i++) crc = (crc >>> 8) ^ crc32.table[(crc ^ buf[i]) & 0xff];
	return (crc ^ -1) >>> 0;
}

function chunk(type, data) {
	const len = Buffer.alloc(4);
	len.writeUInt32BE(data.length);
	const t = Buffer.from(type, 'ascii');
	const crc = Buffer.alloc(4);
	crc.writeUInt32BE(crc32(Buffer.concat([t, data])));
	return Buffer.concat([len, t, data, crc]);
}

function encodePng(img) {
	const sig = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);
	const ihdr = Buffer.alloc(13);
	ihdr.writeUInt32BE(img.w, 0);
	ihdr.writeUInt32BE(img.h, 4);
	ihdr[8] = 8;
	ihdr[9] = 6;
	const raw = Buffer.alloc((img.w * 4 + 1) * img.h);
	for (let y = 0; y < img.h; y++) {
		raw[y * (img.w * 4 + 1)] = 0;
		img.data.copy(raw, y * (img.w * 4 + 1) + 1, y * img.w * 4, (y + 1) * img.w * 4);
	}
	return Buffer.concat([
		sig,
		chunk('IHDR', ihdr),
		chunk('IDAT', zlib.deflateSync(raw, { level: 9 })),
		chunk('IEND', Buffer.alloc(0)),
	]);
}

/* ---------- Primitives ---------- */

function makeImage(w, h, transparent = false) {
	const img = { w, h, data: Buffer.alloc(w * h * 4, 0) };
	if (!transparent) {
		for (let i = 3; i < img.data.length; i += 4) img.data[i] = 255;
	}
	return img;
}

function setPx(img, x, y, [r, g, b, a]) {
	x = Math.round(x); y = Math.round(y);
	if (x < 0 || y < 0 || x >= img.w || y >= img.h) return;
	const i = (y * img.w + x) * 4;
	const sa = a / 255;
	const da = img.data[i + 3] / 255;
	const oa = sa + da * (1 - sa);
	if (oa === 0) return;
	img.data[i] = Math.round((r * sa + img.data[i] * da * (1 - sa)) / oa);
	img.data[i + 1] = Math.round((g * sa + img.data[i + 1] * da * (1 - sa)) / oa);
	img.data[i + 2] = Math.round((b * sa + img.data[i + 2] * da * (1 - sa)) / oa);
	img.data[i + 3] = Math.round(oa * 255);
}

function fillCircle(img, cx, cy, rad, color) {
	for (let yy = Math.floor(cy - rad); yy <= cy + rad; yy++) {
		for (let xx = Math.floor(cx - rad); xx <= cx + rad; xx++) {
			const dx = xx - cx, dy = yy - cy;
			if (dx * dx + dy * dy <= rad * rad) setPx(img, xx, yy, color);
		}
	}
}

function fillPolygon(img, pts, color) {
	const ys = pts.map((p) => p[1]);
	const y0 = Math.max(0, Math.floor(Math.min(...ys)));
	const y1 = Math.min(img.h - 1, Math.ceil(Math.max(...ys)));
	for (let y = y0; y <= y1; y++) {
		const xs = [];
		for (let i = 0; i < pts.length; i++) {
			const a = pts[i], b = pts[(i + 1) % pts.length];
			if ((a[1] <= y && b[1] > y) || (b[1] <= y && a[1] > y)) {
				xs.push(a[0] + ((y - a[1]) * (b[0] - a[0])) / (b[1] - a[1]));
			}
		}
		xs.sort((p, q) => p - q);
		for (let k = 0; k + 1 < xs.length; k += 2) {
			for (let x = Math.round(xs[k]); x <= Math.round(xs[k + 1]); x++) {
				setPx(img, x, y, color);
			}
		}
	}
}

function gradientDiag(img, c1, c2) {
	for (let y = 0; y < img.h; y++) {
		for (let x = 0; x < img.w; x++) {
			const t = (x + y) / (img.w + img.h);
			const i = (y * img.w + x) * 4;
			img.data[i] = Math.round(c1[0] + (c2[0] - c1[0]) * t);
			img.data[i + 1] = Math.round(c1[1] + (c2[1] - c1[1]) * t);
			img.data[i + 2] = Math.round(c1[2] + (c2[2] - c1[2]) * t);
			img.data[i + 3] = 255;
		}
	}
}

/* ---------- Police 5x7 (wordmark) ---------- */

const FONT = {
	A: ['.XXX.', 'X...X', 'X...X', 'XXXXX', 'X...X', 'X...X', 'X...X'],
	C: ['.XXX.', 'X...X', 'X....', 'X....', 'X....', 'X...X', '.XXX.'],
	E: ['XXXXX', 'X....', 'X....', 'XXXX.', 'X....', 'X....', 'XXXXX'],
	F: ['XXXXX', 'X....', 'X....', 'XXXX.', 'X....', 'X....', 'X....'],
	G: ['.XXX.', 'X...X', 'X....', 'X..XX', 'X...X', 'X...X', '.XXXX'],
	I: ['XXXXX', '..X..', '..X..', '..X..', '..X..', '..X..', 'XXXXX'],
	L: ['X....', 'X....', 'X....', 'X....', 'X....', 'X....', 'XXXXX'],
	N: ['X...X', 'XX..X', 'X.X.X', 'X..XX', 'X...X', 'X...X', 'X...X'],
	O: ['.XXX.', 'X...X', 'X...X', 'X...X', 'X...X', 'X...X', '.XXX.'],
	P: ['XXXX.', 'X...X', 'X...X', 'XXXX.', 'X....', 'X....', 'X....'],
	R: ['XXXX.', 'X...X', 'X...X', 'XXXX.', 'X.X..', 'X..X.', 'X...X'],
	S: ['.XXXX', 'X....', 'X....', '.XXX.', '....X', '....X', 'XXXX.'],
	U: ['X...X', 'X...X', 'X...X', 'X...X', 'X...X', 'X...X', '.XXX.'],
	' ': ['.....', '.....', '.....', '.....', '.....', '.....', '.....'],
};

function textWidth(str, scale) {
	return str.length * 6 * scale - scale;
}

function drawText(img, str, x, y, scale, color) {
	let cursor = x;
	for (const ch of str.toUpperCase()) {
		const glyph = FONT[ch] || FONT[' '];
		for (let ry = 0; ry < 7; ry++) {
			for (let rx = 0; rx < 5; rx++) {
				if (glyph[ry][rx] === 'X') fillPolygon(img, [[cursor + rx * scale, y + ry * scale], [cursor + (rx + 1) * scale, y + ry * scale], [cursor + (rx + 1) * scale, y + (ry + 1) * scale], [cursor + rx * scale, y + (ry + 1) * scale]], color);
			}
		}
		cursor += 6 * scale;
	}
}

/* ---------- Le fennec ---------- */

// Palette (pelage du fennec du Sahara)
const CREAM = [249, 240, 216, 255];
const SAND = [238, 216, 168, 255];
const EAR_IN = [226, 168, 132, 255];
const DARK = [43, 36, 26, 255];
const WHITE = [255, 255, 255, 255];
const PURPLE = [109, 93, 246, 255];
const PURPLE_D = [91, 75, 214, 255];

// Géométrie dans un repère 0-100, tête centrée sur (50, 55)
function fennecShapes() {
	return {
		earL: [[17, 2], [46, 12], [30, 46]],
		earR: [[83, 2], [54, 12], [70, 46]],
		earInL: [[25, 12], [41, 17], [31, 36]],
		earInR: [[75, 12], [59, 17], [69, 36]],
		head: [[30, 36], [70, 36], [85, 55], [70, 76], [50, 90], [30, 76], [15, 55]],
		cheekL: [[15, 55], [30, 68], [28, 80], [18, 70]],
		cheekR: [[85, 55], [70, 68], [72, 80], [82, 70]],
		muzzle: [[50, 58], [65, 72], [50, 90], [35, 72]],
		nose: [[50, 71], [55.5, 74.5], [50, 81], [44.5, 74.5]],
	};
}

function scaleShapes(shapes, cx, cy, size) {
	const out = {};
	for (const [name, pts] of Object.entries(shapes)) {
		out[name] = pts.map(([x, y]) => [cx + ((x - 50) * size) / 100, cy + ((y - 55) * size) / 100]);
	}
	return out;
}

function drawFennec(img, cx, cy, size, opts = {}) {
	const s = scaleShapes(fennecShapes(), cx, cy, size);
	const fur = opts.fur || CREAM;
	const dark = opts.dark || DARK;
	fillPolygon(img, s.earL, fur);
	fillPolygon(img, s.earR, fur);
	fillPolygon(img, s.earInL, EAR_IN);
	fillPolygon(img, s.earInR, EAR_IN);
	fillPolygon(img, s.head, fur);
	fillPolygon(img, s.cheekL, WHITE);
	fillPolygon(img, s.cheekR, WHITE);
	fillPolygon(img, s.muzzle, opts.muzzle || WHITE);
	const eyeR = (size * 5) / 100;
	fillCircle(img, cx - size * 0.135, cy - size * 0.015, eyeR, dark);
	fillCircle(img, cx + size * 0.135, cy - size * 0.015, eyeR, dark);
	fillCircle(img, cx - size * 0.105, cy - size * 0.05, eyeR * 0.32, WHITE);
	fillCircle(img, cx + size * 0.165, cy - size * 0.05, eyeR * 0.32, WHITE);
	fillPolygon(img, s.nose, dark);
}

/* ---------- Compositions ---------- */

const G1 = [91, 75, 214, 255];
const G2 = [177, 108, 234, 255];

const out = path.join(process.cwd(), 'brand');
fs.mkdirSync(out, { recursive: true });

// 1) Icône 512 : dégradé de marque + fennec crème
{
	const img = makeImage(512, 512);
	gradientDiag(img, G1, G2);
	fillCircle(img, 460, 60, 120, [255, 255, 255, 16]);
	fillCircle(img, 60, 470, 90, [255, 255, 255, 14]);
	drawFennec(img, 256, 276, 400);
	fs.writeFileSync(path.join(out, 'loginfence-icon-512.png'), encodePng(img));
	console.log('OK loginfence-icon-512.png');
}

// 2) Icône 128
{
	const img = makeImage(128, 128);
	gradientDiag(img, G1, G2);
	drawFennec(img, 64, 69, 100);
	fs.writeFileSync(path.join(out, 'loginfence-icon-128.png'), encodePng(img));
	console.log('OK loginfence-icon-128.png');
}

// 3) Fennec transparent 512 (fourrure violette de marque, pour fonds clairs)
{
	const img = makeImage(512, 512, true);
	drawFennec(img, 256, 266, 440, { fur: [PURPLE_D[0], PURPLE_D[1], PURPLE_D[2], 255], muzzle: [245, 242, 255, 255] });
	fs.writeFileSync(path.join(out, 'loginfence-fennec-transparent-512.png'), encodePng(img));
	console.log('OK loginfence-fennec-transparent-512.png');
}

// 4) Wordmark 1400x520 : fennec + LOGINFENCE + PRO
{
	const W = 1400, H = 520;
	const img = makeImage(W, H, true);
	drawFennec(img, 200, 260, 430);
	const ink = [43, 36, 26, 255];
	const purple = [109, 93, 246, 255];
	const s = 17;
	drawText(img, 'LOGIN', 400, 170, s, ink);
	drawText(img, 'FENCE', 400, 170 + s * 9, s, ink);
	const pro = 'PRO';
	const ps = 10;
	const pw = textWidth(pro, ps);
	const px = 400 + textWidth('FENCE', s) + 50;
	const py = 345;
	fillPolygon(img, [[px - 14, py], [px + pw + 14, py], [px + pw + 14, py + ps * 9], [px - 14, py + ps * 9]], purple);
	drawText(img, pro, px, py + ps, ps, WHITE);
	fs.writeFileSync(path.join(out, 'loginfence-wordmark.png'), encodePng(img));
	console.log('OK loginfence-wordmark.png');
}

/* ---------- Version SVG vectorielle ---------- */

const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="512" height="512">
  <defs>
    <linearGradient id="lg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#5b4bd6"/>
      <stop offset="1" stop-color="#b16cea"/>
    </linearGradient>
  </defs>
  <rect width="100" height="100" rx="22" fill="url(#lg)"/>
  <polygon points="17,2 46,12 30,46" fill="#f9f0d8"/>
  <polygon points="83,2 54,12 70,46" fill="#f9f0d8"/>
  <polygon points="25,12 41,17 31,36" fill="#e2a884"/>
  <polygon points="75,12 59,17 69,36" fill="#e2a884"/>
  <polygon points="30,36 70,36 85,55 70,76 50,90 30,76 15,55" fill="#f9f0d8"/>
  <polygon points="15,55 30,68 28,80 18,70" fill="#ffffff"/>
  <polygon points="85,55 70,68 72,80 82,70" fill="#ffffff"/>
  <polygon points="50,58 65,72 50,90 35,72" fill="#ffffff"/>
  <circle cx="36.5" cy="53.5" r="5" fill="#2b241a"/>
  <circle cx="63.5" cy="53.5" r="5" fill="#2b241a"/>
  <circle cx="34.9" cy="51.9" r="1.5" fill="#ffffff"/>
  <circle cx="61.9" cy="51.9" r="1.5" fill="#ffffff"/>
  <polygon points="50,71 55.5,74.5 50,81 44.5,74.5" fill="#2b241a"/>
</svg>
`;
fs.writeFileSync(path.join(out, 'loginfence-logo.svg'), svg);
console.log('OK loginfence-logo.svg');
console.log('Logo LoginFence (fennec) généré dans brand/');
