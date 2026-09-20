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
 * Canal de mise à jour auto-hébergé (optionnel, sécurisé).
 *
 * DÉSACTIVÉ PAR DÉFAUT : une fois le plugin publié sur WordPress.org,
 * les mises à jour arrivent nativement — ce module n'est utile que pour
 * vos clients directs, avant publication ou pour une édition Pro.
 *
 * Activation (wp-config.php) :
 *   define( 'LOGINFENNEC_UPDATE_SERVER', 'https://updates.exemple.com/loginfennec/update.json' );
 *   define( 'LOGINFENNEC_LICENSE_KEY', 'XXXX-…' ); // optionnel, si le serveur lie les licences
 *
 * Sécurité :
 * - serveur en HTTPS obligatoire (https:// sinon le canal reste inactif) ;
 * - le JSON du serveur déclare un SHA-256 du zip : le fichier est
 *   téléchargé, vérifié, et l'installation est REFUSÉE si l'empreinte
 *   ne correspond pas (intégrité de l'artefact) ;
 * - cache 12 h, aucune donnée du site n'est envoyée (une clé de licence
 *   optionnelle en paramètre si définie).
 *
 * Contrat serveur (hosting statique suffisant) — voir docs/UPDATE-SERVER.md.
 *
 * @package LoginFennecPro
 *
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

class Lnf_Update_Server {

	/**
	 * Initialise le canal si la constante est définie en HTTPS.
	 */
	public static function init() {
		$server = self::server_url();
		if ( '' === $server ) {
			return;
		}
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_pre_download', array( __CLASS__, 'verify_checksum' ), 10, 4 );
	}

	/**
	 * URL du serveur de mises à jour (chaîne vide = canal désactivé).
	 *
	 * @return string
	 */
	public static function server_url() {
		$url = defined( 'LOGINFENNEC_UPDATE_SERVER' ) ? trim( (string) constant( 'LOGINFENNEC_UPDATE_SERVER' ) ) : '';
		if ( '' === $url || 0 !== strpos( $url, 'https://' ) ) {
			return ''; // Refuse tout canal non HTTPS.
		}
		$license = defined( 'LOGINFENNEC_LICENSE_KEY' ) ? trim( (string) constant( 'LOGINFENNEC_LICENSE_KEY' ) ) : '';
		if ( '' !== $license ) {
			$url = add_query_arg( 'license', rawurlencode( $license ), $url );
		}
		return $url;
	}

	/**
	 * Métadonnées du serveur (cache 12 h, silence 15 min en cas d'erreur).
	 *
	 * @return array
	 */
	public static function fetch() {
		$cached = get_transient( 'lnf_update_remote' );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$url      = self::server_url();
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_transient( 'lnf_update_remote', array(), 15 * MINUTE_IN_SECONDS );
			return array();
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['version'] ) || empty( $data['download_url'] ) ) {
			set_transient( 'lnf_update_remote', array(), 15 * MINUTE_IN_SECONDS );
			return array();
		}
		set_transient( 'lnf_update_remote', $data, 12 * HOUR_IN_SECONDS );
		return $data;
	}

	/**
	 * Injecte la mise à jour dans le transient WordPress si plus récente.
	 *
	 * @param object $transient Transient update_plugins.
	 * @return object
	 */
	public static function inject_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		$data = self::fetch();
		if ( empty( $data ) || ! version_compare( LOGINFENNEC_VERSION, (string) $data['version'], '<' ) ) {
			return $transient;
		}
		$basename                    = plugin_basename( LOGINFENNEC_FILE );
		$transient->response[ $basename ] = (object) array(
			'slug'          => 'loginfennec',
			'plugin'        => $basename,
			'new_version'   => (string) $data['version'],
			'url'           => isset( $data['url'] ) ? (string) $data['url'] : home_url( '/' ),
			'package'       => (string) $data['download_url'],
			'requires'      => isset( $data['requires'] ) ? (string) $data['requires'] : '5.2',
			'requires_php'  => isset( $data['requires_php'] ) ? (string) $data['requires_php'] : '7.2',
			'tested'        => isset( $data['tested'] ) ? (string) $data['tested'] : '',
			'last_updated'  => isset( $data['last_updated'] ) ? (string) $data['last_updated'] : '',
		);
		return $transient;
	}

	/**
	 * Fiche « Voir les détails » dans la modale WordPress.
	 *
	 * @param false|object|array $result Résultat.
	 * @param string             $action Action plugins_api.
	 * @param object             $args   Arguments.
	 * @return false|object|array
	 */
	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'loginfennec' !== $args->slug ) {
			return $result;
		}
		$data = self::fetch();
		if ( empty( $data ) ) {
			return $result;
		}
		$info                 = (object) array();
		$info->name           = isset( $data['name'] ) ? (string) $data['name'] : 'LoginFennec Pro';
		$info->slug           = 'loginfennec';
		$info->version        = (string) $data['version'];
		$info->requires       = isset( $data['requires'] ) ? (string) $data['requires'] : '5.2';
		$info->requires_php   = isset( $data['requires_php'] ) ? (string) $data['requires_php'] : '7.2';
		$info->download_link  = (string) $data['download_url'];
		$info->last_updated   = isset( $data['last_updated'] ) ? (string) $data['last_updated'] : '';
		$info->sections       = isset( $data['sections'] ) && is_array( $data['sections'] ) ? $data['sections'] : array();
		return $info;
	}

	/**
	 * Vérifie le SHA-256 du zip téléchargé avant installation.
	 * Empreinte absente → téléchargement laissé à WordPress (comportement
	 * dégradé). Empreinte différente → installation REFUSÉE.
	 *
	 * @param bool|string $reply      Chemin local ou false.
	 * @param string      $package    URL du paquet.
	 * @param object      $upgrader   Upgrader.
	 * @param array       $hook_extra Contexte.
	 * @return bool|string|WP_Error
	 */
	public static function verify_checksum( $reply, $package, $upgrader, $hook_extra ) {
		unset( $upgrader );
		if ( empty( $hook_extra['plugin'] ) || plugin_basename( LOGINFENNEC_FILE ) !== $hook_extra['plugin'] ) {
			return $reply;
		}
		$data = self::fetch();
		if ( empty( $data['checksum'] ) || empty( $data['download_url'] ) || $data['download_url'] !== $package ) {
			return $reply;
		}
		$expected = strtolower( (string) $data['checksum'] );
		if ( ! preg_match( '/^[0-9a-f]{64}$/', $expected ) ) {
			return new WP_Error( 'lnf_update_checksum', __( 'Mise à jour refusée : empreinte de sécurité invalide sur le serveur de mises à jour.', 'loginfennec' ) );
		}
		$temp = download_url( $package );
		if ( is_wp_error( $temp ) ) {
			return $temp;
		}
		$actual = hash_file( 'sha256', $temp );
		if ( ! is_string( $actual ) || ! hash_equals( $expected, $actual ) ) {
			wp_delete_file( $temp );
			return new WP_Error( 'lnf_update_checksum', __( 'Mise à jour refusée : l’empreinte SHA-256 du paquet ne correspond pas à celle publiée par le serveur.', 'loginfennec' ) );
		}
		return $temp;
	}
}
