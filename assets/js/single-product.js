/**
 * Single product: gallery lightbox and tab enhancement.
 *
 * Replaces PhotoSwipe (~40 KB + CSS) with a focused lightbox that matches
 * the design system and handles only what a book gallery needs: zoom,
 * swipe between images, keyboard navigation.
 */
(function (window, document) {
	'use strict';

	var AP = window.AP;

	if (!AP) {
		return;
	}

	/* ======================================================================
	 * Buy Now
	 * ==================================================================== */

	AP.delegate('click', '[data-ap-buy-now-single]', function (event, button) {
		event.preventDefault();

		var form = document.querySelector('form.cart');

		if (!form) {
			return;
		}

		var productField = form.querySelector('[name="add-to-cart"]');
		var variationField = form.querySelector('[name="variation_id"]');
		var qtyField = form.querySelector('[name="quantity"]');

		if (!productField || !productField.value) {
			return;
		}

		var payload = {
			product_id: productField.value,
			quantity: qtyField ? qtyField.value : 1
		};

		if (variationField && variationField.value) {
			payload.variation_id = variationField.value;

			Array.prototype.forEach.call(
				form.querySelectorAll('[name^="attribute_"]'),
				function (field) {
					payload['variation[' + field.name + ']'] = field.value;
				}
			);
		}

		button.classList.add('is-loading');
		button.disabled = true;

		AP.request('buy_now', payload)
			.then(function (result) {
				window.location.href = result.redirect || AP.data.checkoutUrl;
			})
			.catch(function (error) {
				AP.toast(error.message || AP.i18n.genericError, 'error');
				button.classList.remove('is-loading');
				button.disabled = false;
			});
	});

	/* ======================================================================
	 * Gallery lightbox
	 * ==================================================================== */

	var lightbox = null;
	var release = null;
	var images = [];
	var index = 0;

	function collectImages() {
		var gallery = document.querySelector('.woocommerce-product-gallery');

		if (!gallery) {
			return [];
		}

		return Array.prototype.map.call(
			gallery.querySelectorAll('.woocommerce-product-gallery__image a, .woocommerce-product-gallery__image img'),
			function (node) {
				// The anchor points at the full-size file; the img only has
				// the display size, so prefer the anchor when present.
				return node.tagName === 'A'
					? node.getAttribute('href')
					: node.getAttribute('data-large_image') || node.currentSrc || node.src;
			}
		).filter(function (src, i, all) {
			return src && all.indexOf(src) === i;
		});
	}

	function render() {
		var img = lightbox.querySelector('.ap-lightbox__img');
		var counter = lightbox.querySelector('.ap-lightbox__count');

		img.src = images[index];
		img.alt = '';

		if (counter) {
			counter.textContent = index + 1 + ' / ' + images.length;
		}

		lightbox.querySelector('[data-lb-prev]').disabled = images.length < 2;
		lightbox.querySelector('[data-lb-next]').disabled = images.length < 2;
	}

	function step(delta) {
		if (images.length < 2) {
			return;
		}

		index = (index + delta + images.length) % images.length;
		render();
	}

	function openLightbox(startAt) {
		images = collectImages();

		if (!images.length) {
			return;
		}

		index = Math.max(0, Math.min(startAt || 0, images.length - 1));

		lightbox = document.createElement('div');
		lightbox.className = 'ap-lightbox';
		lightbox.setAttribute('role', 'dialog');
		lightbox.setAttribute('aria-modal', 'true');
		lightbox.setAttribute('aria-label', 'ছবি বড় করে দেখুন');

		lightbox.innerHTML =
			'<div class="ap-lightbox__scrim" data-lb-close></div>' +
			'<div class="ap-lightbox__stage">' +
			'<img class="ap-lightbox__img" alt="" />' +
			'</div>' +
			'<button type="button" class="ap-lightbox__btn ap-lightbox__btn--close" data-lb-close aria-label="বন্ধ করুন">' +
			icon('M6 6l12 12M18 6 6 18') +
			'</button>' +
			'<button type="button" class="ap-lightbox__btn ap-lightbox__btn--prev" data-lb-prev aria-label="আগের ছবি">' +
			icon('m15 6-6 6 6 6') +
			'</button>' +
			'<button type="button" class="ap-lightbox__btn ap-lightbox__btn--next" data-lb-next aria-label="পরের ছবি">' +
			icon('m9 6 6 6-6 6') +
			'</button>' +
			'<p class="ap-lightbox__count" aria-live="polite"></p>';

		document.body.appendChild(lightbox);
		render();

		AP.lockScroll();
		release = AP.trapFocus(lightbox);

		lightbox.querySelector('[data-lb-close]').focus();

		document.addEventListener('keydown', onKey);
		wireSwipe();

		window.requestAnimationFrame(function () {
			lightbox.classList.add('is-open');
		});
	}

	function icon(path) {
		return (
			'<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
			'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
			'<path d="' + path + '"/></svg>'
		);
	}

	function closeLightbox() {
		if (!lightbox) {
			return;
		}

		document.removeEventListener('keydown', onKey);
		AP.unlockScroll();

		if (release) {
			release();
			release = null;
		}

		lightbox.classList.remove('is-open');

		var node = lightbox;
		lightbox = null;

		window.setTimeout(function () {
			node.remove();
		}, 220);
	}

	function onKey(event) {
		if (event.key === 'Escape') {
			event.preventDefault();
			closeLightbox();
		} else if (event.key === 'ArrowRight') {
			step(1);
		} else if (event.key === 'ArrowLeft') {
			step(-1);
		}
	}

	function wireSwipe() {
		var startX = 0;
		var stage = lightbox.querySelector('.ap-lightbox__stage');

		stage.addEventListener(
			'touchstart',
			function (event) {
				startX = event.touches[0].clientX;
			},
			{ passive: true }
		);

		stage.addEventListener(
			'touchend',
			function (event) {
				var delta = event.changedTouches[0].clientX - startX;

				if (Math.abs(delta) > 45) {
					step(delta < 0 ? 1 : -1);
				}
			},
			{ passive: true }
		);
	}

	AP.delegate('click', '.ap-lightbox [data-lb-close]', function (event) {
		event.preventDefault();
		closeLightbox();
	});

	AP.delegate('click', '[data-lb-prev]', function (event) {
		event.preventDefault();
		step(-1);
	});

	AP.delegate('click', '[data-lb-next]', function (event) {
		event.preventDefault();
		step(1);
	});

	// Open from the main gallery image or a thumbnail.
	AP.delegate('click', '.woocommerce-product-gallery__image', function (event, node) {
		event.preventDefault();

		var all = Array.prototype.slice.call(
			document.querySelectorAll('.woocommerce-product-gallery__image')
		);

		openLightbox(all.indexOf(node));
	});

	/* ======================================================================
	 * Zoom affordance
	 *
	 * The gallery is only clickable-to-zoom if there is something to zoom
	 * into, so the cursor and the hint button are added at runtime rather
	 * than baked into the template.
	 * ==================================================================== */

	function markGallery() {
		var gallery = document.querySelector('.woocommerce-product-gallery');

		if (!gallery || !collectImages().length) {
			return;
		}

		gallery.classList.add('ap-gallery-zoomable');

		if (gallery.querySelector('.ap-gallery__zoom')) {
			return;
		}

		var hint = document.createElement('button');
		hint.type = 'button';
		hint.className = 'ap-gallery__zoom';
		hint.setAttribute('aria-label', 'ছবি বড় করে দেখুন');
		hint.innerHTML = icon('M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm10 2-4.35-4.35M11 8v6M8 11h6');
		hint.addEventListener('click', function (event) {
			event.preventDefault();
			openLightbox(0);
		});

		var wrap = gallery.querySelector('.woocommerce-product-gallery__wrapper') || gallery;
		wrap.appendChild(hint);
	}

	/* ======================================================================
	 * Tabs
	 *
	 * WooCommerce's tabs are anchor-driven and lose their ARIA state when
	 * jQuery is absent. Wire the roles and keyboard model properly.
	 * ==================================================================== */

	function initTabs() {
		var list = document.querySelector('.wc-tabs');

		if (!list) {
			return;
		}

		list.setAttribute('role', 'tablist');

		var tabs = Array.prototype.slice.call(list.querySelectorAll('li > a'));

		tabs.forEach(function (tab) {
			var li = tab.parentElement;
			var panelId = tab.getAttribute('href');

			li.setAttribute('role', 'presentation');
			tab.setAttribute('role', 'tab');

			if (panelId && panelId.charAt(0) === '#') {
				var panel = document.querySelector(panelId);

				if (panel) {
					panel.setAttribute('role', 'tabpanel');
					panel.setAttribute('aria-labelledby', tab.id || '');
				}
			}

			var active = li.classList.contains('active');
			tab.setAttribute('aria-selected', active ? 'true' : 'false');
			tab.setAttribute('tabindex', active ? '0' : '-1');
		});

		// Roving tabindex: arrows move between tabs, Tab leaves the strip.
		list.addEventListener('keydown', function (event) {
			var current = tabs.indexOf(document.activeElement);

			if (current === -1) {
				return;
			}

			var next = null;

			if (event.key === 'ArrowRight') {
				next = (current + 1) % tabs.length;
			} else if (event.key === 'ArrowLeft') {
				next = (current - 1 + tabs.length) % tabs.length;
			} else if (event.key === 'Home') {
				next = 0;
			} else if (event.key === 'End') {
				next = tabs.length - 1;
			}

			if (next === null) {
				return;
			}

			event.preventDefault();
			tabs[next].focus();
			tabs[next].click();
		});

		list.addEventListener('click', function () {
			// WooCommerce toggles the .active class itself; mirror it into
			// ARIA once the class has settled.
			window.setTimeout(function () {
				tabs.forEach(function (tab) {
					var active = tab.parentElement.classList.contains('active');
					tab.setAttribute('aria-selected', active ? 'true' : 'false');
					tab.setAttribute('tabindex', active ? '0' : '-1');
				});
			}, 0);
		});
	}

	function init() {
		markGallery();
		initTabs();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(window, document);
