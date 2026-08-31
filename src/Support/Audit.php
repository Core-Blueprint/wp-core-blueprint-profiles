<?php
declare(strict_types=1);
namespace CB\Profiles\Support;

use CB\Core\Log\AuditLog;
use CB\Profiles\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Governance audit bridge for Profiles.
 *
 * Profiles owns the semantics and context of its events; Core Blueprint Base
 * owns durable storage, actor attribution, privacy handling and presentation.
 */
final class Audit {
	public static function init(): void {
		add_action( 'update_option_' . Settings::OPTION, [ __CLASS__, 'settings_updated' ], 10, 3 );
		add_filter( 'cb_core_event_labels', [ __CLASS__, 'register_event_labels' ] );
	}

	/**
	 * Audit persisted Profiles settings changes. The dynamic update_option hook
	 * only fires after WordPress has successfully changed the option.
	 *
	 * @param mixed  $old_value Previous option value.
	 * @param mixed  $new_value New option value.
	 * @param string $option    Option name.
	 */
	public static function settings_updated( mixed $old_value, mixed $new_value, string $option = '' ): void {
		unset( $option );

		$old = wp_parse_args( is_array( $old_value ) ? $old_value : [], Settings::defaults() );
		$new = wp_parse_args( is_array( $new_value ) ? $new_value : [], Settings::defaults() );

		if ( (bool) $old['enabled'] !== (bool) $new['enabled'] ) {
			self::log(
				! empty( $new['enabled'] ) ? 'profiles.enabled' : 'profiles.disabled',
				'notice',
				[ 'enabled' => (bool) $new['enabled'] ]
			);
		}

		if ( (string) $old['visibility'] !== (string) $new['visibility'] ) {
			self::log( 'profiles.visibility_changed', 'notice', [
				'from' => (string) $old['visibility'],
				'to'   => (string) $new['visibility'],
			] );
		}

		if ( (string) $old['base_slug'] !== (string) $new['base_slug'] ) {
			self::log( 'profiles.base_slug_changed', 'notice', [
				'from' => (string) $old['base_slug'],
				'to'   => (string) $new['base_slug'],
			] );
		}

		if ( (string) $old['slug_format'] !== (string) $new['slug_format'] ) {
			self::log( 'profiles.slug_format_changed', 'notice', [
				'from' => (string) $old['slug_format'],
				'to'   => (string) $new['slug_format'],
			] );
		}

		if ( (bool) $old['allow_user_slug_edit'] !== (bool) $new['allow_user_slug_edit'] ) {
			self::log( 'profiles.user_slug_editing_changed', 'notice', [
				'from' => (bool) $old['allow_user_slug_edit'],
				'to'   => (bool) $new['allow_user_slug_edit'],
			] );
		}

		$old_roles = array_values( array_unique( array_map( 'sanitize_key', (array) $old['excluded_roles'] ) ) );
		$new_roles = array_values( array_unique( array_map( 'sanitize_key', (array) $new['excluded_roles'] ) ) );
		sort( $old_roles );
		sort( $new_roles );
		if ( $old_roles !== $new_roles ) {
			self::log( 'profiles.excluded_roles_changed', 'notice', [
				'from' => $old_roles,
				'to'   => $new_roles,
			] );
		}

		self::audit_restricted_access_changes( $old, $new );
	}

	public static function user_profile_enabled_changed( int $user_id, bool $enabled ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		self::log(
			$enabled ? 'profiles.user_profile_enabled' : 'profiles.user_profile_disabled',
			'notice',
			[
				'target_user_id' => $user_id,
				'self_service'   => get_current_user_id() === $user_id,
			]
		);
	}

	public static function user_slug_rotated_for_privacy( int $user_id ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		self::log( 'profiles.user_slug_privacy_rotated', 'notice', [
			'target_user_id' => $user_id,
		] );
	}

	public static function user_slug_changed( int $user_id, bool $had_previous_slug ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		self::log( 'profiles.user_slug_changed', 'notice', [
			'target_user_id'    => $user_id,
			'self_service'      => get_current_user_id() === $user_id,
			'had_previous_slug' => $had_previous_slug,
		] );
	}

