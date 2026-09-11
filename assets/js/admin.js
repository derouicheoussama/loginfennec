/**
 * Infinity Customizer — dashboard.
 * Onglets, aperçu en direct (CSS injecté dans l'iframe), presets,
 * médiathèque, interrupteurs et curseurs.
 */
(function ($) {
	'use strict';

	var cfg = window.INFCL_ADMIN || {};
	var $form = $('#infcl-form');
	var $frame = $('#infcl-frame');
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
			if (!name || name.indexOf('infcl[') !== 0) {
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
		var tag = doc.getElementById('infcl-live');
		if (!tag) {
			tag = doc.createElement('style');
			tag.id = 'infcl-live';
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
			return $form.find('[name="infcl[' + key + ']"]').first();
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
		show('.infcl-social', !!socialOn);

		var cp = doc.querySelector('.infcl-copyright');
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
		$.post(cfg.ajaxUrl, collect())
			.done(function (res) {
				if (res && res.success && res.data && res.data.css) {
					applyCss(res.data.css);
					applyDomTweaks();
				}
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
				var $el = $form.find('[name="infcl[' + key + ']"]').first();
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
			var $out = $(this).closest('.infcl-range-row').find('output');
			$out.text(this.value + ($out.data('unit') || ''));
		});
	}

	/* ------------------------------------------------------------------
	 * Événements.
	 * ---------------------------------------------------------------- */

	$form.on('input change', 'input, select, textarea', function () {
		if (!applyingPreset && this.name && this.name !== 'infcl[preset]') {
			$('[name="infcl[preset]"]').val('custom');
			$('.infcl-preset').removeClass('is-active');
		}
		refreshShowIf();
		schedulePreview();
	});

	$form.on('input change', 'input[type=range]', function () {
		var $out = $(this).closest('.infcl-range-row').find('output');
		$out.text(this.value + ($out.data('unit') || ''));
	});

	/* Onglets */
	$(document).on('click', '.infcl-tab', function () {
		var tab = $(this).data('tab');
		$('.infcl-tab').removeClass('is-active');
		$(this).addClass('is-active');
		$('.infcl-panel').removeClass('is-active');
		$('.infcl-panel[data-panel="' + tab + '"]').addClass('is-active');
		if (window.history && window.history.replaceState) {
			window.history.replaceState(null, '', '#' + tab);
		}
	});

	$(document).on('click', '[data-goto]', function () {
		var $tab = $('.infcl-tab[data-tab="' + $(this).data('goto') + '"]');
		if ($tab.length) {
			$tab.trigger('click');
			window.scrollTo({ top: 0, behavior: 'smooth' });
		}
	});

	/* Onglet initial (ancre) */
	var initial = (window.location.hash || '#dashboard').slice(1);
	var $initialTab = $('.infcl-tab[data-tab="' + initial + '"]');
	if ($initialTab.length) {
		$initialTab.trigger('click');
	}

	/* Presets */
	$(document).on('click', '.infcl-preset', function () {
		var key = $(this).data('preset');
		var values = PRESETS[key];
		if (!values) {
			return;
		}
		applyingPreset = true;
		$.each(values, function (fieldKey, value) {
			var $el = $form.find('[name="infcl[' + fieldKey + ']"]').first();
			if (!$el.length) {
				return;
			}
			if ($el.is(':checkbox')) {
				$el.prop('checked', value === 1 || value === true);
			} else if ($el.hasClass('infcl-color') && $.fn.wpColorPicker) {
				$el.wpColorPicker('color', value);
			} else {
				$el.val(value);
			}
		});
		$('[name="infcl[preset]"]').val(key);
		$('.infcl-preset').removeClass('is-active');
		$(this).addClass('is-active');
		applyingPreset = false;

		refreshShowIf();
		refreshOutputs();
		updatePreview();
	});

	/* Sélecteur de couleur natif (iris) */
	if ($.fn.wpColorPicker) {
		$('.infcl-color').wpColorPicker({
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
	$(document).on('click', '.infcl-media-pick', function (e) {
		e.preventDefault();
		var $wrap = $(this).closest('.infcl-media');
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
			$wrap.find('.infcl-media-thumb').attr('src', url).removeAttr('hidden');
		});
		mediaFrame.open();
	});

	$(document).on('click', '.infcl-media-clear', function (e) {
		e.preventDefault();
		var $wrap = $(this).closest('.infcl-media');
		$wrap.find('input[type=url]').val('').trigger('change');
		$wrap.find('.infcl-media-thumb').attr('src', '').attr('hidden', '');
	});

	/* Aperçu : appareils, rechargement */
	$(document).on('click', '.infcl-device', function () {
		$('.infcl-device').removeClass('is-active');
		$(this).addClass('is-active');
		var width = parseInt($(this).data('width'), 10);
		$frame.css('width', width > 0 ? width + 'px' : '100%');
	});

	$(document).on('click', '.infcl-refresh', function () {
		$frame[0].src = $frame[0].src;
	});

	/* Réinitialisation : confirmation */
	$(document).on('click', '#infcl-reset', function (e) {
		if (!window.confirm('Réinitialiser tous les réglages aux valeurs par défaut ?')) {
			e.preventDefault();
		}
	});

	/* Initialisation */
	refreshShowIf();
	refreshOutputs();
})(jQuery);
