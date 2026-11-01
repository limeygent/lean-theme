/**
 * AI Traffic Monitor for SiteGround
 *
 * Reads SiteGround's daily compressed access logs directly and displays
 * known AI-agent activity under Tools > AI Traffic.
 *
 * - Read-only
 * - No database writes
 * - No cron
 * - No AJAX / REST endpoint
 * - No public-facing output
 * - Fixed 30 most recent archived log files
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Number of archived daily logs to scan.
 */
define( 'THT_AI_LOG_DAYS', 30 );

/**
 * Maximum number of recent requests displayed.
 */
define( 'THT_AI_RECENT_LIMIT', 100 );


/**
 * Add the wp-admin page.
 */
add_action( 'admin_menu', function () {
	add_management_page(
		'AI Traffic',
		'AI Traffic',
		'manage_options',
		'tht-ai-traffic',
		'tht_ai_render_admin_page'
	);
} );


/**
 * Identify known AI agents from the raw User-Agent.
 *
 * We deliberately keep "user retrieval" separate from crawlers/search.
 */
function tht_ai_classify_agent( $ua ) {

	$agents = array(

		// OpenAI
		array(
			'match'    => 'ChatGPT-User',
			'provider' => 'OpenAI',
			'agent'    => 'ChatGPT-User',
			'category' => 'User Retrieval',
		),
		array(
			'match'    => 'OAI-SearchBot',
			'provider' => 'OpenAI',
			'agent'    => 'OAI-SearchBot',
			'category' => 'Search / Crawler',
		),
		array(
			'match'    => 'GPTBot',
			'provider' => 'OpenAI',
			'agent'    => 'GPTBot',
			'category' => 'Crawler',
		),

		// Anthropic
		array(
			'match'    => 'claude-code/',
			'provider' => 'Anthropic',
			'agent'    => 'Claude Code',
			'category' => 'User / Agent Retrieval',
		),
		array(
			'match'    => 'Claude-User',
			'provider' => 'Anthropic',
			'agent'    => 'Claude-User',
			'category' => 'User Retrieval',
		),
		array(
			'match'    => 'Claude-SearchBot',
			'provider' => 'Anthropic',
			'agent'    => 'Claude-SearchBot',
			'category' => 'Search / Crawler',
		),
		array(
			'match'    => 'ClaudeBot',
			'provider' => 'Anthropic',
			'agent'    => 'ClaudeBot',
			'category' => 'Crawler',
		),

		// Perplexity
		array(
			'match'    => 'Perplexity-User',
			'provider' => 'Perplexity',
			'agent'    => 'Perplexity-User',
			'category' => 'User Retrieval',
		),
		array(
			'match'    => 'PerplexityBot',
			'provider' => 'Perplexity',
			'agent'    => 'PerplexityBot',
			'category' => 'Search / Retrieval',
		),

		// Other known AI-oriented crawlers.
		array(
			'match'    => 'Bytespider',
			'provider' => 'ByteDance',
			'agent'    => 'Bytespider',
			'category' => 'Crawler',
		),
		array(
			'match'    => 'Meta-ExternalAgent',
			'provider' => 'Meta',
			'agent'    => 'Meta-ExternalAgent',
			'category' => 'Crawler',
		),
	);

	foreach ( $agents as $agent ) {
		if ( stripos( $ua, $agent['match'] ) !== false ) {
			return $agent;
		}
	}

	return false;
}


/**
 * Parse one SiteGround access-log line.
 *
 * Expected general format:
 *
 * IP host - [date] "METHOD /path HTTP/x" status bytes "referrer" "UA" | ...
 */
