=== Hosted JFT ===

Contributors: pjaudiomv, bmltenabled
Tags: jft, just for today, narcotics anonymous, na, hosted jft
Tested up to: 6.2.2
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Hosted JFT is a plugin that allows an NA Community to host their own translated version of the JFT. Add the [hosted_jft]
shortcode to your page or use the widget Hosted JFT to add to your sidebar or footer.

SHORTCODE
Basic: [hosted_jft]
Custom Field Name:  This is the name of the Custom Field used on your post to store the date in MM-DD format
Timezone: This should probably just be your local timezone but can be changed in a shortcode if needed [jft jft_timezone="Europe/Rome"].
A list of supported timezones can be found here <a href="https://www.php.net/manual/en/timezones.php">https://www.php.net/manual/en/timezones.php</a>

EXAMPLES

<a href="https://www.mvana.org/just-for-today/">https://www.mvana.org/just-for-today/</a>

As A Widget
<a href="http://crossroadsarea.org/events-activities/">http://crossroadsarea.org/events-activities/</a>

MORE INFORMATION

<a href="https://github.com/bmlt-enabled/fetch-jft" target="_blank">https://github.com/bmlt-enabled/fetch-jft</a>

== Installation ==

This section describes how to install the plugin and get it working.

1. Download and install the plugin from WordPress dashboard. You can also upload the entire Hosted JFT Plugin folder to the /wp-content/plugins/ directory
2. Activate the plugin through the Plugins menu in WordPress
3. Add [hosted_jft] shortcode to your Wordpress page/post or add widget Hosted JFT to your sidebar, Footer etc.

Hosted JFT is a plugin that allows an NA Community to host their own translated version of the JFT. This is accomplished
by creating a post for each days jft and adding a Custom Field that includes the date in MM-DD (02-04 would be Feb 3rd)
format. I would suggest creating a JFT category for the posts and assign it to them as well but this is not needed.
The plugin requires two required settings, the Custom Field Name and Time Zone.

If using the widget these two additional settings should be set, jft_page_url and jft_more_text.

== Screenshots ==

1. screenshot-1.png

== Changelog ==

= 1.0.3 =

* Fixed some more php warnings.

= 1.0.2 =

* Fixed a couple php warnings.

= 1.0.1 =

* Version bump

= 1.0.0 =

* Initial Release
