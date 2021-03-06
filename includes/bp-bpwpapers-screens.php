<?php

/**
 * BuddyPress Working Papers Screens
 */



// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;



/** Theme Compatability *******************************************************/

/**
 * The main theme compat class for BuddyPress Working Papers
 *
 * This class sets up the necessary theme compatability actions to safely output
 * group template parts to the_title and the_content areas of a theme.
 */
class BP_Working_Papers_Theme_Compat {



	/**
	 * Set up theme compatibility for the BuddyPress Working Papers component.
	 *
	 * @since 0.1
	 */
	function __construct() {

		// add theme comaptibility action
		add_action( 'bp_setup_theme_compat', array( $this, 'is_bpwpapers' ) );

	}



	/**
	 * Are we looking at something that needs BuddyPress Working Papers theme compatability?
	 *
	 * @since 0.1
	 */
	public function is_bpwpapers() {

		// Bail if not looking at a working paper component page
		if ( ! bp_is_bpwpapers_component() ) { return; }

		// BuddyPress Working Papers Directory
		if ( is_multisite() && ! bp_current_action() ) {

			// set is_directory flag
			bp_update_is_directory( true, 'bpwpapers' );

			// inform plugins
			do_action( 'bp_blogs_screen_index' );

			// add hooks
			add_filter( 'bp_get_buddypress_template',                array( $this, 'directory_template_hierarchy' ) );
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'directory_content'    ) );

		}

	}



	/**
	 * Add template hierarchy to theme compat for the BuddyPress Working Papers directory page.
	 *
	 * @since 0.1
	 *
	 * @param string $templates The templates from bp_get_theme_compat_templates().
	 * @return array $templates Array of custom templates to look for.
	 */
	public function directory_template_hierarchy( $templates ) {

		//die('here');

		// set up our templates based on priority
		$new_templates = apply_filters( 'bp_template_hierarchy_bpwpapers_directory', array(
			'bpwpapers/index-directory.php'
		) );

		// merge new templates with existing stack
		// @see bp_get_theme_compat_templates()
		$templates = array_merge( (array) $new_templates, $templates );

		// --<
		return $templates;

	}



	/**
	 * Update the global $post with directory data.
	 *
	 * @since 0.1
	 */
	public function directory_dummy_post() {

		// set title
		$title = bpwpapers_extension_plural();

		// create dummy post
		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => $title,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_bpwpapers',
			'post_status'    => 'publish',
			'is_page'        => true,
			'comment_status' => 'closed'
		) );

	}



	/**
	 * Filter the_content with the BuddyPress Working Papers index template part.
	 *
	 * @since 0.1
	 *
	 * @return string $content Buffered content
	 */
	public function directory_content() {

		// --<
		return bp_buffer_template_part( 'bpwpapers/index', null, false );

	}



} // class ends



// init
new BP_Working_Papers_Theme_Compat();



//==============================================================================



/**
 * Load the top-level BuddyPress Working Papers directory.
 *
 * @since 0.1
 */
function bpwpapers_screen_index() {

	// is this our component page?
	if ( is_multisite() && bp_is_bpwpapers_component() && !bp_current_action() ) {

		// make sure BP knows that it's our directory
		bp_update_is_directory( true, 'bpwpapers' );

		//print_r( ( bp_is_user() ? 'yes' : 'no' ) ); die();

		// allow plugins to handle this
		do_action( 'bpwpapers_screen_index' );

		// load our directory template
		bp_core_load_template( apply_filters( 'bpwpapers_template_index', 'bpwpapers/index' ) );

	}

}

// add action for the above
add_action( 'bp_screens', 'bpwpapers_screen_index', 20 );



/**
 * Load the BuddyPress Working Papers create screen.
 *
 * @since 0.1
 */
