/**
 * ∞ INFINITY CODER — création originale de Derouiche Oussama
 *
 * Logo LoginFennec v2 — « serrure + fennec » :
 * cadran de serrure bleu (accès/connexion) renfermant un fennec orange
 * (trait), décliné en icône, logo complet (wordmark + badge PRO),
 * icône de menu admin, bannières et icônes WordPress.org.
 *
 * Usage : node tools/generate-logo-v2.mjs
 */
import sharp from 'sharp';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..' );

const BLUE = '#1E6BF1';
const ORANGE = '#F07C1B';
const NAVY = '#1A3B8F';
const GRAY = '#a7aaad';

/**
 * Dessin partagé « serrure + fennec » — géométrie unique, viewBox 200×214.
 */
function art( { keyholeStroke = BLUE, fennecStroke = ORANGE, fill = '#ffffff' } = {} ) {
	return `
	<path d="M 50 122 A 64 64 0 1 1 150 122 L 162 192 Q 164 200 156 200 L 44 200 Q 36 200 38 192 Z"
		fill="${ fill }" stroke="${ keyholeStroke }" stroke-width="9"/>
	<g fill="none" stroke="${ fennecStroke }" stroke-width="7" stroke-linecap="round" stroke-linejoin="round">
		<path d="M 50 100 C 52 70 58 42 66 27 C 68 23 72 24 74 28 L 91 60"/>
		<path d="M 150 100 C 148 70 142 42 134 27 C 132 23 128 24 126 28 L 109 60"/>
		<path d="M 62 82 C 64 62 68 48 72 40"/>
		<path d="M 138 82 C 136 62 132 48 128 40"/>
		<path d="M 50 100 C 47 122 58 137 78 144 C 92 148 108 148 122 144 C 142 137 153 122 150 100"/>
		<path d="M 66 106 Q 75 99 84 106"/>
		<path d="M 116 106 Q 125 99 134 106"/>
		<path d="M 94 128 L 100 135 L 106 128"/>
	</g>`;
}

/**
 * Icône « serrure + fennec » — viewBox 200×214.
 * bg : 'transparent' | 'white' | 'navy'.
 */
function iconSvg( opts = {} ) {
	const { bg = 'transparent', keyhole = BLUE, fennec = ORANGE } = opts;
	const bgRect = bg === 'white'
		? '<rect width="200" height="214" fill="#ffffff"/>'
		: bg === 'navy'
			? '<rect width="200" height="214" rx="34" fill="#12275C"/>'
			: '';
	return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 214">
${ bgRect }${ art( { keyholeStroke: keyhole, fennecStroke: fennec, fill: bg === 'navy' ? '#12275C' : '#ffffff' } ) }
</svg>`;
}

/** Mesure la largeur réelle d'un texte rendu (deux passes : rendu + trim). */
async function measureText( text, { size, weight = 800 } ) {
	const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="4000" height="${ Math.round( size * 1.8 ) }"><text x="10" y="${ Math.round( size * 1.3 ) }" font-family="Segoe UI" font-weight="${ weight }" font-size="${ size }" fill="#000">${ text }</text></svg>`;
	const info = await sharp( Buffer.from( svg ) ).trim().toBuffer( { resolveWithObject: true } );
	return Math.max( 40, Math.round( info.info.width - 10 ) );
}

/** Logo complet : icône + « LoginFennec » + badge PRO. */
async function fullLogoSvg( { width = 1600, dark = false, chipTexts = [], tagline = '', bg = '#ffffff' } = {} ) {
	const nameSize = 190;
	const textW = await measureText( 'LoginFennec', { size: nameSize } );
	const badgeW = 240;
	const iconW = 430;
	const iconX = 70;
	const iconY = ( 520 - iconW * 214 / 200 ) / 2;
	const textX = iconX + iconW + 60;
	const nameY = 320;
	const totalW = textX + textW + 36 + badgeW + 60;
	const nameFill = dark ? '#ffffff' : NAVY;
	const chips = chipTexts.length
		? chipTexts.map( ( t, i ) => {
			const cw = 44 + t.length * 24;
			const cx = textX + chipTexts.slice( 0, i ).reduce( ( acc, p ) => acc + 44 + p.length * 24 + 22, 0 );
			return `<rect x="${ cx }" y="380" width="${ cw }" height="62" rx="31" fill="none" stroke="${ dark ? 'rgba(255,255,255,.45)' : '#C9D4EC' }" stroke-width="3"/>`
				+ `<text x="${ cx + cw / 2 }" y="422" text-anchor="middle" font-family="Segoe UI" font-size="30" font-weight="600" fill="${ dark ? '#E4ECFB' : '#40518F' }">${ t }</text>`;
		} ).join( '' )
		: '';
	return `<svg xmlns="http://www.w3.org/2000/svg" width="${ totalW }" height="${ Math.round( 520 * ( width / 1600 ) ) * 0 + 520 }" viewBox="0 0 ${ totalW } 520">
<rect width="${ totalW }" height="520" fill="${ bg }"/>
<g transform="translate(${ iconX },${ iconY }) scale(${ iconW / 200 })">${ art() }</g>
<text x="${ textX }" y="${ nameY }" font-family="Segoe UI" font-weight="800" font-size="${ nameSize }" fill="${ nameFill }">LoginFennec</text>
<rect x="${ textX + textW + 36 }" y="${ nameY - 138 }" width="${ badgeW }" height="140" rx="34" fill="${ ORANGE }"/>
<text x="${ textX + textW + 36 + badgeW / 2 }" y="${ nameY - 34 }" text-anchor="middle" font-family="Segoe UI" font-weight="800" font-size="86" fill="#ffffff">PRO</text>
${ tagline ? `<text x="${ textX }" y="400" font-family="Segoe UI" font-size="40" font-weight="600" fill="${ dark ? '#C7D6F5' : '#5A6CA8' }">${ tagline }</text>` : '' }
${ chips }
</svg>`;
}

