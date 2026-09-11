<?php
/**
 * Sécurité : limitation des tentatives de connexion.
 *
 * Bloque temporairement une adresse IP (et un identifiant) après
 * N échecs de mot de passe, et affiche les messages correspondants.
 *
 * @package InfinityCustomizer
 */

defined( 'ABSPATH' ) || exit;

class Infcl_Login_Security {

	const OPT = 'infcl_login_attempts';

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
		add_filter( 'authenticate', array( __CLASS__, 'authenticate' ), 5, 3 );
		add_action( 'wp_login_failed', array( __CLASS__, 'register_failure' ) );
		add_action( 'wp_login', array( __CLASS__, 'clear_for' ), 10, 2 );
		add_filter( 'login_message', array( __CLASS__, 'remaining_message' ) );
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
		$s = infcl_settings();
		if ( empty( $s['sec_enable'] ) ) {
			return $user;
		}

		foreach ( self::keys_for( $username ) as $key ) {
			$remaining = self::locked_remaining( $key );
			if ( $remaining > 0 ) {
				self::$lock_triggered = true;
				$message = sprintf( esc_html( $s['sec_lock_message'] ), max( 1, $remaining ) );
				return new WP_Error( 'infcl_locked', $message );
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
		$s = infcl_settings();
		if ( empty( $s['sec_enable'] ) ) {
			return;
		}

		$data = self::get_data();
		$now  = time();

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
		$s = infcl_settings();
		if ( empty( $s['sec_enable'] ) || self::$lock_triggered || self::$last_fail_count < 1 ) {
			return $message;
		}
		$remaining = max( 1, (int) $s['sec_max_attempts'] - self::$last_fail_count );
		$message  .= sprintf(
			'<div class="message infcl-attempts" style="margin-top:10px">%s</div>',
			esc_html(
				sprintf(
					/* translators: %d : nombre de tentatives restantes. */
					_n(
						'Attention : il vous reste %d tentative avant le blocage temporaire.',
						'Attention : il vous reste %d tentatives avant le blocage temporaire.',
						$remaining,
						'infinity-customizer'
					),
					$remaining
				)
			)
		);
		return $message;
	}
}
