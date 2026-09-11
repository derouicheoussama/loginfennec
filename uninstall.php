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
 * Désinstallation : suppression de toutes les données du plugin
 * (options, transients), multisite inclus.
 *
 * @package InfinityCustomizer
 *
 * @license GPL-2.0-or-later
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

const INFINITY_LOGINSHIELD_OPTION      = 'infinity_loginshield_settings';
const INFINITY_LOGINSHIELD_ATTEMPTS    = 'inls_login_attempts';
const INFINITY_LOGINSHIELD_TRANSIENT   = 'inls_gh_release';
const INFINITY_LOGINSHIELD_WPORG_CHECK = 'inls_wporg_check';
const INFINITY_LOGINSHIELD_PENDING     = 'inls_pending_installer';
const INFINITY_LOGINSHIELD_VERSION_KEY = 'inls_stored_version';

function inls_uninstall_site() {
	delete_option( INFINITY_LOGINSHIELD_OPTION );
	delete_option( INFINITY_LOGINSHIELD_ATTEMPTS );
	delete_option( INFINITY_LOGINSHIELD_PENDING );
	delete_option( INFINITY_LOGINSHIELD_VERSION_KEY );
	delete_transient( INFINITY_LOGINSHIELD_TRANSIENT );
	delete_transient( INFINITY_LOGINSHIELD_WPORG_CHECK );
}

inls_uninstall_site();

if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'number' => 0,
			'fields' => 'ids',
		)
	);
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		inls_uninstall_site();
		restore_current_blog();
	}
}
