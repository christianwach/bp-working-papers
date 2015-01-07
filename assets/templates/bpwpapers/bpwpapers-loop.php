<?php

/**
 * BuddyPress Working Papers - Working Papers Loop
 * Querystring is set via AJAX in _inc/ajax.php - bp_dtheme_object_filter()
 */



do_action( 'bp_before_blogs_loop' ); ?>
<!-- bpwpapers/bpwpapers-loop.php -->
<?php

// search for them - TODO: add AJAX query string compatibility
if ( bpwpapers_has_blogs( bp_ajax_querystring( 'bpwpapers' ) ) ) {

	?>

	<div id="pag-top" class="pagination">

		<div class="pag-count" id="blog-dir-count-top">
			<?php bpwpapers_blogs_pagination_count(); ?>
		</div>

		<div class="pagination-links" id="blog-dir-pag-top">
			<?php bp_blogs_pagination_links(); ?>
		</div>

	</div>

	<?php do_action( 'bp_before_directory_blogs_list' ); ?>

	<!-- bpwpapers blogs list -->
	<ul id="blogs-list" class="item-list" role="main">

	<?php while ( bp_blogs() ) : bp_the_blog(); ?>

		<li class="clearfix">
			<div class="item-avatar">
				<a href="<?php bp_blog_permalink(); ?>"><?php bp_blog_avatar( 'type=full&width=150&height=150' ); ?></a>
			</div>

			<div class="item">
				<div class="item-title"><a href="<?php bp_blog_permalink(); ?>"><?php bp_blog_name(); ?></a></div>
				<div class="item-author"><span class="bpwpapers-author"><?php

				// get user
				$user_id = bpwpapers_get_author_for_blog( bp_get_blog_id() );

				?><a href="<?php echo bp_core_get_user_domain( $user_id ); ?>"><?php echo esc_html( bp_core_get_user_displayname( $user_id ) ); ?></a></span></div>
				<div class="item-meta"><span class="activity"><?php bp_blog_last_active(); ?></span></div>

				<?php do_action( 'bp_directory_blogs_item' ); ?>
			</div>

			<div class="action">

				<?php do_action( 'bp_directory_blogs_actions' ); ?>

				<div class="meta">

					<?php bp_blog_latest_post(); ?>

				</div>

			</div>

			<div class="clear"></div>
		</li>

	<?php endwhile; ?>

	</ul>

	<?php do_action( 'bp_after_directory_blogs_list' ); ?>

	<?php bp_blog_hidden_fields(); ?>

	<div id="pag-bottom" class="pagination">

		<div class="pag-count" id="blog-dir-count-bottom">

			<?php bpwpapers_blogs_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="blog-dir-pag-bottom">

			<?php bp_blogs_pagination_links(); ?>

		</div>

	</div>

	<?php

} else {

	?>

	<div id="message" class="info">
		<p><?php

		// show nothing found message
		echo sprintf(
			__( 'Sorry, there were no %s found.', 'bpwpapers' ),
			bpwpapers_extension_plural()
		);

		?></p>
	</div>

	<?php

}

do_action( 'bp_after_blogs_loop' );

?>