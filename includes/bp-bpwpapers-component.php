<?php

/**
 * BuddyPress Working Papers Component
 *
 * The working papers component, for listing working papers.
 */

// exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;



/**
 * Class definition
 */
class BP_Working_Papers_Component extends BP_Component {



	/**
	 * Start the component creation process.
	 *
	 * @return void
	 */
	function __construct() {

		// get BP reference
		$bp = buddypress();

		// store component ID
		$this->id = 'bpwpapers';
		//print_r( $this->id ); die();

		// store component name
		// NOTE: ideally we'll use BP theme compatibility - see bpwpapers_load_template_filter() below
		$this->name = bpwpapers_extension_title();

		// add this component to active components
		$bp->active_components[$this->id] = '1';

		// init parent
		parent::start(
			$this->id, // unique ID, also used as slug
			$this->name,
			BP_WORKING_PAPERS_PATH,
			null // don't need menu item in WP admin bar
		);

		/**
		 * BuddyPress-dependent plugins are loaded too late to depend on BP_Component's
		 * hooks, so we must call the function directly.
		 */
		 $this->includes();

	}



	/**
	 * Include our component's files
	 *
	 * @return void
	 */
	public function includes( $includes = array() ) {

		// include screens file
		include( BP_WORKING_PAPERS_PATH . 'includes/bp-bpwpapers-screens.php' );

	}



	/**
	 * Set up global settings for this component.
	 *
	 * @see BP_Component::setup_globals() for description of parameters.
	 * @param array $args See {@link BP_Component::setup_globals()}.
	 * @return void
	 */
	public function setup_globals( $args = array() ) {

		// get BP reference
		$bp = buddypress();

		// construct search string
		$search_string = sprintf(
			__( 'Search %s...', 'bpwpapers' ),
			bpwpapers_extension_plural()
		);

		// construct args
		$args = array(
			// non-multisite installs don't need a top-level BuddyPress Working Papers directory, since there's only one site
			'root_slug'             => isset( $bp->pages->{$this->id}->slug ) ? $bp->pages->{$this->id}->slug : $this->id,
			'has_directory'         => true,
			'search_string'         => $search_string,
		);

		// set up the globals
		parent::setup_globals( $args );

	}



	/**
	 * Create component navigation (Member > Working Papers)
	 *
	 * @return void
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// only setup nav on profile pages
		if ( is_multisite() AND bp_is_user() ) {

			// construct name
			$name = sprintf(
				__( '%s <span>%d</span>', 'bpwpapers' ),
				bpwpapers_extension_plural(),
				bpwpapers_get_total_paper_count_for_user( bp_displayed_user_id() )
			);

			// show a link in the member's profile
			$main_nav = array(
				'name' 		      => $name,
				'slug' 		      => bpwpapers_get_slug(),
				'position' 	      => 30
			);

		}

		// set parent
		parent::setup_nav( $main_nav, $sub_nav );

	}



} // class ends



// set up this component now, since this file is included on bp_loaded
buddypress()->bpwpapers = new BP_Working_Papers_Component();



//==============================================================================



/**
 * Check whether the current page is part of the BuddyPress Working Papers component.
 *
 * @return bool True if the current page is part of the BuddyPress Working Papers component.
 */
function bp_is_bpwpapers_component() {

	// is this our component?
	if ( is_multisite() AND bp_is_current_component( 'bpwpapers' ) ) {

		// yep
		return true;

	}

	// --<
	return false;

}



/**
 * A custom load template filter for this component
 *
 * @return string $found_template Path to the found template
 */
function bpwpapers_load_template_filter( $found_template, $templates ) {

	// check for BP theme compatibility here?
	//print_r( 'here' ); die();

	// only filter the template location when we're on one of our component's pages
	if ( is_multisite() && bp_is_bpwpapers_component() ) {

		// we've got to find the template manually
		foreach ( (array) $templates as $template ) {
			if ( file_exists( get_stylesheet_directory() . '/' . $template ) ) {
				$filtered_templates[] = get_stylesheet_directory() . '/' . $template;
			} elseif ( is_child_theme() && file_exists( get_template_directory() . '/' . $template ) ) {
				$filtered_templates[] = get_template_directory() . '/' . $template;
			} else {
				$filtered_templates[] = BP_WORKING_PAPERS_PATH . 'assets/templates/' . $template;
			}
		}

		// should be one by now
		$found_template = $filtered_templates[0];

		// --<
		return apply_filters( 'bpwpapers_load_template_filter', $found_template );

	}

	// --<
	return $found_template;

}

// add filter for the above
// NOTE: adding this disables BP_Working_Papers_Theme_Compat
add_filter( 'bp_located_template', 'bpwpapers_load_template_filter', 10, 2 );



/**
 * Load our loop when requested
 *
 * @return void
 */
function bpwpapers_object_template_loader() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}

	// Bail if no object passed
	if ( empty( $_POST['object'] ) ) {
		return;
	}

	// Sanitize the object
	$object = sanitize_title( $_POST['object'] );

	// Bail if object is not an active component to prevent arbitrary file inclusion
	if ( ! bp_is_active( $object ) ) {
		return;
	}

	//trigger_error( print_r( $_POST, true ), E_USER_ERROR ); die();

	// enable visit button
	if ( bp_is_active( 'bpwpapers' ) ) {
		add_action( 'bp_directory_blogs_actions',  'bp_blogs_visit_blog_button' );
	}

 	/**
	 * AJAX requests happen too early to be seen by bp_update_is_directory()
	 * so we do it manually here to ensure templates load with the correct
	 * context. Without this check, templates will load the 'single' version
	 * of themselves rather than the directory version.
	 */
	if ( ! bp_current_action() ) {
		bp_update_is_directory( true, bp_current_component() );
	}

	// Locate the object template
	bp_get_template_part( "$object/$object-loop" );
	exit();

}

// add ajax actions for the above
add_action( 'wp_ajax_bpwpapers_filter', 'bpwpapers_object_template_loader' );
add_action( 'wp_ajax_nopriv_bpwpapers_filter', 'bpwpapers_object_template_loader' );



/**
 * Output the working papers slug
 * @uses bpwpapers_get_slug()
 */
function bpwpapers_slug() {
	echo bpwpapers_get_slug();
}

	/**
	 * Return the working papers slug
	 *
	 * @return string The 'blogs' slug.
	 */
	function bpwpapers_get_slug() {
		return apply_filters( 'bpwpapers_get_slug', buddypress()->bpwpapers->slug );
	}



/**
 * Output the working papers component root slug.
 * @uses bpwpapers_get_root_slug()
 */
function bpwpapers_root_slug() {
	echo bpwpapers_get_root_slug();
}

	/**
	 * Return the working papers component root slug
	 *
	 * @return string The 'blogs' root slug.
	 */
	function bpwpapers_get_root_slug() {
		return apply_filters( 'bpwpapers_get_root_slug', buddypress()->bpwpapers->root_slug );
	}



