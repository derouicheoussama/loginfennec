/**
 * ∞ INFINITY CODER — création originale de Derouiche Oussama
 * Plugin : LoginFennec Pro · https://www.derouicheoussama.com
 * Copyright © 2026 Derouiche Oussama. Licence GPL v2+ —
 * toute copie ou modification doit conserver cette signature.
 */
/**
 * LoginFennec Pro — dashboard.
 * Onglets, aperçu en direct (CSS injecté dans l'iframe), presets,
 * médiathèque, tunnel d'achat professionnel (pop-up), licence.
 */
(function ($) {
	'use strict';

	var cfg = window.LNF_ADMIN || {};
	var $form = $('#lnf-form');
	var $frame = $('#lnf-frame');
	var debounceTimer = null;
	var applyingPreset = false;

	/* ------------------------------------------------------------------
	 * Styles prédéfinis (thèmes visuels uniquement).
	 * ---------------------------------------------------------------- */
	var PRESETS = {
		glass: {
			bg_type: 'gradient', bg_color1: '#667eea', bg_color2: '#764ba2',
			bg_gradient_angle: 135, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 10,
			form_bg: '#ffffff', form_opacity: 22, form_blur: 18, form_radius: 18,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#ffffff', label_color: '#ffffff',
			input_bg: '#ffffff', input_color: '#2c3338', input_border: '#ffffff',
			button_bg: '#7c3aed', button_hover: '#6d28d9', button_radius: 10,
			link_color: '#ffffff'
		},
		minimal: {
			bg_type: 'color', bg_color1: '#f0f2f5', bg_color2: '#f0f2f5',
			bg_gradient_angle: 135, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 0,
			form_bg: '#ffffff', form_opacity: 100, form_blur: 0, form_radius: 12,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#1d2327', label_color: '#3c434a',
			input_bg: '#f6f7f7', input_color: '#2c3338', input_border: '#8c8f94',
			button_bg: '#2271b1', button_hover: '#135e96', button_radius: 6,
			link_color: '#50575e'
		},
		dark: {
			bg_type: 'gradient', bg_color1: '#0f172a', bg_color2: '#334155',
			bg_gradient_angle: 160, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 0,
			form_bg: '#111827', form_opacity: 88, form_blur: 10, form_radius: 14,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#e5e7eb', label_color: '#9ca3af',
			input_bg: '#1f2937', input_color: '#f3f4f6', input_border: '#374151',
			button_bg: '#6366f1', button_hover: '#4f46e5', button_radius: 10,
			link_color: '#9ca3af'
		},
		sunset: {
			bg_type: 'gradient', bg_color1: '#f97316', bg_color2: '#ec4899',
			bg_gradient_angle: 120, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 0,
			form_bg: '#ffffff', form_opacity: 18, form_blur: 16, form_radius: 20,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#ffffff', label_color: '#ffffff',
			input_bg: '#ffffff', input_color: '#374151', input_border: '#ffffff',
			button_bg: '#f43f5e', button_hover: '#e11d48', button_radius: 12,
			link_color: '#ffffff'
		},
		ocean: {
			bg_type: 'gradient', bg_color1: '#0ea5e9', bg_color2: '#2563eb',
			bg_gradient_angle: 135, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 0,
			form_bg: '#ffffff', form_opacity: 15, form_blur: 14, form_radius: 18,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#ffffff', label_color: '#ffffff',
			input_bg: '#ffffff', input_color: '#1e3a5f', input_border: '#ffffff',
			button_bg: '#06b6d4', button_hover: '#0891b2', button_radius: 12,
			link_color: '#ffffff'
		},
		forest: {
			bg_type: 'gradient', bg_color1: '#059669', bg_color2: '#065f46',
			bg_gradient_angle: 135, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 0,
			form_bg: '#ffffff', form_opacity: 16, form_blur: 14, form_radius: 18,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#ffffff', label_color: '#ffffff',
			input_bg: '#ffffff', input_color: '#14532d', input_border: '#ffffff',
			button_bg: '#10b981', button_hover: '#059669', button_radius: 12,
			link_color: '#ffffff'
		},
		neon: {
			bg_type: 'gradient', bg_color1: '#0f0c29', bg_color2: '#302b63',
			bg_gradient_angle: 135, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 0,
			form_bg: '#14101f', form_opacity: 78, form_blur: 12, form_radius: 16,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#e2e8f0', label_color: '#a5b4fc',
			input_bg: '#1e1b33', input_color: '#e2e8f0', input_border: '#4c1d95',
			button_bg: '#d946ef', button_hover: '#c026d3', button_radius: 10,
			link_color: '#a5b4fc'
		},
		sakura: {
			bg_type: 'gradient', bg_color1: '#ee9ca7', bg_color2: '#ffdde1',
			bg_gradient_angle: 120, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 0,
			form_bg: '#ffffff', form_opacity: 88, form_blur: 6, form_radius: 20,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#6b3a4b', label_color: '#8d5b6e',
			input_bg: '#ffffff', input_color: '#4a2c3a', input_border: '#e5b8c6',
			button_bg: '#d6587f', button_hover: '#bd4068', button_radius: 14,
			link_color: '#8d5b6e'
		},
		mono: {
			bg_type: 'gradient', bg_color1: '#9ca3af', bg_color2: '#374151',
			bg_gradient_angle: 160, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 0,
			form_bg: '#ffffff', form_opacity: 96, form_blur: 0, form_radius: 8,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#111827', label_color: '#374151',
			input_bg: '#f9fafb', input_color: '#111827', input_border: '#9ca3af',
			button_bg: '#111827', button_hover: '#000000', button_radius: 6,
			link_color: '#4b5563'
		},
		royal: {
			bg_type: 'gradient', bg_color1: '#141e30', bg_color2: '#243b55',
			bg_gradient_angle: 150, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 10,
			form_bg: '#ffffff', form_opacity: 14, form_blur: 16, form_radius: 16,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#ffffff', label_color: '#d9e4f5',
			input_bg: '#ffffff', input_color: '#2c3338', input_border: '#ffffff',
			button_bg: '#c9a227', button_hover: '#a3851c', button_radius: 10,
			link_color: '#cfe0f5'
		},
		coffee: {
			bg_type: 'gradient', bg_color1: '#3e2723', bg_color2: '#795548',
			bg_gradient_angle: 135, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 8,
			form_bg: '#ffffff', form_opacity: 18, form_blur: 10, form_radius: 16,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#ffffff', label_color: '#efe5dc',
			input_bg: '#ffffff', input_color: '#2c3338', input_border: '#ffffff',
			button_bg: '#6d4c41', button_hover: '#4e342e', button_radius: 10,
			link_color: '#f5c26b'
		},
		mint: {
			bg_type: 'gradient', bg_color1: '#0ba360', bg_color2: '#3cba92',
			bg_gradient_angle: 135, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 5,
			form_bg: '#ffffff', form_opacity: 20, form_blur: 14, form_radius: 18,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#ffffff', label_color: '#eafff5',
			input_bg: '#ffffff', input_color: '#2c3338', input_border: '#ffffff',
			button_bg: '#046a38', button_hover: '#03532b', button_radius: 10,
			link_color: '#ffffff'
		},
		berry: {
			bg_type: 'gradient', bg_color1: '#c31432', bg_color2: '#8e0e3f',
			bg_gradient_angle: 120, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 8,
			form_bg: '#ffffff', form_opacity: 20, form_blur: 14, form_radius: 18,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#ffffff', label_color: '#ffe3ec',
			input_bg: '#ffffff', input_color: '#2c3338', input_border: '#ffffff',
			button_bg: '#a50f3c', button_hover: '#7c0a2e', button_radius: 10,
			link_color: '#ffd6e4'
		},
		gold: {
			bg_type: 'gradient', bg_color1: '#8e7028', bg_color2: '#d4b94e',
			bg_gradient_angle: 135, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 0,
			form_bg: '#ffffff', form_opacity: 88, form_blur: 0, form_radius: 16,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#3d2f0f', label_color: '#6b5a24',
			input_bg: '#ffffff', input_color: '#2c3338', input_border: '#d9c98f',
			button_bg: '#8a6d1f', button_hover: '#6e5616', button_radius: 10,
			link_color: '#6e5616'
		},
		midnight: {
			bg_type: 'gradient', bg_color1: '#0f2027', bg_color2: '#2c5364',
			bg_gradient_angle: 160, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 8,
			form_bg: '#ffffff', form_opacity: 16, form_blur: 16, form_radius: 18,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#ffffff', label_color: '#cfe8f5',
			input_bg: '#ffffff', input_color: '#2c3338', input_border: '#ffffff',
			button_bg: '#2c5364', button_hover: '#1b3a47', button_radius: 10,
			link_color: '#9fd8ef'
		},
		coral: {
			bg_type: 'gradient', bg_color1: '#ff9966', bg_color2: '#ff5e62',
			bg_gradient_angle: 120, bg_image: '', bg_blur: 0,
			bg_overlay_color: '#000000', bg_overlay_opacity: 0,
			form_bg: '#ffffff', form_opacity: 24, form_blur: 12, form_radius: 20,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#ffffff', label_color: '#fff0e8',
			input_bg: '#ffffff', input_color: '#2c3338', input_border: '#ffffff',
			button_bg: '#e8555a', button_hover: '#c74045', button_radius: 12,
			link_color: '#ffffff'
		}
	};

	/* ------------------------------------------------------------------
	 * Aperçu en direct.
	 * ---------------------------------------------------------------- */

	function frameDoc() {
		try {
			return $frame[0].contentDocument;
		} catch (e) {
			return null;
		}
	}

	function collect() {
		var data = { action: cfg.previewAction, nonce: cfg.nonce };
		$form.find('input, select, textarea').each(function () {
			var name = this.name;
			if (!name || name.indexOf('lnf[') !== 0) {
				return;
			}
			if (this.type === 'checkbox') {
				data[name] = this.checked ? '1' : '0';
				return;
			}
			if (this.type === 'radio' && !this.checked) {
				return;
			}
			data[name] = $(this).val();
		});
		return data;
	}

	function applyCss(css) {
		var doc = frameDoc();
		if (!doc || !doc.head) {
			return;
		}
		var tag = doc.getElementById('lnf-live');
		if (!tag) {
			tag = doc.createElement('style');
			tag.id = 'lnf-live';
			doc.head.appendChild(tag);
		}
		tag.textContent = css;
	}

	function applyDomTweaks() {
		var doc = frameDoc();
		if (!doc) {
			return;
		}
		function field(key) {
			// Pour une case à cocher, viser la checkbox elle-même : une
			// sentinelle hidden « 0 » (value de secours) précède toujours
			// la case dans le DOM et .first() tomberait dessus.
			var $cb = $form.find('[name="lnf[' + key + ']"]').filter(':checkbox');
			if ($cb.length) {
				return $cb;
			}
			return $form.find('[name="lnf[' + key + ']"]').first();
		}
		function checked(key) {
			var $el = field(key);
			return $el.length ? $el.prop('checked') : null;
		}
		function show(selector, visible) {
			var el = doc.querySelector(selector);
			if (el) {
				el.style.display = visible ? '' : 'none';
			}
		}

		show('#nav a[href*="action=lostpassword"]', !checked('hide_lost_password'));
		show('#nav a[href*="action=register"]', !checked('hide_register'));
		show('#backtoblog', !checked('hide_back_to'));

		var socialOn = checked('social_enable');
		show('.lnf-social', !!socialOn);

		// Icônes sociales en temps réel : chaque URL saisie crée/met à jour
		// son icône dans l'aperçu, une URL vide la masque — sans enregistrer.
		var SOCIAL_NETWORKS = {
			facebook:  { icon: 'dashicons-facebook-alt', label: 'Facebook' },
			twitter:   { icon: 'dashicons-twitter', label: 'X (Twitter)' },
			instagram: { icon: 'dashicons-instagram', label: 'Instagram' },
			linkedin:  { icon: 'dashicons-linkedin', label: 'LinkedIn' },
			youtube:   { icon: 'dashicons-youtube', label: 'YouTube' },
			email:     { icon: 'dashicons-email-alt', label: 'E-mail' }
		};
		var socialWrap = doc.querySelector('.lnf-social');
		if (socialOn && socialWrap) {
			Object.keys(SOCIAL_NETWORKS).forEach(function (key) {
				var url = String(field('social_' + key).val() || '').trim();
				var a = socialWrap.querySelector('.lnf-icon[data-network="' + key + '"]');
				if (!url) {
					if (a) { a.style.display = 'none'; }
					return;
				}
				if (!a) {
					a = doc.createElement('a');
					a.className = 'lnf-icon';
					a.setAttribute('data-network', key);
					a.setAttribute('target', '_blank');
					a.setAttribute('rel', 'noopener noreferrer');
					a.innerHTML = '<span class="dashicons ' + SOCIAL_NETWORKS[key].icon + '"></span>';
					socialWrap.appendChild(a);
				}
				a.style.display = '';
				a.href = (key === 'email' && url.indexOf('mailto:') !== 0) ? 'mailto:' + url : url;
				a.title = SOCIAL_NETWORKS[key].label;
				a.setAttribute('aria-label', SOCIAL_NETWORKS[key].label);
			});
		}

		var cp = doc.querySelector('.lnf-copyright');
		if (cp) {
			if (checked('copyright_enable')) {
				var tpl = String(field('copyright_text').val() || '');
				cp.textContent = tpl
					.replace('{year}', String(new Date().getFullYear()))
					.replace('{sitename}', cfg.sitename || '');
				cp.style.display = '';
			} else {
				cp.style.display = 'none';
			}
		}

		// ——— Classes du body : disposition deux colonnes, white-label ———.
		var bodyEl = doc.body;
		if (bodyEl) {
			var isTwoCol = (field('layout').val() === 'two-column') && String(field('side_image').val() || '') !== '';
			bodyEl.classList.toggle('lnf-two-col', isTwoCol);
			bodyEl.classList.toggle('lnf-white-label', !!checked('white_label'));
		}

		// ——— Message de bienvenue : créé/mis à jour/supprimé en direct ———.
		var welcomeOn = checked('welcome_enable');
		var wTitle = String(field('welcome_title').val() || '').trim();
		var wSub = String(field('welcome_subtitle').val() || '').trim();
		var welcome = welcomeOn && (wTitle !== '' || wSub !== '');
		var wEl = doc.querySelector('.lnf-welcome');
		if (!welcome) {
			if (wEl) { wEl.remove(); }
		} else {
			if (!wEl) {
				wEl = doc.createElement('div');
				wEl.className = 'lnf-welcome';
				var target = doc.querySelector('#login form') || doc.querySelector('#login');
				if (target && target.parentNode) {
					target.parentNode.insertBefore(wEl, target);
				} else {
					doc.body.appendChild(wEl);
				}
			}
			var h3 = wEl.querySelector('h3');
			var p = wEl.querySelector('p');
			if (wTitle !== '') {
				if (!h3) { h3 = doc.createElement('h3'); wEl.insertBefore(h3, wEl.firstChild); }
				h3.textContent = wTitle;
			} else if (h3) {
				h3.remove();
			}
			if (wSub !== '') {
				if (!p) { p = doc.createElement('p'); wEl.appendChild(p); }
				p.textContent = wSub;
			} else if (p) {
				p.remove();
			}
		}

		// ——— Copyright : créé/mis à jour/supprimé en direct ———.
		var cpOn = checked('copyright_enable');
		var cp = doc.querySelector('.lnf-copyright');
		var cpText = String(field('copyright_text').val() || '')
			.replace('{year}', String(new Date().getFullYear()))
			.replace('{sitename}', cfg.sitename || '');
		if (!cpOn || cpText === '') {
			if (cp) { cp.style.display = 'none'; }
		} else {
			if (!cp) {
				cp = doc.createElement('div');
				cp.className = 'lnf-copyright';
				var anchorEl = doc.querySelector('.lnf-social') || doc.querySelector('#nav');
				if (anchorEl && anchorEl.parentNode) {
					anchorEl.parentNode.insertBefore(cp, anchorEl.nextSibling);
				} else if (doc.querySelector('#login')) {
					doc.querySelector('#login').appendChild(cp);
				}
			}
			cp.textContent = cpText;
			cp.style.display = '';
		}

		// ——— Champs : placeholders et libellés en direct ———.
		var userLogin = doc.querySelector('#user_login');
		if (userLogin) {
			userLogin.placeholder = String(field('field_placeholder_user').val() || '');
		}
		var userPass = doc.querySelector('#user_pass');
		if (userPass) {
			userPass.placeholder = String(field('field_placeholder_pass').val() || '');
		}
		var labelUser = doc.querySelector('label[for="user_login"]');
		var labelUserTxt = String(field('field_label_user').val() || '').trim();
		if (labelUser && labelUserTxt !== '') {
			labelUser.textContent = labelUserTxt;
		}
		var labelPass = doc.querySelector('label[for="user_pass"]');
		var labelPassTxt = String(field('field_label_pass').val() || '').trim();
		if (labelPass && labelPassTxt !== '') {
			labelPass.textContent = labelPassTxt;
		}

		// ——— Liens : textes et cibles en direct ———.
		var backLink = doc.querySelector('#backtoblog a');
		var backTxt = String(field('back_to_text').val() || '').trim();
		var backUrl = String(field('back_to_url').val() || '').trim();
		if (backLink) {
			if (backTxt !== '') { backLink.textContent = backTxt; }
			if (backUrl !== '') { backLink.href = backUrl; }
		}
		var regLink = doc.querySelector('#nav a[href*="action=register"]');
		var regTxt = String(field('register_text').val() || '').trim();
		if (regLink && regTxt !== '') {
			regLink.textContent = regTxt;
		}

		// ——— Police Google : met le <link> de l'aperçu en harmonie ———.
		var fg = String(field('font_google').val() || '').trim();
		var fgw = String(field('font_google_weight').val() || '').trim();
		var headEl = doc.head;
		if (headEl) {
			var gfLink = headEl.querySelector('#lnf-gf-live');
			if (fg !== '') {
				var gfHref = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(fg).replace(/%20/g, '+') + (fgw !== '' ? ':wght@' + fgw : '') + '&display=swap';
				if (!gfLink) {
					gfLink = doc.createElement('link');
					gfLink.id = 'lnf-gf-live';
					gfLink.rel = 'stylesheet';
					headEl.appendChild(gfLink);
				}
				if (gfLink.href !== gfHref) {
					gfLink.href = gfHref;
				}
			} else if (gfLink) {
				gfLink.remove();
			}
		}
	}

	function updatePreview() {
		if (!$frame.length) {
			return; // Page installateur : pas d'aperçu.
		}
		// Aperçu en chargement différé : déclenche le chargement et attend
		// l'événement load (l'injection CSS sur about:blank serait perdue).
		if ('about:blank' === $frame.attr('src') || !$frame.attr('src')) {
			loadPreviewOnce();
			$frame.off('load.lnfonce').one('load.lnfonce', function () {
				updatePreview();
			});
			return;
		}
		$.post(cfg.ajaxUrl, collect())
			.done(function (res) {
				if (res && res.success && res.data && res.data.css) {
					applyCss(res.data.css);
					applyDomTweaks();
				}
			})
			.fail(function () {
				// Requête d'aperçu manquée (réseau/timeout) : silencieux, la suivante repartira.
			});
	}

	function schedulePreview() {
		window.clearTimeout(debounceTimer);
		debounceTimer = window.setTimeout(updatePreview, 350);
	}

	/* ------------------------------------------------------------------
	 * Affichage conditionnel + valeurs des curseurs.
	 * ---------------------------------------------------------------- */

	function refreshShowIf() {
		$('[data-showif]').each(function () {
			var cond;
			try {
				cond = JSON.parse($(this).attr('data-showif'));
			} catch (e) {
				return;
			}
			var visible = true;
			$.each(cond, function (key, expected) {
				// Case à cocher : lire la checkbox, pas la sentinelle hidden « 0 ».
				var $el = $form.find('[name="lnf[' + key + ']"]').filter(':checkbox');
				if (!$el.length) {
					$el = $form.find('[name="lnf[' + key + ']"]').first();
				}
				var current;
				if (!$el.length) {
					visible = false;
					return;
				}
				if ($el.is(':checkbox')) {
					current = $el.prop('checked') ? '1' : '0';
				} else {
					current = String($el.val() || '');
				}
				if (current !== String(expected)) {
					visible = false;
				}
			});
			$(this).toggleClass('is-hidden', !visible);
		});
	}

	function refreshOutputs() {
		$form.find('input[type=range]').each(function () {
			var $out = $(this).closest('.lnf-range-row').find('output');
			$out.text(this.value + ($out.data('unit') || ''));
		});
	}

	/* ------------------------------------------------------------------
	 * Événements.
	 * ---------------------------------------------------------------- */

	$form.on('input change', 'input, select, textarea', function () {
		if (!applyingPreset && this.name && this.name !== 'lnf[preset]') {
			$('[name="lnf[preset]"]').val('custom');
			$('.lnf-preset').removeClass('is-active');
		}
		refreshShowIf();
		schedulePreview();
	});

	$form.on('input change', 'input[type=range]', function () {
		var $out = $(this).closest('.lnf-range-row').find('output');
		$out.text(this.value + ($out.data('unit') || ''));
	});

	/* Onglets */
	$(document).on('click', '.lnf-tab', function () {
		var tab = $(this).data('tab');
		$('.lnf-tab').removeClass('is-active');
		$(this).addClass('is-active');
		$('.lnf-panel').removeClass('is-active');
		$('.lnf-panel[data-panel="' + tab + '"]').addClass('is-active');
		if (window.history && window.history.replaceState) {
			window.history.replaceState(null, '', '#' + tab);
		}
	});

	$(document).on('click', '[data-goto]', function () {
		var $tab = $('.lnf-tab[data-tab="' + $(this).data('goto') + '"]');
		if ($tab.length) {
			$tab.trigger('click');
			window.scrollTo({ top: 0, behavior: 'smooth' });
		}
	});

	/* Onglet initial (ancre) */
	var initial = (window.location.hash || '#dashboard').slice(1);
	var $initialTab = $('.lnf-tab[data-tab="' + initial + '"]');
	if ($initialTab.length) {
		$initialTab.trigger('click');
	}

	/* Presets */
	$(document).on('click', '.lnf-preset', function () {
		var key = $(this).data('preset');
		var values = PRESETS[key];
		if (!values) {
			// Style inconnu du navigateur : on active la carte sans écraser les couleurs.
			$('.lnf-preset').removeClass('is-active');
			$(this).addClass('is-active');
			$('[name="lnf[preset]"]').val(key);
			updatePreview();
			return;
		}
		applyingPreset = true;
		$.each(values, function (fieldKey, value) {
			var $el = $form.find('[name="lnf[' + fieldKey + ']"]').first();
			if (!$el.length) {
				return;
			}
			if ($el.is(':checkbox')) {
				$el.prop('checked', value === 1 || value === true);
			} else if ($el.hasClass('lnf-color') && $.fn.wpColorPicker) {
				$el.wpColorPicker('color', value);
			} else {
				$el.val(value);
			}
		});
		$('[name="lnf[preset]"]').val(key);
		$('.lnf-preset').removeClass('is-active');
		$(this).addClass('is-active');
		applyingPreset = false;

		refreshShowIf();
		refreshOutputs();
		updatePreview();
	});

	/* Palettes de l'admin (onglet Admin) */
	var ADMIN_PRESETS = {
		nuit:   { admin_bg: '#00305e', admin_text: '#f9f0d8', admin_hover_bg: '#0a427f', admin_hover_text: '#f2a444', admin_active_bg: '#e88018', admin_active_text: '#ffffff', admin_accent: '#e88018', adminbar_bg: '#00234a', adminbar_text: '#f9f0d8', adminbar_hover: '#f2a444' },
		desert: { admin_bg: '#2f2417', admin_text: '#f0e6d2', admin_hover_bg: '#43321f', admin_hover_text: '#f5c26b', admin_active_bg: '#c2762b', admin_active_text: '#ffffff', admin_accent: '#e08b3d', adminbar_bg: '#241b10', adminbar_text: '#f0e6d2', adminbar_hover: '#f5c26b' },
		ocean:  { admin_bg: '#0f2838', admin_text: '#cfe6f5', admin_hover_bg: '#16405a', admin_hover_text: '#6fc3ff', admin_active_bg: '#2271b1', admin_active_text: '#ffffff', admin_accent: '#38a3e0', adminbar_bg: '#0b1e2b', adminbar_text: '#cfe6f5', adminbar_hover: '#6fc3ff' },
		clair:  { admin_bg: '#ffffff', admin_text: '#2c3338', admin_hover_bg: '#e8eaec', admin_hover_text: '#2271b1', admin_active_bg: '#2271b1', admin_active_text: '#ffffff', admin_accent: '#2271b1', adminbar_bg: '#1d2327', adminbar_text: '#c3c4c7', adminbar_hover: '#72aee6' }
	};
	$(document).on('click', '.lnf-admin-preset', function () {
		var values = ADMIN_PRESETS[$(this).data('lnf-admin-preset')];
		if (!values) {
			return;
		}
		if (!$('[name="lnf[admin_enable]"]').prop('checked')) {
			$('[name="lnf[admin_enable]"]').prop('checked', true).trigger('change');
		}
		$.each(values, function (fieldKey, value) {
			var $el = $form.find('[name="lnf[' + fieldKey + ']"]').first();
			if (!$el.length) {
				return;
			}
			if ($el.hasClass('lnf-color') && $.fn.wpColorPicker) {
				$el.wpColorPicker('color', value);
			} else {
				$el.val(value);
			}
		});
		$('.lnf-admin-preset').removeClass('is-active');
		$(this).addClass('is-active');
		refreshShowIf();
		scheduleAdminPreview();
	});

	/* Aperçu temps réel des couleurs de l'admin : le CSS généré côté serveur
	   est injecté dans la page en cours, le vrai menu se restyle instantanément. */
	var ADMIN_COLOR_KEYS = ['admin_bg', 'admin_text', 'admin_hover_bg', 'admin_hover_text', 'admin_active_bg', 'admin_active_text', 'admin_accent', 'adminbar_bg', 'adminbar_text', 'adminbar_hover'];
	var adminPreviewTimer = null;
	var $adminPreviewStyle = null;

	function scheduleAdminPreview() {
		window.clearTimeout(adminPreviewTimer);
		adminPreviewTimer = window.setTimeout(applyAdminPreview, 250);
	}

	function applyAdminPreview() {
		var data = {
			action: 'lnf_admin_colors_preview',
			nonce: cfg.nonce,
			admin_enable: $('[name="lnf[admin_enable]"]').prop('checked') ? '1' : '0'
		};
		$.each(ADMIN_COLOR_KEYS, function (i, key) {
			data[key] = $form.find('[name="lnf[' + key + ']"]').first().val() || '';
		});
		$.post(cfg.ajaxUrl, data).done(function (res) {
			if (!res || !res.success) {
				return;
			}
			if (!$adminPreviewStyle || !$adminPreviewStyle.length) {
				$adminPreviewStyle = $('<style>').attr('id', 'lnf-admin-preview').appendTo('head');
			}
			$adminPreviewStyle.text(res.data && res.data.css ? res.data.css : '');
		});
	}

	$(document).on('change', '[name="lnf[admin_enable]"]', function () {
		if (!this.checked && $adminPreviewStyle) {
			$adminPreviewStyle.text('');
			return;
		}
		scheduleAdminPreview();
	});
	$(document).on('input', '[name^="lnf[admin_"]', function () {
		scheduleAdminPreview();
	});

	/* Sélecteur de couleur natif (iris) */
	if ($.fn.wpColorPicker) {
		$('.lnf-color').wpColorPicker({
			change: function () {
				schedulePreview();
				if (this.name && this.name.indexOf('lnf[admin_') === 0) {
					scheduleAdminPreview();
				}
			},
			clear: function () {
				window.setTimeout(updatePreview, 60);
				if (this.name && this.name.indexOf('lnf[admin_') === 0) {
					scheduleAdminPreview();
				}
			}
		});
	}

	/* Médiathèque */
	var mediaFrame = null;
	$(document).on('click', '.lnf-media-pick', function (e) {
		e.preventDefault();
		var $wrap = $(this).closest('.lnf-media');
		if (!mediaFrame) {
			mediaFrame = window.wp.media({
				title: 'Choisir une image',
				multiple: false,
				library: { type: 'image' }
			});
		}
		mediaFrame.off('select').on('select', function () {
			var attachment = mediaFrame.state().get('selection').first().toJSON();
			var url = (attachment.sizes && attachment.sizes.full) ? attachment.sizes.full.url : attachment.url;
			$wrap.find('input[type=url]').val(url).trigger('change');
			$wrap.find('.lnf-media-thumb').attr('src', url).removeAttr('hidden');
		});
		mediaFrame.open();
	});

	$(document).on('click', '.lnf-media-clear', function (e) {
		e.preventDefault();
		var $wrap = $(this).closest('.lnf-media');
		$wrap.find('input[type=url]').val('').trigger('change');
		$wrap.find('.lnf-media-thumb').attr('src', '').attr('hidden', '');
	});

	/* Aperçu : appareils, rechargement */
	$(document).on('click', '.lnf-device', function () {
		$('.lnf-device').removeClass('is-active');
		$(this).addClass('is-active');
		var width = parseInt($(this).data('width'), 10);
		$frame.css('width', width > 0 ? width + 'px' : '100%');
	});

	$(document).on('click', '.lnf-refresh', function () {
		$frame[0].src = $frame.attr('data-src') || $frame[0].src;
	});

	// Perf : l'aperçu (page entière wp-login) ne se charge qu'à l'affichage
	// du panneau — le dashboard ne télécharge plus deux pages à l'ouverture.
	function loadPreviewOnce() {
		if ($frame.attr('src') && 'about:blank' !== $frame.attr('src')) {
			return;
		}
		$frame.attr('src', $frame.attr('data-src') || '');
	}
	if ('IntersectionObserver' in window) {
		var previewSeen = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					loadPreviewOnce();
					previewSeen.disconnect();
				}
			});
		}, { rootMargin: '200px' });
		previewSeen.observe(document.getElementById('lnf-frame') || document.body);
	} else {
		loadPreviewOnce();
	}

	/* Aperçu plein écran */
	function exitFullscreenPreview() {
		$('.lnf-preview').removeClass('is-fullscreen');
		$('.lnf-expand').removeClass('is-active');
		$('body').removeClass('lnf-preview-lock');
	}

	$(document).on('click', '.lnf-expand', function () {
		var $preview = $('.lnf-preview');
		var fullscreen = $preview.toggleClass('is-fullscreen').hasClass('is-fullscreen');
		$(this).toggleClass('is-active', fullscreen);
		$('body').toggleClass('lnf-preview-lock', fullscreen);
	});

	$(document).on('keydown', function (e) {
		if (e.key === 'Escape') {
			exitFullscreenPreview();
			closeCheckoutWizard();
		}
	});

	/* Thèmes d'interface du formulaire */
	$(document).on('click', '.lnf-theme-card', function () {
		$('.lnf-theme-card').removeClass('is-active');
		$(this).addClass('is-active');
		$('[name="lnf[form_theme]"]').val($(this).data('theme'));
		schedulePreview();
	});

	/* Packs : bascule Annuelle / À vie */
	var currentBilling = 'yearly';
	$(document).on('click', '.lnf-bill-btn', function () {
		$('.lnf-bill-btn').removeClass('is-active');
		$(this).addClass('is-active');
		currentBilling = $(this).data('billing');
		$('.lnf-price').each(function () {
			var v = $(this).data(currentBilling);
			if (v) { $(this).text(v); }
		});
		$('.lnf-per, .lnf-updates').each(function () {
			var v = $(this).data(currentBilling);
			if (v) { $(this).text(v); }
		});
		updatePaySummary();
	});

	/* Achat : ouverture du tunnel professionnel (pop-up) */
	var selectedPack = 'site1';

	function fmtDA(n) {
		return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
	}
	function payAmountDA() {
		return (cfg.prices && cfg.prices[selectedPack]) ? cfg.prices[selectedPack][currentBilling] : 0;
	}
	function payLabel() {
		return (cfg.planLabels && cfg.planLabels[selectedPack]) ? cfg.planLabels[selectedPack] : 'Pro';
	}
	function payBillingLabel() {
		return currentBilling === 'lifetime' ? 'À vie' : 'Annuelle';
	}

	function updatePaySummary() {
		var amount = payAmountDA();
		$('#lnf-pay-pack-label, #lnf-wz-pack, #lnf-wz-pack-label').text(payLabel());
		$('#lnf-pay-billing-label, #lnf-wz-billing, #lnf-wz-billing-label').text(payBillingLabel());
		$('#lnf-pay-amount').text(fmtDA(amount) + ' DA');
		$('#lnf-wz-amount-da, #lnf-ccp-amount').text(fmtDA(amount) + ' DA');
		var usd = Math.round(amount * (parseFloat(cfg.paypalRate) || 0) * 100) / 100;
		$('#lnf-wz-amount-usd, #lnf-paypal-amount').text(usd + ' ' + cfg.paypalCurrency);
		if (cfg.paypalEmail) {
			var returnUrl = cfg.proUrl + (cfg.proUrl.indexOf('?') > -1 ? '&' : '?') + 'lnf-paid=1';
			var payUrl = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick'
				+ '&business=' + encodeURIComponent(cfg.paypalEmail)
				+ '&item_name=' + encodeURIComponent('LoginFennec Pro — ' + payLabel() + ' (' + payBillingLabel() + ')')
				+ '&amount=' + usd
				+ '&currency_code=' + cfg.paypalCurrency
				+ '&no_shipping=1'
				+ '&rm=2'
				+ '&return=' + encodeURIComponent(returnUrl)
				+ '&cancel_return=' + encodeURIComponent(cfg.proUrl)
				+ '&custom=' + encodeURIComponent(cfg.siteUrl || '');
			$('#lnf-wz-paypal-link, #lnf-paypal-link').attr('href', payUrl);
		}
		var cardUrl = cfg.checkoutUrl + (cfg.checkoutUrl.indexOf('?') > -1 ? '&' : '?') + 'pack=' + selectedPack + '&billing=' + currentBilling + '&site=' + encodeURIComponent(cfg.siteUrl || '');
		window.lnfCardUrl = cardUrl;
		$('#lnf-card-open, #lnf-wz-card-open').attr('data-checkout', cardUrl);
		var proof = 'Bonjour,\n\nJe viens de payer ' + fmtDA(amount) + ' DA pour ' + payLabel() + ' (' + payBillingLabel() + ') sur le site ' + (cfg.siteUrl || '') + '.\n\nVoici ma capture de reçu BaridiMob/CCP :\n';
		$('.lnf-proof-mailto').attr('href', 'mailto:' + (cfg.contactEmail || '') + '?subject=' + encodeURIComponent('Preuve de paiement LoginFennec Pro — ' + payLabel()) + '&body=' + encodeURIComponent(proof));
		if (cfg.whatsapp) {
			$('.lnf-proof-wa').removeAttr('hidden').attr('href', 'https://wa.me/' + cfg.whatsapp + '?text=' + encodeURIComponent(proof));
		}
	}

	$(document).on('click', '.lnf-choose-pack, .lnf-buy-open', function (e) {
		if ($(this).is('a')) {
			e.preventDefault();
		}
		selectedPack = $(this).data('pack') || 'site1';
		$('.lnf-pack').removeClass('is-selected');
		$('.lnf-pack').each(function () {
			if ($(this).find('.lnf-choose-pack[data-pack="' + selectedPack + '"], .lnf-buy-open[data-pack="' + selectedPack + '"]').length) {
				$(this).addClass('is-selected');
			}
		});
		$('#lnf-license-pack').val(selectedPack);
		updatePaySummary();
		$('#lnf-pay').removeAttr('hidden');
		if ($('#lnf-pay')[0] && $('#lnf-pay')[0].scrollIntoView) {
			$('#lnf-pay')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	});

	/* Pop-up d'achat : assistant en 3 étapes */
	function wzStep(n) {
		$('.lnf-wz-pane').removeClass('is-active');
		$('.lnf-wz-pane[data-wpane="' + n + '"]').addClass('is-active');
		$('.lnf-wz-steps .wz-step').removeClass('is-active is-done');
		$('.lnf-wz-steps .wz-step').each(function () {
			var s = parseInt($(this).data('ws'), 10);
			if (s < n) { $(this).addClass('is-done'); }
			if (s === n) { $(this).addClass('is-active'); }
		});
	}

	function openCheckoutWizard(pack) {
		selectedPack = pack || 'site1';
		wzStep(1);
		updatePaySummary();
		$('#lnf-checkout-modal').removeAttr('hidden');
		$('#lnf-modal-key').val('');
	}

	function closeCheckoutWizard(reload) {
		$('#lnf-checkout-modal').attr('hidden', '');
		$('#lnf-wz-card-frame').attr('src', 'about:blank');
		if (reload) {
			window.location.reload();
		}
	}

	$(document).on('click', '.lnf-buy-open', function (e) {
		e.preventDefault();
		openCheckoutWizard($(this).data('pack') || 'site1');
	});

	$(document).on('click', '.lnf-wz-method', function () {
		$('.lnf-wz-method').removeClass('is-active');
		$(this).addClass('is-active');
		$('.lnf-wz-mbody').attr('hidden', '');
		$('.lnf-wz-mbody[data-mbody="' + $(this).data('m') + '"]').removeAttr('hidden');
		// Carte bancaire : charger l'iframe seulement à la sélection (sinon
		// l'URL de checkout serait rechargée à chaque mise à jour du résumé).
		if ($(this).data('m') === 'card' && window.lnfCardUrl) {
			$('#lnf-wz-card-frame').attr('src', window.lnfCardUrl);
			$('#lnf-wz-card-newtab').attr('href', window.lnfCardUrl);
		}
	});

	$(document).on('click', '.lnf-wz-next', function () {
		wzStep(2);
	});

	$(document).on('click', '.lnf-wz-back2', function () {
		wzStep(1);
	});

	$(document).on('click', '#lnf-checkout-modal [data-close]', function () {
		if ($('.lnf-wz-pane[data-wpane="3"]').hasClass('is-active')) {
			window.location.reload();
			return;
		}
		closeCheckoutWizard(false);
		$('#lnf-checkout-modal').attr('hidden', '');
	});

	/* Activer une licence depuis la fenêtre d'achat */
	$(document).on('click', '.lnf-modal-activate', function () {
		var $btn = $(this);
		// Normalisation : espaces supprimés, tirets conservés, majuscules.
		var key = $.trim($('#lnf-modal-key').val()).toUpperCase().replace(/[\s]+/g, '');
		$('#lnf-modal-key').val(key);
		var $out = $('.lnf-wz-pane[data-wpane="2"] .lnf-license-status');
		if (!key) {
			$out.text('Veuillez saisir votre clé de licence.').addClass('is-err').removeClass('is-ok');
			return;
		}
		$btn.prop('disabled', true);
		$out.text('…').removeClass('is-err is-ok');
		$.post(cfg.ajaxUrl, {
			action: 'lnf_activate_license',
			nonce: cfg.nonce,
			license_key: key,
			plan: selectedPack,
			billing: currentBilling
		})
			.done(function (res) {
				if (res && res.success) {
					wzStep(3);
					$('.lnf-wz-pane[data-wpane="3"] .lnf-wz-success-msg').text(res.data.message || 'Pro activé.');
					// Le rechargement applique l'état Pro partout.
					window.setTimeout(function () { window.location.reload(); }, 2500);
				} else {
					var msg = (res && res.data && res.data.message) ? res.data.message : 'Erreur';
					$out.text(msg).addClass('is-err').removeClass('is-ok');
					$btn.prop('disabled', false);
				}
			})
			.fail(function () {
				$out.text('Erreur réseau').addClass('is-err').removeClass('is-ok');
				$btn.prop('disabled', false);
			});
	});

	/* Désactiver la licence */
	$(document).on('click', '.lnf-deactivate-license', function () {
		if (!window.confirm('Désactiver la licence sur ce site ?')) {
			return;
		}
		$.post(cfg.ajaxUrl, { action: 'lnf_deactivate_license', nonce: cfg.nonce })
			.done(function (res) {
				if (res && res.success) { window.location.reload(); }
			});
	});

	/* Activer Pro depuis la page Pro (formulaire clé + pack + facturation) */
	function activateFromProPage(plan, billing, key, $out, $btn) {
		if (!key) {
			$out.text('Veuillez saisir votre clé de licence.').addClass('is-err').removeClass('is-ok');
			return;
		}
		$btn.prop('disabled', true);
		$out.text('…').removeClass('is-err is-ok');
		$.post(cfg.ajaxUrl, {
			action: 'lnf_activate_license',
			nonce: cfg.nonce,
			license_key: key,
			plan: plan,
			billing: billing
		})
			.done(function (res) {
				if (res && res.success) {
					$out.text(res.data && res.data.message ? res.data.message : 'Pro activé.').addClass('is-ok').removeClass('is-err');
					window.setTimeout(function () { window.location.reload(); }, 1500);
				} else {
					$out.text(res && res.data && res.data.message ? res.data.message : 'Erreur').addClass('is-err').removeClass('is-ok');
					$btn.prop('disabled', false);
				}
			})
			.fail(function () {
				$out.text('Erreur réseau').addClass('is-err').removeClass('is-ok');
				$btn.prop('disabled', false);
			});
	}

	$(document).on('click', '.lnf-activate-license', function () {
		activateFromProPage(
			$('#lnf-license-pack').val() || 'site1',
			$('#lnf-license-billing').val() || 'yearly',
			$.trim($('#lnf-license-key').val()).toUpperCase(),
			$('.lnf-license-status'),
			$(this)
		);
	});
	$(document).on('keydown', '#lnf-license-key', function (e) {
		if (e.key === 'Enter') {
			e.preventDefault();
			$('.lnf-activate-license').trigger('click');
		}
	});

	/* Licence développeur : activation en un clic (validation locale). */
	$(document).on('click', '.lnf-dev-activate', function () {
		var $btn = $(this);
		$btn.prop('disabled', true);
		$.post(cfg.ajaxUrl, {
			action: 'lnf_activate_license',
			nonce: cfg.nonce,
			license_key: 'DEV-LOGINFENNEC',
			plan: 'site1',
			billing: 'lifetime'
		})
			.done(function (res) {
				if (res && res.success) {
					window.location.reload();
				} else {
					$btn.prop('disabled', false);
				}
			})
			.fail(function () { $btn.prop('disabled', false); });
	});

	/* Vérifier la licence maintenant */
	$(document).on('click', '.lnf-check-license', function () {
		var $btn = $(this);
		var $out = $('.lnf-license-status');
		$btn.prop('disabled', true);
		$out.text('…').removeClass('is-err is-ok');
		$.post(cfg.ajaxUrl, { action: 'lnf_check_license', nonce: cfg.nonce })
			.always(function () { $btn.prop('disabled', false); })
			.done(function (res) {
				if (res && res.success) { window.location.reload(); }
			})
			.fail(function () {
				$out.text('Erreur réseau').addClass('is-err').removeClass('is-ok');
			});
	});

	/* Copier (coordonnées de paiement) */
	$(document).on('click', '.lnf-copy', function () {
		var text = $.trim($($(this).data('copy')).text());
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text);
		}
		var $btn = $(this);
		$btn.text('✓');
		window.setTimeout(function () { $btn.text('Copier'); }, 1500);
	});

	/* Style d'icône sociale : sélecteur de variante (aperçu live via CSS) */
	$(document).on('click', '.lnf-social-variant', function () {
		$('.lnf-social-variant').removeClass('is-active');
		$(this).addClass('is-active');
		$form.find('[name="lnf[social_variant]"]').val($(this).data('variant'));
		schedulePreview();
	});

	/* Test d'envoi SMS (onglet SMS) */
	$(document).on('click', '.lnf-sms-test', function () {
		var $btn = $(this);
		var $out = $('.lnf-sms-test-status');
		var phone = $('#lnf-sms-test-number').val().trim();
		if (!phone) {
			$out.text('Entrez votre numéro de téléphone.');
			return;
		}
		$btn.prop('disabled', true);
		$out.text('…');
		$.post(cfg.ajaxUrl, { action: 'lnf_sms_test', nonce: cfg.nonce, phone: phone })
			.always(function () { $btn.prop('disabled', false); })
			.done(function (res) {
				if (res && res.success) {
					$out.text(res.data && res.data[0] ? res.data[0].message : 'SMS de test envoyé.');
				} else {
					$out.text(res && res.data && res.data[0] ? res.data[0].message : 'Échec de l’envoi.');
				}
			})
			.fail(function () { $out.text('Erreur réseau.'); });
	});

	/* Vider le journal de sécurité */
	$(document).on('click', '.lnf-purge-log', function () {
		var $btn = $(this);
		if (!window.confirm('Vider le journal de sécurité ?')) {
			return;
		}
		$btn.prop('disabled', true);
		$.post(cfg.ajaxUrl, { action: 'lnf_purge_log', nonce: cfg.nonce })
			.always(function () { $btn.prop('disabled', false); })
			.done(function (res) {
				if (res && res.success) {
					var $journal = $('.lnf-journal');
					$journal.find('table, p.lnf-desc, .lnf-purge-log').remove();
					$journal.append('<p class="lnf-desc">Aucun événement enregistré pour le moment.</p>');
				}
			});
	});

	/* Vérifier les mises à jour */
	$(document).on('click', '.lnf-check-updates', function () {
		var $btn = $(this);
		if ($btn.prop('disabled')) {
			return;
		}
		var $out = $btn.closest('.infcl-card, .lnf-card, .lnf-about-card').find('.lnf-update-status').first();
		$btn.prop('disabled', true).addClass('is-loading');
		$out.html('<span class="lnf-update-msg">…</span>');

		$.post(cfg.ajaxUrl, { action: 'lnf_check_updates', nonce: cfg.nonce })
			.always(function () {
				$btn.prop('disabled', false).removeClass('is-loading');
			})
			.done(function (res) {
				if (!res || !res.success) {
					var msg = (res && res.data && res.data.message) ? res.data.message : 'Erreur';
					$out.html('<span class="lnf-update-msg is-err">' + $('<i>').text(msg).html() + '</span>');
					return;
				}
				var channel = res.data.channel === 'github' ? ' · canal GitHub' : ' · WordPress.org';
				if (res.data.status === 'up_to_date') {
					$out.html('<span class="lnf-update-msg is-ok">✓ À jour — v' + res.data.version + channel + '</span>');
				} else {
					$out.html('<span class="lnf-update-msg is-new">v' + res.data.version + ' disponible' + channel + '</span> <a class="lnf-update-link" href="' + res.data.url + '">Mettre à jour</a>');
				}
			})
			.fail(function () {
				$out.html('<span class="lnf-update-msg is-err">Erreur réseau</span>');
			});
	});

	/* Pack recommandé : active les 5 protections d'un coup */
	$(document).on('click', '.lnf-recommended', function () {
		var $btn = $(this);
		$btn.prop('disabled', true).text('…');
		$.post(cfg.ajaxUrl, { action: 'lnf_enable_recommended', nonce: cfg.nonce })
			.done(function (res) {
				if (res && res.success) {
					window.location.reload();
				} else {
					$btn.prop('disabled', false);
				}
			})
			.fail(function () {
				$btn.prop('disabled', false);
			});
	});

	/* Vider le journal : bouton plus */
	$(document).on('click', '#lnf-reset', function (e) {
		if (!window.confirm('Réinitialiser tous les réglages aux valeurs par défaut ?')) {
			e.preventDefault();
		}
	});

	/* Installateur : navigation entre les étapes */
	var wizardStep = 1;
	var wizardTotal = $('.lnf-wstep').length || 3;

	// Récapitulatif final : reflète les sélections courantes.
	var wizardLabels = {
		styles: { glass: 'Effet verre', minimal: 'Minimal', dark: 'Sombre', sunset: 'Coucher de soleil', ocean: 'Océan', forest: 'Forêt', neon: 'Néon', sakura: 'Sakura', mono: 'Monochrome', royal: 'Royal', custom: 'Personnalisé' },
		themes: { glass: 'Effet verre', classic: 'Classique', outline: 'Contour', pill: 'Pillule', elevated: 'Surélevé', accent: 'Accent', minimal: 'Minimal' }
	};
	function wizardRecap() {
		var preset = $('[name="lnf[preset]"]').val();
		var theme = $('[name="lnf[form_theme]"]').val();
		var sec = $('[name="lnf[sec_enable]"]').filter(':checkbox').prop('checked');
		if ($('#lnf-recap-style').length) {
			$('#lnf-recap-style').text(wizardLabels.styles[preset] || preset || '—');
		}
		if ($('#lnf-recap-theme').length) {
			$('#lnf-recap-theme').text(wizardLabels.themes[theme] || theme || '—');
		}
		if ($('#lnf-recap-sec').length) {
			$('#lnf-recap-sec').text(sec ? 'Activée (force brute, honeypot, anti-énumération)' : 'Standard');
		}
	}

	function wizardShow(n) {
		wizardStep = Math.max(1, Math.min(wizardTotal, n));
		$('.lnf-wstep').removeClass('is-active');
		$('.lnf-wstep[data-step="' + wizardStep + '"]').addClass('is-active');
		$('.lnf-inst-steps li').removeClass('is-active is-done');
		$('.lnf-inst-steps li').each(function () {
			var dot = parseInt($(this).data('step-dot'), 10);
			if (dot < wizardStep) $(this).addClass('is-done');
			if (dot === wizardStep) $(this).addClass('is-active');
		});
		$('.lnf-step-prev').toggle(wizardStep > 1);
		$('.lnf-step-next').toggle(wizardStep < wizardTotal);
		$('.lnf-step-finish').toggle(wizardStep === wizardTotal);
		if (wizardStep === wizardTotal) {
			wizardRecap();
		}
	}

	$(document).on('click', '.lnf-step-next', function () {
		wizardShow(wizardStep + 1);
	});
	$(document).on('click', '.lnf-step-prev', function () {
		wizardShow(wizardStep - 1);
	});

	if ($('.lnf-installer').length) {
		wizardShow(1);
	}

	/* ------------------------------------------------------------------
	 * Suivi des modifications + barre d'enregistrement + Ctrl+S.
	 * ---------------------------------------------------------------- */
	var isDirty = false;

	function markDirty() {
		if (isDirty) {
			return;
		}
		isDirty = true;
		$('.lnf-savebar-state').text('● Modifications non enregistrées');
		$('.lnf-savebar-btn, [form="lnf-form"].lnf-btn-primary').addClass('is-dirty');
	}

	function markClean() {
		isDirty = false;
		$('.lnf-savebar-state').text('');
		$('.lnf-savebar-btn, [form="lnf-form"].lnf-btn-primary').removeClass('is-dirty');
	}

	$form.on('input change', 'input, select, textarea', function () {
		markDirty();
	});

	// Ctrl+S / Cmd+S = enregistrer.
	$(document).on('keydown', function (e) {
		if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's' && $('#lnf-form').length) {
			e.preventDefault();
			$('#lnf-form').trigger('submit');
		}
	});

	// Avertit avant de quitter avec des modifications non enregistrées.
	$(window).on('beforeunload', function () {
		if (isDirty) {
			return 'Modifications non enregistrées.';
		}
	});

	// États du bouton pendant la soumission (les deux boutons Enregistrer).
	$(document).on('submit', '#lnf-form', function () {
		$('.lnf-savebar-btn, [form="lnf-form"].lnf-btn-primary').prop('disabled', true);
		$('.lnf-savebar-state').text('Enregistrement…');
	});

	/* Initialisation */
	refreshShowIf();
	refreshOutputs();
})(jQuery);
