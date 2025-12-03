<?php
namespace Uncanny_Automator\Integrations\Facebook_Lead_Ads\Utilities;

use Exception;
use WP_Error;

/**
 * Class Facebook_Lead_Ads_Client
 *
 * Handles communication with the Facebook Lead Ads API.
 *
 * @package Uncanny_Automator\Integrations\Facebook_Lead_Ads\Utilities
 */
class Client {

	/**
	 * Facebook Graph base URL.
	 *
	 * @var string
	 */
	protected $graph_base_url = 'https://graph.facebook.com/';

	/**
	 * Manages Facebook credentials.
	 *
	 * @var Credentials_Manager
	 */
	protected $credentials_manager = null;

	/**
	 * Constructor.
	 *
	 * Initializes the endpoint URL and credentials manager.
	 */
	public function __construct() {
		$this->credentials_manager = new Credentials_Manager();
	}

	/**
	 * Retrieves page access tokens for the user.
	 *
	 * @return array|WP_Error Array of tokens on success or WP_Error on failure.
	 * @throws Exception If an error occurs during the request.
	 */
public function get_page_access_tokens() {

$access_token = $this->credentials_manager->get_user_access_token();

if ( empty( $access_token ) ) {
return new WP_Error( 'missing_token', __( 'The user access token is missing.', 'uncanny-automator' ) );
}

return $this->get_pages( $access_token );
}

/**
 * Retrieves pages for a given access token.
 *
 * @param string $access_token Access token to query with.
 *
 * @return array|WP_Error
 */
public function get_pages( $access_token ) {
return $this->graph_get(
'/me/accounts',
array(
'fields'       => 'id,name,access_token',
'access_token' => $access_token,
)
);
}

