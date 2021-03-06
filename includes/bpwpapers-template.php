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

There are additional methods here to adapt the CommentPress instance such that
the associated group does not appear elsewhere in the site as well as utilities
to show working paper groups - eg the widgets

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

	// group ID
	public $group_id = false;



	/**
	 * Initialises this object.
	 *
	 * @since 0.1
	 */
	function __construct() {

		// nothing

	}



	/**
	 * Register hooks for this class.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// for main site
		if ( is_main_site() ) {

			// add widget areas for working papers homepage
			add_action( 'widgets_init', array( $this, 'register_sidebars' ) );

			// add widgets for working papers homepage
			add_action( 'widgets_init', array( $this, 'register_widgets' ) );

			// add widget CSS file
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_widget_styles' ) );

		}

		// if the current blog is a working paper...
		if ( bpwpapers_is_working_paper( get_current_blog_id() ) ) {

			// store group ID for this blog
			$this->group_id = bpwpapers_get_group_by_blog_id( get_current_blog_id() );

			// register post type
			add_action( 'init', array( $this, 'register_cpt' ) );

			// intercept publish toggle when WP is set up
			add_action( 'wp', array( $this, 'publish_toggle_intercept' ) );

			// intercept BuddyPress Group Email Subscription stuff
			add_action( 'ass_login_redirect_to', array( $this, 'intercept_email_login_url' ), 10, 2 );
			add_filter( 'bp_ass_activity_notification_message', array( $this, 'intercept_email_text' ), 10, 2 );

			// front end
			if ( ! is_admin() ) {

				// filter template searches
				add_filter( 'template_include', array( $this, 'include_template' ), 10, 1 );

				// add CSS files
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

				// add Javascript files
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

				// add navigation items for groups
				add_filter( 'cp_nav_before_special_pages', array( $this, 'group_navigation_link' ) );

				// override CommentPress "Title Page" (after CPMU)
				add_filter( 'cp_nav_title_page_title', array( $this, 'filter_nav_title' ), 30 );

				// override new site link
				add_filter( 'cp_user_links_new_site_link', array( $this, 'filter_new_site_link' ), 30 );

				// show working paper author avatar in Contents column
				//add_action( 'cp_content_tab_before', array( $this, 'show_author' ) );

				// show working paper author avatar in Header
				add_filter( 'commentpress_header_image_post_customizer', array( $this, 'author_avatar' ), 10, 1 );

				// show join button in header
				add_action( 'commentpress_header_before', array( $this, 'join_button' ) );

				// filter join button content
				add_filter( 'bp_get_group_join_button', array( $this, 'join_button_content' ), 20, 1 );

				// insert publish button in footer by default
				add_action( 'wp_footer', array( $this, 'publish_button' ) );

				// if CommentPress is present, this hook will fire
				add_action( 'commentpress_loaded', array( $this, 'publish_button_commentpress' ) );

			}

		}

	}



	/**
	 * Register custom post type.
	 *
	 * @since 0.1
	 */
	public function register_cpt() {

		// only call this once
		static $registered;

		// bail if already done
		if ( $registered ) return;

		// working paper group
		register_post_type( 'bpwpaper', array(

			'label' => __( 'Working Paper Groups', 'bpwpapers' ),
			'description' => '',
			'public' => true,
			'show_ui' => false,
			'show_in_nav_menus' => false,
			'show_in_menu' => false,
			'capability_type' => 'post',
			'hierarchical' => false,
			'rewrite' => array(
				'slug' => apply_filters( 'bpwpapers_group_cpt_slug', 'group' ),
			),
			'has_archive' => false,
			'query_var' => true,
			'exclude_from_search' => true,
			'can_export' => false,
			'supports' => array( 'title' ),
			'labels' => array (
				'name' => __( 'Groups', 'bpwpapers' ),
				'singular_name' => __( 'Group', 'bpwpapers' ),
				'menu_name' => __( 'Groups', 'bpwpapers' ),
				'add_new' => __( 'Add Group', 'bpwpapers' ),
				'add_new_item' => __( 'Add New Group', 'bpwpapers' ),
				'edit' => __( 'Edit', 'bpwpapers' ),
				'edit_item' => __( 'Edit Group', 'bpwpapers' ),
				'new_item' => __( 'New Group', 'bpwpapers' ),
				'view' => __( 'View Group', 'bpwpapers' ),
				'view_item' => __( 'View Group', 'bpwpapers' ),
				'search_items' => __( 'Search Groups', 'bpwpapers' ),
				'not_found' => __( 'No Groups found', 'bpwpapers' ),
				'not_found_in_trash' => __( 'No Groups found in Trash', 'bpwpapers' ),
				'parent' => __( 'Parent Group', 'bpwpapers' ),
			)

		) );

		//flush_rewrite_rules();

		// flag
		$registered = true;

	}



	/**
	 * Create a page for the Working Paper group.
	 *
	 * @since 0.1
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
			__( 'Activity', 'bpwpapers' )
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
	 * Intercept template for our custom group page.
	 *
	 * @since 0.1
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

			// override is_buddypress_special_page()
			add_filter( 'cp_is_buddypress_special_page', array( $this, 'is_buddypress_special_page' ), 100, 1 );

			// override commentable flag
			add_filter( 'cp_is_commentable', array( $this, 'is_commentable' ), 100, 1 );

		}

		// access plugin
		global $bp_working_papers;

		// add filter options
		add_action( 'bp_group_activity_filter_options', array( $bp_working_papers->activity, 'filter_option_posts' ) );
		add_action( 'bp_group_activity_filter_options', array( $bp_working_papers->activity, 'filter_option_comments' ) );

		// set flag
		$this->is_group = true;

		// --<
		return $found_template;

	}



	/**
	 * Find a template file.
	 *
	 * @since 0.1
	 *
	 * @param string $template Relative path to a template file
	 * @return string $full_path The full path to the template file if one is located, false otherwise
	 */
	public function find_template( $template ) {

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
	 * Register stylesheets on working paper sub-sites.
	 *
	 * @since 0.1
	 */
	public function enqueue_styles() {

		// add admin css
		wp_enqueue_style(

			'bpwpapers-commentpress',
			BP_WORKING_PAPERS_URL . 'assets/css/bpwpapers-commentpress.css',
			null,
			BP_WORKING_PAPERS_VERSION,
			'all' // media

		);

		// enqueue BuddyPress Group Email Subscription styles
		$style_url  = plugins_url() . '/buddypress-group-email-subscription/css/bp-activity-subscription-css.css';
		$style_file = WP_PLUGIN_DIR . '/buddypress-group-email-subscription/css/bp-activity-subscription-css.css';
		if (file_exists($style_file)) {
			wp_register_style('activity-subscription-style', $style_url);
			wp_enqueue_style('activity-subscription-style');
		}

	}



	/**
	 * Register Javascripts on working paper sub-sites.
	 *
	 * @since 0.1
	 */
	public function enqueue_scripts() {

		// enqueue BuddyPress Group Email Subscription script
		wp_register_script(
			'bp-activity-subscription-js',
			plugins_url() . '/buddypress-group-email-subscription/bp-activity-subscription-js.js',
			array( 'jquery' )
		);
		wp_enqueue_script( 'bp-activity-subscription-js' );
		wp_localize_script( 'bp-activity-subscription-js', 'bp_ass', array(
			'mute'   => __( 'Mute', 'bp-ass' ),
			'follow' => __( 'Follow', 'bp-ass' ),
			'error'  => __( 'Error', 'bp-ass' )
		) );

	}



	/**
	 * Register widget areas for BP Working Papers plugin.
	 *
	 * @since 0.1
	 */
	public function register_sidebars() {

		// define an area where a widget may be placed
		register_sidebar( array(
			'name' => __( 'Working Papers Top', 'bpwpapers' ),
			'id' => 'working-papers-top',
			'description' => __( 'A widget area at the top of the Working Papers Homepage', 'bpwpapers' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => "</div>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

		// define an area where a widget may be placed
		register_sidebar( array(
			'name' => __( 'Working Papers Middle Left', 'bpwpapers' ),
			'id' => 'working-papers-middle-left',
			'description' => __( 'A widget area at the middle left of the Working Papers Homepage', 'bpwpapers' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => "</div>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

		// define an area where a widget may be placed
		register_sidebar( array(
			'name' => __( 'Working Papers Middle Right', 'bpwpapers' ),
			'id' => 'working-papers-middle-right',
			'description' => __( 'A widget area at the middle right of the Working Papers Homepage', 'bpwpapers' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => "</div>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

		// define an area where a widget may be placed
		register_sidebar( array(
			'name' => __( 'Working Papers Lower', 'bpwpapers' ),
			'id' => 'working-papers-lower',
			'description' => __( 'A spanning widget area below the middle of the Working Papers Homepage', 'bpwpapers' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => "</div>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

		// define an area where a widget may be placed
		register_sidebar( array(
			'name' => __( 'Working Papers Bottom Left', 'bpwpapers' ),
			'id' => 'working-papers-bottom-left',
			'description' => __( 'A widget area at the bottom left of the Working Papers Homepage', 'bpwpapers' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => "</div>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

		// define an area where a widget may be placed
		register_sidebar( array(
			'name' => __( 'Working Papers Bottom Right', 'bpwpapers' ),
			'id' => 'working-papers-bottom-right',
			'description' => __( 'A widget area at the bottom right of the Working Papers Homepage', 'bpwpapers' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => "</div>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

		// define an area where a widget may be placed
		register_sidebar( array(
			'name' => __( 'Working Papers Sidebar', 'bpwpapers' ),
			'id' => 'working-papers-sidebar',
			'description' => __( 'A widget area in the sidebar of the Working Papers pages', 'bpwpapers' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => "</div>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

	}



	/**
	 * Register widgets for BP Working Papers plugin.
	 *
	 * @since 0.1
	 */
	public function register_widgets() {

		// include widgets
		require_once( BP_WORKING_PAPERS_PATH . '/includes/widgets/bpwpapers-author-widget.php' );
		require_once( BP_WORKING_PAPERS_PATH . '/includes/widgets/bpwpapers-reviewer-widget.php' );
		require_once( BP_WORKING_PAPERS_PATH . '/includes/widgets/bpwpapers-paper-widget.php' );
		require_once( BP_WORKING_PAPERS_PATH . '/includes/widgets/bpwpapers-activity-widget.php' );
		require_once( BP_WORKING_PAPERS_PATH . '/includes/widgets/bpwpapers-recent-widget.php' );

	}



	/**
	 * Register stylesheets on working paper sub-sites.
	 *
	 * @since 0.1
	 */
	public function enqueue_widget_styles() {

		// add admin css
		wp_enqueue_style(

			'bpwpapers-widgets',
			BP_WORKING_PAPERS_URL . 'assets/css/bpwpapers-widgets.css',
			null,
			BP_WORKING_PAPERS_VERSION,
			'all' // media

		);

	}



	/**
	 * Override the login URL that BuddyPress Group Email Subscription prints in emails.
	 *
	 * @since 0.1
	 *
	 * @param string $url The existing BuddyPress Group Email Subscription login URL
	 * @param string $context The BuddyPress Group Email Subscription context
	 * @return string $url The overridden login URL
	 */
	public function intercept_email_login_url( $url, $context ) {

		// target the settings link
		if ( $context != 'notifications' ) return $url;

		// sanity check
		if ( ! isset( $this->group_id ) ) return $url;

		// get the permalink for the group page
		$permalink = groups_get_groupmeta( $this->group_id, BP_WORKING_PAPERS_GROUP_PERMALINK );

		// sanity check
		if ( empty( $permalink ) ) return $url;

		// --<
		return $permalink;

	}



	/**
	 * Override the text that BuddyPress Group Email Subscription sends in emails.
	 *
	 * @since 0.1
	 *
	 * @param string $text The existing BuddyPress Group Email Subscription email text
	 * @param array $params The params that BuddyPress Group Email Subscription uses to construct the text
	 * @return string $text The overridden email text
	 */
	public function intercept_email_text( $text, $params ) {

		/*
		// default params
		$params = array(
			'message'           => $message,
			'notice'            => $notice,
			'user_id'           => $user_id,
			'subscription_type' => $group_status,
			'content'           => $the_content,
			'settings_link'     => ! empty( $settings_link ) ? $settings_link : '',
		)
		*/

		/*
		trigger_error( print_r( array(
			'method' => 'intercept_email_text',
			'text' => $text,
			'params' => $params,
		), true ), E_USER_ERROR ); die();
		*/

		// sanity check
		if ( ! isset( $this->group_id ) ) return $text;

		// get name
		$name = bpwpapers_extension_name();

		// replace instances of "group"
		$text = str_replace(
			' for this group', // instance
			' for this ' . strtolower( $name ), // replacement
			$text
		);

		// --<
		return $text;

	}



	/**
	 * Tell CommentPress that the group page is a BuddyPress page.
	 *
	 * @since 0.1
	 *
	 * @return boolean True, because this is a BuddyPress page
	 */
	public function is_buddypress_special_page( $is_bp_page ) {

		// --<
		return true;

	}



	/**
	 * Tell CommentPress that the group page is not commentable.
	 *
	 * @since 0.1
	 *
	 * @return boolean False, because this is never commentable
	 */
	public function is_commentable( $is_commentable ) {

		// --<
		return false;

	}



	/**
	 * Adds a link to the Special Pages menu in CommentPress themes.
	 *
	 * @since 0.1
	 */
	public function group_navigation_link() {

		// is a CommentPress theme active?
		if ( function_exists( 'commentpress_setup' ) ) {

			// init HTML output
			$html = '';

			// get group page ID
			$page_id = get_option( 'bpwpapers_group_page', false );

			// sanity check
			if ( $page_id === false ) return;

			// get post
			$post = get_post( $page_id );

			// sanity check
			if ( ! is_object( $post ) ) return;

			// get title
			$title = sprintf(
				__( 'Activity in this %s', 'bpwpapers' ),
				bpwpapers_extension_name()
			);

			// construct link
			$link = get_permalink( $post->ID );

			// init class
			$class = '';

			// is this page active?
			if ( isset( $this->is_group ) AND $this->is_group === true ) {

				// override
				$class = ' class="active_page"';

			}

			// construct item
			$html .= '<li'.$class.'>'.
						'<a href="'.$link.'" id="btn_bpwpaper" class="css_btn" title="'.esc_attr( $title ).'">'.
							$title.
						'</a>'.
					 '</li>';

			// output
			echo $html;

		}

	}



	/**
	 * Filter the title of the link to the document homepage.
	 *
	 * @since 0.1
	 *
	 * @return boolean False, because this is never commentable
	 */
	public function filter_nav_title( $title ) {

		// construct title
		$title = sprintf(
			__( '%s Home Page', 'bpwpapers' ),
			bpwpapers_extension_name()
		);

		// --<
		return $title;

	}



	/**
	 * Filter the link to the "new site" page.
	 *
	 * @since 0.1
	 *
	 * @return string Empty string because we don't want that link
	 */
	public function filter_new_site_link( $link ) {

		// --<
		return '';

	}



	//==========================================================================



	/**
	 * Show author avatar in Contents column.
	 *
	 * @since 0.1
	 */
	public function show_author() {

		// get original author
		$author_id = get_option( 'bpwpapers_original_author', false );

		// santiy check
		if ( $author_id === false ) return;

		// show avatar
		echo '
			<div class="paper_author_avatar">
			' . get_avatar( $author_id, $size='150' ) . '
			</div>
		';

	}



	/**
	 * Show author avatar in Header.
	 *
	 * @since 0.1
	 */
	public function author_avatar() {

		// get original author
		$author_id = get_option( 'bpwpapers_original_author', false );

		// santiy check
		if ( $author_id === false ) return false;

		// show avatar
		return get_avatar( $author_id, $size='48' );

	}



	//==========================================================================



	/**
	 * Show the "join group" button.
	 *
	 * @since 0.1
	 */
	public function join_button() {

		// get group ID
		$group_id = bpwpapers_get_group_by_blog_id( get_current_blog_id() );

		// bail if not appropriate
		if ( groups_is_user_member( bp_loggedin_user_id(), $group_id ) ) return;
		if ( groups_is_user_banned( bp_loggedin_user_id(), $group_id ) ) return;

		// get group
		$group = groups_get_group( array( 'group_id' => $group_id ) );

		// gety button
		$button = bp_get_group_join_button( $group );

		// show it
		echo '<div class="bpwpapers_join">';
		echo $button;
		echo '</div>';

	}



	/**
	 * Filter the "join group" button content.
	 *
	 * @since 0.1
	 *
	 * @return array $button The overridden button array
	 */
	public function join_button_content( $button ) {

		// override content
		$button['link_text'] = __( 'Join the Discussion', 'bpwpapers' );
		$button['link_title'] = __( 'Join the Discussion', 'bpwpapers' );

		// override content
		$button['link_href'] .= '&bpwpaper_group=true';

		// --<
		return $button;

	}



	//==========================================================================



	/**
	 * Intercept publish toggling once plugins are loaded.
	 *
	 * @since 0.1
	 */
	public function publish_toggle_intercept() {

		// access globals
		global $post;

		// check publish toggle button
		if (
			! isset( $_GET['bpwpapers_publish_button_nonce'] ) OR
			! wp_verify_nonce( $_GET['bpwpapers_publish_button_nonce'], 'bpwpapers_publish_button' )
		) {

			// kick out
			return;

		}

		// plain old permalink
		$url = get_permalink( $post->ID );

		// get blog ID
		$blog_id = get_current_blog_id();

		// get current status
		$status = get_blog_status( $blog_id, 'public' );

		// toggle published status
		if ( $status != '1' ) {
			bpwpapers_publish_blog( $blog_id );
		} else {
			bpwpapers_unpublish_blog( $blog_id );
		}

		// redirect
		wp_redirect( $url );
		exit();

	}



	/**
	 * Add a publish button to the CommentPress "Contents" column.
	 *
	 * @since 0.1
	 */
	public function publish_button_commentpress() {

		// remove from footer
		remove_action( 'wp_footer', array( $this, 'publish_button' ) );

		// add to Contents column
		add_action( 'cp_content_tab_before_search', array( $this, 'publish_button_wrapper' ) );

	}



	/**
	 * Create a wrapper for a publish button.
	 *
	 * @since 0.1
	 */
	public function publish_button_wrapper() {

		// bail if not logged in
		if ( ! is_user_logged_in() ) return;

		// get author for blog
		$author_id = bpwpapers_get_author_for_blog( get_current_blog_id() );

		// bail if not site owner
		if ( bp_loggedin_user_id() != $author_id ) return;

		// define heading title
		$heading = sprintf(
			__( '%s Status', 'bpwpapers' ),
			bpwpapers_extension_name()
		);

		echo '
		<h3 class="activity_heading">' . $heading . '</h3>

		<div class="paragraph_wrapper bpwpapers_publish_toggle_wrapper">

		';

		do_action( 'bpwpapers_publish_toggle_before' );

		echo '
		<div class="bpwpapers_publish_toggle">
			' . $this->publish_button() . '
		</div><!-- /bpwpapers_publish_toggle -->

		';

		do_action( 'bpwpapers_publish_toggle_after' );

		echo '
		</div>

		';
	}



	/**
	 * Print a publish button to the screen.
	 *
	 * @since 0.1
	 */
	public function publish_button() {

		// bail if not logged in
		if ( ! is_user_logged_in() ) return;

		// get author for blog
		$author_id = bpwpapers_get_author_for_blog( get_current_blog_id() );

		// bail if not site owner
		if ( bp_loggedin_user_id() != $author_id ) return;

		// get status
		$status = get_blog_status( $blog_id, 'public' );

		// change text depending on toggle state
		if ( $status == '1' ) {

			// link text
			$text = sprintf(
				__( 'Unpublish %s', 'bpwpapers' ),
				bpwpapers_extension_name()
			);

			// link title
			$title = sprintf(
				__( 'Unpublish this %s', 'bpwpapers' ),
				bpwpapers_extension_name()
			);

		} else {

			// link text
			$text = sprintf(
				__( 'Publish %s', 'bpwpapers' ),
				bpwpapers_extension_name()
			);

			// link title
			$title = sprintf(
				__( 'Publish this %s', 'bpwpapers' ),
				bpwpapers_extension_name()
			);

		}

		// link url
		$url = wp_nonce_url( get_permalink( $post->ID ), 'bpwpapers_publish_button', 'bpwpapers_publish_button_nonce' );

		// link class
		$class = 'button';

		// construct link
		$link = '<a href="' . $url . '" class="' . $class . '" title="' . esc_attr( $title ) . '">' . $text . '</a>';

		// construct button
		$button = apply_filters( 'bpwpapers_publish_button_link', $link, $text, $title, $url, $class );

		// show button
		echo '
		<div class="bpwpapers_publish_button">
			' . $button . '
		</div><!-- /bpwpapers_publish_button -->
		';

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



