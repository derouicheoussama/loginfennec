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
 * Réglages : valeurs par défaut, accès et assainissement.
 *
 * @package LoginFennecPro
 */

defined( 'ABSPATH' ) || exit;

const LOGINFENNEC_OPTION = 'loginfennec_settings';

// Disponible nativement depuis WP 5.4 ; fallback pour la compatibilité 5.2+.
if ( ! function_exists( 'sanitize_hex_color' ) ) {
	/**
	 * Assainit une couleur hexadécimale.
	 *
	 * @param string $color Couleur.
	 * @return string|null
	 */
	function sanitize_hex_color( $color ) {
		if ( '' === $color ) {
			return '';
		}
		if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
			return $color;
		}
		return null;
	}
}

/**
 * Valeurs par défaut (correspondent au preset « Effet verre »).
 *
 * @return array
 */
function lnf_get_defaults() {
	return array(
		// Style actif (informatif, appliqué par le dashboard).
		'preset' => 'glass',

		// Thème d'interface du formulaire.
		'form_theme' => 'glass', // glass | classic | outline | pill | elevated | accent | minimal.
		'layout'     => 'single', // single | two-column.
		'side_image' => '',

		// Arrière-plan.
		'bg_type'            => 'gradient', // color | gradient | image.
		'bg_color1'          => '#667eea',
		'bg_color2'          => '#764ba2',
		'bg_gradient_angle'  => 135,
		'bg_image'           => '',
		'bg_size'            => 'cover', // cover | contain | repeat.
		'bg_position'        => 'center', // center | top | bottom | left | right | top-left | …
		'bg_blur'            => 0,
		'bg_brightness'      => 100,
		'bg_saturation'      => 100,
		'bg_overlay_color'   => '#000000',
		'bg_overlay_opacity' => 10,

		// Logo.
		'logo_hide'  => false,
		'logo_url'   => '',
		'logo_text'  => '',
		'logo_width' => 120,
		'logo_height' => 84,
		'logo_link'  => '',

		// Formulaire.
		'form_bg'       => '#ffffff',
		'form_opacity'  => 22,
		'form_blur'     => 18,
		'form_radius'   => 18,
		'form_width'    => 340,
		'form_padding'  => 36,
		'form_shadow'   => true,
		'input_height'  => 0,
		'text_color'    => '#ffffff',
		'label_color'   => '#ffffff',
		'input_bg'      => '#ffffff',
		'input_color'   => '#2c3338',
		'input_border'  => '#ffffff',
		'button_bg'     => '#e88018',
		'button_hover'  => '#6d28d9',
		'button_radius' => 10,
		'link_color'    => '#ffffff',

		// Liens de la page de connexion.
		'hide_lost_password' => false,
		'hide_back_to'       => false,
		'back_to_text'       => '',
		'back_to_url'        => '',
		'hide_register'      => false,
		'register_text'      => '',

		// Réseaux sociaux.
		'social_enable'        => false,
		'social_style'         => 'circle', // circle | rounded | square.
		'social_variant'       => 'fill',   // fill | outline | plain | soft.
		'social_size'          => 40,
		'social_icon_color'    => '#ffffff',
		'social_icon_bg'       => '#ffffff',
		'social_icon_bg_opacity' => 18,
		'social_brand'         => true,
		'social_facebook'      => '',
		'social_twitter'       => '',
		'social_instagram'     => '',
		'social_linkedin'      => '',
		'social_youtube'       => '',
		'social_email'         => '',

		// Copyright.
		'copyright_enable' => false,
		'copyright_text'   => '© {year} {sitename} — Tous droits réservés.',

		// Extras.
		'welcome_enable'   => false,
		'welcome_title'    => 'Bienvenue ✨',
		'welcome_subtitle' => 'Connectez-vous pour accéder à votre espace.',
		'font_family'      => 'system', // system | serif | rounded | mono.
		'font_google'      => 'Poppins',
		'font_google_weight' => '400;500;700',
		'font_size'        => 13,
		'anim'             => 'none', // none | fade | slide | zoom.
		'custom_css'       => '',
		'custom_js'        => '',
		'login_redirect'   => '',
		'white_label'      => false,
		'field_placeholder_user' => '',
		'field_placeholder_pass' => '',
		'field_label_user' => '',
		'field_label_pass' => '',

		// Admin Colors.
		'admin_enable'       => false,
		'admin_bg'           => '#1d2327',
		'admin_text'         => '#c3c4c7',
		'admin_hover_bg'     => '#2c3338',
		'admin_hover_text'   => '#72aee6',
		'admin_active_bg'    => '#2271b1',
		'admin_active_text'  => '#ffffff',
		'admin_accent'       => '#0f5aa8',
		'adminbar_bg'        => '#1d2327',
		'adminbar_text'      => '#c3c4c7',
		'adminbar_hover'     => '#72aee6',

		// Connexion par SMS.
		'sms_enabled'      => false,
		'sms_provider'     => 'twilio',
		'sms_twilio_sid'   => '',
		'sms_twilio_token' => '',
		'sms_vonage_key'   => '',
		'sms_vonage_secret' => '',
		'sms_webhook_url'  => '',
		'sms_webhook_token' => '',
		'sms_from'         => '',
		'sms_country'      => '213',
		'sms_template'     => 'Votre code de connexion : {code} (valable {minutes} min).',
		'sms_otp_length'   => 6,
		'sms_otp_ttl'      => 5,
		'sms_max_attempts' => 3,

		// SEO de la page de connexion.
		'seo_noindex'      => true,
		'seo_login_title'  => '',

		// GEO : restriction par pays.
		'geo_enable'       => false,
		'geo_countries'    => '',

		// Avancé & stabilité.
		'auto_update'      => false,
		'safe_mode'        => false,
		// Sécurité.
		'sec_enable'               => true,
		'sec_max_attempts'         => 5,
		'sec_lockout_minutes'      => 15,
		'sec_lock_message'         => 'Trop de tentatives de connexion. Réessayez dans %d minutes.',
		'sec_generic_error'        => true,
		'sec_hide_language_switcher' => false,
		'sec_disable_xmlrpc'       => false,
		'sec_honeypot'             => true,
		'sec_disable_authors'      => true,
		'sec_disable_app_passwords' => false,
		'recaptcha_site_key'       => '',
		'recaptcha_secret_key'     => '',
		'recaptcha_enabled'        => false,
		'sec_alert_email'          => '',
		'sec_whitelist'            => '',
	);
}

