<?php /*
================================================================================
BuddyPress Working Papers Blogs Template
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

We extend the BuddyPress BP Blogs template class so that we can filter by group
association, whilst retaining useful stuff like pagination.

--------------------------------------------------------------------------------
*/



/**
 * Query only working paper blogs
 * 
 * @param array $args Array of arguments with which the query was configured
 * @return bool $has_blogs Whether or not our modified query has found blogs
 */
function bpwpapers_has_blogs( $args = '' ) {
	
	// remove default exclusion filter
	remove_filter( 'bp_has_blogs', 'bpwpapers_filter_papers', 20 );
	
	// user filtering
	$user_id = 0;
	if ( bp_displayed_user_id() ) {
		$user_id = bp_displayed_user_id();
	}
	
	// get paper IDs
	$papers = bpwpapers_get_papers();
	
	//print_r( $args ); die();
	
	// declare defaults
	$defaults = array(
		'type'         => 'active',
		'page'         => 1,
		'per_page'     => 20, // set large default so we avoid pagination
		'max'          => false,
		'page_arg'     => 'bpage',
		'user_id'      => $user_id,
		'include_blog_ids'  => $papers,
		'search_terms' => null,
		'update_meta_cache' => true,
	);
	
	// parse args
	$parsed_args = wp_parse_args( $args, $defaults );

	// re-query with our params
	$has_blogs = bp_has_blogs( $parsed_args );
	
	// add exclusion filter back as default
	add_filter( 'bp_has_blogs', 'bpwpapers_filter_papers', 20, 3 );
	
	// fallback
	return $has_blogs;
	
}



/**
 * Intercept the bp_has_blogs() query and exclude working paper sites
 * 
 * @param bool $has_blogs Whether or not this query has found blogs
 * @param object $blogs_template BuddyPress blogs template object
 * @param array $params Array of arguments with which the query was configured
 * @return bool $has_blogs Whether or not this query has found blogs
 */
function bpwpapers_filter_papers( $has_blogs, $blogs_template, $params ) {
	
	// get paper IDs
	$papers = bpwpapers_get_papers();
	
	// get all blogs via BP_Blogs_Blog
	$all = BP_Blogs_Blog::get_all();
	
	// init ID array
	$blog_ids = array();
	
	if ( is_array( $all['blogs'] ) AND count( $all['blogs'] ) > 0 ) {
		foreach ( $all['blogs'] AS $blog ) {
			$blog_ids[] = $blog->blog_id;
		}
	}
	
	/*
	print_r( array( 
		'method' => 'before', 
		'params' => $params, 
		'papers_excluded' => $papers_excluded,
	) ); //die();
	*/
	
	// let's exclude papers
	$papers_excluded = array_merge( array_diff( $blog_ids, $papers ) );
	
	// do we have an array of blogs to include?
	if ( isset( $params['include_blog_ids'] ) AND ! empty( $params['include_blog_ids'] ) ) {
		
		// convert from comma-delimited if needed
		$include_blog_ids = array_filter( wp_parse_id_list( $params['include_blog_ids'] ) );
	
		// exclude papers
		$params['include_blog_ids'] = array_merge( array_diff( $include_blog_ids, $papers ) );
		
		// if we have none left, return false
		if ( count( $params['include_blog_ids'] ) === 0 ) return false;
	
	} else {
	
		// exclude papers
		$params['include_blog_ids'] = $papers_excluded;
		
	}
	
	/*
	print_r( array( 
		'method' => 'after', 
		'params' => $params, 
		'include_blog_ids' => $include_blog_ids, 
		'blog_ids' => $blog_ids,
		'papers' => $papers,
		'papers_excluded' => $papers_excluded,
	) ); die();
	*/
	
	// remove this filter to avoid recursion
	remove_filter( 'bp_has_blogs', 'bpwpapers_filter_papers', 20 );
	
	// re-query with our params
	$has_blogs = bp_has_blogs( $params );
	
	// re-add filter
	add_filter( 'bp_has_blogs', 'bpwpapers_filter_papers', 20, 3 );

	// fallback
	return $has_blogs;
	
}

