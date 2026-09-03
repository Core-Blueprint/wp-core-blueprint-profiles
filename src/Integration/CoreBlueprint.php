<?php
declare(strict_types=1);
namespace CB\Profiles\Integration;

use CB\Core\Dashboard\CardRegistry;
use CB\Core\ExtensionRegistry;
use CB\Profiles\Admin\CoreBlueprintPage;
use CB\Profiles\Capabilities;
use CB\Profiles\Settings;

defined( 'ABSPATH' ) || exit;

final class CoreBlueprint {
	private static bool $initialized = false;

	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}
		self::$initialized = true;

		add_action( 'cb_core_register_extensions', [ __CLASS__, 'register_extension' ] );
		add_filter( 'cb_core_module_status_definitions', [ __CLASS__, 'register_status_definition' ] );
		add_action( 'cb_core_register_pages', [ __CLASS__, 'register_page' ] );
		add_action( 'cb_core_dashboard_register_cards', [ __CLASS__, 'register_dashboard_shortcuts' ] );
		add_filter( 'plugin_action_links_' . CB_PROFILES_BASENAME, [ __CLASS__, 'plugin_links' ] );
	}

	public static function register_extension(): void {
		if ( ! class_exists( ExtensionRegistry::class ) ) {
			return;
		}

		ExtensionRegistry::register( [
			'id'           => 'core-blueprint-profiles',
			'plugin_file'  => CB_PROFILES_BASENAME,
			'requires_api' => '1.0',
			'menu_url'     => admin_url( 'admin.php?page=core-blueprint-profiles' ),
			'status_id'    => 'profiles',
		] );
	}

	/** @param array<string,array<string,mixed>> $definitions
	 *  @return array<string,array<string,mixed>>
	 */
	public static function register_status_definition( array $definitions ): array {
		$definitions['profiles'] = [
			'provider' => [ __CLASS__, 'extension_status' ],
			'label'    => __( 'Profiles', 'core-blueprint-profiles' ),
			'url'      => admin_url( 'admin.php?page=core-blueprint-profiles' ),
		];
		return $definitions;
	}

	/** @return array{state:string,detail:string,url:string} */
	public static function extension_status(): array {
		$settings = Settings::all();
		$url      = admin_url( 'admin.php?page=core-blueprint-profiles' );

		if ( empty( $settings['enabled'] ) ) {
			return [
				'state'  => 'off',
				'detail' => __( 'Profile pages are currently disabled.', 'core-blueprint-profiles' ),
				'url'    => $url,
			];
		}

		if ( 'logged_in' === (string) ( $settings['visibility'] ?? 'public' ) ) {
			return [
				'state'  => 'ok',
				'detail' => __( 'Profiles are limited to logged-in users.', 'core-blueprint-profiles' ),
				'url'    => $url,
			];
		}

		return [
			'state'  => 'ok',
			'detail' => __( 'Profiles are currently visible to everyone.', 'core-blueprint-profiles' ),
			'url'    => $url,
		];
	}

	public static function register_dashboard_shortcuts(): void {
		if ( ! class_exists( CardRegistry::class ) ) {
			return;
		}

		CardRegistry::register_shortcut( 'core-blueprint-profiles', [
			'id'         => 'settings',
			'label'      => __( 'Settings', 'core-blueprint-profiles' ),
			'url'        => admin_url( 'admin.php?page=core-blueprint-profiles' ),
			'capability' => Capabilities::MANAGE,
			'order'      => 10,
		] );
	}

	public static function register_page(): void {
		if ( class_exists( '\CB\Core\Admin\PageRegistry' ) && class_exists( '\CB\Core\Admin\PageBase' ) ) {
			\CB\Core\Admin\PageRegistry::register(
				new CoreBlueprintPage(),
				[
					'components' => [ 'nav-tabs', 'cards', 'fields', 'form-controls', 'disclosure' ],
				]
			);
		}
	}

	/** @param string[] $links @return string[] */
	public static function plugin_links( array $links ): array {
		$url = admin_url( 'admin.php?page=core-blueprint-profiles' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'core-blueprint-profiles' ) . '</a>' );
		return $links;
	}
}
