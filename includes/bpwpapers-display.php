<?php /*
================================================================================
BuddyPress Working Papers Display Functions
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

Throw any functions which build markup in here.

--------------------------------------------------------------------------------
*/



/** 
 * Adds icon to menu in CBOX theme
 * 
 * @return void
 */
function bpwpapers_cbox_theme_compatibility() {
	
	// is CBOX theme active?
	if ( function_exists( 'cbox_theme_register_widgets' ) ) {
	
		// get group slug
		$group_slug = apply_filters( 'bpwpapers_extension_slug', 'working-paper' );
		
		// init member li
		$member_li = '';
		
		// if the component is active
		if ( isset( buddypress()->bpwpapers->slug ) ) {

			// get papers component slug
			$member_li = ",\n\t\t#user-" . buddypress()->bpwpapers->slug . ':before'."\n";
			
		}
		
		// output style in head
		?>
		
		<style type="text/css">
		/* <![CDATA[ */
		#nav-<?php echo $group_slug; ?>:before<?php echo $member_li; ?>
		{
			content: "C";
		}
		/* ]]> */
		</style>
		
		<?php
		
	}

}

// add action for the above
add_action( 'wp_head', 'bpwpapers_cbox_theme_compatibility' );