/**
 * Retourne les réglages fusionnés avec les valeurs par défaut.
 *
 * @return array
 */
function lnf_settings() {
	static $cache = null;
	if ( null === $cache ) {
		$saved = get_option( LOGINFENNEC_OPTION, array() );
		$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), lnf_get_defaults() );
		// Les secrets sont chiffrés au repos : le reste du plugin consomme
		// toujours des valeurs en clair.
		foreach ( lnf_secret_keys() as $secret_key ) {
			if ( isset( $cache[ $secret_key ] ) ) {
				$cache[ $secret_key ] = lnf_decrypt_secret( $cache[ $secret_key ] );
			}
		}
	}
	return $cache;
}

/**
 * Retourne une valeur précise des réglages.
 *
 * @param string $key Clé.
 * @return mixed
 */
function lnf_get_option( $key ) {
	$s = lnf_settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : null;
}

/**
 * Spécification des champs : type + bornes, utilisée pour l'assainissement.
 *
 * @return array type => array( champ, champ... ) — bornes éventuelles [min, max].
 */
function lnf_field_spec() {
	return array(
		'key'    => array( 'preset', 'form_theme', 'bg_type', 'bg_size', 'bg_position', 'social_style', 'social_variant', 'font_family', 'anim', 'layout', 'sms_provider' ),
		'bool'   => array(
			'logo_hide', 'form_shadow', 'hide_lost_password', 'hide_back_to',
			'hide_register', 'social_enable', 'social_brand', 'copyright_enable',
			'welcome_enable', 'sec_enable', 'sec_generic_error', 'sec_hide_language_switcher',
			'sec_disable_xmlrpc', 'sec_honeypot', 'sec_disable_authors',
			'recaptcha_enabled', 'white_label', 'admin_enable',
			'sec_disable_app_passwords', 'sms_enabled',
			'seo_noindex', 'geo_enable',
			'auto_update', 'safe_mode',
		),
		'url'    => array(
			'bg_image', 'logo_url', 'logo_link', 'back_to_url',
			'social_facebook', 'social_twitter', 'social_instagram',
			'social_linkedin', 'social_youtube', 'login_redirect', 'side_image',
			'sms_webhook_url',
		),
		'text'   => array( 'back_to_text', 'register_text', 'social_email', 'copyright_text', 'sec_lock_message', 'welcome_title', 'welcome_subtitle', 'custom_css', 'sec_whitelist', 'logo_text', 'custom_js', 'field_placeholder_user', 'field_placeholder_pass', 'field_label_user', 'field_label_pass', 'sec_alert_email', 'font_google', 'font_google_weight', 'recaptcha_site_key', 'recaptcha_secret_key', 'sms_twilio_sid', 'sms_twilio_token', 'sms_vonage_key', 'sms_vonage_secret', 'sms_webhook_token', 'sms_from', 'sms_country', 'sms_template', 'seo_login_title', 'geo_countries' ),
		'color'  => array(
			'bg_color1', 'bg_color2', 'bg_overlay_color', 'form_bg', 'text_color',
			'label_color', 'input_bg', 'input_color', 'input_border',
			'button_bg', 'button_hover', 'link_color',
			'social_icon_color', 'social_icon_bg',
			'admin_bg', 'admin_text', 'admin_hover_bg', 'admin_hover_text',
			'admin_active_bg', 'admin_active_text', 'admin_accent',
			'adminbar_bg', 'adminbar_text', 'adminbar_hover',
		),
		'int'    => array(
			'bg_gradient_angle'    => array( 0, 360 ),
			'bg_blur'              => array( 0, 50 ),
			'bg_brightness'        => array( 30, 150 ),
			'bg_saturation'        => array( 0, 200 ),
			'bg_overlay_opacity'   => array( 0, 100 ),
			'font_size'            => array( 12, 18 ),
			'logo_width'           => array( 40, 500 ),
			'logo_height'          => array( 24, 400 ),
			'form_opacity'         => array( 0, 100 ),
			'form_blur'            => array( 0, 40 ),
			'form_radius'          => array( 0, 60 ),
			'form_width'           => array( 260, 560 ),
			'form_padding'         => array( 12, 80 ),
			'input_height'         => array( 0, 60 ),
			'button_radius'        => array( 0, 40 ),
			'social_size'          => array( 28, 72 ),
			'social_icon_bg_opacity' => array( 0, 100 ),
			'sec_max_attempts'     => array( 1, 20 ),
			'sec_lockout_minutes'  => array( 1, 1440 ),
			'sms_otp_length'       => array( 4, 8 ),
			'sms_otp_ttl'          => array( 1, 15 ),
			'sms_max_attempts'     => array( 1, 10 ),
		),
	);
}

