/**
 * ∞ INFINITY CODER — reconstruction de la fiche WordPress.org (readme.txt)
 * Préserve le changelog existant (_changelog-backup.txt) et insère l'entrée 3.7.0.
 */
import fs from 'node:fs';

const head = `=== LoginFennec Pro – Personnalisation page login et Security ===
Contributors: derouicheoussama
Donate link: https://www.paypal.com/donate
Tags: login, login page, security, brute force, sms
Requires at least: 5.2
Tested up to: 7.1
Requires PHP: 7.2
Stable tag: 3.7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Customize and secure your WordPress login page — logo, styles, SMS login, brute-force and GEO protection, SEO — with a live preview.

== Description ==

LoginFennec Pro redesigns your WordPress login page AND protects it, from one elegant dashboard with a live preview. No code needed — and unlike most login customizers, security is built in, not an afterthought.

<strong>✨ In one look</strong>

* 10 one-click styles (Glass, Dark, Neon, Sakura…) + 7 independent form designs
* Live preview — desktop, tablet and mobile, updates as you type
* Login by SMS one-time code (Twilio, Vonage or your own gateway)
* Brute-force lockout with escalating duration, honeypot, country restriction (GEO)
* noindex SEO guard on the login page
* WordPress admin color themes with 4 ready-made palettes
* API keys encrypted at rest, never displayed, never exported

<strong>🎨 Design & Branding</strong>

* Custom logo: image or text, with size, link and hide options
* Background: solid color, gradient with angle control, or image with position, blur, brightness, saturation and colored overlay
* Full color control: form, texts, labels, inputs (normal + focus), button (normal + hover), links — with <strong>automatic text contrast</strong>: never dark text on a dark button, for every color and every style
* Typography: 4 font families, 20+ Google Fonts, text size and input height
* Welcome message (title + subtitle), copyright line with {year} and {sitename}
* Entry animations (fade, slide, zoom) with reduced-motion support
* Custom CSS and custom JavaScript — fully responsive

<strong>📱 SMS Login (OTP)</strong>

* Users sign in with their phone number and a one-time code — no password
* Gateways: Twilio, Vonage, or any local provider through a generic HTTP webhook
* Codes are salted, hashed and single-use, with limited attempts
* Anti-abuse: minimum delay between sends per phone, hourly cap per IP
* Phone number field on user profiles; test-send button in the dashboard

<strong>🛡️ Security</strong>

* Limit login attempts: lock out the IP and the username after N failures — duration doubles on every repeat
* Honeypot anti-bot field, author-scan blocking, REST users endpoint closed
* reCAPTCHA v3, optional XML-RPC and application-passwords deactivation
* Country restriction (GEO): allow login only from selected countries — free HTTPS geolocation, 24 h cache, fail-open, IP whitelist always wins
* Security journal: last 50 events with IP and username, exportable as CSV
* Security score (0-5) with a one-click "recommended pack", security headers on the login page

<strong>🔎 SEO & Admin</strong>

* noindex, nofollow on the login page (recommended, on by default) + custom login page title
* Full WordPress admin color customization: sidebar menu, hover, active item, admin bar, accent — with <strong>real-time preview</strong> and 4 palettes (Nuit fennec, Désert, Océan, Clair)

<strong>⚡ Experience & stability</strong>

* Elegant dashboard: tabs, toggles, sliders, color pickers, onboarding wizard
* Export / import of settings as JSON — secrets never exported, never wiped by an import
* Safe mode: one switch to disable everything when troubleshooting
* Opt-in automatic updates through the native WordPress mechanism
* Translation-ready, multisite-compatible, no ads, no tracking

<strong>Why LoginFennec Pro?</strong>

* Most login customizers only style the page. LoginFennec Pro styles it AND protects it.
* Everything above is free — no feature paywalled inside the free plugin.
* Built by Derouiche Oussama, signed "∞ Infinity Coder" in every source file.

== Installation ==

1. In your WordPress admin, go to **Plugins → Add New → Upload Plugin** and upload the loginfennec zip.
2. Activate — the onboarding wizard opens and walks you through your first style.
3. Open the **LoginFennec Pro** menu: customize with the live preview, enable security in one click.
4. Click **Save** — your design is applied to the real login page (hard-refresh wp-login.php to see it).

== Frequently Asked Questions ==

= Does it slow down my site? =

No. Everything is loaded only where needed: the login page gets one small inline stylesheet, admin assets load only on the plugin screens, and the SMS panel loads only when SMS login is enabled. No external calls happen by default.

= Does it work with my theme, page builder and WooCommerce? =

Yes. The plugin only touches wp-login.php and adds no front-end code to your public pages, so themes and builders are unaffected. Any WordPress login form (including WooCommerce accounts) uses wp-login.php under the hood.

= How does SMS login work, and what does it cost? =

LoginFennec generates the one-time code and hands the SMS to the gateway YOU configure: Twilio, Vonage, or any provider exposing an HTTP API (webhook). SMS pricing depends on that provider only — the plugin itself adds no cost.

= Can I use a local Algerian (or any national) SMS gateway? =

Yes — choose the "Webhook HTTP" gateway. The plugin POSTs {to, from, message} as JSON to your endpoint, with an optional secret token in the X-Lnf-Token header.

= Where are my API keys stored? =

Encrypted with AES-256-GCM using a per-site key derived from your WordPress auth salt. Keys are never displayed in the admin (masked fields), never included in JSON exports, and are kept when you save with an empty field.

= Does it work behind a reverse proxy / CDN? =

The security module uses REMOTE_ADDR. If your server sits behind a proxy, ask your host to forward the real IP, or use the standard WordPress constants (like X-Forwarded-For handling) on your setup.

= How do updates work? =

The plugin is served by the official WordPress.org directory: updates appear in Plugins → Mise à jour like any native plugin, nothing to configure. Automatic updates are opt-in in the Extras tab.

= Is it multisite and translation ready? =

Yes — multisite compatible, and fully translatable (text domain: loginfennec). French strings are included in the interface by default.

= Is it GDPR / privacy friendly? =

Yes. Nothing is sent to third parties by default; the two optional features (your own SMS gateway, and the geojs.io country lookup) are documented in the Privacy section below, with data kept local.

= Can I reset everything? =

Yes — the "Reset" button restores the default settings. The "Safe mode" switch (Extras tab) temporarily disables all customization and security modules for troubleshooting without losing any setting.

= Can I buy Pro without leaving WordPress? =

Yes. Define LOGINFENNEC_CHECKOUT_URL in wp-config.php with your payment link and the checkout opens in an overlay inside the plugin. Add LOGINFENNEC_LICENSE_API to validate license keys against your own server.

== Screenshots ==

1. The elegant dashboard: security score, statistics, security journal and quick actions.
2. The live preview — desktop, tablet and mobile, updating as you type.
3. Ten one-click styles and seven form designs, from glassmorphism to minimal.
4. SMS login: gateway setup, one-time code settings and test-send button.
5. The login page redesigned: custom logo, background, welcome message and social icons.
6. Admin colors: the WordPress sidebar and admin bar restyled in real time.
7. Security settings: brute-force lockout, honeypot, GEO restriction, reCAPTCHA v3.
8. The Pro page: detailed free vs Pro comparison and integrated purchase wizard.

`;

