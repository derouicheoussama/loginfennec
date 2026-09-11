<?php

/**
 * ∞ INFINITY CODER — création originale de Derouiche Oussama
 *
 * Plugin   : Infinity LoginShield – Login Customizer & Security
 * Auteur   : Derouiche Oussama  ·  https://www.derouicheoussama.com
 * GitHub   : https://github.com/derouicheoussama
 * Copyright © 2026 Derouiche Oussama. Tous droits réservés.
 * Licence  : GPL v2 ou ultérieure — toute copie ou modification de ce
 *            fichier DOIT conserver la présente signature et les mentions
 *            de licence et d'attribution (article 2(c) de la GPL).
 */
/**
 * Mises à jour automatiques depuis les releases GitHub.
 *
 * Cherche la dernière release du dépôt configuré et la propose dans
 * l'écran « Extensions » de WordPress. Le fichier zip doit idéalement
 * s'appeler infinity-loginshield.zip (voir .github/workflows/release.yml).
 *
 * @package InfinityCustomizer
 */

defined( 'ABSPATH' ) || exit;

class Inls_GitHub_Updater {

	const SLUG           = 'infinity-loginshield/infinity-loginshield.php';
	const CACHE_KEY      = 'inls_gh_release';
	const WPORG_CHECK_KEY = 'inls_wporg_check';

	/**
	 * Dépôt GitHub « utilisateur/depot ».
	 *
	 * @var string
	 */
	protected static $repo = '';

	/**
	 * Déclare les hooks de mise à jour. Source : GitHub tant que le plugin
	 * n'est pas hébergé sur WordPress.org (détection automatique, cache 12 h).
	 */
	public static function init() {
		$source = apply_filters( 'infinity_loginshield_update_source', self::detect_source() );
		if ( 'github' !== $source ) {
			return; // WordPress.org (ou forçage manuel) gère les mises à jour.
		}
		self::$repo = apply_filters( 'infinity_loginshield_github_repo', INFINITY_LOGINSHIELD_GITHUB_REPO );
		if ( '' === trim( (string) self::$repo ) ) {
			return;
		}

		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_source_folder' ), 10, 3 );
		add_action( 'in_plugin_update_message-' . self::SLUG, array( __CLASS__, 'update_notice' ), 10, 2 );
	}

	/**
	 * Détecte si le plugin est hébergé sur WordPress.org : si oui, le
	 * référentiel officiel doit servir les mises à jour (exigence du répertoire).
	 *
	 * @return string 'github' ou 'wordpress'.
	 */
	protected static function detect_source() {
		$cached = get_transient( self::WPORG_CHECK_KEY );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$on_wporg = false;
		if ( function_exists( 'plugins_api' ) ) {
			$info = plugins_api(
				'plugin_information',
				array(
					'slug'   => 'infinity-loginshield',
					'fields' => array(
						'download_link'  => true,
						'version'        => true,
						'sections'       => false,
						'description'    => false,
						'screenshots'    => false,
						'changelog'      => false,
						'tags'           => false,
						'banners'        => false,
						'icons'          => false,
						'rating'         => false,
						'ratings'        => false,
						'num_ratings'    => false,
						'contributors'   => false,
						'author'         => false,
						'homepage'       => false,
						'added'          => false,
						'last_updated'   => false,
						'active_installs' => false,
						'donate_link'    => false,
						'requires'       => false,
						'tested'         => false,
						'short_description' => false,
					),
				)
			);
			if ( ! is_wp_error( $info ) && ! empty( $info->download_link )
				&& false !== strpos( (string) $info->download_link, 'downloads.wordpress.org' ) ) {
				$on_wporg = true;
			}
		}

		$source = $on_wporg ? 'wordpress' : 'github';
		set_transient( self::WPORG_CHECK_KEY, $source, 12 * HOUR_IN_SECONDS );
		return $source;
	}

