<?php
/**
 * Filename: perfmatters-defaults.php
 * Purpose: Auto-import the theme's tuned Perfmatters performance settings ONCE on
 *          theme activation, so a fresh site is fast out of the box (PSI ~100).
 * Created: 2026-06-22
 *
 * Perfmatters stores all of its settings in the `perfmatters_options` WordPress
 * option. Its own Tools > Export produces a JSON of exactly that array, and Tools >
 * Import just calls update_option() with it. This file does the same automatically,
 * reading the config committed at:
 *     assets/perfmatters/perfmatters-config.json
 *
 * Requirements / behaviour:
 * - The Perfmatters PLUGIN must already be installed + active. This theme never
 *   installs or bundles the plugin; it only seeds its settings.
 * - Runs at most ONCE per site (flag `lean_perfmatters_defaults_imported`), so it
 *   never re-clobbers settings a site owner later tunes by hand.
 * - No-ops silently if Perfmatters is inactive or the config file is missing/invalid.
 * - Preserves any license / API keys already present (never overwrites them blank).
 * - Disable entirely with:
 *       add_filter( 'lean_perfmatters_autoimport', '__return_false' );
 *
 * Main Functions:
 * - lean_perfmatters_import_defaults() - the after_switch_theme handler
 *
 * Dependencies: Perfmatters plugin (optional, checked at runtime).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_switch_theme', 'lean_perfmatters_import_defaults' );

/**
 * Function: lean_perfmatters_import_defaults
 * Purpose: Seed Perfmatters with the theme's tuned config on first activation.
 *
 * @return void
 */
function lean_perfmatters_import_defaults() {

	// Opt-out filter for sites that manage Perfmatters themselves.
	if ( ! apply_filters( 'lean_perfmatters_autoimport', true ) ) {
		return;
	}

	// Only ever seed once per site.
	if ( get_option( 'lean_perfmatters_defaults_imported' ) ) {
		return;
	}

	// Perfmatters must be active (it owns the perfmatters_options option).
	if ( ! defined( 'PERFMATTERS_VERSION' )
		&& ! function_exists( 'perfmatters' )
		&& ! class_exists( 'Perfmatters\\Config' ) ) {
		return;
	}

	$file = get_template_directory() . '/assets/perfmatters/perfmatters-config.json';
	if ( ! is_readable( $file ) ) {
		return;
	}

	$data = json_decode( file_get_contents( $file ), true );
	if ( ! is_array( $data ) || empty( $data ) ) {
		return;
	}

	// A Perfmatters export may be the flat options array, or wrapped with named
	// top-level sections. Support both shapes.
	$options = ( isset( $data['perfmatters_options'] ) && is_array( $data['perfmatters_options'] ) )
		? $data['perfmatters_options']
		: $data;
	$tools = ( isset( $data['perfmatters_tools'] ) && is_array( $data['perfmatters_tools'] ) )
		? $data['perfmatters_tools']
		: null;

	// Never blank out a license / API key the site already holds.
	$existing = get_option( 'perfmatters_options' );
	if ( is_array( $existing ) ) {
		foreach ( $existing as $key => $value ) {
			if ( preg_match( '/license|api_?key|rku/i', $key ) && ! empty( $value ) && empty( $options[ $key ] ) ) {
				$options[ $key ] = $value;
			}
		}
	}

	update_option( 'perfmatters_options', $options );
	if ( null !== $tools ) {
		update_option( 'perfmatters_tools', $tools );
	}

	// Mark done (timestamp), so this never runs again on this site.
	update_option( 'lean_perfmatters_defaults_imported', gmdate( 'c' ) );

	// Drop any previously generated used-CSS so it regenerates with the new settings.
	if ( function_exists( 'perfmatters_clear_used_css' ) ) {
		perfmatters_clear_used_css();
	}
}
