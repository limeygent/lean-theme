<?php
/**
 * Filename: mega-menu-walker.php
 * Purpose:  Custom Walker_Nav_Menu that renders a multi-column mega-menu
 *           dropdown when a top-level menu item carries the CSS class
 *           `mega-menu`. Children are grouped into columns by the value
 *           of each item's Description field, preserving menu order
 *           within each column.
 *
 *           For non-mega menu items, defers to the default WordPress
 *           walker (zero behavior change for any other dropdown).
 *
 * Used by:  template-parts/lean-header.php (passed as 'walker' arg to
 *           wp_nav_menu).
 */

if (!defined('ABSPATH')) exit;

class Lean_Mega_Menu_Walker extends Walker_Nav_Menu {

	/**
	 * Intercept rendering for top-level items flagged with the
	 * `mega-menu` CSS class. Render the parent <li> via the default
	 * walker, then emit a custom <ul class="sub-menu mega-menu">
	 * grouped by description, and stop default recursion into the
	 * children.
	 */
	public function display_element($element, &$children_elements, $max_depth, $depth, $args, &$output) {
		$is_mega = $depth === 0
			&& is_object($element)
			&& in_array('mega-menu', (array) $element->classes, true);

		if (!$is_mega) {
			parent::display_element($element, $children_elements, $max_depth, $depth, $args, $output);
			return;
		}

		// Marker class so CSS can target the parent <li> with position: static.
		$element->classes[] = 'mega-menu-parent';

		// Collect children, then unset so the default walker doesn't recurse.
		$children = isset($children_elements[$element->ID]) ? $children_elements[$element->ID] : [];
		unset($children_elements[$element->ID]);

		// Emit parent <li> + <a> (via default start_el).
		$this->start_el($output, $element, $depth, $args);

		// Emit the mega panel. Plain semantic <ul><li><a> — no role="menu"/menuitem.
		// Those ARIA roles are for application menus (keyboard arrow nav, escape,
		// type-ahead) and actively mislead screen readers when used on a website
		// nav dropdown. WAI-ARIA Authoring Practices: site nav stays as plain
		// <nav><ul><li><a>, which screen readers already announce correctly.
		$output .= '<ul class="sub-menu mega-menu">';
		$output .= $this->render_mega_columns($children);
		$output .= '</ul>';

		// Close parent <li>.
		$this->end_el($output, $element, $depth, $args);
	}

	/**
	 * Group $children by description (= column heading), preserving menu order
	 * within each column. First-seen description determines column order.
	 * Items with empty description go into an untitled column.
	 */
	private function render_mega_columns($children) {
		if (empty($children)) return '';

		$columns      = [];
		$column_order = [];

		foreach ($children as $child) {
			$heading = trim((string) $child->description);
			if (!isset($columns[$heading])) {
				$columns[$heading] = [];
				$column_order[]    = $heading;
			}
			$columns[$heading][] = $child;
		}

		$html = '';
		foreach ($column_order as $heading) {
			$items = $columns[$heading];

			$html .= '<li class="mega-col">';

			if ($heading !== '') {
				$html .= '<div class="mega-col-title">' . esc_html($heading) . '</div>';
			}

			$html .= '<ul class="list-unstyled mb-0">';
			foreach ($items as $item) {
				$url   = esc_url($item->url);
				$title = esc_html($item->title);
				$html .= '<li><a href="' . $url . '">' . $title . '</a></li>';
			}
			$html .= '</ul>';

			$html .= '</li>';
		}

		return $html;
	}
}
