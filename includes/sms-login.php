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
 * Connexion par SMS : code à usage unique envoyé par SMS via Twilio,
 * Vonage ou un webhook HTTP générique (passerelles locales).
 *
 * @package LoginFennecPro
 *
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------
 * État de la fonctionnalité
 * ------------------------------------------------------------------- */

/**
 * La connexion par SMS est-elle disponible ?
 * (activée, passerelle configurée, essai non verrouillé)
 *
 * @return bool
 */
function lnf_sms_active() {
	if ( function_exists( 'lnf_safe_mode' ) && lnf_safe_mode() ) {
		return false;
	}
	if ( function_exists( 'lnf_trial_is_locked' ) && lnf_trial_is_locked() ) {
		return false;
	}
	$s = lnf_settings();
	if ( empty( $s['sms_enabled'] ) ) {
		return false;
	}
	return true;
}

/**
 * La passerelle configurée a-t-elle ses identifiants ?
 *
 * @return bool
 */
function lnf_sms_gateway_ready() {
	$s = lnf_settings();
	switch ( $s['sms_provider'] ) {
		case 'twilio':
			return '' !== $s['sms_twilio_sid'] && '' !== $s['sms_twilio_token'] && '' !== $s['sms_from'];
		case 'vonage':
			return '' !== $s['sms_vonage_key'] && '' !== $s['sms_vonage_secret'] && '' !== $s['sms_from'];
		case 'webhook':
			return '' !== $s['sms_webhook_url'];
	}
	return false;
}

/* ---------------------------------------------------------------------
 * Téléphones
 * ------------------------------------------------------------------- */

/**
 * Normalise un numéro au format international chiffres seuls.
 * « 0555 12-34 56 » avec pays 213 → « 213555123456 ».
 *
 * @param string $raw     Saisie utilisateur.
 * @param string $country Indicatif pays sans « + » (ex. 213).
 * @return string Numéro normalisé, ou chaîne vide.
 */
function lnf_sms_normalize_phone( $raw, $country = '' ) {
	$d       = preg_replace( '/\D+/', '', (string) $raw );
	$country = preg_replace( '/\D+/', '', (string) $country );
	if ( '' === $d ) {
		return '';
	}
	if ( 0 === strpos( $d, '00' ) ) {
		$d = substr( $d, 2 );
	}
	if ( '' !== $country && 0 === strpos( $d, $country ) ) {
		return $d;
	}
	if ( '' !== $country && strlen( $d ) <= 10 ) {
		return $country . ltrim( $d, '0' );
	}
	return $d;
}

/**
 * Retrouve l'utilisateur lié à un numéro (méta utilisateur « lnf_phone »).
 *
 * @param string $phone Numéro normalisé.
 * @return WP_User|false
 */
function lnf_sms_find_user( $phone ) {
	if ( '' === $phone ) {
		return false;
	}
	$ids = get_users(
		array(
			'meta_key' => 'lnf_phone', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- requête indexée unique, nécessaire à la connexion SMS.
			'meta_value' => $phone, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- idem.
			'number'   => 1,
			'fields'   => 'ids',
		)
	);
	if ( ! empty( $ids ) ) {
		return get_userdata( $ids[0] );
	}
	// Repli : comparaison normalisée (espaces, 0 initial, +… enregistrés bruts).
	$all = get_users(
		array(
			'meta_key' => 'lnf_phone', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- idem.
			'fields'   => 'ids',
			'number'   => 500,
		)
	);
	$country = lnf_get_option( 'sms_country' );
	foreach ( $all as $id ) {
		$stored = get_user_meta( $id, 'lnf_phone', true );
		if ( '' !== (string) $stored && lnf_sms_normalize_phone( $stored, $country ) === $phone ) {
			return get_userdata( $id );
		}
	}
	return false;
}

/**
 * IP du client (rate limiting).
 *
 * @return string
 */
function lnf_sms_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return preg_replace( '/[^0-9a-fA-F:.]/', '', (string) $ip );
}

/* ---------------------------------------------------------------------
 * Code à usage unique (OTP)
 * ------------------------------------------------------------------- */

/**
 * Génère un code numérique.
 *
 * @param int $length Longueur souhaitée (4-8).
 * @return string
 */
function lnf_sms_otp_code( $length = 6 ) {
	$length = max( 4, min( 8, (int) $length ) );
	$code   = '';
	for ( $i = 0; $i < $length; $i++ ) {
		$code .= (string) wp_rand( 0, 9 );
	}
	return $code;
}

/**
 * Clé de transient pour un numéro.
 *
 * @param string $phone Numéro normalisé.
 * @return string
 */