function tht_ai_parse_log_line( $line ) {

	$pattern = '/^(\S+)\s+(\S+)\s+-\s+\[([^\]]+)\]\s+"([A-Z]+)\s+(\S+)\s+HTTP\/[^"]+"\s+(\d{3})\s+(\S+)\s+"([^"]*)"\s+"([^"]*)"\s+\|\s*(.*)$/';

	if ( ! preg_match( $pattern, $line, $m ) ) {
		return false;
	}

	$date = DateTime::createFromFormat(
		'd/M/Y:H:i:s O',
		$m[3]
	);

	if ( ! $date ) {
		return false;
	}

	return array(
		'ip'         => $m[1],
		'host'       => $m[2],
		'timestamp'  => $date->getTimestamp(),
		'method'     => $m[4],
		'target'     => $m[5],
		'status'     => (int) $m[6],
		'bytes'      => $m[7],
		'referrer'   => $m[8],
		'user_agent' => $m[9],
		'extra'      => $m[10],
	);
}


/**
 * Normalize a requested URL for reporting.
 *
 * Query strings are removed.
 *
 * For page-like URLs, we standardize trailing slashes so:
 *
 * /some-page
 * /some-page/
 *
 * report as the same page.
 */
function tht_ai_normalize_url( $target ) {

	$parts = explode( '?', $target, 2 );
	$path  = $parts[0];

	if ( $path === '' ) {
		return '/';
	}

	if ( $path === '/' ) {
		return '/';
	}

	/*
	 * Leave apparent files untouched:
	 * robots.txt, llms.txt, XML, images, ZIP, etc.
	 */
	$basename = basename( $path );

	if ( strpos( $basename, '.' ) !== false ) {
		return $path;
	}

	return trailingslashit( $path );
}


/**
 * Determine whether a URL is a special discovery/resource URL.
 */
function tht_ai_is_discovery_resource( $url ) {

	$lower = strtolower( $url );

	if ( $lower === '/robots.txt' ) {
		return true;
	}

	if ( $lower === '/llms.txt' ) {
		return true;
	}

	if (
		strpos( $lower, 'sitemap' ) !== false &&
		strpos( $lower, '.xml' ) !== false
	) {
		return true;
	}

	return false;
}


/**
 * Mask an IP address for ordinary wp-admin display.
 *
 * IPv4:
 * 20.169.78.200 -> 20.169.78.xxx
 *
 * IPv6 gets shortened.
 */
function tht_ai_mask_ip( $ip ) {

	if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		$parts    = explode( '.', $ip );
		$parts[3] = 'xxx';

		return implode( '.', $parts );
	}

	if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
		$parts = explode( ':', $ip );

		return implode( ':', array_slice( $parts, 0, 4 ) ) . ':…';
	}

	return $ip;
}


/**
 * Scan the SiteGround archived access logs.
 */
