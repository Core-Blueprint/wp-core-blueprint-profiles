<?php
declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['profiles_base_ready'] = false;
$GLOBALS['profiles_meta_updates'] = [];
$GLOBALS['profiles_user_meta'] = [ 7 => [ '_cb_profiles_public_slug' => 'friendly-id' ] ];
$GLOBALS['profiles_user'] = new WP_User( 7 );

class WP_User {
	public function __construct(
		public int $ID,
		public array $roles = [ 'subscriber' ],
		public string $user_login = 'editor',
		public string $user_email = 'editor@example.test',
		public string $user_nicename = 'editor'
	) {}
}
class WP_Error {
	public function __construct( public string $code, public string $message = '' ) {}
}
class WP_REST_Request {}

function cb_profiles_base_ready(): bool { return $GLOBALS['profiles_base_ready']; }
function cb_profiles_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}
function sanitize_title( string $value ): string { return strtolower( trim( $value ) ); }
function get_userdata( int $user_id ): WP_User|false {
	return 7 === $user_id ? $GLOBALS['profiles_user'] : false;
}
function get_user_meta( int $user_id, string $key, bool $single = true ): mixed {
	return $GLOBALS['profiles_user_meta'][ $user_id ][ $key ] ?? '';
}
function update_user_meta( int $user_id, string $key, mixed $value ): void {
	$GLOBALS['profiles_meta_updates'][] = [ $user_id, $key, $value ];
	$GLOBALS['profiles_user_meta'][ $user_id ][ $key ] = $value;
}
function get_users( array $args ): array {
	if ( '_cb_profiles_public_slug' !== ( $args['meta_key'] ?? '' ) ) {
		throw new RuntimeException( 'Unexpected user query.' );
	}
	return 'friendly-id' === ( $args['meta_value'] ?? '' ) ? [ 7 ] : [];
}
function get_option( string $key, mixed $fallback = false ): mixed {
	return 'cb_profiles_settings' === $key
		? [ 'enabled' => true, 'visibility' => 'public', 'excluded_roles' => [] ]
		: $fallback;
}
function wp_parse_args( array $args, array $defaults ): array { return array_merge( $defaults, $args ); }
function is_author(): bool { return true; }
function get_queried_object(): WP_User { return $GLOBALS['profiles_user']; }
function get_current_user_id(): int { return 0; }
function apply_filters( string $hook, mixed $value, mixed ...$args ): mixed { return $value; }
function __( string $text, string $domain = '' ): string { return $text; }
function is_email( string $value ): bool { return false !== filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function is_wp_error( mixed $value ): bool { return $value instanceof WP_Error; }
function current_user_can( string $capability, mixed ...$args ): bool { return false; }

$root = dirname( __DIR__ );
foreach ( [
	'src/Settings.php',
	'src/ProfilePolicy.php',
	'src/ProfileSlug.php',
	'src/Routing.php',
	'src/Frontend/Queries.php',
	'src/Frontend/Conditions.php',
	'src/UserProfile.php',
	'src/DeniedRenderer.php',
] as $file ) {
	require_once $root . '/' . $file;
}

use CB\Profiles\DeniedRenderer;
use CB\Profiles\Frontend\Conditions;
use CB\Profiles\Frontend\Queries;
use CB\Profiles\ProfilePolicy;
use CB\Profiles\ProfileSlug;
use CB\Profiles\Routing;
use CB\Profiles\UserProfile;

cb_profiles_assert( false === ProfilePolicy::current_profile_user(), 'Current user must be hidden when Base is unavailable.' );
cb_profiles_assert( ! ProfilePolicy::is_profile_available( 7 ), 'Policy must fail closed without Base.' );
cb_profiles_assert( ! ProfilePolicy::can_view( 7 ), 'Can-view must fail closed without Base.' );
cb_profiles_assert( '' === ProfileSlug::get( 7 ), 'Slug helper must not expose a slug without Base.' );
cb_profiles_assert( 0 === ProfileSlug::find_user_id( 'friendly-id' ), 'Slug lookup must be inert without Base.' );
cb_profiles_assert( '' === ProfileSlug::generate_and_store( 7 ), 'Slug generation must not write without Base.' );
cb_profiles_assert( ProfileSlug::set( 7, 'new-slug' ) instanceof WP_Error, 'Slug setter must refuse writes without Base.' );
cb_profiles_assert( [] === $GLOBALS['profiles_meta_updates'], 'Dependency loss must not mutate profile metadata.' );
cb_profiles_assert( [] === Queries::profiles(), 'Public profile queries must be inert without Base.' );
cb_profiles_assert( ! Conditions::is_own_profile( 7 ), 'Own-profile condition must be inert without Base.' );
cb_profiles_assert( '404' === ( Routing::resolve_request( [ 'cb_profile' => 'friendly-id' ] )['error'] ?? '' ), 'Request must return 404 without Base.' );
cb_profiles_assert( [ -1 ] === ( DeniedRenderer::sitemap_user_args( [] )['include'] ?? null ), 'Sitemaps must fail closed.' );
cb_profiles_assert( [ -1 ] === ( DeniedRenderer::rest_user_args( [], new WP_REST_Request() )['include'] ?? null ), 'Public REST listing must fail closed.' );
UserProfile::save( 7 );
cb_profiles_assert( [] === $GLOBALS['profiles_meta_updates'], 'Direct user profile save must not mutate without Base.' );

$GLOBALS['profiles_base_ready'] = true;
cb_profiles_assert( ProfilePolicy::current_profile_user() instanceof WP_User, 'Public profile context must recover with Base.' );
cb_profiles_assert( ProfilePolicy::can_view( 7 ), 'Existing public profile must remain viewable with Base.' );
cb_profiles_assert( 'friendly-id' === ProfileSlug::get( 7 ), 'Stored public slug must remain unchanged.' );
cb_profiles_assert( 7 === ProfileSlug::find_user_id( 'friendly-id' ), 'Canonical stored slug must resolve.' );
cb_profiles_assert( 7 === ( Routing::resolve_request( [ 'cb_profile' => 'friendly-id' ] )['author'] ?? null ), 'Canonical route must resolve.' );
cb_profiles_assert( '404' === ( Routing::resolve_request( [ 'cb_profile' => 'editor' ] )['error'] ?? '' ), 'Native user_nicename must never resolve as Profiles alias.' );
cb_profiles_assert( [] === $GLOBALS['profiles_meta_updates'], 'Canonical route must not rewrite existing slugs.' );

$GLOBALS['profiles_base_ready'] = false;
cb_profiles_assert( ! ProfilePolicy::can_view( 7 ), 'Policy must recover fail-closed semantics after Base loss.' );

fwrite( STDOUT, "Profiles dependency-loss and canonical-routing runtime regression: PASS\n" );
