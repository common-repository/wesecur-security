=== WeSecur Security - Antivirus, Malware Scanner and Protection for your WordPress ===
Contributors: wesecur
Tags: malware, Antivirus, anti-virus, waf, seguridad, security, firewall, protection, integridad de ficheros, blacklist, hardening
Requires at least: 3.6
Requires PHP: 5.5
Tested up to: 5.4.3
Stable tag: 1.2.1
Donate Link: https://www.wesecur.com/
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

The WeSecur plugin audits and protects your WordPress by daily analyzing malware, blacklists and vulnerabilities that can be exploited to infect your website and blocking attacks and spambots with tools specially designed to protect your WordPress.

== Installation ==


== Description ==

The WESECUR plugin protects your website and makes security maintenance much easier. The plugin includes a firewall and a malware scanner both specifically designed to secure and protect your WordPress.
WESECUR wants to protect and secure your WordPress in the most comprehensive and simple way possible.

== Features ==

<ul>
<li>File integrity checker.</li>
<li>Bruteforce login protection.</li>
<li>Blacklist monitoring.</li>
<li>External malware scanner.</li>
<li>XML-RPC bruteforce protection.</li>
<li>Hardening configurations.</li>
<li>Premium - Server side malware scanner.</li>
<li>Premium - Automatic malware removal</li>
<li>Premium - Vulnerabilities scanner.</li>
<li>Premium - Smart alerts.</li>
</ul>

== Frequently Asked Questions ==

More information can be found in our [Website](https://www.wesecur.com/wordpress-security-plugin/).

= How does the WESECUR plugin protect my website? =

The plugin firewall prevents malicious traffic by blocking attackers before they access to your website. It also blocks common security threats such as fake Googlebot, malicious scanners from hackers and botnets.

= What analysis does the WESECUR malware scanner? =

Scanner of the core files, themes and plugins comparing them with those of the official WordPres.org repository to verify its integrity.

With the premium version we provide you with automatic malware cleaning.

= What sets WESECUR apart from other WordPress plugins? =

The WESECUR plugin is optimized to not affect the performance of your website, and all malware analysis occurs on our servers.

== Screenshots ==

1. Dashboard - Shows website security status and audit Logs.
2. Antivirus - Reports Shows differences in the core WordPress files.
3. Firewall (WAF) - Shows Banned Ip and number of stopped attacks
4. Settings - Offers hardening settings and other functionality of the plugin

== Upgrade Notice ==

= 1.0.1 =
Read the [release announcement post](https://www.wesecur.com/2019/01/04/new-release-wordpress-plugin-wesecur-security-1-0-1/) before upgrading.

= 1.0.0 =
This is the first version of the plugin.

== Changelog ==

For more information, see [Releases](https://www.wesecur.com/category/wordpress-3/plugin-releases/).

= 1.2.1 - 2020-07-10 =
* Fix - Fix a mispelled variable

= 1.2.0 - 2020-07-10 =
* Test - Compatibility with WordPress 5.4.3
* Improve - Update vendor libraries (GuzzleHttp, Smarty and Maxmind DB).
* Improve - Add index.php file in uploads folder to prevent directory listing.
* Improve - Add event "New user account created" in Audit log section.
* Feature - New hardening setting to protect PHP execution in sensitive folders.

= 1.1.0 - 2020-02-21 =
* Test - Compatibility with WordPress 5.3.2
* Improve - Update vendor libraries (GuzzleHttp, Smarty and Maxmind DB).
* Improve - Show country flag next to the IP in Event's table.
* Feature - New hardening setting to protect from XML-RPC attacks.
* Feature - New section to allow FTP configuration for premium users.
* Fix - PHP Notice: "Trying to get property 'value' of non-object"
* Fix - PHP Notice: "Array to string conversion"

= 1.0.6 - 2019-07-13 =
* Fix - Json decode error fixed in specific situation when user failed to login.
* Fix - Database files purge was not working.
* Fix - PHP Notice: "Undefined index: user_passwd"

= 1.0.5 - 2019-05-17 =
* Test - Compatibility with WordPress 5.2
* Feature - New hardening setting to hide WordPress version.
* Feature - Added hidden field in login form to identify and block bad bots.
* Fix - Added missing method to delete integrity files.

= 1.0.4 - 2019-03-13 =
* Feature - Reduced default value of max login attempts before blocking the user.
* Fix - Added missing files on last plugin commit

= 1.0.3 - 2019-03-13 =
* Test - Compatibility with WordPress 5.1.1
* Feature - Show a message when setup is incomplete while using WeSecur Security with an api key.
* Feature - Plugin performance improved by reducing rest API calls.
* Fix - Catch timeout exceptions in requests to the Wesecur API endpoints.

= 1.0.2 - 2019-02-02 =
* Feature - Show a message in the events dashboard when a bad IP is blocked by the firewall.
* Fix - Integrity check ignored files were not working as expected.
* Fix - Integrity check algorithm now makes case sensitive comparison to avoid false positives.

= 1.0.1 - 2019-01-04 =
* Fix - Integrity check false positives removed.
* Fix - Prevent duplicating crons when plugin is disabled and enabled multiple times.

= 1.0.0 - 2018-12-05 =
* First stable release.
