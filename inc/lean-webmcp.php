<?php
/**
 * Filename: lean-webmcp.php
 * Purpose: Declarative WebMCP support for Lean theme forms.
 *          Turns the [lean_form] output into a Chrome Declarative WebMCP tool by
 *          centralizing the toolname/tooldescription/toolparamdescription attributes
 *          and the per-origin Origin Trial token <meta> tag.
 *
 * Main Functions:
 * - lean_webmcp_enabled()            - Global on/off gate (filterable).
 * - lean_webmcp_origin_trial_token() - Reads the per-site Origin Trial token option.
 * - lean_webmcp_origin_trial_meta()  - Echoes the origin-trial <meta> when a token is set.
 * - lean_webmcp_form_attrs()         - Builds toolname/tooldescription attrs.
 * - lean_webmcp_param_attr()         - Builds a field's toolparamdescription attr.
 *
 * Notes:
 * - This is agentic-browsing readiness, NOT a performance/SEO signal. The related
 *   Lighthouse audits are informational only.
 * - The attributes are inert for real users until a per-origin Origin Trial token is
 *   configured (Chrome 149+). Locally, enable chrome://flags/#enable-webmcp-testing.
 *
 * Dependencies:
 * - Loaded by lean-loader.php before inc/forms.php.
 *
 * @see https://developer.chrome.com/docs/ai/webmcp/declarative-api
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Function: lean_webmcp_enabled
 * Purpose: Global enable/disable switch for WebMCP annotations.
 *          Default OFF: annotations are emitted only once the site owner has
 *          pasted this origin's Origin Trial token (an explicit opt-in). Turning
 *          a public lead form into an agent-callable tool is a trust-boundary
 *          change, so it must not happen silently.
 *
 *          To force-enable for local testing without a token (with
 *          chrome://flags/#enable-webmcp-testing):
 *              add_filter('lean_webmcp_enabled', '__return_true');
 *
 * @return bool True when WebMCP output is enabled.
 */
function lean_webmcp_enabled() {
	$has_token = lean_webmcp_origin_trial_token() !== '';
	return (bool) apply_filters('lean_webmcp_enabled', $has_token);
}

/**
 * Function: lean_webmcp_origin_trial_token
 * Purpose: Return the per-site Origin Trial token, if configured.
 *
 * @return string The trimmed token, or '' when unset.
 */
function lean_webmcp_origin_trial_token() {
	return trim((string) get_option('lean_webmcp_origin_trial_token', ''));
}

/**
 * Function: lean_webmcp_origin_trial_meta
 * Purpose: Output the origin-trial <meta> tag into <head>. Called directly from
 *          template-parts/lean-head.php (Lean templates bypass wp_head()).
 *
 * @return void
 */
function lean_webmcp_origin_trial_meta() {
	if (!lean_webmcp_enabled()) {
		return;
	}

	$token = lean_webmcp_origin_trial_token();
	if (!$token) {
		return;
	}

	echo '<meta http-equiv="origin-trial" content="' . esc_attr($token) . '">' . "\n";
}

/**
 * Function: lean_webmcp_sanitize_toolname
 * Purpose: Normalize a tool name into a machine-safe identifier.
 *
 * @param string $toolname Raw tool name.
 * @return string Lowercased, underscore-separated identifier.
 */
function lean_webmcp_sanitize_toolname($toolname) {
	$toolname = strtolower((string) $toolname);
	$toolname = preg_replace('/[^a-z0-9_]/', '_', $toolname);
	$toolname = preg_replace('/_+/', '_', $toolname);
	return trim($toolname, '_');
}

/**
 * Function: lean_webmcp_attrs
 * Purpose: Render an associative array as an escaped HTML attribute string.
 *          Boolean true renders a bare attribute; empty/false/null values are skipped.
 *
 * @param array $attrs name => value pairs.
 * @return string Leading-space attribute string, or '' when disabled.
 */
