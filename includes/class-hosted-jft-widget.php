<?php
/**
 * Hosted JFT sidebar widget.
 *
 * @package Hosted_JFT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays today's Just For Today reading in a sidebar/footer widget.
 */
class Hosted_JFT_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'Hosted_JFT_widget',
			__( 'Hosted JFT', 'hosted-jft' ),
			[
				'classname'   => 'Hosted_JFT_widget',
				'description' => __( 'Displays the Just For Today.', 'hosted-jft' ),
			]
		);
	}

	/**
	 * Output the widget.
	 *
	 * @param array $args     Theme widget wrappers.
	 * @param array $instance Saved widget settings.
	 */
	public function widget( $args, $instance ) {
		// $args values are theme-provided structural markup and are safe to output as-is.
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . esc_html( apply_filters( 'widget_title', $instance['title'] ) ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$jft = Hosted_JFT::get_jft();
		if ( null === $jft ) {
			echo '<div class="jft-widget-excerpt">' . esc_html__( 'No JFT found for today.', 'hosted-jft' ) . '</div>';
			echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		$page_url  = (string) get_option( 'jft_page_url', '' );
		$more_text = (string) get_option( 'jft_more_text', '' );

		echo '<div class="jft-widget-title">' . esc_html( $jft['title'] ) . '</div><br/>';
		echo '<div class="jft-widget-excerpt">' . esc_html( $jft['excerpt'] ) . '</div>';
		if ( '' !== $page_url ) {
			echo '&nbsp;&nbsp;<a href="' . esc_url( $page_url ) . '" class="jft-widget-link">' . esc_html( $more_text ) . '</a><br/><br/>';
		}

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Output the widget settings form.
	 *
	 * @param array $instance Saved widget settings.
	 * @return void
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Title', 'hosted-jft' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'hosted-jft' ); ?>
			</label>
			<input
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				type="text"
				value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}

	/**
	 * Sanitize widget settings on save.
	 *
	 * @param array $new_instance New settings.
	 * @param array $old_instance Previous settings.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = [];
		$instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		return $instance;
	}
}
