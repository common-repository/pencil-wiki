=== Pencil Wiki ===
Contributors: grosbouff
Tags: wiki, documentation
Requires at least: 3.0
Tested up to: 3.5
Stable tag: trunk
License: GPLv2 or later
Donate link:http://bit.ly/gbreant

Pencil Wiki is a simple wiki solution for your Wordpress.

== Description ==

Pencil Wiki is a simple wiki solution for your Wordpress.

Basically, it adds new roles & capabilities that allow your users to add/edit wiki pages (custom post type).  
  
It comes with several widgets and custom templates (that you can override) fully integrated with the *Twenty Twelve* theme.

Pages are edited backend, but there is a menu on every wiki page with the necessary links; and a wiki index / search input.

Only Administrators and Editors can create new wikis (top-level pages); and Wiki Authors can add pages to it.
Administrators and Editors can also LOCK some wiki pages (for any reason) or branches; which means they will not be editable anymore by other users.

I voluntarily made the plugin quite simple to avoid making it intrusive (javascript, css, ...) and put too many features. I recommend to run it with those plugins :
* [Members](http://wordpress.org/extend/plugins/member)Members</a> by Justin Tadlock, to manage roles and capabilities
* [Breadcrumb NavXT](http://mtekk.us/code/breadcrumb-navxt) by John Havlik, to generate breadcrumbs
* [Simple Footnotes](http://wordpress.org/extend/plugins/simple-footnotes) by Andrew Nacin, to put footnotes in your wiki pages.
  
Wordpress has a revision system, which is perfect for a wiki. 
Unfortunately,  I didn't find a way to customize it (add revision messages, etc).

== Installation ==

1.  Extract the zip file and just drop the contents in the *wp-content/plugins/* directory of your WordPress installation and then activate the Plugin from Plugins page.
2.  Copy the content from the plugin's */theme* directory to your current theme directory.
3.  In the Administration Panel > Settings > General, change the default role from *Subscriber* to *Wiki Author*; or give the required capabilities to your users.

== Changelog ==

= 1.0.7 =
* Localization
= 1.0.6 =
* Some bug fixes
= 1.0.5 =
* Template bug fix
* Added a way to add a reason for Revisions and to list them in the Revisions meta box !

= 1.0.1 =
* Added custom walker (Pencil_Wiki_Walker_Page) for the wiki menu, allowing customisation of the selected item and its ancestors.
* Added a filter on "the_content" to add a message if a page is empty (so it's no more a problem to have an empty page & subpages under it)
* New function pwiki_has_children() to check if a wiki page has children.
* Added Tree Widget to display a list of Wiki Pages

= 1.0.0 =
* First release

== Upgrade Notice ==

== Screenshots ==

== Frequently Asked Questions ==

**How can I customize the templates ?**
Copy the templates from pencil-wiki/_inc/theme-default to your current theme (override the existing ones if any) and edit them.

**What are the capabilities used in this plugin ?**

*   edit_root_wiki_pages - allows to create top level wiki pages
*   lock_wiki_pages - allows to lock a wiki page

*   read_private_wiki_pages      
*   edit_private_wiki_pages
*   delete_published_wiki_pages
*   delete_others_wiki_pages
*   delete_private_wiki_pages
*   edit_wiki_pages
*   edit_others_wiki_pages
*   edit_published_wiki_pages
*   delete_wiki_pages
*   publish_wiki_pages

**How can I change the users capabilities ?**
With a plugin made for that ! I suggest you the <a href=\"http://wordpress.org/extend/plugins/members/\">Members</a> by Justin Tadlock.