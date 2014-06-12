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
		add_filter( 'bp_follow_total_follow_counts', array( $this, 'filter_total_follow_count' ), 10, 3 );
		
		// add menu item on papers directory
		add_action( 'bpwpapers_blogs_directory_blog_types', array( $this, 'add_blog_directory_tab' ) );
		
		// add menu item on activity directory
		add_action( 'bp_before_activity_type_tab_favorites', array( $this, 'add_activity_directory_tab' ) );
		
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

		// if $following_ids is empty, pass the largest bigint(20) value to ensure
		// no blogs are matched
		$following_ids = empty( $following_ids ) ? '18446744073709551615' : $following_ids;
	
		// convert from comma-delimited if needed
		$following_ids = array_filter( wp_parse_id_list( $following_ids ) );

		// get paper IDs
		$papers = bpwpapers_get_papers();
	
		// include just papers
		$following_ids = array_intersect( $following_ids, $papers );
		
		$args = array(
			'user_id' => 0,
			'include_blog_ids' => $following_ids,
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

		// parse querystring into an array
		wp_parse_str( $qs, $r );

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
	 * @param array $params The params used to query the followed sites
	 * @return int $filtered_count The filtered total number of BuddyPress Groups
	 */
	public function filter_total_follow_count( $count, $user_id, $params ) {
	
		// only handle blogs
		if ( $params['follow_type'] != 'blogs' ) return $count;
		
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
	
		?>
		<li id="bpwpapers-following"><a href="<?php echo esc_url( bp_loggedin_user_domain() . bpwpapers_get_slug() . '/' . constant( 'BP_FOLLOW_BLOGS_USER_FOLLOWING_SLUG' ). '/' ); ?>"><?php printf( __( 'Following <span>%d</span>', 'bpwpapers' ), (int) $counts['following'] ) ?></a></li><?php
	
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
		
		?>
		<li id="activity-<?php echo $this->activity_slug; ?>"><a href="<?php echo esc_url( bp_loggedin_user_domain() . bp_get_activity_slug() . '/' . $this->activity_slug . '/' ); ?>"><?php printf( __( 'Followed Papers <span>%d</span>', 'bpwpapers' ), (int) $counts['following'] ) ?></a></li><?php
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
	
	
	
} // end class BP_Working_Papers_Follow



