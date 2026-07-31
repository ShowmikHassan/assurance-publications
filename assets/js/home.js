/**
 * Home page.
 *
 * The hero slider and product tabs are owned by the Assurance Blocks
 * plugin. This file only handles home-specific behaviour that spans
 * blocks — currently the reveal-on-scroll for band sections.
 */
(function (window, document) {
	'use strict';

	var AP = window.AP;

	if (!AP) {
		return;
	}

	/**
	 * Fade bands in as they enter the viewport.
	 *
	 * Applied via JS rather than CSS-only so that a visitor with JS
	 * disabled — or a crawler — sees fully-rendered content instead of
	 * sections stuck at opacity 0.
	 */
	function initReveal() {
		if (
			!('IntersectionObserver' in window) ||
			window.matchMedia('(prefers-reduced-motion: reduce)').matches
		) {
			return;
		}

		var bands = Array.prototype.slice.call(
			document.querySelectorAll('.ap-band')
		);

		if (!bands.length) {
			return;
		}

		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting) {
						return;
					}

					entry.target.classList.add('is-revealed');
					observer.unobserve(entry.target);
				});
			},
			{ rootMargin: '0px 0px -8% 0px', threshold: 0.05 }
		);

		bands.forEach(function (band, index) {
			// The first band is above the fold — revealing it would mean a
			// visible flash on load, so mark it done immediately.
			if (index === 0) {
				band.classList.add('is-revealed');
				return;
			}

			band.classList.add('ap-reveal');
			observer.observe(band);
		});
	}

	function init() {
		initReveal();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(window, document);
