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
 * Système d'essai : 7 jours gratuits puis verrouillage jusqu'à activation
 * d'une licence Pro. Protection anti-triche :
 * - double stockage (option + fichier uploads) pour empêcher la suppression ;
 * - détection de rollback d'horloge (si l'horloge recule → verrouillé) ;
 * - persistance après désinstallation/réinstallation via le fichier uploads.
 *
 * @package LoginFennecPro
 *
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

const LNF_TRIAL_DAYS    = 7;
const LNF_TRIAL_OPTION  = 'lnf_trial_data';
const LNF_TRIAL_FILE    = 'loginfennec-trial.json';

/**
 * Chemin du fichier d'essai persistant dans uploads.
 *
 * @return string
 */
function lnf_trial_file_path() {
	$uploads = wp_upload_dir();
	return trailingslashit( $uploads['basedir'] ) . LNF_TRIAL_FILE;
}

/**
 * Signe une chaîne pour empêcher la modification manuelle.
 *
 * @param string $data Données à signer.
 * @return string
 */
function lnf_trial_sign( $data ) {
	return hash_hmac( 'sha256', $data, wp_salt( 'auth' ) );
}

/**
 * Crée un enregistrement d'essai signé.
 *
 * @param int $start Timestamp de début.
 * @param int $last  Timestamp de dernière activité.
 * @return array Données signées.
 */
function lnf_trial_make_record( $start, $last ) {
	$record = array(
		'start' => (int) $start,
		'last'  => (int) $last,
		'site'  => home_url( '/' ),
	);
	$payload = wp_json_encode( array( 'start' => $record['start'], 'last' => $record['last'], 'site' => $record['site'] ) );
	$record['sig'] = lnf_trial_sign( $payload );
	return $record;
}

/**
 * Vérifie la signature d'un enregistrement.
 *
 * @param array $record Enregistrement.
 * @return bool
 */
function lnf_trial_verify_record( $record ) {
	if ( empty( $record['sig'] ) || ! isset( $record['start'], $record['last'], $record['site'] ) ) {
		return false;
	}
	$payload = wp_json_encode( array( 'start' => (int) $record['start'], 'last' => (int) $record['last'], 'site' => $record['site'] ) );
	return lnf_trial_sign( $payload ) === $record['sig'];
}

/**
 * Lit le fichier d'essai persistant.
 *
 * @return array|false
 */
function lnf_trial_read_file() {
	$file = lnf_trial_file_path();
	if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
		return false;
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lecture locale du fichier d'essai.
	$content = file_get_contents( $file );
	$data    = json_decode( $content, true );
	if ( ! is_array( $data ) || ! lnf_trial_verify_record( $data ) ) {
		return false;
	}
	return $data;
}

/**
 * Écrit le fichier d'essai persistant.
 *
 * @param array $record Données signées.
 */
function lnf_trial_write_file( $record ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_put_contents_file_put_contents -- écriture locale du fichier d'essai.
	file_put_contents(
		lnf_trial_file_path(),
		wp_json_encode( $record, JSON_PRETTY_PRINT ),
		LOCK_EX
	);
}

/**
 * Démarre (ou reprend) le suivi d'essai.
 * Si un fichier d'essai persistant existe (après réinstallation),
 * on reprend la date d'origine — pas de nouveau délai.
 */
function lnf_trial_init() {
	$file_data = lnf_trial_read_file();
	$option    = get_option( LNF_TRIAL_OPTION, null );

	$best_start = null;
	$best_last  = null;

	// Prend la date la plus ancienne parmi toutes les sources (anti-réinstallation).
	if ( is_array( $file_data ) && isset( $file_data['start'] ) ) {
		$best_start = (int) $file_data['start'];
		$best_last  = (int) $file_data['last'];
	}
	if ( is_array( $option ) && isset( $option['start'] ) ) {
		$opt_start = (int) $option['start'];
		$opt_last  = (int) $option['last'];
		if ( null === $best_start || $opt_start < $best_start ) {
			$best_start = $opt_start;
			$best_last  = $opt_last;
		}
	}

	$now = time();
	if ( null === $best_start ) {
		// Première installation : démarre l'essai.
		$best_start = $now;
		$best_last  = $now;
	}

	// Anti-rollback : si l'horloge a reculé par rapport à la dernière activité.
	if ( $now < $best_last ) {
		$best_last = $now; // Réaligne mais garde le start d'origine.
		$best_start = min( $best_start, $now - ( LNF_TRIAL_DAYS * DAY_IN_SECONDS ) );
	}

	$record = lnf_trial_make_record( $best_start, $best_last );

	// Sauvegarde dans les deux stockages.
	update_option( LNF_TRIAL_OPTION, $record, false );
	lnf_trial_write_file( $record );
}

