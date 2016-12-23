<?php
/*
 * send response to payhere as from submit form
 */
global $trans_id,$wpdb;
define('PAYPAL_MSG',__('Processing for Payhere, Please wait ....',DOMAIN));
$paymentOpts = templatic_get_payment_options($_REQUEST['paymentmethod']);
/* get all settings in payhere */
$payhere_options = get_option('payment_method_payhere');
$merchantid = $paymentOpts['merchant_id'];

$suburl = '';
if($_REQUEST['page'] == 'upgradenow'){
	$suburl ="&upgrade=pkg";
}

/* get success page with permalink */
$post_id = tmpl_get_post_id_by_meta_key_and_value('is_tevolution_success_page', '1');
$success_page_url = get_permalink($post_id);

/* Wpml language plugin wise url change in return url, cancle url and notify url */
if(is_plugin_active('sitepress-multilingual-cms/sitepress.php')){

	global $sitepress;
    
	if(isset($_REQUEST['lang'])){
		$url = $success_page_url.'/?page=paynow&lang='.$_REQUEST['lang'];
	}elseif($sitepress->get_current_language()){
		
		if($sitepress->get_default_language() != $sitepress->get_current_language()){
			$url = $success_page_url.'/'.$sitepress->get_current_language();
		}else{
			$url = $success_page_url;
		}	
	}else{
		$url = $success_page_url;
	}
	
	if(strstr($url,'?'))
	{
		$returnUrl = apply_filters('tmpl_returnUrl',$url."&ptype=return&pmethod=payhere&trans_id=".$trans_id.$suburl);
		$cancel_return = apply_filters('tmpl_cancel_return',$url."&ptype=cancel&pmethod=payhere&trans_id=".$trans_id.$suburl);
		$notify_url = apply_filters('tmpl_notify_url',$url."&ptype=notifyurl&pmethod=payhere&trans_id=".$trans_id.$suburl);
	}else
	{ 
		$returnUrl = apply_filters('tmpl_returnUrl',$url."?ptype=return&pmethod=payhere&trans_id=".$trans_id.$suburl);
		$cancel_return = apply_filters('tmpl_cancel_return',$url."?ptype=cancel&pmethod=payhere&trans_id=".$trans_id.$suburl);
		$notify_url = apply_filters('tmpl_notify_url',$url."?ptype=notifyurl&pmethod=payhere&trans_id=".$trans_id.$suburl);
	}	
}else{

	$returnUrl = apply_filters('tmpl_returnUrl',$success_page_url."?ptype=return&pmethod=payhere&trans_id=".$trans_id.$suburl);
	$cancel_return = apply_filters('tmpl_cancel_return',$success_page_url."?ptype=cancel&pmethod=payhere&trans_id=".$trans_id.$suburl);
	$notify_url = apply_filters('tmpl_notify_url',$success_page_url."?ptype=notifyurl&pmethod=payhere&trans_id=".$trans_id.$suburl);
}

$currency_code = templatic_get_currency_type();
global $payable_amount,$post_title,$last_postid;



$payable_amount = number_format((float)$payable_amount, 2, '.', ''); /* shows 2 desimal points as per payheres price forlmat */
$post = get_post($last_postid);
$post_title = apply_filters('tmpl_trans_title',$post->post_title);
$user_info = apply_filters('tmpl_trans_user_info',get_userdata($post->post_author));
$address1 = apply_filters('tmpl_trans_address1',get_post_meta($post->post_author,'address'));
$address2 = apply_filters('tmpl_trans_address2',get_post_meta($post->post_author,'area'));
$country = apply_filters('tmpl_trans_country',get_post_meta($post->post_author,'add_country'));
$state = apply_filters('tmpl_trans_state',get_post_meta($post->post_author,'add_state'));
$city = apply_filters('tmpl_trans_state',get_post_meta($post->post_author,'add_city'));

$current_user = wp_get_current_user();

