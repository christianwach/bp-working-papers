<?php /*
================================================================================
BuddyPress Working Papers Admin Functions
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

The plugin's admin screen logic

--------------------------------------------------------------------------------
*/



/*
================================================================================
Class Name
================================================================================
*/

class BP_Group_Sites_Admin {

	/*
	============================================================================
	Properties
	============================================================================
	*/

	// plugin options
	public $bpwpapers_options = array();



	/**
	 * Initialises this object
	 *
	 * @return object
	 */
	function __construct() {

		// get options array, if it exists
		$this->bpwpapers_options = bpwpapers_site_option_get( 'bpwpapers_options', array() );
		//print_r( $this->bpwpapers_options ); die();

		// --<
		return $this;

	}



	/**
	 * Register hooks on plugin init
	 *
	 * @return void
	 */
	public function register_hooks() {

		// if on back end...
		if ( is_admin() ) {

			// add menu to Network Settings submenu
			add_action( 'network_admin_menu', array( $this, 'add_admin_menu' ), 30 );

		}

	}



	/**
	 * Actions to perform on plugin activation
	 *
	 * @return void
	 */
	public function activate() {

		// kick out if we are re-activating
		if ( bpwpapers_site_option_exists( 'bpwpapers_installed', 'false' ) === 'true' ) return;

		// get defaults
		$defaults = $this->_get_defaults();

		// default public comment visibility to "off"
		$this->option_set( 'bpwpapers_public', $defaults['public'] );

		// default name changes to "off"
		$this->option_set( 'bpwpapers_overrides', $defaults['overrides'] );

		// default plugin name to "Working Papers"
		$this->option_set( 'bpwpapers_overrides_title', $defaults['title'] );

		// default singular to "Working Paper"
		$this->option_set( 'bpwpapers_overrides_name', $defaults['name'] );

		// default plural to "Working Papers"
		$this->option_set( 'bpwpapers_overrides_plural', $defaults['plural'] );

		// default button to "Visit Working Paper"
		$this->option_set( 'bpwpapers_overrides_button', $defaults['button'] );

		// default slug to "working-papers"
		$this->option_set( 'bpwpapers_overrides_slug', $defaults['slug'] );

		// default authors to empty array
		$this->option_set( 'bpwpapers_authors', $defaults['authors'] );

		// default blog authors to empty array
		$this->option_set( 'bpwpapers_blog_authors', $defaults['blog_authors'] );

		// save options array
		$this->options_save();

		// set installed flag
		bpwpapers_site_option_set( 'bpwpapers_installed', 'true' );

	}



	/**
	 * Actions to perform on plugin deactivation (NOT deletion)
	 *
	 * @return void
	 */
	public function deactivate() {

		// we'll delete our options in 'uninstall.php'
		// but for testing let's delete them here
		delete_site_option( 'bpwpapers_options' );
		delete_site_option( 'bpwpapers_installed' );

	}



	/**
	 * Add an admin page for this plugin
	 *
	 * @return void
	 */
	public function add_admin_menu() {

		// we must be network admin
		if ( !is_super_admin() ) { return false; }



		// always add the admin page to the Settings menu
		$page = add_submenu_page(

			'settings.php',
			__( 'BuddyPress Working Papers', 'bpwpapers' ),
			__( 'BuddyPress Working Papers', 'bpwpapers' ),
			'manage_options',
			'bpwpapers_admin_page',
			array( $this, '_network_admin_form' )

		);

		// add styles only on our admin page, see:
		// http://codex.wordpress.org/Function_Reference/wp_enqueue_script#Load_scripts_only_on_plugin_pages
		add_action( 'admin_print_styles-'.$page, array( $this, 'add_admin_styles' ) );



		// try and update options
		$saved = $this->options_update();

	}



	/**
	 * Enqueue any styles and scripts needed by our admin page
	 *
	 * @return void
	 */
	public function add_admin_styles() {

		// add admin css
		wp_enqueue_style(

			'bpwpapers-admin-style',
			BP_WORKING_PAPERS_URL . 'assets/css/bpwpapers-admin.css',
			null,
			BP_WORKING_PAPERS_VERSION,
			'all' // media

		);

	}



