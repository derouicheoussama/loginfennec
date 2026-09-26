<?php

/**
 * ∞ INFINITY CODER — création originale de Derouiche Oussama
 *
 * Plugin   : LoginFennec Pro – Login Customizer & Security
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
 * @package LoginFennecPro
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
		add_action( 'admin_notices', array( __CLASS__, 'obsolete_copies_notice' ) );
		add_action( 'after_plugin_row', array( __CLASS__, 'obsolete_copy_row' ), 10, 2 );
		add_filter( 'admin_footer_text', array( __CLASS__, 'footer_signature' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( LOGINFENNEC_FILE ), array( __CLASS__, 'plugin_action_links' ) );
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
		if ( ! empty( lnf_settings()['white_label'] ) ) {
			return '';
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && false !== strpos( (string) $screen->id, 'loginfennec' ) ) {
			return '∞ <strong>Infinity Coder</strong> — conçu par Derouiche Oussama · '
				. '<a href="https://www.derouicheoussama.com" target="_blank" rel="noopener noreferrer">derouicheoussama.com</a>';
		}
		return $text;
	}

	/**
	 * Menu : personnalisation, page Pro et installateur (page cachée).
	 */
	public static function menu() {
		// Icône du menu admin : serrure + fennec (identité v2), gris dashicons.
		$fennec_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 214" width="20" height="20"><g fill="none" stroke="#a7aaad" stroke-linecap="round" stroke-linejoin="round"><path d="M 50 122 A 64 64 0 1 1 150 122 L 162 192 Q 164 200 156 200 L 44 200 Q 36 200 38 192 Z" stroke-width="14"/><path d="M 50 100 C 52 70 58 42 66 27 C 68 23 72 24 74 28 L 91 60" stroke-width="11"/><path d="M 150 100 C 148 70 142 42 134 27 C 132 23 128 24 126 28 L 109 60" stroke-width="11"/><path d="M 50 100 C 47 122 58 137 78 144 C 92 148 108 148 122 144 C 142 137 153 122 150 100" stroke-width="11"/></g></svg>';
		$menu_icon  = 'data:image/svg+xml;base64,' . base64_encode( $fennec_svg );

		add_menu_page(
			__( 'LoginFennec Pro', 'loginfennec' ),
			__( 'LoginFennec Pro', 'loginfennec' ),
			'manage_options',
			'loginfennec',
			array( __CLASS__, 'render_page' ),
			$menu_icon,
			3
		);
		add_submenu_page(
			'loginfennec',
			__( 'Personnalisation de la connexion', 'loginfennec' ),
			__( 'Personnalisation', 'loginfennec' ),
			'manage_options',
			'loginfennec',
			array( __CLASS__, 'render_page' )
		);
		add_submenu_page(
			'loginfennec',
			__( 'Passer en Pro', 'loginfennec' ),
			__( 'Passer en Pro', 'loginfennec' ),
			'manage_options',
			'loginfennec-pro',
			array( __CLASS__, 'render_pro' )
		);
		add_submenu_page(
			'loginfennec',
			__( 'À propos d’LoginFennec Pro', 'loginfennec' ),
			__( 'À propos', 'loginfennec' ),
			'manage_options',
			'loginfennec-about',
			array( __CLASS__, 'render_about' )
		);
		add_submenu_page(
			null,
			__( 'Bienvenue — LoginFennec Pro', 'loginfennec' ),
			__( 'Installateur', 'loginfennec' ),
			'manage_options',
			'loginfennec-installer',
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
		wp_safe_redirect( admin_url( 'admin.php?page=loginfennec-installer' ) );
		exit;
	}

	/**
	 * Charge les assets sur les pages du plugin.
	 *
	 * @param string $hook Page courante.
	 */
	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'loginfennec' ) ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();

		// Cache-busting : la version intègre la date de modification des fichiers.
		$css_ver = LOGINFENNEC_VERSION;
		$js_ver  = LOGINFENNEC_VERSION;
		$css_file = LOGINFENNEC_DIR . 'assets/css/admin.css';
		$js_file  = LOGINFENNEC_DIR . 'assets/js/admin.js';
		if ( file_exists( $css_file ) ) {
			$css_ver .= '.' . (string) filemtime( $css_file );
		}
		if ( file_exists( $js_file ) ) {
			$js_ver .= '.' . (string) filemtime( $js_file );
		}

		wp_enqueue_style(
			'lnf-admin',
			LOGINFENNEC_URL . 'assets/css/admin.css',
			array(),
			$css_ver
		);
		wp_enqueue_script(
			'lnf-admin',
			LOGINFENNEC_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			$js_ver,
			true
		);
		$plans = lnf_license_plans();
		wp_localize_script(
			'lnf-admin',
			'LNF_ADMIN',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'lnf_admin' ),
				'previewAction'  => 'lnf_preview_css',
				'sitename'       => get_bloginfo( 'name' ),
				'siteUrl'        => home_url( '/' ),
				'checkoutUrl'    => lnf_checkout_url(),
				'paypalEmail'    => lnf_paypal_email(),
				'paypalCurrency' => lnf_paypal_currency(),
				'paypalRate'     => lnf_paypal_rate(),
				'ccpRip'         => lnf_ccp_rip(),
				'baridimob'      => lnf_baridimob(),
				'ccpName'        => lnf_ccp_name(),
				'contactEmail'   => lnf_contact_email(),
				'whatsapp'       => lnf_whatsapp_number(),
				'proUrl'         => admin_url( 'admin.php?page=loginfennec-pro' ),
				'prices'         => array(
					'site1' => array(
						'yearly'   => (int) $plans['site1']['yearly']['price'],
						'lifetime' => (int) $plans['site1']['lifetime']['price'],
					),
					'site5' => array(
						'yearly'   => (int) $plans['site5']['yearly']['price'],
						'lifetime' => (int) $plans['site5']['lifetime']['price'],
					),
				),
				'planLabels'     => array(
					'site1' => $plans['site1']['label'],
					'site5' => $plans['site5']['label'],
				),
			)
		);
	}

	/**
	 * Enregistre les réglages.
	 */
	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'loginfennec' ) );
		}
		check_admin_referer( 'lnf_save', 'lnf_nonce' );

		$input = isset( $_POST['lnf'] ) && is_array( $_POST['lnf'] ) ? wp_unslash( $_POST['lnf'] ) : array();
		// Mise à jour partielle : tout champ absent du POST (formulaire tronqué
		// par max_input_vars, extension de sécurité…) conserve sa valeur
		// enregistrée au lieu d'être réinitialisée aux défauts.
		update_option( LOGINFENNEC_OPTION, lnf_sanitize_settings( $input, lnf_settings() ), 'yes' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'loginfennec',
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
			wp_die( esc_html__( 'Accès refusé.', 'loginfennec' ) );
		}
		check_admin_referer( 'lnf_save', 'lnf_nonce' );

		delete_option( LOGINFENNEC_OPTION );
		add_option( LOGINFENNEC_OPTION, lnf_get_defaults(), '', 'yes' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'loginfennec',
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
			wp_die( esc_html__( 'Accès refusé.', 'loginfennec' ) );
		}
		check_admin_referer( 'lnf_wizard', 'lnf_wizard_nonce' );

		$base   = lnf_settings();
		$input  = isset( $_POST['lnf'] ) && is_array( $_POST['lnf'] ) ? wp_unslash( $_POST['lnf'] ) : array();
		update_option( LOGINFENNEC_OPTION, lnf_sanitize_settings( $input, $base ), 'yes' );
		delete_option( 'lnf_pending_installer' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'loginfennec',
					'lnf-welcome' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Vérifie les mises à jour à la demande (interroge WordPress.org puis
	 * le canal secondaire, dans cet ordre de priorité).
	 */
	public static function ajax_check_updates() {
		check_ajax_referer( 'lnf_admin', 'nonce' );
		if ( ! current_user_can( 'update_plugins' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		// Canal secondaire : purge son cache pour que la vérification soit
		// réellement à jour (sinon le transient 12 h masquerait une release
		// toute fraîche).
		if ( class_exists( 'Lnf_Update_Server' ) ) {
			delete_transient( 'lnf_update_remote' );
		}

		// 1) WordPress.org (canal prioritaire).
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		$info = plugins_api(
			'plugin_information',
			array(
				'slug'   => 'loginfennec',
				'fields' => array( 'versions' => false, 'sections' => false, 'banners' => false ),
			)
		);
		if ( ! is_wp_error( $info ) && ! empty( $info->version ) && version_compare( LOGINFENNEC_VERSION, (string) $info->version, '<' ) ) {
			if ( function_exists( 'wp_update_plugins' ) ) {
				wp_update_plugins();
			}
			$basename   = plugin_basename( LOGINFENNEC_FILE );
			$update_url = wp_nonce_url(
				self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . rawurlencode( $basename ) ),
				'upgrade-plugin_' . $basename
			);
			wp_send_json_success(
				array(
					'status'  => 'available',
					'version' => (string) $info->version,
					'channel' => 'wordpress.org',
					'url'     => $update_url,
				)
			);
		}

		// 2) Canal auto-hébergé / GitHub (si configuré).
		if ( class_exists( 'Lnf_Update_Server' ) ) {
			$remote = Lnf_Update_Server::fetch();
			if ( ! empty( $remote ) && version_compare( LOGINFENNEC_VERSION, (string) $remote['version'], '<' ) ) {
				if ( function_exists( 'wp_update_plugins' ) ) {
					wp_update_plugins();
				}
				$basename   = plugin_basename( LOGINFENNEC_FILE );
				$update_url = wp_nonce_url(
					self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . rawurlencode( $basename ) ),
					'upgrade-plugin_' . $basename
				);
				wp_send_json_success(
					array(
						'status'  => 'available',
						'version' => (string) $remote['version'],
						'channel' => 'github',
						'url'     => $update_url,
					)
				);
			}
		}

		// À jour : indique le canal qui servira les prochaines mises à jour.
		$channel = class_exists( 'Lnf_Update_Server' ) && '' !== Lnf_Update_Server::server_url() ? 'github' : 'wordpress.org';
		wp_send_json_success(
			array(
				'status'  => 'up_to_date',
				'version' => LOGINFENNEC_VERSION,
				'channel' => $channel,
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
			wp_die( esc_html__( 'Accès refusé.', 'loginfennec' ) );
		}
		check_admin_referer( 'lnf_export' );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="loginfennec-settings-' . gmdate( 'Ymd-Hi' ) . '.json"' );

		// Anti-fuite : les secrets (tokens SMS, clés reCAPTCHA) ne sortent jamais du site.
		$settings = lnf_settings();
		foreach ( lnf_secret_keys() as $secret ) {
			unset( $settings[ $secret ] );
		}
		echo wp_json_encode( $settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * Importe des réglages depuis un fichier JSON.
	 */
	public static function import_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'loginfennec' ) );
		}
		check_admin_referer( 'lnf_import', 'lnf_import_nonce' );

		$redirect_ok = add_query_arg(
			array(
				'page'          => 'loginfennec',
				'lnf-imported' => 1,
			),
			admin_url( 'admin.php' )
		);
		$redirect_ko = add_query_arg(
			array(
				'page'           => 'loginfennec',
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

		$imported = lnf_sanitize_settings( $data, null );

		// Anti-fuite (sens inverse) : un import sans secrets ne doit pas effacer
		// ceux déjà enregistrés sur le site. Les valeurs stockées lues via
		// lnf_settings() sont en clair (déchiffrées) : on les rechiffre
		// pour l'écriture.
		$stored = lnf_settings();
		foreach ( lnf_secret_keys() as $secret ) {
			if ( '' === (string) $imported[ $secret ] && '' !== (string) $stored[ $secret ] ) {
				$imported[ $secret ] = lnf_encrypt_secret( (string) $stored[ $secret ] );
			}
		}

		update_option( LOGINFENNEC_OPTION, $imported, 'yes' );
		wp_safe_redirect( $redirect_ok );
		exit;
	}

	/**
	 * Vide le cache du plugin (transients de mises à jour et de vérifications)
	 * et rafraîchit les assets côté navigateur.
	 */
	public static function purge_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'loginfennec' ) );
		}
		check_admin_referer( 'lnf_purge_cache' );

		delete_transient( 'lnf_gh_release' );
		delete_transient( 'lnf_wporg_check' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'loginfennec',
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
			wp_die( esc_html__( 'Accès refusé.', 'loginfennec' ) );
		}
		check_admin_referer( 'lnf_export_log' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="loginfennec-journal-' . gmdate( 'Ymd-Hi' ) . '.csv"' );

		echo 'date_utc,ip,username,action' . "\r\n";
		foreach ( Lnf_Login_Security::get_log() as $event ) {
			$row = array(
				gmdate( 'Y-m-d H:i:s', (int) $event['t'] ),
				isset( $event['ip'] ) ? (string) $event['ip'] : '',
				isset( $event['u'] ) ? (string) $event['u'] : '',
				isset( $event['a'] ) ? (string) $event['a'] : '',
			);
			echo implode( ',', array_map( array( __CLASS__, 'csv_field' ), $row ) ) . "\r\n";
		}
		exit;
	}

	/**
	 * Échappe un champ CSV (guillemets, séparateurs) et neutralise l'injection
	 * de formule pour les tableurs (= + - @ en début de valeur).
	 *
	 * @param string $value Valeur brute.
	 * @return string
	 */
	private static function csv_field( $value ) {
		$value = (string) $value;
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			$value = "'" . $value;
		}
		if ( preg_match( '/[",\r\n]/', $value ) ) {
			$value = '"' . str_replace( '"', '""', $value ) . '"';
		}
		return $value;
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
		// lnf_settings() retourne les secrets DÉCHIFFRÉS : on repasse par la
		// sanitization (qui rechiffre) pour ne jamais écrire de clair en base.
		update_option( LOGINFENNEC_OPTION, lnf_sanitize_settings( $s, $s ), 'yes' );
		wp_send_json_success();
	}

	/**
	 * Enregistre la fermeture de la demande d'avis.
	 */
	public static function dismiss_review() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'loginfennec' ) );
		}
		check_admin_referer( 'lnf_dismiss_review' );
		update_option( 'lnf_review_dismissed', 1, false );
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=loginfennec' ) );
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
			'<a href="' . esc_url( admin_url( 'admin.php?page=loginfennec' ) ) . '">'
			. esc_html__( 'Personnaliser', 'loginfennec' ) . '</a>'
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
		if ( plugin_basename( LOGINFENNEC_FILE ) !== $file ) {
			return $meta;
		}
		$meta[] = '<a href="https://github.com/derouicheoussama/loginfennec" target="_blank" rel="noopener noreferrer">GitHub</a>';
		$meta[] = '<a href="' . esc_url( admin_url( 'admin.php?page=loginfennec-about' ) ) . '">' . esc_html__( 'À propos & don', 'loginfennec' ) . '</a>';
		$meta[] = '<a href="https://wordpress.org/plugins/loginfennec/reviews/#new-post" target="_blank" rel="noopener noreferrer">★★★★★ ' . esc_html__( 'Noter le plugin', 'loginfennec' ) . '</a>';
		return $meta;
	}

	/**
	 * Ligne d'alerte sous une copie obsolète de LoginFennec dans Extensions :
	 * bandeau rouge avec un lien « Supprimer cette copie » en un clic.
	 *
	 * @param string $file Basename de l'extension de la ligne.
	 * @param array  $data Données d'en-tête.
	 */
	public static function obsolete_copy_row( $file, $data ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'plugins' !== $screen->id || ! current_user_can( 'delete_plugins' ) ) {
			return;
		}
		if ( $file === plugin_basename( LOGINFENNEC_FILE ) ) {
			return; // Notre propre ligne : toujours saine.
		}
		if ( false === strpos( (string) $data['Name'], 'LoginFennec' ) ) {
			return;
		}
		// Si la copie est PLUS RÉCENTE que nous, ce n'est pas elle l'obsolète.
		if ( isset( $data['Version'] ) && version_compare( LOGINFENNEC_VERSION, (string) $data['Version'], '<' ) ) {
			return;
		}
		$delete = wp_nonce_url(
			self_admin_url( 'plugins.php?action=delete&plugin=' . rawurlencode( $file ) ),
			'delete-plugin_' . $file
		);
		?>
		<tr class="plugin-update-tr"><td colspan="4" class="plugin-update"><div class="update-message notice inline notice-warning"><p>
			<strong>⚠️ <?php esc_html_e( 'Copie obsolète', 'loginfennec' ); ?> (v<?php echo esc_html( $data['Version'] ); ?>)</strong> —
			<?php echo esc_html( sprintf( /* translators: %s : version. */ __( 'la version %s est active dans un autre dossier. Cette copie doit être supprimée.', 'loginfennec' ), LOGINFENNEC_VERSION ) ); ?>
			<a class="button button-small button-link-delete" href="<?php echo esc_url( $delete ); ?>"><?php esc_html_e( 'Supprimer cette copie', 'loginfennec' ); ?></a>
		</p></div></td></tr>
		<?php
	}

	/**
	 * Détecte les copies obsolètes de LoginFennec (dossiers en double créés
	 * par un remplacement d'installation raté) et indique le dossier exact
	 * à supprimer. Affiché uniquement sur la page Extensions.
	 */
	public static function obsolete_copies_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'plugins' !== $screen->id || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$self = plugin_basename( LOGINFENNEC_FILE );
		$old  = array();
		foreach ( get_plugins() as $basename => $data ) {
			if ( $basename === $self || false === strpos( (string) $data['Name'], 'LoginFennec' ) ) {
				continue;
			}
			if ( '/loginfennec.php' === substr( $basename, -16 ) ) {
				$old[] = array( $basename, (string) $data['Version'] );
			}
		}
		if ( empty( $old ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>LoginFennec Pro :</strong> '
			. esc_html( sprintf( /* translators: %d : nombre de copies. */ __( '%d copie obsolète de l’extension est installée en double — une seule copie (la plus récente) doit rester.', 'loginfennec' ), count( $old ) ) )
			. '</p><ul style="list-style:disc;margin-left:18px">';
		foreach ( $old as $copy ) {
			$folder = dirname( $copy[0] );
			echo '<li>— version ' . esc_html( $copy[1] ) . ' — dossier <code>wp-content/plugins/' . esc_html( $folder ) . '</code> : '
				. esc_html__( 'cliquez « Supprimer » sur sa ligne, ou supprimez ce dossier via FTP.', 'loginfennec' )
				. '</li>';
		}
		echo '</ul></p></div>';
	}

	/**
	 * Notices de confirmation.
	 */
	public static function notices() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture seule GET pour affichage.
		$page = isset( $_GET["page"] ) ? sanitize_text_field( wp_unslash( $_GET["page"] ) ) : "";
		if ( empty( $page ) ) {
			return;
		}
		if ( ! isset( $_GET['page'] ) ) {
			return;
		}
		if ( 'loginfennec' === $page && isset( $_GET['lnf-welcome'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>🎉 ' . esc_html__( 'Bienvenue dans LoginFennec Pro !', 'loginfennec' ) . '</strong> ' . esc_html__( 'Votre page de connexion est prête — explorez les onglets pour la personnaliser.', 'loginfennec' ) . '</p></div>';
		}
		if ( 'loginfennec' === $page && isset( $_GET['lnf-saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Réglages enregistrés.', 'loginfennec' ) . '</strong> ' . esc_html__( 'Votre page de connexion est à jour.', 'loginfennec' ) . '</p></div>';
		}
		if ( 'loginfennec' === $page && isset( $_GET['lnf-reset'] ) ) {
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Réglages réinitialisés aux valeurs par défaut.', 'loginfennec' ) . '</p></div>';
		}
		if ( 'loginfennec' === $page && isset( $_GET['lnf-imported'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Réglages importés avec succès.', 'loginfennec' ) . '</strong></p></div>';
		}
		if ( 'loginfennec' === $page && isset( $_GET['lnf-import-error'] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Import impossible : fichier JSON invalide ou illisible.', 'loginfennec' ) . '</p></div>';
		}

		if ( 'loginfennec' === $page && isset( $_GET['lnf-cache'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cache du plugin vidé : vérifications de mises à jour réinitialisées et assets rafraîchis.', 'loginfennec' ) . '</p></div>';
		}

		// Demande d'avis : une seule fois, après 14 jours d'utilisation.
		if ( 'loginfennec' !== $page ) {
			return;
		}
		if ( 'loginfennec' !== $page ) {
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
				<strong><?php esc_html_e( 'Vous aimez LoginFennec Pro ?', 'loginfennec' ); ?></strong>
				<?php esc_html_e( 'Un avis de votre part aide beaucoup le plugin à grandir. Merci pour votre soutien !', 'loginfennec' ); ?>
				<a class="button button-primary" href="https://wordpress.org/plugins/loginfennec/reviews/#new-post" target="_blank" rel="noopener"><?php esc_html_e( 'Laisser un avis', 'loginfennec' ); ?></a>
				<a class="button-link" href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'C’est noté', 'loginfennec' ); ?></a>
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
		echo '<div class="lnf-field"' . esc_html( self::showif( $showif ) ) . '>';
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
		echo '<div class="lnf-field"' . esc_html( self::showif( $showif ) ) . '>';
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
		echo '<div class="lnf-field"' . esc_html( self::showif( $showif ) ) . '>';
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
	 * Champ secret : la valeur enregistrée n'est JAMAIS rendue dans le HTML.
	 * Champ vide = la clé actuelle est conservée ; saisir une valeur la remplace.
	 */
	protected static function field_secret( $s, $key, $label, $desc = '', $showif = array() ) {
		$has = '' !== (string) ( isset( $s[ $key ] ) ? $s[ $key ] : '' );
		echo '<div class="lnf-field"' . esc_html( self::showif( $showif ) ) . '>';
		echo '<label class="lnf-label" for="lnf-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		printf(
			'<input type="password" class="lnf-input" id="lnf-f-%1$s" name="lnf[%1$s]" value="" placeholder="%2$s" autocomplete="new-password">',
			esc_attr( $key ),
			$has ? esc_attr__( '••••••••  (enregistré — laisser vide pour conserver)', 'loginfennec' ) : esc_attr__( 'Collez la clé ici', 'loginfennec' )
		);
		if ( $has ) {
			echo '<p class="lnf-desc lnf-secret-ok"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Clé enregistrée — chiffrée dans la base de données, jamais affichée ni exportée.', 'loginfennec' ) . '</p>';
		}
		if ( $desc ) {
			echo '<p class="lnf-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Champ zone de texte.
	 */
	protected static function field_textarea( $s, $key, $label, $desc = '', $showif = array() ) {
		echo '<div class="lnf-field lnf-field-wide"' . esc_html( self::showif( $showif ) ) . '>';
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
		echo '<div class="lnf-field"' . esc_html( self::showif( $showif ) ) . '>';
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
		echo '<div class="lnf-field lnf-toggle-field"' . esc_html( self::showif( $showif ) ) . '>';
		// Sentinelle « 0 » : garantit que la clé est toujours soumise, même case
		// décochée — indispensable à la mise à jour partielle de save().
		// aria-label : la case est dans un <label> sans texte (lecteurs d'écran).
		echo '<label class="lnf-switch"><input type="hidden" name="lnf[' . esc_attr( $key ) . ']" value="0"><input type="checkbox" name="lnf[' . esc_attr( $key ) . ']" value="1"' . checked( ! empty( $s[ $key ] ), true, false ) . ' aria-label="' . esc_attr( wp_strip_all_tags( $label ) ) . '"><span class="lnf-switch-ui"></span></label>';
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
		echo '<div class="lnf-field lnf-field-wide"' . esc_html( self::showif( $showif ) ) . '>';
		echo '<label class="lnf-label" for="lnf-f-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		echo '<div class="lnf-media">';
		if ( ! empty( $s[ $key ] ) ) {
			printf( '<img class="lnf-media-thumb" src="%s" alt="">', esc_url( $s[ $key ] ) );
		} else {
			echo '<img class="lnf-media-thumb" src="" alt="" hidden>';
		}
		printf( '<input type="url" class="lnf-input" id="lnf-f-%1$s" name="lnf[%1$s]" value="%2$s" placeholder="https://…" autocomplete="off">', esc_attr( $key ), esc_attr( $s[ $key ] ) );
		echo '<span class="lnf-media-actions">';
		echo '<button type="button" class="button lnf-media-pick">' . esc_html__( 'Médiathèque', 'loginfennec' ) . '</button>';
		echo '<button type="button" class="button-link lnf-media-clear">' . esc_html__( 'Retirer', 'loginfennec' ) . '</button>';
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
			'dashboard' => array( __( 'Tableau de bord', 'loginfennec' ), 'dashicons-dashboard' ),
			'styles'    => array( __( 'Styles', 'loginfennec' ), 'dashicons-art' ),
			'logo'      => array( __( 'Logo', 'loginfennec' ), 'dashicons-format-image' ),
			'bg'        => array( __( 'Arrière-plan', 'loginfennec' ), 'dashicons-desktop' ),
			'form'      => array( __( 'Formulaire', 'loginfennec' ), 'dashicons-feedback' ),
			'links'     => array( __( 'Liens', 'loginfennec' ), 'dashicons-editor-unlink' ),
			'social'    => array( __( 'Réseaux sociaux', 'loginfennec' ), 'dashicons-share' ),
			'copyright' => array( __( 'Copyright', 'loginfennec' ), 'dashicons-text' ),
			'extras'    => array( __( 'Extras', 'loginfennec' ), 'dashicons-star-filled' ),
			'security'  => array( __( 'Sécurité', 'loginfennec' ), 'dashicons-shield-alt' ),
			'sms'       => array( __( 'SMS', 'loginfennec' ), 'dashicons-smartphone' ),
			'admin'     => array( __( 'Admin', 'loginfennec' ), 'dashicons-admin-appearance' ),
		);
	}

	/**
	 * Affiche la page de personnalisation.
	 */
	public static function render_page() {
		// Verrouillage : essai expiré sans licence Pro.
		if ( lnf_trial_is_locked() ) {
			self::render_trial_expired();
			return;
		}

		// Compte à rebours pendant l'essai.
		$trial = lnf_trial_status();
		if ( ! lnf_is_pro() && $trial['days_left'] <= 3 && $trial['days_left'] > 0 ) {
			echo '<div class="notice notice-warning"><p><strong>⏳ ' . esc_html__( 'Essai — ', 'loginfennec' ) . absint( $trial['days_left'] ) . esc_html__( ' jour(s) restant(s).', 'loginfennec' ) . '</strong> <a href="' . esc_url( admin_url( 'admin.php?page=loginfennec-pro' ) ) . '">' . esc_html__( 'Passer en Pro', 'loginfennec' ) . '</a></p></div>';
		}

		$s = lnf_settings();

		// Avertissement : le mode sans échec neutralise tout le plugin.
		if ( ! empty( $s['safe_mode'] ) ) {
			echo '<div class="notice notice-warning"><p><strong>🛠 ' . esc_html__( 'Mode sans échec actif :', 'loginfennec' ) . '</strong> '
				. esc_html__( 'personnalisation, sécurité, SMS, GEO et reCAPTCHA sont désactivés. Désactivez le mode sans échec dans l’onglet Extras pour tout réactiver.', 'loginfennec' )
				. '</p></div>';
		}

		// Alerte : un serveur limitant max_input_vars sous le volume de ce
		// formulaire (~130 champs) tronque silencieusement le POST en fin de
		// formulaire — précisément les derniers onglets (SMS, Admin).
		$vars_limit = (int) ini_get( 'max_input_vars' );
		if ( $vars_limit > 0 && $vars_limit < 200 ) {
			echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Sauvegarde incomplète possible :', 'loginfennec' ) . '</strong> '
				. esc_html(
					sprintf(
						/* translators: %d : limite actuelle du serveur. */
						__( 'votre serveur limite les formulaires à %d champs (max_input_vars) alors que cette page en envoie environ 130. Demandez à votre hébergeur (ou augmentez dans wp-config.php) une limite de 300 ou plus, puis réessayez.', 'loginfennec' ),
						$vars_limit
					)
				)
				. '</p></div>';
		}
		?>
			<div class="wrap lnf-wrap">
			<div class="lnf-topbar">
				<div class="lnf-brand">
					<img class="lnf-brand-logo" src="<?php echo esc_url( LOGINFENNEC_URL . 'assets/img/logo-fennec.png' ); ?>" alt="LoginFennec" />
					<span class="lnf-brand-text">
						<strong>LoginFennec Pro</strong>
						<em class="lnf-version"><?php echo esc_html( 'v' . LOGINFENNEC_VERSION ); ?></em>
					</span>
				</div>
				<div class="lnf-topbar-actions">
					<?php if ( ! lnf_is_pro() ) : ?>
						<a class="lnf-btn lnf-btn-pro" href="<?php echo esc_url( admin_url( 'admin.php?page=loginfennec-pro' ) ); ?>">
							<span class="dashicons dashicons-superhero-alt"></span> <?php esc_html_e( 'Passer en Pro', 'loginfennec' ); ?>
						</a>
					<?php endif; ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="lnf_reset">
						<?php wp_nonce_field( 'lnf_save', 'lnf_nonce' ); ?>
						<button type="submit" id="lnf-reset" class="lnf-btn lnf-btn-ghost">
							<?php esc_html_e( 'Réinitialiser', 'loginfennec' ); ?>
						</button>
					</form>
					<button type="submit" form="lnf-form" class="lnf-btn lnf-btn-primary">
						<?php esc_html_e( 'Enregistrer', 'loginfennec' ); ?>
					</button>
				</div>
			</div>

			<div class="lnf-backup-bar">
				<span class="lnf-backup-title"><span class="dashicons dashicons-database-export"></span> <?php esc_html_e( 'Sauvegarde des réglages', 'loginfennec' ); ?></span>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=lnf_export' ), 'lnf_export' ) ); ?>">
					<?php esc_html_e( 'Exporter (JSON)', 'loginfennec' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=lnf_purge_cache' ), 'lnf_purge_cache' ) ); ?>">
					<span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e( 'Vider le cache du plugin', 'loginfennec' ); ?>
				</a>
				<form class="lnf-import-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="lnf_import">
					<?php wp_nonce_field( 'lnf_import', 'lnf_import_nonce' ); ?>
					<label class="screen-reader-text" for="lnf-import-file"><?php esc_html_e( 'Fichier de réglages (JSON)', 'loginfennec' ); ?></label>
					<input type="file" id="lnf-import-file" name="lnf_import_file" accept="application/json,.json" required>
					<?php submit_button( __( 'Importer', 'loginfennec' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<form id="lnf-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lnf_save">
				<?php wp_nonce_field( 'lnf_save', 'lnf_nonce' ); ?>

				<nav class="lnf-tabs" aria-label="<?php esc_attr_e( 'Sections de personnalisation', 'loginfennec' ); ?>">
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
						<?php self::panel_sms( $s ); ?>
						<?php self::panel_admin( $s ); ?>
					</div>

					<aside class="lnf-preview" aria-label="<?php esc_attr_e( 'Aperçu en direct', 'loginfennec' ); ?>">
						<div class="lnf-preview-bar">
							<span class="lnf-preview-title"><?php esc_html_e( 'Aperçu en direct', 'loginfennec' ); ?></span>
							<span class="lnf-preview-devices">
								<button type="button" class="lnf-device is-active" data-width="0" title="<?php esc_attr_e( 'Bureau', 'loginfennec' ); ?>"><span class="dashicons dashicons-desktop"></span></button>
								<button type="button" class="lnf-device" data-width="480" title="<?php esc_attr_e( 'Tablette', 'loginfennec' ); ?>"><span class="dashicons dashicons-tablet"></span></button>
								<button type="button" class="lnf-device" data-width="375" title="<?php esc_attr_e( 'Mobile', 'loginfennec' ); ?>"><span class="dashicons dashicons-smartphone"></span></button>
							</span>
							<span class="lnf-preview-actions">
								<button type="button" class="lnf-expand" title="<?php esc_attr_e( 'Aperçu plein écran', 'loginfennec' ); ?>"><span class="dashicons dashicons-fullscreen-exit-alt"></span></button>
								<button type="button" class="lnf-refresh" title="<?php esc_attr_e( 'Recharger l’aperçu', 'loginfennec' ); ?>"><span class="dashicons dashicons-update"></span></button>
								<a class="lnf-open" href="<?php echo esc_url( wp_login_url() ); ?>" target="_blank" rel="noopener" title="<?php esc_attr_e( 'Ouvrir dans un onglet', 'loginfennec' ); ?>"><span class="dashicons dashicons-external"></span></a>
							</span>
						</div>
						<div class="lnf-frame-holder">
							<iframe id="lnf-frame" src="about:blank" data-src="<?php echo esc_url( wp_login_url() ); ?>" title="<?php esc_attr_e( 'Aperçu de la page de connexion', 'loginfennec' ); ?>"></iframe>
						</div>
						<p class="lnf-preview-note"><?php esc_html_e( 'Les couleurs et effets sont appliqués en direct. Les liens, réseaux sociaux et copyright apparaissent après enregistrement.', 'loginfennec' ); ?></p>
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
		self::panel_open( 'dashboard', __( 'Tableau de bord', 'loginfennec' ), __( 'Statistiques, score de sécurité et accès rapide à tous vos réglages.', 'loginfennec' ) );

		$social_count  = count( lnf_get_social_networks( $s ) );

		// ——— Statistiques de sécurité ———.
		$stats = Lnf_Login_Security::get_stats();
		$stat_items = array(
			array( 'dashicons-yes-alt', __( 'Connexions (7 jours)', 'loginfennec' ), $stats['logins7'], 'is-ok' ),
			array( 'dashicons-shield-alt', __( 'Blocages (7 jours)', 'loginfennec' ), $stats['blocked7'], 'is-warn' ),
			array( 'dashicons-warning', __( 'Échecs (24 h)', 'loginfennec' ), $stats['fails24'], 'is-err' ),
			array( 'dashicons-lock', __( 'Verrouillages actifs', 'loginfennec' ), $stats['locks'], 'is-lock' ),
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
			'sec_enable'           => __( 'Limite des tentatives', 'loginfennec' ),
			'sec_honeypot'         => __( 'Honeypot anti-robots', 'loginfennec' ),
			'sec_disable_authors'  => __( 'Anti-énumération des auteurs', 'loginfennec' ),
			'sec_disable_xmlrpc'   => __( 'XML-RPC désactivé', 'loginfennec' ),
			'sec_generic_error'    => __( 'Erreurs masquées', 'loginfennec' ),
		);
		$score    = 0;
		foreach ( $checks as $key => $label ) {
			if ( ! empty( $s[ $key ] ) ) {
				$score++;
			}
		}
		$score_class = 5 === $score ? 'is-high' : ( $score >= 3 ? 'is-mid' : 'is-low' );
		echo '<div class="lnf-score">';
		echo '<div class="lnf-score-head"><h3 class="lnf-group-title">' . esc_html__( 'Score de sécurité', 'loginfennec' ) . '</h3>';
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
		echo '<button type="button" class="lnf-card-link" data-goto="security">' . esc_html__( 'Renforcer la sécurité', 'loginfennec' ) . '</button>';
		if ( $score < 5 ) {
			echo '<button type="button" class="lnf-card-link lnf-recommended">⚡ ' . esc_html__( 'Activer le pack recommandé (5/5)', 'loginfennec' ) . '</button>';
		}
		echo '</div>';
		echo '</div>';

		// ——— Activité récente ———.
		$log = Lnf_Login_Security::get_log();
		echo '<div class="lnf-activity">';
		echo '<h3 class="lnf-group-title">' . esc_html__( 'Activité récente', 'loginfennec' ) . '</h3>';
		if ( $log ) {
			$badges = array(
				'failed'  => array( __( 'Échec', 'loginfennec' ), 'is-fail' ),
				'blocked' => array( __( 'Bloqué', 'loginfennec' ), 'is-blocked' ),
				'login'   => array( __( 'Connexion', 'loginfennec' ), 'is-login' ),
				'sms'     => array( __( 'SMS', 'loginfennec' ), 'is-login' ),
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
			echo '<button type="button" class="lnf-card-link" data-goto="security">' . esc_html__( 'Ouvrir le journal complet', 'loginfennec' ) . '</button>';
		} else {
			echo '<p class="lnf-desc">' . esc_html__( 'Aucun événement pour le moment — votre page de connexion est tranquille.', 'loginfennec' ) . '</p>';
		}
		echo '</div>';
		$links_count   = ( ! empty( $s['hide_lost_password'] ) ? 1 : 0 ) + ( ! empty( $s['hide_back_to'] ) ? 1 : 0 ) + ( ! empty( $s['back_to_url'] ) || ! empty( $s['back_to_text'] ) ? 1 : 0 );
		$preset_labels = array(
			'glass'   => __( 'Effet verre', 'loginfennec' ),
			'minimal' => __( 'Minimal', 'loginfennec' ),
			'dark'    => __( 'Sombre', 'loginfennec' ),
			'sunset'  => __( 'Coucher de soleil', 'loginfennec' ),
			'ocean'   => __( 'Océan', 'loginfennec' ),
			'forest'  => __( 'Forêt', 'loginfennec' ),
			'neon'    => __( 'Néon', 'loginfennec' ),
			'sakura'  => __( 'Sakura', 'loginfennec' ),
			'mono'    => __( 'Monochrome', 'loginfennec' ),
			'royal'   => __( 'Royal', 'loginfennec' ),
			'custom'  => __( 'Personnalisé', 'loginfennec' ),
		);

		$cards = array(
			array(
				'icon'   => 'dashicons-art',
				'title'  => __( 'Style actif', 'loginfennec' ),
				'state'  => isset( $preset_labels[ $s['preset'] ] ) ? $preset_labels[ $s['preset'] ] : __( 'Personnalisé', 'loginfennec' ),
				'ok'     => true,
				'goto'   => 'styles',
				'button' => __( 'Changer de style', 'loginfennec' ),
			),
			array(
				'icon'   => 'dashicons-shield-alt',
				'title'  => __( 'Sécurité', 'loginfennec' ),
				'state'  => ! empty( $s['sec_enable'] )
					/* translators: 1 : nombre de tentatives, 2 : minutes. */
					? sprintf( __( 'Activé — %1$d tentatives, blocage %2$d min', 'loginfennec' ), (int) $s['sec_max_attempts'], (int) $s['sec_lockout_minutes'] )
					: __( 'Désactivé', 'loginfennec' ),
				'ok'     => ! empty( $s['sec_enable'] ),
				'goto'   => 'security',
				'button' => __( 'Configurer', 'loginfennec' ),
			),
			array(
				'icon'   => 'dashicons-share',
				'title'  => __( 'Réseaux sociaux', 'loginfennec' ),
				'state'  => $social_count > 0
					/* translators: %d : nombre de réseaux configurés. */
					? sprintf( _n( '%d réseau configuré', '%d réseaux configurés', $social_count, 'loginfennec' ), $social_count )
					: __( 'Aucun réseau configuré', 'loginfennec' ),
				'ok'     => $social_count > 0,
				'goto'   => 'social',
				'button' => __( 'Configurer', 'loginfennec' ),
			),
			array(
				'icon'   => 'dashicons-text',
				'title'  => __( 'Copyright', 'loginfennec' ),
				'state'  => ! empty( $s['copyright_enable'] ) ? __( 'Affiché', 'loginfennec' ) : __( 'Masqué', 'loginfennec' ),
				'ok'     => ! empty( $s['copyright_enable'] ),
				'goto'   => 'copyright',
				'button' => __( 'Configurer', 'loginfennec' ),
			),
			array(
				'icon'   => 'dashicons-format-image',
				'title'  => __( 'Logo', 'loginfennec' ),
				'state'  => ! empty( $s['logo_hide'] ) ? __( 'Masqué', 'loginfennec' ) : ( ! empty( $s['logo_url'] ) ? __( 'Image personnalisée', 'loginfennec' ) : __( 'Logo WordPress par défaut', 'loginfennec' ) ),
				'ok'     => ! empty( $s['logo_url'] ) && empty( $s['logo_hide'] ),
				'goto'   => 'logo',
				'button' => __( 'Personnaliser', 'loginfennec' ),
			),
			array(
				'icon'   => 'dashicons-editor-unlink',
				'title'  => __( 'Liens', 'loginfennec' ),
				'state'  => $links_count > 0 ? __( 'Liens personnalisés', 'loginfennec' ) : __( 'Liens par défaut', 'loginfennec' ),
				'ok'     => $links_count > 0,
				'goto'   => 'links',
				'button' => __( 'Personnaliser', 'loginfennec' ),
			),
			array(
				'icon'   => 'dashicons-download',
				'title'  => __( 'Mises à jour', 'loginfennec' ),
					'state'  => sprintf(
						/* translators: %s : version du plugin. */
						__( 'Version %s', 'loginfennec' ),
						LOGINFENNEC_VERSION
					),
				'ok'     => true,
				'goto'   => '',
				'button' => __( 'Vérifier les mises à jour', 'loginfennec' ),
				'check'  => true,
			),
			array(
				'icon'   => 'dashicons-superhero-alt',
				'title'  => __( 'LoginFennec Pro', 'loginfennec' ),
				'state'  => lnf_is_pro()
					? __( 'Licence active : ', 'loginfennec' ) . lnf_license_label()
					: __( 'URL de connexion personnalisée, verrouillage /wp-admin…', 'loginfennec' ),
				'ok'     => lnf_is_pro(),
				'goto'   => '',
				'link'   => admin_url( 'admin.php?page=loginfennec-pro' ),
				'button' => lnf_is_pro() ? __( 'Gérer ma licence', 'loginfennec' ) : __( 'Passer en Pro', 'loginfennec' ),
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
		self::panel_open( 'styles', __( 'Styles modernes', 'loginfennec' ), __( 'Appliquez un style et un thème d’interface en un clic, puis affinez-les dans les onglets suivants.', 'loginfennec' ) );

		$presets = array(
			'glass'   => array( __( 'Effet verre', 'loginfennec' ), 'linear-gradient(135deg,#667eea,#764ba2)' ),
			'minimal' => array( __( 'Minimal', 'loginfennec' ), 'linear-gradient(135deg,#f5f6f8,#dfe3ea)' ),
			'dark'    => array( __( 'Sombre', 'loginfennec' ), 'linear-gradient(160deg,#0f172a,#334155)' ),
			'sunset'  => array( __( 'Coucher de soleil', 'loginfennec' ), 'linear-gradient(120deg,#f97316,#ec4899)' ),
			'ocean'   => array( __( 'Océan', 'loginfennec' ), 'linear-gradient(135deg,#0ea5e9,#2563eb)' ),
			'forest'  => array( __( 'Forêt', 'loginfennec' ), 'linear-gradient(135deg,#059669,#065f46)' ),
			'neon'    => array( __( 'Néon', 'loginfennec' ), 'linear-gradient(135deg,#0f0c29,#302b63)' ),
			'sakura'  => array( __( 'Sakura', 'loginfennec' ), 'linear-gradient(120deg,#ee9ca7,#ffdde1)' ),
			'mono'    => array( __( 'Monochrome', 'loginfennec' ), 'linear-gradient(160deg,#9ca3af,#374151)' ),
			'royal'   => array( __( 'Royal', 'loginfennec' ), 'linear-gradient(150deg,#141e30,#243b55)' ),
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

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Thème d’interface du formulaire', 'loginfennec' ) . '</h3>';
		echo '<p class="lnf-panel-desc">' . esc_html__( 'Le design général du formulaire, indépendant des couleurs.', 'loginfennec' ) . '</p>';
		echo '<input type="hidden" name="lnf[form_theme]" value="' . esc_attr( $s['form_theme'] ) . '">';
		echo '<div class="lnf-presets lnf-themes">';
		$themes = array(
			'glass'    => array( __( 'Effet verre', 'loginfennec' ), 'background:linear-gradient(135deg,#667eea,#764ba2);box-shadow:inset 22px 22px 0 -8px rgba(255,255,255,.4);border-radius:8px;' ),
			'classic'  => array( __( 'Classique', 'loginfennec' ), 'background:#fff;border:1px solid #d5d3e8;border-radius:6px;' ),
			'outline'  => array( __( 'Contour', 'loginfennec' ), 'background:transparent;border:2px solid #0f5aa8;border-radius:8px;' ),
			'pill'     => array( __( 'Pillule', 'loginfennec' ), 'background:#fff;border-radius:999px;' ),
			'elevated' => array( __( 'Surélevé', 'loginfennec' ), 'background:#fff;border-radius:12px;box-shadow:0 12px 20px -8px rgba(0,0,0,.5);' ),
			'accent'   => array( __( 'Accent', 'loginfennec' ), 'background:#fff;border-top:6px solid #0f5aa8;border-radius:8px;' ),
			'minimal'  => array( __( 'Minimal', 'loginfennec' ), 'background:transparent;border-bottom:5px solid #0f5aa8;border-radius:0;' ),
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
		self::panel_open( 'logo', __( 'Logo', 'loginfennec' ), __( 'Remplacez le logo WordPress par le vôtre.', 'loginfennec' ) );

		self::field_toggle( $s, 'logo_hide', __( 'Masquer complètement le logo', 'loginfennec' ) );
		self::field_media( $s, 'logo_url', __( 'Image du logo', 'loginfennec' ), __( 'SVG ou PNG transparent recommandé.', 'loginfennec' ), array( 'logo_hide' => 0 ) );
		self::field_text( $s, 'logo_text', __( 'Ou logo en texte', 'loginfennec' ), 'text', __( 'ex. : MaBoutique', 'loginfennec' ), __( 'Remplace l’image par un titre stylé — prioritaire si les deux sont remplis.', 'loginfennec' ), array( 'logo_hide' => 0 ) );
		self::field_range( $s, 'logo_width', __( 'Largeur', 'loginfennec' ), 40, 400, 'px', '', array( 'logo_hide' => 0 ) );
		self::field_range( $s, 'logo_height', __( 'Hauteur', 'loginfennec' ), 24, 300, 'px', '', array( 'logo_hide' => 0 ) );
		self::field_text( $s, 'logo_link', __( 'Lien du logo', 'loginfennec' ), 'url', 'https://exemple.com', __( 'Laisser vide pour pointer vers l’accueil du site.', 'loginfennec' ), array( 'logo_hide' => 0 ) );

		self::panel_close();
	}

	/**
	 * Panneau : arrière-plan.
	 */
	protected static function panel_background( $s ) {
		self::panel_open( 'bg', __( 'Arrière-plan', 'loginfennec' ), __( 'Couleur, dégradé ou image, avec flou et voile coloré.', 'loginfennec' ) );

		self::field_select(
			$s,
			'bg_type',
			__( 'Type de fond', 'loginfennec' ),
			array(
				'color'    => __( 'Couleur unie', 'loginfennec' ),
				'gradient' => __( 'Dégradé', 'loginfennec' ),
				'image'    => __( 'Image', 'loginfennec' ),
			)
		);
		self::field_color( $s, 'bg_color1', __( 'Couleur de fond / dégradé 1', 'loginfennec' ) );
		self::field_color( $s, 'bg_color2', __( 'Dégradé — couleur 2', 'loginfennec' ), '', array( 'bg_type' => 'gradient' ) );
		self::field_range( $s, 'bg_gradient_angle', __( 'Angle du dégradé', 'loginfennec' ), 0, 360, '°', '', array( 'bg_type' => 'gradient' ) );
		self::field_media( $s, 'bg_image', __( 'Image de fond', 'loginfennec' ), '', array( 'bg_type' => 'image' ) );
		self::field_select(
			$s,
			'bg_size',
			__( 'Affichage de l’image', 'loginfennec' ),
			array(
				'cover'   => __( 'Couvrir (cover)', 'loginfennec' ),
				'contain' => __( 'Contenir (contain)', 'loginfennec' ),
				'repeat'  => __( 'Répéter (motif)', 'loginfennec' ),
			),
			'',
			array( 'bg_type' => 'image' )
		);
		self::field_select(
			$s,
			'bg_position',
			__( 'Position de l’image', 'loginfennec' ),
			array(
				'center'       => __( 'Centrée', 'loginfennec' ),
				'top'          => __( 'En haut', 'loginfennec' ),
				'bottom'       => __( 'En bas', 'loginfennec' ),
				'left'         => __( 'À gauche', 'loginfennec' ),
				'right'        => __( 'À droite', 'loginfennec' ),
				'top-left'     => __( 'Haut gauche', 'loginfennec' ),
				'top-right'    => __( 'Haut droit', 'loginfennec' ),
				'bottom-left'  => __( 'Bas gauche', 'loginfennec' ),
				'bottom-right' => __( 'Bas droit', 'loginfennec' ),
			),
			'',
			array( 'bg_type' => 'image' )
		);
		self::field_range( $s, 'bg_blur', __( 'Flou de l’image', 'loginfennec' ), 0, 30, 'px', __( 'Contrôle du flou (backdrop).', 'loginfennec' ), array( 'bg_type' => 'image' ) );
		self::field_range( $s, 'bg_brightness', __( 'Luminosité de l’image', 'loginfennec' ), 30, 150, '%', '', array( 'bg_type' => 'image' ) );
		self::field_range( $s, 'bg_saturation', __( 'Saturation de l’image', 'loginfennec' ), 0, 200, '%', '', array( 'bg_type' => 'image' ) );
		self::field_color( $s, 'bg_overlay_color', __( 'Voile coloré', 'loginfennec' ), __( 'Couche de couleur superposée au fond.', 'loginfennec' ) );
		self::field_range( $s, 'bg_overlay_opacity', __( 'Opacité du voile', 'loginfennec' ), 0, 100, '%' );

		self::panel_close();
	}

	/**
	 * Panneau : formulaire.
	 */
	protected static function panel_form( $s ) {
		self::panel_open( 'form', __( 'Formulaire', 'loginfennec' ), __( 'Effet verre, couleurs des champs, bouton et liens.', 'loginfennec' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Disposition', 'loginfennec' ) . '</h3>';
		self::field_select(
			$s,
			'layout',
			__( 'Disposition de la page', 'loginfennec' ),
			array(
				'single'      => __( 'Classique (formulaire centré)', 'loginfennec' ),
				'two-column'  => __( 'Deux colonnes (image latérale)', 'loginfennec' ),
			),
			__( 'Le mode deux colonnes place une image à gauche du formulaire.', 'loginfennec' )
		);
		// field_media n'expose pas showif : le wrap est géré ici.
		echo '<div class="lnf-field lnf-field-wide" data-showif="' . esc_attr( wp_json_encode( array( 'layout' => 'two-column' ) ) ) . '">';
		echo '<label class="lnf-label" for="lnf-f-side_image">' . esc_html__( 'Image latérale', 'loginfennec' ) . '</label>';
		echo '<div class="lnf-media">';
		if ( ! empty( $s['side_image'] ) ) {
			printf( '<img class="lnf-media-thumb" src="%s" alt="">', esc_url( $s['side_image'] ) );
		} else {
			echo '<img class="lnf-media-thumb" src="" alt="" hidden>';
		}
		printf( '<input type="url" class="lnf-input" id="lnf-f-side_image" name="lnf[side_image]" value="%s" placeholder="https://…" autocomplete="off">', esc_attr( $s['side_image'] ) );
		echo '<span class="lnf-media-actions"><button type="button" class="button lnf-media-pick">' . esc_html__( 'Médiathèque', 'loginfennec' ) . '</button></span>';
		echo '</div><p class="lnf-desc">' . esc_html__( 'S’affiche à gauche du formulaire. Sans image, la disposition classique reprend.', 'loginfennec' ) . '</p></div>';

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Conteneur', 'loginfennec' ) . '</h3>';
		self::field_color( $s, 'form_bg', __( 'Fond du formulaire', 'loginfennec' ) );
		self::field_range( $s, 'form_opacity', __( 'Opacité du fond', 'loginfennec' ), 0, 100, '%' );
		self::field_range( $s, 'form_blur', __( 'Flou (effet verre)', 'loginfennec' ), 0, 40, 'px' );
		self::field_range( $s, 'form_radius', __( 'Arrondi des coins', 'loginfennec' ), 0, 60, 'px' );
		self::field_range( $s, 'form_width', __( 'Largeur du formulaire', 'loginfennec' ), 260, 560, 'px' );
		self::field_range( $s, 'form_padding', __( 'Espacement intérieur', 'loginfennec' ), 12, 80, 'px' );
		self::field_range( $s, 'input_height', __( 'Hauteur des champs', 'loginfennec' ), 0, 60, 'px', __( '0 = hauteur WordPress par défaut.', 'loginfennec' ) );
		self::field_toggle( $s, 'form_shadow', __( 'Ombre portée', 'loginfennec' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Textes et champs', 'loginfennec' ) . '</h3>';
		self::field_color( $s, 'text_color', __( 'Texte du formulaire', 'loginfennec' ) );
		self::field_color( $s, 'label_color', __( 'Libellés', 'loginfennec' ) );
		self::field_color( $s, 'input_bg', __( 'Fond des champs', 'loginfennec' ) );
		self::field_color( $s, 'input_color', __( 'Texte des champs', 'loginfennec' ) );
		self::field_color( $s, 'input_border', __( 'Bordure des champs', 'loginfennec' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Bouton « Se connecter »', 'loginfennec' ) . '</h3>';
		self::field_color( $s, 'button_bg', __( 'Couleur du bouton', 'loginfennec' ) );
		self::field_color( $s, 'button_hover', __( 'Couleur au survol', 'loginfennec' ) );
		self::field_range( $s, 'button_radius', __( 'Arrondi du bouton', 'loginfennec' ), 0, 40, 'px' );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Liens', 'loginfennec' ) . '</h3>';
		self::field_color( $s, 'link_color', __( 'Couleur des liens', 'loginfennec' ) );

		self::panel_close();
	}

	/**
	 * Panneau : liens.
	 */
	protected static function panel_links( $s ) {
		self::panel_open( 'links', __( 'Liens de la page de connexion', 'loginfennec' ), __( 'Modifiez la cible et le texte des liens, ou masquez-les.', 'loginfennec' ) );

		self::field_toggle( $s, 'hide_lost_password', __( 'Masquer « Mot de passe perdu ? »', 'loginfennec' ) );
		self::field_toggle( $s, 'hide_back_to', __( 'Masquer « Retour au site »', 'loginfennec' ) );
		self::field_text( $s, 'back_to_text', __( 'Texte du lien « Retour au site »', 'loginfennec' ), 'text', __( 'ex. : Retour à la boutique', 'loginfennec' ) );
		self::field_text( $s, 'back_to_url', __( 'Cible du lien « Retour au site »', 'loginfennec' ), 'url', 'https://exemple.com' );
		self::field_toggle( $s, 'hide_register', __( 'Masquer le lien « S’enregistrer »', 'loginfennec' ) );
		self::field_text( $s, 'register_text', __( 'Texte du lien « S’enregistrer »', 'loginfennec' ), 'text', __( 'ex. : Créer un compte client', 'loginfennec' ) );

		self::panel_close();
	}

	/**
	 * Panneau : réseaux sociaux.
	 */
	protected static function panel_social( $s ) {
		self::panel_open( 'social', __( 'Icônes de réseaux sociaux', 'loginfennec' ), __( 'Renseignez une URL pour afficher l’icône — laissez vide pour la masquer.', 'loginfennec' ) );

		self::field_toggle( $s, 'social_enable', __( 'Afficher les icônes sociales', 'loginfennec' ) );
		self::field_toggle( $s, 'social_brand', __( 'Couleurs officielles des marques', 'loginfennec' ), __( 'Chaque icône reprend sa couleur officielle : Facebook bleu, X noir, dégradé Instagram, LinkedIn bleu, YouTube rouge.', 'loginfennec' ) );

		echo '<div class="lnf-field" data-showif="' . esc_attr( wp_json_encode( array( 'social_enable' => 1 ) ) ) . '">';
		echo '<span class="lnf-label">' . esc_html__( 'Style d’icône', 'loginfennec' ) . '</span>';
		echo '<input type="hidden" name="lnf[social_variant]" value="' . esc_attr( $s['social_variant'] ?? 'fill' ) . '">';
		echo '<div class="lnf-social-variants">';
		$variants = array(
			'fill'    => array( __( 'Remplie', 'loginfennec' ), 'background:' . $s['social_icon_bg'] . ';color:' . $s['social_icon_color'] . ';' ),
			'outline' => array( __( 'Contour', 'loginfennec' ), 'border:2px solid ' . $s['social_icon_color'] . ';color:' . $s['social_icon_color'] . ';' ),
			'plain'   => array( __( 'Simple', 'loginfennec' ), 'color:' . $s['social_icon_color'] . ';' ),
			'soft'    => array( __( 'Douce', 'loginfennec' ), 'background:' . lnf_hex_to_rgba( $s['social_icon_color'], 14 ) . ';color:' . $s['social_icon_color'] . ';' ),
		);
		foreach ( $variants as $vkey => $vdata ) {
			printf(
				'<button type="button" class="lnf-social-variant%2$s" data-variant="%1$s"><span class="lnf-sv-dot" style="%3$s"></span>%4$s</button>',
				esc_attr( $vkey ),
				( ( $s['social_variant'] ?? 'fill' ) === $vkey ) ? ' is-active' : '',
				esc_attr( $vdata[1] ),
				esc_html( $vdata[0] )
			);
		}
		echo '</div></div>';

		self::field_select(
			$s,
			'social_style',
			__( 'Forme des icônes', 'loginfennec' ),
			array(
				'circle'  => __( 'Cercle', 'loginfennec' ),
				'rounded' => __( 'Arrondi', 'loginfennec' ),
				'square'  => __( 'Carré', 'loginfennec' ),
			),
			'',
			array( 'social_enable' => 1 )
		);
		self::field_range( $s, 'social_size', __( 'Taille des icônes', 'loginfennec' ), 28, 72, 'px', '', array( 'social_enable' => 1 ) );
		self::field_color( $s, 'social_icon_color', __( 'Couleur de l’icône', 'loginfennec' ), '', array( 'social_enable' => 1 ) );
		self::field_color( $s, 'social_icon_bg', __( 'Fond de l’icône', 'loginfennec' ), '', array( 'social_enable' => 1 ) );
		self::field_range( $s, 'social_icon_bg_opacity', __( 'Opacité du fond d’icône', 'loginfennec' ), 0, 100, '%', '', array( 'social_enable' => 1 ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Réseaux', 'loginfennec' ) . '</h3>';
		self::field_text( $s, 'social_facebook', __( 'Facebook', 'loginfennec' ), 'url', 'https://facebook.com/votre-page' );
		self::field_text( $s, 'social_twitter', __( 'X (Twitter)', 'loginfennec' ), 'url', 'https://x.com/votre-compte' );
		self::field_text( $s, 'social_instagram', __( 'Instagram', 'loginfennec' ), 'url', 'https://instagram.com/votre-compte' );
		self::field_text( $s, 'social_linkedin', __( 'LinkedIn', 'loginfennec' ), 'url', 'https://linkedin.com/in/votre-profil' );
		self::field_text( $s, 'social_youtube', __( 'YouTube', 'loginfennec' ), 'url', 'https://youtube.com/@votre-chaine' );
		self::field_text( $s, 'social_email', __( 'E-mail', 'loginfennec' ), 'email', 'contact@exemple.com' );

		self::panel_close();
	}

	/**
	 * Panneau : copyright.
	 */
	protected static function panel_copyright( $s ) {
		self::panel_open( 'copyright', __( 'Copyright', 'loginfennec' ), __( 'Affichez votre mention de copyright sous le formulaire.', 'loginfennec' ) );

		self::field_toggle( $s, 'copyright_enable', __( 'Afficher le copyright', 'loginfennec' ) );
		self::field_textarea(
			$s,
			'copyright_text',
			__( 'Texte du copyright', 'loginfennec' ),
			/* translators: les balises <code> sont des variables. */
			sprintf( __( 'Variables disponibles : %1$s (année) et %2$s (nom du site).', 'loginfennec' ), '<code>{year}</code>', '<code>{sitename}</code>' ),
			array( 'copyright_enable' => 1 )
		);

		self::panel_close();
	}

	/**
	 * Panneau : extras (message d'accueil, typographie, animation).
	 */
	protected static function panel_extras( $s ) {
		self::panel_open( 'extras', __( 'Extras', 'loginfennec' ), __( 'Message d’accueil, typographie et animation d’entrée de la page de connexion.', 'loginfennec' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Message de bienvenue', 'loginfennec' ) . '</h3>';
		self::field_toggle( $s, 'welcome_enable', __( 'Afficher un message d’accueil', 'loginfennec' ), __( 'Titre et sous-titre affichés au-dessus du formulaire.', 'loginfennec' ) );
		self::field_text( $s, 'welcome_title', __( 'Titre', 'loginfennec' ), 'text', __( 'ex. : Bon retour parmi nous ✨', 'loginfennec' ), '', array( 'welcome_enable' => 1 ) );
		self::field_text( $s, 'welcome_subtitle', __( 'Sous-titre', 'loginfennec' ), 'text', __( 'ex. : Connectez-vous pour gérer votre boutique.', 'loginfennec' ), '', array( 'welcome_enable' => 1 ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Typographie', 'loginfennec' ) . '</h3>';
		self::field_select(
			$s,
			'font_family',
			__( 'Police', 'loginfennec' ),
			array(
				'system'  => __( 'Système (moderne)', 'loginfennec' ),
				'serif'   => __( 'Serif élégante', 'loginfennec' ),
				'rounded' => __( 'Arrondie', 'loginfennec' ),
				'mono'    => __( 'Monospace', 'loginfennec' ),
			)
		);
		self::field_range( $s, 'font_size', __( 'Taille du texte', 'loginfennec' ), 12, 18, 'px' );
		self::field_text( $s, 'font_google', __( 'Police Google (nom exact)', 'loginfennec' ), 'text', __( 'ex. : Poppins', 'loginfennec' ), __( 'Prioritaire sur la famille générique. Vide = utiliser la famille sélectionnée ci-dessus.', 'loginfennec' ) );
		self::field_text( $s, 'font_google_weight', __( 'Graisses Google Fonts', 'loginfennec' ), 'text', '400;500;700', __( 'Poids demandés à fonts.googleapis.com, séparés par des points-virgules.', 'loginfennec' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Animation d’entrée', 'loginfennec' ) . '</h3>';
		self::field_select(
			$s,
			'anim',
			__( 'Effet à l’ouverture de la page', 'loginfennec' ),
			array(
				'none'  => __( 'Aucune', 'loginfennec' ),
				'fade'  => __( 'Fondu', 'loginfennec' ),
				'slide' => __( 'Glissement vers le haut', 'loginfennec' ),
				'zoom'  => __( 'Zoom', 'loginfennec' ),
			),
			__( 'Désactivée automatiquement si l’utilisateur demande moins d’animations.', 'loginfennec' )
		);

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Champs du formulaire', 'loginfennec' ) . '</h3>';
		self::field_text( $s, 'field_label_user', __( 'Libellé « Identifiant »', 'loginfennec' ), 'text', __( 'ex. : E-mail ou pseudo', 'loginfennec' ) );
		self::field_text( $s, 'field_label_pass', __( 'Libellé « Mot de passe »', 'loginfennec' ), 'text', __( 'ex. : Votre mot de passe', 'loginfennec' ) );
		self::field_text( $s, 'field_placeholder_user', __( 'Placeholder « Identifiant »', 'loginfennec' ), 'text', __( 'ex. : vous@exemple.com', 'loginfennec' ) );
		self::field_text( $s, 'field_placeholder_pass', __( 'Placeholder « Mot de passe »', 'loginfennec' ), 'text', __( 'ex. : ••••••••', 'loginfennec' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Après connexion', 'loginfennec' ) . '</h3>';
		self::field_text(
			$s,
			'login_redirect',
			__( 'Redirection après connexion', 'loginfennec' ),
			'url',
			'https://exemple.com/espace-client',
			__( 'Laisser vide pour le comportement WordPress habituel (tableau de bord ou page demandée).', 'loginfennec' )
		);

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Mises à jour & stabilité', 'loginfennec' ) . '</h3>';
		self::field_toggle( $s, 'auto_update', __( 'Mises à jour automatiques du plugin', 'loginfennec' ), __( 'Utilise le mécanisme natif de WordPress : les nouveautés s’installent seules, sans updater tiers.', 'loginfennec' ) );
		self::field_toggle( $s, 'safe_mode', __( 'Mode sans échec (dépannage)', 'loginfennec' ), __( 'Désactive d’un coup : personnalisation de la page de connexion, couleurs d’admin, blocage force brute, SMS, GEO et reCAPTCHA. À utiliser si un réglage rend le site instable — désactivez-le ensuite pour tout réactiver.', 'loginfennec' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Référencement (SEO)', 'loginfennec' ) . '</h3>';
		self::field_toggle( $s, 'seo_noindex', __( 'Empêcher l’indexation de la page de connexion', 'loginfennec' ), __( 'Ajoute noindex, nofollow : Google n’affiche jamais la page de connexion dans les résultats (recommandé).', 'loginfennec' ) );
		self::field_text( $s, 'seo_login_title', __( 'Titre de l’onglet', 'loginfennec' ), 'text', __( 'ex. : Espace client — {site}', 'loginfennec' ), __( 'Laisser vide pour le titre WordPress par défaut. Jeton : {site}.', 'loginfennec' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'White-label', 'loginfennec' ) . '</h3>';
		self::field_toggle( $s, 'white_label', __( 'Mode white-label', 'loginfennec' ), __( 'Masque « Back to… », le lien d’inscription et le logo de marque sur la page de connexion, ainsi que la signature du dashboard.', 'loginfennec' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'CSS personnalisé', 'loginfennec' ) . '</h3>';
		self::field_textarea(
			$s,
			'custom_css',
			__( 'CSS brut pour la page de connexion', 'loginfennec' ),
			__( 'Injecté après tous les réglages du plugin — les balises sont retirées automatiquement.', 'loginfennec' )
		);
		self::field_textarea(
			$s,
			'custom_js',
			__( 'JavaScript personnalisé', 'loginfennec' ),
			__( 'Injecté en pied de page de connexion (fermetures de balise neutralisées automatiquement).', 'loginfennec' )
		);

		self::panel_close();
	}

	/**
	 * Panneau : couleurs de l’administration WordPress.
	 */
	protected static function panel_admin( $s ) {
		self::panel_open( 'admin', __( 'Administration', 'loginfennec' ), __( 'Personnalisez les couleurs du menu latéral et de la barre d’admin de WordPress.', 'loginfennec' ) );

		echo '<div class="lnf-live-note"><span class="dashicons dashicons-visibility"></span> ' . esc_html__( 'Aperçu en temps réel : chaque couleur s’applique instantanément au menu ci-contre, avant même d’enregistrer. Cliquez sur « Enregistrer » pour la conserver.', 'loginfennec' ) . '</div>';

		self::field_toggle( $s, 'admin_enable', __( 'Activer la personnalisation', 'loginfennec' ), __( 'Remplace les couleurs natives de l’interface d’administration.', 'loginfennec' ) );

		echo '<div class="lnf-admin-presets" data-showif="' . esc_attr( wp_json_encode( array( 'admin_enable' => 1 ) ) ) . '">';
		echo '<span class="lnf-label">' . esc_html__( 'Palettes prêtes à l’emploi', 'loginfennec' ) . '</span>';
		echo '<div class="lnf-admin-preset-row">';
		foreach ( self::admin_presets() as $id => $preset ) {
			printf(
				'<button type="button" class="lnf-admin-preset" data-lnf-admin-preset="%1$s" title="%2$s"><span class="lnf-admin-preset-swatch" style="background:%3$s"></span><span class="lnf-admin-preset-swatch" style="background:%4$s"></span><span class="lnf-admin-preset-swatch" style="background:%5$s"></span>%6$s</button>',
				esc_attr( $id ),
				esc_attr( $preset['label'] ),
				esc_attr( $preset['admin_bg'] ),
				esc_attr( $preset['admin_active_bg'] ),
				esc_attr( $preset['admin_accent'] ),
				esc_html( $preset['label'] )
			);
		}
		echo '</div></div>';

		echo '<h3 class="lnf-group-title" data-showif="' . esc_attr( wp_json_encode( array( 'admin_enable' => 1 ) ) ) . '">' . esc_html__( 'Menu latéral', 'loginfennec' ) . '</h3>';
		self::field_color( $s, 'admin_bg', __( 'Fond du menu', 'loginfennec' ), '', array( 'admin_enable' => 1 ) );
		self::field_color( $s, 'admin_text', __( 'Texte et icônes', 'loginfennec' ), '', array( 'admin_enable' => 1 ) );
		self::field_color( $s, 'admin_hover_bg', __( 'Fond au survol', 'loginfennec' ), '', array( 'admin_enable' => 1 ) );
		self::field_color( $s, 'admin_hover_text', __( 'Texte au survol', 'loginfennec' ), '', array( 'admin_enable' => 1 ) );
		self::field_color( $s, 'admin_active_bg', __( 'Fond de la page active', 'loginfennec' ), '', array( 'admin_enable' => 1 ) );
		self::field_color( $s, 'admin_active_text', __( 'Texte de la page active', 'loginfennec' ), '', array( 'admin_enable' => 1 ) );

		echo '<h3 class="lnf-group-title" data-showif="' . esc_attr( wp_json_encode( array( 'admin_enable' => 1 ) ) ) . '">' . esc_html__( 'Accent et barre d’admin', 'loginfennec' ) . '</h3>';
		self::field_color( $s, 'admin_accent', __( 'Couleur d’accent (boutons, liens, cases à cocher)', 'loginfennec' ), '', array( 'admin_enable' => 1 ) );
		self::field_color( $s, 'adminbar_bg', __( 'Fond de la barre d’admin', 'loginfennec' ), '', array( 'admin_enable' => 1 ) );
		self::field_color( $s, 'adminbar_text', __( 'Texte de la barre d’admin', 'loginfennec' ), '', array( 'admin_enable' => 1 ) );
		self::field_color( $s, 'adminbar_hover', __( 'Survol de la barre d’admin', 'loginfennec' ), '', array( 'admin_enable' => 1 ) );

		self::panel_close();
	}

	/**
	 * Palettes prêtes à l’emploi pour l’admin.
	 *
	 * @return array
	 */
	protected static function admin_presets() {
		return array(
			'nuit'   => array(
				'label'           => __( 'Nuit fennec', 'loginfennec' ),
				'admin_bg'        => '#00305e',
				'admin_text'      => '#f9f0d8',
				'admin_hover_bg'  => '#0a427f',
				'admin_hover_text' => '#f2a444',
				'admin_active_bg' => '#0f5aa8',
				'admin_active_text' => '#ffffff',
				'admin_accent'    => '#0f5aa8',
				'adminbar_bg'     => '#00234a',
				'adminbar_text'   => '#f9f0d8',
				'adminbar_hover'  => '#f2a444',
			),
			'desert' => array(
				'label'           => __( 'Désert', 'loginfennec' ),
				'admin_bg'        => '#2f2417',
				'admin_text'      => '#f0e6d2',
				'admin_hover_bg'  => '#43321f',
				'admin_hover_text' => '#f5c26b',
				'admin_active_bg' => '#c2762b',
				'admin_active_text' => '#ffffff',
				'admin_accent'    => '#e08b3d',
				'adminbar_bg'     => '#241b10',
				'adminbar_text'   => '#f0e6d2',
				'adminbar_hover'  => '#f5c26b',
			),
			'ocean'  => array(
				'label'           => __( 'Océan', 'loginfennec' ),
				'admin_bg'        => '#0f2838',
				'admin_text'      => '#cfe6f5',
				'admin_hover_bg'  => '#16405a',
				'admin_hover_text' => '#6fc3ff',
				'admin_active_bg' => '#2271b1',
				'admin_active_text' => '#ffffff',
				'admin_accent'    => '#38a3e0',
				'adminbar_bg'     => '#0b1e2b',
				'adminbar_text'   => '#cfe6f5',
				'adminbar_hover'  => '#6fc3ff',
			),
			'clair'  => array(
				'label'           => __( 'Clair', 'loginfennec' ),
				'admin_bg'        => '#ffffff',
				'admin_text'      => '#2c3338',
				'admin_hover_bg'  => '#e8eaec',
				'admin_hover_text' => '#2271b1',
				'admin_active_bg' => '#2271b1',
				'admin_active_text' => '#ffffff',
				'admin_accent'    => '#2271b1',
				'adminbar_bg'     => '#1d2327',
				'adminbar_text'   => '#c3c4c7',
				'adminbar_hover'  => '#72aee6',
			),
		);
	}

	/**
	 * Panneau : connexion par SMS.
	 */
	protected static function panel_sms( $s ) {
		self::panel_open( 'sms', __( 'Connexion par SMS', 'loginfennec' ), __( 'Vos utilisateurs se connectent avec leur numéro de téléphone et un code à usage unique envoyé par SMS.', 'loginfennec' ) );

		self::field_toggle( $s, 'sms_enabled', __( 'Activer la connexion par SMS', 'loginfennec' ), __( 'Ajoute un lien « Se connecter par SMS » sous le formulaire de connexion. Chaque utilisateur doit renseigner son numéro dans son profil.', 'loginfennec' ) );

		echo '<h3 class="lnf-group-title" data-showif="' . esc_attr( wp_json_encode( array( 'sms_enabled' => 1 ) ) ) . '">' . esc_html__( 'Passerelle SMS', 'loginfennec' ) . '</h3>';
		self::field_select(
			$s,
			'sms_provider',
			__( 'Fournisseur', 'loginfennec' ),
			array(
				'twilio'  => 'Twilio',
				'vonage'  => 'Vonage (Nexmo)',
				'webhook' => __( 'Webhook HTTP (passerelle locale)', 'loginfennec' ),
			),
			__( 'Le webhook envoie {to, from, message} en JSON — compatible avec la plupart des passerelles algériennes exposant une API.', 'loginfennec' ),
			array( 'sms_enabled' => 1 )
		);
		self::field_text( $s, 'sms_from', __( 'Expéditeur', 'loginfennec' ), 'text', 'LoginFennec', __( 'Nom d’expéditeur validé par l’opérateur ou numéro d’envoi (Twilio et Vonage).', 'loginfennec' ), array( 'sms_enabled' => 1 ) );
		self::field_text( $s, 'sms_twilio_sid', __( 'Twilio — Account SID', 'loginfennec' ), 'text', 'AC…', '', array( 'sms_enabled' => 1, 'sms_provider' => 'twilio' ) );
		self::field_secret( $s, 'sms_twilio_token', __( 'Twilio — Auth Token', 'loginfennec' ), '', array( 'sms_enabled' => 1, 'sms_provider' => 'twilio' ) );
		self::field_text( $s, 'sms_vonage_key', __( 'Vonage — API Key', 'loginfennec' ), 'text', '', '', array( 'sms_enabled' => 1, 'sms_provider' => 'vonage' ) );
		self::field_secret( $s, 'sms_vonage_secret', __( 'Vonage — API Secret', 'loginfennec' ), '', array( 'sms_enabled' => 1, 'sms_provider' => 'vonage' ) );
		self::field_text( $s, 'sms_webhook_url', __( 'Webhook — URL d’envoi', 'loginfennec' ), 'url', 'https://sms.exemple.dz/api/send', __( 'Reçoit un POST JSON {to, from, message}.', 'loginfennec' ), array( 'sms_enabled' => 1, 'sms_provider' => 'webhook' ) );
		self::field_secret( $s, 'sms_webhook_token', __( 'Webhook — Jeton secret (optionnel)', 'loginfennec' ), __( 'Envoyé dans l’en-tête X-Lnf-Token pour authentifier la requête.', 'loginfennec' ), array( 'sms_enabled' => 1, 'sms_provider' => 'webhook' ) );

		echo '<h3 class="lnf-group-title" data-showif="' . esc_attr( wp_json_encode( array( 'sms_enabled' => 1 ) ) ) . '">' . esc_html__( 'Code à usage unique', 'loginfennec' ) . '</h3>';
		self::field_text( $s, 'sms_country', __( 'Indicatif pays', 'loginfennec' ), 'text', '213', __( 'Chiffres seulement, sans « + ». Les numéros saisis au format local sont convertis automatiquement.', 'loginfennec' ), array( 'sms_enabled' => 1 ) );
		self::field_range( $s, 'sms_otp_length', __( 'Longueur du code', 'loginfennec' ), 4, 8, __( ' chiffres', 'loginfennec' ), '', array( 'sms_enabled' => 1 ) );
		self::field_range( $s, 'sms_otp_ttl', __( 'Validité du code', 'loginfennec' ), 1, 15, ' min', '', array( 'sms_enabled' => 1 ) );
		self::field_range( $s, 'sms_max_attempts', __( 'Tentatives autorisées par code', 'loginfennec' ), 1, 10, '', __( 'Au-delà, le code est détruit et il faut en redemander un.', 'loginfennec' ), array( 'sms_enabled' => 1 ) );
		self::field_textarea( $s, 'sms_template', __( 'Modèle de message', 'loginfennec' ), __( 'Jetons disponibles : {code} et {minutes}.', 'loginfennec' ), array( 'sms_enabled' => 1 ) );

		echo '<h3 class="lnf-group-title" data-showif="' . esc_attr( wp_json_encode( array( 'sms_enabled' => 1 ) ) ) . '">' . esc_html__( 'Test d’envoi', 'loginfennec' ) . '</h3>';
		echo '<div class="lnf-field lnf-field-wide" data-showif="' . esc_attr( wp_json_encode( array( 'sms_enabled' => 1 ) ) ) . '">';
		echo '<label class="lnf-label" for="lnf-sms-test-number">' . esc_html__( 'Votre numéro', 'loginfennec' ) . '</label>';
		echo '<div style="display:flex;gap:8px;align-items:center">';
		echo '<input type="tel" class="lnf-input" id="lnf-sms-test-number" placeholder="05 XX XX XX XX" autocomplete="off">';
		echo '<button type="button" class="button lnf-sms-test">' . esc_html__( 'Envoyer un SMS de test', 'loginfennec' ) . '</button>';
		echo '</div><p class="lnf-sms-test-status lnf-desc" role="status" aria-live="polite"></p>';
		echo '</div>';

		self::panel_close();
	}

	/**
	 * Panneau : sécurité.
	 */
	protected static function panel_security( $s ) {
		self::panel_open( 'security', __( 'Sécurité de la connexion', 'loginfennec' ), __( 'Bloquez les attaques par force brute sur la page de connexion.', 'loginfennec' ) );

		self::field_toggle( $s, 'sec_enable', __( 'Limiter les tentatives de connexion', 'loginfennec' ), __( 'Après N échecs, l’adresse IP et l’identifiant sont bloqués temporairement.', 'loginfennec' ) );
		self::field_range( $s, 'sec_max_attempts', __( 'Tentatives autorisées', 'loginfennec' ), 1, 20, '', '', array( 'sec_enable' => 1 ) );
		self::field_range( $s, 'sec_lockout_minutes', __( 'Durée du blocage', 'loginfennec' ), 1, 1440, 'min', __( 'La durée double automatiquement à chaque récidive (jusqu’à ×8), pour user la patience des bots.', 'loginfennec' ), array( 'sec_enable' => 1 ) );
		self::field_text( $s, 'sec_lock_message', __( 'Message de blocage', 'loginfennec' ), 'text', '', sprintf( __( 'Utilisez %%d pour la durée restante en minutes.', 'loginfennec' ) ), array( 'sec_enable' => 1 ) );
		self::field_toggle( $s, 'sec_generic_error', __( 'Masquer le détail des erreurs', 'loginfennec' ), __( 'Affiche un message générique au lieu de « mot de passe incorrect ».', 'loginfennec' ) );
		self::field_toggle( $s, 'sec_hide_language_switcher', __( 'Masquer le sélecteur de langue', 'loginfennec' ) );
		self::field_toggle( $s, 'sec_disable_xmlrpc', __( 'Désactiver XML-RPC', 'loginfennec' ), __( 'Coupe une porte d’entrée classique des attaques par force brute (recommandé si vous n’utilisez pas l’appli mobile WordPress).', 'loginfennec' ) );
		self::field_toggle( $s, 'sec_honeypot', __( 'Honeypot anti-robots', 'loginfennec' ), __( 'Ajoute un champ caché que seuls les robots remplissent — la connexion est alors refusée et notée dans le journal.', 'loginfennec' ) );
		self::field_toggle( $s, 'sec_disable_authors', __( 'Bloquer le balayage des auteurs', 'loginfennec' ), __( 'Masque les identifiants : « ?author=N » est redirigé vers l’accueil et l’endpoint REST des utilisateurs est fermé aux visiteurs.', 'loginfennec' ) );
		self::field_toggle( $s, 'sec_disable_app_passwords', __( 'Désactiver les mots de passe d’application', 'loginfennec' ), __( 'Coupe l’accès des applications externes (appli mobile, éditeurs) — durcissement recommandé si vous ne les utilisez pas.', 'loginfennec' ) );

		echo '<h3 class="lnf-group-title">' . esc_html__( 'Restriction par pays (GEO)', 'loginfennec' ) . '</h3>';
		self::field_toggle( $s, 'geo_enable', __( 'Limiter la connexion à certains pays', 'loginfennec' ), __( 'Désactivé par défaut. Activée, cette option envoie l’adresse IP du visiteur au service gratuit geojs.io (HTTPS, sans clé) pour déterminer le pays ; le résultat est mis en cache 24 h. Mentionnez-le dans votre politique de confidentialité.', 'loginfennec' ) );
		self::field_text( $s, 'geo_countries', __( 'Pays autorisés (codes ISO)', 'loginfennec' ), 'text', 'DZ, FR', __( 'Codes à 2 lettres séparés par des virgules — ex. DZ pour l’Algérie. Vide = tous les pays. En cas d’échec de détection, l’accès reste autorisé pour ne jamais vous enfermer dehors.', 'loginfennec' ), array( 'geo_enable' => 1 ) );
		self::field_toggle( $s, 'recaptcha_enabled', __( 'Activer reCAPTCHA v3', 'loginfennec' ), __( 'Protection invisible anti-bot sur la page de connexion. Nécessite des clés reCAPTCHA de Google.', 'loginfennec' ) );
		self::field_text( $s, 'recaptcha_site_key', __( 'Clé Site reCAPTCHA', 'loginfennec' ), 'text', '6Lc…', '', array( 'recaptcha_enabled' => 1 ) );
		self::field_secret( $s, 'recaptcha_secret_key', __( 'Clé Secrète reCAPTCHA', 'loginfennec' ), '', array( 'recaptcha_enabled' => 1 ) );
		self::field_text(
			$s,
			'sec_alert_email',
			__( 'Alerte e-mail en cas de blocage', 'loginfennec' ) . ( lnf_is_pro() ? '' : ' — 🔒 Pro' ),
			'email',
			'vous@exemple.com',
			lnf_is_pro()
				? __( 'Un e-mail vous est envoyé à chaque nouvelle IP bloquée.', 'loginfennec' )
				: __( 'Réservé Pro — activez votre licence (page Passer en Pro) pour recevoir les alertes.', 'loginfennec' )
		);
		self::field_textarea(
			$s,
			'sec_whitelist',
			__( 'Liste blanche d’IP (jamais verrouillées)', 'loginfennec' ),
			__( 'Une IP par ligne ou séparées par des virgules. Le joker * est accepté (ex. 192.168.1.*). Vos IP de confiance ne seront jamais bloquées — utile pour éviter de vous verrouiller vous-même.', 'loginfennec' )
		);

		// ——— Journal de sécurité ———.
		$log = Lnf_Login_Security::get_log();
		echo '<div class="lnf-journal">';
		echo '<h3 class="lnf-group-title">' . esc_html__( 'Journal de sécurité', 'loginfennec' ) . '</h3>';
		if ( $log ) {
			$badges = array(
				'failed'  => array( __( 'Échec', 'loginfennec' ), 'is-fail' ),
				'blocked' => array( __( 'Bloqué', 'loginfennec' ), 'is-blocked' ),
				'login'   => array( __( 'Connexion', 'loginfennec' ), 'is-login' ),
				'sms'     => array( __( 'SMS', 'loginfennec' ), 'is-login' ),
			);
			echo '<table class="lnf-journal-table"><thead><tr><th>' . esc_html__( 'Date', 'loginfennec' ) . '</th><th>IP</th><th>' . esc_html__( 'Identifiant', 'loginfennec' ) . '</th><th>' . esc_html__( 'Action', 'loginfennec' ) . '</th></tr></thead><tbody>';
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
						__( '%d événements enregistrés (50 maximum).', 'loginfennec' ),
						count( $log )
					)
				)
			);
			echo '<button type="button" class="button lnf-purge-log">' . esc_html__( 'Vider le journal', 'loginfennec' ) . '</button> ';
			echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=lnf_export_log' ), 'lnf_export_log' ) ) . '">' . esc_html__( 'Exporter (CSV)', 'loginfennec' ) . '</a> <span class="lnf-purge-status"></span>';
		} else {
			echo '<p class="lnf-desc">' . esc_html__( 'Aucun événement enregistré pour le moment.', 'loginfennec' ) . '</p>';
		}
		echo '</div>';

		echo '<div class="lnf-pro-teaser">';
		echo '<div class="lnf-pro-teaser-text"><h4>' . esc_html__( 'Niveaux de sécurité avancés — LoginFennec Pro', 'loginfennec' ) . '</h4><p>'
			. esc_html__( 'URL de connexion personnalisée, verrouillage dédié de /wp-admin, journal d’audit étendu et support prioritaire. (La 2FA et reCAPTCHA v3 sont inclus gratuitement.)', 'loginfennec' )
			. '</p></div>';
		echo '<a class="lnf-btn lnf-btn-pro" href="' . esc_url( admin_url( 'admin.php?page=loginfennec-pro' ) ) . '">' . esc_html__( 'Passer en Pro', 'loginfennec' ) . '</a>';
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
		return apply_filters( 'loginfennec_donate_url', 'https://www.paypal.com/donate' );
	}

	/**
	 * Écran de verrouillage : essai expiré sans licence Pro.
	 */
	public static function render_trial_expired() {
		$trial = lnf_trial_status();
		?>
		<div class="wrap lnf-wrap lnf-trial-locked">
			<div class="lnf-trial-overlay">
				<div class="lnf-trial-icon" aria-hidden="true">🔒</div>
				<h1><?php esc_html_e( 'Votre essai de 7 jours est terminé', 'loginfennec' ); ?></h1>
				<p class="lnf-trial-sub"><?php esc_html_e( 'Votre page de connexion est revenue au design WordPress par défaut et les fonctionnalités de sécurité sont désactivées.', 'loginfennec' ); ?></p>
				<p class="lnf-trial-sub"><?php esc_html_e( 'Activez une licence LoginFennec Pro pour retrouver votre personnalisation, votre journal de sécurité et toutes les protections anti force brute.', 'loginfennec' ); ?></p>
				<div class="lnf-trial-buttons">
					<a class="lnf-btn lnf-btn-pro" href="<?php echo esc_url( admin_url( 'admin.php?page=loginfennec-pro' ) ); ?>">
						<span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'Acheter une licence Pro', 'loginfennec' ); ?>
					</a>
					<a class="lnf-btn lnf-btn-ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=loginfennec-pro' ) ); ?>">
						<?php esc_html_e( 'J’ai déjà une clé de licence', 'loginfennec' ); ?>
					</a>
				</div>
				<p class="lnf-trial-sig">∞ <?php esc_html_e( 'Création de Derouiche Oussama —', 'loginfennec' ); ?> <a href="https://www.derouicheoussama.com" target="_blank" rel="noopener">derouicheoussama.com</a></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Affiche la page « À propos ».
	 */
	public static function render_about() {
		?>
		<div class="wrap lnf-wrap lnf-about">
			<div class="lnf-hero">
				<img class="lnf-hero-logo" src="<?php echo esc_url( LOGINFENNEC_URL . "assets/img/logo-fennec.png" ); ?>" alt="LoginFennec" />
				<h1><?php esc_html_e( 'LoginFennec Pro', 'loginfennec' ); ?></h1>
				<p>
					<?php esc_html_e( 'La page de connexion de WordPress, enfin à votre image.', 'loginfennec' ); ?><br>
					<span class="lnf-version">v<?php echo esc_html( LOGINFENNEC_VERSION ); ?></span>
				</p>
				<div class="lnf-hero-actions">
						<a class="lnf-btn lnf-btn-donate" href="<?php echo esc_url( self::donate_url() ); ?>" target="_blank" rel="noopener">
							<span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'Faire un don', 'loginfennec' ); ?>
						</a>
						<a class="lnf-btn lnf-btn-ghost is-light" href="https://github.com/derouicheoussama/loginfennec" target="_blank" rel="noopener">
							<span class="dashicons dashicons-github"></span> <?php esc_html_e( 'Voir sur GitHub', 'loginfennec' ); ?>
						</a>
					</div>
					<div class="lnf-quality" aria-label="<?php esc_attr_e( 'Engagements qualité', 'loginfennec' ); ?>">
						<span>✔ <?php esc_html_e( 'Sans publicité', 'loginfennec' ); ?></span>
						<span>✔ <?php esc_html_e( 'Aucune donnée collectée', 'loginfennec' ); ?></span>
						<span>✔ <?php esc_html_e( 'Compatible multisite', 'loginfennec' ); ?></span>
						<span>✔ <?php esc_html_e( 'Prêt pour la traduction', 'loginfennec' ); ?></span>
						<span>✔ <?php esc_html_e( 'Licence GPL v2+', 'loginfennec' ); ?></span>
					</div>
				</div>

			<div class="lnf-about-grid">
				<section class="lnf-about-card">
					<h2><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'Développeur', 'loginfennec' ); ?></h2>
					<p class="lnf-about-dev"><strong><?php esc_html_e( 'Derouiche Oussama', 'loginfennec' ); ?></strong></p>
					<p><?php esc_html_e( 'Créateur du plugin, passionné par WordPress et les interfaces modernes.', 'loginfennec' ); ?></p>
					<div class="lnf-dev-social" aria-label="<?php esc_attr_e( 'Réseaux du développeur', 'loginfennec' ); ?>">
						<?php foreach ( self::dev_socials() as $network ) : ?>
							<a class="lnf-dev-icon" href="<?php echo esc_url( $network['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $network['label'] ); ?>" title="<?php echo esc_attr( $network['label'] ); ?>" style="<?php echo esc_attr( ! empty( $network['color'] ) ? 'color:' . $network['color'] : '' ); ?>">
								<?php if ( ! empty( $network['svg'] ) ) : ?>
									<?php echo wp_kses( $network['svg'], array('svg' => array('viewbox' => array(),'width' => array(),'height' => array(),'fill' => array(),'aria-hidden' => array()),'path' => array('d' => array(),'fill' => array())) ); // SVG de marque (simple-icons), statique et sûr. ?>
								<?php else : ?>
									<span class="dashicons <?php echo esc_attr( $network['icon'] ); ?>"></span>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					</div>
					<p class="lnf-about-links">
						<a href="https://www.derouicheoussama.com" target="_blank" rel="noopener"><?php esc_html_e( 'Site web', 'loginfennec' ); ?></a> ·
						<a href="https://github.com/derouicheoussama" target="_blank" rel="noopener">GitHub</a> ·
						<a href="https://profiles.wordpress.org/derouicheoussama/" target="_blank" rel="noopener">WordPress.org</a>
					</p>
				</section>

				<section class="lnf-about-card">
					<h2><span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Fonctionnalités', 'loginfennec' ); ?></h2>
					<ul class="lnf-about-list">
						<li><?php esc_html_e( 'Logo personnalisé + lien du logo', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( 'Arrière-plan : couleur, dégradé ou image', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( 'Contrôles de flou et d’opacité', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( '10 styles modernes (effet verre, sombre…)', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( 'Liens : personnalisation ou masquage', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( 'Icônes de réseaux sociaux', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( 'Mention de copyright', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( 'Blocage des tentatives de mot de passe', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( 'Honeypot anti-robots et anti-énumération des auteurs', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( 'Journal de sécurité des 50 derniers événements', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( 'Export / import des réglages (JSON)', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( 'Achat intégré et activation de licence Pro', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( 'Aperçu en direct 100 % responsive', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( 'Mises à jour automatiques via GitHub', 'loginfennec' ); ?></li>
					</ul>
				</section>

				<section class="lnf-about-card">
					<h2><span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Liens rapides', 'loginfennec' ); ?></h2>
					<ul class="lnf-about-links-list">
						<li>
							<a href="https://www.derouicheoussama.com" target="_blank" rel="noopener"><?php esc_html_e( 'Site web', 'loginfennec' ); ?></a>
							— <?php esc_html_e( 'tutoriels et actualités', 'loginfennec' ); ?>
						</li>
						<li>
							<a href="https://github.com/derouicheoussama/loginfennec#readme" target="_blank" rel="noopener"><?php esc_html_e( 'Documentation', 'loginfennec' ); ?></a>
							— <?php esc_html_e( 'guide complet sur GitHub', 'loginfennec' ); ?>
						</li>
						<li>
							<a href="https://github.com/derouicheoussama/loginfennec/issues" target="_blank" rel="noopener"><?php esc_html_e( 'Signaler un bug', 'loginfennec' ); ?></a>
							— <?php esc_html_e( 'ouverture d’un ticket en un clic', 'loginfennec' ); ?>
						</li>
						<li>
							<a href="https://wordpress.org/support/plugin/loginfennec/" target="_blank" rel="noopener"><?php esc_html_e( 'Forum d’entraide', 'loginfennec' ); ?></a>
							— <?php esc_html_e( 'poser une question', 'loginfennec' ); ?>
						</li>
						<li>
							<a href="https://wordpress.org/plugins/loginfennec/reviews/#new-post" target="_blank" rel="noopener"><?php esc_html_e( 'Laisser un avis ★', 'loginfennec' ); ?></a>
							— <?php esc_html_e( 'soutenir le projet', 'loginfennec' ); ?>
						</li>
					</ul>
				</section>

				<section class="lnf-about-card">
					<h2><span class="dashicons dashicons-lock"></span> <?php esc_html_e( 'Protection & DMCA', 'loginfennec' ); ?></h2>
					<p class="lnf-desc">
						<?php
						printf(
							/* translators: %s : année courante. */
							esc_html__( 'LoginFennec Pro est une œuvre originale protégée par le droit d’auteur — © %s Derouiche Oussama. La signature « ∞ Infinity Coder » présente dans tous les fichiers doit être conservée.', 'loginfennec' ),
							esc_html( gmdate( 'Y' ) )
						);
						?>
					</p>
					<ul class="lnf-about-links-list">
						<li>
							<strong><?php esc_html_e( 'Version gratuite', 'loginfennec' ); ?></strong>
							— <?php esc_html_e( 'libre d’utilisation sous licence GPL, avec signature intacte.', 'loginfennec' ); ?>
						</li>
						<li>
							<strong><?php esc_html_e( 'Version Pro', 'loginfennec' ); ?></strong>
							— <?php esc_html_e( 'soumise à licence : une clé invalide ou révoquée suspend les fonctionnalités Pro à distance.', 'loginfennec' ); ?>
						</li>
						<li>
							<strong><?php esc_html_e( 'Copies illégales', 'loginfennec' ); ?></strong>
							— <?php esc_html_e( 'revente, republication ou retrait de la signature : signalement DMCA immédiat à l’hébergeur (retrait sous 24-72 h).', 'loginfennec' ); ?>
						</li>
					</ul>
					<?php $badge = lnf_dmca_badge(); ?>
					<?php if ( '' !== $badge ) : ?>
						<p>
							<a href="<?php echo esc_url( lnf_dmca_url() ); ?>" target="_blank" rel="noopener noreferrer nofollow">
								<img src="<?php echo esc_url( $badge ); ?>" alt="<?php esc_attr_e( 'Protégé par DMCA.com', 'loginfennec' ); ?>" loading="lazy">
							</a>
						</p>
					<?php endif; ?>
					<div class="lnf-hero-actions">
						<a class="lnf-btn lnf-btn-ghost" href="<?php echo esc_url( lnf_dmca_url() ); ?>" target="_blank" rel="noopener noreferrer nofollow">
							<span class="dashicons dashicons-flag"></span> <?php esc_html_e( 'Signaler une violation', 'loginfennec' ); ?>
						</a>
					</div>
				</section>

				<section class="lnf-about-card">
					<h2><span class="dashicons dashicons-cloud"></span> <?php esc_html_e( 'Mises à jour', 'loginfennec' ); ?></h2>
					<p><?php esc_html_e( 'Les mises à jour sont servies par le répertoire officiel WordPress.org : l’extension apparaît dans Extensions → Mises à jour comme n’importe quel plugin natif. Rien à configurer.', 'loginfennec' ); ?></p>
					<div class="lnf-about-actions">
						<button type="button" class="lnf-btn lnf-btn-ghost lnf-check-updates"><span class="dashicons dashicons-update-alt"></span> <?php esc_html_e( 'Vérifier les mises à jour', 'loginfennec' ); ?></button>
						<span class="lnf-update-status" aria-live="polite"></span>
					</div>
				</section>

				<section class="lnf-about-card lnf-about-review">
					<h2><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Vous aimez LoginFennec Pro ?', 'loginfennec' ); ?></h2>
					<p class="lnf-about-stars" aria-hidden="true">★★★★★</p>
					<p><?php esc_html_e( 'Votre note sur WordPress.org aide le plugin à être découvert par des milliers d’utilisateurs — et nous motive à livrer toujours plus. Ça ne prend qu’une minute.', 'loginfennec' ); ?></p>
					<div class="lnf-hero-actions">
						<a class="lnf-btn lnf-btn-primary" href="https://wordpress.org/plugins/loginfennec/reviews/#new-post" target="_blank" rel="noopener noreferrer">
							<span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Laisser un avis ★★★★★', 'loginfennec' ); ?>
						</a>
					</div>
				</section>

				<section class="lnf-about-card">
					<h2><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e( 'État du système', 'loginfennec' ); ?></h2>
					<ul class="lnf-about-status">
						<li><strong><?php esc_html_e( 'Version du plugin', 'loginfennec' ); ?></strong> <?php echo esc_html( LOGINFENNEC_VERSION ); ?></li>
						<li><strong><?php esc_html_e( 'Version de WordPress', 'loginfennec' ); ?></strong> <?php echo esc_html( get_bloginfo( 'version' ) ); ?></li>
						<li><strong><?php esc_html_e( 'Version de PHP', 'loginfennec' ); ?></strong> <?php echo esc_html( PHP_VERSION ); ?></li>
						<li><strong><?php esc_html_e( 'Site', 'loginfennec' ); ?></strong> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></li>
						</ul>
					</section>

					<section class="lnf-about-card">
						<h2><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Notes de version', 'loginfennec' ); ?></h2>
						<?php $changelog = self::latest_changelog(); ?>
						<?php if ( $changelog ) : ?>
							<span class="lnf-changelog-version">v<?php echo esc_html( $changelog['version'] ); ?></span>
							<ul class="lnf-changelog">
								<?php foreach ( $changelog['items'] as $line ) : ?>
									<li><?php echo esc_html( $line ); ?></li>
								<?php endforeach; ?>
							</ul>
							<p class="lnf-desc">
								<a href="https://github.com/derouicheoussama/loginfennec/releases" target="_blank" rel="noopener"><?php esc_html_e( 'Historique complet sur GitHub', 'loginfennec' ); ?></a>
							</p>
						<?php else : ?>
							<p class="lnf-desc"><?php esc_html_e( 'Historique disponible sur GitHub.', 'loginfennec' ); ?></p>
						<?php endif; ?>
					</section>
				</div>

			<section class="lnf-about-card lnf-about-support">
				<h2><span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'Soutenir le projet', 'loginfennec' ); ?></h2>
				<p><?php esc_html_e( 'LoginFennec Pro est développé sur le temps personnel. Si ce plugin vous est utile, un petit don aide à le maintenir, à l’améliorer et à le garder gratuit.', 'loginfennec' ); ?></p>
				<div class="lnf-hero-actions">
					<a class="lnf-btn lnf-btn-donate" href="<?php echo esc_url( self::donate_url() ); ?>" target="_blank" rel="noopener">
						<span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'Faire un don via PayPal', 'loginfennec' ); ?>
					</a>
					<a class="lnf-btn lnf-btn-ghost" href="https://wordpress.org/support/plugin/loginfennec/" target="_blank" rel="noopener">
						<?php esc_html_e( 'Forum d’entraide', 'loginfennec' ); ?>
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
			'loginfennec_dev_socials',
			array(
				array(
					'label' => __( 'Site web', 'loginfennec' ),
					'icon'  => 'dashicons-admin-links',
					'url'   => 'https://www.derouicheoussama.com',
				),
				array(
					'label' => 'GitHub',
					'color' => '#24292F',
					'url'   => 'https://github.com/derouicheoussama',
					'svg'   => $icon( 'M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12' ),
				),
				array(
					'label' => 'WordPress.org',
					'color' => '#21759B',
					'icon'  => 'dashicons-wordpress',
					'url'   => 'https://profiles.wordpress.org/derouicheoussama/',
				),
				array(
					'label' => 'Facebook',
					'color' => '#1877F2',
					'url'   => 'https://www.facebook.com/derouiche.oussama',
					'svg'   => $icon( 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' ),
				),
				array(
					'label' => 'Instagram',
					'color' => '#D6249F',
					'url'   => 'https://www.instagram.com/derouiche.oussama/',
					'svg'   => $icon( 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z' ),
				),
				array(
					'label' => 'TikTok',
					'color' => '#010101',
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
		return apply_filters( 'loginfennec_pro_url', '#' );
	}

	/**
	 * Extrait la dernière section du changelog depuis readme.txt.
	 *
	 * @return array { version: string, items: string[] } — vide si indisponible.
	 */
	protected static function latest_changelog() {
		$file = LOGINFENNEC_DIR . 'readme.txt';
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
				<img class="lnf-hero-logo" src="<?php echo esc_url( LOGINFENNEC_URL . "assets/img/logo-fennec.png" ); ?>" alt="LoginFennec" />
				<span class="lnf-version"><?php esc_html_e( 'Configuration guidée', 'loginfennec' ); ?></span>
				<h1><?php esc_html_e( 'Bienvenue dans LoginFennec Pro', 'loginfennec' ); ?></h1>
				<p><?php esc_html_e( 'Transformez votre page de connexion en 3 étapes : choisissez un style et un thème d’interface, activez la protection anti force brute, et c’est parti. Tout reste modifiable ensuite.', 'loginfennec' ); ?></p>
				<ol class="lnf-inst-steps" aria-hidden="true">
					<li class="is-active" data-step-dot="1"><?php esc_html_e( 'Bienvenue', 'loginfennec' ); ?></li>
					<li data-step-dot="2"><?php esc_html_e( 'Style & thème', 'loginfennec' ); ?></li>
					<li data-step-dot="3"><?php esc_html_e( 'Sécurité', 'loginfennec' ); ?></li>
					<li data-step-dot="4"><?php esc_html_e( 'Terminé', 'loginfennec' ); ?></li>
				</ol>
				<a class="lnf-inst-skip" href="<?php echo esc_url( admin_url( 'admin.php?page=loginfennec' ) ); ?>"><?php esc_html_e( 'Passer l’assistant et aller au dashboard →', 'loginfennec' ); ?></a>
			</header>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lnf_wizard">
				<input type="hidden" name="lnf[preset]" value="<?php echo esc_attr( $s['preset'] ); ?>">
				<input type="hidden" name="lnf[form_theme]" value="<?php echo esc_attr( $s['form_theme'] ); ?>">
				<?php wp_nonce_field( 'lnf_wizard', 'lnf_wizard_nonce' ); ?>

				<section class="lnf-wstep is-active" data-step="1">
					<h2><?php esc_html_e( 'Ce que vous allez obtenir', 'loginfennec' ); ?></h2>
					<p class="lnf-inst-desc"><?php esc_html_e( 'Un aperçu de tout ce que couvre LoginFennec Pro.', 'loginfennec' ); ?></p>
					<div class="lnf-inst-grid">
						<div class="lnf-inst-feature"><span class="dashicons dashicons-format-image"></span><h3><?php esc_html_e( 'Logo & arrière-plan', 'loginfennec' ); ?></h3><p><?php esc_html_e( 'Logo image ou texte, image de fond avec flou, luminosité et voile coloré réglables.', 'loginfennec' ); ?></p></div>
						<div class="lnf-inst-feature"><span class="dashicons dashicons-art"></span><h3><?php esc_html_e( '10 styles & 7 thèmes', 'loginfennec' ); ?></h3><p><?php esc_html_e( 'Effet verre, sombre, néon, sakura… combinés à 7 designs de formulaire.', 'loginfennec' ); ?></p></div>
						<div class="lnf-inst-feature"><span class="dashicons dashicons-shield-alt"></span><h3><?php esc_html_e( 'Anti force brute', 'loginfennec' ); ?></h3><p><?php esc_html_e( 'Honeypot anti-robots, blocage IP + identifiant et liste blanche de confiance.', 'loginfennec' ); ?></p></div>
						<div class="lnf-inst-feature"><span class="dashicons dashicons-share"></span><h3><?php esc_html_e( 'Social & copyright', 'loginfennec' ); ?></h3><p><?php esc_html_e( 'Icônes aux couleurs officielles des marques et mention de copyright.', 'loginfennec' ); ?></p></div>
						<div class="lnf-inst-feature"><span class="dashicons dashicons-desktop"></span><h3><?php esc_html_e( 'Aperçu en direct', 'loginfennec' ); ?></h3><p><?php esc_html_e( 'Bureau, tablette et mobile — chaque changement se voit instantanément.', 'loginfennec' ); ?></p></div>
						<div class="lnf-inst-feature"><span class="dashicons dashicons-chart-bar"></span><h3><?php esc_html_e( 'Statistiques', 'loginfennec' ); ?></h3><p><?php esc_html_e( 'Score de sécurité et journal des 50 derniers événements de connexion.', 'loginfennec' ); ?></p></div>
						<div class="lnf-inst-feature"><span class="dashicons dashicons-smartphone"></span><h3><?php esc_html_e( 'Connexion par SMS', 'loginfennec' ); ?></h3><p><?php esc_html_e( 'Numéro de téléphone + code à usage unique : Twilio, Vonage ou votre passerelle locale.', 'loginfennec' ); ?></p></div>
						<div class="lnf-inst-feature"><span class="dashicons dashicons-location"></span><h3><?php esc_html_e( 'GEO & SEO', 'loginfennec' ); ?></h3><p><?php esc_html_e( 'Autorisez la connexion depuis vos pays uniquement et gardez la page hors de Google (noindex).', 'loginfennec' ); ?></p></div>
					</div>
					<p class="lnf-inst-note">∞ <?php esc_html_e( 'Création de Derouiche Oussama — sans publicité, sans collecte de données.', 'loginfennec' ); ?></p>
				</section>

				<section class="lnf-wstep" data-step="2">
					<h2><?php esc_html_e( 'Choisissez votre style', 'loginfennec' ); ?></h2>
					<p class="lnf-inst-desc"><?php esc_html_e( 'Un style définit les couleurs d’ambiance. Vous pourrez tout affiner plus tard (flou, opacité, typographie…).', 'loginfennec' ); ?></p>
					<div class="lnf-presets">
						<?php
						$presets = array(
							'glass'   => array( __( 'Effet verre', 'loginfennec' ), 'linear-gradient(135deg,#667eea,#764ba2)' ),
							'minimal' => array( __( 'Minimal', 'loginfennec' ), 'linear-gradient(135deg,#f5f6f8,#dfe3ea)' ),
							'dark'    => array( __( 'Sombre', 'loginfennec' ), 'linear-gradient(160deg,#0f172a,#334155)' ),
							'sunset'  => array( __( 'Coucher de soleil', 'loginfennec' ), 'linear-gradient(120deg,#f97316,#ec4899)' ),
							'ocean'   => array( __( 'Océan', 'loginfennec' ), 'linear-gradient(135deg,#0ea5e9,#2563eb)' ),
							'forest'  => array( __( 'Forêt', 'loginfennec' ), 'linear-gradient(135deg,#059669,#065f46)' ),
							'neon'    => array( __( 'Néon', 'loginfennec' ), 'linear-gradient(135deg,#0f0c29,#302b63)' ),
							'sakura'  => array( __( 'Sakura', 'loginfennec' ), 'linear-gradient(120deg,#ee9ca7,#ffdde1)' ),
							'mono'    => array( __( 'Monochrome', 'loginfennec' ), 'linear-gradient(160deg,#9ca3af,#374151)' ),
							'royal'   => array( __( 'Royal', 'loginfennec' ), 'linear-gradient(150deg,#141e30,#243b55)' ),
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

					<h2 class="lnf-inst-subtitle"><?php esc_html_e( 'Et le design du formulaire', 'loginfennec' ); ?></h2>
					<p class="lnf-inst-desc"><?php esc_html_e( 'La silhouette du formulaire, indépendante des couleurs.', 'loginfennec' ); ?></p>
					<div class="lnf-presets lnf-themes">
						<?php
						$themes = array(
							'glass'    => array( __( 'Effet verre', 'loginfennec' ), 'background:linear-gradient(135deg,#667eea,#764ba2);box-shadow:inset 22px 22px 0 -8px rgba(255,255,255,.4);border-radius:8px;' ),
							'classic'  => array( __( 'Classique', 'loginfennec' ), 'background:#fff;border:1px solid #d5d3e8;border-radius:6px;' ),
							'outline'  => array( __( 'Contour', 'loginfennec' ), 'background:transparent;border:2px solid #0f5aa8;border-radius:8px;' ),
							'pill'     => array( __( 'Pillule', 'loginfennec' ), 'background:#fff;border-radius:999px;' ),
							'elevated' => array( __( 'Surélevé', 'loginfennec' ), 'background:#fff;border-radius:12px;box-shadow:0 12px 20px -8px rgba(0,0,0,.5);' ),
							'accent'   => array( __( 'Accent', 'loginfennec' ), 'background:#fff;border-top:6px solid #0f5aa8;border-radius:8px;' ),
							'minimal'  => array( __( 'Minimal', 'loginfennec' ), 'background:transparent;border-bottom:5px solid #0f5aa8;border-radius:0;' ),
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
					<h2><?php esc_html_e( 'Protégez votre page de connexion', 'loginfennec' ); ?></h2>
					<p class="lnf-inst-desc"><?php esc_html_e( 'Recommandé : activez toutes les protections dès maintenant — vous pourrez les ajuster dans l’onglet Sécurité.', 'loginfennec' ); ?></p>

					<div class="lnf-inst-security">
						<label class="lnf-switch"><input type="hidden" name="lnf[sec_enable]" value="0"><input type="checkbox" name="lnf[sec_enable]" value="1" <?php checked( ! empty( $s['sec_enable'] ) ); ?>><span class="lnf-switch-ui"></span></label>
						<div>
							<strong><?php esc_html_e( 'Limiter les tentatives de connexion', 'loginfennec' ); ?></strong>
							<p class="lnf-inst-desc"><?php esc_html_e( 'Après N échecs, l’adresse IP et l’identifiant sont bloqués temporairement.', 'loginfennec' ); ?></p>
						</div>
						<div class="lnf-inst-attempts">
							<label for="lnf-wizard-attempts"><?php esc_html_e( 'Tentatives autorisées', 'loginfennec' ); ?></label>
							<input type="number" id="lnf-wizard-attempts" class="lnf-input" name="lnf[sec_max_attempts]" min="1" max="20" value="<?php echo esc_attr( $s['sec_max_attempts'] ); ?>">
							<label for="lnf-wizard-lockout"><?php esc_html_e( 'Blocage (minutes)', 'loginfennec' ); ?></label>
							<input type="number" id="lnf-wizard-lockout" class="lnf-input" name="lnf[sec_lockout_minutes]" min="1" max="1440" value="<?php echo esc_attr( $s['sec_lockout_minutes'] ); ?>">
						</div>
					</div>

					<div class="lnf-inst-checks">
						<div class="lnf-inst-check"><label class="lnf-switch"><input type="hidden" name="lnf[sec_honeypot]" value="0"><input type="checkbox" name="lnf[sec_honeypot]" value="1" <?php checked( ! empty( $s['sec_honeypot'] ) ); ?>><span class="lnf-switch-ui"></span></label><div><strong><?php esc_html_e( 'Honeypot anti-robots', 'loginfennec' ); ?></strong><p class="lnf-inst-desc"><?php esc_html_e( 'Un champ caché piège les bots avant même la connexion.', 'loginfennec' ); ?></p></div></div>
						<div class="lnf-inst-check"><label class="lnf-switch"><input type="hidden" name="lnf[sec_disable_authors]" value="0"><input type="checkbox" name="lnf[sec_disable_authors]" value="1" <?php checked( ! empty( $s['sec_disable_authors'] ) ); ?>><span class="lnf-switch-ui"></span></label><div><strong><?php esc_html_e( 'Anti-énumération des auteurs', 'loginfennec' ); ?></strong><p class="lnf-inst-desc"><?php esc_html_e( 'Vos identifiants restent invisibles aux scanners.', 'loginfennec' ); ?></p></div></div>
						<div class="lnf-inst-check"><label class="lnf-switch"><input type="hidden" name="lnf[sec_disable_xmlrpc]" value="0"><input type="checkbox" name="lnf[sec_disable_xmlrpc]" value="1" <?php checked( ! empty( $s['sec_disable_xmlrpc'] ) ); ?>><span class="lnf-switch-ui"></span></label><div><strong><?php esc_html_e( 'Désactiver XML-RPC', 'loginfennec' ); ?></strong><p class="lnf-inst-desc"><?php esc_html_e( 'Ferme une porte d’entrée classique des attaques.', 'loginfennec' ); ?></p></div></div>
					</div>
					<p class="lnf-inst-desc"><strong><?php esc_html_e( 'Votre IP actuelle', 'loginfennec' ); ?></strong> : <code><?php echo esc_html( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' ); ?></code> — <?php esc_html_e( 'ajoutez-la à la liste blanche (onglet Sécurité) pour ne jamais être verrouillé lors de vos tests.', 'loginfennec' ); ?></p>
					<p class="lnf-inst-pro-note"><a href="<?php echo esc_url( admin_url( 'admin.php?page=loginfennec-pro' ) ); ?>"><?php esc_html_e( 'Passer en Pro', 'loginfennec' ); ?></a> — <?php esc_html_e( 'URL de connexion personnalisée, verrouillage /wp-admin et support prioritaire. (2FA et reCAPTCHA inclus gratuitement.)', 'loginfennec' ); ?></p>
				</section>

				<section class="lnf-wstep" data-step="4">
					<h2><?php esc_html_e( 'Tout est prêt !', 'loginfennec' ); ?></h2>
					<p class="lnf-inst-desc"><?php esc_html_e( 'Voici votre configuration de départ — tout reste modifiable dans le dashboard.', 'loginfennec' ); ?></p>
					<ul class="lnf-recap-list">
						<li><strong><?php esc_html_e( 'Style', 'loginfennec' ); ?></strong> <span id="lnf-recap-style">—</span></li>
						<li><strong><?php esc_html_e( 'Thème du formulaire', 'loginfennec' ); ?></strong> <span id="lnf-recap-theme">—</span></li>
						<li><strong><?php esc_html_e( 'Sécurité', 'loginfennec' ); ?></strong> <span id="lnf-recap-sec">—</span></li>
						<li><strong><?php esc_html_e( 'Aperçu en direct, journal et score', 'loginfennec' ); ?></strong> <?php esc_html_e( 'disponibles dans le dashboard', 'loginfennec' ); ?></li>
					</ul>
					<p class="lnf-inst-desc"><?php esc_html_e( 'Cliquez sur « Terminer » pour appliquer et ouvrir votre tableau de bord.', 'loginfennec' ); ?></p>
				</section>

				<footer class="lnf-inst-footer">
					<button type="button" class="lnf-btn lnf-btn-ghost lnf-step-prev"><?php esc_html_e( 'Retour', 'loginfennec' ); ?></button>
					<button type="button" class="lnf-btn lnf-btn-primary lnf-step-next"><?php esc_html_e( 'Continuer', 'loginfennec' ); ?></button>
					<button type="submit" class="lnf-btn lnf-btn-primary lnf-step-finish"><?php esc_html_e( 'Terminer et ouvrir le dashboard', 'loginfennec' ); ?></button>
				</footer>
			</form>
		</div>
		<?php
	}

	/**
	 * Page « Passer en Pro » : niveaux de sécurité avancés.
	 */
	public static function render_pro() {
		// Comparatif : array( libellé, inclus gratuit ?, inclus Pro ? ).
		$groups = array(
			__( 'Design & personnalisation', 'loginfennec' ) => array(
				array( __( 'Logo, arrière-plan (image, dégradé, flou), 10 styles et 7 thèmes', 'loginfennec' ), true, true ),
				array( __( 'Aperçu en direct bureau / tablette / mobile', 'loginfennec' ), true, true ),
				array( __( 'Icônes sociales aux couleurs officielles des marques', 'loginfennec' ), true, true ),
				array( __( 'Message de bienvenue, copyright, CSS et JS personnalisés', 'loginfennec' ), true, true ),
				array( __( 'Couleurs de l’admin WordPress + 4 palettes prêtes à l’emploi', 'loginfennec' ), true, true ),
			),
			__( 'Connexion & utilisateurs', 'loginfennec' )  => array(
				array( __( 'Connexion par SMS : code OTP (Twilio, Vonage, webhook local)', 'loginfennec' ), true, true ),
				array( __( 'Anti-abus SMS : délai par numéro, plafond horaire par IP', 'loginfennec' ), true, true ),
				array( __( 'Numéro de téléphone sur les profils utilisateurs', 'loginfennec' ), true, true ),
			),
			__( 'Sécurité incluse', 'loginfennec' )          => array(
				array( __( 'Limitation des tentatives : blocage progressif IP + identifiant', 'loginfennec' ), true, true ),
				array( __( 'Honeypot anti-robots et liste blanche d’adresses de confiance', 'loginfennec' ), true, true ),
				array( __( 'Anti-énumération : balayage des auteurs bloqué, REST des users fermé', 'loginfennec' ), true, true ),
				array( __( 'reCAPTCHA v3 sur la connexion', 'loginfennec' ), true, true ),
				array( __( 'Restriction de la connexion par pays (GEO)', 'loginfennec' ), true, true ),
				array( __( 'Alertes e-mail après chaque blocage', 'loginfennec' ), true, true ),
				array( __( 'Journal de sécurité : 50 derniers événements, export CSV', 'loginfennec' ), true, true ),
				array( __( 'Double authentification (2FA) — Authy, Google Authenticator, Duo Mobile', 'loginfennec' ), true, true ),
				array( __( 'URL de connexion personnalisée', 'loginfennec' ), false, true ),
				array( __( 'Verrouillage dédié de /wp-admin (liste blanche IP)', 'loginfennec' ), false, true ),
			),
			__( 'Référencement & données', 'loginfennec' )   => array(
				array( __( 'noindex, nofollow sur la page de connexion + titre SEO', 'loginfennec' ), true, true ),
				array( __( 'Export / import des réglages (secrets jamais exportés)', 'loginfennec' ), true, true ),
				array( __( 'Journal d’audit étendu (au-delà de 50 événements)', 'loginfennec' ), false, true ),
			),
			__( 'Accompagnement', 'loginfennec' )            => array(
				array( __( 'Appareils et sessions de confiance', 'loginfennec' ), false, true ),
				array( __( 'Support prioritaire par e-mail', 'loginfennec' ), false, true ),
				array( __( 'Mises à jour à vie (licence à vie)', 'loginfennec' ), false, true ),
			),
		);

		$benefits = array(
			array(
				'icon'  => 'dashicons-shield-alt',
				'title' => __( 'Sécurité maximale', 'loginfennec' ),
				'text'  => __( 'URL de connexion personnalisée, verrouillage de /wp-admin, journal d’audit étendu et appareils de confiance : votre page de connexion devient une forteresse.', 'loginfennec' ),
			),
			array(
				'icon'  => 'dashicons-chart-line',
				'title' => __( 'Surveillance complète', 'loginfennec' ),
				'text'  => __( 'Journal étendu, alertes e-mail immédiates et détection des comportements suspects.', 'loginfennec' ),
			),
			array(
				'icon'  => 'dashicons-superhero-alt',
				'title' => __( 'Sérénité totale', 'loginfennec' ),
				'text'  => __( 'Blocage géographique, /wp-admin verrouillé et appareils de confiance : vous gardez le contrôle.', 'loginfennec' ),
			),
		);
		$checkout  = lnf_checkout_url();
		$license   = lnf_license_get();
		$pro       = lnf_is_pro();
		$plans     = lnf_license_plans();
		$expired   = ( ! $pro && 'expired' === $license['status'] );

		// URLs d'achat par pack/billing pour le paiement intégré.
		$buy_urls = array();
		if ( '' !== $checkout ) {
			foreach ( array( 'site1', 'site5' ) as $pack_key ) {
				foreach ( array( 'yearly', 'lifetime' ) as $bill ) {
					$buy_urls[ $pack_key . '-' . $bill ] = add_query_arg(
						array(
							'pack'    => $pack_key,
							'billing' => $bill,
							'site'    => rawurlencode( home_url( '/' ) ),
						),
						$checkout
					);
				}
			}
		}
		?>
		<div class="wrap lnf-wrap lnf-pro">
			<?php if ( isset( $_GET['lnf-paid'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- indicateur d'affichage en lecture seule. ?>
				<div class="notice notice-info is-dismissible"><p><strong><?php esc_html_e( 'Paiement reçu, merci !', 'loginfennec' ); ?></strong>
					<?php esc_html_e( 'Ouvrez l’assistant d’achat et collez votre clé de licence à l’étape 2 — elle vous a été envoyée par e-mail.', 'loginfennec' ); ?></p></div>
			<?php endif; ?>
			<div class="lnf-hero lnf-pro-hero">
				<img class="lnf-hero-logo" src="<?php echo esc_url( LOGINFENNEC_URL . "assets/img/logo-fennec.png" ); ?>" alt="LoginFennec" />
				<h1><?php esc_html_e( 'LoginFennec Pro', 'loginfennec' ); ?></h1>
				<p><?php esc_html_e( 'Poussez la sécurité de votre page de connexion au niveau supérieur : protection avancée, surveillance complète et contrôle total.', 'loginfennec' ); ?></p>
				<div class="lnf-hero-actions">
					<?php if ( ! $pro ) : ?>
						<a class="lnf-btn lnf-btn-pro" href="#lnf-packs-head">
							<span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'Acheter maintenant', 'loginfennec' ); ?>
						</a>
					<?php endif; ?>
					<a class="lnf-btn lnf-btn-ghost is-light" href="<?php echo esc_url( admin_url( 'admin.php?page=loginfennec' ) ); ?>">
						<?php esc_html_e( 'Revenir au dashboard', 'loginfennec' ); ?>
					</a>
				</div>
			</div>

			<?php if ( $pro ) : ?>
				<section class="lnf-about-card lnf-license-card">
					<h2>
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Votre licence Pro', 'loginfennec' ); ?>
						<span class="lnf-chip <?php echo lnf_is_pro() ? 'is-on' : 'is-off'; ?>">
							<?php echo lnf_is_pro() ? esc_html__( 'Active', 'loginfennec' ) : esc_html__( 'Expirée', 'loginfennec' ); ?>
						</span>
					</h2>
					<ul class="lnf-license-details">
						<li><strong><?php esc_html_e( 'Clé', 'loginfennec' ); ?></strong> <code><?php echo esc_html( strlen( $license['key'] ) > 10 ? substr( $license['key'], 0, 4 ) . '••••' . substr( $license['key'], -4 ) : $license['key'] ); ?></code></li>
						<li><strong><?php esc_html_e( 'Pack', 'loginfennec' ); ?></strong> <?php echo esc_html( lnf_license_label() ); ?></li>
						<li><strong><?php esc_html_e( 'Type', 'loginfennec' ); ?></strong> <?php echo esc_html( 'lifetime' === $license['billing'] ? __( 'À vie — mises à jour pour toujours', 'loginfennec' ) : __( 'Annuelle', 'loginfennec' ) ); ?></li>
						<li><strong><?php esc_html_e( 'Expiration', 'loginfennec' ); ?></strong> <?php echo esc_html( 'lifetime' === $license['billing'] || 0 === (int) $license['expires'] ? __( 'Jamais — licence à vie', 'loginfennec' ) : date_i18n( get_option( 'date_format' ), (int) $license['expires'] ) ); ?></li>
						<li><strong><?php esc_html_e( 'Sites autorisés', 'loginfennec' ); ?></strong> <?php echo esc_html( $license['sites'] ); ?></li>
						<li><strong><?php esc_html_e( 'Dernière vérification', 'loginfennec' ); ?></strong> <?php echo esc_html( (int) $license['checked'] > 0 ? date_i18n( get_option( 'date_format' ) . ' H:i', (int) $license['checked'] + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) : '—' ); ?></li>
					</ul>
					<div class="lnf-hero-actions">
						<button type="button" class="button lnf-check-license"><span class="dashicons dashicons-update-alt"></span> <?php esc_html_e( 'Vérifier maintenant', 'loginfennec' ); ?></button>
						<button type="button" class="button-link lnf-deactivate-license"><?php esc_html_e( 'Désactiver la licence sur ce site', 'loginfennec' ); ?></button>
						<span class="lnf-license-status" aria-live="polite"></span>
					</div>
				</section>
			<?php else : ?>
				<?php if ( $expired ) : ?>
					<div class="notice notice-warning"><p><strong><?php esc_html_e( 'Votre licence a expiré.', 'loginfennec' ); ?></strong> <?php esc_html_e( 'Renouvelez votre pack pour réactiver les fonctionnalités Pro.', 'loginfennec' ); ?></p></div>
				<?php endif; ?>
				<section class="lnf-about-card lnf-purchase-card">
				<h2><span class="dashicons dashicons-unlock"></span> <?php esc_html_e( 'Débloquer Pro sans quitter votre tableau de bord', 'loginfennec' ); ?></h2>
				<?php if ( '' === lnf_license_api() && ! lnf_is_pro() ) : ?>
					<div class="notice inline notice-info" style="margin:0 0 18px;padding:12px 16px;">
						<p style="margin:0 0 8px;"><strong>🧪 <?php esc_html_e( 'Mode développeur', 'loginfennec' ); ?></strong> —
							<?php esc_html_e( 'aucun serveur de licences n’est configuré : l’activation se fait localement, idéale pour tester l’état Pro.', 'loginfennec' ); ?></p>
						<button type="button" class="button button-primary lnf-dev-activate">🔑 <?php esc_html_e( 'Activer la licence développeur (test)', 'loginfennec' ); ?></button>
					</div>
				<?php endif; ?>
				<ol class="lnf-purchase-steps">
						<li><?php esc_html_e( 'Choisissez votre pack ci-dessous (annuelle ou à vie) et achetez : le paiement s’ouvre ici même.', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( 'Après l’achat, vous recevez votre clé de licence par e-mail.', 'loginfennec' ); ?></li>
						<li><?php esc_html_e( 'Collez la clé ci-dessous : Pro est activé instantanément.', 'loginfennec' ); ?></li>
					</ol>
					<div class="lnf-license-form">
						<select id="lnf-license-pack" class="lnf-input" aria-label="<?php esc_attr_e( 'Pack', 'loginfennec' ); ?>">
							<option value="site1"><?php esc_html_e( 'Pro — 1 site', 'loginfennec' ); ?></option>
							<option value="site5"><?php esc_html_e( 'Pro — 5 sites', 'loginfennec' ); ?></option>
						</select>
						<select id="lnf-license-billing" class="lnf-input" aria-label="<?php esc_attr_e( 'Type de licence', 'loginfennec' ); ?>">
							<option value="yearly"><?php esc_html_e( 'Annuelle', 'loginfennec' ); ?></option>
							<option value="lifetime"><?php esc_html_e( 'À vie', 'loginfennec' ); ?></option>
						</select>
						<input type="text" id="lnf-license-key" class="lnf-input" placeholder="XXXX-XXXX-XXXX-XXXX" autocomplete="off">
						<button type="button" class="button button-primary lnf-activate-license"><?php esc_html_e( 'Activer Pro', 'loginfennec' ); ?></button>
					</div>
					<p class="lnf-license-status" aria-live="polite"></p>
					<?php if ( '' === $checkout ) : ?>
						<p class="lnf-pro-note"><?php esc_html_e( 'Configuration vendeur (visible par les administrateurs uniquement) : définissez LOGINFENNEC_CHECKOUT_URL dans wp-config.php pour ouvrir le paiement intégré, et LOGINFENNEC_LICENSE_API pour valider les clés. D’ici là, l’activation est acceptée localement pour vos tests.', 'loginfennec' ); ?></p>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( ! $pro ) : ?>
				<div class="lnf-packs-head" id="lnf-packs-head">
					<h2><?php esc_html_e( 'Choisissez votre pack', 'loginfennec' ); ?></h2>
					<div class="lnf-billing-toggle" role="group" aria-label="<?php esc_attr_e( 'Type de licence', 'loginfennec' ); ?>">
						<button type="button" class="lnf-bill-btn is-active" data-billing="yearly"><?php esc_html_e( 'Annuelle', 'loginfennec' ); ?></button>
						<button type="button" class="lnf-bill-btn" data-billing="lifetime"><?php esc_html_e( 'À vie', 'loginfennec' ); ?></button>
					</div>
				</div>

				<div class="lnf-packs">
					<div class="lnf-pack">
						<h3><?php esc_html_e( 'Gratuit', 'loginfennec' ); ?></h3>
						<div class="lnf-pack-price"><span class="lnf-price">0 DA</span></div>
						<p class="lnf-pack-per"><?php esc_html_e( 'pour toujours', 'loginfennec' ); ?></p>
						<ul class="lnf-pack-list">
							<li><?php esc_html_e( 'Personnalisation complète de la page de connexion', 'loginfennec' ); ?></li>
							<li><?php esc_html_e( 'Journal de sécurité (50 événements)', 'loginfennec' ); ?></li>
							<li><?php esc_html_e( 'Honeypot + anti-énumération', 'loginfennec' ); ?></li>
							<li><?php esc_html_e( 'Score de sécurité et aperçu en direct', 'loginfennec' ); ?></li>
						</ul>
						<p class="lnf-pack-note">✨ <?php esc_html_e( 'Vous y êtes déjà', 'loginfennec' ); ?></p>
					</div>

						<div class="lnf-pack is-featured">
						<div class="lnf-pack-badge"><?php esc_html_e( 'Recommandé', 'loginfennec' ); ?></div>
						<h3><?php esc_html_e( 'Pro — 1 site', 'loginfennec' ); ?></h3>
						<div class="lnf-pack-price">
							<span class="lnf-price" data-yearly="<?php echo esc_attr( $plans['site1']['yearly']['label'] ); ?>" data-lifetime="<?php echo esc_attr( $plans['site1']['lifetime']['label'] ); ?>"><?php echo esc_html( $plans['site1']['yearly']['label'] ); ?></span>
						</div>
						<ul class="lnf-pack-list">
							<li><?php esc_html_e( 'Tout le gratuit, plus :', 'loginfennec' ); ?></li>
							<li><?php esc_html_e( 'Alertes e-mail de blocage', 'loginfennec' ); ?></li>
							<li><?php esc_html_e( 'URL personnalisée, verrouillage /wp-admin*', 'loginfennec' ); ?></li>
							<li class="lnf-updates" data-yearly="<?php esc_attr_e( 'Mises à jour pendant 1 an', 'loginfennec' ); ?>" data-lifetime="<?php esc_attr_e( 'Mises à jour à vie ♾️', 'loginfennec' ); ?>"><?php esc_html_e( 'Mises à jour pendant 1 an', 'loginfennec' ); ?></li>
						</ul>
						<button type="button" class="lnf-btn lnf-btn-pro lnf-buy-open" data-pack="site1"><?php esc_html_e( 'Acheter — paiement sur place', 'loginfennec' ); ?></button>
					</div>

					<div class="lnf-pack">
						<div class="lnf-pack-badge"><?php esc_html_e( 'Agences & multi-sites', 'loginfennec' ); ?></div>
						<h3><?php esc_html_e( 'Pro — 5 sites', 'loginfennec' ); ?></h3>
						<div class="lnf-pack-price">
							<span class="lnf-price" data-yearly="<?php echo esc_attr( $plans['site5']['yearly']['label'] ); ?>" data-lifetime="<?php echo esc_attr( $plans['site5']['lifetime']['label'] ); ?>"><?php echo esc_html( $plans['site5']['yearly']['label'] ); ?></span>
						</div>
						<ul class="lnf-pack-list">
							<li><?php esc_html_e( 'Tout le pack 1 site, plus :', 'loginfennec' ); ?></li>
							<li><?php esc_html_e( 'Licence pour 5 sites', 'loginfennec' ); ?></li>
							<li><?php esc_html_e( 'Idéal pour agences et clients', 'loginfennec' ); ?></li>
							<li class="lnf-updates" data-yearly="<?php esc_attr_e( 'Mises à jour pendant 1 an', 'loginfennec' ); ?>" data-lifetime="<?php esc_attr_e( 'Mises à jour à vie ♾️', 'loginfennec' ); ?>"><?php esc_html_e( 'Mises à jour pendant 1 an', 'loginfennec' ); ?></li>
						</ul>
						<button type="button" class="lnf-btn lnf-btn-pro lnf-buy-open" data-pack="site5"><?php esc_html_e( 'Acheter — paiement sur place', 'loginfennec' ); ?></button>
					</div>
				</div>
				<p class="lnf-pack-footnote">* <?php esc_html_e( 'Fonctionnalités livrées par le module Pro en préparation — votre licence les débloquera automatiquement dès leur sortie.', 'loginfennec' ); ?></p>

				<?php if ( ! $pro ) : ?>
				<div class="lnf-modal lnf-wizard" id="lnf-checkout-modal" hidden>
					<div class="lnf-modal-backdrop" data-close></div>
					<div class="lnf-modal-box lnf-wz-box" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Achat de LoginFennec Pro', 'loginfennec' ); ?>">
						<div class="lnf-modal-head">
							<strong>🛒 LoginFennec Pro — <span id="lnf-wz-pack-label">—</span> · <span id="lnf-wz-billing-label">—</span></strong>
							<button type="button" class="lnf-modal-close" data-close aria-label="<?php esc_attr_e( 'Fermer', 'loginfennec' ); ?>">×</button>
						</div>
						<div class="lnf-wz-steps" aria-hidden="true">
							<span class="wz-step is-active" data-ws="1"><?php esc_html_e( '1 · Paiement', 'loginfennec' ); ?></span>
							<span class="wz-step" data-ws="2"><?php esc_html_e( '2 · Clé de licence', 'loginfennec' ); ?></span>
							<span class="wz-step" data-ws="3"><?php esc_html_e( '3 · Activé', 'loginfennec' ); ?></span>
						</div>
						<div class="lnf-wz-body">

							<div class="lnf-wz-pane is-active" data-wpane="1">
								<div class="lnf-wz-summary">
									<div class="lnf-wz-sum-row"><span><?php esc_html_e( 'Pack', 'loginfennec' ); ?></span><strong id="lnf-wz-pack">—</strong></div>
									<div class="lnf-wz-sum-row"><span><?php esc_html_e( 'Type', 'loginfennec' ); ?></span><strong id="lnf-wz-billing">—</strong></div>
									<div class="lnf-wz-sum-row lnf-wz-total"><span><?php esc_html_e( 'Montant à payer', 'loginfennec' ); ?></span><strong id="lnf-wz-amount-da">—</strong></div>
								</div>
								<div class="lnf-wz-methods">
									<button type="button" class="lnf-wz-method is-active" data-m="ccp"><span class="dashicons dashicons-bank"></span><strong>BaridiMob / CCP</strong><small><?php esc_html_e( 'en DA', 'loginfennec' ); ?></small></button>
									<button type="button" class="lnf-wz-method" data-m="paypal"><span class="dashicons dashicons-money-alt"></span><strong>PayPal</strong><small><?php esc_html_e( 'international', 'loginfennec' ); ?></small></button>
									<?php if ( '' !== $checkout ) : ?>
										<button type="button" class="lnf-wz-method" data-m="card"><span class="dashicons dashicons-cart"></span><strong><?php esc_html_e( 'Carte bancaire', 'loginfennec' ); ?></strong><small><?php esc_html_e( 'en ligne', 'loginfennec' ); ?></small></button>
									<?php endif; ?>
								</div>

								<div class="lnf-wz-mbody" data-mbody="ccp">
									<p class="lnf-inst-desc"><?php esc_html_e( 'Payez le montant exact via BaridiMob ou un virement CCP :', 'loginfennec' ); ?></p>
									<div class="lnf-ccp-box">
										<div class="lnf-ccp-row"><span><?php esc_html_e( 'Compte CCP', 'loginfennec' ); ?></span><code id="lnf-wz-ccp-rip"><?php echo esc_html( lnf_ccp_rip() ); ?></code><button type="button" class="button-link lnf-copy" data-copy="#lnf-wz-ccp-rip"><?php esc_html_e( 'Copier', 'loginfennec' ); ?></button></div>
										<div class="lnf-ccp-row"><span>BaridiMob</span><code id="lnf-wz-ccp-baridimob"><?php echo esc_html( lnf_baridimob() ); ?></code><button type="button" class="button-link lnf-copy" data-copy="#lnf-wz-ccp-baridimob"><?php esc_html_e( 'Copier', 'loginfennec' ); ?></button></div>
										<div class="lnf-ccp-row"><span><?php esc_html_e( 'Titulaire', 'loginfennec' ); ?></span><strong><?php echo esc_html( lnf_ccp_name() ); ?></strong></div>
										<div class="lnf-ccp-row"><span><?php esc_html_e( 'Montant exact', 'loginfennec' ); ?></span><strong id="lnf-wz-ccp-amount">—</strong><button type="button" class="button-link lnf-copy" data-copy="#lnf-wz-ccp-amount"><?php esc_html_e( 'Copier', 'loginfennec' ); ?></button></div>
									</div>
									<ol class="lnf-ccp-steps">
										<li><?php esc_html_e( 'Payez via l’application BaridiMob ou un virement CCP.', 'loginfennec' ); ?></li>
										<li><?php esc_html_e( 'Envoyez la capture du reçu avec le bouton « Envoyer ma preuve ».', 'loginfennec' ); ?></li>
										<li><?php esc_html_e( 'Recevez votre clé par e-mail, puis passez à l’étape 2.', 'loginfennec' ); ?></li>
									</ol>
									<a class="button lnf-proof-mailto" href="#"><span class="dashicons dashicons-email"></span> <?php esc_html_e( 'Envoyer ma preuve de paiement', 'loginfennec' ); ?></a>
									<a class="button lnf-proof-wa" href="#" target="_blank" rel="noopener noreferrer"<?php if ( '' === lnf_whatsapp_number() ) { echo ' hidden'; } ?>><span class="dashicons dashicons-format-chat"></span> <?php esc_html_e( 'WhatsApp', 'loginfennec' ); ?></a>
									<div class="lnf-wz-actions"><button type="button" class="button button-primary lnf-wz-next"><?php esc_html_e( 'J’ai payé — saisir ma clé', 'loginfennec' ); ?></button></div>
								</div>

								<div class="lnf-wz-mbody" data-mbody="paypal" hidden>
									<p class="lnf-inst-desc"><?php esc_html_e( 'Montant PayPal :', 'loginfennec' ); ?> <strong id="lnf-wz-paypal-amount">—</strong></p>
									<p><a class="button button-primary" id="lnf-wz-paypal-link" href="#" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-external"></span> <?php esc_html_e( 'Payer sur PayPal', 'loginfennec' ); ?></a></p>
									<p class="lnf-inst-desc"><?php esc_html_e( 'Après le paiement, collez la clé reçue par e-mail à l’étape 2.', 'loginfennec' ); ?></p>
									<div class="lnf-wz-actions"><button type="button" class="button button-primary lnf-wz-next"><?php esc_html_e( 'J’ai payé — saisir ma clé', 'loginfennec' ); ?></button></div>
								</div>

								<div class="lnf-wz-mbody" data-mbody="card" hidden>
									<iframe id="lnf-wz-card-frame" src="about:blank" title="<?php esc_attr_e( 'Paiement par carte', 'loginfennec' ); ?>"></iframe>
									<p class="lnf-inst-desc"><a id="lnf-wz-card-newtab" href="#" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'La fenêtre ne s’affiche pas ? Ouvrir dans un nouvel onglet.', 'loginfennec' ); ?></a></p>
									<div class="lnf-wz-actions"><button type="button" class="button button-primary lnf-wz-next"><?php esc_html_e( 'J’ai payé — saisir ma clé', 'loginfennec' ); ?></button></div>
								</div>
							</div>

							<div class="lnf-wz-pane" data-wpane="2">
								<h3><?php esc_html_e( 'Collez votre clé de licence', 'loginfennec' ); ?></h3>
								<p class="lnf-inst-desc"><?php esc_html_e( 'Elle vous a été envoyée par e-mail après votre paiement.', 'loginfennec' ); ?></p>
								<input type="text" id="lnf-modal-key" class="lnf-input" placeholder="XXXX-XXXX-XXXX-XXXX" autocomplete="off">
								<p><button type="button" class="button button-primary lnf-modal-activate"><?php esc_html_e( 'Activer Pro', 'loginfennec' ); ?></button>
								<button type="button" class="button-link lnf-wz-back2">← <?php esc_html_e( 'Revenir au paiement', 'loginfennec' ); ?></button></p>
								<p class="lnf-license-status" aria-live="polite"></p>
							</div>

							<div class="lnf-wz-pane" data-wpane="3">
								<div class="lnf-wz-success">✓</div>
								<h3><?php esc_html_e( 'Pro activé — merci pour votre soutien !', 'loginfennec' ); ?></h3>
								<p class="lnf-wz-success-msg"><?php esc_html_e( 'Votre licence est active sur ce site.', 'loginfennec' ); ?></p>
								<p><button type="button" class="button button-primary" data-close><?php esc_html_e( 'Terminer', 'loginfennec' ); ?></button></p>
							</div>

						</div>
					</div>
				</div>
				<?php endif; ?>
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
				<h2><span class="dashicons dashicons-shield-alt"></span> <?php esc_html_e( 'Comparatif détaillé : Gratuit vs Pro', 'loginfennec' ); ?></h2>
				<table class="lnf-pro-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Fonctionnalité', 'loginfennec' ); ?></th>
							<th><?php esc_html_e( 'Gratuit', 'loginfennec' ); ?></th>
							<th class="lnf-pro-col">Pro</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $groups as $group_label => $group_rows ) : ?>
							<tr class="lnf-pro-group"><td colspan="3"><?php echo esc_html( $group_label ); ?></td></tr>
							<?php foreach ( $group_rows as $row ) : ?>
								<tr>
									<td><?php echo esc_html( $row[0] ); ?></td>
									<td><?php echo $row[1] ? '<span class="dashicons dashicons-yes-alt is-yes"></span>' : '<span class="dashicons dashicons-minus is-no"></span>'; ?></td>
									<td class="lnf-pro-col"><?php echo $row[2] ? '<span class="dashicons dashicons-yes-alt is-yes"></span>' : '<span class="dashicons dashicons-minus is-no"></span>'; ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="lnf-pro-note"><?php esc_html_e( 'Le module Pro est en préparation — le bouton « Passer en Pro » devient actif dès sa sortie (URL personnalisable via le filtre loginfennec_pro_url).', 'loginfennec' ); ?></p>
				<div class="lnf-hero-actions">
					<a class="lnf-btn lnf-btn-pro" href="<?php echo esc_url( self::pro_url() ); ?>" target="_blank" rel="noopener">
						<span class="dashicons dashicons-superhero-alt"></span> <?php esc_html_e( 'Passer en Pro', 'loginfennec' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}
}

Lnf_Admin::init();