// only on front end OR ajax
if ( ! is_admin() OR ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) ) {

	// add filter for the above
	add_filter( 'bp_has_blogs', 'bpwpapers_filter_papers', 20, 3 );

}



/**
 * Override the total number of sites, excluding working papers
 *
 * @return int $filtered_count The filtered total number of BuddyPress Groups
 */
function bpwpapers_filter_total_blog_count() {
	
	// remove filter to prevent recursion
	remove_filter( 'bp_get_total_blog_count', 'bpwpapers_filter_total_blog_count', 8 );
	
	// get actual count
	$actual_count = bp_blogs_total_blogs();
	
	// get working papers
	$papers = bpwpapers_total_papers();
	
	// calculate
	$filtered_count = $actual_count - $papers;

	// add filter again
	add_filter( 'bp_get_total_blog_count', 'bpwpapers_filter_total_blog_count', 8 );
	
	// --<
	return $filtered_count;

}

// only on front end OR ajax
if ( ! is_admin() OR ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) ) {

	// add filter for the above
	add_filter( 'bp_get_total_blog_count', 'bpwpapers_filter_total_blog_count', 8 );

}



/**
 * Override the total number of sites for a user, excluding working papers
 *
 * @param int $count The total number of sites for a user
 * @return int $filtered_count The filtered total number of blogs for a user
 */
function bpwpapers_filter_total_blog_count_for_user( $count ) {
	
	// get working papers for this user
	$paper_count = bpwpapers_get_total_paper_count_for_user( $user_id );
	
	// calculate
	$filtered_count = $count - $paper_count;
	
	// --<
	return $filtered_count;

}

// only on front end OR ajax
if ( ! is_admin() OR ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) ) {

	// add filter for the above, before BP applies its number formatting
	add_filter( 'bp_get_total_blog_count_for_user', 'bpwpapers_filter_total_blog_count_for_user', 8, 1 );

}



/*
================================================================================
Compatibility with BuddyPress Followers plugin
================================================================================
*/



/**
 * Register hooks after Follow Blogs is loaded
 */
function bpwpapers_follow_blogs_init() {
	
	// access BP global
	global $bp;
	
	// is follow blogs present?
	if ( ! isset( $bp->follow->blogs ) ) return;
	
	// add our screen
	add_action( 'bp_screens', 'bpwpapers_screen_member_follow', 3 );
	
	// add menu items on member page
	add_action( 'bp_follow_setup_nav', 'bpwpapers_follow_blogs_setup_nav' );
	
	// add menu item on papers directory
	add_action( 'bpwpapers_blogs_directory_blog_types', 'bpwpapers_add_blog_directory_tab' );
	
	// add blogs filter to AJAX query string
	add_filter( 'bp_ajax_querystring', 'bpwpapers_add_blogs_scope_filter', 30, 2 );
	
	// add activity scope
	add_action( 'bp_before_activity_loop', 'bpwpapers_set_activity_scope_on_user_activity' );
	
	// add activity scope filter to AJAX query string
	add_filter( 'bp_ajax_querystring', 'bpwpapers_add_activity_scope_filter', 20, 2 );
	
}

// add action later than Follow Blogs
add_action( 'plugins_loaded', 'bpwpapers_follow_blogs_init', 25 );



/**
 * Override the total number of followed sites, excluding working papers
 *
 * @param array $count The total number of followed sites for a user (following is always 0)
 * @param int $user_id The numeric ID of a WordPress user
 * @param array $params The params used to query the followed sites
 * @return int $filtered_count The filtered total number of BuddyPress Groups
 */
