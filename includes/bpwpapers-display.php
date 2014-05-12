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
 * @description: adds icon to menu in CBOX theme
 */
function bpwpapers_cbox_theme_compatibility() {
	
	// is CBOX theme active?
	if ( function_exists( 'cbox_theme_register_widgets' ) ) {

		// output style in head
		?>
		
		<style type="text/css">
		/* <![CDATA[ */
		#nav-<?php echo apply_filters( 'bpwpapers_extension_slug', 'working-paper' ) ?>:before 
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



