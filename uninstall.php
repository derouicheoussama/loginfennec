<?php
/**
 * Désinstallation : suppression de toutes les données du plugin
 * (options, transients), multisite inclus.
 *
 * @package InfinityCustomizer
 *
 * @license GPL-2.0-or-later
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

const INFINITY_CUSTOMIZER_OPTION      = 'infinity_customizer_settings';
const INFINITY_CUSTOMIZER_ATTEMPTS    = 'infcl_login_attempts';
const INFINITY_CUSTOMIZER_TRANSIENT   = 'infcl_gh_release';
const INFINITY_CUSTOMIZER_WPORG_CHECK = 'infcl_wporg_check';
const INFINITY_CUSTOMIZER_PENDING     = 'infcl_pending_installer';
const INFINITY_CUSTOMIZER_VERSION_KEY = 'infcl_stored_version';

function infcl_uninstall_site() {
	delete_option( INFINITY_CUSTOMIZER_OPTION );
	delete_option( INFINITY_CUSTOMIZER_ATTEMPTS );
	delete_option( INFINITY_CUSTOMIZER_PENDING );
	delete_option( INFINITY_CUSTOMIZER_VERSION_KEY );
	delete_transient( INFINITY_CUSTOMIZER_TRANSIENT );
	delete_transient( INFINITY_CUSTOMIZER_WPORG_CHECK );
}

infcl_uninstall_site();

if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'number' => 0,
			'fields' => 'ids',
		)
	);
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		infcl_uninstall_site();
		restore_current_blog();
	}
}
