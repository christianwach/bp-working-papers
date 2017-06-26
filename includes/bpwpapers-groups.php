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
 * Query all groups.
 *
 * @since 0.1
 *
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
 * Parse the query passed to bp_has_groups() and exclude paper groups.
 *
 * @since 0.1
 *
 * @param bool $has_groups Whether or not this query has found groups
 * @param object $groups_template BuddyPress groups template object
 * @param array $params Array of arguments with which the query was configured
 * @return bool $has_groups Whether or not our modified query has found groups
 */
function bpwpapers_filter_groups( $has_groups, $groups_template, $params ) {

	// kick out if type is invites
	//if ( $params['type'] == 'invites' ) return $has_groups;

	/*
	trigger_error( print_r( array(
		'has_groups' => $has_groups,
		'groups_template' => $groups_template,
		'params' => $params,
	), true ), E_USER_ERROR ); die();
	*/

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

// only on front end OR ajax
if ( ! is_admin() OR ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) ) {

	// add filter for the above
	add_filter( 'bp_has_groups', 'bpwpapers_filter_groups', 20, 3 );

}



/**
 * Filter out working paper groups from the BuddyPress Event Organiser metabox.
 *
 * @since 0.1
 *
 * @param bool $reject FALSE by default (return TRUE to reject the item)
 * @param object $item The item to be displayed in the metabox
 * @return bool $reject The overridden rejection/acceptance flag
 */
function bpwpapers_event_organiser_metabox_filter( $reject, $item ) {

	//print_r( $item ); die();

	// reject if this group is a working paper group
	if ( bpwpapers_group_has_working_paper( $item->id ) ) return true;

}

// add filter for the above
add_filter( 'bp_event_organiser_reject_item', 'bpwpapers_event_organiser_metabox_filter', 20, 2 );



/**
 * Get all BuddyPress Groups that are working paper groups. (optionally by user)
 *
 * @since 0.1
 *
 * @param int $user_id The numeric ID of a user
 * @return array $groups The full array of working paper groups
 */
function bpwpapers_get_paper_groups( $user_id = 0 ) {

	// init return
	$groups = array();

	// init with unlikely value so we get all
	$params = array(
		'type' => 'alphabetical',
		'per_page' => 100000,
	);

	// did we get a passed in user?
	if ( $user_id !== 0 AND $user_id !== false ) {
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

	//$groups_template = new BP_Groups_Template( $params );

	// get our groups
	$has_groups = bpwpapers_has_groups( $params );

	global $groups_template;

	// did we get any?
	if ( $has_groups ) {
		foreach( $groups_template->groups AS $group ) {

			// add it to our array
			$groups[] = $group->id;

		}
	}

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
 * Override the total number of BuddyPress Groups, excluding working paper groups.
 *
 * @since 0.1
 *
 * @return int $filtered_count The filtered total number of BuddyPress Groups
 */
function bpwpapers_get_total_group_count() {

	// remove filter to prevent recursion
	remove_filter( 'bp_get_total_group_count', 'bpwpapers_get_total_group_count', 8 );

	// get actual count
	$actual_count = bp_get_total_group_count();

	// get working paper groups
	$bpwpapers_groups = bpwpapers_get_paper_groups();

	// calculate
	$filtered_count = $actual_count - count( $bpwpapers_groups );

	// add filter again
	add_filter( 'bp_get_total_group_count', 'bpwpapers_get_total_group_count', 8 );

	// --<
	return $filtered_count;

}

// only on front end OR ajax
if ( ! is_admin() OR ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) ) {

	// add filter for the above
	add_filter( 'bp_get_total_group_count', 'bpwpapers_get_total_group_count', 8 );

}



/**
 * Override the total number of BuddyPress Groups for a user, excluding working paper groups.
 *
 * @since 0.1
 *
 * @return int $filtered_count The filtered total number of BuddyPress Groups for a user
 */
function bpwpapers_get_total_group_count_for_user( $count, $user_id ) {

	// get working paper groups for this user
	$bpwpapers_groups = bpwpapers_get_paper_groups( $user_id );

	// calculate
	$filtered_count = $count - count( $bpwpapers_groups );

	// --<
	return $filtered_count;

}

// only on front end OR ajax
if ( ! is_admin() OR ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) ) {

	// add filter for the above, before BP applies its number formatting
	add_filter( 'bp_get_total_group_count_for_user', 'bpwpapers_get_total_group_count_for_user', 8, 2 );

}



