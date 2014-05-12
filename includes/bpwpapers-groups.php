<?php /*
================================================================================
BuddyPress Working Papers Group Functions
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

--------------------------------------------------------------------------------
*/



/**
 * Configure the custom subnav item for a Working Paper group
 */
function bpwpapers_group_setup_nav() {

	// kick out if not a group
	if ( ! bp_is_group() ) return;
	
	// get current group
	$group = buddypress()->groups->current_group;
	//print_r( $group ); die();
	
	// kick out if the current user doesn't have access
	if ( ( 'public' !== $group->is_visible ) AND ! $group->user_has_access ) return;
	
	// kick out if this isn't a working site group
	if ( ! bpwpapers_group_has_working_paper( $group->id ) ) return;
	
	// construct args for subnav item	
	$args = array(
		'name'            => apply_filters( 'bpwpapers_extension_name', __( 'Working Paper', 'bpwpapers' ) ),
		'slug'            => apply_filters( 'bpwpapers_extension_slug', 'working-paper' ),
		'parent_slug'     => bp_get_current_group_slug(),
		'parent_url'      => bp_get_group_permalink( groups_get_current_group() ),
		'position'        => apply_filters( 'bpwpapers_extension_pos', 31 ),
		'item_css_id'     => 'nav-' . apply_filters( 'bpwpapers_extension_slug', 'working-paper' ),
		'screen_function' => 'bpwpapers_redirect_to_site',
		'user_has_access' => $group->user_has_access
	);
	//print_r( $args ); die();
	
	// let's go
	bp_core_new_subnav_item( $args );
	
}
add_action( 'bp_setup_nav', 'bpwpapers_group_setup_nav' );



/** 
 * Redirects to the working paper site
 */
function bpwpapers_redirect_to_site() {
	
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
 * Get group ID outside the groups loop
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
 * Check if a group has a Working Paper
 *
 * @return boolean True if group has CommentPress groupblog, false otherwise
 */
function bpwpapers_group_has_working_paper( $group_id = null ) {

	// did we get a specific group passed in?
	if ( is_null( $group_id ) ) {
		
		// no, use BP API
		$group_id = bp_get_current_group_id();
		
		// unlikely, but if we don't get one...
		if ( empty( $group_id ) ) {
		
			// try and get ID from BP
			global $bp;
			
			if ( isset( $bp->groups->current_group->id ) ) {
				$group_id = $bp->groups->current_group->id;
			}
		
		}
		
	}
	
	//print_r( $group_id ); die();
	
	// how did we do?
	if ( !empty( $group_id ) AND is_numeric( $group_id ) ) {
	
		// get blog ID
		$blog_id = bpwpapers_get_blog_by_group_id( $group_id );
		
		// is it a working paper?
		if ( $blog_id !== false ) {
		
			// yes
			return true;
		
		}
		
	}
	
	// --<
	return false;
	
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



