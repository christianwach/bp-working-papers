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
 * For a given blog ID, get the group ID
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
 * For a given group ID, get the blog ID
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
 * For a given blog ID, add a given group ID
 *
 * @param int $blog_id the numeric ID of the blog
 * @param int $group_id the numeric ID of the group
 */
function bpwpapers_add_group_to_blog( $blog_id, $group_id ) {

	// save option
	update_site_option( BP_WORKING_PAPERS_PREFIX . $blog_id, $group_id );
	
}



/** 
 * For a given group ID, add a given blog ID
 *
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
 */
function bpwpapers_add_blog_to_group( $group_id, $blog_id ) {

	// save updated option
	groups_update_groupmeta( $group_id, BP_WORKING_PAPERS_OPTION, $blog_id );
	
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



