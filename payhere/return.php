<?php
/*
* THIS FILE WILL BE CALLED ON SUCCESSFUL PAYMENT AFTER SUBMITTING AN EVENT.
*/
add_action('wp_head','show_background_color');
function show_background_color()
{
/* Get the background image. */
	$image = get_background_image();
	/* If there's an image, just call the normal WordPress callback. We won't do anything here. */
	if ( !empty( $image ) ) {
		_custom_background_cb();
		return;
	}
	/* Get the background color. */
	$color = get_background_color();
	/* If no background color, return. */
	if ( empty( $color ) )
		return;
	/* Use 'background' instead of 'background-color'. */
	$style = "background: #{$color};";
?>
<style type="text/css">
body.custom-background {
<?php echo trim( $style );
?>
}
</style>
<?php }

if(isset($_REQUEST['page']) && $_REQUEST['page'] == 'success' )
{
	$page_title = PAYMENT_SUCCESS_TITLE;
}elseif(isset($_REQUEST['ptype']) && $_REQUEST['ptype'] == 'cancel'){
	$page_title = PAYMENT_CANCEL_TITLE;
}
global $page_title,$current_user; ?>
<?php get_header();

 ?>
<section id="content" class="large-9 small-12 columns">
<?php apply_filters( 'wp_title', $page_title, $separator, '',11 ); ?>
<div id="hfeed">
<?php do_action('templ_before_success_container_breadcrumb');?>	 

