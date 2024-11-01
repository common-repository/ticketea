<?php
/**
 * Ticketea Event class
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Ticketea_Event' ) ) {
	/**
	 * Represents a Ticketea event.
	 *
	 * @since 1.0.0
	 */
	class Ticketea_Event {

		/**
		 * The post ID.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var int
		 */
		public $post_id;

		/**
		 * The event ID.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var int
		 */
		public $event_id;

		/**
		 * The event name.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var string
		 */
		public $name;

		/**
		 * The event logo.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var string
		 */
		public $logo;

		/**
		 * The event description.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var string
		 */
		public $description;

		/**
		 * The next session date of the event.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var string
		 */
		public $next_session_date;

		/**
		 * The end date of the event.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var string
		 */
		public $end_date_time;

		/**
		 * The changed fields.
		 *
		 * @since 1.0.0
		 * @access private
		 * @var array
		 */
		private $changed_fields = array();


		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $post_id Optional. The post ID.
		 * @param array $args    Optional. The event's arguments.
		 */
		public function __construct( $post_id = null, array $args = array() ) {
			if ( ! $post_id && ! isset( $args['id'] ) ) {
				return false;
			}

			if ( ! $post_id ) {
				$post_id = $this->get_post_id( $args['id'] );
			}

			if ( $post_id ) {
				$event = WP_Post::get_instance( $post_id );
				if ( ! is_object( $event ) || ! is_a( $event, 'WP_Post' ) || 'ticketea_event' !== $event->post_type ) {
					return false;
				}

				$this->post_id     = intval( $post_id );
				$this->name        = $event->post_title;
				$this->description = $event->post_content;
			}

			$this->populate( $args );
		}

		/**
		 * Populates the event data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args The event's arguments.
		 */
		public function populate( $args ) {
			$meta = array();
			if ( $this->post_id ) {
				$this->event_id = get_post_meta( $this->post_id, 'ticketea_event_id', true );
				$meta = get_post_meta( $this->post_id, 'ticketea_event_data', true );
				$meta['name']              = $this->name;
				$meta['description']       = $this->description;
				$meta['next_session_date'] = get_post_meta( $this->post_id, 'ticketea_event_next_session', true );
				$meta['end_date_time']     = get_post_meta( $this->post_id, 'ticketea_event_end_date', true );
			} elseif ( isset( $args['id'] ) ) {
				$this->event_id = intval( $args['id'] );
			}

			unset( $args['id'] );

			// Use a timestamp to order by date more efficiently.
			if ( isset( $args['next_session_date'] ) ) {
				// The date comes with the UTC timezone.
				$args['next_session_date'] = strtotime( $args['next_session_date'] );
			}

			if ( isset( $args['end_date_time'] ) ) {
				$args['end_date_time'] = strtotime( $args['end_date_time'] );
			}

			$params = wp_parse_args( $args, $meta );

			foreach ( $params as $key => $value ) {
				$default = isset( $meta[ $key ] ) ? $meta[ $key ] : null;
				if ( $value !== $default ) {
					$this->changed_fields[] = $key;
				}

				// Set the field.
				$this->$key = $value;
			}
		}

		/**
		 * Gets if the field or fields have changed or not.
		 *
		 * @since 1.0.0
		 *
		 * @param string|array $key A single field name or an array.
		 * @return boolean True if at least one field has changed. False otherwise.
		 */
		public function has_changed( $key ) {
			if ( is_array( $key ) ) {
				$changed = array_intersect( $key, $this->changed_fields );

				return ! empty( $changed );
			} else {
				return in_array( $key, $this->changed_fields );
			}
		}

		/**
		 * Saves the event.
		 *
		 * @since 1.0.0
		 */
		public function save() {
			// Update the event.
			if ( $this->post_id ) {

				$updates = array();

				if ( $this->has_changed( 'name' ) ) {
					$updates['post_title'] = $this->name;
				}

				if ( $this->has_changed( 'description' ) ) {
					$updates['post_content'] = $this->description;
				}

				if ( ! empty( $updates ) ) {
					wp_update_post( array_merge( $updates, array(
						'ID' => $this->post_id,
					) ) );
				}

			// Create new event.
			} else {

				$post_id = wp_insert_post( array(
					'post_title'     => $this->name,
					'post_content'   => $this->description,
					'post_type'      => 'ticketea_event',
					'post_status'    => 'publish',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				) );

				if ( ! is_wp_error( $post_id ) && 0 < $post_id ) {
					$this->post_id = $post_id;
					add_post_meta( $this->post_id, 'ticketea_event_id', $this->event_id );
				}

			}

			// Create/update the event meta.
			if ( $this->post_id ) {
				$data = get_object_vars( $this );
				unset( $data['post_id'], $data['id'], $data['changed_fields'], $data['event_id'],
					$data['name'], $data['description'], $data['next_session_date'], $data['end_date_time']
				);

				if ( $this->has_changed( array_keys( $data ) ) ) {
					update_post_meta( $this->post_id, 'ticketea_event_data', $data );
				}

				if ( $this->has_changed( 'next_session_date' ) ) {
					update_post_meta( $this->post_id, 'ticketea_event_next_session', $this->next_session_date );
				}

				if ( $this->has_changed( 'end_date_time' ) ) {
					update_post_meta( $this->post_id, 'ticketea_event_end_date', $this->end_date_time );
				}
			}

		}

		/**
		 * Gets the post ID assigned to the event.
		 *
		 * @since 1.0.0
		 *
		 * @global wpdb $wpdb The WordPress database instance.
		 *
		 * @param int $event_id The event ID.
		 * @return int|false The post ID, or false on failure.
		 */
		protected function get_post_id( $event_id ) {
			global $wpdb;

			$query = $wpdb->prepare(
				"SELECT post_id
				 FROM {$wpdb->postmeta}
				 WHERE meta_key = %s AND
				 meta_value = %d",
				array( 'ticketea_event_id', intval( $event_id ) )
			);

			$result = $wpdb->get_var( $query );

			return ( $result ? intval( $result ) : false );
		}

	}
}