function bpwpapers_filter_total_follow_count( $count, $user_id, $params ) {
	
	// only handle blogs
	if ( $params['follow_type'] != 'blogs' ) return $count;
	
	// construct args
	$args = array(
		'user_id' => $user_id,
		'follow_type' => 'blogs',
	);
	
	// get IDs
	$blog_ids = bp_follow_get_following( $args );
	
	// get paper IDs
	$papers = bpwpapers_get_papers();
	
	// is this our component?
	if ( bp_is_bpwpapers_component() ) {
	
		// let's include papers
		$total = array_intersect( $blog_ids, $papers );
	
	} else {
	
		// let's exclude papers
		$total = array_merge( array_diff( $blog_ids, $papers ) );
	
	}
	
	// override value in count array
	$count['following'] = count( $total );

	// --<
	return $count;

}

// add filter for the above
add_filter( 'bp_follow_total_follow_counts', 'bpwpapers_filter_total_follow_count', 10, 3 );



/**
 * Add a "Following (X)" tab to the papers directory.
 *
 * This is so the logged-in user can filter the papers directory to only
 * papers that the current user is following.
 */
function bpwpapers_add_blog_directory_tab() {
	
	// only for logged in users
	if ( ! is_user_logged_in() ) return;
	
	// get counts
	$counts = bp_follow_total_follow_counts( array(
		'user_id'     => bp_loggedin_user_id(),
		'follow_type' => 'blogs',
	) );
	
	// don't show if none found
	if ( empty( $counts['following'] ) ) return false;
	
	?>
	<li id="bpwpapers-following"><a href="<?php echo esc_url( bp_loggedin_user_domain() . bpwpapers_get_slug() . '/' . constant( 'BP_FOLLOW_BLOGS_USER_FOLLOWING_SLUG' ). '/' ); ?>"><?php printf( __( 'Following <span>%d</span>', 'bpwpapers' ), (int) $counts['following'] ) ?></a></li><?php
	
}



/**
 * Setup profile nav
 */
function bpwpapers_follow_blogs_setup_nav() {

	global $bp;

	// Determine user to use
	if ( bp_displayed_user_domain() ) {
		$user_domain = bp_displayed_user_domain();
	} elseif ( bp_loggedin_user_domain() ) {
		$user_domain = bp_loggedin_user_domain();
	} else {
		return;
	}

	bp_core_new_subnav_item( array(
		'name'            => _x( 'Followed Papers', 'Papers subnav tab', 'bpwpapers' ),
		'slug'            => constant( 'BP_FOLLOW_BLOGS_USER_FOLLOWING_SLUG' ),
		'parent_url'      => trailingslashit( $user_domain . bpwpapers_get_slug() ),
		'parent_slug'     => bpwpapers_get_slug(),
		'screen_function' => 'BP_Follow_Blogs_Screens::user_blogs_screen',
		'position'        => 20,
		'item_css_id'     => 'bpwpapers-following'
	) );
	
	// Add activity sub nav item
	if ( bp_is_active( 'activity' ) && apply_filters( 'bp_follow_blogs_show_activity_subnav', true ) ) {
		bp_core_new_subnav_item( array(
			'name'            => _x( 'Followed Papers', 'Activity subnav tab', 'bpwpapers' ),
			'slug'            => 'followpapers',
			'parent_url'      => trailingslashit( $user_domain . bp_get_activity_slug() ),
			'parent_slug'     => bp_get_activity_slug(),
			'screen_function' => 'BP_Follow_Blogs_Screens::user_activity_screen',
			'position'        => 22,
			'item_css_id'     => 'activity-followpapers'
		) );
	}
	
}



/**
 * Filter the blogs loop.
 *
 * Specifically, filter when we're on:
 *  - a user's "Followed Sites" page
 *  - the Sites directory and clicking on the "Following" tab
 *
 * @param str $qs The querystring for the BP loop
 * @param str $object The current object for the querystring
 * @return str Modified querystring
 */