/**
 * Retourne les données d'essai vérifiées et à jour.
 *
 * @return array { start, last, days_elapsed, days_left, expired, locked }
 */
function lnf_trial_status() {
	$file_data = lnf_trial_read_file();
	$option    = get_option( LNF_TRIAL_OPTION, array() );

	$start = null;
	$last  = null;

	// Prend la date de début la plus ancienne (anti-réinstallation).
	if ( is_array( $file_data ) && isset( $file_data['start'] ) && lnf_trial_verify_record( $file_data ) ) {
		$start = (int) $file_data['start'];
		$last  = (int) $file_data['last'];
	}
	if ( is_array( $option ) && isset( $option['start'] ) && lnf_trial_verify_record( $option ) ) {
		$opt_start = (int) $option['start'];
		$opt_last  = (int) $option['last'];
		if ( null === $start || $opt_start < $start ) {
			$start = $opt_start;
			$last  = $opt_last;
		}
	}

	$now = time();
	if ( null === $start ) {
		// Pas de données d'essai : démarre maintenant.
		lnf_trial_init();
		$start = $now;
		$last  = $now;
	}

	// Anti-rollback : si l'horloge recule par rapport à last_seen.
	if ( $now < $last ) {
		// Horloge reculée : on force l'expiration.
		return array(
			'start'       => $start,
			'last'        => $last,
			'days_elapsed' => LNF_TRIAL_DAYS + 1,
			'days_left'   => 0,
			'expired'     => true,
			'locked'      => true,
			'rollback'    => true,
		);
	}

	$elapsed_seconds = $now - $start;
	$days_elapsed    = (int) floor( $elapsed_seconds / DAY_IN_SECONDS );
	$days_left       = max( 0, LNF_TRIAL_DAYS - $days_elapsed );
	$expired         = ( $days_elapsed >= LNF_TRIAL_DAYS );

	return array(
		'start'       => $start,
		'last'        => $last,
		'days_elapsed' => $days_elapsed,
		'days_left'   => $days_left,
		'expired'     => $expired,
		'locked'      => $expired,
		'rollback'    => false,
	);
}

/**
 * Le plugin est-il verrouillé (essai expiré sans licence Pro) ?
 *
 * @return bool
 */
function lnf_trial_is_locked() {
	if ( lnf_is_pro() ) {
		return false; // Licence Pro = tout débloqué, pas d'essai.
	}
	$status = lnf_trial_status();
	return ! empty( $status['locked'] );
}

/**
 * Marque la dernière activité (anti-rollback).
 * Throttlé à une heure : sans cela, chaque page d'admin déclenche une
 * écriture en base ET une écriture fichier — inutile et coûteux.
 */
function lnf_trial_touch() {
	$option = get_option( LNF_TRIAL_OPTION, array() );
	if ( is_array( $option ) && isset( $option['last'] ) && lnf_trial_verify_record( $option )
		&& ( time() - (int) $option['last'] ) < HOUR_IN_SECONDS ) {
		return;
	}
	$status = lnf_trial_status();
	$record = lnf_trial_make_record( $status['start'], time() );
	update_option( LNF_TRIAL_OPTION, $record, false );
	lnf_trial_write_file( $record );
}

/**
 * Réinitialise complètement l'essai (usage développeur uniquement, via filtre).
 */
function lnf_trial_force_reset() {
	delete_option( LNF_TRIAL_OPTION );
	$file = lnf_trial_file_path();
	if ( file_exists( $file ) ) {
		wp_delete_file( $file );
	}
	lnf_trial_init();
}