/**
 * Assainit un tableau d'options entrant.
 *
 * @param array     $input    Données brutes (souvent $_POST['lnf']).
 * @param array|null $base    Base de fusion ; null = enregistrement complet.
 * @return array Réglages assainis.
 */
function lnf_sanitize_settings( $input, $base = null ) {
	$defaults = lnf_get_defaults();
	$out      = ( null === $base ) ? $defaults : $base;
	$input    = is_array( $input ) ? $input : array();
	$spec     = lnf_field_spec();

	foreach ( $spec['key'] as $key ) {
		if ( null !== $base && ! array_key_exists( $key, $input ) ) {
			continue;
		}
		$allowed = array(
			'preset'       => array( 'glass', 'minimal', 'dark', 'sunset', 'ocean', 'forest', 'neon', 'sakura', 'mono', 'royal', 'coffee', 'mint', 'berry', 'gold', 'midnight', 'coral', 'custom' ),
			'form_theme'   => array( 'glass', 'classic', 'outline', 'pill', 'elevated', 'accent', 'minimal', 'sharp', 'soft', 'glassdark', 'duo' ),
			'bg_type'      => array( 'color', 'gradient', 'image' ),
			'bg_size'      => array( 'cover', 'contain', 'repeat' ),
			'bg_position'  => array( 'center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right' ),
			'social_style' => array( 'circle', 'rounded', 'square' ),
			'social_variant' => array( 'fill', 'outline', 'plain', 'soft' ),
			'font_family'  => array( 'system', 'serif', 'rounded', 'mono' ),
			'anim'         => array( 'none', 'fade', 'slide', 'zoom' ),
			'layout'       => array( 'single', 'two-column' ),
			'sms_provider' => array( 'twilio', 'vonage', 'webhook' ),
		);
		$field_allowed = isset( $allowed[ $key ] ) ? $allowed[ $key ] : array();
		$value         = isset( $input[ $key ] ) ? sanitize_key( $input[ $key ] ) : '';
		$out[ $key ]   = in_array( $value, $field_allowed, true ) ? $value : $defaults[ $key ];
	}

	foreach ( $spec['bool'] as $key ) {
		if ( null !== $base && ! array_key_exists( $key, $input ) ) {
			continue;
		}
		$out[ $key ] = ( ! empty( $input[ $key ] ) && 'false' !== $input[ $key ] );
	}

	foreach ( $spec['url'] as $key ) {
		if ( null !== $base && ! array_key_exists( $key, $input ) ) {
			continue;
		}
		$out[ $key ] = esc_url_raw( trim( (string) ( isset( $input[ $key ] ) ? $input[ $key ] : '' ) ) );
	}

	foreach ( $spec['text'] as $key ) {
		if ( null !== $base && ! array_key_exists( $key, $input ) ) {
			continue;
		}
		$raw = (string) ( isset( $input[ $key ] ) ? $input[ $key ] : '' );
		// Champ secret laissé vide → on conserve le secret enregistré
		// (les champs secrets ne sont jamais affichés, donc jamais renvoyés).
		if ( in_array( $key, lnf_secret_keys(), true ) && '' === trim( $raw ) && null !== $base ) {
			continue;
		}
		$out[ $key ] = sanitize_textarea_field( $raw );
	}

	foreach ( $spec['color'] as $key ) {
		if ( null !== $base && ! array_key_exists( $key, $input ) ) {
			continue;
		}
		$hex = sanitize_hex_color( (string) ( isset( $input[ $key ] ) ? $input[ $key ] : '' ) );
		$out[ $key ] = $hex ? $hex : $defaults[ $key ];
	}

	foreach ( $spec['int'] as $key => $range ) {
		if ( null !== $base && ! array_key_exists( $key, $input ) ) {
			continue;
		}
		$value = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : 0;
		$out[ $key ] = max( $range[0], min( $range[1], $value ) );
	}

	// Chiffrement au repos : le tableau retourné est destiné à update_option —
	// les secrets y sont systématiquement stockés chiffrés.
	foreach ( lnf_secret_keys() as $secret_key ) {
		if ( isset( $out[ $secret_key ] ) && '' !== $out[ $secret_key ] ) {
			$out[ $secret_key ] = lnf_encrypt_secret( (string) $out[ $secret_key ] );
		}
	}

	return $out;
}

