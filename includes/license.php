<?php

/**
 * ∞ INFINITY CODER — création originale de Derouiche Oussama
 *
 * Plugin   : LoginFennec Pro – Personnalisation page login et Security
 * Auteur   : Derouiche Oussama  ·  https://www.derouicheoussama.com
 * GitHub   : https://github.com/derouicheoussama
 * Copyright © 2026 Derouiche Oussama. Tous droits réservés.
 * Licence  : GPL v2 ou ultérieure — toute copie ou modification de ce
 *            fichier DOIT conserver la présente signature et les mentions
 *            de licence et d'attribution (article 2(c) de la GPL).
 */
/**
 * Gestion de la licence Pro : packs, achat intégré, activation,
 * vérification quotidienne et statut détaillé.
 *
 * Pour activer le paiement intégré, définissez dans wp-config.php :
 *
 *   define( 'LOGINFENNEC_CHECKOUT_URL', 'https://votre-boutique.lemonsqueezy.com/checkout/…' );
 *   define( 'LOGINFENNEC_LICENSE_API', 'https://votre-serveur.com/api/licence' );
 *
 * - CHECKOUT_URL : lien de paiement (Lemon Squeezy, Stripe Payment Link,
 *   Gumroad…). Les paramètres pack / billing / site sont ajoutés
 *   automatiquement par le plugin.
 * - LICENSE_API  : endpoint de votre serveur de licences. Le plugin envoie
 *   { license_key, site_url } en POST et attend en JSON :
 *   { valid: true, plan: "site1|site5", billing: "yearly|lifetime",
 *     expires: 0|timestamp, email: "…" }.
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
 * Définition des packs Pro (prix en dinars algériens, filtrable).
 *
 * @return array
 */
function lnf_license_plans() {
	return apply_filters(
		'loginfennec_license_plans',
		array(
			'site1' => array(
				'label'    => __( 'LoginFennec Pro — 1 site', 'loginfennec' ),
				'sites'    => 1,
				'yearly'   => array( 'price' => 3900, 'label' => __( '3 900 DA / an', 'loginfennec' ) ),
				'lifetime' => array( 'price' => 6800, 'label' => __( '6 800 DA à vie', 'loginfennec' ) ),
			),
			'site5' => array(
				'label'    => __( 'LoginFennec Pro — 5 sites', 'loginfennec' ),
				'sites'    => 5,
				'yearly'   => array( 'price' => 6800, 'label' => __( '6 800 DA / an', 'loginfennec' ) ),
				'lifetime' => array( 'price' => 7600, 'label' => __( '7 600 DA à vie', 'loginfennec' ) ),
			),
		)
	);
}

/**
 * Données de licence courantes.
 *
 * @return array
 */
function lnf_license_get() {
	$license  = get_option( 'lnf_license', array() );
	$defaults = array(
		'key'     => '',
		'email'   => '',
		'status'  => 'inactive', // inactive | active | expired.
		'plan'    => 'site1',    // site1 | site5.
		'billing' => 'yearly',   // yearly | lifetime.
		'sites'   => 1,
		'expires' => 0,          // timestamp (0 = à vie).
		'site'    => '',
		'checked' => 0,
	);
	return wp_parse_args( is_array( $license ) ? $license : array(), $defaults );
}

/**
 * La licence est-elle valide (Pro débloqué) ?
 *
 * @return bool
 */
function lnf_is_pro() {
	$license = lnf_license_get();
	if ( 'active' !== $license['status'] || '' === $license['key'] ) {
		return false;
	}
	if ( 'lifetime' === $license['billing'] ) {
		return true;
	}
	return (int) $license['expires'] > time();
}

/**
 * Libellé lisible du pack actif.
 *
 * @return string
 */
function lnf_license_label() {
	$license = lnf_license_get();
	$plans   = lnf_license_plans();
	$plan    = isset( $plans[ $license['plan'] ] ) ? $plans[ $license['plan'] ] : null;
	if ( ! $plan ) {
		return __( 'Aucune licence', 'loginfennec' );
	}
	$type = ( 'lifetime' === $license['billing'] )
		? __( 'À vie', 'loginfennec' )
		: __( 'Annuelle', 'loginfennec' );
	return $plan['label'] . ' — ' . $type;
}

/**
 * Gestion des requêtes AJAX et du contrôle quotidien de licence.
 */
class Lnf_License {

	const CRON_HOOK = 'lnf_license_cron';

