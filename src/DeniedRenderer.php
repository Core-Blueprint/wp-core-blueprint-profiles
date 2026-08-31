<?php
declare(strict_types=1);
namespace CB\Profiles;
defined( 'ABSPATH' ) || exit;

final class DeniedRenderer {
	public static function init(): void {
		add_action( 'template_redirect', [ __CLASS__, 'protect_profile' ], 0 );
		add_filter( 'wp_sitemaps_users_query_args', [ __CLASS__, 'sitemap_user_args' ], 10, 1 );
		add_filter( 'rest_user_query', [ __CLASS__, 'rest_user_args' ], 10, 2 );
		add_filter( 'rest_pre_dispatch', [ __CLASS__, 'protect_single_rest_user' ], 10, 3 );
		add_filter( 'rest_prepare_user', [ __CLASS__, 'prepare_rest_user' ], 10, 3 );
	}

	public static function protect_profile(): void {
		$user = ProfilePolicy::current_profile_user();
		if ( ! $user instanceof \WP_User ) {
			return;
		}

		$reason = ProfilePolicy::denial_reason( (int) $user->ID );
		if ( 'allowed' === $reason ) {
			return;
		}

		if ( 'hidden' === $reason ) {
			self::render_404();
		}

		$settings = Settings::all();
		switch ( $settings['denied_behavior'] ) {
			case 'redirect':
				$target = '' !== (string) $settings['redirect_url']
					? (string) $settings['redirect_url']
					: wp_login_url( Routing::profile_url( (int) $user->ID ) );
				wp_safe_redirect( $target );
				exit;
			case 'render':
				self::render_custom( $settings );
				exit;
			default:
				self::render_404();
		}
	}

	/** @param array<string,mixed> $args @return array<string,mixed> */
	public static function sitemap_user_args( array $args ): array {
		$settings = Settings::all();
		if ( empty( $settings['enabled'] ) || 'public' !== $settings['visibility'] ) {
			$args['include'] = [ -1 ];
			return $args;
		}

		$args = self::apply_public_user_constraints( $args );
		return $args;
	}

	/** @param array<string,mixed> $args @return array<string,mixed> */
	public static function rest_user_args( array $args, \WP_REST_Request $request ): array {
		if ( current_user_can( 'list_users' ) ) {
			return $args;
		}
		$settings = Settings::all();
		if ( empty( $settings['enabled'] ) || ( 'logged_in' === $settings['visibility'] && ! is_user_logged_in() ) ) {
			$args['include'] = [ -1 ];
			return $args;
		}
		return self::apply_public_user_constraints( $args );
	}

	public static function prepare_rest_user( \WP_REST_Response $response, \WP_User $user, \WP_REST_Request $request ): \WP_REST_Response {
		if ( current_user_can( 'list_users' ) || 'edit' === (string) $request->get_param( 'context' ) ) {
			return $response;
		}

		$data = $response->get_data();
		if ( ! is_array( $data ) || ! ProfilePolicy::can_view( (int) $user->ID ) ) {
			return $response;
		}

		$slug = ProfileSlug::get( (int) $user->ID );
		$url  = Routing::profile_url( (int) $user->ID );
		if ( '' !== $slug ) {
			$data['slug'] = $slug;
		}
		if ( '' !== $url ) {
			$data['link'] = $url;
		}
		$response->set_data( $data );
		return $response;
	}

	public static function protect_single_rest_user( mixed $result, \WP_REST_Server $server, \WP_REST_Request $request ): mixed {
		if ( null !== $result || current_user_can( 'list_users' ) || ! in_array( $request->get_method(), [ 'GET', 'HEAD' ], true ) ) {
			return $result;
		}
		if ( ! preg_match( '#^/wp/v2/users/(\d+)$#', $request->get_route(), $matches ) ) {
			return $result;
		}
		$user_id = (int) $matches[1];
		if ( $user_id === get_current_user_id() || ProfilePolicy::can_view( $user_id ) ) {
			return $result;
		}
		return new \WP_Error( 'cb_profiles_not_found', __( 'Profile not found.', 'core-blueprint-profiles' ), [ 'status' => 404 ] );
	}

	/** @param array<string,mixed> $args @return array<string,mixed> */
	private static function apply_public_user_constraints( array $args ): array {
		$settings = Settings::all();
		$args['role__not_in'] = array_values( array_unique( array_merge(
			(array) ( $args['role__not_in'] ?? [] ),
			(array) $settings['excluded_roles']
		) ) );

		$visibility_meta = [
			'relation' => 'OR',
			[ 'key' => ProfilePolicy::META_ENABLED, 'compare' => 'NOT EXISTS' ],
			[ 'key' => ProfilePolicy::META_ENABLED, 'value' => '1', 'compare' => '=' ],
		];
		$existing_meta = (array) ( $args['meta_query'] ?? [] );
		$args['meta_query'] = empty( $existing_meta ) ? $visibility_meta : [
			'relation' => 'AND',
			$existing_meta,
			$visibility_meta,
		];
		return $args;
	}

	private static function render_404(): never {
		global $wp_query;
		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->set_404();
		}
		status_header( 404 );
		nocache_headers();
		$template = get_query_template( '404' );
		if ( ! $template ) {
			$template = get_index_template();
		}
		if ( $template ) {
			include $template;
		}
		exit;
	}

	/** @param array<string,mixed> $settings */
	private static function render_custom( array $settings ): void {
		$html = '';
		if ( 'shortcode' === $settings['template_source'] && '' !== trim( (string) $settings['template_shortcode'] ) ) {
			$html = do_shortcode( (string) $settings['template_shortcode'] );
		} elseif ( 'id' === $settings['template_source'] && (int) $settings['template_id'] > 0 ) {
			$post = get_post( (int) $settings['template_id'] );
			if ( $post instanceof \WP_Post && self::is_renderable_template_post( $post ) ) {
				$html = apply_filters( 'the_content', $post->post_content );
			}
		}

		if ( '' === trim( $html ) ) {
			$html = '<div class="cb-profiles-denied"><p>' . esc_html__( 'This profile is available to logged-in users only.', 'core-blueprint-profiles' ) . '</p></div>';
		}

		status_header( 200 );
		nocache_headers();
		if ( ! empty( $settings['wrap_header'] ) ) {
			get_header();
		} else {
			do_action( 'wp_head' );
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered shortcode/page output.
		echo $html;
		if ( ! empty( $settings['wrap_footer'] ) ) {
			get_footer();
		} else {
			do_action( 'wp_footer' );
		}
	}

	private static function is_renderable_template_post( \WP_Post $post ): bool {
		if ( 'publish' !== $post->post_status ) {
			return false;
		}
		$allowed = (array) apply_filters( 'cb_profiles_template_post_types', [ 'page', 'bricks_template', 'elementor_library' ] );
		$allowed = array_values( array_filter( array_map( 'sanitize_key', $allowed ) ) );
		return in_array( $post->post_type, $allowed, true );
	}
}
