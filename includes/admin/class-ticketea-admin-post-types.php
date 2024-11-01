<?php
/**
 * Ticketea Admin Post Types
 *
 * @author     Ticketea
 * @package    Ticketea\Includes\Admin
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Ticketea_Admin_Post_Types' ) ) {
	/**
	 * The class for handle the post types in the admin.
	 *
	 * @since 1.0.0
	 */
	class Ticketea_Admin_Post_Types {

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );

			add_filter( 'manage_ticketea_event_list_posts_columns' , array( $this, 'event_list_columns' ) );
			add_action( 'manage_ticketea_event_list_posts_custom_column', array( $this, 'event_list_column' ), 10, 2 );

			add_filter( 'post_row_actions', array( $this, 'event_list_row_actions' ), 10, 2 );
			add_action( 'admin_action_sync_event_list', array( $this, 'sync_event_list_action' ) );

			add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );
			add_action( 'save_post_ticketea_event_list', array( $this, 'save_event_list' ) );

			add_action( 'added_post_meta', array( $this, 'updated_post_meta' ), 10, 3 );
			add_action( 'updated_post_meta', array( $this, 'updated_post_meta' ), 10, 3 );
		}

		/**
		 * Enqueues the admin post types scripts.
		 *
		 * @since 1.0.0
		 *
		 * @global WP_Post $post    The current post.
		 * @global string  $pagenow The current page.
		 */
		public function enqueue_scripts() {
			global $post, $pagenow;

			// Ticketea Event List new/edit page.
			if ( ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && 'ticketea_event_list' === $_GET['post_type'] ) ||
				( 'post.php' === $pagenow && $post && 'ticketea_event_list' === $post->post_type &&
				isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) ) {

				$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

				// Select2.
				wp_enqueue_style( 'select2', TICKETEA_URL . 'assets/css/select2.min.css', array(), '4.0.0' );
				wp_enqueue_script( 'select2', TICKETEA_URL . 'assets/js/libs/select2.min.js', array(), '4.0.0', true );

				// WP jQuery UI Dialog styles.
				wp_enqueue_style( 'wp-jquery-ui-dialog' );

				wp_enqueue_script( 'ticketea-filters', TICKETEA_URL . 'assets/js/filters' . $suffix . '.js', array( 'jquery', 'select2', 'jquery-ui-dialog' ), '1.0.0', true );
				wp_localize_script( 'ticketea-filters', 'ticketeaFiltersL10n', array(
					'categories'       => ticketea_get_event_categories_for_select2(),
					'filters'          => ticketea_get_event_list_filters( ( $post ? $post->ID : 0 ) ),
					'adminNotice'      => '<div id="message" class="{{type}} notice"><p>{{message}}</p></div>',
					'testModalContent' => $this->get_test_filters_modal_content(),
					'texts'            => array(
						'countryPlaceholder'  => __( 'Select countries', 'ticketea' ),
						'categoryPlaceholder' => __( 'Select a category', 'ticketea' ),
						'typologyPlaceholder' => __( 'Select a typology', 'ticketea' ),
						'topicPlaceholder'    => __( 'Select topics', 'ticketea' ),
						'emptyFilters'        => __( 'You must choose at least one filter.', 'ticketea' ),
						'noEventIds'          => __( 'The filter Events IDs is empty.', 'ticketea' ),
						'testModalTitle'      => __( 'Testing Filters', 'ticketea' ),
					),
				) );

			}
		}

		/**
		 * Customizes the event list columns.
		 *
		 * @since 1.0.0
		 *
		 * @param array $columns The event list columns.
		 * @return array The event list columns.
		 */
		public function event_list_columns( $columns ) {
			return array_merge(
				array_slice( $columns, 0, 2 ),
				array(
					'filters'   => __( 'Filters', 'ticketea' ),
					'events_nb' => __( 'Events', 'ticketea' ),
					'last_sync' => __( 'Last Sync', 'ticketea' ),
					'shortcode' => __( 'Shortcode', 'ticketea' ),
				),
				array_slice( $columns, 2 )
			);
		}

		/**
		 * Prints the event list column.
		 *
		 * @since 1.0.0
		 *
		 * @param string $column  The column to display.
		 * @param int    $post_id The post ID.
		 */
		public function event_list_column( $column, $post_id ) {
			switch ( $column ) {
				case 'filters':
					$this->event_list_filters_column( $post_id );
					break;
				case 'events_nb':
					$event_ids = ticketea_get_event_ids_from_meta( $post_id, null );
					echo is_null( $event_ids ) ? '&ndash;' : count( $event_ids );
					break;
				case 'last_sync':
					if ( ticketea_event_list_is_synchronizing( $post_id ) ) {
						echo _x( 'Synchronizing', 'Event List: Last Sync', 'ticketea' );
					} else {
						$timestamp = ticketea_get_event_list_last_sync( $post_id );
						echo $timestamp ? ticketea_format_datetime( $timestamp ) : _x( 'Never', 'Event List: Last Sync', 'ticketea' );
					}
					break;
				case 'shortcode':
					echo '[ticketea_event_list id="' . esc_attr( $post_id ) . '"]';
					break;
			}
		}

		/**
		 * Filters the row actions for an event list.
		 *
		 * @since 1.0.0
		 *
		 * @param array   $actions The row actions.
		 * @param WP_Post $post    The post object.
		 * @return array The row actions
		 */
		public function event_list_row_actions( $actions, $post ) {
			if ( 'ticketea_event_list' === $post->post_type && 'publish' === get_post_status( $post->ID ) ) {
				$actions['sync'] = '<a href="' . $this->get_event_list_action_link( $post->ID, 'sync_event_list' ) . '" title="' . esc_attr__( 'Sync the event list', 'ticketea' ) . '">' . __( 'Sync', 'ticketea' ) . '</a>';
			}

			return $actions;
		}

		/**
		 * Sync Event List action.
		 *
		 * @since 1.0.0
		 */
		public function sync_event_list_action() {
			if ( empty( $_REQUEST['post'] ) ) {
				wp_die( __( 'No Event List has been supplied!', 'ticketea' ) );
			}

			$post_id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';

			if ( check_admin_referer( 'ticketea_sync_event_list_' . $post_id ) ) {
				if ( 'publish' === get_post_status( $post_id ) ) {
					ticketea_get_event_ids( $post_id, true );
				}

				wp_redirect( admin_url( 'edit.php?post_type=ticketea_event_list' ) );
				die();
			}
		}

		/**
		 * Adds the custom meta boxes.
		 *
		 * @since 1.0.0
		 *
		 * @param string $post_type The current post type.
		 */
		public function meta_boxes( $post_type ) {
			if ( 'ticketea_event_list' === $post_type ) {
				// Filters.
				add_meta_box(
					'event-list-filters',
					__( 'Event List Filters', 'ticketea' ),
					array( $this, 'event_list_filters' ),
					'ticketea_event_list',
					'normal',
					'high'
				);

				// Display.
				add_meta_box(
					'event-list-display',
					__( 'Event List Display', 'ticketea' ),
					array( $this, 'event_list_display' ),
					'ticketea_event_list',
					'normal',
					'high'
				);
			}
		}

		/**
		 * Prints the 'event-list-filters' metabox.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Post $post The post.
		 */
		public function event_list_filters( $post ) {
			$labels = ticketea_get_filters_labels();
			$values = ticketea_get_event_list_filters( $post->ID );

			wp_nonce_field( 'ticketea_event_list_filters', 'event_list_filters[_wpnonce]', false );
			?>
			<p><?php _e( 'Filter the events by the following parameters:', 'ticketea' ); ?></p>

			<table id="filter-by" class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php _e( 'Filter by', 'ticketea' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e( 'Filter by', 'ticketea' ); ?></span></legend>
								<?php
									$filter_by = ( $values['event_id'] ? 'event_id' : 'others' );
									$filter_by_choices = array(
										'event_id' => __( 'Event ID', 'ticketea' ),
										'others'   => __( 'Others', 'ticketea' ),
									);

									foreach ( $filter_by_choices as $value => $label ) :
										$input = sprintf( '<input id="%1$s" type="radio" name="%1$s" value="%2$s" %3$s />',
											'event_list_filter_by',
											esc_attr( $value ),
											checked( $value, $filter_by, false )
										);

										printf( '<label title="%1$s">%2$s %3$s</label><br />',
											esc_attr( $label ),
											$input,
											wp_kses_post( $label )
										);
									endforeach;
								?>
								<p class="description"><?php _e( 'Choose the filters to use.', 'ticketea' ); ?></p>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>

			<table id="event-ids-filters" class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php echo esc_attr( $labels['event_id'] ); ?></th>
						<td>
							<textarea class="regular-textarea" type="text" name="event_list_filters[event_id]"><?php echo esc_attr( $values['event_id'] ); ?></textarea>
							<p class="description"><?php _e( 'Separate the events by commas.', 'ticketea' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<table id="others-filters" class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php echo esc_attr( $labels['category'] ); ?></th>
						<td>
							<select id="category" class="regular-select" name="event_list_filters[category]">
								<option></option>
							<?php
								$categories = ticketea_get_event_categories();
								foreach ( $categories as $id => $category ) {
									printf( '<option value="%1$s" %2$s>%3$s</option>',
										esc_attr( $id ),
										selected( $id, $values['category'], false ),
										esc_html( $category['name'] )
									);
								}
							?>
							</select>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo esc_attr( $labels['typology'] ); ?></th>
						<td>
							<select id="typology" class="regular-select" name="event_list_filters[typology]">
								<option></option>
							</select>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo esc_attr( $labels['topic'] ); ?></th>
						<td>
							<select id="topic" class="regular-select" name="event_list_filters[topic][]" multiple="multiple"></select>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo esc_attr( $labels['organizer_id'] ); ?></th>
						<td>
							<textarea class="regular-textarea" type="text" name="event_list_filters[organizer_id]"><?php echo esc_attr( $values['organizer_id'] ); ?></textarea>
							<p class="description"><?php _e( 'Separate the organizers by commas.', 'ticketea' ); ?></p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo esc_attr( $labels['venue_id'] ); ?></th>
						<td>
							<textarea class="regular-textarea" type="text" name="event_list_filters[venue_id]"><?php echo esc_attr( $values['venue_id'] ); ?></textarea>
							<p class="description"><?php _e( 'Separate the venues by commas.', 'ticketea' ); ?></p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo esc_attr( $labels['venue_city'] ); ?></th>
						<td>
							<textarea class="regular-textarea" type="text" name="event_list_filters[venue_city]"><?php echo esc_attr( $values['venue_city'] ); ?></textarea>
							<p class="description"><?php _e( 'Separate the cities by commas.', 'ticketea' ); ?></p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo esc_attr( $labels['venue_province'] ); ?></th>
						<td>
							<textarea class="regular-textarea" type="text" name="event_list_filters[venue_province]"><?php echo esc_attr( $values['venue_province'] ); ?></textarea>
							<p class="description"><?php _e( 'Separate the provinces by commas.', 'ticketea' ); ?></p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo esc_attr( $labels['venue_country_code'] ); ?></th>
						<td>
							<select id="venue_country_code" class="regular-select" name="event_list_filters[venue_country_code][]" multiple="multiple">
							<?php
								$countries = ticketea_get_countries();
								foreach ( $countries as $code => $country ) {
									printf( '<option value="%1$s" %2$s>%3$s</option>',
										esc_attr( $code ),
										selected( in_array( $code, $values['venue_country_code'] ), true, false ),
										esc_html( $country )
									);
								}
							?>
							</select>
						</td>
					</tr>
				</tbody>
			</table>

			<table id="test-filters" class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"></th>
						<td><a id="test-filters" class="button button-secondary button-large" href="#"><?php _e( 'Test Filters', 'ticketea' ); ?></a></td>
					</tr>
				</tbody>
			</table>

			<?php
		}

		/**
		 * Prints the 'event-list-display' metabox.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Post $post The post.
		 */
		public function event_list_display( $post ) {
			$display_info_meta = get_post_meta( $post->ID, 'ticketea_event_list_display_info', true );
			$display_info = ticketea_parse_supported_args( $display_info_meta, $this->get_display_info_defaults() );
			$display_labels = array(
				'logo'       => _x( 'Image', 'Event List Display', 'ticketea' ),
				'price'      => _x( 'Price', 'Event List Display', 'ticketea' ),
				'type'       => _x( 'Category / Typology / Topic', 'Event List Display', 'ticketea' ),
				'venue_name' => _x( 'Venue Name', 'Event List Display', 'ticketea' ),
				'city'       => _x( 'City, Province & Country', 'Event List Display', 'ticketea' ),
				'button'     => _x( 'Checkout Link', 'Event List Display', 'ticketea' ),
			);

			wp_nonce_field( 'ticketea_event_list_display', 'event_list_display[_wpnonce]', false );
			?>
			<p><?php _e( 'Customize the event list:', 'ticketea' ); ?></p>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php echo _e( 'Display Information', 'ticketea' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e( 'Display Information', 'ticketea' ); ?></span></legend>
								<?php
									foreach ( $display_info as $key => $value ) :
										$field_id = 'event_list_display_' . $key;
										$input = sprintf( '<input type="checkbox" name="event_list_display[info][%1$s]" id="%2$s" value="1" %3$s />',
											esc_attr( $key ),
											esc_attr( $field_id ),
											checked( $value, 1, false )
										);

										printf( '<label for="%1$s">%2$s %3$s</label><br />',
											esc_attr( $field_id ),
											$input,
											wp_kses_post( $display_labels[ $key ] )
										);
									endforeach;
								?>
								<p class="description"><?php _e( 'Choose the information to display in the list.', 'ticketea' ); ?></p>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
		}

		/**
		 * Fires after a post metadata has been successfully added or updated.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $meta_id  The post meta ID.
		 * @param int    $post_id  The post ID.
		 * @param string $meta_key The meta key.
		 */
		public function updated_post_meta( $meta_id, $post_id, $meta_key ) {
			// Delete the 'last sync' metadata when the filters change.
			if ( 'ticketea_event_list_filters' === $meta_key && 'ticketea_event_list' === get_post_type( $post_id ) ) {
				delete_post_meta( $post_id, '_ticketea_last_sync' );
				delete_post_meta( $post_id, '_ticketea_event_ids' );

				// Auto-Sync on filters update.
				if ( 'publish' === get_post_status( $post_id ) ) {
					ticketea_get_event_ids( $post_id );
				}
			}
		}

		/**
		 * Save the Event List meta when the post is saved.
		 *
		 * @since 1.0.0
		 *
		 * @param int $post_id The ID of the post being saved.
		 */
		public function save_event_list( $post_id ) {
			// It's an autosave.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// Don't save revisions and autosaves
			if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
				return;
			}

			// Check the user's permissions.
			if ( ! current_user_can( 'edit_ticketea_event_list', $post_id ) ) {
				return;
			}

			// Save metaboxes.
			$metaboxes = array( 'event_list_filters', 'event_list_display' );
			foreach ( $metaboxes as $metabox ) {
				if ( isset( $_POST[ $metabox ] ) ) {
					// Sanitize the values.
					$metas = array_map( 'wp_unslash', $_POST[ $metabox ] );

					// Verify that the nonce is valid.
					if ( wp_verify_nonce( $metas['_wpnonce'], 'ticketea_' . $metabox ) ) {
						unset( $metas['_wpnonce'] );

						// Save metabox.
						if ( method_exists( $this, 'save_' . $metabox ) ) {
							call_user_func( array( $this, 'save_' . $metabox ), $metas, $post_id );
						}
					}
				}
			}
		}

		/**
		 * Save the 'event-list-filters' metabox.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @param array $metas   The values of the 'event-list-filters' metabox.
		 * @param int   $post_id The post ID.
		 */
		protected function save_event_list_filters( $metas, $post_id ) {
			$values = array();

			if ( isset( $_POST['event_list_filter_by'] ) && 'event_id' === $_POST['event_list_filter_by'] ) {
				$values = array( 'event_id' => sanitize_text_field( $metas['event_id'] ) );
			} else {
				$metas['event_id'] = '';
				foreach ( $metas as $key => $value ) {
					switch( $key ) {
						case 'venue_country_code':
						case 'topic':
							if ( ! is_array( $value ) ) {
								$value = array( $value );
							}

							$values[ $key ] = array_map( 'sanitize_text_field', $value );
							break;
						default:
							$values[ $key ] = sanitize_text_field( $value );
							break;
					}
				}
			}

			$filters = array_filter( $values );
			if ( ! empty( $filters ) ) {
				update_post_meta( $post_id, 'ticketea_event_list_filters', $values );
			}
		}

		/**
		 * Save the 'event-list-display' metabox.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @param array $metas   The values of the 'event-list-display' metabox.
		 * @param int   $post_id The post ID.
		 */
		protected function save_event_list_display( $metas, $post_id ) {
			// Display Info.
			$display_info = array();
			$info         = isset( $metas['info'] ) ? $metas['info'] : array();
			$info_keys    = array_keys( $this->get_display_info_defaults() );

			foreach ( $info_keys as $key ) {
				$display_info[ $key ] = isset( $info[ $key ] ) ? 1 : 0;
			}

			update_post_meta( $post_id, 'ticketea_event_list_display_info', $display_info );
		}

		/**
		 * Prints the event list filters column.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @param int $event_list_id The event list ID.
		 */
		protected function event_list_filters_column( $event_list_id ) {
			$filters = array_filter( ticketea_get_event_list_filters( $event_list_id ) );
			$labels = ticketea_get_filters_labels();

			$category = false;
			if ( isset( $filters['category'] ) ) {
				$category = ticketea_get_event_category( $filters['category'] );
			}

			foreach ( $filters as $key => $value ) {
				switch( $key ) {
					case 'venue_country_code':
						$countries = array();
						foreach ( $value as $country_code ) {
							$countries[] = ticketea_get_country_label( $country_code );
						}

						$clean_value = join( ', ', $countries );
						break;
					case 'category':
						$clean_value = ( $category ? $category['name'] : '-' );
						break;
					case 'typology':
						$clean_value = ( $category ? $category['typologies'][ $value ] : '-' );
						break;
					case 'topic':
						$clean_value = '-';
						if ( $category ) {
							$topics = array();
							foreach ( $value as $topic_id ) {
								$topics[] = $category['topics'][ $topic_id ];
							}

							$clean_value = join( ', ', $topics );
						}
						break;
					default:
						$clean_value = $value;
						break;
				}

				printf( '<strong>%1$s:</strong> %2$s <br />',
					esc_attr( $labels[ $key ] ),
					esc_html( $clean_value )
				);
			}
		}

		/**
		 * Gets the default values for the display info checkboxes.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @return array The default values.
		 */
		protected function get_display_info_defaults() {
			return array(
				'logo'       => 1,
				'price'      => 1,
				'type'       => 1,
				'venue_name' => 1,
				'city'       => 1,
				'button'     => 1,
			);
		}

		/**
		 * Gets the action link for an event list.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @param int    $post_id The post ID.
		 * @param string $action  The action.
		 * @return string The action link.
		 */
		protected function get_event_list_action_link( $post_id, $action ) {
			if ( ! current_user_can( 'edit_ticketea_event_list', $post_id ) ) {
				return;
			}

			$args = array_map( 'urlencode', array(
				'action' => $action,
				'post'   => $post_id,
			) );

			return wp_nonce_url( admin_url( add_query_arg( $args, 'edit.php?post_type=ticketea_event_list' ) ), "ticketea_{$action}_{$post_id}" );
		}

		/**
		 * Gets the content for the test_filters modal.
		 *
		 * @since 1.0.0
		 *
		 * @return string The content for the test_filters modal.
		 */
		protected function get_test_filters_modal_content() {
			ob_start();
			?>
			<div class="tkt-test-filters">
				<p><?php _e( 'We are retrieving the events for your selected filters. Please wait.', 'ticketea' ); ?></p>
				<p class="loading-content"><span class="dashicons dashicons-update tkt-spin"></span></p>
			</div>
			<?php

			return ob_get_clean();
		}

	}
}

new Ticketea_Admin_Post_Types();
