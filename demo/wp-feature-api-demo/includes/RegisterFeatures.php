<?php

namespace A8C\WpFeatureApiDemo;

use WP_Feature;
use A8C\WpFeatureApiDemo\Features\WooProductsFeatures;
require_once __DIR__ . '/Features/WooProductsFeatures.php';

class RegisterFeatures {
	public function init() {
		new WooProductsFeatures();
	}
}
