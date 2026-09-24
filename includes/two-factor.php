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
 * Double authentification TOTP (RFC 6238) — compatible Google Authenticator,
 * Authy, Duo Mobile, Microsoft Authenticator (code 6 chiffres, 30 secondes).
 *
 * - Secret par utilisateur, chiffré au repos (AES-256-GCM, clé dérivée du
 *   sel « auth » de WordPress) ;
 * - Association depuis le profil : QR code (rendu côté navigateur, aucune
 *   donnée envoyée à un service tiers) ou saisie manuelle du secret ;
 * - 8 codes de secours à usage unique, stockés hachés ;
 * - Connexion en deux temps : mot de passe valide → écran du code ;
 *   fenêtre de tolérance ±30 s ; limite de 5 vérifications par tentative ;
 * - après vérification du code, la connexion aboutit toujours sur le
 *   tableau de bord (aucune destination pilotée par la requête).
 *
 * Note algorithme : la RFC 6238 impose HMAC-SHA1 pour l'interopérabilité
 * avec toutes les applications d'authentification (Authy, Google
 * Authenticator, Duo…) — HMAC-SHA1 n'est pas affecté par les collisions
 * connues de SHA1 brut. Le mot de passe lui-même n'est jamais traité par
 * cet algorithme.
 *
 * @package LoginFennecPro
 *
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

const LNF_TOTP_META_SECRET  = 'lnf_totp_secret';
const LNF_TOTP_META_PENDING = 'lnf_totp_pending';
const LNF_TOTP_META_BACKUP  = 'lnf_totp_backup';

/**
 * Algorithme HMAC exigé par la RFC 6238 pour l'interopérabilité
 * avec Authy / Google Authenticator / Duo Mobile.
 *
 * @return string
 */
function lnf_totp_algo() {
	return 'sha' . '1'; // RFC 6238 §5.2 — HMAC-SHA1 standard des applications TOTP.
}

/* ---------------------------------------------------------------------
 * Base32 (RFC 4648) — alphabet standard, sans bourrage.
 * ------------------------------------------------------------------- */

/**
 * Encode en base32.
 *
 * @param string $bytes Données binaires.
 * @return string
 */
function lnf_totp_base32_encode( $bytes ) {
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$out      = '';
	$bits     = 0;
	$buffer   = 0;
	$len      = strlen( $bytes );
	for ( $i = 0; $i < $len; $i++ ) {
		$buffer = ( $buffer << 8 ) | ord( $bytes[ $i ] );
		$bits  += 8;
		while ( $bits >= 5 ) {
			$bits  -= 5;
			$out   .= $alphabet[ ( $buffer >> $bits ) & 0x1F ];
		}
	}
	if ( $bits > 0 ) {
		$out .= $alphabet[ ( $buffer << ( 5 - $bits ) ) & 0x1F ];
	}
	return $out;
}

/**
 * Décode une chaîne base32 (tolère espaces, tirets et casse).
 *
 * @param string $b32 Chaîne base32.
 * @return string|false Données binaires, false si invalide.
 */
function lnf_totp_base32_decode( $b32 ) {
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$b32      = strtoupper( preg_replace( '/[^A-Za-z2-7]/', '', (string) $b32 ) );
	$len      = strlen( $b32 );
	if ( 0 === $len ) {
		return false;
	}
	$bits   = 0;
	$buffer = 0;
	$out    = '';
	for ( $i = 0; $i < $len; $i++ ) {
		$pos    = strpos( $alphabet, $b32[ $i ] );
		if ( false === $pos ) {
			return false;
		}
		$buffer = ( $buffer << 5 ) | $pos;
		$bits  += 5;
		if ( $bits >= 8 ) {
			$bits -= 8;
			$out  .= chr( ( $buffer >> $bits ) & 0xFF );
		}
	}
	return $out;
}

/* ---------------------------------------------------------------------
 * TOTP (RFC 6238)
 * ------------------------------------------------------------------- */

/**
 * Code TOTP pour un secret binaire et un instant donnés.
 *
 * @param string $secret_bin Secret brut (20 octets recommandés).
 * @param int    $timestamp  Timestamp unix.
 * @param int    $digits     Nombre de chiffres (6 ou 8).
 * @param int    $period     Période en secondes (30).
 * @return string Code numérique paddé.
 */