function tht_ai_scan_logs() {

	/*
	 * WordPress:
	 *
	 * /home/user/www/domain.com/public_html/
	 *
	 * Logs:
	 *
	 * /home/user/www/domain.com/logs/
	 */
	$site_root = dirname( untrailingslashit( ABSPATH ) );
	$log_dir   = realpath( $site_root . '/logs' );

	if ( ! $log_dir || ! is_dir( $log_dir ) ) {
		return new WP_Error(
			'log_directory_missing',
			'The SiteGround logs directory could not be found.'
		);
	}

	if ( ! is_readable( $log_dir ) ) {
		return new WP_Error(
			'log_directory_unreadable',
			'The SiteGround logs directory exists but PHP cannot read it.'
		);
	}

	if ( ! function_exists( 'gzopen' ) ) {
		return new WP_Error(
			'gzip_unavailable',
			'PHP gzip support is unavailable.'
		);
	}

	$files = glob( $log_dir . '/*.gz' );

	if ( ! $files ) {
		return new WP_Error(
			'no_logs',
			'No compressed SiteGround access logs were found.'
		);
	}

	/*
	 * Sort filenames newest first.
	 *
	 * SiteGround filenames contain YYYY-MM-DD, so alphabetical
	 * filename sorting also gives chronological order.
	 */
	usort( $files, function ( $a, $b ) {
		return strcmp( basename( $b ), basename( $a ) );
	} );

	$files = array_slice( $files, 0, THT_AI_LOG_DAYS );

	$stats = array(
		'total'             => 0,
		'successful'        => 0,
		'providers'         => array(),
		'agents'            => array(),
		'categories'        => array(),
		'unique_urls'       => array(),
		'content_urls'      => array(),
		'discovery'         => array(),
		'agent_urls'        => array(),
		'daily'             => array(),
		'recent'            => array(),
		'files_scanned'     => count( $files ),
		'newest_log'        => basename( $files[0] ),
		'oldest_log'        => basename( end( $files ) ),
		'lines_read'        => 0,
		'unparsed_lines'    => 0,
	);

	foreach ( $files as $file ) {

		if ( ! is_readable( $file ) ) {
			continue;
		}

		$handle = gzopen( $file, 'rb' );

		if ( ! $handle ) {
			continue;
		}

		while ( ! gzeof( $handle ) ) {

			$line = gzgets( $handle );

			if ( $line === false ) {
				break;
			}

			$stats['lines_read']++;

			/*
			 * Cheap pre-filter.
			 *
			 * Don't run the full regex/parser unless the line
			 * contains a UA signature we care about.
			 */
			if (
				stripos( $line, 'ChatGPT-User' ) === false &&
				stripos( $line, 'OAI-SearchBot' ) === false &&
				stripos( $line, 'GPTBot' ) === false &&
				stripos( $line, 'Claude-' ) === false &&
				stripos( $line, 'claude-code/' ) === false &&
				stripos( $line, 'Perplexity' ) === false &&
				stripos( $line, 'Bytespider' ) === false &&
				stripos( $line, 'Meta-ExternalAgent' ) === false
			) {
				continue;
			}

			$row = tht_ai_parse_log_line( trim( $line ) );

			if ( ! $row ) {
				$stats['unparsed_lines']++;
				continue;
			}

			$agent = tht_ai_classify_agent( $row['user_agent'] );

			if ( ! $agent ) {
				continue;
			}

			$url = tht_ai_normalize_url( $row['target'] );

			$row['url']      = $url;
			$row['provider'] = $agent['provider'];
			$row['agent']    = $agent['agent'];
			$row['category'] = $agent['category'];

			$stats['total']++;

			if ( $row['status'] >= 200 && $row['status'] < 300 ) {
				$stats['successful']++;
			}

			/*
			 * Daily agent totals.
			 *
			 * Use the WordPress/site timezone rather than the UTC timestamp
			 * from the raw SiteGround log.
			 */
			$day = wp_date( 'Y-m-d', $row['timestamp'] );

			if ( ! isset( $stats['daily'][ $day ] ) ) {
				$stats['daily'][ $day ] = array(
					'total'  => 0,
					'agents' => array(),
				);
			}

			$stats['daily'][ $day ]['total']++;

			if ( ! isset( $stats['daily'][ $day ]['agents'][ $agent['agent'] ] ) ) {
				$stats['daily'][ $day ]['agents'][ $agent['agent'] ] = 0;
			}

			$stats['daily'][ $day ]['agents'][ $agent['agent'] ]++;
			
			$stats['unique_urls'][ $url ] = true;

			/*
			 * Provider totals.
			 */
			if ( ! isset( $stats['providers'][ $agent['provider'] ] ) ) {
				$stats['providers'][ $agent['provider'] ] = 0;
			}

			$stats['providers'][ $agent['provider'] ]++;

			/*
			 * Agent totals.
			 */
			if ( ! isset( $stats['agents'][ $agent['agent'] ] ) ) {
				$stats['agents'][ $agent['agent'] ] = array(
					'provider' => $agent['provider'],
					'category' => $agent['category'],
					'hits'     => 0,
					'last'     => 0,
				);
			}

			$stats['agents'][ $agent['agent'] ]['hits']++;

			if (
				$row['timestamp'] >
				$stats['agents'][ $agent['agent'] ]['last']
			) {
				$stats['agents'][ $agent['agent'] ]['last'] =
					$row['timestamp'];
			}

			/*
			 * Category totals.
			 */
			if ( ! isset( $stats['categories'][ $agent['category'] ] ) ) {
				$stats['categories'][ $agent['category'] ] = 0;
			}

			$stats['categories'][ $agent['category'] ]++;

			/*
			 * Discovery resources.
			 */
			if ( tht_ai_is_discovery_resource( $url ) ) {

				if ( ! isset( $stats['discovery'][ $url ] ) ) {
					$stats['discovery'][ $url ] = array(
						'hits'   => 0,
						'agents' => array(),
						'last'   => 0,
					);
				}

				$stats['discovery'][ $url ]['hits']++;
				$stats['discovery'][ $url ]['agents'][ $agent['agent'] ] = true;

				if (
					$row['timestamp'] >
					$stats['discovery'][ $url ]['last']
				) {
					$stats['discovery'][ $url ]['last'] =
						$row['timestamp'];
				}
			}

			/*
			 * Top content pages:
			 *
			 * Only count successful HTTP responses.
			 * This prevents a 301 -> 200 redirect chain from
			 * counting as two meaningful page fetches.
			 */
			if (
				$row['status'] >= 200 &&
				$row['status'] < 300 &&
				! tht_ai_is_discovery_resource( $url )
			) {

				if ( ! isset( $stats['content_urls'][ $url ] ) ) {
					$stats['content_urls'][ $url ] = array(
						'hits'   => 0,
						'agents' => array(),
						'last'   => 0,
					);
				}

				$stats['content_urls'][ $url ]['hits']++;
				$stats['content_urls'][ $url ]['agents'][ $agent['agent'] ] = true;

				if (
					$row['timestamp'] >
					$stats['content_urls'][ $url ]['last']
				) {
					$stats['content_urls'][ $url ]['last'] =
						$row['timestamp'];
				}
			}

			/*
			 * Agent x URL breakdown.
			 *
			 * Again, only successful responses.
			 */
			if ( $row['status'] >= 200 && $row['status'] < 300 ) {

				$key = $agent['agent'] . '|' . $url;

				if ( ! isset( $stats['agent_urls'][ $key ] ) ) {
					$stats['agent_urls'][ $key ] = array(
						'agent'    => $agent['agent'],
						'provider' => $agent['provider'],
						'url'      => $url,
						'hits'     => 0,
						'last'     => 0,
					);
				}

				$stats['agent_urls'][ $key ]['hits']++;

				if (
					$row['timestamp'] >
					$stats['agent_urls'][ $key ]['last']
				) {
					$stats['agent_urls'][ $key ]['last'] =
						$row['timestamp'];
				}
			}

			/*
			 * Keep recent requests in memory only.
			 */
			$stats['recent'][] = $row;
		}

		gzclose( $handle );
	}

	/*
	 * Sort aggregated sections.
	 */
	arsort( $stats['providers'] );
	arsort( $stats['categories'] );

	uasort( $stats['agents'], function ( $a, $b ) {
		return $b['hits'] <=> $a['hits'];
	} );

	uasort( $stats['content_urls'], function ( $a, $b ) {
		return $b['hits'] <=> $a['hits'];
	} );

	uasort( $stats['discovery'], function ( $a, $b ) {
		return $b['hits'] <=> $a['hits'];
	} );

	uasort( $stats['agent_urls'], function ( $a, $b ) {
		return $b['hits'] <=> $a['hits'];
	} );

	usort( $stats['recent'], function ( $a, $b ) {
		return $b['timestamp'] <=> $a['timestamp'];
	} );

	$stats['recent'] = array_slice(
		$stats['recent'],
		0,
		THT_AI_RECENT_LIMIT
	);

	ksort( $stats['daily'] );
	
	return $stats;
}


