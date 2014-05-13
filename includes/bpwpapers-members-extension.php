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



/*
================================================================================
Class Name
================================================================================
*/

class BP_Working_Paper_Authors_Template extends BP_Core_Members_Template {



	/*
	============================================================================
	Properties
	============================================================================
	*/
	
	// need to store this for recalculation
	public $page_arg = 'upage';
	
	
	
	/** 
	 * @description: initialises this object
	 * @return nothing
	 */
	function __construct( $type, $page_number, $per_page, $max, $user_id, $search_terms, $include, $populate_extras, $exclude, $meta_key, $meta_value, $page_arg = 'upage' ) {
		
		// init parent
		parent::__construct( $type, $page_number, $per_page, $max, $user_id, $search_terms, $include, $populate_extras, $exclude, $meta_key, $meta_value, $page_arg = 'upage' );

		// store property for recalculation
		$this->page_arg = $page_arg;
		
	}
	
	
	
	/** 
	 * @description: recalculate properties
	 */
	protected function _recalculate() {

		// recalculate and reassign
		$this->blogs = $this->blogs['blogs'];
		$this->blog_count = count( $this->blogs );

		// rebuild pagination with new blog counts
		if ( (int) $this->total_blog_count && (int) $this->pag_num ) {
	
			$this->pag_links = paginate_links( array(
		
				'base'      => add_query_arg( $this->page_arg, '%#%' ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_blog_count / (int) $this->pag_num ),
				'current'   => (int) $this->pag_page,
				'prev_text' => _x( '&larr;', 'Blog pagination previous text', 'bpwpapers' ),
				'next_text' => _x( '&rarr;', 'Blog pagination next text', 'bpwpapers' ),
				'mid_size'  => 1
			
			) );
		
		}
	
	}



	/**
	 * @description calculate true total of filtered blogs
	 */
	protected function _calculate_true_total() {
	
		// get all blogs first
		$all = bp_blogs_get_all_blogs();
		
		// filter out root blog and group blogs
		$filtered = $this->_exclude_groupblogs_and_root( $all['blogs'] );
		
		// store total
		$this->total_blog_count = count( $filtered['blogs'] );
		
	}



} // class ends



/** 
 * @description: working paper-aware modification of bp_has_members
 * @return boolean true when there are members, false when not
 */
function bpwpapers_has_members( $args = '' ) {
	global $members_template;
	
	/***
	 * Set the defaults based on the current page. Any of these will be overridden
	 * if arguments are directly passed into the loop. Custom plugins should always
	 * pass their parameters directly to the loop.
	 */
	$type         = 'active';
	$user_id      = 0;
	$page         = 1;
	$search_terms = null;
	
	// User filtering
	if ( bp_is_user_friends() && ! bp_is_user_friend_requests() ) {
		$user_id = bp_displayed_user_id();
	}
	
	// type: active ( default ) | random | newest | popular | online | alphabetical
	$defaults = array(
		'type'            => $type,
		'page'            => $page,
		'per_page'        => 20,
		'max'             => false,

		'page_arg'        => 'upage',       // See https://buddypress.trac.wordpress.org/ticket/3679

		'include'         => false,         // Pass a user_id or a list (comma-separated or array) of user_ids to only show these users
		'exclude'         => false,         // Pass a user_id or a list (comma-separated or array) of user_ids to exclude these users

		'user_id'         => $user_id,      // Pass a user_id to only show friends of this user
		'search_terms'    => $search_terms, // Pass search_terms to filter users by their profile data

		'meta_key'        => false,	        // Only return users with this usermeta
		'meta_value'	  => false,	        // Only return users where the usermeta value matches. Requires meta_key

		'populate_extras' => true           // Fetch usermeta? Friend count, last active etc.
	);
	
	$r = wp_parse_args( $args, $defaults );
	extract( $r );
	
	// Pass a filter if ?s= is set.
	if ( is_null( $search_terms ) ) {
		if ( isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) )
			$search_terms = $_REQUEST['s'];
		else
			$search_terms = false;
	}
	
	// Set per_page to max if max is larger than per_page
	if ( $max ) {
		if ( $per_page > $max ) {
			$per_page = $max;
		}
	}
	
	$members_template = new BP_Working_Paper_Authors_Template( 
		$type, $page, $per_page, $max, $user_id, $search_terms, $include, 
		(bool)$populate_extras, $exclude, $meta_key, $meta_value, $page_arg 
	);
	//print_r( $members_template ); die();
	
	return apply_filters( 'bpwpapers_has_members', $members_template->has_members(), $members_template );
	
}



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
 * Output the working papers component root slug.
 * @uses bppaperauthors_get_root_slug()
 */
function bppaperauthors_root_slug() {
	echo bppaperauthors_get_root_slug();
}

	/**
	 * Return the working papers component root slug.
	 * @return string The 'blogs' root slug.
	 */
	function bppaperauthors_get_root_slug() {
		return apply_filters( 'bppaperauthors_get_root_slug', buddypress()->bppaperauthors->root_slug );
	}



/**
 * Override the working papers search form because bp_get_search_default_text() is not
 * allowed to take the value set for th current component
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
 * Check if a user is a white paper author
 *
 * @param int $author_id the numeric ID of the user
 * @return bool True if the user is a white paper author, false otherwise
 */
function bpwpapers_is_author( $author_id ) {

	// access plugin
	global $bp_working_papers;
	
	// get current list
	$authors = $bp_working_papers->admin->option_get( 'authors' );
	
	// if not present, 
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
	$authors = $bp_working_papers->admin->option_get( 'authors' );
	
	// if not already present
	if ( ! array_key_exists( $author_id, $authors ) ) {
		
		// add to array, keyed by user ID, value is an array of working paper site IDs
		$authors[$author_id] = array( $blog_id );
		
	} else {
		
		// add a paper to the current array for this user
		$authors[$author_id][] = $blog_id;
	
	}
		
	// overwrite option
	$bp_working_papers->admin->option_set( $authors );
	
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
	$authors = $bp_working_papers->admin->option_get( 'authors' );
	
	// kick out if not present
	if ( ! array_key_exists( $author_id, $authors ) ) return;
	
	// get current number of papers
	$papers = $authors[$author_id];
		
	// if just one...
	if ( count( $papers ) === 1 ) {
		
		// remove from array
		unset( $authors[$author_id] );
		
	} else {
	
		// remove paper from the current array for this user
		$authors[$author_id] = array_merge( array_diff( $papers, array( $blog_id ) ) );
	
	}

	// overwrite option
	$bp_working_papers->admin->option_set( $authors );
	
	// optionally save
	if ( $save === true ) $bp_working_papers->admin->options_save();
	
}



