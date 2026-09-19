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
	[ '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', path.join( root, 'tools', 'build-zip.ps1' ), '-Source', stage, '-Dest', dest ],
	{ stdio: 'inherit' }
);

const size = fs.statSync( dest ).size;
console.log( `BUILD-OK loginfennec-${ version }.zip (${ Math.round( size / 1024 ) } Ko)` );
