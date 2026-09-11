<?php
/**
 * Dashboard d'administration : menu, onglets, aperçu en direct,
 * page « À propos » et bouton de don.
 *
 * @package InfinityCustomizer
 */

defined( 'ABSPATH' ) || exit;

class Inls_Admin {

	/**
	 * Déclare les hooks d'administration.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_installer_redirect' ), 1 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_inls_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_inls_wizard', array( __CLASS__, 'wizard_save' ) );
		add_action( 'admin_post_inls_reset', array( __CLASS__, 'reset' ) );
		add_action( 'wp_ajax_inls_preview_css', array( __CLASS__, 'ajax_preview_css' ) );
		add_action( 'wp_ajax_inls_check_updates', array( __CLASS__, 'ajax_check_updates' ) );
		add_action( 'wp_ajax_inls_purge_log', array( __CLASS__, 'ajax_purge_log' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	/**
	 * Menu : personnalisation, page Pro et installateur (page cachée).
	 */
	public static function menu() {
		add_menu_page(
			__( 'Infinity LoginShield', 'infinity-loginshield' ),
			__( 'Infinity LoginShield', 'infinity-loginshield' ),
			'manage_options',
			'infinity-loginshield',
			array( __CLASS__, 'render_page' ),
			'dashicons-admin-customizer',
			3
		);
		add_submenu_page(
			'infinity-loginshield',
			__( 'Personnalisation de la connexion', 'infinity-loginshield' ),
			__( 'Personnalisation', 'infinity-loginshield' ),
			'manage_options',
			'infinity-loginshield',
			array( __CLASS__, 'render_page' )
		);
		add_submenu_page(
			'infinity-loginshield',
			__( 'Passer en Pro', 'infinity-loginshield' ),
			__( 'Passer en Pro ✦', 'infinity-loginshield' ),
			'manage_options',
			'infinity-loginshield-pro',
			array( __CLASS__, 'render_pro' )
		);
		add_submenu_page(
			'infinity-loginshield',
			__( 'À propos d’Infinity LoginShield', 'infinity-loginshield' ),
			__( 'À propos', 'infinity-loginshield' ),
			'manage_options',
			'infinity-loginshield-about',
			array( __CLASS__, 'render_about' )
		);
		add_submenu_page(
			null,
			__( 'Bienvenue — Infinity LoginShield', 'infinity-loginshield' ),
			__( 'Installateur', 'infinity-loginshield' ),
			'manage_options',
			'infinity-loginshield-installer',
			array( __CLASS__, 'render_installer' )
		);
	}