	/**
	 * Update options based on content of form
	 *
	 * @return void
	 */
	public function options_update() {

	 	// kick out if the form was not submitted
		if( !isset( $_POST['bpwpapers_submit'] ) ) return;

		// check that we trust the source of the data
		check_admin_referer( 'bpwpapers_admin_action', 'bpwpapers_nonce' );

		// ---------------------------------------------------------------------

		// debugging switch for admins and network admins - if set, triggers do_debug() below
		if ( is_super_admin() AND isset( $_POST['bpwpapers_debug'] ) ) {
			$settings_debug = absint( $_POST['bpwpapers_debug'] );
			$debug = $settings_debug ? 1 : 0;
			if ( $debug ) { $this->do_debug(); }
			return;
		}

		// ---------------------------------------------------------------------

		// okay, we're through - get variables (remove this!)
		extract( $_POST );

		// get defaults
		$defaults = $this->_get_defaults();

		// ---------------------------------------------------------------------

		// set public comments visibility on/off option
		$bpwpapers_public = absint( $bpwpapers_public );
		$this->option_set( 'bpwpapers_public', ( $bpwpapers_public ? 1 : 0 ) );

		// ---------------------------------------------------------------------

		// set name change on/off option
		$bpwpapers_overrides = absint( $bpwpapers_overrides );
		$this->option_set( 'bpwpapers_overrides', ( $bpwpapers_overrides ? 1 : 0 ) );

		// get plugin title option
		$bpwpapers_overrides_title = esc_sql( $bpwpapers_overrides_title );

		// revert to default if we didn't get one...
		if ( $bpwpapers_overrides_title == '' ) {
			$bpwpapers_overrides_title = $defaults['title'];
		}

		// set title option
		$this->option_set( 'bpwpapers_overrides_title', $bpwpapers_overrides_title );

		// get name option
		$bpwpapers_overrides_name = esc_sql( $bpwpapers_overrides_name );

		// revert to default if we didn't get one...
		if ( $bpwpapers_overrides_name == '' ) {
			$bpwpapers_overrides_name = $defaults['name'];
		}

		// set name option
		$this->option_set( 'bpwpapers_overrides_name', $bpwpapers_overrides_name );

		// get plural option
		$bpwpapers_overrides_plural = esc_sql( $bpwpapers_overrides_plural );

		// revert to default if we didn't get one...
		if ( $bpwpapers_overrides_plural == '' ) {
			$bpwpapers_overrides_plural = $defaults['plural'];
		}

		// set plural option
		$this->option_set( 'bpwpapers_overrides_plural', $bpwpapers_overrides_plural );

		// get button option
		$bpwpapers_overrides_button = esc_sql( $bpwpapers_overrides_button );

		// revert to default if we didn't get one...
		if ( $bpwpapers_overrides_button == '' ) {
			$bpwpapers_overrides_button = $defaults['button'];
		}

		// set button option
		$this->option_set( 'bpwpapers_overrides_button', $bpwpapers_overrides_button );

		// set slug option
		$bpwpapers_overrides_slug = sanitize_title( $bpwpapers_overrides_plural );
		$this->option_set( 'bpwpapers_overrides_slug', $bpwpapers_overrides_slug );

		// ---------------------------------------------------------------------

		// set author name change on/off option
		$bppaperauthors_overrides = absint( $bppaperauthors_overrides );
		$this->option_set( 'bppaperauthors_overrides', ( $bppaperauthors_overrides ? 1 : 0 ) );

		// get plugin title option
		$bppaperauthors_overrides_title = esc_sql( $bppaperauthors_overrides_title );

		// revert to default if we didn't get one...
		if ( $bppaperauthors_overrides_title == '' ) {
			$bppaperauthors_overrides_title = $defaults['author_title'];
		}

		// set author title option
		$this->option_set( 'bppaperauthors_overrides_title', $bppaperauthors_overrides_title );

		// get name option
		$bppaperauthors_overrides_name = esc_sql( $bppaperauthors_overrides_name );

		// revert to default if we didn't get one...
		if ( $bppaperauthors_overrides_name == '' ) {
			$bppaperauthors_overrides_name = $defaults['author_name'];
		}

		// set author name option
		$this->option_set( 'bppaperauthors_overrides_name', $bppaperauthors_overrides_name );

		// get plural option
		$bppaperauthors_overrides_plural = esc_sql( $bppaperauthors_overrides_plural );

		// revert to default if we didn't get one...
		if ( $bppaperauthors_overrides_plural == '' ) {
			$bppaperauthors_overrides_plural = $defaults['author_plural'];
		}

		// set author plural option
		$this->option_set( 'bppaperauthors_overrides_plural', $bppaperauthors_overrides_plural );

		// ---------------------------------------------------------------------

		// save
		$this->options_save();

	}



