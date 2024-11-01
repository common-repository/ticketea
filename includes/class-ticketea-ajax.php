<?php
/**
 * Ticketea AJAX
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Ticketea_Ajax' ) ) {
	/**
	 * AJAX Event Handler.
	 *
	 * @since 1.0.0
	 */
	class Ticketea_Ajax {

		/**
		 * Init.
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			// Action => nopriv
			$ajax_actions = array(
				'test_filters' => false,
			);

			foreach ( $ajax_actions as $ajax_action => $nopriv ) {
				add_action( 'wp_ajax_ticketea_' . $ajax_action, array( __CLASS__, $ajax_action ) );

				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_ticketea_' . $ajax_action, array( __CLASS__, $ajax_action ) );
				}
			}
		}

		/**
		 * Test filters action.
		 *
		 * @since 1.0.0
		 */
		public static function test_filters() {
			if ( ! empty( $_POST ) && isset( $_POST['filters'] ) ) {
				$filters = array_filter( $_POST['filters'] );

				if ( empty( $filters ) ) {

					echo '<p>' . __( 'You must choose at least one filter.', 'ticketea' ) . '</p>';

				} else {

					// Prevent timeout.
					@set_time_limit( 0 );

					$feed = new Ticketea_Feed();
					$events_nb = count( $feed->get_events( $filters ) );
					$string = _n( 'The filters will produce a list with %s event.', 'The filters will produce a list with %s events.', $events_nb, 'ticketea' );

					printf( '<p>%1$s</p>', sprintf( $string, '<strong>' . $events_nb . '</strong>' ) );

				}
			}

			echo '<p><a class="close-modal button button-secondary button-large" href="#">' . __( 'Close', 'ticketea' ) . '</a></p>';

			die();
		}

	}
}

Ticketea_Ajax::init();
