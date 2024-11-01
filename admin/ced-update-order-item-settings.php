<?php 

if ( ! defined( 'ABSPATH' ) )
{
	exit; // Exit if accessed directly
}

$wpuo_value = get_option('wpuo_setting_option',false);
if(!empty($wpuo_value))
{
	$wpuo_value = json_decode($wpuo_value, true);
	if($wpuo_value['wpuo_enable'])
	{
		$checked = 'checked';
	}
	else 
	{
		$checked = '';
	}	
}
else
{
	$checked = 'checked';
	$wpuo_value['wpuo_hours'] = 0;
	$wpuo_value['wpuo_time'] = 0;
}	
?>
<div tabindex="0" aria-label="Main content" id="wpbody-content">
	<div class="wrap woocommerce">
		<form enctype="multipart/form-data" action="" id="mainform" method="post">
			<hr/>
			<h3><?php _e('Update Order Settings', 'ced-update-order-onhold');?> </h3>
			<hr/>
			<table class="form-table">
				<tbody>
					<tr valign="top" class="">
						<th class="titledesc" scope="row"><?php _e('Enable Setting', 'ced-update-order-onhold');?></th>
						<td class="forminp forminp-checkbox">
							<label for="woocommerce_calc_shipping"><input type="checkbox" <?php echo $checked?> value="true" id="woocommerce_calc_shipping" name="wpuo_enable"> <?php _e('Enable Update Order', 'ced-update-order-onhold');?>
						</td>
					</tr>
					<tr valign="top" class="">
						<th class="titledesc" scope="row"><?php _e('Update order item within', 'ced-update-order-onhold');?></th>
						<td class="forminp forminp-checkbox">
							<input type="number" value="<?php echo $wpuo_value['wpuo_hours']?>" class="" id="woocommerce_shipping_cost_requires_address" name="wpuo_hours" min="1"> <?php _e('Hours', 'ced-update-order-onhold');?>
						</td>
					</tr>
					<tr valign="top" class="">
						<th class="titledesc" scope="row"><?php _e('No. of times user can update their Order items', 'ced-update-order-onhold');?></th>
						<td class="forminp forminp-checkbox">
							<input type="number" value="<?php echo $wpuo_value['wpuo_time']?>" class="" id="woocommerce_shipping_cost_requires_address" name="wpuo_time" min="1"> 
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<input type="submit" value="Save changes" class="button-primary" name="wpuo_save">
				</p>
		</form>
	</div>
	<div class="clear"></div>
</div>