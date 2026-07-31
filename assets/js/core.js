/**
 * Assurance Publications — core runtime
 * =====================================
 * Shared services every other script depends on: a fetch wrapper for our
 * admin-ajax endpoints, toasts, an event bus, and focus management for
 * the drawer and modals.
 *
 * Exposed as window.AP. No build step, no framework — the site is
 * server-rendered and this is the only JS on the critical path.
 */
(function (window, document) {
	'use strict';

	var data = window.assuranceData || {};
	var i18n = data.i18n || {};

	/* ======================================================================
	 * 1. Event bus
	 *
	 * Modules communicate through this rather than importing each other, so
	 * that e.g. the product card can announce "item added" without knowing
	 * whether the off-canvas cart is present on the page.
	 * ==================================================================== */

	var bus = document.createElement('div');

	function on(event, handler) {
		bus.addEventListener(event, handler);
	}

	function off(event, handler) {
		bus.removeEventListener(event, handler);
	}

	function emit(event, detail) {
		bus.dispatchEvent(new CustomEvent(event, { detail: detail || {} }));
	}

	/* ======================================================================
	 * 2. AJAX
	 * ==================================================================== */

	/**
	 * POST to one of our admin-ajax endpoints.
	 *
	 * Every request carries the shared nonce. The server re-checks it and
	 * validates its own inputs; this is CSRF protection, not authorisation.
	 *
	 * @param {string} action  Action name without the "ap_" prefix.
	 * @param {Object} payload Body fields.
	 * @param {Object} [opts]  { signal } for abortable requests.
	 * @returns {Promise<Object>} Resolves with the `data` half of the
	 *          WP JSON envelope; rejects with an Error carrying `.data`.
	 */
	function send(action, payload, opts) {
		var body = new FormData();
		body.append('action', 'ap_' + action);
		body.append('nonce', data.nonce || '');

		Object.keys(payload || {}).forEach(function (key) {
			var value = payload[key];

			if (value === undefined || value === null) {
				return;
			}

			// Arrays go over as repeated key[] fields so PHP receives a real
			// array without us having to JSON-decode on the server.
			if (Array.isArray(value)) {
				value.forEach(function (item) {
					body.append(key + '[]', item);
				});
				return;
			}

			body.append(key, value);
		});

		return window
			.fetch(data.ajaxUrl, {
				method: 'POST',
				body: body,
				credentials: 'same-origin',
				cache: 'no-store',
				signal: opts.signal
			})
			.then(function (response) {
				/*
				 * Parse the body even on 4xx. A stale nonce answers 403 with a
				 * machine-readable code, and bailing on !response.ok threw that
				 * away as a bare "HTTP 403" — leaving the retry below no way to
				 * tell a recoverable nonce miss from a real failure.
				 */
				return response
					.json()
					.catch(function () {
						throw new Error('HTTP ' + response.status);
					})
					.then(function (json) {
						if (!json || typeof json.success === 'undefined') {
							throw new Error('Malformed response');
						}

						if (!json.success) {
							var err = new Error(
								(json.data && json.data.message) || i18n.genericError || 'Error'
							);
							err.data = json.data || {};
							err.status = response.status;
							throw err;
						}

						return json.data || {};
					});
			});
	}

	/**
	 * POST to one of our endpoints, transparently recovering from a nonce
	 * that expired while the page sat in a full-page cache.
	 *
	 * On a cached host the HTML — and the nonce printed into it — can be
	 * older than the visitor's session, so the first call of the page life
	 * fails CSRF. Minting a fresh token and replaying once turns that into
	 * an invisible round trip instead of a dead Add-to-cart button.
	 */
	function request(action, payload, opts) {
		opts = opts || {};

		return send(action, payload, opts).catch(function (error) {
			var stale = error && error.data && error.data.code === 'stale_nonce';

			if (!stale || opts._retried) {
				throw error;
			}

			return refreshNonce().then(function (fresh) {
				if (!fresh) {
					throw error;
				}

				opts._retried = true;
				return send(action, payload, opts);
			});
		});
	}

	/**
	 * Fetch a nonce tied to the current session. Deliberately unauthenticated
	 * — a nonce is a CSRF token bound to the caller's own session, not a
	 * secret, and WooCommerce refreshes its own the same way.
	 *
	 * @returns {Promise<string>} The new nonce, or '' if it could not be got.
	 */
	function refreshNonce() {
		var body = new FormData();
		body.append('action', 'ap_refresh_nonce');

		return window
			.fetch(data.ajaxUrl, {
				method: 'POST',
				body: body,
				credentials: 'same-origin',
				cache: 'no-store'
			})
			.then(function (response) {
				return response.json();
			})
			.then(function (json) {
				if (json && json.success && json.data && json.data.nonce) {
					data.nonce = json.data.nonce;
					return json.data.nonce;
				}
				return '';
			})
			.catch(function () {
				return '';
			});
	}

	/* ======================================================================
	 * 3. Toasts
	 * ==================================================================== */

	var toastHost = null;

	function ensureToastHost() {
		if (toastHost && document.body.contains(toastHost)) {
			return toastHost;
		}

		toastHost = document.createElement('div');
		toastHost.className = 'ap-toasts';
		// Polite, not assertive: cart feedback should not interrupt a
		// screen reader mid-sentence while the user is still browsing.
		toastHost.setAttribute('role', 'status');
		toastHost.setAttribute('aria-live', 'polite');
		toastHost.setAttribute('aria-atomic', 'false');
		document.body.appendChild(toastHost);

		return toastHost;
	}

	var CHECK_ICON =
		'<svg class="ap-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" ' +
		'stroke="currentColor" stroke-width="2.2" stroke-linecap="round" ' +
		'stroke-linejoin="round" aria-hidden="true"><path d="m5 13 4.5 4.5L19 7"/></svg>';

	/**
	 * Show a transient message.
	 *
	 * @param {string} message Text (already localised).
	 * @param {string} [type]  'success' | 'error' | 'info'.
	 */
	function toast(message, type) {
		if (!message) {
			return;
		}

		var host = ensureToastHost();
		var el = document.createElement('div');

		el.className = 'ap-toast ap-toast--' + (type || 'info');
		el.textContent = message;

		if (type === 'success') {
			el.insertAdjacentHTML('afterbegin', CHECK_ICON);
		}

		host.appendChild(el);

		var timer = window.setTimeout(dismiss, type === 'error' ? 5000 : 3000);

		function dismiss() {
			window.clearTimeout(timer);
			el.classList.add('is-leaving');
			el.addEventListener('animationend', function () {
				el.remove();
			});
		}

		el.addEventListener('click', dismiss);
	}

	/* ======================================================================
	 * 4. Focus management
	 * ==================================================================== */

	var FOCUSABLE = [
		'a[href]',
		'button:not([disabled])',
		'input:not([disabled]):not([type="hidden"])',
		'select:not([disabled])',
		'textarea:not([disabled])',
		'[tabindex]:not([tabindex="-1"])'
	].join(',');

	function focusableWithin(container) {
		return Array.prototype.filter.call(
			container.querySelectorAll(FOCUSABLE),
			function (el) {
				// offsetParent is null for display:none; also skip anything
				// inside a collapsed [hidden] subtree.
				return el.offsetParent !== null || el === document.activeElement;
			}
		);
	}

	/**
	 * Trap Tab inside a container until released.
	 *
	 * Returns a release function that also restores focus to whatever was
	 * focused before the trap engaged — without this, closing a drawer
	 * dumps keyboard users back at the top of the document.
	 *
	 * @param {HTMLElement} container Element to trap within.
	 * @returns {Function} release
	 */
	function trapFocus(container) {
		var previous = document.activeElement;

		function onKeydown(event) {
			if (event.key !== 'Tab') {
				return;
			}

			var items = focusableWithin(container);

			if (!items.length) {
				event.preventDefault();
				return;
			}

			var first = items[0];
			var last = items[items.length - 1];

			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		}

		document.addEventListener('keydown', onKeydown, true);

		return function release() {
			document.removeEventListener('keydown', onKeydown, true);
			if (previous && typeof previous.focus === 'function' && document.body.contains(previous)) {
				previous.focus({ preventScroll: true });
			}
		};
	}

	/* ======================================================================
	 * 5. Scroll lock
	 *
	 * Reference-counted: the drawer and a lightbox can both be open, and
	 * closing one must not unlock the page under the other.
	 * ==================================================================== */

	var lockCount = 0;
	var savedScroll = 0;

	function lockScroll() {
		if (lockCount++ > 0) {
			return;
		}

		savedScroll = window.scrollY;

		// Compensate for the removed scrollbar so the layout does not jump.
		var gap = window.innerWidth - document.documentElement.clientWidth;

		document.body.style.position = 'fixed';
		document.body.style.top = '-' + savedScroll + 'px';
		document.body.style.width = '100%';

		if (gap > 0) {
			document.body.style.paddingRight = gap + 'px';
		}
	}

	function unlockScroll() {
		if (--lockCount > 0) {
			return;
		}

		lockCount = 0;

		document.body.style.position = '';
		document.body.style.top = '';
		document.body.style.width = '';
		document.body.style.paddingRight = '';
		var root = document.documentElement;
		var previousBehavior = root.style.scrollBehavior;
		root.style.scrollBehavior = 'auto';
		window.scrollTo(0, savedScroll);
		root.style.scrollBehavior = previousBehavior;
	}

	/* ======================================================================
	 * 6. Utilities
	 * ==================================================================== */

	function debounce(fn, wait) {
		var timer;

		return function () {
			var args = arguments;
			var self = this;

			window.clearTimeout(timer);
			timer = window.setTimeout(function () {
				fn.apply(self, args);
			}, wait);
		};
	}

	function throttle(fn, wait) {
		var last = 0;
		var timer;

		return function () {
			var args = arguments;
			var self = this;
			var now = Date.now();
			var remaining = wait - (now - last);

			if (remaining <= 0) {
				window.clearTimeout(timer);
				timer = null;
				last = now;
				fn.apply(self, args);
			} else if (!timer) {
				timer = window.setTimeout(function () {
					last = Date.now();
					timer = null;
					fn.apply(self, args);
				}, remaining);
			}
		};
	}

	/**
	 * Delegated event listener.
	 *
	 * Used everywhere instead of per-element binding, because most of our
	 * markup is replaced wholesale by AJAX (cart fragments, filtered grids)
	 * and directly-bound handlers would be lost on every refresh.
	 *
	 * @param {string}   event    Event name.
	 * @param {string}   selector Target selector.
	 * @param {Function} handler  Receives (event, matchedElement).
	 * @param {Node}     [root]   Defaults to document.
	 */
	function delegate(event, selector, handler, root) {
		(root || document).addEventListener(event, function (e) {
			var match = e.target.closest(selector);

			if (match && (root || document).contains(match)) {
				handler(e, match);
			}
		});
	}

	/** Format a number as a store-currency string, matching the server. */
	function formatPrice(amount) {
		var value = Number(amount) || 0;
		var whole = Math.round(value * 100) / 100;
		var text = whole % 1 === 0 ? String(whole) : whole.toFixed(2);

		// Currency position on this store is "right" (৳ after the number is
		// unusual in BD, so the symbol leads in the markup we control and
		// this helper matches the WooCommerce setting for parity).
		return (data.currency || '৳') + text;
	}

	/** Parse a WooCommerce-rendered price string back to a number. */
	function parsePrice(text) {
		if (!text) {
			return 0;
		}
		return parseFloat(String(text).replace(/[^\d.-]/g, '')) || 0;
	}

	/**
	 * Replace WooCommerce cart fragments in the DOM.
	 *
	 * WooCommerce returns a map of selector → replacement HTML. We apply it
	 * ourselves rather than triggering jQuery's `wc_fragments_refreshed`,
	 * because this theme does not otherwise load jQuery on the front end.
	 *
	 * @param {Object} fragments selector → HTML.
	 */
	function applyFragments(fragments) {
		if (!fragments) {
			return;
		}

		Object.keys(fragments).forEach(function (selector) {
			var nodes = document.querySelectorAll(selector);

			Array.prototype.forEach.call(nodes, function (node) {
				node.outerHTML = fragments[selector];
			});
		});

		emit('ap:fragments:applied', { fragments: fragments });

		// Keep jQuery-based plugins (WooCommerce core, bKash) in sync if
		// jQuery happens to be present for another reason.
		if (window.jQuery) {
			window.jQuery(document.body).trigger('wc_fragments_refreshed');
		}
	}

	/* ======================================================================
	 * 7. Public surface
	 * ==================================================================== */

	window.AP = {
		data: data,
		i18n: i18n,
		on: on,
		off: off,
		emit: emit,
		request: request,
		toast: toast,
		trapFocus: trapFocus,
		focusableWithin: focusableWithin,
		lockScroll: lockScroll,
		unlockScroll: unlockScroll,
		debounce: debounce,
		throttle: throttle,
		delegate: delegate,
		formatPrice: formatPrice,
		parsePrice: parsePrice,
		applyFragments: applyFragments
	};
})(window, document);