	/**
	 * Déclare les hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_lnf_activate_license', array( __CLASS__, 'ajax_activate' ) );
		add_action( 'wp_ajax_lnf_deactivate_license', array( __CLASS__, 'ajax_deactivate' ) );
		add_action( 'wp_ajax_lnf_check_license', array( __CLASS__, 'ajax_check' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'daily_check' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Enregistre une licence.
	 *
	 * @param string $key     Clé.
	 * @param string $plan    Pack (site1|site5).
	 * @param string $billing Type (yearly|lifetime).
	 * @param string $email   E-mail.
	 * @param int    $expires Timestamp d'expiration (0 = à vie).
	 * @param string $status  Statut (active|expired).
	 */
	protected static function save( $key, $plan, $billing, $email, $expires, $status = 'active' ) {
		$plans = lnf_license_plans();
		update_option(
			'lnf_license',
			array(
				'key'     => sanitize_text_field( $key ),
				'email'   => sanitize_email( (string) $email ),
				'status'  => sanitize_key( $status ),
				'plan'    => isset( $plans[ $plan ] ) ? $plan : 'site1',
				'billing' => ( 'lifetime' === $billing ) ? 'lifetime' : 'yearly',
				'sites'   => isset( $plans[ $plan ] ) ? (int) $plans[ $plan ]['sites'] : 1,
				'expires' => (int) $expires,
				'site'    => home_url( '/' ),
				'checked' => time(),
			),
			false
		);
	}

	/**
	 * Activation d'une clé de licence.
	 */
	public static function ajax_activate() {
		check_ajax_referer( 'lnf_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$key     = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		$plan    = isset( $_POST['plan'] ) ? sanitize_key( wp_unslash( $_POST['plan'] ) ) : 'site1';
		$billing = isset( $_POST['billing'] ) ? sanitize_key( wp_unslash( $_POST['billing'] ) ) : 'yearly';
		if ( '' === $key || strlen( $key ) < 8 ) {
			wp_send_json_error( array( 'message' => __( 'Veuillez saisir une clé de licence valide.', 'loginfennec' ) ) );
		}

		$expires = ( 'lifetime' === $billing ) ? 0 : time() + YEAR_IN_SECONDS;

		$api = lnf_license_api();
		if ( '' === $api ) {
			// Mode développement : aucun serveur de licences configuré, activation locale.
			self::save( $key, $plan, $billing, '', $expires, 'active' );
			wp_send_json_success(
				array(
					'message' => __( 'Licence enregistrée. (Serveur de licences non configuré : validation locale.)', 'loginfennec' ),
					'details' => lnf_license_get(),
				)
			);
		}

		$response = wp_remote_post(
			$api,
			array(
				'timeout' => 12,
				'body'    => array(
					'license_key' => $key,
					'site_url'    => home_url( '/' ),
					'plan'        => $plan,
					'billing'     => $billing,
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

		$plan    = isset( $data['plan'] ) ? sanitize_key( (string) $data['plan'] ) : $plan;
		$billing = isset( $data['billing'] ) ? sanitize_key( (string) $data['billing'] ) : $billing;
		$expires = isset( $data['expires'] ) ? (int) $data['expires'] : $expires;
		$email   = isset( $data['email'] ) ? sanitize_email( (string) $data['email'] ) : '';

		self::save( $key, $plan, $billing, $email, $expires, 'active' );
		wp_send_json_success(
			array(
				'message' => __( 'Pro activé. Merci pour votre soutien !', 'loginfennec' ),
				'details' => lnf_license_get(),
			)
		);
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

	/**
	 * Vérification manuelle du statut (bouton « Vérifier maintenant »).
	 */
	public static function ajax_check() {
		check_ajax_referer( 'lnf_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		self::daily_check();
		wp_send_json_success( array( 'license' => lnf_license_get() ) );
	}

	/**
	 * Contrôle quotidien : expiration annuelle et révocation distante.
	 */
	public static function daily_check() {
		$license = lnf_license_get();
		if ( 'active' !== $license['status'] || '' === $license['key'] ) {
			return;
		}

		// Expiration d'une licence annuelle.
		if ( 'lifetime' !== $license['billing'] && (int) $license['expires'] > 0 && (int) $license['expires'] < time() ) {
			$license['status'] = 'expired';
			update_option( 'lnf_license', $license, false );
			return;
		}

		// Révocation à distance via le serveur de licences.
		$api = lnf_license_api();
		if ( '' === $api ) {
			return;
		}
		$response = wp_remote_post(
			$api,
			array(
				'timeout' => 10,
				'body'    => array(
					'license_key' => $license['key'],
					'site_url'    => home_url( '/' ),
					'check'       => 1,
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return; // Serveur injoignable : on garde le statut actuel.
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $data['valid'] ) && ! $data['valid'] ) {
			$license['status'] = 'expired';
			update_option( 'lnf_license', $license, false );
		}
	}
}

Lnf_License::init();