function lnf_sms_otp_key( $phone ) {
	return 'lnf_sms_otp_' . md5( $phone );
}

/**
 * Crée et stocke un OTP pour un numéro (jamais en clair : salé + haché).
 *
 * @param string $phone Numéro normalisé.
 * @return string Le code en clair (pour l'envoi).
 */
function lnf_sms_otp_create( $phone ) {
	$s    = lnf_settings();
	$salt = wp_generate_password( 16, false, false );
	$code = lnf_sms_otp_code( (int) $s['sms_otp_length'] );
	$ttl  = max( 1, (int) $s['sms_otp_ttl'] );
	set_transient(
		lnf_sms_otp_key( $phone ),
		array(
			's' => $salt,
			'h' => wp_hash( $salt . '|' . $phone . '|' . $code ),
			'e' => time() + $ttl * MINUTE_IN_SECONDS,
			'a' => 0,
		),
		$ttl * MINUTE_IN_SECONDS
	);
	return $code;
}

/**
 * Vérifie un OTP soumis et retourne l'utilisateur correspondant.
 *
 * @param string $phone Numéro normalisé.
 * @param string $code  Code soumis.
 * @return WP_User|WP_Error
 */
function lnf_sms_otp_verify( $phone, $code ) {
	$s   = lnf_settings();
	$key = lnf_sms_otp_key( $phone );
	$otp = get_transient( $key );
	if ( ! is_array( $otp ) || empty( $otp['h'] ) ) {
		return new WP_Error( 'lnf_sms_expired', __( 'Code expiré ou inexistant : demandez-en un nouveau.', 'loginfennec' ) );
	}
	$max = max( 1, (int) $s['sms_max_attempts'] );
	$otp['a'] = ( isset( $otp['a'] ) ? (int) $otp['a'] : 0 ) + 1;
	if ( $otp['a'] > $max ) {
		delete_transient( $key );
		return new WP_Error( 'lnf_sms_locked', __( 'Trop de tentatives : demandez un nouveau code.', 'loginfennec' ) );
	}
	$given = wp_hash( ( isset( $otp['s'] ) ? $otp['s'] : '' ) . '|' . $phone . '|' . trim( (string) $code ) );
	if ( ! hash_equals( $otp['h'], $given ) ) {
		$left = max( 60, (int) ( $otp['e'] - time() ) );
		set_transient( $key, $otp, $left );
		/* translators: %d : nombre de tentatives restantes. */
		return new WP_Error( 'lnf_sms_wrong', sprintf( __( 'Code incorrect. Tentatives restantes : %d.', 'loginfennec' ), max( 0, $max - $otp['a'] ) ) );
	}
	delete_transient( $key );
	return lnf_sms_find_user( $phone );
}

/* ---------------------------------------------------------------------
 * Envoi via la passerelle
 * ------------------------------------------------------------------- */

/**
 * Envoie un SMS via la passerelle configurée.
 *
 * @param string $phone   Numéro normalisé.
 * @param string $message Texte du message.
 * @return true|WP_Error
 */
function lnf_sms_dispatch( $phone, $message ) {
	$s = lnf_settings();
	if ( ! lnf_sms_gateway_ready() ) {
		return new WP_Error( 'lnf_sms_gateway', __( 'Passerelle SMS non configurée.', 'loginfennec' ) );
	}
	$timeout = 10;

	switch ( $s['sms_provider'] ) {
		case 'twilio':
			$response = wp_remote_post(
				'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode( $s['sms_twilio_sid'] ) . '/Messages.json',
				array(
					'timeout' => $timeout,
					'headers' => array(
						'Authorization' => 'Basic ' . base64_encode( $s['sms_twilio_sid'] . ':' . $s['sms_twilio_token'] ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- authentification HTTP basique exigée par l'API Twilio.
						'Content-Type'  => 'application/x-www-form-urlencoded',
					),
					'body'    => array(
						'From' => $s['sms_from'],
						'To'   => '+' . $phone,
						'Body' => $message,
					),
				)
			);
			break;

		case 'vonage':
			$response = wp_remote_post(
				'https://rest.nexmo.com/sms/json',
				array(
					'timeout' => $timeout,
					'body'    => array(
						'api_key'    => $s['sms_vonage_key'],
						'api_secret' => $s['sms_vonage_secret'],
						'from'       => $s['sms_from'],
						'to'         => $phone,
						'text'       => $message,
						'type'       => 'text',
					),
				)
			);
			break;

		case 'webhook':
			$response = wp_remote_post(
				$s['sms_webhook_url'],
				array(
					'timeout' => $timeout,
					'headers' => array(
						'Content-Type' => 'application/json',
						'X-Lnf-Token'  => (string) $s['sms_webhook_token'],
					),
					'body'    => wp_json_encode(
						array(
							'to'      => $phone,
							'from'    => $s['sms_from'],
							'message' => $message,
						)
					),
				)
			);
			break;

		default:
			return new WP_Error( 'lnf_sms_gateway', __( 'Passerelle SMS inconnue.', 'loginfennec' ) );
	}

	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );

	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'lnf_sms_http', sprintf( /* translators: %d : code HTTP. */ __( 'La passerelle a répondu avec le code HTTP %d.', 'loginfennec' ), $code ) );
	}
	if ( 'vonage' === $s['sms_provider'] ) {
		$data = json_decode( $body, true );
		$status = isset( $data['messages'][0]['status'] ) ? (string) $data['messages'][0]['status'] : '1';
		if ( '0' !== $status ) {
			return new WP_Error( 'lnf_sms_vonage', sprintf( /* translators: %s : code d'erreur Vonage. */ __( 'Vonage a signalé l’erreur %s.', 'loginfennec' ), $status ) );
		}
	}
	return true;
}

