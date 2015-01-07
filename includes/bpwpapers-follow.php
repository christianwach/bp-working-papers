<?php /*
================================================================================
BuddyPress Working Papers compatibility with BuddyPress Followers plugin
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====



--------------------------------------------------------------------------------
*/



/*
================================================================================
Class Name
================================================================================
*/

class BP_Working_Papers_Follow {

	/**
	 * Properties
	 */

	// custom activity slug
	public $activity_slug = 'followpapers';



	/**
	 * Initialises this object
	 *
	 * @return object
	 */
	function __construct() {

		// register hooks
		$this->register_hooks();

		// --<
		return $this;

	}



	/**
	 * Register hooks for this class
	 *
	 * @return void
	 */
	public function register_hooks() {

		// add our screen
		add_action( 'bp_screens', array( $this, 'screen_member_follow' ), 3 );

		// add menu items on member page
		add_action( 'bp_follow_setup_nav', array( $this, 'follow_blogs_setup_nav' ) );

		// add blogs filter to AJAX query string
		add_filter( 'bp_ajax_querystring', array( $this, 'add_blogs_scope_filter' ), 30, 2 );

		// add activity scope
		add_action( 'bp_before_activity_loop', array( $this, 'set_activity_scope_on_user_activity' ) );

		// add activity scope filter to AJAX query string
		add_filter( 'bp_ajax_querystring', array( $this, 'add_activity_scope_filter' ), 20, 2 );

		// add filter for total follow count
		add_filter( 'bp_follow_total_blogs_follow_counts', array( $this, 'filter_total_follow_count' ), 10, 2 );

		// add menu item on papers directory
		add_action( 'bpwpapers_blogs_directory_blog_types', array( $this, 'add_blog_directory_tab' ) );

		// add menu item on activity directory
		add_action( 'bp_before_activity_type_tab_favorites', array( $this, 'add_activity_directory_tab' ) );

		// override Follow Site button text
		add_filter( 'bp_follow_blogs_get_follow_button', array( $this, 'filter_follow_button' ), 20, 3 );

		// override Followed Sites button args
		add_filter( 'bp_follow_blogs_get_sites_button_args', array( $this, 'filter_followed_button' ), 20, 3 );

		// hook into follow/unfollow actions
		add_action( 'bp_follow_start_following_blogs', array( $this, 'follow_paper' ), 20, 1 );
		add_action( 'bp_follow_stop_following_blogs', array( $this, 'unfollow_paper' ), 20, 1 );

		// remove standard join button in header
		global $bp_working_papers;
		remove_action( 'commentpress_header_before', array( $bp_working_papers->template, 'join_button' ) );

		// override "Join the Discussion" reply-to link text
		add_filter( 'bpwpapers_override_reply_to_text', array( $this, 'override_reply_to_text' ), 20, 2 );
		add_filter( 'bpwpapers_override_reply_to_href', array( $this, 'override_reply_to_href' ), 20, 2 );

		// add action for the above
		add_action( 'wp_head', array( $this, 'cbox_theme_compatibility' ) );

		// intercept blog creation
		add_action( 'bpwpapers_signup_validated', array( $this, 'created_blog' ), 20, 3 );

		// intercept group joining
		add_action( 'groups_join_group', array( $this, 'joined_group' ), 20, 2 );

		// intercept group leaving
		add_action( 'groups_leave_group', array( $this, 'left_group' ), 20, 2 );

	}



	/**
	 * Load the Members Follow Papers screen.
	 *
	 * @return void
	 */
	public function screen_member_follow() {

		// is this our target page?
		if (
			is_multisite() &&
			bp_is_bpwpapers_component() &&
			bp_is_user() &&
			bp_is_current_action( constant( 'BP_FOLLOW_BLOGS_USER_FOLLOWING_SLUG' ) )
		) {

			// make sure BP knows that it's our directory
			bp_update_is_directory( true, 'bpwpapers' );

			// allow plugins to handle this
			do_action( 'bpwpapers_screen_member_follow' );

			// load our create template
			bp_core_load_template( apply_filters( 'bpwpapers_template_member', 'bpwpapers/member' ) );

		}

	}



