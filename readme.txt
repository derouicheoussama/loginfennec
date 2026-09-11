=== Infinity Customizer – Login Customizer & Security ===
Contributors: derouicheoussama
Tags: login, customizer, login page, security, social icons
Requires at least: 5.2
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Make the WordPress login page yours: logo, background (blur, opacity, gradients), modern styles, links, social icons, copyright — and block brute-force password attempts.

== Description ==

Infinity Customizer redesigns your WordPress login page with a modern, fluid experience and protects it against brute-force attacks — all from an elegant dashboard with a **live preview**.

**Design**

* Custom logo (image, size, link) — or hide it completely
* Background: solid color, gradient (angle control) or image
* Blur control (backdrop) and colored overlay with opacity control
* 6 one-click modern styles: Glassmorphism, Minimal, Dark, Sunset, Ocean, Forest
* Glassmorphism form: translucency, blur, radius, shadow, width, padding
* Full control over text, labels, inputs, button (normal + hover) and link colors
* Fully responsive (desktop, tablet, mobile)

**Content**

* Customize or hide "Lost your password?", "Back to site" and "Register" links
* Change link text and target URL (e.g. send users to your shop)
* Social media icons: Facebook, X (Twitter), Instagram, LinkedIn, YouTube, e-mail — circle, rounded or square, with size and color controls
* Copyright line with {year} and {sitename} variables

**Security**

* Limit login attempts: after N failures, the IP address and the username are locked for a configurable duration
* Live "attempts remaining" warning on the login screen
* Option to hide detailed error messages (generic message instead)
* Optional: hide the language switcher
* Security headers on the login page (X-Frame-Options, nosniff, Referrer-Policy)

**Experience**

* Modern admin dashboard: tabs, toggle switches, sliders, color pickers
* Live preview of the login page with desktop / tablet / mobile views
* Auto-updates from your GitHub releases (built-in updater)

== Installation ==

1. In your WordPress admin, go to **Plugins → Add New → Upload Plugin** and upload `infinity-customizer.zip`.
2. Activate the plugin.
3. Open the new **Infinity Customizer** menu and start customizing — the live preview updates as you type.
4. Click **Save** to apply your design to the real login page.

== Frequently Asked Questions ==

= Does it work behind a reverse proxy / CDN? =

The security module uses `REMOTE_ADDR`. If your server sits behind a proxy, ask your host to forward the real IP, or use a filter to switch to `X-Forwarded-For` before trusting it.

= How do updates work? =

The plugin checks the latest GitHub release of its repository every 6 hours and offers the update from the Plugins screen. Publishing a `v1.2.3` tag is enough (see the included GitHub Action).

= I published the plugin on WordPress.org, how do I switch updates? =

Add this to your site (or a small companion plugin):
`add_filter( 'infinity_customizer_update_source', fn() => 'wordpress' );`

= Can I reset everything? =

Yes — the "Reset" button in the dashboard restores the default settings.

== Screenshots ==

1. Modern admin dashboard with live preview.
2. One-click modern styles (glassmorphism, dark, sunset…).
3. Background controls: gradient, image, blur and opacity.
4. Social media icons on the login page.
5. Security settings: limit login attempts.
6. The redesigned login page in action.

== Changelog ==

= 1.1.0 =
* New: custom onboarding wizard (welcome, style choice, security) shown on activation and after major updates
* New: "Check for updates" button — queries GitHub instantly and links to the one-click update
* New: live preview now fills the screen, with a fullscreen mode (Esc to close)
* New: background image position control (center, corners, edges)
* New: "Go Pro" page — advanced security levels (2FA, reCAPTCHA, custom login URL, email alerts, audit log, geo-blocking)
* New: developer social profiles on the About page
* Improved: wider layout, the preview uses all available horizontal space

= 1.0.0 =
* Initial release: login page customizer (logo, background, blur, opacity, styles, links, social icons, copyright), live preview dashboard, GitHub updater and login-attempts protection.

== Upgrade Notice ==

= 1.0.0 =
First public release.
