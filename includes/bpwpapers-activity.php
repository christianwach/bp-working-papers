<?php /*
================================================================================
BuddyPress Working Papers Activity Functions
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

Throw any functions which deal with BuddyPress activity in here.

--------------------------------------------------------------------------------
*/



/*
================================================================================
Class Name
================================================================================
*/

class BP_Working_Papers_Activity {

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

		// hooks that always need to be present...
		add_action( 'bp_setup_globals', array( $this, 'add_filter_options' ) );

		// add custom group activity (outside the working paper check, since joining takes place on the main site)
		add_action( 'bp_activity_before_save', array( $this, 'custom_group_activity' ), 10, 1 );

		// add custom site activity (outside the working paper check, since there's no group yet)
		add_action( 'bp_activity_before_save', array( $this, 'custom_site_activity' ), 10, 1 );

		// override activity comment reply link
		add_filter( 'cp_activity_entry_comment_link', array( $this, 'filter_comment_link' ), 10, 1 );

		// if the current blog is a working paper...
		if ( bpwpapers_is_working_paper( get_current_blog_id() ) ) {

			// store group ID for this blog
			$this->group_id = bpwpapers_get_group_by_blog_id( get_current_blog_id() );

			// on working papers, we don't need some filter options
			add_action( 'init', array( $this, 'remove_external_filter_options' ), 50 );

			// add custom post activity
			add_action( 'bp_activity_before_save', array( $this, 'custom_post_activity' ), 10, 1 );

			// add custom comment activity
			add_action( 'bp_activity_before_save', array( $this, 'custom_comment_activity' ), 10, 1 );

			// override the action of this custom comment activity
			add_action( 'commentpress_comment_activity_action', array( $this, 'custom_comment_activity_action' ), 10, 7 );

			// add filter for post name in activity item
			add_filter( 'bpwpapers_activity_post_name', array( $this, 'custom_comment_activity_post_name' ), 10, 2 );

			// add filter for commenting capability
			add_filter( 'commentpress_allowed_to_comment', array( $this, 'allow_anon_commenting' ), 10, 1 );

			// add action for checking comment moderation
			add_filter( 'pre_comment_approved', array( $this, 'check_comment_approval' ), 100, 2 );

			// override reply to link
			add_filter( 'comment_reply_link', array( $this, 'override_reply_to_link' ), 10, 4 );

			// override comment form if no group membership
			add_filter( 'commentpress_show_comment_form', array( $this, 'show_comment_form' ), 10, 1 );

			// override CommentPress TinyMCE
			add_filter( 'cp_override_tinymce', array( $this, 'disable_tinymce' ), 10, 1 );

			// override cp_activity_tab_recent_title_blog
			add_filter( 'cp_activity_tab_recent_title_blog', array( $this, 'get_activity_sidebar_recent_title' ) );

			// add section to activity sidebar in CommentPress
			add_filter( 'commentpress_bp_activity_sidebar_before_members', array( $this, 'get_activity_sidebar_section' ) );

			// on a working paper, filter activity stream to include only items from that group
			add_filter( 'bp_ajax_querystring', array( $this, 'filter_ajax_querystring' ), 999, 2 );

		}

	}



	//==========================================================================



	/**
	 * Remove actions for filter options on group activity stream.
	 *
	 * @since 0.1
	 */
	public function remove_external_filter_options() {

		// remove unnecessary filter options
		remove_action( 'bp_group_activity_filter_options', 'bp_groupblog_posts' );
		remove_action( 'bp_group_activity_filter_options', 'bp_groupblogs_add_filter' );

	}



	/**
	 * Add actions for filter options on group activity stream.
	 *
	 * @since 0.1
	 */
	public function add_filter_options() {

		// remove unnecessary filter options
		remove_action( 'bp_group_activity_filter_options', 'bp_groupblog_posts' );
		remove_action( 'bp_group_activity_filter_options', 'bp_groupblogs_add_filter' );

		// add our sites filter
		add_action( 'bp_activity_filter_options', array( $this, 'filter_option_sites' ) );
		add_action( 'bp_member_activity_filter_options', array( $this, 'filter_option_sites' ) );

		// add our groups filter
		add_action( 'bp_activity_filter_options', array( $this, 'filter_option_groups' ) );
		add_action( 'bp_member_activity_filter_options', array( $this, 'filter_option_groups' ) );

		// add our posts filter
		add_action( 'bp_activity_filter_options', array( $this, 'filter_option_posts' ) );
		add_action( 'bp_member_activity_filter_options', array( $this, 'filter_option_posts' ) );

		// add our comments filter
		add_action( 'bp_activity_filter_options', array( $this, 'filter_option_comments' ) );
		add_action( 'bp_member_activity_filter_options', array( $this, 'filter_option_comments' ) );

		// optionally add post and comment filters to groups that have working papers
		if ( bpwpapers_group_has_working_paper() ) {
			add_action( 'bp_group_activity_filter_options', array( $this, 'filter_option_posts' ) );
			add_action( 'bp_group_activity_filter_options', array( $this, 'filter_option_comments' ) );
		}

	}



	/**
	 * Add a filter option to the filter select box on activity pages.
	 *
	 * @since 0.1
	 */
	public function filter_option_sites( $slug ) {

		// default name, but allow plugins to override
		$post_name = apply_filters(
			'bpwpapers_site_name',
			sprintf( __( 'New %s', 'bpwpapers' ), bpwpapers_extension_plural() )
		);

		// construct option
		$option = '<option value="new_working_paper">'.$post_name.'</option>'."\n";

		// print
		echo $option;

	}



	/**
	 * Add a filter option to the filter select box on activity pages.
	 *
	 * @since 0.1
	 */
	public function filter_option_groups( $slug ) {

		// default name, but allow plugins to override
		$group_name = apply_filters(
			'bpwpapers_group_name',
			sprintf( __( '%s Memberships', 'bpwpapers' ), bpwpapers_extension_name() )
		);

		// construct option
		$option = '<option value="joined_working_paper">'.$group_name.'</option>'."\n";

		// print
		echo $option;

	}



	/**
	 * Add a filter option to the filter select box on activity pages.
	 *
	 * @since 0.1
	 */
	public function filter_option_posts( $slug ) {

		// default name, but allow plugins to override
		$post_name = apply_filters(
			'bpwpapers_post_name',
			sprintf( __( '%s Posts', 'bpwpapers' ), bpwpapers_extension_name() )
		);

		// construct option
		$option = '<option value="new_working_paper_post">'.$post_name.'</option>'."\n";

		// print
		echo $option;

	}



	/**
	 * Add a filter option to the filter select box on activity pages.
	 *
	 * @since 0.1
	 */
	public function filter_option_comments() {

		// default name, but allow plugins to override
		$comment_name = apply_filters(
			'bpwpapers_comment_name',
			sprintf( __( '%s Comments', 'bpwpapers' ), bpwpapers_extension_name() )
		);

		// construct option
		$option = '<option value="new_working_paper_comment">'.$comment_name.'</option>'."\n";

		// print
		echo $option;

	}



	//==========================================================================



	/**
	 * Record the blog post activity for the group.
	 *
	 * @see bp_blogs_record_blog()
	 * @see bp_blogs_record_activity()
	 *
	 * @return object $activity The new activity item
	 */
	public function custom_group_activity( $activity ) {

		// only on new sign-ups
		if ( ( $activity->type != 'joined_group' ) ) return $activity;

		/*
		trigger_error( print_r( array(
			'group activity BEFORE' => $activity,
			'POST' => $_POST,
		), true ), E_USER_ERROR ); die();
		//print_r( array( 'group activity BEFORE' => $activity ) ); //die();
		*/

		// get group ID
		$group_id = $activity->item_id;

		// get blog ID
		$blog_id = bpwpapers_get_blog_by_group_id( $group_id );

		// bail if not a working paper group
		if ( $blog_id === false ) return $activity;

		// set activity type
		$type = 'joined_working_paper';

		// see if we already have the modified activity for this blog (unlikely)
		$id = bp_activity_get_activity_id( array(

			'user_id' => $activity->user_id,
			'type' => $type,
			'item_id' => $group_id

		) );

		// if we don't find a modified item...
		if ( !$id ) {

			// see if we have an unmodified activity item (also unlikely)
			$id = bp_activity_get_activity_id( array(

				'user_id' => $activity->user_id,
				'type' => $activity->type,
				'item_id' => $activity->item_id

			) );

		}

		// If we found an activity for this group then overwrite that to avoid
		// having multiple activities
		if ( $id ) { $activity->id = $id; }

		// get site name
		$name = get_blog_option( $blog_id, 'blogname' );

		// if we're replacing an item, show different message...
		if ( $id ) {

			// replace the necessary values to display in group activity stream
			$activity->action = sprintf(

				__( '%s joined the discussion on the %s %s', 'bpwpapers' ),
				bp_core_get_userlink( $activity->user_id ),
				strtolower( bpwpapers_extension_name() ),
				'<a href="' . get_home_url( $blog_id ) . '">' . esc_attr( $name ) . '</a>'

			);

		} else {

			// replace the necessary values to display in group activity stream
			$activity->action = sprintf(

				__( '%s joined the discussion on the %s %s', 'bpwpapers' ),
				bp_core_get_userlink( $activity->user_id ),
				strtolower( bpwpapers_extension_name() ),
				'<a href="' . get_home_url( $blog_id ) . '">' . esc_attr( $name ) . '</a>'

			);

		}

		// set to relevant custom type
		$activity->type = $type;

		/*
		trigger_error( print_r( array(
			'site activity AFTER' => $activity,
			'POST' => $_POST,
		), true ), E_USER_ERROR ); //die();
		//print_r( array( 'site activity AFTER' => $activity ) ); //die();
		*/

		// prevent from firing again
		remove_action( 'bp_activity_before_save', array( $this, 'custom_group_activity' ) );

		// --<
		return $activity;
	}



	/**
	 * Record the blog creation activity.
	 *
	 * @see bp_blogs_record_blog()
	 * @see bp_blogs_record_activity()
	 *
	 * @return object $activity The new activity item
	 */
	public function custom_site_activity( $activity ) {

		/*
		trigger_error( print_r( array(
			'site activity BEFORE' => $activity,
			'POST' => $_POST,
		), true ), E_USER_ERROR ); //die();
		//print_r( array( 'site activity BEFORE' => $activity ) ); //die();
		*/

		// only on new blog posts
		if ( ( $activity->type != 'new_blog' ) ) return $activity;

		// only on working papers as they are being created
		if ( ! isset( $_POST['bpwpapers-new-blog'] ) ) return $activity;
		if ( $_POST['bpwpapers-new-blog'] != 1 ) return $activity;

		// set activity type
		$type = 'new_working_paper';

		// get blog ID
		$blog_id = $activity->item_id;

		// see if we already have the modified activity for this blog (unlikely)
		$id = bp_activity_get_activity_id( array(

			'user_id' => $activity->user_id,
			'type' => $type,
			'item_id' => $blog_id

		) );

		// if we don't find a modified item...
		if ( !$id ) {

			// see if we have an unmodified activity item (also unlikely)
			$id = bp_activity_get_activity_id( array(

				'user_id' => $activity->user_id,
				'type' => $activity->type,
				'item_id' => $activity->item_id

			) );

		}

		// If we found an activity for this blog then overwrite that to avoid
		// having multiple activities for every blog edit
		if ( $id ) { $activity->id = $id; }

		// get site name
		$name = get_blog_option( $blog_id, 'blogname' );

		// if we're replacing an item, show different message...
		if ( $id ) {

			// replace the necessary values to display in group activity stream
			$activity->action = sprintf(

				__( '%s updated the %s %s', 'bpwpapers' ),
				bp_core_get_userlink( $activity->user_id ),
				strtolower( bpwpapers_extension_name() ),
				'<a href="' . get_home_url( $blog_id ) . '">' . esc_attr( $name ) . '</a>'

			);

		} else {

			// replace the necessary values to display in group activity stream
			$activity->action = sprintf(

				__( '%s created the %s %s', 'bpwpapers' ),
				bp_core_get_userlink( $activity->user_id ),
				strtolower( bpwpapers_extension_name() ),
				'<a href="' . get_home_url( $blog_id ) . '">' . esc_attr( $name ) . '</a>'

			);

		}

		// set to relevant custom type
		$activity->type = $type;

		/*
		trigger_error( print_r( array(
			'site activity AFTER' => $activity,
			'POST' => $_POST,
		), true ), E_USER_ERROR ); //die();
		//print_r( array( 'site activity AFTER' => $activity ) ); //die();
		*/

		// prevent from firing again
		remove_action( 'bp_activity_before_save', array( $this, 'custom_site_activity' ) );

		// --<
		return $activity;
	}



	/**
	 * Record the blog post activity for the group.
	 *
	 * @see bp_groupblog_set_group_to_post_activity( $activity )
	 *
	 * @return object $activity The new activity item
	 */
	public function custom_post_activity( $activity ) {

		//print_r( array( 'post activity BEFORE' => $activity ) ); //die();

		// only on new blog posts
		if ( ( $activity->type != 'new_blog_post' ) ) return $activity;

		// clarify data
		$blog_id = $activity->item_id;
		$post_id = $activity->secondary_item_id;
		$post = get_post( $post_id );

		// only on working papers
		if ( ! bpwpapers_is_working_paper( $blog_id ) ) return $activity;

		// get the group ID for this blog
		$group_id = bpwpapers_get_group_by_blog_id( $blog_id );

		// sanity check
		if ( $group_id === false ) return $activity;

		// get group
		$group = groups_get_group( array( 'group_id' => $group_id ) );

		// safely get home URL
		$home_url = ( $blog_id !== false ) ? get_home_url( $blog_id ) : false;

		// bail if we don't get a home URL for the site
		if ( empty( $home_url ) ) return $activity;

		// get site name
		$blog_name = get_blog_option( $blog_id, 'blogname' );

		// construct blog link
		$blog_link = '<a href="'.$home_url.'" title="'.esc_attr( $blog_name ).'">'.$blog_name.'</a>';

		// get name
		$name = bpwpapers_extension_name();

		// set activity type
		$type = 'new_working_paper_post';

		// see if we already have the modified activity for this blog post
		$id = bp_activity_get_activity_id( array(

			'user_id' => $activity->user_id,
			'type' => $type,
			'item_id' => $group_id,
			'secondary_item_id' => $activity->secondary_item_id

		) );

		// if we don't find a modified item...
		if ( !$id ) {

			// see if we have an unmodified activity item
			$id = bp_activity_get_activity_id( array(

				'user_id' => $activity->user_id,
				'type' => $activity->type,
				'item_id' => $activity->item_id,
				'secondary_item_id' => $activity->secondary_item_id

			) );

		}

		// If we found an activity for this blog post then overwrite that to avoid
		// having multiple activities for every blog post edit
		if ( $id ) {
			$activity->id = $id;
		}

		// allow plugins to override the name of the activity item
		$activity_name = apply_filters(
			'bpwpapers_activity_post_name',
			__( 'post', 'bpwpapers' ),
			$post
		);

		// default to standard BP author
		$activity_author = bp_core_get_userlink( $post->post_author );

		// compat with Co-Authors Plus
		if ( function_exists( 'get_coauthors' ) ) {

			// get multiple authors
			$authors = get_coauthors();
			//print_r( $authors ); die();

			// if we get some
			if ( !empty( $authors ) ) {

				// we only want to override if we have more than one...
				if ( count( $authors ) > 1 ) {

					// use the Co-Authors format of "name, name, name and name"
					$activity_author = '';

					// init counter
					$n = 1;

					// find out how many author we have
					$author_count = count( $authors );

					// loop
					foreach( $authors AS $author ) {

						// default to comma
						$sep = ', ';

						// if we're on the penultimate
						if ( $n == ($author_count - 1) ) {

							// use ampersand
							$sep = __( ' &amp; ', 'bpwpapers' );

						}

						// if we're on the last, don't add
						if ( $n == $author_count ) { $sep = ''; }

						// add name
						$activity_author .= bp_core_get_userlink( $author->ID );

						// and separator
						$activity_author .= $sep;

						// increment
						$n++;

					}

				}

			}

		}

		// if we're replacing an item, show different message...
		if ( $id ) {

			// replace the necessary values to display in activity stream
			$activity->action = sprintf(

				__( '%1$s updated a %2$s %3$s in the %4$s %5$s:', 'bpwpapers' ),

				$activity_author,
				$activity_name,
				'<a href="' . get_permalink( $post->ID ) .'">' . esc_attr( $post->post_title ) . '</a>',
				strtolower( $name ),
				$blog_link

			);

		} else {

			// replace the necessary values to display in activity stream
			$activity->action = sprintf(

				__( '%1$s wrote a new %2$s %3$s in the %4$s %5$s:', 'bpwpapers' ),

				$activity_author,
				$activity_name,
				'<a href="' . get_permalink( $post->ID ) .'">' . esc_attr( $post->post_title ) . '</a>',
				strtolower( $name ),
				$blog_link

			);

		}

		$activity->item_id = (int)$group_id;
		$activity->component = 'groups';

		// having marked all groupblogs as public, we need to hide activity from them if the group is private
		// or hidden, so they don't show up in sitewide activity feeds.
		if ( 'public' != $group->status ) {
			$activity->hide_sitewide = true;
		} else {
			$activity->hide_sitewide = false;
		}

		// set to relevant custom type
		$activity->type = $type;

		//print_r( array( 'post activity AFTER' => $activity ) ); die();

		// prevent from firing again
		remove_action( 'bp_activity_before_save', array( $this, 'custom_post_activity' ) );

		// --<
		return $activity;

	}



	/**
	 * Record the blog activity for the group. Note: if the site is a CommentPress
	 * site, then this method will be dropped in favour of the one internal to
	 * CommentPress, because CommentPress needs to know the subpage of a comment.
	 *
	 * @see bp_groupblog_set_group_to_post_activity()
	 *
	 * @return object $activity The new activity item
	 */
	public function custom_comment_activity( $activity ) {

		//trigger_error( print_r( array( 'comment activity BEFORE' => $activity ), true ), E_USER_ERROR ); die();
		//print_r( array( 'comment activity BEFORE' => $activity ) ); //die();

		// only deal with comments
		if ( ( $activity->type != 'new_blog_comment' ) ) return $activity;

		// which blog?
		$blog_id = $activity->item_id;

		// only on working papers
		if ( ! bpwpapers_is_working_paper( $blog_id ) ) return $activity;

		// get the group ID for this blog
		$group_id = bpwpapers_get_group_by_blog_id( $blog_id );

		// sanity check
		if ( $group_id === false ) return $activity;

		// set activity type
		$type = 'new_working_paper_comment';

		// okay, let's get the group object
		$group = groups_get_group( array( 'group_id' => $group_id ) );
		//print_r( $group ); die();

		// see if we already have the modified activity for this comment
		$id = bp_activity_get_activity_id( array(

			'user_id' => $activity->user_id,
			'type' => $type,
			'item_id' => $group_id,
			'secondary_item_id' => $activity->secondary_item_id

		) );

		// if we don't find a modified item...
		if ( !$id ) {

			// see if we have an unmodified activity item
			$id = bp_activity_get_activity_id( array(

				'user_id' => $activity->user_id,
				'type' => $activity->type,
				'item_id' => $activity->item_id,
				'secondary_item_id' => $activity->secondary_item_id

			) );

		}

		// If we found an activity for this blog comment then overwrite that to avoid having
		// multiple activities for every blog comment edit
		if ( $id ) $activity->id = $id;

		// get the comment
		$comment = get_comment( $activity->secondary_item_id );
		//print_r( $comment ); //die();

		// get the post
		$post = get_post( $comment->comment_post_ID );
		//print_r( $post ); die();

		// was it a registered user?
		if ($comment->user_id != '0') {

			// get user details
			$user = get_userdata( $comment->user_id );

			// construct user link
			$user_link = bp_core_get_userlink( $activity->user_id );

		} else {

			// show anonymous user
			$user_link = '<span class="anon-commenter">'.__( 'Anonymous', 'bpwpapers' ).'</span>';

		}

		// allow plugins to override the name of the activity item
		$activity_name = apply_filters(
			'bpwpapers_activity_post_name',
			__( 'post', 'bpwpapers' ),
			$post
		);

		// init target link
		$target_post_link = '<a href="' . get_permalink( $post->ID ) .'">' .
								esc_html( $post->post_title ) .
							'</a>';

		// Replace the necessary values to display in group activity stream
		$activity->action = sprintf(

			__( '%s left a %s on a %s %s in the group %s:', 'bpwpapers' ),

			$user_link,
			'<a href="' . $activity->primary_link .'">' . __( 'comment', 'bpwpapers' ) . '</a>',
			$activity_name,
			$target_post_link,
			'<a href="' . bp_get_group_permalink( $group ) . '">' . esc_html( $group->name ) . '</a>'

		);

		// apply group id
		$activity->item_id = (int)$group_id;

		// change to groups component
		$activity->component = 'groups';

		// having marked all groupblogs as public, we need to hide activity from them if the group is private
		// or hidden, so they don't show up in sitewide activity feeds.
		if ( 'public' != $group->status ) {
			$activity->hide_sitewide = true;
		} else {
			$activity->hide_sitewide = false;
		}

		// set unique type
		$activity->type = $type;

		// prevent from firing again
		remove_action( 'bp_activity_before_save', array( $this, 'custom_comment_activity' ) );

		//trigger_error( print_r( array( 'comment activity AFTER' => $activity ), true ), E_USER_ERROR ); die();
		//print_r( array( 'comment activity AFTER' => $activity ) ); //die();

		// --<
		return $activity;

	}



	/**
	 * Set the name of the post type in an activity item.
	 *
	 * @param string $name The name of the post in an activity item
	 * @param object $post_obj The post object that the comment has been left on
	 * @return string $name The name of the post in an activity item
	 */
	public function custom_comment_activity_post_name( $name, $post_obj ) {

		// sanity check
		if ( ! is_object( $post_obj ) ) return $name;

		// if it's a page...
		if ( $post_obj->post_type == 'page' ) return __( 'page', 'bpwpapers' );

		// fallback
		return $name;

	}



	/**
	 * Set the action (at the top) of the activity item.
	 *
	 * @param string $action The action of the activity item
	 * @param object $activity The activity object
	 * @return string $action The action of the activity item
	 */
	public function custom_comment_activity_action(

		$action,
		$activity,
		$user_link,
		$comment_link,
		$activity_name,
		$target_post_link,
		$group_link

	) {

		// which blog?
		$blog_id = $activity->item_id;

		// only on working papers
		if ( ! bpwpapers_is_working_paper( $blog_id ) ) return $action;

		// safely get home URL
		$home_url = ( $blog_id !== false ) ? get_home_url( $blog_id ) : false;

		// bail if we don't get a home URL for the site
		if ( empty( $home_url ) ) return $action;

		// get site name
		$blog_name = get_blog_option( $blog_id, 'blogname' );

		// construct blog link
		$blog_link = '<a href="'.$home_url.'" title="'.esc_attr( $blog_name ).'">'.$blog_name.'</a>';

		// get name
		$name = bpwpapers_extension_name();

		// replace any necessary values to display in the activity stream
		$action = sprintf(

			__( '%1$s left a %2$s on the %3$s %4$s in the %5$s %6$s:', 'commentpress-core' ),

			$user_link,
			$comment_link,
			$activity_name,
			$target_post_link,
			strtolower( $name ),
			$blog_link

		);

		// --<
		return $action;

	}



	//==========================================================================



	/**
	 * Filter the group activity feed on a working paper site to show only items from the group.
	 *
	 * @param string $querystring The querystring
	 * @param string $object The filtered querystring
	 * @return string $new_querystring The filtered querystring
	 */
	public function filter_ajax_querystring( $querystring = '', $object = '' ) {

		//print_r( array( $querystring, $object ) ); die();
		//trigger_error( print_r( array( $querystring, $object ), true ), E_USER_ERROR ); die();

		// pass through if not activity stream
		if ( $object != 'activity' ) return $querystring;

		// handle only on a working paper
		if ( ! bpwpapers_is_working_paper( get_current_blog_id() ) ) return $querystring;

		// get group ID
		$group_id = bpwpapers_get_group_by_blog_id( get_current_blog_id() );

		// set some defaults
		$defaults = array(
			'scope' => 'activity',
			'object' => 'groups',
			'user_id' => 0,
			'action' => 'new_working_paper_post,new_working_paper_comment',
			'primary_id' => $group_id,
		);

		// parse defaults
		$new_querystring = wp_parse_args( $querystring, $defaults );

		// we must override some essential settings or the group won't show if there's a cookie
		// set for some other kind of activity
		$new_querystring['scope'] = 'activity';
		$new_querystring['object'] = 'groups';
		$new_querystring['user_id'] = 0;
		$new_querystring['primary_id'] = $group_id;
		//print_r( array( 'blog' => get_current_blog_id(), $querystring, $object, $new_querystring ) ); die();

		// build new string
		$new_querystring = build_query( $new_querystring );

		// allow plugins to override
		return apply_filters( 'bpwpapers_filter_ajax_querystring', $new_querystring, $querystring );

	}



	/**
	 * Filter the comment reply link on activity items. This is called during the
	 * loop, so we can assume that the activity item API will work.
	 *
	 * @return string $link The overridden comment reply link
	 */
	public function filter_comment_link( $link ) {

		// get type of activity
		$type = bp_get_activity_action_name();

		// our custom activity types
		$types = array( 'new_working_paper_post', 'new_working_paper_comment' );

		// not one of ours?
		if ( ! in_array( $type, $types ) ) return $link;

		// change the link
		if ( $type == 'new_working_paper_post' ) {
			$link_text = __( 'Comment', 'bpwpapers' );
		}

		if ( $type == 'new_working_paper_comment' ) {
			$link_text = __( 'Reply', 'bpwpapers' );
		}

		// construct new link to actual comment
		$link = '<a href="' . bp_get_activity_feed_item_link() . '" class="button acomment-reply bp-primary-action">' .
					$link_text .
				'</a>';

		// --<
		return $link;

	}



	//==========================================================================



	/**
	 * Check if anonymous commenting is allowed.
	 *
	 * @param bool $allowed whether commenting is is allowed or not
	 * @return bool $allowed whether commenting is is allowed or not
	 */
	public function allow_anon_commenting( $allowed ) {

		// get current blog ID
		$blog_id = get_current_blog_id();

		// pass through if not working paper
		if ( ! bpwpapers_is_working_paper( $blog_id ) ) { return $allowed; }

		// not allowed
		return false;

	}



	/**
	 * For working papers, if the user is a member of the group, allow unmoderated comments.
	 *
	 * @param int $approved the comment status
	 * @param array $commentdata the comment data
	 * @return bool $approved True if un-moderated commenting is allowed, false otherwise
	 */
	public function check_comment_approval( $approved, $commentdata ) {

		// get current blog ID
		$blog_id = get_current_blog_id();

		// pass through if not working paper
		if ( ! bpwpapers_is_working_paper( $blog_id ) ) { return $approved; }

		// get the user ID of the comment author
		$user_id = absint( $commentdata['user_ID'] );

		// get group for this blog
		$group_id = bpwpapers_get_group_by_blog_id( $blog_id );

		// did we get one?
		if ( $group_id != '' AND is_numeric( $group_id ) ) {

			// is this user a member?
			if ( groups_is_user_member( $user_id, $group_id ) ) {

				// allow un-moderated commenting
				return 1;

			}

		}

		// pass through
		return $approved;

	}



	/**
	 * Override CommentPress "Reply To" link.
	 *
	 * @param string $link the existing link
	 * @param array $args the setup array
	 * @param object $comment the comment
	 * @param object $post the post
	 * @return string $link The link markup
	 */
	public function override_reply_to_link( $link, $args, $comment, $post ) {

		// pass through if not logged in
		if ( ! is_user_logged_in() ) return $link;

		// get current blog ID
		$blog_id = get_current_blog_id();

		// pass through if not working paper
		if ( ! bpwpapers_is_working_paper( $blog_id ) ) return $link;

		// get group for this blog
		$group_id = bpwpapers_get_group_by_blog_id( $blog_id );

		// get user ID
		$user_id = bp_loggedin_user_id();

		// did we get one?
		if ( $group_id != '' AND is_numeric( $group_id ) ) {

			// is this user a member?
			if ( ! groups_is_user_member( $user_id, $group_id ) ) {

				// construct link
				$link = '<a rel="nofollow" href="'.bp_get_group_permalink( $group ).'">'.__( 'Join the group to reply', 'bpwpapers' ).'</a>';

			}

		}

		// --<
		return $link;

	}



	/**
	 * Decides whether or not to show comment form.
	 *
	 * @param bool $show whether or not to show comment form
	 * @return bool $show True if we should show the comment form, false otherwise
	 */
	public function show_comment_form( $show ) {

		// get current blog ID
		$blog_id = get_current_blog_id();

		// pass through if not working paper
		if ( ! bpwpapers_is_working_paper( $blog_id ) ) return $show;

		// get user ID
		$user_id = bp_loggedin_user_id();

		// get group for this blog
		$group_id = bpwpapers_get_group_by_blog_id( $blog_id );

		// did we get one?
		if ( $group_id != '' AND is_numeric( $group_id ) ) {

			// is this user a member?
			if ( groups_is_user_member( $user_id, $group_id ) ) {

				// pass through
				return $show;

			}

		}

		// --<
		return false;

	}



	/**
	 * Override CommentPress TinyMCE Javascript setting.
	 *
	 * @param bool $tinymce whether TinyMCE is enabled or not
	 * @return bool $tinymce whether TinyMCE is enabled or not
	 */
	public function disable_tinymce( $tinymce ) {

		// get current blog ID
		$blog_id = get_current_blog_id();

		// pass through if not working paper
		if ( ! bpwpapers_is_working_paper( $blog_id ) ) return $tinymce;

		// get group for this blog
		$group_id = bpwpapers_get_group_by_blog_id( $blog_id );

		// get user ID
		$user_id = bp_loggedin_user_id();

		// did we get one?
		if ( $group_id != '' AND is_numeric( $group_id ) ) {

			// is this user a member?
			if ( ! groups_is_user_member( $user_id, $group_id ) ) {

				// add filters on reply to link
				add_filter( 'commentpress_reply_to_para_link_text', array( $this, 'override_reply_to_text' ), 10, 2 );
				add_filter( 'commentpress_reply_to_para_link_href', array( $this, 'override_reply_to_href' ), 10, 2 );
				add_filter( 'commentpress_reply_to_para_link_onclick', array( $this, 'override_reply_to_onclick' ), 10, 1 );

				// disable
				$tinymce = 0;

			}

		}

		// use TinyMCE if logged in
		if ( is_user_logged_in() ) return $tinymce;

		// don't use TinyMCE
		return 0;

	}



	/**
	 * Override content of the reply to link.
	 *
	 * @param string $link_text the full text of the reply to link
	 * @param string $paragraph_text paragraph text
	 * @return string $link_text updated content of the reply to link
	 */
	public function override_reply_to_text( $link_text, $paragraph_text ) {

		// if not logged in...
		if ( ! is_user_logged_in() ) {

			// show helpful message
			return apply_filters(
				'bpwpapers_override_reply_to_text_denied',
				__( 'Create an account to leave a comment', 'bpwpapers' )
			);

		}

		// construct link content
		$link_text = __( 'Join the discussion to leave a comment', 'bpwpapers' );

		/*
		// construct link content (alt)
		$link_text = sprintf(
			__( 'Join the discussion to leave a comment on %s', 'bpwpapers' ),
			$paragraph_text
		);
		*/

		// --<
		return apply_filters( 'bpwpapers_override_reply_to_text', $link_text, $paragraph_text );

	}



	/**
	 * Override content of the reply to link target.
	 *
	 * @param string $href The existing target URL
	 * @param string $text_sig The text signature of the paragraph
	 * @return string $href Overridden target URL
	 */
	public function override_reply_to_href( $href, $text_sig ) {

		// if not logged in...
		if ( ! is_user_logged_in() ) {

			// show helpful message
			return apply_filters(
				'bpwpapers_override_reply_to_href_denied',
				'/create-account/'
			);

		}

		// get group for this blog
		$group = groups_get_group( array( 'group_id' => $this->group_id ) );

		// init URL
		$href = wp_nonce_url( bp_get_group_permalink( $group ) . 'join', 'groups_join_group' );

		// add identifier and anchor - should be fine for length, see:
		// http://stackoverflow.com/questions/812925/what-is-the-maximum-possible-length-of-a-query-string
		$href .= '&bpwpaper_group=true&bpwpaper_caller=' . $text_sig;

		// --<
		return apply_filters( 'bpwpapers_override_reply_to_href', $href, $text_sig );

	}



	/**
	 * Override content of the reply to link.
	 *
	 * @return string $onclick the reply to onclick attribute
	 */
	public function override_reply_to_onclick( $onclick ) {

		// --<
		return '';

	}



	//==========================================================================
	// Sidebar additions
	//==========================================================================



	/**
	 * Override the title of the Recent Posts section in the activity sidebar.
	 *
	 * @return string $title The title of the activity sidebar section
	 */
	public function get_activity_sidebar_recent_title() {

		// set title, but allow plugins to override
		$title = sprintf(
			__( 'Recent Comments in this %s', 'bpwpapers' ),
			bpwpapers_extension_name()
		);

		// --<
		return $title;

	}



	/**
	 * Show working papers activity in sidebar.
	 *
	 * @since 0.1
	 */
	public function get_activity_sidebar_section() {

		// All Activity

		// get activities
		if ( bp_has_activities( array(

			'scope' => 'groups',
			'action' => 'new_working_paper_post,new_working_paper_comment',

		) ) ) {

			// change header depending on logged in status
			if ( is_user_logged_in() ) {

				// set default
				$section_header_text = apply_filters(
					'bpwpapers_activity_tab_recent_title_all_yours',
					sprintf(
						__( 'Recent Activity in your %s', 'bpwpapers' ),
						bpwpapers_extension_plural()
					)
				);

			} else {

				// set default
				$section_header_text = apply_filters(
					'bpwpapers_activity_tab_recent_title_all_public',
					sprintf(
						__( 'Recent Activity in Public %s', 'bpwpapers' ),
						bpwpapers_extension_plural()
					)
				);

			}

			// open section
			echo '<h3 class="activity_heading">'.$section_header_text.'</h3>

			<div class="paragraph_wrapper workshop_comments_output">

			<ol class="comment_activity">';

			// do the loop
			while ( bp_activities() ) { bp_the_activity();
				echo $this->get_activity_item();
			}

			// close section
			echo '</ol>

			</div>';

		}



		// Friends Activity

		// for logged in users only...
		if ( is_user_logged_in() ) {

			// get activities
			if ( bp_has_activities( array(

				'scope' => 'friends',
				'action' => 'new_working_paper_post,new_working_paper_comment',

			) ) ) {

				// set default
				$section_header_text = apply_filters(
					'bpwpapers_activity_tab_recent_title_all_yours',
					sprintf(
						__( 'Friends Activity in your %s', 'bpwpapers' ),
						bpwpapers_extension_plural()
					)
				);

				// open section
				echo '<h3 class="activity_heading">'.$section_header_text.'</h3>

				<div class="paragraph_wrapper workshop_comments_output">

				<ol class="comment_activity">';

				// do the loop
				while ( bp_activities() ) { bp_the_activity();
					echo $this->get_activity_item();
				}

				// close section
				echo '</ol>

				</div>';

			}

		}

	}



	/**
	 * Show working papers activity in sidebar.
	 *
	 * @since 0.1
	 */
	public function get_activity_item() {

		$same_post = '';

		?>

		<?php do_action( 'bp_before_activity_entry' ); ?>

		<li class="<?php bp_activity_css_class(); echo $same_post; ?>" id="activity-<?php bp_activity_id(); ?>">

			<div class="comment-wrapper">

				<div class="comment-identifier">

					<a href="<?php bp_activity_user_link(); ?>"><?php bp_activity_avatar( 'width=32&height=32' ); ?></a>
					<?php bp_activity_action(); ?>

				</div>

				<div class="comment-content">

					<?php if ( bp_activity_has_content() ) : ?>

						<?php bp_activity_content_body(); ?>

					<?php endif; ?>

					<?php do_action( 'bp_activity_entry_content' ); ?>

				</div>

			</div>

		</li>

		<?php do_action( 'bp_after_activity_entry' ); ?>

		<?php

	}



} // end class BP_Working_Papers_Activity



