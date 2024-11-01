<?php
/**
 * Ticketea Settings
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Ticketea_Settings' ) ) {
	/**
	 * The class for handle the plugin settings.
	 *
	 * @since 1.0.0
	 */
	class Ticketea_Settings {

		/**
		 * The option name of the settings.
		 *
		 * @since  1.0.0
		 * @access private
		 * @var string
		 */
		private $option_name = 'ticketea_settings';

		/**
		 * The plugin settings.
		 *
		 * @since  1.0.0
		 * @access private
		 * @var array
		 */
		private $settings;


		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'added_option', array( $this, 'settings_updated' ), 10, 2 );
			add_action( 'updated_option', array( $this, 'settings_updated' ), 10, 3 );
		}

		/**
		 * Gets the option name of the settings.
		 *
		 * @since 1.0.0
		 *
		 * @return string The option name.
		 */
		public function get_option_name() {
			return $this->option_name;
		}

		/**
		 * Gets the default settings.
		 *
		 * @since 1.0.0
		 *
		 * @return array The default settings.
		 */
		public function get_default_settings() {
			return array(
				// General.
				'buy_tickets_in' => 'ticketea',
				'default_styles' => true,
				'a_aid'          => '',
				'a_bid'          => '',
			);
		}

		/**
		 * Gets the plugin settings.
		 *
		 * @since 1.0.0
		 *
		 * @return array The plugin settings.
		 */
		public function get_settings() {
			if ( ! $this->settings ) {
				$this->settings = array_merge( $this->get_default_settings(), get_option( $this->option_name, array() ) );
			}

			return $this->settings;
		}

		/**
		 * Gets the setting's value.
		 *
		 * @since 1.0.0
		 *
		 * @param string $setting The setting name.
		 * @param mixed  $default The default value.
		 * @return mixed The setting value. Null if the setting does not exists.
		 */
		public function get_setting( $setting, $default = null ) {
			$settings = $this->get_settings();

			return ( isset( $settings[ $setting ] ) ? $settings[ $setting ] : $default );
		}

		/**
		 * Sets the settings.
		 *
		 * @since 1.0.0
		 *
		 * @param array $settings The new settings values.
		 * @return boolean True if the settings were updated. False otherwise.
		 */
		public function set_settings( $settings ) {
			$new_settings = array_merge( $this->get_settings(), $settings );

			return update_option( $this->option_name, $new_settings );
		}

		/**
		 * Clears the plugin settings.
		 *
		 * @since 1.0.0
		 */
		public function clear() {
			delete_option( $this->option_name );
		}

		/**
		 * Fires after an option has been successfully added or updated.
		 *
		 * We use this method to update the $this->settings property with the new value.
		 *
		 * @since 1.0.0
		 *
		 * @param string $option       Name of the updated option.
		 * @param array  $old_settings The old settings values.
		 * @param array  $new_settings Optional. The new settings values. Only on updated_option hook.
		 */
		public function settings_updated( $option, $old_settings, $new_settings = null ) {
			if ( $this->option_name === $option ) {
				$this->settings = ( 'updated_option' === current_filter() ? $new_settings : $old_settings );

				if ( ! is_array( $new_settings ) || $new_settings['buy_tickets_in'] !== $old_settings['buy_tickets_in'] ) {
					// The event's rewrite rules have changed.
					flush_rewrite_rules();
				}
			}
		}

	}
}

// Register the Settings component.
Ticketea()->register_component( 'settings', new Ticketea_Settings() );
