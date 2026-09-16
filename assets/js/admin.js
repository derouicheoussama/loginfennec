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
			bg_overlay_color: '#000000', bg_overlay_opacity: 0,
			form_bg: '#ffffff', form_opacity: 10, form_blur: 18, form_radius: 16,
			form_shadow: 1, form_width: 340, form_padding: 36,
			text_color: '#ffffff', label_color: '#cbd5e1',
			input_bg: '#ffffff', input_color: '#1e293b', input_border: '#cbd5e1',
			button_bg: '#f59e0b', button_hover: '#d97706', button_radius: 10,
			link_color: '#e2e8f0'
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
	}

	function updatePreview() {
		if (!$frame.length) {
			return; // Page installateur : pas d'aperçu.
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
				var $el = $form.find('[name="lnf[' + key + ']"]').first();
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
		nuit:   { admin_bg: '#1d2327', admin_text: '#c3c4c7', admin_hover_bg: '#2c3338', admin_hover_text: '#9d7bff', admin_active_bg: '#6d5df6', admin_active_text: '#ffffff', admin_accent: '#6d5df6', adminbar_bg: '#1d2327', adminbar_text: '#c3c4c7', adminbar_hover: '#9d7bff' },
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
	});

	/* Sélecteur de couleur natif (iris) */
	if ($.fn.wpColorPicker) {
		$('.lnf-color').wpColorPicker({
			change: function () {
				schedulePreview();
			},
			clear: function () {
				window.setTimeout(updatePreview, 60);
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
		$frame[0].src = $frame[0].src;
	});

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
		$('#lnf-pay-pack-label, #lnf-wz-pack').text(payLabel());
		$('#lnf-pay-billing-label, #lnf-wz-billing').text(payBillingLabel());
		$('#lnf-pay-amount').text(fmtDA(amount) + ' DA');
		$('#lnf-wz-amount-da, #lnf-ccp-amount').text(fmtDA(amount) + ' DA');
		var usd = Math.round(amount * (parseFloat(cfg.paypalRate) || 0) * 100) / 100;
		$('#lnf-wz-amount-usd, #lnf-paypal-amount').text(usd + ' ' + cfg.paypalCurrency);
		if (cfg.paypalEmail) {
			var payUrl = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick'
				+ '&business=' + encodeURIComponent(cfg.paypalEmail)
				+ '&item_name=' + encodeURIComponent('LoginFennec Pro — ' + payLabel() + ' (' + payBillingLabel() + ')')
				+ '&amount=' + usd
				+ '&currency_code=' + cfg.paypalCurrency
				+ '&no_shipping=1'
				+ '&custom=' + encodeURIComponent(cfg.siteUrl || '');
			$('#lnf-wz-paypal-link, #lnf-paypal-link').attr('href', payUrl);
		}
		var cardUrl = cfg.checkoutUrl + (cfg.checkoutUrl.indexOf('?') > -1 ? '&' : '?') + 'pack=' + selectedPack + '&billing=' + currentBilling + '&site=' + encodeURIComponent(cfg.siteUrl || '');
		$('#lnf-card-open, #lnf-wz-card-open').attr('data-checkout', cardUrl);
		var proof = 'Bonjour,\n\nJe viens de payer ' + fmtDA(amount) + ' DA pour ' + payLabel() + ' (' + payBillingLabel() + ') sur le site ' + (cfg.siteUrl || '') + '.\n\nVoici ma capture de reçu BaridiMob/CCP :\n';
		$('.lnf-proof-mailto').attr('href', 'mailto:' + (cfg.contactEmail || '') + '?subject=' + encodeURIComponent('Preuve de paiement LoginFennec Pro — ' + payLabel()) + '&body=' + encodeURIComponent(proof));
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
		var key = $.trim($('#lnf-modal-key').val());
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
				if (res.data.status === 'up_to_date') {
					$out.html('<span class="lnf-update-msg is-ok">✓ À jour — v' + res.data.version + '</span>');
				} else {
					$out.html('<span class="lnf-update-msg is-new">v' + res.data.version + ' disponible</span> <a class="lnf-update-link" href="' + res.data.url + '">Mettre à jour</a>');
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
	$(document).on('click', '#lnf-reset', function () {
		if (!window.confirm('Réinitialiser tous les réglages aux valeurs par défaut ?')) {
			e && e.preventDefault ? e.preventDefault() : null;
		}
	});

	/* Installateur : navigation entre les étapes */
	var wizardStep = 1;
	var wizardTotal = $('.lnf-wstep').length || 3;

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

	/* Initialisation */
	refreshShowIf();
	refreshOutputs();
})(jQuery);
