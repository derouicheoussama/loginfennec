<?php
/**
 * Gestion de la licence Pro : achat intégré, activation et vérification.
 *
 * Pour activer le paiement intégré, définissez dans wp-config.php :
 *
 *   define( 'INFINITY_LOGINSHIELD_CHECKOUT_URL', 'https://votre-boutique.lemonsqueezy.com/checkout/…" );
 *   define( 'INFINITY_LOGINSHIELD_LICENSE_API', 'https://votre-serveur.com/api/licence' );
 *
 * - CHECKOUT_URL : lien de paiement (Lemon Squeezy, Stripe Payment Link,
 *   Gumroad…) affiché dans une fenêtre intégrée au plugin.
 * - LICENSE_API  : endpoint de votre serveur de licences. Le plugin envoie
 *   { license_key, site_url } en POST et attend { valid: true } en JSON.
 *   Sans endpoint, l'activation est acceptée localement (mode développement).
 *
 * @package InfinityLoginShield
 *
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'INFINITY_LOGINSHIELD_CHECKOUT_URL' ) ) {
	define( 'INFINITY_LOGINSHIELD_CHECKOUT_URL', '' );
}

if ( ! defined( 'INFINITY_LOGINSHIELD_LICENSE_API' ) ) {
	define( 'INFINITY_LOGINSHIELD_LICENSE_API', '' );
}

/**
 * URL de paiement (filtrable).
 *
 * @return string
 */
function inls_checkout_url() {
	return apply_filters( 'infinity_loginshield_checkout_url', INFINITY_LOGINSHIELD_CHECKOUT_URL );
}

/**
 * Endpoint du serveur de licences (filtrable).
 *
 * @return string
 */
function inls_license_api() {
	return apply_filters( 'infinity_loginshield_license_api', INFINITY_LOGINSHIELD_LICENSE_API );
}

/**
 * Données de licence courantes.
 *
 * @return array { key, email, status, checked }
 */
function inls_license_get() {
	$license = get_option( 'inls_license', array() );
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
function inls_is_pro() {
	$license = inls_license_get();
	return 'active' === $license['status'] && '' !== $license['key'];
}

/**
 * Gestion des requêtes AJAX de licence.
 */
class Inls_License {

	/**
	 * Déclare les hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_inls_activate_license', array( __CLASS__, 'ajax_activate' ) );
		add_action( 'wp_ajax_inls_deactivate_license', array( __CLASS__, 'ajax_deactivate' ) );
	}

	/**
	 * Active une clé de licence.
	 */
	public static function ajax_activate() {
		check_ajax_referer( 'inls_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		if ( '' === $key || strlen( $key ) < 8 ) {
			wp_send_json_error( array( 'message' => __( 'Veuillez saisir une clé de licence valide.', 'infinity-loginshield' ) ) );
		}

		$api = inls_license_api();
		if ( '' === $api ) {
			// Mode développement : aucun serveur de licences configuré, activation locale.
			update_option(
				'inls_license',
				array(
					'key'     => $key,
					'email'   => '',
					'status'  => 'active',
					'checked' => time(),
				),
				false
			);
			wp_send_json_success(
				array( 'message' => __( 'Licence enregistrée. (Serveur de licences non configuré : validation locale.)', 'infinity-loginshield' ) )
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
			wp_send_json_error( array( 'message' => __( 'Serveur de licences injoignable. Réessayez dans un instant.', 'infinity-loginshield' ) ) );
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['valid'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Licence invalide ou expirée.', 'infinity-loginshield' ) ) );
		}

		update_option(
			'inls_license',
			array(
				'key'     => $key,
				'email'   => isset( $data['email'] ) ? sanitize_email( (string) $data['email'] ) : '',
				'status'  => 'active',
				'checked' => time(),
			),
			false
		);
		wp_send_json_success( array( 'message' => __( 'Pro activé. Merci pour votre soutien !', 'infinity-loginshield' ) ) );
	}

	/**
	 * Désactive la licence locale (et prévient le serveur si configuré).
	 */
	public static function ajax_deactivate() {
		check_ajax_referer( 'inls_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$license = inls_license_get();
		$api     = inls_license_api();
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
		delete_option( 'inls_license' );
		wp_send_json_success();
	}
}

Inls_License::init();
