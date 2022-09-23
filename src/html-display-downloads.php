<?php

namespace Hizzle\Downloads;

/**
 * Displays a list of downloads on the frontend.
 *
 * @var array $downloads
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

?>
<style>

	.hizzle-available-downloads {
		margin-top: 1.5em;
		margin-bottom: 1.5em;
		overflow-x: auto;
	}

	.hizzle-available-downloads p:last-child {
		margin-bottom: 0;
	}

	.hizzle-no-downloads-found {
		color: #a00;
	}

	.hizzle-available-downloads h3 {
		margin-bottom: 0.4rem;
    	font-size: 1.3rem;
	}

	.hizzle-available-downloads .table {
		width: 100%;
    	margin-bottom: 1.6rem;
		border: 1px solid #dee2e6;
		border-collapse: collapse;
	}

	.hizzle-available-downloads .table th {
		vertical-align: bottom;
		border-bottom: 1px solid #dee2e6;
		background-color: rgba(0,0,0,0.02);
		text-align: left;
	}

	.hizzle-available-downloads .table tr:not(:last-child) td {
		border-bottom: 1px solid #dee2e6;
	}

	.hizzle-available-downloads .tables td:not(:last-child),
	.hizzle-available-downloads .tables th:not(:last-child) {
		border-right: 1px solid #dee2e6;
	}

	.hizzle-available-downloads .table td,
	.hizzle-available-downloads .table th {
		padding: 10px 20px;
	}

	.hizzle-available-downloads th.hizzle-download-file-link {
		width: 150px;
	}

</style>

<div class="hizzle-available-downloads">

	<?php if ( empty( $downloads ) ) : ?>
		<p class="hizzle-no-downloads-found">
			<?php esc_html_e( 'No downloads available yet.', 'hizzle-downloads' ); ?>
		</p>
	<?php else : ?>
		<?php foreach ( $downloads as $category => $category_downloads ) : ?>

			<?php if ( 1 < count( $downloads ) && ! empty( $category ) ) : ?>
				<h3><?php echo esc_html( $category ); ?></h3>
			<?php endif; ?>

			<table class="table">

				<thead>
					<tr>
						<th class="hizzle-download-file-name"><?php esc_html_e( 'File', 'hizzle-downloads' ); ?></th>
						<th class="hizzle-download-file-link"><?php esc_html_e( 'Action', 'hizzle-downloads' ); ?></th>
					</tr>
				</thead>

				<tbody>
					<?php /** @var Download $download */ ?>
					<?php foreach ( $category_downloads as $download ) : ?>
						<tr>
							<td class="hizzle-download-file-name"><?php echo esc_html( $download->get_file_name() ); ?></td>
							<td class="hizzle-download-file-link">
								<a class="hizzle-download-file-link__anchor" title="<?php echo esc_attr( $download->get_downloaded_file_name() ); ?>" href="<?php echo esc_url( $download->get_download_url() ); ?>">
									<?php esc_html_e( 'Download', 'hizzle-downloads' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

		<?php endforeach; ?>
	<?php endif; ?>
</div>
