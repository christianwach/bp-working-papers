<?php

/**
 * BuddyPress Working Papers - Working Papers Directory
 */

get_header( 'buddypress' );

?>

	<!-- bpwpapers/index.php -->
	<?php do_action( 'bp_before_directory_blogs_page' ); ?>



	<div id="content" role="main" class="<?php do_action( 'content_class' ); ?>">
		<div class="padder">

		<?php do_action( 'bp_before_directory_blogs' ); ?>

		<form action="" method="post" id="bpwpapers-directory-form" class="dir-form">

			<h3><?php

			// show title
			echo sprintf(
				__( '%s Directory', 'bpwpapers' ),
				bpwpapers_extension_plural()
			);

			// show "create" if logged in
			if ( is_user_logged_in() ) {

				?> &nbsp;<a class="button" href="<?php

				// print link to create page
				echo bp_get_root_domain() . '/' . bpwpapers_get_root_slug() . '/create/'

				?>"><?php

				// show create link
				echo sprintf(
					__( 'Create a %s', 'bpwpapers' ),
					bpwpapers_extension_name()
				);

				?></a><?php

			}

			?></h3>

			<?php do_action( 'bp_before_directory_blogs_content' ); ?>

			<div id="blog-dir-search" class="dir-search" role="search">

				<?php bp_directory_blogs_search_form(); ?>

			</div><!-- #blog-dir-search -->

			<div class="item-list-tabs" role="navigation">
				<ul>
					<li class="selected" id="bpwpapers-all"><a href="<?php bp_root_domain(); ?>/<?php bpwpapers_root_slug(); ?>"><?php

						// filter subnav title
						printf(
							__( 'All %1$s <span>%2$s</span>', 'bpwpapers' ),
							bpwpapers_extension_plural(),
							bpwpapers_get_total_paper_count()
						);

					?></a></li>

					<?php if ( is_user_logged_in() && bpwpapers_get_total_paper_count_for_user( bp_loggedin_user_id() ) ) : ?>

						<li id="bpwpapers-personal"><a href="<?php echo bp_loggedin_user_domain() . bpwpapers_get_slug(); ?>"><?php

						printf(
							__( 'My %1$s <span>%2$s</span>', 'bpwpapers' ),
							bpwpapers_extension_plural(),
							bpwpapers_get_total_paper_count_for_user( bp_loggedin_user_id() )
						);

						?></a></li>

					<?php endif; ?>

					<?php do_action( 'bpwpapers_blogs_directory_blog_types' ); ?>

				</ul>
			</div><!-- .item-list-tabs -->

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

		</form><!-- #bpwpapers-directory-form -->

		<?php do_action( 'bp_after_directory_blogs' ); ?>

		</div><!-- .padder -->
	</div><!-- #content -->



	<?php do_action( 'bp_after_directory_blogs_page' ); ?>



<?php get_sidebar( 'working-papers' ); ?>
<?php get_footer( 'buddypress' ); ?>