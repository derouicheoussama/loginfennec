/**
 * ∞ INFINITY CODER — création originale de Derouiche Oussama
 * LoginFennec Pro — connexion par SMS (code à usage unique).
 * Vanilla JS : la page de connexion ne charge pas jQuery.
 */
/* global LNF_SMS_CFG */
(function () {
	var root = document.getElementById('lnf-sms');
	if (!root) { return; }
	var form = document.getElementById('loginform');
	if (form && form.parentNode) { form.parentNode.insertBefore(root, form.nextSibling); }
	root.hidden = false;

	var cfg = window.LNF_SMS_CFG || {};
	var openBtn = root.querySelector('.lnf-sms-open'),
		panel = root.querySelector('.lnf-sms-panel'),
		stepPhone = root.querySelector('.lnf-sms-step-phone'),
		stepCode = root.querySelector('.lnf-sms-step-code'),
		phoneEl = root.querySelector('#lnf-sms-phone'),
		codeEl = root.querySelector('#lnf-sms-code'),
		statusEl = root.querySelector('.lnf-sms-status'),
		sendBtn = root.querySelector('.lnf-sms-send'),
		verifyBtn = root.querySelector('.lnf-sms-verify'),
		resendBtn = root.querySelector('.lnf-sms-resend'),
		backBtn = root.querySelector('.lnf-sms-back'),
		timer = null,
		busy = false;

	function show(el) { el.hidden = false; }
	function hide(el) { el.hidden = true; }
	function say(msg) { statusEl.textContent = msg || ''; }

	function openPanel() {
		hide(openBtn);
		show(panel);
		hide(backBtn);
		show(stepPhone);
		hide(stepCode);
		say('');
		try { phoneEl.focus(); } catch (e) { /* ignore */ }
	}

	function reset() {
		hide(stepCode);
		show(stepPhone);
		hide(resendBtn);
		hide(backBtn);
		say('');
		if (timer) { window.clearInterval(timer); timer = null; }
	}

	function messageOf(j, fallback) {
		if (j && j.data && j.data[0] && j.data[0].message) { return j.data[0].message; }
		return fallback;
	}

	function post(action, data, done) {
		if (busy) { return; }
		busy = true;
		say(cfg.wait);
		var body = new window.FormData();
		body.append('action', action);
		body.append('nonce', cfg.nonce || '');
		Object.keys(data || {}).forEach(function (k) { body.append(k, data[k]); });
		var redirectInput = document.querySelector('input[name=redirect_to]');
		body.append('redirect_to', redirectInput ? redirectInput.value : '');
		window.fetch(cfg.ajax, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json(); })
			.then(function (j) { busy = false; done(j); })
			.catch(function () { busy = false; say('Erreur réseau, réessayez.'); });
	}

	function countdown(seconds) {
		if (timer) { window.clearInterval(timer); }
		var left = seconds;
		show(resendBtn);
		resendBtn.textContent = 'Renvoyer le code (' + left + 's)';
		timer = window.setInterval(function () {
			left -= 1;
			if (left <= 0) {
				window.clearInterval(timer);
				timer = null;
				resendBtn.textContent = 'Renvoyer le code';
				return;
			}
			resendBtn.textContent = 'Renvoyer le code (' + left + 's)';
		}, 1000);
	}

	function requestCode() {
		var phone = phoneEl.value.trim();
		if (!phone) { say('Entrez votre numéro de téléphone.'); return; }
		hide(resendBtn);
		post('lnfsms_send', { phone: phone }, function (j) {
			if (!j || !j.success) {
				say(messageOf(j, 'Envoi impossible, réessayez.'));
				return;
			}
			say(j.data && j.data.message ? j.data.message : cfg.sent);
			hide(stepPhone);
			show(stepCode);
			try { codeEl.focus(); } catch (e) { /* ignore */ }
			show(backBtn);
			countdown(60);
		});
	}

	function verifyCode() {
		var code = codeEl.value.trim();
		if (!code) { say('Entrez le code reçu par SMS.'); return; }
		post('lnfsms_verify', { phone: phoneEl.value.trim(), code: code }, function (j) {
			if (!j || !j.success) {
				say(messageOf(j, 'Connexion impossible.'));
				return;
			}
			say('Connexion…');
			window.location.assign(j.data && j.data.redirect ? j.data.redirect : (cfg.ajax || '').replace('admin-ajax.php', ''));
		});
	}

	openBtn.addEventListener('click', openPanel);
	backBtn.addEventListener('click', reset);
	sendBtn.addEventListener('click', requestCode);
	resendBtn.addEventListener('click', requestCode);
	verifyBtn.addEventListener('click', verifyCode);
	root.addEventListener('keydown', function (e) {
		if (e.key !== 'Enter') { return; }
		e.preventDefault();
		e.stopPropagation();
		if (!stepCode.hidden) { verifyCode(); } else { requestCode(); }
	});
}());
