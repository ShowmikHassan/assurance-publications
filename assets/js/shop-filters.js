/**
 * Shop archive filtering.
 *
 * Filters mutate the URL via pushState and fetch a fresh grid. Every state
 * is therefore a real, shareable URL that the server can render on its own
 * — the AJAX call is an optimisation, not the source of truth.
 */
(function (window, document) {
	'use strict';

	var AP = window.AP;

	if (!AP) {
		return;
	}

	var sidebar = document.querySelector('[data-ap-filters]');
	var grid = document.querySelector('[data-ap-grid]');

	if (!grid) {
		return;
	}

	var bounds = AP.data.priceBounds || { min: 0, max: 1000 };
	var inFlight = null;

	/* ======================================================================
	 * State
	 * ==================================================================== */

	function readState() {
		var checked = Array.prototype.map.call(
			document.querySelectorAll('[data-ap-cat]:checked'),
			function (input) {
				return input.value;
			}
		);

		var minInput = document.querySelector('[data-ap-range-min]');
		var maxInput = document.querySelector('[data-ap-range-max]');
		var ordering = document.querySelector('.woocommerce-ordering select');

		return {
			cats: checked,
			min: minInput ? Number(minInput.value) : bounds.min,
			max: maxInput ? Number(maxInput.value) : bounds.max,
			orderby: ordering ? ordering.value : '',
			paged: 1
		};
	}

	function stateToUrl(state) {
		var url = new URL(window.location.href);
		var params = url.searchParams;

		params.delete('product_cat');
		params.delete('min_price');
		params.delete('max_price');
		params.delete('paged');

		if (state.cats.length) {
			params.set('product_cat', state.cats.join(','));
		}

		// Only put price in the URL when it actually narrows the catalogue,
		// so the default view keeps a clean canonical URL.
		if (state.min > bounds.min) {
			params.set('min_price', String(state.min));
		}

		if (state.max < bounds.max) {
			params.set('max_price', String(state.max));
		}

		if (state.orderby) {
			params.set('orderby', state.orderby);
		} else {
			params.delete('orderby');
		}

		if (state.paged > 1) {
			params.set('paged', String(state.paged));
		}

		return url.toString();
	}

	/* ======================================================================
	 * Fetch
	 * ==================================================================== */

	var apply = AP.debounce(function () {
		var state = readState();

		// Cancel a filter request the shopper has already superseded.
		if (inFlight) {
			inFlight.abort();
		}

		inFlight = new AbortController();

		grid.classList.add('ap-is-busy');

		AP.request(
			'shop_filter',
			{
				cats: state.cats,
				min: state.min,
				max: state.max,
				orderby: state.orderby,
				paged: state.paged
			},
			{ signal: inFlight.signal }
		)
			.then(function (result) {
				var list = grid.querySelector('ul.products');

				if (!list) {
					grid.innerHTML = result.html;
				} else if (result.found === 0) {
					// The empty state is a plain block, not a card — it must
					// span the whole grid rather than sit in one column.
					list.innerHTML = '<li class="ap-empty-wrap">' + result.html + '</li>';
				} else {
					// Cards come back as bare <article>s; the archive wraps
					// each in the <li> WooCommerce's grid CSS expects.
					list.innerHTML = wrapCards(result.html);
				}

				var count = document.querySelector('[data-ap-result-count]');

				if (count) {
					count.textContent = result.summary;
				}

				// Pagination no longer matches the filtered set; the server
				// render on navigation restores it.
				var pagination = document.querySelector('.woocommerce-pagination');

				if (pagination) {
					pagination.remove();
				}

				window.history.pushState({ apFilter: true }, '', stateToUrl(state));

				toggleClearButton(state);
				AP.emit('ap:cards:rendered', { container: grid });
			})
			.catch(function (error) {
				if (error.name === 'AbortError') {
					return;
				}
				AP.toast(error.message || AP.i18n.genericError, 'error');
			})
			.finally(function () {
				grid.classList.remove('ap-is-busy');
				inFlight = null;
			});
	}, 320);

	/**
	 * Wrap bare card markup in the <li> the products grid expects.
	 */
	function wrapCards(html) {
		var holder = document.createElement('div');
		holder.innerHTML = html;

		return Array.prototype.map
			.call(holder.querySelectorAll('.ap-card'), function (card) {
				return '<li class="product ap-card-item">' + card.outerHTML + '</li>';
			})
			.join('');
	}

	function toggleClearButton(state) {
		var clear = document.querySelector('[data-ap-clear-filters]');

		if (!clear) {
			return;
		}

		var active =
			state.cats.length > 0 || state.min > bounds.min || state.max < bounds.max;

		clear.classList.toggle('ap-hidden', !active);
	}

	/* ======================================================================
	 * Controls
	 * ==================================================================== */

	AP.delegate('change', '[data-ap-cat]', apply);

	AP.delegate('change', '.woocommerce-ordering select', function (event) {
		// Take over the native form submit so ordering joins the AJAX path.
		event.preventDefault();
		apply();
	});

	AP.delegate('submit', 'form.woocommerce-ordering', function (event) {
		event.preventDefault();
	});

	AP.delegate('click', '[data-ap-clear-filters]', function (event) {
		event.preventDefault();

		Array.prototype.forEach.call(
			document.querySelectorAll('[data-ap-cat]:checked'),
			function (input) {
				input.checked = false;
			}
		);

		var minInput = document.querySelector('[data-ap-range-min]');
		var maxInput = document.querySelector('[data-ap-range-max]');

		if (minInput) {
			minInput.value = bounds.min;
		}

		if (maxInput) {
			maxInput.value = bounds.max;
		}

		paintRange();
		apply();
	});

	/* ======================================================================
	 * Price range
	 * ==================================================================== */

	var rangeMin = document.querySelector('[data-ap-range-min]');
	var rangeMax = document.querySelector('[data-ap-range-max]');
	var rangeFill = document.querySelector('[data-ap-range-fill]');
	var outMin = document.querySelector('[data-ap-range-out-min]');
	var outMax = document.querySelector('[data-ap-range-out-max]');

	function paintRange() {
		if (!rangeMin || !rangeMax) {
			return;
		}

		var lo = Number(rangeMin.value);
		var hi = Number(rangeMax.value);
		var span = bounds.max - bounds.min || 1;

		var left = ((lo - bounds.min) / span) * 100;
		var right = ((hi - bounds.min) / span) * 100;

		if (rangeFill) {
			rangeFill.style.insetInlineStart = left + '%';
			rangeFill.style.inlineSize = Math.max(0, right - left) + '%';
		}

		if (outMin) {
			outMin.textContent = AP.formatPrice(lo);
		}

		if (outMax) {
			outMax.textContent = AP.formatPrice(hi);
		}
	}

	/**
	 * Keep the two handles from crossing.
	 *
	 * Rather than clamping the dragged handle (which feels like the slider
	 * is stuck), the handles swap roles when they meet — matching how
	 * every native dual-range control behaves.
	 */
	function guardHandles(moved) {
		if (!rangeMin || !rangeMax) {
			return;
		}

		var lo = Number(rangeMin.value);
		var hi = Number(rangeMax.value);

		if (lo <= hi) {
			return;
		}

		if (moved === 'min') {
			rangeMin.value = hi;
		} else {
			rangeMax.value = lo;
		}
	}

	if (rangeMin && rangeMax) {
		rangeMin.addEventListener('input', function () {
			guardHandles('min');
			paintRange();
		});

		rangeMax.addEventListener('input', function () {
			guardHandles('max');
			paintRange();
		});

		// Query only once the handle is released; filtering on every pixel
		// of a drag would fire dozens of requests.
		rangeMin.addEventListener('change', apply);
		rangeMax.addEventListener('change', apply);

		paintRange();
	}

	/* ======================================================================
	 * Mobile panel
	 * ==================================================================== */

	var scrim = document.querySelector('[data-ap-filters-close].ap-shop__scrim') ||
		document.querySelector('.ap-shop__scrim');
	var panelRelease = null;

	function openPanel() {
		if (!sidebar) {
			return;
		}

		sidebar.classList.add('is-open');

		if (scrim) {
			scrim.hidden = false;
		}

		AP.lockScroll();
		panelRelease = AP.trapFocus(sidebar);

		var close = sidebar.querySelector('[data-ap-filters-close]');

		if (close) {
			close.focus();
		}

		setExpanded(true);
		document.addEventListener('keydown', onPanelKey);
	}

	function closePanel() {
		if (!sidebar || !sidebar.classList.contains('is-open')) {
			return;
		}

		sidebar.classList.remove('is-open');

		if (scrim) {
			scrim.hidden = true;
		}

		AP.unlockScroll();

		if (panelRelease) {
			panelRelease();
			panelRelease = null;
		}

		setExpanded(false);
		document.removeEventListener('keydown', onPanelKey);
	}

	function setExpanded(state) {
		var trigger = document.querySelector('[data-ap-filters-open]');

		if (trigger) {
			trigger.setAttribute('aria-expanded', state ? 'true' : 'false');
		}
	}

	function onPanelKey(event) {
		if (event.key === 'Escape') {
			event.preventDefault();
			closePanel();
		}
	}

	AP.delegate('click', '[data-ap-filters-open]', function (event) {
		event.preventDefault();
		openPanel();
	});

	AP.delegate('click', '[data-ap-filters-close]', function (event) {
		event.preventDefault();
		closePanel();
	});

	/* ======================================================================
	 * Back/forward
	 *
	 * pushState alone leaves the back button showing a stale grid, so a
	 * popstate reloads the server-rendered view for that URL.
	 * ==================================================================== */

	window.addEventListener('popstate', function (event) {
		if (event.state && event.state.apFilter) {
			window.location.reload();
		}
	});
})(window, document);
