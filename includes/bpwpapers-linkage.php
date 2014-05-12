<?php /*
================================================================================
BuddyPress Working Papers Functions
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

Logic functions which don't need to be in the loop.

--------------------------------------------------------------------------------
*/



/**
 * Creates a BuddyPress Group given a title and description
 *
 * @param string $title the title of the BP group
 * @param string $description the description of the BP group
 * @return nothing
 */
function bpwpapers_create_group( $title, $description, $user_id = null ) {
	
	// if no user passed in, use current user
	if ( is_null( $user_id ) ) $user_id = bp_loggedin_user_id();
	
	/**
	 * Possible parameters (see function groups_create_group):
	 *	'group_id'
	 *	'creator_id'
	 *	'name'
	 *	'description'
	 *	'slug'
	 *	'status'
	 *	'enable_forum'
	 *	'date_created'
	 */
	$args = array(
		
		// group_id is not passed so that we create a group
		'creator_id' => $user_id,
		'name' => $title,
		'description' => $description,
		'slug' => groups_check_slug( sanitize_title( esc_attr( $title ) ) ),
		'status' => 'public',
		'enable_forum' => 0,
		'date_created' => current_time( 'mysql' ),

	);
	
	// let BuddyPress do the work
	$new_group_id = groups_create_group( $args );
	
	// add some meta
	groups_update_groupmeta( $new_group_id, 'total_member_count', 1 );
	groups_update_groupmeta( $new_group_id, 'last_activity', time() );
	groups_update_groupmeta( $new_group_id, 'invite_status', 'members' );
	
	// --<
	return $new_group_id;
	
}



/*
 * Creates a BuddyPress Group Membership given a title and description
 *
 * @param int $group_id the numeric ID of the BP group
 * @param int $user_id the numeric ID of the WP user
 * @param bool $is_admin makes this member a group admin
 * @return bool $success or not...
 */
function bpwpapers_create_group_member( $group_id, $user_id, $is_admin = 0 ) {
	
	// User is already a member, just return true
	if ( groups_is_user_member( $user_id, $group_id ) ) return true;
	
	// set up member
	$new_member = new BP_Groups_Member;
	$new_member->group_id = $group_id;
	$new_member->user_id = $user_id;
	$new_member->inviter_id = 0;
	$new_member->is_admin = $is_admin;
	$new_member->user_title = '';
	$new_member->date_modified = bp_core_current_time();
	$new_member->is_confirmed = 1;
	
	// save the membership
	if ( ! $new_member->save() ) return false;
	
	// --<
	return true;
	
}



/** 
 * For a given blog ID, get the array of group IDs
 *
 * @param int $blog_id the numeric ID of the blog
 */
function bpwpapers_get_group_by_blog_id( $blog_id ) {

	// construct option name
	$option_name = BP_WORKING_PAPERS_PREFIX . $blog_id;
	
	// return option if it exists
	return get_site_option( $option_name, false );
	
}



/** 
 * For a given group ID, add a given group ID
 *
 * @param int $group_id the numeric ID of the group
 */
function bpwpapers_get_blog_by_group_id( $group_id ) {

	// get option if it exists
	$blog_id = groups_get_groupmeta( $group_id, BP_WORKING_PAPERS_OPTION );
	
	// sanity check
	if ( !is_numeric( $blog_id ) ) { $blog_id = false; }
	
	// --<
	return $blog_id;
	
}



/**
 * Reciprocal addition of IDs
 *
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
 */
function bpwpapers_link_blog_and_group( $blog_id, $group_id ) {

	// set blog options
	bpwpapers_configure_blog_options( $blog_id );

	// add to blog's option
	bpwpapers_add_group_to_blog( $blog_id, $group_id );

	// add to group's option
	bpwpapers_add_blog_to_group( $group_id, $blog_id );
	
}



/**
 * Reciprocal deletion of IDs
 *
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
 */
function bpwpapers_unlink_blog_and_group( $blog_id, $group_id ) {

	// remove from blog's option
	bpwpapers_remove_group_from_blog( $blog_id, $group_id );

	// remove from group's option
	bpwpapers_remove_blog_from_group( $group_id, $blog_id );

	// unset blog options
	bpwpapers_reset_blog_options( $blog_id );

}



/**
 * Set comment registration
 *
 * @param int $blog_id the numeric ID of the blog
 */
function bpwpapers_configure_blog_options( $blog_id ) {

	// kick out if already a working paper
	if ( bpwpapers_is_working_paper( $blog_id ) ) return;

	// go there
	switch_to_blog( $blog_id );
	
	// get existing comment_registration option
	$existing_option = get_option( 'comment_registration', 0 );
	
	// store it for later
	add_option( 'bpwpapers_saved_comment_registration', $existing_option );

	// anonymous commenting - off by default
	$anon_comments = apply_filters( 
		'bpwpapers_require_comment_registration', 
		0 // disallow
	);
	
	// update option
	update_option( 'comment_registration', $anon_comments );
	
	// switch back
	restore_current_blog();
	
}