let changelog = fs.readFileSync( '_changelog-backup.txt', 'utf8' );
const entry370 = `== Changelog ==

= 3.7.0 =
* New: brand identity v2 — the keyhole-and-fennec logo across the plugin, banners and WordPress.org assets
* Improved: WordPress.org listing — description, FAQ and screenshot captions rewritten

`;
changelog = changelog.replace( '== Changelog ==\n\n= 3.6.2 =', entry370 + '= 3.6.2 =' );
if ( ! changelog.includes( '= 3.7.0 =' ) ) {
	changelog = changelog.replace( '== Changelog ==', entry370.trimEnd() + '\n\n' );
}

const tail = `

== Privacy ==

LoginFennec Pro does not send any data to third parties by default. Two optional features contact external services, only when enabled:

* SMS login: one HTTP request to your own configured SMS gateway (Twilio, Vonage or your webhook) when a user requests a code. The phone number and the message are sent to that gateway only.
* GEO restriction: when enabled, the visitor IP address is sent to geojs.io (HTTPS, free, no account) to determine the country; the result is cached locally for 24 hours and no other data leaves your site.
`;

fs.writeFileSync( 'readme.txt', head + changelog.trimEnd() + tail );
console.log( 'readme.txt reconstruit — entrées changelog :', ( changelog.match( /^= [\d.]+ =$/gm ) || [] ).length );