/* ---------------------------------------------------------------------
 * Limitation de débit
 * ------------------------------------------------------------------- */

/**
 * Contrôles anti-abus avant l'envoi d'un code.
 *
 * @param string $phone Numéro normalisé.
 * @return true|WP_Error
 */
function lnf_sms_rate_ok( $phone ) {
	$wait = get_transient( 'lnf_sms_wait_' . md5( $phone ) );
	if ( $wait ) {
		return new WP_Error( 'lnf_sms_wait', sprintf( /* translators: %d : secondes d'attente. */ __( 'Un code vient d’être envoyé. Réessayez dans %d secondes.', 'loginfennec' ), (int) $wait ) );
	}
	$ip_key = 'lnf_sms_ip_' . md5( lnf_sms_client_ip() );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 10 ) {
		return new WP_Error( 'lnf_sms_flood', __( 'Trop de codes demandés depuis cette adresse. Réessayez dans une heure.', 'loginfennec' ) );
	}
	return true;
}

/**
 * Comptabilise un envoi dans les compteurs anti-abus.
 *
 * @param string $phone Numéro normalisé.
 */
function lnf_sms_rate_hit( $phone ) {
	set_transient( 'lnf_sms_wait_' . md5( $phone ), 60, 60 );
	$ip_key = 'lnf_sms_ip_' . md5( lnf_sms_client_ip() );
	$count  = (int) get_transient( $ip_key );
	set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );
}

/* ---------------------------------------------------------------------
 * Connexion après vérification
 * ------------------------------------------------------------------- */

/**
 * Connecte l'utilisateur et retourne l'URL de redirection.
 *
 * @param WP_User $user        Utilisateur à connecter.
 * @param string  $redirect_to Cible demandée (validée).
 * @return string
 */
function lnf_sms_login_user( $user, $redirect_to = '' ) {
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, true );
	do_action( 'wp_login', $user->user_login, $user );
	if ( method_exists( 'Lnf_Login_Security', 'log_event' ) ) {
		Lnf_Login_Security::log_event( 'sms', $user->user_login );
	}
	$default = current_user_can( 'read' ) ? admin_url() : home_url();
	return wp_validate_redirect( $redirect_to, $default );
}

/* ---------------------------------------------------------------------
 * Page de connexion : panneau + assets
 * ------------------------------------------------------------------- */

/**
 * Charge le CSS/JS du panneau SMS (fichiers versionnés, cache-friendly).
 */
function lnf_sms_enqueue() {
	if ( ! lnf_sms_active() || ! lnf_sms_gateway_ready() ) {
		return;
	}
	wp_enqueue_style( 'lnf-sms', LOGINFENNEC_URL . 'assets/css/sms-login.css', array(), LOGINFENNEC_VERSION );
	wp_enqueue_script( 'lnf-sms', LOGINFENNEC_URL . 'assets/js/sms-login.js', array(), LOGINFENNEC_VERSION, true );
	wp_localize_script(
		'lnf-sms',
		'LNF_SMS_CFG',
		array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'lnf_sms' ),
			'wait'   => __( 'Patientez…', 'loginfennec' ),
			'sent'   => __( 'Code envoyé par SMS si ce numéro est enregistré.', 'loginfennec' ),
			'ttl'    => max( 1, (int) lnf_get_option( 'sms_otp_ttl' ) ),
		)
	);
}
add_action( 'login_enqueue_scripts', 'lnf_sms_enqueue' );

