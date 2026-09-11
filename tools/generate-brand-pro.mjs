// Pack logo professionnel LoginFennec Pro : icône, écusson bouclier,
// monochromes, wordmarks clair/sombre, bannières wp.org et SVG vectoriels.
// Usage : node tools/generate-brand-pro.mjs
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

function eraseCircle(img, cx, cy, rad) {
	for (let yy = Math.floor(cy - rad); yy <= cy + rad; yy++) {
		for (let xx = Math.floor(cx - rad); xx <= cx + rad; xx++) {
			const dx = xx - cx, dy = yy - cy;
			if (xx < 0 || yy < 0 || xx >= img.w || yy >= img.h) continue;
			if (dx * dx + dy * dy <= rad * rad) {
				const i = (yy * img.w + xx) * 4;
				img.data[i + 3] = 0;
			}
		}
	}
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

/* ---------- Police 5x7 ---------- */

const FONT = {
	A: ['.XXX.', 'X...X', 'X...X', 'XXXXX', 'X...X', 'X...X', 'X...X'],
	B: ['XXXX.', 'X...X', 'X...X', 'XXXX.', 'X...X', 'X...X', 'XXXX.'],
	C: ['.XXX.', 'X...X', 'X....', 'X....', 'X....', 'X...X', '.XXX.'],
	D: ['XXXX.', 'X...X', 'X...X', 'X...X', 'X...X', 'X...X', 'XXXX.'],
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
	T: ['XXXXX', '..X..', '..X..', '..X..', '..X..', '..X..', '..X..'],
	U: ['X...X', 'X...X', 'X...X', 'X...X', 'X...X', 'X...X', '.XXX.'],
	Y: ['X...X', 'X...X', '.X.X.', '..X..', '..X..', '..X..', '..X..'],
	'&': ['.XX..', 'X..X.', 'X..X.', '.XX..', 'X.X.X', 'X..X.', '.XX.X'],
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
				if (glyph[ry][rx] === 'X') {
					fillPolygon(img, [
						[cursor + rx * scale, y + ry * scale],
						[cursor + (rx + 1) * scale, y + ry * scale],
						[cursor + (rx + 1) * scale, y + (ry + 1) * scale],
						[cursor + rx * scale, y + (ry + 1) * scale],
					], color);
				}
			}
		}
		cursor += 6 * scale;
	}
}

/* ---------- Le fennec (oreilles à pointes noires, regard vif) ---------- */

const CREAM = [249, 240, 216, 255];
const EAR_IN = [226, 168, 132, 255];
const EAR_TIP = [43, 36, 26, 255];
const DARK = [43, 36, 26, 255];
const WHITE = [255, 255, 255, 255];
const PURPLE = [109, 93, 246, 255];
const PURPLE_D = [91, 75, 214, 255];

function fennecShapes() {
	return {
		earL: [[16, 2], [47, 11], [30, 46]],
		earR: [[84, 2], [53, 11], [70, 46]],
		tipL: [[16, 2], [47, 11], [41, 27]],
		tipR: [[84, 2], [53, 11], [59, 27]],
		earInL: [[27, 16], [42, 20], [32, 38]],
		earInR: [[73, 16], [58, 20], [68, 38]],
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
	const tip = opts.tip || EAR_TIP;
	const dark = opts.dark || DARK;
	const noTips = !!opts.noTips;
	fillPolygon(img, s.earL, fur);
	fillPolygon(img, s.earR, fur);
	if (!noTips) {
		fillPolygon(img, s.tipL, tip);
		fillPolygon(img, s.tipR, tip);
	}
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

// Fennec monochrome (silhouette + découpes transparentes)
function drawFennecMono(img, cx, cy, size, color) {
	const s = scaleShapes(fennecShapes(), cx, cy, size);
	fillPolygon(img, s.earL, color);
	fillPolygon(img, s.earR, color);
	fillPolygon(img, s.head, color);
	const eyeR = (size * 5.5) / 100;
	eraseCircle(img, cx - size * 0.135, cy - size * 0.015, eyeR);
	eraseCircle(img, cx + size * 0.135, cy - size * 0.015, eyeR);
	erasePolygon(img, s.nose);
}

function erasePolygon(img, pts) {
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
				const xx = x, yy = y;
				if (xx < 0 || yy < 0 || xx >= img.w || yy >= img.h) continue;
				const i = (yy * img.w + xx) * 4;
				img.data[i + 3] = 0;
			}
		}
	}
}

/* ---------- Palettes ---------- */

const G1 = [91, 75, 214, 255];
const G2 = [177, 108, 234, 255];
const INK = [30, 27, 50, 255];

