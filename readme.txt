=== Plugin Name ===
Contributors: paulfp
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=Q2Z9WJ7WKFS6W
Tags: phone number, international phone number, telephone, telephone numbers, telecoms, telecoms cloud, api, ipinfo, ipinfoio, ip address, international dialing prefix
Requires at least: 3.0.1
Tested up to: 4.2
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily and automatically display phone number in the correct international format depending on your visitors' location.

== Description ==

The plugin will automatically display telephone numbers in the correct format for international users from any country in the world. It does this by looking up their IP address (using ipinfo.io) and determining the country, then passing this information along with the phone number to the Telecoms Cloud API which formats the number correctly and adds the appropriate International Direct Dialing Number (IDD).

Example: You may have a London phone number - 02079460981 - which would be displayed like so for visitors from the following countries:

* UK: 020 7946 0981 (no prefix added - just spaces added to make number readable)
* US: 011 44 20 7946 0981
* Spain: 00 44 20 7946 0981

Usage: wherever you want a telephone number to be formatted automatically within your website, use the shortcode like so:

[intPnd servicenumber="02079460981" location="GB"]

Note: you must pass the 2-digit country code in which the telephone number is located. For a list, see http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `intl-phone-number.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Trigger the plugin to format numbers like this: [intPnd servicenumber="02079460981" location="GB"]

== Frequently Asked Questions ==

= What external services does this plugin use to achieve this marvellous feat? =

The plugin turns your user's IP address into a 2-digit country code using ipinfo.io and then it passes those details along with the number to the Telecoms Cloud API. Both are free to use for usage below certain limits.

= Do I need to register? =

You don't for ipinfo.io but you'll need to go to www.telecomscloud.com and get some (free) access keys and input them on the plugin options page.

= What if I hit the limits or one of the APIs times out? =

The plugin will just output your number unchanged.

== Changelog ==

= 1.0.1 =
* Minor readme fixes.

= 1.0 =
* Initial stable release.

== Upgrade Notice ==
No upgrade exists.

== Screenshots ==
 No screenshot exists.
