=== Rublon ===
Contributors: Rublon
Tags: two-factor authentication, login, password, two-factor, authentication, security, login verification, two-step verification, 2-step verification, two-step authentication, 2-step authentication, 2-factor authentication, 2-factor, 2FA, wordpress security, mobile, mobile phone, cell phone, smartphone, login protection, qr code, admin, javascript, plugin, multi-factor authentication, MFA, login approval, two-factor verification, Zwei-Faktor-Authentifizierung, dwuskładnikowe uwierzytelnianie, dwuskładnikowe logowanie, logowanie, uwierzytelnianie
Requires at least: 3.3.x
Tested up to: 4.0.1
Stable tag: 2.1.3
License: GNU Public License, version 2 
License URI: http://opensource.org/licenses/gpl-license.php

Instantly protects all accounts with magical, email-based two-factor authentication; optional mobile app for more security; no tokens.
 
== Description ==

= Account Protection for WordPress =
- Works Out-of-the-Box: Just Activate the Plugin
- No Configuration Needed
- So Simple that You Don't Need Training

= Why Do I Need Account Protection? =
WordPress websites get attacked by botnets that carry out bruteforce attacks. Once inside the admin panel, they start infecting your visitors with malware. This will get your website delisted from search engines or removed by your web hosting provider. All websites are a target, regardless of size. Account protection prevents those attacks.

= Why Are Passwords Not Enough? =
Many people have a simple, easy to guess password. It can be stolen when they use multiple devices, the same password across different services or when they're not on a secure connection.

= How Does Rublon Account Protection Work? =
During the first login, confirm your identity by clicking on a link that you get via email. Your next login from the same device will only require a password. For more security, install the Rublon mobile app. Scanning a QR code will then confirm your identity.

= Why Should I Use Rublon? =
Rublon works out-of-the-box. Just activate the plugin and you're done — no configuration needed. Your users don't need training and they don't have to install anything. They don't have to enter any codes. Once they confirm their identity on a device, they can log in to all web services by only entering a password.

= What Does Rublon Cost? =
Nothing! Install it now to get one year for free. If you want to white-label it using your own branding, please contact sales@rublon.com.

= What Languages Is Rublon Available In? =
- English
- German
- Polish

== Installation ==

1. Log in to your WordPress administration panel using an administrator account.
2. Go to "Plugins" -> "Add New" and search for "Rublon" using the plugins search box.
3. Click the "Install Now" button inside the Rublon plugin box in the search results and confirm the installation.
4. Click on "Activate Plugin".
5. Optional: For more security and control, install the Rublon mobile app onto your phone (available for [Android](https://play.google.com/store/apps/details?id=com.rublon.android), [iOS](https://itunes.apple.com/us/app/rublon/id501336019), [Windows Phone](http://www.windowsphone.com/en-us/store/app/rublon/809d960f-a3e8-412d-bc63-6cf7f2167d42) and [BlackBerry](appworld.blackberry.com/webstore/content/20177166/?countrycode=US&lang=en)).

= Server requirements =
- PHP version 5.2.17 or greater
- cURL PHP extension enabled

== Frequently Asked Questions ==

= How can I protect my account with Rublon? =
All you need to do is to install the Rublon for WordPress plugin. After you activate it, all accounts will be instantly protected with email-based two-factor authentication.

= Email-based two-factor authentication is not enough for me. Does Rublon support phone-based, out-of-band two-factor authentication? =
Yes! Just install the Rublon mobile app onto your phone (available for Android, iOS, Windows Phone and BlackBerry). After entering your correct login credentials, you will be prompted to scan a Rublon Code with your phone.

= Do all my users have to be protected by Rublon? =
After you activate the plugin, all accounts will instantly be protected. The minimum protection level for all user groups will be set to "Email". You can change this setting to "None" for each user group at any time. Please note that if a user installs the Rublon mobile app, his account will be protected regardless of this setting.

= Will my login credentials be known to Rublon? =
Rublon never knows your or any of your users' login credentials. They are never transmitted to our servers. Rublon does its work only after WordPress positively verifies your password. It's an independent security layer that sits beneath the login form.

= How secure is my data on Rublon's servers? =
For accounts that are protected via email, their email address gets transmitted to Rublon servers during each login, but is instantly removed after we send you the message containing your personal confirmation link. No personal data of such accounts is ever stored on Rublon servers. For accounts that are protected via mobile app, only the Rublon User ID gets transmitted to Rublon servers during login. Registered users of the Rublon mobile app are governed by its terms of service.

= Why is using the Rublon mobile app more secure than email-based authentication? =
The Rublon mobile app holds your digital identity with your private encryption key, which never leaves your phone. Whenever you perform any action that requires the mobile app, like confirming your identity, it generates a unique encrypted digital signature. Getting access to an email account that is not protected by any second factor is easier than stealing your private key from your phone and reusing it.

= What if I use the Rublon mobile app and I lose my phone? =
Simply deactivate your phone on the "Lost phone" page on our website. Once deactivated, recover your account by installing the Rublon mobile app on a new phone.

= How much does Rublon cost? =
Rublon for WordPress is free and always will be. We will launch a paid business plan soon that will offer premium features.
 
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