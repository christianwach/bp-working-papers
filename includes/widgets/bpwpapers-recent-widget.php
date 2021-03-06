<?php /*
================================================================================
BuddyPress Working Papers Recent Papers Widget
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

--------------------------------------------------------------------------------
*/



/**
 * Makes a custom Widget for displaying Working Papers Activity
 */
class BP_Working_Papers_Recent_Papers_Widget extends WP_Widget {



	/**
	 * Constructor registers widget with WordPress
	 *
	 * @return void
	 */
	function __construct() {

		// init parent
		parent::__construct(

			// base ID
			'bpwpapers_recent_widget',

			// name
			sprintf(
				__( 'Recent %s', 'bpwpapers' ),
				bpwpapers_extension_plural()
			),

			// args
			array(
				'description' => sprintf(
					__( 'Use this widget to display Recent %s', 'bpwpapers' ),
					bpwpapers_extension_plural()
				),
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

		// get activities
		if ( bp_has_activities( array(

			'scope' => 'blogs',
			'action' => 'new_working_paper',
			'max' => 3,

		) ) ) {

			global $activities_template, $bp_working_papers;
			//print_r( $activities_template ); die();

			?>

			<ul class="bpwpapers-activity-widget-list item-list">

			<?php while ( bp_activities() ) : bp_the_activity(); ?>

				<?php do_action( 'bp_before_activity_entry' ); ?>

				<li class="<?php bp_activity_css_class(); ?>" id="activity-<?php bp_activity_id(); ?>">

					<div class="activity-avatar">
						<a href="<?php bp_activity_user_link(); ?>"><?php bp_activity_avatar( 'width=50px&height=50px' ); ?></a>
					</div>

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

						<div class="activity-meta">

							<?php

							// construct "View Site" link
							$comment_link = '<a href="' . bp_get_activity_feed_item_link() . '" class="button acomment-reply">' .
												__( 'Visit Paper', 'bpwpapers' ) .
											'</a>';

							// echo it
							echo $comment_link;

							?>

							<?php do_action( 'bp_activity_entry_meta' ); ?>

						</div>

					</div>

				</li>

				<?php do_action( 'bp_after_activity_entry' ); ?>

			<?php endwhile; ?>

			</ul>

			<?php

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
			$title = sprintf( __( 'Recent %s', 'bpwpapers' ), bpwpapers_extension_plural() );
		}

		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'bpwpapers' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
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



} // ends class BP_Working_Papers_Recent_Papers_Widget



// register this widget
register_widget( 'BP_Working_Papers_Recent_Papers_Widget' );