/**
 * Format timestamp using WordPress/site timezone.
 */
function tht_ai_format_time( $timestamp ) {

	if ( ! $timestamp ) {
		return '—';
	}

	return wp_date(
		'M j, Y g:i:s a',
		$timestamp
	);
}


/**
 * Render wp-admin page.
 */
function tht_ai_render_admin_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.' ) );
	}

	$stats = tht_ai_scan_logs();

	?>
	<div class="wrap">
		<h1>AI Traffic</h1>

		<p>
			Reads the most recent
			<strong><?php echo esc_html( THT_AI_LOG_DAYS ); ?></strong>
			SiteGround archived access logs directly.
			Nothing is stored by this report.
		</p>

	<?php

	if ( is_wp_error( $stats ) ) {
		?>
		<div class="notice notice-error">
			<p><?php echo esc_html( $stats->get_error_message() ); ?></p>
		</div>
		<?php
		return;
	}

	$unique_urls = count( $stats['unique_urls'] );

	$user_retrieval = 0;

	foreach ( $stats['categories'] as $category => $hits ) {
		if (
			$category === 'User Retrieval' ||
			$category === 'User / Agent Retrieval'
		) {
			$user_retrieval += $hits;
		}
	}

	?>

	<style>
		.tht-ai-cards {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
			gap: 12px;
			margin: 20px 0;
			max-width: 1100px;
		}

		.tht-ai-card {
			background: #fff;
			border: 1px solid #dcdcde;
			border-radius: 4px;
			padding: 16px;
		}

		.tht-ai-card .number {
			font-size: 28px;
			font-weight: 600;
			line-height: 1.2;
			margin-bottom: 4px;
		}

		.tht-ai-card .label {
			color: #50575e;
		}

		.tht-ai-section {
			margin-top: 28px;
			max-width: 1200px;
		}

		.tht-ai-table td,
		.tht-ai-table th {
			vertical-align: top;
		}

		.tht-ai-ua {
			max-width: 500px;
			word-break: break-word;
			font-family: monospace;
			font-size: 11px;
		}

		.tht-ai-url {
			word-break: break-word;
		}

		.tht-ai-badge {
			display: inline-block;
			background: #f0f0f1;
			border-radius: 3px;
			padding: 2px 6px;
			margin: 1px 3px 1px 0;
			font-size: 11px;
		}
	
		.tht-ai-chart-legend {
			display: flex;
			flex-wrap: wrap;
			gap: 8px 18px;
			margin: 14px 0 20px;
		}

		.tht-ai-chart-legend-item {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			font-size: 12px;
		}

		.tht-ai-chart-legend-color {
			display: inline-block;
			width: 11px;
			height: 11px;
			border-radius: 2px;
		}

		.tht-ai-chart-wrap {
			background: #fff;
			border: 1px solid #dcdcde;
				padding: 20px 20px 10px;
			overflow-x: auto;
		}

		.tht-ai-chart {
			display: flex;
			align-items: flex-end;
			gap: 5px;
			height: 300px;
			min-width: 900px;
			padding-top: 25px;
		}

		.tht-ai-chart-day {
			flex: 1 0 24px;
			height: 100%;
			display: flex;
			flex-direction: column;
			justify-content: flex-end;
			align-items: stretch;
			min-width: 24px;
		}

		.tht-ai-chart-total {
			text-align: center;
			font-size: 10px;
			color: #646970;
			height: 18px;
			line-height: 18px;
		}

		.tht-ai-chart-column {
			display: flex;
			flex-direction: column-reverse;
			width: 100%;
			min-height: 2px;
			cursor: help;
		}

		.tht-ai-chart-segment {
			width: 100%;
			min-height: 1px;
		}

		.tht-ai-chart-date {
			text-align: center;
			font-size: 10px;
			color: #646970;
			height: 32px;
			padding-top: 5px;
			white-space: nowrap;
			transform: rotate(-45deg);
			transform-origin: center top;
		}
	
	</style>

	<div class="tht-ai-cards">

		<div class="tht-ai-card">
			<div class="number">
				<?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?>
			</div>
			<div class="label">AI Requests</div>
		</div>

		<div class="tht-ai-card">
			<div class="number">
				<?php echo esc_html( number_format_i18n( $user_retrieval ) ); ?>
			</div>
			<div class="label">User / Agent Retrievals</div>
		</div>

		<div class="tht-ai-card">
			<div class="number">
				<?php echo esc_html( number_format_i18n( $unique_urls ) ); ?>
			</div>
			<div class="label">Unique URLs</div>
		</div>

		<div class="tht-ai-card">
			<div class="number">
				<?php echo esc_html( number_format_i18n( count( $stats['agents'] ) ) ); ?>
			</div>
			<div class="label">AI Agents</div>
		</div>

	</div>

		<?php
