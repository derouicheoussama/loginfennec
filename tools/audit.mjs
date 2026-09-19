/**
 * ∞ INFINITY CODER — audit croisé LoginFennec Pro
 * Vérifie la cohérence : defaults ↔ spec de sanitization ↔ UI ↔ consommation ↔ désinstallation.
 */
import fs from 'node:fs';

const read = ( p ) => fs.readFileSync( p, 'utf8' );
const settings = read( 'includes/settings.php' );
const admin = read( 'includes/admin.php' );
const files = [ 'loginfennec.php', ...fs.readdirSync( 'includes' ).filter( ( f ) => f.endsWith( '.php' ) ).map( ( f ) => 'includes/' + f ) ];
const all = files.map( ( f ) => read( f ) ).join( '\n' );

function slice( src, startRe, endRe ) {
	const a = src.search( startRe );
	if ( a < 0 ) { return ''; }
	const b = src.slice( a ).search( endRe );
	return src.slice( a, b > 0 ? a + b + 40 : undefined );
}

// 1. Clés des defaults
const defaultsSlice = slice( settings, /function lnf_get_defaults/, /\t}\n/ );
const defaults = [ ...defaultsSlice.matchAll( /'([a-z_]+)'\s+=>/g ) ].map( ( m ) => m[ 1 ] );

