<?php /*
================================================================================
BP Working Papers Uninstaller
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====


--------------------------------------------------------------------------------
*/



// kick out if uninstall not called from WordPress
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit(); }



/**
 * Restore blog options for all Working Paper sites. Also delete the groups.
 *
 * This procedure leaves each site still present in the network, but resets its
 * relationship with BuddyPress
 *
 * @return void
 */
function bpwpapers_reset() {

	// get all blog IDs
	$blog_ids = bpwpapers_get_papers();

	// if we get some...
	if ( count( $blog_ids ) > 0 ) {

		// have at it
		foreach( $blog_ids AS $blog_id ) {

			// clean up as if blog was deleted
			bpwpapers_blog_deleted( $blog_id );

			// reset blog options
			bpwpapers_reset_blog_options( $blog_id );

		}

	}

}



// reset all working paper sites
bpwpapers_reset();

// delete options
delete_site_option( 'bpwpapers_options' );
delete_site_option( 'bpwpapers_installed' );


