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

	// groups
	public $groups = array();
	
	
	
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
	
		// hooks that always need to be present...
		add_action( 'bp_setup_globals', array( $this, 'add_filter_options' ) );
		
		// if the current blog is a working paper...
		if ( bpwpapers_is_working_paper( get_current_blog_id() ) ) {
			
			// add custom site activity
			//add_action( 'bp_activity_before_save', array( $this, 'custom_site_activity' ), 10, 1 );
			
			// add custom post activity
			add_action( 'bp_activity_before_save', array( $this, 'custom_post_activity' ), 10, 1 );
			
			// add custom comment activity
			add_action( 'bp_activity_before_save', array( $this, 'custom_comment_activity' ), 10, 1 );
			
			// add filter for post name in activity item
			//add_filter( 'bpwpapers_activity_post_name', array( $this, 'custom_comment_activity_post_name' ), 10, 2 );
		
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
			
			/*
			// add navigation items for groups
			//add_filter( 'cp_nav_after_network_home_title', array( $this, 'get_group_navigation_links' ) );
		
			// add section to activity sidebar in CommentPress
			//add_filter( 'commentpress_bp_activity_sidebar_before_members', array( $this, 'get_activity_sidebar_section' ) );
			
			// override cp_activity_tab_recent_title_blog
			//add_filter( 'cp_activity_tab_recent_title_blog', array( $this, 'get_activity_sidebar_recent_title' ) );
			*/
			
		}
		
	}
	
	
		
	//==========================================================================
	
	
	
	/**
	 * Add actions for filter options on group activity stream
	 * 
	 * @return void
	 */
	function add_filter_options() {
		
		// add our posts filter
		add_action( 'bp_activity_filter_options', array( $this, 'posts_filter_option' ) );
		if ( bpwpapers_group_has_working_paper() ) {
			add_action( 'bp_group_activity_filter_options', array( $this, 'posts_filter_option' ) );
		}
		add_action( 'bp_member_activity_filter_options', array( $this, 'posts_filter_option' ) );
		
		// add our comments filter
		add_action( 'bp_activity_filter_options', array( $this, 'comments_filter_option' ) );
		if ( bpwpapers_group_has_working_paper() ) {
			add_action( 'bp_group_activity_filter_options', array( $this, 'comments_filter_option' ) );
		}
		add_action( 'bp_member_activity_filter_options', array( $this, 'comments_filter_option' ) );
		
	}
	
	
	
	/** 
	 * Add a filter option to the filter select box on group activity pages
	 */
	function posts_filter_option( $slug ) {
		
		// default name, but allow plugins to override
		$post_name = apply_filters( 
			'bpwpapers_post_name', 
			__( 'Working Paper Posts', 'bpwpapers' )
		);
		
		// construct option
		$option = '<option value="new_working_paper_post">'.$post_name.'</option>'."\n";
		
		// print
		echo $option;

	}
	
	
	
	/**
	 * Add a filter option to the filter select box on group activity pages
	 */
	function comments_filter_option() { 
		
		// default name, but allow plugins to override
		$comment_name = apply_filters( 
			'bpwpapers_comment_name', 
			__( 'Working Paper Comments', 'bpwpapers' )
		);
		
		// construct option
		$option = '<option value="new_working_paper_comment">'.$comment_name.'</option>'."\n";
		
		// print
		echo $option;
		
	}
	
	
	
	//==========================================================================
	
	
	
	/**
	 * Record the blog post activity for the group
	 * 
	 * @see: bp_groupblog_set_group_to_post_activity( $activity )
	 * @return object $activity The new activity item
	 */
	function custom_post_activity( $activity ) {
	
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
			__( 'post', 'bpwpapers' )
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
		
			// replace the necessary values to display in group activity stream
			$activity->action = sprintf( 
		
				__( '%s updated a %s %s in the group %s:', 'bpwpapers' ),
			
				$activity_author, 
				$activity_name, 
				'<a href="' . get_permalink( $post->ID ) .'">' . esc_attr( $post->post_title ) . '</a>', 
				'<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' 
			
			);
		
		} else {
	
			// replace the necessary values to display in group activity stream
			$activity->action = sprintf( 
		
				__( '%s wrote a new %s %s in the group %s:', 'bpwpapers' ),
			
				$activity_author, 
				$activity_name, 
				'<a href="' . get_permalink( $post->ID ) .'">' . esc_attr( $post->post_title ) . '</a>', 
				'<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' 
			
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
		remove_action( 'bp_activity_before_save', 'bpwpapers_group_custom_post_activity' );
		
		// --<
		return $activity;
		
	}
	
	
	
	/**
	 * Record the blog activity for the group. Note: if the site is a CommentPress
	 * site, then this method will be dropped in favour of the one internal to
	 * CommentPress, because CommentPress needs to know the subpage of a comment
	 * 
	 * @see: bp_groupblog_set_group_to_post_activity()
	 * @return object $activity The new activity item
	 */
	function custom_comment_activity( $activity ) {
	
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
	 * Set the name of the post type in an activity item
	 * 
	 * @param string $name The name of the post in an activity item
	 * @param object $post_obj The post object that the comment has been left on
	 * @return string $name The name of the post in an activity item
	 */
	function custom_comment_activity_post_name( $name, $post_obj ) {
		
		// nothing for now
		return $name;
		
	}
	
	
	
	/** 
	 * Check if anonymous commenting is allowed
	 * 
	 * @param bool $allowed whether commenting is is allowed or not
	 * @return bool $allowed whether commenting is is allowed or not
	 */
	function allow_anon_commenting( $allowed ) {

		// get current blog ID
		$blog_id = get_current_blog_id();
	
		// pass through if not working paper
		if ( ! bpwpapers_is_working_paper( $blog_id ) ) { return $allowed; }
	
		// not allowed
		return false;

	}
	
	
	
	/** 
	 * For working papers, if the user is a member of the group, allow unmoderated comments
	 * 
	 * @param int $approved the comment status
	 * @param array $commentdata the comment data
	 */
	function check_comment_approval( $approved, $commentdata ) {
	
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
	 * Override CommentPress "Reply To" link
	 * 
	 * @param string $link the existing link
	 * @param array $args the setup array
	 * @param object $comment the comment
	 * @param object $post the post
	 * @return string $link The link markup
	 */
	function override_reply_to_link( $link, $args, $comment, $post ) {
	
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
	 * Decides whether or not to show comment form
	 * 
	 * @param bool $show whether or not to show comment form
	 * @return bool $show True if we should show the comment form, false otherwise
	 */
	function show_comment_form( $show ) {
	
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
	 * Override CommentPress TinyMCE Javascript setting
	 * 
	 * @param bool $tinymce whether TinyMCE is enabled or not
	 * @return bool $tinymce whether TinyMCE is enabled or not
	 */
	function disable_tinymce( $tinymce ) {
		
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
				add_filter( 'commentpress_reply_to_para_link_href', array( $this, 'override_reply_to_href' ), 10, 1 );
				add_filter( 'commentpress_reply_to_para_link_onclick', array( $this, 'override_reply_to_onclick' ), 10, 2 );
				
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
	 * Override content of the reply to link
	 * 
	 * @param string $link_text the full text of the reply to link
	 * @param string $paragraph_text paragraph text
	 * @return string $link_text updated content of the reply to link
	 */
	function override_reply_to_text( $link_text, $paragraph_text ) {
	
		// construct link content
		$link_text = sprintf(
			__( 'Join the group to leave a comment on %s', 'bpwpapers' ),
			$paragraph_text
		);
		
		// --<
		return $link_text;
	
	}
	
	
	
	/** 
	 * Override content of the reply to link target
	 * 
	 * @param string $href existing target URL
	 * @return string $href permalink of the groups directory
	 */
	function override_reply_to_href( $href ) {
	
		// --<
		return bp_get_groups_directory_permalink();
	
	}
	
	
	
	/** 
	 * Override content of the reply to link
	 * 
	 * @return string $onclick the reply to onclick attribute
	 */
	function override_reply_to_onclick( $onclick ) {
	
		// --<
		return '';
	
	}
	
	
	
	//==========================================================================
	// Methods below are yet to do...
	//==========================================================================
	
	
	
	/** 
	 * Adds links to the Special Pages menu in CommentPress themes
	 */
	function get_group_navigation_links() {
	
		// is a CommentPress theme active?
		if ( function_exists( 'commentpress_setup' ) ) {

			// init HTML output
			$html = '';
	
			// get the groups this user can see
			$user_group_ids = $this->get_groups_for_user();
	
			// kick out if all are empty
			if (
				count( $user_group_ids['my_groups'] ) == 0 AND 
				count( $user_group_ids['linked_groups'] ) == 0 AND 
				count( $user_group_ids['public_groups'] ) == 0 
			) {
				// --<
				return;
			}
			
			// init array
			$groups = array();
				
			// if any has entries
			if (
				count( $user_group_ids['my_groups'] ) > 0 OR 
				count( $user_group_ids['public_groups'] ) > 0 
			) {

				// merge the arrays
				$groups = array_unique( array_merge( 
					$user_group_ids['my_groups'], 
					$user_group_ids['linked_groups'], 
					$user_group_ids['public_groups'] 
				) );

			}
			
			// define config array
			$config_array = array(
				//'user_id' => $user_id,
				'type' => 'alphabetical',
				'populate_extras' => 0,
				'include' => $groups
			);
	
			// get groups
			if ( bp_has_groups( $config_array ) ) {
		
				// access object
				global $groups_template, $post;
		
				// only show if user has more than one...
				if ( $groups_template->group_count > 1 ) {
				
					// set title, but allow plugins to override
					$title = apply_filters( 
						'bpwpapers_working_papers_menu_item_title', 
						sprintf(
							__( 'Groups reading this %s', 'bpwpapers' ),
							apply_filters( 'bpwpapers_extension_name', __( 'site', 'bpwpapers' ) )
						)
					);
					
					// construct item
					$html .= '<li><a href="#working_papers-list" id="btn_working_papers" class="css_btn" title="'.$title.'">'.$title.'</a>';
				
					// open sublist
					$html .= '<ul class="children" id="working_papers-list">'."\n";
					
					// init lists
					$mine = array();
					$linked = array();
					$public = array();

					// do the loop
					while ( bp_groups() ) {  bp_the_group();
					
						// construct item
						$item = '<li>'.
									'<a href="'.bp_get_group_permalink().'" class="css_btn btn_working_papers" title="'.bp_get_group_name().'">'.
										bp_get_group_name().
									'</a>'.
								'</li>';
						
						// get group ID
						$group_id = bp_get_group_id();
						
						// mine?
						if ( in_array( $group_id, $user_group_ids['my_groups'] ) ) {
							$mine[] = $item;
							continue;
						}
			
						// linked?
						if ( in_array( $group_id, $user_group_ids['linked_groups'] ) ) {
							$linked[] = $item;
							continue;
						}
			
						// public?
						if ( in_array( $group_id, $user_group_ids['public_groups'] ) ) {
							$public[] = $item;
						}
						
					} // end while
					
					// did we get any that are mine?
					if ( count( $mine ) > 0 ) {
					
						// join items
						$items = implode( "\n", $mine );
					
						// only show if we one of the other lists is populated
						if ( count( $linked ) > 0 OR count( $public ) > 0 ) {
						
							// construct title
							$title = __( 'My Groups', 'bpwpapers' );
						
							// construct item
							$sublist = '<li><a href="#working_papers-list-mine" id="btn_working_papers_mine" class="css_btn" title="'.$title.'">'.$title.'</a>';
						
							// open sublist
							$sublist .= '<ul class="children" id="working_papers-list-mine">'."\n";
							
							// insert items
							$sublist .= $items;
						
							// close sublist
							$sublist .= '</ul>'."\n";
							$sublist .= '</li>'."\n";
							
							// replace items
							$items = $sublist;
						
						}
						
						// add to html
						$html .= $items;
				
					}
			
					// did we get any that are linked?
					if ( count( $linked ) > 0 ) {
					
						// join items
						$items = implode( "\n", $linked );
					
						// only show if we one of the other lists is populated
						if ( count( $mine ) > 0 OR count( $public ) > 0 ) {
						
							// construct title
							$title = __( 'Linked Groups', 'bpwpapers' );
						
							// construct item
							$sublist = '<li><a href="#working_papers-list-linked" id="btn_working_papers_linked" class="css_btn" title="'.$title.'">'.$title.'</a>';
						
							// open sublist
							$sublist .= '<ul class="children" id="working_papers-list-linked">'."\n";
							
							// insert items
							$sublist .= $items;
						
							// close sublist
							$sublist .= '</ul>'."\n";
							$sublist .= '</li>'."\n";
							
							// replace items
							$items = $sublist;
						
						}
						
						// add to html
						$html .= $items;
				
					}
			
					// did we get any that are public?
					if ( count( $public ) > 0 ) {
					
						// join items
						$items = implode( "\n", $public );
					
						// only show if we one of the other lists is populated
						if ( count( $mine ) > 0 OR count( $linked ) > 0 ) {
						
							// construct title
							$title = __( 'Public Groups', 'bpwpapers' );
						
							// construct item
							$sublist = '<li><a href="#working_papers-list-public" id="btn_working_papers_public" class="css_btn" title="'.$title.'">'.$title.'</a>';
						
							// open sublist
							$sublist .= '<ul class="children" id="working_papers-list-public">'."\n";
							
							// insert items
							$sublist .= $items;
						
							// close sublist
							$sublist .= '</ul>'."\n";
							$sublist .= '</li>'."\n";
							
							// replace items
							$items = $sublist;
						
						}
						
						// add to html
						$html .= $items;
				
					}
			
					// close tags
					$html .= '</ul>'."\n";
					$html .= '</li>'."\n";
			
				} else {
			
					// set title
					$title = __( 'Group Home Page', 'bpwpapers' );
				
					// do we want to use bp_get_group_name()
			
					// do the loop (though there will only be one item
					while ( bp_groups() ) {  bp_the_group();
			
						// construct item
						$html .= '<li>'.
									'<a href="'.bp_get_group_permalink().'" id="btn_working_papers" class="css_btn" title="'.$title.'">'.
										$title.
									'</a>'.
								 '</li>';
				
					}
			
				}
	
			}
	
			// output
			echo $html;
	
		}

	}
	
	
	
	// =============================================================================
	// We may or may not use what follows...
	// =============================================================================
	
	
	
	/** 
	 * Show working papers activity in sidebar
	 */
	function get_activity_sidebar_section() {
	
		// All Activity
		
		// get activities	
		if ( bp_has_activities( array(

			'scope' => 'groups',
			'action' => 'new_working_paper_comment,new_working_paper_post',
	
		) ) ) {

			// change header depending on logged in status
			if ( is_user_logged_in() ) {
	
				// set default
				$section_header_text = apply_filters(
					'bpwpapers_activity_tab_recent_title_all_yours', 
					sprintf(
						__( 'All Recent Activity in your %s', 'bpwpapers' ),
						apply_filters( 'bpwpapers_extension_plural', __( 'Working Papers', 'bpwpapers' ) )
					)
				);
		
			} else { 
	
				// set default
				$section_header_text = apply_filters(
					'bpwpapers_activity_tab_recent_title_all_public', 
					sprintf(
						__( 'Recent Activity in Public %s', 'bpwpapers' ),
						apply_filters( 'bpwpapers_extension_plural', __( 'Working Papers', 'bpwpapers' ) )
					)
				);
	
			}
			
			// open section
			echo '<h3 class="activity_heading">'.$section_header_text.'</h3>
			
			<div class="paragraph_wrapper working_papers_comments_output">
	
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
				'action' => 'new_working_paper_comment,new_working_paper_post',
	
			) ) ) {

				// set default
				$section_header_text = apply_filters(
					'bpwpapers_activity_tab_recent_title_all_yours', 
					sprintf(
						__( 'Friends Activity in your %s', 'bpwpapers' ),
						apply_filters( 'bpwpapers_extension_plural', __( 'Working Papers', 'bpwpapers' ) )
					)
				);
	
				// open section
				echo '<h3 class="activity_heading">'.$section_header_text.'</h3>
			
				<div class="paragraph_wrapper working_papers_comments_output">
	
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
	 * Override the title of the Recent Posts section in the activity sidebar
	 * 
	 * @return string $title The title of the activity sidebar section
	 */
	function get_activity_sidebar_recent_title() {
	
		// set title, but allow plugins to override
		$title = sprintf(
			__( 'Recent Comments in this %s', 'bpwpapers' ),
			apply_filters( 'bpwpapers_extension_name', __( 'Working Paper', 'bpwpapers' ) )
		);
		
		// --<
		return $title;
		
	}
	
	
	
	/** 
	 * Show working papers activity in sidebar
	 */
	function get_activity_item() {
		
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



