<?php

/**
 * BuddyPress Working Papers - Create Working Paper
 */

get_header( 'buddypress' ); 

?>

	<!-- bpwpapers/create.php -->
	<?php do_action( 'bp_before_directory_blogs_content' ); ?>
	
	
	
	<div id="content" role="main" class="<?php do_action( 'content_class' ); ?>">
		<div class="padder" role="main">
		
		<?php do_action( 'bp_before_create_blog_content_template' ); ?>

		<div class="dir-form">

		<?php do_action( 'template_notices' ); ?>

			<h3><?php 
			
			// show title
			echo sprintf( 
				__( 'Create a %s', 'bpwpapers' ), 
				apply_filters( 'bpwpapers_extension_name', __( 'Working Paper', 'bpwpapers' ) )
			);

			?> &nbsp;<a class="button" href="<?php echo trailingslashit( bp_get_root_domain() . '/' . bpwpapers_get_root_slug() ); ?>"><?php 
			
			// show title
			echo sprintf( 
				__( '%s Directory', 'bpwpapers' ), 
				apply_filters( 'bpwpapers_extension_plural', __( 'Working Papers', 'bpwpapers' ) )
			);

			?></a></h3>
			
		</div>

		<?php do_action( 'bp_before_create_blog_content' ); ?>

		<?php bpwpapers_show_working_paper_create_form(); ?>

		<?php do_action( 'bp_after_create_blog_content' ); ?>
		
		<?php do_action( 'bp_after_create_blog_content_template' ); ?>

		</div><!-- .padder -->
	</div><!-- #content -->
	
	
	
	<?php do_action( 'bp_after_directory_blogs_content' ); ?>



<?php get_sidebar( 'working-papers' ); ?>
<?php get_footer( 'buddypress' ); ?>