const out = path.join(process.cwd(), 'brand', 'pro');
fs.mkdirSync(out, { recursive: true });

/* 1) Icône principale 1024 : dégradé + fennec détaillé */
{
	const img = makeImage(1024, 1024);
	gradientDiag(img, G1, G2);
	fillCircle(img, 920, 120, 240, [255, 255, 255, 16]);
	fillCircle(img, 120, 940, 180, [255, 255, 255, 14]);
	drawFennec(img, 512, 552, 800);
	fs.writeFileSync(path.join(out, 'loginfennec-icon-1024.png'), encodePng(img));
	console.log('OK loginfennec-icon-1024.png');
}

/* 2) Écusson bouclier 1024 : le fennec gardien */
{
	const img = makeImage(1024, 1024);
	gradientDiag(img, G1, G2);
	const sh = [[512, 40], [930, 170], [930, 520], [512, 980], [94, 520], [94, 170]];
	fillPolygon(img, sh, [255, 255, 255, 26]);
	// bord intérieur blanc
	const inner = sh.map(([x, y]) => [512 + (x - 512) * 0.92, 510 + (y - 510) * 0.92]);
	fillPolygon(img, inner, G1);
	gradientPolygon(img, inner, G1, G2);
	drawFennec(img, 512, 470, 560);
	fs.writeFileSync(path.join(out, 'loginfennec-shield-1024.png'), encodePng(img));
	console.log('OK loginfennec-shield-1024.png');
}

function gradientPolygon(img, pts, c1, c2) {
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
			for (let x = Math.max(0, Math.round(xs[k])); x <= Math.min(img.w - 1, Math.round(xs[k + 1])); x++) {
				const t = (x + y) / (img.w + img.h);
				const i = (y * img.w + x) * 4;
				img.data[i] = Math.round(c1[0] + (c2[0] - c1[0]) * t);
				img.data[i + 1] = Math.round(c1[1] + (c2[1] - c1[1]) * t);
				img.data[i + 2] = Math.round(c1[2] + (c2[2] - c1[2]) * t);
				img.data[i + 3] = 255;
			}
		}
	}
}

/* 3) Monochromes 1024 (noir et blanc, découpes) */
{
	const img = makeImage(1024, 1024, true);
	drawFennecMono(img, 512, 522, 860, [30, 27, 50, 255]);
	fs.writeFileSync(path.join(out, 'loginfennec-mono-black-1024.png'), encodePng(img));
	console.log('OK loginfennec-mono-black-1024.png');

	const img2 = makeImage(1024, 1024, true);
	drawFennecMono(img2, 512, 522, 860, [255, 255, 255, 255]);
	fs.writeFileSync(path.join(out, 'loginfennec-mono-white-1024.png'), encodePng(img2));
	console.log('OK loginfennec-mono-white-1024.png');
}

/* 4) Wordmarks : sombre (fond clair) et clair (fond sombre) */
function wordmark(file, ink, badge, badgeText, transparent) {
	const W = 1400, H = 520;
	const img = makeImage(W, H, true);
	if (!transparent) {
		for (let i = 3; i < img.data.length; i += 4) img.data[i] = 255;
	}
	drawFennec(img, 200, 260, 430, transparent ? { fur: PURPLE_D, muzzle: [245, 242, 255, 255], dark: [245, 242, 255, 255] } : {});
	drawText(img, 'LOGIN', 400, 170, 17, ink);
	drawText(img, 'FENNEC', 400, 170 + 17 * 9, 17, ink);
	const pw = textWidth('PRO', 10);
	const px = 400 + textWidth('FENNEC', 17) + 50;
	const py = 345;
	fillPolygon(img, [[px - 14, py], [px + pw + 14, py], [px + pw + 14, py + 90], [px - 14, py + 90]], badge);
	drawText(img, 'PRO', px, py + 10, 10, badgeText);
	fs.writeFileSync(path.join(out, file), encodePng(img));
	console.log('OK ' + file);
}

wordmark('loginfennec-wordmark-dark.png', INK, PURPLE, WHITE, true);
wordmark('loginfennec-wordmark-light.png', [255, 255, 255, 255], [255, 255, 255, 255], [91, 75, 214, 255], true);

