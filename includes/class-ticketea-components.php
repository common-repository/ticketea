<?php
/**
 * Ticketea Components
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Singleton pattern.
if ( ! class_exists( 'Ticketea_Singleton' ) ) {
	require_once( 'class-ticketea-singleton.php' );
}

if ( ! class_exists( 'Ticketea_Components' ) ) {
	/**
	 * The class for handle the plugin components.
	 *
	 * @since 1.0.0
	 */
	abstract class Ticketea_Components extends Ticketea_Singleton {

		/**
		 * The registered components.
		 *
		 * @since 1.0.0
		 * @access protected
		 * @var array The registered components.
		 */
		protected $components = array();


		/**
		 * Gets the IDs of the registered components.
		 *
		 * @since 1.0.0
		 *
		 * @return array The components IDs.
		 */
		public function get_component_ids() {
			return array_keys( $this->components );
		}

		/**
		 * Gets if the component is registered or not.
		 *
		 * @since 1.0.0
		 *
		 * @param string $component_id The component ID.
		 * @return bool True if the component is registered. False otherwise.
		 */
		public function has_component( $component_id ) {
			return ( array_key_exists( $component_id, $this->components ) );
		}

		/**
		 * Gets the specified component or null if not exists.
		 *
		 * @since 1.0.0
		 *
		 * @param string $component_id The component id.
		 * @return mixed|null          The component instance or null if not exists.
		 */
		public function get_component( $component_id ) {
			return ( $this->has_component( $component_id ) ? $this->components[ $component_id ] : null );
		}

		/**
		 * Registers a component.
		 *
		 * @since 1.0.0
		 *
		 * @param string $component_id The component ID.
		 * @param mixed  $instance	   The component instance.
		 * @return mixed|bool The instance of the registered component. False on failure.
		 */
		public function register_component( $component_id, $instance ) {
			$component = false;
			if ( ! $this->has_component( $component_id ) ) {
				$this->components[ $component_id ] = $instance;
				$component = $instance;
			}

			return $component;
		}

		/**
		 * Unregisters a component.
		 *
		 * @since 1.0.0
		 *
		 * @param string $component_id The component ID.
		 * @return bool True if the component has been unregistered. False otherwise.
		 */
		public function unregister_component( $component_id ) {
			$unregistered = false;
			if ( $this->has_component( $component_id ) ) {
				unset( $this->components[ $component_id ] );
				$unregistered = true;
			}

			return $unregistered;
		}

	}
}
