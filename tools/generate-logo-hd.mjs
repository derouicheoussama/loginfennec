// Logo LoginFennec haute définition — fennec détaillé multi-variants.
// Usage : node tools/generate-logo-hd.mjs
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
			for (let x = Math.max(0, Math.round(xs[k])); x <= Math.min(img.w - 1, Math.round(xs[k + 1])); x++) {
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

/* ---------- Le fennec détaillé (repère 0-100) ---------- */

const CREAM = [249, 240, 216, 255];
const CREAM_D = [240, 224, 186, 255];
const EAR_IN = [226, 168, 132, 255];
const EAR_TIP = [43, 36, 26, 255];
const DARK = [43, 36, 26, 255];
const WHITE = [255, 255, 255, 255];
const NOSE_SH = [60, 50, 35, 128];

function fennecDetailed() {
	return {
		// Oreilles externes
		earL:      [[14, 0], [48, 10], [30, 47]],
		earR:      [[86, 0], [52, 10], [70, 47]],
		// Pointes noires (comme le vrai fennec)
		tipL:      [[14, 0], [47, 11], [40, 29]],
		tipR:      [[86, 0], [53, 11], [60, 29]],
		// Intérieurs d'oreilles
		earInL:    [[26, 15], [43, 19], [32, 38]],
		earInR:    [[74, 15], [57, 19], [68, 38]],
		// Tête principale
		head:      [[29, 35], [71, 35], [86, 54], [71, 76], [50, 91], [29, 76], [14, 54]],
		// Joues blanches (poils)
		cheekL:    [[14, 54], [30, 67], [28, 81], [17, 69]],
		cheekR:    [[86, 54], [70, 67], [72, 81], [83, 69]],
		// Museau
		muzzle:    [[50, 57], [66, 72], [50, 91], [34, 72]],
		// Nez + ombre
		nose:      [[50, 71], [56, 74.5], [50, 81], [44, 74.5]],
		noseShade: [[50, 79], [53, 76], [50, 81], [47, 76]],
		// Marques faciales (larmes)
		tearL:     [[37, 58], [39, 64], [36, 64], [34, 58]],
		tearR:     [[63, 58], [66, 64], [64, 64], [61, 58]],
		// Front marque
		browMark:  [[44, 38], [56, 38], [53, 43], [47, 43]],
	};
}

function scaleShapes(shapes, cx, cy, size) {
	const out = {};
	for (const [name, pts] of Object.entries(shapes)) {
		out[name] = pts.map(([x, y]) => [cx + ((x - 50) * size) / 100, cy + ((y - 55) * size) / 100]);
	}
	return out;
}

function drawFennecHD(img, cx, cy, size) {
	const s = scaleShapes(fennecDetailed(), cx, cy, size);

	// Oreilles
	fillPolygon(img, s.earL, CREAM);
	fillPolygon(img, s.earR, CREAM);
	fillPolygon(img, s.tipL, EAR_TIP);
	fillPolygon(img, s.tipR, EAR_TIP);
	fillPolygon(img, s.earInL, EAR_IN);
	fillPolygon(img, s.earInR, EAR_IN);

	// Tête
	fillPolygon(img, s.head, CREAM);
	// Zones d'ombre subtiles sur le crâne
	fillPolygon(img, [[29, 35], [50, 35], [50, 45], [29, 45]], CREAM_D);
	fillPolygon(img, [[50, 35], [71, 35], [71, 45], [50, 45]], CREAM_D);

	// Joues
	fillPolygon(img, s.cheekL, WHITE);
	fillPolygon(img, s.cheekR, WHITE);

	// Museau
	fillPolygon(img, s.muzzle, WHITE);

	// Marques faciales
	fillPolygon(img, s.browMark, WHITE);

	// Yeux
	const eyeR = (size * 5.2) / 100;
	const eyeOff = (size * 13.5) / 100;
	const eyeY = cy - (size * 1) / 100;
	fillCircle(img, cx - eyeOff, eyeY, eyeR, DARK);
	fillCircle(img, cx + eyeOff, eyeY, eyeR, DARK);
	// Reflets
	const glint = eyeR * 0.32;
	fillCircle(img, cx - eyeOff + eyeR * 0.25, eyeY - eyeR * 0.3, glint, WHITE);
	fillCircle(img, cx + eyeOff + eyeR * 0.25, eyeY - eyeR * 0.3, glint, WHITE);
	// Larmes foncées sous les yeux
	fillPolygon(img, [
		[cx - eyeOff - eyeR * 0.3, eyeY + eyeR * 0.6],
		[cx - eyeOff + eyeR * 0.3, eyeY + eyeR * 0.6],
		[cx - eyeOff, eyeY + eyeR * 1.6],
	], [43, 36, 26, 80]);
	fillPolygon(img, [
		[cx + eyeOff - eyeR * 0.3, eyeY + eyeR * 0.6],
		[cx + eyeOff + eyeR * 0.3, eyeY + eyeR * 0.6],
		[cx + eyeOff, eyeY + eyeR * 1.6],
	], [43, 36, 26, 80]);

	// Nez + ombre
	fillPolygon(img, s.nose, DARK);
	fillPolygon(img, s.noseShade, NOSE_SH);
}

/* ---------- Police 5x7 pour les textes ---------- */

const FONT = {
	L: ['X....', 'X....', 'X....', 'X....', 'X....', 'X....', 'XXXXX'],
	O: ['.XXX.', 'X...X', 'X...X', 'X...X', 'X...X', 'X...X', '.XXX.'],
	G: ['.XXX.', 'X...X', 'X....', 'X..XX', 'X...X', 'X...X', '.XXXX'],
	I: ['XXXXX', '..X..', '..X..', '..X..', '..X..', '..X..', 'XXXXX'],
	N: ['X...X', 'XX..X', 'X.X.X', 'X..XX', 'X...X', 'X...X', 'X...X'],
	F: ['XXXXX', 'X....', 'X....', 'XXXX.', 'X....', 'X....', 'X....'],
	E: ['XXXXX', 'X....', 'X....', 'XXXX.', 'X....', 'X....', 'XXXXX'],
	C: ['.XXX.', 'X...X', 'X....', 'X....', 'X....', 'X...X', '.XXX.'],
	P: ['XXXX.', 'X...X', 'X...X', 'XXXX.', 'X....', 'X....', 'X....'],
	R: ['XXXX.', 'X...X', 'X...X', 'XXXX.', 'X.X..', 'X..X.', 'X...X'],
	S: ['.XXXX', 'X....', 'X....', '.XXX.', '....X', '....X', 'XXXX.'],
	T: ['XXXXX', '..X..', '..X..', '..X..', '..X..', '..X..', '..X..'],
	U: ['X...X', 'X...X', 'X...X', 'X...X', 'X...X', 'X...X', '.XXX.'],
	Y: ['X...X', 'X...X', '.X.X.', '..X..', '..X..', '..X..', '..X..'],
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

/* ---------- Palettes ---------- */

const G1 = [91, 75, 214, 255];
const G2 = [177, 108, 234, 255];
const SAND1 = [230, 200, 150, 255];
const SAND2 = [180, 140, 90, 255];

const out = path.join(process.cwd(), 'brand', 'hd');
fs.mkdirSync(out, { recursive: true });

/* 1) Icône ultra HD 2048 : dégradé + fennec détaillé */
{
	const img = makeImage(2048, 2048);
	gradientDiag(img, G1, G2);
	fillCircle(img, 1840, 240, 480, [255, 255, 255, 14]);
	fillCircle(img, 240, 1880, 360, [255, 255, 255, 12]);
	drawFennecHD(img, 1024, 1104, 1600);
	fs.writeFileSync(path.join(out, 'loginfennec-icon-hd-2048.png'), encodePng(img));
	console.log('OK loginfennec-icon-hd-2048.png');
}

/* 2) Icône HD 1024 */
{
	const img = makeImage(1024, 1024);
	gradientDiag(img, G1, G2);
	fillCircle(img, 920, 120, 240, [255, 255, 255, 14]);
	fillCircle(img, 120, 940, 180, [255, 255, 255, 12]);
	drawFennecHD(img, 512, 552, 800);
	fs.writeFileSync(path.join(out, 'loginfennec-icon-hd-1024.png'), encodePng(img));
	console.log('OK loginfennec-icon-hd-1024.png');
}

/* 3) Fennec transparent 2048 (fonds clairs) */
{
	const img = makeImage(2048, 2048, true);
	drawFennecHD(img, 1024, 1064, 1760);
	fs.writeFileSync(path.join(out, 'loginfennec-fennec-transparent-2048.png'), encodePng(img));
	console.log('OK loginfennec-fennec-transparent-2048.png');
}

/* 4) Fond sable désert (ambiance algérienne) 2048 */
{
	const img = makeImage(2048, 2048);
	gradientDiag(img, SAND1, SAND2);
	// Dunes subtiles
	fillCircle(img, 400, 2100, 1200, [220, 190, 140, 80]);
	fillCircle(img, 1700, 1900, 900, [255, 230, 180, 60]);
	// Soleil
	fillCircle(img, 1024, 380, 200, [255, 245, 200, 100]);
	drawFennecHD(img, 1024, 1104, 1600);
	fs.writeFileSync(path.join(out, 'loginfennec-desert-2048.png'), encodePng(img));
	console.log('OK loginfennec-desert-2048.png');
}

/* 5) Cover réseaux sociaux 1200×630 (Open Graph) */
{
	const img = makeImage(1200, 630);
	gradientDiag(img, G1, G2);
	fillCircle(img, 1100, 80, 300, [255, 255, 255, 14]);
	fillCircle(img, 100, 580, 250, [255, 255, 255, 12]);
	drawFennecHD(img, 980, 315, 560);
	// Marque
	const s = 12;
	drawText(img, 'LOGINFENNEC', 80, 200, s, WHITE);
	drawText(img, 'PRO', 80, 200 + s * 9, s * 0.7, [255, 255, 255, 200]);
	const sub = 'PERSONNALISATION PAGE LOGIN & SECURITY';
	drawText(img, sub, 80, 200 + s * 17, Math.max(2, Math.round(s / 3)), [255, 255, 255, 170]);
	fs.writeFileSync(path.join(out, 'loginfennec-og-1200x630.png'), encodePng(img));
	console.log('OK loginfennec-og-1200x630.png');
}

/* ---------- Police pour le cover OG ---------- */

