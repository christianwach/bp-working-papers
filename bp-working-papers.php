<?php
/*
--------------------------------------------------------------------------------
Plugin Name: BuddyPress Working Papers
Description: Create a relationship between BuddyPress Groups and WordPress Sites for Peer Review of Working Papers.
Version: 0.2
Author: Christian Wach
Author URI: http://haystack.co.uk
Plugin URI: http://haystack.co.uk
Network: true
--------------------------------------------------------------------------------
*/



// set our version here
define( 'BP_WORKING_PAPERS_VERSION', '0.2' );

// store reference to this file
if ( !defined( 'BP_WORKING_PAPERS_FILE' ) ) {
	define( 'BP_WORKING_PAPERS_FILE', __FILE__ );
}

// store URL to this plugin's directory
if ( !defined( 'BP_WORKING_PAPERS_URL' ) ) {
	define( 'BP_WORKING_PAPERS_URL', plugin_dir_url( BP_WORKING_PAPERS_FILE ) );
}
// store PATH to this plugin's directory
if ( !defined( 'BP_WORKING_PAPERS_PATH' ) ) {
	define( 'BP_WORKING_PAPERS_PATH', plugin_dir_path( BP_WORKING_PAPERS_FILE ) );
}

// set site option prefix for storing the group ID for a blog ID
define( 'BP_WORKING_PAPERS_BLOG_GROUP_PREFIX', 'bpwpapers_blog_group_' );

// set working papers option name
define( 'BP_WORKING_PAPERS_OPTION', 'bpwpapers_group_blogs' );

// set working papers option name
define( 'BP_WORKING_PAPERS_GROUP_PERMALINK', 'bpwpapers_group_permalink' );

// set working paper author meta key name
define( 'BP_WORKING_PAPERS_AUTHOR_META_KEY', 'bpwpapers_author' );



/*
================================================================================
Class Name
================================================================================
*/

class BP_Working_Papers {

	/*
	============================================================================
	Properties
	============================================================================
	*/

	// admin object
	public $admin;

	// activity object
	public $activity;

	// template object
	public $template;



	/**
	 * Initialises this object.
	 *
	 * @since 0.1
	 */
	function __construct() {

		// load our admin class file
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-admin.php' );

		// init object, sending reference to this class
		$this->admin = new BP_Group_Sites_Admin( $this );

		// use translation files
		add_action( 'plugins_loaded', array( $this, 'enable_translation' ) );

		// add actions for plugin init on BuddyPress init
		add_action( 'bp_loaded', array( $this, 'initialise' ) );
		add_action( 'bp_loaded', array( $this, 'register_hooks' ) );
		add_action( 'bp_include', array( $this, 'register_theme_hooks' ) );

		// action for Follow Blogs compatibility
		add_action( 'bp_follow_blogs_loaded', array( $this, 'follow_blogs_init' ), 25 );

	}



	//==========================================================================



	/**
	 * Actions to perform on plugin activation.
	 *
	 * @since 0.1
	 */
	public function activate() {

		// pass through to admin
		$this->admin->activate();

	}



	/**
	 * Actions to perform on plugin deactivation (NOT deletion)
	 *
	 * @since 0.1
	 */
	public function deactivate() {

		// pass through to admin
		$this->admin->deactivate();

	}



	//==========================================================================



	/**
	 * Load translation files.
	 *
	 * @since 0.1
	 *
	 * A good reference on how to implement translation in WordPress:
	 * http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
	 */
	public function enable_translation() {

		// not used, as there are no translations as yet
		load_plugin_textdomain(

			// unique name
			'bpwpapers',

			// deprecated argument
			false,

			// relative path to directory containing translation files
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'

		);

	}



	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// load our linkage functions file
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-linkage.php' );

		// load our display functions file
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-display.php' );

		// load our group functions file
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-groups.php' );

		// load our template functions file
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-template.php' );

		// init object
		$this->template = new BP_Working_Papers_Template;

		// load our activity functions file
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-activity.php' );

		// init object
		$this->activity = new BP_Working_Papers_Activity;

		// load our blogs extension
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-blogs.php' );

		// load our sites component file
		require( BP_WORKING_PAPERS_PATH . 'includes/bp-bpwpapers-component.php' );

		// load our members extension
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-authors.php' );

		// load our members component file
		require( BP_WORKING_PAPERS_PATH . 'includes/bp-bppaperauthors-component.php' );

	}



	/**
	 * Register hooks on plugin init.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// hooks that always need to be present...
		$this->admin->register_hooks();
		$this->template->register_hooks();
		$this->activity->register_hooks();

	}



	/**
	 * Register theme hooks on bp include.
	 *
	 * @since 0.1
	 */
	public function register_theme_hooks() {

		// add our templates to the theme compatibility layer
		add_action( 'bp_register_theme_packages', array( $this, 'theme_compat' ) );

	}



	/**
	 * Add our templates to the theme stack.
	 *
	 * @since 0.1
	 */
	public function theme_compat() {

		// add templates dir to BuddyPress
		bp_register_template_stack( 'bpwpapers_templates_dir',  16 );

	}



	/**
	 * Include file only when Follow Blogs is loaded.
	 *
	 * @since 0.1
	 */
	public function follow_blogs_init() {

		// load our compatibility file
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-follow.php' );

		// init object
		$this->follow = new BP_Working_Papers_Follow;

	}



} // class ends



//==============================================================================



// init plugin
global $bp_working_papers;
$bp_working_papers = new BP_Working_Papers;

// activation
register_activation_hook( __FILE__, array( $bp_working_papers, 'activate' ) );

// deactivation
register_deactivation_hook( __FILE__, array( $bp_working_papers, 'deactivate' ) );

// this plugin uses the 'uninstall.php' method
// see: http://codex.wordpress.org/Function_Reference/register_uninstall_hook



/**
 * Returns the path to our templates directory.
 *
 * @since 0.1
 *
 * @return string $path Path to this plugin's templates directory
 */
function bpwpapers_templates_dir() {

	// return filterable path to templates
	$path = apply_filters(
		'bpwpapers_templates_dir', // hook
		BP_WORKING_PAPERS_PATH . 'assets/templates' // path
	);

	// --<
	return $path;

}



