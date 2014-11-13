<?php /*
================================================================================
BuddyPress Working Papers Featured Reviewer Widget
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

--------------------------------------------------------------------------------
*/



/**
 * Makes a custom Widget for displaying a Featured Reviewer
 */
class BP_Working_Papers_Reviewer_Widget extends WP_Widget {



	/**
	 * Constructor registers widget with WordPress
	 *
	 * @return void
	 */
	function __construct() {

		// init parent
		parent::__construct(

			// base ID
			'bpwpapers_reviewer_widget',

			// name
			__( 'Featured Paper Reviewer', 'bpwpapers' ),

			// args
			array(
				'description' => __( 'Use this widget to choose your Featured Working Papers Reviewer', 'bpwpapers' ),
			)

		);

	}



	/**
	 * Outputs the HTML for this widget
	 *
	 * @param array $args An array of standard parameters for widgets in this theme
	 * @param array $instance An array of settings for this widget instance
	 * @return void Echoes its output
	 */
	public function widget( $args, $instance ) {

		// get widget title
		$title = apply_filters( 'widget_title', $instance['title'] );

		// show before
		echo $args['before_widget'];

		// if we have a title, show it
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		// get reviewer
		if ( bp_has_members( array( 'include' => $instance['reviewer_id'] ) ) ) {

			while ( bp_members() ) : bp_the_member();

				// user link
				$user_link = bp_core_get_user_domain( bp_get_member_user_id() ) . bp_get_activity_slug();

				?>
				<div class="bpwpapers-featured-reviewer clearfix">

					<div class="item-header">

						<div class="item-avatar">
							<a href="<?php echo $user_link; ?>"><?php bp_member_avatar( 'type=full&width=300&height=300' ); ?></a>
						</div>

						<div class="item-reviewer">
							<a href="<?php echo $user_link; ?>"><?php bp_member_name(); ?></a>
						</div>

					</div>

					<div class="item">
						<div class="item-inner">

							<div class="item-meta"><span class="activity"><?php bp_member_last_active(); ?></span></div>

							<?php do_action( 'bp_directory_members_item' ); ?>

							<?php do_action( 'bpwpapers_authors_directory_profile_fields' ); ?>

							<?php $this->show_comments( bp_get_member_user_id() ); ?>

							<span class="more"><a href="<?php echo $user_link; ?>"><?php _e( 'More activity by this reviewer &rarr;', 'ihc-cbox' ); ?></a></span>

						</div>
					</div><!-- /.item -->

				</div>
				<?php

			endwhile;

		}

		// show after
		echo $args['after_widget'];

		//print_r( $args ); die();

	}



	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		//print_r( $instance ); die();

