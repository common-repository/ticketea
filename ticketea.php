<?php
/**
 * Plugin Name: Ticketea
 * Plugin URI: https://www.ticketea.com/
 * Description: Displays the events of Ticketea on your website.
 * Author: Ticketea
 * Author URI: https://www.ticketea.com/
 * Version: 1.0.0
 * Requires at least: 3.8
 * Tested up to: 4.2.2
 *
 * Text Domain: ticketea
 * Domain Path: /i18n/languages/
 *
 * Copyright: © 2015 Ticketea.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author     Ticketea
 * @package    Ticketea
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Components handler.
if ( ! class_exists( 'Ticketea_Components' ) ) {
	require_once( 'includes/class-ticketea-components.php' );
}

if ( ! class_exists( 'Ticketea' ) ) {
	/**
	 * The main class.
	 *
	 * @since 1.0.0
	 */
	final class Ticketea extends Ticketea_Components {

		/**
		 * The plugin version.
		 *
		 * @since  1.0.0
		 * @access public
		 * @var string
		 */
		public $version = '1.0.0';


		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 * @access protected
		 */
		protected function __construct() {
			parent::__construct();

			$this->define_constants();
			$this->includes();

			// Activate.
			register_activation_hook( __FILE__, array( $this, 'activate' ) );
			add_action( 'wpmu_new_blog', array( $this, 'network_activate' ) );
			add_action( 'activate_blog', array( $this, 'network_activate' ) );

			// Deactivate.
			register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

			add_action( 'plugins_loaded', array( $this, 'load_plugin' ) );
			add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ) );
			add_action( 'init', array( $this, 'init' ), 0 );
		}

		/**
		 * Gets the plugin basename.
		 *
		 * @since 1.0.0
		 *
		 * @return string The plugin basename.
		 */
		public function get_basename() {
			return plugin_basename( __FILE__ );
		}

		/**
		 * Gets the plugin slug.
		 *
		 * @since 1.0.0
		 *
		 * @return string The plugin slug.
		 */
		public function get_slug() {
			return dirname( $this->get_basename() );
		}

		/**
		 * Define the constants if they are not defined previously.
		 *
		 * @since 1.0.0
		 */
		public function define_constants() {
			// The plugin directory path.
			if ( ! defined( 'TICKETEA_PATH' ) ) {
				define( 'TICKETEA_PATH', plugin_dir_path( __FILE__ ) );
			}

			// The plugin URL path.
			if ( ! defined( 'TICKETEA_URL' ) ) {
				define( 'TICKETEA_URL', plugin_dir_url( __FILE__ ) );
			}
		}

		/**
		 * Includes.
		 *
		 * @since 1.0.0
		 */
		public function includes() {
			include_once( 'includes/class-ticketea-autoloader.php' );
			include_once( 'includes/ticketea-functions.php' );
			include_once( 'includes/ticketea-event-list-functions.php' );
			include_once( 'includes/ticketea-event-functions.php' );
		}

		/**
		 * Activate.
		 *
		 * @since 1.0.0
		 */
		public function activate() {
			Ticketea_Roles::add_capabilities();
			Ticketea_Post_Types::init();

			wp_schedule_event( strtotime( 'tomorrow' ), 'daily', 'ticketea_purge_events' );

			flush_rewrite_rules();
		}

		/**
		 * Network Activate.
		 *
		 * @since 1.0.0
		 *
		 * @param int $blog_id Blog ID.
		 */
		public function network_activate( $blog_id ) {
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}

			if ( is_plugin_active_for_network( $this->get_basename() ) ) {
				switch_to_blog( $blog_id );
				$this->activate();
				restore_current_blog();
			}
		}

		/**
		 * Deactivate.
		 *
		 * @since 1.0.0
		 */
		public function deactivate() {
			$this->get_component( 'manager' )->clear_cache();
			wp_clear_scheduled_hook( 'ticketea_purge_events' );

			flush_rewrite_rules();
		}

		/**
		 * Load plugin.
		 *
		 * @since 1.0.0
		 */
		public function load_plugin() {
			// Set up localisation.
			load_plugin_textdomain( 'ticketea', false, $this->get_slug() . '/i18n/languages' );

			// Load class instances.
			include_once( 'includes/class-ticketea-settings.php' );
			include_once( 'includes/class-ticketea-manager.php' );
			include_once( 'includes/ticketea-component-functions.php' );
			include_once( 'includes/class-ticketea-events.php' );

			if ( ticketea_is_request( 'cron' ) ) {
				include_once( 'includes/ticketea-cron-functions.php' );
			}

			if ( ticketea_is_request( 'admin' ) ) {
				include_once( 'includes/admin/class-ticketea-admin.php' );
			}

			if ( ticketea_is_request( 'ajax' ) ) {
				include_once( 'includes/class-ticketea-ajax.php' );
			}
		}

		/**
		 * After theme setup.
		 *
		 * @since 1.0.0
		 */
		public function after_setup_theme() {
			if ( ticketea_is_request( 'frontend' ) ) {
				include_once( 'includes/ticketea-template-functions.php' );
				include_once( 'includes/class-ticketea-frontend.php' );
			}
		}

		/**
		 * Init.
		 *
		 * @since 1.0.0
		 */
		public function init() {
			// Init post types.
			Ticketea_Post_Types::init();
		}

	}

	/**
	 * The main function for returning the plugin instance and avoiding
	 * the need to declare the global variable.
	 *
	 * @since 1.0.0
	 *
	 * @param string $component_id Optional. The component ID.
	 * @return Mixed The Ticketea instance or the specified component.
	 * Null if it's not found.
	 */
	function Ticketea( $component_id = '' ) {
		$ticketea = Ticketea::get_instance();

		if ( $component_id ) {
			return $ticketea->get_component( $component_id );
		}

		return $ticketea;
	}

	Ticketea();
}
