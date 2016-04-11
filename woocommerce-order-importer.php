<?php
/*
Plugin Name: WooCommerce Order Importer
Plugin URI:
Description:
Version: 1.0.0
Author: Catman Studios
Author URI: https://catmanstudios.com
 License: GNU General Public License v3.0
 License URI: http://www.gnu.org/licenses/gpl-3.0.html

*/


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooCommerceOrderImporter {

	/*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const name = 'WooCommerce Order Importer';
	const slug = 'woocommerce-order-importer';

	/**
	 * Constructor
	 */
	function __construct() {
		//register an activation hook for the plugin
		register_activation_hook( __FILE__, array( $this, 'install_woocommerce_order_importer' ) );

		//Hook up to the init action
		add_action( 'init', array( $this, 'init_woocommerce_order_importer' ) );

	}

	/**
	 * Runs when the plugin is activated
	 */
	function install_woocommerce_order_importer() {

		global $wpdb;

		$ordersql = 'select * from temp_orders';


		$orders = $wpdb->get_results($ordersql, ARRAY_A);
		foreach($orders as $order){
			$order_id = $order['ID'];
			unset($order['ID']);
			$order_key = $wpdb->get_var("select meta_value from temp_postmeta where post_id = $order_id and meta_key = '_order_key'");
			// check if this order has been added already and skip if it has;
			$existing_orderid= $wpdb->get_var("select post_id from $wpdb->postmeta join $wpdb->posts on ID = post_id where meta_key='_order_key' and meta_value='$order_key'");
			if($existing_orderid != null) continue;

			$customer_user = $this->get_customer_user_id($order_id);

			$post = wp_insert_post($order);
			$metasql = 'select * from temp_postmeta where post_id = ' . $order_id;
			$itemsql = 'select * from temp_orderitems where order_id = ' . $order_id;
			$meta = $wpdb->get_results($metasql);


			$items = $wpdb->get_results($itemsql, ARRAY_A);
			//preserve original order id
			update_post_meta($post, '_order_number', $order_id);
			foreach($meta as $metum){

				switch ($metum->meta_key){
					case '_customer_user':
							update_post_meta($post, $metum->meta_key, $customer_user);
						break;
					case '_order_number':

						break;
					default:
						update_post_meta($post, $metum->meta_key, $metum->meta_value);

				}



			}

			foreach($items as $item){
				$orderitemid = $item['order_item_id'];
				unset($item['order_item_id']);
				$item['order_id'] = $post;
				$wpdb->insert('wp_woocommerce_order_items', $item);
				$itemmetasql = 'select * from temp_orderitemmeta where order_item_id='. $orderitemid;
				$itemid = $wpdb->insert_id;

				$itemmeta = $wpdb->get_results($itemmetasql, ARRAY_A);

				foreach($itemmeta as $meta){
					unset($meta['meta_id']);
					$meta['order_item_id'] = $itemid;
					$wpdb->insert('wp_woocommerce_order_itemmeta', $meta);
				}
			}

		}

		// do not generate any output here
	}

	function get_customer_user_id($order_id){
		global $wpdb;


		$old_user_id = $wpdb->get_var("select meta_value from temp_postmeta where post_id = $order_id and meta_key = '_customer_user'");
		$email = $wpdb->get_var("select user_email from temp_users where ID = $old_user_id");
		$user = get_user_by('email', $email);

		if($user  ){
			return $user->ID;

		}else{
				$old_user = $wpdb->get_row("select * from temp_users where ID = $old_user_id");
				$username = $old_user->user_login;
				if(username_exists($username)){
					$parts = explode('@', $email);
					$username = $parts[0];
				}


				$password = wp_generate_password($length=12, $include_standard_special_chars=false);
				if(username_exists($username)){
					$i = 1;

					do {
						$newusername = $username.$i;
						$i++;
					}
					while(username_exists($newusername));

				$user_id=	wp_create_user($newusername, $password, $email);
				}else{
					$user_id = wp_create_user($username, $password, $email);
				}
				$user_meta = $wpdb->get_results("select * from temp_usermeta where user_id=$old_user_id");

				foreach($user_meta as $meta){
						update_user_meta($user_id, $meta->meta_key, $meta->meta_value);
				}

				return $user_id;

		}
	}

	/**
	 * Runs when the plugin is initialized
	 */
	function init_woocommerce_order_importer() {
		// Setup localization
		load_plugin_textdomain( self::slug, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		// Load JavaScript and stylesheets
		$this->register_scripts_and_styles();

		// Register the shortcode [my_shortcode]
		add_shortcode( 'my_shortcode', array( $this, 'render_shortcode' ) );

		if ( is_admin() ) {
			//this will run when in the WordPress admin
		} else {
			//this will run when on the frontend
		}

		/*
		 * TODO: Define custom functionality for your plugin here
		 *
		 * For more information:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action( 'your_action_here', array( $this, 'action_callback_method_name' ) );
		add_filter( 'your_filter_here', array( $this, 'filter_callback_method_name' ) );
	}

	function action_callback_method_name() {
		// TODO define your action method here
	}

	function filter_callback_method_name() {
		// TODO define your filter method here
	}

	function render_shortcode($atts) {
		// Extract the attributes
		extract(shortcode_atts(array(
			'attr1' => 'foo', //foo is a default value
			'attr2' => 'bar'
			), $atts));
		// you can now access the attribute values using $attr1 and $attr2
	}

	/**
	 * Registers and enqueues stylesheets for the administration panel and the
	 * public facing site.
	 */
	private function register_scripts_and_styles() {
		if ( is_admin() ) {
			$this->load_file( self::slug . '-admin-script', '/js/admin.js', true );
			$this->load_file( self::slug . '-admin-style', '/css/admin.css' );
		} else {
			$this->load_file( self::slug . '-script', '/js/script.js', true );
			$this->load_file( self::slug . '-style', '/css/style.css' );
		} // end if/else
	} // end register_scripts_and_styles

	/**
	 * Helper function for registering and enqueueing scripts and styles.
	 *
	 * @name	The 	ID to register with WordPress
	 * @file_path		The path to the actual file
	 * @is_script		Optional argument for if the incoming file_path is a JavaScript source file.
	 */
	private function load_file( $name, $file_path, $is_script = false ) {

		$url = plugins_url($file_path, __FILE__);
		$file = plugin_dir_path(__FILE__) . $file_path;

		if( file_exists( $file ) ) {
			if( $is_script ) {

				wp_enqueue_script($name, $url, array('jquery'), false, true ); //depends on jquery
			} else {

				wp_enqueue_style( $name, $url );
			} // end if
		} // end if

	} // end load_file

} // end class
new WooCommerceOrderImporter();
