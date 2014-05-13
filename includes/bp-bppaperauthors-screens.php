<?php

/**
 * BuddyPress Working Papers Author Screens
 */



// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;



/** Theme Compatability *******************************************************/

/**
 * The main theme compat class for BuddyPress Working Papers
 *
 * This class sets up the necessary theme compatability actions to safely output
 * group template parts to the_title and the_content areas of a theme.
 */
class BP_Working_Paper_Authors_Theme_Compat {
	
	
	
	/**
	 * Set up theme compatibility for the BuddyPress Working Papers component.
	 */
	public function __construct() {
		
		// add theme comaptibility action
		add_action( 'bp_setup_theme_compat', array( $this, 'is_bppaperauthors' ) );
		
	}
	
	
	
	/**
	 * Are we looking at something that needs BuddyPress Working Paper Authors theme compatability?
	 */
	public function is_bppaperauthors() {
		
		// Bail if not looking at a working paper component page
		if ( ! bp_is_bppaperauthors_component() ) { return; }
		
		// BuddyPress Working Papers Directory
		if ( is_multisite() && ! bp_current_action() ) {
		
			// set is_directory flag
			bp_update_is_directory( true, 'bppaperauthors' );
			
			// inform plugins
			do_action( 'bp_blogs_screen_index' );
			
			// add hooks
			add_filter( 'bp_get_buddypress_template',                array( $this, 'directory_template_hierarchy' ) );
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'directory_content'    ) );
			
		}
		
	}
	
	
	
	/**
	 * Add template hierarchy to theme compat for the BuddyPress Working Papers directory page.
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates().
	 * @return array $templates Array of custom templates to look for.
	 */
	public function directory_template_hierarchy( $templates ) {
	
		//die('here');

		// set up our templates based on priority
		$new_templates = apply_filters( 'bp_template_hierarchy_bppaperauthors_directory', array(
			'bppaperauthors/index-directory.php'
		) );

		// merge new templates with existing stack
		// @see bp_get_theme_compat_templates()
		$templates = array_merge( (array) $new_templates, $templates );
		
		// --<
		return $templates;
		
	}
	
	
	
	/**
	 * Update the global $post with directory data.
	 *
	 * @since BuddyPress (1.7.0)
	 */
	public function directory_dummy_post() {

		// set title
		$title = apply_filters( 'bppaperauthors_extension_plural', __( 'Working Paper Authors', 'bpwpapers' ) );
		
		// create dummy post
		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => $title,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_bppaperauthors',
			'post_status'    => 'publish',
			'is_page'        => true,
			'comment_status' => 'closed'
		) );
		
	}
	
	
	
	/**
	 * Filter the_content with the BuddyPress Working Papers index template part.
	 */
	public function directory_content() {
		
		// --<
		return bp_buffer_template_part( 'bppaperauthors/index', null, false );
		
	}
	
	
	
} // class ends



// init
new BP_Working_Papers_Theme_Compat();



/**
 * Load the top-level BuddyPress Working Papers directory.
 */
function bppaperauthors_screen_index() {
	
	// is this our component page?
	if ( is_multisite() && bp_is_bppaperauthors_component() && !bp_current_action() ) {
		
		// make sure BP knows that it's our directory
		bp_update_is_directory( true, 'bppaperauthors' );
		
		// allow plugins to handle this
		do_action( 'bppaperauthors_screen_index' );
		
		// load our directory template
		bp_core_load_template( apply_filters( 'bppaperauthors_screen_index', 'bppaperauthors/index' ) );

	}
	
}

// add action for the above
add_action( 'bp_screens', 'bppaperauthors_screen_index', 20 );



