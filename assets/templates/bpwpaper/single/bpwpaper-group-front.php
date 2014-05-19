<!-- bpwpaper/single/bpwpaper-group-front.php -->

<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
	<ul>
		<li class="feed"><a href="<?php bp_group_activity_feed_link() ?>" title="<?php _e( 'RSS Feed', 'bpwpapers' ); ?>"><?php _e( 'RSS', 'bpwpapers' ) ?></a></li>

		<?php do_action( 'bp_group_activity_syndication_options' ) ?>

		<li id="activity-filter-select" class="last">
			<label for="activity-filter-by"><?php _e( 'Show:', 'bpwpapers' ); ?></label> 
			<select id="activity-filter-by">
				<option value="-1"><?php _e( 'Everything', 'bpwpapers' ) ?></option>
				<option value="joined_group"><?php _e( 'Group Memberships', 'bpwpapers' ) ?></option>
				<?php do_action( 'bp_group_activity_filter_options' ) ?>
			</select>
		</li>
		
	</ul>
</div><!-- .item-list-tabs -->

<?php do_action( 'bp_before_group_activity_content' ) ?>

<div class="activity single-group" role="main">
	<?php bpwpapers_locate_template( array( 'bpwpaper/single/bpwpaper-activity-loop.php' ), true ) ?>
</div><!-- .activity.single-group -->

<?php do_action( 'bp_after_group_activity_content' ) ?>