function bpwpapers_screen_create_a_blog() {

	// is this our target page?
	if ( is_multisite() && bp_is_bpwpapers_component() && bp_is_current_action( 'create' ) ) {

		// allow plugins to handle this
		do_action( 'bpwpapers_screen_create_a_blog' );

		// load our create template
		bp_core_load_template( apply_filters( 'bpwpapers_template_create_a_blog', 'bpwpapers/create' ) );

	}

}

// add action for the above
add_action( 'bp_screens', 'bpwpapers_screen_create_a_blog', 3 );



/**
 * Load the BuddyPress Working Papers Members screen.
 *
 * @since 0.1
 */
function bpwpapers_screen_member() {

	// is this our target page?
	if ( is_multisite() && bp_is_bpwpapers_component() && bp_is_user() ) {

		//print_r( ( bp_is_user() ? 'yes' : 'no' ) ); die();
		// make sure BP knows that it's our directory
		bp_update_is_directory( true, 'bpwpapers' );

		// allow plugins to handle this
		do_action( 'bpwpapers_screen_member' );

		// load our create template
		bp_core_load_template( apply_filters( 'bpwpapers_template_member', 'bpwpapers/member' ) );

	}

}

// add action for the above
add_action( 'bp_screens', 'bpwpapers_screen_member', 3 );



/**
 * Show the BuddyPress Working Papers create form.
 *
 * Copied from bp_show_blog_signup_form and amended
 *
 * @since 0.1
 */
function bpwpapers_show_working_paper_create_form( $blogname = '', $blog_title = '', $errors = '' ) {

	global $current_user;

	// are we submitting the form?
	if ( isset($_POST['submit']) ) {

		//print_r( 'submitted' ); die();

		// yes - validate
		bpwpapers_validate_signup();

	} else {

		// show form
		if ( ! is_wp_error($errors) ) {
			$errors = new WP_Error();
		}

		// allow definition of default variables
		$filtered_results = apply_filters(
			'signup_another_blog_init',
			array('blogname' => $blogname, 'blog_title' => $blog_title, 'errors' => $errors )
		);

		$blogname = $filtered_results['blogname'];
		$blog_title = $filtered_results['blog_title'];
		$errors = $filtered_results['errors'];

		$singular = strtolower( bpwpapers_extension_name() );
		$plural = strtolower( bpwpapers_extension_plural() );

		?>

		<div class="entry clearfix">

		<?php

		if ( $errors->get_error_code() ) {

			// show error message
			echo '<div id="message" class="error"><p>' .
					__( 'There was a problem, please correct the form below and try again.', 'bpwpapers' ) .
				 '</p></div>';
		}
		?>

		<p><?php

		echo sprintf(
			__( 'By filling out the form below, you can <strong>add a %s to your account</strong>.', 'bpwpapers' ),
			$singular
		);

		?></p>

		<p><?php

		echo sprintf(
			__( 'There is no limit to the number of %s that you can have, so create to your heart&#8217;s content.', 'bpwpapers' ),
			$plural
		);

		?></p>

		<form class="standard-form" id="setupform" method="post" action="">

			<input type="hidden" name="stage" value="gimmeanotherblog" />

			<?php do_action( 'signup_hidden_fields' ); ?>

			<?php bpwpapers_signup_blog( $blogname, $blog_title, $errors ); ?>

			<p class="submitbutton">
				<input id="submit" type="submit" name="submit" class="submit" value="<?php

				echo sprintf(
					__( 'Create %s', 'bpwpapers' ),
					bpwpapers_extension_name()
				);

				?>" />
			</p>

			<?php wp_nonce_field( 'bp_blog_signup_form' ) ?>

		</form>

		</div>

		<?php

	}

}



/**
 * Show the BuddyPress Working Papers create form content.
 *
 * Copied from bp_blogs_signup_blog and amended
 *
 * @since 0.1
 */
