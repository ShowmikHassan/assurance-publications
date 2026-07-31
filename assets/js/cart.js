/**
 * Cart page: coupon, suggestions carousel, totals refresh.
 *
 * Quantity steppers and row removal are handled by off-canvas-cart.js,
 * which owns those controls wherever they appear.
 */
(function (window, document) {
	'use strict';

	var AP = window.AP;

	if (!AP) {
		return;
	}

	/* ======================================================================
	 * Free-shipping bar — grow the fill in on render instead of snapping
	 * straight to its resting width.
	 * ==================================================================== */

	function animateFreeship(root) {
		var fills = (root || document).querySelectorAll('.ap-freeship__fill');

		Array.prototype.forEach.call(fills, function (fill) {
			var target = fill.style.width;
			fill.style.width = '0%';

			window.requestAnimationFrame(function () {
				window.requestAnimationFrame(function () {
					fill.style.width = target;
				});
			});
		});
	}

	animateFreeship();

	/* ======================================================================
	 * Coupon
	 * ==================================================================== */

	function couponMessage(text, state) {
		var el = document.querySelector('[data-ap-coupon-msg]');

		if (!el) {
			return;
		}

		el.textContent = text || '';
		el.classList.toggle('is-error', state === 'error');
		el.classList.toggle('is-ok', state === 'ok');
	}

	AP.delegate('click', '[data-ap-apply-coupon]', function (event, button) {
		event.preventDefault();

		var input = document.getElementById('coupon_code');
		var code = input ? input.value.trim() : '';

		if (!code) {
			couponMessage('কুপন কোড লিখুন', 'error');
			if (input) {
				input.focus();
			}
			return;
		}

		button.classList.add('is-loading');
		button.disabled = true;
		couponMessage('');

		AP.request('coupon', { code: code })
			.then(function (result) {
				AP.applyFragments(result.fragments);
				couponMessage(result.message, 'ok');

				if (input) {
					input.value = '';
				}

				// Totals are not part of the fragment set WooCommerce
				// publishes, so the page needs a refresh to show the new
				// discount line. Delayed so the success message is readable.
				window.setTimeout(function () {
					window.location.reload();
				}, 900);
			})
			.catch(function (error) {
				couponMessage(error.message || AP.i18n.genericError, 'error');
			})
			.finally(function () {
				button.classList.remove('is-loading');
				button.disabled = false;
			});
	});

	// Enter inside the coupon box should apply, not submit the cart form.
	AP.delegate('keydown', '#coupon_code', function (event) {
		if (event.key !== 'Enter') {
			return;
		}

		event.preventDefault();

		var button = document.querySelector('[data-ap-apply-coupon]');

		if (button) {
			button.click();
		}
	});

	/* ======================================================================
	 * Totals refresh after a quantity change
	 *
	 * The drawer fragments update the drawer, but the cart page's own
	 * totals table is server-rendered and not in the fragment set. Rather
	 * than reload the whole page on every ± tap, refetch just the totals.
	 * ==================================================================== */

	var refreshTotals = AP.debounce(function () {
		var container = document.querySelector('.ap-cart__totals');

		if (!container) {
			return;
		}

		container.classList.add('ap-is-busy');

		// Cache-bust: on a host with full-page caching this GET would
		// otherwise come back as the pre-change HTML.
		var url =
			window.location.href +
			(window.location.search ? '&' : '?') +
			'_ap=' +
			Date.now();

		window
			.fetch(url, { credentials: 'same-origin', cache: 'no-store' })
			.then(function (response) {
				return response.text();
			})
			.then(function (html) {
				var doc = new DOMParser().parseFromString(html, 'text/html');
				var fresh = doc.querySelector('.ap-cart__totals');
				var rows = doc.querySelector('.ap-cart__items');
				var current = document.querySelector('.ap-cart__items');

				if (fresh) {
					container.innerHTML = fresh.innerHTML;
					animateFreeship(container);
					syncCartBar();
				}

				// An emptied cart changes the whole page shell, so hand off
				// to a real navigation rather than patching fragments.
				if (!rows || !rows.children.length) {
					window.location.reload();
					return;
				}

				if (rows && current) {
					var freshKeys = Array.prototype.map.call(
						rows.querySelectorAll('.ap-cart-row'),
						function (row) {
							return row.dataset.cartKey;
						}
					);
					var liveKeys = Array.prototype.map.call(
						current.querySelectorAll('.ap-cart-row'),
						function (row) {
							return row.dataset.cartKey;
						}
					);

					/*
					 * A book added from the suggestions strip is a new line,
					 * not a changed one, so patching money columns alone left
					 * it missing from the list. Swap the whole list only when
					 * the set of lines actually differs — otherwise keep it,
					 * so a quantity box being typed into is not destroyed.
					 */
					if (freshKeys.join('|') !== liveKeys.join('|')) {
						current.innerHTML = rows.innerHTML;
						AP.emit('ap:cards:rendered', { container: current });
						return;
					}

					Array.prototype.forEach.call(
						rows.querySelectorAll('.ap-cart-row'),
						function (freshRow) {
							var key = freshRow.dataset.cartKey;
							var liveRow = current.querySelector(
								'.ap-cart-row[data-cart-key="' + cssEscape(key) + '"]'
							);

							if (!liveRow) {
								return;
							}

							var freshTotal = freshRow.querySelector('.ap-cart-row__total');
							var liveTotal = liveRow.querySelector('.ap-cart-row__total');

							if (freshTotal && liveTotal) {
								liveTotal.innerHTML = freshTotal.innerHTML;
							}
						}
					);
				}
			})
			.catch(function () {
				/* Leave the stale total rather than breaking the page. */
			})
			.finally(function () {
				container.classList.remove('ap-is-busy');
			});
	}, 500);

	function cssEscape(value) {
		if (window.CSS && window.CSS.escape) {
			return window.CSS.escape(value);
		}
		return String(value).replace(/["\\]/g, '\\$&');
	}

	/*
	 * Keep the sticky bar showing whatever the totals table says.
	 *
	 * Calling this from each place that changes the cart proved fragile —
	 * quantity edits, coupons and the shipping calculator all update the
	 * table by different routes, and any path that forgot to call left the
	 * bar showing a stale total beside a correct one. Observing the table
	 * instead means every route is covered by construction.
	 */
	function syncCartBar() {
		var target = document.querySelector('[data-ap-cartbar-amount]');

		if (!target) {
			return;
		}

		var source =
			document.querySelector('.ap-cart__totals .order-total .amount') ||
			document.querySelector('.ap-cart__totals .order-total td');

		if (source && target.innerHTML !== source.innerHTML) {
			target.innerHTML = source.innerHTML;
		}
	}

	function watchCartTotals() {
		var totals = document.querySelector('.ap-cart__totals');

		syncCartBar();

		if (!totals || !window.MutationObserver) {
			return;
		}

		new window.MutationObserver(syncCartBar).observe(totals, {
			childList: true,
			subtree: true,
			characterData: true
		});
	}

	watchCartTotals();
	window.addEventListener('load', syncCartBar);

	AP.on('ap:cart:updated', refreshTotals);

	/* Shipping calculator — recalculate as soon as a district is picked. */
	AP.delegate('change', '#calc_shipping_state', function (event, select) {
		var form = select.closest('form.woocommerce-shipping-calculator');

		if (!form) {
			return;
		}

		var trigger = form.querySelector('[name="calc_shipping"]');

		if (trigger) {
			trigger.click();
			return;
		}

		form.submit();
	});

	/* ======================================================================
	 * Suggestions carousel
	 * ==================================================================== */

	var track = document.querySelector('[data-ap-suggest-track]');

	function shownIds() {
		if (!track) {
			return [];
		}

		return Array.prototype.map.call(
			track.querySelectorAll('[data-product-id]'),
			function (cell) {
				return Number(cell.dataset.productId);
			}
		);
	}

	/**
	 * After a card is added from the carousel, drop that cell and pull in
	 * the next unseen popular book so the strip keeps its length.
	 */
	AP.on('ap:cart:updated', function (event) {
		if (!track) {
			return;
		}

		var productId = event.detail && event.detail.productId;

		if (!productId) {
			return;
		}

		var cell = track.querySelector('[data-product-id="' + Number(productId) + '"]');

		if (!cell) {
			return;
		}

		cell.classList.add('is-leaving');

		AP.request('cart_suggestions', { shown: shownIds() })
			.then(function (result) {
				window.setTimeout(function () {
					if (result.html) {
						cell.insertAdjacentHTML('afterend', result.html);
						var added = cell.nextElementSibling;
						if (added) {
							added.classList.add('is-entering');
						}
					}

					cell.remove();
					AP.emit('ap:cards:rendered', { container: track });
				}, 320);
			})
			.catch(function () {
				// No replacement available — still remove the added card,
				// since showing a book already in the cart is worse than a
				// slightly shorter strip.
				window.setTimeout(function () {
					cell.remove();
				}, 320);
			});
	});

	/* Arrow navigation. */
	AP.delegate('click', '[data-ap-scroll]', function (event, button) {
		event.preventDefault();

		var section = button.closest('.ap-band, .ap-suggest, section');
		var scroller = section && section.querySelector('.ap-scroller');

		if (!scroller) {
			return;
		}

		var direction = Number(button.dataset.apScroll) || 1;

		if (scroller.classList.contains('ap-suggest__track')) {
			/*
			 * Step by a whole cell plus the flex gap, not by the track's
			 * client width. The cells are a percentage of the track minus
			 * the gap, so scrolling the full client width overshoots by one
			 * gap each press and the strip drifts out of alignment.
			 */
			var cell = scroller.querySelector('.ap-suggest__cell');
			var step = scroller.clientWidth;

			if (cell) {
				var gap = parseFloat(window.getComputedStyle(scroller).columnGap) || 0;
				var perPage = Math.max(1, Math.round(scroller.clientWidth / (cell.offsetWidth + gap)));
				step = perPage * (cell.offsetWidth + gap);
			}

			scroller.scrollBy({ left: step * direction, behavior: 'smooth' });
			return;
		}

		var first = scroller.querySelector(':scope > *');
		var step = first ? first.offsetWidth + 16 : 240;

		scroller.scrollBy({ left: step * direction * 2, behavior: 'smooth' });
	});
})(window, document);
