<?php /*
================================================================================
BuddyPress Working Papers Authors Template
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

We extend the BuddyPress BP BP_Core_Members_Template template class so that we can filter by
working paper authorship, whilst retaining useful stuff like pagination.

--------------------------------------------------------------------------------
*/



/**
 * Parse the query
 *
 * @param object $query_obj Query object passed by reference
 * @return array $retval Array of WordPress users
 */
function bpwpapers_authors_core_get_users( $retval, $params ) {

	// is this our component?
	if ( ! bp_is_bppaperauthors_component() ) return $retval;

	// until this bug is fixed, we need to trap the "no authors" scenario
	// @see https://buddypress.trac.wordpress.org/ticket/5659
	$total = bpwpapers_total_authors();
	if ( $total == 0 ) return array( 'users' => array(), 'total' => 0 );

	if ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) {
		//trigger_error( print_r( $params, true ), E_USER_ERROR ); die();
	} else {
		//print_r( $params ); die();
	}

	// do we have our meta query?
	if ( empty( $params['meta_key'] ) ) {

		// no, insert it
		$params['meta_key'] = BP_WORKING_PAPERS_AUTHOR_META_KEY;
		$params['meta_value'] = true;

		// remove this filter
		remove_filter( 'bp_core_get_users', 'bpwpapers_authors_core_get_users', 20 );

		// re-query with our params
		$retval = bp_core_get_users( $params );

		// re-add filter
		add_filter( 'bp_core_get_users', 'bpwpapers_authors_core_get_users', 20, 2 );

	}

	// fallback
	return $retval;

}

// add filter for the above
add_filter( 'bp_core_get_users', 'bpwpapers_authors_core_get_users', 20, 2 );



/*
================================================================================
Functions which may only be used in the loop
================================================================================
*/



/**
 * Output the total working paper author count for the site.
 */
function bpwpapers_total_member_count() {
	echo bpwpapers_get_total_member_count();
}



	/**
	 * Return the total member count in your BP instance.
	 *
	 * Since BuddyPress 1.6, this function has used bp_core_get_active_member_count(),
	 * which counts non-spam, non-deleted users who have last_activity.
	 * This value will correctly match the total member count number used
	 * for pagination on member directories.
	 *
	 * Before BuddyPress 1.6, this function used bp_core_get_total_member_count(),
	 * which did not take into account last_activity, and thus often
	 * resulted in higher counts than shown by member directory pagination.
	 *
	 * @return int Member count.
	 */
	function bpwpapers_get_total_member_count() {
		return apply_filters( 'bpwpapers_get_total_member_count', bpwpapers_total_authors() );
	}
	add_filter( 'bpwpapers_get_total_member_count', 'bp_core_number_format' );




/**
 * Print pagination count to screen
 * copied from bp_members_pagination_count()
 */
function bpwpapers_members_pagination_count() {
	echo bpwpapers_get_members_pagination_count();
}



	/**
	 * Get pagination count
	 * copied from bp_get_members_pagination_count() and adapted
	 *
	 * @return string $pag The pagination text including number of members
	 */
	function bpwpapers_get_members_pagination_count() {
		global $members_template;

		if ( empty( $members_template->type ) ) {
			$members_template->type = '';
		}

		$start_num = intval( ( $members_template->pag_page - 1 ) * $members_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $members_template->pag_num - 1 ) > $members_template->total_member_count ) ? $members_template->total_member_count : $start_num + ( $members_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $members_template->total_member_count );

		if ( 'active' == $members_template->type ) {

			$pag = sprintf(
				_n(
					'Viewing author %1$s to %2$s (of %3$s active author)',
					'Viewing author %1$s to %2$s (of %3$s active authors)',
					$total,
					'bpwpapers'
				),
				$from_num,
				$to_num,
				$total
			);

		} elseif ( 'popular' == $members_template->type ) {

			$pag = sprintf(
				_n(
					'Viewing author %1$s to %2$s (of %3$s author with friends)',
					'Viewing author %1$s to %2$s (of %3$s authors with friends)',
					$total,
					'bpwpapers'
				),
				$from_num,
				$to_num,
				$total
			);

		} elseif ( 'online' == $members_template->type ) {

			$pag = sprintf(
				_n(
					'Viewing author %1$s to %2$s (of %3$s author online)',
					'Viewing author %1$s to %2$s (of %3$s authors online)',
					$total,
					'bpwpapers'
				),
				$from_num,
				$to_num,
				$total
			);

		} else {

			$pag = sprintf(
				_n(
					'Viewing author %1$s to %2$s (of %3$s author)',
					'Viewing author %1$s to %2$s (of %3$s authors)',
					$total,
					'bpwpapers'
				),
				$from_num,
				$to_num,
				$total
			);

		}

		return apply_filters( 'bpwpapers_members_pagination_count', $pag );

	}



/**
 * Override the working paper authors search form because bp_get_search_default_text() is not
 * allowed to take the value set for the current component
 * copied from bp_directory_members_search_form() and amended
 */
