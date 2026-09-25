=== Core Blueprint Profiles ===
Contributors: coreblueprint
Tags: profiles, users, privacy, author archives, members
Requires at least: 7.0
Requires PHP: 8.4
Stable tag: 1.0.0-rc1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Privacy-aware WordPress profile pages with configurable URLs, visibility and builder-friendly author archives.

== Description ==

Core Blueprint Profiles adds privacy-aware public profile pages for WordPress users while keeping native WordPress author archives as the canonical frontend context.

Profiles can use anonymous public slugs that are independent from usernames, login email addresses and user_nicename. Administrators control profile availability, visibility, URL generation and excluded roles. Per-user profile visibility can also be managed from the WordPress user edit screen.

Hidden or excluded profiles return a real 404. Logged-in-only profiles can return a 404, redirect, or render configured restricted content.

The public users sitemap and public WordPress user REST responses follow the same profile policy.

Core Blueprint Base is required. Profiles uses the Base Extension Registry and Settings Registry.

When Bricks is active, an optional adapter adds profile dynamic data, member queries and profile-aware conditions. Bricks is not a dependency.

Core Blueprint Profiles does not send telemetry, tracking data or external requests.

== Installation ==

1. Install and activate Core Blueprint Base.
2. Install and activate Core Blueprint Profiles.
3. Open Core Blueprint settings and configure Profiles.
4. Create an Author Archive template in your theme or page builder if you want a custom public profile design.

== Frequently Asked Questions ==

= Does Profiles replace WordPress users? =

No. Profiles uses native WordPress users and author archive context.

= Are WordPress usernames or email addresses used in public profile URLs? =

Not by default. Anonymous profile slugs are the privacy-friendly default and are stored separately from login identity.

= Is Bricks required? =

No. Bricks support is an optional adapter. Profiles remains builder-agnostic.

= Does the plugin send data to external services? =

No. The plugin does not include telemetry, tracking or external requests.

== Changelog ==

= 1.0.0-rc1 =
* First public release candidate.
* Adds privacy-aware profile URLs and visibility policy.
* Adds native author archive integration and public profile helpers.
* Adds optional Bricks dynamic data, query and condition adapters.
* Adds Core Blueprint Base settings and extension integration.
