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
 * Version:           4.3.0
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
				$self      = plugin_basename( __FILE__ );
				$old_active = array();
				$self_newer = false;
				$other_newer = '';

				// 1. Anciennes générations (loginfence, infinity-loginshield).
				foreach ( array( 'loginfence/loginfence.php', 'infinity-loginshield/infinity-loginshield.php' ) as $candidate ) {
					if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $candidate ) ) {
						$old_active[] = $candidate;
					}
				}

				// 2. Doublons de LoginFennec elle-même (dossiers loginfennec-1,
				// loginfennec-2… créés quand un remplacement d'install échoue).
				// La copie la plus RÉCENTE reste active, l'autre est désactivée.
				$self_version = '';
				$self_header  = get_file_data( __FILE__, array( 'v' => 'Version' ) );
				if ( ! empty( $self_header['v'] ) ) {
					$self_version = (string) $self_header['v'];
				}
				foreach ( (array) get_option( 'active_plugins', array() ) as $active ) {
					if ( ! is_string( $active ) || $active === $self || ! preg_match( '#(^|/)loginfennec\.php$#', $active ) ) {
						continue;
					}
					$other_file = WP_PLUGIN_DIR . '/' . $active;
					$other_head = file_exists( $other_file ) ? get_file_data( $other_file, array( 'v' => 'Version' ) ) : array();
					$other_ver  = empty( $other_head['v'] ) ? '0' : (string) $other_head['v'];
					if ( version_compare( $self_version, $other_ver, '>=' ) ) {
						$old_active[] = $active;
					} else {
						$other_newer = $other_ver;
					}
				}

				if ( empty( $old_active ) && '' === $other_newer ) {
					return;
				}

				if ( '' !== $other_newer ) {
					// Cette copie est plus ancienne que celle déjà active :
					// on se désactive soi-même (jamais la copie fonctionnelle).
					deactivate_plugins( $self, true );
					add_action(
						'admin_notices',
						function () use ( $other_newer ) {
							echo '<div class="notice notice-warning is-dismissible"><p><strong>LoginFennec Pro :</strong> '
								. esc_html( sprintf( /* translators: %s: numéro de version. */ __( 'une copie plus récente (v%s) est déjà active — cette copie obsolète a été désactivée automatiquement. Supprimez-la dans Extensions.', 'loginfennec' ), $other_newer ) )
								. '</p></div>';
						}
					);
					return;
				}

				deactivate_plugins( $old_active, true );
				add_action(
					'admin_notices',
					function () use ( $old_active ) {
						echo '<div class="notice notice-warning is-dismissible"><p><strong>LoginFennec Pro :</strong> '
							. esc_html( count( $old_active ) ) . ' '
							. esc_html__( 'copie obsolète du plugin était encore active — elle a été désactivée automatiquement pour éviter tout conflit. Supprimez-la dans Extensions.', 'loginfennec' )
							. '</p></div>';
					}
				);
			}
		);
	}
	return;
}

define( 'LOGINFENNEC_VERSION', '4.3.0' );
define( 'LOGINFENNEC_FILE', __FILE__ );
define( 'LOGINFENNEC_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOGINFENNEC_URL', plugin_dir_url( __FILE__ ) );

require_once LOGINFENNEC_DIR . 'includes/settings.php';
require_once LOGINFENNEC_DIR . 'includes/trial.php';
require_once LOGINFENNEC_DIR . 'includes/login-appearance.php';
require_once LOGINFENNEC_DIR . 'includes/login-security.php';
require_once LOGINFENNEC_DIR . 'includes/two-factor.php';
require_once LOGINFENNEC_DIR . 'includes/sms-login.php';
require_once LOGINFENNEC_DIR . 'includes/geo-login.php';
require_once LOGINFENNEC_DIR . 'includes/license.php';
require_once LOGINFENNEC_DIR . 'includes/integrity.php';
require_once LOGINFENNEC_DIR . 'includes/recaptcha.php';
require_once LOGINFENNEC_DIR . 'includes/admin-colors.php';

// Canal de mise à jour auto-hébergé (DÉSACTIVÉ par défaut — une fois le
// plugin publié sur WordPress.org, les mises à jour arrivent nativement).
// Activation pour vos clients directs, dans wp-config.php :
//   define( 'LOGINFENNEC_UPDATE_SERVER', 'https://updates.exemple.com/loginfennec/update.json' );
// Voir docs/UPDATE-SERVER.md (contrat serveur + procédure de publication).
// Le paquet wp.org exclut class-updater.php : le file_exists évite tout
// fatal sur ce paquet.
if ( defined( 'LOGINFENNEC_UPDATE_SERVER' ) && constant( 'LOGINFENNEC_UPDATE_SERVER' ) && 0 === strpos( (string) constant( 'LOGINFENNEC_UPDATE_SERVER' ), 'https://' ) && file_exists( LOGINFENNEC_DIR . 'includes/class-updater.php' ) ) {
	require_once LOGINFENNEC_DIR . 'includes/class-updater.php';
	Lnf_Update_Server::init();
}

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
	// One-shot : sans ce drapeau, la migration rejouait ~15 requêtes SQL
	// (get_option/delete_option) à CHAQUE chargement de page.
	if ( get_option( 'lnf_legacy_migrated' ) ) {
		return;
	}
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

	update_option( 'lnf_legacy_migrated', 1, false );
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
 * Mode sans échec : les modules de sécurité restent à l'écart pour dépanner.
 */
function lnf_init_modules() {
	if ( function_exists( 'lnf_safe_mode' ) && lnf_safe_mode() ) {
		return;
	}
	Lnf_Login_Security::init();
}
add_action( 'plugins_loaded', 'lnf_init_modules' );

/**
 * Mises à jour automatiques opt-in (réglage « auto_update ») via le
 * mécanisme natif de WordPress — aucun updater personnalisé.
 */
function lnf_auto_update_plugin( $update, $item ) {
	if ( isset( $item->slug ) && 'loginfennec' === $item->slug && function_exists( 'lnf_get_option' ) && ! empty( lnf_get_option( 'auto_update' ) ) ) {
		return true;
	}
	return $update;
}
add_filter( 'auto_update_plugin', 'lnf_auto_update_plugin', 10, 2 );

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
