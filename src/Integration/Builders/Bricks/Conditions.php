<?php
declare(strict_types=1);

namespace CB\Profiles\Integration\Builders\Bricks;

use CB\Profiles\Frontend\Conditions as ProfileConditions;

defined( 'ABSPATH' ) || exit;

final class Conditions {
	private const GROUP             = 'cb_profiles';
	private const IS_PROFILE        = 'cb_profiles_is_profile';
	private const CAN_VIEW          = 'cb_profiles_can_view_profile';
	private const PROFILE_AVAILABLE = 'cb_profiles_profile_available';
	private const OWN_PROFILE       = 'cb_profiles_is_own_profile';
	private const PROFILE_ROLE      = 'cb_profiles_profile_role';

	public static function init(): void {
		add_filter( 'bricks/conditions/groups', [ self::class, 'register_group' ] );
		add_filter( 'bricks/conditions/options', [ self::class, 'register_options' ] );
		add_filter( 'bricks/conditions/result', [ self::class, 'result' ], 10, 3 );
	}

	/**
	 * @param array<int,array<string,mixed>> $groups
	 * @return array<int,array<string,mixed>>
	 */
	public static function register_group( array $groups ): array {
		$groups[] = [
			'name'  => self::GROUP,
			'label' => __( 'Core Blueprint Profiles', 'core-blueprint-profiles' ),
		];
		return $groups;
	}

	/**
	 * @param array<int,array<string,mixed>> $options
	 * @return array<int,array<string,mixed>>
	 */
	public static function register_options( array $options ): array {
		$options[] = [
			'key'   => self::IS_PROFILE,
			'label' => __( 'Is a profile request', 'core-blueprint-profiles' ),
			'group' => self::GROUP,
		];
		$options[] = [
			'key'   => self::CAN_VIEW,
			'label' => __( 'Visitor can view profile', 'core-blueprint-profiles' ),
			'group' => self::GROUP,
		];
		$options[] = [
			'key'   => self::PROFILE_AVAILABLE,
			'label' => __( 'Profile is available', 'core-blueprint-profiles' ),
			'group' => self::GROUP,
		];
		$options[] = [
			'key'   => self::OWN_PROFILE,
			'label' => __( 'Visitor is profile owner', 'core-blueprint-profiles' ),
			'group' => self::GROUP,
		];
		$options[] = [
			'key'     => self::PROFILE_ROLE,
			'label'   => __( 'Profile role', 'core-blueprint-profiles' ),
			'group'   => self::GROUP,
			'compare' => [
				'type'    => 'select',
				'options' => [ '==' => '=', '!=' => '≠' ],
			],
			'value'   => [
				'type'    => 'select',
				'options' => self::role_options(),
			],
		];

		return $options;
	}

	public static function result( bool $result, string $condition_key, array $condition ): bool {
		$supported = [
			self::IS_PROFILE,
			self::CAN_VIEW,
			self::PROFILE_AVAILABLE,
			self::OWN_PROFILE,
			self::PROFILE_ROLE,
		];
		if ( ! in_array( $condition_key, $supported, true ) ) {
			return $result;
		}

		if ( self::IS_PROFILE === $condition_key ) {
			return ProfileConditions::is_profile_request();
		}

		$user_id = Context::user_id();
		if ( self::CAN_VIEW === $condition_key ) {
			return ProfileConditions::can_view( $user_id );
		}
		if ( self::PROFILE_AVAILABLE === $condition_key ) {
			return ProfileConditions::profile_available( $user_id );
		}
		if ( self::OWN_PROFILE === $condition_key ) {
			return ProfileConditions::is_own_profile( $user_id );
		}

		$value   = isset( $condition['value'] ) && is_scalar( $condition['value'] ) ? sanitize_key( (string) $condition['value'] ) : '';
		$compare = isset( $condition['compare'] ) && is_scalar( $condition['compare'] ) ? (string) $condition['compare'] : '==';
		if ( '' === $value || ! in_array( $compare, [ '==', '!=' ], true ) ) {
			return false;
		}

		$matches = ProfileConditions::has_role( $user_id, $value );
		return '!=' === $compare ? ! $matches : $matches;
	}

	/** @return array<string,string> */
	private static function role_options(): array {
		$options = [];
		foreach ( wp_roles()->roles as $role => $definition ) {
			$label = isset( $definition['name'] ) ? translate_user_role( (string) $definition['name'] ) : (string) $role;
			$options[ (string) $role ] = $label;
		}
		return $options;
	}
}
