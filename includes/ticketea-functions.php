<?php
/**
 * Miscelaneous plugin-wide functions
 *
 * This file should contain only standalone functions.
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Array map recursive.
 *
 * @since 1.0.0
 *
 * @see array_map
 *
 * @param callable $callback The function to run for each element.
 * @param array    $array    The array.
 * @return array The processed array.
 */
function ticketea_array_map_recursive( $callback, $array ) {
	foreach ( $array as $key => $value ) {
		if ( is_array( $array[ $key ] ) ) {
			$array[ $key ] = ticketea_array_map_recursive( $callback, $value );
		} else {
			$array[ $key ] = call_user_func( $callback, $value );
		}
	}

	return $array;
}

/**
 * Gets the value of the url parameter.
 *
 * @since 1.0.0
 *
 * @param string $param   The url parameter.
 * @param mixed  $default The default value.
 * @return mixed The parameter value.
 */
function ticketea_get_url_param( $param, $default = '' ) {
	$value = $default;
	$key = sanitize_key( $param );

	if ( ! empty( $_POST ) && isset( $_POST['_wp_http_referer'] ) ) {
		$query_string = parse_url( $_POST['_wp_http_referer'], PHP_URL_QUERY );
		if ( $query_string ) {
			$query_args = array();
			parse_str( $query_string, $query_args );
			if ( isset( $query_args[ $key ] ) ) {
				$value = $query_args[ $key ];
			}
		}
	} elseif ( isset( $_GET[ $key ] ) ) {
		$value = $_GET[ $key ];
	}

	return urldecode( $value );
}

/**
 * Gets the DateTime object for a date, optionally forced into the given timezone.
 *
 * @since 1.0.0
 *
 * @param string|int          $date      The date to localize.
 * @param string|DateTimeZone $timezone  Optional. The timezone.
 * @return DateTime The DataTime object.
 */
function ticketea_get_datetime( $date, $timezone = null ) {
	$string = ( ( is_numeric( $date ) && (int) $date == $date ) ? date( 'Y-m-d H:i:s', $date ) : $date );

	$datetime = date_create( $string, new DateTimeZone( 'UTC' ) );

	if ( $timezone ) {
		if ( is_string( $timezone ) ) {
			$timezone = new DateTimeZone( $timezone );
		}

		if ( $timezone instanceof DateTimeZone ) {
			$datetime->setTimezone( $timezone );
		}
	}

	return $datetime;
}

/**
 * Merge user defined arguments into defaults array.
 *
 * The defaults should be considered to be all of the arguments which are
 * supported by the caller and given as a list. The returned arguments will
 * only contain the arguments in the $defaults list.
 *
 * If the $args list has unsupported arguments, then they will be ignored and
 * removed from the final returned list.
 *
 * @since 1.0.0
 *
 * @see wp_parse_args()
 *
 * @param array $args     Value to merge with $defaults
 * @param array $defaults Array that serves as the defaults.
 * @return array Merged user defined values with defaults.
 */
function ticketea_parse_supported_args( $args, array $defaults = array() ) {
	$result = wp_parse_args( $args, $defaults );

	return array_intersect_key( $result, $defaults );
}

/**
 * Checks the request type.
 *
 * @since 1.0.0
 *
 * @param string $type The type to check. [admin, ajax, cron, frontend]
 * @return bool True if the request type is the same as the $type value. False otherwise.
 */
function ticketea_is_request( $type ) {
	$is_request = false;

	switch ( $type ) {
		case 'admin':
			$is_request = is_admin();
			break;
		case 'ajax':
			$is_request = defined( 'DOING_AJAX' );
			break;
		case 'cron':
			$is_request = defined( 'DOING_CRON' );
			break;
		case 'frontend':
			$is_request = ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			break;
	}

	return $is_request;
}

/**
 * Loads a template.
 *
 * @since 1.0.0
 *
 * @param string $template_name The template name.
 * @param array  $args          Optional. The template arguments.
 */
function ticketea_load_template( $template_name, $args = array() ) {
	$template = locate_template( 'ticketea/' . $template_name . '.php' );
	if ( ! $template ) {
		$template = TICKETEA_PATH . 'templates/' . $template_name . '.php';
	}

	if ( file_exists( $template ) ) {
		if ( $args && is_array( $args ) ) {
			extract( $args );
		}

		include( $template );
	}
}

/**
 * Gets the base argument for the paginate_links function.
 *
 * Adds compatibility with WordPress 3.8.
 *
 * @since 1.0.0
 *
 * @see paginate_links()
 *
 * @return string The paginate_links base.
 */
function ticketea_get_paginate_links_base() {
	$pagenum_link = html_entity_decode( get_pagenum_link() );
	$url_parts    = explode( '?', $pagenum_link );

	// Append the format placeholder to the base URL.
	$pagenum_link = trailingslashit( $url_parts[0] ) . '%_%';

	return $pagenum_link;
}

/**
 * Gets the format argument for the paginate_links function.
 *
 * Adds compatibility with WordPress 3.8.
 *
 * @since 1.0.0
 *
 * @see paginate_links()
 *
 * @return string The paginate_links format.
 */
function ticketea_get_paginate_links_format() {
	global $wp_rewrite;

	$pagenum_link = ticketea_get_paginate_links_base();

	// URL base depends on permalink settings.
	$format  = $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
	$format .= $wp_rewrite->using_permalinks() ? user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' ) : '?paged=%#%';

	return $format;
}

/**
 * Gets the events per page.
 *
 * @since 1.0.0
 *
 * @return int The events per page.
 */
function ticketea_get_events_per_page() {
	return get_option( 'posts_per_page', 20 );
}

/**
 * Gets the currency symbol.
 *
 * @since 1.0.0
 *
 * @param string $currency The currency name.
 * @return string The currency symbol.
 */
