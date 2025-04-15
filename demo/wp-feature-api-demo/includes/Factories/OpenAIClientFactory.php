<?php

namespace A8C\WpFeatureApiDemo\Factories;

use OpenAI;
use OpenAI\Client;
use A8C\WpFeatureApiDemo\Options;
use WP_Error;
class OpenAIClientFactory {
	public static function create(): Client|WP_Error {
		$api_key = Options::get_api_key();
		if (empty($api_key)) {
			return new WP_Error(
				'missing_api_key',
				__('OpenAI API key is not configured. Please set it in the Feature API Demo settings.', 'wp-feature-api-demo'),
				['status' => 500]
			);
		}

		return OpenAI::client($api_key);
	}
}