	/**
	 * Save array as site option
	 *
	 * @return bool Success or failure
	 */
	public function options_save() {

		// save array as site option
		return bpwpapers_site_option_set( 'bpwpapers_options', $this->bpwpapers_options );

	}



	/**
	 * Return a value for a specified option
	 *
	 * @param string $option_name The name of the option
	 * @return bool Whether or not the option exists
	 */
	public function option_exists( $option_name = '' ) {

		// test for null
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_exists()', 'bpwpapers' ) );
		}

		// get existence of option in array
		return array_key_exists( $option_name, $this->bpwpapers_options );

	}



	/**
	 * Return a value for a specified option
	 *
	 * @param string $option_name The name of the option
	 * @param mixed $default The default value if the option does not exist
	 * @return mixed The option or the default
	 */
	public function option_get( $option_name = '', $default = false ) {

		// test for null
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_get()', 'bpwpapers' ) );
		}

		// get option
		return ( array_key_exists( $option_name, $this->bpwpapers_options ) ) ? $this->bpwpapers_options[ $option_name ] : $default;

	}



	/**
	 * Sets a value for a specified option
	 *
	 * @param string $option_name The name of the option
	 * @param mixed $value The value of the option
	 */
	public function option_set( $option_name = '', $value = '' ) {

		// test for null
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_set()', 'bpwpapers' ) );
		}

		// test for other than string
		if ( !is_string( $option_name ) ) {
			die( __( 'You must supply the option as a string to option_set()', 'bpwpapers' ) );
		}

		// set option
		$this->bpwpapers_options[ $option_name ] = $value;

	}



	/**
	 * Deletes a specified option
	 *
	 * @param string $option_name The name of the option
	 */
	public function option_delete( $option_name = '' ) {

		// test for null
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_delete()', 'bpwpapers' ) );
		}

		// unset option
		unset( $this->bpwpapers_options[ $option_name ] );

	}



	/**
	 * General debugging utility
	 * @return void
	 */
	public function do_debug() {

		return;

		/*
		// get current list
		$authors = bpwpapers_get_authors();

		foreach( $authors AS $author_id ) {

			// delete user meta
			delete_user_meta( $author_id, BP_WORKING_PAPERS_AUTHOR_META_KEY );

		}
		*/

		// default blog authors to empty array
		$this->option_set( 'bpwpapers_blog_authors', array() );

		$this->options_save();

	}



	/**
	 * Show our admin page
	 *
	 * @return void
	 */
	public function _network_admin_form() {

		// only allow network admins through
		if( is_super_admin() == false ) {
			wp_die( __( 'You do not have permission to access this page.', 'bpwpapers' ) );
		}

		// show message
		if ( isset( $_GET['updated'] ) ) {
			echo '<div id="message" class="updated"><p>'.__( 'Options saved.', 'bpwpapers' ).'</p></div>';
		}



		// sanitise admin page url
		$url = $_SERVER['REQUEST_URI'];
		$url_array = explode( '&', $url );
		if ( is_array( $url_array ) ) { $url = $url_array[0]; }



		// get defaults
		$defaults = $this->_get_defaults();



		// open admin page
		echo '
		<div class="wrap" id="bpwpapers_admin_wrapper">

		<div class="icon32" id="icon-options-general"><br/></div>

		<h2>'.__( 'BuddyPress Working Papers Settings', 'bpwpapers' ).'</h2>

		<form method="post" action="'.htmlentities( $url.'&updated=true' ).'">

		'.wp_nonce_field( 'bpwpapers_admin_action', 'bpwpapers_nonce', true, false ).'
		'.wp_referer_field( false )."\n\n";



		// show multisite options
		echo '
		<div id="bpwpapers_admin_options">

		<p>'.__( 'Configure how BuddyPress Working Papers behaves.', 'bpwpapers' ).'</p>'."\n\n";



		// init public comments checkbox
		$bpwpapers_public = '';
		if ( $this->option_get( 'bpwpapers_public' ) == '1' ) $bpwpapers_public = ' checked="checked"';

		// add global options
		echo '
		<hr>
		<h3>'.__( 'Global Options', 'bpwpapers' ).'</h3>

		<table class="form-table">

			<tr valign="top">
				<th scope="row"><label for="bpwpapers_public">'.__( 'Should comments in public groups be visible to readers who are not members of those groups?', 'bpwpapers' ).'</label></th>
				<td><input id="bpwpapers_public" name="bpwpapers_public" value="1" type="checkbox"'.$bpwpapers_public.' /></td>
			</tr>

		</table>'."\n\n";



		// init name change checkbox
		$bpwpapers_overrides = '';
		if ( $this->option_get( 'bpwpapers_overrides' ) == '1' ) $bpwpapers_overrides = ' checked="checked"';

		// init plugin title
		$bpwpapers_overrides_title = $this->option_get( 'bpwpapers_overrides_title' );
		if ( $bpwpapers_overrides_title == '' ) $bpwpapers_overrides_title = esc_attr( $defaults['title'] );

		// init name
		$bpwpapers_overrides_name = $this->option_get( 'bpwpapers_overrides_name' );
		if ( $bpwpapers_overrides_name == '' ) $bpwpapers_overrides_name = esc_attr( $defaults['name'] );

		// init plural
		$bpwpapers_overrides_plural = $this->option_get( 'bpwpapers_overrides_plural' );
		if ( $bpwpapers_overrides_plural == '' ) $bpwpapers_overrides_plural = esc_attr( $defaults['plural'] );

		// init button
		$bpwpapers_overrides_button = $this->option_get( 'bpwpapers_overrides_button' );
		if ( $bpwpapers_overrides_button == '' ) $bpwpapers_overrides_button = esc_attr( $defaults['button'] );

		// add working paper naming options
		echo '
		<hr>
		<h3>'.__( 'Working Paper Component Options', 'bpwpapers' ).'</h3>

		<table class="form-table">

			<tr valign="top">
				<th scope="row"><label for="bpwpapers_overrides">'.__( 'Enable component changes?', 'bpwpapers' ).'</label></th>
				<td><input id="bpwpapers_overrides" name="bpwpapers_overrides" value="1" type="checkbox"'.$bpwpapers_overrides.' /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="bpwpapers_overrides_title">'.__( 'Component Title', 'bpwpapers' ).'</label></th>
				<td><input id="bpwpapers_overrides_title" name="bpwpapers_overrides_title" value="'.$bpwpapers_overrides_title.'" type="text" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="bpwpapers_overrides_name">'.__( 'Singular name for a Working Paper', 'bpwpapers' ).'</label></th>
				<td><input id="bpwpapers_overrides_name" name="bpwpapers_overrides_name" value="'.$bpwpapers_overrides_name.'" type="text" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="bpwpapers_overrides_plural">'.__( 'Plural name for Working Papers', 'bpwpapers' ).'</label></th>
				<td><input id="bpwpapers_overrides_plural" name="bpwpapers_overrides_plural" value="'.$bpwpapers_overrides_plural.'" type="text" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="bpwpapers_overrides_button">'.__( 'Visit Working Paper button text', 'bpwpapers' ).'</label></th>
				<td><input id="bpwpapers_overrides_button" name="bpwpapers_overrides_button" value="'.$bpwpapers_overrides_button.'" type="text" /></td>
			</tr>

		</table>'."\n\n";



		// init name change checkbox
		$bppaperauthors_overrides = '';
		if ( $this->option_get( 'bppaperauthors_overrides' ) == '1' ) $bppaperauthors_overrides = ' checked="checked"';

		// init plugin title
		$bppaperauthors_overrides_title = $this->option_get( 'bppaperauthors_overrides_title' );
		if ( $bppaperauthors_overrides_title == '' ) $bppaperauthors_overrides_title = esc_attr( $defaults['author_title'] );

		// init name
		$bppaperauthors_overrides_name = $this->option_get( 'bppaperauthors_overrides_name' );
		if ( $bppaperauthors_overrides_name == '' ) $bppaperauthors_overrides_name = esc_attr( $defaults['author_name'] );

		// init plural
		$bppaperauthors_overrides_plural = $this->option_get( 'bppaperauthors_overrides_plural' );
		if ( $bppaperauthors_overrides_plural == '' ) $bppaperauthors_overrides_plural = esc_attr( $defaults['author_plural'] );

		// init button
		$bppaperauthors_overrides_button = $this->option_get( 'bppaperauthors_overrides_button' );
		if ( $bppaperauthors_overrides_button == '' ) $bppaperauthors_overrides_button = esc_attr( $defaults['author_button'] );

		// add working paper author naming options
		echo '
		<hr>
		<h3>'.__( 'Working Paper Author Component Options', 'bpwpapers' ).'</h3>

		<table class="form-table">

			<tr valign="top">
				<th scope="row"><label for="bppaperauthors_overrides">'.__( 'Enable component changes?', 'bpwpapers' ).'</label></th>
				<td><input id="bppaperauthors_overrides" name="bppaperauthors_overrides" value="1" type="checkbox"'.$bppaperauthors_overrides.' /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="bppaperauthors_overrides_title">'.__( 'Component Title', 'bpwpapers' ).'</label></th>
				<td><input id="bppaperauthors_overrides_title" name="bppaperauthors_overrides_title" value="'.$bppaperauthors_overrides_title.'" type="text" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="bppaperauthors_overrides_name">'.__( 'Singular name for a Working Paper Author', 'bpwpapers' ).'</label></th>
				<td><input id="bppaperauthors_overrides_name" name="bppaperauthors_overrides_name" value="'.$bppaperauthors_overrides_name.'" type="text" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="bppaperauthors_overrides_plural">'.__( 'Plural name for Working Paper Authors', 'bpwpapers' ).'</label></th>
				<td><input id="bppaperauthors_overrides_plural" name="bppaperauthors_overrides_plural" value="'.$bppaperauthors_overrides_plural.'" type="text" /></td>
			</tr>

		</table>'."\n\n";



		if ( is_super_admin() ) {

			// show debugger
			echo '
			<hr>
			<h3>'.__( 'Developer Testing', 'bpwpapers' ).'</h3>

			<table class="form-table">

				<tr>
					<th scope="row">'.__( 'Debug', 'bpwpapers' ).'</th>
					<td>
						<input type="checkbox" class="settings-checkbox" name="bpwpapers_debug" id="bpwpapers_debug" value="1" />
						<label class="civi_wp_member_sync_settings_label" for="bpwpapers_debug">'.__( 'Check this to trigger do_debug().', 'bpwpapers' ).'</label>
					</td>
				</tr>

			</table>'."\n\n";

		}



		// close #bpwpapers_admin_options
		echo '</div>'."\n\n";



		// close admin form
		echo '
		<p class="submit">
			<input type="submit" name="bpwpapers_submit" value="'.__( 'Save Changes', 'bpwpapers' ).'" class="button-primary" />
		</p>

		</form>

		</div>
		'."\n\n\n\n";

	}



	/**
	 * Get default values for this plugin
	 *
	 * @return array $defaults The default values for this plugin
	 */
	public function _get_defaults() {

		// init return
		$defaults = array();

		// default visibility of public group comments to off
		$defaults['public'] = 0;

		// default working papers component changes to off
		$defaults['overrides'] = 0;

		// default plugin title to "Working Papers"
		$defaults['title'] = __( 'Working Papers', 'bpwpapers' );

		// default singular to "Working Paper"
		$defaults['name'] = __( 'Working Paper', 'bpwpapers' );

		// default plural to "Working Papers"
		$defaults['plural'] = __( 'Working Papers', 'bpwpapers' );

		// default button to "Visit Working Paper"
		$defaults['button'] = __( 'Visit Working Paper', 'bpwpapers' );

		// default group slug to "working-paper"
		$defaults['slug'] = 'working-paper';

		// default author list
		$defaults['authors'] = array();

		// default working paper authors component changes to off
		$defaults['author_overrides'] = 0;

		// default plugin title to "Working Paper Authors"
		$defaults['author_title'] = __( 'Working Paper Authors', 'bpwpapers' );

		// default singular to "Working Paper Author"
		$defaults['author_name'] = __( 'Working Paper Author', 'bpwpapers' );

		// default plural to "Working Paper Authors"
		$defaults['author_plural'] = __( 'Working Paper Authors', 'bpwpapers' );

		// default blog authors list
		$defaults['blog_authors'] = array();

		// --<
		return $defaults;

	}



} // end class BP_Group_Sites_Admin



