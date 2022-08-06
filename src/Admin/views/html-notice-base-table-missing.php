<?php

	namespace Hizzle\Downloads\Admin;

	/**
	 * Admin View: Notice - Base table missing.
	 *
	 */

	defined( 'ABSPATH' ) || exit;

?>
<div class="error hizzle-downloads-message">

	<p>
		<strong><?php esc_html_e( 'Database tables missing', 'hizzle-downloads' ); ?></strong>
	</p>

	<p>
		<?php
			printf(
				/* translators: %1%s: Missing tables (seperated by ",")*/
				esc_html__( 'One or more database tables required for Hizzle Downloads to function are missing, some features may not work as expected. Missing tables: %1$s.', 'hizzle-downloads' ),
				'<code>' . esc_html( implode( ', ', get_option( 'hizzle_downloads_schema_missing_tables' ) ) ) . '</code>'
			);
		?>
	</p>

	<p>
		<a class="button button-primary" href="<?php echo esc_url( Admin::action_url( 'verify_db_tables' ) ); ?>">
			<?php esc_html_e( 'Create tables', 'hizzle-downloads' ); ?>
		</a>
	</p>
</div>
