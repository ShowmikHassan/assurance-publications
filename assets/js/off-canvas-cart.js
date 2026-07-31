/**
 * Off-canvas cart.
 *
 * Opens on add-to-cart and on the header cart icon. Contents are always
 * server-rendered — every mutation returns fresh markup through
 * WooCommerce's fragment mechanism, so the drawer cannot drift out of sync
 * with the real cart the way a client-side mirror would.
 */
(function (window, document) {
	'use strict';

	var AP = window.AP;

	if (!AP) {
		return;
	}

	var root = null;
	var release = null;
	var isOpen = false;

	function getRoot() {
		if (!root || !document.body.contains(root)) {
			root = document.querySelector('[data-ap-drawer-root]');
		}
		return root;
	}

	/* ======================================================================
	 * Open / close
	 * ==================================================================== */

	function open() {
		var el = getRoot();

		if (!el || isOpen) {
			return;
		}

		isOpen = true;
		el.hidden = false;

		// Force a reflow so the transform transition runs from its start
		// value; without this the drawer snaps open with no animation
		// because `hidden` removal and the class land in the same frame.
		void el.offsetWidth;

		el.classList.add('is-open');
		AP.lockScroll();

		var panel = el.querySelector('[data-ap-drawer]');
		release = AP.trapFocus(panel);

		// Focus the close button rather than the panel: it is a real
		// control, so screen readers announce something actionable, and
		// Escape is already wired.
		var close = panel.querySelector('[data-ap-drawer-close]');
		if (close) {
			close.focus({ preventScroll: true });
		}

		document.addEventListener('keydown', onKeydown);
		AP.emit('ap:cart:opened');
	}

	function close() {
		var el = getRoot();

		if (!el || !isOpen) {
			return;
		}

		isOpen = false;
		el.classList.remove('is-open');
		AP.unlockScroll();

		document.removeEventListener('keydown', onKeydown);

		if (release) {
			release();
			release = null;
		}

		// Wait out the slide-out before hiding, so the panel does not
		// disappear mid-transition.
		var panel = el.querySelector('[data-ap-drawer]');
		var done = false;

		function finish() {
			if (done) {
				return;
			}
			done = true;
			if (!isOpen) {
				el.hidden = true;
			}
		}

		panel.addEventListener('transitionend', finish, { once: true });
		// Fallback for reduced-motion, where transitionend never fires.
		window.setTimeout(finish, 500);

		AP.emit('ap:cart:closed');
	}

	function onKeydown(event) {
		if (event.key === 'Escape') {
			event.preventDefault();
			close();
		}
	}

	/* ======================================================================
	 * Triggers
	 * ==================================================================== */

	AP.on('ap:cart:open', function (event) {
		// Adding to the cart no longer opens the drawer — the toast and the
		// header badge confirm it. Only the cart icon opens the panel.
		if (event.detail && 'add' === event.detail.source) {
			return;
		}

		open();
	});

	AP.delegate('click', '[data-ap-drawer-close]', function (event) {
		event.preventDefault();
		close();
	});

	AP.delegate('click', '[data-ap-cart-toggle]', function (event) {
		event.preventDefault();
		refreshThenOpen();
	});

	/**
	 * Any link that points at the cart page opens the drawer instead.
	 *
	 * This deliberately matches on the destination rather than on
	 * Blocksy's `a.ct-cart-item` class alone. This store's header has no
	 * Blocksy cart element configured — the cart is reached through a plain
	 * "CART" nav item — so a class-based hook would leave the drawer
	 * unreachable except by adding something to the basket. Matching the
	 * URL covers the nav link, Blocksy's cart icon if it is ever enabled,
	 * and any cart button placed in a block or widget.
	 *
	 * The href stays intact throughout, so this degrades to a normal
	 * navigation with JS off.
	 */
	function isCartLink(link) {
		if (!link || !link.getAttribute) {
			return false;
		}

		// Links inside the drawer (View Cart, Checkout, product links in the
		// suggestions row) must always navigate for real — otherwise "View
		// Cart" just reopens the same drawer and the cart page is never
		// reachable. Same for anything explicitly opted out.
		if (link.closest('[data-ap-drawer-root], [data-ap-skip-drawer]')) {
			return false;
		}

		if (link.matches('a.ct-cart-item, [data-ap-cart-toggle]')) {
			return true;
		}

		var href = link.getAttribute('href');

		if (!href || !AP.data.cartUrl) {
			return false;
		}

		// Compare normalised paths so query strings and trailing slashes
		// do not cause a miss.
		try {
			var target = new URL(href, window.location.origin);
			var cart = new URL(AP.data.cartUrl, window.location.origin);

			return (
				target.origin === cart.origin &&
				target.pathname.replace(/\/+$/, '') === cart.pathname.replace(/\/+$/, '')
			);
		} catch (error) {
			return false;
		}
	}

	AP.delegate('click', 'a[href]', function (event, link) {
		if (!isCartLink(link)) {
			return;
		}

		// Honour modified clicks — a shopper deliberately opening the cart
		// in a new tab should get the cart page, not a drawer they cannot see.
		if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
			return;
		}

		// On the cart page itself the drawer would just duplicate what is
		// already on screen.
		if (document.body.classList.contains('woocommerce-cart')) {
			return;
		}

		event.preventDefault();
		refreshThenOpen();
	});

	/**
	 * Pull fresh cart state before opening cold.
	 *
	 * The drawer is server-rendered at page load; if the shopper changed
	 * their cart in another tab since then, opening without a refresh would
	 * show stale lines.
	 */
	function refreshThenOpen() {
		open();

		AP.request('get_cart', {})
			.then(function (result) {
				AP.applyFragments(result.fragments);
			})
			.catch(function () {
				/* Showing the last-known cart beats showing an error. */
			});
	}

	/* ======================================================================
	 * Line item controls
	 * ==================================================================== */

	function setLineBusy(key, busy) {
		var line = document.querySelector('.ap-line[data-cart-key="' + cssEscape(key) + '"]');

		if (line) {
			line.classList.toggle('is-busy', !!busy);
		}
	}

	/** CSS.escape with a fallback for older Safari. */
	function cssEscape(value) {
		if (window.CSS && window.CSS.escape) {
			return window.CSS.escape(value);
		}
		return String(value).replace(/["\\]/g, '\\$&');
	}

	function updateQuantity(key, quantity) {
		setLineBusy(key, true);

		return AP.request('update_qty', { cart_key: key, quantity: quantity })
			.then(function (result) {
				AP.applyFragments(result.fragments);
				AP.emit('ap:cart:updated', result);
				return result;
			})
			.catch(function (error) {
				AP.toast(error.message || AP.i18n.genericError, 'error');
				setLineBusy(key, false);
				throw error;
			});
	}

	AP.delegate('click', '[data-ap-remove-item]', function (event, button) {
		event.preventDefault();

		var key = button.dataset.cartKey;
		var line = button.closest('.ap-line, .ap-cart-row');

		if (line) {
			line.classList.add('is-leaving');
		}

		AP.request('remove_item', { cart_key: key })
			.then(function (result) {
				AP.applyFragments(result.fragments);
				AP.emit('ap:cart:updated', result);
				AP.toast(result.message || AP.i18n.removed, 'success');
			})
			.catch(function (error) {
				if (line) {
					line.classList.remove('is-leaving');
				}
				AP.toast(error.message || AP.i18n.genericError, 'error');
			});
	});

	/* ======================================================================
	 * Quantity switcher
	 *
	 * Shared by the drawer, the cart table and the single-product form.
	 * Steppers that carry a data-cart-key talk to the server; the rest just
	 * change the input for a subsequent form submit.
	 * ==================================================================== */

	AP.delegate('click', '[data-ap-qty-step]', function (event, button) {
		event.preventDefault();

		var wrap = button.closest('[data-ap-qty]');
		var input = wrap.querySelector('.ap-qty__input');
		var step = Number(button.dataset.apQtyStep) || 0;

		var min = input.min === '' ? 0 : Number(input.min);
		var max = input.max === '' ? Infinity : Number(input.max);
		var next = (Number(input.value) || 0) + step;

		next = Math.max(min, Math.min(max, next));

		if (next === Number(input.value)) {
			return;
		}

		input.value = next;
		syncStepperState(wrap);

		// Fire input/change so any other listener (WooCommerce's own cart
		// update button, plugins) sees the same events a keystroke produces.
		input.dispatchEvent(new Event('input', { bubbles: true }));
		input.dispatchEvent(new Event('change', { bubbles: true }));
	});

	function syncStepperState(wrap) {
		var input = wrap.querySelector('.ap-qty__input');
		var value = Number(input.value) || 0;
		var min = input.min === '' ? 0 : Number(input.min);
		var max = input.max === '' ? Infinity : Number(input.max);

		var minus = wrap.querySelector('[data-ap-qty-step="-1"]');
		var plus = wrap.querySelector('[data-ap-qty-step="1"]');

		// At the minimum inside the cart, the down-step becomes a remove,
		// so it stays enabled there; on a product form it is a floor.
		if (minus) {
			minus.disabled = value <= min && !input.dataset.cartKey;
		}

		if (plus) {
			plus.disabled = value >= max;
		}
	}

	/**
	 * Persist a cart quantity change, debounced so holding the + button
	 * sends one request rather than one per click.
	 */
	var pushQuantity = AP.debounce(function (key, value) {
		updateQuantity(key, value).catch(function () {
			/* Toasted in updateQuantity. */
		});
	}, 450);

	AP.delegate('change', '.ap-qty__input', function (event, input) {
		var wrap = input.closest('[data-ap-qty]');

		if (wrap) {
			syncStepperState(wrap);
		}

		var key = input.dataset.cartKey;

		if (!key) {
			return;
		}

		var value = Number(input.value);

		if (Number.isNaN(value) || value < 0) {
			return;
		}

		pushQuantity(key, value);
	});

	// Typing in the box should also update the button states immediately.
	AP.delegate('input', '.ap-qty__input', function (event, input) {
		var wrap = input.closest('[data-ap-qty]');

		if (wrap) {
			syncStepperState(wrap);
		}
	});

	/* ======================================================================
	 * Boot
	 * ==================================================================== */

	function initSteppers(scope) {
		Array.prototype.forEach.call(
			(scope || document).querySelectorAll('[data-ap-qty]'),
			syncStepperState
		);
	}

	/** Every element that now opens the drawer instead of navigating. */
	function cartTriggers() {
		return Array.prototype.filter.call(
			document.querySelectorAll('a[href], [data-ap-cart-toggle]'),
			isCartLink
		);
	}

	function init() {
		initSteppers();

		if (document.body.classList.contains('woocommerce-cart')) {
			return;
		}

		// These links no longer navigate, so announce that they open a
		// dialog. Without this a screen-reader user is told "link, cart"
		// and then finds themselves in a panel with no warning.
		cartTriggers().forEach(function (link) {
			link.setAttribute('aria-haspopup', 'dialog');
			link.setAttribute('aria-expanded', 'false');
		});
	}

	function reflectExpanded(state) {
		cartTriggers().forEach(function (link) {
			link.setAttribute('aria-expanded', state ? 'true' : 'false');
		});
	}

	AP.on('ap:cart:opened', function () {
		reflectExpanded(true);
	});

	AP.on('ap:cart:closed', function () {
		reflectExpanded(false);
	});

	AP.on('ap:fragments:applied', function () {
		initSteppers();
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(window, document);
