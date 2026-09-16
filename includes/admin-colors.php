<?php

/**
 * ∞ INFINITY CODER — création originale de Derouiche Oussama
 *
 * Plugin   : LoginFennec Pro – Personnalisation page login et Security
 * Auteur   : Derouiche Oussama  ·  https://www.derouicheoussama.com
 * Copyright © 2026 Derouiche Oussama. Tous droits réservés.
 * Licence  : GPL v2 ou ultérieure.
 */
/**
 * Personnalisation des couleurs de l'admin WordPress :
 * menu latéral, hover, actif, barre d'admin, liens.
 *
 * @package LoginFennecPro
 *
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

/**
 * Retourne les couleurs admin configurées.
 *
 * @return array
 */
function lnf_admin_colors() {
	$defaults = array(
		'admin_enable'       => false,
		'admin_bg'           => '#1d2327',
		'admin_text'         => '#c3c4c7',
		'admin_hover_bg'     => '#2c3338',
		'admin_hover_text'   => '#72aee6',
		'admin_active_bg'    => '#2271b1',
		'admin_active_text'  => '#ffffff',
		'admin_accent'       => '#6d5df6',
		'adminbar_bg'        => '#1d2327',
		'adminbar_text'      => '#c3c4c7',
		'adminbar_hover'     => '#72aee6',
	);
	$settings = function_exists( 'lnf_settings' ) ? lnf_settings() : array();
	$out      = array();
	foreach ( $defaults as $key => $default ) {
		$value       = isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
		$out[ $key ] = ( 'admin_enable' === $key ) ? (bool) $value : sanitize_hex_color( (string) $value );
		if ( null === $out[ $key ] ) {
			$out[ $key ] = $default;
		}
	}
	return $out;
}

/**
 * Génère le CSS admin custom.
 *
 * @return string
 */
function lnf_admin_colors_css() {
	$c = lnf_admin_colors();
	if ( empty( $c['admin_enable'] ) ) {
		return '';
	}

	$css = "\n/* LoginFennec — Admin Colors */\n";
	$css .= ":root{--wp-admin-theme-color:{$c['admin_accent']};--wp-admin-theme-color-darker-10:" . lnf_color_darker( $c['admin_accent'] ) . ";--wp-admin-theme-color-darker-20:" . lnf_color_darker( $c['admin_accent'], 20 ) . ";}\n";

	// Menu latéral.
	$css .= "#adminmenu{background:{$c['admin_bg']};}\n";
	$css .= "#adminmenu a{color:{$c['admin_text']};}\n";
	$css .= "#adminmenu .wp-submenu{background:" . lnf_color_darker( $c['admin_bg'], 8 ) . ";}\n";
	$css .= "#adminmenu .wp-submenu a{color:{$c['admin_text']};}\n";
	$css .= "#adminmenu li.menu-top:hover,#adminmenu li.opens-sub>a.menu-top,#adminmenu li>a.menu-top:focus{background:{$c['admin_hover_bg']};color:{$c['admin_hover_text']};}\n";
	$css .= "#adminmenu li.current a.menu-top,#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,#adminmenu li.wp-has-current-submenu .wp-submenu .wp-submenu-head{background:{$c['admin_active_bg']};color:{$c['admin_active_text']};}\n";
	$css .= "#adminmenu .wp-has-current-submenu .wp-submenu,#adminmenu .wp-has-current-submenu .wp-submenu.sub-open,#adminmenu .opens-sub .wp-submenu{background:" . lnf_color_darker( $c['admin_active_bg'], 10 ) . ";}\n";

	// Séparateurs du menu.
	$css .= "#adminmenu .wp-menu-separator{border-color:" . lnf_color_lighter( $c['admin_bg'], 15 ) . ";}\n";

	// Icônes du menu.
	$css .= "#adminmenu div.wp-menu-image::before{color:{$c['admin_text']};}\n";
	$css .= "#adminmenu li.menu-top:hover div.wp-menu-image::before,#adminmenu li.current div.wp-menu-image::before{color:{$c['admin_hover_text']};}\n";

	// Barre d'admin supérieure.
	$css .= "#wpadminbar{background:{$c['adminbar_bg']};}\n";
	$css .= "#wpadminbar .ab-item,#wpadminbar a.ab-item,#wpadminbar>#wp-toolbar span.ab-label,#wpadminbar>#wp-toolbar span.noticon{color:{$c['adminbar_text']};}\n";
	$css .= "#wpadminbar .ab-icon::before,#wpadminbar .ab-item::before{color:{$c['adminbar_text']};}\n";
	$css .= "#wpadminbar:hover .ab-item,#wpadminbar a:hover .ab-label{color:{$c['adminbar_hover']};}\n";
	$css .= "#wpadminbar .ab-submenu{background:" . lnf_color_darker( $c['adminbar_bg'], 10 ) . ";}\n";
	$css .= "#wpadminbar .quicklinks .menupop ul li a:hover{background:{$c['adminbar_hover']};}\n";

	// Collapse button.
	$css .= "#collapse-button{color:{$c['admin_text']};}\n";
	$css .= "#collapse-button:hover{color:{$c['admin_hover_text']};background:{$c['admin_hover_bg']};}\n";

	// Folded state.
	$css .= ".folded #adminmenu .wp-submenu.sub-open,.folded #adminmenu .opens-sub .wp-submenu{background:" . lnf_color_darker( $c['admin_bg'], 8 ) . ";}\n";

	return $css;
}

