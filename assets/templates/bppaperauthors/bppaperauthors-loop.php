<?php

/**
 * BuddyPress Working Papers - Working Paper Authors Loop
 * Querystring is set via AJAX in _inc/ajax.php - bp_dtheme_object_filter()
 */

//print_r( ( bp_is_bppaperauthors_component() ? 'yes' : 'no' ) ); die();

do_action( 'bp_before_members_loop' );

// search for them - TODO: add AJAX query string compatibility
if ( bp_has_members( bp_ajax_querystring( 'members' ) ) ) {

	?>

	<div id="pag-top" class="pagination">

		<div class="pag-count" id="member-dir-count-top">

			<?php bp_members_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="member-dir-pag-top">

			<?php bp_members_pagination_links(); ?>

		</div>

	</div>

	<?php do_action( 'bp_before_directory_members_list' ); ?>

	<ul id="members-list" class="item-list" role="main">

	<?php while ( bp_members() ) : bp_the_member(); ?>

		<li class="clearfix">
			<div class="item-avatar">
				<a href="<?php bp_member_permalink(); ?>"><?php bp_member_avatar( 'type=full&width=150&height=150' ); ?></a>
			</div>

			<div class="item">
				<div class="item-title">
					<a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>

					<?php if ( bp_get_member_latest_update() ) : ?>
						<span class="update"><?php bp_member_latest_update(); ?></span>
					<?php endif; ?>

				</div>

				<div class="item-meta"><span class="activity"><?php bp_member_last_active(); ?></span></div>

				<?php do_action( 'bp_directory_members_item' ); ?>

				<?php
				 /***
				  * If you want to show specific profile fields here you can,
				  * but it'll add an extra query for each member in the loop
				  * (only one regardless of the number of fields you show):
				  *
				  * bp_member_profile_data( 'field=the field name' );
				  */
				?>
				
				<?php
				
				// get affiliation data
				$data = bp_get_member_profile_data( array( 'field' => 'Affiliation' ) );
				
				// if we get some
				if ( ! empty( $data ) ) { 
				
					?><div class="user-meta">
						<span class="affiliation-label"><?php _e( 'Affiliation:', 'bpwpapers' ); ?></span> 
						<span class="affiliation"><?php echo $data; ?></span>
					</div><?php 
				
				}
				
				?>
				
				<?php
				
				// get interests data
				$data = bp_get_member_profile_data( array( 'field' => 'Interests' ) );
				
				// if we get some
				if ( ! empty( $data ) ) { 
				
					?><div class="user-meta">
						<span class="interests-label"><?php _e( 'Interests:', 'bpwpapers' ); ?></span> 
						<span class="interests"><?php echo $data; ?></span>
					</div><?php 
				
				}
				
				?>
				
			</div>

			<div class="action">

				<?php do_action( 'bp_directory_members_actions' ); ?>

			</div>

			<div class="clear"></div>
		</li>

	<?php endwhile; ?>

	</ul>

	<?php do_action( 'bp_after_directory_members_list' ); ?>

	<?php bp_member_hidden_fields(); ?>

	<div id="pag-bottom" class="pagination">

		<div class="pag-count" id="member-dir-count-bottom">

			<?php bp_members_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="member-dir-pag-bottom">

			<?php bp_members_pagination_links(); ?>

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
			apply_filters( 'bppaperauthors_extension_plural', __( 'Working Paper Authors', 'bpwpapers' ) )
		);
		
		?></p>
	</div>
	
	<?php 
	
}

do_action( 'bp_after_members_loop' ); 

?>