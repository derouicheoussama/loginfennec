=== Infinity LoginShield – Login Customizer & Security ===
Contributors: derouicheoussama
Donate link: https://www.paypal.com/donate
Tags: login, customizer, login page, security, social icons
Requires at least: 5.2
Tested up to: 7.1
Requires PHP: 7.2
Stable tag: 1.8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Make the WordPress login page yours: logo, background (blur, opacity, gradients), modern styles, links, social icons, copyright — and block brute-force password attempts.

== Description ==

Infinity LoginShield redesigns your WordPress login page with a modern, fluid experience and protects it against brute-force attacks — all from an elegant dashboard with a **live preview**.

**Design**

* Custom logo (image, size, link) — or hide it completely
* Background: solid color, gradient (angle control) or image
* Blur control (backdrop) and colored overlay with opacity control
* 10 one-click modern styles: Glassmorphism, Minimal, Dark, Sunset, Ocean, Forest, Neon, Sakura, Monochrome, Royal
* 7 form UI themes (glass, classic, outline, pill, elevated, accent, minimal) — independent from the colors
* Glassmorphism form: translucency, blur, radius, shadow, width, padding
* Full control over text, labels, inputs, button (normal + hover) and link colors
* Welcome message, 4 font families, entry animations, custom CSS
* Fully responsive (desktop, tablet, mobile)

**Content**

* Customize or hide "Lost your password?", "Back to site" and "Register" links
* Change link text and target URL (e.g. send users to your shop)
* Social media icons: Facebook, X (Twitter), Instagram, LinkedIn, YouTube, e-mail — circle, rounded or square, with size and color controls
* Copyright line with {year} and {sitename} variables

**Security**

* Limit login attempts: after N failures, the IP address and the username are locked for a configurable duration
* Live "attempts remaining" warning on the login screen
* Honeypot anti-bot field (blocked bots show up in the journal)
* Block author scans (?author=N) and close the REST users endpoint to visitors
* Optional XML-RPC deactivation
* Security journal: the last 50 events (failures, blocks, logins) with IP and username
* Option to hide detailed error messages (generic message instead)
* Optional: hide the language switcher
* Security headers on the login page (X-Frame-Options, nosniff, Referrer-Policy)

**Experience**

* Modern admin dashboard: tabs, toggle switches, sliders, color pickers
* Live preview of the login page with desktop / tablet / mobile views
* Export / import your settings as JSON
* Custom onboarding wizard, official brand colors, welcome message, copyright
* Auto-updates from your GitHub releases (built-in updater, switches to WordPress.org automatically)

== Installation ==

1. In your WordPress admin, go to **Plugins → Add New → Upload Plugin** and upload `infinity-loginshield.zip`.
2. Activate the plugin.
3. Open the new **Infinity LoginShield** menu and start customizing — the live preview updates as you type.
4. Click **Save** to apply your design to the real login page.

== Frequently Asked Questions ==

= Does it work behind a reverse proxy / CDN? =

The security module uses `REMOTE_ADDR`. If your server sits behind a proxy, ask your host to forward the real IP, or use a filter to switch to `X-Forwarded-For` before trusting it.

= How do updates work? =

The plugin checks the latest GitHub release of its repository every 6 hours and offers the update from the Plugins screen. Publishing a `v1.2.3` tag is enough (see the included GitHub Action).

= I published the plugin on WordPress.org, how do I switch updates? =

Add this to your site (or a small companion plugin):
`add_filter( 'infinity_loginshield_update_source', fn() => 'wordpress' );`

= Can I reset everything? =

Yes — the "Reset" button in the dashboard restores the default settings.

= Can I buy Pro without leaving WordPress? =

Yes. Define `INFINITY_LOGINSHIELD_CHECKOUT_URL` in wp-config.php with your payment link (Lemon Squeezy, Stripe Payment Link, Gumroad…) and the checkout opens in an overlay inside the plugin. Add `INFINITY_LOGINSHIELD_LICENSE_API` to validate license keys against your own server.

== Screenshots ==

1. Modern admin dashboard with live preview.
2. One-click modern styles (glassmorphism, dark, sunset…).
3. Background controls: gradient, image, blur and opacity.
4. Social media icons on the login page.
5. Security settings: limit login attempts.
6. The redesigned login page in action.

== Changelog ==

= 1.8.1 =
* Fixed: official brand colors of social icons now always win over any other CSS
* New: "Protection & DMCA" card on the About page — copyright, license terms and violation reporting
* New: optional DMCA badge via INFINITY_LOGINSHIELD_DMCA_BADGE / INFINITY_LOGINSHIELD_DMCA_URL

= 1.8.0 =
* New: "∞ Infinity Coder" signature in every source file (PHP, JS, CSS) and in the generated login-page HTML
* New: "Infinity Coder — conçu par Derouiche Oussama" in the admin footer of every plugin page
* License and attribution notices added to all sources (must be preserved on copy/modify, per GPL 2(c))

= 1.7.0 =
* New: in-plugin purchase — the checkout opens in a secure overlay inside wp-admin (Lemon Squeezy, Stripe Payment Link, Gumroad…)
* New: license key activation and deactivation from the Pro page, with optional license-server validation
* New: one `inls_is_pro()` helper for add-ons and the upcoming Pro module

= 1.6.1 =
* Improved: Pro page redesign — benefits cards, comparison table grouped by category (Protection / Surveillance / Contrôle) with a highlighted Pro column
* Removed: the AI-style sparkle decorations on the Pro page, menu and dashboard

= 1.6.0 =
* New: IP whitelist — trusted IPs (exact or wildcard) are never locked out
* New: custom redirect after login
* New: one-click "recommended pack" activates all 5 security protections (score 5/5)
* New: export the security journal as CSV
* Perf: login background image is preloaded, and the login page now sends no-cache headers

= 1.5.1 =
* Fixed: Sakura, Neon, Monochrome and Royal styles now apply correctly with one click
* Fixed: developer social icons no longer shift on hover — perfectly aligned and stable
* Perf: the Dashicons stylesheet only loads on the login page when social icons are enabled

= 1.5.0 =
* New: dashboard with live security statistics (logins, blocks, failures, active locks)
* New: security score (0-5) with a visual checklist of active protections
* New: recent activity feed right on the dashboard
* Improved: About page — quick links, latest changelog and quality commitments

= 1.4.0 =
* New: honeypot anti-bot field on the login form (blocked bots appear in the security journal)
* New: block author scans (?author=N) and close the REST users endpoint to logged-out visitors
* New: export / import your settings as JSON
* New: "Customize" shortcut on the Plugins screen + GitHub / About row links
* Dev: CI workflow with PHP syntax checks and PHPCS configuration

= 1.3.0 =
* Important: the plugin is now called **Infinity LoginShield**
* New: 7 form UI themes (glass, classic, outline, pill, elevated, accent, minimal) — independent from colors
* New: 4 color styles (Neon, Sakura, Monochrome, Royal) — 10 presets total
* New: custom CSS field for the login page
* New: security journal — the last 50 events (failures, blocks, logins) with IP and username
* New: XML-RPC hardening toggle
* Existing settings are migrated automatically

= 1.2.0 =
* New: official brand colors for social icons (Facebook blue, X black, Instagram gradient, LinkedIn blue, YouTube red, Gmail red) — toggle on/off
* New: "Extras" tab — welcome message (title + subtitle) above the form, typography (4 font families + text size), entry animation (fade, slide, zoom) with reduced-motion support
* New: background image brightness and saturation controls
* Fixed: live preview iframe could collapse to a short height, cutting off the login page

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
