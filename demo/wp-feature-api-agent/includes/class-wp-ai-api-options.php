<?php
/**
 * Options class for the AI API Proxy.
 *
 * @package WordPress\Feature_API_Agent
 */

namespace A8C\WpFeatureApiAgent;

/**
 * Handles the settings page for the AI API Proxy.
 */
class WP_AI_API_Options {

	/**
	 * Option name for OpenAI API key.
	 *
	 * @var string
	 */
	const OPENAI_OPTION_NAME = 'wp_ai_api_proxy_openai_key';

	/**
	 * Option name for Anthropic API key.
	 *
	 * @var string
	 */
	const ANTHROPIC_OPTION_NAME = 'wp_ai_api_proxy_anthropic_key';

	/**
	 * Option name for Google API key.
	 *
	 * @var string
	 */
	const GOOGLE_OPTION_NAME = 'wp_ai_api_proxy_google_key';

	/**
	 * Option name for default provider.
	 *
	 * @var string
	 */
	const DEFAULT_PROVIDER_OPTION_NAME = 'wp_ai_api_proxy_default_provider';

	/**
	 * Option name for default model.
	 *
	 * @var string
	 */
	const DEFAULT_MODEL_OPTION_NAME = 'wp_ai_api_proxy_default_model';

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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Adds the options page to the admin menu.
	 */
	public function add_options_page() {
		add_options_page(
			__( 'AI API Proxy Settings', 'wp-feature-api-agent' ),
			__( 'AI API Proxy', 'wp-feature-api-agent' ),
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

		register_setting(
			self::OPTION_PAGE,
			self::ANTHROPIC_OPTION_NAME,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			self::OPTION_PAGE,
			self::GOOGLE_OPTION_NAME,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			self::OPTION_PAGE,
			self::DEFAULT_PROVIDER_OPTION_NAME,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			self::OPTION_PAGE,
			self::DEFAULT_MODEL_OPTION_NAME,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		// Add settings section.
		add_settings_section(
			'wp_ai_api_proxy_api_section',
			__( 'API Settings', 'wp-feature-api-agent' ),
			array( $this, 'render_api_section_description' ),
			self::OPTION_PAGE
		);

		// Add settings fields.
		add_settings_field(
			'openai_api_key',
			__( 'OpenAI API Key', 'wp-feature-api-agent' ),
			array( $this, 'render_openai_api_key_field' ),
			self::OPTION_PAGE,
			'wp_ai_api_proxy_api_section'
		);

		add_settings_field(
			'anthropic_api_key',
			__( 'Anthropic API Key', 'wp-feature-api-agent' ),
			array( $this, 'render_anthropic_api_key_field' ),
			self::OPTION_PAGE,
			'wp_ai_api_proxy_api_section'
		);

		add_settings_field(
			'google_api_key',
			__( 'Google API Key', 'wp-feature-api-agent' ),
			array( $this, 'render_google_api_key_field' ),
			self::OPTION_PAGE,
			'wp_ai_api_proxy_api_section'
		);

		// Add default provider and model section.
		add_settings_section(
			'wp_ai_api_proxy_default_section',
			__( 'Default Settings', 'wp-feature-api-agent' ),
			array( $this, 'render_default_section_description' ),
			self::OPTION_PAGE
		);

		add_settings_field(
			'default_provider',
			__( 'Default Provider', 'wp-feature-api-agent' ),
			array( $this, 'render_default_provider_field' ),
			self::OPTION_PAGE,
			'wp_ai_api_proxy_default_section'
		);

		add_settings_field(
			'default_model',
			__( 'Default Model', 'wp-feature-api-agent' ),
			array( $this, 'render_default_model_field' ),
			self::OPTION_PAGE,
			'wp_ai_api_proxy_default_section'
		);
	}

	/**
	 * Renders the API section description.
	 */
	public function render_api_section_description() {
		echo '<p>' . esc_html__( 'Configure your API keys for the AI services you want to use.', 'wp-feature-api-agent' ) . '</p>';
	}

	/**
	 * Renders the default section description.
	 */
	public function render_default_section_description() {
		echo '<p>' . esc_html__( 'Configure the default provider and model to use.', 'wp-feature-api-agent' ) . '</p>';
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
	 * Renders the Anthropic API key field.
	 */
	public function render_anthropic_api_key_field() {
		$value = get_option( self::ANTHROPIC_OPTION_NAME );
		?>
		<input type="password"
			   name="<?php echo esc_attr( self::ANTHROPIC_OPTION_NAME ); ?>"
			   value="<?php echo esc_attr( $value ); ?>"
			   class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter your Anthropic API key.', 'wp-feature-api-agent' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders the Google API key field.
	 */
	public function render_google_api_key_field() {
		$value = get_option( self::GOOGLE_OPTION_NAME );
		?>
		<input type="password"
			   name="<?php echo esc_attr( self::GOOGLE_OPTION_NAME ); ?>"
			   value="<?php echo esc_attr( $value ); ?>"
			   class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter your Google API key.', 'wp-feature-api-agent' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders the default provider field.
	 */
	public function render_default_provider_field() {
		$value = get_option( self::DEFAULT_PROVIDER_OPTION_NAME, 'openai' );
		$providers = array(
			'openai'    => __( 'OpenAI', 'wp-feature-api-agent' ),
			'anthropic' => __( 'Anthropic', 'wp-feature-api-agent' ),
			'google'    => __( 'Google', 'wp-feature-api-agent' ),
		);
		?>
		<select name="<?php echo esc_attr( self::DEFAULT_PROVIDER_OPTION_NAME ); ?>" id="default-provider">
			<?php foreach ( $providers as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select the default AI provider to use.', 'wp-feature-api-agent' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders the default model field.
	 */
	public function render_default_model_field() {
		$value = get_option( self::DEFAULT_MODEL_OPTION_NAME, 'gpt-3.5-turbo' );
		$provider = get_option( self::DEFAULT_PROVIDER_OPTION_NAME, 'openai' );
		$models = $this->get_provider_models( $provider );
		?>
		<select name="<?php echo esc_attr( self::DEFAULT_MODEL_OPTION_NAME ); ?>" id="default-model">
			<?php foreach ( $models as $model_id => $model_name ) : ?>
				<option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $value, $model_id ); ?>>
					<?php echo esc_html( $model_name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select the default model to use.', 'wp-feature-api-agent' ); ?>
		</p>
		<?php
	}

	/**
	 * Gets the models for a specific provider.
	 *
	 * @param string $provider The provider key.
	 * @return array The models for the provider.
	 */
	private function get_provider_models( $provider ) {
		$models = array();

		switch ( $provider ) {
			case 'openai':
				$models = array(
					'gpt-3.5-turbo'      => __( 'GPT-3.5 Turbo', 'wp-feature-api-agent' ),
					'gpt-4'              => __( 'GPT-4', 'wp-feature-api-agent' ),
					'gpt-4-turbo'        => __( 'GPT-4 Turbo', 'wp-feature-api-agent' ),
					'gpt-4o'             => __( 'GPT-4o', 'wp-feature-api-agent' ),
					'gpt-4o-mini'        => __( 'GPT-4o Mini', 'wp-feature-api-agent' ),
				);
				break;
			case 'anthropic':
				$models = array(
					'claude-3-opus-20240229' => __( 'Claude 3 Opus', 'wp-feature-api-agent' ),
					'claude-3-sonnet-20240229' => __( 'Claude 3 Sonnet', 'wp-feature-api-agent' ),
					'claude-3-haiku-20240307' => __( 'Claude 3 Haiku', 'wp-feature-api-agent' ),
					'claude-2.1' => __( 'Claude 2.1', 'wp-feature-api-agent' ),
				);
				break;
			case 'google':
				$models = array(
					'gemini-1.5-pro' => __( 'Gemini 1.5 Pro', 'wp-feature-api-agent' ),
					'gemini-1.5-flash' => __( 'Gemini 1.5 Flash', 'wp-feature-api-agent' ),
					'gemini-1.0-pro' => __( 'Gemini 1.0 Pro', 'wp-feature-api-agent' ),
					'gemma-2-9b' => __( 'Gemma 2 9B', 'wp-feature-api-agent' ),
				);
				break;
		}

		return $models;
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
		$anthropic_key = get_option( self::ANTHROPIC_OPTION_NAME );
		$google_key = get_option( self::GOOGLE_OPTION_NAME );

		if ( empty( $openai_key ) && empty( $anthropic_key ) && empty( $google_key ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php
					printf(
						/* translators: %s: URL to the settings page */
						esc_html__( 'No API keys are set. The AI API Proxy will not work. Please configure at least one API key in the %s.', 'wp-feature-api-agent' ),
						'<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::OPTION_PAGE ) ) . '">' . esc_html__( 'AI API Proxy settings', 'wp-feature-api-agent' ) . '</a>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Enqueues admin scripts.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'settings_page_' . self::OPTION_PAGE !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'wp-ai-api-proxy-admin',
			WP_AI_API_PROXY_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			WP_AI_API_PROXY_VERSION,
			true
		);

		wp_localize_script(
			'wp-ai-api-proxy-admin',
			'wpAiApiProxy',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wp-ai-api-proxy-nonce' ),
				'providers' => array(
					'openai' => $this->get_provider_models( 'openai' ),
					'anthropic' => $this->get_provider_models( 'anthropic' ),
					'google' => $this->get_provider_models( 'google' ),
				),
			)
		);
	}

	/**
	 * Get the OpenAI API key.
	 *
	 * @return string The OpenAI API key.
	 */
	public static function get_openai_api_key(): string {
		return get_option( self::OPENAI_OPTION_NAME, '' );
	}

	/**
	 * Get the Anthropic API key.
	 *
	 * @return string The Anthropic API key.
	 */
	public static function get_anthropic_api_key(): string {
		return get_option( self::ANTHROPIC_OPTION_NAME, '' );
	}

	/**
	 * Get the Google API key.
	 *
	 * @return string The Google API key.
	 */
	public static function get_google_api_key(): string {
		return get_option( self::GOOGLE_OPTION_NAME, '' );
	}

	/**
	 * Gets the default provider.
	 *
	 * @return string The default provider.
	 */
	public static function get_default_provider() {
		return get_option( self::DEFAULT_PROVIDER_OPTION_NAME, 'openai' );
	}

	/**
	 * Gets the default model.
	 *
	 * @return string The default model.
	 */
	public static function get_default_model() {
		return get_option( self::DEFAULT_MODEL_OPTION_NAME, 'gpt-3.5-turbo' );
	}
}
