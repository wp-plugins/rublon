=== Rublon ===
Contributors: Rublon
Tags: two-factor authentication, login, password, two-factor, authentication, security, login verification, two-step verification, 2-step verification, two-step authentication, 2-step authentication, 2-factor authentication, 2-factor, 2FA, wordpress security, mobile, mobile phone, cell phone, smartphone, login protection, qr code, admin, javascript, plugin, multi-factor authentication, MFA, login approval, two-factor verification, Zwei-Faktor-Authentifizierung, dwuskładnikowe uwierzytelnianie, dwuskładnikowe logowanie, logowanie, uwierzytelnianie
Requires at least: 3.3.x
Tested up to: 4.0.1
Stable tag: 2.1.7
License: GNU Public License, version 2 
License URI: http://opensource.org/licenses/gpl-license.php

Instantly protects all accounts with effortless, email-based two-factor authentication; optional mobile app for more security; no tokens.
 
== Description ==

= Account Protection for WordPress =
- Instantly increase security for all users
- 1-click download; 1-click activation
- No configuration or training needed

= Why Do I Need Account Protection? =
Botnets carry out brute force attacks against thousands of WordPress sites and blogs every day, regardless of size. Once inside, botnets infect your visitors with malware. A compromised website leads to delisting by search engines or blocking by your hosting provider. Account protection prevents such attacks.

= Why are Passwords Not Enough? =
Many people use a simple, easy-to-guess password. It can be easily stolen when they use multiple devices; the same password across multiple services; or on unsecured connections, such as public Wi-Fi networks. Botnets hammer at your WordPress site trying to compromise it using millions of common passwords and character combinations.

= How Does Rublon Account Protection Work? =
During the first login, confirm your identity by clicking on the link you’ll receive via email. Your next login from the same device will only require your WordPress password. For additional security, the Rublon mobile app scans a Rublon Code to confirm your identity.

= Why Should I Use Rublon? =
Rublon is simple and easy. Activate the plugin and you're done. Your users don't need to install or configure anything and don’t need training or one-time codes. Once they confirm their identity on a device, they can log in to all web services by only entering their WordPress password.

= How is Rublon Different? =
Traditional two-factor authentication solutions demand users enter a one-time password each time they want to login. That’s why people don’t like them. Rublon is different. With Rublon, you confirm your identity by simply clicking on a link or scanning a Rublon Code.

= What Does Rublon Cost? =
Nothing! The basic Rublon plugin is free and always will be. Premium features like custom branding are available through our paid Business Plan. Please contact sales@rublon.com.

= In What Languages Is Rublon Available? =
- English
- German
- Polish

---