function bpwpapers_signup_blog( $blogname = '', $blog_title = '', $errors = '' ) {

	global $current_site;

	// get name
	$name = bpwpapers_extension_name();

	?>

	<div class="bpwpapers_title">

	<label for="blog_title"><?php

	// Working Paper title
	echo sprintf(
		__( 'What is the title of your %s?', 'bpwpapers' ),
		$name
	);

	 ?></label>

	<?php

	if ( $errmsg = $errors->get_error_message('blog_title') ) { ?>

		<p class="error"><?php echo $errmsg ?></p>

	<?php

	}

	// show title input
	echo '<input name="blog_title" type="text" id="blog_title" value="' . esc_html( $blog_title ) . '" />';

	?>

	</div>

	<div class="bpwpapers_url">

	<label for="blogname"><?php

	// Working Paper subfolder URL
	// do we actually want people to define their own URL?
	if( !is_subdomain_install() ) {
		echo sprintf(
			__( 'Choose a nice URL for your %s:', 'bpwpapers' ),
			$name
		);
	} else {
		echo '<label for="blogname">' . sprintf(
			__( '%s Domain:', 'bpwpapers' ),
			$name
		) . '</label>';
	}

	?></label><?php

	if ( $errmsg = $errors->get_error_message('blogname') ) { ?>

		<p class="error"><?php echo $errmsg ?></p>

	<?php }

	// if subfolders...
	if ( !is_subdomain_install() ) {

		// get protocol
		$http = ( is_ssl() ) ? 'https://' : 'http://';

		echo '<span class="prefix_address">' .
				$http . $current_site->domain . $current_site->path .
			 '</span> <input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /><br />';

	} else {
		echo '<input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /> <span class="suffix_address">.' . bp_blogs_get_subdomain_base() . '</span><br />';
	}

	?>

	</div>

	<input type="hidden" id="blog_public_on" name="blog_public" value="1" />
	<input type="hidden" value="1" id="cpmu-new-blog" name="cpmu-new-blog" />
	<input type="hidden" value="1" id="bpwpapers-new-blog" name="bpwpapers-new-blog" />

	<?php

	// do not allow plugins to hook in here... maybe reinstate with different action?
	//do_action('signup_blogform', $errors);

}



/**
 * Validate the BuddyPress Working Papers create form content.
 *
 * Copied from bp_blogs_validate_blog_signup and amended
 *
 * @since 0.1
 *
 * @return bool True if successful, false otherwise
 */
function bpwpapers_validate_signup() {

	global $wpdb, $current_user, $blogname, $blog_title, $errors, $domain, $path, $current_site;

	if ( !check_admin_referer( 'bp_blog_signup_form' ) )
		return false;

	$current_user = wp_get_current_user();

	if( !is_user_logged_in() )
		die();

	$result = bp_blogs_validate_blog_form();
	extract( $result );

	// do we have an error?
	if ( $errors->get_error_code() ) {

		unset( $_POST['submit'] );

		// this amend is needed because there's no way to override the text
		bpwpapers_show_working_paper_create_form( $blogname, $blog_title, $errors );

		return false;

	}

	// default to "not visible"
	$public = '0';

	// optionally make visible only to site owner
	if ( class_exists( 'ds_more_privacy_options' ) ) {
		$public = '-3';
	}

	$meta = apply_filters( 'signup_create_blog_meta', array( 'lang_id' => 1, 'public' => $public ) ); // deprecated
	$meta = apply_filters( 'add_signup_meta', $meta );

	// if this is a subdomain install, set up the site inside the root domain
	if ( is_subdomain_install() ) {
		$domain = $blogname . '.' . preg_replace( '|^www\.|', '', $current_site->domain );
	}

	// create new site
	$new_blog_id = wpmu_create_blog( $domain, $path, $blog_title, $current_user->ID, $meta, $wpdb->siteid );

	// create new group
	$new_group_id = bpwpapers_create_group( $blog_title, $description );

	// create linkage
	bpwpapers_link_blog_and_group( $new_blog_id, $new_group_id );

	// store user ID in list of authors...
	bpwpapers_grant_authorship( $current_user->ID, $new_blog_id );

	// show confirmation markup
	bpwpapers_confirm_signup( $domain, $path, $blog_title, $current_user->user_login, $current_user->user_email, $meta );

	// broadcast
	do_action( 'bpwpapers_signup_validated', $current_user->ID, $new_blog_id, $new_group_id );

	// --<
	return true;

}



