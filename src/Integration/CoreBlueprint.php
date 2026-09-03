<?php
declare(strict_types=1);
namespace CB\Profiles\Integration;

use CB\Core\ExtensionRegistry;
use CB\Profiles\Admin\CoreBlueprintPage;

defined( 'ABSPATH' ) || exit;

final class CoreBlueprint {
	public static function init(): void {
		add_action( 'cb_core_register_extensions', [ __CLASS__, 'register_extension' ] );
		add_action( 'cb_core_register_pages', [ __CLASS__, 'register_page' ] );
		add_filter( 'plugin_action_links_' . CB_PROFILES_BASENAME, [ __CLASS__, 'plugin_links' ] );
	}

	public static function register_extension(): void {
		ExtensionRegistry::register( [
			'id'           => 'core-blueprint-profiles',
			'plugin_file'  => CB_PROFILES_BASENAME,
			'requires_api' => '1.0',
			'menu_url'     => admin_url( 'admin.php?page=core-blueprint-profiles' ),
		] );
	}

	public static function register_page(): void {
		if ( class_exists( '\\CB\\Core\\Admin\\PageRegistry' ) && class_exists( '\\CB\\Core\\Admin\\PageBase' ) ) {
			\CB\Core\Admin\PageRegistry::register(
				new CoreBlueprintPage(),
				[
					'components' => [ 'nav-tabs', 'cards', 'fields', 'form-controls' ],
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