> #### Follow Us
> [Facebook](https://www.facebook.com/RublonApp) | [Google+](https://plus.google.com/+Rublon) | [LinkedIn](https://www.linkedin.com/company/2772205) | [Twitter](https://twitter.com/rublon) | [YouTube](https://www.youtube.com/channel/UCI6QmxMvUThCg8vhli5DWxg)

---

== Installation ==

1. Log in to your WordPress administration panel using an administrator account.
2. Go to "Plugins" -> "Add New" and search for "Rublon" using the plugins search box.
3. Click the "Install Now" button inside the Rublon plugin box in the search results and confirm the installation.
4. Click on "Activate Plugin".
5. During your next login, confirm your identity via an email link Rublon sends you.
6. Optional: For more security and control, install the Rublon mobile app onto your phone (available for [Android](https://play.google.com/store/apps/details?id=com.rublon.android), [iOS](https://itunes.apple.com/us/app/rublon/id501336019) and [Windows Phone](http://www.windowsphone.com/en-us/store/app/rublon/809d960f-a3e8-412d-bc63-6cf7f2167d42)).

= Server requirements =
- PHP version 5.2.17 or greater
- cURL PHP extension enabled

== Frequently Asked Questions ==

= How can I protect my account with Rublon? =
Simply install the Rublon for WordPress plugin and activate it. After activation, all accounts have instant protection with email-based, two-factor authentication.

= I want more than email-based, two-factor authentication. Does Rublon support phone-based, out-of-band two-factor authentication? =
Yes! Just install the Rublon mobile app onto your phone (available for Android, iOS and Windows Phone). After entering your WordPress login credentials, you will be prompted to scan a Rublon Code with your phone.

= Do all my users have to be protected by Rublon? =
Plugin activation instantly protects all accounts. The minimum (default) protection level for all user groups is set to "Email." You may change this setting to "None" for any user group at any time. However, users who install the Rublon mobile app will have additional protection regardless of the setting you’ve selected.

= Will my login credentials be known to Rublon? =
No. Rublon never knows your credentials or those of your users.  They are never transmitted to our servers. Rublon does its work in the background only after WordPress verifies your password. It's an independent security layer that sits beneath the login form.

= How secure is my data on Rublon's servers? =
For accounts protected via email, the email address is transmitted to Rublon servers during each login but instantly removed after Rublon sends the email with your personal confirmation link. No personal data of such accounts is ever stored on Rublon servers. For accounts protected via mobile app, only the Rublon User ID is transmitted to Rublon servers during login. Registered users of the Rublon mobile app are governed by its terms of service.

= Why is using the Rublon mobile app more secure than email-based authentication? =
The Rublon mobile app holds your digital identity with your private encryption key, which never leaves your phone. With any action requiring the mobile app, such as confirming your identity, the Rublon app generates an unique encrypted digital signature. Gaining access to an email account without two-factor authentication is easier than stealing your private key from your phone and reusing it.

= What if I use the Rublon mobile app and I lose my phone? =
Simply deactivate your phone using the "Lost phone" feature on the Help Page at Rublon.com. Once deactivated, recover your account by installing the Rublon mobile app on a new phone.

= How much does Rublon cost? =
Rublon for WordPress is free and always will be. Our paid Business Plan offers premium features.
 
== Screenshots ==

1. Plugins page with "Protect your account" button, which is becomes available to all users after an administrator protects his account
2. Rublon page viewed by an administrator before protecting his account
3. Protecting your account requires you to confirm your identity by scanning a Rublon QR code with your smartphone, using the Rublon mobile app
4. A protected account features a Rublon icon visible at the top right corner of every page inside the administration panel, placed next to the current user's avatar
5. Rublon page viewed by a user after an administrator has protected his account
6. Login page with Rublon Protection Badge
7. On a protected account, Rublon verifies if the user who provided correct login credentials is logging in from a Trusted Device
 
== Upgrade notice ==

After a successful installation, the plugin can be updated automatically in the "Plugins" section of the Administation Panel.
 
== Changelog ==

= 2.1.8 =
* Possibility to disable Real-Time Remote Logout, which caused some Firefox users to experience slower page load times

= 2.1.7 =
* Additional security for the first authentication factor
* Improved database garbage collecting
* Minor text improvements

= 2.1.6 =
* Improved compatibility with some third-party plugins
* Improved positioning of Rublon Badge
* Minor text improvements

= 2.1.5 =
* Improved compatibility with some non-standard PHP server configurations

= 2.1.4 =
* Logout remotely from WordPress by removing a trusted device
* Improved memory management
* Improved handling of WordPress AJAX requests
* Improved positioning of Rublon Badge
* Minor text improvements

= 2.1.3 =
* Minor text improvements 

= 2.1.2 =
* Added compatibility with some cloud-based solutions
* Additional handling for connection problems

= 2.1.1 =
* Trusted Device Manager accessible from Dashboard and Rublon submenu
* Improved compatibility with other plugins
* Prevention of use on unsupported PHP versions
* Minor text improvements

= 2.1.0 =
* Multisite support added
* Default Protection Level downgrade now needs to be confirmed via Rublon
* Trusted Devices can be managed through the WordPress Administration Dashboard
* Updated visuals for display on iOS Retina devices

= 2.0.1 =
* Improved compatibility with PHP version 5.2.17 and higher
* Minor text changes

= 2.0.0 =
* Email-based two-step login for all users turned on by default (no mobile app required)
* Changing your email address or password requires your confirmation (via email or mobile app)
* Trusted devices now manageable via Rublon settings page
* Administrators can enforce a default protection type (email or mobile app) for specific user roles
* Automated plugin configuration — just activate the plugin to turn on the protection  

= 1.3.0 =
* Minor text changes

= 1.2.9 =
* Added right-to-left text orientation support

= 1.2.8 =
* Additional information about missing PHP libraries

= 1.2.7 =
* Croatian and Serbian language support added, thanks to Borisa Djuraskovic from www.webhostinghub.com
 
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
* Simplified setup process - protect your account right away after activating the plugin
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

= 1.0.0 =
* Rublon for WordPress: Automatic Two-Factor Authentication