	/**
	 * Retrieves forms associated with a Facebook page.
	 *
	 * @param int    $page_id           ID of the Facebook page.
	 * @param string $page_access_token Access token for the page.
	 *
	 * @return array Array of forms on success or WP_Error on failure.
	 */
	public function get_forms( int $page_id, string $page_access_token ) {

		try {
			return $this->graph_get(
				'/' . absint( $page_id ) . '/leadgen_forms',
				array(
					'fields'       => 'id,name',
					'access_token' => $page_access_token,
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'Error: get_forms method exception', $e->getMessage() );
		}
	}

	/**
	 * Retrieves form fields.
	 *
	 * @param int    $page_id           ID of the Facebook page.
	 * @param int    $form_id           ID of the form selected.
	 * @param string $page_access_token Access token for the page.
	 *
	 * @return array|WP_Error Array of forms on success or WP_Error on failure.
	 */
	public function get_form_fields( int $page_id, int $form_id, string $page_access_token ) {

		try {
			return $this->graph_get(
				'/' . absint( $form_id ),
				array(
					'fields'       => 'questions,leads_count,locale',
					'access_token' => $page_access_token,
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'Error: get_form_fields method exception', $e->getMessage() );
		}
	}

	/**
	 * Retrieves the single lead data.
	 *
	 * @param int $lead_id
	 *
	 * @return array
	 */
	public function get_lead( int $page_id, int $lead_id, string $page_access_token ) {

		try {
			$response = $this->graph_get(
				'/' . absint( $lead_id ),
				array(
					'fields'       => 'created_time,field_data,ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,form_id,page_id,platform,custom_disclaimer_responses',
					'access_token' => $page_access_token,
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'Error: get_lead method exception', $e->getMessage() );
		}

		return (array) $response ?? array();
	}

	/**
	 * Exchanges an OAuth code for a short-lived token.
	 *
	 * @param string $code         The code returned by Facebook.
	 * @param string $redirect_uri The redirect URI used during authorization.
	 *
	 * @return array|WP_Error
	 */
	public function exchange_code_for_token( $code, $redirect_uri ) {
		return $this->graph_get(
			'/oauth/access_token',
			array(
				'client_id'     => Oauth::get_app_id(),
				'client_secret' => Oauth::get_app_secret(),
				'redirect_uri'  => $redirect_uri,
				'code'          => $code,
			)
		);
	}

	/**
	 * Exchanges a short-lived token for a long-lived token.
	 *
	 * @param string $short_lived_token The token to exchange.
	 *
	 * @return array|WP_Error
	 */
	public function exchange_long_lived_token( $short_lived_token ) {
		return $this->graph_get(
			'/oauth/access_token',
			array(
				'grant_type'        => 'fb_exchange_token',
				'client_id'         => Oauth::get_app_id(),
				'client_secret'     => Oauth::get_app_secret(),
				'fb_exchange_token' => $short_lived_token,
			)
		);
	}

	/**
	 * Retrieves the connected Facebook user profile.
	 *
	 * @param string $access_token The token to query with.
	 *
	 * @return array|WP_Error
	 */
	public function get_user_profile( $access_token ) {
		return $this->graph_get(
			'/me',
			array(
				'fields'       => 'id,name',
				'access_token' => $access_token,
			)
		);
	}

	/**
	 * Attempts to subscribe the app to leadgen webhooks for a page.
	 *
	 * @param int    $page_id           Page identifier.
	 * @param string $page_access_token Page token.
	 *
	 * @return array|WP_Error
	 */
	public function subscribe_page_to_leads( $page_id, $page_access_token ) {
		return $this->graph_post(
			'/' . absint( $page_id ) . '/subscribed_apps',
			array(
				'subscribed_fields' => 'leadgen',
				'access_token'     => $page_access_token,
			)
		);
	}

	/**
	 * Verifies that a page token is valid by requesting the subscribed apps.
	 *
	 * @param int    $page_id           Page identifier.
	 * @param string $page_access_token Page token.
	 *
	 * @return string|WP_Error
	 */
	public function verify_page( $page_id, $page_access_token ) {
		$subscriptions = $this->graph_get(
			'/' . absint( $page_id ) . '/subscribed_apps',
			array(
				'fields'       => 'subscribed_fields',
				'access_token' => $page_access_token,
			)
		);

		if ( is_wp_error( $subscriptions ) ) {
			return $subscriptions;
		}

		$has_leadgen = false;

		foreach ( (array) ( $subscriptions['data'] ?? array() ) as $subscription ) {
			if ( isset( $subscription['subscribed_fields'] ) && in_array( 'leadgen', (array) $subscription['subscribed_fields'], true ) ) {
				$has_leadgen = true;
				break;
			}
		}

		if ( ! $has_leadgen ) {
			return new WP_Error( 'missing_subscription', __( 'The Facebook page is not subscribed to leadgen events.', 'uncanny-automator' ) );
		}

		return __( 'Ready', 'uncanny-automator' );
	}

	/**
	 * Makes a GET request to the Facebook Graph API.
	 *
	 * @param string $path   API path.
	 * @param array  $params Query parameters.
	 *
	 * @return array|WP_Error
	 * @throws Exception When the request fails.
	 */
	protected function graph_get( $path, array $params = array() ) {
		return $this->graph_request( $path, $params, 'GET' );
	}

	/**
	 * Makes a POST request to the Facebook Graph API.
	 *
	 * @param string $path   API path.
	 * @param array  $params Body parameters.
	 *
	 * @return array|WP_Error
	 * @throws Exception When the request fails.
	 */
	protected function graph_post( $path, array $params = array() ) {
		return $this->graph_request( $path, $params, 'POST' );
	}

	/**
	 * Sends a request to the Facebook Graph API.
	 *
	 * @param string $path   API path.
	 * @param array  $params Request params.
	 * @param string $method HTTP method.
	 *
	 * @return array|WP_Error
	 * @throws Exception When HTTP fails.
	 */
	protected function graph_request( $path, array $params, $method = 'GET' ) {
		$url = trailingslashit( $this->graph_base_url ) . Oauth::get_api_version() . $path;

		$args = array(
			'timeout' => 30,
		);

		if ( 'GET' === strtoupper( $method ) ) {
			$url = add_query_arg( $params, $url );
		} else {
			$args['body'] = $params;
		}

		$args['method'] = strtoupper( $method );

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 ) {
			$message = $body['error']['message'] ?? __( 'Unexpected error contacting Facebook.', 'uncanny-automator' );
			return new WP_Error( 'facebook_http_error', $message );
		}

		return (array) $body;
	}
}