	/**
	 * Setup profile nav
	 *
	 * @return void
	 */
	public function follow_blogs_setup_nav() {

		global $bp;

		// Determine user to use
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		bp_core_new_subnav_item( array(
			'name'            => _x( 'Followed Papers', 'Papers subnav tab', 'bpwpapers' ),
			'slug'            => constant( 'BP_FOLLOW_BLOGS_USER_FOLLOWING_SLUG' ),
			'parent_url'      => trailingslashit( $user_domain . bpwpapers_get_slug() ),
			'parent_slug'     => bpwpapers_get_slug(),
			'screen_function' => 'BP_Follow_Blogs_Screens::user_blogs_screen',
			'position'        => 20,
			'item_css_id'     => 'bpwpapers-following'
		) );

		// Add activity sub nav item
		if ( bp_is_active( 'activity' ) && apply_filters( 'bp_follow_blogs_show_activity_subnav', true ) ) {
			bp_core_new_subnav_item( array(
				'name'            => _x( 'Followed Papers', 'Activity subnav tab', 'bpwpapers' ),
				'slug'            => $this->activity_slug,
				'parent_url'      => trailingslashit( $user_domain . bp_get_activity_slug() ),
				'parent_slug'     => bp_get_activity_slug(),
				'screen_function' => 'BP_Follow_Blogs_Screens::user_activity_screen',
				'position'        => 22,
				'item_css_id'     => 'activity-followpapers'
			) );
		}

	}



	/**
	 * Filter the blogs loop.
	 *
	 * Specifically, filter when we're on:
	 *  - a user's "Followed Papers" page
	 *  - the Working Papers directory and clicking on the "Following" tab
	 *
	 * @param str $qs The querystring for the BP loop
	 * @param str $object The current object for the querystring
	 * @return str Modified querystring
	 */
	public function add_blogs_scope_filter( $qs, $object ) {

		// not on the blogs object? stop now!
		if ( $object != 'bpwpapers' ) {
			return $qs;
		}

		// parse querystring into an array
		wp_parse_str( $qs, $r );

		// set scope if a user is on a user's "Followed Papers" page
		if (
			is_multisite() &&
			bp_is_bpwpapers_component() &&
			bp_is_user() &&
			bp_is_current_action( constant( 'BP_FOLLOW_BLOGS_USER_FOLLOWING_SLUG' ) )
		) {
			$r['scope'] = 'following';
		}

		if ( 'following' !== $r['scope'] ) {
			return $qs;
		}

		// determine user to check
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();

		// get blog IDs that the user is following
		$following_ids = bp_get_following_ids( array(
			'user_id' => $user_id,
			'follow_type' => 'blogs',
		) );

		// convert from comma-delimited if needed
		$following_ids = array_filter( wp_parse_id_list( $following_ids ) );

		// did we get any?
		if ( count( $following_ids ) > 0 ) {

			// get paper IDs
			$papers = bpwpapers_get_papers();

			// include just papers
			$following_ids = array_intersect( $following_ids, $papers );

		}

		// do we have any left?
		if ( count( $following_ids ) === 0 ) {

			// no, pass the largest bigint(20) value to ensure no blogs are matched
			$following_ids = array( '18446744073709551615' );

		}

		$args = array(
			'user_id' => 0,
			'include_blog_ids' => implode( ',', $following_ids ),
		);

		// make sure we add a separator if we have an existing querystring
		if ( ! empty( $qs ) ) {
			$qs .= '&';
		}

		// add our follow parameters to the end of the querystring
		$qs .= build_query( $args );

		return $qs;

	}



