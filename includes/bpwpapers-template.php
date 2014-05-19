<?php /*
================================================================================
BuddyPress Working Papers Template Functions
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

For each working paper, we need to create a "page" that shows group activity.
Instead of using a regular page and awkwardly filtering it out, let's have a CPT
with no UI and create a single instance of that for the group.

We can then point template searches at our plugin's templates directory, while
allowing themes to override the templates by including their own copies.

--------------------------------------------------------------------------------
*/



/*
================================================================================
Class Name
================================================================================
*/

class BP_Working_Papers_Template {

	/*
	============================================================================
	Properties
	============================================================================
	*/

	// groups
	//public $groups = array();
	
	
	
	/** 
	 * Initialises this object
	 * 
	 * @return object
	 */
	function __construct() {
	
		// --<
		return $this;

	}
	
	
	
	/**
	 * Register hooks for this class
	 * 
	 * @return void
	 */
	public function register_hooks() {
	
		// if the current blog is a working paper...
		if ( bpwpapers_is_working_paper( get_current_blog_id() ) ) {
			
			// register post type
			add_action( 'init', array( $this, 'register_cpt' ) );
			
			// front end
			if ( ! is_admin() ) {
		
				// filter template searches
				add_filter( 'template_include', array( $this, 'include_template' ), 10, 1 );
			
				// add CSS files
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			
			}
			
		}

	}
	
	
	
	/**
	 * Register custom post type
	 */
	function register_cpt() {
		
		// only call this once
		static $registered;
		
		// bail if already done
		if ( $registered ) return;
	
		// working paper group
		register_post_type( 'bpwpaper', array( 
		
			'label' => __( 'Working Paper Groups' ),
			'description' => '',
			'public' => true,
			'show_ui' => false,
			'show_in_nav_menus' => false,
			'show_in_menu' => false,
			'capability_type' => 'post',
			'hierarchical' => false,
			'rewrite' => array( 
				'slug' => 'group',
			),
			'has_archive' => false,
			'query_var' => true,
			'exclude_from_search' => true,
			'can_export' => false,
			'supports' => array( 'title' ),
			'labels' => array (
				'name' => 'Groups',
				'singular_name' => 'Group',
				'menu_name' => 'Groups',
				'add_new' => 'Add Group',
				'add_new_item' => 'Add New Group',
				'edit' => 'Edit',
				'edit_item' => 'Edit Group',
				'new_item' => 'New Group',
				'view' => 'View Group',
				'view_item' => 'View Group',
				'search_items' => 'Search Groups',
				'not_found' => 'No Groups found',
				'not_found_in_trash' => 'No Groups found in Trash',
				'parent' => 'Parent Group',
			)
			
		) );
		
		//flush_rewrite_rules();
		
		// flag
		$registered = true;
	
	}
	
	
	
	/**
	 * Create a page for the Working Paper group
	 * 
	 * @return int $page_id The numeric ID of the new page
	 */
	public function create_page() {
	
		// define group page
		$page = array(
			'post_status' => 'publish',
			'post_type' => 'bpwpaper',
			'post_parent' => 0,
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'to_ping' => '', // quick fix for Windows
			'pinged' => '', // quick fix for Windows
			'post_content_filtered' => '', // quick fix for Windows
			'post_content' => '', // quick fix for Windows
			'post_excerpt' => '', // quick fix for Windows
			'menu_order' => 0
		);
		
		// allow overrides of title
		$page['post_title'] = apply_filters( 
			'bpwpapers_group_page_title',
			__( 'Group', 'bpwpapers' )
		);
		
		// allow overrides of template
		$page['page_template'] = apply_filters( 
			'bpwpapers_group_page_template',
			'bpwpaper/bpwpaper-group.php'
		);
		
		// Insert the page into the database
		$page_id = wp_insert_post( $page );
		
		// --<
		return $page_id;
		
	}
	
	
	
