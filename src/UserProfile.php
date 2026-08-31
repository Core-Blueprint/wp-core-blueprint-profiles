<?php
declare(strict_types=1);
namespace CB\Profiles;
defined( 'ABSPATH' ) || exit;

final class UserProfile {
	public static function init(): void {
		add_action( 'show_user_profile', [ __CLASS__, 'render' ] );
		add_action( 'edit_user_profile', [ __CLASS__, 'render' ] );
		add_action( 'user_profile_update_errors', [ __CLASS__, 'validate' ], 10, 3 );
		add_action( 'personal_options_update', [ __CLASS__, 'save' ] );
		add_action( 'edit_user_profile_update', [ __CLASS__, 'save' ] );
	}

	public static function render( \WP_User $user ): void {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}
		$enabled  = ProfilePolicy::individual_enabled( (int) $user->ID );
		$slug     = ProfileSlug::get( (int) $user->ID );
		$can_edit = self::can_edit_slug( (int) $user->ID );
		$base_url = home_url( user_trailingslashit( Settings::base_slug() ) );
		?>
		<h2><?php esc_html_e( 'Core Blueprint Profiles', 'core-blueprint-profiles' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><?php esc_html_e( 'Profile page', 'core-blueprint-profiles' ); ?></th>
				<td>
					<?php wp_nonce_field( 'cb_profiles_user_profile_' . $user->ID, 'cb_profiles_user_profile_nonce' ); ?>
					<label><input type="checkbox" name="cb_profiles_public_enabled" value="1" <?php checked( $enabled ); ?>> <?php esc_html_e( 'Enable this user’s profile page', 'core-blueprint-profiles' ); ?></label>
					<p class="description"><?php esc_html_e( 'Disable this to make this user’s profile URL return a real 404, regardless of the global visibility setting.', 'core-blueprint-profiles' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="cb_profiles_public_slug"><?php esc_html_e( 'Profile URL', 'core-blueprint-profiles' ); ?></label></th>
				<td>
					<?php if ( $can_edit ) : ?>
						<code><?php echo esc_html( trailingslashit( $base_url ) ); ?></code><input type="text" id="cb_profiles_public_slug" class="regular-text code" name="cb_profiles_public_slug" value="<?php echo esc_attr( $slug ); ?>">
						<p class="description"><?php esc_html_e( 'Choose a unique profile URL. Email addresses and login names are never accepted as profile slugs.', 'core-blueprint-profiles' ); ?></p>
					<?php else : ?>
						<code><?php echo esc_html( trailingslashit( $base_url ) . $slug . '/' ); ?></code>
						<p class="description"><?php esc_html_e( 'Profile URL customization is disabled by the site administrator.', 'core-blueprint-profiles' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	public static function validate( \WP_Error $errors, bool $update, \stdClass $user ): void {
		unset( $update );
		$user_id = isset( $user->ID ) ? (int) $user->ID : 0;
		if ( $user_id <= 0 || ! self::can_edit_slug( $user_id ) || ! isset( $_POST['cb_profiles_public_slug'] ) ) {
			return;
		}
		$requested = sanitize_text_field( wp_unslash( (string) $_POST['cb_profiles_public_slug'] ) );
		$result    = ProfileSlug::validate( $user_id, $requested );
		if ( is_wp_error( $result ) ) {
			$errors->add( $result->get_error_code(), $result->get_error_message() );
		}
	}

	public static function save( int $user_id ): void {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		$nonce = isset( $_POST['cb_profiles_user_profile_nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['cb_profiles_user_profile_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'cb_profiles_user_profile_' . $user_id ) ) {
			return;
		}
		update_user_meta( $user_id, ProfilePolicy::META_ENABLED, ! empty( $_POST['cb_profiles_public_enabled'] ) ? '1' : '0' );

		if ( self::can_edit_slug( $user_id ) && isset( $_POST['cb_profiles_public_slug'] ) ) {
			$requested = sanitize_text_field( wp_unslash( (string) $_POST['cb_profiles_public_slug'] ) );
			ProfileSlug::set( $user_id, $requested );
		}
	}

	private static function can_edit_slug( int $user_id ): bool {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		if ( current_user_can( Capabilities::MANAGE ) ) {
			return true;
		}
		$settings = Settings::all();
		return ! empty( $settings['allow_user_slug_edit'] ) && get_current_user_id() === $user_id;
	}
}
