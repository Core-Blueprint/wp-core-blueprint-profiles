<?php
declare(strict_types=1);
namespace CB\Profiles;
defined( 'ABSPATH' ) || exit;

final class Settings {
	public const OPTION = 'cb_profiles_settings';

	/** @return array<string,mixed> */
	public static function defaults(): array {
		return [
			'enabled'            => true,
			'base_slug'          => 'profile',
			'slug_format'        => 'anonymous',
			'allow_user_slug_edit' => false,
			'visibility'         => 'public',
			'excluded_roles'     => [ 'administrator' ],
			'denied_behavior'    => '404',
			'redirect_url'       => '',
			'template_source'    => 'shortcode',
			'template_shortcode' => '',
			'template_id'        => 0,
			'wrap_header'        => true,
			'wrap_footer'        => true,
		];
	}

	/** @return array<string,mixed> */
	public static function all(): array {
		return wp_parse_args( (array) get_option( self::OPTION, [] ), self::defaults() );
	}

	public static function base_slug(): string {
		$slug = sanitize_title( (string) self::all()['base_slug'] );
		return '' !== $slug ? $slug : 'profile';
	}

	/** @param mixed $input @return array<string,mixed> */
	public static function sanitize( mixed $input ): array {
		$input = is_array( $input ) ? $input : [];
		$out   = self::defaults();

		$out['enabled']    = ! empty( $input['enabled'] );
		$slug              = sanitize_title( (string) ( $input['base_slug'] ?? 'profile' ) );
		$out['base_slug']  = '' !== $slug ? $slug : 'profile';
		$out['visibility'] = in_array( $input['visibility'] ?? '', [ 'public', 'logged_in' ], true ) ? (string) $input['visibility'] : 'public';
		$out['slug_format'] = in_array( $input['slug_format'] ?? '', [ 'anonymous', 'name', 'display_name' ], true ) ? (string) $input['slug_format'] : 'anonymous';
		$out['allow_user_slug_edit'] = ! empty( $input['allow_user_slug_edit'] );

		$roles = array_map( 'sanitize_key', (array) ( $input['excluded_roles'] ?? [] ) );
		$out['excluded_roles'] = array_values( array_unique( array_filter( $roles, static fn( string $role ): bool => '' !== $role && wp_roles()->is_role( $role ) ) ) );

		$out['denied_behavior'] = in_array( $input['denied_behavior'] ?? '', [ '404', 'redirect', 'render' ], true ) ? (string) $input['denied_behavior'] : '404';
		$out['redirect_url']    = esc_url_raw( (string) ( $input['redirect_url'] ?? '' ) );
		$out['template_source'] = in_array( $input['template_source'] ?? '', [ 'shortcode', 'id' ], true ) ? (string) $input['template_source'] : 'shortcode';
		$out['template_shortcode'] = 'shortcode' === $out['template_source'] ? sanitize_text_field( (string) ( $input['template_shortcode'] ?? '' ) ) : '';
		$out['template_id']        = 'id' === $out['template_source'] ? absint( $input['template_id'] ?? 0 ) : 0;
		$out['wrap_header']        = ! empty( $input['wrap_header'] );
		$out['wrap_footer']        = ! empty( $input['wrap_footer'] );

		$old = self::all();
		if ( ( $old['base_slug'] ?? 'profile' ) !== $out['base_slug'] ) {
			set_transient( 'cb_profiles_flush_rewrite_rules', '1', MINUTE_IN_SECONDS );
		}

		return $out;
	}
}
