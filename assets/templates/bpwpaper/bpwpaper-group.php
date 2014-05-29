<?php 
/*
Template Name: Group Home
*/

$group_id = bpwpapers_get_group_by_blog_id( get_current_blog_id() );
//print_r($group_id); die();

// get the group
$params = array(
	'include' => array( $group_id )
);

get_header( 'buddypress' );

?>

<!-- bpwpaper/bpwpaper-group.php -->

<div id="wrapper">



<div id="main_wrapper" class="clearfix">



<div id="page_wrapper">



	<div id="content">
		<div class="padder">

			<?php if ( bpwpapers_has_groups( $params ) ) : while ( bp_groups() ) : bp_the_group(); ?>

			<?php do_action( 'bp_before_group_home_content' ) ?>

			<div id="item-header" role="complementary">

				<?php bpwpapers_locate_template( array( 'bpwpaper/single/bpwpaper-group-header.php' ), true ); ?>

			</div><!-- #item-header -->

			<div id="item-nav">
				<div class="item-list-tabs no-ajax" id="object-nav" role="navigation">
					<ul>

						<?php bp_get_options_nav(); ?>

						<?php do_action( 'bp_group_options_nav' ); ?>

					</ul>
				</div>
			</div><!-- #item-nav -->

			<div id="item-body">

				<?php do_action( 'bp_before_group_body' );
				
				/*
				if ( bp_is_group_admin_page() && bp_group_is_visible() ) :
					locate_template( array( 'groups/single/admin.php' ), true );

				elseif ( bp_is_group_members() && bp_group_is_visible() ) :
					locate_template( array( 'groups/single/members.php' ), true );

				elseif ( bp_is_group_invites() && bp_group_is_visible() ) :
					locate_template( array( 'groups/single/send-invites.php' ), true );

				elseif ( bp_is_group_forum() && bp_group_is_visible() && bp_is_active( 'forums' ) && bp_forums_is_installed_correctly() ) :
					locate_template( array( 'groups/single/forum.php' ), true );

				elseif ( bp_is_group_membership_request() ) :
					locate_template( array( 'groups/single/request-membership.php' ), true );

				elseif ( bp_group_is_visible() && bp_is_active( 'activity' ) ) :
					locate_template( array( 'groups/single/activity.php' ), true );

				elseif ( bp_group_is_visible() ) :
					locate_template( array( 'groups/single/members.php' ), true );

				elseif ( !bp_group_is_visible() ) :
					// The group is not visible, show the status message

					do_action( 'bp_before_group_status_message' ); ?>

					<div id="message" class="info">
						<p><?php bp_group_status_message(); ?></p>
					</div>

					<?php do_action( 'bp_after_group_status_message' );

				else :
				*/
				
					// If nothing sticks, just load a group front template if one exists.
					bpwpapers_locate_template( array( 'bpwpaper/single/bpwpaper-group-front.php' ), true );

				//endif;

				do_action( 'bp_after_group_body' ); ?>

			</div><!-- #item-body -->

			<?php do_action( 'bp_after_group_home_content' ); ?>

			<?php endwhile; endif; ?>

		</div><!-- .padder -->
	</div><!-- #content -->

</div><!-- /page_wrapper -->



</div><!-- /main_wrapper -->



</div><!-- /wrapper -->



<?php get_sidebar( 'working-papers' ); ?>
<?php get_footer( 'buddypress' ); ?>