/**
 * Clés de réglages contenant des secrets (jamais exportées, jamais affichées,
 * chiffrées au repos).
 *
 * @return array
 */
function lnf_secret_keys() {
	return array(
		'sms_twilio_token',
		'sms_vonage_secret',
		'sms_webhook_token',
		'recaptcha_secret_key',
	);
}

/**
 * Clé de chiffrement des secrets : dérivée du sel « auth » de WordPress
 * (wp-config.php) + une graine aléatoire propre au site. Une simple copie
 * de la base de données ne suffit donc pas à déchiffrer les clés API.
 *
 * @return string Clé hexadécimale 256 bits.
 */
function lnf_secret_key() {
	static $key = null;
	if ( null !== $key ) {
		return $key;
	}
	$seed = get_option( 'lnf_secret_seed', '' );
	if ( ! is_string( $seed ) || '' === $seed ) {
		$seed = bin2hex( random_bytes( 16 ) );
		update_option( 'lnf_secret_seed', $seed, false );
	}
	$key = hash( 'sha256', wp_salt( 'auth' ) . '|' . $seed );
	return $key;
}

/**
 * Chiffre un secret (AES-256-GCM). Préfixe « lnfenc1: ».
 * Sans openssl (rare), retourne la valeur telle quelle : dégradation douce.
 *
 * @param string $plain Secret en clair.
 * @return string
 */
function lnf_encrypt_secret( $plain ) {
	$plain = (string) $plain;
	if ( '' === $plain || ! function_exists( 'openssl_encrypt' ) ) {
		return $plain;
	}
	$iv     = random_bytes( 12 );
	$tag    = '';
	$cipher = openssl_encrypt( $plain, 'aes-256-gcm', lnf_secret_key(), OPENSSL_RAW_DATA, $iv, $tag, '', 16 );
	if ( false === $cipher ) {
		return $plain;
	}
	return 'lnfenc1:' . base64_encode( $iv . $tag . $cipher );
}

