<?php

/**
 * ∞ INFINITY CODER — création originale de Derouiche Oussama
 *
 * Plugin   : Infinity LoginShield – Login Customizer & Security
 * Auteur   : Derouiche Oussama  ·  https://www.derouicheoussama.com
 * GitHub   : https://github.com/derouicheoussama
 * Copyright © 2026 Derouiche Oussama. Tous droits réservés.
 * Licence  : GPL v2 ou ultérieure — toute copie ou modification de ce
 *            fichier DOIT conserver la présente signature et les mentions
 *            de licence et d'attribution (article 2(c) de la GPL).
 */
/**
 * Plugin Name:       Infinity LoginShield – Login Customizer & Security
 * Plugin URI:        https://github.com/derouicheoussama/infinity-loginshield
 * Description:       Personnalisez votre page de connexion : logo, arrière-plan (flou, opacité, dégradés), 10 styles et 7 thèmes d'interface, liens, icônes sociales aux couleurs officielles, copyright, CSS personnalisé — et bloquez les tentatives de mot de passe avec journal de sécurité. Interface moderne avec aperçu en direct.
 * Version:           1.9.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Tested up to:      7.1
 * Author:            Derouiche Oussama
 * Author URI:        https://github.com/derouicheoussama
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       infinity-loginshield
 * Domain Path:       /languages
 *
 * Pas d'en-tête « Update URI » volontairement : une fois le plugin accepté
 * sur WordPress.org, c'est le référentiel officiel qui sert les mises à jour.
 * En attendant, l'updater intégré utilise GitHub (voir includes/github-updater.php).
 *
 * @package InfinityLoginShield
 */

defined( 'ABSPATH' ) || exit;

define( 'INFINITY_LOGINSHIELD_VERSION', '1.9.1' );
define( 'INFINITY_LOGINSHIELD_FILE', __FILE__ );
define( 'INFINITY_LOGINSHIELD_DIR', plugin_dir_path( __FILE__ ) );
define( 'INFINITY_LOGINSHIELD_URL', plugin_dir_url( __FILE__ ) );

/**
 * Dépôt GitHub utilisé pour les mises à jour automatiques tant que le plugin
 * n'est pas hébergé sur WordPress.org. L'updater bascule automatiquement vers
 * le référentiel officiel dès que le plugin y est détecté. Forçage manuel :
 * add_filter( 'infinity_loginshield_update_source', function () { return 'github'; } );
 */
if ( ! defined( 'INFINITY_LOGINSHIELD_GITHUB_REPO' ) ) {
	define( 'INFINITY_LOGINSHIELD_GITHUB_REPO', 'derouicheoussama/infinity-loginshield' );
}

require_once INFINITY_LOGINSHIELD_DIR . 'includes/settings.php';
require_once INFINITY_LOGINSHIELD_DIR . 'includes/login-appearance.php';
require_once INFINITY_LOGINSHIELD_DIR . 'includes/login-security.php';
require_once INFINITY_LOGINSHIELD_DIR . 'includes/github-updater.php';
require_once INFINITY_LOGINSHIELD_DIR . 'includes/license.php';

if ( is_admin() ) {
	require_once INFINITY_LOGINSHIELD_DIR . 'includes/admin.php';
}

/**
 * Charge les traductions.
 */
function inls_load_textdomain() {
	load_plugin_textdomain(
		'infinity-loginshield',
		false,
		dirname( plugin_basename( INFINITY_LOGINSHIELD_FILE ) ) . '/languages'
	);
}
add_action( 'init', 'inls_load_textdomain' );

/**
 * À l'activation : enregistre les réglages par défaut et programme
 * l'ouverture de l'installateur personnalisé.
 */
function inls_activate() {
	add_option( 'infinity_loginshield_settings', inls_get_defaults(), '', 'yes' );
	update_option( 'inls_pending_installer', 1, false );
	add_option( 'inls_first_activated', time(), '', false );
}
register_activation_hook( __FILE__, 'inls_activate' );

/**
 * Migration depuis l'ancien nom « Infinity Customizer » (versions < 1.3.0) :
 * reprend les réglages et le journal de tentatives, puis nettoie l'ancien nom.
 * Les options « legacy » ci-dessous gardent volontairement l'ancien préfixe.
 */
function inls_migrate_legacy() {
	$old_settings = get_option( 'infinity_customizer_settings' );
	if ( false !== $old_settings && false === get_option( 'infinity_loginshield_settings' ) ) {
		update_option( 'infinity_loginshield_settings', $old_settings, 'yes' );
	}
	$old_attempts = get_option( 'infcl_login_attempts' );
	if ( false !== $old_attempts && false === get_option( 'inls_login_attempts' ) ) {
		update_option( 'inls_login_attempts', $old_attempts, false );
	}
	if ( false !== $old_settings ) {
		delete_option( 'infinity_customizer_settings' );
		delete_option( 'infcl_login_attempts' );
		delete_option( 'infcl_pending_installer' );
		delete_option( 'infcl_stored_version' );
		delete_transient( 'infcl_gh_release' );
		delete_transient( 'infcl_wporg_check' );
	}
}
add_action( 'plugins_loaded', 'inls_migrate_legacy', 12 );

/**
 * Après une mise à jour : déclenche l'installateur (nouvelles fonctionnalités)
 * et mémorise la version installée.
 */
function inls_maybe_upgrade() {
	$stored = get_option( 'inls_stored_version', '0' );
	if ( version_compare( $stored, INFINITY_LOGINSHIELD_VERSION, '>=' ) ) {
		return;
	}
	if ( version_compare( $stored, '1.1.0', '<' ) ) {
		update_option( 'inls_pending_installer', 1, false );
	}
	update_option( 'inls_stored_version', INFINITY_LOGINSHIELD_VERSION );
}
add_action( 'plugins_loaded', 'inls_maybe_upgrade', 20 );

/**
 * Initialise les modules.
 */
function inls_init_modules() {
	Inls_GitHub_Updater::init();
	Inls_Login_Security::init();
}
add_action( 'plugins_loaded', 'inls_init_modules' );

/**
 * En-têtes de sécurité et anti-cache sur la page de connexion.
 */
function inls_login_security_headers() {
	if ( ! headers_sent() ) {
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
	}
}
add_action( 'login_init', 'inls_login_security_headers' );
