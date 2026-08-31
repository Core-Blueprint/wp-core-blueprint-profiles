<?php
declare(strict_types=1);
namespace CB\Profiles\Admin;
use CB\Profiles\Capabilities;
defined( 'ABSPATH' ) || exit;

final class FallbackPage {
	public static function init(): void { add_action( 'admin_menu', [ __CLASS__, 'menu' ] ); }
	public static function menu(): void {
		if ( defined( 'CB_CORE_VERSION' ) ) { return; }
		add_options_page( __( 'Core Blueprint Profiles', 'core-blueprint-profiles' ), __( 'Profiles', 'core-blueprint-profiles' ), Capabilities::MANAGE, 'core-blueprint-profiles', [ __CLASS__, 'render' ] );
	}
	public static function render(): void {
		if ( ! current_user_can( Capabilities::MANAGE ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage Profiles.', 'core-blueprint-profiles' ) );
		}
		PageContent::render();
	}
}