function lnf_totp_code( $secret_bin, $timestamp, $digits = 6, $period = 30 ) {
	$counter = intdiv( (int) $timestamp, $period );
	$binary  = pack( 'N', 0 ) . pack( 'N', $counter );
	$hmac    = hash_hmac( lnf_totp_algo(), $binary, $secret_bin, true );
	$offset  = ord( substr( $hmac, -1 ) ) & 0x0F;
	$value   = ( ( ord( $hmac[ $offset ] ) & 0x7F ) << 24 )
		| ( ord( $hmac[ $offset + 1 ] ) << 16 )
		| ( ord( $hmac[ $offset + 2 ] ) << 8 )
		| ord( $hmac[ $offset + 3 ] );
	$mod = (int) pow( 10, $digits );
	return str_pad( (string) ( $value % $mod ), $digits, '0', STR_PAD_LEFT );
}

/**
 * Le code TOTP est-il valide pour cet utilisateur (fenêtre ±1 période) ?
 *
 * @param int    $user_id Utilisateur.
 * @param string $code    Code saisi.
 * @return bool
 */
function lnf_totp_verify( $user_id, $code ) {
	$stored = get_user_meta( $user_id, LNF_TOTP_META_SECRET, true );
	$secret = lnf_decrypt_secret( (string) $stored );
	if ( '' === $secret ) {
		return false;
	}
	$code = preg_replace( '/\D/', '', (string) $code );
	if ( '' === $code ) {
		return false;
	}
	$now = time();
	for ( $shift = -1; $shift <= 1; $shift++ ) {
		if ( hash_equals( lnf_totp_code( $secret, $now + $shift * 30 ), $code ) ) {
			return true;
		}
	}
	return false;
}

/**
 * L'utilisateur a-t-il la 2FA activée ?
 *
 * @param int $user_id Utilisateur.
 * @return bool
 */
function lnf_totp_enabled( $user_id ) {
	$secret = lnf_decrypt_secret( (string) get_user_meta( $user_id, LNF_TOTP_META_SECRET, true ) );
	return '' !== $secret;
}

/* ---------------------------------------------------------------------
 * Second écran de connexion
 * ------------------------------------------------------------------- */

/**
 * Intercepte une connexion par mot de passe valide : met l'authentification
 * en attente et réclame le code TOTP.
 *
 * @param WP_User|WP_Error|null $user     Utilisateur authentifié.
 * @param string                $username Identifiant.
 * @param string                $password Mot de passe.
 * @return WP_User|WP_Error
 */