	/**
	 * Set activity scope on a user's "Activity > Followed Papers" page
	 *
	 * @return void
	 */
	public function set_activity_scope_on_user_activity() {

		if ( ! bp_is_current_action( $this->activity_slug ) ) {
			return;
		}

		$scope = $this->activity_slug;

		// if we have a post value already, let's add our scope to the existing cookie value
		if ( !empty( $_POST['cookie'] ) ) {
			$_POST['cookie'] .= "%3B%20bp-activity-scope%3D{$scope}";
		} else {
			$_POST['cookie'] .= "bp-activity-scope%3D{$scope}";
		}

		// set the activity scope by faking an ajax request (loophole!)
		if ( ! defined( 'DOING_AJAX' ) ) {
			$_POST['cookie'] .= "%3B%20bp-activity-filter%3D-1";

			// reset the selected tab
			@setcookie( 'bp-activity-scope',  $scope, 0, '/' );

			//reset the dropdown menu to 'Everything'
			@setcookie( 'bp-activity-filter', '-1',   0, '/' );
		}

	}



	/**
	 * Filter the activity loop.
	 *
	 * Specifically, when on the activity directory and clicking on the "Followed Papers" tab.
	 *
	 * @param str $qs The querystring for the BP loop
	 * @param str $object The current object for the querystring
	 * @return str Modified querystring
	 */
	public function add_activity_scope_filter( $qs, $object ) {

		// not on the activity object? stop now!
		if ( $object != 'activity' ) {
			return $qs;
		}

		// parse defaults
		$r = wp_parse_args( $qs );

		// check for Follow Blogs, so we can filter paper activity out
		if (
			bp_is_current_action( constant( 'BP_FOLLOW_BLOGS_USER_FOLLOWING_SLUG' ) ) OR
			bp_is_current_action( constant( 'BP_FOLLOW_BLOGS_USER_ACTIVITY_SLUG' ) ) OR
			constant( 'BP_FOLLOW_BLOGS_USER_ACTIVITY_SLUG' ) === $r['scope']
		) {
			return $this->filter_followblogs_activity( $qs );
		}

		if ( bp_is_current_action( $this->activity_slug ) ) {
			$r['scope'] = $this->activity_slug;
		}

		if ( $this->activity_slug !== $r['scope'] ) {
			return $qs;
		}

		// determine user to check
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();

		// get blog IDs that the user is following
		$following_ids = bp_get_following_ids( array(
			'user_id' => $user_id,
			'follow_type' => 'blogs',
		) );

		// convert from comma-delimited if needed
		$following_ids = array_filter( wp_parse_id_list( $following_ids ) );

		// did we get any?
		if ( count( $following_ids ) > 0 ) {

			// get paper IDs
			$papers = bpwpapers_get_papers();

			// include just papers
			$following_ids = array_intersect( $following_ids, $papers );

			// init group IDs
			$group_ids = array();

			// get groups for these blogs
			if ( count( $following_ids ) > 0 ) {
				foreach( $following_ids AS $following_id ) {
					$group_ids[] = bpwpapers_get_group_by_blog_id( $following_id );
				}
			}

			// overwrite following IDs
			$following_ids = $group_ids;

			// clear user ID if on sitewide activity page
			$user_id = ( bp_is_activity_component() AND ! bp_is_user() ) ? 0 : $user_id;

			$args = array(
				'user_id'    => $user_id,
				'object'     => 'groups',
				'primary_id' => implode( ',', $group_ids ),
			);

		}

		// did we get any?
		if ( count( $following_ids ) === 0 ) {

			// no, pass the largest bigint(20) value to ensure no blogs are matched
			$following_ids = '18446744073709551615';

			$args = array(
				'user_id'    => 0,
				'object'     => 'blogs',
				'primary_id' => $following_ids,
			);

		}

		// make sure we add a separator if we have an existing querystring
		if ( ! empty( $qs ) ) {
			$qs .= '&';
		}

		// add our follow parameters to the end of the querystring
		$qs .= build_query( $args );

		return $qs;

	}



