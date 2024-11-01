<?php
/**
 * Functions that have dependencies with the plugin components
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Gets if it is necessary to load the default styles or not.
 *
 * @since 1.0.0
 *
 * @return boolean True if we have to load the default styles. False otherwise.
 */
function ticketea_load_default_styles() {
	return Ticketea( 'settings' )->get_setting( 'default_styles' );
}

/**
 * Gets if we have to redirect the user to the event's page in Ticketea.com or not.
 *
 * @since 1.0.0
 *
 * @return boolean True if we have to redirect the user to the event's page in Ticketea.com. False otherwise.
 */
function ticketea_redirect_to_event() {
	$buy_tickets_in = Ticketea( 'settings' )->get_setting( 'buy_tickets_in' );

	return ( 'ticketea' === $buy_tickets_in );
}

/**
 * Gets parameters for the affiliation program.
 *
 * @since 1.0.0
 *
 * @return false|array The parameters for the affiliation program. False otherwise.
 */
function ticketea_get_affiliation_params() {
	$a_aid = Ticketea( 'settings' )->get_setting( 'a_aid' );
	$a_bid = Ticketea( 'settings' )->get_setting( 'a_bid' );

	if ( ! $a_aid || ! $a_bid ) {
		return false;
	}

	return compact( 'a_aid', 'a_bid' );
}

/**
 * Maybe adds the parameters for the affiliation program to the url.
 *
 * @since 1.0.0
 *
 * @return string The url with the affiliation parameters if necessary.
 */
function ticketea_maybe_add_affiliation_params( $url ) {
	$affiliation_params = ticketea_get_affiliation_params();
	if ( $affiliation_params ) {
		$url = add_query_arg( array(
			'a_aid' => urlencode( $affiliation_params['a_aid'] ),
			'a_bid' => urlencode( $affiliation_params['a_bid'] ),
		), $url );
	}

	return $url;
}
