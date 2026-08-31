<?php
declare(strict_types=1);
namespace CB\Profiles\Admin;
use CB\Profiles\Capabilities;
defined( 'ABSPATH' ) || exit;

final class CoreBlueprintPage extends \CB\Core\Admin\PageBase {
	public function slug(): string { return 'core-blueprint-profiles'; }
	public function title(): string { return __( 'Profiles', 'core-blueprint-profiles' ); }
	public function menu_title(): string { return __( 'Profiles', 'core-blueprint-profiles' ); }
	public function capability(): string { return Capabilities::MANAGE; }
	public function position(): ?int { return 146; }
	public function render(): void { $this->guard(); PageContent::render(); }
}
