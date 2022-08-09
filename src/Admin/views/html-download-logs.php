<?php

	namespace Hizzle\Downloads\Admin;

    /**
     * Admin View: Download Logs Table.
     *
     */
    defined( 'ABSPATH' ) || exit;

	$logs_table = new Download_Logs_Table();

?>

<div class="wrap hizzle-downloads-page" id="hizzle-downloads-wrapper">

	<h1 class="wp-heading-inline"><?php esc_html_e( 'Downloadable Logs', 'hizzle-downloads' ); ?></h1>

	<?php Notices::output_custom_notices(); ?>

	<form id="hizzle-download-logs-table" method="GET">
		<?php $logs_table->search_box( __( 'Search Logs', 'hizzle-downloads' ), 'search' ); ?>
		<?php $logs_table->display(); ?>
	</form>

</div>
