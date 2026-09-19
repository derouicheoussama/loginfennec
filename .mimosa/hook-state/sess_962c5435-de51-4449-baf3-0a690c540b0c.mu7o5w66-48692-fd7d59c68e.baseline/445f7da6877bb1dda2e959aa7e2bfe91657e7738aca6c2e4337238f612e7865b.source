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
	delete_transient( LOGINFENNEC_TRANSIENT );
	delete_transient( LOGINFENNEC_WPORG_CHECK );

	// Connexion par SMS : numéros de téléphone et codes OTP (transients).
	delete_metadata( 'user', 0, 'lnf_phone', '', true );
	global $wpdb;
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_lnf_sms_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_lnf_sms_' ) . '%'
		)
	);
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
