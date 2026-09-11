<?php
/**
 * Apparence de la page de connexion : CSS dynamique, logo, liens,
 * réseaux sociaux, copyright.
 *
 * @package InfinityCustomizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Construit le CSS de la page de connexion à partir des réglages.
 * Réutilisé par l'aperçu en direct du dashboard.
 *
 * @param array $s Réglages (infcl_settings()).
 * @return string CSS.
 */
function infcl_build_login_css( $s ) {
	$css = '';

	// Mise en page : largeur du formulaire centrée.
	$css .= sprintf(
		'body.login #login{width:%1$dpx;max-width:calc(100%% - 40px);margin:8%% auto 0;padding-inline:0;box-sizing:border-box;}',
		(int) $s['form_width']
	);

	// Éléments au-dessus des calques d'arrière-plan.
	$css .= 'body.login #login,body.login .infcl-social,body.login .infcl-copyright{position:relative;z-index:1;}';

	// ——— Arrière-plan ———.
	if ( 'image' === $s['bg_type'] && ! empty( $s['bg_image'] ) ) {
		$size = 'cover';
		if ( 'contain' === $s['bg_size'] || 'repeat' === $s['bg_size'] ) {
			$size = $s['bg_size'];
		}
		$repeat   = ( 'repeat' === $s['bg_size'] ) ? 'repeat' : 'no-repeat';
		$position = str_replace( '-', ' ', (string) $s['bg_position'] );
		if ( ! preg_match( '/^(top|bottom|left|right|center)?( (top|bottom|left|right|center))?$/', $position ) ) {
			$position = 'center';
		}

		$css .= 'body.login{background:#101517;}';
		$css .= sprintf(
			'body.login::before{content:"";position:fixed;inset:0;z-index:0;background:url(%1$s) %7$s / %2$s %3$s;filter:blur(%4$dpx) brightness(%5$d%%) saturate(%6$d%%);transform:scale(1.08);pointer-events:none;}',
			wp_json_encode( esc_url_raw( $s['bg_image'] ) ),
			$size,
			$repeat,
			(int) $s['bg_blur'],
			(int) $s['bg_brightness'],
			(int) $s['bg_saturation'],
			$position
		);
	} elseif ( 'gradient' === $s['bg_type'] ) {
		$css .= sprintf(
			'body.login{background:linear-gradient(%1$ddeg, %2$s 0%%, %3$s 100%%);background-attachment:fixed;}',
			(int) $s['bg_gradient_angle'],
			$s['bg_color1'],
			$s['bg_color2']
		);
	} else {
		$css .= sprintf( 'body.login{background:%s;background-attachment:fixed;}', $s['bg_color1'] );
	}

	if ( (int) $s['bg_overlay_opacity'] > 0 ) {
		$css .= sprintf(
			'body.login::after{content:"";position:fixed;inset:0;z-index:0;background:%s;pointer-events:none;}',
			infcl_hex_to_rgba( $s['bg_overlay_color'], (int) $s['bg_overlay_opacity'] )
		);
	}

	// ——— Logo ———.
	if ( ! empty( $s['logo_hide'] ) ) {
		$css .= 'body.login #login h1{display:none;}';
	} else {
		if ( ! empty( $s['logo_url'] ) ) {
			$css .= sprintf(
				'body.login #login h1 a{background-image:url(%1$s);width:%2$dpx;height:%3$dpx;max-width:80vw;background-size:contain;background-position:center center;background-repeat:no-repeat;margin:0 auto 22px;}',
				wp_json_encode( esc_url_raw( $s['logo_url'] ) ),
				(int) $s['logo_width'],
				(int) $s['logo_height']
			);
		}
	}

	// ——— Formulaire (effet verre) ———.
	$form_bg  = infcl_hex_to_rgba( $s['form_bg'], (int) $s['form_opacity'] );
	$css     .= sprintf(
		'body.login #login form,body.login form{background:%1$s;-webkit-backdrop-filter:blur(%2$dpx);backdrop-filter:blur(%2$dpx);border-radius:%3$dpx;border:1px solid %4$s;padding:%5$dpx %5$dpx calc(%5$dpx - 6px);}',
		$form_bg,
		(int) $s['form_blur'],
		(int) $s['form_radius'],
		infcl_hex_to_rgba( $s['input_border'], 35 ),
		(int) $s['form_padding']
	);
	if ( ! empty( $s['form_shadow'] ) ) {
		$css .= 'body.login #login form,body.login form{box-shadow:0 24px 70px -18px rgba(0,0,0,.45);}';
	}

	// Textes et libellés.
	$css .= sprintf( 'body.login{color:%s;}', $s['text_color'] );
	$css .= sprintf( 'body.login form label,body.login .forgetmenot label{color:%s;}', $s['label_color'] );

	// Champs de saisie.
	$css .= sprintf(
		'body.login form .input,body.login form input[type=checkbox]{background:%1$s;color:%2$s;border-color:%3$s;border-radius:8px;}',
		$s['input_bg'],
		$s['input_color'],
		$s['input_border']
	);
	$css .= sprintf(
		'body.login form .input:focus{border-color:%1$s;box-shadow:0 0 0 3px %2$s;}',
		$s['button_bg'],
		infcl_hex_to_rgba( $s['button_bg'], 30 )
	);

	// Bouton principal.
	$css .= sprintf(
		'body.login #wp-submit{background:%1$s;border-color:%1$s;color:#fff;border-radius:%2$dpx;height:40px;padding:0 18px;font-size:14px;}',
		$s['button_bg'],
		(int) $s['button_radius']
	);
	$css .= sprintf(
		'body.login #wp-submit:hover,body.login #wp-submit:focus{background:%1$s;border-color:%1$s;box-shadow:0 0 0 3px %2$s;}',
		$s['button_hover'],
		infcl_hex_to_rgba( $s['button_hover'], 35 )
	);

	// Liens (nav, retour au site).
	$css .= sprintf(
		'body.login #nav a,body.login #backtoblog a,body.login .infcl-copyright a{color:%s;}',
		$s['link_color']
	);
	$css .= sprintf( 'body.login #nav a:hover,body.login #backtoblog a:hover{color:%s;}', $s['button_bg'] );

	// Masquage des liens.
	if ( ! empty( $s['hide_lost_password'] ) ) {
		$css .= 'body.login #nav a[href*="action=lostpassword"]{display:none;}';
	}
	if ( ! empty( $s['hide_register'] ) ) {
		$css .= 'body.login #nav a[href*="action=register"]{display:none;}';
	}
	if ( ! empty( $s['hide_back_to'] ) ) {
		$css .= 'body.login #backtoblog{display:none;}';
	}

	// ——— Réseaux sociaux ———.
	if ( ! empty( $s['social_enable'] ) ) {
		$radius = '50%';
		if ( 'rounded' === $s['social_style'] ) {
			$radius = '12px';
		} elseif ( 'square' === $s['social_style'] ) {
			$radius = '6px';
		}
		$css .= sprintf(
			'.infcl-social{display:flex;justify-content:center;align-items:center;gap:10px;flex-wrap:wrap;margin:18px 0 0;}',
			(int) $s['social_size']
		);
		$css .= sprintf(
			'.infcl-social a.infcl-icon{width:%1$dpx;height:%1$dpx;border-radius:%2$s;display:inline-flex;align-items:center;justify-content:center;color:%3$s;background:%4$s;text-decoration:none;transition:transform .18s ease,background-color .18s ease,box-shadow .18s ease;}',
			(int) $s['social_size'],
			$radius,
			$s['social_icon_color'],
			infcl_hex_to_rgba( $s['social_icon_bg'], (int) $s['social_icon_bg_opacity'] )
		);
		$css .= sprintf(
			'.infcl-social a.infcl-icon .dashicons{font-size:%1$dpx;width:%1$dpx;height:%1$dpx;line-height:1;}',
			max( 14, (int) round( (int) $s['social_size'] * 0.55 ) )
		);
		$css .= sprintf(
			'.infcl-social a.infcl-icon:hover{transform:translateY(-2px);background:%1$s;box-shadow:0 8px 20px -6px %2$s;color:%3$s;}',
			$s['social_icon_bg'],
			infcl_hex_to_rgba( $s['social_icon_bg'], 60 ),
			$s['social_icon_color']
		);

		// Couleurs officielles des marques (priorité sur les couleurs génériques).
		if ( ! empty( $s['social_brand'] ) ) {
			$brands = array(
				'facebook'  => '#1877F2',
				'twitter'   => '#000000',
				'instagram' => 'linear-gradient(45deg, #F58529 0%, #DD2A7B 45%, #8134AF 70%, #515BD4 100%)',
				'linkedin'  => '#0A66C2',
				'youtube'   => '#FF0000',
				'email'     => '#EA4335',
			);
			foreach ( $brands as $network => $color ) {
				$css .= sprintf(
					'.infcl-social a.infcl-icon[data-network="%1$s"],.infcl-social a.infcl-icon[data-network="%1$s"]:hover{background:%2$s;color:#fff;}',
					$network,
					$color
				);
			}
		}
	}

	// ——— Copyright ———.
	$css .= sprintf(
		'.infcl-copyright{margin-top:14px;text-align:center;font-size:12.5px;opacity:.92;letter-spacing:.2px;}',
		$s['link_color']
	);

	// ——— Responsive ———.
	$css .= sprintf(
		'@media (max-width:600px){body.login #login{width:100%%;max-width:calc(100%% - 32px);}body.login #login form,body.login form{padding:%1$dpx 20px %2$dpx;}body.login #login h1 a{max-width:72vw;}}',
		max( 18, (int) $s['form_padding'] - 10 ),
		max( 18, (int) $s['form_padding'] - 12 )
	);

	// ——— Extras : typographie ———.
	$fonts = array(
		'serif'   => 'Georgia, "Times New Roman", serif',
		'rounded' => '"Trebuchet MS", "Segoe UI", Verdana, sans-serif',
		'mono'    => 'Consolas, "SF Mono", "Courier New", monospace',
	);
	if ( 'system' !== $s['font_family'] && isset( $fonts[ $s['font_family'] ] ) ) {
		$css .= sprintf(
			'body.login,body.login form .input,body.login #wp-submit{font-family:%s;}',
			$fonts[ $s['font_family'] ]
		);
	}
	$css .= sprintf(
		'body.login{font-size:%1$dpx;}body.login form label,body.login .forgetmenot label{font-size:%1$dpx;}',
		(int) $s['font_size']
	);

	// ——— Extras : animation d'entrée ———.
	if ( 'none' !== $s['anim'] ) {
		$names = array( 'fade' => 'infcl-fade', 'slide' => 'infcl-slide', 'zoom' => 'infcl-zoom' );
		$name  = isset( $names[ $s['anim'] ] ) ? $names[ $s['anim'] ] : 'infcl-fade';
		$css  .= '@keyframes infcl-fade{from{opacity:0}to{opacity:1}}'
			. '@keyframes infcl-slide{from{opacity:0;transform:translateY(26px)}to{opacity:1;transform:none}}'
			. '@keyframes infcl-zoom{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:none}}';
		$css  .= sprintf(
			'body.login #login h1 a{animation:%1$s .7s ease both;}body.login #login form{animation:%1$s .6s ease .05s both;}',
			$name
		);
		$css  .= '@media (prefers-reduced-motion: reduce){body.login #login h1 a,body.login #login form{animation:none !important;}}';
	}

	// ——— Extras : message d'accueil ———.
	if ( ! empty( $s['welcome_enable'] ) ) {
		$css .= '.infcl-welcome{margin:0 0 16px;text-align:center;}';
		$css .= sprintf( '.infcl-welcome h3{margin:0 0 6px;font-size:22px;line-height:1.25;color:%s;}', $s['text_color'] );
		$css .= sprintf( '.infcl-welcome p{margin:0;font-size:13.5px;color:%s;}', $s['label_color'] );
	}

	// Messages d'erreur / info sur fond translucide.
	$css .= 'body.login #login_error,body.login .message,body.login #login .message{border-radius:10px;}';

	return $css;
}

