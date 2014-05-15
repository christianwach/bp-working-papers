<?php

/**
 * BuddyPress Working Papers - Working Papers Directory
 */

get_header( 'buddypress' );

?>

	<!-- bpwpapers/member.php -->
	<?php do_action( 'bp_before_directory_blogs_page' ); ?>
	
	
	
	<div id="content" role="main" class="<?php do_action( 'content_class' ); ?>">
		<div class="padder">

		<?php do_action( 'bp_before_directory_blogs' ); ?>

		<div class="dir-form">

			<h3><?php
			
			// get current user
			$current_user = wp_get_current_user();
			
			// if not my papers
			if ( !is_user_logged_in() OR bp_displayed_user_id() != bp_loggedin_user_id() ) {
			
				// show title for other users
				echo sprintf( 
					__( '%1$s by %2$s', 'bpwpapers' ),
					apply_filters( 'bpwpapers_extension_plural', __( 'Working Papers', 'bpwpapers' ) ),
					bp_get_displayed_user_fullname()
				);
			
			} else {
			
				// show "my working papers" title
				echo sprintf( 
					__( 'My %s', 'bpwpapers' ),
					apply_filters( 'bpwpapers_extension_plural', __( 'Working Papers', 'bpwpapers' ) )
				);
			
			}
			
			// optionally show "create" button
			if ( is_user_logged_in() AND bp_displayed_user_id() == bp_loggedin_user_id() ) {
			
				?> &nbsp;<a class="button" href="<?php 
			
				// print link to create page
				echo bp_get_root_domain() . '/' . bpwpapers_get_root_slug() . '/create/' 
			
				?>"><?php 
			
				// show create link
				echo sprintf( 
					__( 'Create a %s', 'bpwpapers' ), 
					apply_filters( 'bpwpapers_extension_name', __( 'Working Paper', 'bpwpapers' ) )
				);

				?></a><?php
			
			}
			
			?></h3>
			
		</div>

		<?php do_action( 'bp_before_directory_blogs_content' ); ?>

		<div class="item-list-tabs" id="subnav" role="navigation">
			<ul>

				<?php do_action( 'bp_blogs_directory_blog_sub_types' ); ?>

				<li id="bpwpapers-order-select" class="last filter">

					<label for="bpwpapers-order-by"><?php _e( 'Order By:', 'bpwpapers' ); ?></label>
					<select id="bpwpapers-order-by">
						<option value="active"><?php _e( 'Last Active', 'bpwpapers' ); ?></option>
						<option value="newest"><?php _e( 'Newest', 'bpwpapers' ); ?></option>
						<option value="alphabetical"><?php _e( 'Alphabetical', 'bpwpapers' ); ?></option>

						<?php do_action( 'bp_blogs_directory_order_options' ); ?>

					</select>
				</li>
			</ul>
		</div>

		<div id="bpwpapers-dir-list" class="bpwpapers dir-list">

			<?php bp_locate_template( array( 'bpwpapers/bpwpapers-loop.php' ), true, false ); ?>

		</div><!-- #bpwpapers-dir-list -->

		<?php do_action( 'bp_directory_blogs_content' ); ?>

		<?php wp_nonce_field( 'directory_bpwpapers', '_wpnonce-bpwpapers-filter' ); ?>

		<?php do_action( 'bp_after_directory_blogs_content' ); ?>

		<?php do_action( 'bp_after_directory_blogs' ); ?>

		</div><!-- .padder -->
	</div><!-- #content -->
	
	
	
	<?php do_action( 'bp_after_directory_blogs_page' ); ?>



<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer( 'buddypress' ); ?>