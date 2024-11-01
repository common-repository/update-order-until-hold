<?php 
/**
 * Plugin Name: Update Order Until HOLD
 * Plugin URI: http://cedcommerce.com
 * Description: A extension to add/Update product to an existing order having ON-HOLD status.
 * Version: 1.0.0
 * Author: CedCommerce
 * Author URI: http://cedcommerce.com
 * Requires at least: 3.5
 * Tested up to: 5.2.0
 * Text Domain: ced-update-order-onhold
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) 
{
	exit; // Exit if accessed directly
}

define('CED_WUOH_DIR', plugin_dir_path( __FILE__ ));
define('CED_WUOH_DIR_URL', plugin_dir_url( __FILE__ ));
define('CED_WUOH_PREFIX', 'ced_wuoh_');

$activated = true;

if (function_exists('is_multisite') && is_multisite())
{
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) )
	{
		$activated = false;
	}
}
else
{
	if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
	{
		$activated = false;
	}
}
/**
 * Check if WooCommerce is active
 **/
if ($activated) 
{
	include_once CED_WUOH_DIR.'includes/ced-update-order-main-class.php';
	
	add_filter( 'plugin_action_links','ced_wuoh_doc_settings', 10, 5 );
	
	/**
	 * This function is to add setting and docs link on plugin list page.
	 * @name ced_wuoh_doc_settings()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link http://cedcommerce.com/
	 */
	
	function ced_wuoh_doc_settings( $actions, $plugin_file )
	{
		static $plugin;
		if (!isset($plugin))
		{
			$plugin = plugin_basename(__FILE__);
		}
		if ($plugin == $plugin_file)
		{
			$settings = array('settings' => '<a href="'.home_url('/wp-admin/admin.php?page=update_order_products').'">' . __('Settings', 'ced-update-order-onhold') . '</a>');
			$actions = array_merge($settings, $actions);
		}
		return $actions;
	}
	
	add_action('plugins_loaded', 'ced_wuoh_load_text_domain');
	
	/**
	 * This function is used to load language'.
	 * @name ced_wuoh_load_text_domain()
	 * @author CedCommerce<plugins@cedcommerce.com>
	 * @link http://cedcommerce.com/
	 */
	
	function ced_wuoh_load_text_domain()
	{
		$domain = "ced-update-order-onhold";
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, CED_WUOH_DIR .'languages/'.$domain.'-' . $locale . '.mo' );
		$var=load_plugin_textdomain( 'ced-update-order-onhold', false, plugin_basename( dirname(__FILE__) ) . '/languages' );
	}
}
else
{
	/**
	 * Show error notice if woocommerce is not activated.
	 * @name ced_sadc_plugin_error_notice()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link http://cedcommerce.com/
	 */
	function ced_wuoh_plugin_error_notice()
	{
		wp_enqueue_style('ced_wuoh_hide_css', CED_WUOH_DIR_URL.'assets/css/ced-hide-notify.css');
		?>
		<div class="error notice is-dismissible">
			<p><?php _e( 'Woocommerce is not activated.please install woocommerce to use the Update Order Untill HOLD plugin !!!', 'ced-update-order-onhold' ); ?></p>
		</div>
		<?php
		
	}
		
	add_action( 'admin_init', 'ced_wuoh_plugin_deactivate' );
		
	/**
	 * Deactivate extension if woocommerce is not activated.
	 * @name ced_sadc_plugin_deactivate()
	 * @author CedCommerce <plugins@cedcommerce.com>
	 * @link http://cedcommerce.com/
	 */
	
	function ced_wuoh_plugin_deactivate() 
	{
		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action( 'admin_notices', 'ced_wuoh_plugin_error_notice' );
	}
}
?>