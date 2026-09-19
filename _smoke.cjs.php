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
$GLOBALS['__transients'] = array();
function get_transient( $k ) {
	if ( ! isset( $GLOBALS['__transients'][ $k ] ) ) { return false; }
	$t = $GLOBALS['__transients'][ $k ];
	if ( $t['x'] < time() ) { unset( $GLOBALS['__transients'][ $k ] ); return false; }
	return $t['v'];
}
function set_transient( $k, $v, $e = 0 ) { $GLOBALS['__transients'][ $k ] = array( 'v' => $v, 'x' => $e > 0 ? time() + $e : PHP_INT_MAX ); return true; }
function delete_transient( $k ) { unset( $GLOBALS['__transients'][ $k ] ); return true; }
function wp_remote_post( ...$a ) { return array( 'response' => array( 'code' => 200 ), 'body' => '{}' ); }
function wp_remote_get( ...$a ) { return array( 'response' => array( 'code' => 200 ), 'body' => '{}' ); }
function wp_remote_retrieve_response_code( ...$a ) { return 200; }
function wp_remote_retrieve_body( $r ) { return isset( $r['body'] ) ? $r['body'] : ''; }
function is_wp_error( $t ) { return $t instanceof WP_Error; }
class WP_Error {
	protected $code    = '';
	protected $message = '';
	public function __construct( $code = '', $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
function add_role( ...$a ) {}
function remove_role( ...$a ) {}
function get_role( ...$a ) { return null; }
function wp_mail( ...$a ) { return true; }
function wp_next_scheduled_hook( ...$a ) { return false; }
function do_action( $tag, ...$args ) {
	if ( empty( $GLOBALS['__actions'][ $tag ] ) ) { return; }
	foreach ( $GLOBALS['__actions'][ $tag ] as $cb ) {
		__invoke_cb( $cb, $args, $tag );
	}
}
function apply_filters( $t, $v ) {
	if ( empty( $GLOBALS['__filters'][ $t ] ) ) { return $v; }
	foreach ( $GLOBALS['__filters'][ $t ] as $cb ) {
		$v = __invoke_cb( $cb, array( $v ), $t, $v );
	}
	return $v;
}
/**
 * Invoque un callback de hook avec le bon nombre d'arguments.
 * Une Error fatale (classe/fonction introuvable…) fait ÉCHOUER le harnais :
 * c'est exactement la classe de bug qui a tué l'activation en production.
 */
function __invoke_cb( $cb, $args, $tag, $default = null ) {
	try {
		if ( $cb instanceof Closure || is_string( $cb ) ) {
			$ref = new ReflectionFunction( $cb );
			$n   = $ref->getNumberOfParameters();
			while ( count( $args ) < $n ) { $args[] = $default; }
			return $ref->invokeArgs( $args );
		}
		if ( is_array( $cb ) && 2 === count( $cb ) ) {
			$ref = new ReflectionMethod( $cb[0], $cb[1] );
			$n   = $ref->getNumberOfParameters();
			while ( count( $args ) < $n ) { $args[] = $default; }
			return $ref->invokeArgs( is_object( $cb[0] ) ? $cb[0] : null, $args );
		}
		return call_user_func( $cb );
	} catch ( Throwable $e ) {
		echo '  ✘ ERREUR HOOK ' . $tag . ' : ' . $e->getMessage() . "\n";
		$GLOBALS['__hook_errors'][] = $tag . ': ' . $e->getMessage();
		return $default;
	}
}
$GLOBALS['__hook_errors'] = array();
function has_filter( ...$a ) { return false; }
function did_action( ...$a ) { return 0; }
function wpautop( $s ) { return '<p>' . $s . '</p>'; }
function shortcode_atts( $d, $a ) { return array_merge( $d, (array) $a ); }
function get_userdata( $id ) {
	if ( ! isset( $GLOBALS['__users'][ $id ] ) ) { return false; }
	$u           = (object) $GLOBALS['__users'][ $id ];
	$u->ID       = $id;
	$u->roles    = array( 'subscriber' );
	$u->allcaps  = array( 'read' => true );
	return $u;
}
function get_user_by( ...$a ) { return false; }
function get_user_meta( $id, $k, $single ) { return isset( $GLOBALS['__usermeta'][ $id ][ $k ] ) ? $GLOBALS['__usermeta'][ $id ][ $k ] : ''; }
function update_user_meta( $id, $k, $v ) { $GLOBALS['__usermeta'][ $id ][ $k ] = $v; return true; }
function delete_user_meta( $id, $k ) { unset( $GLOBALS['__usermeta'][ $id ][ $k ] ); return true; }
$GLOBALS['__users']     = array();
$GLOBALS['__usermeta']  = array();
function get_users( $args = array() ) {
	$out = array();
	foreach ( $GLOBALS['__users'] as $id => $u ) {
		if ( isset( $args['meta_key'] ) && ! isset( $GLOBALS['__usermeta'][ $id ][ $args['meta_key'] ] ) ) { continue; }
		if ( isset( $args['meta_key'], $args['meta_value'] ) && $GLOBALS['__usermeta'][ $id ][ $args['meta_key'] ] !== $args['meta_value'] ) { continue; }
		$out[] = $id;
	}
	return $out;
}
function wp_set_current_user( $id ) { $GLOBALS['__current_user'] = $id; }
function wp_set_auth_cookie( ...$a ) { $GLOBALS['__auth_cookie'] = $a; }
function wp_validate_redirect( $to, $default ) {
	$to = (string) $to;
	if ( '' !== $to && 0 === strpos( $to, 'http://test.local' ) ) { return $to; }
	return $default;
}
function wp_sanitize_redirect( $to ) { return preg_replace( '/[\r\n\t]/', '', (string) $to ); }
function wp_rand( $min = 0, $max = 0 ) { return 0 === $max ? mt_rand() : mt_rand( $min, $max ); }
function wp_generate_password( $len = 12, $special = true, $extra = false ) {
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$out   = '';
	for ( $i = 0; $i < $len; $i++ ) { $out .= $chars[ wp_rand( 0, strlen( $chars ) - 1 ) ]; }
	return $out;
}
function wp_strip_all_tags( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_user( $s, $strict = false ) { return trim( strip_tags( (string) $s ) ); }
function wp_doing_ajax() { return false; }
function wp_doing_cron() { return false; }
function wp_is_json_request() { return false; }
function nocache_headers() {}
function wp_safe_redirect( $u ) { return false; }
function remove_filter( ...$a ) { return true; }
function has_action( ...$a ) { return false; }
function username_exists( ...$a ) { return false; }
function email_exists( ...$a ) { return false; }
function wp_get_admin_colors() { return array(); }
function register_activation( ...$a ) {}
function load_template( ...$a ) {}
function get_temp_dir() { return sys_get_temp_dir() . '/'; }
function wp_is_writable( ...$a ) { return true; }
function wp_hash( $d ) { return hash( 'sha256', $d ); }
function get_home_url( ...$a ) { return 'http://test.local'; }
function network_site_url( ...$a ) { return 'http://test.local'; }
function is_email( $s ) { return filter_var( $s, FILTER_VALIDATE_EMAIL ) ? $s : false; }
function antispambot( $s ) { return $s; }
function size_format( ...$a ) { return ''; }
function wp_read_image_metadata( ...$a ) { return array(); }

define( 'LOGINFENNEC_FILE', __DIR__ . '/loginfennec.php' );

// État passé en argument : l'option est posée AVANT le chargement du plugin,
// car lnf_settings() met ses réglages en cache statique.
$mode    = isset( $argv[1] ) ? $argv[1] : '';
$enabled = 'enabled' === $mode;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
if ( $enabled || 'sms' === $mode ) {
	$base = array(
		'admin_enable' => '1',
		'admin_bg'     => '#ABCDEF',
	);
	if ( 'sms' === $mode ) {
		$base = array_merge(
			$base,
			array(
				'sms_enabled'   => '1',
				'sms_provider'  => 'webhook',
				'sms_webhook_url' => 'https://sms.exemple.dz/api/send',
				'sms_from'      => 'LoginFennec',
				'sms_country'   => '213',
				'sms_otp_length' => 6,
				'sms_otp_ttl'   => 5,
				'sms_max_attempts' => 3,
			)
		);
	}
	$GLOBALS['__options']['loginfennec_settings'] = $base;
}
if ( 'geo' === $mode ) {
	$GLOBALS['__options']['loginfennec_settings'] = array(
		'geo_enable'      => '1',
		'geo_countries'   => 'DZ, fr; tn|us',
		'seo_noindex'     => '0',
		'seo_login_title' => 'Espace {site}',
		'sec_whitelist'   => '10.0.0.1',
	);
}
if ( 'safe' === $mode ) {
	$GLOBALS['__options']['loginfennec_settings'] = array(
		'safe_mode'   => '1',
		'sms_enabled' => '1',
		'geo_enable'  => '1',
		'geo_countries' => 'DZ',
		'admin_enable' => '1',
		'recaptcha_enabled' => '1',
		'recaptcha_site_key' => 'k',
		'recaptcha_secret_key' => 's',
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
$fail = 0;

// --- Déclenche les hooks de chargement comme WordPress le fait ---
// (c'est ici qu'une classe manquante — ex. updater supprimé — devient fatale).
do_action( 'plugins_loaded' );
do_action( 'init' );
do_action( 'wp_loaded' );
// Après une activation réelle, le plugin redirige vers l'installateur (exit).
// On simule un admin ayant déjà passé l'installateur pour continuer les tests.
delete_option( 'lnf_pending_installer' );
do_action( 'admin_init' );

echo "== Chargement & panneaux ==\n";
check( 'hooks de chargement sans erreur fatale', empty( $GLOBALS['__hook_errors'] ) );
if ( ! empty( $GLOBALS['__hook_errors'] ) ) {
	foreach ( $GLOBALS['__hook_errors'] as $err ) {
		echo '    → ' . $err . "\n";
	}
}
if ( 'sms' === $mode || $enabled ) {
	// $before inutilisé
	foreach ( array( 'panel_sms', 'panel_admin', 'panel_security', 'panel_extras', 'panel_styles', 'panel_form', 'render_about', 'render_pro' ) as $panel ) {
		$GLOBALS['__hook_errors'] = array();
		if ( ! class_exists( 'Lnf_Admin' ) || ! method_exists( 'Lnf_Admin', $panel ) ) {
			check( 'panneau ' . $panel . ' introuvable', false );
			continue;
		}
		ob_start();
		try {
			$ref = new ReflectionMethod( 'Lnf_Admin', $panel );
			$ref->setAccessible( true );
			$ref->invoke( null, lnf_settings() );
			$html = ob_get_clean();
			check( 'panneau ' . $panel . ' rendu (' . strlen( $html ) . ' o)', strlen( $html ) > 50 && empty( $GLOBALS['__hook_errors'] ) );
		} catch ( Throwable $e ) {
			ob_end_clean();
			check( 'panneau ' . $panel . ' : ' . $e->getMessage(), false );
		}
	}
}

// --- Tests ---
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
if ( 'sms' !== $mode && 'safe' !== $mode ) {
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
		ob_start();
		lnf_admin_colors_css_output();
		$out_html = ob_get_clean();
		check( 'CSS admin imprimé en fin de head (style tag)', false !== strpos( $out_html, 'lnf-admin-colors' ) );
	} else {
		check( 'CSS vide si désactivé (defaults)', '' === lnf_admin_colors_css() );
	}
}

echo "== SMS ==\n";
if ( 'sms' !== $mode ) {
	check( 'module SMS chargé', function_exists( 'lnf_sms_active' ) );
	check( 'SMS inactif par défaut', ! lnf_sms_active() );
	check( 'gateway non prête par défaut', ! lnf_sms_gateway_ready() );
}
if ( 'sms' === $mode ) {
	$out = lnf_sanitize_settings( array( 'sms_provider' => 'evil', 'sms_enabled' => '1', 'sms_otp_length' => '99', 'sms_otp_ttl' => '0' ) );
	check( 'sanitize : provider inconnu → twilio', 'twilio' === $out['sms_provider'] );
	check( 'sanitize : sms_enabled = true', true === $out['sms_enabled'] );
	check( 'sanitize : longueur clampée à 8', 8 === $out['sms_otp_length'] );
	check( 'sanitize : validité clampée à 1', 1 === $out['sms_otp_ttl'] );

	check( 'normalize local → international', '213555123456' === lnf_sms_normalize_phone( '0555 12-34 56', '213' ) );
	check( 'normalize déjà international', '213555123456' === lnf_sms_normalize_phone( '+213 555 12 34 56', '213' ) );
	check( 'normalize 00 international', '213555123456' === lnf_sms_normalize_phone( '00213555123456', '213' ) );
	check( 'normalize numéro étranger inchangé', '33612345678' === lnf_sms_normalize_phone( '33612345678', '213' ) );
	check( 'normalize vide → vide', '' === lnf_sms_normalize_phone( '   ', '213' ) );

	check( 'gateway prête (webhook avec URL)', lnf_sms_gateway_ready() );

	$GLOBALS['__users'][7]    = array( 'ID' => 7, 'user_login' => 'oussama' );
	$GLOBALS['__usermeta'][7] = array( 'lnf_phone' => '213555123456' );
	check( 'utilisateur trouvé par numéro', false !== lnf_sms_find_user( '213555123456' ) );
	check( 'numéro inconnu → false', false === lnf_sms_find_user( '213999999999' ) );

	$code = lnf_sms_otp_create( '213555123456' );
	check( 'code OTP à 6 chiffres', (bool) preg_match( '/^\d{6}$/', $code ) );
	$wrong = lnf_sms_otp_verify( '213555123456', '000000' );
	check( 'mauvais code → erreur', is_wp_error( $wrong ) );
	check( 'tentatives restantes indiquées', is_wp_error( $wrong ) && false !== strpos( $wrong->get_error_message(), '2' ) );
	$ok = lnf_sms_otp_verify( '213555123456', $code );
	check( 'bon code → utilisateur', is_object( $ok ) && ! is_wp_error( $ok ) && 7 === $ok->ID );
	check( 'code à usage unique (consommé)', is_wp_error( lnf_sms_otp_verify( '213555123456', $code ) ) );

	// Verrouillage après trop de tentatives (max = 3).
	lnf_sms_otp_create( '213555123457' );
	lnf_sms_otp_verify( '213555123457', '111111' );
	lnf_sms_otp_verify( '213555123457', '111111' );
	lnf_sms_otp_verify( '213555123457', '111111' );
	$locked = lnf_sms_otp_verify( '213555123457', '111111' );
	check( 'verrouillage après 3 échecs', is_wp_error( $locked ) && false !== strpos( $locked->get_error_message(), 'Trop de tentatives' ) );

	lnf_sms_rate_hit( '213555123458' );
	check( 'rate limit par numéro', is_wp_error( lnf_sms_rate_ok( '213555123458' ) ) );
	check( 'autre numéro non limité', true === lnf_sms_rate_ok( '213999000111' ) );

	$sent = lnf_sms_dispatch( '213555123456', 'Message de test' );
	check( 'envoi via webhook → true', true === $sent );

	$user = lnf_sms_find_user( '213555123456' );
	$url  = lnf_sms_login_user( $user, '' );
	check( 'connexion → URL admin par défaut', 'http://test.local/wp-admin/' === $url );
	check( 'cookie d’authentification posé', isset( $GLOBALS['__auth_cookie'] ) );
	$log = Lnf_Login_Security::get_log();
	check( 'connexion notée au journal (sms)', ! empty( $log ) && 'sms' === $log[0]['a'] && 'oussama' === $log[0]['u'] );

	$redirect = lnf_sms_login_user( $user, 'http://evil.example.com/back' );
	check( 'redirection externe rejetée', 'http://test.local/wp-admin/' === $redirect );
	$redirect2 = lnf_sms_login_user( $user, 'http://test.local/bienvenue' );
	check( 'redirection locale acceptée', 'http://test.local/bienvenue' === $redirect2 );
}

echo "== SEO / GEO / Anti-fuite ==\n";
check( 'index.php dans les dossiers (anti-listing)', file_exists( 'includes/index.php' ) && file_exists( 'assets/js/index.php' ) && file_exists( 'assets/css/index.php' ) );
check( 'assets SMS versionnés présents', file_exists( 'assets/js/sms-login.js' ) && file_exists( 'assets/css/sms-login.css' ) );
check( 'clés secrètes identifiées', 4 === count( lnf_secret_keys() ) );
$sec = lnf_sanitize_settings( array( 'sms_twilio_token' => 'TOPSECRET', 'sms_vonage_secret' => 'VSEC' ) );
check( 'sanitize conserve les secrets (chiffrés, déchiffrables)', 'TOPSECRET' === lnf_decrypt_secret( $sec['sms_twilio_token'] ) && 'VSEC' === lnf_decrypt_secret( $sec['sms_vonage_secret'] ) );

if ( 'geo' === $mode ) {
	check( 'pays autorisés normalisés', array( 'DZ', 'FR', 'TN', 'US' ) === lnf_geo_allowed_countries() );
	$GLOBALS['__mock_country'] = 'DZ';
	add_filter( 'lnf_geo_country', function () { return $GLOBALS['__mock_country']; } );
	check( 'pays autorisé → non bloqué', ! lnf_geo_blocked() );
	$GLOBALS['__mock_country'] = 'JP';
	check( 'pays hors liste → bloqué', lnf_geo_blocked() );
	$GLOBALS['__mock_country'] = '';
	check( 'détection impossible → fail-open', ! lnf_geo_blocked() );
	$_SERVER['REMOTE_ADDR'] = '10.0.0.1';
	$GLOBALS['__mock_country'] = 'JP';
	check( 'IP en liste blanche → jamais bloquée', ! lnf_geo_blocked() );
	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	ob_start();
	lnf_seo_noindex();
	check( 'noindex désactivé → aucune balise', '' === ob_get_clean() );
	check( 'titre login personnalisé', 'Espace Test' === lnf_seo_login_title( 'Log In' ) );
} else {
	ob_start();
	lnf_seo_noindex();
	check( 'noindex actif par défaut', false !== strpos( ob_get_clean(), 'noindex' ) );
	check( 'titre login inchangé si vide', 'Log In' === lnf_seo_login_title( 'Log In' ) );
}

// Aperçu temps réel : générateur CSS paramétrable (indépendant du cache réglages).
$preview = lnf_admin_colors_css( array(
	'admin_enable' => true,
	'admin_bg' => '#123456', 'admin_text' => '#fedcba',
	'admin_hover_bg' => '#111111', 'admin_hover_text' => '#222222',
	'admin_active_bg' => '#333333', 'admin_active_text' => '#ffffff',
	'admin_accent' => '#e88018', 'adminbar_bg' => '#444444',
	'adminbar_text' => '#555555', 'adminbar_hover' => '#666666',
) );
check( 'aperçu admin : CSS construit des couleurs passées', is_string( $preview ) && false !== strpos( $preview, '#123456' ) );
check( 'aperçu admin : accent propagé (thème + dérivés)', false !== strpos( $preview, '#e88018' ) && false !== strpos( $preview, '--wp-admin-theme-color' ) );
check( 'aperçu admin : désactivé → CSS vide', '' === lnf_admin_colors_css( array( 'admin_enable' => false ) ) );
check( 'aperçu admin : aucune injection HTML', false === stripos( (string) $preview, '<script' ) );

// Sauvegarde partielle (save) : les champs absents du POST conservent leur
// valeur enregistrée au lieu d'être réinitialisés aux défauts.
$base    = array_merge( lnf_get_defaults(), array( 'admin_enable' => true, 'admin_bg' => '#112233', 'admin_text' => '#fedcba' ) );
$partial = lnf_sanitize_settings( array( 'admin_bg' => '#445566', 'admin_enable' => '0' ), $base );
check( 'save partiel : champ fourni enregistré', '#445566' === $partial['admin_bg'] );
check( 'save partiel : champ absent conservé (pas de reset)', '#fedcba' === $partial['admin_text'] );
check( 'save partiel : sentinelle 0 → case décochée', false === $partial['admin_enable'] );
$partial2 = lnf_sanitize_settings( array( 'admin_bg' => '#445566', 'admin_enable' => '1' ), $base );
check( 'save partiel : sentinelle 1 → case cochée', true === $partial2['admin_enable'] );
$full = lnf_sanitize_settings( array(
	'admin_enable' => '1', 'admin_bg' => '#112233', 'admin_text' => '#fedcba',
	'admin_hover_bg' => '#0a427f', 'admin_hover_text' => '#f2a444',
	'admin_active_bg' => '#e88018', 'admin_active_text' => '#ffffff',
	'admin_accent' => '#e88018', 'adminbar_bg' => '#00234a',
	'adminbar_text' => '#f9f0d8', 'adminbar_hover' => '#f2a444',
), null );
check( 'save complet (base null) : toutes les couleurs passent', '#112233' === $full['admin_bg'] && true === $full['admin_enable'] && '#f2a444' === $full['adminbar_hover'] );

// Secrets : chiffrement au repos + champ vide qui conserve + lecture déchiffrée.
$enc = lnf_encrypt_secret( 'TOPSECRET-TOKEN' );
check( 'secret chiffré (préfixe + pas de clair)', 0 === strpos( $enc, 'lnfenc1:' ) && false === strpos( $enc, 'TOPSECRET' ) );
check( 'déchiffrement restitue le secret', 'TOPSECRET-TOKEN' === lnf_decrypt_secret( $enc ) );
check( 'valeur héritée en clair passée telle quelle', 'legacy-key' === lnf_decrypt_secret( 'legacy-key' ) );
check( 'chiffré falsifié → chaîne vide', '' === lnf_decrypt_secret( 'lnfenc1:' . substr( $enc, 8, -4 ) . 'AAAA' ) );

$base_sec = array_merge( lnf_get_defaults(), array( 'sms_twilio_token' => 'EXISTING-KEY' ) );
$kept     = lnf_sanitize_settings( array( 'sms_twilio_token' => '' ), $base_sec );
check( 'champ secret vide → clé conservée', 'EXISTING-KEY' === lnf_decrypt_secret( $kept['sms_twilio_token'] ) );
$replaced = lnf_sanitize_settings( array( 'sms_twilio_token' => 'NEW-KEY' ), $base_sec );
check( 'nouvelle clé → chiffrée à l\'écriture', 0 === strpos( (string) $replaced['sms_twilio_token'], 'lnfenc1:' ) && 'NEW-KEY' === lnf_decrypt_secret( $replaced['sms_twilio_token'] ) );
$from_empty = lnf_sanitize_settings( array( 'sms_vonage_secret' => 'FRESH' ), lnf_get_defaults() );
check( 'secret vide + nouvelle clé → chiffrée', 0 === strpos( (string) $from_empty['sms_vonage_secret'], 'lnfenc1:' ) );

// Contraste : jamais d'écriture sombre sur sombre, ni claire sur clair.
check( 'contraste : fond sombre → texte blanc', '#ffffff' === lnf_contrast_text( '#00305e' ) );
check( 'contraste : fond noir → texte blanc', '#ffffff' === lnf_contrast_text( '#000000' ) );
check( 'contraste : fond crème → texte sombre', '#1d2327' === lnf_contrast_text( '#f9f0d8' ) );
check( 'contraste : fond blanc → texte sombre', '#1d2327' === lnf_contrast_text( '#ffffff' ) );
check( 'contraste : orange fennec → texte sombre (lisible)', '#1d2327' === lnf_contrast_text( '#e88018' ) );
check( 'luminance bornée', lnf_luminance( '#000000' ) < 0.01 && lnf_luminance( '#ffffff' ) > 0.99 );

// Mode sans échec : tout est neutralisé (uniquement en mode 'safe').
if ( 'safe' === $mode ) {
	check( 'safe mode : détecté', lnf_safe_mode() );
	check( 'safe mode : SMS désactivé', ! lnf_sms_active() );
	check( 'safe mode : GEO désactivé', ! lnf_geo_blocked() );
	check( 'safe mode : reCAPTCHA désactivé', ! lnf_recaptcha_enabled() );
	ob_start();
	lnf_admin_colors_css_output();
	check( 'safe mode : couleurs admin non imprimées', '' === ob_get_clean() );
}

// Layout : la valeur « two-column » doit survivre à la sanitization
// (bug historique : clé 'layout' sans liste autorisée → toujours réinitialisée).
$lay = lnf_sanitize_settings( array( 'layout' => 'two-column' ), null );
check( 'layout two-column sauvegardable', 'two-column' === $lay['layout'] );
$lay_bad = lnf_sanitize_settings( array( 'layout' => 'pirate' ), null );
check( 'layout invalide → défaut', 'single' === $lay_bad['layout'] );
$fg = lnf_sanitize_settings( array( 'font_google' => 'Playfair Display' ), null );
check( 'police Google avec espace préservée', 'Playfair Display' === $fg['font_google'] );
$si = lnf_sanitize_settings( array( 'side_image' => 'https://test.local/img.jpg' ), null );
check( 'side_image assaini en URL', 'https://test.local/img.jpg' === $si['side_image'] );

echo "\n" . ( $fail ? "ÉCHEC : $fail test(s)" : 'TOUS LES TESTS PASSENT' ) . "\n";
exit( $fail ? 1 : 0 );