/**
 * Réseaux sociaux configurés.
 *
 * @param array $s Réglages.
 * @return array tableau libellé => [ 'url', 'icon' ].
 */
function infcl_get_social_networks( $s ) {
	$networks = array(
		'facebook'  => array( 'icon' => 'dashicons-facebook-alt', 'label' => __( 'Facebook', 'infinity-customizer' ) ),
		'twitter'   => array( 'icon' => 'dashicons-twitter', 'label' => __( 'X (Twitter)', 'infinity-customizer' ) ),
		'instagram' => array( 'icon' => 'dashicons-instagram', 'label' => __( 'Instagram', 'infinity-customizer' ) ),
		'linkedin'  => array( 'icon' => 'dashicons-linkedin', 'label' => __( 'LinkedIn', 'infinity-customizer' ) ),
		'youtube'   => array( 'icon' => 'dashicons-youtube', 'label' => __( 'YouTube', 'infinity-customizer' ) ),
		'email'     => array( 'icon' => 'dashicons-email-alt', 'label' => __( 'E-mail', 'infinity-customizer' ) ),
	);

	$active = array();
	foreach ( $networks as $key => $data ) {
		$url = isset( $s[ 'social_' . $key ] ) ? trim( (string) $s[ 'social_' . $key ] ) : '';
		if ( '' !== $url ) {
			$href = ( 'email' === $key && 0 !== strpos( $url, 'mailto:' ) ) ? 'mailto:' . $url : $url;
			$active[ $key ] = array(
				'url'   => $href,
				'icon'  => $data['icon'],
				'label' => $data['label'],
			);
		}
	}
	return $active;
}