/**
 * Unset comment registration
 *
 * @param int $blog_id the numeric ID of the blog
 */
function bpwpapers_reset_blog_options( $blog_id ) {

	// kick out if still a working paper
	if ( bpwpapers_is_working_paper( $blog_id ) ) return;

	// go there
	switch_to_blog( $blog_id );

	// get saved comment_registration option
	$previous_option = get_option( 'bpwpapers_saved_comment_registration', 0 );
	
	// remove our saved one
	delete_option( 'bpwpapers_saved_comment_registration' );

	// update option
	update_option( 'comment_registration', $previous_option );
	
	// switch back
	restore_current_blog();
	
}



/** 
 * For a given blog ID, add a given group ID
 *
 * @param int $blog_id the numeric ID of the blog
 * @param int $group_id the numeric ID of the group
 */
function bpwpapers_add_group_to_blog( $blog_id, $group_id ) {

	// get existing group ID
	$group_id = bpwpapers_get_group_by_blog_id( $blog_id );
	
	// if we don't already have one...
	if ( $group_id !== false ) {
	
		// save option
		update_site_option( BP_WORKING_PAPERS_PREFIX . $blog_id, $group_id );
	
	}
	
}



/** 
 * For a given group ID, add a given blog ID
 *
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
 */
function bpwpapers_add_blog_to_group( $group_id, $blog_id ) {

	// get existing blog ID
	$blog_id = bpwpapers_get_blog_by_group_id( $group_id );
	
	// if we don't already have one...
	if ( $blog_id !== false ) {
	
		// save updated option
		groups_update_groupmeta( $group_id, BP_WORKING_PAPERS_OPTION, $blog_id );
	
	}
	
}



/** 
 * For a given blog ID, remove a given group ID
 *
 * @param int $blog_id the numeric ID of the blog
 * @param int $group_id the numeric ID of the group
 */
function bpwpapers_remove_group_from_blog( $blog_id, $group_id ) {

	// delete the site option
	delete_site_option( BP_WORKING_PAPERS_PREFIX . $blog_id );

}



/** 
 * For a given group ID, remove a given blog ID
 *
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
 */
function bpwpapers_remove_blog_from_group( $group_id, $blog_id ) {

	// delete group option
	groups_delete_groupmeta( $group_id, BP_WORKING_PAPERS_OPTION );

}



/**
 * Sever link and delete group when a site gets deleted
 *
 * @param int $blog_id the numeric ID of the blog
 */
function bpwpapers_blog_deleted( $blog_id, $drop = false ) {

	// get existing group ID
	$group_id = bpwpapers_get_group_by_blog_id( $blog_id );

	// sanity check
	if ( is_numeric( $group_id ) ) {
	
		// delete group meta
		bpwpapers_remove_blog_from_group( $group_id, $blog_id );
		
	}
	
	// delete the site option
	delete_site_option( BP_WORKING_PAPERS_PREFIX . $blog_id );
	
	// TODO: delete the group

}

// sever links when site deleted
add_action( 'delete_blog', 'bpwpapers_blog_deleted', 10, 1 );
	


/**
 * Sever link and delete blog before a group gets deleted so we can still access meta
 *
 * @param int $group_id the numeric ID of the group
 */
function bpwpapers_group_deleted( $group_id ) {

	// get array of blog IDs
	$blog_id = bpwpapers_get_blog_by_group_id( $group_id );

	// sanity check
	if ( $blog_id !== false ) {
	
		// delete site option
		bpwpapers_remove_group_from_blog( $blog_id, $group_id );
		
	}
	
	// our option will be deleted by groups_delete_group()

	// TODO: delete the blog

}
	
// sever links just before group is deleted, while meta still exists
add_action( 'groups_before_delete_group', 'bpwpapers_group_deleted', 10, 1 );




/** 
 * Check if blog is a groupblog
 *
 * @param int $blog_id the numeric ID of the blog
 */
function bpwpapers_is_groupblog( $blog_id ) {

	// init return
	$return = false;

	// do we have groupblogs enabled?
	if ( function_exists( 'get_groupblog_group_id' ) ) {
	
		// yes, get group id
		$group_id = get_groupblog_group_id( $blog_id );
		
		// is this blog a groupblog? 
		if ( is_numeric( $group_id ) ) { $return = true; }
		
	}
	
	// --<
	return $return;
	
}



/** 
 * Check if blog is a working paper
 *
 * @param int $blog_id the numeric ID of the blog
 */
function bpwpapers_is_working_paper( $blog_id ) {

	// init return
	$return = false;

	// get group for this site
	$group_id = bpwpapers_get_group_by_blog_id( $blog_id );
	
	// if we have a group ID, then it is
	if ( $group_id !== false ) { 
		$return = true; 
	}
	
	// --<
	return $return;
	
}



