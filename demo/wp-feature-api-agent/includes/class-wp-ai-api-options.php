<?php
/**
 * Options class for the AI API Proxy & WP Feature Agent Demo.
 *
 * @package WordPress\Feature_API_Agent
 */

namespace A8C\WpFeatureApiAgent;

/**
 * Handles the settings page for the AI API Proxy & WP Feature Agent Demo.
 */
class WP_AI_API_Options {

	/**
	 * Option name for OpenAI API key.
	 *
	 * @var string
	 */
	const OPENAI_OPTION_NAME = 'wp_ai_api_proxy_openai_key';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const OPTION_PAGE = 'wp-ai-api-proxy-settings';

	/**
	 * Initializes the options page.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
	}

	/**
	 * Adds the options page to the admin menu.
	 */
	public function add_options_page() {
		add_options_page(
			__( 'WP Feature Agent Demo - Settings', 'wp-feature-api-agent' ),
			__( 'WP Feature Agent Demo', 'wp-feature-api-agent' ),
			'manage_options',
			self::OPTION_PAGE,
			array( $this, 'render_options_page' )
		);
	}

	/**
	 * Registers the settings.
	 */
	public function register_settings() {
		// Register settings for API keys.
		register_setting(
			self::OPTION_PAGE,
			self::OPENAI_OPTION_NAME,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		add_settings_section(
			'wp_ai_api_proxy_api_section',
			__( 'API Settings', 'wp-feature-api-agent' ),
			array( $this, 'render_api_section_description' ),
			self::OPTION_PAGE
		);

		add_settings_field(
			'openai_api_key',
			__( 'OpenAI API Key', 'wp-feature-api-agent' ),
			array( $this, 'render_openai_api_key_field' ),
			self::OPTION_PAGE,
			'wp_ai_api_proxy_api_section'
		);
	}

	/**
	 * Renders the API section description.
	 */
	public function render_api_section_description() {
		echo '<p>' . esc_html__( 'Configure your API keys for the AI services you want to use.', 'wp-feature-api-agent' ) . '</p>';
	}

	/**
	 * Renders the OpenAI API key field.
	 */
	public function render_openai_api_key_field() {
		$value = get_option( self::OPENAI_OPTION_NAME );
		?>
		<input type="password"
			   name="<?php echo esc_attr( self::OPENAI_OPTION_NAME ); ?>"
			   value="<?php echo esc_attr( $value ); ?>"
			   class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter your OpenAI API key.', 'wp-feature-api-agent' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders the options page.
	 */
	public function render_options_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_PAGE );
				do_settings_sections( self::OPTION_PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Displays admin notices.
	 */
	public function display_admin_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$openai_key = get_option( self::OPENAI_OPTION_NAME );

		if ( empty( $openai_key ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php
					printf(
						/* translators: %s: URL to the settings page */
						esc_html__( 'No API keys are set. The WP Feature Agent Demo will not work. Please configure your OpenAI API key in the %s.', 'wp-feature-api-agent' ),
						'<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::OPTION_PAGE ) ) . '">' . esc_html__( 'WP Feature Agent Demo settings', 'wp-feature-api-agent' ) . '</a>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Get the OpenAI API key.
	 *
	 * @return string The OpenAI API key.
	 */
	public static function get_openai_api_key(): string {
		return get_option( self::OPENAI_OPTION_NAME, '' );
	}
}
