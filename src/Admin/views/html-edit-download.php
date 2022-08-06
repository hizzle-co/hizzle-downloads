<?php

    namespace Hizzle\Downloads\Admin;

    /**
     * Admin View: Downloads edit page.
     *
     * @var \Hizzle\Downloads\Download $download
     */

    defined( 'ABSPATH' ) || exit;

?>

<div class="wrap hizzle-downloads-page" id="hizzle-downlads-wrapper">

    <h1 class="wp-heading-inline">
        <?php if ( $download->exists() ) : ?>
            <span><?php esc_html_e( 'Edit Downloadable File', 'hizzle-downloads' ); ?></span>
            <a href="<?php echo esc_url( add_query_arg( 'hizzle_download', '0', admin_url( 'admin.php?page=hizzle-downloads' ) ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'hizzle-downloads' ); ?></a>
        <?php else : ?>
		    <span><?php esc_html_e( 'Add Downloadable File', 'hizzle-downloads' ); ?></span>
        <?php endif; ?>
    </h1>

    <form id="hizzle-edit-download" class="hizzle-basic-form" method="POST">
        <?php Admin::action_field( 'save_download' ); ?>
        <?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
		<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
        <input type="hidden" name="hizzle_download_id" value="<?php echo esc_attr( $download->get_id() ); ?>" />

        <div id="poststuff">
			<div id="post-body" class="metabox-holder columns-<?php echo 1 === get_current_screen()->get_columns() ? '1' : '2'; ?>">

				<div id="postbox-container-1" class="postbox-container">
    				<?php do_meta_boxes( get_current_screen()->id, 'side', $download ); ?>
				</div>

				<div id="postbox-container-2" class="postbox-container">
    				<?php do_meta_boxes( get_current_screen()->id, 'normal', $download ); ?>
					<?php do_meta_boxes( get_current_screen()->id, 'advanced', $download ); ?>
				</div>
			</div>
		</div>

		<script>jQuery(document).ready(function(){ postboxes.add_postbox_toggles('<?php echo esc_js( get_current_screen()->id ); ?>'); });</script>

    </form>
</div>
