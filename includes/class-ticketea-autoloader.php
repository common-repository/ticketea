<?php
/**
 * Plugin Class Autoloader
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Ticketea_Autoloader' ) ) {
	/**
	 * Loads the classes on demand.
	 *
	 * @since 1.0.0
	 */
	class Ticketea_Autoloader {

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			if ( function_exists( '__autoload' ) ) {
				spl_autoload_register( '__autoload' );
			}

			spl_autoload_register( array( $this, 'autoload' ) );
		}

		/**
		 * Auto-load classes on demand to reduce memory consumption.
		 *
		 * @since 1.0.0
		 *
		 * @param string $class The class to load.
		 */
		public function autoload( $class ) {
			$classname = strtolower( $class );
			$file = 'class-' . str_replace( '_', '-', $classname ) . '.php';

			$autoload = array(
				'ticketea_admin' => TICKETEA_PATH . 'includes/admin/',
				'ticketea'       => TICKETEA_PATH . 'includes/',
			);

			foreach ( $autoload as $prefix => $path ) {
				if ( 0 === strpos( $classname, $prefix ) ) {
					$file_path = $path . $file;
					if ( is_readable( $file_path ) ) {
						include_once( $file_path );
						return;
					}
				}
			}
		}

	}
}

new Ticketea_Autoloader();