function bpwpapers_add_blogs_scope_filter( $qs, $object ) {
	
	// not on the blogs object? stop now!
	if ( $object != 'bpwpapers' ) {
		return $qs;
	}

	// parse querystring into an array
	wp_parse_str( $qs, $r );

	// set scope if a user is on a user's "Followed Papers" page
	if ( 
		is_multisite() && 
		bp_is_bpwpapers_component() && 
		bp_is_user() && 
		bp_is_current_action( constant( 'BP_FOLLOW_BLOGS_USER_FOLLOWING_SLUG' ) ) 
	) {
		$r['scope'] = 'following';
	}

	if ( 'following' !== $r['scope'] ) {
		return $qs;
	}

	// get blog IDs that the user is following
	$following_ids = bp_get_following_ids( array(
		'follow_type' => 'blogs',
	) );

	// if $following_ids is empty, pass the largest bigint(20) value to ensure
	// no blogs are matched
	$following_ids = empty( $following_ids ) ? '18446744073709551615' : $following_ids;
	
	// convert from comma-delimited if needed
	$following_ids = array_filter( wp_parse_id_list( $following_ids ) );

	// get paper IDs
	$papers = bpwpapers_get_papers();
	
	// include just papers
	$following_ids = array_intersect( $following_ids, $papers );

	$args = array(
		'user_id'          => 0,
		'include_blog_ids' => $following_ids,
	);

	// make sure we add a separator if we have an existing querystring
	if ( ! empty( $qs ) ) {
		$qs .= '&';
	}

	// add our follow parameters to the end of the querystring
	$qs .= build_query( $args );

	return $qs;
	
}



/**
 * Set activity scope on a user's "Activity > Followed Sites" page
 */
function bpwpapers_set_activity_scope_on_user_activity() {

	if ( ! bp_is_current_action( 'followpapers' ) ) {
		return;
	}

	$scope = 'followpapers';

	// if we have a post value already, let's add our scope to the existing cookie value
	if ( !empty( $_POST['cookie'] ) ) {
		$_POST['cookie'] .= "%3B%20bp-activity-scope%3D{$scope}";
	} else {
		$_POST['cookie'] .= "bp-activity-scope%3D{$scope}";
	}

	// set the activity scope by faking an ajax request (loophole!)
	if ( ! defined( 'DOING_AJAX' ) ) {
		$_POST['cookie'] .= "%3B%20bp-activity-filter%3D-1";

		// reset the selected tab
		@setcookie( 'bp-activity-scope',  $scope, 0, '/' );

		//reset the dropdown menu to 'Everything'
		@setcookie( 'bp-activity-filter', '-1',   0, '/' );
	}
	
}



/**
 * Filter the activity loop.
 *
 * Specifically, when on the activity directory and clicking on the "Sites I
 * Follow" tab.
 *
 * @param str $qs The querystring for the BP loop
 * @param str $object The current object for the querystring
 * @return str Modified querystring
 */
function bpwpapers_add_activity_scope_filter( $qs, $object ) {

	// not on the activity object? stop now!
	if ( $object != 'activity' ) {
		return $qs;
	}

	// parse querystring into an array
	wp_parse_str( $qs, $r );

	if ( bp_is_current_action( 'followpapers' ) ) {
		$r['scope'] = 'followpapers';
	}

	if ( 'followpapers' !== $r['scope'] ) {
		return $qs;
	}

	// get blog IDs that the user is following
	$following_ids = bp_get_following_ids( array(
		'follow_type' => 'blogs',
	) );

	// if $following_ids is empty, pass the largest bigint(20) value to ensure
	// no blogs are matched
	$following_ids = empty( $following_ids ) ? '18446744073709551615' : $following_ids;
	
	// convert from comma-delimited if needed
	$following_ids = array_filter( wp_parse_id_list( $following_ids ) );

	// get paper IDs
	$papers = bpwpapers_get_papers();
	
	// include just papers
	$following_ids = array_intersect( $following_ids, $papers );

	$args = array(
		'user_id'    => 0,
		'object'     => 'blogs',
		'primary_id' => $following_ids,
	);

	// make sure we add a separator if we have an existing querystring
	if ( ! empty( $qs ) ) {
		$qs .= '&';
	}

	// add our follow parameters to the end of the querystring
	$qs .= build_query( $args );

	return $qs;
	
}



/*
================================================================================
Functions which may only be used in the loop
================================================================================
*/



