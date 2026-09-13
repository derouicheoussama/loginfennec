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
 * Vérification d'intégrité du code : signatures, notifications,
 * verrouillage en cas de modification non autorisée.
 *
 * @package LoginFennecPro
 *
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

/** URL webhook pour notification de modification (optionnel). */
if ( ! defined( 'LOGINFENNEC_TAMPER_WEBHOOK' ) ) {
	define( 'LOGINFENNEC_TAMPER_WEBHOOK', '' );
}

/** E-mail du développeur pour notification de modification. */
if ( ! defined( 'LOGINFENNEC_DEV_EMAIL' ) ) {
	define( 'LOGINFENNEC_DEV_EMAIL', '' );
}

/**
 * Fichiers critiques et leur signature obligatoire.
 *
 * @return array fichier => signature attendue.
 */
function lnf_integrity_critical_files() {
	return array(
		'loginfennec.php'              => 'INFINITY CODER',
		'includes/settings.php'        => 'INFINITY CODER',
		'includes/admin.php'           => 'INFINITY CODER',
		'includes/login-appearance.php' => 'INFINITY CODER',
		'includes/login-security.php'  => 'INFINITY CODER',
		'includes/github-updater.php'  => 'INFINITY CODER',
		'includes/license.php'         => 'INFINITY CODER',
		'includes/trial.php'           => 'INFINITY CODER',
		'assets/js/admin.js'           => 'INFINITY CODER',
		'assets/css/admin.css'         => 'INFINITY CODER',
	);
}

/**
 * Vérifie l'intégrité de tous les fichiers critiques.
 *
 * @return array Liste des fichiers altérés (vide = OK).
 */
function lnf_integrity_verify() {
	$tampered = array();
	foreach ( lnf_integrity_critical_files() as $file => $needle ) {
		$path = LOGINFENNEC_DIR . $file;
		if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
			$tampered[] = $file . ' — fichier manquant';
			continue;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- vérification d'intégrité locale.
		$content = file_get_contents( $path );
		if ( false === strpos( $content, $needle ) ) {
			$tampered[] = $file . ' — signature retirée';
		}
	}
	return $tampered;
}

/**
 * Envoie les notifications de modification (e-mail + webhook).
 *
 * @param array $tampered Fichiers altérés.
 */
function lnf_integrity_notify( $tampered ) {
	if ( empty( $tampered ) ) {
		return;
	}
	$site  = home_url( '/' );
	$domain = wp_parse_url( $site, PHP_URL_HOST );
	$body  = __( 'LoginFennec Pro — fichiers modifiés détectés :', 'loginfennec' ) . "\n"
		. implode( "\n", $tampered ) . "\n\n"
		. __( 'Site :', 'loginfennec' ) . ' ' . $site . "\n"
		. __( 'Date :', 'loginfennec' ) . ' ' . gmdate( 'Y-m-d H:i:s' ) . "\n\n"
		. __( 'La signature « ∞ Infinity Coder by Derouiche Oussama » a été retirée de ces fichiers. Ceci constitue une violation de la licence GPL (article 2c).', 'loginfennec' );

	// E-mail admin du site.
	wp_mail( get_option( 'admin_email' ), '[LoginFennec] Modification détectée', $body );

	// E-mail développeur.
	$dev = defined( 'LOGINFENNEC_DEV_EMAIL' ) ? LOGINFENNEC_DEV_EMAIL : '';
	if ( '' !== $dev ) {
		wp_mail( $dev, '[LoginFennec] Tampering detected — ' . $domain, $body );
	}

	// Webhook développeur.
	$webhook = defined( 'LOGINFENNEC_TAMPER_WEBHOOK' ) ? LOGINFENNEC_TAMPER_WEBHOOK : '';
	if ( '' !== $webhook ) {
		wp_remote_post(
			$webhook,
			array(
				'timeout' => 8,
				'body'    => array(
					'site'    => $site,
					'domain'  => $domain,
					'files'   => $tampered,
					'time'    => gmdate( 'c' ),
				),
			)
		);
	}
}

/**
 * Vérifie l'intégrité au chargement de l'admin.
 * Ne s'exécute qu'une fois par session pour ne pas ralentir.
 */
function lnf_integrity_check() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Throttle : une vérification par session (2h).
	if ( get_transient( 'lnf_integrity_checked' ) ) {
		return;
	}
	set_transient( 'lnf_integrity_checked', 1, 2 * HOUR_IN_SECONDS );

	$tampered = lnf_integrity_verify();
	if ( ! empty( $tampered ) ) {
		lnf_integrity_notify( $tampered );
		add_action(
			'admin_notices',
			function () use ( $tampered ) {
				echo '<div class="notice notice-error"><p><strong>⚠️ LoginFennec Pro — Integrity Alert</strong><br>';
				echo esc_html__( 'The following files have been modified and the signature removed:', 'loginfennec' ) . '</p><ul>';
				foreach ( array_slice( $tampered, 0, 5 ) as $file ) {
					echo '<li>' . esc_html( $file ) . '</li>';
				}
				echo '</ul><p>' . esc_html__( 'The developer has been notified. Restoring the original files will resolve this warning.', 'loginfennec' ) . '</p></div>';
			}
		);
	}
}
add_action( 'admin_init', 'lnf_integrity_check' );

/**
 * Envoie une notification de modification au développeur.
 *
 * @param array $tampered Fichiers altérés.
 */
function lnf_integrity_send_notification( $tampered ) {
	lnf_integrity_notify( $tampered );
}
