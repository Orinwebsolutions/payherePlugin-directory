<?php
/*
 THIS FILE WILL BE CALLED IF THE USER CANCEL THE PROCESS OF SUBMITTING FORM.
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


global $page_title,$current_user;
$page_title =  __('Payment Cancellation','templatic');

get_header(); ?>
<?php if ( get_option( 'ptthemes_breadcrumbs' ) == 'Yes') {  ?>
<div class="breadcrumb_in"><a href="<?php echo site_url(); ?>"><?php _e('Home'); ?></a> &raquo; <?php echo $page_title; ?></div><?php } ?>

<section id="content" class="large-9 small-12 columns">
	
	
	<div class="post-content">
	<?php if($current_user->ID !=''){ ?>
	<h2><?php  _e('Payment Cancellation','templatic');?></h2>
	<?php 
	
	$tmpdata = get_option('templatic_settings');
	$filecontent = stripslashes(get_option('post_payment_cancel_msg_content'));
	if(!$filecontent)
	{
		$filecontent = PAY_CANCEL_MSG;
	}
	$store_name = '<a href="'.site_url().'">'.get_option('blogname').'</a>';
	$store_login='';
	$store_login_link='';
	if(function_exists('get_tevolution_login_permalink')){
		$store_login = '<a href="'.get_tevolution_login_permalink().'">'.__('Click Login','templatic').'</a>';
		$store_login_link = get_tevolution_login_permalink();
	}
	$search_array = array('[#site_name#]','[#admin_email#]','[#site_login_url#]','[#site_login_url_link#]');
	$replace_array = array($store_name,get_option('admin_email'));
	$filecontent = str_replace($search_array,$replace_array,$filecontent,$store_login,$store_login_link);
	echo $filecontent;
	echo ' <a href="'.site_url().'">';
	_e('Go to Home','templatic');
	echo '</a>';
	$post = get_post($_REQUEST['pid']);
	
	$current_user_id = $current_user->ID;
	$user_info = get_userdata( $current_user_id );
	$fromEmail = $user_info->user_email;
	//$fromEmailName = $user_info->user_login;
	$fromEmailName = get_site_emailName_plugin();
	$to_name_user = $user_info->user_nicename;
	$to = get_option('admin_email');
	$to_name_admin = stripslashes(get_option('blogname'));	
	$subject = $tmpdata['payment_cancelled_subject'];
	if(!$subject)
	{
		$subject = 'Payment Cancelled';
	}
	
	$payment_cancelled_content= stripslashes($tmpdata['payment_cancelled_content']);
		
	if(!$payment_cancelled_content)
	{
		$payment_cancelled_content = '[#post_type#] has been cancelled with transaction id [#transection_id#]';
	}
	
	/* for client */
	$payment_cancelled_content_user=str_replace(array('[#site_name#]','[#to_name#]','[#post_type#]','[#transection_id#]'),array($store_name,$to_name_user,ucfirst(get_post_type($_REQUEST['pid'])),$_REQUEST['trans_id']),$payment_cancelled_content);
	
	/* for admin */
	$payment_cancelled_content_admin=str_replace(array('[#site_name#]','[#to_name#]','[#post_type#]','[#transection_id#]'),array($store_name,$to_name_admin,ucfirst(get_post_type($_REQUEST['pid'])),$_REQUEST['trans_id']),$payment_cancelled_content);
	
	$filecontent1 = $payment_cancelled_content_admin;
	$filecontent2 = $payment_cancelled_content_user;
	
	@templ_send_email($fromEmail,$fromEmailName,$to,$toname,$subject,$filecontent1,''); /* email to admin*/
	if($fromEmail != $to)
	{
		@templ_send_email($to,$toname,$fromEmail,$fromEmailName,$subject,$filecontent2,''); /* email to client */
	}

	}else{
		_e('You are not allowed to access this page.','templatic');	
	}
	global $wpdb;
	$tevolution_post_type = tevolution_get_post_type();
	foreach($tevolution_post_type as $post_type)
	{
		delete_user_meta($current_user->ID,$post_type.'_package_select');
		delete_user_meta($current_user->ID,$post_type.'_list_of_post');
		delete_user_meta($current_user->ID,'package_selected');
		delete_user_meta($current_user->ID,'total_list_of_post');
		$transaction_db_table_name = $wpdb->prefix.'transactions';
		$transaction_insert = 'Delete from '.$transaction_db_table_name.' where trans_id='.wp_kses_post($_REQUEST['trans_id']).' and user_id='.$current_user->ID;
		
		$wpdb->query($transaction_insert);
		/* delete the cancel post from database */
		$delete_post = get_post($_GET['pid']);
		if(isset($_GET['pid']) && $_GET['pid'] !='' && !empty($delete_post) && !isset($_GET['upgrade'])){
		
			wp_delete_post( $_GET['pid']);
			
		}
	}
	?> 
	</div> <!-- content #end -->
</section>
<?php get_sidebar(); ?>
<?php get_footer(); ?>