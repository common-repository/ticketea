<?php
/**
 * Ticketea Uninstall
 *
 * @author     Ticketea
 * @package    Ticketea\Uninstaller
 * @since      1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Plugin uninstall.
 *
 * @since 1.0.0
 *
 * @global wpdb $wpdb The WordPress database instance.
 */
function ticketea_uninstall() {
	global $wpdb;

	// Capabilities.
	include_once( 'includes/class-ticketea-roles.php' );
	Ticketea_Roles::remove_capabilities();

	// Delete transients.
	$transients = get_option( 'ticketea_transients', array() );
	if ( $transients ) {
		foreach ( $transients as $transient ) {
			delete_transient( $transient );
		}
	}

	// Delete options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'ticketea_%';" );

	// Delete posts + data.
	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'ticketea_event_list', 'ticketea_event' );" );
	$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );
}
ticketea_uninstall();
