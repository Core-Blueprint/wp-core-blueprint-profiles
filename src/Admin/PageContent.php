<?php
declare(strict_types=1);
namespace CB\Profiles\Admin;

use CB\Profiles\ProfilePolicy;
use CB\Profiles\Settings;

defined( 'ABSPATH' ) || exit;

final class PageContent {
	public static function render(): void {
		$s = Settings::all();
		$roles = wp_roles()->roles;
		?>
		<div class="wrap cb-core-wrap">
			<div class="cb-core-page-header"><div><p class="cb-core-eyebrow"><?php esc_html_e( 'Core Blueprint', 'core-blueprint-profiles' ); ?></p><h1><?php esc_html_e( 'Profiles', 'core-blueprint-profiles' ); ?></h1></div></div>
			<p><?php esc_html_e( 'Create privacy-aware profile pages for your WordPress users. Choose who can view them, how profile URLs are created, and which users should have a profile.', 'core-blueprint-profiles' ); ?></p>

			<nav class="cb-core-tab-wrapper cb-profiles-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Profiles settings', 'core-blueprint-profiles' ); ?>" data-cb-profiles-tabs>
				<button type="button" class="nav-tab nav-tab-active" role="tab" aria-selected="true" aria-controls="cb-profiles-tab-general" id="cb-profiles-tab-general-button" data-cb-profiles-tab="general"><?php esc_html_e( 'General', 'core-blueprint-profiles' ); ?></button>
				<button type="button" class="nav-tab" role="tab" aria-selected="false" aria-controls="cb-profiles-tab-restricted" id="cb-profiles-tab-restricted-button" data-cb-profiles-tab="restricted"><?php esc_html_e( 'Restricted Access', 'core-blueprint-profiles' ); ?></button>
				<button type="button" class="nav-tab" role="tab" aria-selected="false" aria-controls="cb-profiles-tab-usage" id="cb-profiles-tab-usage-button" data-cb-profiles-tab="usage"><?php esc_html_e( 'Usage', 'core-blueprint-profiles' ); ?></button>
			</nav>

			<form method="post" action="options.php">
				<?php settings_fields( 'cb_profiles_settings_group' ); ?>
				<section id="cb-profiles-tab-general" class="cb-profiles-tab-panel" role="tabpanel" aria-labelledby="cb-profiles-tab-general-button" data-cb-profiles-panel="general">
					<div class="cb-core-card">
						<header class="cb-core-card__header"><h2 class="cb-core-card__title"><?php esc_html_e( 'Profile pages', 'core-blueprint-profiles' ); ?></h2></header>
						<div class="cb-core-card__body cb-profiles-fields">
							<?php self::checkbox( 'enabled', __( 'Enable profile pages', 'core-blueprint-profiles' ), $s['enabled'], __( 'Create frontend profile pages for WordPress users. When disabled, all profile URLs return a real 404.', 'core-blueprint-profiles' ), true ); ?>
							<div class="cb-core-field" data-cb-profiles-enabled-setting>
								<label class="cb-core-field__label" for="cb_profiles_visibility"><?php esc_html_e( 'Who can view profiles?', 'core-blueprint-profiles' ); ?></label>
								<div class="cb-core-field__control"><select id="cb_profiles_visibility" name="<?php echo esc_attr( Settings::OPTION ); ?>[visibility]" data-cb-profiles-visibility><option value="public" <?php selected( $s['visibility'], 'public' ); ?>><?php esc_html_e( 'Everyone', 'core-blueprint-profiles' ); ?></option><option value="logged_in" <?php selected( $s['visibility'], 'logged_in' ); ?>><?php esc_html_e( 'Logged-in users only', 'core-blueprint-profiles' ); ?></option></select></div>
								<p class="description"><?php esc_html_e( 'Choose who can open an enabled user profile.', 'core-blueprint-profiles' ); ?></p>
							</div>
						</div>
					</div>

					<div class="cb-core-card" data-cb-profiles-requires-enabled>
						<header class="cb-core-card__header"><h2 class="cb-core-card__title"><?php esc_html_e( 'Profile URLs', 'core-blueprint-profiles' ); ?></h2></header>
						<div class="cb-core-card__body cb-profiles-fields">
							<div class="cb-core-field">
								<label class="cb-core-field__label" for="cb_profiles_base_slug"><?php esc_html_e( 'Profile base path', 'core-blueprint-profiles' ); ?></label>
								<div class="cb-core-field__control"><input type="text" id="cb_profiles_base_slug" class="regular-text code" name="<?php echo esc_attr( Settings::OPTION ); ?>[base_slug]" value="<?php echo esc_attr( (string) $s['base_slug'] ); ?>" placeholder="profile"></div>
								<p class="description"><?php esc_html_e( 'The part of the URL that comes before the individual profile identifier.', 'core-blueprint-profiles' ); ?> <?php printf( esc_html__( 'Example: %s', 'core-blueprint-profiles' ), '<code>' . esc_html( home_url( '/' . Settings::base_slug() . '/k7p4x2n9/' ) ) . '</code>' ); ?></p>
							</div>
							<div class="cb-core-field">
								<label class="cb-core-field__label" for="cb_profiles_slug_format"><?php esc_html_e( 'How should new profile URLs be created?', 'core-blueprint-profiles' ); ?></label>
								<div class="cb-core-field__control"><select id="cb_profiles_slug_format" name="<?php echo esc_attr( Settings::OPTION ); ?>[slug_format]"><option value="anonymous" <?php selected( $s['slug_format'], 'anonymous' ); ?>><?php esc_html_e( 'Anonymous identifier — Recommended for privacy', 'core-blueprint-profiles' ); ?></option><option value="name" <?php selected( $s['slug_format'], 'name' ); ?>><?php esc_html_e( 'First and last name', 'core-blueprint-profiles' ); ?></option><option value="display_name" <?php selected( $s['slug_format'], 'display_name' ); ?>><?php esc_html_e( 'Display name', 'core-blueprint-profiles' ); ?></option></select></div>
								<div class="cb-profiles-url-examples" aria-label="<?php esc_attr_e( 'Profile URL examples', 'core-blueprint-profiles' ); ?>">
									<span><strong><?php esc_html_e( 'Anonymous:', 'core-blueprint-profiles' ); ?></strong> <code>/<?php echo esc_html( Settings::base_slug() ); ?>/k7p4x2n9/</code></span>
									<span><strong><?php esc_html_e( 'Name:', 'core-blueprint-profiles' ); ?></strong> <code>/<?php echo esc_html( Settings::base_slug() ); ?>/chris-bruinsma/</code></span>
									<span><strong><?php esc_html_e( 'Display name:', 'core-blueprint-profiles' ); ?></strong> <code>/<?php echo esc_html( Settings::base_slug() ); ?>/chris-b/</code></span>
								</div>
								<p class="description"><?php esc_html_e( 'This is used only when a profile URL is created for the first time. Existing profile URLs do not change automatically. Login names and email addresses are never used.', 'core-blueprint-profiles' ); ?></p>
							</div>
							<?php self::checkbox( 'allow_user_slug_edit', __( 'Let users choose their own profile URL', 'core-blueprint-profiles' ), $s['allow_user_slug_edit'], __( 'Users can replace their generated profile identifier with a unique name of their choice. Administrators can always change it.', 'core-blueprint-profiles' ) ); ?>
						</div>
					</div>

					<div class="cb-core-card" data-cb-profiles-requires-enabled>
						<header class="cb-core-card__header"><h2 class="cb-core-card__title"><?php esc_html_e( 'Who should have a profile?', 'core-blueprint-profiles' ); ?></h2></header>
						<div class="cb-core-card__body">
							<p class="cb-core-card__lead"><?php esc_html_e( 'By default, enabled WordPress users can have a profile. Exclude roles that should never have a profile page, such as administrator or technical accounts.', 'core-blueprint-profiles' ); ?></p>
							<div class="cb-core-module cb-profiles-role-disclosure" data-cb-profiles-role-disclosure>
								<div class="cb-core-module-header">
									<div class="cb-core-module-header-content">
										<div class="title"><?php esc_html_e( 'Hide profiles for selected roles', 'core-blueprint-profiles' ); ?></div>
										<p class="description"><?php esc_html_e( 'Selected roles:', 'core-blueprint-profiles' ); ?> <span data-cb-profiles-role-count><?php echo esc_html( (string) count( (array) $s['excluded_roles'] ) ); ?></span>. <?php esc_html_e( 'Users with these roles do not have a profile page; their profile URL returns a real 404.', 'core-blueprint-profiles' ); ?></p>
									</div>
									<button type="button" class="cb-core-module-collapse" aria-expanded="false" aria-controls="cb-profiles-role-options" aria-label="<?php esc_attr_e( 'Show or hide role options', 'core-blueprint-profiles' ); ?>" data-cb-profiles-role-toggle>
										<svg class="cb-core-chevron" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path d="M3 5l5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
									</button>
								</div>
								<div class="cb-core-module-body" id="cb-profiles-role-options" aria-hidden="true" inert>
									<div><div class="cb-profiles-role-grid">
									<?php foreach ( $roles as $slug => $role ) : ?><label><input type="checkbox" name="<?php echo esc_attr( Settings::OPTION ); ?>[excluded_roles][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, (array) $s['excluded_roles'], true ) ); ?> data-cb-profiles-role-checkbox> <?php echo esc_html( translate_user_role( $role['name'] ) ); ?> <code><?php echo esc_html( $slug ); ?></code></label><?php endforeach; ?>
									</div></div>
								</div>
							</div>
						</div>
					</div>
					<?php submit_button( __( 'Save settings', 'core-blueprint-profiles' ) ); ?>
				</section>
				<section id="cb-profiles-tab-restricted" class="cb-profiles-tab-panel" role="tabpanel" aria-labelledby="cb-profiles-tab-restricted-button" data-cb-profiles-panel="restricted" hidden>
					<div class="cb-core-card cb-profiles-context-card">
						<header class="cb-core-card__header"><h2 class="cb-core-card__title"><?php esc_html_e( 'Restricted Access', 'core-blueprint-profiles' ); ?></h2></header>
						<div class="cb-core-card__body">
							<p data-cb-profiles-context="disabled"<?php echo $s['enabled'] ? ' hidden' : ''; ?>><strong><?php esc_html_e( 'Profile pages are currently disabled.', 'core-blueprint-profiles' ); ?></strong> <?php esc_html_e( 'These settings will apply if profile pages are enabled and limited to logged-in users.', 'core-blueprint-profiles' ); ?></p>
							<p data-cb-profiles-context="public"<?php echo ( $s['enabled'] && 'public' === $s['visibility'] ) ? '' : ' hidden'; ?>><strong><?php esc_html_e( 'Profiles are currently visible to everyone.', 'core-blueprint-profiles' ); ?></strong> <?php esc_html_e( 'Restricted Access settings are only used when profiles are limited to logged-in users.', 'core-blueprint-profiles' ); ?></p>
							<p data-cb-profiles-context="logged_in"<?php echo ( $s['enabled'] && 'logged_in' === $s['visibility'] ) ? '' : ' hidden'; ?>><strong><?php esc_html_e( 'Profiles are limited to logged-in users.', 'core-blueprint-profiles' ); ?></strong> <?php esc_html_e( 'Choose what logged-out visitors should see when they open a profile URL.', 'core-blueprint-profiles' ); ?></p>
						</div>
					</div>

					<div class="cb-core-card" data-cb-profiles-restricted-settings>
						<header class="cb-core-card__header"><h2 class="cb-core-card__title"><?php esc_html_e( 'What should logged-out visitors see?', 'core-blueprint-profiles' ); ?></h2></header>
						<div class="cb-core-card__body cb-profiles-fields">
							<p class="cb-core-card__lead"><?php esc_html_e( 'These settings apply only to profiles that exist but require a logged-in account. Hidden or excluded profiles always return a real 404.', 'core-blueprint-profiles' ); ?></p>
							<div class="cb-core-field"><label class="cb-core-field__label" for="cb_profiles_denied_behavior"><?php esc_html_e( 'Logged-out visitor action', 'core-blueprint-profiles' ); ?></label><div class="cb-core-field__control"><select id="cb_profiles_denied_behavior" name="<?php echo esc_attr( Settings::OPTION ); ?>[denied_behavior]" data-cb-profiles-denied-behavior><option value="404" <?php selected( $s['denied_behavior'], '404' ); ?>><?php esc_html_e( 'Show 404', 'core-blueprint-profiles' ); ?></option><option value="redirect" <?php selected( $s['denied_behavior'], 'redirect' ); ?>><?php esc_html_e( 'Redirect', 'core-blueprint-profiles' ); ?></option><option value="render" <?php selected( $s['denied_behavior'], 'render' ); ?>><?php esc_html_e( 'Custom page', 'core-blueprint-profiles' ); ?></option></select></div></div>

							<div data-cb-profiles-behavior="redirect">
								<div class="cb-core-field"><label class="cb-core-field__label" for="cb_profiles_redirect_url"><?php esc_html_e( 'Redirect URL', 'core-blueprint-profiles' ); ?></label><div class="cb-core-field__control"><input type="url" id="cb_profiles_redirect_url" class="regular-text" name="<?php echo esc_attr( Settings::OPTION ); ?>[redirect_url]" value="<?php echo esc_attr( (string) $s['redirect_url'] ); ?>"></div><p class="description"><?php esc_html_e( 'Leave empty to use the WordPress login page and return the visitor to the requested profile after login.', 'core-blueprint-profiles' ); ?></p></div>
							</div>

							<div data-cb-profiles-behavior="render">
								<div class="cb-core-field"><label class="cb-core-field__label" for="cb_profiles_template_source"><?php esc_html_e( 'Custom page source', 'core-blueprint-profiles' ); ?></label><div class="cb-core-field__control"><select id="cb_profiles_template_source" name="<?php echo esc_attr( Settings::OPTION ); ?>[template_source]" data-cb-profiles-template-source><option value="shortcode" <?php selected( $s['template_source'], 'shortcode' ); ?>><?php esc_html_e( 'Shortcode', 'core-blueprint-profiles' ); ?></option><option value="id" <?php selected( $s['template_source'], 'id' ); ?>><?php esc_html_e( 'Page or template ID', 'core-blueprint-profiles' ); ?></option></select></div></div>
								<div data-cb-profiles-template="shortcode" class="cb-core-field"><label class="cb-core-field__label" for="cb_profiles_template_shortcode"><?php esc_html_e( 'Template shortcode', 'core-blueprint-profiles' ); ?></label><div class="cb-core-field__control"><input type="text" id="cb_profiles_template_shortcode" class="regular-text code" name="<?php echo esc_attr( Settings::OPTION ); ?>[template_shortcode]" value="<?php echo esc_attr( (string) $s['template_shortcode'] ); ?>" placeholder='[bricks_template id="123"]'></div><p class="description"><?php esc_html_e( 'Use a shortcode supplied by Bricks, Elementor or another builder/plugin.', 'core-blueprint-profiles' ); ?></p></div>
								<div data-cb-profiles-template="id" class="cb-core-field"><label class="cb-core-field__label" for="cb_profiles_template_id"><?php esc_html_e( 'Page or template ID', 'core-blueprint-profiles' ); ?></label><div class="cb-core-field__control"><input type="number" min="0" step="1" id="cb_profiles_template_id" class="small-text" name="<?php echo esc_attr( Settings::OPTION ); ?>[template_id]" value="<?php echo esc_attr( (string) (int) $s['template_id'] ); ?>"></div><p class="description"><?php esc_html_e( 'Only published pages and supported template post types are rendered.', 'core-blueprint-profiles' ); ?></p></div>
								<div class="cb-profiles-inline-checks"><input type="hidden" name="<?php echo esc_attr( Settings::OPTION ); ?>[wrap_header]" value="0"><label><input type="checkbox" name="<?php echo esc_attr( Settings::OPTION ); ?>[wrap_header]" value="1" <?php checked( $s['wrap_header'] ); ?>> <?php esc_html_e( 'Include theme header', 'core-blueprint-profiles' ); ?></label><input type="hidden" name="<?php echo esc_attr( Settings::OPTION ); ?>[wrap_footer]" value="0"><label><input type="checkbox" name="<?php echo esc_attr( Settings::OPTION ); ?>[wrap_footer]" value="1" <?php checked( $s['wrap_footer'] ); ?>> <?php esc_html_e( 'Include theme footer', 'core-blueprint-profiles' ); ?></label></div>
							</div>
						</div>
					</div>
					<?php submit_button( __( 'Save settings', 'core-blueprint-profiles' ) ); ?>
				</section>
			</form>

			<section id="cb-profiles-tab-usage" class="cb-profiles-tab-panel" role="tabpanel" aria-labelledby="cb-profiles-tab-usage-button" data-cb-profiles-panel="usage" hidden>
				<div class="cb-core-card">
					<header class="cb-core-card__header"><h2 class="cb-core-card__title"><?php esc_html_e( 'Builder and theme usage', 'core-blueprint-profiles' ); ?></h2></header>
					<div class="cb-core-card__body">
						<p><?php esc_html_e( 'Profiles remain native WordPress author archives. Build your author archive with your theme or page builder and use normal WordPress user data or ACF user fields.', 'core-blueprint-profiles' ); ?></p>
						<p><strong><?php esc_html_e( 'Example URL:', 'core-blueprint-profiles' ); ?></strong> <code><?php echo esc_html( home_url( '/' . Settings::base_slug() . '/k7p4x2n9/' ) ); ?></code></p>
						<div class="cb-core-divider"></div>
						<p><code>cb_profiles_is_profile()</code> — <?php esc_html_e( 'whether the current request is a profile.', 'core-blueprint-profiles' ); ?></p>
						<p><code>cb_profiles_get_profile_user()</code> — <?php esc_html_e( 'current WordPress profile user.', 'core-blueprint-profiles' ); ?></p>
						<p><code>cb_profiles_get_profile_user_id()</code> — <?php esc_html_e( 'current profile user ID.', 'core-blueprint-profiles' ); ?></p>
						<p><code>cb_profiles_get_profile_url( $user_id )</code> — <?php esc_html_e( 'canonical profile URL.', 'core-blueprint-profiles' ); ?></p>
						<p><code>cb_profiles_can_view_profile( $user_id )</code> — <?php esc_html_e( 'profile visibility check.', 'core-blueprint-profiles' ); ?></p>
					</div>
				</div>
			</section>
		</div>
		<?php
	}

	private static function checkbox( string $key, string $label, mixed $checked, string $description, bool $enabled_control = false ): void {
		$name = Settings::OPTION . '[' . $key . ']';
		?>
		<div class="cb-core-field cb-core-field--enable"<?php echo $enabled_control ? ' data-cb-profiles-enabled-control' : ''; ?>><input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="0"><label class="cb-core-field__label"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked ); ?>> <?php echo esc_html( $label ); ?></label><p class="description"><?php echo esc_html( $description ); ?></p></div>
		<?php
	}
}
