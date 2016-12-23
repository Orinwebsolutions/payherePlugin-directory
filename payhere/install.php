<?php
/*
 * insert option for paypal in database while plugin activation
 */
$paymentmethodname = 'payhere'; 
if($_REQUEST['install']==$paymentmethodname)
{
	$paymethodinfo = array();
	$payOpts = array();
	$payOpts[] = array(
					"title"			=>	__('Your PayHere Merchant ID','templatic-admin'),
					"fieldname"		=>	"merchant_id",
					"value"			=>	"1210575",
					"description"	=>	__('Example','templatic-admin').__(": 1210575",'templatic-admin')
					);
	$paymethodinfo = array(
						"name" 		=> __('PayHere','templatic-admin'),
						"key" 		=> $paymentmethodname,
						"isactive"	=>	'1', /* 1->display,0->hide*/
						"display_order"=>'7',
						"payOpts"	=>	$payOpts,
						);
	
	update_option("payment_method_$paymentmethodname", $paymethodinfo );
	$install_message = __("Payment Method integrated successfully",'templatic-admin');
	$option_id = $wpdb->get_var("select option_id from $wpdb->options where option_name like \"payment_method_$paymentmethodname\"");
	wp_redirect("admin.php?page=monetization&tab=payment_options");
}elseif($_REQUEST['uninstall']==$paymentmethodname)
{
	delete_option("payment_method_$paymentmethodname");
	$install_message = __("this payment method cannot deleted because it is fix, you can deactive it",'templatic-admin');
}
?>