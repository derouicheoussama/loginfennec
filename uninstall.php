<?php
/**
 * Désinstallation : suppression de toutes les données du plugin.
 *
 * @package InfinityCustomizer
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

const INFINITY_CUSTOMIZER_OPTION    = 'infinity_customizer_settings';
const INFINITY_CUSTOMIZER_ATTEMPTS  = 'infcl_login_attempts';
const INFINITY_CUSTOMIZER_TRANSIENT = 'infcl_gh_release';

function infcl_uninstall_site() {
	delete_option( INFINITY_CUSTOMIZER_OPTION );
	delete_option( INFINITY_CUSTOMIZER_ATTEMPTS );
	delete_transient( INFINITY_CUSTOMIZER_TRANSIENT );
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
