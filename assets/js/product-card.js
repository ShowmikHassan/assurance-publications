/**
 * Product card behaviour: add to cart, buy now, variation popover.
 *
 * Every handler is delegated from `document`, because cards are routinely
 * replaced wholesale — by the shop filter, the home tabs and the cart
 * suggestions carousel — and directly-bound listeners would not survive.
 */
(function (window, document) {
	'use strict';

	var AP = window.AP;

	if (!AP) {
		return;
	}

	var i18n = AP.i18n;

	/* ======================================================================
	 * Button state
	 * ==================================================================== */

	function setLoading(button, loading) {
		button.classList.toggle('is-loading', !!loading);
		button.disabled = !!loading;

		if (loading) {
			button.setAttribute('aria-busy', 'true');
		} else {
			button.removeAttribute('aria-busy');
		}
	}

	function flashDone(button) {
		button.classList.add('is-done');
		window.setTimeout(function () {
			button.classList.remove('is-done');
		}, 1100);
	}

	/* ======================================================================
	 * Add to cart
	 * ==================================================================== */

	/**
	 * @param {HTMLElement} button    Trigger.
	 * @param {Object}      payload   { product_id, quantity, variation_id, variation }
	 * @param {boolean}     openCart  Open the drawer on success.
	 * @returns {Promise}
	 */
	function addToCart(button, payload, openCart) {
		setLoading(button, true);

		return AP.request('add_to_cart', payload)
			.then(function (result) {
				AP.applyFragments(result.fragments);

				AP.emit('ap:cart:updated', result);

				if (openCart !== false) {
					AP.emit('ap:cart:open', { source: 'add', result: result });
				}

				AP.toast(result.message || i18n.added, 'success');
				flashDone(button);

				return result;
			})
			.catch(function (error) {
				AP.toast(error.message || i18n.addFailed, 'error');

				// A stale nonce means the page has outlived its session; a
				// reload is the only thing that can fix it, so say so rather
				// than letting the shopper retry into the same failure.
				if (error.data && error.data.code === 'stale_nonce') {
					window.setTimeout(function () {
						window.location.reload();
					}, 1600);
				}

				throw error;
			})
			.finally(function () {
				setLoading(button, false);
			});
	}

	AP.delegate('click', '[data-ap-add-to-cart]', function (event, button) {
		event.preventDefault();

		if (button.dataset.needsOptions === '1') {
			openVariationPopover(button);
			return;
		}

		addToCart(
			button,
			{
				product_id: button.dataset.productId,
				quantity: button.dataset.quantity || 1
			},
			true
		).catch(function () {
			/* Already surfaced as a toast. */
		});
	});

	/* ======================================================================
	 * Buy now
	 * ==================================================================== */

	AP.delegate('click', '[data-ap-buy-now]', function (event, button) {
		event.preventDefault();

		if (button.dataset.needsOptions === '1') {
			openVariationPopover(button, { buyNow: true });
			return;
		}

		setLoading(button, true);

		AP.request('buy_now', {
			product_id: button.dataset.productId,
			quantity: button.dataset.quantity || 1
		})
			.then(function (result) {
				window.location.href = result.redirect || AP.data.checkoutUrl;
			})
			.catch(function (error) {
				AP.toast(error.message || i18n.addFailed, 'error');
				setLoading(button, false);
			});
	});

	/* ======================================================================
	 * Variation popover
	 * ==================================================================== */

	var openPopover = null;

	function closePopover() {
		if (!openPopover) {
			return;
		}

		if (openPopover.release) {
			openPopover.release();
		}

		openPopover.el.remove();
		openPopover = null;

		document.removeEventListener('keydown', onPopoverKeydown);
		document.removeEventListener('click', onOutsideClick, true);
		window.removeEventListener('resize', closePopover);
		window.removeEventListener('scroll', closePopover, true);
	}

	function onPopoverKeydown(event) {
		if (event.key === 'Escape') {
			closePopover();
		}
	}

	function onOutsideClick(event) {
		if (openPopover && !openPopover.el.contains(event.target) && event.target !== openPopover.trigger) {
			closePopover();
		}
	}

	/**
	 * Position the popover next to its trigger, flipping when it would
	 * overflow the viewport.
	 */
	function position(el, trigger) {
		var rect = trigger.getBoundingClientRect();
		var width = el.offsetWidth;
		var height = el.offsetHeight;
		var margin = 8;

		var top = rect.bottom + window.scrollY + margin;
		var left = rect.right + window.scrollX - width;

		// Flip above when there is not room below.
		if (rect.bottom + height + margin > window.innerHeight && rect.top - height - margin > 0) {
			top = rect.top + window.scrollY - height - margin;
		}

		// Clamp horizontally to the viewport.
		left = Math.max(margin + window.scrollX, Math.min(left, window.scrollX + window.innerWidth - width - margin));

		el.style.top = top + 'px';
		el.style.left = left + 'px';
	}

	function escapeHtml(value) {
		var div = document.createElement('div');
		div.textContent = value == null ? '' : String(value);
		return div.innerHTML;
	}

	/**
	 * Open the variation chooser for a variable product.
	 *
	 * @param {HTMLElement} trigger Bag or Buy Now button.
	 * @param {Object}      [opts]  { buyNow }
	 */
	function openVariationPopover(trigger, opts) {
		opts = opts || {};
		closePopover();

		setLoading(trigger, true);

		AP.request('variation_form', { product_id: trigger.dataset.productId })
			.then(function (spec) {
				var el = document.createElement('div');
				el.className = 'ap-varpop';
				el.setAttribute('role', 'dialog');
				el.setAttribute('aria-modal', 'false');
				el.setAttribute('aria-label', i18n.selectOptions);

				var html =
					'<button type="button" class="ap-icon-btn ap-icon-btn--bare ap-varpop__close" ' +
					'aria-label="' + escapeHtml(i18n.closeCart || 'Close') + '">' +
					'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
					'stroke-width="1.8" stroke-linecap="round" aria-hidden="true">' +
					'<path d="M6 6l12 12M18 6 6 18"/></svg></button>' +
					'<p class="ap-varpop__title">' + escapeHtml(spec.title) + '</p>';

				spec.attributes.forEach(function (attr) {
					html +=
						'<div class="ap-varpop__row" data-attr="' + escapeHtml(attr.name) + '">' +
						'<span class="ap-varpop__label">' + escapeHtml(attr.label) + '</span>' +
						'<div class="ap-varpop__opts" role="group" aria-label="' + escapeHtml(attr.label) + '">';

					attr.options.forEach(function (option) {
						html +=
							'<button type="button" class="ap-varpop__opt" aria-pressed="false" ' +
							'data-value="' + escapeHtml(option.value) + '">' +
							escapeHtml(option.label) +
							'</button>';
					});

					html += '</div></div>';
				});

				html +=
					'<div class="ap-varpop__foot">' +
					'<span class="ap-varpop__price">' + escapeHtml(spec.priceHtml) + '</span>' +
					'<button type="button" class="ap-btn ap-btn--primary ap-btn--sm" data-varpop-submit disabled>' +
					escapeHtml(opts.buyNow ? 'এখনই কিনুন' : i18n.added ? 'যোগ করুন' : 'Add') +
					'</button></div>';

				el.innerHTML = html;
				document.body.appendChild(el);
				position(el, trigger);

				openPopover = {
					el: el,
					trigger: trigger,
					spec: spec,
					selection: {},
					release: AP.trapFocus(el)
				};

				var firstOption = el.querySelector('.ap-varpop__opt');
				if (firstOption) {
					firstOption.focus();
				}

				document.addEventListener('keydown', onPopoverKeydown);
				// Capture phase so the outside-click check runs before the
				// delegated card handlers below it.
				document.addEventListener('click', onOutsideClick, true);
				window.addEventListener('resize', closePopover);
				window.addEventListener('scroll', closePopover, true);

				wirePopover(el, opts);
			})
			.catch(function (error) {
				AP.toast(error.message || i18n.genericError, 'error');
			})
			.finally(function () {
				setLoading(trigger, false);
			});
	}

	/**
	 * Find the variation matching the current selection, if complete.
	 *
	 * @returns {Object|null}
	 */
	function matchVariation() {
		if (!openPopover) {
			return null;
		}

		var spec = openPopover.spec;
		var selection = openPopover.selection;

		if (Object.keys(selection).length !== spec.attributes.length) {
			return null;
		}

		return (
			spec.variations.filter(function (variation) {
				return spec.attributes.every(function (attr) {
					var wanted = variation.attributes[attr.name];
					// An empty value on the variation means "any", which is
					// how WooCommerce encodes a wildcard attribute.
					return wanted === '' || wanted === selection[attr.name];
				});
			})[0] || null
		);
	}

	function refreshPopoverState() {
		var el = openPopover.el;
		var match = matchVariation();
		var submit = el.querySelector('[data-varpop-submit]');
		var price = el.querySelector('.ap-varpop__price');

		if (match) {
			price.textContent = match.price;
			submit.disabled = !(match.inStock && match.purchasable);
			openPopover.match = match;
		} else {
			price.textContent = openPopover.spec.priceHtml;
			submit.disabled = true;
			openPopover.match = null;
		}
	}

	function wirePopover(el, opts) {
		el.addEventListener('click', function (event) {
			var option = event.target.closest('.ap-varpop__opt');

			if (option) {
				var row = option.closest('.ap-varpop__row');
				var attr = row.dataset.attr;
				var pressed = option.getAttribute('aria-pressed') === 'true';

				Array.prototype.forEach.call(
					row.querySelectorAll('.ap-varpop__opt'),
					function (sibling) {
						sibling.setAttribute('aria-pressed', 'false');
					}
				);

				if (pressed) {
					delete openPopover.selection[attr];
				} else {
					option.setAttribute('aria-pressed', 'true');
					openPopover.selection[attr] = option.dataset.value;
				}

				refreshPopoverState();
				return;
			}

			if (event.target.closest('.ap-varpop__close')) {
				closePopover();
				return;
			}

			var submit = event.target.closest('[data-varpop-submit]');

			if (!submit || !openPopover.match) {
				return;
			}

			var payload = {
				product_id: openPopover.trigger.dataset.productId,
				variation_id: openPopover.match.id,
				quantity: 1
			};

			Object.keys(openPopover.selection).forEach(function (key) {
				payload['variation[' + key + ']'] = openPopover.selection[key];
			});

			if (opts.buyNow) {
				setLoading(submit, true);

				AP.request('buy_now', payload)
					.then(function (result) {
						window.location.href = result.redirect || AP.data.checkoutUrl;
					})
					.catch(function (error) {
						AP.toast(error.message || i18n.addFailed, 'error');
						setLoading(submit, false);
					});

				return;
			}

			addToCart(submit, payload, true)
				.then(closePopover)
				.catch(function () {
					/* Toasted already. */
				});
		});
	}

	/* ======================================================================
	 * Single-product form — reuse the same AJAX path so the drawer opens
	 * there too, instead of the default full-page reload.
	 * ==================================================================== */

	AP.delegate('submit', 'form.cart', function (event, form) {
		// Leave grouped and external products to WooCommerce: grouped posts
		// an array of quantities our endpoint does not model, and external
		// products navigate off-site.
		if (form.closest('.product-type-grouped') || form.closest('.product-type-external')) {
			return;
		}

		var button = form.querySelector('button[type="submit"], .single_add_to_cart_button');

		if (!button || button.classList.contains('disabled')) {
			return;
		}

		var productId = form.querySelector('[name="add-to-cart"]');
		var variationId = form.querySelector('[name="variation_id"]');

		// Without a product id we cannot build the request; fall through to
		// the native form post rather than swallowing the submit.
		if (!productId || !productId.value) {
			return;
		}

		event.preventDefault();

		var payload = {
			product_id: productId.value,
			quantity: (form.querySelector('[name="quantity"]') || {}).value || 1
		};

		if (variationId && variationId.value) {
			payload.variation_id = variationId.value;

			Array.prototype.forEach.call(
				form.querySelectorAll('[name^="attribute_"]'),
				function (field) {
					payload['variation[' + field.name + ']'] = field.value;
				}
			);
		}

		addToCart(button, payload, true).catch(function () {
			/* Toasted already. */
		});
	});
})(window, document);