/*
================================================================================
Primary filters for overrides
================================================================================
*/



/**
 * Override group extension title
 *
 * @return str $title The group extension title
 */
function bpwpapers_extension_title() {
	
	// set default
	$title = __( 'Working Papers', 'bpwpapers' );

	// access object
	global $bp_working_papers;

	// are we overriding?
	if ( $bp_working_papers->admin->option_get( 'bpwpapers_overrides' ) ) {

		// override with our option
		$title = $bp_working_papers->admin->option_get( 'bpwpapers_overrides_title' );

	}

	// --<
	return apply_filters( 'bpwpapers_extension_title', $title );

}



/**
 * Override group extension singular name
 *
 * @param str $name The existing name
 * @return str $name The overridden name
 */
function bpwpapers_override_extension_name( $name ) {

	// access object
	global $bp_working_papers;

	// are we overriding?
	if ( $bp_working_papers->admin->option_get( 'bpwpapers_overrides' ) ) {

		// override with our option
		$name = $bp_working_papers->admin->option_get( 'bpwpapers_overrides_name' );

	}

	// --<
	return $name;

}

// add filter for the above
add_filter( 'bpwpapers_extension_name', 'bpwpapers_override_extension_name', 10, 1 );



/**
 * Override group extension plural
 *
 * @param str $plural The existing plural name
 * @return str $plural The overridden plural name
 */
