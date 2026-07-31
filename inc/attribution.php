<?php
/**
 * Filename: attribution.php
 * Purpose: Capture the visitor's campaign source/medium so form leads can be attributed.
 *
 * Why a cookie instead of reading $_GET at submit time:
 * a visitor lands on a campaign URL (/service/?utm_source=google&utm_medium=cpc), reads a
 * couple of pages, and submits the form from somewhere else entirely. By the time the AJAX
 * POST fires, the query string is long gone. So we stamp a first-party cookie on the landing
 * page and read it back at submit.
 *
 * Why JS instead of PHP:
 * pages are served from a full-page cache, so on a cache hit PHP never sees the visitor's
 * query string at all. The snippet below runs client-side, which is cache-proof.
 *
 * The cookie (lean_attr) holds no personal data — campaign labels and a click id only —
 * and expires after 30 days.
 */

// ──────────────────────────────────────────────────────────────────────────────
// FRONT-END CAPTURE
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Print the attribution snippet exactly once per request.
 *
 * Emitted from both head paths: lean_head (standalone — Lean templates bypass wp_head())
 * and wp_head (integration, when a parent theme's header runs). The static guard makes a
 * double-emit impossible if a page ever runs both.
 *
 * @return void
 */
add_action('lean_head', 'lean_attribution_emit_script', 5);
add_action('wp_head', 'lean_attribution_emit_script', 5);
function lean_attribution_emit_script() {
	static $emitted = false;
	if ($emitted || is_admin()) {
		return;
	}
	$emitted = true;
	// The opt-out attributes matter: an optimizer that defers this until first interaction
	// would miss the landing page entirely on a bounce-and-return, and mislabel the visit as
	// direct. Cheap insurance against Rocket Loader / Autoptimize / LiteSpeed defaults.
	?>
<script data-cfasync="false" data-no-optimize="1" data-no-defer="1">
/* Lean attribution: stamp campaign source on the landing page, read back at form submit. */
(function (w, d) {
	var NAME = 'lean_attr', MAX_AGE = 30 * 86400, LIMIT = 100;

	function read() {
		var m = d.cookie.match(/(?:^|;\s*)lean_attr=([^;]*)/);
		if (!m) return null;
		try { return JSON.parse(decodeURIComponent(m[1])); } catch (e) { return null; }
	}
	// Short keys keep the cookie small — it rides along on every request.
	// s=source m=medium c=campaign t=term n=content k=click id l=landing path
	function write(o) {
		d.cookie = NAME + '=' + encodeURIComponent(JSON.stringify(o)) + ';path=/;max-age=' + MAX_AGE +
			';SameSite=Lax' + (w.location.protocol === 'https:' ? ';Secure' : '');
	}
	w.leanAttribution = read;

	var q;
	try { q = new URLSearchParams(w.location.search); } catch (e) { return; }
	function v(k) { return (q.get(k) || '').trim().slice(0, LIMIT); }

	// Ad platforms strip/replace UTMs often enough that the click id is the reliable signal.
	var gclid = v('gclid') || v('gbraid') || v('wbraid'),
		fbclid = v('fbclid'),
		msclkid = v('msclkid'),
		click = gclid || fbclid || msclkid,
		src = v('utm_source'),
		med = v('utm_medium'),
		touch = null;

	if (src || med || click) {
		// A tagged or paid click always wins — last touch is what closed the lead.
		touch = {
			s: src || (gclid ? 'google' : fbclid ? 'facebook' : msclkid ? 'bing' : ''),
			m: med || (click ? 'cpc' : ''),
			c: v('utm_campaign'), t: v('utm_term'), n: v('utm_content'),
			k: click, l: w.location.pathname
		};
	} else if (!read()) {
		// Untagged, and no earlier touch to preserve: derive something useful from the
		// referrer so the admin column reads "google / organic" rather than sitting blank.
		var host = '';
		try { host = new URL(d.referrer).hostname.replace(/^www\./, ''); } catch (e) {}

		if (!host || host === w.location.hostname.replace(/^www\./, '')) {
			touch = { s: 'direct', m: '(none)' };
		} else {
			var labels = host.split('.');
			function brand(list) {
				for (var i = 0; i < labels.length; i++) {
					if (list.indexOf(labels[i]) > -1) return labels[i];
				}
				return '';
			}
			var engine = brand(['google', 'bing', 'duckduckgo', 'yahoo', 'ecosia', 'brave', 'baidu', 'yandex', 'startpage', 'qwant', 'aol']),
				social = brand(['facebook', 'instagram', 'twitter', 'linkedin', 'pinterest', 'reddit', 'youtube', 'tiktok', 'threads']),
				// Assistant referrals are their own channel now — worth separating from
				// plain "referral" so the source column can be read at a glance.
				ai = brand(['chatgpt', 'openai', 'perplexity', 'claude', 'anthropic', 'copilot', 'gemini']);
			if (!social && (host === 't.co' || host === 'x.com')) social = 'twitter';
			if (ai === 'openai') ai = 'chatgpt';
			if (ai === 'anthropic') ai = 'claude';

			touch = ai ? { s: ai, m: 'ai' }
				: engine ? { s: engine, m: 'organic' }
				: social ? { s: social, m: 'social' }
				: { s: host.slice(0, LIMIT), m: 'referral' };
		}
		touch.c = ''; touch.t = ''; touch.n = ''; touch.k = ''; touch.l = w.location.pathname;
	}

	/* Only stamp when the touch actually changes. Two reasons: sites that carry UTMs across
	   internal links would otherwise rewrite the cookie on every page view (destroying the
	   real landing page), and an unchanged touch is the same visit, not a new one. */
	var prev = read(),
		isNewVisit = !!touch && (!prev || prev.s !== touch.s || prev.m !== touch.m || prev.c !== touch.c);
	if (isNewVisit) write(touch);

	/* Visit path — the pages walked before submitting. Kept in localStorage rather than the
	   cookie so it never rides along on every request, and because it outgrows a cookie fast.
	   The window is deliberately long: someone who reads a service page, leaves to compare
	   competitors and comes back to submit is exactly the story worth telling on a lead. */
	var PATH_KEY = 'lean_path', GAP = 24 * 60 * 60 * 1000, REVISIT = 5 * 60, MAX_STEPS = 20;
	try {
		var now = Date.now(),
			nowSec = Math.round(now / 1000),
			saved = JSON.parse(w.localStorage.getItem(PATH_KEY) || 'null'),
			fresh = saved && saved.ts && (now - saved.ts) < GAP && Array.isArray(saved.p),
			trail = fresh ? saved.p : [],
			here = w.location.pathname.slice(0, 191),
			last = trail.length ? trail[trail.length - 1] : null,
			// Coming back through a new source is a new arrival, not a reload — record it as
			// its own step and label it, so the reader sees "came back" rather than a
			// fabricated 18-minute dwell on the page they left.
			isReturn = isNewVisit && trail.length > 0;

		// Skip reloads, keep genuine revisits (A → B → A tells you something). The freshness
		// window matters: returning to the same page later is a new step, and silently reusing
		// the old arrival time would report a dwell of however long they were away.
		if (!last || last.u !== here || (nowSec - (last.t || 0)) > REVISIT || isReturn) {
			var step = { u: here, t: nowSec };
			if (isReturn) step.r = touch.s || 'direct';
			trail.push(step);
		}
		// On a long visit keep the entry page plus the most recent steps — the middle is the
		// least useful part, and the entry page is what pairs with the campaign source.
		if (trail.length > MAX_STEPS) trail = trail.slice(0, 1).concat(trail.slice(1 - MAX_STEPS));

		w.localStorage.setItem(PATH_KEY, JSON.stringify({ ts: now, p: trail }));
		w.leanVisitPath = function () { return trail; };
	} catch (e) {
		w.leanVisitPath = function () { return []; }; // Private mode / storage disabled.
	}
})(window, document);
</script>
	<?php
}

// ──────────────────────────────────────────────────────────────────────────────
// SERVER-SIDE READ
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Read and sanitize the attribution fields the form's submit handler posts.
 *
 * Always returns the full set of keys, so callers never have to test for them. Values are
 * clamped to the column width; everything is attacker-controlled, so treat it as untrusted
 * text (it is escaped again on output).
 *
 * @return array{source:string,medium:string,campaign:string,term:string,content:string,click_id:string,landing_page:string}
 */
function lean_attribution_from_post() {
	$field = function ($key, $length = 100) {
		$value = sanitize_text_field(wp_unslash($_POST[$key] ?? ''));
		return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
	};

	return array(
		'source'       => $field('utm_source'),
		'medium'       => $field('utm_medium'),
		'campaign'     => $field('utm_campaign'),
		'term'         => $field('utm_term'),
		'content'      => $field('utm_content'),
		'click_id'     => $field('click_id'),
		'landing_page' => $field('landing_page', 191),
	);
}

/**
 * Read and sanitize the visit path the form posts (JSON: [{u: path, t: epoch}, …]).
 *
 * @return array List of array{u:string,t:int}, oldest first. Empty when storage was blocked,
 *               the payload is junk, or the visitor is a pre-attribution lead.
 */
function lean_attribution_path_from_post() {
	$raw = wp_unslash($_POST['visit_path'] ?? '');
	if ($raw === '' || strlen($raw) > 8192) {
		return array();
	}

	$decoded = json_decode($raw, true);
	if (!is_array($decoded)) {
		return array();
	}

	$steps = array();
	foreach ($decoded as $step) {
		if (!is_array($step) || empty($step['u'])) {
			continue;
		}
		$entry = array(
			'u' => substr(sanitize_text_field($step['u']), 0, 191),
			't' => isset($step['t']) ? absint($step['t']) : 0,
		);
		// Set only on a step the visitor arrived at through a new source — i.e. they left and
		// came back. Holds that source, so the line can say how they returned.
		if (!empty($step['r'])) {
			$entry['r'] = substr(sanitize_text_field($step['r']), 0, 100);
		}
		$steps[] = $entry;
		if (count($steps) >= 20) { // Mirrors the client-side cap.
			break;
		}
	}

	return $steps;
}

/**
 * Render a visit path as plain-text lines for the notification email / admin detail.
 *
 * Times are shown as offsets from the first page, so the reader sees pace ("they sat on the
 * pricing page for four minutes") without doing arithmetic.
 *
 * @param array  $steps  Output of lean_attribution_path_from_post().
 * @param string $indent Prefix for each line.
 * @return string '' when there's no path to show.
 */
function lean_attribution_format_path($steps, $indent = '  ') {
	if (empty($steps) || !is_array($steps)) {
		return '';
	}

	$start = (int) ($steps[0]['t'] ?? 0);
	$last  = count($steps) - 1;
	$lines = array();

	foreach ($steps as $i => $step) {
		$line = $indent . ($i + 1) . '. ' . $step['u'];
		// A lone "+0:00" on a single-page visit is noise — offsets only earn their place
		// once there's a second page to be relative to.
		if ($last > 0 && $start && !empty($step['t'])) {
			$offset = max(0, (int) $step['t'] - $start);
			// Stopwatch notation, rolling up to h:mm:ss once a visit spans an hour — "+72:00"
			// is technically right but nobody reads it as "an hour and twelve minutes".
			$line .= $offset >= HOUR_IN_SECONDS
				? sprintf(
					' (+%d:%02d:%02d)',
					intdiv($offset, HOUR_IN_SECONDS),
					intdiv($offset % HOUR_IN_SECONDS, MINUTE_IN_SECONDS),
					$offset % MINUTE_IN_SECONDS
				)
				: sprintf(' (+%d:%02d)', intdiv($offset, MINUTE_IN_SECONDS), $offset % MINUTE_IN_SECONDS);
		}

		$notes = array();
		if (!empty($step['r'])) {
			$notes[] = 'came back via ' . $step['r'];
		}
		if ($i === $last) {
			$notes[] = 'submitted here';
		}
		if ($notes) {
			$line .= ' — ' . implode(', ', $notes);
		}

		$lines[] = $line;
	}

	return implode("\n", $lines);
}

/**
 * Heading for a visit path, e.g. "3 pages over 6m 12s" or "1 page (landed and submitted)".
 *
 * @param array $steps Output of lean_attribution_path_from_post().
 * @return string '' when there's no path to summarise.
 */
function lean_attribution_path_summary($steps) {
	if (empty($steps) || !is_array($steps)) {
		return '';
	}

	$count = count($steps);
	if ($count === 1) {
		return '1 page';
	}

	$span = (int) ($steps[$count - 1]['t'] ?? 0) - (int) ($steps[0]['t'] ?? 0);
	if ($span <= 0) {
		return $count . ' pages';
	}

	// Visits can now span hours (someone leaves to research and comes back), so read out in
	// the largest sensible unit and drop empty trailing parts: "1h 12m", "47m", "40s".
	if ($span >= HOUR_IN_SECONDS) {
		$minutes  = intdiv($span % HOUR_IN_SECONDS, MINUTE_IN_SECONDS);
		$duration = intdiv($span, HOUR_IN_SECONDS) . 'h' . ($minutes ? ' ' . $minutes . 'm' : '');
	} elseif ($span >= MINUTE_IN_SECONDS) {
		$seconds  = $span % MINUTE_IN_SECONDS;
		$duration = intdiv($span, MINUTE_IN_SECONDS) . 'm' . ($seconds ? ' ' . $seconds . 's' : '');
	} else {
		$duration = $span . 's';
	}

	return $count . ' pages over ' . $duration;
}

/**
 * Human-readable "source / medium" for admin lists, emails and CSV.
 *
 * @param string $source
 * @param string $medium
 * @param string $empty Placeholder for rows captured before attribution existed.
 * @return string
 */
function lean_attribution_label($source, $medium, $empty = '—') {
	$source = trim((string) $source);
	$medium = trim((string) $medium);

	if ($source === '' && $medium === '') {
		return $empty;
	}
	if ($medium === '') {
		return $source;
	}
	return ($source === '' ? '(not set)' : $source) . ' / ' . $medium;
}