	/**
	 * Interroge l'API GitHub (releases/latest), avec cache de 6 h.
	 *
	 * @param bool $force Forcer le rafraîchissement du cache.
	 * @return array|false
	 */
	public static function fetch_latest_release( $force = false ) {
		if ( ! $force ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) && ! empty( $cached['version'] ) ) {
				return $cached;
			}
		}

		$parts = explode( '/', self::$repo );
		$parts = array_map( 'rawurlencode', $parts );
		$url   = 'https://api.github.com/repos/' . implode( '/', $parts ) . '/releases/latest';
		$results = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'Infinity-LoginShield-Updater/' . INFINITY_LOGINSHIELD_VERSION,
				),
			)
		);

		if ( is_wp_error( $results ) || 200 !== wp_remote_retrieve_response_code( $results ) ) {
			// Nouvel essai dans 15 minutes en cas d'échec réseau ou de quota.
			set_transient( self::CACHE_KEY, array( 'version' => INFINITY_LOGINSHIELD_VERSION ), 15 * MINUTE_IN_SECONDS );
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $results ), true );
		if ( empty( $data['tag_name'] ) ) {
			return false;
		}

		$release = array(
			'version'   => ltrim( (string) $data['tag_name'], 'vV' ),
			'name'      => isset( $data['name'] ) ? (string) $data['name'] : '',
			'changelog' => isset( $data['body'] ) ? (string) $data['body'] : '',
			'url'       => isset( $data['html_url'] ) ? (string) $data['html_url'] : '',
			'download'  => '',
		);

		// Asset zip préféré : infinity-loginshield.zip (sinon le premier zip).
		if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				$name = isset( $asset['name'] ) ? strtolower( (string) $asset['name'] ) : '';
				$url2 = isset( $asset['browser_download_url'] ) ? (string) $asset['browser_download_url'] : '';
				if ( $url2 && '.zip' === substr( $name, -4 ) ) {
					if ( 'infinity-loginshield.zip' === $name ) {
						$release['download'] = $url2;
						break;
					}
					if ( '' === $release['download'] ) {
						$release['download'] = $url2;
					}
				}
			}
		}
		// Repli : archive de source GitHub (le dossier sera renommé à l'installation).
		if ( '' === $release['download'] && ! empty( $data['zipball_url'] ) ) {
			$release['download'] = (string) $data['zipball_url'];
		}

		set_transient( self::CACHE_KEY, $release, 6 * HOUR_IN_SECONDS );
		return $release;
	}

	/**
	 * Injecte la mise à jour dans le transient des extensions.
	 *
	 * @param stdClass $transient Transient update_plugins.
	 * @return stdClass
	 */
	public static function inject_update( $transient ) {
		if ( ! is_object( $transient ) || ! isset( $transient->checked ) ) {
			return $transient;
		}
		$release = self::fetch_latest_release();
		if ( ! $release || empty( $release['version'] ) || '' === $release['download'] ) {
			return $transient;
		}

		if ( version_compare( INFINITY_LOGINSHIELD_VERSION, $release['version'], '<' ) ) {
			$transient->response[ self::SLUG ] = self::to_update_object( $release );
		} else {
			$transient->no_update[ self::SLUG ] = self::to_update_object( $release );
		}
		return $transient;
	}

	/**
	 * Construit l'objet de mise à jour.
	 *
	 * @param array $release Release GitHub.
	 * @return stdClass
	 */
	protected static function to_update_object( $release ) {
		$obj           = new stdClass();
		$obj->slug     = 'infinity-loginshield';
		$obj->plugin   = self::SLUG;
		$obj->new_version = $release['version'];
		$obj->url      = $release['url'] ? $release['url'] : 'https://github.com/' . self::$repo;
		$obj->package  = $release['download'];
		$obj->tested   = '6.8';
		$obj->requires = '5.2';
		return $obj;
	}

	/**
	 * Fiche « Voir les détails » de l'extension.
	 *
	 * @param false|object|array $result Résultat.
	 * @param string             $action Action.
	 * @param object             $args   Arguments.
	 * @return false|object
	 */
	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'infinity-loginshield' !== $args->slug ) {
			return $result;
		}
		$release = self::fetch_latest_release();
		if ( ! $release || '' === $release['download'] ) {
			return $result;
		}

		$info             = new stdClass();
		$info->name       = 'Infinity LoginShield – Login Customizer & Security';
		$info->slug       = 'infinity-loginshield';
		$info->version    = $release['version'];
		$info->author     = '<a href="https://github.com/derouicheoussama" target="_blank" rel="noopener">Derouiche Oussama</a>';
		$info->homepage   = $release['url'] ? $release['url'] : 'https://github.com/' . self::$repo;
		$info->download_link = $release['download'];
		$info->requires   = '5.2';
		$info->tested     = '6.8';
		$info->sections   = array(
			'description' => '<p>' . sprintf(
				/* translators: %s : nom du dépôt GitHub. */
				esc_html__( 'Personnalisez votre page de connexion (logo, arrière-plan, flou, opacité, styles modernes, liens, réseaux sociaux, copyright) et protégez-la contre les tentatives de mot de passe. Mises à jour via le dépôt GitHub %s.', 'infinity-loginshield' ),
				'<strong>' . esc_html( self::$repo ) . '</strong>'
			) . '</p>',
			'changelog'   => '<pre>' . esc_html( $release['changelog'] ) . '</pre>',
		);
		return $info;
	}

	/**
	 * Renomme le dossier extrait si l'archive ne s'appelle pas
	 * « infinity-loginshield » (archives de source GitHub : repo-tag).
	 *
	 * @param string     $source        Chemin source.
	 * @param string     $remote_source Chemin distant.
	 * @param WP_Upgrader $upgrader     Upgrader.
	 * @return string
	 */
	public static function fix_source_folder( $source, $remote_source, $upgrader ) {
		global $wp_filesystem;
		if ( empty( $source ) || empty( $wp_filesystem ) ) {
			return $source;
		}
		$basename = basename( untrailingslashit( $source ) );
		if ( 'infinity-loginshield' === $basename ) {
			return $source;
		}
		// Ne s'applique qu'à nos propres paquets.
		if ( 0 !== strpos( $basename, 'infinity-loginshield' ) ) {
			return $source;
		}
		$target = dirname( untrailingslashit( $source ) ) . '/infinity-loginshield';
		if ( $wp_filesystem->move( $source, $target ) ) {
			return $target;
		}
		return $source;
	}

	/**
	 * Note sous l'avis de mise à jour (lien vers les notes de version).
	 *
	 * @param array $data    Données de l'extension.
	 * @param array $release Réponse de mise à jour.
	 */
	public static function update_notice( $data, $release ) {
		if ( ! empty( $release->url ) ) {
			echo ' <a href="' . esc_url( $release->url ) . '" target="_blank" rel="noopener">'
				. esc_html__( 'Voir les notes de version sur GitHub', 'infinity-loginshield' )
				. '</a>.';
		}
	}
}