function bpwpapers_override_extension_plural( $plural ) {

	// access object
	global $bp_working_papers;

	// are we overriding?
	if ( $bp_working_papers->admin->option_get( 'bpwpapers_overrides' ) ) {

		// override with our option
		$plural = $bp_working_papers->admin->option_get( 'bpwpapers_overrides_plural' );

	}

	// --<
	return $plural;

}

// add filter for the above
add_filter( 'bpwpapers_extension_plural', 'bpwpapers_override_extension_plural', 10, 1 );



/**
 * Override group extension slug
 *
 * @param str $slug The existing slug
 * @return str $slug The overridden slug
 */
function bpwpapers_override_extension_slug( $slug ) {

	// access object
	global $bp_working_papers;

	// are we overriding?
	if ( $bp_working_papers->admin->option_get( 'bpwpapers_overrides' ) ) {

		// override with our option
		$slug = $bp_working_papers->admin->option_get( 'bpwpapers_overrides_slug' );

	}

	// --<
	return $slug;

}

// add filter for the above
add_filter( 'bpwpapers_extension_slug', 'bpwpapers_override_extension_slug', 10, 1 );



/**
 * Override the name of the button on the BuddyPress Working Papers "sites" screen
 *
 * @param array $button The existing button
 * @return array $button The overridden button
 */