	/**
	 * À l'activation (ou après une mise à jour majeure) : ouvre
	 * l'installateur personnalisé une seule fois.
	 */
	public static function maybe_installer_redirect() {
		if ( ! get_option( 'inls_pending_installer' ) ) {
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
		delete_option( 'inls_pending_installer' );
		wp_safe_redirect( admin_url( 'admin.php?page=infinity-loginshield-installer' ) );
		exit;
	}

	/**
	 * Charge les assets sur les pages du plugin.
	 *
	 * @param string $hook Page courante.
	 */
	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'infinity-loginshield' ) ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_style(
			'inls-admin',
			INFINITY_LOGINSHIELD_URL . 'assets/css/admin.css',
			array(),
			INFINITY_LOGINSHIELD_VERSION
		);
		wp_enqueue_script(
			'inls-admin',
			INFINITY_LOGINSHIELD_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			INFINITY_LOGINSHIELD_VERSION,
			true
		);
		wp_localize_script(
			'inls-admin',
			'INLS_ADMIN',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'inls_admin' ),
				'previewAction' => 'inls_preview_css',
				'sitename'      => get_bloginfo( 'name' ),
			)
		);
	}

	/**
	 * Enregistre les réglages.
	 */
	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'infinity-loginshield' ) );
		}
		check_admin_referer( 'inls_save', 'inls_nonce' );

		$input = isset( $_POST['inls'] ) && is_array( $_POST['inls'] ) ? wp_unslash( $_POST['inls'] ) : array();
		update_option( INFINITY_LOGINSHIELD_OPTION, inls_sanitize_settings( $input, null ), 'yes' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'infinity-loginshield',
					'inls-saved' => 1,
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
			wp_die( esc_html__( 'Accès refusé.', 'infinity-loginshield' ) );
		}
		check_admin_referer( 'inls_save', 'inls_nonce' );

		delete_option( INFINITY_LOGINSHIELD_OPTION );
		add_option( INFINITY_LOGINSHIELD_OPTION, inls_get_defaults(), '', 'yes' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'infinity-loginshield',
					'inls-reset' => 1,
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
		check_ajax_referer( 'inls_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		$input   = isset( $_POST['inls'] ) && is_array( $_POST['inls'] ) ? wp_unslash( $_POST['inls'] ) : array();
		$partial = inls_sanitize_settings( $input, inls_settings() );
		wp_send_json_success( array( 'css' => inls_build_login_css( $partial ) ) );
	}

	/**
	 * Enregistre les choix de l'installateur (assistant de bienvenue).
	 */
	public static function wizard_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'infinity-loginshield' ) );
		}
		check_admin_referer( 'inls_wizard', 'inls_wizard_nonce' );

		$base   = inls_settings();
		$input  = isset( $_POST['inls'] ) && is_array( $_POST['inls'] ) ? wp_unslash( $_POST['inls'] ) : array();
		update_option( INFINITY_LOGINSHIELD_OPTION, inls_sanitize_settings( $input, $base ), 'yes' );
		delete_option( 'inls_pending_installer' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'infinity-loginshield',
					'inls-welcome' => 1,
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
		check_ajax_referer( 'inls_admin', 'nonce' );
		if ( ! current_user_can( 'update_plugins' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$release = Inls_GitHub_Updater::fetch_latest_release( true );
		if ( ! $release || empty( $release['version'] ) || '' === $release['download'] ) {
			wp_send_json_error(
				array( 'message' => __( 'Impossible de joindre GitHub pour le moment. Réessayez plus tard.', 'infinity-loginshield' ) )
			);
		}

		if ( version_compare( INFINITY_LOGINSHIELD_VERSION, $release['version'], '>=' ) ) {
			wp_send_json_success(
				array(
					'status'  => 'up_to_date',
					'version' => INFINITY_LOGINSHIELD_VERSION,
				)
			);
		}

		// Force la reconstruction du transient puis prépare le lien de mise à jour.
		if ( function_exists( 'wp_update_plugins' ) ) {
			wp_update_plugins();
		}
		$basename   = plugin_basename( INFINITY_LOGINSHIELD_FILE );
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
		check_ajax_referer( 'inls_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		Inls_Login_Security::purge_log();
		wp_send_json_success();
	}

	/**
	 * Notices de confirmation.
	 */
	public static function notices() {
		if ( ! isset( $_GET['page'] ) ) {
			return;
		}
		if ( 'infinity-loginshield' === $_GET['page'] && isset( $_GET['inls-welcome'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>🎉 ' . esc_html__( 'Bienvenue dans Infinity LoginShield !', 'infinity-loginshield' ) . '</strong> ' . esc_html__( 'Votre page de connexion est prête — explorez les onglets pour la personnaliser.', 'infinity-loginshield' ) . '</p></div>';
		}
		if ( 'infinity-loginshield' === $_GET['page'] && isset( $_GET['inls-saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Réglages enregistrés.', 'infinity-loginshield' ) . '</strong> ' . esc_html__( 'Votre page de connexion est à jour.', 'infinity-loginshield' ) . '</p></div>';
		}
		if ( 'infinity-loginshield' === $_GET['page'] && isset( $_GET['inls-reset'] ) ) {
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Réglages réinitialisés aux valeurs par défaut.', 'infinity-loginshield' ) . '</p></div>';
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
		echo '<div class="inls-field"' . self::showif( $showif ) . '>';
		echo '<label class="inls-label" for="inls-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		echo '<div class="inls-range-row">';
		printf(
			'<input type="range" id="inls-f-%1$s" name="inls[%1$s]" min="%2$d" max="%3$d" step="1" value="%4$d">',
			esc_attr( $key ),
			(int) $min,
			(int) $max,
			(int) $value
		);
		printf(
			'<output class="inls-range-out" data-unit="%1$s">%2$s%1$s</output>',
			esc_attr( $unit ),
			esc_html( $value )
		);
		echo '</div>';
		if ( $desc ) {
			echo '<p class="inls-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ couleur.
	 */
	protected static function field_color( $s, $key, $label, $desc = '', $showif = array() ) {
		echo '<div class="inls-field"' . self::showif( $showif ) . '>';
		echo '<label class="inls-label" for="inls-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		printf(
			'<input type="text" class="inls-color" id="inls-f-%1$s" name="inls[%1$s]" value="%2$s" data-default-color="%2$s">',
			esc_attr( $key ),
			esc_attr( $s[ $key ] )
		);
		if ( $desc ) {
			echo '<p class="inls-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ texte / url.
	 */
	protected static function field_text( $s, $key, $label, $type = 'text', $placeholder = '', $desc = '', $showif = array() ) {
		echo '<div class="inls-field"' . self::showif( $showif ) . '>';
		echo '<label class="inls-label" for="inls-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		printf(
			'<input type="%1$s" class="inls-input" id="inls-f-%2$s" name="inls[%2$s]" value="%3$s" placeholder="%4$s" autocomplete="off">',
			esc_attr( $type ),
			esc_attr( $key ),
			esc_attr( $s[ $key ] ),
			esc_attr( $placeholder )
		);
		if ( $desc ) {
			echo '<p class="inls-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ zone de texte.
	 */
	protected static function field_textarea( $s, $key, $label, $desc = '', $showif = array() ) {
		echo '<div class="inls-field inls-field-wide"' . self::showif( $showif ) . '>';
		echo '<label class="inls-label" for="inls-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		printf(
			'<textarea class="inls-input" id="inls-f-%1$s" name="inls[%1$s]" rows="2">%2$s</textarea>',
			esc_attr( $key ),
			esc_textarea( $s[ $key ] )
		);
		if ( $desc ) {
			echo '<p class="inls-desc">' . wp_kses_post( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ liste déroulante.
	 */
	protected static function field_select( $s, $key, $label, $options, $desc = '', $showif = array() ) {
		echo '<div class="inls-field"' . self::showif( $showif ) . '>';
		echo '<label class="inls-label" for="inls-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		echo '<select class="inls-input" id="inls-f-' . esc_attr( $key ) . '" name="inls[' . esc_attr( $key ) . ']">';
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
			echo '<p class="inls-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ interrupteur.
	 */
	protected static function field_toggle( $s, $key, $label, $desc = '', $showif = array() ) {
		echo '<div class="inls-field inls-toggle-field"' . self::showif( $showif ) . '>';
		echo '<label class="inls-switch"><input type="checkbox" name="inls[' . esc_attr( $key ) . ']" value="1"' . checked( ! empty( $s[ $key ] ), true, false ) . '><span class="inls-switch-ui"></span></label>';
		echo '<div class="inls-toggle-text"><span class="inls-label">' . esc_html( $label ) . '</span>';
		if ( $desc ) {
			echo '<p class="inls-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div></div>';
	}

	/**
	 * Champ image (médiathèque).
	 */
	protected static function field_media( $s, $key, $label, $desc = '', $showif = array() ) {
		echo '<div class="inls-field inls-field-wide"' . self::showif( $showif ) . '>';
		echo '<label class="inls-label">' . esc_html( $label ) . '</label>';
		echo '<div class="inls-media">';
		if ( ! empty( $s[ $key ] ) ) {
			printf( '<img class="inls-media-thumb" src="%s" alt="">', esc_url( $s[ $key ] ) );
		} else {
			echo '<img class="inls-media-thumb" src="" alt="" hidden>';
		}
		printf( '<input type="url" class="inls-input" name="inls[%s]" value="%s" placeholder="https://…" autocomplete="off">', esc_attr( $key ), esc_attr( $s[ $key ] ) );
		echo '<span class="inls-media-actions">';
		echo '<button type="button" class="button inls-media-pick">' . esc_html__( 'Médiathèque', 'infinity-loginshield' ) . '</button>';
		echo '<button type="button" class="button-link inls-media-clear">' . esc_html__( 'Retirer', 'infinity-loginshield' ) . '</button>';
		echo '</span></div>';
		if ( $desc ) {
			echo '<p class="inls-desc">' . esc_html( $desc ) . '</p>';
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
			'dashboard' => array( __( 'Tableau de bord', 'infinity-loginshield' ), 'dashicons-dashboard' ),
			'styles'    => array( __( 'Styles', 'infinity-loginshield' ), 'dashicons-art' ),
			'logo'      => array( __( 'Logo', 'infinity-loginshield' ), 'dashicons-format-image' ),
			'bg'        => array( __( 'Arrière-plan', 'infinity-loginshield' ), 'dashicons-desktop' ),
			'form'      => array( __( 'Formulaire', 'infinity-loginshield' ), 'dashicons-feedback' ),
			'links'     => array( __( 'Liens', 'infinity-loginshield' ), 'dashicons-editor-unlink' ),
			'social'    => array( __( 'Réseaux sociaux', 'infinity-loginshield' ), 'dashicons-share' ),
			'copyright' => array( __( 'Copyright', 'infinity-loginshield' ), 'dashicons-text' ),
			'extras'    => array( __( 'Extras', 'infinity-loginshield' ), 'dashicons-star-filled' ),
			'security'  => array( __( 'Sécurité', 'infinity-loginshield' ), 'dashicons-shield-alt' ),
		);
	}

	/**
	 * Affiche la page de personnalisation.
	 */
	public static function render_page() {
		$s = inls_settings();
		?>
		<div class="wrap inls-wrap">
			<div class="inls-topbar">
				<div class="inls-brand">
					<span class="inls-brand-mark" aria-hidden="true">&#8734;</span>
					<span class="inls-brand-text">
						<strong>Infinity LoginShield</strong>
						<em class="inls-version"><?php echo esc_html( 'v' . INFINITY_LOGINSHIELD_VERSION ); ?></em>
					</span>
				</div>
				<div class="inls-topbar-actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="inls_reset">
						<?php wp_nonce_field( 'inls_save', 'inls_nonce' ); ?>
						<button type="submit" id="inls-reset" class="inls-btn inls-btn-ghost">
							<?php esc_html_e( 'Réinitialiser', 'infinity-loginshield' ); ?>
						</button>
					</form>
					<button type="submit" form="inls-form" class="inls-btn inls-btn-primary">
						<?php esc_html_e( 'Enregistrer', 'infinity-loginshield' ); ?>
					</button>
				</div>
			</div>

			<form id="inls-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="inls_save">
				<?php wp_nonce_field( 'inls_save', 'inls_nonce' ); ?>

				<nav class="inls-tabs" aria-label="<?php esc_attr_e( 'Sections de personnalisation', 'infinity-loginshield' ); ?>">
					<?php foreach ( self::tabs() as $id => $tab ) : ?>
						<button type="button" class="inls-tab<?php echo 'dashboard' === $id ? ' is-active' : ''; ?>" data-tab="<?php echo esc_attr( $id ); ?>">
							<span class="dashicons <?php echo esc_attr( $tab[1] ); ?>" aria-hidden="true"></span>
							<?php echo esc_html( $tab[0] ); ?>
						</button>
					<?php endforeach; ?>
				</nav>

				<div class="inls-layout">
					<div class="inls-panels">
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

					<aside class="inls-preview" aria-label="<?php esc_attr_e( 'Aperçu en direct', 'infinity-loginshield' ); ?>">
						<div class="inls-preview-bar">
							<span class="inls-preview-title"><?php esc_html_e( 'Aperçu en direct', 'infinity-loginshield' ); ?></span>
							<span class="inls-preview-devices">
								<button type="button" class="inls-device is-active" data-width="0" title="<?php esc_attr_e( 'Bureau', 'infinity-loginshield' ); ?>"><span class="dashicons dashicons-desktop"></span></button>
								<button type="button" class="inls-device" data-width="480" title="<?php esc_attr_e( 'Tablette', 'infinity-loginshield' ); ?>"><span class="dashicons dashicons-tablet"></span></button>
								<button type="button" class="inls-device" data-width="375" title="<?php esc_attr_e( 'Mobile', 'infinity-loginshield' ); ?>"><span class="dashicons dashicons-smartphone"></span></button>
							</span>
							<span class="inls-preview-actions">
								<button type="button" class="inls-expand" title="<?php esc_attr_e( 'Aperçu plein écran', 'infinity-loginshield' ); ?>"><span class="dashicons dashicons-fullscreen-exit-alt"></span></button>
								<button type="button" class="inls-refresh" title="<?php esc_attr_e( 'Recharger l’aperçu', 'infinity-loginshield' ); ?>"><span class="dashicons dashicons-update"></span></button>
								<a class="inls-open" href="<?php echo esc_url( wp_login_url() ); ?>" target="_blank" rel="noopener" title="<?php esc_attr_e( 'Ouvrir dans un onglet', 'infinity-loginshield' ); ?>"><span class="dashicons dashicons-external"></span></a>
							</span>
						</div>
						<div class="inls-frame-holder">
							<iframe id="inls-frame" src="<?php echo esc_url( wp_login_url() ); ?>" title="<?php esc_attr_e( 'Aperçu de la page de connexion', 'infinity-loginshield' ); ?>" loading="lazy"></iframe>
						</div>
						<p class="inls-preview-note"><?php esc_html_e( 'Les couleurs et effets sont appliqués en direct. Les liens, réseaux sociaux et copyright apparaissent après enregistrement.', 'infinity-loginshield' ); ?></p>
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
			'<section class="inls-panel%1$s" data-panel="%2$s"><header class="inls-panel-head"><h2>%3$s</h2>%4$s</header><div class="inls-panel-body">',
			'dashboard' === $id ? ' is-active' : '',
			esc_attr( $id ),
			esc_html( $title ),
			$desc ? '<p class="inls-panel-desc">' . esc_html( $desc ) . '</p>' : ''
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
		self::panel_open( 'dashboard', __( 'Tableau de bord', 'infinity-loginshield' ), __( 'Vue d’ensemble de votre page de connexion.', 'infinity-loginshield' ) );

		$social_count  = count( inls_get_social_networks( $s ) );
		$links_count   = ( ! empty( $s['hide_lost_password'] ) ? 1 : 0 ) + ( ! empty( $s['hide_back_to'] ) ? 1 : 0 ) + ( ! empty( $s['back_to_url'] ) || ! empty( $s['back_to_text'] ) ? 1 : 0 );
		$preset_labels = array(
			'glass'   => __( 'Effet verre', 'infinity-loginshield' ),
			'minimal' => __( 'Minimal', 'infinity-loginshield' ),
			'dark'    => __( 'Sombre', 'infinity-loginshield' ),
			'sunset'  => __( 'Coucher de soleil', 'infinity-loginshield' ),
			'ocean'   => __( 'Océan', 'infinity-loginshield' ),
			'forest'  => __( 'Forêt', 'infinity-loginshield' ),
			'neon'    => __( 'Néon', 'infinity-loginshield' ),
			'sakura'  => __( 'Sakura', 'infinity-loginshield' ),
			'mono'    => __( 'Monochrome', 'infinity-loginshield' ),
			'royal'   => __( 'Royal', 'infinity-loginshield' ),
			'custom'  => __( 'Personnalisé', 'infinity-loginshield' ),
		);

		$cards = array(
			array(
				'icon'   => 'dashicons-art',
				'title'  => __( 'Style actif', 'infinity-loginshield' ),
				'state'  => isset( $preset_labels[ $s['preset'] ] ) ? $preset_labels[ $s['preset'] ] : __( 'Personnalisé', 'infinity-loginshield' ),
				'ok'     => true,
				'goto'   => 'styles',
				'button' => __( 'Changer de style', 'infinity-loginshield' ),
			),
			array(
				'icon'   => 'dashicons-shield-alt',
				'title'  => __( 'Sécurité', 'infinity-loginshield' ),
				'state'  => ! empty( $s['sec_enable'] )
					/* translators: 1 : nombre de tentatives, 2 : minutes. */
					? sprintf( __( 'Activé — %1$d tentatives, blocage %2$d min', 'infinity-loginshield' ), (int) $s['sec_max_attempts'], (int) $s['sec_lockout_minutes'] )
					: __( 'Désactivé', 'infinity-loginshield' ),
				'ok'     => ! empty( $s['sec_enable'] ),
				'goto'   => 'security',
				'button' => __( 'Configurer', 'infinity-loginshield' ),
			),
			array(
				'icon'   => 'dashicons-share',
				'title'  => __( 'Réseaux sociaux', 'infinity-loginshield' ),
				'state'  => $social_count > 0
					/* translators: %d : nombre de réseaux configurés. */
					? sprintf( _n( '%d réseau configuré', '%d réseaux configurés', $social_count, 'infinity-loginshield' ), $social_count )
					: __( 'Aucun réseau configuré', 'infinity-loginshield' ),
				'ok'     => $social_count > 0,
				'goto'   => 'social',
				'button' => __( 'Configurer', 'infinity-loginshield' ),
			),
			array(
				'icon'   => 'dashicons-text',
				'title'  => __( 'Copyright', 'infinity-loginshield' ),
				'state'  => ! empty( $s['copyright_enable'] ) ? __( 'Affiché', 'infinity-loginshield' ) : __( 'Masqué', 'infinity-loginshield' ),
				'ok'     => ! empty( $s['copyright_enable'] ),
				'goto'   => 'copyright',
				'button' => __( 'Configurer', 'infinity-loginshield' ),
			),
			array(
				'icon'   => 'dashicons-format-image',
				'title'  => __( 'Logo', 'infinity-loginshield' ),
				'state'  => ! empty( $s['logo_hide'] ) ? __( 'Masqué', 'infinity-loginshield' ) : ( ! empty( $s['logo_url'] ) ? __( 'Image personnalisée', 'infinity-loginshield' ) : __( 'Logo WordPress par défaut', 'infinity-loginshield' ) ),
				'ok'     => ! empty( $s['logo_url'] ) && empty( $s['logo_hide'] ),
				'goto'   => 'logo',
				'button' => __( 'Personnaliser', 'infinity-loginshield' ),
			),
			array(
				'icon'   => 'dashicons-editor-unlink',
				'title'  => __( 'Liens', 'infinity-loginshield' ),
				'state'  => $links_count > 0 ? __( 'Liens personnalisés', 'infinity-loginshield' ) : __( 'Liens par défaut', 'infinity-loginshield' ),
				'ok'     => $links_count > 0,
				'goto'   => 'links',
				'button' => __( 'Personnaliser', 'infinity-loginshield' ),
			),
			array(
				'icon'   => 'dashicons-download',
				'title'  => __( 'Mises à jour', 'infinity-loginshield' ),
				'state'  => sprintf(
					/* translators: %s : dépôt GitHub. */
					__( 'Version %s — GitHub : ', 'infinity-loginshield' ),
					INFINITY_LOGINSHIELD_VERSION
				) . INFINITY_LOGINSHIELD_GITHUB_REPO,
				'ok'     => true,
				'goto'   => '',
				'button' => __( 'Vérifier les mises à jour', 'infinity-loginshield' ),
				'check'  => true,
			),
			array(
				'icon'   => 'dashicons-superhero-alt',
				'title'  => __( 'Infinity LoginShield Pro', 'infinity-loginshield' ),
				'state'  => __( '2FA, reCAPTCHA, URL de connexion personnalisée…', 'infinity-loginshield' ),
				'ok'     => false,
				'goto'   => '',
				'link'   => admin_url( 'admin.php?page=infinity-loginshield-pro' ),
				'button' => __( 'Passer en Pro ✦', 'infinity-loginshield' ),
			),
		);

		echo '<div class="inls-cards">';
		foreach ( $cards as $card ) {
			echo '<div class="inls-card">';
			echo '<span class="inls-card-icon"><span class="dashicons ' . esc_attr( $card['icon'] ) . '"></span></span>';
			echo '<div class="inls-card-body"><h3>' . esc_html( $card['title'] ) . '</h3>';
			echo '<p><span class="inls-chip ' . ( $card['ok'] ? 'is-on' : 'is-off' ) . '">' . wp_kses_post( $card['state'] ) . '</span></p>';
			if ( ! empty( $card['check'] ) ) {
				echo '<span class="inls-card-link inls-check-updates" tabindex="0"><span class="dashicons dashicons-update-alt"></span> ' . esc_html( $card['button'] ) . '</span>';
				echo '<span class="inls-update-status" aria-live="polite"></span>';
			} elseif ( ! empty( $card['link'] ) ) {
				echo '<a class="inls-card-link" href="' . esc_url( $card['link'] ) . '">' . esc_html( $card['button'] ) . ' <span class="dashicons dashicons-external"></span></a>';
			} else {
				echo '<button type="button" class="inls-card-link" data-goto="' . esc_attr( $card['goto'] ) . '">' . esc_html( $card['button'] ) . '</button>';
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
		self::panel_open( 'styles', __( 'Styles modernes', 'infinity-loginshield' ), __( 'Appliquez un style et un thème d’interface en un clic, puis affinez-les dans les onglets suivants.', 'infinity-loginshield' ) );

		$presets = array(
			'glass'   => array( __( 'Effet verre', 'infinity-loginshield' ), 'linear-gradient(135deg,#667eea,#764ba2)' ),
			'minimal' => array( __( 'Minimal', 'infinity-loginshield' ), 'linear-gradient(135deg,#f5f6f8,#dfe3ea)' ),
			'dark'    => array( __( 'Sombre', 'infinity-loginshield' ), 'linear-gradient(160deg,#0f172a,#334155)' ),
			'sunset'  => array( __( 'Coucher de soleil', 'infinity-loginshield' ), 'linear-gradient(120deg,#f97316,#ec4899)' ),
			'ocean'   => array( __( 'Océan', 'infinity-loginshield' ), 'linear-gradient(135deg,#0ea5e9,#2563eb)' ),
			'forest'  => array( __( 'Forêt', 'infinity-loginshield' ), 'linear-gradient(135deg,#059669,#065f46)' ),
			'neon'    => array( __( 'Néon', 'infinity-loginshield' ), 'linear-gradient(135deg,#0f0c29,#302b63)' ),
			'sakura'  => array( __( 'Sakura', 'infinity-loginshield' ), 'linear-gradient(120deg,#ee9ca7,#ffdde1)' ),
			'mono'    => array( __( 'Monochrome', 'infinity-loginshield' ), 'linear-gradient(160deg,#9ca3af,#374151)' ),
			'royal'   => array( __( 'Royal', 'infinity-loginshield' ), 'linear-gradient(150deg,#141e30,#243b55)' ),
		);

		echo '<input type="hidden" name="inls[preset]" value="' . esc_attr( $s['preset'] ) . '">';
		echo '<div class="inls-presets">';
		foreach ( $presets as $key => $preset ) {
			printf(
				'<button type="button" class="inls-preset%3$s" data-preset="%1$s"><span class="inls-preset-preview" style="background:%2$s"></span><span class="inls-preset-name">%4$s</span></button>',
				esc_attr( $key ),
				esc_attr( $preset[1] ),
				$s['preset'] === $key ? ' is-active' : '',
				esc_html( $preset[0] )
			);
		}
		echo '</div>';

		echo '<h3 class="inls-group-title">' . esc_html__( 'Thème d’interface du formulaire', 'infinity-loginshield' ) . '</h3>';
		echo '<p class="inls-panel-desc">' . esc_html__( 'Le design général du formulaire, indépendant des couleurs.', 'infinity-loginshield' ) . '</p>';
		echo '<input type="hidden" name="inls[form_theme]" value="' . esc_attr( $s['form_theme'] ) . '">';
		echo '<div class="inls-presets inls-themes">';
		$themes = array(
			'glass'    => array( __( 'Effet verre', 'infinity-loginshield' ), 'background:linear-gradient(135deg,#667eea,#764ba2);box-shadow:inset 22px 22px 0 -8px rgba(255,255,255,.4);border-radius:8px;' ),
			'classic'  => array( __( 'Classique', 'infinity-loginshield' ), 'background:#fff;border:1px solid #d5d3e8;border-radius:6px;' ),
			'outline'  => array( __( 'Contour', 'infinity-loginshield' ), 'background:transparent;border:2px solid #6d5df6;border-radius:8px;' ),
			'pill'     => array( __( 'Pillule', 'infinity-loginshield' ), 'background:#fff;border-radius:999px;' ),
			'elevated' => array( __( 'Surélevé', 'infinity-loginshield' ), 'background:#fff;border-radius:12px;box-shadow:0 12px 20px -8px rgba(0,0,0,.5);' ),
			'accent'   => array( __( 'Accent', 'infinity-loginshield' ), 'background:#fff;border-top:6px solid #7c3aed;border-radius:8px;' ),
			'minimal'  => array( __( 'Minimal', 'infinity-loginshield' ), 'background:transparent;border-bottom:5px solid #6d5df6;border-radius:0;' ),
		);
		foreach ( $themes as $key => $theme ) {
			printf(
				'<button type="button" class="inls-preset inls-theme-card%3$s" data-theme="%1$s"><span class="inls-preset-preview" style="%2$s"></span><span class="inls-preset-name">%4$s</span></button>',
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
		self::panel_open( 'logo', __( 'Logo', 'infinity-loginshield' ), __( 'Remplacez le logo WordPress par le vôtre.', 'infinity-loginshield' ) );

		self::field_toggle( $s, 'logo_hide', __( 'Masquer complètement le logo', 'infinity-loginshield' ) );
		self::field_media( $s, 'logo_url', __( 'Image du logo', 'infinity-loginshield' ), __( 'SVG ou PNG transparent recommandé.', 'infinity-loginshield' ), array( 'logo_hide' => 0 ) );
		self::field_range( $s, 'logo_width', __( 'Largeur', 'infinity-loginshield' ), 40, 400, 'px', '', array( 'logo_hide' => 0 ) );
		self::field_range( $s, 'logo_height', __( 'Hauteur', 'infinity-loginshield' ), 24, 300, 'px', '', array( 'logo_hide' => 0 ) );
		self::field_text( $s, 'logo_link', __( 'Lien du logo', 'infinity-loginshield' ), 'url', 'https://exemple.com', __( 'Laisser vide pour pointer vers l’accueil du site.', 'infinity-loginshield' ), array( 'logo_hide' => 0 ) );

		self::panel_close();
	}

	/**
	 * Panneau : arrière-plan.
	 */
	protected static function panel_background( $s ) {
		self::panel_open( 'bg', __( 'Arrière-plan', 'infinity-loginshield' ), __( 'Couleur, dégradé ou image, avec flou et voile coloré.', 'infinity-loginshield' ) );

		self::field_select(
			$s,
			'bg_type',
			__( 'Type de fond', 'infinity-loginshield' ),
			array(
				'color'    => __( 'Couleur unie', 'infinity-loginshield' ),
				'gradient' => __( 'Dégradé', 'infinity-loginshield' ),
				'image'    => __( 'Image', 'infinity-loginshield' ),
			)
		);
		self::field_color( $s, 'bg_color1', __( 'Couleur de fond / dégradé 1', 'infinity-loginshield' ) );
		self::field_color( $s, 'bg_color2', __( 'Dégradé — couleur 2', 'infinity-loginshield' ), '', array( 'bg_type' => 'gradient' ) );
		self::field_range( $s, 'bg_gradient_angle', __( 'Angle du dégradé', 'infinity-loginshield' ), 0, 360, '°', '', array( 'bg_type' => 'gradient' ) );
		self::field_media( $s, 'bg_image', __( 'Image de fond', 'infinity-loginshield' ), '', array( 'bg_type' => 'image' ) );
		self::field_select(
			$s,
			'bg_size',
			__( 'Affichage de l’image', 'infinity-loginshield' ),
			array(
				'cover'   => __( 'Couvrir (cover)', 'infinity-loginshield' ),
				'contain' => __( 'Contenir (contain)', 'infinity-loginshield' ),
				'repeat'  => __( 'Répéter (motif)', 'infinity-loginshield' ),
			),
			'',
			array( 'bg_type' => 'image' )
		);
		self::field_select(
			$s,
			'bg_position',
			__( 'Position de l’image', 'infinity-loginshield' ),
			array(
				'center'       => __( 'Centrée', 'infinity-loginshield' ),
				'top'          => __( 'En haut', 'infinity-loginshield' ),
				'bottom'       => __( 'En bas', 'infinity-loginshield' ),
				'left'         => __( 'À gauche', 'infinity-loginshield' ),
				'right'        => __( 'À droite', 'infinity-loginshield' ),
				'top-left'     => __( 'Haut gauche', 'infinity-loginshield' ),
				'top-right'    => __( 'Haut droit', 'infinity-loginshield' ),
				'bottom-left'  => __( 'Bas gauche', 'infinity-loginshield' ),
				'bottom-right' => __( 'Bas droit', 'infinity-loginshield' ),
			),
			'',
			array( 'bg_type' => 'image' )
		);
		self::field_range( $s, 'bg_blur', __( 'Flou de l’image', 'infinity-loginshield' ), 0, 30, 'px', __( 'Contrôle du flou (backdrop).', 'infinity-loginshield' ), array( 'bg_type' => 'image' ) );
		self::field_range( $s, 'bg_brightness', __( 'Luminosité de l’image', 'infinity-loginshield' ), 30, 150, '%', '', array( 'bg_type' => 'image' ) );
		self::field_range( $s, 'bg_saturation', __( 'Saturation de l’image', 'infinity-loginshield' ), 0, 200, '%', '', array( 'bg_type' => 'image' ) );
		self::field_color( $s, 'bg_overlay_color', __( 'Voile coloré', 'infinity-loginshield' ), __( 'Couche de couleur superposée au fond.', 'infinity-loginshield' ) );
		self::field_range( $s, 'bg_overlay_opacity', __( 'Opacité du voile', 'infinity-loginshield' ), 0, 100, '%' );

		self::panel_close();
	}

	/**
	 * Panneau : formulaire.
	 */
	protected static function panel_form( $s ) {
		self::panel_open( 'form', __( 'Formulaire', 'infinity-loginshield' ), __( 'Effet verre, couleurs des champs, bouton et liens.', 'infinity-loginshield' ) );

		echo '<h3 class="inls-group-title">' . esc_html__( 'Conteneur', 'infinity-loginshield' ) . '</h3>';
		self::field_color( $s, 'form_bg', __( 'Fond du formulaire', 'infinity-loginshield' ) );
		self::field_range( $s, 'form_opacity', __( 'Opacité du fond', 'infinity-loginshield' ), 0, 100, '%' );
		self::field_range( $s, 'form_blur', __( 'Flou (effet verre)', 'infinity-loginshield' ), 0, 40, 'px' );
		self::field_range( $s, 'form_radius', __( 'Arrondi des coins', 'infinity-loginshield' ), 0, 60, 'px' );
		self::field_range( $s, 'form_width', __( 'Largeur du formulaire', 'infinity-loginshield' ), 260, 560, 'px' );
		self::field_range( $s, 'form_padding', __( 'Espacement intérieur', 'infinity-loginshield' ), 12, 80, 'px' );
		self::field_toggle( $s, 'form_shadow', __( 'Ombre portée', 'infinity-loginshield' ) );

		echo '<h3 class="inls-group-title">' . esc_html__( 'Textes et champs', 'infinity-loginshield' ) . '</h3>';
		self::field_color( $s, 'text_color', __( 'Texte du formulaire', 'infinity-loginshield' ) );
		self::field_color( $s, 'label_color', __( 'Libellés', 'infinity-loginshield' ) );
		self::field_color( $s, 'input_bg', __( 'Fond des champs', 'infinity-loginshield' ) );
		self::field_color( $s, 'input_color', __( 'Texte des champs', 'infinity-loginshield' ) );
		self::field_color( $s, 'input_border', __( 'Bordure des champs', 'infinity-loginshield' ) );

		echo '<h3 class="inls-group-title">' . esc_html__( 'Bouton « Se connecter »', 'infinity-loginshield' ) . '</h3>';
		self::field_color( $s, 'button_bg', __( 'Couleur du bouton', 'infinity-loginshield' ) );
		self::field_color( $s, 'button_hover', __( 'Couleur au survol', 'infinity-loginshield' ) );
		self::field_range( $s, 'button_radius', __( 'Arrondi du bouton', 'infinity-loginshield' ), 0, 40, 'px' );

		echo '<h3 class="inls-group-title">' . esc_html__( 'Liens', 'infinity-loginshield' ) . '</h3>';
		self::field_color( $s, 'link_color', __( 'Couleur des liens', 'infinity-loginshield' ) );

		self::panel_close();
	}

	/**
	 * Panneau : liens.
	 */
	protected static function panel_links( $s ) {
		self::panel_open( 'links', __( 'Liens de la page de connexion', 'infinity-loginshield' ), __( 'Modifiez la cible et le texte des liens, ou masquez-les.', 'infinity-loginshield' ) );

		self::field_toggle( $s, 'hide_lost_password', __( 'Masquer « Mot de passe perdu ? »', 'infinity-loginshield' ) );
		self::field_toggle( $s, 'hide_back_to', __( 'Masquer « Retour au site »', 'infinity-loginshield' ) );
		self::field_text( $s, 'back_to_text', __( 'Texte du lien « Retour au site »', 'infinity-loginshield' ), 'text', __( 'ex. : Retour à la boutique', 'infinity-loginshield' ) );
		self::field_text( $s, 'back_to_url', __( 'Cible du lien « Retour au site »', 'infinity-loginshield' ), 'url', 'https://exemple.com' );
		self::field_toggle( $s, 'hide_register', __( 'Masquer le lien « S’enregistrer »', 'infinity-loginshield' ) );
		self::field_text( $s, 'register_text', __( 'Texte du lien « S’enregistrer »', 'infinity-loginshield' ), 'text', __( 'ex. : Créer un compte client', 'infinity-loginshield' ) );

		self::panel_close();
	}

	/**
	 * Panneau : réseaux sociaux.
	 */
	protected static function panel_social( $s ) {
		self::panel_open( 'social', __( 'Icônes de réseaux sociaux', 'infinity-loginshield' ), __( 'Renseignez une URL pour afficher l’icône — laissez vide pour la masquer.', 'infinity-loginshield' ) );

		self::field_toggle( $s, 'social_enable', __( 'Afficher les icônes sociales', 'infinity-loginshield' ) );
		self::field_toggle( $s, 'social_brand', __( 'Couleurs officielles des marques', 'infinity-loginshield' ), __( 'Chaque icône reprend sa couleur officielle : Facebook bleu, X noir, dégradé Instagram, LinkedIn bleu, YouTube rouge.', 'infinity-loginshield' ) );
		self::field_select(
			$s,
			'social_style',
			__( 'Forme des icônes', 'infinity-loginshield' ),
			array(
				'circle'  => __( 'Cercle', 'infinity-loginshield' ),
				'rounded' => __( 'Arrondi', 'infinity-loginshield' ),
				'square'  => __( 'Carré', 'infinity-loginshield' ),
			),
			'',
			array( 'social_enable' => 1 )
		);
		self::field_range( $s, 'social_size', __( 'Taille des icônes', 'infinity-loginshield' ), 28, 72, 'px', '', array( 'social_enable' => 1 ) );
		self::field_color( $s, 'social_icon_color', __( 'Couleur de l’icône', 'infinity-loginshield' ), '', array( 'social_enable' => 1 ) );
		self::field_color( $s, 'social_icon_bg', __( 'Fond de l’icône', 'infinity-loginshield' ), '', array( 'social_enable' => 1 ) );
		self::field_range( $s, 'social_icon_bg_opacity', __( 'Opacité du fond d’icône', 'infinity-loginshield' ), 0, 100, '%', '', array( 'social_enable' => 1 ) );

		echo '<h3 class="inls-group-title">' . esc_html__( 'Réseaux', 'infinity-loginshield' ) . '</h3>';
		self::field_text( $s, 'social_facebook', __( 'Facebook', 'infinity-loginshield' ), 'url', 'https://facebook.com/votre-page' );
		self::field_text( $s, 'social_twitter', __( 'X (Twitter)', 'infinity-loginshield' ), 'url', 'https://x.com/votre-compte' );
		self::field_text( $s, 'social_instagram', __( 'Instagram', 'infinity-loginshield' ), 'url', 'https://instagram.com/votre-compte' );
		self::field_text( $s, 'social_linkedin', __( 'LinkedIn', 'infinity-loginshield' ), 'url', 'https://linkedin.com/in/votre-profil' );
		self::field_text( $s, 'social_youtube', __( 'YouTube', 'infinity-loginshield' ), 'url', 'https://youtube.com/@votre-chaine' );
		self::field_text( $s, 'social_email', __( 'E-mail', 'infinity-loginshield' ), 'email', 'contact@exemple.com' );

		self::panel_close();
	}

	/**
	 * Panneau : copyright.
	 */
	protected static function panel_copyright( $s ) {
		self::panel_open( 'copyright', __( 'Copyright', 'infinity-loginshield' ), __( 'Affichez votre mention de copyright sous le formulaire.', 'infinity-loginshield' ) );

		self::field_toggle( $s, 'copyright_enable', __( 'Afficher le copyright', 'infinity-loginshield' ) );
		self::field_textarea(
			$s,
			'copyright_text',
			__( 'Texte du copyright', 'infinity-loginshield' ),
			/* translators: les balises <code> sont des variables. */
			sprintf( __( 'Variables disponibles : %1$s (année) et %2$s (nom du site).', 'infinity-loginshield' ), '<code>{year}</code>', '<code>{sitename}</code>' ),
			array( 'copyright_enable' => 1 )
		);

		self::panel_close();
	}

	/**
	 * Panneau : extras (message d'accueil, typographie, animation).
	 */
	protected static function panel_extras( $s ) {
		self::panel_open( 'extras', __( 'Extras', 'infinity-loginshield' ), __( 'Message d’accueil, typographie et animation d’entrée de la page de connexion.', 'infinity-loginshield' ) );

		echo '<h3 class="inls-group-title">' . esc_html__( 'Message de bienvenue', 'infinity-loginshield' ) . '</h3>';
		self::field_toggle( $s, 'welcome_enable', __( 'Afficher un message d’accueil', 'infinity-loginshield' ), __( 'Titre et sous-titre affichés au-dessus du formulaire.', 'infinity-loginshield' ) );
		self::field_text( $s, 'welcome_title', __( 'Titre', 'infinity-loginshield' ), 'text', __( 'ex. : Bon retour parmi nous ✨', 'infinity-loginshield' ), '', array( 'welcome_enable' => 1 ) );
		self::field_text( $s, 'welcome_subtitle', __( 'Sous-titre', 'infinity-loginshield' ), 'text', __( 'ex. : Connectez-vous pour gérer votre boutique.', 'infinity-loginshield' ), '', array( 'welcome_enable' => 1 ) );

		echo '<h3 class="inls-group-title">' . esc_html__( 'Typographie', 'infinity-loginshield' ) . '</h3>';
		self::field_select(
			$s,
			'font_family',
			__( 'Police', 'infinity-loginshield' ),
			array(
				'system'  => __( 'Système (moderne)', 'infinity-loginshield' ),
				'serif'   => __( 'Serif élégante', 'infinity-loginshield' ),
				'rounded' => __( 'Arrondie', 'infinity-loginshield' ),
				'mono'    => __( 'Monospace', 'infinity-loginshield' ),
			)
		);
		self::field_range( $s, 'font_size', __( 'Taille du texte', 'infinity-loginshield' ), 12, 18, 'px' );

		echo '<h3 class="inls-group-title">' . esc_html__( 'Animation d’entrée', 'infinity-loginshield' ) . '</h3>';
		self::field_select(
			$s,
			'anim',
			__( 'Effet à l’ouverture de la page', 'infinity-loginshield' ),
			array(
				'none'  => __( 'Aucune', 'infinity-loginshield' ),
				'fade'  => __( 'Fondu', 'infinity-loginshield' ),
				'slide' => __( 'Glissement vers le haut', 'infinity-loginshield' ),
				'zoom'  => __( 'Zoom', 'infinity-loginshield' ),
			),
			__( 'Désactivée automatiquement si l’utilisateur demande moins d’animations.', 'infinity-loginshield' )
		);

		echo '<h3 class="inls-group-title">' . esc_html__( 'CSS personnalisé', 'infinity-loginshield' ) . '</h3>';
		self::field_textarea(
			$s,
			'custom_css',
			__( 'CSS brut pour la page de connexion', 'infinity-loginshield' ),
			__( 'Injecté après tous les réglages du plugin — les balises sont retirées automatiquement.', 'infinity-loginshield' )
		);

		self::panel_close();
	}

	/**
	 * Panneau : sécurité.
	 */
	protected static function panel_security( $s ) {
		self::panel_open( 'security', __( 'Sécurité de la connexion', 'infinity-loginshield' ), __( 'Bloquez les attaques par force brute sur la page de connexion.', 'infinity-loginshield' ) );

		self::field_toggle( $s, 'sec_enable', __( 'Limiter les tentatives de connexion', 'infinity-loginshield' ), __( 'Après N échecs, l’adresse IP et l’identifiant sont bloqués temporairement.', 'infinity-loginshield' ) );
		self::field_range( $s, 'sec_max_attempts', __( 'Tentatives autorisées', 'infinity-loginshield' ), 1, 20, '', '', array( 'sec_enable' => 1 ) );
		self::field_range( $s, 'sec_lockout_minutes', __( 'Durée du blocage', 'infinity-loginshield' ), 1, 1440, 'min', '', array( 'sec_enable' => 1 ) );
		self::field_text( $s, 'sec_lock_message', __( 'Message de blocage', 'infinity-loginshield' ), 'text', '', sprintf( __( 'Utilisez %%d pour la durée restante en minutes.', 'infinity-loginshield' ) ), array( 'sec_enable' => 1 ) );
		self::field_toggle( $s, 'sec_generic_error', __( 'Masquer le détail des erreurs', 'infinity-loginshield' ), __( 'Affiche un message générique au lieu de « mot de passe incorrect ».', 'infinity-loginshield' ) );
		self::field_toggle( $s, 'sec_hide_language_switcher', __( 'Masquer le sélecteur de langue', 'infinity-loginshield' ) );
		self::field_toggle( $s, 'sec_disable_xmlrpc', __( 'Désactiver XML-RPC', 'infinity-loginshield' ), __( 'Coupe une porte d’entrée classique des attaques par force brute (recommandé si vous n’utilisez pas l’appli mobile WordPress).', 'infinity-loginshield' ) );

		// ——— Journal de sécurité ———.
		$log = Inls_Login_Security::get_log();
		echo '<div class="inls-journal">';
		echo '<h3 class="inls-group-title">' . esc_html__( 'Journal de sécurité', 'infinity-loginshield' ) . '</h3>';
		if ( $log ) {
			$badges = array(
				'failed'  => array( __( 'Échec', 'infinity-loginshield' ), 'is-fail' ),
				'blocked' => array( __( 'Bloqué', 'infinity-loginshield' ), 'is-blocked' ),
				'login'   => array( __( 'Connexion', 'infinity-loginshield' ), 'is-login' ),
			);
			echo '<table class="inls-journal-table"><thead><tr><th>' . esc_html__( 'Date', 'infinity-loginshield' ) . '</th><th>IP</th><th>' . esc_html__( 'Identifiant', 'infinity-loginshield' ) . '</th><th>' . esc_html__( 'Action', 'infinity-loginshield' ) . '</th></tr></thead><tbody>';
			foreach ( array_slice( $log, 0, 20 ) as $event ) {
				$badge = isset( $badges[ $event['a'] ] ) ? $badges[ $event['a'] ] : array( $event['a'], 'is-fail' );
				printf(
					'<tr><td>%1$s</td><td><code>%2$s</code></td><td>%3$s</td><td><span class="inls-badge %5$s">%4$s</span></td></tr>',
					esc_html( date_i18n( 'j F, H:i', $event['t'] + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ),
					esc_html( $event['ip'] ),
					esc_html( $event['u'] ? $event['u'] : '—' ),
					esc_html( $badge[0] ),
					esc_attr( $badge[1] )
				);
			}
			echo '</tbody></table>';
			printf(
				'<p class="inls-desc">%s</p>',
				esc_html(
					sprintf(
						/* translators: %d : nombre d'événements. */
						__( '%d événements enregistrés (50 maximum).', 'infinity-loginshield' ),
						count( $log )
					)
				)
			);
			echo '<button type="button" class="button inls-purge-log">' . esc_html__( 'Vider le journal', 'infinity-loginshield' ) . '</button> <span class="inls-purge-status"></span>';
		} else {
			echo '<p class="inls-desc">' . esc_html__( 'Aucun événement enregistré pour le moment.', 'infinity-loginshield' ) . '</p>';
		}
		echo '</div>';

		echo '<div class="inls-pro-teaser">';
		echo '<div class="inls-pro-teaser-text"><h4>✦ ' . esc_html__( 'Niveaux de sécurité avancés — Infinity LoginShield Pro', 'infinity-loginshield' ) . '</h4><p>'
			. esc_html__( 'Double authentification (2FA), reCAPTCHA v3, URL de connexion personnalisée, alertes e-mail, journal des tentatives et blocage géographique.', 'infinity-loginshield' )
			. '</p></div>';
		echo '<a class="inls-btn inls-btn-pro" href="' . esc_url( admin_url( 'admin.php?page=infinity-loginshield-pro' ) ) . '">' . esc_html__( 'Passer en Pro', 'infinity-loginshield' ) . '</a>';
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
		return apply_filters( 'infinity_loginshield_donate_url', 'https://www.paypal.com/donate' );
	}

	/**
	 * Affiche la page « À propos ».
	 */
	public static function render_about() {
		?>
		<div class="wrap inls-wrap inls-about">
			<div class="inls-hero">
				<span class="inls-hero-mark" aria-hidden="true">&#8734;</span>
				<h1><?php esc_html_e( 'Infinity LoginShield', 'infinity-loginshield' ); ?></h1>
				<p>
					<?php esc_html_e( 'La page de connexion de WordPress, enfin à votre image.', 'infinity-loginshield' ); ?><br>
					<span class="inls-version">v<?php echo esc_html( INFINITY_LOGINSHIELD_VERSION ); ?></span>
				</p>
				<div class="inls-hero-actions">
					<a class="inls-btn inls-btn-donate" href="<?php echo esc_url( self::donate_url() ); ?>" target="_blank" rel="noopener">
						<span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'Faire un don', 'infinity-loginshield' ); ?>
					</a>
					<a class="inls-btn inls-btn-ghost is-light" href="https://github.com/derouiche-oussama/infinity-loginshield" target="_blank" rel="noopener">
						<span class="dashicons dashicons-github"></span> <?php esc_html_e( 'Voir sur GitHub', 'infinity-loginshield' ); ?>
					</a>
				</div>
			</div>

			<div class="inls-about-grid">
				<section class="inls-about-card">
					<h2><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'Développeur', 'infinity-loginshield' ); ?></h2>
					<p class="inls-about-dev"><strong><?php esc_html_e( 'Derouiche Oussama', 'infinity-loginshield' ); ?></strong></p>
					<p><?php esc_html_e( 'Créateur du plugin, passionné par WordPress et les interfaces modernes.', 'infinity-loginshield' ); ?></p>
					<div class="inls-dev-social" aria-label="<?php esc_attr_e( 'Réseaux du développeur', 'infinity-loginshield' ); ?>">
						<?php foreach ( self::dev_socials() as $network ) : ?>
							<a class="inls-dev-icon" href="<?php echo esc_url( $network['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $network['label'] ); ?>" title="<?php echo esc_attr( $network['label'] ); ?>">
								<span class="dashicons <?php echo esc_attr( $network['icon'] ); ?>"></span>
							</a>
						<?php endforeach; ?>
					</div>
					<p class="inls-about-links">
						<a href="https://github.com/derouiche-oussama" target="_blank" rel="noopener">GitHub</a> ·
						<a href="https://profiles.wordpress.org/derouicheoussama/" target="_blank" rel="noopener">WordPress.org</a>
					</p>
				</section>

				<section class="inls-about-card">
					<h2><span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Fonctionnalités', 'infinity-loginshield' ); ?></h2>
					<ul class="inls-about-list">
						<li><?php esc_html_e( 'Logo personnalisé + lien du logo', 'infinity-loginshield' ); ?></li>
						<li><?php esc_html_e( 'Arrière-plan : couleur, dégradé ou image', 'infinity-loginshield' ); ?></li>
						<li><?php esc_html_e( 'Contrôles de flou et d’opacité', 'infinity-loginshield' ); ?></li>
						<li><?php esc_html_e( '6 styles modernes (effet verre, sombre…)', 'infinity-loginshield' ); ?></li>
						<li><?php esc_html_e( 'Liens : personnalisation ou masquage', 'infinity-loginshield' ); ?></li>
						<li><?php esc_html_e( 'Icônes de réseaux sociaux', 'infinity-loginshield' ); ?></li>
						<li><?php esc_html_e( 'Mention de copyright', 'infinity-loginshield' ); ?></li>
						<li><?php esc_html_e( 'Blocage des tentatives de mot de passe', 'infinity-loginshield' ); ?></li>
						<li><?php esc_html_e( 'Aperçu en direct 100 % responsive', 'infinity-loginshield' ); ?></li>
						<li><?php esc_html_e( 'Mises à jour automatiques via GitHub', 'infinity-loginshield' ); ?></li>
					</ul>
				</section>

				<section class="inls-about-card">
					<h2><span class="dashicons dashicons-cloud"></span> <?php esc_html_e( 'Mises à jour', 'infinity-loginshield' ); ?></h2>
					<p>
						<?php
						printf(
							/* translators: %s : dépôt GitHub. */
							esc_html__( 'Ce plugin se met à jour automatiquement depuis les releases GitHub du dépôt :', 'infinity-loginshield' )
						);
						echo ' <code>' . esc_html( INFINITY_LOGINSHIELD_GITHUB_REPO ) . '</code>';
						?>
					</p>
					<p><?php esc_html_e( 'Publiez un nouveau tag (ex. v1.2.1) : l’action GitHub construit le zip et propage la mise à jour à tous les sites.', 'infinity-loginshield' ); ?></p>
					<div class="inls-about-actions">
						<button type="button" class="inls-btn inls-btn-ghost inls-check-updates"><span class="dashicons dashicons-update-alt"></span> <?php esc_html_e( 'Vérifier les mises à jour', 'infinity-loginshield' ); ?></button>
						<span class="inls-update-status" aria-live="polite"></span>
					</div>
				</section>

				<section class="inls-about-card">
					<h2><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e( 'État du système', 'infinity-loginshield' ); ?></h2>
					<ul class="inls-about-status">
						<li><strong><?php esc_html_e( 'Version du plugin', 'infinity-loginshield' ); ?></strong> <?php echo esc_html( INFINITY_LOGINSHIELD_VERSION ); ?></li>
						<li><strong><?php esc_html_e( 'Version de WordPress', 'infinity-loginshield' ); ?></strong> <?php echo esc_html( get_bloginfo( 'version' ) ); ?></li>
						<li><strong><?php esc_html_e( 'Version de PHP', 'infinity-loginshield' ); ?></strong> <?php echo esc_html( PHP_VERSION ); ?></li>
						<li><strong><?php esc_html_e( 'Site', 'infinity-loginshield' ); ?></strong> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></li>
					</ul>
				</section>
			</div>

			<section class="inls-about-card inls-about-support">
				<h2><span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'Soutenir le projet', 'infinity-loginshield' ); ?></h2>
				<p><?php esc_html_e( 'Infinity LoginShield est développé sur le temps personnel. Si ce plugin vous est utile, un petit don aide à le maintenir, à l’améliorer et à le garder gratuit.', 'infinity-loginshield' ); ?></p>
				<div class="inls-hero-actions">
					<a class="inls-btn inls-btn-donate" href="<?php echo esc_url( self::donate_url() ); ?>" target="_blank" rel="noopener">
						<span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'Faire un don via PayPal', 'infinity-loginshield' ); ?>
					</a>
					<a class="inls-btn inls-btn-ghost" href="https://wordpress.org/support/plugin/infinity-loginshield/" target="_blank" rel="noopener">
						<?php esc_html_e( 'Forum d’entraide', 'infinity-loginshield' ); ?>
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
			'infinity_loginshield_dev_socials',
			array(
				array(
					'label' => 'GitHub',
					'icon'  => 'dashicons-github',
					'url'   => 'https://github.com/derouiche-oussama',
				),
				array(
					'label' => 'WordPress.org',
					'icon'  => 'dashicons-wordpress',
					'url'   => 'https://profiles.wordpress.org/derouicheoussama/',
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
					'label' => __( 'Site web', 'infinity-loginshield' ),
					'icon'  => 'dashicons-admin-links',
					'url'   => home_url( '/' ),
				),
				array(
					'label' => __( 'E-mail', 'infinity-loginshield' ),
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
		return apply_filters( 'infinity_loginshield_pro_url', '#' );
	}

	/**
	 * Installateur : assistant de bienvenue en 3 étapes.
	 */
	public static function render_installer() {
		$s = inls_settings();
		?>
		<div class="wrap inls-installer">
			<header class="inls-inst-hero">
				<span class="inls-inst-mark" aria-hidden="true">&#8734;</span>
				<h1><?php esc_html_e( 'Bienvenue dans Infinity LoginShield', 'infinity-loginshield' ); ?></h1>
				<p><?php esc_html_e( 'Transformez votre page de connexion en 3 étapes : choisissez un style moderne, activez la protection anti force brute, et c’est parti.', 'infinity-loginshield' ); ?></p>
				<ol class="inls-inst-steps" aria-hidden="true">
					<li class="is-active" data-step-dot="1"><?php esc_html_e( 'Bienvenue', 'infinity-loginshield' ); ?></li>
					<li data-step-dot="2"><?php esc_html_e( 'Style', 'infinity-loginshield' ); ?></li>
					<li data-step-dot="3"><?php esc_html_e( 'Sécurité', 'infinity-loginshield' ); ?></li>
				</ol>
			</header>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="inls_wizard">
				<input type="hidden" name="inls[preset]" value="<?php echo esc_attr( $s['preset'] ); ?>">
				<?php wp_nonce_field( 'inls_wizard', 'inls_wizard_nonce' ); ?>

				<section class="inls-wstep is-active" data-step="1">
					<div class="inls-inst-grid">
						<div class="inls-inst-feature"><span class="dashicons dashicons-format-image"></span><h3><?php esc_html_e( 'Logo & arrière-plan', 'infinity-loginshield' ); ?></h3><p><?php esc_html_e( 'Votre logo, votre image de fond, flou et voile réglables.', 'infinity-loginshield' ); ?></p></div>
						<div class="inls-inst-feature"><span class="dashicons dashicons-art"></span><h3><?php esc_html_e( '6 styles modernes', 'infinity-loginshield' ); ?></h3><p><?php esc_html_e( 'Effet verre, sombre, coucher de soleil… un clic, tout est prêt.', 'infinity-loginshield' ); ?></p></div>
						<div class="inls-inst-feature"><span class="dashicons dashicons-shield-alt"></span><h3><?php esc_html_e( 'Anti force brute', 'infinity-loginshield' ); ?></h3><p><?php esc_html_e( 'Blocage automatique des tentatives de mot de passe.', 'infinity-loginshield' ); ?></p></div>
						<div class="inls-inst-feature"><span class="dashicons dashicons-share"></span><h3><?php esc_html_e( 'Social & copyright', 'infinity-loginshield' ); ?></h3><p><?php esc_html_e( 'Icônes de réseaux sociaux et votre mention de copyright.', 'infinity-loginshield' ); ?></p></div>
					</div>
				</section>

				<section class="inls-wstep" data-step="2">
					<h2><?php esc_html_e( 'Choisissez votre style', 'infinity-loginshield' ); ?></h2>
					<p class="inls-inst-desc"><?php esc_html_e( 'Vous pourrez tout affiner plus tard (couleurs, flou, opacité…).', 'infinity-loginshield' ); ?></p>
					<div class="inls-presets">
						<?php
						$presets = array(
							'glass'   => array( __( 'Effet verre', 'infinity-loginshield' ), 'linear-gradient(135deg,#667eea,#764ba2)' ),
							'minimal' => array( __( 'Minimal', 'infinity-loginshield' ), 'linear-gradient(135deg,#f5f6f8,#dfe3ea)' ),
							'dark'    => array( __( 'Sombre', 'infinity-loginshield' ), 'linear-gradient(160deg,#0f172a,#334155)' ),
							'sunset'  => array( __( 'Coucher de soleil', 'infinity-loginshield' ), 'linear-gradient(120deg,#f97316,#ec4899)' ),
							'ocean'   => array( __( 'Océan', 'infinity-loginshield' ), 'linear-gradient(135deg,#0ea5e9,#2563eb)' ),
							'forest'  => array( __( 'Forêt', 'infinity-loginshield' ), 'linear-gradient(135deg,#059669,#065f46)' ),
						);
						foreach ( $presets as $key => $preset ) {
							printf(
								'<button type="button" class="inls-preset%3$s" data-preset="%1$s"><span class="inls-preset-preview" style="background:%2$s"></span><span class="inls-preset-name">%4$s</span></button>',
								esc_attr( $key ),
								esc_attr( $preset[1] ),
								$s['preset'] === $key ? ' is-active' : '',
								esc_html( $preset[0] )
							);
						}
						?>
					</div>
				</section>

				<section class="inls-wstep" data-step="3">
					<h2><?php esc_html_e( 'Protégez votre page de connexion', 'infinity-loginshield' ); ?></h2>
					<p class="inls-inst-desc"><?php esc_html_e( 'Recommandé : bloquez les attaques par force brute dès maintenant.', 'infinity-loginshield' ); ?></p>
					<div class="inls-inst-security">
						<label class="inls-switch"><input type="checkbox" name="inls[sec_enable]" value="1" <?php checked( ! empty( $s['sec_enable'] ) ); ?>><span class="inls-switch-ui"></span></label>
						<div>
							<strong><?php esc_html_e( 'Limiter les tentatives de connexion', 'infinity-loginshield' ); ?></strong>
							<p class="inls-inst-desc"><?php esc_html_e( 'Après N échecs, l’adresse IP et l’identifiant sont bloqués temporairement.', 'infinity-loginshield' ); ?></p>
						</div>
						<div class="inls-inst-attempts">
							<label for="inls-wizard-attempts"><?php esc_html_e( 'Tentatives autorisées', 'infinity-loginshield' ); ?></label>
							<input type="number" id="inls-wizard-attempts" class="inls-input" name="inls[sec_max_attempts]" min="1" max="20" value="<?php echo esc_attr( $s['sec_max_attempts'] ); ?>">
						</div>
					</div>
					<p class="inls-inst-pro-note">✦ <a href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-loginshield-pro' ) ); ?>"><?php esc_html_e( 'Passer en Pro', 'infinity-loginshield' ); ?></a> — <?php esc_html_e( '2FA, reCAPTCHA, URL de connexion personnalisée et alertes e-mail.', 'infinity-loginshield' ); ?></p>
				</section>

				<footer class="inls-inst-footer">
					<button type="button" class="inls-btn inls-btn-ghost inls-step-prev"><?php esc_html_e( 'Retour', 'infinity-loginshield' ); ?></button>
					<button type="button" class="inls-btn inls-btn-primary inls-step-next"><?php esc_html_e( 'Continuer', 'infinity-loginshield' ); ?></button>
					<button type="submit" class="inls-btn inls-btn-primary inls-step-finish"><?php esc_html_e( 'Terminer et ouvrir le dashboard', 'infinity-loginshield' ); ?></button>
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
			array( __( 'Limitation des tentatives + blocage IP', 'infinity-loginshield' ), true, true ),
			array( __( 'Messages de sécurité personnalisés', 'infinity-loginshield' ), true, true ),
			array( __( 'Journal des tentatives (audit complet)', 'infinity-loginshield' ), false, true ),
			array( __( 'Alertes e-mail après chaque blocage', 'infinity-loginshield' ), false, true ),
			array( __( 'reCAPTCHA v3 / hCaptcha sur la connexion', 'infinity-loginshield' ), false, true ),
			array( __( 'Double authentification (2FA)', 'infinity-loginshield' ), false, true ),
			array( __( 'URL de connexion personnalisée', 'infinity-loginshield' ), false, true ),
			array( __( 'Blocage géographique (pays)', 'infinity-loginshield' ), false, true ),
			array( __( 'Protection dédiée de /wp-admin (liste blanche IP)', 'infinity-loginshield' ), false, true ),
			array( __( 'Détection avancée des activités suspectes', 'infinity-loginshield' ), false, true ),
			array( __( 'Sessions & appareils de confiance', 'infinity-loginshield' ), false, true ),
		);
		?>
		<div class="wrap inls-wrap inls-pro">
			<div class="inls-hero inls-pro-hero">
				<span class="inls-hero-mark" aria-hidden="true">&#10022;</span>
				<h1><?php esc_html_e( 'Infinity LoginShield Pro', 'infinity-loginshield' ); ?></h1>
				<p><?php esc_html_e( 'Poussez la sécurité de votre page de connexion au niveau supérieur : protection avancée, surveillance et contrôle total.', 'infinity-loginshield' ); ?></p>
				<div class="inls-hero-actions">
					<a class="inls-btn inls-btn-pro" href="<?php echo esc_url( self::pro_url() ); ?>" target="_blank" rel="noopener">
						<span class="dashicons dashicons-superhero-alt"></span> <?php esc_html_e( 'Passer en Pro', 'infinity-loginshield' ); ?>
					</a>
					<a class="inls-btn inls-btn-ghost is-light" href="<?php echo esc_url( admin_url( 'admin.php?page=infinity-loginshield' ) ); ?>">
						<?php esc_html_e( 'Revenir au dashboard', 'infinity-loginshield' ); ?>
					</a>
				</div>
			</div>

			<div class="inls-about-card inls-pro-table-card">
				<h2><span class="dashicons dashicons-shield-alt"></span> <?php esc_html_e( 'Niveaux de sécurité : Gratuit vs Pro', 'infinity-loginshield' ); ?></h2>
				<table class="inls-pro-table">
					<thead>
						<tr><th><?php esc_html_e( 'Fonctionnalité', 'infinity-loginshield' ); ?></th><th><?php esc_html_e( 'Gratuit', 'infinity-loginshield' ); ?></th><th>Pro ✦</th></tr>
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
				<p class="inls-pro-note"><?php esc_html_e( 'Le module Pro est en préparation — le bouton « Passer en Pro » devient actif dès sa sortie (URL personnalisable via le filtre infinity_loginshield_pro_url).', 'infinity-loginshield' ); ?></p>
			</div>
		</div>
		<?php
	}
}

Inls_Admin::init();