if($_REQUEST['page'] == 'upgradenow' || $_REQUEST['post_viewer_package']){
	$price_package_id=$_REQUEST['pkg_id'];
        $returnUrl = $returnUrl.'&pid='.$last_postid;
        $cancel_return = $cancel_return.'&pid='.$last_postid;  
        $notify_url = $notify_url.'&pid='.$last_postid;          
        
       // '&pid='.$last_postid.'&trans_id='.$trans_id
        $custom_1 = 'upgpkg';
}
else{
    $price_package_id=$_REQUEST['pkg_id'];
    $custom_1 = 'newpkg';
}

/* if subscription package is done then show package name in payhere's item name */

/* get transaction details for getting package id */
$trans_detail = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."transactions WHERE trans_id =".$trans_id);
/* get package name from package id */
$post_title = get_the_title( $trans_detail->package_id );

$package_amount=get_post_meta($price_package_id,'package_amount',true);
$validity=get_post_meta($price_package_id,'validity',true);
$validity_per=get_post_meta($price_package_id,'validity_per',true);
$recurring=get_post_meta($price_package_id,'recurring',true);
$billing_num=get_post_meta($price_package_id,'billing_num',true);
$billing_per=get_post_meta($price_package_id,'billing_per',true);
$billing_cycle=get_post_meta($price_package_id,'billing_cycle',true);
$first_free_trail_period=get_post_meta($price_package_id,'first_free_trail_period',true);
if($recurring==1){
	$c=$billing_num;
	if($billing_per=='M'){
		$rec_type=sprintf('%d Month', $c);
		$cycle= 'Month';
	}elseif($billing_per=='D'){
		$rec_type=sprintf('%d Week', $c/7);
		$cycle= 'Week';
	}else{
		$rec_type=sprintf('%d Year', $c);
		$cycle= 'Year';
	}
				
	$c_recurrence=$rec_type;
	/*$c_duration='FOREVER';*/
	$c_duration=$billing_cycle.' '.$cycle;	
	
}

/* set url according to payhere mode selected in payment setting */
if($payhere_options['payhere_mode'] == 1){ /* if test mode */
	$action_url = 'https://sandbox.payhere.lk/pay/checkout';
}else{ /* if live mode */
	$action_url = 'https://www.payhere.lk/pay/checkout';
}
?>
<form name="frm_payment_method" action="<?php echo $action_url;?>" method="post">
<input type="hidden" name="merchant_id" value="<?php echo $merchantid;?>"/>
<input type="hidden" name="return_url" value="<?php echo $returnUrl;?>"/>
<input type="hidden" name="cancel_url" value="<?php echo $cancel_return;?>"/>
<input type="hidden" name="notify_url" value="<?php echo $notify_url;?>"/>
<input type="hidden" name="first_name" value="<?php echo $current_user->user_firstname; ?>">
<input type="hidden" name="last_name" value="<?php echo $current_user->last_name; ?>" >
<input type="hidden" name="email" value="<?php echo $current_user->user_email; ?>" >
<input type="hidden" name="phone" value="<?php echo $current_user->user_phone; ?>" >
<input type="hidden" name="address" value="<?php echo $address; ?>" >
<input type="hidden" name="city" value="<?php echo $city; ?>" >
<input type="hidden" name="country" value="<?php echo $country; ?>" >
<input type="hidden" name="order_id" value="<?php echo $price_package_id;?>"/>
<input type="hidden" name="items" value="<?php echo $post_title;?>"/>
<input type="hidden" name="currency" value="<?php echo $currency_code;?>"/>
<input type="hidden" name="amount" value="<?php echo $payable_amount;?>"/>
<input type="hidden" name="custom_1" value="<?php echo $custom_1;?>"/>
</form>
<div class="wrapper" >
<div class="clearfix container_message payment_processing_msg" style=" width:100%;text-align:center; height: 100%; margin-top: -10px; position: relative; top: 50%;">
	<h2 class="head2"><?php _e(PAYPAL_MSG);?></h2>
 </div>
</div>
<script type="text/javascript" async>
setTimeout("document.frm_payment_method.submit()",50); 
</script> <?php exit;?>