<?php
/**
 * Harnais de fumée : stubs WordPress minimaux + chargement du plugin
 * + test du pipeline admin colors (defaults → sanitize → CSS).
 */

error_reporting( E_ALL );
define( 'ABSPATH', __DIR__ . '/' );

// --- Stubs WordPress ---
$GLOBALS['__options']    = array();
$GLOBALS['__filters']    = array();
$GLOBALS['__actions']    = array();
$GLOBALS['__shortcodes'] = array();

function add_option( $k, $v ) { $GLOBALS['__options'][ $k ] = $v; return true; }
function get_option( $k, $d = false ) { return isset( $GLOBALS['__options'][ $k ] ) ? $GLOBALS['__options'][ $k ] : $d; }
function update_option( $k, $v ) { $GLOBALS['__options'][ $k ] = $v; return true; }
function delete_option( $k ) { unset( $GLOBALS['__options'][ $k ] ); return true; }
function add_filter( $t, $cb, $p = 10, $a = 1 ) { $GLOBALS['__filters'][ $t ][] = $cb; return true; }
function add_action( $t, $cb, $p = 10, $a = 1 ) { $GLOBALS['__actions'][ $t ][] = $cb; return true; }
function remove_action( ...$a ) { return true; }
function register_activation_hook( ...$a ) {}
function register_deactivation_hook( ...$a ) {}
function add_shortcode( ...$a ) {}
function wp_clear_scheduled_hook( ...$a ) {}
function wp_next_scheduled( ...$a ) { return false; }
function wp_schedule_event( ...$a ) { return true; }
function wp_unschedule_event( ...$a ) { return true; }
function load_plugin_textdomain( ...$a ) { return true; }
function plugin_basename( $f ) { return basename( dirname( $f ) ) . '/' . basename( $f ); }
function wp_upload_dir() { return array( 'basedir' => sys_get_temp_dir(), 'baseurl' => 'http://x/uploads' ); }
function wp_mkdir_p( ...$a ) { return true; }
function wp_salt( ...$a ) { return 'test-salt'; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function __safe( $s, $d = null ) { return is_null( $s ) ? '' : (string) $s; }
function __( $s, $d = null ) { return $s; }
function esc_html__( $s, $d = null ) { return $s; }
function esc_attr__( $s, $d = null ) { return $s; }
function esc_html_e( $s, $d = null ) { echo $s; }
function esc_attr_e( $s, $d = null ) { echo $s; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_url( $s ) { return (string) $s; }
function esc_url_raw( $s ) { return (string) $s; }
function esc_textarea( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function absint( $v ) { return abs( (int) $v ); }
function selected( $a, $b, $echo = true ) { $r = ( (string) $a === (string) $b ) ? " selected='selected'" : ''; if ( $echo ) { echo $r; } return $r; }
function checked( $a, $b, $echo = true ) { $r = ( (bool) $a === (bool) $b ) ? " checked='checked'" : ''; if ( $echo ) { echo $r; } return $r; }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, (array) $args ); }
if ( ! function_exists( 'sanitize_hex_color' ) ) {
	function sanitize_hex_color( $color ) {
		if ( '' === $color ) { return ''; }
		if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) { return $color; }
		return null;
	}
}
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_textarea_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) ); }
function sanitize_email( $s ) { return filter_var( $s, FILTER_VALIDATE_EMAIL ) ? $s : ''; }
function wp_kses_post( $s ) { return strip_tags( (string) $s ); }
function wp_kses( $s, $allowed ) { return strip_tags( (string) $s ); }
function current_time( $t ) { return time(); }
function get_site_url( ...$a ) { return 'http://test.local'; }
function home_url( $p = '' ) { return 'http://test.local' . $p; }
function admin_url( $p = '' ) { return 'http://test.local/wp-admin/' . $p; }
function wp_get_current_user() { return (object) array( 'ID' => 1, 'roles' => array( 'administrator' ) ); }
function current_user_can( ...$a ) { return true; }
function is_multisite() { return false; }
function get_locale() { return 'fr_FR'; }
function add_menu_page( ...$a ) { return ''; }
function add_submenu_page( ...$a ) { return ''; }
function wp_enqueue_style( ...$a ) {}
function wp_enqueue_script( ...$a ) {}
function wp_add_inline_style( ...$a ) {}
function wp_localize_script( ...$a ) {}
function register_setting( ...$a ) {}
function add_settings_section( ...$a ) {}
function add_settings_field( ...$a ) {}
function submit_button( ...$a ) {}
function wp_nonce_field( ...$a ) {}
function wp_verify_nonce( ...$a ) { return true; }
function wp_create_nonce( ...$a ) { return 'nonce'; }
function wp_unslash( $v ) { return $v; }
function trailingslashit( $s ) { return rtrim( $s, '/\\' ) . '/'; }
function untrailingslashit( $s ) { return rtrim( $s, '/\\' ); }
function add_query_arg( ...$a ) { return ''; }
function remove_query_arg( ...$a ) { return ''; }
function is_admin() { return true; }
function is_user_logged_in() { return true; }
function is_admin_bar_showing() { return true; }
function get_bloginfo( $k ) { return 'Test'; }
function wp_get_theme() { return (object) array( 'Name' => 'Test' ); }
function date_i18n( $f, $t ) { return date( $f, $t ); }
function get_date_from_gmt( ...$a ) { return date( 'Y-m-d H:i:s' ); }
function get_transient( ...$a ) { return false; }
function set_transient( ...$a ) { return true; }
function delete_transient( ...$a ) { return true; }
function wp_remote_post( ...$a ) { return array( 'response' => array( 'code' => 200 ), 'body' => '{}' ); }
function wp_remote_get( ...$a ) { return array( 'response' => array( 'code' => 200 ), 'body' => '{}' ); }
function wp_remote_retrieve_response_code( ...$a ) { return 200; }
function wp_remote_retrieve_body( $r ) { return isset( $r['body'] ) ? $r['body'] : ''; }
function is_wp_error( $t ) { return $t instanceof WP_Error; }
class WP_Error {}
function add_role( ...$a ) {}
function remove_role( ...$a ) {}
function get_role( ...$a ) { return null; }
function wp_mail( ...$a ) { return true; }
function wp_next_scheduled_hook( ...$a ) { return false; }
function do_action( ...$a ) {}
function apply_filters( $t, $v ) { return isset( $GLOBALS['__filters'][ $t ] ) ? array_reduce( $GLOBALS['__filters'][ $t ], function ( $c, $cb ) { return $cb( $c ); }, $v ) : $v; }
function has_filter( ...$a ) { return false; }
function did_action( ...$a ) { return 0; }
function wpautop( $s ) { return '<p>' . $s . '</p>'; }
function shortcode_atts( $d, $a ) { return array_merge( $d, (array) $a ); }
function get_userdata( ...$a ) { return false; }
function get_user_by( ...$a ) { return false; }
function username_exists( ...$a ) { return false; }
function email_exists( ...$a ) { return false; }
function wp_get_admin_colors() { return array(); }
function register_activation( ...$a ) {}
function load_template( ...$a ) {}
function get_temp_dir() { return sys_get_temp_dir() . '/'; }
function wp_is_writable( ...$a ) { return true; }
function wp_hash( $d ) { return hash( 'sha256', $d ); }
function wp_generate_password( ...$a ) { return 'pass'; }
function get_home_url( ...$a ) { return 'http://test.local'; }
function network_site_url( ...$a ) { return 'http://test.local'; }
function is_email( $s ) { return filter_var( $s, FILTER_VALIDATE_EMAIL ) ? $s : false; }
function antispambot( $s ) { return $s; }
function size_format( ...$a ) { return ''; }
function wp_read_image_metadata( ...$a ) { return array(); }