/*
 * Daily stacked agent chart.
 */
$daily_agents = array();

/*
 * Build the complete set of agents represented in the chart.
 */
foreach ( $stats['daily'] as $day_data ) {
	foreach ( $day_data['agents'] as $agent_name => $hits ) {
		$daily_agents[ $agent_name ] = true;
	}
}

$daily_agents = array_keys( $daily_agents );

/*
 * Keep chart colors stable by agent.
 */
$agent_colors = array(
	'ChatGPT-User'    => '#10a37f',
	'OAI-SearchBot'   => '#6e8efb',
	'GPTBot'          => '#8fa8ff',
	'Claude-User'     => '#d97757',
	'Claude Code'     => '#a85d45',
	'Claude-SearchBot'=> '#e9a17f',
	'ClaudeBot'       => '#c7866b',
	'Perplexity-User' => '#20808d',
	'PerplexityBot'   => '#36a7b4',
	'Bytespider'      => '#777777',
	'Meta-ExternalAgent' => '#8b65c2',
);

$fallback_colors = array(
	'#2271b1',
	'#72aee6',
	'#00a32a',
	'#dba617',
	'#d63638',
	'#8c8f94',
);

$fallback_index = 0;

foreach ( $daily_agents as $agent_name ) {

	if ( ! isset( $agent_colors[ $agent_name ] ) ) {
		$agent_colors[ $agent_name ] =
			$fallback_colors[
				$fallback_index % count( $fallback_colors )
			];

		$fallback_index++;
	}
}

