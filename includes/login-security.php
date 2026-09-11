<?php

/**
 * ∞ INFINITY CODER — création originale de Derouiche Oussama
 *
 * Plugin   : LoginFence Pro – Login Customizer & Security
 * Auteur   : Derouiche Oussama  ·  https://www.derouicheoussama.com
 * GitHub   : https://github.com/derouicheoussama
 * Copyright © 2026 Derouiche Oussama. Tous droits réservés.
 * Licence  : GPL v2 ou ultérieure — toute copie ou modification de ce
 *            fichier DOIT conserver la présente signature et les mentions
 *            de licence et d'attribution (article 2(c) de la GPL).
 */
/**
 * Sécurité : limitation des tentatives de connexion.
 *
 * Bloque temporairement une adresse IP (et un identifiant) après
 * N échecs de mot de passe, et affiche les messages correspondants.
 *
 * @package LoginFencePro
 */

defined( 'ABSPATH' ) || exit;

class Lnf_Login_Security {

	const OPT = 'lnf_login_attempts';

	const LOG_OPT = 'lnf_security_log';
	const LOG_CAP = 50;

	/**
	 * Vrai si une demande a été bloquée lors de la requête courante
	 * (le message d'erreur verrouillé ne doit pas être « génériquisé »).
	 *
	 * @var bool
	 */
	public static $lock_triggered = false;

	/**
	 * Nombre d'échecs enregistrés sur la requête courante (pour l'astuce
	 * « tentatives restantes »).
	 *
	 * @var int
	 */
	protected static $last_fail_count = 0;

	/**
	 * Déclare les hooks.
	 */
	public static function init() {
		add_filter( 'authenticate', array( __CLASS__, 'check_honeypot' ), 1, 3 );
		add_filter( 'authenticate', array( __CLASS__, 'authenticate' ), 5, 3 );
		add_action( 'wp_login_failed', array( __CLASS__, 'register_failure' ) );
		add_action( 'wp_login', array( __CLASS__, 'clear_for' ), 10, 2 );
		add_filter( 'login_message', array( __CLASS__, 'remaining_message' ) );
		add_filter( 'xmlrpc_enabled', array( __CLASS__, 'maybe_disable_xmlrpc' ) );
		add_action( 'init', array( __CLASS__, 'harden_author_scans' ) );
	}