/** Bannière WordPress.org (viewBox 1544×500, rendu en 1544 et 772). */
async function bannerSvg() {
	const size = 118;
	const textW = await measureText( 'LoginFennec', { size } );
	const badgeX = 420 + textW + 30;
	return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1544 500">
<defs>
	<linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
		<stop offset="0" stop-color="#0C2456"/>
		<stop offset="1" stop-color="#1B3E8F"/>
	</linearGradient>
</defs>
<rect width="1544" height="500" fill="url(#bg)"/>
<g opacity="0.10" stroke="#ffffff" fill="none" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" transform="translate(1200,-40) scale(2.1)">
	<path d="M 50 122 A 64 64 0 1 1 150 122 L 162 192 Q 164 200 156 200 L 44 200 Q 36 200 38 192 Z"/>
	<path d="M 50 100 C 52 70 58 42 66 27 C 68 23 72 24 74 28 L 91 60"/>
	<path d="M 150 100 C 148 70 142 42 134 27 C 132 23 128 24 126 28 L 109 60"/>
</g>
<g transform="translate(84,84) scale(1.56)">${ art( { keyholeStroke: '#5B8DF5', fill: '#0F2A63' } ) }</g>
<text x="420" y="205" font-family="Segoe UI" font-weight="800" font-size="${ size }" fill="#ffffff">LoginFennec</text>
<rect x="${ badgeX }" y="102" width="164" height="98" rx="24" fill="${ ORANGE }"/>
<text x="${ badgeX + 82 }" y="169" text-anchor="middle" font-family="Segoe UI" font-weight="800" font-size="60" fill="#ffffff">PRO</text>
<text x="424" y="278" font-family="Segoe UI" font-size="38" font-weight="600" fill="#C7D6F5">Personnalisez et sécurisez votre page de connexion</text>
<g font-family="Segoe UI" font-size="27" font-weight="600">
	<rect x="424" y="330" width="212" height="58" rx="29" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="3"/>
	<text x="530" y="368" text-anchor="middle" fill="#E4ECFB">Anti force brute</text>
	<rect x="656" y="330" width="226" height="58" rx="29" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="3"/>
	<text x="769" y="368" text-anchor="middle" fill="#E4ECFB">Connexion SMS</text>
	<rect x="902" y="330" width="150" height="58" rx="29" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="3"/>
	<text x="977" y="368" text-anchor="middle" fill="#E4ECFB">GEO</text>
	<rect x="1072" y="330" width="252" height="58" rx="29" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="3"/>
	<text x="1198" y="368" text-anchor="middle" fill="#E4ECFB">Aperçu en direct</text>
</g>
<text x="424" y="446" font-family="Segoe UI" font-size="26" fill="#8FA5D8">∞ Infinity Coder — Derouiche Oussama</text>
</svg>`;
}

const renderPng = async ( svg, file, width ) => {
	const target = path.join( root, file );
	fs.mkdirSync( path.dirname( target ), { recursive: true } );
	await sharp( Buffer.from( svg ), { density: 300 } ).resize( { width } ).png().toFile( target );
	console.log( 'OK', file, width + 'px' );
};

const writeText = ( file, content ) => {
	const target = path.join( root, file );
	fs.mkdirSync( path.dirname( target ), { recursive: true } );
	fs.writeFileSync( target, content );
	console.log( 'OK', file );
};

/* ---------- Génération ---------- */

// Icônes du plugin.
await renderPng( iconSvg( { bg: 'white' } ), 'assets/img/logo-fennec.png', 512 );
await renderPng( iconSvg( { bg: 'transparent' } ), 'assets/img/logo-icon-512.png', 512 );
await renderPng( await fullLogoSvg( {} ), 'assets/img/logo-full.png', 1600 );

// Menu admin (monochrome, ton dashicons).
writeText( 'assets/img/menu-fennec.svg', iconSvg( { keyhole: GRAY, fennec: GRAY } ) );

// Marque HD (hors zip).
await renderPng( iconSvg( { bg: 'white' } ), 'brand/hd/loginfennec-v2-icon-2048.png', 2048 );
await renderPng( iconSvg( { bg: 'transparent' } ), 'brand/hd/loginfennec-v2-icon-transparent-2048.png', 2048 );
await renderPng( await fullLogoSvg( { tagline: 'Personnalisez et sécurisez votre page de connexion' } ), 'brand/hd/loginfennec-v2-full-2048.png', 3200 );

// WordPress.org : bannières + icônes.
const banner = await bannerSvg();
await renderPng( banner, 'wporg-assets/banner-1544x500.png', 1544 );
await renderPng( banner, 'wporg-assets/banner-772x250.png', 772 );
await renderPng( iconSvg( { bg: 'navy' } ), 'wporg-assets/icon-256x256.png', 256 );
await renderPng( iconSvg( { bg: 'navy' } ), 'wporg-assets/icon-128x128.png', 128 );
writeText( 'wporg-assets/icon.svg', iconSvg( { bg: 'navy' } ) );

console.log( 'TERMINÉ' );
