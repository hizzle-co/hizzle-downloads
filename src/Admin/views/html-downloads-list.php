<?php

	namespace Hizzle\Downloads\Admin;

    /**
     * Admin View: Downloads Table.
     *
     */
    defined( 'ABSPATH' ) || exit;

	$downloads_table = new Downloads_Table();

?>

<div class="wrap hizzle-downloads-page" id="hizzle-downloads-wrapper">

	<h1 class="wp-heading-inline">
		<span><?php esc_html_e( 'Downloadable Files', 'hizzle-downloads' ); ?></span>
		<a href="<?php echo esc_url( add_query_arg( 'hizzle_download', '0', admin_url( 'admin.php?page=hizzle-downloads' ) ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'hizzle-downloads' ); ?></a>
	</h1>

	<?php Notices::output_custom_notices(); ?>

	<p class="description">
		<?php
			printf(
				// translators: %s is a shortcode.
				esc_html__( 'Use the %s shortcode to display all downloads that the current user has access to.', 'hizzle-downloads' ),
				'<code>[hizzle-downloads]</code>'
			);
		?>
	</p>

	<form id="hizzle-downloads-table" method="post">
		<?php $downloads_table->search_box( __( 'Search Downloads', 'hizzle-downloads' ), 'search' ); ?>
		<?php $downloads_table->display(); ?>
	</form>

</div>