/** 
 * Copied from bp_blogs_pagination_count() and amended
 */
function bpwpapers_blogs_pagination_count() {
	global $blogs_template;

	$start_num = intval( ( $blogs_template->pag_page - 1 ) * $blogs_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $blogs_template->pag_num - 1 ) > $blogs_template->total_blog_count ) ? $blogs_template->total_blog_count : $start_num + ( $blogs_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $blogs_template->total_blog_count );
	
	// get singular name
	$singular = strtolower( apply_filters( 'bpwpapers_extension_name', __( 'working paper', 'bpwpapers' ) ) );
	
	// get plural name
	$plural = strtolower( apply_filters( 'bpwpapers_extension_plural', __( 'working papers', 'bpwpapers' ) ) );
	
	// we need to override the singular name
	echo sprintf( 
		__( 'Viewing %1$s %2$s to %3$s (of %4$s %5$s)', 'buddypress' ), 
		$singular,
		$from_num, 
		$to_num, 
		$total,
		$plural
	);
	
}



/**
 * Get all working paper IDs
 * 
 * @return array $papers Array of all working paper site IDs
 */
function bpwpapers_get_papers() {
	
	// init
	$papers = array();
	
	// access plugin
	global $bp_working_papers;

	// get current list
	$blog_authors = $bp_working_papers->admin->option_get( 'bpwpapers_blog_authors' );
	
	// if we get some, we return the keys, which are the blog IDs
	if ( is_array( $blog_authors ) AND count( $blog_authors ) > 0 ) {
		return array_keys( $blog_authors );
	}

	// --<
	return $papers;
	
}



/**
 * Get the total number of working papers being tracked.
 * copied from bp_total_blogs() and amended
 *
 * @return int $count Total blog count.
 */
function bpwpapers_total_papers() {
	
	// get from cache if possible
	if ( !$count = wp_cache_get( 'bpwpapers_total_papers', 'bpwpapers' ) ) {
		
		// access plugin
		global $bp_working_papers;
	
		// get current list
		$blog_authors = $bp_working_papers->admin->option_get( 'bpwpapers_blog_authors' );
	
		// get total
		$total = bp_core_number_format( count( $blog_authors ) );
		
		// stash it
		wp_cache_set( 'bpwpapers_total_papers', $count, 'bpwpapers' );
		
	}
	
	// --<
	return $total;
	
}



/**
 * Output the total number of working papers on the site.
 */
function bpwpapers_total_paper_count() {
	echo bpwpapers_get_total_paper_count();
}

	/**
	 * Return the total number of working papers on the site
	 * 
	 * @return int Total number of working papers.
	 */
	function bpwpapers_get_total_paper_count() {
		return apply_filters( 'bpwpapers_get_total_paper_count', bpwpapers_total_papers() );
	}

	// format number that gets returned
	add_filter( 'bpwpapers_get_total_paper_count', 'bp_core_number_format' );



/**
 * Get the total number of working papers for a user
 * copied from bp_blogs_total_blogs_for_user() and amended
 *
 * @return int $count Total blog count for a user
 */
function bpwpapers_total_papers_for_user( $user_id = 0 ) {
	
	// get user ID if none passed
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}
	
	if ( !$count = wp_cache_get( 'bpwpapers_total_papers_for_user_' . $user_id, 'bpwpapers' ) ) {

		// get papers for author
		$blogs = bpwpapers_get_author_papers( $user_id );
		
		// get count
		$count = bp_core_number_format( count( $blogs ) );
		
		// stash it
		wp_cache_set( 'bpwpapers_total_papers_for_user_' . $user_id, $count, 'bpwpapers' );
		
	}
	
	// --<
	return $count;
	
}



/**
 * Output the total number of working papers for a user
 */
