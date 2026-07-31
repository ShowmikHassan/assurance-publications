/**
 * Checkout: collapsible order note, phone normalisation, district-driven
 * totals refresh.
 */
(function (window, document) {
	'use strict';

	var AP = window.AP;

	if (!AP) {
		return;
	}


	AP.delegate('change', '[data-ap-note-toggle]', function (event, checkbox) {
		var field = document.querySelector('[data-ap-note-field]');

		if (!field) {
			return;
		}

		field.hidden = !checkbox.checked;

		if (checkbox.checked) {
			var textarea = field.querySelector('textarea');
			if (textarea) {
				textarea.focus();
			}
		}
	});

	/* Normalise +880/880 prefixes so the server validator sees 01XXXXXXXXX. */

	AP.delegate('blur', '#billing_phone', function (event, input) {
		var digits = input.value.replace(/[^\d]/g, '');

		if (digits.length === 13 && digits.indexOf('880') === 0) {
			digits = digits.slice(3);
		} else if (digits.length === 12 && digits.indexOf('88') === 0) {
			digits = digits.slice(2);
		}

		if (digits) {
			input.value = digits;
		}
	});

	/* WooCommerce recalculates shipping itself on .address-field change;
	   this only surfaces the pending state. */

	AP.delegate('change', '#billing_state', function () {
		var review = document.querySelector('.woocommerce-checkout-review-order');

		if (review) {
			review.classList.add('ap-is-busy');
		}
	});

	if (window.jQuery) {
		window.jQuery(document.body).on('updated_checkout', function () {
			var review = document.querySelector('.woocommerce-checkout-review-order');

			if (review) {
				review.classList.remove('ap-is-busy');
			}

			syncPaybar();
			collapseOrderItems();
		});
	}

	/* Order summary: show 3 items, rest behind a toggle. Re-runs after
	   updated_checkout because WooCommerce re-renders the whole table. */

	var LIMIT = 3;

	function chevron(direction) {
		return (
			'<svg class="ap-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" ' +
			'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" ' +
			'stroke-linejoin="round" aria-hidden="true" focusable="false">' +
			(direction === 'up' ? '<path d="m6 15 6-6 6 6"/>' : '<path d="m6 9 6 6 6-6"/>') +
			'</svg>'
		);
	}

	function toggleLabel(expanded, hiddenCount) {
		return expanded
			? '<span>' + (AP.i18n.showLess || 'কম দেখুন') + '</span>' + chevron('up')
			: '<span>' + (AP.i18n.showMore || 'আরও দেখুন') + ' (' + hiddenCount + ')</span>' +
			chevron('down');
	}

	function collapseOrderItems() {
		var table = document.querySelector('.woocommerce-checkout-review-order-table');

		if (!table) {
			return;
		}

		var rows = table.querySelectorAll('tbody > tr.cart_item');

		if (!rows.length) {
			return;
		}

		var existing = table.querySelector('.ap-order-toggle-row');
		if (existing) {
			existing.remove();
		}

		if (rows.length <= LIMIT) {
			return;
		}

		var hiddenCount = rows.length - LIMIT;

		Array.prototype.slice.call(rows, LIMIT).forEach(function (row) {
			row.hidden = true;
		});

		var toggleRow = document.createElement('tr');
		toggleRow.className = 'ap-order-toggle-row';
		toggleRow.innerHTML =
			'<td colspan="2"><button type="button" class="ap-order-toggle" ' +
			'data-ap-order-toggle aria-expanded="false">' +
			toggleLabel(false, hiddenCount) +
			'</button></td>';

		rows[LIMIT - 1].insertAdjacentElement('afterend', toggleRow);
	}

	AP.delegate('click', '[data-ap-order-toggle]', function (event, button) {
		event.preventDefault();

		var table = button.closest('table');
		var rows = table.querySelectorAll('tbody > tr.cart_item');
		var toggleRow = button.closest('tr');
		var expanded = button.getAttribute('aria-expanded') === 'true';
		var hiddenCount = rows.length - LIMIT;

		Array.prototype.slice.call(rows, LIMIT).forEach(function (row) {
			row.hidden = expanded;
		});

		// Follow the list it controls: sit under the 3rd row while collapsed,
		// under the last row once everything is showing.
		var anchor = expanded ? rows[LIMIT - 1] : rows[rows.length - 1];
		anchor.insertAdjacentElement('afterend', toggleRow);

		button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
		button.innerHTML = toggleLabel(!expanded, hiddenCount);
	});

	collapseOrderItems();

	/* The breakdown and button label are rendered server-side per gateway,
	   and WooCommerce does not refresh on a gateway change by itself. */

	if (window.jQuery) {
		window.jQuery(document.body).on(
			'change',
			'input[name="payment_method"]',
			function () {
				window.jQuery(document.body).trigger('update_checkout');
			}
		);
	}

	/* ======================================================================
	 * Checkout error fallback
	 *
	 * When bKash is the selected gateway its plugin takes over the order
	 * button and runs its own submit. Its submit_error() begins with
	 * `loader.style.display = 'none'`, but `loader` is only assigned inside
	 * create_bkash_loader(), which never runs in the redirect
	 * (checkout_url) flow — so it throws on the first line and the error
	 * list, the inline field messages and the scroll never happen. The
	 * shopper just sees the button do nothing.
	 *
	 * dc_bkash_payment is a const inside an IIFE, so the method cannot be
	 * patched. This reads the same response and renders what their handler
	 * failed to, but only when nothing else already did — WooCommerce's own
	 * done() callbacks run before ajaxComplete, so a normal COD failure
	 * renders through core and this stays out of the way.
	 * ==================================================================== */

	function renderCheckoutErrors(messagesHtml) {
		var form = document.querySelector('form.checkout');

		if (!form) {
			return;
		}

		var stale = document.querySelectorAll(
			'.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message, .checkout-inline-error-message'
		);
		Array.prototype.forEach.call(stale, function (el) {
			el.remove();
		});

		var group = document.createElement('div');
		group.className = 'woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout';
		group.innerHTML = messagesHtml;
		form.insertBefore(group, form.firstChild);

		// Mirror core: mark each named field and repeat its message beneath.
		Array.prototype.forEach.call(group.querySelectorAll('[data-id]'), function (item) {
			var field = document.getElementById(item.getAttribute('data-id'));

			if (!field) {
				return;
			}

			var row = field.closest('.form-row');

			if (!row) {
				return;
			}

			row.classList.add('woocommerce-invalid', 'woocommerce-invalid-required-field');

			var text = item.textContent.trim();

			if (!text || row.querySelector('.checkout-inline-error-message')) {
				return;
			}

			var msg = document.createElement('p');
			msg.className = 'checkout-inline-error-message';
			msg.textContent = text;
			row.appendChild(msg);

			field.setAttribute('aria-invalid', 'true');
		});

		decorateErrorItems();

		// The list is the summary of everything that went wrong, so that is
		// where the shopper is taken; individual messages then jump to their
		// own field (see the delegated handler below).
		group.scrollIntoView({ behavior: 'smooth', block: 'center' });
	}

	/**
	 * Make each message in the error list operable, so it can be used to
	 * jump straight to the field it describes.
	 */
	function decorateErrorItems() {
		var items = document.querySelectorAll('.woocommerce-error li[data-id]');

		Array.prototype.forEach.call(items, function (item) {
			if (item.dataset.apLinked) {
				return;
			}

			if (!document.getElementById(item.getAttribute('data-id'))) {
				return;
			}

			item.dataset.apLinked = '1';
			item.classList.add('is-linked');
			item.setAttribute('role', 'button');
			item.setAttribute('tabindex', '0');
		});
	}

	function focusErrorField(item) {
		var field = document.getElementById(item.getAttribute('data-id'));

		if (!field) {
			return;
		}

		var row = field.closest('.form-row') || field;

		row.scrollIntoView({ behavior: 'smooth', block: 'center' });

		// Focus after the scroll settles, so the browser does not fight the
		// smooth scroll by jumping to the field itself.
		window.setTimeout(function () {
			field.focus({ preventScroll: true });
		}, 350);
	}

	AP.delegate('click', '.woocommerce-error li[data-id]', function (event, item) {
		event.preventDefault();
		focusErrorField(item);
	});

	AP.delegate('keydown', '.woocommerce-error li[data-id]', function (event, item) {
		if (event.key !== 'Enter' && event.key !== ' ') {
			return;
		}

		event.preventDefault();
		focusErrorField(item);
	});

	/*
	 * Hooked through ajaxPrefilter rather than ajaxComplete: the gateway's
	 * exception escapes jQuery's resolveWith(), which aborts the remaining
	 * done() callbacks *and* the global ajaxComplete/ajaxSuccess triggers.
	 * A callback attached here is registered before theirs, so it still
	 * runs; the work itself is deferred a tick so core and the gateway get
	 * first refusal at rendering the failure.
	 */
	if (window.jQuery) {
		window.jQuery.ajaxPrefilter(function (options, originalOptions, jqXHR) {
			if (!options || !options.url || options.url.indexOf('wc-ajax=checkout') === -1) {
				return;
			}

			jqXHR.done(function (result) {
				if (!result || 'failure' !== result.result || !result.messages) {
					return;
				}

				window.setTimeout(function () {
					// Something already rendered it — leave it alone.
					if (document.querySelector('.woocommerce-NoticeGroup-checkout')) {
						return;
					}

					renderCheckoutErrors(result.messages);
				}, 0);
			});
		});
	}

	/* Terms/privacy read in place instead of leaving the checkout. */

	var modalRelease = null;

	function openPolicyModal(triggerLink) {
		var root = document.querySelector('[data-ap-modal-root]');

		if (!root) {
			return;
		}

		var titleEl = root.querySelector('[data-ap-modal-title]');
		var bodyEl = root.querySelector('[data-ap-modal-body]');

		titleEl.textContent = triggerLink.textContent.trim();
		bodyEl.innerHTML = '<p class="ap-modal__loading">…</p>';

		root.hidden = false;
		void root.offsetWidth;
		root.classList.add('is-open');
		AP.lockScroll();
		modalRelease = AP.trapFocus(root.querySelector('.ap-modal'));

		AP.request('policy_page', { url: triggerLink.href })
			.then(function (result) {
				titleEl.textContent = result.title || titleEl.textContent;
				bodyEl.innerHTML = result.html || '';
			})
			.catch(function () {
				bodyEl.innerHTML =
					'<p>' + (AP.i18n.genericError || 'লোড করা যায়নি।') + '</p>' +
					'<p><a href="' + triggerLink.href + '">' + triggerLink.textContent.trim() + '</a></p>';
			});
	}

	function closePolicyModal() {
		var root = document.querySelector('[data-ap-modal-root]');

		if (!root || root.hidden) {
			return;
		}

		root.classList.remove('is-open');
		AP.unlockScroll();

		if (modalRelease) {
			modalRelease();
			modalRelease = null;
		}

		window.setTimeout(function () {
			root.hidden = true;
		}, 250);
	}

	/*
	 * WooCommerce binds its own handler to .woocommerce-terms-and-conditions-link
	 * that slides the terms open inline. Capture phase so we win the click and
	 * can stop it reaching that handler.
	 */
	document.addEventListener(
		'click',
		function (event) {
			var link = event.target.closest(
				'.woocommerce-terms-and-conditions-link,' +
					'.woocommerce-terms-and-conditions-wrapper a,' +
					'.woocommerce-privacy-policy-text a,' +
					'.woocommerce-terms-and-conditions-checkbox-text a'
			);

			if (!link) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			openPolicyModal(link);
		},
		true
	);

	AP.delegate('click', '[data-ap-modal-close]', function (event) {
		event.preventDefault();
		closePolicyModal();
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			closePolicyModal();
		}
	});

	/* Mobile pay bar. Mirrors what is charged at this step, not the order
	   total — with COD only the courier fee is collected now. */

	function syncPaybar() {
		var labelTarget = document.querySelector('[data-ap-paybar-label]');
		var amountTarget = document.querySelector('[data-ap-paybar-amount]');

		if (!amountTarget) {
			return;
		}

		var now = document.querySelector('.ap-pay-note__row.is-now');

		if (now) {
			var label = now.querySelector('.ap-pay-note__label');
			var amount = now.querySelector('.ap-pay-note__amount');

			if (label && labelTarget) {
				labelTarget.textContent = label.textContent;
			}

			if (amount) {
				amountTarget.innerHTML = amount.innerHTML;
			}

			return;
		}

		var total = document.querySelector('.order-total .amount');

		if (total) {
			amountTarget.textContent = total.textContent;
		}
	}

	syncPaybar();

	/* WooCommerce scrolls to the notice on failure but never moves focus. */

	if (window.jQuery) {
		window.jQuery(document.body).on('checkout_error', function () {
			decorateErrorItems();

			// The terms notice is suppressed server-side; mark the checkbox
			// itself instead so the shopper is pointed at the actual control.
			var terms = document.getElementById('terms');

			if (terms && !terms.checked) {
				var wrapper = terms.closest('.form-row');
				if (wrapper) {
					wrapper.classList.add('ap-terms-error');
					terms.focus();
				}
				return;
			}

			var notice = document.querySelector('.woocommerce-error');

			if (!notice) {
				return;
			}

			notice.setAttribute('tabindex', '-1');
			notice.setAttribute('role', 'alert');
			notice.focus();
		});
	}

	AP.delegate('change', '#terms', function (event, input) {
		var wrapper = input.closest('.form-row');

		if (wrapper && input.checked) {
			wrapper.classList.remove('ap-terms-error');
		}
	});
})(window, document);
