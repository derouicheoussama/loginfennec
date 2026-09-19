=== LoginFennec Pro – Personnalisation page login et Security ===
Contributors: derouicheoussama
Donate link: https://www.paypal.com/donate
Tags: login, login page, security, brute force, sms
Requires at least: 5.2
Tested up to: 7.1
Requires PHP: 7.2
Stable tag: 3.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Customize and secure your WordPress login page: logo, styles, brute-force protection, security journal — with a live preview.

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

<strong>📱 SMS Login (OTP)</strong>

* Users sign in with their phone number and a one-time code — no password
* SMS gateways: Twilio, Vonage, or any local provider through a generic HTTP webhook
* Codes are salted and hashed, single-use, with limited attempts
* Anti-abuse: minimum delay between sends per phone, hourly cap per IP
* Phone number field on user profiles; test-send button in the dashboard

<strong>🌍 GEO Restriction</strong>

* Allow login only from selected countries (ISO codes, e.g. DZ)
* Free HTTPS geolocation (geojs.io), result cached 24 h per IP, no API key
* Fail-open on detection failure — you can never be locked out
* IP whitelist always bypasses the country check

<strong>🔎 SEO &amp; Admin</strong>

* noindex, nofollow on the login page so it never shows up in Google (recommended)
* Custom login page title
* Full WordPress admin color customization: sidebar menu, hover, active item, admin bar, accent color
* 4 ready-made admin palettes: Nuit fennec, Désert, Océan, Clair

<strong>⚡ Experience</strong>

* Elegant dashboard: tabs, toggles, sliders, color pickers and live preview (desktop / tablet / mobile, fullscreen)
* Security statistics and recent activity right on the dashboard
* Onboarding wizard on activation; export / import of settings as JSON (secrets never exported)
* In-plugin purchase and license activation for the upcoming Pro
* Updates served by WordPress.org once published (nothing to configure)
* Translation-ready, multisite-compatible, no ads, no tracking

<strong>Why LoginFennec Pro?</strong>

* Most login customizers only style the page. LoginFennec Pro styles it AND protects it.
* Everything above is free — no feature paywalled inside the free plugin.
* Built by Derouiche Oussama, signed "∞ Infinity Coder" in every source file.

== Privacy ==

LoginFennec Pro does not send any data to third parties by default. Two optional features contact external services, only when enabled:

* SMS login: one HTTP request to your own configured SMS gateway (Twilio, Vonage or your webhook) when a user requests a code. The phone number and the message are sent to that gateway only.
* GEO restriction: when enabled, the visitor IP address is sent to geojs.io (HTTPS, free, no account) to determine the country; the result is cached locally for 24 hours and no other data leaves your site.

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

= 3.5.0 =
* Security: API keys and secrets (Twilio, Vonage, webhook, reCAPTCHA) are now encrypted at rest with AES-256-GCM using a per-site key derived from the WordPress auth salt
* Security: secret fields never display their stored value in the admin HTML — leave empty to keep the current key, type a new one to replace it
* Security: an empty secret field on save or import keeps the existing key

= 3.4.2 =
* Fixed: admin colors (and any field) could silently reset when the server truncated the form submission — saving is now a partial update that keeps stored values for fields missing from the request
* Added: hidden sentinel values on every toggle so unchecking still works with partial updates
* Added: automatic warning when the server low-limits max_input_vars below what this settings page needs

= 3.4.1 =
* New: real-time preview in the Admin tab — every color and palette restyles the live WordPress admin menu instantly, before saving
* New: onboarding note explaining the live admin-colors preview

= 3.4.0 =
* Changed: dashboard refreshed with a professional blue palette (derived from the fennec logo navy)
* Improved: detailed Free vs Pro comparison now covers every area — design, SMS login, security, GEO/SEO, support
* New: "Rate ★★★★★ on WordPress.org" link in the plugin row meta and a review card on the About page
* Updated: About page now documents WordPress.org-powered updates (no more GitHub updater references)
* Commercial release readiness: full audit, no known issues

= 3.3.1 =
* New: two more feature boxes in the onboarding overview (SMS login, GEO & SEO)
* Changed: full rebrand of the plugin dashboard with the fennec logo palette — fennec orange accent, deep navy, warm sand neutrals
* Changed: "Nuit fennec" admin palette now matches the logo (navy menu, orange active item, cream text)
* Changed: default login button and admin accent colors follow the brand

= 3.3.0 =
* Fixed: fatal error at activation — removed a leftover call to the deleted GitHub updater class (now checks WordPress.org)
* New: GEO restriction — allow login only from selected countries (opt-in, free HTTPS geolocation, 24 h cache, fail-open)
* New: SEO — noindex/nofollow on the login page (recommended, on by default) and custom login page title
* New: SMS login (3.2.0) hardened — CSS/JS moved to versioned asset files
* Security: settings export no longer contains secrets (SMS tokens, reCAPTCHA secret); import never wipes stored secrets
* Security: index.php guards added to every plugin folder (no directory listing)
* Updated: readme privacy section documenting the optional external requests (SMS gateway, geojs.io)