		// get title
		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Featured Reviewer', 'bpwpapers' );
		}

		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'bpwpapers' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php

		// get reviewer ID
		if ( isset( $instance['reviewer_id'] ) ) {
			$reviewer_id = $instance['reviewer_id'];
		} else {
			$reviewer_id = 0;
		}

		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'reviewer_id' ); ?>"><?php _e( 'Reviewer:', 'bpwpapers' ); ?></label>
		<select id="<?php echo $this->get_field_id( 'reviewer_id' ); ?>" name="<?php echo $this->get_field_name( 'reviewer_id' ); ?>">
			<?php

			// do we have one yet?
			$none = '';
			if ( $reviewer_id == 0 ) $none = ' selected="selected"';

			?><option value="0"<?php //echo $none; ?>><?php _e( 'None selected', 'bpwpapers' ); ?></option>
			<?php

			// init params
			$params = array();

			// no, insert it
			$params['meta_key'] = BP_WORKING_PAPERS_AUTHOR_META_KEY;
			$params['meta_value'] = true;

			// remove this filter
			remove_filter( 'bp_core_get_users', 'bpwpapers_authors_core_get_users', 20 );

			// re-query with our params
			$reviewer_array = bp_core_get_users( $params );

			// re-add filter
			add_filter( 'bp_core_get_users', 'bpwpapers_authors_core_get_users', 20, 2 );

			// do we have any?
			if ( count( $reviewer_array['users'] ) > 0 ) {

				foreach( $reviewer_array['users'] AS $reviewer ) {

					// get reviewer name
					$reviewer_name = $reviewer->fullname;

					// sanity checks and fallbacks
					if ( empty( $reviewer_name ) ) $reviewer_name = $reviewer->display_name;
					if ( empty( $reviewer_name ) ) $reviewer_name = $reviewer->user_nicename;

					// get selected
					$selected = '';
					if ( $reviewer->ID == $reviewer_id ) $selected = ' selected="selected"';

					// show select option
					echo '<option value="' . $reviewer->ID . '"' . $selected . '>' . $reviewer_name . '</option>'."\n";

				}

			}

			?>
		</select>
		</p>
		<?php

	}



	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array $instance Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {

		// never lose a value
		$instance = wp_parse_args( $new_instance, $old_instance );

		// --<
		return $instance;

	}



	/**
	 * Show activity for this reviewer
	 *
	 * @param $user_id The numeric ID of a WordPress user
	 * @return void
	 */
	public function show_comments( $user_id ) {

		// get activities
		if ( bp_has_activities( array(

			//'scope' => 'groups',
			'action' => 'new_working_paper_comment',
			'max' => 1,
			'user_id' => $user_id,

		) ) ) {

			/*
			global $activities_template;
			print_r( array(
				'has_activities' => $activities_template->has_activities(),
				'activities_template' => $activities_template,
			) ); die();
			*/

			// double check, since something seems not to work
			global $activities_template;
			if ( $activities_template->has_activities() ) {

				?>

				<div class="item-title"><?php _e( 'Latest Review', 'bpwpapers' ); ?></div>

				<ul class="bpwpapers-widget-activity-list item-list">

				<?php while ( bp_activities() ) : bp_the_activity(); ?>

					<?php do_action( 'bp_before_activity_entry' ); ?>

					<li class="<?php bp_activity_css_class(); ?>" id="activity-<?php bp_activity_id(); ?>">

						<div class="activity-content">

							<div class="activity-header">
								<?php bp_activity_action(); ?>
							</div>

							<?php if ( bp_activity_has_content() ) : ?>

								<div class="activity-inner">
									<?php bp_activity_content_body(); ?>
								</div>

							<?php endif; ?>

							<?php do_action( 'bp_activity_entry_content' ); ?>

							<?php if ( is_user_logged_in() ) : ?>

								<div class="activity-meta">

									<?php if ( bp_activity_can_comment() ) : ?>

										<?php

										// construct comment link
										$comment_link = '<a href="' . bp_get_activity_comment_link() . '" class="button acomment-reply bp-primary-action" id="acomment-comment-' . bp_get_activity_id() . '">'.sprintf( __( 'Comment <span>%s</span>', 'bpwpapers' ), bp_activity_get_comment_count() ) . '</a>';

										// echo it, but allow plugin overrides first
										echo apply_filters( 'cp_activity_entry_comment_link', $comment_link );

										?>

									<?php endif; ?>

									<?php do_action( 'bp_activity_entry_meta' ); ?>

								</div>

							<?php endif; ?>

						</div>

					</li>

					<?php do_action( 'bp_after_activity_entry' ); ?>

				<?php endwhile; ?>

				</ul>

				<?php

			} else {

				?>

				<p class="bpwpapers-no-activity"><?php _e( 'No Recent Comments.' ); ?></p>

				<?php

			}

		} else {

			?>

			<p class="bpwpapers-no-activity"><?php _e( 'No Recent Comments.' ); ?></p>

			<?php

		}

	}



} // ends class BP_Working_Papers_Reviewer_Widget



// register this widget
register_widget( 'BP_Working_Papers_Reviewer_Widget' );



