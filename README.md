# Core Blueprint Profiles

Privacy-aware WordPress user profile pages built on native WordPress author archives.

## v1.0.0-rc1

- Guided admin setup for profile availability, visibility, URLs and restricted access.
- Configurable profile base slug, e.g. `/profile/k7p4x2n9/` or `/profiel/k7p4x2n9/`.
- Dedicated public profile slugs are independent from WordPress usernames, login emails and `user_nicename`.
- Anonymous identifiers are the privacy-friendly default; administrators can instead generate slugs from first/last name or display name.
- Existing public slugs remain stable until deliberately changed.
- Administrators can optionally allow users to choose their own unique profile URL.
- Native WordPress author context: no profile CPT and no custom template engine.
- Works with themes and page builders that support author archives, including Bricks and Elementor.
- Optional Bricks adapter for profile dynamic data, member queries and profile-aware conditions; Bricks is never a dependency.
- Public or logged-in-only profile visibility.
- Exclude selected WordPress roles from profile pages.
- Per-user profile-page toggle on the WordPress user edit screen.
- Hidden/excluded profiles always return a real 404.
- Restricted logged-in-only profiles can show a 404, redirect, or render a shortcode/page-template.
- Optional theme header/footer around a custom restricted page.
- Public users sitemap and public WordPress user REST responses follow the same profile policy and expose the privacy-safe Profiles slug instead of `user_nicename`.
- Optional Core Blueprint Likes visibility bridge; neither plugin requires the other.
- Public helper API for builder/theme integrations.
- English source strings with Dutch and German translations.
- No telemetry, tracking or external requests.

## Builder usage

Core Blueprint Profiles does not render the public profile itself. Create an Author Archive template in your theme/page builder and use normal WordPress author/user data, ACF user fields, or the builder-neutral Profiles contracts.

When Bricks is active, the optional adapter adds:

- `Core Blueprint Profiles` dynamic data for public profile identity, URL, avatar and biography fields.
- `Profiles: Members` and `Profiles: Current profile` query types.
- Profile-aware conditions for profile requests, visibility, ownership, availability and role.
- Privacy-safe query records that deliberately omit WordPress login, email and `user_nicename` identity fields.

Public helpers:

- `cb_profiles_is_profile()`
- `cb_profiles_get_profile_user()`
- `cb_profiles_get_profile_user_id()`
- `cb_profiles_get_profile_url( $user_id )`
- `cb_profiles_can_view_profile( $user_id )`
