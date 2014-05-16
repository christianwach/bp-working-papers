<?php
/*
--------------------------------------------------------------------------------
Plugin Name: BuddyPress Working Papers
Description: Create a relationship between BuddyPress Groups and WordPress Sites for Peer Review of Working Papers. 
Version: 0.1
Author: Christian Wach
Author URI: http://haystack.co.uk
Plugin URI: http://haystack.co.uk
Network: true
--------------------------------------------------------------------------------
*/



// set our version here
define( 'BP_WORKING_PAPERS_VERSION', '0.1' );

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

// set site option prefix
define( 'BP_WORKING_PAPERS_PREFIX', 'bpwpapers_blog_group_' );

// set working papers option name
define( 'BP_WORKING_PAPERS_OPTION', 'bpwpapers_group_blogs' );

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
	
	
	
	/** 
	 * Initialises this object
	 * 
	 * @return object
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
		
		// --<
		return $this;

	}
	
	
	
	//==========================================================================
	
	
	
	/**
	 * Actions to perform on plugin activation
	 * 
	 * @return void
	 */
	public function activate() {
	
		// pass through to admin
		$this->admin->activate();

	}
	
	
	
	/**
	 * Actions to perform on plugin deactivation (NOT deletion)
	 * 
	 * @return void
	 */
	public function deactivate() {
		
		// pass through to admin
		$this->admin->deactivate();

	}
	
	
		
	//==========================================================================
	
	
	
	/** 
	 * Load translation files
	 * 
	 * A good reference on how to implement translation in WordPress:
	 * http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
	 * 
	 * @return void
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
	 * Do stuff on plugin init
	 * 
	 * @return void
	 */
	public function initialise() {
		
		// load our linkage functions file
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-linkage.php' );
	
		// load our display functions file
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-display.php' );
	
		// load our group functions file
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-groups.php' );
	
		// load our activity functions file
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-activity.php' );
		
		// init object
		$this->activity = new BP_Working_Papers_Activity;
		
		// load our blogs extension
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-blogs-extension.php' );
	
		// load our sites component file
		require( BP_WORKING_PAPERS_PATH . 'includes/bp-bpwpapers-component.php' );
		
		// load our members extension
		require( BP_WORKING_PAPERS_PATH . 'includes/bpwpapers-authors.php' );
	
		// load our members component file
		require( BP_WORKING_PAPERS_PATH . 'includes/bp-bppaperauthors-component.php' );
		
	}
	
	
		
	/**
	 * Register hooks on plugin init
	 * 
	 * @return void
	 */
	public function register_hooks() {
	
		// hooks that always need to be present...
		$this->admin->register_hooks();
		$this->activity->register_hooks();
		
	}
	
	
		
	/**
	 * Register theme hooks on bp include
	 * 
	 * @return void
	 */
	public function register_theme_hooks() {
	
		// add our templates to the theme compatibility layer
		add_action( 'bp_register_theme_packages', array( $this, 'theme_compat' ) );
		
	}
	
	
	
	/**
	 * Add our templates to the theme stack
	 * 
	 * @return void
	 */
	public function theme_compat() {
	
		// add templates dir to BuddyPress
		bp_register_template_stack( 'bpwpapers_templates_dir',  16 );
		
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

// will use the 'uninstall.php' method
// see: http://codex.wordpress.org/Function_Reference/register_uninstall_hook



/**
 * Returns the path to our templates directory
 * 
 * @return string $path Path to this plugin's templates directory
 */
function bpwpapers_templates_dir() {
	
	// return filterable path to templates
	$path = apply_filters(
		'bpwpapers_templates_dir', // hook
		BP_WORKING_PAPERS_PATH . 'assets/templates' // path
	);
	
	/*
	print_r( array( 
		'method' => 'bpwpapers_theme_dir',
		'path' => $path,
	) ); die();
	*/
	
	// --<
	return $path;
	
}