	/**
	 * Override the total number of followed sites, excluding working papers
	 *
	 * @param array $count The total number of followed sites for a user (following is always 0)
	 * @param int $user_id The numeric ID of a WordPress user
	 * @return int $filtered_count The filtered total number of BuddyPress Groups
	 */
	public function filter_total_follow_count( $count, $user_id ) {

		// construct args
		$args = array(
			'user_id' => $user_id,
			'follow_type' => 'blogs',
		);

		// get IDs
		$blog_ids = bp_follow_get_following( $args );

		// get paper IDs
		$papers = bpwpapers_get_papers();

		// is this our component?
		if ( bp_is_bpwpapers_component() ) {

			// let's include papers
			$total = array_intersect( $blog_ids, $papers );

		} else {

			// let's exclude papers
			$total = array_merge( array_diff( $blog_ids, $papers ) );

		}

		// override value in count array
		$count['following'] = count( $total );

		// --<
		return $count;

	}



	/**
	 * Add a "Following (X)" tab to the papers directory.
	 *
	 * This is so the logged-in user can filter the papers directory to only
	 * papers that the current user is following.
	 *
	 * @return void
	 */
	public function add_blog_directory_tab() {

		// only for logged in users
		if ( ! is_user_logged_in() ) return;

		// get counts
		$counts = bp_follow_total_follow_counts( array(
			'user_id'     => bp_loggedin_user_id(),
			'follow_type' => 'blogs',
		) );

		// don't show if none found
		if ( empty( $counts['following'] ) ) return false;

		// add list item
		echo '<li id="bpwpapers-following"><a href="' . esc_url( bp_loggedin_user_domain() . bpwpapers_get_slug() . '/' . constant( 'BP_FOLLOW_BLOGS_USER_FOLLOWING_SLUG' ). '/' ) . '">' . sprintf( __( 'Following <span>%d</span>', 'bpwpapers' ), (int) $counts['following'] ) . '</a></li>';

	}



	/**
	 * Adds a "Followed Sites (X)" tab to the activity directory.
	 *
	 * This is so the logged-in user can filter the activity stream to only papers
	 * that the current user is following.
	 */
	public function add_activity_directory_tab() {

		// only for logged in users
		if ( ! is_user_logged_in() ) return;

		// get counts directly
		$counts = $this->get_paper_follow_count( bp_loggedin_user_id() );

		// don't show if none found
		//if ( empty( $counts['following'] ) ) return false;

		// show list item
		echo '<li id="activity-' . $this->activity_slug . '"><a href="' . esc_url( bp_loggedin_user_domain() . bp_get_activity_slug() . '/' . $this->activity_slug . '/' ) . '">' . sprintf( __( 'Followed Papers <span>%d</span>', 'bpwpapers' ), (int) $counts['following'] ) . '</a></li>';

	}



	/**
	 * Get the total number of followed working papers
	 *
	 * @param int $user_id The numeric ID of a WordPress user
	 * @return array $count The count array
	 */
	public function get_paper_follow_count( $user_id = 0 ) {

		// construct args
		$args = array(
			'user_id' => $user_id,
			'follow_type' => 'blogs',
		);

		// get IDs
		$blog_ids = bp_follow_get_following( $args );

		// get paper IDs
		$papers = bpwpapers_get_papers();

		// just include papers
		$total = array_intersect( $blog_ids, $papers );

		// define count array
		$count = array(
			'followers' => 0,
			'following' => count( $total ),
		);

		// --<
		return $count;

	}



	/**
	 * Filter the activity loop.
	 *
	 * Specifically, when on the activity directory and clicking on the "Followed Blogs" tab.
	 *
	 * @param str $qs The querystring for the BP loop
	 * @return str Modified querystring
	 */
	public function filter_followblogs_activity( $qs ) {

		// parse querystring into an array
		wp_parse_str( $qs, $params );

		// convert from comma-delimited if needed
		$following_ids = array_filter( wp_parse_id_list( $params['primary_id'] ) );

		// did we get any?
		if ( count( $following_ids ) > 0 ) {

			// get paper IDs
			$papers = bpwpapers_get_papers();

			// let's exclude papers
			$following_ids = array_merge( array_diff( $following_ids, $papers ) );

		}

		// did we get any?
		if ( count( $following_ids ) === 0 ) {

			// no, pass the largest bigint(20) value to ensure no blogs are matched
			$following_ids = array( '18446744073709551615' );

		}

		// replace primary IDs
		$params['primary_id'] = implode( ',', $following_ids );

		// rebuild querystring
		$qs = build_query( $params );

		return $qs;

	}