function bpwpapers_total_paper_count_for_user( $user_id = 0 ) {
	echo bpwpapers_get_total_paper_count_for_user( $user_id );
}

	/**
	 * Return the total number of working papers for this user
	 * 
	 * @return int Total number of working papers for this user
	 */
	function bpwpapers_get_total_paper_count_for_user( $user_id = 0 ) {
		return apply_filters( 'bpwpapers_get_total_paper_count_for_user', bpwpapers_total_papers_for_user( $user_id ) );
	}
	add_filter( 'bpwpapers_get_total_paper_count_for_user', 'bp_core_number_format' );



//==============================================================================
	
	
	
/** 
 * Check if blog is a groupblog
 *
 * @param int $blog_id the numeric ID of the blog
 * @return bool $return True if blog is a groupblog, false otherwise
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
 * @return bool $return True if blog is a working paper, false otherwise
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



/**
 * Sever link and delete group when a site gets deleted
 *
 * @param int $blog_id the numeric ID of the blog
 * @return void
 */
function bpwpapers_blog_deleted( $blog_id, $drop = false ) {
	
	// bail if not working paper
	if ( ! bpwpapers_is_working_paper( $blog_id ) ) return;

	// get existing group ID
	$group_id = bpwpapers_get_group_by_blog_id( $blog_id );

	// sanity check
	if ( is_numeric( $group_id ) ) {
	
		// delete group meta
		bpwpapers_remove_blog_from_group( $group_id, $blog_id );
		
		// delete the group
		groups_delete_group( $group_id );
	
	}
	
	// delete the site option
	delete_site_option( BP_WORKING_PAPERS_BLOG_GROUP_PREFIX . $blog_id );
	
	// get author for this blog
	$author_id = bpwpapers_get_author_for_blog( $blog_id );
	
	// sanity check
	if ( $author_id !== false AND is_numeric( $author_id ) ) {
	
		// revoke authorship
		bpwpapers_revoke_authorship( $author_id, $blog_id );
	
	}
	
}

// sever links when site deleted
add_action( 'delete_blog', 'bpwpapers_blog_deleted', 10, 1 );
	


/**
 * Configure blog options
 *
 * @param int $blog_id the numeric ID of the blog
 * @param int $group_id the numeric ID of the group
 * @return void
 */
function bpwpapers_configure_blog_options( $blog_id, $group_id ) {

	// kick out if already a working paper
	if ( bpwpapers_is_working_paper( $blog_id ) ) return;

	// go there
	switch_to_blog( $blog_id );
	
	// -------------------------------------------------------------------------
	// Set commenting options
	// -------------------------------------------------------------------------
	
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
	
	// -------------------------------------------------------------------------
	// A better tagline
	// -------------------------------------------------------------------------
	
	// set new, more descriptive tagline
	update_option( 'blogdescription', sprintf(
		__( 'A %1$s by %2$s', 'bpwpapers' ),
		apply_filters( 'bpwpapers_extension_name', __( 'Working Paper', 'bpwpapers' ) ),
		bp_get_loggedin_user_fullname()
	) );
	
	// -------------------------------------------------------------------------
	// Save original author
	// -------------------------------------------------------------------------
	
	// add an option for easy access
	add_option( 'bpwpapers_original_author', bp_loggedin_user_id() );
	
	// -------------------------------------------------------------------------
	// Site setup
	// -------------------------------------------------------------------------
	
	// access object
	global $bp_working_papers;
	
	// register CPT first
	$bp_working_papers->template->register_cpt();

	// create group page
	$page_id = $bp_working_papers->template->create_page();

	// store page ID for later
	add_option( 'bpwpapers_group_page', $page_id );

	// go ahead and flush
	flush_rewrite_rules();
	
	// -------------------------------------------------------------------------
	// Save group permalink in group meta
	// -------------------------------------------------------------------------
	
	// get permalink to this page
	$permalink = get_permalink( $page_id );
	
	// save option with group page permalink
	groups_update_groupmeta( $group_id, BP_WORKING_PAPERS_GROUP_PERMALINK, $permalink );
	
	// switch back
	restore_current_blog();
	
}



/**
 * Unset blog options
 *
 * @param int $blog_id the numeric ID of the blog
 * @return void
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



