<?php
/**
 * Ticketea Events
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Ticketea_Events' ) ) {
	/**
	 * Class for handle the Ticketea Events.
	 *
	 * @since 1.0.0
	 */
	class Ticketea_Events {

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// Ticketea Manager hooks.
			add_filter( 'ticketea_get_cache', array( $this, 'get_cache' ), 10, 3 );
			add_filter( 'ticketea_set_cache', array( $this, 'set_cache' ), 10, 2 );

			// Event List hooks.
			add_action( 'ticketea_fetch_events_before', array( $this, 'fetch_events_before' ) );
			add_action( 'ticketea_fetch_events_after', array( $this, 'fetch_events_after' ) );
			add_action( 'delete_post', array( $this, 'event_list_delete' ) );

			// Crons.
			add_action( 'ticketea_purge_events', array( $this, 'purge_events' ) );
			add_action( 'ticketea_purged_events', array( $this, 'events_deleted' ) );
		}

		/**
		 * Filters the cached value for a Ticketea API request.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed  $value    The cached value.
		 * @param string $endpoint The request endpoint.
		 * @param array  $params   The request parameters.
		 * @return mixed The cached value.
		 */
		public function get_cache( $value, $endpoint, $params ) {
			if ( 'get_events' === $endpoint && ! is_array( $value ) ) {
				/*
				 * Tells the parent function that it is necessary execute an
				 * asynchronous request.
				 */
				$value = 'async';
			}

			return $value;
		}

		/**
		 * Filters the value to be cached for a Ticketea API request.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed  $value    The value to be cached.
		 * @param string $endpoint The request endpoint.
		 * @return mixed The value to be cached.
		 */
		public function set_cache( $value, $endpoint ) {
			if ( 'get_events' === $endpoint ) {
				$event_ids = $this->sync_events( $value );

				// Save only the ID of the events.
				$value = $event_ids;
			}

			return $value;
		}

		/**
		 * Synchronizes the events data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $events An array with events.
		 * @return array The event IDs.
		 */
		public function sync_events( $events ) {
			$event_ids = array();

			foreach ( $events as $event ) {
				$ticketea_event = ticketea_get_event( null, $event );
				if ( $ticketea_event && ( $ticketea_event->post_id || $ticketea_event->next_session_date > time() ) ) {
					$ticketea_event->save();

					$event_ids[] = $ticketea_event->event_id;
				}
			}

			return $event_ids;
		}

		/**
		 * Process the fetch-events before.
		 *
		 * @since 1.0.0
		 *
		 * @param int $event_list_id The event list ID.
		 */
		public function fetch_events_before( $event_list_id ) {
			// Add 'Synchronizing' status to the list.
			add_post_meta( $event_list_id, '_ticketea_synchronizing_list', true, true );
		}

		/**
		 * Process the fetch-events after.
		 *
		 * @since 1.0.0
		 *
		 * @param int $event_list_id The event list ID.
		 */
		public function fetch_events_after( $event_list_id ) {
			// Remove 'Synchronizing' status to the list.
			delete_post_meta( $event_list_id, '_ticketea_synchronizing_list' );
		}

		/**
		 * Purges the events before an event list object is deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param int $post_id The post ID.
		 */
		public function event_list_delete( $post_id ) {
			if ( $post_id > 0 && 'ticketea_event_list' === get_post_type( $post_id ) ) {
				$this->purge_events();
			}
		}

		/**
		 * Purges the events.
		 *
		 * @since 1.0.0
		 */
		public function purge_events() {
			$events_in_use = ticketea_get_event_ids_in_use();

			/**
			 * Filters the arguments of the query used to purge the events.
			 *
			 * @since 1.0.0
			 */
			$args = apply_filters( 'ticketea_purge_events_args', array(
				'fields'         => 'ids',
				'post_type'      => 'ticketea_event',
				'post_status'    => array( 'any', 'trash' ),
				'posts_per_page' => -1,
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => 'ticketea_event_end_date',
						'value'   => strtotime( 'today -1 week' ),
						'type'    => 'numeric',
						'compare' => '<',
					),
					array(
						'key'     => 'ticketea_event_id',
						'value'   => $events_in_use,
						'compare' => 'NOT IN',
					),
				),
			) );

			$wp_query = new WP_Query();
			$post_ids = $wp_query->query( $args );

			// Prevent timeout.
			@set_time_limit( 0 );

			$event_ids = array();
			foreach ( $post_ids as $post_id ) {
				$event = ticketea_get_event( $post_id );
				if ( $event && $event->event_id ) {
					$event_ids[] = $event->event_id;
				}

				wp_delete_post( $post_id );
			}

			if ( ! empty( $event_ids ) ) {
				/**
				 * Fired after purge some events.
				 *
				 * @since 1.0.0
				 *
				 * @param array $event_ids The event IDs.
				 */
				do_action( 'ticketea_purged_events', $event_ids );
			}
		}

		/**
		 * Process the deleted events.
		 *
		 * @since 1.0.0
		 *
		 * @param array $event_ids The event ids.
		 */
		public function events_deleted( array $event_ids ) {
			$event_list_ids = ticketea_get_event_list_ids();

			// Updates the event ids meta data of the event lists.
			foreach ( $event_list_ids as $event_list_id ) {
				$event_ids_meta = ticketea_get_event_ids_from_meta( $event_list_id, null );
				if ( $event_ids_meta ) {
					$diff = array_diff( $event_ids_meta, $event_ids );
					if ( empty( $diff ) ) {
						delete_post_meta( $event_list_id, '_ticketea_event_ids' );
					} else {
						update_post_meta( $event_list_id, '_ticketea_event_ids', $diff );
					}
				}
			}
		}

	}
}

new Ticketea_Events();