	/** @param array<string,string> $labels @return array<string,string> */
	public static function register_event_labels( array $labels ): array {
		$labels['profiles_enabled']                   = __( 'Profiles: profile pages enabled', 'core-blueprint-profiles' );
		$labels['profiles_disabled']                  = __( 'Profiles: profile pages disabled', 'core-blueprint-profiles' );
		$labels['profiles_visibility_changed']        = __( 'Profiles: visibility changed', 'core-blueprint-profiles' );
		$labels['profiles_base_slug_changed']         = __( 'Profiles: base URL changed', 'core-blueprint-profiles' );
		$labels['profiles_slug_format_changed']       = __( 'Profiles: URL format changed', 'core-blueprint-profiles' );
		$labels['profiles_user_slug_editing_changed'] = __( 'Profiles: user URL editing policy changed', 'core-blueprint-profiles' );
		$labels['profiles_excluded_roles_changed']    = __( 'Profiles: excluded roles changed', 'core-blueprint-profiles' );
		$labels['profiles_restricted_access_changed'] = __( 'Profiles: restricted access settings changed', 'core-blueprint-profiles' );
		$labels['profiles_user_profile_enabled']      = __( 'Profiles: user profile enabled', 'core-blueprint-profiles' );
		$labels['profiles_user_profile_disabled']     = __( 'Profiles: user profile disabled', 'core-blueprint-profiles' );
		$labels['profiles_user_slug_changed']         = __( 'Profiles: user profile URL changed', 'core-blueprint-profiles' );
		$labels['profiles_user_slug_privacy_rotated'] = __( 'Profiles: user profile URL rotated for privacy', 'core-blueprint-profiles' );
		return $labels;
	}

	/** @param array<string,mixed> $old @param array<string,mixed> $new */
	private static function audit_restricted_access_changes( array $old, array $new ): void {
		$fields = [
			'denied_behavior',
			'redirect_url',
			'template_source',
			'template_shortcode',
			'template_id',
			'wrap_header',
			'wrap_footer',
		];
		$changed = [];
		foreach ( $fields as $field ) {
			if ( $old[ $field ] !== $new[ $field ] ) {
				$changed[] = $field;
			}
		}
		if ( empty( $changed ) ) {
			return;
		}

		$context = [ 'changed_fields' => $changed ];
		if ( in_array( 'denied_behavior', $changed, true ) ) {
			$context['behavior_from'] = (string) $old['denied_behavior'];
			$context['behavior_to']   = (string) $new['denied_behavior'];
		}
		if ( in_array( 'template_source', $changed, true ) ) {
			$context['template_source_from'] = (string) $old['template_source'];
			$context['template_source_to']   = (string) $new['template_source'];
		}
		if ( in_array( 'template_id', $changed, true ) ) {
			$context['template_id_from'] = (int) $old['template_id'];
			$context['template_id_to']   = (int) $new['template_id'];
		}
		if ( in_array( 'redirect_url', $changed, true ) ) {
			$context['redirect_from_configured'] = '' !== (string) $old['redirect_url'];
			$context['redirect_to_configured']   = '' !== (string) $new['redirect_url'];
		}
		if ( in_array( 'template_shortcode', $changed, true ) ) {
			$context['shortcode_from_configured'] = '' !== (string) $old['template_shortcode'];
			$context['shortcode_to_configured']   = '' !== (string) $new['template_shortcode'];
		}
		if ( in_array( 'wrap_header', $changed, true ) ) {
			$context['wrap_header_from'] = (bool) $old['wrap_header'];
			$context['wrap_header_to']   = (bool) $new['wrap_header'];
		}
		if ( in_array( 'wrap_footer', $changed, true ) ) {
			$context['wrap_footer_from'] = (bool) $old['wrap_footer'];
			$context['wrap_footer_to']   = (bool) $new['wrap_footer'];
		}

		self::log( 'profiles.restricted_access_changed', 'notice', $context );
	}

	private static function log( string $event_type, string $severity, array $context = [] ): void {
		if ( class_exists( AuditLog::class ) ) {
			AuditLog::log( $event_type, $severity, $context );
		}
	}
}
