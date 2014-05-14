<?php

/**
 * BuddyPress Working Papers - Working Paper Authors Directory
 */

get_header( 'buddypress' );

?>

	<!-- bppaperauthors/index.php -->
	<?php do_action( 'bp_before_directory_members_page' ); ?>

	<div id="content" role="main" class="<?php do_action( 'content_class' ); ?>">
		<div class="padder">

		<?php do_action( 'bp_before_directory_members' ); ?>

		<form action="" method="post" id="bppaperauthors-directory-form" class="dir-form">

			<h3><?php 
			
			// show title
			echo sprintf( 
				__( '%s Directory', 'bpwpapers' ), 
				apply_filters( 'bppaperauthors_extension_plural', __( 'Working Paper Authors', 'bpwpapers' ) )
			);

			?></h3>

			<?php do_action( 'bp_before_directory_members_content' ); ?>

			<div id="bppaperauthors-dir-search" class="dir-search" role="search">

				<?php bppaperauthors_directory_members_search_form(); ?>

			</div><!-- #bppaperauthors-dir-search -->

			<div class="item-list-tabs" role="navigation">
				<ul>
					
					<li class="selected" id="bppaperauthors-all"><a href="<?php 
						echo trailingslashit( bp_get_root_domain() . '/' . bppaperauthors_get_root_slug() ); 
					?>"><?php 
					
						// filter subnav title
						printf( 
							__( 'All %1$s <span>%2$s</span>', 'bpwpapers' ), 
							apply_filters( 'bppaperauthors_extension_plural', __( 'Working Paper Authors', 'bpwpapers' ) ),
							bp_get_total_member_count()
						); 
						
					?></a></li>

					<?php /* if ( is_user_logged_in() && bp_is_active( 'friends' ) && bp_get_total_friend_count( bp_loggedin_user_id() ) ) : ?>

						<li id="bppaperauthors-personal"><a href="<?php echo bp_loggedin_user_domain() . bp_get_friends_slug() . '/my-friends/' ?>"><?php printf( __( 'My Friends <span>%s</span>', 'commentpress-core' ), bp_get_total_friend_count( bp_loggedin_user_id() ) ); ?></a></li>

					<?php endif; */ ?>

					<?php do_action( 'bp_members_directory_member_types' ); ?>

				</ul>
			</div><!-- .item-list-tabs -->

			<div class="item-list-tabs" id="subnav" role="navigation">
				<ul>

					<?php do_action( 'bp_members_directory_member_sub_types' ); ?>

					<li id="bppaperauthors-order-select" class="last filter">

						<label for="bppaperauthors-order-by"><?php _e( 'Order By:', 'bpwpapers' ); ?></label>
						<select id="bppaperauthors-order-by">
							<option value="active"><?php _e( 'Last Active', 'bpwpapers' ); ?></option>
							<option value="newest"><?php _e( 'Newest Registered', 'bpwpapers' ); ?></option>

							<?php if ( bp_is_active( 'xprofile' ) ) : ?>

								<option value="alphabetical"><?php _e( 'Alphabetical', 'bpwpapers' ); ?></option>

							<?php endif; ?>

							<?php do_action( 'bp_members_directory_order_options' ); ?>

						</select>
					</li>
				</ul>
			</div>

			<div id="bppaperauthors-dir-list" class="bppaperauthors dir-list">

				<?php bp_locate_template( array( 'bppaperauthors/bppaperauthors-loop.php' ), true, false ); ?>

			</div><!-- #bppaperauthors-dir-list -->

			<?php do_action( 'bp_directory_members_content' ); ?>

			<?php wp_nonce_field( 'directory_members', '_wpnonce-member-filter' ); ?>

			<?php do_action( 'bp_after_directory_members_content' ); ?>

		</form><!-- #bppaperauthors-directory-form -->

		<?php do_action( 'bp_after_directory_members' ); ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php do_action( 'bp_after_directory_members_page' ); ?>

<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer( 'buddypress' ); ?>
