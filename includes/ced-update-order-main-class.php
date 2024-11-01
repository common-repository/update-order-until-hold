<?php

if ( ! defined( 'ABSPATH' ) )
{
	exit; // Exit if accessed directly
}

if( ! class_exists( 'Ced_update_order' ) )
{
	class Ced_update_order{
	
		public function __construct() {
			
			add_action( 'init', array ( $this, 'ced_wuoh_save_setting_check' ));
			add_action( 'wp_enqueue_scripts', array($this,'ced_wuoh_define_ajax_url'));
			add_action( 'admin_menu', array($this, 'ced_wuoh_update_ordered_product_page'));
			add_filter( 'woocommerce_create_order', array($this,'ced_wuoh_update_order_id'));
			add_filter( 'woocommerce_checkout_fields' , array($this,'ced_wuoh_custom_override_checkout_fields' ));
			add_filter( 'woocommerce_order_item_quantity' , array($this,'ced_wuoh_manage_stock', 10, 3 ));
			add_action( 'woocommerce_view_order', array($this,'ced_wuoh_reorder_button'));
			add_action( 'wp_ajax_wuoh_cancel_update', array($this,'ced_wuoh_cancel_update_callback' ));
			add_action( 'wp_ajax_nopriv_wuoh_cancel_update', array($this,'ced_wuoh_cancel_update_callback' ));
			add_filter( 'woocommerce_order_button_html', array($this,'ced_wuoh_change_button_text'));
			add_action( 'woocommerce_order_details_after_order_table', array($this,'ced_wuoh_add_edit_order_button' ));
			add_filter( 'woocommerce_thankyou_order_received_text', array($this,'ced_wuoh_check_thankyou_page' ));
			add_action( 'woocommerce_thankyou', array($this,'ced_wuoh_check_order_thankyou_page' ));
			add_action('woocommerce_checkout_order_processed', array ( $this, 'ced_wuoh_update_payment_method' ), 10, 2);
			add_filter( 'wp_mail_content_type', array ( $this, 'ced_wuoh_set_content_type' ));
		}
		

		/**
		 * This function is for update notification and other confidential purpose'.
		 * @name ced_wuoh_save_setting_check()
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		
		function ced_wuoh_save_setting_check()
		{
			if(!session_id()){
				session_start();
			}
			
			if(isset($_POST['wpuo_save']))
			{
				$wpuo_value['wpuo_enable'] = sanitize_text_field($_POST['wpuo_enable']);
				$wpuo_value['wpuo_hours'] = sanitize_text_field($_POST['wpuo_hours']);
				$wpuo_value['wpuo_time'] = sanitize_text_field($_POST['wpuo_time']);
				update_option('wpuo_setting_option', json_encode($wpuo_value));
			}
		}
		

	
		
		/**
		 * Define Ajaxurl in extension.
		 * @name ced_wuoh_define_ajax_url()
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		
		function ced_wuoh_define_ajax_url()
		{
			wp_register_script( 'ced_wcoh_ajax_js', plugin_dir_url( __FILE__ ).'../assets/js/ced-ajax-url.js', array('jquery'), '1.0.0', true );
			$pass_value = array(
					'ajaxurl' => admin_url('admin-ajax.php'),
			);
			wp_localize_script( 'ced_wcoh_ajax_js', 'ajax_url', $pass_value );
			
			wp_enqueue_script( 'ced_wcoh_ajax_js' );
			wp_enqueue_script( 'wcoh_checkout_page_js', plugin_dir_url( __FILE__ ).'../assets/js/ced_wuoh_checkout.js', array('jquery'), '1.0.0', true );
			wp_enqueue_style( 'wcoh_checkout_page_css', plugin_dir_url( __FILE__ ).'../assets/css/ced_wuoh_checkout.css');
		}		
				
		/**
		 * This function is used to show settings menu of extension.
		 * @name ced_wuoh_update_ordered_product_page()
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		
		function ced_wuoh_update_ordered_product_page()
		{
			add_submenu_page('woocommerce', 'Update Order Item ON-HOLD', 'Update Order Item ON-HOLD', 'manage_options', 'update_order_products',  array($this, 'ced_wuoh_update_order_products') );
		}
		

		/**
		 * This function is used to save settings of extension.
		 * @name ced_wuoh_update_order_products()
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		
		function ced_wuoh_update_order_products()
		{
			include_once CED_WUOH_DIR.'admin/ced-update-order-item-settings.php';
		}
		
		/**
		 * Update order with new item in cart
		 * @name ced_wuoh_update_order_id()
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		
		function ced_wuoh_update_order_id()
		{
			if(isset($_SESSION['edit_order_id']))
			{
				global $wpdb;
				$order_id = $_SESSION['edit_order_id'];
				$order = wc_update_order( array ( 'order_id' => $order_id ) );
				$orders = new WC_Order($order_id);
				$items = $orders->get_items();
		
				foreach($items as $key=>$item)
				{
					wc_delete_order_item($key);
				}
		
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					$item_id = $order->add_product(
							$values['data'],
							$values['quantity'],
							array(
									'variation' => $values['variation'],
									'totals'    => array(
											'subtotal'     => $values['line_subtotal'],
											'subtotal_tax' => $values['line_subtotal_tax'],
											'total'        => $values['line_total'],
											'tax'          => $values['line_tax'],
											'tax_data'     => $values['line_tax_data'] // Since 2.2
									)
							)
					);
		
					if ( ! $item_id ) {
						throw new Exception( sprintf( __( 'Error %d: Unable to update order. Please try again.', 'ced-update-order-onhold' ), 402 ) );
					}
				}
		
				$order->set_total( WC()->cart->shipping_total, 'shipping' );
				$order->set_total( WC()->cart->get_cart_discount_total(), 'cart_discount' );
				$order->set_total( WC()->cart->get_cart_discount_tax_total(), 'cart_discount_tax' );
				$order->set_total( WC()->cart->tax_total, 'tax' );
				$order->set_total( WC()->cart->shipping_tax_total, 'shipping_tax' );
				$order->set_total( WC()->cart->total );
		
				$to = get_option( 'admin_email', null );
		
				$subject = 'Order Update #'.$order_id;
				$subject = apply_filters('ced_update_notification_message',$subject);
		
				$message = __('Products of Order Id','ced-update-order-onhold').' :'.$order_id.__('is updated. Please check the update order by click on link:','ced-update-order-onhold');
				$message .='<br/>';
				$message .= '<a href='.home_url('wp-admin/post.php?post='.$order_id.'&action=edit').'>'.__('Click Here','ced-update-order-onhold').'</a>';
				$message .='<br/>'.__('Or','ced-update-order-onhold').'<br/>';
				$message .=__('If the above link did not work, please cut and paste the following internet address into your browser :-','ced-update-order-onhold').'<br/>';
				$message .=home_url('wp-admin/post.php?post='.$order_id.'&action=edit');
				$message = apply_filters('ced_update_notification_message',$message);
				wp_mail( $to, $subject, $message );
				do_action('ced_update_order_notification', $order_id);
		
				return $order_id;
			}
		}
		

		/**
		 * Remove shipping and billing address field at checkout
		 * @name ced_wuoh_custom_override_checkout_fields()
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		
		function ced_wuoh_custom_override_checkout_fields( $fields )
		{
			if(isset($_SESSION['edit_order_id']))
			{
				$fields = array();
			}
			return $fields;
		}
		
		/**
		 * Manage stock on UPdate order
		 * @name ced_wuoh_manage_stock()
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		
		function ced_wuoh_manage_stock($qty, $order, $edit_item)
		{
			if(isset($_SESSION['edit_order_items']) && isset($_SESSION['edit_order_id']))
			{
				$current_item = $edit_item['product_id'];
				$items = $_SESSION['edit_order_items'];
				foreach($items as $item)
				{
					if($current_item == $item['product_id'])
					{
						$old_qty = $item['qty'];
					}
				}
				if($old_qty == $qty)
				{
					$return_qty = $qty-$old_qty;
				}
				else
				{
					if($old_qty > $qty)
					{
						$return_qty = $qty-$old_qty;
					}
					else
					{
						$return_qty = $qty-$old_qty;
					}
				}
				return $return_qty;
			}
			else
			{
				return $qty;
			}
		}
		
		/**
		 * ON UPDATE ORDER ADD ORDER PRODUCT TO CART
		 * @name ced_wuoh_reorder_button()
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		
		function ced_wuoh_reorder_button($order_id)
		{
			if(isset($_POST['edit_order']))
			{
				global $woocommerce;
		
				$woocommerce->cart->empty_cart();
				$order = wc_get_order( $order_id );
				if ( sizeof( $order->get_items() ) > 0)
				{
					foreach( $order->get_items() as $item_id => $item )
					{
						$_product  = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
						$item_meta = new WC_Order_Item_Meta( $item['item_meta'], $_product );
		
						if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) )
						{
							if(isset($item_meta->meta))
							{
								foreach($item_meta->meta as $key=>$val)
								{
									if($key=='_qty')
									{
										$reorder_meta['quantity'] = $val[0];
									}
									if($key=='_product_id')
									{
										$reorder_meta['product_id'] = $val[0];
									}
									if($key=='_variation_id')
									{
										$reorder_meta['variation_id'] = $val[0];
									}
		
									if(substr($key, 0, 3) === 'pa_')
									{
										$pro_attribute['attribute_'.$key] = $val[0];
									}
								}
								$reorder_meta['meta'] = $item_meta->meta;
							}
							if(isset($pro_attribute))
							{
								$reorder_meta['attribute'] = $pro_attribute;
							}
							$cart_product[] = $reorder_meta;
						}
					}
					foreach($cart_product as $k=>$reorder_meta)
					{
						global $woocommerce;
						if(isset($reorder_meta['product_id']) && $reorder_meta['quantity'])
						{
							if(isset($reorder_meta['variation_id']))
							{
								$variation_id = $reorder_meta['variation_id'];
							}
							else
							{
								$variation_id = null;
							}
		
							if(isset($reorder_meta['meta']))
							{
								$meta = $reorder_meta['meta'];
							}
							else
							{
								$meta = null;
							}
		
							if(isset($reorder_meta['attribute']))
							{
								$attribute = $reorder_meta['attribute'];
							}
							else
							{
								$attribute = null;
							}
		
							$woocommerce->cart->add_to_cart( $reorder_meta['product_id'], $reorder_meta['quantity'], $variation_id, $attribute, $meta );
							$_SESSION['edit_order_id'] = $order->id;
							$_SESSION['edit_order_items'] =  $order->get_items();
						}
					}
		
					if(sizeof($woocommerce->cart->cart_contents) != 0)
					{
						$wuoh_cart_url = array(
								'wuoh_cart_url' => $woocommerce->cart->get_cart_url()
						);
						wp_localize_script( 'ced_wcoh_ajax_js', 'wuoh_cart_url', $wuoh_cart_url );
					}
				}
			}
		}


		/**
		 * Cancel Update at checkout page
		 * @name ced_wuoh_cancel_update_callback()
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		
		function ced_wuoh_cancel_update_callback()
		{
			$response = array();
			if(isset($_POST['action']))
			{
				if($_POST['action'] == 'wuoh_cancel_update')
				{
					if(isset($_SESSION['edit_order_id']))
					{
						unset($_SESSION['edit_order_id']);
						unset($_SESSION['edit_order_items']);
					}
				}
			}
			$response['clear'] = true;
			echo json_encode($response);
			die;
		}
		
		/**
		 * Replace button on Checkout page
		 * @name ced_wuoh_change_button_text()
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		
		function ced_wuoh_change_button_text()
		{
			if(isset($_SESSION['edit_order_id']))
			{
				$order_button_text = __('Update Order','ced-update-order-onhold');
				echo '<input type="submit" class="button alt" name="woocommerce_checkout_place_order" id="aplace_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '" />';
				echo '<input id="wuoh_cancel_update" class="button alt" type="button" value="Cancel Update"/>';
		
			}
			else
			{
				$order_button_text = __('Place Order','ced-update-order-onhold');
				echo '<input type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '" />';
			}
		}
		
		/**
		 * Replace button on Checkout page
		 * @name ced_wuoh_add_edit_order_button()
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		
		function ced_wuoh_add_edit_order_button($order_id)
		{
			$wpuo_setting = get_option('wpuo_setting_option', false);
			if(isset($wpuo_setting))
			{
				$wpuo_setting = json_decode($wpuo_setting,true);
				$order = wc_get_order( $order_id );
				$order_status = $order->post_status;
				$current_time = date("Y-m-d h:i:sa");
				$order_time = $order->order_date;
				$order_time=  strtotime($order_time);
				$current_time=  strtotime($current_time);
				$diff=$current_time-$order_time;
		
				//Print the difference in hours : minutes
				$diff_hour = date("H",$diff);
				$new_time = strtotime('+'.$wpuo_setting['wpuo_hours'].' hours',$order_time);
				$diff = $new_time - $current_time;
				$days = $diff/86400;
				$diff_hour = date("H",$diff);
				$diff_sec = date("s",$diff);
				$diff_time = date("H:i:s",$diff);
				$time = $diff_time;
				$seconds = strtotime("1970-01-01 $time UTC");
				$no_of_times = get_option('wpou_order_update_time_'.$order_id->id, 0);
		
				if($order_status == 'wc-on-hold' && is_page('my-account') && $diff_hour < $wpuo_setting['wpuo_hours'] && $no_of_times < $wpuo_setting['wpuo_time'] && $diff > 0)
				{
					?>
					<form action="" method="post">
						<div>
							<div style="float:left;margin-right: 5%;">
								<b><?php _e('Time left to update','ced-update-order-onhold')?> :</b>
							</div>
							<div style="float:left;margin-bottom: 2%;">
								<?php if($days >= 1){?>
								<div class="ced_wuoh_timer" style="width: 75px;"><b><?php _e('Days','ced-update-order-onhold');?></b> </div>
								<?php }?>
								<div class="ced_wuoh_timer" style="width: 50px;"><b><?php _e('Hrs','ced-update-order-onhold');?></b></div>
								<div class="ced_wuoh_timer" style="width: 50px;"><b><?php _e('Min','ced-update-order-onhold');?></b></div>
								<div class="ced_wuoh_timer" style="width: 50px;"><b><?php _e('Sec','ced-update-order-onhold');?></b></div>
								<div class="ced_clear"></div>
								<div id="ced_wuoh_countdown"></div>
							</div>
						</div>
						<div class="ced_clear"></div>
						<div>
							<b><span  style="margin-right: 5%;"><?php _e('Number of Updates','ced-update-order-onhold')?> : </span><span><?php echo $no_of_times;?>/<?php echo $wpuo_setting['wpuo_time'];?></span></b>
						</div>
						<input type="hidden" value="<?php echo $diff?>" id="wpuo_time_diff"></p>
						<input type="submit" class="button view" value="<?php _e('Update Order','ced-update-order-onhold');?>" name="edit_order">
						
					</form>
					<?php
				}
			}
		}
		
		/**
		 * Show update message on thankyou page
		 * @name ced_wuoh_check_thankyou_page()
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		
		
		function ced_wuoh_check_thankyou_page()
		{
			if(isset($_SESSION['edit_order_id']))
			{
				$order_id = $_SESSION['edit_order_id'];
				_e('Your Order is successfully updated','ced-update-order-onhold');
				unset($_SESSION['edit_order_id']);
				$no_of_times = get_option('wpou_order_update_time_'.$order_id, 0);
				$no_of_times = $no_of_times + 1;
				update_option('wpou_order_update_time_'.$order_id, $no_of_times);
			}
		}
		
		/**
		 * Show update message on thankyou page
		 * @name ced_wuoh_check_order_thankyou_page()
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		
		function ced_wuoh_check_order_thankyou_page($orderid )
		{
			if(isset($_SESSION['edit_order_id']))
			{
				$order_id = $_SESSION['edit_order_id'];
				_e('Your Order is successfully updated','ced-update-order-onhold');
				unset($_SESSION['edit_order_id']);
				$no_of_times = get_option('wpou_order_update_time_'.$order_id, 0);
				$no_of_times = $no_of_times + 1;
				update_option('wpou_order_update_time_'.$order_id, $no_of_times);
			}
		}
		
		function ced_wuoh_update_payment_method($order_id, $posted)
		{
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		
			if(isset($posted['payment_method']))
			{
				$payment_method = $posted['payment_method'];
				if(isset($available_gateways[$payment_method]))
				{
					$payment_method_title = $available_gateways[$payment_method]->title;
					if(isset($payment_method) && isset($payment_method_title))
					{
						update_post_meta( $order_id, '_payment_method', $payment_method );
						update_post_meta( $order_id, '_payment_method_title', $payment_method_title );
					}
				}
			}
		}
		
		/**
		 * Mail content type
		 * @name ced_wuoh_set_content_type()
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		
		function ced_wuoh_set_content_type( $content_type ) {
			return 'text/html';
		}
	}
	new Ced_update_order();
}
?>