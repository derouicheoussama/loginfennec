<?php

/**
 * ∞ INFINITY CODER — création originale de Derouiche Oussama
 *
 * Plugin   : LoginFennec Pro – Login Customizer & Security
 * Auteur   : Derouiche Oussama  ·  https://www.derouicheoussama.com
 * GitHub   : https://github.com/derouicheoussama
 * Copyright © 2026 Derouiche Oussama. Tous droits réservés.
 * Licence  : GPL v2 ou ultérieure — toute copie ou modification de ce
 *            fichier DOIT conserver la présente signature et les mentions
 *            de licence et d'attribution (article 2(c) de la GPL).
 */
/**
 * Désinstallation : suppression de toutes les données du plugin
 * (options, transients), multisite inclus.
 *
 * @package LoginFennecPro
 *
 * @license GPL-2.0-or-later
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

const LOGINFENNEC_OPTION      = 'loginfennec_settings';
const LOGINFENNEC_ATTEMPTS    = 'lnf_login_attempts';
const LOGINFENNEC_TRANSIENT   = 'lnf_gh_release';
const LOGINFENNEC_WPORG_CHECK = 'lnf_wporg_check';
const LOGINFENNEC_PENDING     = 'lnf_pending_installer';
const LOGINFENNEC_VERSION_KEY = 'lnf_stored_version';

function lnf_uninstall_site() {
	delete_option( LOGINFENNEC_OPTION );
	delete_option( LOGINFENNEC_ATTEMPTS );
	delete_option( LOGINFENNEC_PENDING );
	delete_option( LOGINFENNEC_VERSION_KEY );
	delete_option( 'lnf_secret_seed' );
	delete_option( 'lnf_security_log' );
	delete_option( 'lnf_license' );
	delete_option( 'lnf_trial_data' );
	delete_option( 'lnf_first_activated' );
	delete_option( 'lnf_review_dismissed' );
	delete_transient( LOGINFENNEC_TRANSIENT );
	delete_transient( LOGINFENNEC_WPORG_CHECK );
	delete_transient( 'lnf_wporg_latest' );

	// Connexion par SMS et GEO : numéros, codes OTP, compteurs anti-abus,
	// cache de géolocalisation (transients dynamiques).
	delete_metadata( 'user', 0, 'lnf_phone', '', true );
	global $wpdb;
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_lnf_sms_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_lnf_sms_' ) . '%',
			$wpdb->esc_like( '_transient_lnf_geo_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_lnf_geo_' ) . '%'
		)
	);

	// Crons du plugin.
	wp_clear_scheduled_hook( 'lnf_license_cron' );
}

lnf_uninstall_site();

if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'number' => 0,
			'fields' => 'ids',
		)
	);
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		lnf_uninstall_site();
		restore_current_blog();
	}
}
