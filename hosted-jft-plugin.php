<?php
/**
 * Plugin Name: Hosted JFT
 * Plugin URI: https://wordpress.org/plugins/hosted-jft/
 * Description: Allows an NA Community to host their own translated version of the Just For Today (JFT) via the [hosted_jft] shortcode or the Hosted JFT widget.
 * Version: 1.2.0
 * Author: bmltenabled
 * Author URI: https://bmlt.app
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hosted-jft
 *
 * @package Hosted_JFT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HOSTED_JFT_VERSION', '1.2.0' );

require_once __DIR__ . '/includes/class-hosted-jft-widget.php';

/**
 * Hosted JFT plugin.
 *
 * Looks up the post for "today" (matched by a custom field holding an MM-DD date)
 * and exposes it through a shortcode, a widget, and a REST endpoint.
 */
class Hosted_JFT {

	private static ?self $instance = null;

	/** REST namespace for the public JFT endpoint. */
	const REST_NAMESPACE = 'hosted-jft/v1';

	/** Settings group used by the options page. */
	const SETTINGS_GROUP = 'hosted-jft-plugin-settings-group';

	/** Legacy pretty-URL path kept for backwards compatibility with existing consumers. */
	const LEGACY_ENDPOINT = 'get-jft';

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'hosted_jft', [ static::class, 'render_shortcode' ] );
		add_action( 'widgets_init', [ static::class, 'register_widget' ] );
		add_action( 'admin_menu', [ static::class, 'admin_menu' ] );
		add_action( 'admin_init', [ static::class, 'register_settings' ] );
		add_action( 'rest_api_init', [ static::class, 'register_rest_routes' ] );
		add_action( 'template_redirect', [ static::class, 'handle_legacy_endpoint' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ static::class, 'settings_link' ] );
	}

	// -------------------------------------------------------------------------
	// Core lookup
	// -------------------------------------------------------------------------

	/**
	 * Fetch the JFT post for "today" in the given (or configured) time zone.
	 *
	 * @param string $timezone Optional IANA time zone identifier. Falls back to the saved option.
	 * @return array{title:string,content:string,excerpt:string,url:string}|null
	 */
	public static function get_jft( string $timezone = '' ): ?array {
		$custom_field = (string) get_option( 'jft_custom_field', '' );
		if ( '' === $custom_field ) {
			return null;
		}

		$timezone = self::sanitize_timezone( $timezone );
		if ( '' === $timezone ) {
			$timezone = self::sanitize_timezone( (string) get_option( 'jft_timezone', '' ) );
		}

		try {
			$now = new DateTimeImmutable( 'now', new DateTimeZone( '' !== $timezone ? $timezone : 'UTC' ) );
		} catch ( Exception $e ) {
			$now = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		}
		$today = $now->format( 'm-d' );

		$posts = get_posts(
			[
				'numberposts' => 1,
				'post_type'   => 'post',
				'post_status' => 'publish',
				'meta_key'    => $custom_field, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => $today,        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);

		if ( empty( $posts ) ) {
			return null;
		}

		$post = $posts[0];
		return [
			'title'   => get_the_title( $post ),
			'content' => (string) $post->post_content,
			'excerpt' => (string) $post->post_excerpt,
			'url'     => (string) get_permalink( $post ),
		];
	}

	/**
	 * Validate a time zone string against the list of known IANA identifiers.
	 *
	 * @param mixed $timezone Raw time zone value.
	 * @return string A valid identifier, or '' when invalid/empty.
	 */
	public static function sanitize_timezone( $timezone ): string {
		$timezone = is_string( $timezone ) ? trim( $timezone ) : '';
		if ( '' === $timezone ) {
			return '';
		}
		return in_array( $timezone, timezone_identifiers_list(), true ) ? $timezone : '';
	}

	// -------------------------------------------------------------------------
	// Shortcode
	// -------------------------------------------------------------------------

	/**
	 * Render the [hosted_jft] shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ): string {
		$atts = shortcode_atts( [ 'timezone' => '' ], $atts, 'hosted_jft' );
		$jft  = self::get_jft( (string) $atts['timezone'] );

		if ( null === $jft || '' === trim( $jft['content'] ) || '[hosted_jft]' === $jft['content'] ) {
			return esc_html__( 'No JFT found for today, there could be a problem with settings or a missing post for today.', 'hosted-jft' );
		}

		$title = '<div class="spo-title"><h2 class="spo-title">' . esc_html( $jft['title'] ) . '</h2></div>';

		return $title . apply_filters( 'the_content', $jft['content'] );
	}

	// -------------------------------------------------------------------------
	// Widget
	// -------------------------------------------------------------------------

	public static function register_widget(): void {
		register_widget( 'Hosted_JFT_Widget' );
	}

	// -------------------------------------------------------------------------
	// REST endpoint
	// -------------------------------------------------------------------------

	public static function register_rest_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/jft',
			[
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'args'                => [
					'tz' => [
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => [ static::class, 'sanitize_timezone' ],
					],
				],
				'callback'            => [ static::class, 'rest_jft' ],
			]
		);
	}

	/**
	 * REST callback for GET /hosted-jft/v1/jft.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_jft( WP_REST_Request $request ) {
		$jft = self::get_jft( (string) $request->get_param( 'tz' ) );
		if ( null === $jft ) {
			return new WP_Error( 'hosted_jft_not_found', __( 'No JFT found for today.', 'hosted-jft' ), [ 'status' => 404 ] );
		}
		return rest_ensure_response( [ $jft ] );
	}

	/**
	 * Backwards-compatible JSON endpoint at /get-jft (optionally ?tz=...).
	 *
	 * Kept so existing integrations continue to work; new consumers should use
	 * the REST route registered in register_rest_routes().
	 */
	public static function handle_legacy_endpoint(): void {
		if ( is_admin() ) {
			return;
		}
		$request = isset( $GLOBALS['wp']->request ) ? (string) $GLOBALS['wp']->request : '';
		if ( self::LEGACY_ENDPOINT !== $request ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public, read-only endpoint; no state is changed.
		$tz  = isset( $_GET['tz'] ) ? sanitize_text_field( wp_unslash( $_GET['tz'] ) ) : '';
		$jft = self::get_jft( $tz );
		wp_send_json( null === $jft ? [] : [ $jft ] );
	}

	// -------------------------------------------------------------------------
	// Admin settings
	// -------------------------------------------------------------------------

	public static function admin_menu(): void {
		add_options_page(
			__( 'Hosted JFT Plugin Settings', 'hosted-jft' ),
			__( 'Hosted JFT', 'hosted-jft' ),
			'manage_options',
			'hosted-jft-plugin',
			[ static::class, 'render_settings_page' ]
		);
	}

	/**
	 * Add a "Settings" link on the Plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public static function settings_link( array $links ): array {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=hosted-jft-plugin' ) ) . '">' . esc_html__( 'Settings', 'hosted-jft' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	public static function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			'jft_timezone',
			[
				'type'              => 'string',
				'sanitize_callback' => [ static::class, 'sanitize_timezone' ],
				'default'           => '',
			]
		);
		register_setting(
			self::SETTINGS_GROUP,
			'jft_custom_field',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			]
		);
		register_setting(
			self::SETTINGS_GROUP,
			'jft_page_url',
			[
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			]
		);
		register_setting(
			self::SETTINGS_GROUP,
			'jft_more_text',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			]
		);
	}

	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$custom_field = (string) get_option( 'jft_custom_field', '' );
		$tz_setting   = (string) get_option( 'jft_timezone', '' );
		$page_url     = (string) get_option( 'jft_page_url', '' );
		$more_text    = (string) get_option( 'jft_more_text', '' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Hosted JFT Plugin Settings', 'hosted-jft' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::SETTINGS_GROUP );
				?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Custom Field Name', 'hosted-jft' ); ?></th>
						<td>
							<input type="text" id="jft_custom_field" name="jft_custom_field" value="<?php echo esc_attr( $custom_field ); ?>">
							<p class="description"><?php esc_html_e( 'Choose the Custom Field Name for the JFT posts. Insert the [hosted_jft] shortcode on your page or post.', 'hosted-jft' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Time Zone', 'hosted-jft' ); ?></th>
						<td>
							<select id="jft_timezone" name="jft_timezone">
								<option value=""></option>
								<?php foreach ( timezone_identifiers_list() as $tz_item ) : ?>
									<option value="<?php echo esc_attr( $tz_item ); ?>" <?php selected( $tz_item, $tz_setting ); ?>>
										<?php echo esc_html( $tz_item ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'This should be set to your local time zone.', 'hosted-jft' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'JFT Page URL', 'hosted-jft' ); ?></th>
						<td>
							<input type="text" id="jft_page_url" name="jft_page_url" value="<?php echo esc_attr( $page_url ); ?>">
							<p class="description"><?php esc_html_e( 'The URL of the page that displays the JFT; used by the widget.', 'hosted-jft' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'More Text', 'hosted-jft' ); ?></th>
						<td>
							<input type="text" id="jft_more_text" name="jft_more_text" value="<?php echo esc_attr( $more_text ); ?>">
							<p class="description"><?php esc_html_e( 'The text for the "view full JFT" link; used by the widget.', 'hosted-jft' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

Hosted_JFT::get_instance();
