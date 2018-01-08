<?php
/*
Plugin Name: Autosugest Posts
Plugin URI: https://www.skkills.com/
Description: Add autosugest support for numeric prodcuts sugestion
Author: Ciocoiu Ionut Marius | Skkills.com
Author URI: https://www.skkills.com/
Text Domain: types-autosugest-posts 
Version: 1.0.1
*/

define( 'CIMAUP_VERSION', '1.0.1' );
define( 'CIMAUP_REQUIRED_WP_VERSION', '4.7' );
define( 'CIMAUP_PLUGIN', __FILE__ );
define( 'CIMAUP_PLUGIN_BASENAME', plugin_basename( CIMAUP_PLUGIN ) );
define( 'CIMAUP_PLUGIN_NAME', trim( dirname( CIMAUP_PLUGIN_BASENAME ), '/' ) );
define( 'CIMAUP_PLUGIN_DIR', untrailingslashit( dirname( CIMAUP_PLUGIN ) ) );



/********************************************************************
 ***************************** CONFIG SETUP *************************
 ********************************************************************/

$field_list = array();
$field_list['suggested-product-code'] = array("selector" => '[name="wpcf[suggested-product-code]"]', "post_types" => array("produkter"));
 

/********************************************************************
 ***************************** AJAX SUGGESTION **********************
 ********************************************************************/
 
add_action('wp_ajax_nopriv_cimaup_get_suggested_posts', 'cimaup_ajax_listings');
add_action('wp_ajax_cimaup_get_suggested_posts', 'cimaup_ajax_listings');
 
 
function cimaup_ajax_listings() {
	global $field_list, $wpdb;  
   
	if($_REQUEST['filter_item'] && isset($field_list[$_REQUEST['filter_item']])){
 
	$query_criteria = $field_list[$_REQUEST['filter_item']];
	 
	
	$args = array(
	    'post_type' => $query_criteria['post_types'],
	    's' => $wpdb->esc_like(stripslashes($_REQUEST['query'])),
	    'post_status' => 'publish',
	    'orderby'     => 'title', 
	    'order'       => 'ASC'        
	); 
	
	$wp_query = new WP_Query($args); 
	
	$titles = array();
	
	if ( $wp_query->have_posts() ) {
 
		while ( $wp_query->have_posts() ) {
			$wp_query->the_post();
			$wp_query->post->ID;
			$titles[$wp_query->post->ID]['label'] = addslashes($wp_query->post->post_title) .' (ID: '.$wp_query->post->ID.')';
			$titles[$wp_query->post->ID]['value'] = $wp_query->post->ID;
		}
	 
		wp_reset_postdata();
	}
	 
	echo json_encode($titles);  
	}
	die();  
}


add_action('wp_ajax_nopriv_cimaup_get_post_title', 'cimaup_get_post_by_id');
add_action('wp_ajax_cimaup_get_post_title', 'cimaup_get_post_by_id');
 
 
function cimaup_get_post_by_id() {  
   if(isset($_REQUEST['post_id'])){
		$post = get_post($_REQUEST['post_id']);
		if(isset($post -> ID)){
			echo $post->post_title .' (ID: '.$post->ID.')';
		} 
    }
	die();  
}
  
 
/*********************************************************************
 ******************* ALTER FIELDS FOR AUTOSUGEST *********************
 *********************************************************************/
 
 
if(is_admin()){ 
	 function cimaup_assets() {
		 
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_register_style( 'jquery-ui-styles','//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' );
		wp_enqueue_style( 'jquery-ui-styles' );
	
	 
	}
	add_action('wp_enqueue_scripts', 'cimaup_assets');
	 
	function cimaup_add_autosugest(){
		
		global $field_list, $pagenow;
		 
		if ( in_array($pagenow, array('post.php','post-new.php','page.php','page-new.php' ))) {
			
		 
			 if(is_array($field_list) && count($field_list)){
			 	?> <script> 
			 		jQuery(document).ready(function() {  <?php
					 	foreach($field_list as $field_id => $field){ ?> 
					 		
					 		if(jQuery('<?php echo $field['selector']; ?>').length){
					 			
					 			jQuery('<?php echo $field['selector']; ?>').before('<input type="text" id="cimaup-<?php echo $field_id; ?>" name="cimaup-<?php echo $field_id; ?>" value="" class="wpt-form-textfield form-textfield textfield textfield" />');
					 			
					 			
					 			
					 			if(jQuery('<?php echo $field['selector']; ?>').val() != ""){
					 				 console.log(jQuery('<?php echo $field['selector']; ?>').val());
					 				jQuery.ajax({
											type: 'POST',
											dataType: 'json',
											url: '<?php echo site_url();?>/wp-admin/admin-ajax.php',
											data: 'action=cimaup_get_post_title&post_id='+jQuery('<?php echo $field['selector']; ?>').val(),
											cache: false
									}).always(function(data) {
									    var resp = data; 
									    jQuery('#cimaup-<?php echo $field_id; ?>').val(jQuery.trim(resp.responseText));
									})
					 			}
					 			
					 			jQuery('<?php echo $field['selector']; ?>').hide();
					 			
					 			
					 			jQuery('#cimaup-<?php echo $field_id; ?>').autocomplete({
									source: function(name, response) {
									 
										jQuery.ajax({
											type: 'POST',
											dataType: 'json',
											url: '<?php echo site_url();?>/wp-admin/admin-ajax.php',
											data: 'action=cimaup_get_suggested_posts&filter_item=<?php echo $field_id; ?>&query='+name.term,
											success: function(data) {
												response(data); 
											}
										});
									}, 
								 	select: function(e, ui) { 
								 		e.preventDefault();
								       jQuery('<?php echo $field['selector']; ?>').val(ui.item.value);
								       jQuery('#cimaup-<?php echo $field_id; ?>').val(ui.item.label);
								   },
								   change: function( e, ui ) {
								   	   console.log(ui);
								   	   if(ui.item === null){
								   	   	  jQuery('<?php echo $field['selector']; ?>').val("");
								   	   }
								   },
								   focus: function( e, ui ) {
								   	   console.log(ui);
								   	   if(ui.item === null){
								   	   	  jQuery('<?php echo $field['selector']; ?>').val("");
								   	   }
								   }
								})
								 
					 		}
					 		
					 	<?php }
				?> });</script> <?php
			 }
		}
	}
	add_action( 'admin_footer', 'cimaup_add_autosugest');
 }
 