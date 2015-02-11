<?php /*
================================================================================
BuddyPress Working Papers Featured Author Widget
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

--------------------------------------------------------------------------------
*/



/**
 * Makes a custom Widget for displaying a Featured Author
 */
class BP_Working_Papers_Author_Widget extends WP_Widget {



	/**
	 * Constructor registers widget with WordPress
	 *
	 * @return void
	 */
	function __construct() {

		// init parent
		parent::__construct(

			// base ID
			'bpwpapers_author_widget',

			// name
			sprintf(
				__( 'Featured %s Author', 'bpwpapers' ),
				bpwpapers_extension_name()
			),

			// args
			array(
				'description' => sprintf(
					__( 'Use this widget to choose your Featured %s Author', 'bpwpapers' ),
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

		// get author
		if ( bp_has_members( array( 'include' => $instance['author_id'] ) ) ) {

			while ( bp_members() ) : bp_the_member();

				// user link
				$user_link = bp_core_get_user_domain( bp_get_member_user_id() ) . bpwpapers_get_slug();

				?>
				<div class="bpwpapers-featured-author clearfix">

					<div class="item-header">

						<div class="item-avatar">
							<a href="<?php echo $user_link; ?>"><?php bp_member_avatar( 'type=full&width=300&height=300' ); ?></a>
						</div>

						<div class="item-author">
							<a href="<?php echo $user_link; ?>"><?php bp_member_name(); ?></a>
						</div>

					</div>

					<div class="item">
						<div class="item-inner">

							<div class="item-title">
								<?php if ( bp_get_member_latest_update() ) : ?>
									<span class="update"><?php bp_member_latest_update(); ?></span>
								<?php endif; ?>
							</div>

							<div class="item-meta"><span class="activity"><?php bp_member_last_active(); ?></span></div>

							<?php do_action( 'bp_directory_members_item' ); ?>

							<?php do_action( 'bpwpapers_authors_directory_profile_fields' ); ?>
							<?php do_action( 'bpwpapers_authors_directory_papers_list', 'widget-loop' ); ?>

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
			$title = __( 'Featured Author', 'bpwpapers' );
		}

		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'bpwpapers' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php

		// get author ID
		if ( isset( $instance['author_id'] ) ) {
			$author_id = $instance['author_id'];
		} else {
			$author_id = 0;
		}

		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'author_id' ); ?>"><?php _e( 'Author:', 'bpwpapers' ); ?></label>
		<select id="<?php echo $this->get_field_id( 'author_id' ); ?>" name="<?php echo $this->get_field_name( 'author_id' ); ?>">
			<?php

			// do we have one yet?
			$none = '';
			if ( $author_id == 0 ) $none = ' selected="selected"';

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
			$author_array = bp_core_get_users( $params );

			// re-add filter
			add_filter( 'bp_core_get_users', 'bpwpapers_authors_core_get_users', 20, 2 );

			// do we have any?
			if ( count( $author_array['users'] ) > 0 ) {

				foreach( $author_array['users'] AS $author ) {

					// get author name
					$author_name = $author->fullname;

					// sanity checks and fallbacks
					if ( empty( $author_name ) ) $author_name = $author->display_name;
					if ( empty( $author_name ) ) $author_name = $author->user_nicename;

					// get selected
					$selected = '';
					if ( $author->ID == $author_id ) $selected = ' selected="selected"';

					// show select option
					echo '<option value="' . $author->ID . '"' . $selected . '>' . $author_name . '</option>'."\n";

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



} // ends class BP_Working_Papers_Author_Widget



// register this widget
register_widget( 'BP_Working_Papers_Author_Widget' );



