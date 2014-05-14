<?php /*
================================================================================
BuddyPress Working Papers Members Template
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
 * @param object $query_obj Query object passed by reference
 */
function bpwpapers_authors_core_get_users( $retval, $params ) {
	
	// is this our component?
	if ( ! bp_is_bppaperauthors_component() ) return $retval;
	
	// do we have our meta query?
	if ( empty( $params['meta_key'] ) ) {
		
		// no, insert it
		$params['meta_key'] = BP_WORKING_PAPERS_AUTHOR_META_KEY;
		$params['meta_value'] = true;
		
		// remove this filter
		remove_filter( 'bp_core_get_users', 20 );
		
		// re-query with our params
		return bp_core_get_users( $params );
		
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
 * Print pagination count to screen
 * copied from bp_members_pagination_count()
 */
function bpwpapers_members_pagination_count() {
	echo bpwpapers_get_members_pagination_count();
}



	/**
	 * Get pagination count
	 * copied from bp_get_members_pagination_count() and adapted
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
					'Viewing member %1$s to %2$s (of %3$s active member)', 
					'Viewing member %1$s to %2$s (of %3$s active members)', 
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
					'Viewing member %1$s to %2$s (of %3$s member with friends)', 
					'Viewing member %1$s to %2$s (of %3$s members with friends)', 
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
					'Viewing member %1$s to %2$s (of %3$s member online)', 
					'Viewing member %1$s to %2$s (of %3$s members online)', 
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
					'Viewing member %1$s to %2$s (of %3$s member)', 
					'Viewing member %1$s to %2$s (of %3$s members)', 
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
 * Save a user's ID because it's too expensive to recalculate every time we want to list users 
 * in the Working Paper Authors Directory
 *
 * @param int $author_id the numeric ID of the user
 * @param int $blog_id the numeric ID of the working paper site
 * @param bool $save Optionally pass 'false' to override saving the option
 */
function bpwpapers_grant_authorship( $author_id, $blog_id, $save = true ) {

	// access plugin
	global $bp_working_papers;
	
	// get current list
	$authors = $bp_working_papers->admin->option_get( 'bpwpapers_authors' );
	
	// if not already present
	if ( ! array_key_exists( $author_id, $authors ) ) {
		
		// add to array, keyed by user ID, value is an array of working paper site IDs
		$authors[$author_id] = array( $blog_id );
		
		// add user meta
		update_user_meta( $user_id, BP_WORKING_PAPERS_AUTHOR_META_KEY, true );
		
	} else {
		
		// add a paper to the current array for this user
		$authors[$author_id][] = $blog_id;
		
		// user meta will already exist, no need to add
	
	}
		
	// overwrite option
	$bp_working_papers->admin->option_set( 'bpwpapers_authors', $authors );
	
	// optionally save
	if ( $save === true ) $bp_working_papers->admin->options_save();
	
}



/** 
 * Maybe remove user from the list of authors, depending on the number of papers
 *
 * @param int $author_id the numeric ID of the user
 * @param int $blog_id the numeric ID of the working paper site
 * @param bool $save Optionally pass 'false' to override saving the option
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
		delete_user_meta( $user_id, BP_WORKING_PAPERS_AUTHOR_META_KEY );
		
	} else {
	
		// remove paper from the current array for this user
		$authors[$author_id] = array_merge( array_diff( $papers, array( $blog_id ) ) );
		
		// still an author, so don't remove meta key
	
	}

	// overwrite option
	$bp_working_papers->admin->option_set( 'bpwpapers_authors', $authors );
	
	// optionally save
	if ( $save === true ) $bp_working_papers->admin->options_save();
	
}