/**
 * Assombrit une couleur hex d'un pourcentage.
 *
 * @param string $hex Couleur hex.
 * @param int    $percent Pourcentage d'assombrissement.
 * @return string
 */
function lnf_color_darker( $hex, $percent = 10 ) {
	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$r = max( 0, round( hexdec( substr( $hex, 0, 2 ) ) * ( 100 - $percent ) / 100 ) );
	$g = max( 0, round( hexdec( substr( $hex, 2, 2 ) ) * ( 100 - $percent ) / 100 ) );
	$b = max( 0, round( hexdec( substr( $hex, 4, 2 ) ) * ( 100 - $percent ) / 100 ) );
	return sprintf( '#%02x%02x%02x', $r, $g, $b );
}

/**
 * Éclaircit une couleur hex d'un pourcentage.
 *
 * @param string $hex Couleur hex.
 * @param int    $percent Pourcentage d'éclaircissement.
 * @return string
 */
function lnf_color_lighter( $hex, $percent = 10 ) {
	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$r = min( 255, round( hexdec( substr( $hex, 0, 2 ) ) + ( 255 - hexdec( substr( $hex, 0, 2 ) ) ) * $percent / 100 ) );
	$g = min( 255, round( hexdec( substr( $hex, 2, 2 ) ) + ( 255 - hexdec( substr( $hex, 2, 2 ) ) ) * $percent / 100 ) );
	$b = min( 255, round( hexdec( substr( $hex, 4, 2 ) ) + ( 255 - hexdec( substr( $hex, 4, 2 ) ) ) * $percent / 100 ) );
	return sprintf( '#%02x%02x%02x', $r, $g, $b );
}

/**
 * Injecte le CSS admin custom dans l'admin WordPress.
 */
function lnf_admin_colors_css_output() {
	$c = lnf_admin_colors();
	if ( empty( $c['admin_enable'] ) ) {
		return;
	}
	wp_add_inline_style( 'common', lnf_admin_colors_css() );
}
add_action( 'admin_enqueue_scripts', 'lnf_admin_colors_css_output', 99 );

/**
 * Injecte le CSS admin bar côté front-end (si bar visible).
 */
function lnf_adminbar_css_frontend() {
	$c = lnf_admin_colors();
	if ( empty( $c['admin_enable'] ) || ! is_user_logged_in() || ! is_admin_bar_showing() ) {
		return;
	}
	echo '<style id="lnf-adminbar-custom">' . lnf_admin_colors_css() . '</style>';
}
add_action( 'wp_head', 'lnf_adminbar_css_frontend', 99 );
