<?php /*
================================================================================
BuddyPress Working Papers Group Extension
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

This class extends BP_Group_Extension to create the screens our plugin requires.
See: http://codex.buddypress.org/developer/plugin-development/group-extension-api/

--------------------------------------------------------------------------------
*/



// prevent problems during upgrade or when Groups are disabled
if ( !class_exists( 'BP_Group_Extension' ) ) { return; }



/*
================================================================================
Class Name
================================================================================
*/

class BP_Working_Papers_Group_Extension extends BP_Group_Extension {
	
	
	
	/*
	============================================================================
	Properties
	============================================================================
	*/
	
	/*
	// 'public' will show our extension to non-group members
	// 'private' means only members of the group can view our extension
	public $visibility = 'public';
	
	// if our extension does not need a navigation item, set this to false
	public $enable_nav_item = true;
	
	// if our extension does not need an edit screen, set this to false
	public $enable_edit_item = true;
	
	// if our extension does not need an admin metabox, set this to false
	public $enable_admin_item = true;
	
	// the context of our admin metabox. See add_meta_box()
	public $admin_metabox_context = 'core';
	
	// the priority of our admin metabox. See add_meta_box()
	public $admin_metabox_priority = 'normal';
	*/

	// no need for a creation step
	public $enable_create_step = false;
	
	
	
	/** 
	 * @description: initialises this object
	 * @return nothing
	 */
	function __construct() {
		
		// init vars with filters applied
		$name = apply_filters( 'bpwpapers_extension_name', __( 'Working Paper', 'bpwpapers' ) );
		$slug = apply_filters( 'bpwpapers_extension_slug', 'working-paper' );
		$pos = apply_filters( 'bpwpapers_extension_pos', 31 );
		
		// test for BP 1.8+
		// could also use 'bp_esc_sql_order' (the other core addition)
		if ( function_exists( 'bp_core_get_upload_dir' ) ) {
			
			// init array
			$args = array(
				'name' => $name,
				'slug' => $slug,
				'nav_item_position' => $pos,
				'enable_create_step' => false,
			);
			
			// init
			parent::init( $args );
	 
	 	} else {
		
			// name our tab
			$this->name = $name;
			$this->slug = $slug;
		
			// set position in navigation
			$this->nav_item_position = $pos;
		
		}
		
	}
	
	
	
	/**
	 * @description display our content when the nav item is selected
	 */
	function display() {
		
		// hand off to function
		bpwpapers_get_extension_display();
		
	}
	
	
	
	/**
	 * If your group extension requires a meta box in the Dashboard group admin,
	 * use this method to display the content of the metabox
	 *
	 * As in the case of create_screen() and edit_screen(), it may be helpful
	 * to abstract shared markup into a separate method.
	 *
	 * This is an optional method. If you don't need/want a metabox on the group
	 * admin panel, don't define this method in your class.
	 *
	 * @param int $group_id the numeric ID of the group being edited
	 */
	function admin_screen( $group_id ) {
		
		// hand off to function
		//echo bpwpapers_get_extension_admin_screen();

	}
	
	
	
	/**
	 * The routine run after the group is saved on the Dashboard group admin screen
	 *
	 * @param int $group_id the numeric ID of the group being edited
	 */
	function admin_screen_save( $group_id ) {
	
		// Grab your data out of the $_POST global and save as necessary
		
	}
	
	
	
} // class ends



// register our class
bp_register_group_extension( 'BP_Working_Papers_Group_Extension' );



/** 
 * @description: the public extension page redirects to the working paper site
 */
function bpwpapers_get_extension_display() {
	
	// get current group ID
	$group_id = bpwpapers_get_current_group_id();
	
	// sanity check
	if ( ! is_numeric( $group_id ) ) return;
	
	// get blog ID
	$blog_id = bpwpapers_get_blog_by_group_id( $group_id );
	
	// safely get 
	$home_url = ( $blog_id !== false ) ? get_home_url( $blog_id ) : false;
	
	// redirect if we get a home URL for the site
	if ( ! empty( $home_url ) ) {
		wp_redirect( $home_url );
		die();
	}
	
}



/** 
 * @description: get group ID on admin and creation screens
 * @return int $group_id the current group ID
 */
function bpwpapers_get_current_group_id() {

	// access BP global
	global $bp;
	
	// init return
	$group_id = null;
	
	// test for new group ID
	if ( isset( $bp->groups->new_group_id ) ) {
		$group_id = $bp->groups->new_group_id;
		
	// test for current group ID
	} elseif ( isset( $bp->groups->current_group->id ) ) {
		$group_id = $bp->groups->current_group->id;
	}
	
	// --<
	return $group_id;

}



/** 
 * @description: filter media buttons by authoritative groups context
 * @param bool $enabled if media buttons are enabled
 * @return bool $enabled if media buttons are enabled
 */
function bpwpapers_authoritative_group_media_buttons( $allowed ) {
	
	// disallow by default
	$allowed = false;
	
	// is this user a member of an auth group on this blog?
	if ( bpwpapers_is_authoritative_group_member() ) {
		
		// allow
		return true;
	
	}
	
	// --<
	return $allowed;
	
}

// add filter for the above
//add_filter( 'commentpress_rte_media_buttons', 'bpwpapers_authoritative_group_media_buttons', 10, 1 );



/** 
 * @description: filter quicktags by authoritative groups context
 * @param array $quicktags the quicktags
 * @return array/bool $quicktags false if quicktags are disabled, array of buttons otherwise
 */
function bpwpapers_authoritative_group_quicktags( $quicktags ) {
	
	// disallow quicktags by default
	$quicktags = false;

	// is this user a member of an auth group on this blog?
	if ( bpwpapers_is_authoritative_group_member() ) {
		
		// allow quicktags
		$quicktags = array(
			'buttons' => 'strong,em,ul,ol,li,link,close'
		);

		// --<
		return $quicktags;
	
	}
	
	// --<
	return $quicktags;
	
}

// add filter for the above
//add_filter( 'commentpress_rte_quicktags', 'bpwpapers_authoritative_group_quicktags', 10, 1 );



