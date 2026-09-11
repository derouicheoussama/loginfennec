<?php
/**
 * Mises à jour automatiques depuis les releases GitHub.
 *
 * Cherche la dernière release du dépôt configuré et la propose dans
 * l'écran « Extensions » de WordPress. Le fichier zip doit idéalement
 * s'appeler infinity-customizer.zip (voir .github/workflows/release.yml).
 *
 * @package InfinityCustomizer
 */

defined( 'ABSPATH' ) || exit;

class Infcl_GitHub_Updater {

	const SLUG      = 'infinity-customizer/infinity-customizer.php';
	const CACHE_KEY = 'infcl_gh_release';

	/**
	 * Dépôt GitHub « utilisateur/depot ».
	 *
	 * @var string
	 */
	protected static $repo = '';

	/**
	 * Déclare les hooks de mise à jour (source GitHub uniquement).
	 */
	public static function init() {
		$source = apply_filters( 'infinity_customizer_update_source', 'github' );
		if ( 'github' !== $source ) {
			return; // Laisser WordPress.org gérer les mises à jour.
		}
		self::$repo = apply_filters( 'infinity_customizer_github_repo', INFINITY_CUSTOMIZER_GITHUB_REPO );
		if ( '' === trim( (string) self::$repo ) ) {
			return;
		}

		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_source_folder' ), 10, 3 );
		add_action( 'in_plugin_update_message-' . self::SLUG, array( __CLASS__, 'update_notice' ), 10, 2 );
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
					'User-Agent' => 'Infinity-Customizer-Updater/' . INFINITY_CUSTOMIZER_VERSION,
				),
			)
		);

		if ( is_wp_error( $results ) || 200 !== wp_remote_retrieve_response_code( $results ) ) {
			// Nouvel essai dans 15 minutes en cas d'échec réseau ou de quota.
			set_transient( self::CACHE_KEY, array( 'version' => INFINITY_CUSTOMIZER_VERSION ), 15 * MINUTE_IN_SECONDS );
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

		// Asset zip préféré : infinity-customizer.zip (sinon le premier zip).
		if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				$name = isset( $asset['name'] ) ? strtolower( (string) $asset['name'] ) : '';
				$url2 = isset( $asset['browser_download_url'] ) ? (string) $asset['browser_download_url'] : '';
				if ( $url2 && '.zip' === substr( $name, -4 ) ) {
					if ( 'infinity-customizer.zip' === $name ) {
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

		if ( version_compare( INFINITY_CUSTOMIZER_VERSION, $release['version'], '<' ) ) {
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
		$obj->slug     = 'infinity-customizer';
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
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'infinity-customizer' !== $args->slug ) {
			return $result;
		}
		$release = self::fetch_latest_release();
		if ( ! $release || '' === $release['download'] ) {
			return $result;
		}

		$info             = new stdClass();
		$info->name       = 'Infinity Customizer – Login Customizer & Security';
		$info->slug       = 'infinity-customizer';
		$info->version    = $release['version'];
		$info->author     = '<a href="https://github.com/derouiche-oussama" target="_blank" rel="noopener">Derouiche Oussama</a>';
		$info->homepage   = $release['url'] ? $release['url'] : 'https://github.com/' . self::$repo;
		$info->download_link = $release['download'];
		$info->requires   = '5.2';
		$info->tested     = '6.8';
		$info->sections   = array(
			'description' => '<p>' . sprintf(
				/* translators: %s : nom du dépôt GitHub. */
				esc_html__( 'Personnalisez votre page de connexion (logo, arrière-plan, flou, opacité, styles modernes, liens, réseaux sociaux, copyright) et protégez-la contre les tentatives de mot de passe. Mises à jour via le dépôt GitHub %s.', 'infinity-customizer' ),
				'<strong>' . esc_html( self::$repo ) . '</strong>'
			) . '</p>',
			'changelog'   => '<pre>' . esc_html( $release['changelog'] ) . '</pre>',
		);
		return $info;
	}

	/**
	 * Renomme le dossier extrait si l'archive ne s'appelle pas
	 * « infinity-customizer » (archives de source GitHub : repo-tag).
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
		if ( 'infinity-customizer' === $basename ) {
			return $source;
		}
		// Ne s'applique qu'à nos propres paquets.
		if ( 0 !== strpos( $basename, 'infinity-customizer' ) ) {
			return $source;
		}
		$target = dirname( untrailingslashit( $source ) ) . '/infinity-customizer';
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
				. esc_html__( 'Voir les notes de version sur GitHub', 'infinity-customizer' )
				. '</a>.';
		}
	}
}