= 3.2.0 =

= 3.2.0 =
* New: SMS login — users sign in with their phone number and a one-time code
* New: SMS gateways Twilio, Vonage and generic HTTP webhook (local providers)
* New: phone number field on user profiles
* New: "SMS" tab with a test-send button and anti-abuse limits (per phone and per IP)
* New: OTP codes are salted and hashed, never stored in plain text

= 3.1.0 =
* New: full WordPress admin color customization (sidebar menu, hover, active item, admin bar, accent color)
* New: 4 ready-made admin palettes (Nuit fennec, Désert, Océan, Clair)
* New: dedicated "Admin" tab in the dashboard with color pickers
* Fixed: corrupted white-label login body class filter

= 3.0.0 =
* New: Google Fonts integration (20+ fonts with live preview)
* New: layout two-column mode with side image
* New: reCAPTCHA v3 integration for login protection
* New: custom login URL support
* New: white-label mode
* New: CSS file cache for better performance
* New: side image upload for two-column layout
* Improved: major performance optimization


= 2.6.1 =
* Fixed: Plugin Check compliance — output escaping, input sanitization, nonce verification, file operations
* Fixed: readme short description trimmed to 150 characters
* Improved: all security hooks now check trial status before activation


= 2.6.0 =
* Security: code integrity verification — signature checked on every admin load
* Security: tamper notification (email to admin + developer + webhook)
* Improved: fennec logo in admin menu, dashboard header, and all hero sections
* Improved: VS Code setup with recommended extensions and PHP 8.3 portable


= 2.5.1 =
* Improved: fennec logo throughout the plugin — admin menu icon, dashboard header, hero sections, installer
* Improved: brand assets organized in assets/img/ and brand/ folders


= 2.5.0 =
* New: 7-day trial system with anti-tampering (clock rollback detection, persistent file in uploads)
* New: after trial, login page returns to WordPress default and security features stop
* New: dashboard shows trial countdown during the last 3 days
* New: trial expired screen with one-click purchase and license activation
* Security: lockout escalation now works correctly


= 2.4.0 =
* New: professional in-plugin checkout popup — 3 steps (payment method, license key, activation), payment without ever leaving WordPress
* Payment methods: BaridiMob/CCP (account + exact amount + copy buttons), PayPal (auto-converted amount), embedded card checkout
* The selected pack feeds the popup automatically


= 2.4.0 =
* New: professional in-plugin checkout popup — 3 steps (payment method, license key, activation), payment without ever leaving WordPress
* Payment methods: BaridiMob/CCP (account + exact amount + copy buttons), PayPal (auto-converted amount), embedded card checkout
* The selected pack feeds the popup automatically


= 2.3.3 =
* Fixed: the Annuelle/À vie switch now syncs the license activation form
* Verified: automated tests of all 4 license offers (activation, sites, expiry, deactivation) pass in real PHP 8.3


= 2.3.2 =
* New: one-click purchase — "Acheter maintenant" goes straight to the packs, the 1-site pack is preselected and the payment methods (BaridiMob/CCP, PayPal, card) are visible immediately
* Improved: selected pack is highlighted


= 2.3.1 =
* New: real payment coordinates built in — CCP 0014480375 clé 46, BaridiMob 00799999001448037546, PayPal business payment@derouiche.dev (direct _xclick with amount)
* The three payment methods (BaridiMob/CCP, PayPal, card) are now always available on the Pro page

= 2.3.0 =
* New: in-plugin checkout with 3 payment methods — BaridiMob/CCP (account details + copy + proof email), PayPal link, and embedded card checkout
* New: pack chooser feeds the payment flow (1 site / 5 sites × yearly / lifetime)

= 2.2.0 =
* New: full licensing system — packs for 1 site and 5 sites, yearly or lifetime, priced in DA
* New: pricing page with Annuelle / À vie switch, advantages and lifetime updates
* New: license details panel — status, masked key, pack, type, expiry date, sites, last check
* New: daily license check (remote revocation + yearly expiry) and "verify now" button
* New: first Pro feature unlocked by license — email alerts on IP lockout
* New: activation form with pack and billing selection; local test mode without license server

= 2.1.6 =
* Improved: Pro page and all Pro elements now use a blue color scheme (hero, button, badges, table column)

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

= 2.0.0 =
* Important: renamed to LoginFennec Pro (fennec mascot) — settings migrated automatically
* Everything since the first release: design customizer, security suite, live preview, dashboard