	/**
	 * Honeypot : champ caché rempli uniquement par les robots.
	 *
	 * @param WP_User|WP_Error|null $user     Utilisateur ou erreur.
	 * @param string                $username Identifiant.
	 * @param string                $password Mot de passe.
	 * @return WP_User|WP_Error
	 */
	public static function check_honeypot( $user, $username, $password ) {
		$s = lnf_settings();
		if ( empty( $s['sec_honeypot'] ) ) {
			return $user;
		}
		$trap = isset( $_POST['lnf_hp'] ) ? trim( wp_unslash( $_POST['lnf_hp'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- vérification anti-bot publique.
		if ( '' === $trap ) {
			return $user;
		}
		self::log_event( 'blocked', $username );
		return new WP_Error(
			'lnf_honeypot',
			'<strong>' . esc_html__( 'Erreur', 'loginfence' ) . '</strong> : ' . esc_html__( 'requête refusée par la protection anti-robots.', 'loginfence' )
		);
	}

	/**
	 * Active la protection contre le balayage des auteurs (visiteurs non connectés).
	 */
	public static function harden_author_scans() {
		$s = lnf_settings();
		if ( empty( $s['sec_disable_authors'] ) ) {
			return;
		}
		add_filter( 'redirect_canonical', array( __CLASS__, 'block_author_scan' ) );
		add_filter( 'rest_endpoints', array( __CLASS__, 'hide_rest_users' ) );
	}

	/**
	 * ?author=N est redirigé vers l'accueil au lieu de révéler l'identifiant.
	 *
	 * @param string $redirect_url URL de redirection canonique.
	 * @return string
	 */
	public static function block_author_scan( $redirect_url ) {
		if ( isset( $_GET['author'] ) && ! is_user_logged_in() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- blocage de balayage public.
			return home_url( '/' );
		}
		return $redirect_url;
	}

	/**
	 * Masque l'endpoint REST des utilisateurs pour les non-connectés.
	 *
	 * @param array $endpoints Routes REST.
	 * @return array
	 */
	public static function hide_rest_users( $endpoints ) {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( false !== strpos( $route, '/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}

	/**
	 * Désactive XML-RPC si l'option de durcissement est active.
	 *
	 * @param bool $enabled État courant.
	 * @return bool
	 */
	public static function maybe_disable_xmlrpc( $enabled ) {
		$s = lnf_settings();
		return empty( $s['sec_disable_xmlrpc'] ) ? $enabled : false;
	}

	/**
	 * Ajoute une entrée au journal de sécurité (50 dernières).
	 *
	 * @param string $action   fail | blocked | login.
	 * @param string $username Identifiant concerné.
	 */
	public static function log_event( $action, $username = '' ) {
		$log = get_option( self::LOG_OPT, array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		array_unshift(
			$log,
			array(
				't' => time(),
				'ip' => self::client_ip(),
				'u'  => sanitize_user( (string) $username, true ),
				'a'  => sanitize_key( $action ),
			)
		);
		if ( count( $log ) > self::LOG_CAP ) {
			$log = array_slice( $log, 0, self::LOG_CAP );
		}
		update_option( self::LOG_OPT, $log, false );
	}

	/**
	 * Journal de sécurité (du plus récent au plus ancien).
	 *
	 * @return array
	 */
	public static function get_log() {
		$log = get_option( self::LOG_OPT, array() );
		return is_array( $log ) ? $log : array();
	}

	/**
	 * Vide le journal de sécurité.
	 */
	public static function purge_log() {
		delete_option( self::LOG_OPT );
	}

	/**
	 * Statistiques de sécurité pour le tableau de bord.
	 *
	 * @return array { logins7, blocked7, fails24, locks }
	 */
	public static function get_stats() {
		$now   = time();
		$stats = array(
			'logins7'  => 0,
			'blocked7' => 0,
			'fails24'  => 0,
			'locks'    => 0,
		);

		foreach ( self::get_log() as $event ) {
			$age = $now - (int) $event['t'];
			if ( 'login' === $event['a'] && $age <= 7 * DAY_IN_SECONDS ) {
				$stats['logins7']++;
			}
			if ( 'blocked' === $event['a'] && $age <= 7 * DAY_IN_SECONDS ) {
				$stats['blocked7']++;
			}
			if ( 'failed' === $event['a'] && $age <= DAY_IN_SECONDS ) {
				$stats['fails24']++;
			}
		}

		$attempts = get_option( self::OPT, array() );
		if ( is_array( $attempts ) ) {
			foreach ( $attempts as $row ) {
				if ( ! empty( $row['u'] ) && (int) $row['u'] > $now ) {
					$stats['locks']++;
				}
			}
		}
		return $stats;
	}

	/**
	 * Adresse IP du client (REMOTE_ADDR uniquement : fiable sans configuration
	 * de proxy; voir FAQ du readme pour X-Forwarded-For).
	 *
	 * @return string
	 */
	protected static function client_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	/**
	 * Clés de suivi (IP + identifiant) pour un échec.
	 *
	 * @param string $username Identifiant tenté.
	 * @return array
	 */
	protected static function keys_for( $username ) {
		$keys = array();
		$ip   = self::client_ip();
		if ( $ip ) {
			$keys[] = 'ip:' . md5( $ip );
		}
		$username = sanitize_user( (string) $username, true );
		if ( $username ) {
			$keys[] = 'user:' . md5( strtolower( $username ) );
		}
		return $keys;
	}

	/**
	 * Données de suivi.
	 *
	 * @return array
	 */
	protected static function get_data() {
		$data = get_option( self::OPT, array() );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Sauvegarde les données de suivi (sans autoload).
	 *
	 * @param array $data Données.
	 */
	protected static function save_data( $data ) {
		// Purge : entrées de plus de 24 h.
		$now = time();
		foreach ( $data as $key => $row ) {
			if ( empty( $row['t'] ) || ( $now - (int) $row['t'] ) > DAY_IN_SECONDS ) {
				unset( $data[ $key ] );
			}
		}
		// Garde-fou de taille : 2000 entrées maximum.
		if ( count( $data ) > 2000 ) {
			uasort( $data, array( __CLASS__, 'sort_by_time' ) );
			$data = array_slice( $data, -2000, null, true );
		}
		update_option( self::OPT, $data, false );
	}

	/**
	 * Tri chronologique croissant.
	 *
	 * @param array $a Ligne A.
	 * @param array $b Ligne B.
	 * @return int
	 */
	protected static function sort_by_time( $a, $b ) {
		$ta = isset( $a['t'] ) ? (int) $a['t'] : 0;
		$tb = isset( $b['t'] ) ? (int) $b['t'] : 0;
		return $ta <=> $tb;
	}

	/**
	 * Minutes restantes de verrouillage pour une clé (0 si aucune).
	 *
	 * @param string $key Clé.
	 * @return int
	 */
	protected static function locked_remaining( $key ) {
		$data = self::get_data();
		if ( empty( $data[ $key ]['u'] ) ) {
			return 0;
		}
		$remaining = (int) $data[ $key ]['u'] - time();
		return ( $remaining > 0 ) ? (int) ceil( $remaining / MINUTE_IN_SECONDS ) : 0;
	}

	/**
	 * Vérifie si une IP est dans la liste blanche (exempte de verrouillage).
	 * Une entrée peut finir par « * » (ex. 192.168.1.*).
	 *
	 * @param string $ip IP à tester (vide = IP courante).
	 * @return bool
	 */
	public static function is_whitelisted( $ip = '' ) {
		$ip  = '' !== $ip ? $ip : self::client_ip();
		$s   = lnf_settings();
		$raw = trim( (string) $s['sec_whitelist'] );
		if ( '' === $raw || '' === $ip ) {
			return false;
		}
		foreach ( preg_split( '/\s*,\s*|\s*\n\s*/', $raw ) as $allowed ) {
			$allowed = trim( (string) $allowed );
			if ( '' === $allowed ) {
				continue;
			}
			if ( '*' === substr( $allowed, -1 ) ) {
				if ( 0 === strncmp( $ip, rtrim( $allowed, '*' ), strlen( rtrim( $allowed, '*' ) ) ) ) {
					return true;
				}
			} elseif ( $allowed === $ip ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Bloque l'authentification pendant un verrouillage.
	 *
	 * @param WP_User|WP_Error|null $user     Utilisateur ou erreur.
	 * @param string                $username Identifiant.
	 * @param string                $password Mot de passe.
	 * @return WP_User|WP_Error
	 */
	public static function authenticate( $user, $username, $password ) {
		if ( empty( $username ) ) {
			return $user;
		}
		if ( self::is_whitelisted() ) {
			return $user;
		}
		$s = lnf_settings();
		if ( empty( $s['sec_enable'] ) ) {
			return $user;
		}

		foreach ( self::keys_for( $username ) as $key ) {
			$remaining = self::locked_remaining( $key );
			if ( $remaining > 0 ) {
				self::$lock_triggered = true;
				$message = sprintf( esc_html( $s['sec_lock_message'] ), max( 1, $remaining ) );
				return new WP_Error( 'lnf_locked', $message );
			}
		}
		return $user;
	}

	/**
	 * Enregistre un échec de connexion.
	 *
	 * @param string $username Identifiant tenté.
	 */
	public static function register_failure( $username ) {
		$s = lnf_settings();
		if ( empty( $s['sec_enable'] ) ) {
			return;
		}
		if ( self::is_whitelisted() ) {
			return; // IP de confiance : jamais comptée, jamais verrouillée.
		}

		$data = self::get_data();
		$now  = time();

		self::log_event( 'failed', $username );

		foreach ( self::keys_for( $username ) as $key ) {
			// Un verrouillage en cours n'est pas prolongé.
			if ( ! empty( $data[ $key ]['u'] ) && (int) $data[ $key ]['u'] > $now ) {
				continue;
			}
			if ( empty( $data[ $key ] ) ) {
				$data[ $key ] = array( 'c' => 0, 't' => $now, 'u' => 0 );
			}
			$data[ $key ]['c'] = (int) $data[ $key ]['c'] + 1;
			if ( $data[ $key ]['c'] >= (int) $s['sec_max_attempts'] ) {
				$data[ $key ]['u'] = $now + ( (int) $s['sec_lockout_minutes'] * MINUTE_IN_SECONDS );
				$data[ $key ]['c'] = 0;
				self::log_event( 'blocked', $username );
			}
			self::$last_fail_count = max( self::$last_fail_count, (int) $data[ $key ]['c'] );
		}

		self::save_data( $data );
	}

	/**
	 * Réinitialise le suivi après une connexion réussie.
	 *
	 * @param string $username Identifiant.
	 */
	public static function clear_for( $username ) {
		self::log_event( 'login', $username );
		$data = self::get_data();
		foreach ( self::keys_for( $username ) as $key ) {
			unset( $data[ $key ] );
		}
		self::save_data( $data );
	}

	/**
	 * Affiche le nombre de tentatives restantes après un échec.
	 *
	 * @param string $message Message courant.
	 * @return string
	 */
	public static function remaining_message( $message ) {
		$s = lnf_settings();
		if ( empty( $s['sec_enable'] ) || self::$lock_triggered || self::$last_fail_count < 1 ) {
			return $message;
		}
		$remaining = max( 1, (int) $s['sec_max_attempts'] - self::$last_fail_count );
		$message  .= sprintf(
			'<div class="message lnf-attempts" style="margin-top:10px">%s</div>',
			esc_html(
				sprintf(
					/* translators: %d : nombre de tentatives restantes. */
					_n(
						'Attention : il vous reste %d tentative avant le blocage temporaire.',
						'Attention : il vous reste %d tentatives avant le blocage temporaire.',
						$remaining,
						'loginfence'
					),
					$remaining
				)
			)
		);
		return $message;
	}
}