	/**
	 * Filter the Follow Site button
	 *
	 * @param array $button The button config array
	 * @param array $params The bp-follower array
	 * @param bool $is_following User is following the site, or not
	 * @return str Modified querystring
	 */
	public function filter_follow_button( $button, $params, $is_following ) {

		// is this site a working paper?
		if ( ! bpwpapers_is_working_paper( $params['leader_id'] ) ) return $button;

		// we need to look at these...
		global $bp, $blogs_template;

		// setup some variables
		if ( $is_following ) {

			// init link text
			$link_text = _x( 'Unfollow', 'Button', 'bpwpapers' );

			if ( empty( $blogs_template->in_the_loop ) ) {
				$paper_name = bpwpapers_extension_name();
				$link_text = _x( sprintf( 'Unfollow %s', $paper_name ), 'Button', 'bpwpapers' );
			}

		} else {

			// init link text
			$link_text = _x( 'Follow', 'Button', 'bpwpapers' );

			// in the loop?
			if ( empty( $blogs_template->in_the_loop ) ) {
				$paper_name = bpwpapers_extension_name();
				$link_text = _x( sprintf( 'Follow %s', $paper_name ), 'Button', 'bpwpapers' );
			}

		}

		// replace link title
		$button['link_text'] = $link_text;

		// --<
		return $button;

	}



	/**
	 * Filter the Followed Sites button
	 *
	 * @param array $params The setup params for the button
	 * @return array Modified setup params for the button
	 */
	public function filter_followed_button( $params ) {

		// is this site a working paper?
		if ( ! bpwpapers_is_working_paper( get_current_blog_id() ) ) return $params;

		// configure the link the way we want it
		$paper_name = apply_filters( 'bpwpapers_extension_plural', __( 'Working Papers', 'bpwpapers' ) );

		// new URL
		$params['link'] = esc_url( bp_loggedin_user_domain() . bpwpapers_get_slug() . '/' .
						  constant( 'BP_FOLLOW_BLOGS_USER_FOLLOWING_SLUG' ). '/' );

		// new title
		$params['text'] = _x( sprintf( 'Followed %s', $paper_name ), 'Footer button', 'bpwpapers' );

		// --<
		return $params;

	}



	/**
	 * Intercept follow site action
	 *
	 * @param object $object The followed object data
	 * @return void
	 */
	public function follow_paper( $object ) {

		// only handle sites
		if ( $object->follow_type != 'blogs' ) return;

		// is this site a working paper?
		if ( ! bpwpapers_is_working_paper( $object->leader_id ) ) return;

		// get group ID
		$group_id = bpwpapers_get_group_by_blog_id( $object->leader_id );

		// unhook our action to prevent recursion
		remove_action( 'groups_join_group', array( $this, 'joined_group' ), 20 );

		// add user to group
		$success = groups_join_group( $group_id, $object->follower_id );

		// re-hook our action
		add_action( 'groups_join_group', array( $this, 'joined_group' ), 20, 2 );

	}



	/**
	 * Intercept unfollow site action
	 *
	 * @param object $object The followed object data
	 * @return void
	 */
	public function unfollow_paper( $object ) {

		// only handle sites
		if ( $object->follow_type != 'blogs' ) return;

		// is this site a working paper?
		if ( ! bpwpapers_is_working_paper( $object->leader_id ) ) return;

		// get group ID
		$group_id = bpwpapers_get_group_by_blog_id( $object->leader_id );

		// remove user from group
		groups_leave_group( $group_id, $object->follower_id );

	}



	/**
	 * Intercept blog created action and auto-follow site for creator
	 *
	 * This is done via the 'bpwpapers_signup_validated' hook because the linkage
	 * is not established until after the group has been created.
	 *
	 * @param int $group_id The numeric ID of the BP group
	 * @param object $member The BP member
	 * @param object $group The BP group
	 * @return void
	 */
	public function created_blog( $user_id, $blog_id, $group_id ) {

		// only handle working paper groups
		if ( ! bpwpapers_group_has_working_paper( $group_id ) ) return;

		// auto-follow
		$this->joined_group( $group_id, $user_id );

	}



