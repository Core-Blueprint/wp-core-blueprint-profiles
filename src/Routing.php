<?php
declare(strict_types=1);
namespace CB\Profiles;
defined( 'ABSPATH' ) || exit;

final class Routing {
	public const QUERY_VAR = 'cb_profile';

	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_rewrite_rules' ], 10 );
		add_action( 'init', [ __CLASS__, 'maybe_flush' ], 99 );
		add_filter( 'query_vars', [ __CLASS__, 'query_vars' ] );
		add_filter( 'request', [ __CLASS__, 'resolve_request' ] );
		add_filter( 'author_link', [ __CLASS__, 'author_link' ], 10, 3 );
		add_action( 'template_redirect', [ __CLASS__, 'canonicalize_native_author' ], -5 );
	}

	public static function register_rewrite_rules(): void {
		$base = preg_quote( Settings::base_slug(), '#' );
		add_rewrite_rule( '^' . $base . '/([^/]+)/page/([0-9]{1,})/?$', 'index.php?' . self::QUERY_VAR . '=$matches[1]&paged=$matches[2]', 'top' );
		add_rewrite_rule( '^' . $base . '/([^/]+)/?$', 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top' );
	}

	/** @param string[] $vars @return string[] */
	public static function query_vars( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return array_values( array_unique( $vars ) );
	}

	/** @param array<string,mixed> $vars @return array<string,mixed> */
	public static function resolve_request( array $vars ): array {
		$slug = isset( $vars[ self::QUERY_VAR ] ) ? sanitize_title( (string) $vars[ self::QUERY_VAR ] ) : '';
		if ( '' === $slug ) {
			return $vars;
		}

		$user_id = ProfileSlug::find_user_id( $slug );
		if ( $user_id <= 0 ) {
			$legacy_user = get_user_by( 'slug', $slug );
			if ( $legacy_user instanceof \WP_User && ! self::native_identifier_is_sensitive( $legacy_user ) ) {
				ProfileSlug::get( (int) $legacy_user->ID );
				$user_id = (int) $legacy_user->ID;
			}
		}
		if ( $user_id <= 0 ) {
			$vars['error'] = '404';
			return $vars;
		}

		$vars['author'] = $user_id;
		unset( $vars['author_name'] );
		return $vars;
	}

	public static function maybe_flush(): void {
		if ( false === get_transient( 'cb_profiles_flush_rewrite_rules' ) ) {
			return;
		}
		delete_transient( 'cb_profiles_flush_rewrite_rules' );
		flush_rewrite_rules( false );
	}

	public static function author_link( string $link, int $author_id, string $author_nicename ): string {
		unset( $author_nicename );
		if ( empty( Settings::all()['enabled'] ) ) {
			return '';
		}
		$url = self::profile_url( $author_id );
		return '' !== $url ? $url : '';
	}

	public static function profile_url( int $user_id ): string {
		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User || ! ProfilePolicy::is_profile_available( $user_id ) ) {
			return '';
		}
		$slug = ProfileSlug::get( $user_id );
		if ( '' === $slug ) {
			return '';
		}
		return home_url( user_trailingslashit( Settings::base_slug() . '/' . $slug ) );
	}

	public static function canonicalize_native_author(): void {
		if ( ! is_author() ) {
			return;
		}
		$user = ProfilePolicy::current_profile_user();
		if ( ! $user instanceof \WP_User ) {
			return;
		}

		$requested_profile_slug = sanitize_title( (string) get_query_var( self::QUERY_VAR ) );
		if ( '' !== $requested_profile_slug ) {
			$canonical_slug = ProfileSlug::get( (int) $user->ID );
			if ( '' !== $canonical_slug && $requested_profile_slug !== $canonical_slug ) {
				$url = self::profile_url( (int) $user->ID );
				if ( '' !== $url ) {
					wp_safe_redirect( $url, 301 );
					exit;
				}
			}
			return;
		}

		if ( self::native_identifier_is_sensitive( $user ) ) {
			self::mark_404();
			return;
		}

		$url = self::profile_url( (int) $user->ID );
		if ( '' !== $url ) {
			wp_safe_redirect( $url, 301 );
			exit;
		}
	}

	private static function native_identifier_is_sensitive( \WP_User $user ): bool {
		if ( is_email( (string) $user->user_login ) ) {
			return true;
		}
		$nicename = sanitize_title( (string) $user->user_nicename );
		return '' !== (string) $user->user_email && $nicename === sanitize_title( (string) $user->user_email );
	}

	private static function mark_404(): void {
		global $wp_query;
		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->set_404();
		}
		status_header( 404 );
		nocache_headers();
	}
}