function ticketea_get_currency_symbol( $currency ) {
	switch ( $currency ) {
		case 'AED' :
			$currency_symbol = 'د.إ';
			break;
		case 'AUD' :
		case 'CAD' :
		case 'CLP' :
		case 'COP' :
		case 'HKD' :
		case 'MXN' :
		case 'NZD' :
		case 'SGD' :
		case 'USD' :
			$currency_symbol = '&#36;';
			break;
		case 'BDT':
			$currency_symbol = '&#2547;&nbsp;';
			break;
		case 'BGN' :
			$currency_symbol = '&#1083;&#1074;.';
			break;
		case 'BRL' :
			$currency_symbol = '&#82;&#36;';
			break;
		case 'CHF' :
			$currency_symbol = '&#67;&#72;&#70;';
			break;
		case 'CNY' :
		case 'JPY' :
		case 'RMB' :
			$currency_symbol = '&yen;';
			break;
		case 'CZK' :
			$currency_symbol = '&#75;&#269;';
			break;
		case 'DKK' :
			$currency_symbol = 'kr.';
			break;
		case 'DOP' :
			$currency_symbol = 'RD&#36;';
			break;
		case 'EGP' :
			$currency_symbol = 'EGP';
			break;
		case 'EUR' :
			$currency_symbol = '&euro;';
			break;
		case 'GBP' :
			$currency_symbol = '&pound;';
			break;
		case 'HRK' :
			$currency_symbol = 'Kn';
			break;
		case 'HUF' :
			$currency_symbol = '&#70;&#116;';
			break;
		case 'IDR' :
			$currency_symbol = 'Rp';
			break;
		case 'ILS' :
			$currency_symbol = '&#8362;';
			break;
		case 'INR' :
			$currency_symbol = 'Rs.';
			break;
		case 'ISK' :
			$currency_symbol = 'Kr.';
			break;
		case 'KIP' :
			$currency_symbol = '&#8365;';
			break;
		case 'KRW' :
			$currency_symbol = '&#8361;';
			break;
		case 'MYR' :
			$currency_symbol = '&#82;&#77;';
			break;
		case 'NGN' :
			$currency_symbol = '&#8358;';
			break;
		case 'NOK' :
			$currency_symbol = '&#107;&#114;';
			break;
		case 'NPR' :
			$currency_symbol = 'Rs.';
			break;
		case 'PHP' :
			$currency_symbol = '&#8369;';
			break;
		case 'PLN' :
			$currency_symbol = '&#122;&#322;';
			break;
		case 'PYG' :
			$currency_symbol = '&#8370;';
			break;
		case 'RON' :
			$currency_symbol = 'lei';
			break;
		case 'RUB' :
			$currency_symbol = '&#1088;&#1091;&#1073;.';
			break;
		case 'SEK' :
			$currency_symbol = '&#107;&#114;';
			break;
		case 'THB' :
			$currency_symbol = '&#3647;';
			break;
		case 'TRY' :
			$currency_symbol = '&#8378;';
			break;
		case 'TWD' :
			$currency_symbol = '&#78;&#84;&#36;';
			break;
		case 'UAH' :
			$currency_symbol = '&#8372;';
			break;
		case 'VND' :
			$currency_symbol = '&#8363;';
			break;
		case 'ZAR' :
			$currency_symbol = '&#82;';
			break;
		default :
			$currency_symbol = '';
			break;
	}

	/**
	 * Filters the currency symbol.
	 *
	 * @since 1.0.0
	 *
	 * @param string $currency_symbol The currency symbol.
	 * @param string $currency        The currency name.
	 */
	$symbol = apply_filters( 'ticketea_currency_symbol', $currency_symbol, $currency );

	return $symbol;
}

/**
 * Formats a price.
 *
 * @since 1.0.0
 *
 * @param float  $price    The price to format.
 * @param string $currency The currency.
 * @return string The formatted price.
 */
function ticketea_format_price( $price, $currency = '' ) {
	$symbol = ticketea_get_currency_symbol( $currency );
	$price_format = _x( '%1$s%2$s', 'Price format: %1$s for the symbol and %2$s for the price', 'ticketea' );

	return sprintf( $price_format , $symbol, number_format_i18n( $price, 2 ) );
}

/**
 * Formats a datetime.
 *
 * @since 1.0.0
 *
 * @param int  $timestamp The timestamp to format.
 * @param bool $gmt       Optional. Whether to use GMT timezone. Default false.
 * @return string The formatted datetime.
 */
function ticketea_format_datetime( $timestamp, $gmt = false ) {
	$datetime_format = _x( '%1$s at %2$s', 'Datetime format: %1$s for the date and %2$s for the time', 'ticketea' );
	$date_format     = _x( 'Y/m/d', 'PHP Date format', 'ticketea' );
	$time_format     = _x( 'H:i', 'PHP Time format', 'ticketea' );

	return sprintf( $datetime_format, date_i18n( $date_format, $timestamp, $gmt ), date_i18n( $time_format, $timestamp, $gmt ) );
}

/**
 * Gets all countries.
 *
 * @since 1.0.0
 *
 * @return array All the countries.
 */
function ticketea_get_countries() {
	/**
	 * Filters the countries.
	 *
	 * @since 1.0.0
	 */
	return apply_filters( 'ticketea_countries', include( TICKETEA_PATH . 'i18n/ticketea-countries.php' ) );
}

/**
 * Gets the country's label by its code.
 *
 * @since 1.0.0
 *
 * @param string $code The country's code.
 * @return string The country's label.
 */
function ticketea_get_country_label( $code ) {
	$countries = ticketea_get_countries();

	return ( isset( $countries[ $code ] ) ? $countries[ $code ] : '' );
}
