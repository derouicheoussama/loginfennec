<?php

/**
 * ∞ INFINITY CODER — création originale de Derouiche Oussama
 *
 * Plugin   : LoginFence Pro – Login Customizer & Security
 * Auteur   : Derouiche Oussama  ·  https://www.derouicheoussama.com
 * GitHub   : https://github.com/derouicheoussama
 * Copyright © 2026 Derouiche Oussama. Tous droits réservés.
 * Licence  : GPL v2 ou ultérieure — toute copie ou modification de ce
 *            fichier DOIT conserver la présente signature et les mentions
 *            de licence et d'attribution (article 2(c) de la GPL).
 */
/**
 * Plugin Name:       LoginFence Pro – Login Customizer & Security
 * Plugin URI:        https://github.com/derouicheoussama/loginfence
 * Description:       Personnalisez votre page de connexion : logo, arrière-plan (flou, opacité, dégradés), 10 styles et 7 thèmes d'interface, liens, icônes sociales aux couleurs officielles, copyright, CSS personnalisé — et bloquez les tentatives de mot de passe avec journal de sécurité. Interface moderne avec aperçu en direct.
 * Version:           2.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Tested up to:      7.1
 * Author:            Derouiche Oussama
 * Author URI:        https://github.com/derouicheoussama
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       loginfence
 * Domain Path:       /languages
 *
 * Pas d'en-tête « Update URI » volontairement : une fois le plugin accepté
 * sur WordPress.org, c'est le référentiel officiel qui sert les mises à jour.
 * En attendant, l'updater intégré utilise GitHub (voir includes/github-updater.php).
 *
 * @package LoginFencePro
 */

defined( 'ABSPATH' ) || exit;

