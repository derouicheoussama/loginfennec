<?php
/**
 * Dashboard d'administration : menu, onglets, aperçu en direct,
 * page « À propos » et bouton de don.
 *
 * @package InfinityCustomizer
 */

defined( 'ABSPATH' ) || exit;

class Infcl_Admin {

	/**
	 * Déclare les hooks d'administration.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_installer_redirect' ), 1 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_infcl_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_infcl_wizard', array( __CLASS__, 'wizard_save' ) );
		add_action( 'admin_post_infcl_reset', array( __CLASS__, 'reset' ) );
		add_action( 'wp_ajax_infcl_preview_css', array( __CLASS__, 'ajax_preview_css' ) );
		add_action( 'wp_ajax_infcl_check_updates', array( __CLASS__, 'ajax_check_updates' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	/**
	 * Menu : personnalisation, page Pro et installateur (page cachée).
	 */
	public static function menu() {
		add_menu_page(
			__( 'Infinity Customizer', 'infinity-customizer' ),
			__( 'Infinity Customizer', 'infinity-customizer' ),
			'manage_options',
			'infinity-customizer',
			array( __CLASS__, 'render_page' ),
			'dashicons-admin-customizer',
			3
		);
		add_submenu_page(
			'infinity-customizer',
			__( 'Personnalisation de la connexion', 'infinity-customizer' ),
			__( 'Personnalisation', 'infinity-customizer' ),
			'manage_options',
			'infinity-customizer',
			array( __CLASS__, 'render_page' )
		);
		add_submenu_page(
			'infinity-customizer',
			__( 'Passer en Pro', 'infinity-customizer' ),
			__( 'Passer en Pro ✦', 'infinity-customizer' ),
			'manage_options',
			'infinity-customizer-pro',
			array( __CLASS__, 'render_pro' )
		);
		add_submenu_page(
			'infinity-customizer',
			__( 'À propos d’Infinity Customizer', 'infinity-customizer' ),
			__( 'À propos', 'infinity-customizer' ),
			'manage_options',
			'infinity-customizer-about',
			array( __CLASS__, 'render_about' )
		);
		add_submenu_page(
			null,
			__( 'Bienvenue — Infinity Customizer', 'infinity-customizer' ),
			__( 'Installateur', 'infinity-customizer' ),
			'manage_options',
			'infinity-customizer-installer',
			array( __CLASS__, 'render_installer' )
		);
	}