define( 'LOGINFENNEC_FILE', __DIR__ . '/loginfennec.php' );

// État « activé » passé en argument : l'option est posée AVANT le chargement
// du plugin, car lnf_settings() met ses réglages en cache statique.
$enabled = isset( $argv[1] ) && 'enabled' === $argv[1];
if ( $enabled ) {
	$GLOBALS['__options']['loginfennec_settings'] = array(
		'admin_enable' => '1',
		'admin_bg'     => '#ABCDEF',
	);
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) { define( 'HOUR_IN_SECONDS', 3600 ); }
if ( ! defined( 'DAY_IN_SECONDS' ) ) { define( 'DAY_IN_SECONDS', 86400 ); }
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) { define( 'MINUTE_IN_SECONDS', 60 ); }
if ( ! defined( 'WP_DEBUG' ) ) { define( 'WP_DEBUG', false ); }

function plugin_dir_path( $f ) { return trailingslashit( dirname( $f ) ); }
function plugin_dir_url( $f ) { return 'http://test.local/wp-content/plugins/loginfennec/'; }
function plugins_url( $p = '', $f = '' ) { return 'http://test.local/wp-content/plugins/loginfennec/' . $p; }
function register_block_type( ...$a ) {}
function wp_set_script_translations( ...$a ) {}
function load_plugin_textdomain_retry( ...$a ) {}
function get_plugin_data( ...$a ) { return array( 'Version' => LOGINFENNEC_VERSION ); }