	/**
	 * Intercept template for our custom group page
	 *
	 * @param string $template Absolute path to template
	 * @return string $found_template Absolute path to template
	 */
	public function include_template( $template ) {
	
		// get ID of requested post
		$id = get_queried_object_id();
		
		// sanity check
		if ( ! $id ) return $template;
		
		// get group page ID
		$page_id = get_option( 'bpwpapers_group_page', false );
		
		// sanity check
		if ( $page_id === false ) return $template;
		if ( $page_id != $id ) return $template;
		
		// define our template
		$group_template = 'bpwpaper/bpwpaper-group.php';
		
		// look for it
		$found_template = $this->find_template( $group_template );
		
		// how did we do?
		if ( $found_template === false ) return $template;
		
		// let's let CommentPress know...
		global $commentpress_core;
		
		// is it active
		if ( is_object( $commentpress_core ) ) {
			
			// override BuddyPress flag
			$commentpress_core->buddypress_init();
			
			// override commentable flag
			add_filter( 'cp_is_commentable', array( $this, 'is_commentable' ), 100, 1 );
		
		}
		
		// access plugin
		global $bp_working_papers;

		// add filter options
		add_action( 'bp_group_activity_filter_options', array( $bp_working_papers->activity, 'filter_option_posts' ) );
		add_action( 'bp_group_activity_filter_options', array( $bp_working_papers->activity, 'filter_option_comments' ) );

		// --<
		return $found_template;
	
	}
	
	
	
	/**
	 * Find a template file
	 * 
	 * @param string $template Relative path to a template file
	 * @return string $full_path The full path to the template file if one is located, false otherwise
	 */
	function find_template( $template ) {
		
		// init as false
		$full_path = false;

		// child theme
		$template_dir = get_stylesheet_directory();
		
		// parent theme
		$parent_template_dir = get_template_directory();
		
		// plugin template dir
		$plugin_dir = bpwpapers_templates_dir();
		
		// define stack
		$stack = array( $template_dir, $parent_template_dir, $plugin_dir );
		
		// allow overrides
		$stack = apply_filters( 'bpwpapers_template_stack', $stack );
		
		// sanity check
		$stack = array_unique( $stack );
		
		// let's look...
		foreach ( $stack AS $dir ) {
			
			// well?
			if ( file_exists( trailingslashit( $dir ) . $template ) ) {
			
				// yay, found it
				$full_path = trailingslashit( $dir ) . $template;
				break;
				
			}
			
		}
		
		// --<
		return $full_path;
	
	}
	
	
	
	/** 
	 * Register plugin stylesheet
	 * 
	 * @return void
	 */
	public function enqueue_styles() {
	
		// add admin css
		wp_enqueue_style(
			
			'bpwpapers-public-style', 
			BP_WORKING_PAPERS_URL . 'assets/css/bpwpapers-public.css',
			null,
			BP_WORKING_PAPERS_VERSION,
			'all' // media
			
		);
		
	}
	
	
	
	/** 
	 * Tell CommentPress that the group page is not commentable
	 * 
	 * @return boolean False, because this is never commentable
	 */
	public function is_commentable( $is_commentable ) {
		
		// --<
		return false;
		
	}
	
	
	
	//==========================================================================
	
	
	
} // end class BP_Working_Papers_Template



/*
================================================================================
Globally available utility functions
================================================================================
*/



/** 
 * Replacement for 'locate_template()', adapted from Event Organiser's template handling functions.
 * 
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool $load If true the template file will be loaded if it is found.
 * @param bool $require_once Whether to require_once or require. Default true. Has no effect if $load is false.
 * @return string The template filename if one is located, false otherwise
 */
function bpwpapers_locate_template( $template_names, $load = false, $require_once = true ) {
	
	// init return
	$located = false;
	
	// access object
	global $bp_working_papers;
	
	// look through array...
	foreach ( (array) $template_names as $template_name ) {
		
		// sanity check
		if ( empty( $template_name ) ) continue;
		
		// look for the template in our hierarchy
		$located = $bp_working_papers->template->find_template( $template_name );
		
		// how did we do?
		if ( $located !== false ) break;
		
	}
	
	// handle extra params
	if ( $load !== false AND $located != '' ) {
		load_template( $located, $require_once );
	}
	
	// --<
	return $located;
	
}



