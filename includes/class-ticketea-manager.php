<?php
/**
 * Ticketea Manager
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Ticketea_Manager' ) ) {
	/**
	 * Class for handle calls to the Ticketea Feed.
	 *
	 * @since 1.0.0
	 */
	class Ticketea_Manager {

		/**
		 * The API instance.
		 *
		 * @since 1.0.0
		 * @access private
		 * @var mixed
		 */
		private $api_instance;


		/**
		 * Gets the API instance.
		 *
		 * @since 1.0.0
		 *
		 * @return mixed The API instance.
		 */
		public function get_api() {
			// On demand.
			if ( ! $this->api_instance ) {
				$this->api_instance = new Ticketea_Feed();
			}

			return $this->api_instance;
		}

		/**
		 * Makes a call to the Ticketea API, or return an existing transient.
		 *
		 * @since 1.0.0
		 *
		 * @param string  $endpoint The request endpoint.
		 * @param array   $params   Optional. The request's parameters.
		 * @param boolean $force    Optional. Force a fresh request, ignoring any existing transient.
		 * @return array {
		 *     @type mixed   $result The request's result.
		 *     @type boolean $cache  True if the results comes from the cached version. False otherwise.
		 * }
		 */
		public function request( $endpoint, $params = array(), $force = false ) {
			// Return the cached version.
			if ( ! $force ) {
				$cached = $this->get_cache( $endpoint, $params );
				if ( $cached ) {
					return array( 'result' => $cached, 'cache' => true );
				}
			}

			// Make a fresh request and cache it.
			$ticketea_api = $this->get_api();
			$response = call_user_func( array( $ticketea_api, $endpoint ), $params );

			// Prevent timeout.
			@set_time_limit( 0 );

			/**
			 * Fired after retrieve a response from the Ticketea API.
			 *
			 * @since 1.0.0
			 *
			 * @param mixed  $response The response.
			 * @param string $endpoint The request endpoint.
			 * @param array  $params The request's parameters.
			 */
			do_action( 'ticketea_api_response', $response, $endpoint, $params );

			$result = $this->set_cache( $endpoint, $params, $response );

			return array( 'result' => $result, 'cache' => false );
		}

		/**
		 * Gets the transient for a certain endpoint and combination of parameters.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @see get_transient()
		 *
		 * @param string $endpoint Endpoint being called.
		 * @param array  $params   Parameters to be passed during the call.
		 *
		 * @return mixed Transient's value, false if not found.
		 */
		protected function get_cache( $endpoint, $params ) {
			$value = get_transient( $this->get_transient_name( $endpoint, $params ) );

			/**
			 * Filters the cached value from a Ticketea API request.
			 *
			 * @since 1.0.0
			 *
			 * @param mixed  $value    The cached value.
			 * @param string $endpoint The request endpoint.
			 * @param array  $params   The request's parameters.
			 */
			return apply_filters( 'ticketea_get_cache', $value, $endpoint, $params );
		}

		/**
		 * Sets the transient for a certain endpoint and combination of parameters.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @see set_transient()
		 *
		 * @param string $endpoint Endpoint being called.
		 * @param array  $params   Parameters to be passed during the call.
		 * @param mixed  $value    Transient value.
		 *
		 * @return mixed Transient's value.
		 */
		protected function set_cache( $endpoint, $params, $value ) {
			/**
			 * Filters the value to be cached for a Ticketea API request.
			 *
			 * @since 1.0.0
			 *
			 * @param mixed  $value    The value to be cached.
			 * @param string $endpoint The request endpoint.
			 * @param array  $params   The request's parameters.
			 */
			$cache = apply_filters( 'ticketea_set_cache', $value, $endpoint, $params );

			$transient_name = $this->get_transient_name( $endpoint, $params );
			$half_an_hour_in_seconds = 1800;

			/**
			 * Sets the cache expiration time.
			 *
			 * @since 1.0.0
			 */
			$cache_expiration_time = apply_filters( 'ticketea_cache_expiration_time', $half_an_hour_in_seconds );
			set_transient( $transient_name, $cache, $cache_expiration_time );

			$this->register_transient( $transient_name );

			return $cache;
		}

		/**
		 * Deletes all transients.
		 *
		 * @since 1.0.0
		 */
		public function clear_cache() {
			$transients = get_option( 'ticketea_transients', array() );
			if ( $transients ) {
				foreach ( $transients as $transient ) {
					delete_transient( $transient );
				}
			}

			delete_option( 'ticketea_transients' );
		}

		/**
		 * Determines a transient's name based on endpoint and parameters.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @param string $endpoint Endpoint being called.
		 * @param array  $params   Parameters to be passed during the call.
		 * @return string The transient's name.
		 */
		protected function get_transient_name( $endpoint, $params ) {
			// Maximum should be 45 characters for transients with expiration.
			return substr( 'ticketea_' . md5( $endpoint . ( json_encode( $params ) ) ), 0, 45 );
		}

		/**
		 * Adds a transient name to the list of registered transients.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @param string $transient_name The transient name.
		 */
		protected function register_transient( $transient_name ) {
			// Get any existing list of transients.
			$transients = get_option( 'ticketea_transients', array() );

			// Add the new transient if it doesn't already exist.
			if ( ! in_array( $transient_name, $transients ) ) {
				$transients[] = $transient_name;
			}

			// Save the updated list of transients.
			update_option( 'ticketea_transients', $transients );
		}

	}
}

// Register the Manager component.
Ticketea()->register_component( 'manager', new Ticketea_Manager() );
