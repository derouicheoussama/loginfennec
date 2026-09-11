// Génère les assets du dépôt WordPress.org (bannières + icônes PNG)
// sans dépendance externe : encodeur PNG minimal + dessin logiciel.
// Usage : node tools/generate-assets.mjs
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
	ihdr[8] = 8; // profondeur
	ihdr[9] = 6; // RGBA
	const raw = Buffer.alloc((img.w * 4 + 1) * img.h);
	for (let y = 0; y < img.h; y++) {
		raw[y * (img.w * 4 + 1)] = 0; // filtre « none »
		img.data.copy(raw, y * (img.w * 4 + 1) + 1, y * img.w * 4, (y + 1) * img.w * 4);
	}
	return Buffer.concat([
		sig,
		chunk('IHDR', ihdr),
		chunk('IDAT', zlib.deflateSync(raw, { level: 9 })),
		chunk('IEND', Buffer.alloc(0)),
	]);
}

/* ---------- Primitives de dessin ---------- */

function makeImage(w, h) {
	return { w, h, data: Buffer.alloc(w * h * 4, 255) };
}

function setPx(img, x, y, [r, g, b, a]) {
	x = Math.round(x); y = Math.round(y);
	if (x < 0 || y < 0 || x >= img.w || y >= img.h) return;
	const i = (y * img.w + x) * 4;
	const sa = a / 255;
	const da = img.data[i + 3] / 255;
	const oa = sa + da * (1 - sa);
	img.data[i] = Math.round((r * sa + img.data[i] * da * (1 - sa)) / oa);
	img.data[i + 1] = Math.round((g * sa + img.data[i + 1] * da * (1 - sa)) / oa);
	img.data[i + 2] = Math.round((b * sa + img.data[i + 2] * da * (1 - sa)) / oa);
	img.data[i + 3] = Math.round(oa * 255);
}

function fillRect(img, x, y, w, h, color) {
	for (let yy = y; yy < y + h; yy++) for (let xx = x; xx < x + w; xx++) setPx(img, xx, yy, color);
}

function fillCircle(img, cx, cy, rad, color) {
	for (let yy = Math.floor(cy - rad); yy <= cy + rad; yy++) {
		for (let xx = Math.floor(cx - rad); xx <= cx + rad; xx++) {
			const dx = xx - cx, dy = yy - cy;
			if (dx * dx + dy * dy <= rad * rad) setPx(img, xx, yy, color);
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
	C: ['.XXX.', 'X...X', 'X....', 'X....', 'X....', 'X...X', '.XXX.'],
	E: ['XXXXX', 'X....', 'X....', 'XXXX.', 'X....', 'X....', 'XXXXX'],
	F: ['XXXXX', 'X....', 'X....', 'XXXX.', 'X....', 'X....', 'X....'],
	G: ['.XXX.', 'X...X', 'X....', 'X..XX', 'X...X', 'X...X', '.XXXX'],
	I: ['XXXXX', '..X..', '..X..', '..X..', '..X..', '..X..', 'XXXXX'],
	L: ['X....', 'X....', 'X....', 'X....', 'X....', 'X....', 'XXXXX'],
	M: ['X...X', 'XX.XX', 'X.X.X', 'X.X.X', 'X...X', 'X...X', 'X...X'],
	N: ['X...X', 'XX..X', 'X.X.X', 'X..XX', 'X...X', 'X...X', 'X...X'],
	O: ['.XXX.', 'X...X', 'X...X', 'X...X', 'X...X', 'X...X', '.XXX.'],
	R: ['XXXX.', 'X...X', 'X...X', 'XXXX.', 'X.X..', 'X..X.', 'X...X'],
	S: ['.XXXX', 'X....', 'X....', '.XXX.', '....X', '....X', 'XXXX.'],
	T: ['XXXXX', '..X..', '..X..', '..X..', '..X..', '..X..', '..X..'],
	U: ['X...X', 'X...X', 'X...X', 'X...X', 'X...X', 'X...X', '.XXX.'],
	Y: ['X...X', 'X...X', '.X.X.', '..X..', '..X..', '..X..', '..X..'],
	Z: ['XXXXX', '....X', '...X.', '..X..', '.X...', 'X....', 'XXXXX'],
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
				if (glyph[ry][rx] === 'X') fillRect(img, cursor + rx * scale, y + ry * scale, scale, scale, color);
			}
		}
		cursor += 6 * scale;
	}
}

/* ---------- Symbole infini (lemniscate) ---------- */

function drawInfinity(img, cx, cy, halfWidth, thickness, color) {
	const steps = 700;
	for (let i = 0; i <= steps; i++) {
		const t = (i / steps) * Math.PI * 2;
		const d = 1 + Math.sin(t) ** 2;
		const x = cx + (halfWidth * Math.cos(t)) / d;
		const y = cy + ((halfWidth * Math.sin(t) * Math.cos(t)) / d) * 2;
		fillCircle(img, x, y, thickness, color);
	}
}

/* ---------- Compositions ---------- */

const PURPLE = [91, 75, 214, 255];
const VIOLET = [177, 108, 234, 255];
const WHITE = [255, 255, 255, 255];

function decor(img) {
	fillCircle(img, img.w * 0.92, img.h * 0.1, img.h * 0.5, [255, 255, 255, 14]);
	fillCircle(img, img.w * 0.06, img.h * 0.94, img.h * 0.35, [255, 255, 255, 12]);
}

function banner(w, h, file) {
	const img = makeImage(w, h);
	gradientDiag(img, PURPLE, VIOLET);
	decor(img);
	drawInfinity(img, w * 0.79, h * 0.5, w * 0.14, h * 0.052, [255, 255, 255, 46]);
	const s = Math.round(h / 38.5); // ≈13 sur 500, ≈6 sur 250
	drawText(img, 'INFINITY', w * 0.062, h * 0.25, s, WHITE);
	drawText(img, 'CUSTOMIZER', w * 0.062, h * 0.25 + s * 9, s, WHITE);
	const sub = 'LOGIN CUSTOMIZER & SECURITY';
	const ss = Math.max(2, Math.round(h / 83)); // ≈6 sur 500, ≈3 sur 250
	drawText(img, sub, w * 0.062, h * 0.25 + s * 20, ss, [255, 255, 255, 215]);
	fs.writeFileSync(file, encodePng(img));
	console.log('OK', file, `${w}x${h}`);
}

function icon(size, file) {
	const img = makeImage(size, size);
	gradientDiag(img, PURPLE, VIOLET);
	drawInfinity(img, size / 2, size / 2, size * 0.3, size * 0.078, WHITE);
	fs.writeFileSync(file, encodePng(img));
	console.log('OK', file, `${size}x${size}`);
}

const out = path.join(process.cwd(), 'wporg-assets');
fs.mkdirSync(out, { recursive: true });
banner(1544, 500, path.join(out, 'banner-1544x500.png'));
banner(772, 250, path.join(out, 'banner-772x250.png'));
icon(256, path.join(out, 'icon-256x256.png'));
icon(128, path.join(out, 'icon-128x128.png'));
console.log('Assets WordPress.org générés dans wporg-assets/');