/*
 * Find largest daily total so all columns share the same scale.
 */
$max_daily = 0;

foreach ( $stats['daily'] as $day_data ) {
	if ( $day_data['total'] > $max_daily ) {
		$max_daily = $day_data['total'];
	}
}

if ( $max_daily < 1 ) {
	$max_daily = 1;
}
?>

<div class="tht-ai-section">
	<h2>AI Requests by Day</h2>

	<p>
		Each column represents one day. Colors show the AI agents
		responsible for those requests.
	</p>

	<div class="tht-ai-chart-legend">

		<?php foreach ( $daily_agents as $agent_name ) : ?>

			<span class="tht-ai-chart-legend-item">

				<span
					class="tht-ai-chart-legend-color"
					style="background: <?php echo esc_attr( $agent_colors[ $agent_name ] ); ?>;"
				></span>

				<?php echo esc_html( $agent_name ); ?>

			</span>

		<?php endforeach; ?>

	</div>


	<div class="tht-ai-chart-wrap">

		<div class="tht-ai-chart">

			<?php foreach ( $stats['daily'] as $day => $day_data ) : ?>

				<?php
				/*
				 * Height relative to busiest day.
				 */
				$column_height =
					( $day_data['total'] / $max_daily ) * 100;

				$tooltip_lines = array();

				foreach ( $day_data['agents'] as $agent_name => $hits ) {
					$tooltip_lines[] =
						$agent_name . ': ' . number_format_i18n( $hits );
				}

				$tooltip =
					wp_date(
						'M j, Y',
						strtotime( $day . ' 12:00:00' )
					) .
					"\nTotal: " .
					number_format_i18n( $day_data['total'] ) .
					"\n" .
					implode( "\n", $tooltip_lines );
				?>

				<div class="tht-ai-chart-day">

					<div class="tht-ai-chart-total">
						<?php echo esc_html( $day_data['total'] ); ?>
					</div>

					<div
						class="tht-ai-chart-column"
						style="height: <?php echo esc_attr( $column_height ); ?>%;"
						title="<?php echo esc_attr( $tooltip ); ?>"
					>

						<?php foreach ( $day_data['agents'] as $agent_name => $hits ) : ?>

							<?php
							$segment_height =
								$day_data['total'] > 0
									? ( $hits / $day_data['total'] ) * 100
									: 0;
							?>

							<div
								class="tht-ai-chart-segment"
								style="
									height: <?php echo esc_attr( $segment_height ); ?>%;
									background: <?php echo esc_attr( $agent_colors[ $agent_name ] ); ?>;
								"
							></div>

						<?php endforeach; ?>

					</div>

					<div class="tht-ai-chart-date">
						<?php
						echo esc_html(
							wp_date(
								'M j',
								strtotime( $day . ' 12:00:00' )
							)
						);
						?>
					</div>

				</div>

			<?php endforeach; ?>

		</div>

	</div>
