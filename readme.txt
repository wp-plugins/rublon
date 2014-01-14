=== Rublon ===
Contributors: Rublon
Tags: two-factor authentication, login, password, two-factor, authentication, security, login verification, two-step verification, 2-step verification, two-step authentication, 2-step authentication, 2-factor authentication, 2-factor, 2FA, wordpress security, mobile, mobile phone, cell phone, smartphone, login protection, qr code, admin, javascript, plugin, multi-factor authentication, MFA, login approval, two-factor verification
Requires at least: 3.3.x
Tested up to: 3.8
Stable tag: 1.2.6
License: GNU Public License, version 2 
License URI: http://opensource.org/licenses/gpl-license.php

Stronger protection for online accounts: Rublon protects your users' accounts from hackers, even if they steal their passwords.

== Description ==

Rublon provides stronger protection for all users of your WordPress website through automatic two-factor authentication. It protects their accounts from sign-ins from unknown devices, even if their passwords get stolen. Find out more at http://www.rublon.com.

Rublon for WordPress requires the Rublon mobile app, which lets you define your Trusted Devices. Available for Android, BlackBerry, iPhone and Windows Phone: www.rublon.com/get

+++

Your online accounts can be accessed from any device in the world, often without your knowledge. Rublon protects your personal data, as well as business and financial operations, by restricting access from unknown devices.

Use Rublon to manage and define the devices you trust, such as your laptop, tablet and mobile phone. Rublon protected accounts will only be accessible from your Trusted Devices. The Rublon mobile app allows you to add or remove Trusted Devices at any time, anywhere you are.

Rublon is an additional security layer that verifies whether you are signing in from a Trusted Device. It works on top of any existing authentication process. This means that you still have to type your username and password or use a social login button to sign in, but it must be done on a Trusted Device in order to access your account. And if you want to sign in using a new device, simply confirm your identity using Rublon, and then add it to your Trusted Devices.

You can protect your data by protecting your account with Rublon on every web service that uses our security system. If your web service does not, please contact your system administrator and ask him to integrate Rublon.

+++

= Supported languages =
- English
- German
- Polish
- Spanish - thanks to Andrew Kurtis from www.webhostinghub.com

== Installation ==

1. Install the Rublon mobile app onto your smartphone and use it to create a Rublon account: http://rublon.com/get
2. Sign in to your WordPress site using an administrator account.
3. Go to the "Plugins" -> "Add New" page, type "Rublon" in the search box and press the "Search Plugins" button.
4. Click the "Install Now" link next to the Rublon plugin in the search results and confirm the installation.
5. After a successful installation, click "Activate Plugin".
6. Now click "Settings" under the plugin name on the list or use the configuration link displayed on top of the "Plugins" page.
7. In order to activate Rublon for your WordPress site click the "Activate Rublon" button and scan the QR code that appears afterwards, using the Rublon mobile app on your smartphone. Your user account will be automatically protected by Rublon.

= Server requirements =
- PHP version 5.2.17 or greater
- cURL PHP extension enabled

== Frequently Asked Questions ==

= How to use Rublon? =

In order to use Rublon please go to "Your Profile" section and click "Protect your account".

= Is Rublon available for my smartphone? =

Currently Rublon is available for users of smartphones with the Android system (e.g. HTC, LG, Motorola, Samsung, Sony), the iPhone/iPod, smartphones with Windows Phone system (e.g. HTC, Nokia Lumia) and the BlackBerry. Go get it: http://rublon.com/get.

= How do I install Rublon? =

Go to http://rublon.com, click the big green button ("Free download") and follow the instructions.

= How much does it cost to use Rublon? =

The Rublon app for your smartphone is available free of charge. If you run a web service and want to provide Rublon to your users, please contact us at sales@rublon.com.

= Do I have to use my smartphone at each sign in? =

No! Your smartphone is only needed when you sign in from a new device. It is used to prove your identity and allows you to add such a device to your trusted devices. Any sign in from a trusted device works the same way as before: simply enter your login credentials. Rublon works as an invisible security layer during the sign in process.

== Screenshots ==

1. Plugins page with "Protect your account" button, which is only available to administrators until one of them protects their account with Rublon
2. Rublon page viewed by an administrator before protecting his account
3. Protecting your account requires you to confirm your identity by scanning a Rublon QR code with your smartphone, using the Rublon mobile app available for Android, BlackBerry, iPhone and Windows Phone.
4. A protected account features a Rublon icon visible at the top right corner of every page inside the administration panel, placed next to the current user's avatar.
5. Rublon page viewed by a user after an administrator has protected his account
6. Login page with Rublon Seal
7. On a protected account, Rublon verifies if the user who provided correct login credentials is logging in from a Trusted Device.

== Upgrade notice ==

After a successful installation, the plugin can be updated automatically in the "Plugins" section of the Administation Panel.

== Changelog ==

= 1.2.6 =
* Spanish language support added, thanks to Andrew Kurtis from www.webhostinghub.com

= 1.2.5 =
* Added compatibility for admin dashboards working over SSL (thanks to Robert Abela from www.wpwhitesecurity.com for reporting it)
* Internal Rublon libraries updated

= 1.2.4 =
* Visual components updated for compatibility with WordPress 3.8
* Internal Rublon libraries updated

= 1.2.3 =
* XML-RPC API disabled by default with the option to enable it back on the plugin's settings page
* Rublon internal libraries updated

= 1.2.2 =
* Rublon can now also serve as an additional factor for any other authentication method, e.g. social login through Facebook
* Core Rublon libraries updated

= 1.2.1 =
* German language support added
* Improved compatibility with a few unusual server configuration types
* Added compatibility with some maintenance mode plugins
* Added compatibility with the Better WP Security plugin 

= 1.2.0 =
* Simplified setup process — protect your account right away after activating the plugin
* An administrator needs to protect his account with Rublon before any other user will be able to do it
* Rublon Seal shows up on sign-in page
* Rublon now has an own section inside the main menu of the administration panel
* If your account is protected, the Rublon icon shows up at the top right corner of any page in the administration panel
* Users unfamiliar with Rublon are now being informed that they need the Rublon mobile app
* Visual improvements

= 1.1.9 =
* Rublon settings page moved from the "Settings" to "Plugins" section of the administration area
* Rublon icon added to the Rublon settings page
* Outdated versions of the plugin will not be able to activate the Rublon service anymore

= 1.1.8 =
* Warning message about improper PHP version on PHP versions between 5.2.17 and 5.3.2 no longer displayed
* Code updated to WordPress coding standards, thanks to Alex King from http://alexking.org

= 1.1.7 =
* Fixed error when setting CAcert path in cURL in PHP 5.2.17
* Confirmed compatibility with PHP version 5.2.17

= 1.1.6 =
* Improved error handling
* Minor text and translation changes

= 1.1.5 =
* Improved error handling

= 1.1.4 =
* User accounts protected by Rublon are marked with a Rublon icon on the user list in the Administration Panel
* The process of securing a user's account with Rublon has been technically improved
* Advanced error handling during plugin activation

= 1.1.3 =
* Minor text and translation changes

= 1.1.2 =
* Administrator's account is automatically protected by Rublon upon plugin activation
* Administrators can now disable other users' two-factor authentication

= 1.1.1 =
* Minor text and translation updates

= 1.1.0 =
* Error handling and data verification in the admin settings

= 1.0.2 =
* Rublon library update

= 1.0.1 =
* Updated texts and translations

= 1.0 =
* Rublon for WordPress: Automatic Two-Factor Authentication