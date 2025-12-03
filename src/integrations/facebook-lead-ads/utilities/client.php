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
         * Facebook Graph API base URL.
         */
        const GRAPH_API_BASE = 'https://graph.facebook.com/v19.0/';

        /**
         * Manages Facebook credentials.
         *
         * @var Credentials_Manager
         */
        protected $credentials_manager = null;

        /**
         * Constructor.
         *
         * Initializes the credentials manager.
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

                $tokens = $this->credentials_manager->get_pages_credentials();

                if ( empty( $tokens ) ) {
                        return new WP_Error( 'page_access_token_exception', esc_html__( 'No page access tokens are stored.', 'uncanny-automator' ) );
                }

                return array(
                        'data' => array(
                                'data' => $tokens,
                        ),
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

                $endpoint = $this->build_graph_url( sprintf( '%d/leadgen_forms', $page_id ), array(
                        'access_token' => $page_access_token,
                ) );

                try {
                        $response = $this->request( $endpoint );
                } catch ( Exception $e ) {
                        return new WP_Error( 'Error: get_forms method exception', $e->getMessage() );
                }

                return $response;
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

                $endpoint = $this->build_graph_url( $form_id, array(
                        'fields'        => 'questions',
                        'access_token'  => $page_access_token,
                ) );

                try {
                        $response = $this->request( $endpoint );
                } catch ( Exception $e ) {
                        return new WP_Error( 'Error: get_form_fields method exception', $e->getMessage() );
                }

                return $response;
        }

	/**
	 * Retrieves the single lead data.
	 *
	 * @param int $lead_id
	 *
	 * @return array
	 */
	public function get_lead( int $page_id, int $lead_id, string $page_access_token ) {

                $endpoint = $this->build_graph_url( $lead_id, array(
                        'fields'       => 'created_time,field_data,form_id,leadgen_id,page_id',
                        'access_token' => $page_access_token,
                ) );

                try {
                        $response = $this->request( $endpoint );
                } catch ( Exception $e ) {
                        return new WP_Error( 'Error: get_lead method exception', $e->getMessage() );
                }

                return (array) $response;
        }

        /**
         * Validates that the provided tokens can access the Graph API.
         *
         * @return WP_Error|array
         */
        public function verify_tokens() {

                $token = $this->credentials_manager->get_user_access_token();

                if ( empty( $token ) ) {
                        return new WP_Error( 'missing_user_token', esc_html__( 'User access token is missing.', 'uncanny-automator' ) );
                }

                $endpoint = $this->build_graph_url( 'me', array(
                        'access_token' => $token,
                        'fields'       => 'id,name',
                ) );

                try {
                        return $this->request( $endpoint );
                } catch ( Exception $e ) {
                        return new WP_Error( 'token_verification_failed', $e->getMessage() );
                }
        }

        /**
         * Validates a page access token.
         *
         * @param int|string $page_id Page ID.
         * @param string     $page_access_token Page access token.
         *
         * @return WP_Error|array
         */
        public function verify_page_token( $page_id, $page_access_token ) {

                $endpoint = $this->build_graph_url( (string) $page_id, array(
                        'fields'       => 'id,name',
                        'access_token' => $page_access_token,
                ) );

                try {
                        return $this->request( $endpoint );
                } catch ( Exception $e ) {
                        return new WP_Error( 'page_token_verification_failed', $e->getMessage() );
                }
        }

        /**
         * Build a Graph API URL with query parameters.
         *
         * @param string $path   Graph path.
         * @param array  $params Query parameters.
         *
         * @return string
         */
        protected function build_graph_url( $path, array $params = array() ) {
                $path  = ltrim( $path, '/' );
                $url   = trailingslashit( self::GRAPH_API_BASE ) . $path;
                $query = http_build_query( $params );

                return empty( $query ) ? $url : $url . '?' . $query;
        }

        /**
         * Execute a request to the Facebook Graph API.
         *
         * @param string $endpoint Endpoint URL.
         * @param array  $args     Optional request args.
         *
         * @return array
         * @throws Exception When the request fails.
         */
        protected function request( $endpoint, array $args = array() ) {
                $defaults = array(
                        'timeout' => 30,
                );

                $args     = wp_parse_args( $args, $defaults );
                $response = wp_remote_get( $endpoint, $args );

                if ( is_wp_error( $response ) ) {
                        throw new Exception( $response->get_error_message() );
                }

                $code = wp_remote_retrieve_response_code( $response );
                $body = json_decode( wp_remote_retrieve_body( $response ), true );

                if ( 200 !== $code ) {
                        $message = $body['error']['message'] ?? esc_html__( 'Unexpected error from Facebook Graph API.', 'uncanny-automator' );
                        throw new Exception( $message );
                }

                return is_array( $body ) ? $body : array();
        }
}