// 2. Spec par type
const specSlice = slice( settings, /function lnf_field_spec/, /\n\}/ );
const spec = new Map();
for ( const m of specSlice.matchAll( /'(\w+)'\s*=>\s*array\(/g ) ) { /* types int */ }
const typeOf = ( key ) => {
	const zones = { key: /'key'\s*=>\s*array\(([^)]*)\)/, bool: /'bool'\s*=>\s*array\(([^)]*)\)/, url: /'url'\s*=>\s*array\(([^)]*)\)/, text: /'text'\s*=>\s*array\(([^)]*)\)/, color: /'color'\s*=>\s*array\(([^)]*)\)/ };
	for ( const [ t, re ] of Object.entries( zones ) ) {
		const mm = specSlice.match( re );
		if ( mm && mm[ 1 ].split( ',' ).map( ( s ) => s.trim().replace( /'/g, '' ) ).includes( key ) ) { return t; }
	}
	const intZone = specSlice.slice( specSlice.search( /'int'\s*=>\s*array\(/ ) );
	const intKeys = [ ...intZone.matchAll( /'([a-z_]+)'\s*=>\s*array\(/g ) ].map( ( m ) => m[ 1 ] );
	if ( intKeys.includes( key ) ) { return 'int'; }
	return null;
};

// 3. Clés rendues dans l'UI (multiline)
const uiKeys = new Set();
for ( const m of admin.matchAll( /self::field_\w+\(\s*\$s,\s*'([a-z_]+)'/g ) ) { uiKeys.add( m[ 1 ] ); }

// 4. Consommation hors settings/UI
const others = files.filter( ( f ) => f !== 'includes/settings.php' ).map( read ).join( '\n' ) + admin.split( 'self::panel_' ).slice( -1 )[ 0 ];
const consumed = ( key ) => new RegExp( `\\[\\s*'${ key }'\\s*\\]|lnf_get_option\\(\\s*'${ key }'\\s*\\)` ).test( all.replace( defaultsSlice, '' ).replace( specSlice, '' ) );

// Rapport 1 : couverture de sanitization
console.log( '== RÉGLAGES SANS SPEC DE SANITIZATION (réinitialisés/ignorés à la sauvegarde) ==' );
let issues = 0;
for ( const k of defaults ) {
	const t = typeOf( k );
	if ( ! t ) { console.log( `  ✘ ${ k } — absente de lnf_field_spec()` ); issues++; }
}
if ( ! issues ) { console.log( '  ✔ couverture complète' ); }

console.log( '== CLÉS DE SPEC ABSENTES DES DEFAULTS ==' );
const specAll = [];
for ( const k of defaults ) { /* noop */ }
const specKeyRe = /'([a-z_]+)'/g;
const specZoneKey = specSlice.match( /'key'\s*=>\s*array\(([^)]*)\)/ )[ 1 ];
const specZoneBool = specSlice.match( /'bool'\s*=>\s*array\(([^)]*)\)/ )[ 1 ];
const specZoneUrl = specSlice.match( /'url'\s*=>\s*array\(([^)]*)\)/ )[ 1 ];
const specZoneText = specSlice.match( /'text'\s*=>\s*array\(([^)]*)\)/ )[ 1 ];
const specZoneColor = specSlice.match( /'color'\s*=>\s*array\(([^)]*)\)/ )[ 1 ];
const collect = ( zone ) => zone.split( ',' ).map( ( s ) => s.trim().replace( /'/g, '' ) ).filter( ( s ) => /^[a-z_]+$/.test( s ) );
const intZone = specSlice.slice( specSlice.search( /'int'\s*=>\s*array\(/ ) );
const specIntKeys = [ ...intZone.matchAll( /'([a-z_]+)'\s*=>\s*array\(/g ) ].map( ( m ) => m[ 1 ] );
const allSpecKeys = [ ...collect( specZoneKey ), ...collect( specZoneBool ), ...collect( specZoneUrl ), ...collect( specZoneText ), ...collect( specZoneColor ), ...specIntKeys ];
const dupSpec = allSpecKeys.filter( ( k, i ) => allSpecKeys.indexOf( k ) !== i );
if ( dupSpec.length ) { console.log( '  ⚠ clé dans PLUSIEURS types de spec : ' + [ ...new Set( dupSpec ) ].join( ', ' ) ); }
for ( const k of [ ...new Set( allSpecKeys ) ] ) {
	if ( ! defaults.includes( k ) ) { console.log( `  ⚠ ${ k } — dans la spec mais pas dans les defaults` ); }
}

console.log( '== RENDU UI SANS DEFAULT (fatal/render vide possible) ==' );
for ( const k of uiKeys ) {
	if ( ! defaults.includes( k ) ) { console.log( `  ⚠ ${ k } — rendu mais absent des defaults` ); }
}

console.log( '== DEFAULTS JAMAIS RENDUS DANS L’UI (options mortes) ==' );
for ( const k of defaults ) {
	if ( ! uiKeys.has( k ) ) {
		const used = new RegExp( `\\[\\s*'${ k }'\\s*\\]` ).test( all.replace( defaultsSlice, '' ) ) || new RegExp( `'${ k }'` ).test( admin.split( '/* ----' )[ 0 ] );
		console.log( `  · ${ k } — pas de champ UI (lecture code : ${ used ? 'oui' : 'NON' })` );
	}
}

console.log( '== OPTIONS/TRANSIENTS ÉCRITS vs PURGE DÉSINSTALLATION ==' );
const writes = new Set();
for ( const m of all.matchAll( /(?:update_option|add_option|delete_option)\(\s*'([a-z_]+)'/g ) ) { writes.add( m[ 1 ] ); }
for ( const m of all.matchAll( /set_transient\(\s*'([a-z_]+)'/g ) ) { writes.add( m[ 1 ] ); }

const uninstall = read( 'uninstall.php' );
const purged = new Set( [ ...uninstall.matchAll( /delete_option\(\s*(?:LOGINFENNEC_\w+|'([a-z_]+)')\s*\)/g ) ].flatMap( ( m ) => m[ 1 ] ? [ m[ 1 ] ] : [] ) );
const constMap = { LOGINFENNEC_OPTION: 'loginfennec_settings', LOGINFENNEC_ATTEMPTS: 'lnf_login_attempts', LOGINFENNEC_PENDING: 'lnf_pending_installer', LOGINFENNEC_VERSION_KEY: 'lnf_stored_version' };
for ( const [ cst, name ] of Object.entries( constMap ) ) { if ( uninstall.includes( `delete_option( ${ cst } )` ) ) { purged.add( name ); } }
const dynPrefixes = [ 'lnf_sms_', 'lnf_geo_' ];
for ( const name of [ ...writes ].sort() ) {
	if ( [ 'loginfennec_settings', 'lnf_login_attempts', 'lnf_pending_installer', 'lnf_stored_version', 'lnf_secret_seed', 'lnf_security_log' ].includes( name ) ) { continue; }
	const covered = purged.has( name ) || dynPrefixes.some( ( p ) => name.startsWith( p ) ) || name.startsWith( '_transient_lnf_sms_' ) || /transient/i.test( name ) === false && uninstall.includes( 'transient_timeout_lnf_sms_' );
	if ( ! covered && ! uninstall.toLowerCase().includes( `'${ name }'` ) && ! uninstall.includes( `${ name }` ) ) {
		console.log( `  ⚠ '${ name }' écrit mais jamais purgé à la désinstallation` );
	}
}
console.log( '== CRON ==' );
for ( const m of all.matchAll( /wp_schedule_event\([^;]*?'([a-z_]+)'\s*\)|wp_clear_scheduled_hook\(\s*'([a-z_]+)'/g ) ) {
	const hook = m[ 1 ] || m[ 2 ];
	if ( ! uninstall.includes( `'${ hook }'` ) ) { console.log( `  ⚠ cron '${ hook }' non nettoyé dans uninstall.php` ); }
}
console.log( '== FIN AUDIT ==' );
