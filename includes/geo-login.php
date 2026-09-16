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
 * GEO : restriction de la connexion par pays (liste d'autorisation).
 *
 * La détection utilise l'API gratuite get.geojs.io (HTTPS, sans clé) et
 * met le résultat en cache 24 h par IP. En cas d'échec de détection,
 * l'accès reste autorisé (fail-open) pour ne jamais verrouiller un admin.
 *
 * Confidentialité : activée, cette option envoie l'adresse IP du visiteur
 * à get.geojs.io. Mentionner cette transmission dans la politique de
 * confidentialité du site (voir readme.txt).
 *
 * @package LoginFennecPro
 *
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

/**
 * IP du client (détection GEO).
 *
 * @return string
 */
function lnf_geo_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return preg_replace( '/[^0-9a-fA-F:.]/', '', (string) $ip );
}

/**
 * Pays (code ISO à 2 lettres) d'une IP, avec cache 24 h.
 * Résultat filtrable via « lnf_geo_country » (tests, VPN internes…).
 *
 * @param string $ip Adresse IP.
 * @return string Code pays en majuscules, ou chaîne vide si inconnu.
 */
function lnf_geo_country_for_ip( $ip ) {
	if ( '' === $ip ) {
		return '';
	}
	$cached = get_transient( 'lnf_geo_' . md5( $ip ) );
	if ( is_string( $cached ) && preg_match( '/^[A-Z]{2}$/', $cached ) ) {
		return $cached;
	}
	$response = wp_remote_get(
		'https://get.geojs.io/v1/ip/country/' . rawurlencode( $ip ) . '.json',
		array( 'timeout' => 4 )
	);
	if ( is_wp_error( $response ) ) {
		return '';
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 !== $code || empty( $data['country_code'] ) || ! is_string( $data['country_code'] ) ) {
		return '';
	}
	$country = strtoupper( preg_replace( '/[^A-Za-z]/', '', $data['country_code'] ) );
	if ( ! preg_match( '/^[A-Z]{2}$/', $country ) ) {
		return '';
	}
	set_transient( 'lnf_geo_' . md5( $ip ), $country, DAY_IN_SECONDS );
	return $country;
}

/**
 * Liste des pays autorisés (codes ISO 2 lettres, normalisés).
 *
 * @return array
 */
function lnf_geo_allowed_countries() {
	$raw = trim( (string) lnf_get_option( 'geo_countries' ) );
	if ( '' === $raw ) {
		return array();
	}
	$allowed = array();
	foreach ( preg_split( '/[\s,;|]+/', strtoupper( $raw ) ) as $cc ) {
		$cc = preg_replace( '/[^A-Z]/', '', (string) $cc );
		if ( preg_match( '/^[A-Z]{2}$/', (string) $cc ) ) {
			$allowed[] = $cc;
		}
	}
	return array_unique( $allowed );
}

/**
 * La connexion depuis cette IP doit-elle être bloquée ?
 *
 * @return bool
 */
function lnf_geo_blocked() {
	$s = lnf_settings();
	if ( empty( $s['geo_enable'] ) ) {
		return false;
	}
	$allowed = lnf_geo_allowed_countries();
	if ( empty( $allowed ) ) {
		return false;
	}
	$ip = lnf_geo_client_ip();
	if ( class_exists( 'Lnf_Login_Security' ) && method_exists( 'Lnf_Login_Security', 'is_whitelisted' ) && Lnf_Login_Security::is_whitelisted( $ip ) ) {
		return false;
	}
	$country = apply_filters( 'lnf_geo_country', lnf_geo_country_for_ip( $ip ), $ip );
	if ( '' === $country ) {
		// Fail-open : détection impossible → on n'enferme personne dehors.
		return false;
	}
	return ! in_array( $country, $allowed, true );
}

/**
 * Garde-fou sur l'authentification (mot de passe) : refuse très tôt.
 *
 * @param WP_Error|WP_User|null $user     Utilisateur en cours.
 * @param string                $username Identifiant soumis.
 * @param string                $password Mot de passe soumis.
 * @return WP_Error|WP_User|null
 */
function lnf_geo_guard_auth( $user, $username = '', $password = '' ) {
	unset( $username, $password );
	if ( lnf_geo_blocked() ) {
		if ( class_exists( 'Lnf_Login_Security' ) && method_exists( 'Lnf_Login_Security', 'log_event' ) ) {
			Lnf_Login_Security::log_event( 'blocked', 'GEO:' . lnf_geo_country_for_ip( lnf_geo_client_ip() ) );
		}
		return new WP_Error( 'lnf_geo_blocked', __( 'La connexion depuis votre région n’est pas autorisée sur ce site.', 'loginfennec' ) );
	}
	return $user;
}
add_filter( 'authenticate', 'lnf_geo_guard_auth', 1, 3 );
