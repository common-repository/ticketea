<?php
/**
 * Ticketea Post Types
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Ticketea_Post_Types' ) ) {
	/**
	 * The class for handle the post types.
	 *
	 * @since 1.0.0
	 */
	class Ticketea_Post_Types {

		/**
		 * Init.
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			// Register post types.
			self::register_event_list();
			self::register_event();
		}

		/**
		 * Register the ticketea_event_list post type.
		 *
		 * @since 1.0.0
		 */
		public static function register_event_list() {
			$labels = array(
				'name'                => _x( 'Event Lists', 'Post Type General Name', 'ticketea' ),
				'singular_name'       => _x( 'Event List', 'Post Type Singular Name', 'ticketea' ),
				'menu_name'           => 'Ticketea',
				'parent_item_colon'   => __( 'Parent List:', 'ticketea' ),
				'all_items'           => __( 'Event Lists', 'ticketea' ),
				'view_item'           => __( 'View List', 'ticketea' ),
				'add_new_item'        => __( 'Add New Event List', 'ticketea' ),
				'add_new'             => __( 'Add Event List', 'ticketea' ),
				'edit_item'           => __( 'Edit List', 'ticketea' ),
				'update_item'         => __( 'Update List', 'ticketea' ),
				'search_items'        => __( 'Search Lists', 'ticketea' ),
				'not_found'           => __( 'Not found', 'ticketea' ),
				'not_found_in_trash'  => __( 'Not found in Trash', 'ticketea' ),
			);

			$args = array(
				'label'               => __( 'Ticketea event list', 'ticketea' ),
				'description'         => __( 'Ticketea event lists', 'ticketea' ),
				'labels'              => $labels,
				'supports'            => array( 'title', 'editor', 'thumbnail' ),
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => current_user_can( 'manage_ticketea' ) ? 'ticketea' : false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'menu_position'       => 35,
				'capability_type'     => 'ticketea_event_list',
				'map_meta_cap'        => true,
				'can_export'          => true,
				'has_archive'         => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => true,
				'rewrite'             => array(
					'slug'       => _x( 'events', 'Post Type Rewrite Slug', 'ticketea' ),
					'with_front' => false,
				),
			);

			register_post_type( 'ticketea_event_list', $args );
		}

		/**
		 * Register the ticketea_event post type.
		 *
		 * @since 1.0.0
		 */
		public static function register_event() {
			// This function doesn't exists on the plugin activation.
			$redirect_to_event = function_exists( 'ticketea_redirect_to_event' ) ? ticketea_redirect_to_event() : true;

			$labels = array(
				'name'                => _x( 'Events', 'Post Type General Name', 'ticketea' ),
				'singular_name'       => _x( 'Event', 'Post Type Singular Name', 'ticketea' ),
				'menu_name'           => __( 'Events', 'ticketea' ),
				'parent_item_colon'   => __( 'Parent Event:', 'ticketea' ),
				'all_items'           => __( 'All Events', 'ticketea' ),
				'view_item'           => __( 'View Event', 'ticketea' ),
				'add_new_item'        => __( 'Add New Event', 'ticketea' ),
				'add_new'             => __( 'Add New', 'ticketea' ),
				'edit_item'           => __( 'Edit Event', 'ticketea' ),
				'update_item'         => __( 'Update Event', 'ticketea' ),
				'search_items'        => __( 'Search Events', 'ticketea' ),
				'not_found'           => __( 'Not found', 'ticketea' ),
				'not_found_in_trash'  => __( 'Not found in Trash', 'ticketea' ),
			);

			$args = array(
				'label'               => __( 'Ticketea event', 'ticketea' ),
				'description'         => __( 'Ticketea events', 'ticketea' ),
				'labels'              => $labels,
				'supports'            => array( 'title', 'editor' ),
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'menu_position'       => 30,
				'capability_type'     => 'ticketea_event',
				'map_meta_cap'        => true,
				'can_export'          => true,
				'has_archive'         => false,
				'exclude_from_search' => false,
				'publicly_queryable'  => ! $redirect_to_event,
				'rewrite'             => array(
					'slug'       => _x( 'event', 'Post Type Rewrite Slug', 'ticketea' ),
					'with_front' => false,
				),
			);

			register_post_type( 'ticketea_event', $args );
		}

	}
}
