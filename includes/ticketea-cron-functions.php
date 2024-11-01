<?php
/**
 * Ticketea Cron Functions
 *
 * Functions listening the cron requests.
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Fetchs the events for a list.
 *
 * @since 1.0.0
 *
 * @param int $event_list_id The event list ID.
 */
function ticketea_cron_fetch_events( $event_list_id ) {
	ticketea_get_event_ids( $event_list_id, true );
}
add_action( 'ticketea_cron_fetch_events', 'ticketea_cron_fetch_events', 10, 2 );