function bppaperauthors_directory_members_search_form() {

	$default_search_value = bp_get_search_default_text( 'bppaperauthors' );
	$search_value         = !empty( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : $default_search_value;

	$search_form_html = '<form action="" method="get" id="search-members-form">
		<label><input type="text" name="s" id="members_search" placeholder="'. esc_attr( $search_value ) .'" /></label>
		<input type="submit" id="members_search_submit" name="members_search_submit" value="' . __( 'Search', 'buddypress' ) . '" />
	</form>';

	echo apply_filters( 'bp_directory_members_search_form', $search_form_html );

}



//==============================================================================



/**
 * Get all working paper author IDs
 *
 * @return bool True if the user is a working paper author, false otherwise
 */
function bpwpapers_get_authors() {

	// access plugin
	global $bp_working_papers;

	// get current list
	$authors = $bp_working_papers->admin->option_get( 'bpwpapers_authors' );

	// if not present,
	if ( is_array( $authors ) AND count( $authors ) > 0 ) {
		return array_keys( $authors );
	}

	// fallback
	return false;

}



/**
 * Get working papers by author ID
 *
 * @param int $author_id The numeric ID of the working paper author
 * @return array An array of the numeric IDs of the working papers
 */
function bpwpapers_get_author_papers( $author_id ) {

	// access plugin
	global $bp_working_papers;

	// get current list
	$authors = $bp_working_papers->admin->option_get( 'bpwpapers_authors' );

	/*
	print_r( array(
		'author_id' => $author_id,
		'authors' => $authors,
	) ); die();
	*/

	// if present, return it
	if ( array_key_exists( $author_id, $authors ) ) {
		return $authors[$author_id];
	}

	// fallback
	return array();

}



/**
 * Get total count of working paper authors
 *
 * @return int Total number of working paper authors
 */
function bpwpapers_total_authors() {

	// get current list
	$authors = bpwpapers_get_authors();

	// did we get any?
	if ( $authors !== false ) {

		// return count
		return count( $authors );

	}

	// fallback
	return 0;

}



/**
 * Check if a user is a working paper author
 *
 * @param int $author_id the numeric ID of the user
 * @return bool True if the user is a working paper author, false otherwise
 */
function bpwpapers_is_author( $author_id ) {

	// access plugin
	global $bp_working_papers;

	// get current list
	$authors = $bp_working_papers->admin->option_get( 'bpwpapers_authors' );

	// if present, user is author
	if ( array_key_exists( $author_id, $authors ) ) return true;

	// --<
	return false;

}



/**
 * Get the user ID that created a working paper
 *
 * @param int $blog_id the numeric ID of the blog
 * @return int The numeric ID of the user that created a working paper, false otherwise
 */
function bpwpapers_get_author_for_blog( $blog_id ) {

	// access plugin
	global $bp_working_papers;

	// get current list
	$blog_authors = $bp_working_papers->admin->option_get( 'bpwpapers_blog_authors' );

	// if present, return value
	if ( array_key_exists( $blog_id, $blog_authors ) ) {
		return $blog_authors[$blog_id];
	}

	// --<
	return false;

}



/**
 * Save a user's ID because it's too expensive to recalculate every time we want to list users
 * in the Working Paper Authors Directory
 *
 * @param int $author_id the numeric ID of the user
 * @param int $blog_id the numeric ID of the working paper site
 * @param bool $save Optionally pass 'false' to override saving the option
 * @return void
 */
function bpwpapers_grant_authorship( $author_id, $blog_id, $save = true ) {

	// access plugin
	global $bp_working_papers;

	// get current author data
	$authors = $bp_working_papers->admin->option_get( 'bpwpapers_authors' );

	// if not already present
	if ( ! array_key_exists( $author_id, $authors ) ) {

		// add to array, keyed by user ID, value is an array of working paper site IDs
		$authors[$author_id] = array( $blog_id );

		// add user meta
		update_user_meta( $author_id, BP_WORKING_PAPERS_AUTHOR_META_KEY, true );

	} else {

		// add a paper to the current array for this user
		$authors[$author_id][] = $blog_id;

		// user meta will already exist, no need to add

	}

	// overwrite option
	$bp_working_papers->admin->option_set( 'bpwpapers_authors', $authors );

	// -------------------------------------------------------------------------

	// get current list of blog authors
	$blog_authors = $bp_working_papers->admin->option_get( 'bpwpapers_blog_authors' );

	// add this blog and author to it
	$blog_authors[$blog_id] = $author_id;

	// overwrite option
	$bp_working_papers->admin->option_set( 'bpwpapers_blog_authors', $blog_authors );

	// -------------------------------------------------------------------------

	// optionally save
	if ( $save === true ) $bp_working_papers->admin->options_save();

}



/**
 * Maybe remove user from the list of authors, depending on the number of papers
 *
 * @param int $author_id the numeric ID of the user
 * @param int $blog_id the numeric ID of the working paper site
 * @param bool $save Optionally pass 'false' to override saving the option
 * @return void
 */
function bpwpapers_revoke_authorship( $author_id, $blog_id, $save = true ) {

	// access plugin
	global $bp_working_papers;

	// get current list
	$authors = $bp_working_papers->admin->option_get( 'bpwpapers_authors' );

	// kick out if not present
	if ( ! array_key_exists( $author_id, $authors ) ) return;

	// get current number of papers
	$papers = $authors[$author_id];

	// if just one...
	if ( count( $papers ) === 1 ) {

		// remove from array
		unset( $authors[$author_id] );

		// delete user meta
		delete_user_meta( $author_id, BP_WORKING_PAPERS_AUTHOR_META_KEY );

	} else {

		// remove paper from the current array for this user
		$authors[$author_id] = array_merge( array_diff( $papers, array( $blog_id ) ) );

		// still an author, so don't remove meta key

	}

	// overwrite option
	$bp_working_papers->admin->option_set( 'bpwpapers_authors', $authors );

	// -------------------------------------------------------------------------

	// get current list of blog authors
	$blog_authors = $bp_working_papers->admin->option_get( 'bpwpapers_blog_authors' );

	// remove this blog and author from it
	if ( isset( $blog_authors[$blog_id] ) ) unset( $blog_authors[$blog_id] );

	// overwrite option
	$bp_working_papers->admin->option_set( 'bpwpapers_blog_authors', $blog_authors );

	// -------------------------------------------------------------------------

	// optionally save
	if ( $save === true ) $bp_working_papers->admin->options_save();

}