function bpwpapers_get_visit_site_button( $button ) {

	/**
	 * [id] => visit_blog
	 * [component] => blogs
	 * [must_be_logged_in] =>
	 * [block_self] =>
	 * [wrapper_class] => blog-button visit
	 * [link_href] => http://domain/site-slug/
	 * [link_class] => blog-button visit
	 * [link_text] => Visit Site
	 * [link_title] => Visit Site
	 */

	//print_r( $button ); die();

	// switch by blogtype
	if ( bpwpapers_is_working_paper( bp_get_blog_id() ) ) {

		// access object
		global $bp_working_papers;

		// set sensible default
		$label = __( 'Visit Working Paper', 'bpwpapers' );

		// are we overriding?
		if ( $bp_working_papers->admin->option_get( 'bpwpapers_overrides' ) ) {

			// override with our option
			$label = $bp_working_papers->admin->option_get( 'bpwpapers_overrides_button' );

		}

		$button['link_text'] = apply_filters( 'bpwpapers_visit_site_button_text', $label );
		$button['link_title'] = apply_filters( 'bpwpapers_visit_site_button_title', $label );

	}

	// --<
	return $button;

}

// add fliter for the above
add_filter( 'bp_get_blogs_visit_blog_button', 'bpwpapers_get_visit_site_button', 30, 1 );



