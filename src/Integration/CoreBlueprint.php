<?php
declare(strict_types=1);
namespace CB\Profiles\Integration;

use CB\Core\Admin\SettingsRegistry;
use CB\Core\Dashboard\CardRegistry;
use CB\Core\ExtensionRegistry;
use CB\Profiles\Admin\PageContent;
use CB\Profiles\Capabilities;
use CB\Profiles\Settings;

defined( 'ABSPATH' ) || exit;

final class CoreBlueprint {
	public const ID = 'core-blueprint-profiles';

	private static bool $initialized = false;

	public static function init(): void {
		if ( ! self::runtime_ready() || self::$initialized ) {
			return;
		}
		self::$initialized = true;

		add_action( 'cb_core_register_extensions', [ __CLASS__, 'register_extension' ] );
		add_filter( 'cb_core_module_status_definitions', [ __CLASS__, 'register_status_definition' ] );
		add_action( 'cb_core_register_settings', [ __CLASS__, 'register_settings_provider' ] );
		add_action( 'cb_core_dashboard_register_cards', [ __CLASS__, 'register_dashboard_shortcuts' ] );
		add_filter( 'plugin_action_links_' . CB_PROFILES_BASENAME, [ __CLASS__, 'plugin_links' ] );
	}

	public static function register_extension(): void {
		if ( ! self::runtime_ready() || ! class_exists( ExtensionRegistry::class ) || ! class_exists( SettingsRegistry::class ) ) {
			return;
		}

		ExtensionRegistry::register( [
			'id'           => self::ID,
			'plugin_file'  => CB_PROFILES_BASENAME,
			'requires_api' => '1.0',
			'menu_url'     => SettingsRegistry::url( self::ID ),
			'status_id'    => 'profiles',
		] );
	}

	public static function register_settings_provider(): void {
		if ( ! self::runtime_ready() || ! class_exists( SettingsRegistry::class ) ) {
			return;
		}

		SettingsRegistry::register(
			self::ID,
			[
				'label'       => __( 'Profiles', 'core-blueprint-profiles' ),
				'description' => __( 'Create privacy-aware profile pages for your WordPress users. Choose who can view them, how profile URLs are created, and which users should have a profile.', 'core-blueprint-profiles' ),
				'group'       => SettingsRegistry::GROUP_COMMUNITY,
				'capability'  => Capabilities::MANAGE,
				'renderer'    => [ PageContent::class, 'render' ],
				'requirements' => [
					'components' => [ 'nav-tabs', 'cards', 'fields', 'form-controls', 'disclosure' ],
				],
			]
		);
	}

	/** @param array<string,array<string,mixed>> $definitions
	 *  @return array<string,array<string,mixed>>
	 */
	public static function register_status_definition( array $definitions ): array {
		if ( ! self::runtime_ready() || ! class_exists( SettingsRegistry::class ) ) {
			return $definitions;
		}
		$definitions['profiles'] = [
			'provider' => [ __CLASS__, 'extension_status' ],
			'label'    => __( 'Profiles', 'core-blueprint-profiles' ),
			'url'      => SettingsRegistry::url( self::ID ),
		];
		return $definitions;
	}

	/** @return array{state:string,detail:string,url:string} */
	public static function extension_status(): array {
		if ( ! self::runtime_ready() || ! class_exists( SettingsRegistry::class ) ) {
			return [
				'state'  => 'off',
				'detail' => function_exists( 'cb_profiles_dependency_message' ) ? \cb_profiles_dependency_message() : 'Core Blueprint Profiles runtime is unavailable.',
				'url'    => '',
			];
		}

		$settings = Settings::all();
		$url      = SettingsRegistry::url( self::ID );

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
		if ( ! self::runtime_ready() || ! class_exists( CardRegistry::class ) || ! class_exists( SettingsRegistry::class ) ) {
			return;
		}

		CardRegistry::register_shortcut( self::ID, [
			'id'         => 'settings',
			'label'      => __( 'Settings', 'core-blueprint-profiles' ),
			'url'        => SettingsRegistry::url( self::ID ),
			'capability' => Capabilities::MANAGE,
			'order'      => 10,
		] );
	}

	/** @param string[] $links @return string[] */
	public static function plugin_links( array $links ): array {
		if ( ! self::runtime_ready() || ! class_exists( SettingsRegistry::class ) ) {
			return $links;
		}

		$url = SettingsRegistry::url( self::ID );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'core-blueprint-profiles' ) . '</a>' );
		return $links;
	}

	private static function runtime_ready(): bool {
		return function_exists( 'cb_profiles_runtime_ready' ) && \cb_profiles_runtime_ready();
	}
}
