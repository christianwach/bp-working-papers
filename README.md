BuddyPress Working Papers
=========================

The *BuddyPress Working Papers* plugin creates a relationship between *BuddyPress* Groups and *WordPress* Sites for the purpose of peer reviewing Working Papers. The plugin is specifically designed to work with [*CommentPress*](https://wordpress.org/plugins/commentpress-core/) "documents" (which are themselves complete sub-sites) so that there can be effective peer review.

**Please note:** This plugin is for use in *WordPress* multisite installs only.

### Compatibility ###

* The supplied templates work best with [*Commons in a Box*](https://wordpress.org/plugins/commons-in-a-box/) and may need tweaking for other themes.
* The plugin is designed and tested for use with [*CommentPress*](https://wordpress.org/plugins/commentpress-core/), but may work with other themes.
* The plugin is compatible with and enhanced by [*BuddyPress Follow*](https://wordpress.org/plugins/buddypress-followers/).


## Installation ##

### GitHub ###

There are two ways to install from GitHub:

#### ZIP Download ####

If you have downloaded *BuddyPress Working Papers* as a ZIP file from the GitHub repository, do the following to install and activate the plugin and theme:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/bp-working-papers`
2. Network activate the plugin
3. You are done!

#### git clone ####

If you have cloned the code from GitHub, it is assumed that you know what you're doing.


## Initial Setup ##

After you've activated the plugin, you will need to visit the *BuddyPress* settings page and assign two new pages which will be the Papers Directory and the Authors Directory. The process is the same as when you created other *BuddyPress* directory pages.

You should then visit the *BuddyPress Working Papers* settings page where you can rename the component elements to suit your use case.

*BuddyPress Working Papers* provides a number of widgets that you can use, along with basic styling that you may wish to override:

* A *Featured Author Widget* that shows details about a Working Paper author
* A *Featured Reviewer Widget* that shows details about a Working Paper commenter
* A *Featured Paper Widget* that shows details about a particular Working Paper
* A *Recent Papers Widget* that shows a list of new Working Papers
* An *Activity Widget* that shows recent comments in Working Papers

The plugin also defines a number of widget areas that you can use to create, for example, an "Overview" page or a custom sidebar. Include the following in your template and style appropriately:

* `dynamic_sidebar( 'working-papers-top' )`
* `dynamic_sidebar( 'working-papers-middle-left' )`
* `dynamic_sidebar( 'working-papers-middle-right' )`
* `dynamic_sidebar( 'working-papers-lower' )`
* `dynamic_sidebar( 'working-papers-bottom-left' )`
* `dynamic_sidebar( 'working-papers-bottom-right' )`
* `dynamic_sidebar( 'working-papers-sidebar' )`

## Usage ##

To create a *Working Paper*, visit your *Working Papers Directory*, where you will see a "Create a Paper" button. This takes you to a page where you can title your paper and give it a URL. This is very similar to the standard *WordPress* multisite "New Site" page. When you're ready, click "Create Paper" and a specially-configured *CommentPress*-enabled sub-site will be created for you, containing its own *BuddyPress* group.


Start writing and discussing!