/**
 * Creates a BuddyPress Group given a title and description.
 *
 * @since 0.1
 *
 * @param string $title the title of the BP group
 * @param string $description the description of the BP group
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
 * Sever link and delete blog before a group gets deleted so we can still access meta.
 *
 * @since 0.1
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




/**
 * Creates a BuddyPress Group Membership given a title and description.
 *
 * @since 0.1
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
 * Configure the custom subnav item for a Working Paper group.
 *
 * @since 0.1
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
		'name'            => bpwpapers_extension_name(),
		'slug'            => bpwpapers_extension_slug(),
		'parent_slug'     => bp_get_current_group_slug(),
		'parent_url'      => bp_get_group_permalink( groups_get_current_group() ),
		'position'        => apply_filters( 'bpwpapers_extension_pos', 31 ),
		'item_css_id'     => 'nav-' . bpwpapers_extension_slug(),
		'screen_function' => 'bpwpapers_redirect_to_site',
		'user_has_access' => $group->user_has_access
	);
	//print_r( $args ); die();

	// let's go
	bp_core_new_subnav_item( $args );

}
add_action( 'bp_setup_nav', 'bpwpapers_group_setup_nav' );



/**
 * Redirects to the working paper site.
 *
 * @since 0.1
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
 * Get group ID outside the groups loop.
 *
 * @since 0.1
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
 * Check if a group has a Working Paper.
 *
 * @since 0.1
 *
 * @return bool True if group has working paper, false otherwise
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
 * Override the group permalink.
 *
 * @since 0.1
 *
 * @param string $permalink The permalink of the group on the main site
 * @return string $permalink The permalink of the group on the working paper site
 */
function bpwpapers_get_group_permalink( $permalink ) {

	// bail if not joining
	if ( ! isset( $_GET['bpwpaper_group'] ) ) return $permalink;
	if ( $_GET['bpwpaper_group'] != 'true' ) return $permalink;

	// get calling page
	$url = parse_url( $_SERVER['HTTP_REFERER'] );
	$scheme = isset( $url['scheme'] ) ? $url['scheme'] . '://' : '';
	$host = isset( $url['host'] ) ? $url['host'] : '';
	$path = isset( $url['path'] ) ? $url['path'] : '';

	// construct link to calling page
	$new_permalink = $scheme . $host . $path;

	// did we get a caller?
	if ( isset( $_GET['bpwpaper_caller'] ) AND $_GET['bpwpaper_caller'] != '' ) {

		// add text sig
		$new_permalink .= '#' . trim( $_GET['bpwpaper_caller'] );

	}

	// allow plugin overrides
	return apply_filters( 'bpwpapers_get_group_permalink', $new_permalink );

}



/**
 * Enable the override of the group permalink only once user has joined group.
 *
 * @since 0.1
 *
 * @param int $blog_id the numeric ID of the blog
 * @param int $group_id the numeric ID of the group
 */
function bpwpapers_enable_group_permalink_filter( $group_id, $user_id ) {

	// bail if AJAX posting
	if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) ) return;

	// bail if not joining one of our groups
	if ( ! isset( $_GET['bpwpaper_group'] ) ) return;
	if ( $_GET['bpwpaper_group'] != 'true' ) return;

	// add filter for overriding the group permalink
	add_filter( 'bp_get_group_permalink', 'bpwpapers_get_group_permalink', 20, 1 );

}

// add action for the above
add_action( 'groups_join_group', 'bpwpapers_enable_group_permalink_filter', 20, 2 );



/**
 * Filter media buttons by authoritative groups context.
 *
 * @since 0.1
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
 * Filter quicktags by authoritative groups context.
 *
 * @since 0.1
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



