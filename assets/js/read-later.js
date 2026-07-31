/**
 * "একটু পরে দেখুন" — read-later bookmarks.
 *
 * localStorage is the source of truth for guests. Logged-in users get the
 * same list mirrored to user meta; the server merges rather than replaces,
 * so bookmarks made before signing in are absorbed on first interaction.
 */
(function (window, document) {
	'use strict';

	var AP = window.AP;

	if (!AP) {
		return;
	}

	var KEY = 'ap_read_later';
	var ids = [];

	/* ======================================================================
	 * Storage
	 *
	 * Every access is guarded: Safari private mode throws on setItem, and
	 * a thrown storage error must degrade to "bookmarks do not persist",
	 * never to a broken button.
	 * ==================================================================== */

	function load() {
		try {
			var raw = window.localStorage.getItem(KEY);
			var parsed = raw ? JSON.parse(raw) : [];
			return Array.isArray(parsed) ? parsed.map(Number).filter(Boolean) : [];
		} catch (error) {
			return [];
		}
	}

	function persist() {
		try {
			window.localStorage.setItem(KEY, JSON.stringify(ids));
		} catch (error) {
			/* Quota or private mode — the in-memory list still works. */
		}
	}

	/* ======================================================================
	 * Rendering
	 * ==================================================================== */

	function isSaved(id) {
		return ids.indexOf(Number(id)) !== -1;
	}

	function paint(button) {
		var saved = isSaved(button.dataset.productId);

		button.setAttribute('aria-pressed', saved ? 'true' : 'false');
		button.classList.toggle('is-active', saved);

		var path = button.querySelector('svg path');

		// Swap outline for solid by toggling the fill, rather than swapping
		// the whole SVG — keeps the icon from reflowing on click.
		if (path) {
			button.querySelector('svg').setAttribute('fill', saved ? 'currentColor' : 'none');
		}

		var label = button.querySelector('.ap-sr-only');

		if (label) {
			label.textContent = saved
				? 'পরে দেখার তালিকা থেকে সরান'
				: 'একটু পরে দেখার তালিকায় রাখুন';
		}
	}

	function paintAll(root) {
		Array.prototype.forEach.call(
			(root || document).querySelectorAll('[data-ap-read-later]'),
			paint
		);
	}

	/* ======================================================================
	 * Toggle
	 * ==================================================================== */

	AP.delegate('click', '[data-ap-read-later]', function (event, button) {
		event.preventDefault();
		event.stopPropagation();

		var id = Number(button.dataset.productId);

		if (!id) {
			return;
		}

		var nowSaved = !isSaved(id);

		if (nowSaved) {
			ids.push(id);
		} else {
			ids = ids.filter(function (existing) {
				return existing !== id;
			});
		}

		persist();
		paintAll();

		AP.toast(
			nowSaved ? AP.i18n.savedLater : AP.i18n.removedLater,
			'success'
		);

		AP.emit('ap:readlater:changed', { ids: ids, id: id, saved: nowSaved });

		// Mirror to the account only when there is an account to mirror to.
		if (!AP.data.loggedIn) {
			return;
		}

		AP.request('toggle_read_later', {
			product_id: id,
			active: nowSaved ? 1 : 0,
			local: ids
		})
			.then(function (result) {
				if (result.synced && Array.isArray(result.ids)) {
					// Adopt the server's merged view so a list built on
					// another device shows up here too.
					ids = result.ids.map(Number);
					persist();
					paintAll();
				}
			})
			.catch(function () {
				/* Local state already updated; a failed sync is not worth
				   interrupting the shopper over. */
			});
	});

	/* ======================================================================
	 * Boot
	 * ==================================================================== */

	function init() {
		var local = load();
		var server = Array.isArray(AP.data.readLater) ? AP.data.readLater.map(Number) : [];

		// Union of both sides. The server does the same merge on write, so
		// the two converge after the first toggle.
		ids = local.slice();

		server.forEach(function (id) {
			if (ids.indexOf(id) === -1) {
				ids.push(id);
			}
		});

		persist();
		paintAll();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	// Cards inserted by the shop filter or a carousel refill need painting.
	AP.on('ap:cards:rendered', function (event) {
		paintAll(event.detail && event.detail.container);
	});

	AP.on('ap:fragments:applied', function () {
		paintAll();
	});
})(window, document);