	/**
	 * À l'activation (ou après une mise à jour majeure) : ouvre
	 * l'installateur personnalisé une seule fois.
	 */
	public static function maybe_installer_redirect() {
		if ( ! get_option( 'infcl_pending_installer' ) ) {
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
		delete_option( 'infcl_pending_installer' );
		wp_safe_redirect( admin_url( 'admin.php?page=infinity-customizer-installer' ) );
		exit;
	}

	/**
	 * Charge les assets sur les pages du plugin.
	 *
	 * @param string $hook Page courante.
	 */
	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'infinity-customizer' ) ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_style(
			'infcl-admin',
			INFINITY_CUSTOMIZER_URL . 'assets/css/admin.css',
			array(),
			INFINITY_CUSTOMIZER_VERSION
		);
		wp_enqueue_script(
			'infcl-admin',
			INFINITY_CUSTOMIZER_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			INFINITY_CUSTOMIZER_VERSION,
			true
		);
		wp_localize_script(
			'infcl-admin',
			'INFCL_ADMIN',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'infcl_admin' ),
				'previewAction' => 'infcl_preview_css',
				'sitename'      => get_bloginfo( 'name' ),
			)
		);
	}

	/**
	 * Enregistre les réglages.
	 */
	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'infinity-customizer' ) );
		}
		check_admin_referer( 'infcl_save', 'infcl_nonce' );

		$input = isset( $_POST['infcl'] ) && is_array( $_POST['infcl'] ) ? wp_unslash( $_POST['infcl'] ) : array();
		update_option( INFINITY_CUSTOMIZER_OPTION, infcl_sanitize_settings( $input, null ), 'yes' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'infinity-customizer',
					'infcl-saved' => 1,
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
			wp_die( esc_html__( 'Accès refusé.', 'infinity-customizer' ) );
		}
		check_admin_referer( 'infcl_save', 'infcl_nonce' );

		delete_option( INFINITY_CUSTOMIZER_OPTION );
		add_option( INFINITY_CUSTOMIZER_OPTION, infcl_get_defaults(), '', 'yes' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'infinity-customizer',
					'infcl-reset' => 1,
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
		check_ajax_referer( 'infcl_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		$input   = isset( $_POST['infcl'] ) && is_array( $_POST['infcl'] ) ? wp_unslash( $_POST['infcl'] ) : array();
		$partial = infcl_sanitize_settings( $input, infcl_settings() );
		wp_send_json_success( array( 'css' => infcl_build_login_css( $partial ) ) );
	}

	/**
	 * Enregistre les choix de l'installateur (assistant de bienvenue).
	 */
	public static function wizard_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'infinity-customizer' ) );
		}
		check_admin_referer( 'infcl_wizard', 'infcl_wizard_nonce' );

		$base   = infcl_settings();
		$input  = isset( $_POST['infcl'] ) && is_array( $_POST['infcl'] ) ? wp_unslash( $_POST['infcl'] ) : array();
		update_option( INFINITY_CUSTOMIZER_OPTION, infcl_sanitize_settings( $input, $base ), 'yes' );
		delete_option( 'infcl_pending_installer' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'infinity-customizer',
					'infcl-welcome' => 1,
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
		check_ajax_referer( 'infcl_admin', 'nonce' );
		if ( ! current_user_can( 'update_plugins' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$release = Infcl_GitHub_Updater::fetch_latest_release( true );
		if ( ! $release || empty( $release['version'] ) || '' === $release['download'] ) {
			wp_send_json_error(
				array( 'message' => __( 'Impossible de joindre GitHub pour le moment. Réessayez plus tard.', 'infinity-customizer' ) )
			);
		}

		if ( version_compare( INFINITY_CUSTOMIZER_VERSION, $release['version'], '>=' ) ) {
			wp_send_json_success(
				array(
					'status'  => 'up_to_date',
					'version' => INFINITY_CUSTOMIZER_VERSION,
				)
			);
		}

		// Force la reconstruction du transient puis prépare le lien de mise à jour.
		if ( function_exists( 'wp_update_plugins' ) ) {
			wp_update_plugins();
		}
		$basename   = plugin_basename( INFINITY_CUSTOMIZER_FILE );
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
	 * Notices de confirmation.
	 */
	public static function notices() {
		if ( ! isset( $_GET['page'] ) ) {
			return;
		}
		if ( 'infinity-customizer' === $_GET['page'] && isset( $_GET['infcl-welcome'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>🎉 ' . esc_html__( 'Bienvenue dans Infinity Customizer !', 'infinity-customizer' ) . '</strong> ' . esc_html__( 'Votre page de connexion est prête — explorez les onglets pour la personnaliser.', 'infinity-customizer' ) . '</p></div>';
		}
		if ( 'infinity-customizer' === $_GET['page'] && isset( $_GET['infcl-saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Réglages enregistrés.', 'infinity-customizer' ) . '</strong> ' . esc_html__( 'Votre page de connexion est à jour.', 'infinity-customizer' ) . '</p></div>';
		}
		if ( 'infinity-customizer' === $_GET['page'] && isset( $_GET['infcl-reset'] ) ) {
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Réglages réinitialisés aux valeurs par défaut.', 'infinity-customizer' ) . '</p></div>';
		}
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
		echo '<div class="infcl-field"' . self::showif( $showif ) . '>';
		echo '<label class="infcl-label" for="infcl-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		echo '<div class="infcl-range-row">';
		printf(
			'<input type="range" id="infcl-f-%1$s" name="infcl[%1$s]" min="%2$d" max="%3$d" step="1" value="%4$d">',
			esc_attr( $key ),
			(int) $min,
			(int) $max,
			(int) $value
		);
		printf(
			'<output class="infcl-range-out" data-unit="%1$s">%2$s%1$s</output>',
			esc_attr( $unit ),
			esc_html( $value )
		);
		echo '</div>';
		if ( $desc ) {
			echo '<p class="infcl-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ couleur.
	 */
	protected static function field_color( $s, $key, $label, $desc = '', $showif = array() ) {
		echo '<div class="infcl-field"' . self::showif( $showif ) . '>';
		echo '<label class="infcl-label" for="infcl-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		printf(
			'<input type="text" class="infcl-color" id="infcl-f-%1$s" name="infcl[%1$s]" value="%2$s" data-default-color="%2$s">',
			esc_attr( $key ),
			esc_attr( $s[ $key ] )
		);
		if ( $desc ) {
			echo '<p class="infcl-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ texte / url.
	 */
	protected static function field_text( $s, $key, $label, $type = 'text', $placeholder = '', $desc = '', $showif = array() ) {
		echo '<div class="infcl-field"' . self::showif( $showif ) . '>';
		echo '<label class="infcl-label" for="infcl-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		printf(
			'<input type="%1$s" class="infcl-input" id="infcl-f-%2$s" name="infcl[%2$s]" value="%3$s" placeholder="%4$s" autocomplete="off">',
			esc_attr( $type ),
			esc_attr( $key ),
			esc_attr( $s[ $key ] ),
			esc_attr( $placeholder )
		);
		if ( $desc ) {
			echo '<p class="infcl-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ zone de texte.
	 */
	protected static function field_textarea( $s, $key, $label, $desc = '', $showif = array() ) {
		echo '<div class="infcl-field infcl-field-wide"' . self::showif( $showif ) . '>';
		echo '<label class="infcl-label" for="infcl-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		printf(
			'<textarea class="infcl-input" id="infcl-f-%1$s" name="infcl[%1$s]" rows="2">%2$s</textarea>',
			esc_attr( $key ),
			esc_textarea( $s[ $key ] )
		);
		if ( $desc ) {
			echo '<p class="infcl-desc">' . wp_kses_post( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ liste déroulante.
	 */
	protected static function field_select( $s, $key, $label, $options, $desc = '', $showif = array() ) {
		echo '<div class="infcl-field"' . self::showif( $showif ) . '>';
		echo '<label class="infcl-label" for="infcl-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		echo '<select class="infcl-input" id="infcl-f-' . esc_attr( $key ) . '" name="infcl[' . esc_attr( $key ) . ']">';
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
			echo '<p class="infcl-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ interrupteur.
	 */
	protected static function field_toggle( $s, $key, $label, $desc = '', $showif = array() ) {
		echo '<div class="infcl-field infcl-toggle-field"' . self::showif( $showif ) . '>';
		echo '<label class="infcl-switch"><input type="checkbox" name="infcl[' . esc_attr( $key ) . ']" value="1"' . checked( ! empty( $s[ $key ] ), true, false ) . '><span class="infcl-switch-ui"></span></label>';
		echo '<div class="infcl-toggle-text"><span class="infcl-label">' . esc_html( $label ) . '</span>';
		if ( $desc ) {
			echo '<p class="infcl-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div></div>';
	}

	/**
	 * Champ image (médiathèque).
	 */
	protected static function field_media( $s, $key, $label, $desc = '', $showif = array() ) {
		echo '<div class="infcl-field infcl-field-wide"' . self::showif( $showif ) . '>';
		echo '<label class="infcl-label">' . esc_html( $label ) . '</label>';
		echo '<div class="infcl-media">';
		if ( ! empty( $s[ $key ] ) ) {
			printf( '<img class="infcl-media-thumb" src="%s" alt="">', esc_url( $s[ $key ] ) );
		} else {
			echo '<img class="infcl-media-thumb" src="" alt="" hidden>';
		}
		printf( '<input type="url" class="infcl-input" name="infcl[%s]" value="%s" placeholder="https://…" autocomplete="off">', esc_attr( $key ), esc_attr( $s[ $key ] ) );
		echo '<span class="infcl-media-actions">';
		echo '<button type="button" class="button infcl-media-pick">' . esc_html__( 'Médiathèque', 'infinity-customizer' ) . '</button>';
		echo '<button type="button" class="button-link infcl-media-clear">' . esc_html__( 'Retirer', 'infinity-customizer' ) . '</button>';
		echo '</span></div>';
		if ( $desc ) {
			echo '<p class="infcl-desc">' . esc_html( $desc ) . '</p>';
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
			'dashboard' => array( __( 'Tableau de bord', 'infinity-customizer' ), 'dashicons-dashboard' ),
			'styles'    => array( __( 'Styles', 'infinity-customizer' ), 'dashicons-art' ),
			'logo'      => array( __( 'Logo', 'infinity-customizer' ), 'dashicons-format-image' ),
			'bg'        => array( __( 'Arrière-plan', 'infinity-customizer' ), 'dashicons-desktop' ),
			'form'      => array( __( 'Formulaire', 'infinity-customizer' ), 'dashicons-feedback' ),
			'links'     => array( __( 'Liens', 'infinity-customizer' ), 'dashicons-editor-unlink' ),
			'social'    => array( __( 'Réseaux sociaux', 'infinity-customizer' ), 'dashicons-share' ),
			'copyright' => array( __( 'Copyright', 'infinity-customizer' ), 'dashicons-text' ),
			'extras'    => array( __( 'Extras', 'infinity-customizer' ), 'dashicons-star-filled' ),
			'security'  => array( __( 'Sécurité', 'infinity-customizer' ), 'dashicons-shield-alt' ),
		);
	}

	/**
	 * Affiche la page de personnalisation.
	 */
	public static function render_page() {
		$s = infcl_settings();
		?>
		<div class="wrap infcl-wrap">
			<div class="infcl-topbar">
				<div class="infcl-brand">
					<span class="infcl-brand-mark" aria-hidden="true">&#8734;</span>
					<span class="infcl-brand-text">
						<strong>Infinity Customizer</strong>
						<em class="infcl-version"><?php echo esc_html( 'v' . INFINITY_CUSTOMIZER_VERSION ); ?></em>
					</span>
				</div>
				<div class="infcl-topbar-actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="infcl_reset">
						<?php wp_nonce_field( 'infcl_save', 'infcl_nonce' ); ?>
						<button type="submit" id="infcl-reset" class="infcl-btn infcl-btn-ghost">
							<?php esc_html_e( 'Réinitialiser', 'infinity-customizer' ); ?>
						</button>
					</form>
					<button type="submit" form="infcl-form" class="infcl-btn infcl-btn-primary">
						<?php esc_html_e( 'Enregistrer', 'infinity-customizer' ); ?>
					</button>
				</div>
			</div>

			<form id="infcl-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="infcl_save">
				<?php wp_nonce_field( 'infcl_save', 'infcl_nonce' ); ?>

				<nav class="infcl-tabs" aria-label="<?php esc_attr_e( 'Sections de personnalisation', 'infinity-customizer' ); ?>">
					<?php foreach ( self::tabs() as $id => $tab ) : ?>
						<button type="button" class="infcl-tab<?php echo 'dashboard' === $id ? ' is-active' : ''; ?>" data-tab="<?php echo esc_attr( $id ); ?>">
							<span class="dashicons <?php echo esc_attr( $tab[1] ); ?>" aria-hidden="true"></span>
							<?php echo esc_html( $tab[0] ); ?>
						</button>
					<?php endforeach; ?>
				</nav>

				<div class="infcl-layout">
					<div class="infcl-panels">
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

					<aside class="infcl-preview" aria-label="<?php esc_attr_e( 'Aperçu en direct', 'infinity-customizer' ); ?>">
						<div class="infcl-preview-bar">
							<span class="infcl-preview-title"><?php esc_html_e( 'Aperçu en direct', 'infinity-customizer' ); ?></span>
							<span class="infcl-preview-devices">
								<button type="button" class="infcl-device is-active" data-width="0" title="<?php esc_attr_e( 'Bureau', 'infinity-customizer' ); ?>"><span class="dashicons dashicons-desktop"></span></button>
								<button type="button" class="infcl-device" data-width="480" title="<?php esc_attr_e( 'Tablette', 'infinity-customizer' ); ?>"><span class="dashicons dashicons-tablet"></span></button>
								<button type="button" class="infcl-device" data-width="375" title="<?php esc_attr_e( 'Mobile', 'infinity-customizer' ); ?>"><span class="dashicons dashicons-smartphone"></span></button>
							</span>
							<span class="infcl-preview-actions">
								<button type="button" class="infcl-expand" title="<?php esc_attr_e( 'Aperçu plein écran', 'infinity-customizer' ); ?>"><span class="dashicons dashicons-fullscreen-exit-alt"></span></button>
								<button type="button" class="infcl-refresh" title="<?php esc_attr_e( 'Recharger l’aperçu', 'infinity-customizer' ); ?>"><span class="dashicons dashicons-update"></span></button>
								<a class="infcl-open" href="<?php echo esc_url( wp_login_url() ); ?>" target="_blank" rel="noopener" title="<?php esc_attr_e( 'Ouvrir dans un onglet', 'infinity-customizer' ); ?>"><span class="dashicons dashicons-external"></span></a>
							</span>
						</div>
						<div class="infcl-frame-holder">
							<iframe id="infcl-frame" src="<?php echo esc_url( wp_login_url() ); ?>" title="<?php esc_attr_e( 'Aperçu de la page de connexion', 'infinity-customizer' ); ?>" loading="lazy"></iframe>
						</div>
						<p class="infcl-preview-note"><?php esc_html_e( 'Les couleurs et effets sont appliqués en direct. Les liens, réseaux sociaux et copyright apparaissent après enregistrement.', 'infinity-customizer' ); ?></p>
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
			'<section class="infcl-panel%1$s" data-panel="%2$s"><header class="infcl-panel-head"><h2>%3$s</h2>%4$s</header><div class="infcl-panel-body">',
			'dashboard' === $id ? ' is-active' : '',
			esc_attr( $id ),
			esc_html( $title ),
			$desc ? '<p class="infcl-panel-desc">' . esc_html( $desc ) . '</p>' : ''
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
		self::panel_open( 'dashboard', __( 'Tableau de bord', 'infinity-customizer' ), __( 'Vue d’ensemble de votre page de connexion.', 'infinity-customizer' ) );

		$social_count  = count( infcl_get_social_networks( $s ) );
		$links_count   = ( ! empty( $s['hide_lost_password'] ) ? 1 : 0 ) + ( ! empty( $s['hide_back_to'] ) ? 1 : 0 ) + ( ! empty( $s['back_to_url'] ) || ! empty( $s['back_to_text'] ) ? 1 : 0 );
		$preset_labels = array(
			'glass'   => __( 'Effet verre', 'infinity-customizer' ),
			'minimal' => __( 'Minimal', 'infinity-customizer' ),
			'dark'    => __( 'Sombre', 'infinity-customizer' ),
			'sunset'  => __( 'Coucher de soleil', 'infinity-customizer' ),
			'ocean'   => __( 'Océan', 'infinity-customizer' ),
			'forest'  => __( 'Forêt', 'infinity-customizer' ),
			'custom'  => __( 'Personnalisé', 'infinity-customizer' ),
		);

		$cards = array(
			array(
				'icon'   => 'dashicons-art',
				'title'  => __( 'Style actif', 'infinity-customizer' ),
				'state'  => isset( $preset_labels[ $s['preset'] ] ) ? $preset_labels[ $s['preset'] ] : __( 'Personnalisé', 'infinity-customizer' ),
				'ok'     => true,
				'goto'   => 'styles',
				'button' => __( 'Changer de style', 'infinity-customizer' ),
			),
			array(
				'icon'   => 'dashicons-shield-alt',
				'title'  => __( 'Sécurité', 'infinity-customizer' ),
				'state'  => ! empty( $s['sec_enable'] )
					/* translators: 1 : nombre de tentatives, 2 : minutes. */
					? sprintf( __( 'Activé — %1$d tentatives, blocage %2$d min', 'infinity-customizer' ), (int) $s['sec_max_attempts'], (int) $s['sec_lockout_minutes'] )
					: __( 'Désactivé', 'infinity-customizer' ),
				'ok'     => ! empty( $s['sec_enable'] ),
				'goto'   => 'security',
				'button' => __( 'Configurer', 'infinity-customizer' ),
			),
			array(
				'icon'   => 'dashicons-share',
				'title'  => __( 'Réseaux sociaux', 'infinity-customizer' ),
				'state'  => $social_count > 0
					/* translators: %d : nombre de réseaux configurés. */
					? sprintf( _n( '%d réseau configuré', '%d réseaux configurés', $social_count, 'infinity-customizer' ), $social_count )
					: __( 'Aucun réseau configuré', 'infinity-customizer' ),
				'ok'     => $social_count > 0,
				'goto'   => 'social',
				'button' => __( 'Configurer', 'infinity-customizer' ),
			),
			array(
				'icon'   => 'dashicons-text',
				'title'  => __( 'Copyright', 'infinity-customizer' ),
				'state'  => ! empty( $s['copyright_enable'] ) ? __( 'Affiché', 'infinity-customizer' ) : __( 'Masqué', 'infinity-customizer' ),
				'ok'     => ! empty( $s['copyright_enable'] ),
				'goto'   => 'copyright',
				'button' => __( 'Configurer', 'infinity-customizer' ),
			),
			array(
				'icon'   => 'dashicons-format-image',
				'title'  => __( 'Logo', 'infinity-customizer' ),
				'state'  => ! empty( $s['logo_hide'] ) ? __( 'Masqué', 'infinity-customizer' ) : ( ! empty( $s['logo_url'] ) ? __( 'Image personnalisée', 'infinity-customizer' ) : __( 'Logo WordPress par défaut', 'infinity-customizer' ) ),
				'ok'     => ! empty( $s['logo_url'] ) && empty( $s['logo_hide'] ),
				'goto'   => 'logo',
				'button' => __( 'Personnaliser', 'infinity-customizer' ),
			),
			array(
				'icon'   => 'dashicons-editor-unlink',
				'title'  => __( 'Liens', 'infinity-customizer' ),
				'state'  => $links_count > 0 ? __( 'Liens personnalisés', 'infinity-customizer' ) : __( 'Liens par défaut', 'infinity-customizer' ),
				'ok'     => $links_count > 0,
				'goto'   => 'links',
				'button' => __( 'Personnaliser', 'infinity-customizer' ),
			),
			array(
				'icon'   => 'dashicons-download',
				'title'  => __( 'Mises à jour', 'infinity-customizer' ),
				'state'  => sprintf(
					/* translators: %s : dépôt GitHub. */
					__( 'Version %s — GitHub : ', 'infinity-customizer' ),
					INFINITY_CUSTOMIZER_VERSION
				) . INFINITY_CUSTOMIZER_GITHUB_REPO,
				'ok'     => true,
				'goto'   => '',
				'button' => __( 'Vérifier les mises à jour', 'infinity-customizer' ),
				'check'  => true,
			),
			array(
				'icon'   => 'dashicons-superhero-alt',
				'title'  => __( 'Infinity Customizer Pro', 'infinity-customizer' ),
				'state'  => __( '2FA, reCAPTCHA, URL de connexion personnalisée…', 'infinity-customizer' ),
				'ok'     => false,
				'goto'   => '',
				'link'   => admin_url( 'admin.php?page=infinity-customizer-pro' ),
				'button' => __( 'Passer en Pro ✦', 'infinity-customizer' ),
			),
		);

		echo '<div class="infcl-cards">';
		foreach ( $cards as $card ) {
			echo '<div class="infcl-card">';
			echo '<span class="infcl-card-icon"><span class="dashicons ' . esc_attr( $card['icon'] ) . '"></span></span>';
			echo '<div class="infcl-card-body"><h3>' . esc_html( $card['title'] ) . '</h3>';
			echo '<p><span class="infcl-chip ' . ( $card['ok'] ? 'is-on' : 'is-off' ) . '">' . wp_kses_post( $card['state'] ) . '</span></p>';
			if ( ! empty( $card['check'] ) ) {
				echo '<span class="infcl-card-link infcl-check-updates" tabindex="0"><span class="dashicons dashicons-update-alt"></span> ' . esc_html( $card['button'] ) . '</span>';
				echo '<span class="infcl-update-status" aria-live="polite"></span>';
			} elseif ( ! empty( $card['link'] ) ) {
				echo '<a class="infcl-card-link" href="' . esc_url( $card['link'] ) . '">' . esc_html( $card['button'] ) . ' <span class="dashicons dashicons-external"></span></a>';
			} else {
				echo '<button type="button" class="infcl-card-link" data-goto="' . esc_attr( $card['goto'] ) . '">' . esc_html( $card['button'] ) . '</button>';
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
		self::panel_open( 'styles', __( 'Styles modernes', 'infinity-customizer' ), __( 'Appliquez un style en un clic, puis affinez-le dans les onglets suivants.', 'infinity-customizer' ) );

		$presets = array(
			'glass'   => array( __( 'Effet verre', 'infinity-customizer' ), 'linear-gradient(135deg,#667eea,#764ba2)' ),
			'minimal' => array( __( 'Minimal', 'infinity-customizer' ), 'linear-gradient(135deg,#f5f6f8,#dfe3ea)' ),
			'dark'    => array( __( 'Sombre', 'infinity-customizer' ), 'linear-gradient(160deg,#0f172a,#334155)' ),
			'sunset'  => array( __( 'Coucher de soleil', 'infinity-customizer' ), 'linear-gradient(120deg,#f97316,#ec4899)' ),
			'ocean'   => array( __( 'Océan', 'infinity-customizer' ), 'linear-gradient(135deg,#0ea5e9,#2563eb)' ),
			'forest'  => array( __( 'Forêt', 'infinity-customizer' ), 'linear-gradient(135deg,#059669,#065f46)' ),
		);

		echo '<input type="hidden" name="infcl[preset]" value="' . esc_attr( $s['preset'] ) . '">';
		echo '<div class="infcl-presets">';
		foreach ( $presets as $key => $preset ) {
			printf(
				'<button type="button" class="infcl-preset%3$s" data-preset="%1$s"><span class="infcl-preset-preview" style="background:%2$s"></span><span class="infcl-preset-name">%4$s</span></button>',
				esc_attr( $key ),
				esc_attr( $preset[1] ),
				$s['preset'] === $key ? ' is-active' : '',
				esc_html( $preset[0] )
			);
		}
		echo '</div>';

		self::panel_close();
	}

	/**
	 * Panneau : logo.
	 */
	protected static function panel_logo( $s ) {
		self::panel_open( 'logo', __( 'Logo', 'infinity-customizer' ), __( 'Remplacez le logo WordPress par le vôtre.', 'infinity-customizer' ) );

		self::field_toggle( $s, 'logo_hide', __( 'Masquer complètement le logo', 'infinity-customizer' ) );
		self::field_media( $s, 'logo_url', __( 'Image du logo', 'infinity-customizer' ), __( 'SVG ou PNG transparent recommandé.', 'infinity-customizer' ), array( 'logo_hide' => 0 ) );
		self::field_range( $s, 'logo_width', __( 'Largeur', 'infinity-customizer' ), 40, 400, 'px', '', array( 'logo_hide' => 0 ) );
		self::field_range( $s, 'logo_height', __( 'Hauteur', 'infinity-customizer' ), 24, 300, 'px', '', array( 'logo_hide' => 0 ) );
		self::field_text( $s, 'logo_link', __( 'Lien du logo', 'infinity-customizer' ), 'url', 'https://exemple.com', __( 'Laisser vide pour pointer vers l’accueil du site.', 'infinity-customizer' ), array( 'logo_hide' => 0 ) );

		self::panel_close();
	}

	/**
	 * Panneau : arrière-plan.
	 */
	protected static function panel_background( $s ) {
		self::panel_open( 'bg', __( 'Arrière-plan', 'infinity-customizer' ), __( 'Couleur, dégradé ou image, avec flou et voile coloré.', 'infinity-customizer' ) );

		self::field_select(
			$s,
			'bg_type',
			__( 'Type de fond', 'infinity-customizer' ),
			array(
				'color'    => __( 'Couleur unie', 'infinity-customizer' ),
				'gradient' => __( 'Dégradé', 'infinity-customizer' ),
				'image'    => __( 'Image', 'infinity-customizer' ),
			)
		);
		self::field_color( $s, 'bg_color1', __( 'Couleur de fond / dégradé 1', 'infinity-customizer' ) );
		self::field_color( $s, 'bg_color2', __( 'Dégradé — couleur 2', 'infinity-customizer' ), '', array( 'bg_type' => 'gradient' ) );
		self::field_range( $s, 'bg_gradient_angle', __( 'Angle du dégradé', 'infinity-customizer' ), 0, 360, '°', '', array( 'bg_type' => 'gradient' ) );
		self::field_media( $s, 'bg_image', __( 'Image de fond', 'infinity-customizer' ), '', array( 'bg_type' => 'image' ) );
		self::field_select(
			$s,
			'bg_size',
			__( 'Affichage de l’image', 'infinity-customizer' ),
			array(
				'cover'   => __( 'Couvrir (cover)', 'infinity-customizer' ),
				'contain' => __( 'Contenir (contain)', 'infinity-customizer' ),
				'repeat'  => __( 'Répéter (motif)', 'infinity-customizer' ),
			),
			'',
			array( 'bg_type' => 'image' )
		);
		self::field_select(
			$s,
			'bg_position',
			__( 'Position de l’image', 'infinity-customizer' ),
			array(
				'center'       => __( 'Centrée', 'infinity-customizer' ),
				'top'          => __( 'En haut', 'infinity-customizer' ),
				'bottom'       => __( 'En bas', 'infinity-customizer' ),
				'left'         => __( 'À gauche', 'infinity-customizer' ),
				'right'        => __( 'À droite', 'infinity-customizer' ),
				'top-left'     => __( 'Haut gauche', 'infinity-customizer' ),
				'top-right'    => __( 'Haut droit', 'infinity-customizer' ),
				'bottom-left'  => __( 'Bas gauche', 'infinity-customizer' ),
				'bottom-right' => __( 'Bas droit', 'infinity-customizer' ),
			),
			'',
			array( 'bg_type' => 'image' )
		);
		self::field_range( $s, 'bg_blur', __( 'Flou de l’image', 'infinity-customizer' ), 0, 30, 'px', __( 'Contrôle du flou (backdrop).', 'infinity-customizer' ), array( 'bg_type' => 'image' ) );
		self::field_range( $s, 'bg_brightness', __( 'Luminosité de l’image', 'infinity-customizer' ), 30, 150, '%', '', array( 'bg_type' => 'image' ) );
		self::field_range( $s, 'bg_saturation', __( 'Saturation de l’image', 'infinity-customizer' ), 0, 200, '%', '', array( 'bg_type' => 'image' ) );
		self::field_color( $s, 'bg_overlay_color', __( 'Voile coloré', 'infinity-customizer' ), __( 'Couche de couleur superposée au fond.', 'infinity-customizer' ) );
		self::field_range( $s, 'bg_overlay_opacity', __( 'Opacité du voile', 'infinity-customizer' ), 0, 100, '%' );

		self::panel_close();
	}

	/**
	 * Panneau : formulaire.
	 */
	protected static function panel_form( $s ) {
		self::panel_open( 'form', __( 'Formulaire', 'infinity-customizer' ), __( 'Effet verre, couleurs des champs, bouton et liens.', 'infinity-customizer' ) );

		echo '<h3 class="infcl-group-title">' . esc_html__( 'Conteneur', 'infinity-customizer' ) . '</h3>';
		self::field_color( $s, 'form_bg', __( 'Fond du formulaire', 'infinity-customizer' ) );
		self::field_range( $s, 'form_opacity', __( 'Opacité du fond', 'infinity-customizer' ), 0, 100, '%' );
		self::field_range( $s, 'form_blur', __( 'Flou (effet verre)', 'infinity-customizer' ), 0, 40, 'px' );
		self::field_range( $s, 'form_radius', __( 'Arrondi des coins', 'infinity-customizer' ), 0, 60, 'px' );
		self::field_range( $s, 'form_width', __( 'Largeur du formulaire', 'infinity-customizer' ), 260, 560, 'px' );
		self::field_range( $s, 'form_padding', __( 'Espacement intérieur', 'infinity-customizer' ), 12, 80, 'px' );
		self::field_toggle( $s, 'form_shadow', __( 'Ombre portée', 'infinity-customizer' ) );

		echo '<h3 class="infcl-group-title">' . esc_html__( 'Textes et champs', 'infinity-customizer' ) . '</h3>';
		self::field_color( $s, 'text_color', __( 'Texte du formulaire', 'infinity-customizer' ) );
		self::field_color( $s, 'label_color', __( 'Libellés', 'infinity-customizer' ) );
		self::field_color( $s, 'input_bg', __( 'Fond des champs', 'infinity-customizer' ) );
		self::field_color( $s, 'input_color', __( 'Texte des champs', 'infinity-customizer' ) );
		self::field_color( $s, 'input_border', __( 'Bordure des champs', 'infinity-customizer' ) );

		echo '<h3 class="infcl-group-title">' . esc_html__( 'Bouton « Se connecter »', 'infinity-customizer' ) . '</h3>';
		self::field_color( $s, 'button_bg', __( 'Couleur du bouton', 'infinity-customizer' ) );
		self::field_color( $s, 'button_hover', __( 'Couleur au survol', 'infinity-customizer' ) );
		self::field_range( $s, 'button_radius', __( 'Arrondi du bouton', 'infinity-customizer' ), 0, 40, 'px' );

		echo '<h3 class="infcl-group-title">' . esc_html__( 'Liens', 'infinity-customizer' ) . '</h3>';
		self::field_color( $s, 'link_color', __( 'Couleur des liens', 'infinity-customizer' ) );

		self::panel_close();
	}

	/**
	 * Panneau : liens.
	 */
	protected static function panel_links( $s ) {
		self::panel_open( 'links', __( 'Liens de la page de connexion', 'infinity-customizer' ), __( 'Modifiez la cible et le texte des liens, ou masquez-les.', 'infinity-customizer' ) );

		self::field_toggle( $s, 'hide_lost_password', __( 'Masquer « Mot de passe perdu ? »', 'infinity-customizer' ) );
		self::field_toggle( $s, 'hide_back_to', __( 'Masquer « Retour au site »', 'infinity-customizer' ) );
		self::field_text( $s, 'back_to_text', __( 'Texte du lien « Retour au site »', 'infinity-customizer' ), 'text', __( 'ex. : Retour à la boutique', 'infinity-customizer' ) );
		self::field_text( $s, 'back_to_url', __( 'Cible du lien « Retour au site »', 'infinity-customizer' ), 'url', 'https://exemple.com' );
		self::field_toggle( $s, 'hide_register', __( 'Masquer le lien « S’enregistrer »', 'infinity-customizer' ) );
		self::field_text( $s, 'register_text', __( 'Texte du lien « S’enregistrer »', 'infinity-customizer' ), 'text', __( 'ex. : Créer un compte client', 'infinity-customizer' ) );

		self::panel_close();
	}

	/**
	 * Panneau : réseaux sociaux.
	 */
	protected static function panel_social( $s ) {
		self::panel_open( 'social', __( 'Icônes de réseaux sociaux', 'infinity-customizer' ), __( 'Renseignez une URL pour afficher l’icône — laissez vide pour la masquer.', 'infinity-customizer' ) );

		self::field_toggle( $s, 'social_enable', __( 'Afficher les icônes sociales', 'infinity-customizer' ) );
		self::field_toggle( $s, 'social_brand', __( 'Couleurs officielles des marques', 'infinity-customizer' ), __( 'Chaque icône reprend sa couleur officielle : Facebook bleu, X noir, dégradé Instagram, LinkedIn bleu, YouTube rouge.', 'infinity-customizer' ) );
		self::field_select(
			$s,
			'social_style',
			__( 'Forme des icônes', 'infinity-customizer' ),
			array(
				'circle'  => __( 'Cercle', 'infinity-customizer' ),
				'rounded' => __( 'Arrondi', 'infinity-customizer' ),
				'square'  => __( 'Carré', 'infinity-customizer' ),
			),
			'',
			array( 'social_enable' => 1 )
		);
		self::field_range( $s, 'social_size', __( 'Taille des icônes', 'infinity-customizer' ), 28, 72, 'px', '', array( 'social_enable' => 1 ) );
		self::field_color( $s, 'social_icon_color', __( 'Couleur de l’icône', 'infinity-customizer' ), '', array( 'social_enable' => 1 ) );
		self::field_color( $s, 'social_icon_bg', __( 'Fond de l’icône', 'infinity-customizer' ), '', array( 'social_enable' => 1 ) );
		self::field_range( $s, 'social_icon_bg_opacity', __( 'Opacité du fond d’icône', 'infinity-customizer' ), 0, 100, '%', '', array( 'social_enable' => 1 ) );

		echo '<h3 class="infcl-group-title">' . esc_html__( 'Réseaux', 'infinity-customizer' ) . '</h3>';
		self::field_text( $s, 'social_facebook', __( 'Facebook', 'infinity-customizer' ), 'url', 'https://facebook.com/votre-page' );
		self::field_text( $s, 'social_twitter', __( 'X (Twitter)', 'infinity-customizer' ), 'url', 'https://x.com/votre-compte' );
		self::field_text( $s, 'social_instagram', __( 'Instagram', 'infinity-customizer' ), 'url', 'https://instagram.com/votre-compte' );
		self::field_text( $s, 'social_linkedin', __( 'LinkedIn', 'infinity-customizer' ), 'url', 'https://linkedin.com/in/votre-profil' );
		self::field_text( $s, 'social_youtube', __( 'YouTube', 'infinity-customizer' ), 'url', 'https://youtube.com/@votre-chaine' );
		self::field_text( $s, 'social_email', __( 'E-mail', 'infinity-customizer' ), 'email', 'contact@exemple.com' );

		self::panel_close();
	}

	/**
	 * Panneau : copyright.
	 */
	protected static function panel_copyright( $s ) {
		self::panel_open( 'copyright', __( 'Copyright', 'infinity-customizer' ), __( 'Affichez votre mention de copyright sous le formulaire.', 'infinity-customizer' ) );

		self::field_toggle( $s, 'copyright_enable', __( 'Afficher le copyright', 'infinity-customizer' ) );
		self::field_textarea(
			$s,
			'copyright_text',
			__( 'Texte du copyright', 'infinity-customizer' ),
			/* translators: les balises <code> sont des variables. */
			sprintf( __( 'Variables disponibles : %1$s (année) et %2$s (nom du site).', 'infinity-customizer' ), '<code>{year}</code>', '<code>{sitename}</code>' ),
			array( 'copyright_enable' => 1 )
		);

		self::panel_close();
	}

	/**
	 * Panneau : extras (message d'accueil, typographie, animation).
	 */
	protected static function panel_extras( $s ) {
		self::panel_open( 'extras', __( 'Extras', 'infinity-customizer' ), __( 'Message d’accueil, typographie et animation d’entrée de la page de connexion.', 'infinity-customizer' ) );

		echo '<h3 class="infcl-group-title">' . esc_html__( 'Message de bienvenue', 'infinity-customizer' ) . '</h3>';
		self::field_toggle( $s, 'welcome_enable', __( 'Afficher un message d’accueil', 'infinity-customizer' ), __( 'Titre et sous-titre affichés au-dessus du formulaire.', 'infinity-customizer' ) );
		self::field_text( $s, 'welcome_title', __( 'Titre', 'infinity-customizer' ), 'text', __( 'ex. : Bon retour parmi nous ✨', 'infinity-customizer' ), '', array( 'welcome_enable' => 1 ) );
		self::field_text( $s, 'welcome_subtitle', __( 'Sous-titre', 'infinity-customizer' ), 'text', __( 'ex. : Connectez-vous pour gérer votre boutique.', 'infinity-customizer' ), '', array( 'welcome_enable' => 1 ) );

		echo '<h3 class="infcl-group-title">' . esc_html__( 'Typographie', 'infinity-customizer' ) . '</h3>';
		self::field_select(
			$s,
			'font_family',
			__( 'Police', 'infinity-customizer' ),
			array(
				'system'  => __( 'Système (moderne)', 'infinity-customizer' ),
				'serif'   => __( 'Serif élégante', 'infinity-customizer' ),
				'rounded' => __( 'Arrondie', 'infinity-customizer' ),
				'mono'    => __( 'Monospace', 'infinity-customizer' ),
			)
		);
		self::field_range( $s, 'font_size', __( 'Taille du texte', 'infinity-customizer' ), 12, 18, 'px' );

		echo '<h3 class="infcl-group-title">' . esc_html__( 'Animation d’entrée', 'infinity-customizer' ) . '</h3>';
		self::field_select(
			$s,
			'anim',
			__( 'Effet à l’ouverture de la page', 'infinity-customizer' ),
			array(
				'none'  => __( 'Aucune', 'infinity-customizer' ),
				'fade'  => __( 'Fondu', 'infinity-customizer' ),
				'slide' => __( 'Glissement vers le haut', 'infinity-customizer' ),
				'zoom'  => __( 'Zoom', 'infinity-customizer' ),
			),
			__( 'Désactivée automatiquement si l’utilisateur demande moins d’animations.', 'infinity-customizer' )
		);

		self::panel_close();
	}

	/**
	 * Panneau : sécurité.
	 */
	protected static function panel_security( $s ) {
		self::panel_open( 'security', __( 'Sécurité de la connexion', 'infinity-customizer' ), __( 'Bloquez les attaques par force brute sur la page de connexion.', 'infinity-customizer' ) );

		self::field_toggle( $s, 'sec_enable', __( 'Limiter les tentatives de connexion', 'infinity-customizer' ), __( 'Après N échecs, l’adresse IP et l’identifiant sont bloqués temporairement.', 'infinity-customizer' ) );
		self::field_range( $s, 'sec_max_attempts', __( 'Tentatives autorisées', 'infinity-customizer' ), 1, 20, '', '', array( 'sec_enable' => 1 ) );
		self::field_range( $s, 'sec_lockout_minutes', __( 'Durée du blocage', 'infinity-customizer' ), 1, 1440, 'min', '', array( 'sec_enable' => 1 ) );
		self::field_text( $s, 'sec_lock_message', __( 'Message de blocage', 'infinity-customizer' ), 'text', '', sprintf( __( 'Utilisez %%d pour la durée restante en minutes.', 'infinity-customizer' ) ), array( 'sec_enable' => 1 ) );
		self::field_toggle( $s, 'sec_generic_error', __( 'Masquer le détail des erreurs', 'infinity-customizer' ), __( 'Affiche un message générique au lieu de « mot de passe incorrect ».', 'infinity-customizer' ) );
		self::field_toggle( $s, 'sec_hide_language_switcher', __( 'Masquer le sélecteur de langue', 'infinity-customizer' ) );

		echo '<div class="infcl-pro-teaser">';
		echo '<div class="infcl-pro-teaser-text"><h4>✦ ' . esc_html__( 'Niveaux de sécurité avancés — Infinity Customizer Pro', 'infinity-customizer' ) . '</h4><p>'
			. esc_html__( 'Double authentification (2FA), reCAPTCHA v3, URL de connexion personnalisée, alertes e-mail, journal des tentatives et blocage géographique.', 'infinity-customizer' )
			. '</p></div>';
		echo '<a class="infcl-btn infcl-btn-pro" href="' . esc_url( admin_url( 'admin.php?page=infinity-customizer-pro' ) ) . '">' . esc_html__( 'Passer en Pro', 'infinity-customizer' ) . '</a>';
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
		return apply_filters( 'infinity_customizer_donate_url', 'https://www.paypal.com/donate' );
	}

	/**
	 * Affiche la page « À propos ».
	 */
	public static function render_about() {
		?>
		<div class="wrap infcl-wrap infcl-about">
			<div class="infcl-hero">
				<span class="infcl-hero-mark" aria-hidden="true">&#8734;</span>
				<h1><?php esc_html_e( 'Infinity Customizer', 'infinity-customizer' ); ?></h1>
				<p>
					<?php esc_html_e( 'La page de connexion de WordPress, enfin à votre image.', 'infinity-customizer' ); ?><br>
					<span class="infcl-version">v<?php echo esc_html( INFINITY_CUSTOMIZER_VERSION ); ?></span>
				</p>
				<div class="infcl-hero-actions">
					<a class="infcl-btn infcl-btn-donate" href="<?php echo esc_url( self::donate_url() ); ?>" target="_blank" rel="noopener">
						<span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'Faire un don', 'infinity-customizer' ); ?>
					</a>
					<a class="infcl-btn infcl-btn-ghost is-light" href="https://github.com/derouiche-oussama/infinity-customizer" target="_blank" rel="noopener">
						<span class="dashicons dashicons-github"></span> <?php esc_html_e( 'Voir sur GitHub', 'infinity-customizer' ); ?>
					</a>
				</div>
			</div>

			<div class="infcl-about-grid">
				<section class="infcl-about-card">
					<h2><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'Développeur', 'infinity-customizer' ); ?></h2>
					<p class="infcl-about-dev"><strong><?php esc_html_e( 'Derouiche Oussama', 'infinity-customizer' ); ?></strong></p>
					<p><?php esc_html_e( 'Créateur du plugin, passionné par WordPress et les interfaces modernes.', 'infinity-customizer' ); ?></p>
					<div class="infcl-dev-social" aria-label="<?php esc_attr_e( 'Réseaux du développeur', 'infinity-customizer' ); ?>">
						<?php foreach ( self::dev_socials() as $network ) : ?>
							<a class="infcl-dev-icon" href="<?php echo esc_url( $network['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $network['label'] ); ?>" title="<?php echo esc_attr( $network['label'] ); ?>">
								<span class="dashicons <?php echo esc_attr( $network['icon'] ); ?>"></span>
							</a>
						<?php endforeach; ?>
					</div>
					<p class="infcl-about-links">
						<a href="https://github.com/derouiche-oussama" target="_blank" rel="noopener">GitHub</a> ·
						<a href="https://profiles.wordpress.org/" target="_blank" rel="noopener">WordPress.org</a>
					</p>
				</section>

				<section class="infcl-about-card">
					<h2><span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Fonctionnalités', 'infinity-customizer' ); ?></h2>
					<ul class="infcl-about-list">
						<li><?php esc_html_e( 'Logo personnalisé + lien du logo', 'infinity-customizer' ); ?></li>
						<li><?php esc_html_e( 'Arrière-plan : couleur, dégradé ou image', 'infinity-customizer' ); ?></li>
						<li><?php esc_html_e( 'Contrôles de flou et d’opacité', 'infinity-customizer' ); ?></li>
						<li><?php esc_html_e( '6 styles modernes (effet verre, sombre…)', 'infinity-customizer' ); ?></li>
						<li><?php esc_html_e( 'Liens : personnalisation ou masquage', 'infinity-customizer' ); ?></li>
						<li><?php esc_html_e( 'Icônes de réseaux sociaux', 'infinity-customizer' ); ?></li>
						<li><?php esc_html_e( 'Mention de copyright', 'infinity-customizer' ); ?></li>
						<li><?php esc_html_e( 'Blocage des tentatives de mot de passe', 'infinity-customizer' ); ?></li>
						<li><?php esc_html_e( 'Aperçu en direct 100 % responsive', 'infinity-customizer' ); ?></li>
						<li><?php esc_html_e( 'Mises à jour automatiques via GitHub', 'infinity-customizer' ); ?></li>
					</ul>
				</section>

				<section class="infcl-about-card">
					<h2><span class="dashicons dashicons-cloud"></span> <?php esc_html_e( 'Mises à jour', 'infinity-customizer' ); ?></h2>
					<p>
						<?php
						printf(
							/* translators: %s : dépôt GitHub. */
							esc_html__( 'Ce plugin se met à jour automatiquement depuis les releases GitHub du dépôt :', 'infinity-customizer' )
						);
						echo ' <code>' . esc_html( INFINITY_CUSTOMIZER_GITHUB_REPO ) . '</code>';
						?>
					</p>
					<p><?php esc_html_e( 'Publiez un nouveau tag (ex. v1.2.1) : l’action GitHub construit le zip et propage la mise à jour à tous les sites.', 'infinity-customizer' ); ?></p>
					<div class="infcl-about-actions">
						<button type="button" class="infcl-btn infcl-btn-ghost infcl-check-updates"><span class="dashicons dashicons-update-alt"></span> <?php esc_html_e( 'Vérifier les mises à jour', 'infinity-customizer' ); ?></button>
						<span class="infcl-update-status" aria-live="polite"></span>
					</div>
				</section>

				<section class="infcl-about-card">
					<h2><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e( 'État du système', 'infinity-customizer' ); ?></h2>
					<ul class="infcl-about-status">
						<li><strong><?php esc_html_e( 'Version du plugin', 'infinity-customizer' ); ?></strong> <?php echo esc_html( INFINITY_CUSTOMIZER_VERSION ); ?></li>
						<li><strong><?php esc_html_e( 'Version de WordPress', 'infinity-customizer' ); ?></strong> <?php echo esc_html( get_bloginfo( 'version' ) ); ?></li>
						<li><strong><?php esc_html_e( 'Version de PHP', 'infinity-customizer' ); ?></strong> <?php echo esc_html( PHP_VERSION ); ?></li>
						<li><strong><?php esc_html_e( 'Site', 'infinity-customizer' ); ?></strong> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></li>
					</ul>
				</section>
			</div>

			<section class="infcl-about-card infcl-about-support">
				<h2><span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'Soutenir le projet', 'infinity-customizer' ); ?></h2>
				<p><?php esc_html_e( 'Infinity Customizer est développé sur le temps personnel. Si ce plugin vous est utile, un petit don aide à le maintenir, à l’améliorer et à le garder gratuit.', 'infinity-customizer' ); ?></p>
				<div class="infcl-hero-actions">
					<a class="infcl-btn infcl-btn-donate" href="<?php echo esc_url( self::donate_url() ); ?>" target="_blank" rel="noopener">
						<span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'Faire un don via PayPal', 'infinity-customizer' ); ?>
					</a>
					<a class="infcl-btn infcl-btn-ghost" href="https://wordpress.org/support/plugin/infinity-customizer/" target="_blank" rel="noopener">
						<?php esc_html_e( 'Forum d’entraide', 'infinity-customizer' ); ?>
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
	 *
	 * @return array
	 */
	protected static function dev_socials() {
		return apply_filters(
			'infinity_customizer_dev_socials',
			array(
				array(
					'label' => 'GitHub',
					'icon'  => 'dashicons-github',
					'url'   => 'https://github.com/derouiche-oussama',
				),
				array(
					'label' => 'LinkedIn',
					'icon'  => 'dashicons-linkedin',
					'url'   => 'https://www.linkedin.com/',
				),
				array(
					'label' => 'X (Twitter)',
					'icon'  => 'dashicons-twitter',
					'url'   => 'https://x.com/',
				),
				array(
					'label' => 'Facebook',
					'icon'  => 'dashicons-facebook-alt',
					'url'   => 'https://www.facebook.com/',
				),
				array(
					'label' => __( 'Site web', 'infinity-customizer' ),
					'icon'  => 'dashicons-admin-links',
					'url'   => home_url( '/' ),
				),
				array(
					'label' => __( 'E-mail', 'infinity-customizer' ),
					'icon'  => 'dashicons-email-alt',
					'url'   => 'mailto:contact@example.com',
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
		return apply_filters( 'infinity_customizer_pro_url', '#' );
	}

	/**
	 * Installateur : assistant de bienvenue en 3 étapes.
	 */
	public static function render_installer() {
		$s = infcl_settings();
		?>
		<div class="wrap infcl-installer">
			<header class="infcl-inst-hero">
				<span class="infcl-inst-mark" aria-hidden="true">&#8734;</span>
				<h1><?php esc_html_e( 'Bienvenue dans Infinity Customizer', 'infinity-customizer' ); ?></h1>
				<p><?php esc_html_e( 'Transformez votre page de connexion en 3 étapes : choisissez un style moderne, activez la protection anti force brute, et c’est parti.', 'infinity-customizer' ); ?></p>
				<ol class="infcl-inst-steps" aria-hidden="true">
					<li class="is-active" data-step-dot="1"><?php esc_html_e( 'Bienvenue', 'infinity-customizer' ); ?></li>
					<li data-step-dot="2"><?php esc_html_e( 'Style', 'infinity-customizer' ); ?></li>
					<li data-step-dot="3"><?php esc_html_e( 'Sécurité', 'infinity-customizer' ); ?></li>
				</ol>
			</header>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="infcl_wizard">
				<input type="hidden" name="infcl[preset]" value="<?php echo esc_attr( $s['preset'] ); ?>">
				<?php wp_nonce_field( 'infcl_wizard', 'infcl_wizard_nonce' ); ?>

				<section class="infcl-wstep is-active" data-step="1">
					<div class="infcl-inst-grid">
						<div class="infcl-inst-feature"><span class="dashicons dashicons-format-image"></span><h3><?php esc_html_e( 'Logo & arrière-plan', 'infinity-customizer' ); ?></h3><p><?php esc_html_e( 'Votre logo, votre image de fond, flou et voile réglables.', 'infinity-customizer' ); ?></p></div>
						<div class="infcl-inst-feature"><span class="dashicons dashicons-art"></span><h3><?php esc_html_e( '6 styles modernes', 'infinity-customizer' ); ?></h3><p><?php esc_html_e( 'Effet verre, sombre, coucher de soleil… un clic, tout est prêt.', 'infinity-customizer' ); ?></p></div>
						<div class="infcl-inst-feature"><span class="dashicons dashicons-shield-alt"></span><h3><?php esc_html_e( 'Anti force brute', 'infinity-customizer' ); ?></h3><p><?php esc_html_e( 'Blocage automatique des tentatives de mot de passe.', 'infinity-customizer' ); ?></p></div>
						<div class="infcl-inst-feature"><span class="dashicons dashicons-share"></span><h3><?php esc_html_e( 'Social & copyright', 'infinity-customizer' ); ?></h3><p><?php esc_html_e( 'Icônes de réseaux sociaux et votre mention de copyright.', 'infinity-customizer' ); ?></p></div>
					</div>
				</section>

				<section class="infcl-wstep" data-step="2">
					<h2><?php esc_html_e( 'Choisissez votre style', 'infinity-customizer' ); ?></h2>
					<p class="infcl-inst-desc"><?php esc_html_e( 'Vous pourrez tout affiner plus tard (couleurs, flou, opacité…).', 'infinity-customizer' ); ?></p>
					<div class="infcl-presets">
						<?php
						$presets = array(
							'glass'   => array( __( 'Effet verre', 'infinity-customizer' ), 'linear-gradient(135deg,#667eea,#764ba2)' ),
							'minimal' => array( __( 'Minimal', 'infinity-customizer' ), 'linear-gradient(135deg,#f5f6f8,#dfe3ea)' ),
							'dark'    => array( __( 'Sombre', 'infinity-customizer' ), 'linear-gradient(160deg,#0f172a,#334155)' ),
							'sunset'  => array( __( 'Coucher de soleil', 'infinity-customizer' ), 'linear-gradient(120deg,#f97316,#ec4899)' ),
							'ocean'   => array( __( 'Océan', 'infinity-customizer' ), 'linear-gradient(135deg,#0ea5e9,#2563eb)' ),
							'forest'  => array( __( 'Forêt', 'infinity-customizer' ), 'linear-gradient(135deg,#059669,#065f46)' ),
						);
						foreach ( $presets as $key => $preset ) {
							printf(
								'<button type="button" class="infcl-preset%3$s" data-preset="%1$s"><span class="infcl-preset-preview" style="background:%2$s"></span><span class="infcl-preset-name">%4$s</span></button>',
								esc_attr( $key ),
								esc_attr( $preset[1] ),
								$s['preset'] === $key ? ' is-active' : '',
								esc_html( $preset[0] )
							);
						}
						?>
					</div>
				</section>

				<section class="infcl-wstep" data-step="3">
					<h2><?php esc_html_e( 'Protégez votre page de connexion', 'infinity-customizer' ); ?></h2>
					<p class="infcl-inst-desc"><?php esc_html_e( 'Recommandé : bloquez les attaques par force brute dès maintenant.', 'infinity-customizer' ); ?></p>
					<div class="infcl-inst-security">
						<label class="infcl-switch"><input type="checkbox" name="infcl[sec_enable]" value="1" <?php checked( ! empty( $s['sec_enable'] ) ); ?>><span class="infcl-switch-ui"></span></label>
						<div>
							<strong><?php esc_html_e( 'Limiter les tentatives de connexion', 'infinity-customizer' ); ?></strong>
							<p class="infcl-inst-desc"><?php esc_html_e( 'Après N échecs, l’adresse IP et l’identifiant sont bloqués temporairement.', 'infinity-customizer' ); ?></p>
						</div>
						<div class="infcl-inst-attempts">
							<label for="infcl-wizard-attempts"><?php esc_html_e( 'Tentatives autorisées', 'infinity-customizer' ); ?></label>
							<input type="number" id="infcl-wizard-attempts" class="infcl-input" name="infcl[sec_max_attempts]" min="1" max="20" value="<?php echo esc_attr( $s['sec_max_attempts'] ); ?>">
						</div>
					</div>
					<p class="infcl-inst-pro-note">✦ <a href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-customizer-pro' ) ); ?>"><?php esc_html_e( 'Passer en Pro', 'infinity-customizer' ); ?></a> — <?php esc_html_e( '2FA, reCAPTCHA, URL de connexion personnalisée et alertes e-mail.', 'infinity-customizer' ); ?></p>
				</section>

				<footer class="infcl-inst-footer">
					<button type="button" class="infcl-btn infcl-btn-ghost infcl-step-prev"><?php esc_html_e( 'Retour', 'infinity-customizer' ); ?></button>
					<button type="button" class="infcl-btn infcl-btn-primary infcl-step-next"><?php esc_html_e( 'Continuer', 'infinity-customizer' ); ?></button>
					<button type="submit" class="infcl-btn infcl-btn-primary infcl-step-finish"><?php esc_html_e( 'Terminer et ouvrir le dashboard', 'infinity-customizer' ); ?></button>
				</footer>
			</form>
		</div>
		<?php
	}

	/**
	 * Page « Passer en Pro » : niveaux de sécurité avancés.
	 */
	public static function render_pro() {
		$rows = array(
			array( __( 'Limitation des tentatives + blocage IP', 'infinity-customizer' ), true, true ),
			array( __( 'Messages de sécurité personnalisés', 'infinity-customizer' ), true, true ),
			array( __( 'Journal des tentatives (audit complet)', 'infinity-customizer' ), false, true ),
			array( __( 'Alertes e-mail après chaque blocage', 'infinity-customizer' ), false, true ),
			array( __( 'reCAPTCHA v3 / hCaptcha sur la connexion', 'infinity-customizer' ), false, true ),
			array( __( 'Double authentification (2FA)', 'infinity-customizer' ), false, true ),
			array( __( 'URL de connexion personnalisée', 'infinity-customizer' ), false, true ),
			array( __( 'Blocage géographique (pays)', 'infinity-customizer' ), false, true ),
			array( __( 'Durcissement XML-RPC & REST', 'infinity-customizer' ), false, true ),
			array( __( 'Sessions & appareils de confiance', 'infinity-customizer' ), false, true ),
		);
		?>
		<div class="wrap infcl-wrap infcl-pro">
			<div class="infcl-hero infcl-pro-hero">
				<span class="infcl-hero-mark" aria-hidden="true">&#10022;</span>
				<h1><?php esc_html_e( 'Infinity Customizer Pro', 'infinity-customizer' ); ?></h1>
				<p><?php esc_html_e( 'Poussez la sécurité de votre page de connexion au niveau supérieur : protection avancée, surveillance et contrôle total.', 'infinity-customizer' ); ?></p>
				<div class="infcl-hero-actions">
					<a class="infcl-btn infcl-btn-pro" href="<?php echo esc_url( self::pro_url() ); ?>" target="_blank" rel="noopener">
						<span class="dashicons dashicons-superhero-alt"></span> <?php esc_html_e( 'Passer en Pro', 'infinity-customizer' ); ?>
					</a>
					<a class="infcl-btn infcl-btn-ghost is-light" href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-customizer' ) ); ?>">
						<?php esc_html_e( 'Revenir au dashboard', 'infinity-customizer' ); ?>
					</a>
				</div>
			</div>

			<div class="infcl-about-card infcl-pro-table-card">
				<h2><span class="dashicons dashicons-shield-alt"></span> <?php esc_html_e( 'Niveaux de sécurité : Gratuit vs Pro', 'infinity-customizer' ); ?></h2>
				<table class="infcl-pro-table">
					<thead>
						<tr><th><?php esc_html_e( 'Fonctionnalité', 'infinity-customizer' ); ?></th><th><?php esc_html_e( 'Gratuit', 'infinity-customizer' ); ?></th><th>Pro ✦</th></tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row[0] ); ?></td>
								<td><?php echo $row[1] ? '<span class="dashicons dashicons-yes-alt is-yes"></span>' : '<span class="dashicons dashicons-no-alt is-no"></span>'; ?></td>
								<td><span class="dashicons dashicons-yes-alt is-yes"></span></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="infcl-pro-note"><?php esc_html_e( 'Le module Pro est en préparation — le bouton « Passer en Pro » devient actif dès sa sortie (URL personnalisable via le filtre infinity_customizer_pro_url).', 'infinity-customizer' ); ?></p>
			</div>
		</div>
		<?php
	}
}

Infcl_Admin::init();
