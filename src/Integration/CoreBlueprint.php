<?php
declare(strict_types=1);
namespace CB\Profiles\Integration;

use CB\Profiles\Admin\CoreBlueprintPage;

defined( 'ABSPATH' ) || exit;

final class CoreBlueprint {
	public static function init(): void {
		add_action( 'cb_core_register_pages', [ __CLASS__, 'register_page' ] );
		add_filter( 'plugin_action_links_' . CB_PROFILES_BASENAME, [ __CLASS__, 'plugin_links' ] );
	}

	public static function register_page(): void {
		if ( class_exists( '\\CB\\Core\\Admin\\PageRegistry' ) && class_exists( '\\CB\\Core\\Admin\\PageBase' ) ) {
			\CB\Core\Admin\PageRegistry::register( new CoreBlueprintPage() );
		}
	}

	/** @param string[] $links @return string[] */
	public static function plugin_links( array $links ): array {
		$url = defined( 'CB_CORE_VERSION' ) ? admin_url( 'admin.php?page=core-blueprint-profiles' ) : admin_url( 'options-general.php?page=core-blueprint-profiles' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'core-blueprint-profiles' ) . '</a>' );
		return $links;
	}
}
