<?php
/**
 * Ticketea Admin Settings
 *
 * @author     Ticketea
 * @package    Ticketea\Includes\Admin
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Ticketea_Admin_Settings' ) ) {
	/**
	 * Admin settings class.
	 *
	 * @since 1.0.0
	 */
	class Ticketea_Admin_Settings {

		/**
		 * The settings page slug.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		const PAGE_SLUG = 'ticketea_settings';

		/**
		 * The current settings tab.
		 *
		 * @since 1.0.0
		 * @access private
		 * @var string
		 */
		private $tab;

		/**
		 * The Ticketea Settings instance.
		 *
		 * @since 1.0.0
		 * @access private
		 * @var Ticketea_Settings
		 */
		private $settings = array();


		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 *
		 * @param Ticketea_Settings $settings The Ticketea Settings instance.
		 */
		public function __construct( Ticketea_Settings $settings ) {
			$this->settings = $settings;
			$this->tab      = ticketea_get_url_param( 'tab', 'general' );

			add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}

		/**
		 * Admin menu.
		 *
		 * @since 1.0.0
		 */
		public function admin_menu() {
			add_submenu_page( 'ticketea', __( 'Ticketea Settings', 'ticketea' ), __( 'Settings', 'ticketea' ), 'administrator', self::PAGE_SLUG, array( $this, 'settings_page' ) );
		}

		/**
		 * Registers the settings.
		 *
		 * @since 1.0.0
		 */
		public function register_settings() {
			register_setting( $this->get_settings_group_name(), $this->settings->get_option_name(), array( $this, 'validate' ) );

			$settings = $this->get_settings_fields( $this->tab );
			foreach( $settings as $section ) {
				add_settings_section( $section['id'], $section['title'], array( $this, 'section_description' ), self::PAGE_SLUG );
				foreach ( $section['fields'] as $field ) {
					add_settings_field(
						$field['id'],
						$field['title'],
						array( $this, "{$field['type']}_field" ),
						self::PAGE_SLUG,
						$section['id'],
						$field
					);
				}
			}
		}

		/**
		 * Gets the settings group name.
		 *
		 * @since 1.0.0
		 *
		 * @return string The settings group name.
		 */
		public function get_settings_group_name() {
			return $this->settings->get_option_name();
		}

		/**
		 * Gets the settings tabs.
		 *
		 * @since 1.0.0
		 *
		 * @return array The settings tabs.
		 */
		public function get_settings_tabs() {
			return array(
				'general' => __( 'General', 'ticketea' ),
			);
		}

		/**
		 * Gets an array with settings fields.
		 *
		 * @since 1.0.0
		 *
		 * @param string $tab Optional. Filter the settings by the specified tab.
		 * @return array The settings fields.
		 */
		public function get_settings_fields( $tab = '' ) {
			$settings = array(
				// General Settings.
				'general' => array(
					array(
						'id'     => 'general',
						'title'  => __( 'General', 'ticketea' ),
						'fields' => array(
							array(
								'id'      => 'buy_tickets_in',
								'title'   => __( 'Buy tickets in', 'ticketea' ),
								'desc'    => __( 'Choose where the users can buy the tickets.', 'ticketea' ),
								'type'    => 'radio',
								'choices' => array(
									'ticketea'    => __( "Redirect to the event's page in Ticketea.com", 'ticketea' ),
									'own_website' => __( 'Buy the tickets in my website.', 'ticketea' ),
								),
							),
							array(
								'id'    => 'default_styles',
								'title' => __( 'Default Styles', 'ticketea' ),
								'label' => __( 'Load the default styles.', 'ticketea' ),
								'desc'  => __( 'Disable this option if you want to load your custom styles.', 'ticketea' ),
								'type'  => 'checkbox',
							),
						),
					),
					array(
						'id'     => 'affiliation',
						'title'  => __( 'Affiliation Program', 'ticketea' ),
						'desc'   => __( 'Enter your keys for the affiliation program.', 'ticketea' ),
						'fields' => array(
							array(
								'id'      => 'a_aid',
								'title'   => __( 'A_AID', 'ticketea' ),
								'type'    => 'text',
							),
							array(
								'id'      => 'a_bid',
								'title'   => __( 'A_BID', 'ticketea' ),
								'type'    => 'text',
							),
						),
					),
				),
			);

			if ( $tab ) {
				return ( isset( $settings[ $tab ] ) ? $settings[ $tab ] : array() );
			}

			return $settings;
		}

		/**
		 * Validates the settings.
		 *
		 * @since 1.0.0
		 *
		 * @param array $values The settings values.
		 * @return array The validated settings values.
		 */
		public function validate( $values ) {
			$clean_values = $this->settings->get_settings();
			$settings = $this->get_settings_fields( $this->tab );

			foreach ( $settings as $section ) {
				foreach ( $section['fields'] as $setting ) {
					$type = sanitize_title( $setting['type'] );
					$value = isset( $values[ $setting['id'] ] ) ? wp_unslash( $values[ $setting['id'] ] ) : null;

					switch( $type ) {
						case 'checkbox':
							$value = ( (bool) $value ) ? 1 : 0;
							break;
						default:
							$value = sanitize_text_field( $value );
							break;
					}

					$clean_values[ $setting['id'] ] = $value;
				}
			}

			return $clean_values;
		}

		/**
		 * Prints the settings page.
		 *
		 * @since 1.0.0
		 */
		public function settings_page() {
			?>
			<div id="ticketea-settings" class="wrap">
				<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
				<h2 class="nav-tab-wrapper">
				<?php
					$tabs = $this->get_settings_tabs();
					foreach ( $tabs as $tab => $label ) :
						printf( '<a href="%1$s" title="%2$s" class="nav-tab%3$s">%4$s</a>',
							esc_url( add_query_arg( array(
								'page' => self::PAGE_SLUG,
								'tab'  => $tab
							), admin_url( 'admin.php' ) ) ),
							esc_attr( $label ),
							( $this->tab === $tab ? ' nav-tab-active' : '' ),
							esc_html( $label )
						);
					endforeach;
				?>
				</h2>

				<?php settings_errors(); ?>
				<div class="tab_container">
					<form method="post" action="options.php">
					<?php
						settings_fields( $this->get_settings_group_name() );
						do_settings_sections( self::PAGE_SLUG );
						submit_button();
					?>
					</form>
				</div>
			</div>
			<?php
		}

		/**
		 * Prints the section description.
		 *
		 * @since 1.0.0
		 *
		 * @param array $section The section object.
		 */
		public function section_description( $section ) {
			$section_settings = $this->get_settings_fields( $this->tab );
			if ( isset( $section_settings[ $section['id'] ] ) && isset( $section_settings[ $section['id'] ]['desc'] ) ) {
				echo '<p class="section-description">' . wp_kses_post( $section_settings[ $section['id'] ]['desc'] ) . '</p>';
			}
		}

		/**
		 * Prints the field description.
		 *
		 * @since 1.0.0
		 *
		 * @param string $desc The field description.
		 */
		public function field_description( $desc ) {
			if ( $desc ) {
				echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
			}
		}

		/**
		 * Prints a text field.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args Arguments passed by the setting.
		 */
		public function text_field( $args ) {
			$value = $this->settings->get_setting( $args['id'] );

			printf( '<input id="%1$s" class="%2$s" type="%3$s" name="%4$s" value="%5$s" %6$s />',
				esc_attr( $args['id'] ),
				esc_attr( isset( $args['class'] ) ? $args['class'] : 'regular-text' ),
				esc_attr( $args['type'] ),
				esc_attr( $this->get_field_name( $args['id'] ) ),
				esc_attr( $value ),
				esc_attr( isset( $args['required'] ) && $args['required'] ? 'required': '' )
			);

			if ( isset( $args['desc'] ) ) {
				$this->field_description( $args['desc'] );
			}
		}

		/**
		 * Prints a radio field.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args Arguments passed by the setting.
		 */
		public function radio_field( $args ) {
			$current_value = $this->settings->get_setting( $args['id'] );
			?>
			<fieldset>
				<legend class="screen-reader-text"><span><?php echo esc_attr( $args['title'] ); ?></span></legend>
				<?php
					foreach ( $args['choices'] as $value => $label ) :
						$input = sprintf( '<input id="%1$s" type="%2$s" name="%3$s" value="%4$s" %5$s />',
							esc_attr( $args['id'] ),
							esc_attr( $args['type'] ),
							esc_attr( $this->get_field_name( $args['id'] ) ),
							esc_attr( $value ),
							checked( $value, $current_value, false )
						);

						printf( '<label title="%1$s">%2$s %3$s</label>',
							esc_attr( $label ),
							$input,
							wp_kses_post( $label )
						);

						echo '<br />';
					endforeach;

					if ( isset( $args['desc'] ) ) {
						$this->field_description( $args['desc'] );
					}
				?>
			</fieldset>
			<?php
		}

		/**
		 * Prints a checkbox field.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args Arguments passed by the setting.
		 */
		public function checkbox_field( $args ) {
			$label = ( isset( $args['label'] ) ? $args['label'] : $args['title'] );
			$value = (bool) $this->settings->get_setting( $args['id'] );
			?>
			<fieldset>
				<legend class="screen-reader-text"><span><?php echo esc_attr( $args['title'] ); ?></span></legend>
				<?php
					$input = sprintf( '<input id="%1$s" type="%2$s" name="%3$s" %4$s />',
						esc_attr( $args['id'] ),
						esc_attr( $args['type'] ),
						esc_attr( $this->get_field_name( $args['id'] ) ),
						checked( $value, true, false )
					);

					printf( '<label for="%1$s">%2$s %3$s</label>',
						esc_attr( $args['id'] ),
						$input,
						wp_kses_post( $label )
					);

					if ( isset( $args['desc'] ) ) {
						$this->field_description( $args['desc'] );
					}
				?>
			</fieldset>
			<?php
		}

		/**
		 * Gets the name attribute for the field.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @param int $id The field ID.
		 * @return string The field name.
		 */
		protected function get_field_name( $id ) {
			$option_name = $this->settings->get_option_name();

			return "{$option_name}[{$id}]";
		}

	}
}

new Ticketea_Admin_Settings( Ticketea( 'settings' ) );
