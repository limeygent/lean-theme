<?php
/**
 * Header Template
 *
 * Standard WordPress header. Opens doctype/html/head/body and renders the
 * site header via the existing template parts.
 */
?>
<!doctype html>
<html lang="<?php echo esc_attr(lean_get_page_language()); ?>">
	<head>
		<?php lean_get_template_part('template-parts/lean-head'); ?>
	</head>

	<body <?php body_class('lean-body'); ?>>
		<?php lean_get_template_part('template-parts/lean-header'); ?>

		<main id="lean-main" class="lean-main" tabindex="-1">
