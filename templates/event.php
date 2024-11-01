<?php
/**
 * The event template
 *
 * @author     Ticketea
 * @package    Ticketea\Templates
 * @since      1.0.0
 */

$event = ticketea_get_event( get_the_ID() );
if ( ! $event || ! isset( $display_info ) ) {
	return;
}
?>
<div class="ticketea-event">

	<?php
		if ( isset( $display_info['logo'] ) && $display_info['logo'] ) :
			the_post_thumbnail();
		endif;
	?>

	<div class="ticketea-event-content">
		<h2 class="ticketea-event-title"><a href="<?php the_permalink(); ?>" rel="nofollow"><?php the_title(); ?></a></h2>
		<p class="ticketea-event-next-session"><?php ticketea_event_next_session( $event->next_session_date, $event->timezone ); ?></p>

		<?php if ( isset( $display_info['price'] ) && $display_info['price'] ) : ?>
			<p class="ticketea-event-min-price"><?php printf( __( 'from %s', 'ticketea' ),
				'<span class="ticketea-event-price">' . ticketea_format_price( $event->min_price / 100, $event->currency ) . '</span>'
			);?></p>
		<?php endif; ?>

		<?php if ( isset( $display_info['type'] ) && $display_info['type'] ) : ?>
			<p class="ticketea-event-type"><?php echo esc_html( ticketea_event_type( $event->category, $event->typology, $event->topic ) ); ?></p>
		<?php endif; ?>

		<?php if ( isset( $display_info['venue_name'] ) && $display_info['venue_name'] ) : ?>
			<p><span class="ticketea-event-venue-name"><?php echo esc_html( $event->venue_name ); ?></span></p>
		<?php endif; ?>

		<?php if ( isset( $display_info['city'] ) && $display_info['city'] ) : ?>
			<p>
				<span class="ticketea-event-venue-city"><?php echo esc_html( $event->venue_city ); ?></span>, 
				<span class="ticketea-event-venue-province"><?php echo esc_html( $event->venue_province ); ?></span>, 
				<span class="ticketea-event-venue-country"><?php echo esc_html( ticketea_get_country_label( $event->venue_country_code ) ); ?></span>
			</p>
		<?php endif; ?>

		<?php if ( isset( $display_info['button'] ) && $display_info['button'] ) : ?>
			<p class="ticketea-buy-tickets"><a href="<?php the_permalink(); ?>" rel="nofollow"><?php _e( 'buy tickets', 'ticketea' ); ?></a></p>
		<?php endif; ?>

	</div>

</div>
