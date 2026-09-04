<?php
declare(strict_types=1);

namespace CB\Profiles\Integration\Builders\Bricks;

use CB\Profiles\Frontend\Data;

defined( 'ABSPATH' ) || exit;

final class DynamicData {
	private const GROUP  = 'Core Blueprint Profiles';
	private const PREFIX = 'cb_profiles_';

	/** @var array<string,string> */
	private const FIELDS = [
		'cb_profiles_profile_id'    => 'id',
		'cb_profiles_display_name'  => 'display_name',
		'cb_profiles_first_name'    => 'first_name',
		'cb_profiles_last_name'     => 'last_name',
		'cb_profiles_bio'           => 'bio',
		'cb_profiles_profile_url'   => 'profile_url',
		'cb_profiles_profile_slug'  => 'profile_slug',
		'cb_profiles_avatar_url'    => 'avatar_url',
		'cb_profiles_website_url'   => 'website_url',
	];

	public static function init(): void {
		add_filter( 'bricks/dynamic_tags_list', [ self::class, 'register_tags' ] );
		add_filter( 'bricks/dynamic_data/render_tag', [ self::class, 'render_tag' ], 20, 3 );
		add_filter( 'bricks/dynamic_data/render_content', [ self::class, 'render_content' ], 20, 3 );
		add_filter( 'bricks/frontend/render_data', [ self::class, 'render_content' ], 20, 2 );
	}

	/**
	 * @param array<int,array<string,mixed>> $tags
	 * @return array<int,array<string,mixed>>
	 */
	public static function register_tags( array $tags ): array {
		foreach ( self::labels() as $name => $label ) {
			$tags[] = [
				'name'  => '{' . $name . '}',
				'label' => $label,
				'group' => self::GROUP,
			];
		}
		return $tags;
	}

	public static function render_tag( mixed $tag, mixed $context = null, string $render_context = 'text' ): mixed {
		unset( $render_context );
		if ( ! is_string( $tag ) ) {
			return $tag;
		}

		$name = trim( $tag, '{}' );
		if ( ! isset( self::FIELDS[ $name ] ) ) {
			return $tag;
		}

		$value = self::value( $name, $context );
		return null === $value ? '' : $value;
	}

	public static function render_content( mixed $content, mixed $context = null, string $render_context = 'text' ): mixed {
		unset( $render_context );
		if ( ! is_string( $content ) || ! str_contains( $content, '{' . self::PREFIX ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/\{(cb_profiles_[a-z0-9_]+)\}/',
			static function ( array $matches ) use ( $context ): string {
				$value = self::value( (string) $matches[1], $context );
				return null === $value ? '' : (string) $value;
			},
			$content
		) ?? $content;
	}

	private static function value( string $name, mixed $context ): string|int|null {
		$field = self::FIELDS[ $name ] ?? '';
		if ( '' === $field ) {
			return null;
		}

		$user_id = Context::user_id( $context );
		return $user_id > 0 ? Data::field( $user_id, $field ) : null;
	}

	/** @return array<string,string> */
	private static function labels(): array {
		return [
			'cb_profiles_profile_id'   => __( 'Profile · User ID', 'core-blueprint-profiles' ),
			'cb_profiles_display_name' => __( 'Profile · Display name', 'core-blueprint-profiles' ),
			'cb_profiles_first_name'   => __( 'Profile · First name', 'core-blueprint-profiles' ),
			'cb_profiles_last_name'    => __( 'Profile · Last name', 'core-blueprint-profiles' ),
			'cb_profiles_bio'          => __( 'Profile · Bio', 'core-blueprint-profiles' ),
			'cb_profiles_profile_url'  => __( 'Profile · URL', 'core-blueprint-profiles' ),
			'cb_profiles_profile_slug' => __( 'Profile · Public slug', 'core-blueprint-profiles' ),
			'cb_profiles_avatar_url'   => __( 'Profile · Avatar URL', 'core-blueprint-profiles' ),
			'cb_profiles_website_url'  => __( 'Profile · Website URL', 'core-blueprint-profiles' ),
		];
	}
}
