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
 * Désinstallation : suppression de toutes les données du plugin
 * (options, transients), multisite inclus.
 *
 * @package LoginFencePro
 *
 * @license GPL-2.0-or-later
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

const LOGINFENCE_OPTION      = 'loginfence_settings';
const LOGINFENCE_ATTEMPTS    = 'lnf_login_attempts';
const LOGINFENCE_TRANSIENT   = 'lnf_gh_release';
const LOGINFENCE_WPORG_CHECK = 'lnf_wporg_check';
const LOGINFENCE_PENDING     = 'lnf_pending_installer';
const LOGINFENCE_VERSION_KEY = 'lnf_stored_version';

function lnf_uninstall_site() {
	delete_option( LOGINFENCE_OPTION );
	delete_option( LOGINFENCE_ATTEMPTS );
	delete_option( LOGINFENCE_PENDING );
	delete_option( LOGINFENCE_VERSION_KEY );
	delete_transient( LOGINFENCE_TRANSIENT );
	delete_transient( LOGINFENCE_WPORG_CHECK );
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
