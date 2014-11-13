<!-- bpwpaper/single/bpwpaper-group-header.php -->

<?php do_action( 'bp_before_group_header' ); ?>

<div id="group-header-content">

	<h2><?php

	// show title
	echo sprintf(
		__( 'Activity in this %s', 'bpwpapers' ),
		apply_filters( 'bpwpapers_extension_name', __( 'Working Paper', 'bpwpapers' ) )
	);

	?></h2>
	<span class="activity"><?php printf( __( 'active %s', 'bpwpapers' ), bp_get_group_last_active() ); ?></span>

	<?php do_action( 'bp_before_group_header_meta' ); ?>

	<div id="item-meta">

		<div id="item-buttons">

			<?php //do_action( 'bp_group_header_actions' ); ?>

		</div><!-- #item-buttons -->

		<?php do_action( 'bp_group_header_meta' ); ?>

	</div>

</div><!-- #group-header-content -->

<?php

do_action( 'bp_after_group_header' );
do_action( 'template_notices' );

?>