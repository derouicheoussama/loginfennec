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
 * Apparence de la page de connexion : CSS dynamique, logo, liens,
 * réseaux sociaux, copyright.
 *
 * @package LoginFennecPro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Construit le CSS de la page de connexion à partir des réglages.
 * Réutilisé par l'aperçu en direct du dashboard.
 *
 * @param array $s Réglages (lnf_settings()).
 * @return string CSS.
 */
function lnf_build_login_css( $s ) {
	$css = '';

	// ——— Layout 2 colonnes : actif seulement avec une image latérale ———.
	$two_col = ( 'two-column' === ( $s['layout'] ?? 'single' ) && ! empty( $s['side_image'] ) );

	// ——— Google Fonts ———.
	// Prend le pas sur la famille générique : ne s'applique que si une
	// police Google est renseignée (sinon le sélecteur « Police » du
	// dashboard serait sans effet).
	if ( ! empty( $s['font_google'] ) ) {
		$css .= sprintf(
			'body.login,body.login form .input,body.login #wp-submit{font-family:"%1$s",sans-serif !important;}',
			esc_html( $s['font_google'] )
		);
	}

	// ——— Layout 2 colonnes ———.
	if ( 'two-column' === ( $s['layout'] ?? 'single' ) && ! empty( $s['side_image'] ) ) {
		$css .= sprintf(
			'body.login #login{display:grid;grid-template-columns:1fr 1fr;width:%1$dpx !important;max-width:calc(100%% - 40px);border-radius:%2$dpx;overflow:hidden;}',
			(int) $s['form_width'] * 2,
			(int) $s['form_radius']
		);
		$css .= sprintf(
			'body.login #login::before{content:"";display:block;background:url(%s) center / cover no-repeat;min-height:400px;}',
			esc_url( $s['side_image'] )
		);
		$css .= 'body.login #login > *{grid-column:2;}';
	}

	// Mise en page : largeur du formulaire centrée.
	$css .= sprintf(
		'body.login #login{width:%1$dpx;max-width:calc(100%% - 40px);margin:8%% auto 0;padding-inline:0;box-sizing:border-box;}',
		(int) $s['form_width']
	);

	// ——— Layout 2 colonnes : grille + image latérale ———.
	if ( $two_col ) {
		$css .= 'body.login.lnf-two-col #login{grid-template-columns:1fr 1fr;gap:0;max-width:900px;border-radius:18px;overflow:hidden;}';
		$css .= 'body.login.lnf-two-col #login::before{content:"";display:block;background-size:cover;background-position:center;min-height:100%;}';
		$css .= 'body.login.lnf-two-col #login > *{grid-column:2;}';
		$css .= 'body.login.lnf-two-col #login form{border-radius:0;box-shadow:none;}';
		$css .= '@media (max-width:782px){body.login.lnf-two-col #login{grid-template-columns:1fr;max-width:calc(100% - 40px);}body.login.lnf-two-col #login::before{display:none;}}';
	}

	// ——— White-label : masque les marques sur la page de connexion ———.
	// (le CSS ne peut pas vivre dans admin.css : jamais chargé côté login).
	if ( ! empty( $s['white_label'] ) ) {
		$css .= 'body.lnf-white-label #backtoblog,body.lnf-white-label #nav a[href*="action=register"] .lnf-brand-mark,body.lnf-white-label .lnf-brand-logo{display:none !important;}';
	}

	// Éléments au-dessus des calques d'arrière-plan.
	$css .= 'body.login #login,body.login .lnf-social,body.login .lnf-copyright{position:relative;z-index:1;}';

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
			lnf_hex_to_rgba( $s['bg_overlay_color'], (int) $s['bg_overlay_opacity'] )
		);
	}

	// ——— Logo ———.
	if ( ! empty( $s['logo_hide'] ) ) {
		$css .= 'body.login #login h1{display:none;}';
	} else {
		if ( ! empty( $s['logo_text'] ) ) {
			// Logo texte : remplace l'image par un titre stylé.
			$css .= sprintf(
				'body.login #login h1 a{background:none !important;width:auto;height:auto;text-indent:0;font-size:28px;font-weight:700;line-height:1.25;color:%1$s;text-decoration:none;}',
				$s['text_color']
			);
		} elseif ( ! empty( $s['logo_url'] ) ) {
			$css .= sprintf(
				'body.login #login h1 a{background-image:url(%1$s);width:%2$dpx;height:%3$dpx;max-width:80vw;background-size:contain;background-position:center center;background-repeat:no-repeat;margin:0 auto 22px;}',
				wp_json_encode( esc_url_raw( $s['logo_url'] ) ),
				(int) $s['logo_width'],
				(int) $s['logo_height']
			);
		}
	}

	// ——— Formulaire (effet verre) ———.
	$form_bg  = lnf_hex_to_rgba( $s['form_bg'], (int) $s['form_opacity'] );
	$css     .= sprintf(
		'body.login #login form,body.login form{background:%1$s;-webkit-backdrop-filter:blur(%2$dpx);backdrop-filter:blur(%2$dpx);border-radius:%3$dpx;border:1px solid %4$s;padding:%5$dpx %5$dpx calc(%5$dpx - 6px);}',
		$form_bg,
		(int) $s['form_blur'],
		(int) $s['form_radius'],
		lnf_hex_to_rgba( $s['input_border'], 35 ),
		(int) $s['form_padding']
	);
	if ( ! empty( $s['form_shadow'] ) ) {
		$css .= 'body.login #login form,body.login form{box-shadow:0 24px 70px -18px rgba(0,0,0,.45);}';
	}

	// Textes et libellés.
	$css .= sprintf( 'body.login{color:%s;}', $s['text_color'] );
	// Variables partagées : le panneau « Connexion par SMS » hérite du design
	// (fond, couleur de texte) au lieu de ses valeurs de repli neutres.
	$css .= sprintf(
		'body.login{--lnf-form-bg:%1$s;--lnf-form-color:%2$s;}',
		lnf_hex_to_rgba( $s['form_bg'], (int) $s['form_opacity'] ),
		$s['text_color']
	);
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
		lnf_hex_to_rgba( $s['button_bg'], 30 )
	);
	if ( (int) $s['input_height'] > 0 ) {
		$css .= sprintf( 'body.login form .input{height:%dpx;}', (int) $s['input_height'] );
	}

	// Bouton principal — texte calculé automatiquement : jamais d'écriture
	// sombre sur un bouton sombre (ni claire sur clair), quel que soit le style.
	$css .= sprintf(
		'body.login #wp-submit{background:%1$s;border-color:%1$s;color:%2$s;border-radius:%3$dpx;height:40px;padding:0 18px;font-size:14px;}',
		$s['button_bg'],
		lnf_contrast_text( $s['button_bg'] ),
		(int) $s['button_radius']
	);
	$css .= sprintf(
		'body.login #wp-submit:hover,body.login #wp-submit:focus{background:%1$s;border-color:%1$s;color:%2$s;box-shadow:0 0 0 3px %3$s;}',
		$s['button_hover'],
		lnf_contrast_text( $s['button_hover'] ),
		lnf_hex_to_rgba( $s['button_hover'], 35 )
	);

	// Liens (nav, retour au site).
	$css .= sprintf(
		'body.login #nav a,body.login #backtoblog a,body.login .lnf-copyright a{color:%s;}',
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
		$icon_rgba = lnf_hex_to_rgba( $s['social_icon_bg'], (int) $s['social_icon_bg_opacity'] );
		$variant   = in_array( $s['social_variant'] ?? 'fill', array( 'fill', 'outline', 'plain', 'soft' ), true ) ? $s['social_variant'] : 'fill';

		$css .= sprintf(
			'.lnf-social{display:flex;justify-content:center;align-items:center;gap:10px;flex-wrap:wrap;margin:18px 0 0;}',
			(int) $s['social_size']
		);

		// Style d'icône : remplie / contour / simple / douce — chaque variante
		// reste compatible avec la forme (cercle, arrondi, carré).
		switch ( $variant ) {
			case 'outline':
				$css .= sprintf(
					'.lnf-social a.lnf-icon{width:%1$dpx;height:%1$dpx;border-radius:%2$s;display:inline-flex;align-items:center;justify-content:center;color:%3$s;background:transparent;border:2px solid %3$s;text-decoration:none;transition:transform .18s ease,background-color .18s ease,box-shadow .18s ease;}',
					(int) $s['social_size'],
					$radius,
					$s['social_icon_color']
				);
				$css .= sprintf(
					'.lnf-social a.lnf-icon .dashicons{font-size:%1$dpx;width:%1$dpx;height:%1$dpx;line-height:1;}',
					max( 14, (int) round( (int) $s['social_size'] * 0.55 ) )
				);
				$css .= sprintf(
					'.lnf-social a.lnf-icon:hover{transform:translateY(-2px);background:%1$s;color:%2$s;box-shadow:0 8px 20px -6px %3$s;}',
					lnf_hex_to_rgba( $s['social_icon_color'], 12 ),
					$s['social_icon_color'],
					lnf_hex_to_rgba( $s['social_icon_color'], 45 )
				);
				break;

			case 'plain':
				$css .= sprintf(
					'.lnf-social a.lnf-icon{width:%1$dpx;height:%1$dpx;border-radius:%2$s;display:inline-flex;align-items:center;justify-content:center;color:%3$s;background:transparent;border:0;text-decoration:none;transition:transform .18s ease,color .18s ease;}',
					(int) $s['social_size'],
					$radius,
					$s['social_icon_color']
				);
				$css .= sprintf(
					'.lnf-social a.lnf-icon .dashicons{font-size:%1$dpx;width:%1$dpx;height:%1$dpx;line-height:1;}',
					max( 18, (int) round( (int) $s['social_size'] * 0.66 ) )
				);
				$css .= sprintf(
					'.lnf-social a.lnf-icon:hover{transform:translateY(-2px) scale(1.06);color:%1$s;}',
					lnf_hex_to_rgba( $s['social_icon_color'], 78 )
				);
				break;

			case 'soft':
				$css .= sprintf(
					'.lnf-social a.lnf-icon{width:%1$dpx;height:%1$dpx;border-radius:%2$s;display:inline-flex;align-items:center;justify-content:center;color:%3$s;background:%4$s;border:0;text-decoration:none;transition:transform .18s ease,background-color .18s ease;}',
					(int) $s['social_size'],
					$radius,
					$s['social_icon_color'],
					lnf_hex_to_rgba( $s['social_icon_color'], 14 )
				);
				$css .= sprintf(
					'.lnf-social a.lnf-icon .dashicons{font-size:%1$dpx;width:%1$dpx;height:%1$dpx;line-height:1;}',
					max( 14, (int) round( (int) $s['social_size'] * 0.58 ) )
				);
				$css .= sprintf(
					'.lnf-social a.lnf-icon:hover{transform:translateY(-2px);background:%1$s;}',
					lnf_hex_to_rgba( $s['social_icon_color'], 26 )
				);
				break;

			case 'fill':
			default:
				$css .= sprintf(
					'.lnf-social a.lnf-icon{width:%1$dpx;height:%1$dpx;border-radius:%2$s;display:inline-flex;align-items:center;justify-content:center;color:%3$s;background:%4$s;text-decoration:none;transition:transform .18s ease,background-color .18s ease,box-shadow .18s ease;}',
					(int) $s['social_size'],
					$radius,
					$s['social_icon_color'],
					$icon_rgba
				);
				$css .= sprintf(
					'.lnf-social a.lnf-icon .dashicons{font-size:%1$dpx;width:%1$dpx;height:%1$dpx;line-height:1;}',
					max( 14, (int) round( (int) $s['social_size'] * 0.55 ) )
				);
				$css .= sprintf(
					'.lnf-social a.lnf-icon:hover{transform:translateY(-2px);background:%1$s;box-shadow:0 8px 20px -6px %2$s;color:%3$s;}',
					$s['social_icon_bg'],
					lnf_hex_to_rgba( $s['social_icon_bg'], 60 ),
					$s['social_icon_color']
				);
				break;
		}

		// Couleurs officielles des marques (priorité absolue sur les couleurs génériques).
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
					'.lnf-social a.lnf-icon[data-network="%1$s"],.lnf-social a.lnf-icon[data-network="%1$s"]:hover{background:%2$s !important;color:#fff !important;}',
					$network,
					$color
				);
			}
			// L'icône X est noire : un liseré discret la rend visible sur fond sombre.
			$css .= '.lnf-social a.lnf-icon[data-network="twitter"]{box-shadow:inset 0 0 0 1px rgba(255,255,255,.28) !important;}';
		}
	}

	// ——— Copyright ———.
	$css .= sprintf(
		'.lnf-copyright{margin-top:14px;text-align:center;font-size:12.5px;opacity:.92;letter-spacing:.2px;}',
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
		$names = array( 'fade' => 'lnf-fade', 'slide' => 'lnf-slide', 'zoom' => 'lnf-zoom' );
		$name  = isset( $names[ $s['anim'] ] ) ? $names[ $s['anim'] ] : 'lnf-fade';
		$css  .= '@keyframes lnf-fade{from{opacity:0}to{opacity:1}}'
			. '@keyframes lnf-slide{from{opacity:0;transform:translateY(26px)}to{opacity:1;transform:none}}'
			. '@keyframes lnf-zoom{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:none}}';
		$css  .= sprintf(
			'body.login #login h1 a{animation:%1$s .7s ease both;}body.login #login form{animation:%1$s .6s ease .05s both;}',
			$name
		);
		$css  .= '@media (prefers-reduced-motion: reduce){body.login #login h1 a,body.login #login form{animation:none !important;}}';
	}

	// ——— Extras : message d'accueil ———.
	if ( ! empty( $s['welcome_enable'] ) ) {
		$css .= '.lnf-welcome{margin:0 0 16px;text-align:center;}';
		$css .= sprintf( '.lnf-welcome h3{margin:0 0 6px;font-size:22px;line-height:1.25;color:%s;}', $s['text_color'] );
		$css .= sprintf( '.lnf-welcome p{margin:0;font-size:13.5px;color:%s;}', $s['label_color'] );
	}

	// ——— Thème d'interface du formulaire ———.
	$css .= lnf_form_theme_css( $s );

	// ——— CSS personnalisé ———.
	if ( '' !== trim( (string) $s['custom_css'] ) ) {
		$css .= str_replace( '<', '', (string) $s['custom_css'] ) . "\n";
	}

	// Messages d'erreur / info sur fond translucide.
	$css .= 'body.login #login_error,body.login .message,body.login #login .message{border-radius:10px;}';

	return $css;
}

