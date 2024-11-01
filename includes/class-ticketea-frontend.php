<?php
/**
 * Ticketea Frontend
 *
 * @author     Ticketea
 * @package    Ticketea\Includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Ticketea_Frontend' ) ) {
	/**
	 * Frontend class.
	 *
	 * Contains the frontend hooks.
	 *
	 * @since 1.0.0
	 */
	class Ticketea_Frontend {

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// Enqueue scripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Event List hooks.
			add_filter( 'the_content', array( $this, 'event_list_content' ), 99 );

			// Event hooks.
			add_filter( 'the_content', array( $this, 'event_content' ), 99 );
			add_filter( 'post_type_link', array( $this, 'event_permalink' ), 10, 2 );
			add_filter( 'get_post_metadata', array( $this, 'get_event_thumbnail_id' ), 10, 3 );
			add_filter( 'post_thumbnail_html', array( $this, 'event_thumbnail' ), 10, 5 );
			add_filter( 'get_edit_post_link', array( $this, 'event_edit_link' ) );

			// Template redirect.
			add_filter( 'template_redirect', array( $this, 'template_redirect' ), 0 );

			// Shortcodes.
			add_shortcode( 'ticketea_event_list', array( $this, 'event_list_shortcode' ) );
		}

		/**
		 * Enqueues the scripts.
		 *
		 * @since 1.0.0
		 *
		 * @global WP_Post $post The current post.
		 */
		public function enqueue_scripts() {
			global $post;

			if ( ticketea_load_default_styles() ) {
				// Load the styles only if they are necessary.
				if ( is_singular( array( 'ticketea_event_list', 'ticketea_event' ) ) ||
					( $post && has_shortcode( $post->post_content, 'ticketea_event_list' ) ) ) {
					wp_enqueue_style( 'ticketea-styles', TICKETEA_URL . 'assets/css/style.css' );
				}
			}

			if ( is_singular( 'ticketea_event' ) ) {
				$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

				wp_enqueue_script( 'iframe-resizer', TICKETEA_URL . 'assets/js/libs/iframeResizer.min.js', array(), '2.8.6', true );
				wp_enqueue_script( 'ticketea-single-event', TICKETEA_URL . 'assets/js/single-event' . $suffix . '.js', array( 'jquery', 'iframe-resizer' ), '1.0.0', true );
			}
		}

		/**
		 * Filters the event list's content.
		 *
		 * @since 1.0.0
		 */
		public function event_list_content( $content ) {
			if ( is_singular( 'ticketea_event_list' ) ) {
				ob_start();
				ticketea_load_template( 'event-list' );
				$content .= ob_get_clean();
			}

			return $content;
		}

		/**
		 * Filters the event's content.
		 *
		 * @since 1.0.0
		 */
		public function event_content( $content ) {
			if ( is_singular( 'ticketea_event' ) ) {
				ob_start();
				ticketea_load_template( 'single-event' );
				$content = ob_get_clean();
			}

			return $content;
		}

		/**
		 * Filters the event's permalink.
		 *
		 * @since 1.0.0
		 *
		 * @param string  $permalink The event's permalink.
		 * @param WP_Post $post      The post object.
		 * @return string The event's permalink.
		 */
		public function event_permalink( $permalink, $post ) {
			if ( 'ticketea_event' === $post->post_type ) {
				if ( ticketea_redirect_to_event() ) {
					$event = ticketea_get_event( $post->ID );

					// Use the Event url.
					if ( $event && property_exists( $event, 'url' ) ) {
						$permalink = ticketea_maybe_add_affiliation_params( $event->url );
					}
				}
			}

			return $permalink;
		}

		/**
		 * Gets the event's thumbnail ID.
		 *
		 * @since 1.0.0
		 *
		 * @param null|array|string $value The meta value.
		 * @param int               $object_id Object ID.
		 * @param string            $meta_key  Meta key.
		 * @return string The event's thumbnail ID.
		 */
		public function get_event_thumbnail_id( $value, $object_id, $meta_key ) {
			if ( '_thumbnail_id' === $meta_key && ! is_singular( 'ticketea_event' ) && 'ticketea_event' === get_post_type( $object_id ) ) {
				// Return the Event ID.
				$value = $object_id;
			}

			return $value;
		}

		/**
		 * Filters the event's thumbnail html.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html              The event's thumbnail html.
		 * @param int    $post_id           The post ID.
		 * @param string $post_thumbnail_id The post thumbnail ID.
		 * @param string $size              The post thumbnail size.
		 * @param string $attr              Query string of attributes.
		 * @return string The event's thumbnail html.
		 */
		public function event_thumbnail( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
			$event = ticketea_get_event( $post_id );
			if ( $event ) {
				$image = ( $event->logo ? $event->logo : TICKETEA_URL . 'assets/images/no-picture.png' );
				$html = sprintf( '<img %1$s class="%2$s" src="%3$s" alt="%4$s" />',
					image_hwstring( '160', '160' ),
					esc_attr( 'attachment-' . $size . ' ticketea-event-logo' ),
					esc_url( $image ),
					esc_attr( $event->name )
				);

				// It isn't the event's single page.
				if ( ! is_single( $event->post_id ) ) {
					$class = ( is_singular() ? '' : ' class="' . esc_attr( $size ) . '"' );

					$html = sprintf( '<a%1$s href="%2$s" title="%3$s" rel="nofollow">%4$s</a>',
						$class,
						esc_url( get_permalink() ),
						esc_attr( $event->name ),
						$html
					);
				}
			}

			return $html;
		}

		/**
		 * Filters the event edit url.
		 *
		 * @since 1.0.0
		 *
		 * @param string $edit_url The event edit url.
		 * @return string
		 */
		public function event_edit_link( $edit_url ) {
			// Disable edit link in frontend.
			if ( is_singular( 'ticketea_event' ) ) {
				return '';
			}

			return $edit_url;
		}

		/**
		 * Template redirect.
		 *
		 * @since 1.0.0
		 *
		 * @global WP_Query $wp_query The wp_query instance.
		 */
		public function template_redirect() {
			global $wp_query;

			if ( is_singular( 'ticketea_event_list' ) ) {
				$paged           = (int) $wp_query->get( 'paged' );
				$event_ids       = ticketea_get_event_ids( get_queried_object_id() );
				$events_per_page = ticketea_get_events_per_page();
				$max_pages       = ceil( count( $event_ids ) / $events_per_page );

				if ( $paged > 1 && $paged <= $max_pages ) {
					// Prevent redirect.
					remove_action( 'template_redirect', 'redirect_canonical' );
				}
			} elseif ( is_singular( 'ticketea_event' ) ) {
				$event = ticketea_get_event( get_queried_object_id() );
				if ( $event ) {
					$events_in_use = ticketea_get_event_ids_in_use();

					if ( ! in_array( $event->event_id, $events_in_use ) ) {
						$wp_query->set_404();
						status_header( 404 );
						nocache_headers();
					}
				}
			}
		}

		/**
		 * Event List shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param array $atts The shortcode attributes.
		 * @return string The shortcode content.
		 */
		public function event_list_shortcode( $atts ) {
			// Disable the shortcode for the event lists.
			if ( 'ticketea_event_list' === get_post_type() ) {
				return '';
			}

			$params = shortcode_atts( array(
				'id' => 0,
			), $atts, 'ticketea_event_list' );

			if ( 'ticketea_event_list' !== get_post_type( $params['id'] ) || 'publish' !== get_post_status( $params['id'] ) ) {
				if ( current_user_can( 'edit_ticketea_event_lists'  ) ) {
					echo '<p>' . __( 'The shortcode ID attribute is not from a published Event List.', 'ticketea' ) . '</p>';
				}

				return;
			}

			ob_start();
			ticketea_load_template( 'event-list', array(
				'event_list_id' => intval( $params['id'] ),
			) );

			return ob_get_clean();
		}

	}
}

new Ticketea_Frontend();
