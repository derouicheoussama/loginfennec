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
 * Réglages : valeurs par défaut, accès et assainissement.
 *
 * @package InfinityCustomizer
 */

defined( 'ABSPATH' ) || exit;

const INFINITY_LOGINSHIELD_OPTION = 'infinity_loginshield_settings';

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
function inls_get_defaults() {
	return array(
		// Style actif (informatif, appliqué par le dashboard).
		'preset' => 'glass',

		// Thème d'interface du formulaire.
		'form_theme' => 'glass', // glass | classic | outline | pill | elevated | accent | minimal.

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
		'button_bg'     => '#7c3aed',
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
		'font_size'        => 13,
		'anim'             => 'none', // none | fade | slide | zoom.
		'custom_css'       => '',
		'custom_js'        => '',
		'login_redirect'   => '',
		'field_placeholder_user' => '',
		'field_placeholder_pass' => '',
		'field_label_user' => '',
		'field_label_pass' => '',

		// Sécurité.
		'sec_enable'               => true,
		'sec_max_attempts'         => 5,
		'sec_lockout_minutes'      => 15,
		'sec_lock_message'         => 'Trop de tentatives de connexion. Réessayez dans %d minutes.',
		'sec_generic_error'        => false,
		'sec_hide_language_switcher' => false,
		'sec_disable_xmlrpc'       => false,
		'sec_honeypot'             => true,
		'sec_disable_authors'      => true,
		'sec_whitelist'            => '',
	);
}

/**
 * Retourne les réglages fusionnés avec les valeurs par défaut.
 *
 * @return array
 */
function inls_settings() {
	static $cache = null;
	if ( null === $cache ) {
		$saved = get_option( INFINITY_LOGINSHIELD_OPTION, array() );
		$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), inls_get_defaults() );
	}
	return $cache;
}

/**
 * Retourne une valeur précise des réglages.
 *
 * @param string $key Clé.
 * @return mixed
 */
function inls_get_option( $key ) {
	$s = inls_settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : null;
}

/**
 * Spécification des champs : type + bornes, utilisée pour l'assainissement.
 *
 * @return array type => array( champ, champ... ) — bornes éventuelles [min, max].
 */
function inls_field_spec() {
	return array(
		'key'    => array( 'preset', 'form_theme', 'bg_type', 'bg_size', 'bg_position', 'social_style', 'font_family', 'anim' ),
		'bool'   => array(
			'logo_hide', 'form_shadow', 'hide_lost_password', 'hide_back_to',
			'hide_register', 'social_enable', 'social_brand', 'copyright_enable',
			'welcome_enable', 'sec_enable', 'sec_generic_error', 'sec_hide_language_switcher',
			'sec_disable_xmlrpc', 'sec_honeypot', 'sec_disable_authors',
		),
		'url'    => array(
			'bg_image', 'logo_url', 'logo_link', 'back_to_url',
			'social_facebook', 'social_twitter', 'social_instagram',
			'social_linkedin', 'social_youtube', 'login_redirect',
		),
		'text'   => array( 'back_to_text', 'register_text', 'social_email', 'copyright_text', 'sec_lock_message', 'welcome_title', 'welcome_subtitle', 'custom_css', 'sec_whitelist', 'logo_text', 'custom_js', 'field_placeholder_user', 'field_placeholder_pass', 'field_label_user', 'field_label_pass' ),
		'color'  => array(
			'bg_color1', 'bg_color2', 'bg_overlay_color', 'form_bg', 'text_color',
			'label_color', 'input_bg', 'input_color', 'input_border',
			'button_bg', 'button_hover', 'link_color',
			'social_icon_color', 'social_icon_bg',
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
		),
	);
}

/**
 * Assainit un tableau d'options entrant.
 *
 * @param array     $input    Données brutes (souvent $_POST['inls']).
 * @param array|null $base    Base de fusion ; null = enregistrement complet.
 * @return array Réglages assainis.
 */
function inls_sanitize_settings( $input, $base = null ) {
	$defaults = inls_get_defaults();
	$out      = ( null === $base ) ? $defaults : $base;
	$input    = is_array( $input ) ? $input : array();
	$spec     = inls_field_spec();

	foreach ( $spec['key'] as $key ) {
		if ( null !== $base && ! array_key_exists( $key, $input ) ) {
			continue;
		}
		$allowed = array(
			'preset'       => array( 'glass', 'minimal', 'dark', 'sunset', 'ocean', 'forest', 'neon', 'sakura', 'mono', 'royal', 'custom' ),
			'form_theme'   => array( 'glass', 'classic', 'outline', 'pill', 'elevated', 'accent', 'minimal' ),
			'bg_type'      => array( 'color', 'gradient', 'image' ),
			'bg_size'      => array( 'cover', 'contain', 'repeat' ),
			'bg_position'  => array( 'center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right' ),
			'social_style' => array( 'circle', 'rounded', 'square' ),
			'font_family'  => array( 'system', 'serif', 'rounded', 'mono' ),
			'anim'         => array( 'none', 'fade', 'slide', 'zoom' ),
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
		$out[ $key ] = sanitize_textarea_field( (string) ( isset( $input[ $key ] ) ? $input[ $key ] : '' ) );
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

	return $out;
}

/**
 * Convertit une couleur hexadécimale en rgba().
 *
 * @param string $hex     Couleur hex (#rgb ou #rrggbb).
 * @param int    $opacity Opacité en pourcentage (0-100).
 * @return string
 */
function inls_hex_to_rgba( $hex, $opacity = 100 ) {
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
function inls_expand_copyright( $text ) {
	$replacements = array(
		'{year}'     => gmdate( 'Y' ),
		'{sitename}' => get_bloginfo( 'name' ),
	);
	return strtr( (string) $text, $replacements );
}
