<?php
/*
Plugin Name: WooCommerce Product Details Customiser
Plugin URI: http://jameskoster.co.uk/tag/product-details-customiser/
Version: 0.2.0
Description: Allows you to customise WooCommerce product details pages. Show / Hide core components like product imagery, tabs, upsells and related products.
Author: jameskoster
Tested up to: 3.8.1
Author URI: http://jameskoster.co.uk
Text Domain: woocommerce-product-details-customiser
Domain Path: /languages/

	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	/**
	 * Localisation
	 **/
	load_plugin_textdomain( 'woocommerce-product-details-customiser', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	/**
	 * PDC class
	 **/
	if ( ! class_exists( 'WC_pdc' ) ) {

		class WC_pdc {

			public function __construct() {
				add_action( 'wp_enqueue_scripts', array( $this, 'wc_pdc_styles' ) );

				// Init settings
				$this->settings = array(
					array(
						'name' 		=> __( 'Product Details', 'woocommerce-product-details-customiser' ),
						'desc'		=> __( 'Toggle the display of various components on product details pages.', 'woocommerce-product-details-customiser' ),
						'type' 		=> 'title',
						'id' 		=> 'wc_pdc_options'
					),
					array(
						'name' 		=> __( 'Display', 'woocommerce-product-details-customiser' ),
						'desc' 		=> __( 'Product Images', 'woocommerce-product-details-customiser' ),
						'id' 		=> 'wc_pdc_product_images',
						'type' 		=> 'checkbox',
						'default'	=> 'yes'
					),
					array(
						'desc' 		=> __( 'Product Tabs', 'woocommerce-product-details-customiser' ),
						'id' 		=> 'wc_pdc_tabs',
						'type' 		=> 'checkbox',
						'default'	=> 'yes'
					),
					array(
						'desc' 		=> __( 'Upsells', 'woocommerce-product-details-customiser' ),
						'id' 		=> 'wc_pdc_upsells',
						'type' 		=> 'checkbox',
						'default'	=> 'yes'
					),
					array(
						'desc' 		=> __( 'Related Products', 'woocommerce-product-details-customiser' ),
						'id' 		=> 'wc_pdc_related',
						'type' 		=> 'checkbox',
						'default'	=> 'yes'
					),
					array(
						'title' 	=> __( 'Related Products / Upsells Columns', 'woocommerce-product-details-customiser' ),
						'desc'		=> __( 'The number of columns that related products / upsells are arranged in to on product detail pages', 'woocommerce-product-details-customiser' ),
						'id' 		=> 'wc_pdc_columns',
						'default'	=> '2',
						'type' 		=> 'select',
						'options' 	=> array(
							'2'  	=> __( '2', 'woocommerce-product-details-customiser' ),
							'3' 	=> __( '3', 'woocommerce-product-details-customiser' ),
							'4' 	=> __( '4', 'woocommerce-product-details-customiser' ),
							'5' 	=> __( '5', 'woocommerce-product-details-customiser' )
						)
					),
					array( 'type' => 'sectionend', 'id' => 'wc_pdc_options' ),
				);


				// Default options
				add_option( 'wc_pdc_columns', '2' );
				add_option( 'wc_pdc_product_images', 'yes' );
				add_option( 'wc_pdc_upsells', 'yes' );
				add_option( 'wc_pdc_related', 'yes' );
				add_option( 'wc_pdc_tabs', 'yes' );

				// Admin
				add_action( 'woocommerce_settings_catalog_options_after', array( $this, 'admin_settings' ), 21 );
				add_action( 'woocommerce_update_options_catalog', array( $this, 'save_admin_settings' ) );
				add_action( 'woocommerce_update_options_products', array( $this, 'save_admin_settings' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'wc_pdc_admin_scripts' ) );

				// Frontend
				add_action( 'init', array( $this, 'wc_pdc_fire_customisations' ) );
				add_filter( 'body_class', array( $this, 'woocommerce_pdc_columns' ) );

			}


	        /*-----------------------------------------------------------------------------------*/
			/* Class Functions */
			/*-----------------------------------------------------------------------------------*/

			// Load the settings
			function admin_settings() {
				woocommerce_admin_fields( $this->settings );
			}


			// Save the settings
			function save_admin_settings() {
				woocommerce_update_options( $this->settings );
			}

			// Admin scripts
			function wc_pdc_admin_scripts() {
				$screen       = get_current_screen();
			    $wc_screen_id = strtolower( __( 'WooCommerce', 'woocommerce' ) );

			    // WooCommerce admin pages
			    if ( in_array( $screen->id, apply_filters( 'woocommerce_screen_ids', array( 'toplevel_page_' . $wc_screen_id, $wc_screen_id . '_page_woocommerce_settings' ) ) ) ) {

			    	wp_enqueue_script( 'wc-pdc-script', plugins_url( '/assets/js/script.min.js', __FILE__ ) );

			    }
			}

			// Setup styles
			function wc_pdc_styles() {
				wp_enqueue_style( 'pdc-layout-styles', plugins_url( '/assets/css/layout.css', __FILE__ ), '', '', 'only screen and (min-width: ' . apply_filters( 'woocommerce_style_smallscreen_breakpoint', $breakpoint = '768px' ) . ')' );
			}

			// Fire customisations!
			function wc_pdc_fire_customisations() {
				// Remove and re-add the new upsells display function
				remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
				add_action( 'woocommerce_after_single_product_summary', array( $this, 'woocommerce_pdc_upsells' ), 15 );

				// Filter the related products
				add_filter( 'woocommerce_output_related_products_args', array( $this, 'woocommerce_pdc_related_products' ) );

				// Product Image
				if ( get_option( 'wc_pdc_product_images' ) == 'no' ) {
					remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
					add_filter( 'body_class', array( $this, 'woocommerce_pdc_layout' ) );
				}

				// Upsells
				if ( get_option( 'wc_pdc_upsells' ) == 'no' ) {
					remove_action( 'woocommerce_after_single_product_summary', array( $this, 'woocommerce_pdc_upsells' ), 15 );
				}

				// Related Products
				if ( get_option( 'wc_pdc_related' ) == 'no' ) {
					remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
				}

				// Tabs
				if ( get_option( 'wc_pdc_tabs' ) == 'no' ) {
					remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
				}
			}


			/*-----------------------------------------------------------------------------------*/
			/* Frontend Functions */
			/*-----------------------------------------------------------------------------------*/

			// Product columns
			function woocommerce_pdc_columns( $classes ) {
				$columns 	= get_option( 'wc_pdc_columns' );
				$classes[] 	= 'collateral-product-columns-' . $columns;
				return $classes;
			}

			// Details layout
			function woocommerce_pdc_layout( $classes ) {
				$images 	= get_option( 'wc_pdc_product_images' );
				if ( $images = 'no' ) {
					$classes[] 	= 'details-full-width';
				} else {
					$classes[] = '';
				}
				return $classes;
			}

			// Upsells
			function woocommerce_pdc_upsells() {
			    $columns 	= get_option( 'wc_pdc_columns' );
			    woocommerce_upsell_display( -1, $columns );
			}

			// Related Products
			function woocommerce_pdc_related_products() {
				$columns 	= get_option( 'wc_pdc_columns' );
				$args = array(
					'posts_per_page' => apply_filters( 'woocommerce_pdc_related_products_per_page', $columns ),
					'columns'        => $columns,
				);
				return $args;
			}
		}

		$WC_pdc = new WC_pdc();
	}
}