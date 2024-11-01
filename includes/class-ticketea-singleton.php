<?php
/**
 * Ticketea Singleton
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Ticketea_Singleton' ) ) {
	/**
	 * The class that implements the singleton pattern.
	 *
	 * @since 1.0.0
	 */
	abstract class Ticketea_Singleton {

		/**
		 * Gets the *Singleton* instance of this class.
		 *
		 * @since 1.0.0
		 *
		 * @staticvar Ticketea_Singleton $instance The *Singleton* instances of this class.
		 * @return Ticketea_Singleton The *Singleton* instance.
		 */
		public static function get_instance() {
			static $instance = null;
			if ( null === $instance ) {
				$instance = new static();
			}

			return $instance;
		}

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 * @access protected
		 */
		protected function __construct() {}

		/**
		 * Throw error on object clone.
		 *
		 * @since 1.0.0
		 * @access private
		 */
		private function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'ticketea' ), '1.0.0' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @since 1.0.0
		 * @access private
		 */
		private function __wakeup() {
			// Unserializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'ticketea' ), '1.0.0' );
		}

	}
}