/**
 * Affiche le CSS personnalisé dans la page de connexion.
 */
function infcl_login_head() {
	$s = infcl_settings();
	echo '<style id="infinity-customizer">' . "\n";
	echo wp_strip_all_tags( infcl_build_login_css( $s ) ) . "\n";
	echo "</style>\n";
}
add_action( 'login_head', 'infcl_login_head', 30 );

/**
 * Charge Dashicons (icônes sociales) sur la page de connexion.
 */
function infcl_login_enqueue() {
	wp_enqueue_style( 'dashicons' );
}
add_action( 'login_enqueue_scripts', 'infcl_login_enqueue' );

/**
 * Lien du logo.
 *
 * @return string
 */
function infcl_logo_url() {
	$s = infcl_settings();
	return ! empty( $s['logo_link'] ) ? $s['logo_link'] : home_url( '/' );
}
add_filter( 'login_headerurl', 'infcl_logo_url', 100 );

/**
 * Texte alternatif du logo.
 *
 * @return string
 */
function infcl_logo_text() {
	return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'infcl_logo_text', 100 );

/**
 * Message d'accueil personnalisé au-dessus du formulaire.
 *
 * @param string $message Message courant.
 * @return string
 */
function infcl_welcome_message( $message ) {
	$s = infcl_settings();
	if ( empty( $s['welcome_enable'] ) ) {
		return $message;
	}
	$title    = trim( (string) $s['welcome_title'] );
	$subtitle = trim( (string) $s['welcome_subtitle'] );
	if ( '' === $title && '' === $subtitle ) {
		return $message;
	}
	$html = '<div class="infcl-welcome">';
	if ( '' !== $title ) {
		$html .= '<h3>' . esc_html( $title ) . '</h3>';
	}
	if ( '' !== $subtitle ) {
		$html .= '<p>' . esc_html( $subtitle ) . '</p>';
	}
	$html .= '</div>';
	return $message . $html;
}
add_filter( 'login_message', 'infcl_welcome_message', 5 );