define( 'LOGINFENCE_VERSION', '2.0.0' );
define( 'LOGINFENCE_FILE', __FILE__ );
define( 'LOGINFENCE_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOGINFENCE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Dépôt GitHub utilisé pour les mises à jour automatiques tant que le plugin
 * n'est pas hébergé sur WordPress.org. L'updater bascule automatiquement vers
 * le référentiel officiel dès que le plugin y est détecté. Forçage manuel :
 * add_filter( 'loginfence_update_source', function () { return 'github'; } );
 */
if ( ! defined( 'LOGINFENCE_GITHUB_REPO' ) ) {
	define( 'LOGINFENCE_GITHUB_REPO', 'derouicheoussama/loginfence' );
}

require_once LOGINFENCE_DIR . 'includes/settings.php';
require_once LOGINFENCE_DIR . 'includes/login-appearance.php';
require_once LOGINFENCE_DIR . 'includes/login-security.php';
require_once LOGINFENCE_DIR . 'includes/github-updater.php';
require_once LOGINFENCE_DIR . 'includes/license.php';

if ( is_admin() ) {
	require_once LOGINFENCE_DIR . 'includes/admin.php';
}

/**
 * Charge les traductions.
 */
function lnf_load_textdomain() {
	load_plugin_textdomain(
		'loginfence',
		false,
		dirname( plugin_basename( LOGINFENCE_FILE ) ) . '/languages'
	);
}
add_action( 'init', 'lnf_load_textdomain' );

/**
 * À l'activation : enregistre les réglages par défaut et programme
 * l'ouverture de l'installateur personnalisé.
 */
function lnf_activate() {
	add_option( 'loginfence_settings', lnf_get_defaults(), '', 'yes' );
	update_option( 'lnf_pending_installer', 1, false );
	add_option( 'lnf_first_activated', time(), '', false );
}
register_activation_hook( __FILE__, 'lnf_activate' );

/**
 * Migration des anciens noms vers « LoginFence Pro » :
 * - Infinity Customizer (< 1.3.0) : infinity_customizer_* / infcl_*
 * - Infinity LoginShield (1.3.0 → 1.9.2) : infinity_loginshield_* / inls_*
 * Les options « legacy » ci-dessous gardent volontairement les anciens préfixes.
 */
function lnf_migrate_legacy() {
	// Réglages : chaîne complète des anciens slugs.
	$settings_chain = array(
		'infinity_customizer_settings',
		'infinity_loginshield_settings',
	);
	$settings = false;
	foreach ( $settings_chain as $old_key ) {
		$value = get_option( $old_key );
		if ( false !== $value ) {
			$settings = $value;
		}
	}
	if ( false !== $settings && false === get_option( 'loginfence_settings' ) ) {
		update_option( 'loginfence_settings', $settings, 'yes' );
	}

	// Journal des tentatives (depuis 1.3.0) et journal de sécurité (1.6.0).
	$attempts_chain = array( 'infcl_login_attempts', 'inls_login_attempts' );
	$attempts       = false;
	foreach ( $attempts_chain as $old_key ) {
		$value = get_option( $old_key );
		if ( false !== $value ) {
			$attempts = $value;
		}
	}
	if ( false !== $attempts && false === get_option( 'lnf_login_attempts' ) ) {
		update_option( 'lnf_login_attempts', $attempts, false );
	}
	$security_log = get_option( 'inls_security_log' );
	if ( false !== $security_log && false === get_option( 'lnf_security_log' ) ) {
		update_option( 'lnf_security_log', $security_log, false );
	}

	// Licence Pro (1.7.0).
	$license = get_option( 'inls_license' );
	if ( false !== $license && false === get_option( 'lnf_license' ) ) {
		update_option( 'lnf_license', $license, false );
	}

	// État : installateur, version, demande d'avis.
	$flag_map = array(
		'inls_pending_installer' => 'lnf_pending_installer',
		'inls_stored_version'    => 'lnf_stored_version',
		'inls_first_activated'   => 'lnf_first_activated',
		'inls_review_dismissed'  => 'lnf_review_dismissed',
	);
	foreach ( $flag_map as $old_key => $new_key ) {
		$value = get_option( $old_key );
		if ( false !== $value && false === get_option( $new_key ) ) {
			update_option( $new_key, $value, false );
		}
	}

	// Transients de cache : chaîne des anciens noms.
	$transient_chain = array(
		'infcl_gh_release',
		'inls_gh_release',
		'infcl_wporg_check',
		'inls_wporg_check',
	);
	foreach ( $transient_chain as $old_transient ) {
		delete_transient( $old_transient );
	}

	// Nettoyage final des anciennes options.
	$legacy_options = array(
		'infinity_customizer_settings',
		'infinity_loginshield_settings',
		'infcl_login_attempts',
		'inls_login_attempts',
		'inls_security_log',
		'inls_license',
		'inls_pending_installer',
		'inls_stored_version',
		'inls_first_activated',
		'inls_review_dismissed',
		'infcl_pending_installer',
		'infcl_stored_version',
	);
	foreach ( $legacy_options as $old_option ) {
		delete_option( $old_option );
	}
}
add_action( 'plugins_loaded', 'lnf_migrate_legacy', 12 );

/**
 * Après une mise à jour : déclenche l'installateur (nouvelles fonctionnalités)
 * et mémorise la version installée.
 */
function lnf_maybe_upgrade() {
	$stored = get_option( 'lnf_stored_version', '0' );
	if ( version_compare( $stored, LOGINFENCE_VERSION, '>=' ) ) {
		return;
	}
	if ( version_compare( $stored, '1.1.0', '<' ) ) {
		update_option( 'lnf_pending_installer', 1, false );
	}
	update_option( 'lnf_stored_version', LOGINFENCE_VERSION );
}
add_action( 'plugins_loaded', 'lnf_maybe_upgrade', 20 );

/**
 * Initialise les modules.
 */
function lnf_init_modules() {
	Lnf_GitHub_Updater::init();
	Lnf_Login_Security::init();
}
add_action( 'plugins_loaded', 'lnf_init_modules' );

/**
 * En-têtes de sécurité et anti-cache sur la page de connexion.
 */
function lnf_login_security_headers() {
	if ( ! headers_sent() ) {
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
	}
}
add_action( 'login_init', 'lnf_login_security_headers' );
