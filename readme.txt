=== LoginFennec Pro – Personnalisation page login et Security ===
Contributors: derouicheoussama
Donate link: https://www.paypal.com/donate
Tags: login, customizer, login page, security, social icons
Requires at least: 5.2
Tested up to: 7.1
Requires PHP: 7.2
Stable tag: 2.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Make the WordPress login page yours: logo, background (blur, opacity, gradients), 10 modern styles, 7 form themes, social icons with official brand colors, security journal and brute-force protection — all with a live preview.

== Description ==

LoginFennec Pro — Personnalisation page login et Security. It redesigns your WordPress login page AND protects it, from one elegant dashboard with a live preview. No code needed — and unlike most login customizers, security is built in, not an afterthought.

<strong>🎨 Design &amp; Branding</strong>

* 10 one-click modern styles: Glassmorphism, Minimal, Dark, Sunset, Ocean, Forest, Neon, Sakura, Monochrome, Royal
* 7 form UI themes (glass, classic, outline, pill, elevated, accent, minimal) — independent from the colors
* Custom logo: image or text, with size, link and hide options
* Background: solid color, gradient with angle control, or image with position, blur, brightness, saturation and colored overlay
* Full color control: form, texts, labels, inputs (normal + focus), button (normal + hover), links
* Typography: 4 font families, text size and input height
* Welcome message (title + subtitle), copyright line with {year} and {sitename}
* Entry animations (fade, slide, zoom) with reduced-motion support
* Custom CSS and custom JavaScript
* Fully responsive: desktop, tablet, mobile

<strong>🔗 Content &amp; Links</strong>

* Social media icons with official brand colors: Facebook, X, Instagram, LinkedIn, YouTube, email — circle, rounded or square
* Customize or hide "Lost your password?", "Back to site" and "Register" links
* Change link text and target URL (e.g. send users back to your shop)
* Custom placeholders and labels for the username and password fields
* Custom redirect after login

<strong>🛡️ Security</strong>

* Limit login attempts: lock out the IP and the username after N failures, for a configurable duration
* Honeypot anti-bot field — blocked bots show up in the journal
* Block author scans (?author=N) and close the REST users endpoint to logged-out visitors
* IP whitelist so you can never lock yourself out
* Optional XML-RPC deactivation and generic error messages
* Security journal: the last 50 events (failures, blocks, logins) with IP and username, exportable as CSV
* Security score (0-5) with a one-click "recommended pack"
* Security headers on the login page (X-Frame-Options, nosniff, no-cache)

<strong>⚡ Experience</strong>

* Elegant dashboard: tabs, toggles, sliders, color pickers and live preview (desktop / tablet / mobile, fullscreen)
* Security statistics and recent activity right on the dashboard
* Onboarding wizard on activation; export / import of settings as JSON
* In-plugin purchase and license activation for the upcoming Pro
* Auto-updates from your GitHub releases — switches to WordPress.org automatically once hosted there
* Translation-ready, multisite-compatible, no ads, no tracking

<strong>Why LoginShield?</strong>

* Most login customizers only style the page. LoginShield styles it AND protects it.
* Everything above is free — no feature paywalled inside the free plugin.
* Built by Derouiche Oussama, signed "∞ Infinity Coder" in every source file.

== Installation ==

1. In your WordPress admin, go to **Plugins → Add New → Upload Plugin** and upload `loginfennec.zip`.
2. Activate the plugin.
3. Open the new **LoginFennec Pro** menu and start customizing — the live preview updates as you type.
4. Click **Save** to apply your design to the real login page.

== Frequently Asked Questions ==

= Does it work behind a reverse proxy / CDN? =

The security module uses `REMOTE_ADDR`. If your server sits behind a proxy, ask your host to forward the real IP, or use a filter to switch to `X-Forwarded-For` before trusting it.

= How do updates work? =

The plugin checks the latest GitHub release of its repository every 6 hours and offers the update from the Plugins screen. Publishing a `v1.2.3` tag is enough (see the included GitHub Action).

= I published the plugin on WordPress.org, how do I switch updates? =

