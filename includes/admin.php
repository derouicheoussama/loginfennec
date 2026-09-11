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
 * Dashboard d'administration : menu, onglets, aperçu en direct,
 * page « À propos » et bouton de don.
 *
 * @package LoginFencePro
 */

defined( 'ABSPATH' ) || exit;

class Lnf_Admin {

	/**
	 * Déclare les hooks d'administration.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_installer_redirect' ), 1 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_lnf_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_lnf_wizard', array( __CLASS__, 'wizard_save' ) );
		add_action( 'admin_post_lnf_reset', array( __CLASS__, 'reset' ) );
		add_action( 'admin_post_lnf_export', array( __CLASS__, 'export_settings' ) );
		add_action( 'admin_post_lnf_import', array( __CLASS__, 'import_settings' ) );
		add_action( 'admin_post_lnf_export_log', array( __CLASS__, 'export_log_csv' ) );
		add_action( 'admin_post_lnf_purge_cache', array( __CLASS__, 'purge_cache' ) );
		add_action( 'admin_post_lnf_dismiss_review', array( __CLASS__, 'dismiss_review' ) );
		add_action( 'wp_ajax_lnf_preview_css', array( __CLASS__, 'ajax_preview_css' ) );
		add_action( 'wp_ajax_lnf_check_updates', array( __CLASS__, 'ajax_check_updates' ) );
		add_action( 'wp_ajax_lnf_purge_log', array( __CLASS__, 'ajax_purge_log' ) );
		add_action( 'wp_ajax_lnf_enable_recommended', array( __CLASS__, 'ajax_enable_recommended' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_filter( 'admin_footer_text', array( __CLASS__, 'footer_signature' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( LOGINFENCE_FILE ), array( __CLASS__, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Signature « Infinity Coder » en pied de page admin,
	 * uniquement sur les pages du plugin.
	 *
	 * @param string $text Texte par défaut.
	 * @return string
	 */
	public static function footer_signature( $text ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && false !== strpos( (string) $screen->id, 'loginfence' ) ) {
			return '∞ <strong>Infinity Coder</strong> — conçu par Derouiche Oussama · '
				. '<a href="https://www.derouicheoussama.com" target="_blank" rel="noopener noreferrer">derouicheoussama.com</a>';
		}
		return $text;
	}

	/**
	 * Menu : personnalisation, page Pro et installateur (page cachée).
	 */
	public static function menu() {
		add_menu_page(
			__( 'LoginFence Pro', 'loginfence' ),
			__( 'LoginFence Pro', 'loginfence' ),
			'manage_options',
			'loginfence',
			array( __CLASS__, 'render_page' ),
			'dashicons-admin-customizer',
			3
		);
		add_submenu_page(
			'loginfence',
			__( 'Personnalisation de la connexion', 'loginfence' ),
			__( 'Personnalisation', 'loginfence' ),
			'manage_options',
			'loginfence',
			array( __CLASS__, 'render_page' )
		);
		add_submenu_page(
			'loginfence',
			__( 'Passer en Pro', 'loginfence' ),
			__( 'Passer en Pro', 'loginfence' ),
			'manage_options',
			'loginfence-pro',
			array( __CLASS__, 'render_pro' )
		);
		add_submenu_page(
			'loginfence',
			__( 'À propos d’LoginFence Pro', 'loginfence' ),
			__( 'À propos', 'loginfence' ),
			'manage_options',
			'loginfence-about',
			array( __CLASS__, 'render_about' )
		);
		add_submenu_page(
			null,
			__( 'Bienvenue — LoginFence Pro', 'loginfence' ),
			__( 'Installateur', 'loginfence' ),
			'manage_options',
			'loginfence-installer',
			array( __CLASS__, 'render_installer' )
		);
	}

	/**
	 * À l'activation (ou après une mise à jour majeure) : ouvre
	 * l'installateur personnalisé une seule fois.
	 */
	public static function maybe_installer_redirect() {
		if ( ! get_option( 'lnf_pending_installer' ) ) {
			return;
		}
		if ( wp_doing_ajax() || wp_doing_cron() || wp_is_json_request() || defined( 'IFRAME_REQUEST' ) || defined( 'WP_CLI' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_GET['activate-multi'] ) ) { // Activation en masse : pas de redirection.
			return;
		}
		delete_option( 'lnf_pending_installer' );
		wp_safe_redirect( admin_url( 'admin.php?page=loginfence-installer' ) );
		exit;
	}

	/**
	 * Charge les assets sur les pages du plugin.
	 *
	 * @param string $hook Page courante.
	 */
	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'loginfence' ) ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();

		// Cache-busting : la version intègre la date de modification des fichiers.
		$css_ver = LOGINFENCE_VERSION;
		$js_ver  = LOGINFENCE_VERSION;
		$css_file = LOGINFENCE_DIR . 'assets/css/admin.css';
		$js_file  = LOGINFENCE_DIR . 'assets/js/admin.js';
		if ( file_exists( $css_file ) ) {
			$css_ver .= '.' . (string) filemtime( $css_file );
		}
		if ( file_exists( $js_file ) ) {
			$js_ver .= '.' . (string) filemtime( $js_file );
		}

		wp_enqueue_style(
			'lnf-admin',
			LOGINFENCE_URL . 'assets/css/admin.css',
			array(),
			$css_ver
		);
		wp_enqueue_script(
			'lnf-admin',
			LOGINFENCE_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			$js_ver,
			true
		);
		wp_localize_script(
			'lnf-admin',
			'LNF_ADMIN',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'lnf_admin' ),
				'previewAction' => 'lnf_preview_css',
				'sitename'      => get_bloginfo( 'name' ),
			)
		);
	}

	/**
	 * Enregistre les réglages.
	 */
	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'loginfence' ) );
		}
		check_admin_referer( 'lnf_save', 'lnf_nonce' );

		$input = isset( $_POST['lnf'] ) && is_array( $_POST['lnf'] ) ? wp_unslash( $_POST['lnf'] ) : array();
		update_option( LOGINFENCE_OPTION, lnf_sanitize_settings( $input, null ), 'yes' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'loginfence',
					'lnf-saved' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Réinitialise les réglages.
	 */
	public static function reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'loginfence' ) );
		}
		check_admin_referer( 'lnf_save', 'lnf_nonce' );

		delete_option( LOGINFENCE_OPTION );
		add_option( LOGINFENCE_OPTION, lnf_get_defaults(), '', 'yes' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'loginfence',
					'lnf-reset' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Aperçu en direct : renvoie le CSS calculé depuis les champs du formulaire.
	 */
	public static function ajax_preview_css() {
		check_ajax_referer( 'lnf_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		$input   = isset( $_POST['lnf'] ) && is_array( $_POST['lnf'] ) ? wp_unslash( $_POST['lnf'] ) : array();
		$partial = lnf_sanitize_settings( $input, lnf_settings() );
		wp_send_json_success( array( 'css' => lnf_build_login_css( $partial ) ) );
	}

	/**
	 * Enregistre les choix de l'installateur (assistant de bienvenue).
	 */
	public static function wizard_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'loginfence' ) );
		}
		check_admin_referer( 'lnf_wizard', 'lnf_wizard_nonce' );

		$base   = lnf_settings();
		$input  = isset( $_POST['lnf'] ) && is_array( $_POST['lnf'] ) ? wp_unslash( $_POST['lnf'] ) : array();
		update_option( LOGINFENCE_OPTION, lnf_sanitize_settings( $input, $base ), 'yes' );
		delete_option( 'lnf_pending_installer' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'loginfence',
					'lnf-welcome' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Vérifie les mises à jour à la demande (interroge GitHub immédiatement).
	 */
	public static function ajax_check_updates() {
		check_ajax_referer( 'lnf_admin', 'nonce' );
		if ( ! current_user_can( 'update_plugins' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$release = Lnf_GitHub_Updater::fetch_latest_release( true );
		if ( ! $release || empty( $release['version'] ) || '' === $release['download'] ) {
			wp_send_json_error(
				array( 'message' => __( 'Impossible de joindre GitHub pour le moment. Réessayez plus tard.', 'loginfence' ) )
			);
		}

		if ( version_compare( LOGINFENCE_VERSION, $release['version'], '>=' ) ) {
			wp_send_json_success(
				array(
					'status'  => 'up_to_date',
					'version' => LOGINFENCE_VERSION,
				)
			);
		}

		// Force la reconstruction du transient puis prépare le lien de mise à jour.
		if ( function_exists( 'wp_update_plugins' ) ) {
			wp_update_plugins();
		}
		$basename   = plugin_basename( LOGINFENCE_FILE );
		$update_url = wp_nonce_url(
			self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . rawurlencode( $basename ) ),
			'upgrade-plugin_' . $basename
		);

		wp_send_json_success(
			array(
				'status'  => 'available',
				'version' => $release['version'],
				'url'     => $update_url,
			)
		);
	}

	/**
	 * Vide le journal de sécurité.
	 */
	public static function ajax_purge_log() {
		check_ajax_referer( 'lnf_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		Lnf_Login_Security::purge_log();
		wp_send_json_success();
	}

	/**
	 * Exporte les réglages en fichier JSON.
	 */
	public static function export_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'loginfence' ) );
		}
		check_admin_referer( 'lnf_export' );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=loginfence-settings-' . gmdate( 'Ymd-Hi' ) . '.json' );
		echo wp_json_encode( lnf_settings(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * Importe des réglages depuis un fichier JSON.
	 */
	public static function import_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'loginfence' ) );
		}
		check_admin_referer( 'lnf_import', 'lnf_import_nonce' );

		$redirect_ok = add_query_arg(
			array(
				'page'          => 'loginfence',
				'lnf-imported' => 1,
			),
			admin_url( 'admin.php' )
		);
		$redirect_ko = add_query_arg(
			array(
				'page'           => 'loginfence',
				'lnf-import-error' => 1,
			),
			admin_url( 'admin.php' )
		);

		if ( empty( $_FILES['lnf_import_file'] ) || ! isset( $_FILES['lnf_import_file']['error'] ) || UPLOAD_ERR_OK !== (int) $_FILES['lnf_import_file']['error'] ) {
			wp_safe_redirect( $redirect_ko );
			exit;
		}
		$file     = $_FILES['lnf_import_file'];
		$filesize = isset( $file['size'] ) ? (int) $file['size'] : 0;
		if ( $filesize < 2 || $filesize > MB_IN_BYTES || ! is_uploaded_file( $file['tmp_name'] ) ) {
			wp_safe_redirect( $redirect_ko );
			exit;
		}

		$content = (string) file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichier téléversé temporaire.
		$data    = json_decode( $content, true );
		if ( ! is_array( $data ) ) {
			wp_safe_redirect( $redirect_ko );
			exit;
		}

		update_option( LOGINFENCE_OPTION, lnf_sanitize_settings( $data, null ), 'yes' );
		wp_safe_redirect( $redirect_ok );
		exit;
	}

	/**
	 * Vide le cache du plugin (transients de mises à jour et de vérifications)
	 * et rafraîchit les assets côté navigateur.
	 */
	public static function purge_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'loginfence' ) );
		}
		check_admin_referer( 'lnf_purge_cache' );

		delete_transient( 'lnf_gh_release' );
		delete_transient( 'lnf_wporg_check' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'loginfence',
					'lnf-cache' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Exporte le journal de sécurité en CSV.
	 */
	public static function export_log_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'loginfence' ) );
		}
		check_admin_referer( 'lnf_export_log' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=loginfence-journal-' . gmdate( 'Ymd-Hi' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		if ( $out ) {
			fputcsv( $out, array( 'date_utc', 'ip', 'username', 'action' ) );
			foreach ( Lnf_Login_Security::get_log() as $event ) {
				fputcsv(
					$out,
					array(
						gmdate( 'Y-m-d H:i:s', (int) $event['t'] ),
						$event['ip'],
						$event['u'],
						$event['a'],
					)
				);
			}
			fclose( $out );
		}
		exit;
	}

	/**
	 * Active le pack de sécurité recommandé (score 5/5) en un clic.
	 */
	public static function ajax_enable_recommended() {
		check_ajax_referer( 'lnf_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		$s = lnf_settings();
		foreach ( array( 'sec_enable', 'sec_honeypot', 'sec_disable_authors', 'sec_disable_xmlrpc', 'sec_generic_error' ) as $key ) {
			$s[ $key ] = true;
		}
		update_option( LOGINFENCE_OPTION, $s, 'yes' );
		wp_send_json_success();
	}

	/**
	 * Enregistre la fermeture de la demande d'avis.
	 */
	public static function dismiss_review() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'loginfence' ) );
		}
		check_admin_referer( 'lnf_dismiss_review' );
		update_option( 'lnf_review_dismissed', 1, false );
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=loginfence' ) );
		exit;
	}

	/**
	 * Lien « Personnaliser » sur la ligne du plugin (liste des extensions).
	 *
	 * @param array $links Liens existants.
	 * @return array
	 */
	public static function plugin_action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=loginfence' ) ) . '">'
			. esc_html__( 'Personnaliser', 'loginfence' ) . '</a>'
		);
		return $links;
	}

	/**
	 * Métadonnées de ligne (GitHub, À propos, don).
	 *
	 * @param array  $meta Liens existants.
	 * @param string $file Fichier du plugin de la ligne.
	 * @return array
	 */
	public static function plugin_row_meta( $meta, $file ) {
		if ( plugin_basename( LOGINFENCE_FILE ) !== $file ) {
			return $meta;
		}
		$meta[] = '<a href="https://github.com/derouicheoussama/loginfence" target="_blank" rel="noopener noreferrer">GitHub</a>';
		$meta[] = '<a href="' . esc_url( admin_url( 'admin.php?page=loginfence-about' ) ) . '">' . esc_html__( 'À propos & don', 'loginfence' ) . '</a>';
		return $meta;
	}

	/**
	 * Notices de confirmation.
	 */
	public static function notices() {
		if ( ! isset( $_GET['page'] ) ) {
			return;
		}
		if ( 'loginfence' === $_GET['page'] && isset( $_GET['lnf-welcome'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>🎉 ' . esc_html__( 'Bienvenue dans LoginFence Pro !', 'loginfence' ) . '</strong> ' . esc_html__( 'Votre page de connexion est prête — explorez les onglets pour la personnaliser.', 'loginfence' ) . '</p></div>';
		}
		if ( 'loginfence' === $_GET['page'] && isset( $_GET['lnf-saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Réglages enregistrés.', 'loginfence' ) . '</strong> ' . esc_html__( 'Votre page de connexion est à jour.', 'loginfence' ) . '</p></div>';
		}
		if ( 'loginfence' === $_GET['page'] && isset( $_GET['lnf-reset'] ) ) {
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Réglages réinitialisés aux valeurs par défaut.', 'loginfence' ) . '</p></div>';
		}
		if ( 'loginfence' === $_GET['page'] && isset( $_GET['lnf-imported'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Réglages importés avec succès.', 'loginfence' ) . '</strong></p></div>';
		}
		if ( 'loginfence' === $_GET['page'] && isset( $_GET['lnf-import-error'] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Import impossible : fichier JSON invalide ou illisible.', 'loginfence' ) . '</p></div>';
		}

		if ( 'loginfence' === $_GET['page'] && isset( $_GET['lnf-cache'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cache du plugin vidé : vérifications de mises à jour réinitialisées et assets rafraîchis.', 'loginfence' ) . '</p></div>';
		}

		// Demande d'avis : une seule fois, après 14 jours d'utilisation.
		if ( 'loginfence' !== $_GET['page'] ) {
			return;
		}
		$first = (int) get_option( 'lnf_first_activated', 0 );
		if ( ! $first ) {
			update_option( 'lnf_first_activated', time(), false );
			return;
		}
		if ( get_option( 'lnf_review_dismissed', false ) || ( time() - $first ) <= 14 * DAY_IN_SECONDS ) {
			return;
		}
		$dismiss = wp_nonce_url( admin_url( 'admin-post.php?action=lnf_dismiss_review' ), 'lnf_dismiss_review' );
		?>
		<div class="notice notice-info lnf-review">
			<p>
				<span class="lnf-stars" aria-hidden="true">★★★★★</span>
				<strong><?php esc_html_e( 'Vous aimez LoginFence Pro ?', 'loginfence' ); ?></strong>
				<?php esc_html_e( 'Un avis de votre part aide beaucoup le plugin à grandir. Merci pour votre soutien !', 'loginfence' ); ?>
				<a class="button button-primary" href="https://wordpress.org/plugins/loginfence/reviews/#new-post" target="_blank" rel="noopener"><?php esc_html_e( 'Laisser un avis', 'loginfence' ); ?></a>
				<a class="button-link" href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'C’est noté', 'loginfence' ); ?></a>
			</p>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Helpers de rendu des champs
	 * ------------------------------------------------------------------- */

	/**
	 * Attribut data-showif (affichage conditionnel côté JS).
	 *
	 * @param array $cond Conditions champ => valeur.
	 * @return string
	 */
	protected static function showif( $cond ) {
		return $cond ? ' data-showif="' . esc_attr( wp_json_encode( $cond ) ) . '"' : '';
	}

	/**
	 * Champ curseur.
	 */
	protected static function field_range( $s, $key, $label, $min, $max, $unit = '', $desc = '', $showif = array() ) {
		$value = (int) $s[ $key ];
		echo '<div class="lnf-field"' . self::showif( $showif ) . '>';
		echo '<label class="lnf-label" for="lnf-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		echo '<div class="lnf-range-row">';
		printf(
			'<input type="range" id="lnf-f-%1$s" name="lnf[%1$s]" min="%2$d" max="%3$d" step="1" value="%4$d">',
			esc_attr( $key ),
			(int) $min,
			(int) $max,
			(int) $value
		);
		printf(
			'<output class="lnf-range-out" data-unit="%1$s">%2$s%1$s</output>',
			esc_attr( $unit ),
			esc_html( $value )
		);
		echo '</div>';
		if ( $desc ) {
			echo '<p class="lnf-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ couleur.
	 */
	protected static function field_color( $s, $key, $label, $desc = '', $showif = array() ) {
		echo '<div class="lnf-field"' . self::showif( $showif ) . '>';
		echo '<label class="lnf-label" for="lnf-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		printf(
			'<input type="text" class="lnf-color" id="lnf-f-%1$s" name="lnf[%1$s]" value="%2$s" data-default-color="%2$s">',
			esc_attr( $key ),
			esc_attr( $s[ $key ] )
		);
		if ( $desc ) {
			echo '<p class="lnf-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ texte / url.
	 */
	protected static function field_text( $s, $key, $label, $type = 'text', $placeholder = '', $desc = '', $showif = array() ) {
		echo '<div class="lnf-field"' . self::showif( $showif ) . '>';
		echo '<label class="lnf-label" for="lnf-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		printf(
			'<input type="%1$s" class="lnf-input" id="lnf-f-%2$s" name="lnf[%2$s]" value="%3$s" placeholder="%4$s" autocomplete="off">',
			esc_attr( $type ),
			esc_attr( $key ),
			esc_attr( $s[ $key ] ),
			esc_attr( $placeholder )
		);
		if ( $desc ) {
			echo '<p class="lnf-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ zone de texte.
	 */
	protected static function field_textarea( $s, $key, $label, $desc = '', $showif = array() ) {
		echo '<div class="lnf-field lnf-field-wide"' . self::showif( $showif ) . '>';
		echo '<label class="lnf-label" for="lnf-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		printf(
			'<textarea class="lnf-input" id="lnf-f-%1$s" name="lnf[%1$s]" rows="2">%2$s</textarea>',
			esc_attr( $key ),
			esc_textarea( $s[ $key ] )
		);
		if ( $desc ) {
			echo '<p class="lnf-desc">' . wp_kses_post( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ liste déroulante.
	 */
	protected static function field_select( $s, $key, $label, $options, $desc = '', $showif = array() ) {
		echo '<div class="lnf-field"' . self::showif( $showif ) . '>';
		echo '<label class="lnf-label" for="lnf-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		echo '<select class="lnf-input" id="lnf-f-' . esc_attr( $key ) . '" name="lnf[' . esc_attr( $key ) . ']">';
		foreach ( $options as $value => $text ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $value ),
				selected( (string) $s[ $key ], (string) $value, false ),
				esc_html( $text )
			);
		}
		echo '</select>';
		if ( $desc ) {
			echo '<p class="lnf-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ interrupteur.
	 */
	protected static function field_toggle( $s, $key, $label, $desc = '', $showif = array() ) {
		echo '<div class="lnf-field lnf-toggle-field"' . self::showif( $showif ) . '>';
		echo '<label class="lnf-switch"><input type="checkbox" name="lnf[' . esc_attr( $key ) . ']" value="1"' . checked( ! empty( $s[ $key ] ), true, false ) . '><span class="lnf-switch-ui"></span></label>';
		echo '<div class="lnf-toggle-text"><span class="lnf-label">' . esc_html( $label ) . '</span>';
		if ( $desc ) {
			echo '<p class="lnf-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div></div>';
	}

	/**
	 * Champ image (médiathèque).
	 */
	protected static function field_media( $s, $key, $label, $desc = '', $showif = array() ) {
		echo '<div class="lnf-field lnf-field-wide"' . self::showif( $showif ) . '>';
		echo '<label class="lnf-label">' . esc_html( $label ) . '</label>';
		echo '<div class="lnf-media">';
		if ( ! empty( $s[ $key ] ) ) {
			printf( '<img class="lnf-media-thumb" src="%s" alt="">', esc_url( $s[ $key ] ) );
		} else {
			echo '<img class="lnf-media-thumb" src="" alt="" hidden>';
		}
		printf( '<input type="url" class="lnf-input" name="lnf[%s]" value="%s" placeholder="https://…" autocomplete="off">', esc_attr( $key ), esc_attr( $s[ $key ] ) );
		echo '<span class="lnf-media-actions">';
		echo '<button type="button" class="button lnf-media-pick">' . esc_html__( 'Médiathèque', 'loginfence' ) . '</button>';
		echo '<button type="button" class="button-link lnf-media-clear">' . esc_html__( 'Retirer', 'loginfence' ) . '</button>';
		echo '</span></div>';
		if ( $desc ) {
			echo '<p class="lnf-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/* ---------------------------------------------------------------------
	 * Page principale
	 * ------------------------------------------------------------------- */

	/**
	 * Onglets de la page.
	 *
	 * @return array
	 */
	protected static function tabs() {
		return array(
			'dashboard' => array( __( 'Tableau de bord', 'loginfence' ), 'dashicons-dashboard' ),
			'styles'    => array( __( 'Styles', 'loginfence' ), 'dashicons-art' ),
			'logo'      => array( __( 'Logo', 'loginfence' ), 'dashicons-format-image' ),
			'bg'        => array( __( 'Arrière-plan', 'loginfence' ), 'dashicons-desktop' ),
			'form'      => array( __( 'Formulaire', 'loginfence' ), 'dashicons-feedback' ),
			'links'     => array( __( 'Liens', 'loginfence' ), 'dashicons-editor-unlink' ),
			'social'    => array( __( 'Réseaux sociaux', 'loginfence' ), 'dashicons-share' ),
			'copyright' => array( __( 'Copyright', 'loginfence' ), 'dashicons-text' ),
			'extras'    => array( __( 'Extras', 'loginfence' ), 'dashicons-star-filled' ),
			'security'  => array( __( 'Sécurité', 'loginfence' ), 'dashicons-shield-alt' ),
		);
	}

	/**
	 * Affiche la page de personnalisation.
	 */
	public static function render_page() {
		$s = lnf_settings();
		?>
		<div class="wrap lnf-wrap">
			<div class="lnf-topbar">
				<div class="lnf-brand">
					<span class="lnf-brand-mark" aria-hidden="true">&#8734;</span>
					<span class="lnf-brand-text">
						<strong>LoginFence Pro</strong>
						<em class="lnf-version"><?php echo esc_html( 'v' . LOGINFENCE_VERSION ); ?></em>
					</span>
				</div>
				<div class="lnf-topbar-actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="lnf_reset">
						<?php wp_nonce_field( 'lnf_save', 'lnf_nonce' ); ?>
						<button type="submit" id="lnf-reset" class="lnf-btn lnf-btn-ghost">
							<?php esc_html_e( 'Réinitialiser', 'loginfence' ); ?>
						</button>
					</form>
					<button type="submit" form="lnf-form" class="lnf-btn lnf-btn-primary">
						<?php esc_html_e( 'Enregistrer', 'loginfence' ); ?>
					</button>
				</div>
			</div>

			<div class="lnf-backup-bar">
				<span class="lnf-backup-title"><span class="dashicons dashicons-database-export"></span> <?php esc_html_e( 'Sauvegarde des réglages', 'loginfence' ); ?></span>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=lnf_export' ), 'lnf_export' ) ); ?>">
					<?php esc_html_e( 'Exporter (JSON)', 'loginfence' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=lnf_purge_cache' ), 'lnf_purge_cache' ) ); ?>">
					<span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e( 'Vider le cache du plugin', 'loginfence' ); ?>
				</a>
				<form class="lnf-import-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="lnf_import">
					<?php wp_nonce_field( 'lnf_import', 'lnf_import_nonce' ); ?>
					<label class="screen-reader-text" for="lnf-import-file"><?php esc_html_e( 'Fichier de réglages (JSON)', 'loginfence' ); ?></label>
					<input type="file" id="lnf-import-file" name="lnf_import_file" accept="application/json,.json" required>
					<?php submit_button( __( 'Importer', 'loginfence' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<form id="lnf-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lnf_save">
				<?php wp_nonce_field( 'lnf_save', 'lnf_nonce' ); ?>

				<nav class="lnf-tabs" aria-label="<?php esc_attr_e( 'Sections de personnalisation', 'loginfence' ); ?>">
					<?php foreach ( self::tabs() as $id => $tab ) : ?>
						<button type="button" class="lnf-tab<?php echo 'dashboard' === $id ? ' is-active' : ''; ?>" data-tab="<?php echo esc_attr( $id ); ?>">
							<span class="dashicons <?php echo esc_attr( $tab[1] ); ?>" aria-hidden="true"></span>
							<?php echo esc_html( $tab[0] ); ?>
						</button>
					<?php endforeach; ?>
				</nav>

				<div class="lnf-layout">
					<div class="lnf-panels">
						<?php self::panel_dashboard( $s ); ?>
						<?php self::panel_styles( $s ); ?>
						<?php self::panel_logo( $s ); ?>
						<?php self::panel_background( $s ); ?>
						<?php self::panel_form( $s ); ?>
						<?php self::panel_links( $s ); ?>
						<?php self::panel_social( $s ); ?>
						<?php self::panel_copyright( $s ); ?>
						<?php self::panel_extras( $s ); ?>
						<?php self::panel_security( $s ); ?>
					</div>

					<aside class="lnf-preview" aria-label="<?php esc_attr_e( 'Aperçu en direct', 'loginfence' ); ?>">
						<div class="lnf-preview-bar">
							<span class="lnf-preview-title"><?php esc_html_e( 'Aperçu en direct', 'loginfence' ); ?></span>
							<span class="lnf-preview-devices">
								<button type="button" class="lnf-device is-active" data-width="0" title="<?php esc_attr_e( 'Bureau', 'loginfence' ); ?>"><span class="dashicons dashicons-desktop"></span></button>
								<button type="button" class="lnf-device" data-width="480" title="<?php esc_attr_e( 'Tablette', 'loginfence' ); ?>"><span class="dashicons dashicons-tablet"></span></button>
								<button type="button" class="lnf-device" data-width="375" title="<?php esc_attr_e( 'Mobile', 'loginfence' ); ?>"><span class="dashicons dashicons-smartphone"></span></button>
							</span>
							<span class="lnf-preview-actions">
								<button type="button" class="lnf-expand" title="<?php esc_attr_e( 'Aperçu plein écran', 'loginfence' ); ?>"><span class="dashicons dashicons-fullscreen-exit-alt"></span></button>
								<button type="button" class="lnf-refresh" title="<?php esc_attr_e( 'Recharger l’aperçu', 'loginfence' ); ?>"><span class="dashicons dashicons-update"></span></button>
								<a class="lnf-open" href="<?php echo esc_url( wp_login_url() ); ?>" target="_blank" rel="noopener" title="<?php esc_attr_e( 'Ouvrir dans un onglet', 'loginfence' ); ?>"><span class="dashicons dashicons-external"></span></a>
							</span>
						</div>
						<div class="lnf-frame-holder">
							<iframe id="lnf-frame" src="<?php echo esc_url( wp_login_url() ); ?>" title="<?php esc_attr_e( 'Aperçu de la page de connexion', 'loginfence' ); ?>" loading="lazy"></iframe>
						</div>
						<p class="lnf-preview-note"><?php esc_html_e( 'Les couleurs et effets sont appliqués en direct. Les liens, réseaux sociaux et copyright apparaissent après enregistrement.', 'loginfence' ); ?></p>
					</aside>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Ouvre un panneau (section).
	 */
	protected static function panel_open( $id, $title, $desc = '' ) {
		printf(
			'<section class="lnf-panel%1$s" data-panel="%2$s"><header class="lnf-panel-head"><h2>%3$s</h2>%4$s</header><div class="lnf-panel-body">',
			'dashboard' === $id ? ' is-active' : '',
			esc_attr( $id ),
			esc_html( $title ),
			$desc ? '<p class="lnf-panel-desc">' . esc_html( $desc ) . '</p>' : ''
		);
	}

	/**
	 * Ferme un panneau.
	 */
	protected static function panel_close() {
		echo '</div></section>';
	}

	/**
	 * Panneau : tableau de bord.
	 */
	protected static function panel_dashboard( $s ) {
		self::panel_open( 'dashboard', __( 'Tableau de bord', 'loginfence' ), __( 'Statistiques, score de sécurité et accès rapide à tous vos réglages.', 'loginfence' ) );

		$social_count  = count( lnf_get_social_networks( $s ) );

		// ——— Statistiques de sécurité ———.
		$stats = Lnf_Login_Security::get_stats();
		$stat_items = array(
			array( 'dashicons-yes-alt', __( 'Connexions (7 jours)', 'loginfence' ), $stats['logins7'], 'is-ok' ),
			array( 'dashicons-shield-alt', __( 'Blocages (7 jours)', 'loginfence' ), $stats['blocked7'], 'is-warn' ),
			array( 'dashicons-warning', __( 'Échecs (24 h)', 'loginfence' ), $stats['fails24'], 'is-err' ),
			array( 'dashicons-lock', __( 'Verrouillages actifs', 'loginfence' ), $stats['locks'], 'is-lock' ),
		);
		echo '<div class="lnf-stats">';
		foreach ( $stat_items as $item ) {
			printf(
				'<div class="lnf-stat %4$s"><span class="dashicons %1$s"></span><strong>%2$d</strong><small>%3$s</small></div>',
				esc_attr( $item[0] ),
				(int) $item[2],
				esc_html( $item[1] ),
				esc_attr( $item[3] )
			);
		}
		echo '</div>';

		// ——— Score de sécurité ———.
		$checks = array(
			'sec_enable'           => __( 'Limite des tentatives', 'loginfence' ),
			'sec_honeypot'         => __( 'Honeypot anti-robots', 'loginfence' ),
			'sec_disable_authors'  => __( 'Anti-énumération des auteurs', 'loginfence' ),
			'sec_disable_xmlrpc'   => __( 'XML-RPC désactivé', 'loginfence' ),
			'sec_generic_error'    => __( 'Erreurs masquées', 'loginfence' ),
		);
		$score    = 0;
		foreach ( $checks as $key => $label ) {
			if ( ! empty( $s[ $key ] ) ) {
				$score++;
			}
		}
		$score_class = 5 === $score ? 'is-high' : ( $score >= 3 ? 'is-mid' : 'is-low' );
		echo '<div class="lnf-score">';
		echo '<div class="lnf-score-head"><h3 class="lnf-group-title">' . esc_html__( 'Score de sécurité', 'loginfence' ) . '</h3>';
		printf( '<span class="lnf-score-value %1$s">%2$d/5</span>', esc_attr( $score_class ), (int) $score );
		echo '</div>';
		printf( '<div class="lnf-score-bar"><span class="%1$s" style="width:%2$d%%"></span></div>', esc_attr( $score_class ), (int) ( $score * 20 ) );
		echo '<div class="lnf-score-chips">';
		foreach ( $checks as $key => $label ) {
			$on = ! empty( $s[ $key ] );
			printf(
				'<span class="lnf-chip %1$s">%2$s %3$s</span>',
				$on ? 'is-on' : 'is-off',
				$on ? '✔' : '✖',
				esc_html( $label )
			);
		}
		echo '</div>';
		echo '<div class="lnf-score-actions">';
		echo '<button type="button" class="lnf-card-link" data-goto="security">' . esc_html__( 'Renforcer la sécurité', 'loginfence' ) . '</button>';
		if ( $score < 5 ) {
			echo '<button type="button" class="lnf-card-link lnf-recommended">⚡ ' . esc_html__( 'Activer le pack recommandé (5/5)', 'loginfence' ) . '</button>';
		}
		echo '</div>';
		echo '</div>';

		// ——— Activité récente ———.
		$log = Lnf_Login_Security::get_log();
		echo '<div class="lnf-activity">';
		echo '<h3 class="lnf-group-title">' . esc_html__( 'Activité récente', 'loginfence' ) . '</h3>';
		if ( $log ) {
			$badges = array(
				'failed'  => array( __( 'Échec', 'loginfence' ), 'is-fail' ),
				'blocked' => array( __( 'Bloqué', 'loginfence' ), 'is-blocked' ),
				'login'   => array( __( 'Connexion', 'loginfence' ), 'is-login' ),
			);
			echo '<ul class="lnf-activity-list">';
			foreach ( array_slice( $log, 0, 5 ) as $event ) {
				$badge = isset( $badges[ $event['a'] ] ) ? $badges[ $event['a'] ] : $badges['failed'];
				printf(
					'<li><span class="lnf-badge %4$s">%1$s</span><code>%2$s</code><span class="lnf-activity-user">%3$s</span><time>%5$s</time></li>',
					esc_html( $badge[0] ),
					esc_html( $event['ip'] ),
					esc_html( $event['u'] ? $event['u'] : '—' ),
					esc_attr( $badge[1] ),
					esc_html( date_i18n( 'j F, H:i', $event['t'] + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) )
				);
			}
			echo '</ul>';
			echo '<button type="button" class="lnf-card-link" data-goto="security">' . esc_html__( 'Ouvrir le journal complet', 'loginfence' ) . '</button>';
		} else {
			echo '<p class="lnf-desc">' . esc_html__( 'Aucun événement pour le moment — votre page de connexion est tranquille.', 'loginfence' ) . '</p>';
		}
		echo '</div>';
		$links_count   = ( ! empty( $s['hide_lost_password'] ) ? 1 : 0 ) + ( ! empty( $s['hide_back_to'] ) ? 1 : 0 ) + ( ! empty( $s['back_to_url'] ) || ! empty( $s['back_to_text'] ) ? 1 : 0 );
		$preset_labels = array(
			'glass'   => __( 'Effet verre', 'loginfence' ),
			'minimal' => __( 'Minimal', 'loginfence' ),
			'dark'    => __( 'Sombre', 'loginfence' ),
			'sunset'  => __( 'Coucher de soleil', 'loginfence' ),
			'ocean'   => __( 'Océan', 'loginfence' ),
			'forest'  => __( 'Forêt', 'loginfence' ),
			'neon'    => __( 'Néon', 'loginfence' ),
			'sakura'  => __( 'Sakura', 'loginfence' ),
			'mono'    => __( 'Monochrome', 'loginfence' ),
			'royal'   => __( 'Royal', 'loginfence' ),
			'custom'  => __( 'Personnalisé', 'loginfence' ),
		);

		$cards = array(
			array(
				'icon'   => 'dashicons-art',
				'title'  => __( 'Style actif', 'loginfence' ),
				'state'  => isset( $preset_labels[ $s['preset'] ] ) ? $preset_labels[ $s['preset'] ] : __( 'Personnalisé', 'loginfence' ),
				'ok'     => true,
				'goto'   => 'styles',
				'button' => __( 'Changer de style', 'loginfence' ),
			),
			array(
				'icon'   => 'dashicons-shield-alt',
				'title'  => __( 'Sécurité', 'loginfence' ),
				'state'  => ! empty( $s['sec_enable'] )
					/* translators: 1 : nombre de tentatives, 2 : minutes. */
					? sprintf( __( 'Activé — %1$d tentatives, blocage %2$d min', 'loginfence' ), (int) $s['sec_max_attempts'], (int) $s['sec_lockout_minutes'] )
					: __( 'Désactivé', 'loginfence' ),
				'ok'     => ! empty( $s['sec_enable'] ),
				'goto'   => 'security',
				'button' => __( 'Configurer', 'loginfence' ),
			),
			array(
				'icon'   => 'dashicons-share',
				'title'  => __( 'Réseaux sociaux', 'loginfence' ),
				'state'  => $social_count > 0
					/* translators: %d : nombre de réseaux configurés. */
					? sprintf( _n( '%d réseau configuré', '%d réseaux configurés', $social_count, 'loginfence' ), $social_count )
					: __( 'Aucun réseau configuré', 'loginfence' ),
				'ok'     => $social_count > 0,
				'goto'   => 'social',
				'button' => __( 'Configurer', 'loginfence' ),
			),
			array(
				'icon'   => 'dashicons-text',
				'title'  => __( 'Copyright', 'loginfence' ),
				'state'  => ! empty( $s['copyright_enable'] ) ? __( 'Affiché', 'loginfence' ) : __( 'Masqué', 'loginfence' ),
				'ok'     => ! empty( $s['copyright_enable'] ),
				'goto'   => 'copyright',
				'button' => __( 'Configurer', 'loginfence' ),
			),
			array(
				'icon'   => 'dashicons-format-image',
				'title'  => __( 'Logo', 'loginfence' ),
				'state'  => ! empty( $s['logo_hide'] ) ? __( 'Masqué', 'loginfence' ) : ( ! empty( $s['logo_url'] ) ? __( 'Image personnalisée', 'loginfence' ) : __( 'Logo WordPress par défaut', 'loginfence' ) ),
				'ok'     => ! empty( $s['logo_url'] ) && empty( $s['logo_hide'] ),
				'goto'   => 'logo',
				'button' => __( 'Personnaliser', 'loginfence' ),
			),
			array(
				'icon'   => 'dashicons-editor-unlink',
				'title'  => __( 'Liens', 'loginfence' ),
				'state'  => $links_count > 0 ? __( 'Liens personnalisés', 'loginfence' ) : __( 'Liens par défaut', 'loginfence' ),
				'ok'     => $links_count > 0,
				'goto'   => 'links',
				'button' => __( 'Personnaliser', 'loginfence' ),
			),
			array(
				'icon'   => 'dashicons-download',
				'title'  => __( 'Mises à jour', 'loginfence' ),
				'state'  => sprintf(
					/* translators: %s : dépôt GitHub. */
					__( 'Version %s — GitHub : ', 'loginfence' ),
					LOGINFENCE_VERSION
				) . LOGINFENCE_GITHUB_REPO,
				'ok'     => true,
				'goto'   => '',
				'button' => __( 'Vérifier les mises à jour', 'loginfence' ),
				'check'  => true,
			),
			array(
				'icon'   => 'dashicons-superhero-alt',
				'title'  => __( 'LoginFence Pro', 'loginfence' ),
				'state'  => __( '2FA, reCAPTCHA, URL de connexion personnalisée…', 'loginfence' ),
				'ok'     => false,
				'goto'   => '',
				'link'   => admin_url( 'admin.php?page=loginfence-pro' ),
				'button' => __( 'Passer en Pro', 'loginfence' ),
			),
		);

		echo '<div class="lnf-cards">';
		foreach ( $cards as $card ) {
			echo '<div class="lnf-card">';
			echo '<span class="lnf-card-icon"><span class="dashicons ' . esc_attr( $card['icon'] ) . '"></span></span>';
			echo '<div class="lnf-card-body"><h3>' . esc_html( $card['title'] ) . '</h3>';
			echo '<p><span class="lnf-chip ' . ( $card['ok'] ? 'is-on' : 'is-off' ) . '">' . wp_kses_post( $card['state'] ) . '</span></p>';
			if ( ! empty( $card['check'] ) ) {
				echo '<span class="lnf-card-link lnf-check-updates" tabindex="0"><span class="dashicons dashicons-update-alt"></span> ' . esc_html( $card['button'] ) . '</span>';
				echo '<span class="lnf-update-status" aria-live="polite"></span>';
			} elseif ( ! empty( $card['link'] ) ) {
				echo '<a class="lnf-card-link" href="' . esc_url( $card['link'] ) . '">' . esc_html( $card['button'] ) . ' <span class="dashicons dashicons-external"></span></a>';
			} else {
				echo '<button type="button" class="lnf-card-link" data-goto="' . esc_attr( $card['goto'] ) . '">' . esc_html( $card['button'] ) . '</button>';
			}
			echo '</div></div>';
		}
		echo '</div>';

		self::panel_close();
	}

	/**
	 * Panneau : styles prédéfinis.
	 */
	protected static function panel_styles( $s ) {
		self::panel_open( 'styles', __( 'Styles modernes', 'loginfence' ), __( 'Appliquez un style et un thème d’interface en un clic, puis affinez-les dans les onglets suivants.', 'loginfence' ) );

		$presets = array(
			'glass'   => array( __( 'Effet verre', 'loginfence' ), 'linear-gradient(135deg,#667eea,#764ba2)' ),
			'minimal' => array( __( 'Minimal', 'loginfence' ), 'linear-gradient(135deg,#f5f6f8,#dfe3ea)' ),
			'dark'    => array( __( 'Sombre', 'loginfence' ), 'linear-gradient(160deg,#0f172a,#334155)' ),
			'sunset'  => array( __( 'Coucher de soleil', 'loginfence' ), 'linear-gradient(120deg,#f97316,#ec4899)' ),
			'ocean'   => array( __( 'Océan', 'loginfence' ), 'linear-gradient(135deg,#0ea5e9,#2563eb)' ),
			'forest'  => array( __( 'Forêt', 'loginfence' ), 'linear-gradient(135deg,#059669,#065f46)' ),
			'neon'    => array( __( 'Néon', 'loginfence' ), 'linear-gradient(135deg,#0f0c29,#302b63)' ),
			'sakura'  => array( __( 'Sakura', 'loginfence' ), 'linear-gradient(120deg,#ee9ca7,#ffdde1)' ),
			'mono'    => array( __( 'Monochrome', 'loginfence' ), 'linear-gradient(160deg,#9ca3af,#374151)' ),
			'royal'   => array( __( 'Royal', 'loginfence' ), 'linear-gradient(150deg,#141e30,#243b55)' ),
		);

		echo '<input type="hidden" name="lnf[preset]" value="' . esc_attr( $s['preset'] ) . '">';
		echo '<div class="lnf-presets">';
		foreach ( $presets as $key => $preset ) {
			printf(
				'<button type="button" class="lnf-preset%3$s" data-preset="%1$s"><span class="lnf-preset-preview" style="background:%2$s"></span><span class="lnf-preset-name">%4$s</span></button>',
				esc_attr( $key ),
				esc_attr( $preset[1] ),
				$s['preset'] === $key ? ' is-active' : '',
				esc_html( $preset[0] )
			);
		}
		echo '</div>';

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Thème d’interface du formulaire', 'loginfence' ) . '</h3>';
		echo '<p class="lnf-panel-desc">' . esc_html__( 'Le design général du formulaire, indépendant des couleurs.', 'loginfence' ) . '</p>';
		echo '<input type="hidden" name="lnf[form_theme]" value="' . esc_attr( $s['form_theme'] ) . '">';
		echo '<div class="lnf-presets lnf-themes">';
		$themes = array(
			'glass'    => array( __( 'Effet verre', 'loginfence' ), 'background:linear-gradient(135deg,#667eea,#764ba2);box-shadow:inset 22px 22px 0 -8px rgba(255,255,255,.4);border-radius:8px;' ),
			'classic'  => array( __( 'Classique', 'loginfence' ), 'background:#fff;border:1px solid #d5d3e8;border-radius:6px;' ),
			'outline'  => array( __( 'Contour', 'loginfence' ), 'background:transparent;border:2px solid #6d5df6;border-radius:8px;' ),
			'pill'     => array( __( 'Pillule', 'loginfence' ), 'background:#fff;border-radius:999px;' ),
			'elevated' => array( __( 'Surélevé', 'loginfence' ), 'background:#fff;border-radius:12px;box-shadow:0 12px 20px -8px rgba(0,0,0,.5);' ),
			'accent'   => array( __( 'Accent', 'loginfence' ), 'background:#fff;border-top:6px solid #7c3aed;border-radius:8px;' ),
			'minimal'  => array( __( 'Minimal', 'loginfence' ), 'background:transparent;border-bottom:5px solid #6d5df6;border-radius:0;' ),
		);
		foreach ( $themes as $key => $theme ) {
			printf(
				'<button type="button" class="lnf-preset lnf-theme-card%3$s" data-theme="%1$s"><span class="lnf-preset-preview" style="%2$s"></span><span class="lnf-preset-name">%4$s</span></button>',
				esc_attr( $key ),
				esc_attr( $theme[1] ),
				$s['form_theme'] === $key ? ' is-active' : '',
				esc_html( $theme[0] )
			);
		}
		echo '</div>';

		self::panel_close();
	}

	/**
	 * Panneau : logo.
	 */
	protected static function panel_logo( $s ) {
		self::panel_open( 'logo', __( 'Logo', 'loginfence' ), __( 'Remplacez le logo WordPress par le vôtre.', 'loginfence' ) );

		self::field_toggle( $s, 'logo_hide', __( 'Masquer complètement le logo', 'loginfence' ) );
		self::field_media( $s, 'logo_url', __( 'Image du logo', 'loginfence' ), __( 'SVG ou PNG transparent recommandé.', 'loginfence' ), array( 'logo_hide' => 0 ) );
		self::field_text( $s, 'logo_text', __( 'Ou logo en texte', 'loginfence' ), 'text', __( 'ex. : MaBoutique', 'loginfence' ), __( 'Remplace l’image par un titre stylé — prioritaire si les deux sont remplis.', 'loginfence' ), array( 'logo_hide' => 0 ) );
		self::field_range( $s, 'logo_width', __( 'Largeur', 'loginfence' ), 40, 400, 'px', '', array( 'logo_hide' => 0 ) );
		self::field_range( $s, 'logo_height', __( 'Hauteur', 'loginfence' ), 24, 300, 'px', '', array( 'logo_hide' => 0 ) );
		self::field_text( $s, 'logo_link', __( 'Lien du logo', 'loginfence' ), 'url', 'https://exemple.com', __( 'Laisser vide pour pointer vers l’accueil du site.', 'loginfence' ), array( 'logo_hide' => 0 ) );

		self::panel_close();
	}

	/**
	 * Panneau : arrière-plan.
	 */
	protected static function panel_background( $s ) {
		self::panel_open( 'bg', __( 'Arrière-plan', 'loginfence' ), __( 'Couleur, dégradé ou image, avec flou et voile coloré.', 'loginfence' ) );

		self::field_select(
			$s,
			'bg_type',
			__( 'Type de fond', 'loginfence' ),
			array(
				'color'    => __( 'Couleur unie', 'loginfence' ),
				'gradient' => __( 'Dégradé', 'loginfence' ),
				'image'    => __( 'Image', 'loginfence' ),
			)
		);
		self::field_color( $s, 'bg_color1', __( 'Couleur de fond / dégradé 1', 'loginfence' ) );
		self::field_color( $s, 'bg_color2', __( 'Dégradé — couleur 2', 'loginfence' ), '', array( 'bg_type' => 'gradient' ) );
		self::field_range( $s, 'bg_gradient_angle', __( 'Angle du dégradé', 'loginfence' ), 0, 360, '°', '', array( 'bg_type' => 'gradient' ) );
		self::field_media( $s, 'bg_image', __( 'Image de fond', 'loginfence' ), '', array( 'bg_type' => 'image' ) );
		self::field_select(
			$s,
			'bg_size',
			__( 'Affichage de l’image', 'loginfence' ),
			array(
				'cover'   => __( 'Couvrir (cover)', 'loginfence' ),
				'contain' => __( 'Contenir (contain)', 'loginfence' ),
				'repeat'  => __( 'Répéter (motif)', 'loginfence' ),
			),
			'',
			array( 'bg_type' => 'image' )
		);
		self::field_select(
			$s,
			'bg_position',
			__( 'Position de l’image', 'loginfence' ),
			array(
				'center'       => __( 'Centrée', 'loginfence' ),
				'top'          => __( 'En haut', 'loginfence' ),
				'bottom'       => __( 'En bas', 'loginfence' ),
				'left'         => __( 'À gauche', 'loginfence' ),
				'right'        => __( 'À droite', 'loginfence' ),
				'top-left'     => __( 'Haut gauche', 'loginfence' ),
				'top-right'    => __( 'Haut droit', 'loginfence' ),
				'bottom-left'  => __( 'Bas gauche', 'loginfence' ),
				'bottom-right' => __( 'Bas droit', 'loginfence' ),
			),
			'',
			array( 'bg_type' => 'image' )
		);
		self::field_range( $s, 'bg_blur', __( 'Flou de l’image', 'loginfence' ), 0, 30, 'px', __( 'Contrôle du flou (backdrop).', 'loginfence' ), array( 'bg_type' => 'image' ) );
		self::field_range( $s, 'bg_brightness', __( 'Luminosité de l’image', 'loginfence' ), 30, 150, '%', '', array( 'bg_type' => 'image' ) );
		self::field_range( $s, 'bg_saturation', __( 'Saturation de l’image', 'loginfence' ), 0, 200, '%', '', array( 'bg_type' => 'image' ) );
		self::field_color( $s, 'bg_overlay_color', __( 'Voile coloré', 'loginfence' ), __( 'Couche de couleur superposée au fond.', 'loginfence' ) );
		self::field_range( $s, 'bg_overlay_opacity', __( 'Opacité du voile', 'loginfence' ), 0, 100, '%' );

		self::panel_close();
	}

	/**
	 * Panneau : formulaire.
	 */
	protected static function panel_form( $s ) {
		self::panel_open( 'form', __( 'Formulaire', 'loginfence' ), __( 'Effet verre, couleurs des champs, bouton et liens.', 'loginfence' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Conteneur', 'loginfence' ) . '</h3>';
		self::field_color( $s, 'form_bg', __( 'Fond du formulaire', 'loginfence' ) );
		self::field_range( $s, 'form_opacity', __( 'Opacité du fond', 'loginfence' ), 0, 100, '%' );
		self::field_range( $s, 'form_blur', __( 'Flou (effet verre)', 'loginfence' ), 0, 40, 'px' );
		self::field_range( $s, 'form_radius', __( 'Arrondi des coins', 'loginfence' ), 0, 60, 'px' );
		self::field_range( $s, 'form_width', __( 'Largeur du formulaire', 'loginfence' ), 260, 560, 'px' );
		self::field_range( $s, 'form_padding', __( 'Espacement intérieur', 'loginfence' ), 12, 80, 'px' );
		self::field_range( $s, 'input_height', __( 'Hauteur des champs', 'loginfence' ), 0, 60, 'px', __( '0 = hauteur WordPress par défaut.', 'loginfence' ) );
		self::field_toggle( $s, 'form_shadow', __( 'Ombre portée', 'loginfence' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Textes et champs', 'loginfence' ) . '</h3>';
		self::field_color( $s, 'text_color', __( 'Texte du formulaire', 'loginfence' ) );
		self::field_color( $s, 'label_color', __( 'Libellés', 'loginfence' ) );
		self::field_color( $s, 'input_bg', __( 'Fond des champs', 'loginfence' ) );
		self::field_color( $s, 'input_color', __( 'Texte des champs', 'loginfence' ) );
		self::field_color( $s, 'input_border', __( 'Bordure des champs', 'loginfence' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Bouton « Se connecter »', 'loginfence' ) . '</h3>';
		self::field_color( $s, 'button_bg', __( 'Couleur du bouton', 'loginfence' ) );
		self::field_color( $s, 'button_hover', __( 'Couleur au survol', 'loginfence' ) );
		self::field_range( $s, 'button_radius', __( 'Arrondi du bouton', 'loginfence' ), 0, 40, 'px' );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Liens', 'loginfence' ) . '</h3>';
		self::field_color( $s, 'link_color', __( 'Couleur des liens', 'loginfence' ) );

		self::panel_close();
	}

	/**
	 * Panneau : liens.
	 */
	protected static function panel_links( $s ) {
		self::panel_open( 'links', __( 'Liens de la page de connexion', 'loginfence' ), __( 'Modifiez la cible et le texte des liens, ou masquez-les.', 'loginfence' ) );

		self::field_toggle( $s, 'hide_lost_password', __( 'Masquer « Mot de passe perdu ? »', 'loginfence' ) );
		self::field_toggle( $s, 'hide_back_to', __( 'Masquer « Retour au site »', 'loginfence' ) );
		self::field_text( $s, 'back_to_text', __( 'Texte du lien « Retour au site »', 'loginfence' ), 'text', __( 'ex. : Retour à la boutique', 'loginfence' ) );
		self::field_text( $s, 'back_to_url', __( 'Cible du lien « Retour au site »', 'loginfence' ), 'url', 'https://exemple.com' );
		self::field_toggle( $s, 'hide_register', __( 'Masquer le lien « S’enregistrer »', 'loginfence' ) );
		self::field_text( $s, 'register_text', __( 'Texte du lien « S’enregistrer »', 'loginfence' ), 'text', __( 'ex. : Créer un compte client', 'loginfence' ) );

		self::panel_close();
	}

	/**
	 * Panneau : réseaux sociaux.
	 */
	protected static function panel_social( $s ) {
		self::panel_open( 'social', __( 'Icônes de réseaux sociaux', 'loginfence' ), __( 'Renseignez une URL pour afficher l’icône — laissez vide pour la masquer.', 'loginfence' ) );

		self::field_toggle( $s, 'social_enable', __( 'Afficher les icônes sociales', 'loginfence' ) );
		self::field_toggle( $s, 'social_brand', __( 'Couleurs officielles des marques', 'loginfence' ), __( 'Chaque icône reprend sa couleur officielle : Facebook bleu, X noir, dégradé Instagram, LinkedIn bleu, YouTube rouge.', 'loginfence' ) );
		self::field_select(
			$s,
			'social_style',
			__( 'Forme des icônes', 'loginfence' ),
			array(
				'circle'  => __( 'Cercle', 'loginfence' ),
				'rounded' => __( 'Arrondi', 'loginfence' ),
				'square'  => __( 'Carré', 'loginfence' ),
			),
			'',
			array( 'social_enable' => 1 )
		);
		self::field_range( $s, 'social_size', __( 'Taille des icônes', 'loginfence' ), 28, 72, 'px', '', array( 'social_enable' => 1 ) );
		self::field_color( $s, 'social_icon_color', __( 'Couleur de l’icône', 'loginfence' ), '', array( 'social_enable' => 1 ) );
		self::field_color( $s, 'social_icon_bg', __( 'Fond de l’icône', 'loginfence' ), '', array( 'social_enable' => 1 ) );
		self::field_range( $s, 'social_icon_bg_opacity', __( 'Opacité du fond d’icône', 'loginfence' ), 0, 100, '%', '', array( 'social_enable' => 1 ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Réseaux', 'loginfence' ) . '</h3>';
		self::field_text( $s, 'social_facebook', __( 'Facebook', 'loginfence' ), 'url', 'https://facebook.com/votre-page' );
		self::field_text( $s, 'social_twitter', __( 'X (Twitter)', 'loginfence' ), 'url', 'https://x.com/votre-compte' );
		self::field_text( $s, 'social_instagram', __( 'Instagram', 'loginfence' ), 'url', 'https://instagram.com/votre-compte' );
		self::field_text( $s, 'social_linkedin', __( 'LinkedIn', 'loginfence' ), 'url', 'https://linkedin.com/in/votre-profil' );
		self::field_text( $s, 'social_youtube', __( 'YouTube', 'loginfence' ), 'url', 'https://youtube.com/@votre-chaine' );
		self::field_text( $s, 'social_email', __( 'E-mail', 'loginfence' ), 'email', 'contact@exemple.com' );

		self::panel_close();
	}

	/**
	 * Panneau : copyright.
	 */
	protected static function panel_copyright( $s ) {
		self::panel_open( 'copyright', __( 'Copyright', 'loginfence' ), __( 'Affichez votre mention de copyright sous le formulaire.', 'loginfence' ) );

		self::field_toggle( $s, 'copyright_enable', __( 'Afficher le copyright', 'loginfence' ) );
		self::field_textarea(
			$s,
			'copyright_text',
			__( 'Texte du copyright', 'loginfence' ),
			/* translators: les balises <code> sont des variables. */
			sprintf( __( 'Variables disponibles : %1$s (année) et %2$s (nom du site).', 'loginfence' ), '<code>{year}</code>', '<code>{sitename}</code>' ),
			array( 'copyright_enable' => 1 )
		);

		self::panel_close();
	}

	/**
	 * Panneau : extras (message d'accueil, typographie, animation).
	 */
	protected static function panel_extras( $s ) {
		self::panel_open( 'extras', __( 'Extras', 'loginfence' ), __( 'Message d’accueil, typographie et animation d’entrée de la page de connexion.', 'loginfence' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Message de bienvenue', 'loginfence' ) . '</h3>';
		self::field_toggle( $s, 'welcome_enable', __( 'Afficher un message d’accueil', 'loginfence' ), __( 'Titre et sous-titre affichés au-dessus du formulaire.', 'loginfence' ) );
		self::field_text( $s, 'welcome_title', __( 'Titre', 'loginfence' ), 'text', __( 'ex. : Bon retour parmi nous ✨', 'loginfence' ), '', array( 'welcome_enable' => 1 ) );
		self::field_text( $s, 'welcome_subtitle', __( 'Sous-titre', 'loginfence' ), 'text', __( 'ex. : Connectez-vous pour gérer votre boutique.', 'loginfence' ), '', array( 'welcome_enable' => 1 ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Typographie', 'loginfence' ) . '</h3>';
		self::field_select(
			$s,
			'font_family',
			__( 'Police', 'loginfence' ),
			array(
				'system'  => __( 'Système (moderne)', 'loginfence' ),
				'serif'   => __( 'Serif élégante', 'loginfence' ),
				'rounded' => __( 'Arrondie', 'loginfence' ),
				'mono'    => __( 'Monospace', 'loginfence' ),
			)
		);
		self::field_range( $s, 'font_size', __( 'Taille du texte', 'loginfence' ), 12, 18, 'px' );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Animation d’entrée', 'loginfence' ) . '</h3>';
		self::field_select(
			$s,
			'anim',
			__( 'Effet à l’ouverture de la page', 'loginfence' ),
			array(
				'none'  => __( 'Aucune', 'loginfence' ),
				'fade'  => __( 'Fondu', 'loginfence' ),
				'slide' => __( 'Glissement vers le haut', 'loginfence' ),
				'zoom'  => __( 'Zoom', 'loginfence' ),
			),
			__( 'Désactivée automatiquement si l’utilisateur demande moins d’animations.', 'loginfence' )
		);

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Champs du formulaire', 'loginfence' ) . '</h3>';
		self::field_text( $s, 'field_label_user', __( 'Libellé « Identifiant »', 'loginfence' ), 'text', __( 'ex. : E-mail ou pseudo', 'loginfence' ) );
		self::field_text( $s, 'field_label_pass', __( 'Libellé « Mot de passe »', 'loginfence' ), 'text', __( 'ex. : Votre mot de passe', 'loginfence' ) );
		self::field_text( $s, 'field_placeholder_user', __( 'Placeholder « Identifiant »', 'loginfence' ), 'text', __( 'ex. : vous@exemple.com', 'loginfence' ) );
		self::field_text( $s, 'field_placeholder_pass', __( 'Placeholder « Mot de passe »', 'loginfence' ), 'text', __( 'ex. : ••••••••', 'loginfence' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Après connexion', 'loginfence' ) . '</h3>';
		self::field_text(
			$s,
			'login_redirect',
			__( 'Redirection après connexion', 'loginfence' ),
			'url',
			'https://exemple.com/espace-client',
			__( 'Laisser vide pour le comportement WordPress habituel (tableau de bord ou page demandée).', 'loginfence' )
		);

		echo '<h3 class="lnf-group-title">' . esc_html__( 'CSS personnalisé', 'loginfence' ) . '</h3>';
		self::field_textarea(
			$s,
			'custom_css',
			__( 'CSS brut pour la page de connexion', 'loginfence' ),
			__( 'Injecté après tous les réglages du plugin — les balises sont retirées automatiquement.', 'loginfence' )
		);
		self::field_textarea(
			$s,
			'custom_js',
			__( 'JavaScript personnalisé', 'loginfence' ),
			__( 'Injecté en pied de page de connexion (fermetures de balise neutralisées automatiquement).', 'loginfence' )
		);

		self::panel_close();
	}

	/**
	 * Panneau : sécurité.
	 */
	protected static function panel_security( $s ) {
		self::panel_open( 'security', __( 'Sécurité de la connexion', 'loginfence' ), __( 'Bloquez les attaques par force brute sur la page de connexion.', 'loginfence' ) );

		self::field_toggle( $s, 'sec_enable', __( 'Limiter les tentatives de connexion', 'loginfence' ), __( 'Après N échecs, l’adresse IP et l’identifiant sont bloqués temporairement.', 'loginfence' ) );
		self::field_range( $s, 'sec_max_attempts', __( 'Tentatives autorisées', 'loginfence' ), 1, 20, '', '', array( 'sec_enable' => 1 ) );
		self::field_range( $s, 'sec_lockout_minutes', __( 'Durée du blocage', 'loginfence' ), 1, 1440, 'min', '', array( 'sec_enable' => 1 ) );
		self::field_text( $s, 'sec_lock_message', __( 'Message de blocage', 'loginfence' ), 'text', '', sprintf( __( 'Utilisez %%d pour la durée restante en minutes.', 'loginfence' ) ), array( 'sec_enable' => 1 ) );
		self::field_toggle( $s, 'sec_generic_error', __( 'Masquer le détail des erreurs', 'loginfence' ), __( 'Affiche un message générique au lieu de « mot de passe incorrect ».', 'loginfence' ) );
		self::field_toggle( $s, 'sec_hide_language_switcher', __( 'Masquer le sélecteur de langue', 'loginfence' ) );
		self::field_toggle( $s, 'sec_disable_xmlrpc', __( 'Désactiver XML-RPC', 'loginfence' ), __( 'Coupe une porte d’entrée classique des attaques par force brute (recommandé si vous n’utilisez pas l’appli mobile WordPress).', 'loginfence' ) );
		self::field_toggle( $s, 'sec_honeypot', __( 'Honeypot anti-robots', 'loginfence' ), __( 'Ajoute un champ caché que seuls les robots remplissent — la connexion est alors refusée et notée dans le journal.', 'loginfence' ) );
		self::field_toggle( $s, 'sec_disable_authors', __( 'Bloquer le balayage des auteurs', 'loginfence' ), __( 'Masque les identifiants : « ?author=N » est redirigé vers l’accueil et l’endpoint REST des utilisateurs est fermé aux visiteurs.', 'loginfence' ) );
		self::field_textarea(
			$s,
			'sec_whitelist',
			__( 'Liste blanche d’IP (jamais verrouillées)', 'loginfence' ),
			__( 'Une IP par ligne ou séparées par des virgules. Le joker * est accepté (ex. 192.168.1.*). Vos IP de confiance ne seront jamais bloquées — utile pour éviter de vous verrouiller vous-même.', 'loginfence' )
		);

		// ——— Journal de sécurité ———.
		$log = Lnf_Login_Security::get_log();
		echo '<div class="lnf-journal">';
		echo '<h3 class="lnf-group-title">' . esc_html__( 'Journal de sécurité', 'loginfence' ) . '</h3>';
		if ( $log ) {
			$badges = array(
				'failed'  => array( __( 'Échec', 'loginfence' ), 'is-fail' ),
				'blocked' => array( __( 'Bloqué', 'loginfence' ), 'is-blocked' ),
				'login'   => array( __( 'Connexion', 'loginfence' ), 'is-login' ),
			);
			echo '<table class="lnf-journal-table"><thead><tr><th>' . esc_html__( 'Date', 'loginfence' ) . '</th><th>IP</th><th>' . esc_html__( 'Identifiant', 'loginfence' ) . '</th><th>' . esc_html__( 'Action', 'loginfence' ) . '</th></tr></thead><tbody>';
			foreach ( array_slice( $log, 0, 20 ) as $event ) {
				$badge = isset( $badges[ $event['a'] ] ) ? $badges[ $event['a'] ] : array( $event['a'], 'is-fail' );
				printf(
					'<tr><td>%1$s</td><td><code>%2$s</code></td><td>%3$s</td><td><span class="lnf-badge %5$s">%4$s</span></td></tr>',
					esc_html( date_i18n( 'j F, H:i', $event['t'] + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ),
					esc_html( $event['ip'] ),
					esc_html( $event['u'] ? $event['u'] : '—' ),
					esc_html( $badge[0] ),
					esc_attr( $badge[1] )
				);
			}
			echo '</tbody></table>';
			printf(
				'<p class="lnf-desc">%s</p>',
				esc_html(
					sprintf(
						/* translators: %d : nombre d'événements. */
						__( '%d événements enregistrés (50 maximum).', 'loginfence' ),
						count( $log )
					)
				)
			);
			echo '<button type="button" class="button lnf-purge-log">' . esc_html__( 'Vider le journal', 'loginfence' ) . '</button> ';
			echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=lnf_export_log' ), 'lnf_export_log' ) ) . '">' . esc_html__( 'Exporter (CSV)', 'loginfence' ) . '</a> <span class="lnf-purge-status"></span>';
		} else {
			echo '<p class="lnf-desc">' . esc_html__( 'Aucun événement enregistré pour le moment.', 'loginfence' ) . '</p>';
		}
		echo '</div>';

		echo '<div class="lnf-pro-teaser">';
		echo '<div class="lnf-pro-teaser-text"><h4>' . esc_html__( 'Niveaux de sécurité avancés — LoginFence Pro', 'loginfence' ) . '</h4><p>'
			. esc_html__( 'Double authentification (2FA), reCAPTCHA v3, URL de connexion personnalisée, alertes e-mail, journal des tentatives et blocage géographique.', 'loginfence' )
			. '</p></div>';
		echo '<a class="lnf-btn lnf-btn-pro" href="' . esc_url( admin_url( 'admin.php?page=loginfence-pro' ) ) . '">' . esc_html__( 'Passer en Pro', 'loginfence' ) . '</a>';
		echo '</div>';

		self::panel_close();
	}

	/* ---------------------------------------------------------------------
	 * Page « À propos »
	 * ------------------------------------------------------------------- */

	/**
	 * URL du bouton de don.
	 *
	 * @return string
	 */
	protected static function donate_url() {
		return apply_filters( 'loginfence_donate_url', 'https://www.paypal.com/donate' );
	}

	/**
	 * Affiche la page « À propos ».
	 */
	public static function render_about() {
		?>
		<div class="wrap lnf-wrap lnf-about">
			<div class="lnf-hero">
				<span class="lnf-hero-mark" aria-hidden="true">&#8734;</span>
				<h1><?php esc_html_e( 'LoginFence Pro', 'loginfence' ); ?></h1>
				<p>
					<?php esc_html_e( 'La page de connexion de WordPress, enfin à votre image.', 'loginfence' ); ?><br>
					<span class="lnf-version">v<?php echo esc_html( LOGINFENCE_VERSION ); ?></span>
				</p>
				<div class="lnf-hero-actions">
						<a class="lnf-btn lnf-btn-donate" href="<?php echo esc_url( self::donate_url() ); ?>" target="_blank" rel="noopener">
							<span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'Faire un don', 'loginfence' ); ?>
						</a>
						<a class="lnf-btn lnf-btn-ghost is-light" href="https://github.com/derouicheoussama/loginfence" target="_blank" rel="noopener">
							<span class="dashicons dashicons-github"></span> <?php esc_html_e( 'Voir sur GitHub', 'loginfence' ); ?>
						</a>
					</div>
					<div class="lnf-quality" aria-label="<?php esc_attr_e( 'Engagements qualité', 'loginfence' ); ?>">
						<span>✔ <?php esc_html_e( 'Sans publicité', 'loginfence' ); ?></span>
						<span>✔ <?php esc_html_e( 'Aucune donnée collectée', 'loginfence' ); ?></span>
						<span>✔ <?php esc_html_e( 'Compatible multisite', 'loginfence' ); ?></span>
						<span>✔ <?php esc_html_e( 'Prêt pour la traduction', 'loginfence' ); ?></span>
						<span>✔ <?php esc_html_e( 'Licence GPL v2+', 'loginfence' ); ?></span>
					</div>
				</div>

			<div class="lnf-about-grid">
				<section class="lnf-about-card">
					<h2><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'Développeur', 'loginfence' ); ?></h2>
					<p class="lnf-about-dev"><strong><?php esc_html_e( 'Derouiche Oussama', 'loginfence' ); ?></strong></p>
					<p><?php esc_html_e( 'Créateur du plugin, passionné par WordPress et les interfaces modernes.', 'loginfence' ); ?></p>
					<div class="lnf-dev-social" aria-label="<?php esc_attr_e( 'Réseaux du développeur', 'loginfence' ); ?>">
						<?php foreach ( self::dev_socials() as $network ) : ?>
							<a class="lnf-dev-icon" href="<?php echo esc_url( $network['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $network['label'] ); ?>" title="<?php echo esc_attr( $network['label'] ); ?>" style="<?php echo esc_attr( ! empty( $network['bg'] ) ? 'background:' . $network['bg'] : '' ); ?>">
								<?php if ( ! empty( $network['svg'] ) ) : ?>
									<?php echo $network['svg']; // SVG de marque (simple-icons), statique et sûr. ?>
								<?php else : ?>
									<span class="dashicons <?php echo esc_attr( $network['icon'] ); ?>"></span>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					</div>
					<p class="lnf-about-links">
						<a href="https://www.derouicheoussama.com" target="_blank" rel="noopener"><?php esc_html_e( 'Site web', 'loginfence' ); ?></a> ·
						<a href="https://github.com/derouicheoussama" target="_blank" rel="noopener">GitHub</a> ·
						<a href="https://profiles.wordpress.org/derouicheoussama/" target="_blank" rel="noopener">WordPress.org</a>
					</p>
				</section>

				<section class="lnf-about-card">
					<h2><span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Fonctionnalités', 'loginfence' ); ?></h2>
					<ul class="lnf-about-list">
						<li><?php esc_html_e( 'Logo personnalisé + lien du logo', 'loginfence' ); ?></li>
						<li><?php esc_html_e( 'Arrière-plan : couleur, dégradé ou image', 'loginfence' ); ?></li>
						<li><?php esc_html_e( 'Contrôles de flou et d’opacité', 'loginfence' ); ?></li>
						<li><?php esc_html_e( '6 styles modernes (effet verre, sombre…)', 'loginfence' ); ?></li>
						<li><?php esc_html_e( 'Liens : personnalisation ou masquage', 'loginfence' ); ?></li>
						<li><?php esc_html_e( 'Icônes de réseaux sociaux', 'loginfence' ); ?></li>
						<li><?php esc_html_e( 'Mention de copyright', 'loginfence' ); ?></li>
						<li><?php esc_html_e( 'Blocage des tentatives de mot de passe', 'loginfence' ); ?></li>
						<li><?php esc_html_e( 'Honeypot anti-robots et anti-énumération des auteurs', 'loginfence' ); ?></li>
						<li><?php esc_html_e( 'Journal de sécurité des 50 derniers événements', 'loginfence' ); ?></li>
						<li><?php esc_html_e( 'Export / import des réglages (JSON)', 'loginfence' ); ?></li>
						<li><?php esc_html_e( 'Achat intégré et activation de licence Pro', 'loginfence' ); ?></li>
						<li><?php esc_html_e( 'Aperçu en direct 100 % responsive', 'loginfence' ); ?></li>
						<li><?php esc_html_e( 'Mises à jour automatiques via GitHub', 'loginfence' ); ?></li>
					</ul>
				</section>

				<section class="lnf-about-card">
					<h2><span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Liens rapides', 'loginfence' ); ?></h2>
					<ul class="lnf-about-links-list">
						<li>
							<a href="https://www.derouicheoussama.com" target="_blank" rel="noopener"><?php esc_html_e( 'Site web', 'loginfence' ); ?></a>
							— <?php esc_html_e( 'tutoriels et actualités', 'loginfence' ); ?>
						</li>
						<li>
							<a href="https://github.com/derouicheoussama/loginfence#readme" target="_blank" rel="noopener"><?php esc_html_e( 'Documentation', 'loginfence' ); ?></a>
							— <?php esc_html_e( 'guide complet sur GitHub', 'loginfence' ); ?>
						</li>
						<li>
							<a href="https://github.com/derouicheoussama/loginfence/issues" target="_blank" rel="noopener"><?php esc_html_e( 'Signaler un bug', 'loginfence' ); ?></a>
							— <?php esc_html_e( 'ouverture d’un ticket en un clic', 'loginfence' ); ?>
						</li>
						<li>
							<a href="https://wordpress.org/support/plugin/loginfence/" target="_blank" rel="noopener"><?php esc_html_e( 'Forum d’entraide', 'loginfence' ); ?></a>
							— <?php esc_html_e( 'poser une question', 'loginfence' ); ?>
						</li>
						<li>
							<a href="https://wordpress.org/plugins/loginfence/reviews/#new-post" target="_blank" rel="noopener"><?php esc_html_e( 'Laisser un avis ★', 'loginfence' ); ?></a>
							— <?php esc_html_e( 'soutenir le projet', 'loginfence' ); ?>
						</li>
					</ul>
				</section>

				<section class="lnf-about-card">
					<h2><span class="dashicons dashicons-lock"></span> <?php esc_html_e( 'Protection & DMCA', 'loginfence' ); ?></h2>
					<p class="lnf-desc">
						<?php
						printf(
							/* translators: %s : année courante. */
							esc_html__( 'LoginFence Pro est une œuvre originale protégée par le droit d’auteur — © %s Derouiche Oussama. La signature « ∞ Infinity Coder » présente dans tous les fichiers doit être conservée.', 'loginfence' ),
							esc_html( gmdate( 'Y' ) )
						);
						?>
					</p>
					<ul class="lnf-about-links-list">
						<li>
							<strong><?php esc_html_e( 'Version gratuite', 'loginfence' ); ?></strong>
							— <?php esc_html_e( 'libre d’utilisation sous licence GPL, avec signature intacte.', 'loginfence' ); ?>
						</li>
						<li>
							<strong><?php esc_html_e( 'Version Pro', 'loginfence' ); ?></strong>
							— <?php esc_html_e( 'soumise à licence : une clé invalide ou révoquée suspend les fonctionnalités Pro à distance.', 'loginfence' ); ?>
						</li>
						<li>
							<strong><?php esc_html_e( 'Copies illégales', 'loginfence' ); ?></strong>
							— <?php esc_html_e( 'revente, republication ou retrait de la signature : signalement DMCA immédiat à l’hébergeur (retrait sous 24-72 h).', 'loginfence' ); ?>
						</li>
					</ul>
					<?php $badge = lnf_dmca_badge(); ?>
					<?php if ( '' !== $badge ) : ?>
						<p>
							<a href="<?php echo esc_url( lnf_dmca_url() ); ?>" target="_blank" rel="noopener noreferrer nofollow">
								<img src="<?php echo esc_url( $badge ); ?>" alt="<?php esc_attr_e( 'Protégé par DMCA.com', 'loginfence' ); ?>" loading="lazy">
							</a>
						</p>
					<?php endif; ?>
					<div class="lnf-hero-actions">
						<a class="lnf-btn lnf-btn-ghost" href="<?php echo esc_url( lnf_dmca_url() ); ?>" target="_blank" rel="noopener noreferrer nofollow">
							<span class="dashicons dashicons-flag"></span> <?php esc_html_e( 'Signaler une violation', 'loginfence' ); ?>
						</a>
					</div>
				</section>

				<section class="lnf-about-card">
					<h2><span class="dashicons dashicons-cloud"></span> <?php esc_html_e( 'Mises à jour', 'loginfence' ); ?></h2>
					<p>
						<?php
						printf(
							/* translators: %s : dépôt GitHub. */
							esc_html__( 'Ce plugin se met à jour automatiquement depuis les releases GitHub du dépôt :', 'loginfence' )
						);
						echo ' <code>' . esc_html( LOGINFENCE_GITHUB_REPO ) . '</code>';
						?>
					</p>
					<p><?php esc_html_e( 'Publiez un nouveau tag (ex. v1.2.1) : l’action GitHub construit le zip et propage la mise à jour à tous les sites.', 'loginfence' ); ?></p>
					<div class="lnf-about-actions">
						<button type="button" class="lnf-btn lnf-btn-ghost lnf-check-updates"><span class="dashicons dashicons-update-alt"></span> <?php esc_html_e( 'Vérifier les mises à jour', 'loginfence' ); ?></button>
						<span class="lnf-update-status" aria-live="polite"></span>
					</div>
				</section>

				<section class="lnf-about-card">
					<h2><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e( 'État du système', 'loginfence' ); ?></h2>
					<ul class="lnf-about-status">
						<li><strong><?php esc_html_e( 'Version du plugin', 'loginfence' ); ?></strong> <?php echo esc_html( LOGINFENCE_VERSION ); ?></li>
						<li><strong><?php esc_html_e( 'Version de WordPress', 'loginfence' ); ?></strong> <?php echo esc_html( get_bloginfo( 'version' ) ); ?></li>
						<li><strong><?php esc_html_e( 'Version de PHP', 'loginfence' ); ?></strong> <?php echo esc_html( PHP_VERSION ); ?></li>
						<li><strong><?php esc_html_e( 'Site', 'loginfence' ); ?></strong> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></li>
						</ul>
					</section>

					<section class="lnf-about-card">
						<h2><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Notes de version', 'loginfence' ); ?></h2>
						<?php $changelog = self::latest_changelog(); ?>
						<?php if ( $changelog ) : ?>
							<span class="lnf-changelog-version">v<?php echo esc_html( $changelog['version'] ); ?></span>
							<ul class="lnf-changelog">
								<?php foreach ( $changelog['items'] as $line ) : ?>
									<li><?php echo esc_html( $line ); ?></li>
								<?php endforeach; ?>
							</ul>
							<p class="lnf-desc">
								<a href="https://github.com/derouicheoussama/loginfence/releases" target="_blank" rel="noopener"><?php esc_html_e( 'Historique complet sur GitHub', 'loginfence' ); ?></a>
							</p>
						<?php else : ?>
							<p class="lnf-desc"><?php esc_html_e( 'Historique disponible sur GitHub.', 'loginfence' ); ?></p>
						<?php endif; ?>
					</section>
				</div>

			<section class="lnf-about-card lnf-about-support">
				<h2><span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'Soutenir le projet', 'loginfence' ); ?></h2>
				<p><?php esc_html_e( 'LoginFence Pro est développé sur le temps personnel. Si ce plugin vous est utile, un petit don aide à le maintenir, à l’améliorer et à le garder gratuit.', 'loginfence' ); ?></p>
				<div class="lnf-hero-actions">
					<a class="lnf-btn lnf-btn-donate" href="<?php echo esc_url( self::donate_url() ); ?>" target="_blank" rel="noopener">
						<span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'Faire un don via PayPal', 'loginfence' ); ?>
					</a>
					<a class="lnf-btn lnf-btn-ghost" href="https://wordpress.org/support/plugin/loginfence/" target="_blank" rel="noopener">
						<?php esc_html_e( 'Forum d’entraide', 'loginfence' ); ?>
					</a>
				</div>
			</section>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Installateur personnalisé (assistant de bienvenue)
	 * ------------------------------------------------------------------- */

	/**
	 * Réseaux sociaux du développeur (personnalisables via le filtre).
	 * SVG de marque : simple-icons (libre) — couleurs officielles.
	 *
	 * @return array
	 */
	protected static function dev_socials() {
		$svg = '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="%s"/></svg>';
		$icon = static function ( $path ) use ( $svg ) {
			return sprintf( $svg, $path );
		};

		return apply_filters(
			'loginfence_dev_socials',
			array(
				array(
					'label' => __( 'Site web', 'loginfence' ),
					'icon'  => 'dashicons-admin-links',
					'url'   => 'https://www.derouicheoussama.com',
				),
				array(
					'label' => 'GitHub',
					'bg'    => '#24292F',
					'url'   => 'https://github.com/derouicheoussama',
					'svg'   => $icon( 'M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12' ),
				),
				array(
					'label' => 'WordPress.org',
					'bg'    => '#21759B',
					'icon'  => 'dashicons-wordpress',
					'url'   => 'https://profiles.wordpress.org/derouicheoussama/',
				),
				array(
					'label' => 'Facebook',
					'bg'    => '#1877F2',
					'url'   => 'https://www.facebook.com/derouiche.oussama',
					'svg'   => $icon( 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' ),
				),
				array(
					'label' => 'Instagram',
					'bg'    => 'radial-gradient(circle at 30% 110%, #fdf497 0%, #fd5949 45%, #d6249f 60%, #285AEB 90%)',
					'url'   => 'https://www.instagram.com/derouiche.oussama/',
					'svg'   => $icon( 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z' ),
				),
				array(
					'label' => 'TikTok',
					'bg'    => '#010101',
					'url'   => 'https://www.tiktok.com/@derouiche.oussama',
					'svg'   => $icon( 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z' ),
				),
			)
		);
	}

	/**
	 * URL du bouton « Passer en Pro ».
	 *
	 * @return string
	 */
	protected static function pro_url() {
		return apply_filters( 'loginfence_pro_url', '#' );
	}

	/**
	 * Extrait la dernière section du changelog depuis readme.txt.
	 *
	 * @return array { version: string, items: string[] } — vide si indisponible.
	 */
	protected static function latest_changelog() {
		$file = LOGINFENCE_DIR . 'readme.txt';
		if ( ! is_readable( $file ) ) {
			return array();
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lecture locale du readme du plugin.
		$content = (string) file_get_contents( $file );
		if ( ! preg_match( '/== Changelog ==\s*.*?= ?([0-9.]+) ?=\s*(.*?)(?:\n= [0-9.]+ =|\z)/s', $content, $m ) ) {
			return array();
		}
		$lines = array_filter(
			array_map( 'trim', explode( "\n", trim( (string) $m[2] ) ) ),
			function ( $line ) {
				return '' !== $line;
			}
		);
		$items = array();
		foreach ( $lines as $line ) {
			$items[] = ltrim( $line, "* \t" );
		}
		return array(
			'version' => $m[1],
			'items'   => $items,
		);
	}

	/**
	 * Installateur : assistant de bienvenue en 3 étapes.
	 */
	public static function render_installer() {
		$s = lnf_settings();
		?>
		<div class="wrap lnf-installer">
			<header class="lnf-inst-hero">
				<span class="lnf-inst-mark" aria-hidden="true">&#8734;</span>
				<span class="lnf-version"><?php esc_html_e( 'Configuration guidée', 'loginfence' ); ?></span>
				<h1><?php esc_html_e( 'Bienvenue dans LoginFence Pro', 'loginfence' ); ?></h1>
				<p><?php esc_html_e( 'Transformez votre page de connexion en 3 étapes : choisissez un style et un thème d’interface, activez la protection anti force brute, et c’est parti. Tout reste modifiable ensuite.', 'loginfence' ); ?></p>
				<ol class="lnf-inst-steps" aria-hidden="true">
					<li class="is-active" data-step-dot="1"><?php esc_html_e( 'Bienvenue', 'loginfence' ); ?></li>
					<li data-step-dot="2"><?php esc_html_e( 'Style & thème', 'loginfence' ); ?></li>
					<li data-step-dot="3"><?php esc_html_e( 'Sécurité', 'loginfence' ); ?></li>
				</ol>
			</header>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lnf_wizard">
				<input type="hidden" name="lnf[preset]" value="<?php echo esc_attr( $s['preset'] ); ?>">
				<input type="hidden" name="lnf[form_theme]" value="<?php echo esc_attr( $s['form_theme'] ); ?>">
				<?php wp_nonce_field( 'lnf_wizard', 'lnf_wizard_nonce' ); ?>

				<section class="lnf-wstep is-active" data-step="1">
					<h2><?php esc_html_e( 'Ce que vous allez obtenir', 'loginfence' ); ?></h2>
					<p class="lnf-inst-desc"><?php esc_html_e( 'Un aperçu de tout ce que couvre LoginFence Pro.', 'loginfence' ); ?></p>
					<div class="lnf-inst-grid">
						<div class="lnf-inst-feature"><span class="dashicons dashicons-format-image"></span><h3><?php esc_html_e( 'Logo & arrière-plan', 'loginfence' ); ?></h3><p><?php esc_html_e( 'Logo image ou texte, image de fond avec flou, luminosité et voile coloré réglables.', 'loginfence' ); ?></p></div>
						<div class="lnf-inst-feature"><span class="dashicons dashicons-art"></span><h3><?php esc_html_e( '10 styles & 7 thèmes', 'loginfence' ); ?></h3><p><?php esc_html_e( 'Effet verre, sombre, néon, sakura… combinés à 7 designs de formulaire.', 'loginfence' ); ?></p></div>
						<div class="lnf-inst-feature"><span class="dashicons dashicons-shield-alt"></span><h3><?php esc_html_e( 'Anti force brute', 'loginfence' ); ?></h3><p><?php esc_html_e( 'Honeypot anti-robots, blocage IP + identifiant et liste blanche de confiance.', 'loginfence' ); ?></p></div>
						<div class="lnf-inst-feature"><span class="dashicons dashicons-share"></span><h3><?php esc_html_e( 'Social & copyright', 'loginfence' ); ?></h3><p><?php esc_html_e( 'Icônes aux couleurs officielles des marques et mention de copyright.', 'loginfence' ); ?></p></div>
						<div class="lnf-inst-feature"><span class="dashicons dashicons-desktop"></span><h3><?php esc_html_e( 'Aperçu en direct', 'loginfence' ); ?></h3><p><?php esc_html_e( 'Bureau, tablette et mobile — chaque changement se voit instantanément.', 'loginfence' ); ?></p></div>
						<div class="lnf-inst-feature"><span class="dashicons dashicons-chart-bar"></span><h3><?php esc_html_e( 'Statistiques', 'loginfence' ); ?></h3><p><?php esc_html_e( 'Score de sécurité et journal des 50 derniers événements de connexion.', 'loginfence' ); ?></p></div>
					</div>
					<p class="lnf-inst-note">∞ <?php esc_html_e( 'Création de Derouiche Oussama — sans publicité, sans collecte de données.', 'loginfence' ); ?></p>
				</section>

				<section class="lnf-wstep" data-step="2">
					<h2><?php esc_html_e( 'Choisissez votre style', 'loginfence' ); ?></h2>
					<p class="lnf-inst-desc"><?php esc_html_e( 'Un style définit les couleurs d’ambiance. Vous pourrez tout affiner plus tard (flou, opacité, typographie…).', 'loginfence' ); ?></p>
					<div class="lnf-presets">
						<?php
						$presets = array(
							'glass'   => array( __( 'Effet verre', 'loginfence' ), 'linear-gradient(135deg,#667eea,#764ba2)' ),
							'minimal' => array( __( 'Minimal', 'loginfence' ), 'linear-gradient(135deg,#f5f6f8,#dfe3ea)' ),
							'dark'    => array( __( 'Sombre', 'loginfence' ), 'linear-gradient(160deg,#0f172a,#334155)' ),
							'sunset'  => array( __( 'Coucher de soleil', 'loginfence' ), 'linear-gradient(120deg,#f97316,#ec4899)' ),
							'ocean'   => array( __( 'Océan', 'loginfence' ), 'linear-gradient(135deg,#0ea5e9,#2563eb)' ),
							'forest'  => array( __( 'Forêt', 'loginfence' ), 'linear-gradient(135deg,#059669,#065f46)' ),
							'neon'    => array( __( 'Néon', 'loginfence' ), 'linear-gradient(135deg,#0f0c29,#302b63)' ),
							'sakura'  => array( __( 'Sakura', 'loginfence' ), 'linear-gradient(120deg,#ee9ca7,#ffdde1)' ),
							'mono'    => array( __( 'Monochrome', 'loginfence' ), 'linear-gradient(160deg,#9ca3af,#374151)' ),
							'royal'   => array( __( 'Royal', 'loginfence' ), 'linear-gradient(150deg,#141e30,#243b55)' ),
						);
						foreach ( $presets as $key => $preset ) {
							printf(
								'<button type="button" class="lnf-preset%3$s" data-preset="%1$s"><span class="lnf-preset-preview" style="background:%2$s"></span><span class="lnf-preset-name">%4$s</span></button>',
								esc_attr( $key ),
								esc_attr( $preset[1] ),
								$s['preset'] === $key ? ' is-active' : '',
								esc_html( $preset[0] )
							);
						}
						?>
					</div>

					<h2 class="lnf-inst-subtitle"><?php esc_html_e( 'Et le design du formulaire', 'loginfence' ); ?></h2>
					<p class="lnf-inst-desc"><?php esc_html_e( 'La silhouette du formulaire, indépendante des couleurs.', 'loginfence' ); ?></p>
					<div class="lnf-presets lnf-themes">
						<?php
						$themes = array(
							'glass'    => array( __( 'Effet verre', 'loginfence' ), 'background:linear-gradient(135deg,#667eea,#764ba2);box-shadow:inset 22px 22px 0 -8px rgba(255,255,255,.4);border-radius:8px;' ),
							'classic'  => array( __( 'Classique', 'loginfence' ), 'background:#fff;border:1px solid #d5d3e8;border-radius:6px;' ),
							'outline'  => array( __( 'Contour', 'loginfence' ), 'background:transparent;border:2px solid #6d5df6;border-radius:8px;' ),
							'pill'     => array( __( 'Pillule', 'loginfence' ), 'background:#fff;border-radius:999px;' ),
							'elevated' => array( __( 'Surélevé', 'loginfence' ), 'background:#fff;border-radius:12px;box-shadow:0 12px 20px -8px rgba(0,0,0,.5);' ),
							'accent'   => array( __( 'Accent', 'loginfence' ), 'background:#fff;border-top:6px solid #7c3aed;border-radius:8px;' ),
							'minimal'  => array( __( 'Minimal', 'loginfence' ), 'background:transparent;border-bottom:5px solid #6d5df6;border-radius:0;' ),
						);
						foreach ( $themes as $key => $theme ) {
							printf(
								'<button type="button" class="lnf-preset lnf-theme-card%3$s" data-theme="%1$s"><span class="lnf-preset-preview" style="%2$s"></span><span class="lnf-preset-name">%4$s</span></button>',
								esc_attr( $key ),
								esc_attr( $theme[1] ),
								$s['form_theme'] === $key ? ' is-active' : '',
								esc_html( $theme[0] )
							);
						}
						?>
					</div>
				</section>

				<section class="lnf-wstep" data-step="3">
					<h2><?php esc_html_e( 'Protégez votre page de connexion', 'loginfence' ); ?></h2>
					<p class="lnf-inst-desc"><?php esc_html_e( 'Recommandé : activez toutes les protections dès maintenant — vous pourrez les ajuster dans l’onglet Sécurité.', 'loginfence' ); ?></p>

					<div class="lnf-inst-security">
						<label class="lnf-switch"><input type="checkbox" name="lnf[sec_enable]" value="1" <?php checked( ! empty( $s['sec_enable'] ) ); ?>><span class="lnf-switch-ui"></span></label>
						<div>
							<strong><?php esc_html_e( 'Limiter les tentatives de connexion', 'loginfence' ); ?></strong>
							<p class="lnf-inst-desc"><?php esc_html_e( 'Après N échecs, l’adresse IP et l’identifiant sont bloqués temporairement.', 'loginfence' ); ?></p>
						</div>
						<div class="lnf-inst-attempts">
							<label for="lnf-wizard-attempts"><?php esc_html_e( 'Tentatives autorisées', 'loginfence' ); ?></label>
							<input type="number" id="lnf-wizard-attempts" class="lnf-input" name="lnf[sec_max_attempts]" min="1" max="20" value="<?php echo esc_attr( $s['sec_max_attempts'] ); ?>">
							<label for="lnf-wizard-lockout"><?php esc_html_e( 'Blocage (minutes)', 'loginfence' ); ?></label>
							<input type="number" id="lnf-wizard-lockout" class="lnf-input" name="lnf[sec_lockout_minutes]" min="1" max="1440" value="<?php echo esc_attr( $s['sec_lockout_minutes'] ); ?>">
						</div>
					</div>

					<div class="lnf-inst-checks">
						<div class="lnf-inst-check"><label class="lnf-switch"><input type="checkbox" name="lnf[sec_honeypot]" value="1" <?php checked( ! empty( $s['sec_honeypot'] ) ); ?>><span class="lnf-switch-ui"></span></label><div><strong><?php esc_html_e( 'Honeypot anti-robots', 'loginfence' ); ?></strong><p class="lnf-inst-desc"><?php esc_html_e( 'Un champ caché piège les bots avant même la connexion.', 'loginfence' ); ?></p></div></div>
						<div class="lnf-inst-check"><label class="lnf-switch"><input type="checkbox" name="lnf[sec_disable_authors]" value="1" <?php checked( ! empty( $s['sec_disable_authors'] ) ); ?>><span class="lnf-switch-ui"></span></label><div><strong><?php esc_html_e( 'Anti-énumération des auteurs', 'loginfence' ); ?></strong><p class="lnf-inst-desc"><?php esc_html_e( 'Vos identifiants restent invisibles aux scanners.', 'loginfence' ); ?></p></div></div>
						<div class="lnf-inst-check"><label class="lnf-switch"><input type="checkbox" name="lnf[sec_disable_xmlrpc]" value="1" <?php checked( ! empty( $s['sec_disable_xmlrpc'] ) ); ?>><span class="lnf-switch-ui"></span></label><div><strong><?php esc_html_e( 'Désactiver XML-RPC', 'loginfence' ); ?></strong><p class="lnf-inst-desc"><?php esc_html_e( 'Ferme une porte d’entrée classique des attaques.', 'loginfence' ); ?></p></div></div>
					</div>
					<p class="lnf-inst-pro-note"><a href="<?php echo esc_url( admin_url( 'admin.php?page=loginfence-pro' ) ); ?>"><?php esc_html_e( 'Passer en Pro', 'loginfence' ); ?></a> — <?php esc_html_e( '2FA, reCAPTCHA, URL de connexion personnalisée et alertes e-mail.', 'loginfence' ); ?></p>
				</section>

				<footer class="lnf-inst-footer">
					<button type="button" class="lnf-btn lnf-btn-ghost lnf-step-prev"><?php esc_html_e( 'Retour', 'loginfence' ); ?></button>
					<button type="button" class="lnf-btn lnf-btn-primary lnf-step-next"><?php esc_html_e( 'Continuer', 'loginfence' ); ?></button>
					<button type="submit" class="lnf-btn lnf-btn-primary lnf-step-finish"><?php esc_html_e( 'Terminer et ouvrir le dashboard', 'loginfence' ); ?></button>
				</footer>
			</form>
		</div>
		<?php
	}

	/**
	 * Page « Passer en Pro » : niveaux de sécurité avancés.
	 */
	public static function render_pro() {
		$groups = array(
			__( 'Protection', 'loginfence' )        => array(
				array( __( 'Limitation des tentatives + blocage IP', 'loginfence' ), true, true ),
				array( __( 'Messages de sécurité personnalisés', 'loginfence' ), true, true ),
				array( __( 'reCAPTCHA v3 / hCaptcha sur la connexion', 'loginfence' ), false, true ),
				array( __( 'Double authentification (2FA)', 'loginfence' ), false, true ),
				array( __( 'URL de connexion personnalisée', 'loginfence' ), false, true ),
				array( __( 'Protection dédiée de /wp-admin (liste blanche IP)', 'loginfence' ), false, true ),
			),
			__( 'Surveillance', 'loginfence' )      => array(
				array( __( 'Journal des tentatives (audit complet)', 'loginfence' ), false, true ),
				array( __( 'Alertes e-mail après chaque blocage', 'loginfence' ), false, true ),
				array( __( 'Détection avancée des activités suspectes', 'loginfence' ), false, true ),
			),
			__( 'Contrôle', 'loginfence' )          => array(
				array( __( 'Blocage géographique (pays)', 'loginfence' ), false, true ),
				array( __( 'Sessions & appareils de confiance', 'loginfence' ), false, true ),
			),
		);

		$benefits = array(
			array(
				'icon'  => 'dashicons-shield-alt',
				'title' => __( 'Sécurité maximale', 'loginfence' ),
				'text'  => __( '2FA, reCAPTCHA et URL de connexion personnalisée : votre page de connexion devient une forteresse.', 'loginfence' ),
			),
			array(
				'icon'  => 'dashicons-chart-line',
				'title' => __( 'Surveillance complète', 'loginfence' ),
				'text'  => __( 'Journal étendu, alertes e-mail immédiates et détection des comportements suspects.', 'loginfence' ),
			),
			array(
				'icon'  => 'dashicons-superhero-alt',
				'title' => __( 'Sérénité totale', 'loginfence' ),
				'text'  => __( 'Blocage géographique, /wp-admin verrouillé et appareils de confiance : vous gardez le contrôle.', 'loginfence' ),
			),
		);
		$checkout = lnf_checkout_url();
		$license  = lnf_license_get();
		$pro      = lnf_is_pro();
		?>
		<div class="wrap lnf-wrap lnf-pro">
			<div class="lnf-hero lnf-pro-hero">
				<span class="lnf-hero-mark" aria-hidden="true">&#8734;</span>
				<h1><?php esc_html_e( 'LoginFence Pro', 'loginfence' ); ?></h1>
				<p><?php esc_html_e( 'Poussez la sécurité de votre page de connexion au niveau supérieur : protection avancée, surveillance complète et contrôle total.', 'loginfence' ); ?></p>
				<div class="lnf-hero-actions">
					<?php if ( ! $pro ) : ?>
						<?php if ( '' !== $checkout ) : ?>
							<button type="button" class="lnf-btn lnf-btn-pro lnf-open-checkout" data-checkout="<?php echo esc_url( $checkout ); ?>">
								<span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'Acheter Pro — paiement intégré', 'loginfence' ); ?>
							</button>
						<?php else : ?>
							<a class="lnf-btn lnf-btn-pro" href="<?php echo esc_url( self::pro_url() ); ?>" target="_blank" rel="noopener">
								<span class="dashicons dashicons-superhero-alt"></span> <?php esc_html_e( 'Passer en Pro', 'loginfence' ); ?>
							</a>
						<?php endif; ?>
					<?php endif; ?>
					<a class="lnf-btn lnf-btn-ghost is-light" href="<?php echo esc_url( admin_url( 'admin.php?page=loginfence' ) ); ?>">
						<?php esc_html_e( 'Revenir au dashboard', 'loginfence' ); ?>
					</a>
				</div>
			</div>

			<?php if ( $pro ) : ?>
				<section class="lnf-about-card lnf-pro-active">
					<h2><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Pro actif — merci pour votre soutien !', 'loginfence' ); ?></h2>
					<p>
						<?php esc_html_e( 'Licence :', 'loginfence' ); ?>
						<code><?php echo esc_html( strlen( $license['key'] ) > 10 ? substr( $license['key'], 0, 4 ) . '••••' . substr( $license['key'], -4 ) : $license['key'] ); ?></code>
					</p>
					<button type="button" class="button lnf-deactivate-license"><?php esc_html_e( 'Désactiver la licence sur ce site', 'loginfence' ); ?></button>
					<span class="lnf-license-status" aria-live="polite"></span>
				</section>
			<?php else : ?>
				<section class="lnf-about-card lnf-purchase-card">
					<h2><span class="dashicons dashicons-unlock"></span> <?php esc_html_e( 'Débloquer Pro sans quitter votre tableau de bord', 'loginfence' ); ?></h2>
					<ol class="lnf-purchase-steps">
						<li><?php esc_html_e( 'Cliquez sur « Acheter Pro » : le paiement sécurisé s’ouvre ici même.', 'loginfence' ); ?></li>
						<li><?php esc_html_e( 'Après l’achat, vous recevez votre clé de licence par e-mail.', 'loginfence' ); ?></li>
						<li><?php esc_html_e( 'Collez la clé ci-dessous : Pro est activé instantanément.', 'loginfence' ); ?></li>
					</ol>
					<div class="lnf-license-form">
						<label class="screen-reader-text" for="lnf-license-key"><?php esc_html_e( 'Clé de licence', 'loginfence' ); ?></label>
						<input type="text" id="lnf-license-key" class="lnf-input" placeholder="XXXX-XXXX-XXXX-XXXX" autocomplete="off">
						<button type="button" class="button button-primary lnf-activate-license"><?php esc_html_e( 'Activer Pro', 'loginfence' ); ?></button>
					</div>
					<p class="lnf-license-status" aria-live="polite"></p>
					<?php if ( '' === $checkout ) : ?>
						<p class="lnf-pro-note"><?php esc_html_e( 'Configuration vendeur (visible par les administrateurs uniquement) : définissez LOGINFENCE_CHECKOUT_URL dans wp-config.php pour ouvrir le paiement intégré, et LOGINFENCE_LICENSE_API pour valider les clés. D’ici là, le bouton du hero utilise le lien externe.', 'loginfence' ); ?></p>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( ! $pro && '' !== $checkout ) : ?>
				<div class="lnf-modal" id="lnf-checkout-modal" hidden>
					<div class="lnf-modal-backdrop" data-close></div>
					<div class="lnf-modal-box" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Paiement sécurisé', 'loginfence' ); ?>">
						<div class="lnf-modal-head">
							<strong><?php esc_html_e( 'Paiement sécurisé — LoginFence Pro', 'loginfence' ); ?></strong>
							<button type="button" class="lnf-modal-close" data-close aria-label="<?php esc_attr_e( 'Fermer', 'loginfence' ); ?>">×</button>
						</div>
						<iframe src="about:blank" title="<?php esc_attr_e( 'Paiement', 'loginfence' ); ?>"></iframe>
						<p class="lnf-modal-note"><?php esc_html_e( 'Paiement chiffré HTTPS. Après l’achat, collez votre clé de licence dans le formulaire ci-dessous.', 'loginfence' ); ?></p>
					</div>
				</div>
			<?php endif; ?>

			<div class="lnf-pro-benefits">
				<?php foreach ( $benefits as $benefit ) : ?>
					<div class="lnf-pro-benefit">
						<span class="dashicons <?php echo esc_attr( $benefit['icon'] ); ?>"></span>
						<h3><?php echo esc_html( $benefit['title'] ); ?></h3>
						<p><?php echo esc_html( $benefit['text'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="lnf-about-card lnf-pro-table-card">
				<h2><span class="dashicons dashicons-shield-alt"></span> <?php esc_html_e( 'Comparatif détaillé : Gratuit vs Pro', 'loginfence' ); ?></h2>
				<table class="lnf-pro-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Fonctionnalité', 'loginfence' ); ?></th>
							<th><?php esc_html_e( 'Gratuit', 'loginfence' ); ?></th>
							<th class="lnf-pro-col">Pro</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $groups as $group_label => $group_rows ) : ?>
							<tr class="lnf-pro-group"><td colspan="3"><?php echo esc_html( $group_label ); ?></td></tr>
							<?php foreach ( $group_rows as $row ) : ?>
								<tr>
									<td><?php echo esc_html( $row[0] ); ?></td>
									<td><?php echo $row[1] ? '<span class="dashicons dashicons-yes-alt is-yes"></span>' : '<span class="dashicons dashicons-no-alt is-no"></span>'; ?></td>
									<td class="lnf-pro-col"><span class="dashicons dashicons-yes-alt is-yes"></span></td>
								</tr>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="lnf-pro-note"><?php esc_html_e( 'Le module Pro est en préparation — le bouton « Passer en Pro » devient actif dès sa sortie (URL personnalisable via le filtre loginfence_pro_url).', 'loginfence' ); ?></p>
				<div class="lnf-hero-actions">
					<a class="lnf-btn lnf-btn-pro" href="<?php echo esc_url( self::pro_url() ); ?>" target="_blank" rel="noopener">
						<span class="dashicons dashicons-superhero-alt"></span> <?php esc_html_e( 'Passer en Pro', 'loginfence' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}
}

Lnf_Admin::init();