/**
 * Déchiffre un secret stocké. Une valeur sans préfixe (héritée d'une version
 * antérieure, en clair) est retournée telle quelle.
 *
 * @param string $stored Valeur stockée.
 * @return string
 */
function lnf_decrypt_secret( $stored ) {
	$stored = (string) $stored;
	if ( '' === $stored || 0 !== strpos( $stored, 'lnfenc1:' ) ) {
		return $stored;
	}
	if ( ! function_exists( 'openssl_decrypt' ) ) {
		return '';
	}
	$raw = base64_decode( substr( $stored, 8 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- décodage du chiffré maison.
	if ( false === $raw || strlen( $raw ) < 29 ) {
		return '';
	}
	$plain = openssl_decrypt(
		substr( $raw, 28 ),
		'aes-256-gcm',
		lnf_secret_key(),
		OPENSSL_RAW_DATA,
		substr( $raw, 0, 12 ),
		substr( $raw, 12, 16 )
	);
	return false === $plain ? '' : $plain;
}

/**
 * Luminance relative perçue (0-1) d'une couleur hexadécimale
 * (formule WCAG : 0.2126 R + 0.7152 G + 0.0722 B, canaux linéarisés).
 *
 * @param string $hex Couleur hex (#rgb ou #rrggbb).
 * @return float
 */
function lnf_luminance( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return 0;
	}
	$linearize = static function ( $channel ) {
		$channel /= 255;
		return $channel <= 0.03928 ? $channel / 12.92 : pow( ( $channel + 0.055 ) / 1.055, 2.4 );
	};
	return 0.2126 * $linearize( hexdec( substr( $hex, 0, 2 ) ) )
		+ 0.7152 * $linearize( hexdec( substr( $hex, 2, 2 ) ) )
		+ 0.0722 * $linearize( hexdec( substr( $hex, 4, 2 ) ) );
}

/**
 * Couleur de texte (blanc ou encre sombre) offrant le meilleur contraste
 * de lecture sur un fond donné. Garantie : jamais d'écriture sombre sur
 * un bouton sombre, jamais d'écriture claire sur un bouton clair — quel
 * que soit le style ou la couleur choisis.
 *
 * @param string $background Fond sur lequel le texte sera posé.
 * @param string $dark      Encre sombre à utiliser.
 * @param string $light     Encre claire à utiliser.
 * @return string
 */
function lnf_contrast_text( $background, $dark = '#1d2327', $light = '#ffffff' ) {
	$l       = lnf_luminance( $background );
	$l_light = lnf_luminance( $light );
	$l_dark  = lnf_luminance( $dark );
	$ratio_light = ( max( $l, $l_light ) + 0.05 ) / ( min( $l, $l_light ) + 0.05 );
	$ratio_dark  = ( max( $l, $l_dark ) + 0.05 ) / ( min( $l, $l_dark ) + 0.05 );
	return $ratio_light >= $ratio_dark ? $light : $dark;
}

/**
 * Mode sans échec : désactive d'un coup sécurité, SMS, GEO, reCAPTCHA,
 * personnalisation et couleurs d'admin pour dépanner un site instable.
 *
 * @return bool
 */
function lnf_safe_mode() {
	return ! empty( lnf_get_option( 'safe_mode' ) );
}

/**
 * Convertit une couleur hexadécimale en rgba().
 *
 * @param string $hex     Couleur hex (#rgb ou #rrggbb).
 * @param int    $opacity Opacité en pourcentage (0-100).
 * @return string
 */
function lnf_hex_to_rgba( $hex, $opacity = 100 ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		$hex = '000000';
	}
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );
	$a = max( 0, min( 100, (int) $opacity ) ) / 100;
	return sprintf( 'rgba(%d, %d, %d, %s)', $r, $g, $b, round( $a, 2 ) );
}

/**
 * Remplace les variables du texte de copyright.
 *
 * @param string $text Texte brut.
 * @return string
 */
function lnf_expand_copyright( $text ) {
	$replacements = array(
		'{year}'     => gmdate( 'Y' ),
		'{sitename}' => get_bloginfo( 'name' ),
	);
	return strtr( (string) $text, $replacements );
}