require __DIR__ . '/loginfennec.php';

// --- Tests ---
$fail = 0;
function check( $label, $cond ) {
	global $fail;
	echo ( $cond ? '  ✔ ' : '  ✘ ' ) . $label . "\n";
	if ( ! $cond ) { $fail++; }
}

echo "== Defaults ==\n";
$d = lnf_get_defaults();
check( 'admin_enable par défaut = false', isset( $d['admin_enable'] ) && false === $d['admin_enable'] );
check( 'admin_bg par défaut = #1d2327', '#1d2327' === $d['admin_bg'] );
check( 'adminbar_hover présent', isset( $d['adminbar_hover'] ) );

echo "== Sanitization ==\n";
$in = array(
	'admin_enable'    => '1',
	'admin_bg'        => '#ABCDEF',
	'admin_text'      => 'not-a-color',
	'admin_hover_bg'  => '#123',
	'admin_active_bg' => '<script>#ff0000</script>',
);
$out = lnf_sanitize_settings( $in );
check( 'admin_enable = 1', 1 === $out['admin_enable'] || true === $out['admin_enable'] || '1' === $out['admin_enable'] );
check( 'admin_bg accepté', '#ABCDEF' === $out['admin_bg'] );
check( 'admin_text rejeté → défaut', '#c3c4c7' === $out['admin_text'] );
check( 'admin_hover_bg 3 digits accepté', '#123' === $out['admin_hover_bg'] );

echo "== Module admin colors ==\n";
if ( $enabled ) {
	$c = lnf_admin_colors();
	check( 'admin_enable actif', ! empty( $c['admin_enable'] ) );
	check( 'admin_bg lu des réglages', '#ABCDEF' === $c['admin_bg'] );
	check( 'admin_text retombé sur défaut sûr', '#c3c4c7' === $c['admin_text'] );
	$css = lnf_admin_colors_css();
	check( 'CSS généré non vide', is_string( $css ) && strlen( $css ) > 100 );
	check( 'CSS contient #adminmenu', false !== strpos( $css, '#adminmenu' ) );
	check( 'CSS contient #ABCDEF', false !== strpos( $css, '#ABCDEF' ) );
	check( 'CSS sans balise script', false === stripos( $css, '<script' ) );
} else {
	check( 'CSS vide si désactivé (defaults)', '' === lnf_admin_colors_css() );
}

echo "\n" . ( $fail ? "ÉCHEC : $fail test(s)" : 'TOUS LES TESTS PASSENT' ) . "\n";
exit( $fail ? 1 : 0 );