function lean_webmcp_attrs($attrs = array()) {
	if (!lean_webmcp_enabled()) {
		return '';
	}

	$out = '';

	foreach ($attrs as $name => $value) {
		if ($value === true) {
			$out .= ' ' . esc_attr($name);
			continue;
		}

		if ($value === false || $value === null || $value === '') {
			continue;
		}

		$out .= sprintf(' %s="%s"', esc_attr($name), esc_attr($value));
	}

	return $out;
}

/**
 * Function: lean_webmcp_tool_config
 * Purpose: Return the toolname/tooldescription preset for a given tool key.
 *
 * @param string $tool    Tool key (request_service_estimate|submit_contact_request|request_callback).
 * @param array  $context Optional context (e.g. 'page_title').
 * @return array { toolname, tooldescription }
 */
function lean_webmcp_tool_config($tool = 'request_service_estimate', $context = array()) {
	$page_title = isset($context['page_title']) ? $context['page_title'] : '';

	$configs = array(
		'request_service_estimate' => array(
			'toolname'        => 'request_service_estimate',
			'tooldescription' => $page_title
				? sprintf('Submits a service estimate request from the %s page.', $page_title)
				: 'Submits a service estimate request to the business.',
		),
		'submit_contact_request' => array(
			'toolname'        => 'submit_contact_request',
			'tooldescription' => $page_title
				? sprintf('Submits a general contact request from the %s page.', $page_title)
				: 'Submits a general contact request to the business.',
		),
		'request_callback' => array(
			'toolname'        => 'request_callback',
			'tooldescription' => 'Submits a request for the business to call the customer back.',
		),
	);

	$config = isset($configs[$tool]) ? $configs[$tool] : $configs['request_service_estimate'];

	return apply_filters('lean_webmcp_tool_config', $config, $tool, $context);
}

/**
 * Function: lean_webmcp_form_attrs
 * Purpose: Build the <form>-level WebMCP attributes string.
 *
 * Note: `toolautosubmit` is intentionally NOT supported. This form submits via a
 * JS handler that appends the AJAX action, daily token, and page metadata at
 * submit time; the markup has no native action/method/token controls, so an
 * agent-triggered native autosubmit could not satisfy the endpoint's contract.
 * Agents fill the form; the user submits.
 *
 * @param array $args { tool, description, page_title }
 * @return string Attribute string for the <form> tag, or '' when disabled.
 */
function lean_webmcp_form_attrs($args = array()) {
	if (!lean_webmcp_enabled()) {
		return '';
	}

	$defaults = array(
		'tool'        => 'request_service_estimate',
		'description' => '',
		'page_title'  => '',
	);

	$args = wp_parse_args($args, $defaults);

	$config = lean_webmcp_tool_config($args['tool'], array(
		'page_title' => $args['page_title'],
	));

	$attrs = array(
		'toolname'        => lean_webmcp_sanitize_toolname($config['toolname']),
		'tooldescription' => $args['description'] ? $args['description'] : $config['tooldescription'],
	);

	return lean_webmcp_attrs($attrs);
}

/**
 * Function: lean_webmcp_param_attr
 * Purpose: Build the toolparamdescription attribute for a known form field.
 *
 * Deliberately omits the `website` honeypot: WebMCP exposes every *named* field
 * as a tool parameter and offers no exclusion mechanism, so we cannot keep the
 * trap out of the schema — but we must not hand agents a description telling them
 * it is a spam trap to leave blank, which would make the honeypot deterministically
 * bypassable. The server still relies on rate limiting (a control that does not
 * disclose which field is the trap) alongside the token check.
 *
 * @param string $field Field name (name|email|phone|address|message).
 * @return string Attribute string, or '' if the field is unknown/disabled.
 */
function lean_webmcp_param_attr($field) {
	if (!lean_webmcp_enabled()) {
		return '';
	}

	$descriptions = array(
		'name'    => "The customer's full name.",
		'email'   => "The customer's email address.",
		'phone'   => "The customer's phone number.",
		'address' => 'The service address or project location, if applicable.',
		'message' => 'Details about the service request, problem, project, or question.',
	);

	if (empty($descriptions[$field])) {
		return '';
	}

	return lean_webmcp_attrs(array(
		'toolparamdescription' => $descriptions[$field],
	));
}