/**
 * Confirm BuddyPress Working Papers creation.
 *
 * Copied from bp_blogs_confirm_blog_signup and amended
 *
 * @since 0.1
 */
function bpwpapers_confirm_signup( $domain, $path, $blog_title, $user_name, $user_email = '', $meta = '' ) {

	$protocol = is_ssl() ? 'https://' : 'http://';
	$blog_url = $protocol . $domain . $path;

	?>

	<div class="entry clearfix">

	<p class="bpwpapers-congrats"><?php

	echo sprintf(
		__( 'Congratulations! You have successfully created a new %s.', 'bpwpapers' ),
		strtolower( bpwpapers_extension_name() )
	);

	?></p>

	<p class="bpwpapers-new-url"><?php

	printf(
		__( 'The URL for your new %1$s is <a href="%2$s">%3$s</a>', 'bpwpapers' ),
		strtolower( bpwpapers_extension_name() ),
		$blog_url,
		$blog_url
	);

	?></p>

	<p class="bpwpapers-login-link"><?php

	printf(
		__( '<a href="%1$s">Login</a> as "%2$s" using your existing password.', 'bpwpapers' ),
		$blog_url . "wp-login.php",
		$user_name
	);

	?></p>

	<?php

	do_action('signup_finished');

	?>

	</div><!-- /.entry -->

	<?php

}



/**
 * Output button for visiting a group in the working papers loop.
 *
 * @since 0.1
 *
 * @see bp_get_blogs_visit_blog_button() for description of arguments.
 *
 * @param array $args See {@link bp_get_blogs_visit_blog_button()}.
 */
function bpwpapers_visit_group_button( $args = '' ) {
	echo bpwpapers_get_visit_group_button( $args );
}

// add after "visit working paper" button
//add_action( 'bp_directory_blogs_actions',  'bpwpapers_visit_group_button', 20 );

	/**
	 * Return button for visiting a group in the working papers loop.
	 *
	 * @see BP_Button for a complete description of arguments and return value.
	 *
	 * @param array $args {
	 *     Arguments are listed below, with their default values. For a
	 *     complete description of arguments, see {@link BP_Button}.
	 *     @type string $id Default: 'visit_blog'.
	 *     @type string $component Default: 'blogs'.
	 *     @type bool $must_be_logged_in Default: false.
	 *     @type bool $block_self Default: false.
	 *     @type string $wrapper_class Default: 'blog-button visit'.
	 *     @type string $link_href Permalink of the current blog in the loop.
	 *     @type string $link_class Default: 'blog-button visit'.
	 *     @type string $link_text Default: 'Visit Site'.
	 *     @type string $link_title Default: 'Visit Site'.
	 * }
	 * @return string The HTML for the Visit button.
	 */
	function bpwpapers_get_visit_group_button( $args = '' ) {

		// get group ID
		$group_id = bpwpapers_get_group_by_blog_id( bp_get_blog_id() );

		// get group
		$group = groups_get_group( array( 'group_id' => $group_id ) );

		// get group permalink
		$group_link = bp_get_group_permalink( $group );

		$defaults = array(
			'id'                => 'visit_bpwpgroup',
			'component'         => 'bpwpapers',
			'must_be_logged_in' => false,
			'block_self'        => false,
			'wrapper_class'     => 'bpwpgroup-button visit',
			'link_href'         => $group_link,
			'link_class'        => 'bpwpgroup-button visit',
			'link_text'         => __( 'Visit Group', 'bpwpapers' ),
			'link_title'        => __( 'Visit Group', 'bpwpapers' ),
		);

		$button = wp_parse_args( $args, $defaults );

		// Filter and return the HTML button
		return bp_get_button( apply_filters( 'bpwpapers_get_visit_group_button', $button ) );

	}