/**
 * CSS du thème d'interface choisi pour le formulaire.
 * Ces règles passent après les réglages de couleur afin d'imposer le design.
 *
 * @param array $s Réglages.
 * @return string
 */
function lnf_form_theme_css( $s ) {
	$sel = 'body.login #login form,body.login form';
	$in  = 'body.login form .input';
	$btn = 'body.login #wp-submit';

	switch ( $s['form_theme'] ) {
		case 'classic':
			return sprintf(
				'%1$s{background:%2$s;-webkit-backdrop-filter:none;backdrop-filter:none;border:1px solid %3$s;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.1);}%4$s{border-radius:4px;}',
				$sel,
				$s['form_bg'],
				lnf_hex_to_rgba( $s['input_border'], 30 ),
				$in
			);
		case 'outline':
			return sprintf(
				'%1$s{background:transparent;-webkit-backdrop-filter:none;backdrop-filter:none;border:2px solid %2$s;border-radius:12px;box-shadow:none;}%3$s{background:%4$s;}',
				$sel,
				$s['input_border'],
				$in,
				lnf_hex_to_rgba( $s['input_bg'], 90 )
			);
		case 'pill':
			return sprintf(
				'%1$s{border-radius:26px;padding:%2$dpx %2$dpx calc(%2$dpx - 4px);}%3$s{border-radius:999px;}%4$s{border-radius:999px;width:100%%;}',
				$sel,
				min( 80, (int) $s['form_padding'] + 6 ),
				$in,
				$btn
			);
		case 'elevated':
			return sprintf(
				'%1$s{border-radius:26px;box-shadow:0 34px 70px -24px rgba(0,0,0,.55);border:none;padding:%2$dpx %2$dpx calc(%2$dpx - 4px);}%3$s{border:none;border-radius:10px;width:100%%;}',
				$sel,
				min( 80, (int) $s['form_padding'] + 8 ),
				$btn
			);
		case 'accent':
			return sprintf(
				'%1$s{border:none;border-top:6px solid %2$s;border-radius:12px;box-shadow:0 14px 34px -14px rgba(0,0,0,.4);}',
				$sel,
				$s['button_bg']
			);
		case 'minimal':
			return sprintf(
				'%1$s{background:transparent;-webkit-backdrop-filter:none;backdrop-filter:none;border:none;box-shadow:none;padding:8px 0;}'
				. '%2$s{background:transparent;border:none;border-bottom:2px solid %3$s;border-radius:0;color:%4$s;padding-left:0;padding-right:0;}'
				. '%2$s:focus{background:transparent;}'
				. '%5$s{width:100%%;}',
				$sel,
				$in,
				$s['input_border'],
				$s['input_color'],
				$btn
			);
		case 'glass':
		default:
			return '';
	}
}

