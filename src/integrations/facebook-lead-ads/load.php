<?php

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Ensure the integration class is available even if autoloaders haven't run yet.
if ( ! class_exists( 'Uncanny_Automator\Integrations\Facebook_Lead_Ads\Facebook_Lead_Ads_Integration' ) ) {
        require_once __DIR__ . '/class-facebook-lead-ads.php';
}

new Uncanny_Automator\Integrations\Facebook_Lead_Ads\Facebook_Lead_Ads_Integration();
