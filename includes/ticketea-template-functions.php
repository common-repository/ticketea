<?php
/**
 * Ticketea template functions
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'ticketea_events_loop' ) ) :
/**
 * Gets the loop with the events of the list.
 *
 * @since 1.0.0
 *
 * @param int $event_list_id The event list ID.
 * @return false|WP_Query The events loop. False on failure.
 */
function ticketea_events_loop( $event_list_id ) {
	// Check if it's an event list.
	if ( 'ticketea_event_list' !== get_post_type( $event_list_id ) ) {
		return false;
	}

	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
	$event_ids = ticketea_get_event_ids( $event_list_id );
	// An empty array returns the recent posts. So we have to force a zero results.
	if ( empty( $event_ids ) ) {
		$event_ids = array( 0 );
	}

	/**
	 * Filters the arguments of the events loop.
	 *
	 * @since 1.0.0
	 */
	$args = apply_filters( 'ticketea_events_loop_args', array(
		'post_type'      => 'ticketea_event',
		'post_status'    => 'publish',
		'posts_per_page' => ticketea_get_events_per_page(),
		'paged'          => $paged,
		'meta_key'       => 'ticketea_event_next_session',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => 'ticketea_event_id',
				'value'   => $event_ids,
				'compare' => 'IN',
			),
		),
	) );

	return new WP_Query( $args );
}
endif;

if ( ! function_exists( 'ticketea_events_nav' ) ) :
/**
 * Paging navigation on event listings.
 *
 * @since 1.0.0
 *
 * @see paginate_links()
 *
 * @param int      $event_list_id The event list ID.
 * @param WP_Query $events        The current WP_Query object requiring paging navigation.
 */
function ticketea_events_nav( $event_list_id, WP_Query $events ) {
	// Bail if we only have one page and don't need pagination.
	if ( $events->max_num_pages < 2 ) {
		return;
	}

	// Set arguments for paginate_links().
	$args = array(
		'next_text' => __( 'Next &rarr;', 'ticketea' ),
		'prev_text' => __( '&larr; Previous', 'ticketea' ),
		'total'     => $events->max_num_pages,
		'base'      => ticketea_get_paginate_links_base(),
		'format'    => ticketea_get_paginate_links_format(),
	);

	// Use the permalink of the event list in the front page and non pages.
	if ( is_front_page() || is_singular() ) {
		$args['base'] = get_permalink( $event_list_id ) . '%_%';
	}

	// If we have 5 or less pages, just show them all.
	if ( 5 >= $events->max_num_pages ) {
		$args['show_all'] = true;
	}

	?>
	<nav class="navigation paging-navigation pagination" role="navigation">
		<h1 class="screen-reader-text"><?php esc_html_e( 'Events navigation', 'ticketea' ); ?></h1>
		<div class="nav-links">
			<?php echo paginate_links( $args ); ?>
		</div><!-- .pagination -->
	</nav><!-- .navigation -->
	<?php
}
endif;

if ( ! function_exists( 'ticketea_get_event_iframe_url' ) ) :
/**
 * Gets the url of the event's iframe.
 *
 * @since 1.0.0
 *
 * @param string $event_url The event's url.
 * @return string $event_url The url of the event's iframe.
 */
function ticketea_get_event_iframe_url( $event_url ) {
	$iframe_url = ticketea_maybe_add_affiliation_params( untrailingslashit( $event_url ) . '/custom/' );

	return esc_url( $iframe_url );
}
endif;

if ( ! function_exists( 'ticketea_event_next_session' ) ) :
/**
 * Prints HTML with the next session date of the event.
 *
 * @since 1.0.0
 *
 * @param string|int       $date The event next session date.
 * @param string $timezone Optional. The event timezone.
 */
function ticketea_event_next_session( $date, $timezone = null ) {
	$datetime = ticketea_get_datetime( $date, $timezone );

	printf( '<time datetime="%1$s"><span class="ticketea-event-date">%2$s</span> - <span class="ticketea-event-hour">%3$s</span></time>',
		esc_attr( date( 'c', $date ) ),
		$datetime->format( get_option( 'date_format', 'F j, Y' ) ),
		$datetime->format( get_option( 'time_format', 'g:i a' ) )
	);
}
endif;

if ( ! function_exists( 'ticketea_event_type' ) ) :
/**
 * Prints HTML with the event type.
 *
 * The event category, typology and topic.
 *
 * @since 1.0.0
 *
 * @param int $category_id The category ID.
 * @param int $typology_id The typology ID.
 * @param int $topic_id    The topic ID.
 */
function ticketea_event_type( $category_id, $typology_id, $topic_id ) {
	$category = ticketea_get_event_category( $category_id );

	$data = array();
	if ( $category ) {
		$data['category'] = isset( $category['name'] ) ? $category['name'] : '';
		$data['typology'] = isset( $category['typologies'] ) && $category['typologies'][ $typology_id ] ? $category['typologies'][ $typology_id ] : '';
		$data['topic']    = isset( $category['topics'] ) && $category['topics'][ $topic_id ] ? $category['topics'][ $topic_id ] : '';

		$html = array();
		foreach ( $data as $key => $value ) {
			if ( $value ) {
				$html[] = sprintf( '<span class="ticketea-event-%1$s">%2$s</span>',
					esc_attr( $key ),
					esc_html( $value )
				);
			}
		}

		echo join( ' / ', $html );
	}
}
endif;