/**
 * Réseaux sociaux configurés.
 *
 * @param array $s Réglages.
 * @return array tableau libellé => [ 'url', 'icon' ].
 */
function lnf_get_social_networks( $s ) {
	$networks = array(
		'facebook'  => array( 'icon' => 'dashicons-facebook-alt', 'label' => __( 'Facebook', 'loginfennec' ) ),
		'twitter'   => array( 'icon' => 'dashicons-twitter', 'label' => __( 'X (Twitter)', 'loginfennec' ) ),
		'instagram' => array( 'icon' => 'dashicons-instagram', 'label' => __( 'Instagram', 'loginfennec' ) ),
		'linkedin'  => array( 'icon' => 'dashicons-linkedin', 'label' => __( 'LinkedIn', 'loginfennec' ) ),
		'youtube'   => array( 'icon' => 'dashicons-youtube', 'label' => __( 'YouTube', 'loginfennec' ) ),
		'email'     => array( 'icon' => 'dashicons-email-alt', 'label' => __( 'E-mail', 'loginfennec' ) ),
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
function lnf_login_head() {
	// Mode sans échec : page de connexion native, aucune personnalisation.
	if ( function_exists( 'lnf_safe_mode' ) && lnf_safe_mode() ) {
		return;
	}
	// Essai expiré sans licence Pro : pas de personnalisation.
	if ( lnf_trial_is_locked() ) {
		return;
	}
	$s = lnf_settings();

	// Google Fonts.
	if ( ! empty( $s['font_google'] ) ) {
		$gf = str_replace( ' ', '+', $s['font_google'] );
		$weights = ! empty( $s['font_google_weight'] ) ? ':wght@' . $s['font_google_weight'] : '';
		$href    = 'https://fonts.googleapis.com/css2?family=' . rawurlencode( $gf ) . $weights . '&display=swap';
		printf( '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n" );
		printf( '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n" );
		// Chargement non bloquant : la page s'affiche immédiatement avec la
		// police de secours, la police Google arrive en arrière-plan.
		printf(
			'<link rel="stylesheet" id="lnf-google-font" href="%1$s" media="print" onload="this.media=\'all\';">' . "\n" .
			'<noscript><link rel="stylesheet" href="%1$s"></noscript>',
			esc_url( $href )
		);
	}

	// Rapidité : précharge l'image de fond avant le CSS (moins de flash visuel).
	if ( 'image' === $s['bg_type'] && ! empty( $s['bg_image'] ) ) {
		printf( '<link rel="preload" as="image" href="%s">' . "\n", esc_url( $s['bg_image'] ) );
	}
	echo '<style id="loginfennec">' . "\n";
	echo "/* ∞ INFINITY CODER — style généré par LoginFennec Pro\n";
	echo ' * Création originale de Derouiche Oussama — https://www.derouicheoussama.com' . "\n */\n";
	echo wp_strip_all_tags( lnf_build_login_css( $s ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS generated from escaped settings.
	echo "\n";
	echo "</style>\n";
}
add_action( 'login_head', 'lnf_login_head', 30 );

/**
 * Honeypot anti-robots : champ caché hors écran, rempli uniquement par les bots.
 */
function lnf_login_honeypot() {
	$s = lnf_settings();
	if ( empty( $s['sec_honeypot'] ) ) {
		return;
	}
	echo '<p class="lnf-hp" style="position:absolute!important;left:-9999px!important;top:-9999px!important;margin:0;" aria-hidden="true">'
		. '<label>' . esc_html__( 'Ne pas remplir ce champ', 'loginfennec' )
		. ' <input type="text" name="lnf_hp" value="" tabindex="-1" autocomplete="off"></label></p>';
}
add_action( 'login_form', 'lnf_login_honeypot' );

/**
 * SEO : la page de connexion ne doit jamais être indexée par les moteurs
 * (contenu dupliqué, fuite du nom du site dans les résultats).
 */
function lnf_seo_noindex() {
	$s = lnf_settings();
	if ( empty( $s['seo_noindex'] ) ) {
		return;
	}
	echo '<meta name="robots" content="noindex, nofollow">' . "\n";
}
add_action( 'login_head', 'lnf_seo_noindex', 1 );

/**
 * SEO : titre de l'onglet de la page de connexion personnalisable.
 * Jeton disponible : {site} (nom du site).
 *
 * @param string $title Titre par défaut.
 * @return string
 */
function lnf_seo_login_title( $title ) {
	$custom = trim( (string) lnf_get_option( 'seo_login_title' ) );
	if ( '' === $custom ) {
		return $title;
	}
	return str_replace( '{site}', get_bloginfo( 'name' ), $custom );
}
add_filter( 'login_title', 'lnf_seo_login_title' );

/**
 * Charge Dashicons (icônes sociales) sur la page de connexion,
 * uniquement lorsque les icônes sont activées (performance).
 */
function lnf_login_enqueue() {
	$s = lnf_settings();
	if ( ! empty( $s['social_enable'] ) ) {
		wp_enqueue_style( 'dashicons' );
	}
}
add_action( 'login_enqueue_scripts', 'lnf_login_enqueue' );

/**
 * Lien du logo.
 *
 * @return string
 */
function lnf_logo_url() {
	$s = lnf_settings();
	return ! empty( $s['logo_link'] ) ? $s['logo_link'] : home_url( '/' );
}
add_filter( 'login_headerurl', 'lnf_logo_url', 100 );

/**
 * Texte alternatif du logo.
 *
 * @return string
 */
function lnf_logo_text() {
	return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'lnf_logo_text', 100 );

/**
 * Message d'accueil personnalisé au-dessus du formulaire.
 *
 * @param string $message Message courant.
 * @return string
 */
function lnf_welcome_message( $message ) {
	$s = lnf_settings();
	if ( empty( $s['welcome_enable'] ) ) {
		return $message;
	}
	$title    = trim( (string) $s['welcome_title'] );
	$subtitle = trim( (string) $s['welcome_subtitle'] );
	if ( '' === $title && '' === $subtitle ) {
		return $message;
	}
	$html = '<div class="lnf-welcome">';
	if ( '' !== $title ) {
		$html .= '<h3>' . esc_html( $title ) . '</h3>';
	}
	if ( '' !== $subtitle ) {
		$html .= '<p>' . esc_html( $subtitle ) . '</p>';
	}
	$html .= '</div>';
	return $message . $html;
}
add_filter( 'login_message', 'lnf_welcome_message', 5 );

/**
 * Redirection personnalisée après connexion (si configurée et si WordPress
 * n'a pas déjà une destination explicite).
 *
 * @param string   $redirect_to           Destination calculée par WordPress.
 * @param string   $requested_redirect_to Destination demandée (paramètre redirect_to).
 * @param WP_User  $user                  Utilisateur connecté.
 * @return string
 */
function lnf_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
	if ( ! $user instanceof WP_User ) {
		return $redirect_to;
	}
	$s      = lnf_settings();
	$target = trim( (string) $s['login_redirect'] );
	if ( '' !== $target && '' === trim( (string) $requested_redirect_to ) ) {
		return $target;
	}
	return $redirect_to;
}
add_filter( 'login_redirect', 'lnf_login_redirect', 20, 3 );

/**
 * Message d'erreur générique (option « masquer les détails »).
 *
 * @param string $error Message d'erreur.
 * @return string
 */
function lnf_generic_login_error( $error ) {
	$s = lnf_settings();
	if ( empty( $s['sec_generic_error'] ) || Lnf_Login_Security::$lock_triggered ) {
		return $error;
	}
	return '<strong>' . esc_html__( 'Erreur', 'loginfennec' ) . '</strong> : '
		. esc_html__( 'Identifiants incorrects. Veuillez réessayer.', 'loginfennec' );
}
add_filter( 'login_errors', 'lnf_generic_login_error', 100 );

/**
 * Masque le sélecteur de langue.
 *
 * @return bool
 */
function lnf_hide_language_switcher() {
	$s = lnf_settings();
	return empty( $s['sec_hide_language_switcher'] );
}
add_filter( 'login_display_language_dropdown', 'lnf_hide_language_switcher', 100 );

/**
 * Pied de page : réseaux sociaux, copyright, ajustements de liens.
 */
function lnf_login_footer() {
	$s   = lnf_settings();
	$js  = '';

	// Signature visible dans le code source de la page.
	echo '<!-- ∞ INFINITY CODER | LoginFennec Pro — création originale de Derouiche Oussama | https://www.derouicheoussama.com -->' . "\n";

	// ——— Réseaux sociaux ———.
	if ( ! empty( $s['social_enable'] ) ) {
		$networks = lnf_get_social_networks( $s );
		if ( $networks ) {
			echo '<div class="lnf-social" aria-label="' . esc_attr__( 'Réseaux sociaux', 'loginfennec' ) . '">';
			foreach ( $networks as $key => $data ) {
				printf(
					'<a class="lnf-icon" data-network="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer" aria-label="%3$s" title="%3$s"><span class="dashicons %4$s"></span></a>',
					esc_attr( $key ),
					esc_url( $data['url'] ),
					esc_attr( $data['label'] ),
					esc_attr( $data['icon'] )
				);
			}
			echo '</div>';

			// Déplace les icônes juste sous le formulaire / les liens de nav.
			$js .= 'var s=document.querySelector(".lnf-social");if(s){var n=document.querySelector("#nav");if(n&&n.parentNode){n.parentNode.insertBefore(s,n.nextSibling);}}';
		}
	}

	// ——— Copyright ———.
	if ( ! empty( $s['copyright_enable'] ) && '' !== trim( (string) $s['copyright_text'] ) ) {
		printf(
			'<div class="lnf-copyright">%s</div>',
			wp_kses_post( lnf_expand_copyright( $s['copyright_text'] ) )
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

	// ——— Champs : placeholders, libellés, logo texte ———.
	$field_cfg = array();
	if ( '' !== trim( (string) $s['field_placeholder_user'] ) ) {
		$field_cfg['phUser'] = trim( (string) $s['field_placeholder_user'] );
	}
	if ( '' !== trim( (string) $s['field_placeholder_pass'] ) ) {
		$field_cfg['phPass'] = trim( (string) $s['field_placeholder_pass'] );
	}
	if ( '' !== trim( (string) $s['field_label_user'] ) ) {
		$field_cfg['lbUser'] = trim( (string) $s['field_label_user'] );
	}
	if ( '' !== trim( (string) $s['field_label_pass'] ) ) {
		$field_cfg['lbPass'] = trim( (string) $s['field_label_pass'] );
	}
	if ( '' !== trim( (string) $s['logo_text'] ) && empty( $s['logo_hide'] ) ) {
		$field_cfg['logoText'] = trim( (string) $s['logo_text'] );
	}
	if ( $field_cfg ) {
		$js .= 'var fc=' . wp_json_encode( $field_cfg ) . ';'
			. 'if(fc.phUser){var i=document.getElementById("user_login");if(i){i.placeholder=fc.phUser;}}'
			. 'if(fc.phPass){var i=document.getElementById("user_pass");if(i){i.placeholder=fc.phPass;}}'
			. 'if(fc.lbUser){var l=document.querySelector("label[for=user_login]");if(l){l.textContent=fc.lbUser;}}'
			. 'if(fc.lbPass){var l=document.querySelector("label[for=user_pass]");if(l){l.textContent=fc.lbPass;}}'
			. 'if(fc.logoText){var a=document.querySelector("#login h1 a");if(a){a.textContent=fc.logoText;}}';
	}

	if ( $js ) {
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — JS intentional, construit à partir de réglages échappés.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JS intentionnel.
	echo '<script>document.addEventListener("DOMContentLoaded",function(){' . $js . '});</script>';
	}

	// ——— JS personnalisé ———.
	if ( '' !== trim( (string) $s['custom_js'] ) ) {
		$custom_js = str_ireplace( '</script', '<\/script', (string) $s['custom_js'] );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JS utilisateur intentionnel, fermetures neutralisées.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JS utilisateur intentionnel.
	echo '<script id="loginfennec-custom">' . $custom_js . '</script>';
	}
}
add_action( 'login_footer', 'lnf_login_footer', 20 );
