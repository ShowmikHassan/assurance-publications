/**
 * Assurance Publications — contact form
 * ======================================
 * Progressive AJAX submit on top of a plain HTML form: it still POSTs
 * normally if JS fails to load, and degrades to a full page reload back to
 * admin-ajax.php's JSON response rather than breaking silently.
 */
(function (window, document) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var form = document.getElementById('ap-contact-form');

		if (!form || !window.AP) {
			return;
		}

		var statusEl = form.querySelector('[data-ap-form-status]');
		var submitBtn = form.querySelector('.ap-contact-form__submit');

		function clearErrors() {
			form.querySelectorAll('.ap-field').forEach(function (field) {
				field.classList.remove('has-error');
			});
			form.querySelectorAll('[data-ap-error]').forEach(function (el) {
				el.textContent = '';
			});
		}

		function showFieldErrors(fields) {
			Object.keys(fields || {}).forEach(function (name) {
				var input = form.querySelector('[name="' + name + '"]');
				var errorEl = form.querySelector('[data-ap-error="' + name + '"]');

				if (input) {
					input.closest('.ap-field').classList.add('has-error');
				}

				if (errorEl) {
					errorEl.textContent = fields[name];
				}
			});
		}

		function setStatus(message, state) {
			if (!statusEl) {
				return;
			}

			if (!message) {
				statusEl.hidden = true;
				statusEl.textContent = '';
				statusEl.removeAttribute('data-state');
				return;
			}

			statusEl.hidden = false;
			statusEl.textContent = message;
			statusEl.setAttribute('data-state', state || 'error');
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			clearErrors();
			setStatus('');

			var payload = {
				name: form.name.value.trim(),
				email: form.email.value.trim(),
				phone: form.phone.value.trim(),
				message: form.message.value.trim(),
				website: form.website.value
			};

			submitBtn.classList.add('is-loading');
			submitBtn.disabled = true;

			window.AP.request('contact_form', payload)
				.then(function (data) {
					setStatus(data.message, 'success');
					form.reset();
					window.AP.toast(data.message, 'success');
				})
				.catch(function (err) {
					var data = err.data || {};
					setStatus(data.message || err.message, 'error');

					if (data.fields) {
						showFieldErrors(data.fields);
					}
				})
				.finally(function () {
					submitBtn.classList.remove('is-loading');
					submitBtn.disabled = false;
				});
		});
	});
})(window, document);
