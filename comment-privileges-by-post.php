<?php
/*
Plugin Name: Comment Privileges By Post
Plugin URI: http://www.berriart.com/en/comment-privileges-by-post/
Description: Allows forcing users to register and login individually on each post or page. Also, if you are already forcing users to login you may open comments in any post or page.
Version: 1.0
Author: Alberto Varela
Author URI: http://www.berriart.com
License: GPL2
*/

/*  Copyright 2011  Alberto Varela  (email : alberto@berriart.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

    Althought you may obtain a copy of the License at

    http://www.gnu.org/licenses/gpl-2.0.html
*/

// Internationalizing the plugin 
$currentLocale = get_locale();
if(!empty($currentLocale)) 
{
  $moFile = dirname(__FILE__) . '/lang/' . $currentLocale . '.mo';
  if(@file_exists($moFile) && is_readable($moFile)) load_textdomain('comment-privileges-by-post', $moFile);
}


// Add actions and filters
add_action( 'add_meta_boxes', 'comment_privileges_by_post_add_meta_box' );
add_action( 'save_post', 'comment_privileges_by_post_save_postdata' );
add_filter( 'option_comment_registration', 'comment_privileges_by_post_filter' );


// The function to add the meta box
if ( !function_exists('comment_privileges_by_post_add_meta_box') ):
function comment_privileges_by_post_add_meta_box($post_type)
{
  if ( post_type_supports($post_type, 'comments') )
	add_meta_box('commentprivilegesbypostdiv', __('Comments privileges', 'comment-privileges-by-post'), 'comment_privileges_by_post_meta_box', $post_type, 'normal', 'core');
}
endif;


// The meta box
if ( !function_exists('comment_privileges_by_post_meta_box') ):
function comment_privileges_by_post_meta_box($post) {

    // Use nonce for verification
    wp_nonce_field( plugin_basename(__FILE__), 'comment_privileges_by_post_noncename' );

    // If default is closed...
    if( get_option('comment_registration') )
    {
        $value = 'open';
        $label = __('Allow users to comment without login', 'comment-privileges-by-post');
    }
    // If default is open...
    else
    {
        $value = 'protected';
        $label = __('Users must be registered and logged in to comment', 'comment-privileges-by-post');
    }
?>
<p class="meta-options">
	<label for="comment_privileges_by_post_comment_registration" class="selectit"><input name="comment_privileges_by_post_comment_registration" type="checkbox" id="comment_privileges_by_post_comment_registration" value="<?php echo $value; ?>" <?php checked(get_post_meta($post->ID, '_comment_privileges_by_post_comment_registration', true), $value); ?> /> <?php echo $label; ?></label>
</p>
<?php
}
endif;


// When the post is saved, saves our custom data
function comment_privileges_by_post_save_postdata( $post_id ) {

  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times

  if ( !wp_verify_nonce( $_POST['comment_privileges_by_post_noncename'], plugin_basename(__FILE__) ) )
      return $post_id;

  // verify if this is an auto save routine.
  // If it is our form has not been submitted, so we dont want to do anything
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
      return $post_id;


  // Check permissions
  if ( 'page' == $_POST['post_type'] )
  {
    if ( !current_user_can( 'edit_page', $post_id ) )
        return $post_id;
  }
  else
  {
    if ( !current_user_can( 'edit_post', $post_id ) )
        return $post_id;
  }

  // OK, we're authenticated: we need to find and save the data

  $old = get_post_meta($post_id, '_comment_privileges_by_post_comment_registration', true);
  $new = $_POST['comment_privileges_by_post_comment_registration'];

  if ($new && $new != $old) {
      update_post_meta($post_id, '_comment_privileges_by_post_comment_registration', $new);
  } elseif ('' == $new && $old) {
      delete_post_meta($post_id, '_comment_privileges_by_post_comment_registration', $old);
  }

}


// The filter to force login when the option is enabled
if ( !function_exists('comment_privileges_by_post_filter') ):
function comment_privileges_by_post_filter($option)
{
    global $post;

    // Confirm that we are getting the option in a single or a page
    if( is_single() || is_page() )
    {
        // If default is open...
        if( 0 == $option && 'protected' == get_post_meta($post->ID, '_comment_privileges_by_post_comment_registration', true))
        {
           $option = 1;
        }
        // If default is closed...
        elseif( 1 == $option && 'open' == get_post_meta($post->ID, '_comment_privileges_by_post_comment_registration', true))
        {
           $option = 0;
        }
    }

    return $option;
}
endif;

