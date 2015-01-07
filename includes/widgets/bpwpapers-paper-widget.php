<?php /*
================================================================================
BuddyPress Working Papers Featured Working Paper Widget
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

--------------------------------------------------------------------------------
*/



/**
 * Makes a custom Widget for displaying a Featured Working Paper
 */
class BP_Working_Papers_Paper_Widget extends WP_Widget {



	/**
	 * Constructor registers widget with WordPress
	 *
	 * @return void
	 */
	function __construct() {

		// init parent
		parent::__construct(

			// base ID
			'bpwpapers_paper_widget',

			// name
			__( 'Featured Paper', 'bpwpapers' ),

			// args
			array(
				'description' => __( 'Use this widget to choose your Featured Working Paper', 'bpwpapers' ),
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

		// get paper
		if ( bpwpapers_has_blogs( array( 'include_blog_ids' => $instance['paper_id'] ) ) ) {

			while ( bp_blogs() ) : bp_the_blog();

				// get author ID
				//$author_id = bpwpapers_get_author_for_blog( $instance['paper_id'] );

				// get description
				$description = get_blog_option( $instance['paper_id'], 'blogdescription' );

				// get group ID
				$group_id = bpwpapers_get_group_by_blog_id( $instance['paper_id'] );

				?>
				<div class="bpwpapers-featured-paper clearfix">

					<div class="item-header">

						<div class="item-avatar">
							<a href="<?php bp_blog_permalink(); ?>"><?php bp_blog_avatar( 'type=full&width=300&height=300' ); ?></a>
						</div>

						<div class="item-name">
							<div class="item-main-title">
								<a href="<?php bp_blog_permalink(); ?>"><?php bp_blog_name(); ?></a>
							</div>
							<div class="item-sub-title">
								<a href="<?php bp_blog_permalink(); ?>"><?php echo $description; ?></a>
							</div>
						</div>

					</div>

					<div class="item">
						<div class="item-inner">

							<div class="item-meta"><span class="activity"><?php bp_blog_last_active(); ?></span></div>
							<?php do_action( 'bp_directory_blogs_item' ); ?>

							<?php $this->show_comments( $group_id ); ?>

							<div class="action">
								<div class="meta">
									<?php bp_blog_latest_post(); ?>
								</div>
								<?php do_action( 'bp_directory_blogs_actions' ); ?>
							</div>

						</div>
					</div>

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
			$title = __( 'Featured Paper', 'bpwpapers' );
		}

		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'bpwpapers' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php

		// get paper ID
		if ( isset( $instance['paper_id'] ) ) {
			$paper_id = $instance['paper_id'];
		} else {
			$paper_id = 0;
		}

		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'paper_id' ); ?>"><?php _e( 'Paper:', 'bpwpapers' ); ?></label>
		<select id="<?php echo $this->get_field_id( 'paper_id' ); ?>" name="<?php echo $this->get_field_name( 'paper_id' ); ?>">
			<?php

			// do we have one yet?
			$none = '';
			if ( $paper_id == 0 ) $none = ' selected="selected"';

			?><option value="0"<?php //echo $none; ?>><?php _e( 'None selected', 'bpwpapers' ); ?></option>
			<?php

			// init params
			$params = array();

			// set params we want to get all papers
			$params['type'] = 'alphabetical';
			$params['per_page'] = 100000;

			// get papers
			if ( bpwpapers_has_blogs( $params ) ) {

				while ( bp_blogs() ) : bp_the_blog();

					// get blog ID
					$blog_id = bp_get_blog_id();

					// get selected
					$selected = '';
					if ( $blog_id == $paper_id ) $selected = ' selected="selected"';

					// show select option
					echo '<option value="' . $blog_id . '"' . $selected . '>' . bp_get_blog_name() . '</option>'."\n";

				endwhile;

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
	 * Show latest activity for this paper
	 *
	 * @param $user_id The numeric ID of a WordPress user
	 * @return void
	 */
	public function show_comments( $group_id ) {

		// get activities
		if ( bp_has_activities( array(

			//'scope' => 'groups',
			'action' => 'new_working_paper_comment',
			'max' => 2,
			'primary_id' => $group_id,

		) ) ) {

			// double check, since something seems not to work
			global $activities_template;
			if ( $activities_template->has_activities() ) {

				?>

				<div class="item-title"><?php _e( 'Latest Activity', 'bpwpapers' ); ?></div>

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
										$comment_link = '<a href="' . bp_get_activity_comment_link() . '" class="button acomment-reply bp-primary-action" id="acomment-comment-' . bp_get_activity_id() . '">' . sprintf( __( 'Comment <span>%s</span>', 'bpwpapers' ), bp_activity_get_comment_count() ) . '</a>';

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



} // ends class BP_Working_Papers_Paper_Widget



// register this widget
register_widget( 'BP_Working_Papers_Paper_Widget' );



