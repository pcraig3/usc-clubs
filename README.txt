=== USC Clubs ===
Contributors: pcraig3
Tags: clubs list, beta
Requires at least: 3.8
Tested up to: 3.9
Stable tag: 2.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin beams in some info from GitHub.

== Description ==

This plugin beams in some info from GitHub.

It's kind of a proof of concept: use wordpress shortcodes to pull in data stored on github.
This would give you a backend api that anyone could use.

== Frequently Asked Questions ==

= Why doesn't it work yet? =

Shut up.

= Does it make any sense to do this? =

It took me a long time.  So 'yes.'


== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 2.1.1 =
* Added JQuery Accordion to Filters on mobile version.

= 2.1.0 =
* Another biggish update.  Mostly I forgot to update the version.
* * Single template basically finished.
* * Single clubs now called by going to /clubs/clubs-list/{id}
* * API result fir single clubs cached, not saved asynchronously though.
* * Added a widget area for single clubs
* * Updated club Urls so they look better in the browser
* * OH YEAH AND FIXED THE OBJECT CACHE ISSUE WHICH WAS BREAKING MY PLUGINS

= 2.0.0 =
* Changed quite a bit structurally.  Ahem.
* * Single template renamed and re-structured so that it might be called be WordPress itself (more easily).
* * Query var now 'usc_clubs' rather than clubsapi
* * Shortcode now 'usc_clubs' rather than 'testplugin'
* * No longer calling clubs api from template.
* * WordPress default 'main' query totally cancelled on single club pages.
* * Single clubs now called by going to /clubs/clubs-list/{id}
* * WP_AJAX file created (in its own namespace) to do async stuff, get all API results, and also manage transients.
* * API result cached, and automatically saved asyncronously if not found in cache. (Read: this is super cool.)
* * Due to some limitations in WesternLink's architecture, we're having JavaScript build our filter checkboxes.
* * Clubs list page also built first for non-JS people and then (if JS) rebuilt with filterJS search tools.
* * "All" checkbox included again.
*
* I guess that's it.

= 1.4.0 =
* Renaming everything.  Hopefully it all still works.
* Making it not work comes later.

= 1.3.0 =
* Styled my dynamic pages, added the flag object, fontawesome icons, and linked everything together.

= 1.2.0 =
* Figured out how to create custom, dynamically-generated pages.

= 1.1.1 =
* commenting.

= 1.1.0 =
* Plugin connects to API which pulls information from github.

= 1.0.1 =
* Using shortcodes instead of string substitution.

= 1.0.0 =
* Got basic word substitution working.

= 0.9 =
* Totally new.  Renaming everything.

== Upgrade Notice ==

= 0.9 =
Please don't use this version for anything.

== Where it's due ==

The basic structure of this plugin was cloned from the [WordPress-Plugin-Boilerplate](https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate) project.