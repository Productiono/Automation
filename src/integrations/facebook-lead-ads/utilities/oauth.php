<?php
namespace Uncanny_Automator\Integrations\Facebook_Lead_Ads\Utilities;

/**
 * Handles OAuth configuration for Facebook Lead Ads.
 */
class Oauth {

	/**
	 * Facebook app ID.
	 */
	const APP_ID = '1882808745980291';

	/**
	 * Facebook app secret.
	 */
	const APP_SECRET = 'ac2bd4d540356e689fa6838dcf61a896';

	/**
	 * Graph API version to use for requests.
	 */
	const API_VERSION = 'v18.0';

	/**
	 * Returns the configured app ID.
	 *
	 * @return string
	 */
	public static function get_app_id() {
		return apply_filters( 'automator_fbla_app_id', self::APP_ID );
	}

	/**
	 * Returns the configured app secret.
	 *
	 * @return string
	 */
	public static function get_app_secret() {
		return apply_filters( 'automator_fbla_app_secret', self::APP_SECRET );
	}

	/**
	 * Returns the API version.
	 *
	 * @return string
	 */
	public static function get_api_version() {
		return apply_filters( 'automator_fbla_api_version', self::API_VERSION );
	}
}
