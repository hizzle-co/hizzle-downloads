<?php

/**
 * Displays a password input field.
 *
 * @var \Hizzle\Downloads\Download $file
 * @since   1.0.3
 */

defined( 'ABSPATH' ) || exit;

// fake post to prevent notices in wp_enqueue_scripts call
$GLOBALS['post'] = new WP_Post( (object) array( 'filter' => 'raw' ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

// render simple page with form in it.
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<meta name="robots" content="noindex, nofollow" />
	<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
	<?php
		wp_enqueue_scripts();
		wp_print_styles();
		wp_print_head_scripts();
		wp_custom_css_cb();
		wp_site_icon();
	?>
	<style type="text/css">
		body{ 
			background: white;
			width: 100%;
			max-width: 100%;
			text-align: left;
		}

		html, body, #page, #content {
			padding: 0 !important;
			margin: 0 !important;
		}

		/* hide all other elements */
		body::before,
		body::after,
		body > *:not(#hizzle-downloads-password) { 
			display:none !important; 
		}

		#hizzle-downloads-password {
			display: flex !important;
			width: 100%;
			height: 100%;
			min-height: 90vh;
			padding: 20px;
			border: 0;
			margin: 0;
			align-items: center;
			justify-content: center;
		}

		.hizzle-downloads-password-inner {
			width: 400px;
    		max-width: 100%;
		}

		#hizzle-downloads-password .hizzle-downloads-help-text {
			font-size: 14px;
			margin: 2px 0 5px;
			color: #646970;
			display: block;
		}
	</style>
</head>
<body class="page-template-default page">
	<div id="hizzle-downloads-password" class="page type-page status-publish hentry post post-content">
		<div class="hizzle-downloads-password-inner">
			<form method="POST" autocomplete="off" action="<?php echo esc_url( add_query_arg( array() ) ); ?>">
				<h2><?php echo esc_html( $file->get_file_name() ); ?></h2>
				<p>
					<label for="hizzle-downloads-password__input"><?php esc_html_e( 'Password', 'hizzle-downloads' ); ?></label>
					<input type="password" name="hizzle_downloads_file_password" id="hizzle-downloads-password__input" autocomplete="new-password" />
					<span class="hizzle-downloads-help-text"><?php esc_html_e( 'Enter the file password', 'hizzle-downloads' ); ?></span>
				</p>
				<p>
					<input type="submit" class="button button-primary btn btn-primary" value="<?php esc_attr_e( 'Download File', 'hizzle-downloads' ); ?>" />
				</p>
			</form>
		</div>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
