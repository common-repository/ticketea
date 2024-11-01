<?php
/**
 * Ticketea Roles
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Ticketea_Roles' ) ) {
	/**
	 * The class for handle the roles and capabilities.
	 *
	 * @since 1.0.0
	 */
	class Ticketea_Roles {

		/**
		 * Adds the capabilities.
		 *
		 * @since 1.0.0
		 */
		public static function add_capabilities() {
			self::update_capabilities( 'add' );
		}

		/**
		 * Removes the capabilities.
		 *
		 * @since 1.0.0
		 */
		public static function remove_capabilities() {
			self::update_capabilities( 'remove' );
		}

		/**
		 * Updates the capabilities.
		 *
		 * @since 1.0.0
		 * @access private
		 *
		 * @global WP_Roles $wp_roles The WordPress Roles instance.
		 *
		 * @param string $action The action to execute. [add, remove]
		 */
		private static function update_capabilities( $action ) {
			global $wp_roles;

			$actions = array(
				'add'    => 'add_cap',
				'remove' => 'remove_cap',
			);

			if ( ! isset( $actions[ $action ] ) || ! class_exists( 'WP_Roles' ) ) {
				return;
			}

			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}

			$func = $actions[ $action ];
			$capabilities = self::get_capabilities();

			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					call_user_func( array( $wp_roles, $func ), 'administrator', $cap );
				}
			}
		}

		/**
		 * Get capabilities for ticketea.
		 *
		 * @since 1.0.0
		 * @access private
		 *
		 * @return array The capabilities.
		 */
		private static function get_capabilities() {
			$capabilities = array();

			$capabilities['core'] = array(
				'manage_ticketea',
			);

			$capability_types = array( 'ticketea_event_list' );

			foreach ( $capability_types as $capability_type ) {

				$capabilities[ $capability_type ] = array(
					// Post type.
					"edit_{$capability_type}",
					"read_{$capability_type}",
					"delete_{$capability_type}",
					"edit_{$capability_type}s",
					"edit_others_{$capability_type}s",
					"publish_{$capability_type}s",
					"read_private_{$capability_type}s",
					"delete_{$capability_type}s",
					"delete_private_{$capability_type}s",
					"delete_published_{$capability_type}s",
					"delete_others_{$capability_type}s",
					"edit_private_{$capability_type}s",
					"edit_published_{$capability_type}s",
				);
			}

			return $capabilities;
		}

	}
}
