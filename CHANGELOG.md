# Changelog

## 1.0.0-rc1 — 2026-08-25

- Folded the tested rc4 feature set into the shared Core Blueprint pre-launch `1.0.0-rc1` baseline.
- Hardened public WordPress user REST responses so `slug` and `link` use the privacy-safe Core Blueprint Profiles identity instead of exposing `user_nicename`.
- No other functional changes from the tested rc4 release candidate.

## 0.1.0-rc4

- Fix the excluded-role disclosure toggle when Core Blueprint Base also handles shared module-collapse controls.
- No routing, privacy, settings, or profile-policy changes.


## 0.1.0-rc3 — 2026-08-25

- Reworked the Profiles admin into a guided setup flow with separate Profile pages, Profile URLs and profile availability cards.
- Replaced ambiguous public/visibility terminology with task-focused labels and explanations.
- Added live contextual guidance for Restricted Access.
- Collapsed the excluded-role list by default while keeping the selected-role count visible.
- Added contextual inactive states without clearing or disabling saved settings.
- Aligned per-user profile copy with public and logged-in-only visibility modes.
- Updated EN/NL/DE translations for the revised interface.

## 0.1.0-rc2 — 2026-08-25

- Replaced public `user_nicename` URLs with dedicated privacy-safe profile slugs.
- Added anonymous identifiers as the privacy-friendly default profile URL format.
- Added optional first/last-name and display-name slug generation with anonymous fallback.
- Added administrator-controlled user profile URL customization.
- Added uniqueness, reserved-slug and email/login leakage safeguards, including automatic rotation if an existing slug later becomes sensitive.
- Added safe rc1 URL migration and canonical redirects for non-sensitive legacy nicenames.
- Native author routes derived from email logins now return 404 instead of confirming the account mapping.
- Added automatic rewrite refresh for the rc1 → rc2 upgrade.

## 0.1.0-rc1 — 2026-08-25

- Initial release candidate.
- Added configurable native WordPress author profile routing.
- Added global and per-user profile visibility controls.
- Added excluded-role privacy policy.
- Added restricted access behaviour: 404, redirect or custom page.
- Added builder-agnostic shortcode/page-template denial rendering.
- Added sitemap and public REST visibility safeguards.
- Added optional Core Blueprint Likes visibility integration.
- Added EN/NL/DE internationalization.
