<?php
/**
 * The event list template
 *
 * @author     Ticketea
 * @package    Ticketea\Templates
 * @since      1.0.0
 */

if ( ! isset( $event_list_id ) ) :
	$event_list_id = get_the_ID();
endif;

$events = ticketea_events_loop( $event_list_id );
$display_info = ticketea_get_event_list_display_info( $event_list_id );

if ( $events && $events->have_posts() ) : ?>

	<div class="ticketea-wrapper">

		<div class="ticketea-events">

			<?php
				while ( $events->have_posts() ) : $events->the_post();
					ticketea_load_template( 'event', array( 'display_info' => $display_info ) );
				endwhile;
			?>

		</div>

		<?php ticketea_events_nav( $event_list_id, $events ); ?>

	</div>

<?php else: ?>

	<?php ticketea_load_template( 'no-events', array( 'event_list_id' => $event_list_id ) ); ?>

<?php endif;

wp_reset_postdata();