//==============================================================================



/**
 * Override author component title
 *
 * @param str $title The existing title
 * @return str $title The overridden title
 */
function bppaperauthors_override_extension_title( $title ) {

	// access object
	global $bp_working_papers;

	// are we overriding?
	if ( $bp_working_papers->admin->option_get( 'bppaperauthors_overrides' ) ) {

		// override with our option
		$title = $bp_working_papers->admin->option_get( 'bppaperauthors_overrides_title' );

	}

	// --<
	return $title;

}

// add filter for the above
add_filter( 'bppaperauthors_extension_title', 'bppaperauthors_override_extension_title', 10, 1 );



/**
 * Override author component singular name
 *
 * @param str $name The existing name
 * @return str $name The overridden name
 */
function bppaperauthors_override_extension_name( $name ) {

	// access object
	global $bp_working_papers;

	// are we overriding?
	if ( $bp_working_papers->admin->option_get( 'bppaperauthors_overrides' ) ) {

		// override with our option
		$name = $bp_working_papers->admin->option_get( 'bppaperauthors_overrides_name' );

	}

	// --<
	return $name;

}

// add filter for the above
add_filter( 'bppaperauthors_extension_name', 'bppaperauthors_override_extension_name', 10, 1 );



/**
 * Override author component plural
 *
 * @param str $plural The existing plural name
 * @return str $plural The overridden plural name
 */
function bppaperauthors_override_extension_plural( $plural ) {

	// access object
	global $bp_working_papers;

	// are we overriding?
	if ( $bp_working_papers->admin->option_get( 'bppaperauthors_overrides' ) ) {

		// override with our option
		$plural = $bp_working_papers->admin->option_get( 'bppaperauthors_overrides_plural' );

	}

	// --<
	return $plural;

}

// add filter for the above
add_filter( 'bppaperauthors_extension_plural', 'bppaperauthors_override_extension_plural', 10, 1 );



/*
================================================================================
Globally available utility functions
================================================================================
*/



/**
 * Test existence of a specified site option
 *
 * @param str $option_name The name of the option
 * @return bool $exists Whether or not the option exists
 */
function bpwpapers_site_option_exists( $option_name = '' ) {

	// test for null
	if ( $option_name == '' ) {
		die( __( 'You must supply an option to bpwpapers_option_wpms_exists()', 'bpwpapers' ) );
	}

	// test by getting option with unlikely default
	if ( bpwpapers_site_option_get( $option_name, 'fenfgehgefdfdjgrkj' ) == 'fenfgehgefdfdjgrkj' ) {
		return false;
	} else {
		return true;
	}

}



/**
 * Return a value for a specified site option
 *
 * @param str $option_name The name of the option
 * @param str $default The default value of the option if it has no value
 * @return mixed $value the value of the option
 */
function bpwpapers_site_option_get( $option_name = '', $default = false ) {

	// test for null
	if ( $option_name == '' ) {
		die( __( 'You must supply an option to bpwpapers_site_option_get()', 'bpwpapers' ) );
	}

	// get option
	return get_site_option( $option_name, $default );

}



/**
 * Set a value for a specified site option
 *
 * @param str $option_name The name of the option
 * @param mixed $value The value to set the option to
 * @return bool $success If the value of the option was successfully saved
 */
function bpwpapers_site_option_set( $option_name = '', $value = '' ) {

	// test for null
	if ( $option_name == '' ) {
		die( __( 'You must supply an option to bpwpapers_site_option_set()', 'bpwpapers' ) );
	}

	// set option
	return update_site_option( $option_name, $value );

}



