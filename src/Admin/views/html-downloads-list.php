<?php

	namespace Hizzle\Downloads\Admin;

    /**
     * Admin View: Downloads Table.
     *
     */
    defined( 'ABSPATH' ) || exit;

	$downloads_table = new Downloads_Table();

?>

<div class="wrap hpay-downloads-page" id="hpay-wrapper">

	<h1 class="wp-heading-inline">
		<span><?php esc_html_e( 'Downloads', 'hizzle-pay' ); ?></span>
		<a href="<?php echo esc_url( add_query_arg( 'hpay_download', '0', admin_url( 'admin.php?page=hpay-downloads' ) ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'hizzle-pay' ); ?></a>
	</h1>

	<?php Notices::output_custom_notices(); ?>

	<form id="hpay-downloads-table" method="GET">
		<?php $downloads_table->search_box( __( 'Search Downloads', 'hizzle-pay' ), 'search' ); ?>
		<?php $downloads_table->display(); ?>
	</form>

</div>