Add this to your site (or a small companion plugin):
`add_filter( 'loginfennec_update_source', fn() => 'wordpress' );`

= Can I reset everything? =

Yes — the "Reset" button in the dashboard restores the default settings.

= Can I buy Pro without leaving WordPress? =

Yes. Define `LOGINFENNEC_CHECKOUT_URL` in wp-config.php with your payment link (Lemon Squeezy, Stripe Payment Link, Gumroad…) and the checkout opens in an overlay inside the plugin. Add `LOGINFENNEC_LICENSE_API` to validate license keys against your own server.

== Screenshots ==

1. Modern admin dashboard with live preview.
2. One-click modern styles (glassmorphism, dark, sunset…).
3. Background controls: gradient, image, blur and opacity.
4. Social media icons on the login page.
5. Security settings: limit login attempts.
6. The redesigned login page in action.

== Changelog ==

= 2.1.5 =
* Improved: the Pro page now uses the same width and centered layout as the About page (hero centered too)

= 2.1.4 =
* Improved: About page layout widened (1500px) for a fuller, better-balanced display

= 2.1.3 =
* Fixed: fatal error on activation — a duplicate method declaration in the security module (introduced in 2.1.0) is removed
* Verified with real PHP 8.3 execution: plugin load, activation and all admin pages

= 2.1.2 =
* Fixed: the plugin now automatically deactivates the old copy (LoginFence / Infinity LoginShield) on first admin load — activation works even when the old version is still active

= 2.1.1 =
* Fixed: anti-conflict guard — if an old copy of the plugin (folder "loginfence" or "infinity-loginshield") is still active, LoginFennec no longer causes a critical error and displays a clear notice instead

= 2.1.0 =
* Security: escalating lockout — the ban duration doubles on each repeat offense (up to ×8)
* Security: optional deactivation of application passwords
* Security: extra headers on the login page (X-XSS-Protection, Permissions-Policy)
* Improved: onboarding wizard with a recap step, your current IP displayed and a skip option
* Improved: About page social icons in a simple flat brand-color style

= 2.0.0 =
* Important: the plugin is now called **LoginFennec Pro** — your settings are migrated automatically
* Everything from 1.x is included: design customizer, security suite, journal, stats, in-plugin purchase and license activation


= 1.9.2 =
* Improved: developer social icons now use the original brand logos (Facebook, X, Instagram, LinkedIn, YouTube, TikTok, GitHub) with their official colors
* Improved: About page design centered for a cleaner layout

= 1.9.1 =
* Improved: onboarding wizard — centered pro layout, all 10 styles + 7 form themes, full security step (lockout duration, honeypot, author blocking, XML-RPC)
* New: "Clear plugin cache" button (resets update checks and refreshes assets)
* Perf: admin CSS/JS now auto-busted via file modification time — no more stale cached assets

= 1.9.0 =
* New: text logo option (replaces the image with a styled title)
* New: custom placeholders and labels for the username and password fields
* New: custom JavaScript field for the login page
* New: input height control (0 = WordPress default)
* Improved: fully reorganized readme description for easier discovery

= 1.8.1 =
* Fixed: official brand colors of social icons now always win over any other CSS
* New: "Protection & DMCA" card on the About page — copyright, license terms and violation reporting
* New: optional DMCA badge via LOGINFENNEC_DMCA_BADGE / LOGINFENNEC_DMCA_URL

= 1.8.0 =
* New: "∞ Infinity Coder" signature in every source file (PHP, JS, CSS) and in the generated login-page HTML
* New: "Infinity Coder — conçu par Derouiche Oussama" in the admin footer of every plugin page
* License and attribution notices added to all sources (must be preserved on copy/modify, per GPL 2(c))

= 1.7.0 =
* New: in-plugin purchase — the checkout opens in a secure overlay inside wp-admin (Lemon Squeezy, Stripe Payment Link, Gumroad…)
* New: license key activation and deactivation from the Pro page, with optional license-server validation
* New: one `lnf_is_pro()` helper for add-ons and the upcoming Pro module

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
* Important: the plugin is now called **LoginFennec Pro**
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
