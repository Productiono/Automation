<?php

namespace Uncanny_Automator\Integrations\Facebook_Lead_Ads\Utilities;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Uncanny_Automator\Integrations\Facebook_Lead_Ads\Helpers\Facebook_Lead_Ads_Helpers;
use Uncanny_Automator\Integrations\Facebook_Lead_Ads\Utilities\Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Rest_Api.
 *
 * Handles REST API endpoints for the Facebook Lead Ads integration.
 *
 * @package Uncanny_Automator\Integrations\Facebook_Lead_Ads\Utilities
 */
class Rest_Api {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	const REST_NAMESPACE = 'automator/v1';

	/**
	 * Listener REST route.
	 *
	 * @var string
	 */
	const LISTENER_REST_ROUTE = '/integration/facebook-lead-ads';

	/**
	 * OAuth redirect REST route.
	 *
	 * @var string
	 */
	const OAUTH_REST_ROUTE = '/integration/facebook-lead-ads/oauth';

	/**
	 * Verification REST route.
	 *
	 * @var string
	 */
	const VERIFICATION_REST_ROUTE = '/integration/facebook-lead-ads/verification';

	/**
	 * Returns the listener endpoint URL.
	 *
	 * @return string Listener endpoint URL.
	 */
	public static function get_listener_endpoint_url() {
		return rest_url( self::REST_NAMESPACE . self::LISTENER_REST_ROUTE );
	}

	/**
	 * Returns the OAuth redirect URL.
	 *
	 * @return string OAuth redirect URL.
	 */
	public static function get_oauth_redirect_url() {
		return rest_url( self::REST_NAMESPACE . self::OAUTH_REST_ROUTE );
	}

	/**
	 * Registers the REST API endpoints.
	 *
	 * @return void
	 */
	public function register_endpoint() {

		// Listener endpoint.
		register_rest_route(
			self::REST_NAMESPACE,
			self::LISTENER_REST_ROUTE,
			array(
				'methods'             => array( WP_REST_Server::CREATABLE ),
				'callback'            => array( $this, 'handle_request' ),
				'permission_callback' => '__return_true', // Public route.
			)
		);

		// Verification endpoint.
		register_rest_route(
			self::REST_NAMESPACE,
			self::VERIFICATION_REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE, // Equivalent to POST.
				'callback'            => array( $this, 'verification_handle_request' ),
				'permission_callback' => '__return_true', // Public route.
			)
		);

		// OAuth redirect endpoint.
		register_rest_route(
			self::REST_NAMESPACE,
			self::OAUTH_REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'oauth_redirect_handler' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handles the verification request.
	 *
	 * Processes the verification request from the REST API.
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response The REST API response.
	 */
	public function verification_handle_request( WP_REST_Request $request ) {

		return new WP_REST_Response( array( 'received' => time() ), 200 );
	}

	/**
	 * Handles the listener request.
	 *
	 * Processes incoming POST or GET requests for the listener endpoint.
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response The REST API response.
	 */
	public function handle_request( WP_REST_Request $request ) {

		$data = $request->get_params();

		if ( empty( $data ) ) {
			return rest_ensure_response(
				array(
					'code'    => 'rest_invalid_data',
					'message' => esc_html_x( 'No data provided.', 'Facebook Lead Ads', 'uncanny-automator' ),
				)
			)->set_status( 400 );
		}

		$args = array(
			'data'    => $data,
			'request' => $request,
		);

		/**
		 * Fires after the Facebook Lead Ads REST API request is processed.
		 *
		 * @param array $args {
		 *     Arguments for the action.
		 *
		 *     @type array          $data    Data received in the request.
		 *     @type WP_REST_Request $request The REST API request object.
		 * }
		 */
		do_action( 'automator_facebook_lead_ads_rest_api_handle_request_after', $args );

		return rest_ensure_response(
			array(
				'code'    => 'rest_success',
				'message' => esc_html_x( 'Data processed successfully.', 'Facebook Lead Ads', 'uncanny-automator' ),
				'data'    => $data,
			)
		)->set_status( 200 );
	}

	/**
	 * Returns the arguments for the REST API endpoint.
	 *
	 * Defines the structure and validation rules for the endpoint arguments.
	 *
	 * @return array Endpoint argument definitions.
	 */
	private function get_endpoint_args() {
		return array(
			'data' => array(
				'required'          => true,
				'type'              => 'object',
				'validate_callback' => function ( $param ) {
					return is_array( $param );
				},
			),
		);
	}

	/**
	 * Handles the OAuth redirect from Facebook.
	 *
	 * Exchanges the code for an access token, stores credentials, and redirects back
	 * to the settings screen.
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 *
	 * @return WP_REST_Response|void
	 */
	public function oauth_redirect_handler( WP_REST_Request $request ) {

		$state = $request->get_param( 'state' );

		if ( ! wp_verify_nonce( $state, Facebook_Lead_Ads_Helpers::CONNECTION_NONCE ) ) {
			return $this->redirect_with_message( __( 'Invalid authorization state received.', 'uncanny-automator' ) );
		}

		$code = $request->get_param( 'code' );

		if ( empty( $code ) ) {
			return $this->redirect_with_message( __( 'Authorization code missing from Facebook response.', 'uncanny-automator' ) );
		}

		$client = new Client();

		$token_response = $client->exchange_code_for_token( $code, self::get_oauth_redirect_url() );

		if ( is_wp_error( $token_response ) || empty( $token_response['access_token'] ) ) {
			$message = is_wp_error( $token_response ) ? $token_response->get_error_message() : __( 'Unable to retrieve access token from Facebook.', 'uncanny-automator' );
			return $this->redirect_with_message( $message );
		}

		$long_lived_response = $client->exchange_long_lived_token( $token_response['access_token'] );
		$user_access_token   = $long_lived_response['access_token'] ?? $token_response['access_token'];

		$user_profile = $client->get_user_profile( $user_access_token );

		if ( is_wp_error( $user_profile ) ) {
			return $this->redirect_with_message( $user_profile->get_error_message() );
		}

		$pages = $client->get_pages( $user_access_token );

		if ( is_wp_error( $pages ) ) {
			return $this->redirect_with_message( $pages->get_error_message() );
		}

		$pages_data = (array) $pages['data'] ?? array();

		foreach ( $pages_data as $page ) {
			if ( empty( $page['id'] ) || empty( $page['access_token'] ) ) {
				continue;
			}

			$client->subscribe_page_to_leads( $page['id'], $page['access_token'] );
		}

		$connections_manager = Facebook_Lead_Ads_Helpers::create_connection_manager();

		try {
			$connections_manager->connect(
				array(
					'user_access_token'  => $user_access_token,
					'user'               => $user_profile,
					'pages_access_tokens' => $pages_data,
				)
			);
		} catch ( \InvalidArgumentException $exception ) {
			return $this->redirect_with_message( $exception->getMessage() );
		}

		return $this->redirect_with_message();
	}

	/**
	 * Redirects back to the settings screen with an optional error message.
	 *
	 * @param string $message Optional message to display.
	 *
	 * @return void
	 */
	private function redirect_with_message( $message = '' ) {
		$url = Facebook_Lead_Ads_Helpers::get_settings_page_url();

		if ( ! empty( $message ) ) {
			$url = add_query_arg( 'error_message', rawurlencode( $message ), $url );
		}

		wp_safe_redirect( $url );
		exit;
	}
}
