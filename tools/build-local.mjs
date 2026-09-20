/**
 * ∞ INFINITY CODER — création originale de Derouiche Oussama
 *
 * Build local du zip WordPress : staging (mêmes exclusions que la CI)
 * puis compression via tools/build-zip.ps1 (entrées en slash).
 *
 * Usage : node tools/build-local.mjs
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';

const root = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..' );
const build = path.join( root, 'build' );
const stage = path.join( build, 'loginfennec' );

const main = fs.readFileSync( path.join( root, 'loginfennec.php' ), 'utf8' );
const version = ( main.match( /define\(\s*'LOGINFENNEC_VERSION',\s*'([^']+)'/ ) || [] )[ 1 ] || 'dev';
const dest = path.join( build, `loginfennec-${ version }.zip` );

fs.rmSync( stage, { recursive: true, force: true } );
for ( const old of fs.readdirSync( build ).filter( ( f ) => /^loginfennec-.*\.zip$/.test( f ) ) ) {
	fs.rmSync( path.join( build, old ), { force: true } );
}
fs.mkdirSync( stage, { recursive: true } );
for ( const entry of [ 'assets', 'includes', 'languages', 'loginfennec.php', 'readme.txt', 'uninstall.php' ] ) {
	fs.cpSync( path.join( root, entry ), path.join( stage, entry ), { recursive: true } );
}

execFileSync(
	'powershell.exe',
	[ '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', path.join( root, 'tools', 'build-zip.ps1' ), '-Source', stage, '-Dest', dest, '-Prefix', 'loginfennec' ],
	{ stdio: 'inherit' }
);

// Paquet wp.org : sans le canal auto-hébergé (mises à jour natives wp.org).
const wporgStage = path.join( build, '_wporg' );
fs.rmSync( wporgStage, { recursive: true, force: true } );
fs.cpSync( stage, wporgStage, { recursive: true } );
fs.rmSync( path.join( wporgStage, 'includes', 'class-updater.php' ), { force: true } );
const wporgDest = path.join( build, `loginfennec-${ version }-wporg.zip` );
execFileSync(
	'powershell.exe',
	[ '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', path.join( root, 'tools', 'build-zip.ps1' ), '-Source', wporgStage, '-Dest', wporgDest, '-Prefix', 'loginfennec' ],
	{ stdio: 'inherit' }
);
fs.rmSync( wporgStage, { recursive: true, force: true } );

// Contrôle final : structure WordPress obligatoire (sinon « Le fichier de
// l'extension n'existe pas » chez l'utilisateur). Lecture du central
// directory (fiable, insensible aux data descriptors).
import zlib from 'node:zlib';
const zipBuf = fs.readFileSync( dest );
const eocd = zipBuf.lastIndexOf( Buffer.from( [ 0x50, 0x4b, 0x05, 0x06 ] ) );
if ( eocd < 0 ) { console.error( 'ÉCHEC : zip invalide (EOCD introuvable)' ); process.exit( 1 ); }
const entryCount = zipBuf.readUInt16LE( eocd + 10 );
let ptr = zipBuf.readUInt32LE( eocd + 16 );
const entries = [];
for ( let i = 0; i < entryCount; i++ ) {
	if ( zipBuf.readUInt32LE( ptr ) !== 0x02014b50 ) { break; }
	const nameLen = zipBuf.readUInt16LE( ptr + 28 );
	const extraLen = zipBuf.readUInt16LE( ptr + 30 );
	const commentLen = zipBuf.readUInt16LE( ptr + 32 );
	entries.push( zipBuf.toString( 'utf8', ptr + 46, ptr + 46 + nameLen ) );
	ptr += 46 + nameLen + extraLen + commentLen;
}
let fail = 0;
for ( const name of entries ) {
	if ( name.includes( '\\' ) ) { console.error( 'ÉCHEC : antislash dans ' + name ); fail++; }
	if ( ! name.startsWith( 'loginfennec/' ) ) { console.error( 'ÉCHEC : hors racine — ' + name ); fail++; }
}
if ( ! entries.includes( 'loginfennec/loginfennec.php' ) ) { console.error( 'ÉCHEC : fichier principal absent' ); fail++; }
if ( fail ) { process.exit( 1 ); }

const size = fs.statSync( dest ).size;
console.log( `BUILD-OK loginfennec-${ version }.zip (${ Math.round( size / 1024 ) } Ko, ${ entries.length } entrées, structure vérifiée)` );
console.log( `BUILD-OK loginfennec-${ version }-wporg.zip (sans canal auto-hébergé)` );