</div>

	<div class="tht-ai-section">
		<h2>Providers</h2>

		<table class="widefat striped tht-ai-table">
			<thead>
				<tr>
					<th>Provider</th>
					<th>Requests</th>
				</tr>
			</thead>
			<tbody>

			<?php foreach ( $stats['providers'] as $provider => $hits ) : ?>
				<tr>
					<td><?php echo esc_html( $provider ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $hits ) ); ?></td>
				</tr>
			<?php endforeach; ?>

			</tbody>
		</table>
	</div>


	<div class="tht-ai-section">
		<h2>Agents</h2>

		<table class="widefat striped tht-ai-table">
			<thead>
				<tr>
					<th>Agent</th>
					<th>Provider</th>
					<th>Type</th>
					<th>Requests</th>
					<th>Last Seen</th>
				</tr>
			</thead>
			<tbody>

			<?php foreach ( $stats['agents'] as $agent_name => $agent ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $agent_name ); ?></strong></td>
					<td><?php echo esc_html( $agent['provider'] ); ?></td>
					<td><?php echo esc_html( $agent['category'] ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $agent['hits'] ) ); ?></td>
					<td><?php echo esc_html( tht_ai_format_time( $agent['last'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>

			</tbody>
		</table>
	</div>


	<div class="tht-ai-section">
		<h2>Top AI Content Pages</h2>

		<p>
			Successful HTTP responses only. Redirect requests are excluded
			so a 301 → 200 sequence does not count as two page fetches.
		</p>

		<table class="widefat striped tht-ai-table">
			<thead>
				<tr>
					<th>URL</th>
					<th>Hits</th>
					<th>Agents</th>
					<th>Last Seen</th>
				</tr>
			</thead>
			<tbody>

			<?php
			$content_count = 0;

			foreach ( $stats['content_urls'] as $url => $data ) :

				if ( $content_count >= 50 ) {
					break;
				}

				$content_count++;
				?>
				<tr>
					<td class="tht-ai-url">
						<strong><?php echo esc_html( $url ); ?></strong>
					</td>
					<td>
						<?php echo esc_html( number_format_i18n( $data['hits'] ) ); ?>
					</td>
					<td>
						<?php foreach ( array_keys( $data['agents'] ) as $agent_name ) : ?>
							<span class="tht-ai-badge">
								<?php echo esc_html( $agent_name ); ?>
							</span>
						<?php endforeach; ?>
					</td>
					<td>
						<?php echo esc_html( tht_ai_format_time( $data['last'] ) ); ?>
					</td>
				</tr>
			<?php endforeach; ?>

			</tbody>
		</table>
	</div>


	<div class="tht-ai-section">
		<h2>Discovery Resources</h2>

		<table class="widefat striped tht-ai-table">
			<thead>
				<tr>
					<th>Resource</th>
					<th>Hits</th>
					<th>Agents</th>
					<th>Last Seen</th>
				</tr>
			</thead>
			<tbody>

			<?php

			/*
			 * Always show robots.txt and llms.txt,
			 * even if there were zero observed requests.
			 */
			$discovery = $stats['discovery'];

			foreach ( array( '/robots.txt', '/llms.txt' ) as $required ) {
				if ( ! isset( $discovery[ $required ] ) ) {
					$discovery[ $required ] = array(
						'hits'   => 0,
						'agents' => array(),
						'last'   => 0,
					);
				}
			}

			uasort( $discovery, function ( $a, $b ) {
				return $b['hits'] <=> $a['hits'];
			} );

			foreach ( $discovery as $url => $data ) :
				?>
				<tr>
					<td><strong><?php echo esc_html( $url ); ?></strong></td>
					<td><?php echo esc_html( number_format_i18n( $data['hits'] ) ); ?></td>
					<td>
						<?php
						if ( empty( $data['agents'] ) ) {
							echo '—';
						} else {
							foreach ( array_keys( $data['agents'] ) as $agent_name ) {
								echo '<span class="tht-ai-badge">' .
									esc_html( $agent_name ) .
									'</span>';
							}
						}
						?>
					</td>
					<td><?php echo esc_html( tht_ai_format_time( $data['last'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>

			</tbody>
		</table>
	</div>


	<div class="tht-ai-section">
		<h2>Agent × URL</h2>

		<table class="widefat striped tht-ai-table">
			<thead>
				<tr>
					<th>Agent</th>
					<th>Provider</th>
					<th>URL</th>
					<th>Hits</th>
					<th>Last Seen</th>
				</tr>
			</thead>
			<tbody>

			<?php

			$combo_count = 0;

			foreach ( $stats['agent_urls'] as $data ) :

				if ( $combo_count >= 100 ) {
					break;
				}

				$combo_count++;
				?>
				<tr>
					<td><strong><?php echo esc_html( $data['agent'] ); ?></strong></td>
					<td><?php echo esc_html( $data['provider'] ); ?></td>
					<td class="tht-ai-url"><?php echo esc_html( $data['url'] ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $data['hits'] ) ); ?></td>
					<td><?php echo esc_html( tht_ai_format_time( $data['last'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>

			</tbody>
		</table>
	</div>


	<div class="tht-ai-section">
		<h2>Recent AI Requests</h2>

		<p>
			Most recent <?php echo esc_html( THT_AI_RECENT_LIMIT ); ?>
			matching requests. IP addresses are masked in this view.
		</p>

		<table class="widefat striped tht-ai-table">
			<thead>
				<tr>
					<th>Time</th>
					<th>Agent</th>
					<th>URL</th>
					<th>Status</th>
					<th>IP</th>
					<th>User-Agent</th>
				</tr>
			</thead>
			<tbody>

			<?php foreach ( $stats['recent'] as $row ) : ?>
				<tr>
					<td>
						<?php echo esc_html( tht_ai_format_time( $row['timestamp'] ) ); ?>
					</td>
					<td>
						<strong><?php echo esc_html( $row['agent'] ); ?></strong><br>
						<small><?php echo esc_html( $row['provider'] ); ?></small>
					</td>
					<td class="tht-ai-url">
						<?php echo esc_html( $row['target'] ); ?>
					</td>
					<td>
						<?php echo esc_html( $row['status'] ); ?>
					</td>
					<td>
						<?php echo esc_html( tht_ai_mask_ip( $row['ip'] ) ); ?>
					</td>
					<td class="tht-ai-ua">
						<?php echo esc_html( $row['user_agent'] ); ?>
					</td>
				</tr>
			<?php endforeach; ?>

			</tbody>
		</table>
	</div>

	</div>
	<?php
}
