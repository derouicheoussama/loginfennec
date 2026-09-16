<?php

/**
 * ∞ INFINITY CODER — création originale de Derouiche Oussama
 *
 * Plugin   : LoginFennec Pro – Personnalisation page login et Security
 * Auteur   : Derouiche Oussama  ·  https://www.derouicheoussama.com
 * GitHub   : https://github.com/derouicheoussama
 * Copyright © 2026 Derouiche Oussama. Tous droits réservés.
 * Licence  : GPL v2 ou ultérieure — toute copie ou modification de ce
 *            fichier DOIT conserver la présente signature et les mentions
 *            de licence et d'attribution (article 2(c) de la GPL).
 */
/**
 * Plugin Name:       LoginFennec Pro – Personnalisation page login et Security
 * Plugin URI:        https://github.com/derouicheoussama/loginfennec
 * Description:       Personnalisation page login et Security : logo, arrière-plan (flou, opacité, dégradés), 10 styles et 7 thèmes d'interface, liens, icônes sociales aux couleurs officielles, copyright, CSS/JS personnalisé — et bloquez les tentatives de mot de passe avec honeypot, journal de sécurité et score. Interface moderne avec aperçu en direct.
 * Version:           3.4.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Tested up to:      7.1
 * Author:            Derouiche Oussama
 * Author URI:        https://github.com/derouicheoussama
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       loginfennec
 * Domain Path:       /languages
 *
 * Pas d'en-tête « Update URI » volontairement : une fois le plugin accepté
 * sur WordPress.org, c'est le référentiel officiel qui sert les mises à jour.
 *
 * @package LoginFennecPro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Garde anti-doublon : si une autre génération du plugin (ancien dossier
 * « loginfence » ou « infinity-loginshield ») est encore active, ses
 * fonctions existent déjà. On désactive automatiquement l'ancienne copie
 * (dans l'administration) pour éviter toute erreur critique.
 */
if ( function_exists( 'lnf_settings' ) || function_exists( 'inls_settings' ) || function_exists( 'infcl_settings' ) ) {
	if ( is_admin() ) {
		add_action(
			'admin_init',
			function () {
				if ( ! current_user_can( 'activate_plugins' ) || ! function_exists( 'deactivate_plugins' ) ) {
					return;
				}
				$old_candidates = array(
					'loginfence/loginfence.php',
					'infinity-loginshield/infinity-loginshield.php',
				);
				$old_active = array();
				foreach ( $old_candidates as $candidate ) {
					if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $candidate ) ) {
						$old_active[] = $candidate;
					}
				}
				if ( empty( $old_active ) ) {
					return;
				}
				deactivate_plugins( $old_active, true );
				add_action(
					'admin_notices',
					function () use ( $old_active ) {
						echo '<div class="notice notice-warning is-dismissible"><p><strong>LoginFennec Pro :</strong> '
							. esc_html__( 'l’ancienne copie du plugin a été désactivée automatiquement pour éviter tout conflit. Vous pouvez maintenant la supprimer dans Extensions.', 'loginfennec' )
							. '</p></div>';
					}
				);
			}
		);
	}
	return;
}

define( 'LOGINFENNEC_VERSION', '3.4.0' );
define( 'LOGINFENNEC_FILE', __FILE__ );
define( 'LOGINFENNEC_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOGINFENNEC_URL', plugin_dir_url( __FILE__ ) );

/**
 * Dépôt GitHub utilisé pour les mises à jour automatiques tant que le plugin
 * n'est pas hébergé sur WordPress.org. L'updater bascule automatiquement vers
 * le référentiel officiel dès que le plugin y est détecté. Forçage manuel :
 * add_filter( 'loginfennec_update_source', function () { return 'github'; } );
 */
require_once LOGINFENNEC_DIR . 'includes/settings.php';
require_once LOGINFENNEC_DIR . 'includes/trial.php';
require_once LOGINFENNEC_DIR . 'includes/login-appearance.php';
require_once LOGINFENNEC_DIR . 'includes/login-security.php';
require_once LOGINFENNEC_DIR . 'includes/sms-login.php';
require_once LOGINFENNEC_DIR . 'includes/geo-login.php';
require_once LOGINFENNEC_DIR . 'includes/license.php';
require_once LOGINFENNEC_DIR . 'includes/integrity.php';
require_once LOGINFENNEC_DIR . 'includes/recaptcha.php';
require_once LOGINFENNEC_DIR . 'includes/admin-colors.php';

if ( is_admin() ) {
	require_once LOGINFENNEC_DIR . 'includes/admin.php';
}

/**
 * Charge les traductions.
 */
/**
 * Charge les traductions.
 * phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 * Note : conservé pour compatibilité < 4.6 et traductions locales manuelles.
 * WordPress 4.6+ charge automatiquement les traductions depuis wp.org.
 */
function lnf_load_textdomain() {
	load_plugin_textdomain(
		'loginfennec',
		false,
		dirname( plugin_basename( LOGINFENNEC_FILE ) ) . '/languages'
	);
}
add_action( 'init', 'lnf_load_textdomain' );

/**
 * À l'activation : enregistre les réglages par défaut et programme
 * l'ouverture de l'installateur personnalisé.
 */
function lnf_activate() {
	add_option( 'loginfennec_settings', lnf_get_defaults(), '', 'yes' );
	update_option( 'lnf_pending_installer', 1, false );
	add_option( 'lnf_first_activated', time(), '', false );
}
register_activation_hook( __FILE__, 'lnf_activate' );

/**
 * Mode white-label : ajoute la classe CSS et masque la marque.
 */
function lnf_white_label_class( $classes ) {
	if ( ! empty( lnf_settings()['white_label'] ) ) {
		$classes[] = 'lnf-white-label';
	}
	return $classes;
}
add_filter( 'login_body_class', 'lnf_white_label_class' );

/**
 * À la désactivation : arrête le contrôle quotidien de licence.
 */
function lnf_deactivate() {
	if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
		wp_clear_scheduled_hook( 'lnf_license_cron' );
	}
}
register_deactivation_hook( __FILE__, 'lnf_deactivate' );

/**
 * Migration des anciens noms vers « LoginFennec Pro » :
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
	if ( false !== $settings && false === get_option( 'loginfennec_settings' ) ) {
		update_option( 'loginfennec_settings', $settings, 'yes' );
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
	if ( version_compare( $stored, LOGINFENNEC_VERSION, '>=' ) ) {
		return;
	}
	if ( version_compare( $stored, '1.1.0', '<' ) ) {
		update_option( 'lnf_pending_installer', 1, false );
	}
	update_option( 'lnf_stored_version', LOGINFENNEC_VERSION );
}
add_action( 'plugins_loaded', 'lnf_maybe_upgrade', 20 );

/**
 * Initialise les modules.
 */
function lnf_init_modules() {
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
		header( 'X-XSS-Protection: 1; mode=block' );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
	}
}
add_action( 'login_init', 'lnf_login_security_headers' );
