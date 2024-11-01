<?php
/**
 * Ticketea Feed
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Ticketea_Feed' ) ) {
	/**
	 * The class for retrieve the events from the Ticketea Feed.
	 *
	 * @since 1.0.0
	 */
	class Ticketea_Feed {

		/**
		 * The feed endpoint.
		 *
		 * @since 1.0.0
		 */
		const FEED_ENDPOINT = 'https://www.ticketea.com/feeds/';


		/**
		 * Gets the events.
		 *
		 * @since 1.0.0
		 *
		 * @param array $filters Optional. The events's filters.
		 * @return array The events.
		 */
		public function get_events( $filters = array() ) {
			$params = $this->parse_filters( $filters );

			$url = $this->build_url( 'events/last_events.json', $params );
			$response = $this->request( $url );

			return ( is_array( $response ) ? $response : array() );
		}

		/**
		 * Sanitizes a parameter.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $param The parameter.
		 * @return string The sanitized parameter.
		 */
		public function sanitize_param( $param ) {
			return urlencode( strtolower( $param ) );
		}

		/**
		 * Build an url.
		 *
		 * @since 1.0.0
		 *
		 * @param string $route  The route for the url.
		 * @param array  $params Optional. The GET parameters.
		 * @return string The url built.
		 */
		protected function build_url( $route, $params = array() ) {
			$url = self::FEED_ENDPOINT . $route;

			if ( ! empty( $params ) ) {
				$sanitized_params = ticketea_array_map_recursive( array( $this, 'sanitize_param' ), $params );
				$query = preg_replace( '/\[[0-9]+\]/', '[]', urldecode( build_query( $sanitized_params ) ) );
				$url = sprintf( $url . '?%s', $query );
			}

			return $url;
		}

		/**
		 * Executes a remote request.
		 *
		 * @since 1.0.0
		 *
		 * @param strint $url    The request url.
		 * @param string $method Optional. The request method. Default GET.
		 * @param array  $params Optional. The POST parameters.
		 * @return mixed The response or False on failure.
		 */
		protected function request( $url, $method = 'GET', $params = array() ) {
			$args = array(
				'timeout' => 90,
			);

			if ( 'GET' === $method ) {
				$response = wp_remote_get( $url, $args );
			} else {
				if ( ! empty( $params ) ) {
					$args['body'] = $params;
				}

				$response = wp_remote_post( $url, $args );
			}

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$status_code  = wp_remote_retrieve_response_code( $response );
			$content_type = wp_remote_retrieve_header( $response, 'content-type' );
			if ( 200 !== $status_code || false === strpos( $content_type, 'application/json' ) ) {
				return false;
			}

			return json_decode( wp_remote_retrieve_body( $response ), true );
		}

		/**
		 * Parses the filters used as params for a feed request.
		 *
		 * @since 1.0.0
		 *
		 * @param array $filters The filters to process.
		 * @return array The parsed filters.
		 */
		protected function parse_filters( $filters ) {
			// Trim.
			$parsed_filters = ticketea_array_map_recursive( 'trim', $filters );

			// Remove falsy values.
			$parsed_filters = array_filter( $filters );

			// Explode some filters.
			$explode_filters = array( 'event_id', 'topic', 'organizer_id', 'venue_id', 'venue_city', 'venue_province', 'venue_country_code' );
			foreach ( $explode_filters as $key ) {
				if ( isset( $parsed_filters[ $key ] ) ) {
					if ( is_string( $parsed_filters[ $key ] ) ) {
						$parsed_filters[ $key ] = array_map( 'trim', explode( ',', $parsed_filters[ $key ] ) );
					}
				}
			}

			return $parsed_filters;
		}

	}
}
