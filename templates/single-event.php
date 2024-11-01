<?php
/**
 * The single event template
 *
 * @author     Ticketea
 * @package    Ticketea\Templates
 * @since      1.0.0
 */

$event = ticketea_get_event( get_the_ID() );
if ( ! $event ) {
	return;
}
?>
<div class="ticketea-wrapper ticketea-wrapper-single">

	<div class="ticketea-event">

		<?php the_post_thumbnail(); ?>

		<div class="ticketea-event-content">
			<p class="ticketea-event-next-session"><?php ticketea_event_next_session( $event->next_session_date, $event->timezone ); ?></p>
			<p class="ticketea-event-type"><?php echo esc_html( ticketea_event_type( $event->category, $event->typology, $event->topic ) ); ?></p>
			<p>
				<span class="ticketea-event-venue-name"><?php echo esc_html( $event->venue_name ); ?></span>
				<span class="ticketea-event-venue-address"><?php echo esc_html( $event->venue_address ); ?></span>
			</p>
			<p>
				<span class="ticketea-event-venue-city"><?php echo esc_html( $event->venue_city ); ?></span>, 
				<span class="ticketea-event-venue-province"><?php echo esc_html( $event->venue_province ); ?></span>, 
				<span class="ticketea-event-venue-country"><?php echo esc_html( ticketea_get_country_label( $event->venue_country_code ) ); ?></span>
			</p>
		</div>
	</div>

	<iframe class="ticketea-event-checkout" width="100%" scrolling="no" frameborder="0" src="<?php echo ticketea_get_event_iframe_url( $event->url ); ?>" target="_top"></iframe>

</div>
