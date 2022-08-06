<?php
/**
 * Admin View: Custom Notices
 *
 * @var array $notice_data
 * @var string $notice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$notice_type = isset( $notice_data['type'] ) ? $notice_data['type'] : 'updated';
$notice_msg  = isset( $notice_data['msg'] ) ? $notice_data['msg'] : '';
?>

<?php if ( ! empty( $notice_msg ) ) : ?>
	<div id="hizzle-downloads-message-<?php echo esc_attr( $notice ); ?>" class="notice <?php echo esc_attr( $notice_type ); ?> hizzle-downloads-message is-dismissible">
		<p>
			<?php echo wp_kses_post( $notice_msg ); ?>
		</p>
	</div>
<?php endif; ?>
