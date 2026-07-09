/**
 * Blog roll infinite scroll.
 *
 * A sentinel near the end of the grid pulls in the next batch of cards as it
 * approaches the viewport, so the roll never renders a /page/2/ link. WordPress
 * still serves those URLs; inc/blog-roll.php marks them noindex.
 *
 * The sentinel is a real <button>. Scrolling is not an input a keyboard user
 * has, so auto-load alone would strand them at the first batch; the button gives
 * them the same affordance, and covers browsers without IntersectionObserver.
 */
(function () {
	'use strict';

	var button = document.getElementById('lean-load-more');
	var sentinel = document.getElementById('lean-load-more-sentinel');
	var grid = document.getElementById('lean-post-grid');
	if (!button || !sentinel || !grid) {
		return;
	}

	var status = document.getElementById('lean-load-more-status');
	var ajaxUrl = button.getAttribute('data-ajax-url');
	var paged = parseInt(button.getAttribute('data-paged'), 10) || 1;

	var idleLabel = button.textContent.trim();
	var loading = false;
	var exhausted = false;
	// Cleared when a request fails, so a dead network can't drive the observer
	// into a retry storm. The button stays, and a click turns it back on.
	var autoLoad = true;
	var observer = null;

	function announce(message) {
		if (status) {
			status.textContent = message;
		}
	}

	function finish() {
		button.textContent = idleLabel;
		button.removeAttribute('aria-busy');
		loading = false;
	}

	function stop() {
		exhausted = true;
		if (observer) {
			observer.disconnect();
		}

		// Focus may be sitting on the button we are about to remove: the user clicked
		// it, or clicked earlier and an auto-load has now finished the list. Deleting
		// the focused element drops focus to <body>, which sends the next Tab back to
		// the top of the document. Park it on the last card first.
		if (document.activeElement && sentinel.contains(document.activeElement)) {
			var lastCard = grid.lastElementChild;
			var lastLink = lastCard && lastCard.querySelector('a');
			if (lastLink) {
				lastLink.focus();
			}
		}

		sentinel.remove();
	}

	/**
	 * @param {boolean} moveFocus Only for explicit clicks. Auto-load must never
	 *   yank focus out from under someone who is simply scrolling.
	 */
	function loadMore(moveFocus) {
		if (loading || exhausted) {
			return;
		}
		loading = true;
		button.setAttribute('aria-busy', 'true');
		button.textContent = 'Loading…';
		announce('Loading more posts.');

		var body = new URLSearchParams();
		body.set('action', 'lean_load_more_posts');
		body.set('paged', String(paged + 1));

		fetch(ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
			credentials: 'same-origin'
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('HTTP ' + response.status);
				}
				return response.json();
			})
			.then(function (payload) {
				if (!payload || !payload.success || !payload.data) {
					throw new Error('Unexpected response');
				}

				var firstNewCard = null;
				if (payload.data.html) {
					var staging = document.createElement('div');
					staging.innerHTML = payload.data.html;
					firstNewCard = staging.firstElementChild;
					while (staging.firstChild) {
						grid.appendChild(staging.firstChild);
					}
				}

				paged += 1;
				button.setAttribute('data-paged', String(paged));
				autoLoad = true;

				if (moveFocus && firstNewCard) {
					// No tabindex: the card title is a real link, already focusable,
					// and pinning tabindex="-1" would drop it from the tab order.
					var link = firstNewCard.querySelector('a');
					if (link) {
						link.focus();
					}
				}

				if (!payload.data.has_more) {
					announce('All posts loaded.');
					finish();
					stop();
					return;
				}

				announce('More posts loaded.');
				finish();

				// The sentinel may still be on screen after appending — a tall viewport,
				// or a short batch. IntersectionObserver only fires on a *change*, so
				// re-observe to re-evaluate and keep the chain going.
				if (observer && autoLoad) {
					observer.unobserve(sentinel);
					observer.observe(sentinel);
				}
			})
			.catch(function () {
				autoLoad = false;
				finish();
				announce('Could not load more posts. Select to try again.');
			});
	}

	button.addEventListener('click', function () {
		autoLoad = true;
		loadMore(true);
	});

	if ('IntersectionObserver' in window) {
		observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting && autoLoad) {
					loadMore(false);
				}
			});
		}, { rootMargin: '300px 0px' });

		observer.observe(sentinel);
	}
})();