/* 5) Bannières wp.org à l'effigie du fennec */
function banner(W, H, file) {
	const img = makeImage(W, H);
	gradientDiag(img, G1, G2);
	fillCircle(img, W * 0.93, H * 0.1, H * 0.5, [255, 255, 255, 16]);
	drawFennec(img, W * 0.85, H * 0.5, H * 0.76);
	const s = Math.round(H / 33); // ≈15 sur 500, ≈8 sur 250
	drawText(img, 'LOGINFENNEC', W * 0.055, H * 0.28, s, WHITE);
	const sub = 'LOGIN & SECURITY';
	const ss = Math.max(2, Math.round(H / 50));
	drawText(img, sub, W * 0.055, H * 0.28 + s * 10, ss, [255, 255, 255, 220]);
	const pw = textWidth('PRO', ss + 2);
	const px = W * 0.055 + textWidth(sub, ss) + 24;
	const py = H * 0.28 + s * 10 - 4;
	fillPolygon(img, [[px - 10, py], [px + pw + 10, py], [px + pw + 10, py + (ss + 2) * 9], [px - 10, py + (ss + 2) * 9]], [255, 255, 255, 235]);
	drawText(img, 'PRO', px, py + ss + 2, ss, PURPLE_D);
	fs.writeFileSync(path.join(out, file), encodePng(img));
	console.log('OK ' + file);
}

banner(1544, 500, 'banner-1544x500.png');
banner(772, 250, 'banner-772x250.png');

/* 6) Icônes wp.org (fennec remplace l'infini) */
{
	const img = makeImage(256, 256);
	gradientDiag(img, G1, G2);
	drawFennec(img, 128, 138, 200);
	fs.writeFileSync(path.join(out, 'icon-256x256.png'), encodePng(img));
	const small = makeImage(128, 128);
	gradientDiag(small, G1, G2);
	drawFennec(small, 64, 69, 100);
	fs.writeFileSync(path.join(out, 'icon-128x128.png'), encodePng(small));
	console.log('OK icônes wp.org 256/128');
}

/* 7) SVG vectoriels */
const fennecSvgInner = `
  <polygon points="16,2 47,11 30,46" fill="#f9f0d8"/>
  <polygon points="84,2 53,11 70,46" fill="#f9f0d8"/>
  <polygon points="16,2 47,11 41,27" fill="#2b241a"/>
  <polygon points="84,2 53,11 59,27" fill="#2b241a"/>
  <polygon points="27,16 42,20 32,38" fill="#e2a884"/>
  <polygon points="73,16 58,20 68,38" fill="#e2a884"/>
  <polygon points="30,36 70,36 85,55 70,76 50,90 30,76 15,55" fill="#f9f0d8"/>
  <polygon points="15,55 30,68 28,80 18,70" fill="#ffffff"/>
  <polygon points="85,55 70,68 72,80 82,70" fill="#ffffff"/>
  <polygon points="50,58 65,72 50,90 35,72" fill="#ffffff"/>
  <circle cx="36.5" cy="53.5" r="5" fill="#2b241a"/>
  <circle cx="63.5" cy="53.5" r="5" fill="#2b241a"/>
  <circle cx="34.9" cy="51.9" r="1.5" fill="#ffffff"/>
  <circle cx="61.9" cy="51.9" r="1.5" fill="#ffffff"/>
  <polygon points="50,71 55.5,74.5 50,81 44.5,74.5" fill="#2b241a"/>`;

fs.writeFileSync(path.join(out, 'loginfennec-icon.svg'), `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="512" height="512">
  <defs><linearGradient id="lg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#5b4bd6"/><stop offset="1" stop-color="#b16cea"/></linearGradient></defs>
  <rect width="100" height="100" rx="22" fill="url(#lg)"/>${fennecSvgInner}
</svg>
`);

fs.writeFileSync(path.join(out, 'loginfennec-shield.svg'), `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="512" height="512">
  <defs><linearGradient id="lg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#5b4bd6"/><stop offset="1" stop-color="#b16cea"/></linearGradient></defs>
  <polygon points="50,3 89,15 89,48 50,97 11,48 11,15" fill="url(#lg)"/>
  <g transform="translate(50,46) scale(0.62) translate(-50,-55)">${fennecSvgInner}
  </g>
</svg>
`);

fs.writeFileSync(path.join(out, 'loginfennec-mono-black.svg'), `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="512" height="512">
  <g fill="#1e1b32" fill-rule="evenodd">
    <polygon points="16,2 47,11 30,46"/>
    <polygon points="84,2 53,11 70,46"/>
    <polygon points="30,36 70,36 85,55 70,76 50,90 30,76 15,55"/>
  </g>
  <circle cx="36.5" cy="53.5" r="5" fill="#ffffff"/>
  <circle cx="63.5" cy="53.5" r="5" fill="#ffffff"/>
  <polygon points="50,71 55.5,74.5 50,81 44.5,74.5" fill="#ffffff"/>
</svg>
`);

console.log('Pack professionnel généré dans brand/pro/');
