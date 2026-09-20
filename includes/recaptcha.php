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
 * reCAPTCHA v3 : protection invisible anti-bot sur la page de connexion.
 *
 * @package LoginFennecPro
 *
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

/**
 * reCAPTCHA v3 activé ?
 *
 * @return bool
 */
function lnf_recaptcha_enabled() {
	if ( function_exists( 'lnf_safe_mode' ) && lnf_safe_mode() ) {
		return false;
	}
	$s = lnf_settings();
	return ! empty( $s['recaptcha_enabled'] )
		&& ! empty( $s['recaptcha_site_key'] )
		&& ! empty( $s['recaptcha_secret_key'] );
}

/**
 * Charge le script reCAPTCHA v3 sur la page de connexion.
 */
function lnf_recaptcha_enqueue() {
	if ( ! lnf_recaptcha_enabled() ) {
		return;
	}
	$s = lnf_settings();
	wp_enqueue_script(
		'lnf-recaptcha',
		'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( $s['recaptcha_site_key'] ),
		array(),
		null,
		true
	);
}
add_action( 'login_enqueue_scripts', 'lnf_recaptcha_enqueue' );

/**
 * Ajoute le token reCAPTCHA au formulaire de connexion via JS.
 */
function lnf_recaptcha_footer() {
	if ( ! lnf_recaptcha_enabled() ) {
		return;
	}
	$s = lnf_settings();
	?>
	<script>
	// Garde : le pied de page est imprimé aussi sur lostpassword/register —
	// sans #loginform, addEventListener sur null lèverait une TypeError.
	(function () {
		var f = document.getElementById('loginform');
		if (!f) { return; }
		f.addEventListener('submit', function(e) {
			if (typeof grecaptcha !== 'undefined') {
				e.preventDefault();
				grecaptcha.ready(function() {
					grecaptcha.execute('<?php echo esc_js( $s['recaptcha_site_key'] ); ?>', {action: 'login'}).then(function(token) {
						var input = document.createElement('input');
						input.type = 'hidden';
						input.name = 'lnf_recaptcha_token';
						input.value = token;
						f.appendChild(input);
						f.submit();
					});
				});
			}
		});
	})();
	</script>
	<?php
}
add_action( 'login_footer', 'lnf_recaptcha_footer', 99 );

/**
 * Vérifie le token reCAPTCHA pendant l'authentification.
 *
 * @param WP_User|WP_Error|null $user     Utilisateur ou erreur.
 * @param string                $username Identifiant.
 * @param string                $password Mot de passe.
 * @return WP_User|WP_Error
 */
function lnf_recaptcha_verify( $user, $username, $password ) {
	if ( ! lnf_recaptcha_enabled() ) {
		return $user;
	}
	// Token absent ou vide = soumission sans passer par le JS (bot) :
	// la vérification est REFUSÉE, pas ignorée — sinon la protection
	// serait contournable en omettant simplement le champ.
	if ( ! isset( $_POST['lnf_recaptcha_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- formulaire de connexion public, pas de nonce disponible.
		return new WP_Error(
			'lnf_recaptcha_missing',
			'<strong>' . esc_html__( 'Erreur', 'loginfennec' ) . '</strong> : ' . esc_html__( 'Vérification anti-robots manquante. Activez JavaScript et réessayez.', 'loginfennec' )
		);
	}
	$s     = lnf_settings();
	$token = sanitize_text_field( wp_unslash( $_POST['lnf_recaptcha_token'] ) );
	if ( empty( $token ) ) {
		return new WP_Error(
			'lnf_recaptcha_missing',
			'<strong>' . esc_html__( 'Erreur', 'loginfennec' ) . '</strong> : ' . esc_html__( 'Vérification anti-robots manquante. Activez JavaScript et réessayez.', 'loginfennec' )
		);
	}

	$response = wp_remote_post(
		'https://www.google.com/recaptcha/api/siteverify',
		array(
			'timeout' => 10,
			'body'    => array(
				'secret'   => $s['recaptcha_secret_key'],
				'response' => $token,
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $user; // Serveur injoignable : on laisse passer (jamais enfermer les utilisateurs pour une panne Google).
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $data['success'] ) || ( isset( $data['score'] ) && $data['score'] < 0.5 ) ) {
		return new WP_Error(
			'lnf_recaptcha_failed',
			'<strong>' . esc_html__( 'Erreur', 'loginfennec' ) . '</strong> : ' . esc_html__( 'Vérification anti-robots échouée. Réessayez.', 'loginfennec' )
		);
	}
	return $user;
}
add_filter( 'authenticate', 'lnf_recaptcha_verify', 30, 3 );
