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
 * Gestion de la licence Pro : achat intégré, activation et vérification.
 *
 * Pour activer le paiement intégré, définissez dans wp-config.php :
 *
 *   define( 'LOGINFENNEC_CHECKOUT_URL', 'https://votre-boutique.lemonsqueezy.com/checkout/…" );
 *   define( 'LOGINFENNEC_LICENSE_API', 'https://votre-serveur.com/api/licence' );
 *
 * - CHECKOUT_URL : lien de paiement (Lemon Squeezy, Stripe Payment Link,
 *   Gumroad…) affiché dans une fenêtre intégrée au plugin.
 * - LICENSE_API  : endpoint de votre serveur de licences. Le plugin envoie
 *   { license_key, site_url } en POST et attend { valid: true } en JSON.
 *   Sans endpoint, l'activation est acceptée localement (mode développement).
 *
 * @package LoginFennecPro
 *
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'LOGINFENNEC_CHECKOUT_URL' ) ) {
	define( 'LOGINFENNEC_CHECKOUT_URL', '' );
}

if ( ! defined( 'LOGINFENNEC_LICENSE_API' ) ) {
	define( 'LOGINFENNEC_LICENSE_API', '' );
}

/** Page « protection / signalement DMCA » (filtrable). */
if ( ! defined( 'LOGINFENNEC_DMCA_URL' ) ) {
	define( 'LOGINFENNEC_DMCA_URL', 'https://www.dmca.com/' );
}

/** URL du badge DMCA (optionnel, ex. badge DMCA.com Protection Pro). */
if ( ! defined( 'LOGINFENNEC_DMCA_BADGE' ) ) {
	define( 'LOGINFENNEC_DMCA_BADGE', '' );
}

/**
 * Lien de signalement DMCA (filtrable).
 *
 * @return string
 */
function lnf_dmca_url() {
	return apply_filters( 'loginfennec_dmca_url', LOGINFENNEC_DMCA_URL );
}

/**
 * URL du badge DMCA affiché sur la page À propos (filtrable, vide par défaut).
 *
 * @return string
 */
function lnf_dmca_badge() {
	return apply_filters( 'loginfennec_dmca_badge', LOGINFENNEC_DMCA_BADGE );
}

/**
 * URL de paiement (filtrable).
 *
 * @return string
 */
function lnf_checkout_url() {
	return apply_filters( 'loginfennec_checkout_url', LOGINFENNEC_CHECKOUT_URL );
}

/**
 * Endpoint du serveur de licences (filtrable).
 *
 * @return string
 */
function lnf_license_api() {
	return apply_filters( 'loginfennec_license_api', LOGINFENNEC_LICENSE_API );
}

/**
 * Données de licence courantes.
 *
 * @return array { key, email, status, checked }
 */
function lnf_license_get() {
	$license = get_option( 'lnf_license', array() );
	return wp_parse_args(
		is_array( $license ) ? $license : array(),
		array(
			'key'     => '',
			'email'   => '',
			'status'  => 'inactive',
			'checked' => 0,
		)
	);
}

/**
 * Le site est-il en mode Pro ?
 *
 * @return bool
 */
function lnf_is_pro() {
	$license = lnf_license_get();
	return 'active' === $license['status'] && '' !== $license['key'];
}

/**
 * Gestion des requêtes AJAX de licence.
 */
class Lnf_License {

	/**
	 * Déclare les hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_lnf_activate_license', array( __CLASS__, 'ajax_activate' ) );
		add_action( 'wp_ajax_lnf_deactivate_license', array( __CLASS__, 'ajax_deactivate' ) );
	}

	/**
	 * Active une clé de licence.
	 */
	public static function ajax_activate() {
		check_ajax_referer( 'lnf_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		if ( '' === $key || strlen( $key ) < 8 ) {
			wp_send_json_error( array( 'message' => __( 'Veuillez saisir une clé de licence valide.', 'loginfennec' ) ) );
		}

		$api = lnf_license_api();
		if ( '' === $api ) {
			// Mode développement : aucun serveur de licences configuré, activation locale.
			update_option(
				'lnf_license',
				array(
					'key'     => $key,
					'email'   => '',
					'status'  => 'active',
					'checked' => time(),
				),
				false
			);
			wp_send_json_success(
				array( 'message' => __( 'Licence enregistrée. (Serveur de licences non configuré : validation locale.)', 'loginfennec' ) )
			);
		}

		$response = wp_remote_post(
			$api,
			array(
				'timeout' => 12,
				'body'    => array(
					'license_key' => $key,
					'site_url'    => home_url( '/' ),
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => __( 'Serveur de licences injoignable. Réessayez dans un instant.', 'loginfennec' ) ) );
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['valid'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Licence invalide ou expirée.', 'loginfennec' ) ) );
		}

		update_option(
			'lnf_license',
			array(
				'key'     => $key,
				'email'   => isset( $data['email'] ) ? sanitize_email( (string) $data['email'] ) : '',
				'status'  => 'active',
				'checked' => time(),
			),
			false
		);
		wp_send_json_success( array( 'message' => __( 'Pro activé. Merci pour votre soutien !', 'loginfennec' ) ) );
	}

	/**
	 * Désactive la licence locale (et prévient le serveur si configuré).
	 */
	public static function ajax_deactivate() {
		check_ajax_referer( 'lnf_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$license = lnf_license_get();
		$api     = lnf_license_api();
		if ( '' !== $api && '' !== $license['key'] ) {
			wp_remote_post(
				$api,
				array(
					'timeout' => 8,
					'body'    => array(
						'license_key' => $license['key'],
						'site_url'    => home_url( '/' ),
						'deactivate'  => 1,
					),
				)
			);
		}
		delete_option( 'lnf_license' );
		wp_send_json_success();
	}
}

Lnf_License::init();
