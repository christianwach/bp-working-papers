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
 * Query only working paper blogs.
 *
 * @since 0.1
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

	// if empty, create array guaranteed to produce no result
	if ( empty( $papers ) ) $papers = array( PHP_INT_MAX );

	// declare defaults
	$defaults = array(
		'type'         => 'active',
		'page'         => 1,
		'per_page'     => 20,
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
 * Intercept the bp_has_blogs() query and exclude working paper sites.
 *
 * @since 0.1
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

	// let's exclude
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
 * Override the total number of sites, excluding working papers.
 *
 * @since 0.1
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
 * Override the total number of sites for a user, excluding working papers.
 *
 * @since 0.1
 *
 * @param int $count The total number of sites for a user
 * @return int $filtered_count The filtered total number of blogs for a user
 */
function bpwpapers_filter_total_blog_count_for_user( $count ) {

	// get user ID if none passed
	$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();

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
Functions which may only be used in the loop
================================================================================
*/



/**
 * Copied from bp_blogs_pagination_count() and amended.
 *
 * @since 0.1
 */
function bpwpapers_blogs_pagination_count() {
	global $blogs_template;

	$start_num = intval( ( $blogs_template->pag_page - 1 ) * $blogs_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $blogs_template->pag_num - 1 ) > $blogs_template->total_blog_count ) ? $blogs_template->total_blog_count : $start_num + ( $blogs_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $blogs_template->total_blog_count );

	// get singular name
	$singular = strtolower( bpwpapers_extension_name() );

	// get plural name
	$plural = strtolower( bpwpapers_extension_plural() );

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
 * Get all working paper IDs.
 *
 * @since 0.1
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
 *
 * Copied from bp_total_blogs() and amended.
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

		// filter by visibility
		$filtered = bpwpapers_filter_total_papers_by_visibility( $blog_authors );

		// get total
		$total = bp_core_number_format( count( $filtered ) );

		// stash it
		wp_cache_set( 'bpwpapers_total_papers', $count, 'bpwpapers' );

	}

	// --<
	return $total;

}



/**
 * Filter papers depending on their visibility.
 *
 * @since 0.1
 *
 * @param array $blog_authors The unfiltered list of author IDs, keyed by blog ID
 * @return array $blog_authors The filtered list of author IDs, keyed by blog ID
 */
function bpwpapers_filter_total_papers_by_visibility( $blog_authors ) {

	// init filtered
	$filtered = array();

	// filter these by whether the blog is public or not
	if ( count( $blog_authors ) > 0 ) {

		// get current user
		$user_id = bp_loggedin_user_id();

		// let's see...
		foreach( $blog_authors AS $blog_id => $author_id ) {

			// get status
			$status = get_blog_status( $blog_id, 'public' );

			// is this blog public?
			if ( $status == '1' ) {

				// keep it
				$filtered[$blog_id] = $author_id;

			} else {

				// otherwise only add it if it's the author's blog
				if ( $author_id == $user_id ) {
					$filtered[$blog_id] = $author_id;
				}

			}

		}

	}

	// --<
	return $filtered;

}



/**
 * Output the total number of working papers on the site.
 *
 * @since 0.1
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
 * Get the total number of working papers for a user.
 *
 * Copied from bp_blogs_total_blogs_for_user() and amended
 *
 * @since 0.1
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
 * Output the total number of working papers for a user.
 *
 * @since 0.1
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
 * Check if blog is a groupblog.
 *
 * @since 0.1
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
 * Check if blog is a working paper.
 *
 * @since 0.1
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
 * Sever link and delete group when a site gets deleted.
 *
 * @since 0.1
 *
 * @param int $blog_id the numeric ID of the blog
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
 * Configure blog options.
 *
 * @since 0.1
 *
 * @param int $blog_id the numeric ID of the blog
 * @param int $group_id the numeric ID of the group
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
		bpwpapers_extension_name(),
		bp_get_loggedin_user_fullname()
	) );

	// -------------------------------------------------------------------------
	// Save original author
	// -------------------------------------------------------------------------

	// add an option for easy access to the author's user ID
	add_option( 'bpwpapers_original_author', bp_loggedin_user_id() );

	// add an option for easy access to the author's name
	add_option( 'bpwpapers_original_author_name', bp_get_loggedin_user_fullname() );

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
 * Unset blog options.
 *
 * @since 0.1
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
 * Publish blog.
 *
 * @since 0.1
 *
 * @param int $blog_id the numeric ID of the blog
 */
function bpwpapers_publish_blog( $blog_id ) {

	// kick out if not a working paper
	if ( ! bpwpapers_is_working_paper( $blog_id ) ) return;

	// get status
	$status = get_blog_status( $blog_id, 'public' );

	// set status
	if ( $status != '1' ) {

		// save new value
		update_option( 'blog_public', '1' );

	}

}



/**
 * Unublish blog.
 *
 * @since 0.1
 *
 * @param int $blog_id the numeric ID of the blog
 */
function bpwpapers_unpublish_blog( $blog_id ) {

	// kick out if not a working paper
	if ( ! bpwpapers_is_working_paper( $blog_id ) ) return;

	// get status
	$status = get_blog_status( $blog_id, 'public' );

	// change text depending on toggle state
	if ( $status == '1' ) {

		// default to "not visible"
		$public = '0';

		// optionally make visible only to site owner
		if ( class_exists( 'ds_more_privacy_options' ) ) {
			$public = '-3';
		}

		// save new value
		update_option( 'blog_public', $public );

	}

}