	/**
	 * Intercept group join action
	 *
	 * @param int $group_id The numeric ID of the BP group
	 * @param int $user_id The numeric ID of the WP user
	 * @return void
	 */
	public function joined_group( $group_id, $user_id ) {

		// only handle working paper groups
		if ( ! bpwpapers_group_has_working_paper( $group_id ) ) return;

		// get blog ID
		$blog_id = bpwpapers_get_blog_by_group_id( $group_id );

		// remove our action to prevent recursion
		remove_action( 'bp_follow_start_following_blogs', array( $this, 'follow_paper' ), 20 );

		// set up follow
		$args = array(
			'leader_id'   => $blog_id,
			'follower_id' => $user_id,
			'follow_type' => 'blogs',
		);

		// follow
		$success = bp_follow_start_following( $args );

		// re-add our action
		add_action( 'bp_follow_start_following_blogs', array( $this, 'follow_paper' ), 20, 1 );

	}



	/**
	 * Intercept group leave action
	 *
	 * @param int $group_id The numeric ID of the BP group
	 * @param int $user_id The numeric ID of the WP user
	 * @return void
	 */
	public function left_group( $group_id, $user_id ) {

		// only handle working paper groups
		if ( ! bpwpapers_group_has_working_paper( $group_id ) ) return;

		// get blog ID
		$blog_id = bpwpapers_get_blog_by_group_id( $group_id );

		// remove our action to prevent recursion
		remove_action( 'bp_follow_stop_following_blogs', array( $this, 'unfollow_paper' ), 20 );

		// set up follow
		$args = array(
			'leader_id'   => $blog_id,
			'follower_id' => $user_id,
			'follow_type' => 'blogs',
		);

		// follow
		$success = bp_follow_stop_following( $args );

		// re-add our action
		add_action( 'bp_follow_stop_following_blogs', array( $this, 'unfollow_paper' ), 20, 1 );

	}



	/**
	 * Override content of the reply to link
	 *
	 * @param string $link_text the full text of the reply to link
	 * @param string $paragraph_text paragraph text
	 * @return string $link_text updated content of the reply to link
	 */
	public function override_reply_to_text( $link_text, $paragraph_text ) {

		// get name
		$paper_name = bpwpapers_extension_name();

		// construct link content
		$link_text = __( sprintf( 'Follow this %s to leave a comment', $paper_name ), 'bpwpapers' );

		/*
		// construct link content (alt)
		$link_text = sprintf(
			__( 'Join the discussion to leave a comment on %s', 'bpwpapers' ),
			$paragraph_text
		);
		*/

		// --<
		return $link_text;

	}



	/**
	 * Override content of the reply to link target and use BP Follow's target
	 *
	 * @param string $href The existing target URL
	 * @param string $text_sig The text signature of the paragraph
	 * @return string $href Overridden target URL
	 */
	public function override_reply_to_href( $href, $text_sig ) {

		// init URL
		$href = wp_nonce_url(
			add_query_arg( 'blog_id', get_current_blog_id(), get_permalink( get_option( 'bpwpapers_group_page' ) ) ),
			'bp_follow_blog_follow',
			'bpfb-follow'
		);

		//wp_nonce_url( bp_get_group_permalink( $group ) . 'join', 'groups_join_group' );

		// do we want to set this to the follow site HREF?
		return $href;

	}



	/**
	 * Adds icon to menu in CBOX theme
	 *
	 * @return void
	 */
	function cbox_theme_compatibility() {

		// is CBOX theme active?
		if ( function_exists( 'cbox_theme_register_widgets' ) ) {

			// output style in head
			?>

			<style type="text/css">
			/* <![CDATA[ */
			li#activity-<?php echo $this->activity_slug; ?> a:before
			{
				content: "C";
			}
			li#activity-followblogs a:before
			{
				content: "*";
			}
			/* ]]> */
			</style>

			<?php

		}

	}



} // end class BP_Working_Papers_Follow



