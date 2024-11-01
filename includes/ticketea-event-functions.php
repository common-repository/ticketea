<?php
/**
 * Ticketea Event Functions
 *
 * Functions for things related with events.
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Gets a Ticketea event object.
 *
 * @since 1.0.0
 *
 * @param int   $post_id Optional. The post ID.
 * @param array $args    Optional. The event's arguments.
 * @return Ticketea_Event|false The Ticketea event object. False on failure.
 */
function ticketea_get_event( $post_id = null, array $args = array() ) {
	$event = new Ticketea_Event( $post_id, $args );
	if ( ! $event->post_id && ! $event->event_id ) {
		return false;
	}

	return $event;
}

/**
 * Gets the event ids in use.
 *
 * @since 1.0.0
 *
 * @global wpdb $wpdb The WordPress database instance.
 *
 * @return array The event ids in use.
 */
function ticketea_get_event_ids_in_use() {
	global $wpdb;

	$event_ids = array();

	$query = $wpdb->prepare(
		"SELECT postmeta.meta_value
		 FROM {$wpdb->postmeta} postmeta
		 LEFT JOIN {$wpdb->posts} posts ON posts.ID = postmeta.post_id
		 WHERE posts.post_status = %s AND postmeta.meta_key = %s",
		array( 'publish', '_ticketea_event_ids' )
	);

	$results = $wpdb->get_results( $query );

	if ( $results ) {
		foreach ( $results as $row ) {
			$event_ids_meta = maybe_unserialize( $row->meta_value );
			$event_ids = array_merge( $event_ids, $event_ids_meta );
		}

		$event_ids = array_unique( $event_ids );
	}

	return $event_ids;
}

/**
 * Gets the event categories.
 *
 * @since 1.0.0
 *
 * @return array The event categories.
 */
function ticketea_get_event_categories() {
	/**
	 * Filters the event categories.
	 *
	 * @since 1.0.0
	 */
	return apply_filters( 'ticketea_event_categories', include( TICKETEA_PATH . 'i18n/ticketea-event-categories.php' ) );
}

/**
 * Gets the event categories formatted for the select2 library.
 *
 * @since 1.0.0
 *
 * @return array The event categories.
 */
function ticketea_get_event_categories_for_select2() {
	$formatted_categories = array();
	$categories = ticketea_get_event_categories();
	foreach ( $categories as $category_id => $category ) {
		$typologies = array();
		foreach( $category['typologies'] as $id => $label ) {
			$typologies[] = array( 'id' => $id, 'text' => $label );
		}

		$topics = array();
		foreach( $category['topics'] as $id => $label ) {
			$topics[] = array( 'id' => $id, 'text' => $label );
		}

		$formatted_categories[ $category_id ] = array(
			'name'       => $category['name'],
			'typologies' => $typologies,
			'topics'     => $topics,
		);
	}

	return $formatted_categories;
}

/**
 * Gets the event category data by ID.
 *
 * @since 1.0.0
 *
 * @param int $category_id The category ID.
 * @return The category data.
 */
function ticketea_get_event_category( $category_id ) {
	$categories = ticketea_get_event_categories();

	return ( isset( $categories[ $category_id ] ) ) ? $categories[ $category_id ] : false;
}
