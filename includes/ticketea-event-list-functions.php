<?php
/**
 * Ticketea Event List Functions
 *
 * Functions for things related with event lists.
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Gets the IDs of the event lists.
 *
 * @since 1.0.0
 *
 * @return array The IDs of the event lists.
 */
function ticketea_get_event_list_ids() {
	$args = array(
		'fields'         => 'ids',
		'post_type'      => 'ticketea_event_list',
		'post_status'    => array( 'any', 'trash' ),
		'posts_per_page' => -1,
	);

	$wp_query = new WP_Query();

	return $wp_query->query( $args );
}

/**
 * Gets the labels for the event list filters.
 *
 * @since 1.0.0
 *
 * @return array The labels for the event list filters.
 */
function ticketea_get_filters_labels() {
	return array(
		'event_id'           => _x( 'Events IDs', 'Filter Label', 'ticketea' ),
		'category'           => _x( 'Category', 'Filter Label', 'ticketea' ),
		'typology'           => _x( 'Typology', 'Filter Label', 'ticketea' ),
		'topic'              => _x( 'Topics', 'Filter Label', 'ticketea' ),
		'organizer_id'       => _x( 'Organizers IDs', 'Filter Label', 'ticketea' ),
		'venue_id'           => _x( 'Venues IDs', 'Filter Label', 'ticketea' ),
		'venue_city'         => _x( 'Cities', 'Filter Label', 'ticketea' ),
		'venue_province'     => _x( 'Provinces', 'Filter Label','ticketea' ),
		'venue_country_code' => _x( 'Countries', 'Filter Label','ticketea' ),
	);
}

/**
 * Gets the filters for an event list.
 *
 * @since 1.0.0
 *
 * @param int $event_list_id The event list ID.
 * @return array The event list's filters.
 */
function ticketea_get_event_list_filters( $event_list_id ) {
	$meta = get_post_meta( $event_list_id, 'ticketea_event_list_filters', true );
	if ( ! is_array( $meta ) ) {
		$meta = array();
	}

	$filters = wp_parse_args( $meta, array(
		'event_id'           => '',
		'category'           => '',
		'typology'           => '',
		'topic'              => array(),
		'organizer_id'       => '',
		'venue_id'           => '',
		'venue_city'         => '',
		'venue_province'     => '',
		'venue_country_code' => array(),
	) );

	/**
	 * Filters the event list's filters.
	 *
	 * @since 1.0.0
	 *
	 * @param array $filters       The filters of the event list.
	 * @param int   $event_list_id The event list ID.
	 */
	return apply_filters( 'ticketea_event_list_filters', $filters, $event_list_id );
}

/**
 * Gets the information to display in the event list.
 *
 * @since 1.0.0
 *
 * @param int $event_list_id The event list ID.
 * @return array The event list's display-info.
 */
function ticketea_get_event_list_display_info( $event_list_id ) {
	$display_info = get_post_meta( $event_list_id, 'ticketea_event_list_display_info', true );
	if ( ! is_array( $display_info ) ) {
		return array();
	}

	/**
	 * Filters the information to display in the event list.
	 *
	 * @since 1.0.0
	 *
	 * @param array $display_info  The information to display in the event list.
	 * @param int   $event_list_id The event list ID.
	 */
	return apply_filters( 'ticketea_event_list_display_info', $display_info, $event_list_id );
}

/**
 * Gets the last synchronization of the event list.
 *
 * @since 1.0.0
 *
 * @param int $event_list_id The event list ID.
 * @return int|bool The timestamp of the last synchronization. False if it doesn't exists.
 */
function ticketea_get_event_list_last_sync( $event_list_id ) {
	$last_sync = get_post_meta( $event_list_id, '_ticketea_last_sync', true );

	return ( $last_sync ? (int) $last_sync : false );
}

/**
 * Gets if the event list is synchronizing or not.
 *
 * @since 1.0.0
 *
 * @param int $event_list_id The event list ID.
 * @return bool True if the event list is synchronizing. False otherwise.
 */
function ticketea_event_list_is_synchronizing( $event_list_id ) {
	return (bool) get_post_meta( $event_list_id, '_ticketea_synchronizing_list', true );
}

/**
 * Gets the event ids of the event list from the post meta.
 *
 * @since 1.0.0
 *
 * @param int   $event_list_id The event list ID.
 * @param mixed $default       The default value.
 * @return array The event ids.
 */
function ticketea_get_event_ids_from_meta( $event_list_id, $default = array() ) {
	$event_ids = get_post_meta( $event_list_id, '_ticketea_event_ids', true );

	return is_array( $event_ids ) ? $event_ids : $default;
}

/**
 * Gets the event ids of the event list.
 *
 * Executes a remote request if necessary.
 *
 * @since 1.0.0
 *
 * @param int     $event_list_id The event list ID.
 * @param boolean $force         Optional. Force a fresh request, ignoring any existing transient.
 * @return array The event ids.
 */
function ticketea_get_event_ids( $event_list_id, $force = false ) {
	if ( $force ) {
		/**
		 * Triggered before the fetch events request.
		 *
		 * @since 1.0.0
		 *
		 * @param int $event_list_id The event list Id.
		 */
		do_action( 'ticketea_fetch_events_before', $event_list_id );
	}

	$filters  = ticketea_get_event_list_filters( $event_list_id );
	$response = Ticketea( 'manager' )->request( 'get_events', $filters, $force );

	// Fetchs the events asynchronously.
	if ( $response['cache'] && 'async' === $response['result'] ) {
		wp_schedule_single_event( time(), 'ticketea_cron_fetch_events', array( $event_list_id ) );
	}

	// We use null as default value to distinguish the result from empty arrays.
	$event_ids = ticketea_get_event_ids_from_meta( $event_list_id, null );
	if ( is_array( $response['result'] ) && ( ! $response['cache'] || ( $response['cache'] && is_null( $event_ids ) ) ) ) {

		// Update the event Ids.
		if ( $response['result'] !== $event_ids ) {
			update_post_meta( $event_list_id, '_ticketea_event_ids', $response['result'] );
			$event_ids = $response['result'];
		}

		// Update last sync.
		if ( ! $response['cache'] || ! ticketea_get_event_list_last_sync( $event_list_id ) ) {
			update_post_meta( $event_list_id, '_ticketea_last_sync', current_time( 'timestamp' ) );
		}

		if ( ! $response['cache'] ) {
			/**
			 * Triggered after the fetch events request.
			 *
			 * @since 1.0.0
			 *
			 * @param int $event_list_id The event list Id.
			 */
			do_action( 'ticketea_fetch_events_after', $event_list_id );
		}

	}

	return is_null( $event_ids ) ? array() : $event_ids;
}