/**
 * Affiche le panneau SMS (déplacé sous le formulaire par le script).
 */
function lnf_sms_render_panel() {
	if ( ! lnf_sms_active() || ! lnf_sms_gateway_ready() ) {
		return;
	}
	?>
	<div id="lnf-sms" class="lnf-sms" hidden>
		<button type="button" class="lnf-sms-open"><?php esc_html_e( '📱 Se connecter par SMS', 'loginfennec' ); ?></button>
		<div class="lnf-sms-panel" hidden>
			<div class="lnf-sms-step lnf-sms-step-phone">
				<label for="lnf-sms-phone"><?php esc_html_e( 'Numéro de téléphone', 'loginfennec' ); ?></label>
				<input type="tel" id="lnf-sms-phone" name="lnf_sms_phone" autocomplete="tel" inputmode="tel" placeholder="<?php echo esc_attr( '05 XX XX XX XX' ); ?>">
				<button type="button" class="lnf-sms-send button"><?php esc_html_e( 'Recevoir le code', 'loginfennec' ); ?></button>
			</div>
			<div class="lnf-sms-step lnf-sms-step-code" hidden>
				<label for="lnf-sms-code"><?php esc_html_e( 'Code reçu par SMS', 'loginfennec' ); ?></label>
				<input type="text" id="lnf-sms-code" name="lnf_sms_code" autocomplete="one-time-code" inputmode="numeric" maxlength="8">
				<button type="button" class="lnf-sms-verify button button-primary"><?php esc_html_e( 'Se connecter', 'loginfennec' ); ?></button>
				<p class="lnf-sms-resend-row"><button type="button" class="lnf-link lnf-sms-resend" hidden><?php esc_html_e( 'Renvoyer le code', 'loginfennec' ); ?></button></p>
			</div>
			<p class="lnf-sms-status" role="status" aria-live="polite"></p>
			<button type="button" class="lnf-link lnf-sms-back" hidden><?php esc_html_e( '← Mot de passe', 'loginfennec' ); ?></button>
		</div>
	</div>
	<?php
}

/* ---------------------------------------------------------------------
 * AJAX
 * ------------------------------------------------------------------- */

/**
 * AJAX : envoi d'un code SMS.
 */
function lnf_sms_ajax_send() {
	check_ajax_referer( 'lnf_sms', 'nonce' );
	$generic = array( 'message' => __( 'Code envoyé par SMS si ce numéro est enregistré.', 'loginfennec' ) );
	if ( function_exists( 'lnf_geo_blocked' ) && lnf_geo_blocked() ) {
		wp_send_json_error( array( array( 'message' => __( 'Demande impossible depuis votre région.', 'loginfennec' ) ) ) );
	}
	if ( ! lnf_sms_active() || ! lnf_sms_gateway_ready() ) {
		wp_send_json_success( $generic );
	}
	$phone  = lnf_sms_normalize_phone( isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '', lnf_get_option( 'sms_country' ) );
	$rate   = lnf_sms_rate_ok( $phone );
	if ( is_wp_error( $rate ) ) {
		wp_send_json_error( array( array( 'message' => $rate->get_error_message() ) ) );
	}
	$user = lnf_sms_find_user( $phone );
	if ( ! $user ) {
		usleep( wp_rand( 200000, 450000 ) );
		wp_send_json_success( $generic );
	}
	$code = lnf_sms_otp_create( $phone );
	$s    = lnf_settings();
	$text = trim( (string) $s['sms_template'] );
	if ( '' === $text ) {
		$text = __( 'Votre code de connexion : {code} (valable {minutes} min).', 'loginfennec' );
	}
	$text = str_replace( '{code}', $code, $text );
	$text = str_replace( '{minutes}', (string) max( 1, (int) $s['sms_otp_ttl'] ), $text );
	$sent = lnf_sms_dispatch( $phone, sanitize_text_field( $text ) );
	if ( is_wp_error( $sent ) ) {
		wp_send_json_error( array( array( 'message' => $sent->get_error_message() ) ) );
	}
	lnf_sms_rate_hit( $phone );
	if ( method_exists( 'Lnf_Login_Security', 'log_event' ) ) {
		Lnf_Login_Security::log_event( 'sms', 'OTP → ' . substr( $phone, 0, strlen( $phone ) - 4 ) . '****' );
	}
	wp_send_json_success( $generic );
}
add_action( 'wp_ajax_lnfsms_send', 'lnf_sms_ajax_send' );
add_action( 'wp_ajax_nopriv_lnfsms_send', 'lnf_sms_ajax_send' );

