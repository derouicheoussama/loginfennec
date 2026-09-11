<?php
/**
 * Plugin Name:       Infinity Customizer – Login Customizer & Security
 * Plugin URI:        https://github.com/derouiche-oussama/infinity-customizer
 * Description:       Personnalisez votre page de connexion : logo, arrière-plan (flou, opacité, dégradés), 6 styles modernes, liens, icônes sociales, copyright — et bloquez les tentatives de mot de passe. Interface d'administration moderne avec aperçu en direct.
 * Version:           1.2.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Tested up to:      7.1
 * Author:            Derouiche Oussama
 * Author URI:        https://github.com/derouiche-oussama
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       infinity-customizer
 * Domain Path:       /languages
 *
 * Pas d'en-tête « Update URI » volontairement : une fois le plugin accepté
 * sur WordPress.org, c'est le référentiel officiel qui sert les mises à jour.
 * En attendant, l'updater intégré utilise GitHub (voir includes/github-updater.php).
 *
 * @package InfinityCustomizer
 */

defined( 'ABSPATH' ) || exit;

define( 'INFINITY_CUSTOMIZER_VERSION', '1.2.0' );
define( 'INFINITY_CUSTOMIZER_FILE', __FILE__ );
define( 'INFINITY_CUSTOMIZER_DIR', plugin_dir_path( __FILE__ ) );
define( 'INFINITY_CUSTOMIZER_URL', plugin_dir_url( __FILE__ ) );

/**
 * Dépôt GitHub utilisé pour les mises à jour automatiques tant que le plugin
 * n'est pas hébergé sur WordPress.org. L'updater bascule automatiquement vers
 * le référentiel officiel dès que le plugin y est détecté. Forçage manuel :
 * add_filter( 'infinity_customizer_update_source', function () { return 'github'; } );
 */
if ( ! defined( 'INFINITY_CUSTOMIZER_GITHUB_REPO' ) ) {
	define( 'INFINITY_CUSTOMIZER_GITHUB_REPO', 'derouiche-oussama/infinity-customizer' );
}

require_once INFINITY_CUSTOMIZER_DIR . 'includes/settings.php';
require_once INFINITY_CUSTOMIZER_DIR . 'includes/login-appearance.php';
require_once INFINITY_CUSTOMIZER_DIR . 'includes/login-security.php';
require_once INFINITY_CUSTOMIZER_DIR . 'includes/github-updater.php';

if ( is_admin() ) {
	require_once INFINITY_CUSTOMIZER_DIR . 'includes/admin.php';
}

/**
 * Charge les traductions.
 */
function infcl_load_textdomain() {
	load_plugin_textdomain(
		'infinity-customizer',
		false,
		dirname( plugin_basename( INFINITY_CUSTOMIZER_FILE ) ) . '/languages'
	);
}
add_action( 'init', 'infcl_load_textdomain' );

/**
 * À l'activation : enregistre les réglages par défaut et programme
 * l'ouverture de l'installateur personnalisé.
 */
function infcl_activate() {
	add_option( 'infinity_customizer_settings', infcl_get_defaults(), '', 'yes' );
	update_option( 'infcl_pending_installer', 1, false );
}
register_activation_hook( __FILE__, 'infcl_activate' );

/**
 * Après une mise à jour : déclenche l'installateur (nouvelles fonctionnalités)
 * et mémorise la version installée.
 */
function infcl_maybe_upgrade() {
	$stored = get_option( 'infcl_stored_version', '0' );
	if ( version_compare( $stored, INFINITY_CUSTOMIZER_VERSION, '>=' ) ) {
		return;
	}
	if ( version_compare( $stored, '1.1.0', '<' ) ) {
		update_option( 'infcl_pending_installer', 1, false );
	}
	update_option( 'infcl_stored_version', INFINITY_CUSTOMIZER_VERSION );
}
add_action( 'plugins_loaded', 'infcl_maybe_upgrade', 20 );

/**
 * Initialise les modules.
 */
function infcl_init_modules() {
	Infcl_GitHub_Updater::init();
	Infcl_Login_Security::init();
}
add_action( 'plugins_loaded', 'infcl_init_modules' );

/**
 * En-têtes de sécurité sur la page de connexion.
 */
function infcl_login_security_headers() {
	if ( ! headers_sent() ) {
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	}
}
add_action( 'login_init', 'infcl_login_security_headers' );
