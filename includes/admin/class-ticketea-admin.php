<?php
/**
 * Ticketea Admin
 *
 * @author     Ticketea
 * @package    Ticketea\Includes\Admin
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Ticketea_Admin' ) ) {
	/**
	 * Admin class.
	 *
	 * @since 1.0.0
	 */
	class Ticketea_Admin {

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'includes' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Includes.
		 *
		 * @since 1.0.0
		 */
		public function includes() {
			include_once( 'class-ticketea-admin-post-types.php' );
			include_once( 'class-ticketea-admin-settings.php' );
		}

		/**
		 * Admin menu.
		 *
		 * @since 1.0.0
		 */
		public function admin_menu() {
			add_menu_page( 'Ticketea', 'Ticketea', 'manage_ticketea', 'ticketea', null, null, '55' );
		}

		/**
		 * Enqueues the admin scripts.
		 *
		 * @since 1.0.0
		 */
		public function enqueue_scripts() {
			wp_enqueue_style( 'ticketea-admin', TICKETEA_URL . 'assets/css/admin.css' );
		}

	}
}

new Ticketea_Admin();