/**
 * AJAX : vérification du code et connexion.
 */
function lnf_sms_ajax_verify() {
	check_ajax_referer( 'lnf_sms', 'nonce' );
	if ( function_exists( 'lnf_geo_blocked' ) && lnf_geo_blocked() ) {
		wp_send_json_error( array( array( 'message' => __( 'Connexion impossible depuis votre région.', 'loginfennec' ) ) ) );
	}
	if ( ! lnf_sms_active() ) {
		wp_send_json_error( array( array( 'message' => __( 'Connexion par SMS désactivée.', 'loginfennec' ) ) ) );
	}
	$phone = lnf_sms_normalize_phone( isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '', lnf_get_option( 'sms_country' ) );
	$code  = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
	$result = lnf_sms_otp_verify( $phone, $code );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( array( 'message' => $result->get_error_message() ) ) );
	}
	if ( ! $result ) {
		wp_send_json_error( array( array( 'message' => __( 'Code incorrect.', 'loginfennec' ) ) ) );
	}
	$redirect = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validé ci-dessous par wp_validate_redirect().
	wp_send_json_success(
		array(
			'redirect' => lnf_sms_login_user( $result, $redirect ),
		)
	);
}
add_action( 'wp_ajax_lnfsms_verify', 'lnf_sms_ajax_verify' );
add_action( 'wp_ajax_nopriv_lnfsms_verify', 'lnf_sms_ajax_verify' );

/**
 * AJAX admin : envoi d'un SMS de test.
 */
function lnf_sms_ajax_test() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( array( 'message' => __( 'Permission refusée.', 'loginfennec' ) ) ) );
	}
	check_ajax_referer( 'lnf_admin', 'nonce' );
	$phone = lnf_sms_normalize_phone( isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '', lnf_get_option( 'sms_country' ) );
	if ( '' === $phone ) {
		wp_send_json_error( array( array( 'message' => __( 'Numéro invalide.', 'loginfennec' ) ) ) );
	}
	$sent = lnf_sms_dispatch( $phone, __( 'LoginFennec Pro : envoi de test réussi. Votre passerelle SMS fonctionne.', 'loginfennec' ) );
	if ( is_wp_error( $sent ) ) {
		wp_send_json_error( array( array( 'message' => $sent->get_error_message() ) ) );
	}
	wp_send_json_success( array( array( 'message' => __( 'SMS de test envoyé.', 'loginfennec' ) ) ) );
}
add_action( 'wp_ajax_lnf_sms_test', 'lnf_sms_ajax_test' );

/* ---------------------------------------------------------------------
 * Profil utilisateur : numéro de téléphone
 * ------------------------------------------------------------------- */

/**
 * Champ téléphone sur le profil.
 *
 * @param WP_User $user Utilisateur affiché.
 */
function lnf_sms_profile_field( $user ) {
	$value = get_user_meta( $user->ID, 'lnf_phone', true );
	?>
	<h2><?php esc_html_e( 'Connexion par SMS — LoginFennec', 'loginfennec' ); ?></h2>
	<?php wp_nonce_field( 'lnf_phone_profile', 'lnf_phone_nonce' ); ?>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="lnf-phone"><?php esc_html_e( 'Téléphone', 'loginfennec' ); ?></label></th>
			<td>
				<input type="tel" id="lnf-phone" name="lnf_phone" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="05 XX XX XX XX">
				<p class="description"><?php esc_html_e( 'Permet la connexion par code SMS. Format international conseillé : +213 5 XX XX XX XX.', 'loginfennec' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'lnf_sms_profile_field' );
add_action( 'edit_user_profile', 'lnf_sms_profile_field' );

/**
 * Enregistre le téléphone du profil.
 *
 * @param int $user_id Identifiant utilisateur.
 */
function lnf_sms_profile_save( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	if ( ! isset( $_POST['lnf_phone_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lnf_phone_nonce'] ) ), 'lnf_phone_profile' ) ) {
		return;
	}
	$phone = isset( $_POST['lnf_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['lnf_phone'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_text_field appliqué ci-dessus.
	$phone = lnf_sms_normalize_phone( $phone, lnf_get_option( 'sms_country' ) );
	if ( '' === $phone ) {
		delete_user_meta( $user_id, 'lnf_phone' );
		return;
	}
	update_user_meta( $user_id, 'lnf_phone', $phone );
}
add_action( 'personal_options_update', 'lnf_sms_profile_save' );
add_action( 'edit_user_profile_update', 'lnf_sms_profile_save' );
