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



/*
================================================================================
Class Name
================================================================================
*/

// TODO: this class may not be necessary if we can filter bp_has_blogs instead

class BP_Working_Papers_Blogs_Template extends BP_Blogs_Template {



	/*
	============================================================================
	Properties
	============================================================================
	*/
	
	// need to store this for recalculation
	public $page_arg = 'bpage';
	
	
	
	/** 
	 * Initialises this object
	 * 
	 * @return void
	 */
	function __construct( $type, $page, $per_page, $max, $user_id, $search_terms, $page_arg ) {
		
		// init parent
		parent::__construct( $type, $page, $per_page, $max, $user_id, $search_terms, $page_arg );
		
		// calculate true total (for this user if passed)
		$this->_calculate_true_total( $user_id );
		
		// store property for recalculation
		$this->page_arg = $page_arg;
		
		// exclude groupblogs and the BP root blog
		$this->filter_blogs();

		/*
		
		At some point, BP_Blogs_Template is bound to go the way of BP_Groups_Template
		and arguments will be passed as an associative array. The following code will
		go some way to dealing with that situation when it arises.
		
		// get passed arguments
		$args = func_get_args();
		
		// did we get any?
		if( is_array( $args ) AND count( $args ) > 1 ) {
			
			// yes, init parent
			parent::__construct( $args );
			
			// modify with our additions
			$this->filter_by_group( $args );

		} else {
			
			// no, init with empty array
			$this->params = array();

		}
		
		*/

	}
	
	
	
	/**
	 * @description exclude groupblogs and the BP root blog
	 */
	public function filter_blogs() {
		
		// if we got some...
		if ( is_array( $this->blogs ) AND count( $this->blogs ) > 0 ) {
		
			// exclude groupblogs and the BP root blog
			$this->blogs = $this->_exclude_groupblogs_and_root( $this->blogs );
		
			// recalculate parameters
			$this->_recalculate();
	
		}
		
	}
	
	
	
	/** 
	 * Filter blogs by their group associations
	 * 
	 * @param array $blogs an array of blogs
	 * @return array filtered blogs
	 */
	protected function _exclude_groupblogs_and_root( $blogs ) {
		
		// init return
		$filtered_blogs = array();
		$filtered_blogs['blogs'] = array();

		// if we have some blogs...
		if ( is_array( $blogs ) AND count( $blogs ) > 0 ) {
		
			// let's look at them
			foreach( $blogs AS $blog ) {
			
				// is it the BP root blog?
				if ( $blog->blog_id == bp_get_root_blog_id() ) { continue; }
				
				// is it a groupblog?
				if ( bpwpapers_is_groupblog( $blog->blog_id ) ) { continue; }
				
				// if we're showing the component directory, include only working papers
				if ( bp_is_bpwpapers_component() ) {
				
					// is it a working paper?
					if ( !bpwpapers_is_working_paper( $blog->blog_id ) ) { continue; }
					
				}
				
				// okay, none of those - add it
				$filtered_blogs['blogs'][] = $blog;
		
			}
	
		}
	
		// total blog count is calculated by _calculate_true_total()
		$filtered_blogs['total'] = $this->total_blog_count;
	
		/*
		// DIE!!!!!!
		print_r( array(
			'blogs' => $blogs, 
			'filtered_blogs' => $filtered_blogs,
			'total_blog_count' => $this->total_blog_count
		) ); die();
		*/
	
		// --<
		return $filtered_blogs;
	
	}



	/** 
	 * Recalculate properties
	 * 
	 * @return void
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
	 * Calculate true total of filtered blogs
	 * 
	 * @return void
	 */
	protected function _calculate_true_total( $user_id ) {
	
		// are we filtering by user ID?
		if ( $user_id == 0 ) {

			// get all blogs first
			$all = bp_blogs_get_all_blogs();
			
		} else {
			
			// get all for this user
			$all = bp_blogs_get_blogs_for_user( $user_id );

		}
		
		// filter out root blog and group blogs
		$filtered = $this->_exclude_groupblogs_and_root( $all['blogs'] );
		
		// store total
		$this->total_blog_count = count( $filtered['blogs'] );
		
		/*
		print_r( array(
			'user_id' => $user_id, 
			'user' => new WP_User( $user_id ), 
			'all' => $all, 
			'filtered' => $filtered,
			'total_blog_count' => $this->total_blog_count,
			'debug_backtrace' => debug_backtrace( 0 )
		) ); //die();
		*/
	
	}



} // class ends



//==============================================================================



/** 
 * Group-aware modification of bp_has_blogs
 * 
 * @return bool True when there are blogs, false when not
 */
function bpwpapers_has_blogs( $args = '' ) {
	global $blogs_template;

	/***
	 * Set the defaults based on the current page. Any of these will be overridden
	 * if arguments are directly passed into the loop. Custom plugins should always
	 * pass their parameters directly to the loop.
	 */
	$type         = 'active';
	$user_id      = 0;
	$search_terms = null;

	// User filtering
	if ( bp_displayed_user_id() )
		$user_id = bp_displayed_user_id();

	$defaults = array(
		'type'         => $type,
		'page'         => 1,
		'per_page'     => 20,
		'max'          => false,

		'page_arg'     => 'bpage',        // See https://buddypress.trac.wordpress.org/ticket/3679

		'user_id'      => $user_id,       // Pass a user_id to limit to only blogs this user has higher than subscriber access to
		'search_terms' => $search_terms,  // Pass search terms to filter on the blog title or description.
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	if ( is_null( $search_terms ) ) {
		if ( isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) )
			$search_terms = $_REQUEST['s'];
		else
			$search_terms = false;
	}

	if ( $max ) {
		if ( $per_page > $max ) {
			$per_page = $max;
		}
	}

	$blogs_template = new BP_Working_Papers_Blogs_Template( $type, $page, $per_page, $max, $user_id, $search_terms, $page_arg );
	return apply_filters( 'bpwpapers_has_blogs', $blogs_template->has_blogs(), $blogs_template );
	
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
 * Get the total number of working papers being tracked.
 * copied from bp_total_blogs() and amended
 *
 * @return int $count Total blog count.
 */
function bpwpapers_total_papers() {
	
	// get from cache if possible
	if ( !$count = wp_cache_get( 'bpwpapers_total_papers', 'bpwpapers' ) ) {
		
		// access blogs template
		global $blogs_template;
		
		// if we haven't got one yet, create one
		if ( !isset( $blogs_template ) ) { bpwpapers_has_blogs(); }
		
		// get total
		$total = bp_core_number_format( $blogs_template->total_blog_count );
		
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

		// access blogs template
		global $blogs_template;
		
		// create one
		bpwpapers_has_blogs( array( 'user_id' => $user_id ) );
		
		// get count
		$count = bp_core_number_format( $blogs_template->total_blog_count );
		
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
	delete_site_option( BP_WORKING_PAPERS_PREFIX . $blog_id );
	
}

// sever links when site deleted
add_action( 'delete_blog', 'bpwpapers_blog_deleted', 10, 1 );
	


/**
 * Set comment registration
 *
 * @param int $blog_id the numeric ID of the blog
 * @return void
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