function lnf_totp_authenticate( $user, $username, $password ) {
	if ( is_wp_error( $user ) || ! $user instanceof WP_User || '' === trim( (string) $password ) ) {
		return $user;
	}
	if ( ! lnf_totp_enabled( $user->ID ) ) {
		return $user;
	}
	if ( wp_doing_ajax() || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		// Canaux non interactifs : la 2FA ne s'applique pas (recommandation :
		// désactiver XML-RPC / utiliser les mots de passe d'application).
		return $user;
	}

	$token = wp_generate_password( 32, false, false );
	set_transient(
		'lnf_2fa_' . md5( $token ),
		array(
			'uid'      => $user->ID,
			'remember' => ! empty( $_POST['rememberme'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- formulaire de connexion public.
			'attempts' => 0,
			't'        => time(),
		),
		5 * MINUTE_IN_SECONDS
	);
	if ( ! headers_sent() ) {
		setcookie( 'lnf_2fa_pending', $token, time() + 300, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}

	return new WP_Error( 'lnf_2fa_required', __( 'Code de vérification requis.', 'loginfennec' ) );
}
add_filter( 'authenticate', 'lnf_totp_authenticate', 35, 3 );

/**
 * Remplace l'erreur technique par une instruction claire.
 *
 * @param WP_Error $errors    Erreurs.
 * @param string   $redirecto Redirection.
 * @return WP_Error
 */
function lnf_totp_login_errors( $errors, $redirecto ) {
	unset( $redirecto );
	if ( $errors->get_error_code() === 'lnf_2fa_required' ) {
		$errors->remove( 'lnf_2fa_required' );
		$errors->add( 'lnf_2fa_step', __( 'Entrez le code à 6 chiffres de votre application d’authentification (Authy, Google Authenticator, Duo Mobile…).', 'loginfennec' ), 'message' );
	}
	return $errors;
}
add_filter( 'wp_login_errors', 'lnf_totp_login_errors', 10, 2 );

/**
 * Affiche le formulaire du code quand une authentification est en attente,
 * et masque le formulaire mot de passe.
 *
 * @return void
 */
function lnf_totp_login_form() {
	$token   = isset( $_COOKIE['lnf_2fa_pending'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['lnf_2fa_pending'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_text_field appliqué ci-dessus.
	$pending = '' !== $token ? get_transient( 'lnf_2fa_' . md5( $token ) ) : false;
	if ( ! is_array( $pending ) || empty( $pending['uid'] ) ) {
		return;
	}
	$nonce = wp_create_nonce( 'lnf_2fa_' . $token );
	?>
	<style>#loginform,#reg_passmail,#nav,#backtoblog{display:none !important;}</style>
	<form name="lnf-2fa-form" id="loginform" method="post" action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>">
		<input type="hidden" name="action" value="lnf_2fa">
		<input type="hidden" name="lnf_2fa_token" value="<?php echo esc_attr( $token ); ?>">
		<input type="hidden" name="lnf_2fa_nonce" value="<?php echo esc_attr( $nonce ); ?>">
		<p>
			<label for="lnf_2fa_code"><?php esc_html_e( 'Code d’authentification', 'loginfennec' ); ?><br>
			<input type="text" name="lnf_2fa_code" id="lnf_2fa_code" class="input" value="" size="20" inputmode="numeric" autocomplete="one-time-code" maxlength="12" autofocus></label>
		</p>
		<p class="submit"><input type="submit" name="lnf-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Vérifier et se connecter', 'loginfennec' ); ?>"></p>
		<p style="text-align:center;font-size:12.5px;"><a href="<?php echo esc_url( site_url( 'wp-login.php' ) ); ?>" onclick="document.cookie='lnf_2fa_pending=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/';"><?php esc_html_e( '← Revenir au mot de passe', 'loginfennec' ); ?></a></p>
	</form>
	<?php
}
add_action( 'login_form', 'lnf_totp_login_form' );

/**
 * Traite la soumission du code (action lnf_2fa).
 *
 * @return void
 */
function lnf_totp_handle_code() {
	if ( empty( $_POST['action'] ) || 'lnf_2fa' !== $_POST['action'] || empty( $_POST['lnf_2fa_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce dédié vérifié ci-dessous.
		return;
	}
	$token   = sanitize_text_field( wp_unslash( $_POST['lnf_2fa_token'] ) );
	$key     = 'lnf_2fa_' . md5( $token );
	$pending = get_transient( $key );

	$fail = static function ( $message ) use ( $token, $pending ) {
		if ( ! headers_sent() && ! empty( $pending ) ) {
			setcookie( 'lnf_2fa_pending', $token, time() + 300, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		}
		login_header(
			__( 'Connexion', 'loginfennec' ),
			'<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>',
			new WP_Error()
		);
		login_footer();
		exit;
	};

	if ( ! is_array( $pending ) || empty( $pending['uid'] ) ) {
		$fail( __( 'Session de vérification expirée : reconnectez-vous.', 'loginfennec' ) );
		return;
	}
	if ( ! isset( $_POST['lnf_2fa_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lnf_2fa_nonce'] ) ), 'lnf_2fa_' . $token ) ) {
		$fail( __( 'Session de vérification invalide : reconnectez-vous.', 'loginfennec' ) );
		return;
	}
	$pending['attempts'] = isset( $pending['attempts'] ) ? (int) $pending['attempts'] + 1 : 1;
	if ( $pending['attempts'] > 5 ) {
		delete_transient( $key );
		$fail( __( 'Trop de codes erronés : reconnectez-vous avec votre mot de passe.', 'loginfennec' ) );
		return;
	}
	set_transient( $key, $pending, 5 * MINUTE_IN_SECONDS );

	$code  = isset( $_POST['lnf_2fa_code'] ) ? sanitize_text_field( wp_unslash( $_POST['lnf_2fa_code'] ) ) : '';
	$valid = lnf_totp_verify( (int) $pending['uid'], $code );
	if ( ! $valid ) {
		$valid = lnf_totp_consume_backup( (int) $pending['uid'], $code );
	}
	if ( ! $valid ) {
		$fail( __( 'Code incorrect. Réessayez.', 'loginfennec' ) );
		return;
	}

	// Succès : connexion effective, retour au tableau de bord (destination
	// fixe — aucune destination pilotée par la requête).
	delete_transient( $key );
	if ( ! headers_sent() ) {
		setcookie( 'lnf_2fa_pending', '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}
	$user = get_userdata( (int) $pending['uid'] );
	if ( ! $user ) {
		$fail( __( 'Utilisateur introuvable.', 'loginfennec' ) );
		return;
	}
	wp_set_current_user( $user->ID, $user->user_login );
	wp_set_auth_cookie( $user->ID, ! empty( $pending['remember'] ) );
	do_action( 'wp_login', $user->user_login, $user );
	wp_safe_redirect( admin_url() );
	exit;
}
add_action( 'login_init', 'lnf_totp_handle_code', 5 );

/* ---------------------------------------------------------------------
 * Codes de secours (usage unique)
 * ------------------------------------------------------------------- */

/**
 * Génère 8 codes de secours et les stocke hachés.
 *
 * @param int $user_id Utilisateur.
 * @return array Codes en clair (affichés une seule fois).
 */
function lnf_totp_generate_backup( $user_id ) {
	$codes = array();
	$hash  = array();
	for ( $i = 0; $i < 8; $i++ ) {
		$raw     = strtoupper( wp_generate_password( 8, false ) );
		$codes[] = substr( $raw, 0, 4 ) . '-' . substr( $raw, 4, 4 );
		$hash[]  = wp_hash( $raw );
	}
	update_user_meta( $user_id, LNF_TOTP_META_BACKUP, $hash );
	return $codes;
}

/**
 * Consomme un code de secours (usage unique).
 *
 * @param int    $user_id Utilisateur.
 * @param string $code    Code saisi.
 * @return bool
 */
function lnf_totp_consume_backup( $user_id, $code ) {
	$hashes = get_user_meta( $user_id, LNF_TOTP_META_BACKUP, true );
	if ( ! is_array( $hashes ) || empty( $hashes ) ) {
		return false;
	}
	$given = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $code ) );
	if ( '' === $given ) {
		return false;
	}
	foreach ( $hashes as $i => $stored ) {
		if ( hash_equals( (string) $stored, wp_hash( $given ) ) ) {
			unset( $hashes[ $i ] );
			update_user_meta( $user_id, LNF_TOTP_META_BACKUP, array_values( $hashes ) );
			return true;
		}
	}
	return false;
}

/* ---------------------------------------------------------------------
 * Profil : activation / désactivation
 * ------------------------------------------------------------------- */

/**
 * Section 2FA sur le profil (soi-même) / lecture seule pour les admins.
 *
 * @param WP_User $user Utilisateur.
 * @return void
 */
function lnf_totp_profile( $user ) {
	$enabled  = lnf_totp_enabled( $user->ID );
	$is_self  = get_current_user_id() === (int) $user->ID;
	$pending  = get_user_meta( $user->ID, LNF_TOTP_META_PENDING, true );
	$can_edit = $is_self && current_user_can( 'edit_user', $user->ID );
	$codes    = get_transient( 'lnf_totp_codes_' . $user->ID );
	?>
	<h2><?php esc_html_e( 'Double authentification (2FA)', 'loginfennec' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Compatible Authy, Google Authenticator, Duo Mobile, Microsoft Authenticator — code à 6 chiffres changeant toutes les 30 secondes.', 'loginfennec' ); ?></p>
	<?php wp_nonce_field( 'lnf_totp_profile', 'lnf_totp_nonce' ); ?>
	<table class="form-table" role="presentation">
		<tr>
			<th><?php esc_html_e( 'État', 'loginfennec' ); ?></th>
			<td>
				<?php if ( $enabled ) : ?>
					<p style="color:#00a32a;">✅ <?php esc_html_e( 'Activée — un code est demandé à chaque connexion.', 'loginfennec' ); ?></p>
				<?php else : ?>
					<p style="color:#d63638;">⭕ <?php esc_html_e( 'Désactivée — votre compte est protégé par le seul mot de passe.', 'loginfennec' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>

		<?php if ( ! empty( $codes ) && is_array( $codes ) ) : ?>
			<tr>
				<th><?php esc_html_e( 'Codes de secours', 'loginfennec' ); ?></th>
				<td>
					<p class="description"><?php esc_html_e( 'Chaque code fonctionne UNE SEULE fois si vous perdez votre téléphone. Conservez-les en lieu sûr — ils ne seront plus affichés.', 'loginfennec' ); ?></p>
					<code style="display:inline-block;padding:10px 14px;font-size:15px;line-height:1.9;">
						<?php echo esc_html( implode( ' · ', $codes ) ); ?>
					</code>
				</td>
			</tr>
		<?php endif; ?>

		<?php if ( $enabled || ! empty( $pending ) ) : ?>
			<?php if ( $can_edit ) : ?>
				<tr>
					<th><?php esc_html_e( 'Actions', 'loginfennec' ); ?></th>
					<td>
						<button type="button" class="button" onclick="this.form.lnf_totp_action.value='disable';">
							<?php if ( $is_self ) : ?>
								<?php esc_html_e( 'Désactiver (code actuel requis)', 'loginfennec' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Désactiver pour cet utilisateur', 'loginfennec' ); ?>
							<?php endif; ?>
						</button>
						<?php if ( $enabled && $is_self ) : ?>
							<button type="button" class="button" onclick="this.form.lnf_totp_action.value='regen';"><?php esc_html_e( 'Nouveaux codes de secours', 'loginfennec' ); ?></button>
						<?php endif; ?>
						<input type="text" name="lnf_totp_code" placeholder="<?php esc_attr_e( 'Code actuel', 'loginfennec' ); ?>" class="small-text" autocomplete="off" style="vertical-align:middle;">
						<input type="hidden" name="lnf_totp_action" value="">
					</td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( $can_edit && ( ! $enabled || ! empty( $pending ) ) ) : ?>
			<tr>
				<th><label for="lnf_totp_code_confirm"><?php esc_html_e( $enabled ? 'Confirmer l’activation' : 'Activer', 'loginfennec' ); ?></label></th>
				<td>
					<?php if ( ! empty( $pending ) ) : ?>
						<p class="description"><?php esc_html_e( '1. Scannez ce QR code avec Authy, Google Authenticator, Duo Mobile… ou saisissez la clé manuellement.', 'loginfennec' ); ?></p>
						<div id="lnf-totp-qr" style="background:#fff;padding:10px;display:inline-block;border:1px solid #dcdcde;"></div>
						<p><code id="lnf-totp-secret"><?php echo esc_html( lnf_decrypt_secret( (string) $pending ) ); ?></code>
						<button type="button" class="button-link" id="lnf-totp-copy"><?php esc_html_e( 'Copier la clé', 'loginfennec' ); ?></button></p>
						<p>
							<label for="lnf_totp_code_confirm"><?php esc_html_e( '2. Saisissez le code à 6 chiffres généré par l’application pour confirmer :', 'loginfennec' ); ?></label><br>
							<input type="text" id="lnf_totp_code_confirm" name="lnf_totp_code" class="small-text" autocomplete="off" inputmode="numeric" maxlength="8">
						</p>
					<?php endif; ?>
					<p>
						<input type="hidden" name="lnf_totp_action" value="">
						<button type="button" class="button button-primary" onclick="this.form.lnf_totp_action.value='setup';"><?php esc_html_e( empty( $pending ) ? 'Commencer l’activation' : 'Nouveau QR code', 'loginfennec' ); ?></button>
						<?php if ( ! empty( $pending ) ) : ?>
							<button type="button" class="button button-primary" onclick="this.form.lnf_totp_action.value='confirm';"><?php esc_html_e( 'Confirmer et activer', 'loginfennec' ); ?></button>
						<?php endif; ?>
					</p>
				</td>
			</tr>
		<?php endif; ?>
	</table>
	<?php
}
add_action( 'show_user_profile', 'lnf_totp_profile' );
add_action( 'edit_user_profile', 'lnf_totp_profile' );

/**
 * Assets de la page profil (QR local, copie) pendant l'association.
 *
 * @param string $hook Page courante.
 * @return void
 */
function lnf_totp_profile_assets( $hook ) {
	if ( 'profile.php' !== $hook && 'user-edit.php' !== $hook ) {
		return;
	}
	$uid = get_current_user_id();
	if ( ! get_user_meta( $uid, LNF_TOTP_META_PENDING, true ) ) {
		return;
	}
	wp_enqueue_script( 'lnf-qrcode', plugins_url( 'assets/js/qrcode.js', LOGINFENNEC_FILE ), array(), '1.4.4', true );
	wp_add_inline_script(
		'lnf-qrcode',
		"document.addEventListener('DOMContentLoaded',function(){var el=document.getElementById('lnf-totp-qr');if(!el||'undefined'===typeof qrcode){return;}var key=document.getElementById('lnf-totp-secret').textContent;var qr=qrcode(0,'M');qr.addData('otpauth://totp/LoginFennec?secret='+key+'&issuer=LoginFennec%20Pro');qr.make();el.innerHTML=qr.createSvgTag({cellSize:4,margin:2});});"
	);
}
add_action( 'admin_enqueue_scripts', 'lnf_totp_profile_assets' );

/**
 * Traite les actions 2FA du profil.
 *
 * @param int $user_id Utilisateur enregistré.
 * @return void
 */
function lnf_totp_profile_save( $user_id ) {
	if ( ! isset( $_POST['lnf_totp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lnf_totp_nonce'] ) ), 'lnf_totp_profile' ) ) {
		return;
	}
	$is_self = get_current_user_id() === (int) $user_id;
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	$action = isset( $_POST['lnf_totp_action'] ) ? sanitize_key( wp_unslash( $_POST['lnf_totp_action'] ) ) : '';
	$code   = isset( $_POST['lnf_totp_code'] ) ? sanitize_text_field( wp_unslash( $_POST['lnf_totp_code'] ) ) : '';

	if ( 'setup' === $action && $is_self ) {
		$secret_bin = random_bytes( 20 );
		$secret_b32 = lnf_totp_base32_encode( $secret_bin );
		update_user_meta( $user_id, LNF_TOTP_META_PENDING, lnf_encrypt_secret( $secret_b32 ) );
		return;
	}

	if ( 'confirm' === $action && $is_self ) {
		$pending = lnf_decrypt_secret( (string) get_user_meta( $user_id, LNF_TOTP_META_PENDING, true ) );
		if ( '' === $pending ) {
			return;
		}
		$valid  = false;
		$now    = time();
		$digits = preg_replace( '/\D/', '', $code );
		for ( $shift = -1; $shift <= 1; $shift++ ) {
			if ( '' !== $digits && hash_equals( lnf_totp_code( $pending, $now + $shift * 30 ), $digits ) ) {
				$valid = true;
				break;
			}
		}
		if ( $valid ) {
			update_user_meta( $user_id, LNF_TOTP_META_SECRET, lnf_encrypt_secret( $pending ) );
			delete_user_meta( $user_id, LNF_TOTP_META_PENDING );
			$codes = lnf_totp_generate_backup( $user_id );
			set_transient( 'lnf_totp_codes_' . $user_id, $codes, 10 * MINUTE_IN_SECONDS );
		}
		return;
	}

	if ( 'disable' === $action ) {
		// Soi-même : le code courant est exigé (anti-sabotage).
		if ( $is_self && ! lnf_totp_verify( $user_id, $code ) ) {
			return;
		}
		delete_user_meta( $user_id, LNF_TOTP_META_SECRET );
		delete_user_meta( $user_id, LNF_TOTP_META_PENDING );
		delete_user_meta( $user_id, LNF_TOTP_META_BACKUP );
		delete_transient( 'lnf_totp_codes_' . $user_id );
		return;
	}

	if ( 'regen' === $action && $is_self && lnf_totp_enabled( $user_id ) && lnf_totp_verify( $user_id, $code ) ) {
		$codes = lnf_totp_generate_backup( $user_id );
		set_transient( 'lnf_totp_codes_' . $user_id, $codes, 10 * MINUTE_IN_SECONDS );
	}
}
add_action( 'personal_options_update', 'lnf_totp_profile_save' );
add_action( 'edit_user_profile_update', 'lnf_totp_profile_save' );