/**
 * Message d'erreur générique (option « masquer les détails »).
 *
 * @param string $error Message d'erreur.
 * @return string
 */
function infcl_generic_login_error( $error ) {
	$s = infcl_settings();
	if ( empty( $s['sec_generic_error'] ) || Infcl_Login_Security::$lock_triggered ) {
		return $error;
	}
	return '<strong>' . esc_html__( 'Erreur', 'infinity-customizer' ) . '</strong> : '
		. esc_html__( 'Identifiants incorrects. Veuillez réessayer.', 'infinity-customizer' );
}
add_filter( 'login_errors', 'infcl_generic_login_error', 100 );

/**
 * Masque le sélecteur de langue.
 *
 * @return bool
 */
function infcl_hide_language_switcher() {
	$s = infcl_settings();
	return empty( $s['sec_hide_language_switcher'] );
}
add_filter( 'login_display_language_dropdown', 'infcl_hide_language_switcher', 100 );

/**
 * Pied de page : réseaux sociaux, copyright, ajustements de liens.
 */
function infcl_login_footer() {
	$s   = infcl_settings();
	$js  = '';

	// ——— Réseaux sociaux ———.
	if ( ! empty( $s['social_enable'] ) ) {
		$networks = infcl_get_social_networks( $s );
		if ( $networks ) {
			echo '<div class="infcl-social" aria-label="' . esc_attr__( 'Réseaux sociaux', 'infinity-customizer' ) . '">';
			foreach ( $networks as $key => $data ) {
				printf(
					'<a class="infcl-icon" data-network="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer" aria-label="%3$s" title="%3$s"><span class="dashicons %4$s"></span></a>',
					esc_attr( $key ),
					esc_url( $data['url'] ),
					esc_attr( $data['label'] ),
					esc_attr( $data['icon'] )
				);
			}
			echo '</div>';

			// Déplace les icônes juste sous le formulaire / les liens de nav.
			$js .= 'var s=document.querySelector(".infcl-social");if(s){var n=document.querySelector("#nav");if(n&&n.parentNode){n.parentNode.insertBefore(s,n.nextSibling);}}';
		}
	}

	// ——— Copyright ———.
	if ( ! empty( $s['copyright_enable'] ) && '' !== trim( (string) $s['copyright_text'] ) ) {
		printf(
			'<div class="infcl-copyright">%s</div>',
			wp_kses_post( infcl_expand_copyright( $s['copyright_text'] ) )
		);
	}

	// ——— Personnalisation des liens (texte / cible) ———.
	$replacements = array();

	if ( '' !== trim( (string) $s['back_to_text'] ) || '' !== trim( (string) $s['back_to_url'] ) ) {
		$replacements[] = array(
			'selector' => '#backtoblog a',
			'href'     => trim( (string) $s['back_to_url'] ),
			'text'     => trim( (string) $s['back_to_text'] ),
		);
	}
	if ( '' !== trim( (string) $s['register_text'] ) ) {
		$replacements[] = array(
			'selector' => '#nav a[href*="action=register"]',
			'href'     => '',
			'text'     => trim( (string) $s['register_text'] ),
		);
	}

	if ( $replacements ) {
		$js .= 'var reps=' . wp_json_encode( $replacements ) . ';'
			. 'reps.forEach(function(r){var el=document.querySelector(r.selector);'
			. 'if(el){if(r.href){el.setAttribute("href",r.href);}if(r.text){el.textContent=r.text;}}});';
	}

	if ( $js ) {
		echo '<script>document.addEventListener("DOMContentLoaded",function(){' . $js . '});</script>';
	}
}
add_action( 'login_footer', 'infcl_login_footer', 20 );
