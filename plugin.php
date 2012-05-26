<?php
/* 
Plugin Name: JDesrosiers Page Redirect
Plugin URI: 
Description: Simple WordPress plugin to redirect pages to other pages
Author: Julien Desrosiers
Version: 1.0 
Author URI: http://www.juliendesrosiers.com
*/

define('JDPR_NONCE', 'jdprredirectedpageidmeta_noncename');
define('JDPR_POST_META', 'jdpr_redirected_page_id');

// Meta box
// -----------------------------------------------------------------

function jdpr_add_language_metaboxe() {
  add_meta_box('jdpr_redirected_page_id', __('Page Redirect', 'jdpr'), 'jdpr_redirected_page_id', 'page', 'normal', 'default');
}

// The Post's corresponding post id Metabox
function jdpr_redirected_page_id() {
  global $post;
  echo '<input type="hidden" name="'. JDPR_NONCE .'" '
   . 'id="'. JDPR_NONCE .'" value="'
   . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

  // Get the redirected page id data if its already been entered
  $redirected_page_id = get_post_meta($post->ID, '_'.JDPR_POST_META, true);
  
  // Echo out the field
  $get_posts_conditions = array(
    'numberposts' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'post_type' => 'page',
    'post_status' => 'publish',
    'exclude' => $post->ID
  );
  $pages = get_pages($get_posts_conditions);
  $html = '<p><label for="'. JDPR_POST_META .'"><strong>'. __('Choose the destination page to redirect', 'jdpr') .'</strong></label></p>';
  $html .= '<p><select name="_'. JDPR_POST_META .'" id="'. JDPR_POST_META .'">';
  $html .= '<option value="">'. __('[Select a page]', 'jdpr') .'</option>';
  foreach ($pages as $p) {
    $html .= '<option value="'. $p->ID .'" ';
    if ($redirected_page_id && $redirected_page_id == $p->ID) {
      $html .= ' selected="selected" ';
    }
    $html .= ' >'. $p->post_title .'</option>';
  }
  $html .= '</select></p>';

  echo $html;
}

// Save the metabox data
function jdpr_save_post_meta($post_id, $post) {
  global $jdpr_post_types;
  // if we're not in a jdpr-enabled post type, skip.
  if (in_array($post->post_type, $jdpr_post_types)) return $post;
  // verify this came from our screen and with proper authorization,
  // because save_post can be triggered at other times
  if (empty($_POST['_'.JDPR_POST_META]) || empty($_POST[JDPR_NONCE]) || !wp_verify_nonce($_POST[JDPR_NONCE], plugin_basename(__FILE__)) ) {
    return $post->ID;
  }
  // Is the user allowed to edit the pages? 
  if (!current_user_can('edit_page', $post->ID)) {
    return $post->ID;
  }
  // OK, we're authenticated: we need to find and save the data
  // We'll put it into an array to make it easier to loop though.
  $post_meta['_'.JDPR_POST_META] = $_POST['_'.JDPR_POST_META];
  // Add values of $post_meta as custom fields
  foreach ($post_meta as $key => $value) { // Cycle through the $post_meta array!
    // if ($post->post_type == 'revision') return; // Don't store custom data twice
    $value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
    update_post_meta($post->ID, $key, $value); // (will add it if not already present)
    if (!$value) delete_post_meta($post->ID, $key); // Delete if blank
  }
}


