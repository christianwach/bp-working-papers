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
 * Parse the query
 * 
 * @param bool $has_groups Whether or not this query has found groups
 * @param object $groups_template BuddyPress groups template object
 * @param array $params Array of arguments with which the query was configured
 * @return bool $has_groups Whether or not our modified query has found groups
 */
function bpwpapers_has_groups( $params ) {
	
	// remove this filter to avoid recursion
	remove_filter( 'bp_has_groups', 'bpwpapers_filter_groups', 20 );
	
	// re-query with our params
	$has_groups = bp_has_groups( $params );
	
	// add filter back in
	add_filter( 'bp_has_groups', 'bpwpapers_filter_groups', 20, 3 );

	// fallback
	return $has_groups;
	
}



/**
 * Parse the query
 * 
 * @param bool $has_groups Whether or not this query has found groups
 * @param object $groups_template BuddyPress groups template object
 * @param array $params Array of arguments with which the query was configured
 * @return bool $has_groups Whether or not our modified query has found groups
 */
function bpwpapers_filter_groups( $has_groups, $groups_template, $params ) {
	
	/*
	print_r( array(
		'has_groups' => $has_groups, 
		'groups_template' => $groups_template, 
		'params' => $params, 
	) ); die();
	*/
	
	// do we have our exclude array?
	if ( empty( $params['exclude'] ) ) {
	
		// always exclude working paper groups
		$params['exclude'] = bpwpapers_get_paper_groups();
		
		// remove this filter to avoid recursion
		remove_filter( 'bp_has_groups', 'bpwpapers_filter_groups', 20 );
		
		// re-query with our params
		$has_groups = bp_has_groups( $params );
		
		global $groups_template;
	
		/*
		print_r( array(
			'params' => $params,
			'groups_template' => $groups_template,
		) ); die();
		*/
		
		// add filter back in
		add_filter( 'bp_has_groups', 'bpwpapers_filter_groups', 20, 3 );
	
	} else {
		
		// TODO: merge arrays...
	
	}
	
	// fallback
	return $has_groups;
	
}

// only on front end
if ( ! is_admin() ) {

	// add filter for the above
	add_filter( 'bp_has_groups', 'bpwpapers_filter_groups', 20, 3 );

}



/**
 * Get all BuddyPress Groups that are working paper groups (optionally by user)
 *
 * @param int $user_id The numeric ID of a user
 * @return array $groups The full array of working paper groups
 */
function bpwpapers_get_paper_groups( $user_id = false ) {
	
	// init return
	$groups = array();
	
	// remove this filter to avoid recursion
	remove_filter( 'bp_has_groups', 'bpwpapers_filter_groups', 20 );
	
	// init with unlikely value so we get all
	$params = array(
		'per_page' => 100000,
	);
	
	// did we get a passed in user?
	if ( $user_id !== false ) {
		$params['user_id'] = $user_id;
	}
	
	// construct meta query
	$params['meta_query'] = array(
		'relation' => 'AND',
		array(
			'key' => BP_WORKING_PAPERS_OPTION,
			'value' => 1,
			'type' => 'numeric',
			'compare' => '>'
		)
	);
	
	// get our groups
	$has_groups = bp_has_groups( $params );
	
	global $groups_template;
	
	// did we get any?
	if ( $has_groups ) {
		foreach( $groups_template->groups AS $group ) {
			
			// add it to our array
			$groups[] = $group->id;
			
		}
	}
	
	// add filter back in
	add_filter( 'bp_has_groups', 'bpwpapers_filter_groups', 20, 3 );
	
	/*
	print_r( array(
		'params' => $params,
		'has_groups' => $has_groups,
		'groups_template' => $groups_template,
		'groups' => $groups,
	) ); die();
	*/
	
	// --<
	return $groups;
	
}


	
/**
 * Override the total number of BuddyPress Groups, excluding working paper groups
 *
 * @return int $filtered_count The filtered total number of BuddyPress Groups
 */
function bpwpapers_get_total_group_count() {
	
	// remove filter to prevent recursion
	remove_filter( 'bp_get_total_group_count', 'bpwpapers_get_total_group_count', 20 );
	
	// get actual count
	$actual_count = bp_get_total_group_count();
	
	// get working paper groups
	$bpwpapers_groups = bpwpapers_get_paper_groups();
	
	// calculate
	$filtered_count = $actual_count - count( $bpwpapers_groups );

	// add filter again
	add_filter( 'bp_get_total_group_count', 'bpwpapers_get_total_group_count', 20 );
	
	// --<
	return $filtered_count;

}

// only on front end
if ( ! is_admin() ) {

	// add filter for the above
	add_filter( 'bp_get_total_group_count', 'bpwpapers_get_total_group_count', 20 );

}



/**
 * Override the total number of BuddyPress Groups for a user, excluding working paper groups
 *
 * @return int $filtered_count The filtered total number of BuddyPress Groups for a user
 */
function bpwpapers_get_total_group_count_for_user( $count, $user_id ) {
	
	// remove filter to prevent recursion
	remove_filter( 'bp_get_total_group_count_for_user', 'bpwpapers_get_total_group_count_for_user', 8 );
	
	// get working paper groups for this user
	$bpwpapers_groups = bpwpapers_get_paper_groups( $user_id );
	
	// calculate
	$filtered_count = $count - count( $bpwpapers_groups );

	// add filter again
	add_filter( 'bp_get_total_group_count_for_user', 'bpwpapers_get_total_group_count_for_user', 8, 2 );
	
	// --<
	return $filtered_count;

}

// only on front end
if ( ! is_admin() ) {

	// add filter for the above, before BP applies its number formatting
	add_filter( 'bp_get_total_group_count_for_user', 'bpwpapers_get_total_group_count_for_user', 8, 2 );

}



/**
 * Creates a BuddyPress Group given a title and description
 *
 * @param string $title the title of the BP group
 * @param string $description the description of the BP group
 * @return void
 */
function bpwpapers_create_group( $title, $description, $user_id = null ) {
	
	// if no user passed in, use current user
	if ( is_null( $user_id ) ) $user_id = bp_loggedin_user_id();
	
	// get current time
	$time = current_time( 'mysql' );
	
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
		'date_created' => $time,

	);
	
	// let BuddyPress do the work
	$new_group_id = groups_create_group( $args );
	
	// add some meta
	groups_update_groupmeta( $new_group_id, 'total_member_count', 1 );
	groups_update_groupmeta( $new_group_id, 'last_activity', $time );
	groups_update_groupmeta( $new_group_id, 'invite_status', 'members' );
	
	// --<
	return $new_group_id;
	
}



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

}
	
// sever links just before group is deleted, while meta still exists
//add_action( 'groups_before_delete_group', 'bpwpapers_group_deleted', 10, 1 );




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
 * 
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
 * @return bool True if group has CommentPress groupblog, false otherwise
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
 * Filter media buttons by authoritative groups context
 * 
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
 * Filter quicktags by authoritative groups context
 * 
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



