<?php

namespace A8C\WpFeatureApiDemo\Schemas;

use WP_Feature_Schema_Adapter;

class Schema {
	private static string $dir = WP_FEATURE_API_DEMO_PATH . 'includes/Schemas/json-ld';

	public static function get( string $name ): array {

		$file = self::$dir . '/' . $name . '.php';
		if (!file_exists($file)) {
			return [];
		}

		$schema = require $file;
		$adapter = new WP_Feature_Schema_Adapter($schema);
		return $adapter->transform($schema);
	}
}