<?php 
	if($_REQUEST['trans_id'] != "" && $_REQUEST['pid'] != ""){

	global $wpdb;
	$transaction_db_table_name=$wpdb->prefix.'transactions';
	$trans_qry = "select * from $transaction_db_table_name where trans_id='".$_REQUEST['trans_id']."' ";
	$trans_id = $wpdb->get_row($trans_qry);     
        
	if($trans_id->trans_id !=""){
	$tmpdata = get_option('templatic_settings');
	$filecontent = stripslashes($tmpdata['post_payment_success_msg_content']);
	if(!$filecontent)
	{
		$filecontent = PAYMENT_SUCCESS_MSG;
	}
	$filesubject = __('Payment procedure has been completed','templatic');
	
	$store_name = get_option('blogname');
	$order_id = $_REQUEST['pid'];
	if(get_post_type($order_id)=='event')
	{
		$post_link = get_permalink($_REQUEST['pid']);
	}else
	{
	$post_link = get_permalink($_REQUEST['pid']);	
	}
	
	
	$buyer_information = "";
	
	$post = get_post($_REQUEST['pid']);
	$address = stripslashes(get_post_meta($post->ID,'geo_address',true));
	$geo_latitude = get_post_meta($post->ID,'geo_latitude',true);
	$geo_longitude = get_post_meta($post->ID,'geo_longitude',true);
	$timing = get_post_meta($post->ID,'timing',true);
	$contact = stripslashes(get_post_meta($post->ID,'contact',true));
	$email = get_post_meta($post->ID,'email',true);
	$website = get_post_meta($post->ID,'website',true);
	$twitter = get_post_meta($post->ID,'twitter',true);
	$facebook = get_post_meta($post->ID,'facebook',true);
			
	$store_login='';
	$store_login_link='';
	if(function_exists('get_tevolution_login_permalink')){
		$store_login = '<a href="'.get_tevolution_login_permalink().'">'.__('Click Login','templatic').'</a>';
		$store_login_link = get_tevolution_login_permalink();
	}
	
	$search_array = array('[#site_name#]','[#submited_information_link#]','[#submited_information#]','[#site_login_url#]','[#site_login_url_link#]');
	$replace_array = array($store_name,$post_link,$buyer_information,$store_login,$store_login_link);
	
	$filecontent = str_replace($search_array,$replace_array,$filecontent);
	?>
	<div class="content-title"><?php echo $page_title; ?></div>
	<?php
	if($_REQUEST['pid']!="" && $_REQUEST['trans_id']!=""){
         

        
		if(isset($_SESSION['upgrade_info']) && !empty($_SESSION['upgrade_info'])){
			$upgrade_data = $_SESSION['upgrade_info']['upgrade_data'];
			$upgrade_data['total_price'] = $_SESSION['upgrade_info']['total_price'];
			$payable_amount = $_SESSION['upgrade_info']['total_price'];
			$package_select = $_SESSION['upgrade_info']['package_select'];
			
			update_post_meta($_REQUEST['pid'] ,'upgrade_data',$upgrade_data);
			update_post_meta($_REQUEST['pid'] ,'paid_amount',$payable_amount);
			update_post_meta($_REQUEST['pid'] ,'total_price',$payable_amount);
			update_post_meta($_REQUEST['pid'] ,'package_select',$package_select);
			
			unset($_SESSION['upgrade_info']);

		}
		
		if($trans_id->payforfeatured_h == 1  && $trans_id->payforfeatured_c == 1){
			update_post_meta($_REQUEST['pid'], 'featured_c', 'c');
			update_post_meta($_REQUEST['pid'], 'featured_h', 'h');
			update_post_meta($_REQUEST['pid'], 'featured_type', 'both');			
		}elseif($trans_id->payforfeatured_c == 1){
			update_post_meta($_REQUEST['pid'], 'featured_c', 'c');
			update_post_meta($_REQUEST['pid'], 'featured_type', 'c');
		}elseif($trans_id->payforfeatured_h == 1){
			update_post_meta($_REQUEST['pid'], 'featured_h', 'h');
			update_post_meta($_REQUEST['pid'], 'featured_type', 'h');
		}else{
			update_post_meta($_REQUEST['pid'], 'featured_type', 'none');	
		}
                
        /* always approve when payment success */
		$status = 'Approved';
		update_post_meta($_REQUEST['pid'],'status',$status);
		
	}
	}else{
		$filesubject =  INVALID_TRANSACTION_TITLE;
		$filecontent = AUTHENTICATION_CONTENT;
	
	}
	/*Payment success email: start*/
        global $wpdb;
	$transaction_Id = $_REQUEST['trans_id'];
	$transaction_db_table_name = $wpdb->prefix . "transactions";
	$ordersql = "select * from $transaction_db_table_name where trans_id=\"$transaction_Id\"";
	$orderinfo = $wpdb->get_row($ordersql);
	$pid = $orderinfo->post_id;
	$payment_type = $orderinfo->payment_method;
	$amount = $orderinfo->payable_amt;
	$payment_date =  date_i18n(get_option('date_format'),strtotime($orderinfo->payment_date));
	$user_detail = get_userdata($orderinfo->user_id); /* get user details */
	$user_email = $user_detail->user_email;
	$user_login = $user_detail->display_name;
	if(isset($orderinfo->status) && $orderinfo->status== 1)
		$payment_status = APPROVED_TEXT;
	elseif(isset($orderinfo->status) && $orderinfo->status== 2)
		$payment_status = ORDER_CANCEL_TEXT;
	elseif(isset($orderinfo->status) && $orderinfo->status== 0)
		$payment_status = PENDING_MONI;
		
	$to = get_site_emailId_plugin();
	/* added limit to query for query performance */
	$productinfosql = "select ID,post_title,guid,post_author from $wpdb->posts where ID = $pid LIMIT 0,1";
	$productinfo = get_post($pid);
        $post_name = $productinfo->post_title;

        
	$transaction_details="";
	
	if(isset($_REQUEST['upgrade']) && $_REQUEST['upgrade']=='pkg'){
		$transaction_details .= "--------------------------------------------------</br>\r\n";
		$transaction_details .= "Transaction details of upgrade subscription.</br>\r\n";
		$transaction_details .= "--------------------------------------------------</br>\r\n";	
	}else{
		$transaction_details .= "<p>--------------------------------------------------</p>";
		$transaction_details .= "<p>".__('Payment Details for','templatic')." $post_name</p>";
		$transaction_details .= "<p>--------------------------------------------------</p>";
	}

	if($transaction_Id)
		$transaction_details .= "<p>".__('Payhere Transaction ID','templatic').": $transaction_Id</p>";
	if($amount)
		$transaction_details .= "<p>".__('Amount','templatic').": ".fetch_currency_with_position($amount)."</p>";	
	if($payment_status !='')
		$transaction_details .= "<p>".__('Status','templatic').": $payment_status</p>";
	if($payment_type !='')
		$transaction_details .= "<p>".__('Type','templatic').": $payment_type</p>";
	$transaction_details .= 	"<p>".__('Date','templatic').": $payment_date</p>";
	$transaction_details .= "--------------------------------------------------\r\n";
	$transaction_details = $transaction_details;
	$subject = $tmpdata['payment_success_email_subject_to_admin'];
	if(!$subject)
	{
		$subject = __("Payment Success Confirmation Email",'templatic');
	}
	$content = $tmpdata['payment_success_email_content_to_admin'];
	if(!$content)
	{
		$content = __("<p>Howdy [#to_name#],</p><p>You have received a payment of [#payable_amt#] on [#site_name#]. Details are available below</p><p>[#transaction_details#]</p><p>Thanks,<br/>[#site_name#]</p>",'templatic');
	}
	$store_name = '<a href="'.site_url().'">'.get_option('blogname').'</a>';
	$store_login='';
	$store_login_link='';
	if(function_exists('get_tevolution_login_permalink')){
		$store_login = '<a href="'.get_tevolution_login_permalink().'">'.__('Click Login','templatic').'</a>';
		$store_login_link = get_tevolution_login_permalink();
	}
	
	$fromEmail = get_option('admin_email');
	$fromEmailuname = get_site_emailName_plugin();
	$fromEmailName = stripslashes(get_option('blogname'));	
	$search_array = array('[#to_name#]','[#payable_amt#]','[#transaction_details#]','[#site_name#]','[#site_login_url#]','[#site_login_url_link#]');
	$replace_array = array($fromEmailuname,$payable_amount,$transaction_details,$store_name,$store_login,$store_login_link);
	$filecontent1 = str_replace($search_array,$replace_array,$content);
                
	templ_send_email($fromEmail,$fromEmailName,$to,$user_login,$subject,$filecontent1,''); /* email to admin*/        
	/* post details*/
	$post_link = site_url().'/?ptype=preview&alook=1&pid='.$pid;
	$post_title = '<a href="'.$post_link.'">'.stripslashes($productinfo->post_title).'</a>'; 
	$aid = $productinfo->post_author;
	$mail_post_type = $productinfo->post_type;
	$userInfo = get_userdata($aid);
	$to_name = $userInfo->display_name;
	$to_email = $userInfo->user_email;
	$user_email = $userInfo->user_email;
        
	$transaction_details ="";
	if(isset($_REQUEST['upgrade']) && $_REQUEST['upgrade']=='pkg'){

		$transaction_details .= "<p>--------------------------------------------------</p>";
		$transaction_details .= "<p>".__('Transaction details of upgrade subscription.','templatic')."</p>";
		$transaction_details .= "<p>--------------------------------------------------</p>";
	}else{
		$transaction_details .= "<p>--------------------------------------------------</p>";
		$transaction_details .= "<p>".__('Payment Details for','templatic'). $post_title."</p>";
		$transaction_details .= "<p>--------------------------------------------------</p>";
	}
	if($transaction_Id)
		$transaction_details .= "<p>".__('Payhere Transaction ID','templatic').":". $transaction_Id."</p>"; 
        if($amount)
		$transaction_details .= "<p>".__('Amount','templatic').": ".fetch_currency_with_position($amount)."</p>";	
	if($payment_status !='')
		$transaction_details .= "<p>".__('Status','templatic').":".$payment_status."</p>";
	if($payment_type !='')
		$transaction_details .= "<p>".__('Type','templatic').":".$payment_type."</p>";
	$transaction_details .= "<p>".__('Date','templatic').":".$payment_date."</p>";
	$transaction_details .= "<p>--------------------------------------------------</p>";
	$transaction_details = $transaction_details;
            
	$subject = $tmpdata['payment_success_email_subject_to_client'];
	if(!$subject)
	{
		$subject = __("Payment Success Confirmation Email",'templatic');
	}
	$content = $tmpdata['payment_success_email_content_to_client'];
	if(!$content)
	{
		$content = __("<p>Hello [#to_name#]</p><p>Here's some info about your payment...</p><p>[#transaction_details#]</p><p>If you'll have any questions about this payment please send an email to [#admin_email#]</p><p>Thanks!,<br/>[#site_name#]</p>",'templatic');
	}
	$store_name = '<a href="'.site_url().'">'.get_option('blogname').'</a>';

	$store_login='';
	$store_login_link='';
	if(function_exists('get_tevolution_login_permalink')){
		$store_login = '<a href="'.get_tevolution_login_permalink().'">'.__('Click Login','templatic').'</a>';
		$store_login_link = get_tevolution_login_permalink();
	}
	
	$search_array = array('[#to_name#]','[#transaction_details#]','[#site_name#]','[#admin_email#]','[#transection_id#]','[#post_type#]','[#site_login_url#]','[#site_login_url_link#]');
	$replace_array = array($to_name,$transaction_details,$store_name,get_option('admin_email'),$transaction_Id,ucfirst(get_post_type($pid)),$store_login,$store_login_link);
	$content1 = str_replace($search_array,$replace_array,$content);
	templ_send_email($fromEmail,$fromEmailName,$user_email,$user_login,$subject,$content1,'');//user email sending

	/*Payment success email: end	*/
}
else if(isset($_REQUEST['trans_id']) && $_REQUEST['trans_id'] != '' && @$_REQUEST['pid'] == '')
{
//    echo 'you bro your inside else';
		global $monetization,$wpdb;
		/* Get the payment method and paid amount */
		$transaction = $wpdb->prefix."transactions";
		$wpdb->query("UPDATE $transaction SET status=1 , paypal_transection_id ='".$_REQUEST['txn_id']."' where trans_id = '".wp_kses_post($_REQUEST['trans_id'])."'");

		$paidamount = get_post_meta(@$_REQUEST['pid'],'paid_amount',true);
		if(@$paidamount==''){
			$paidamount_result = $wpdb->get_row("select payable_amt,package_id from $transaction t  order by t.trans_id DESC");
			$paidamount = $paidamount_result->payable_amt;
			$package_id = $paidamount_result->package_id;
		}
		$user_limit_post=get_user_meta($current_user->ID,$post_type.'_list_of_post',true); /*get the user wise limit post count on price package select*/
		if(!$user_limit_post)	
			$user_limit_post=get_user_meta($current_user->ID,$post_type.'_list_of_post',true); /*get the user wise limit post count on price package select*/
		$package_limit_post=get_post_meta($package_id,'limit_no_post',true);/* get the price package limit number of post*/
		$user_have_pkg = get_post_meta($package_id,'package_type',true); 
		$user_last_postid = $monetization->templ_get_packagetype_last_postid($current_user->ID,$post_type); /* User last post id*/
		$user_have_days = $monetization->templ_days_for_packagetype($current_user->ID,$post_type); /* return alive days(numbers) of last selected package  */
		$is_user_have_alivedays = $monetization->is_user_have_alivedays($current_user->ID,$post_type); /* return user have an alive days or not true/false */
		$is_user_package_have_alivedays = $monetization->is_user_package_have_alivedays($current_user->ID,$post_type,$package_id); /* return user have an alive days or not true/false */
		
}
else{
	$filesubject = INVALID_TRANSACTION_TITLE;
	$filecontent = INVALID_TRANSACTION_CONTENT;
}
/*Add Action for change the paypal successful return content as per needed */
do_action('paypal_successfull_return_content',$_REQUEST['pid'],$filesubject,$filecontent);
?>
<?php 
if(@$_REQUEST['trans_id'] != "" && @$_REQUEST['pid'] != "")
{
	do_action('tevolution_submition_success_post_content');
}?>
</div> <!-- content #end -->
</section> <!-- content #end -->
<?php if ( is_active_sidebar( 'primary-sidebar') ) : ?>
	<aside id="sidebar-primary" class="sidebar large-3 small-12 columns">
		<?php dynamic_sidebar('primary-sidebar'); ?>
	</aside>
<?php endif; ?>
<?php get_footer(); ?>