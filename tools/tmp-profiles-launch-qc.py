from pathlib import Path
import subprocess


def show(path: str) -> str:
    return subprocess.check_output(['git', 'show', f'origin/base-10e23-profiles-v1:{path}'], text=True)


def once(text: str, old: str, new: str, label: str) -> str:
    count = text.count(old)
    if count != 1:
        raise RuntimeError(f'{label}: expected 1 match, got {count}')
    return text.replace(old, new, 1)

for rel in ['src/Admin/Assets.php', 'src/Integration/CoreBlueprint.php', 'src/Plugin.php']:
    Path(rel).write_text(show(rel))
Path('src/Admin/FallbackPage.php').unlink(missing_ok=True)

p = Path('core-blueprint-profiles.php')
s = show('core-blueprint-profiles.php')
s = once(
    s,
    "define( 'CB_PROFILES_VERSION', '1.0.0' );\n",
    "define( 'CB_PROFILES_VERSION', '1.0.0' );\ndefine( 'CB_PROFILES_REQUIRED_API', '1.0' );\n",
    'required API constant',
)
old_hooks = "register_activation_hook( __FILE__, [ '\\\\CB\\\\Profiles\\\\Install', 'activate' ] );\nregister_deactivation_hook( __FILE__, [ '\\\\CB\\\\Profiles\\\\Install', 'deactivate' ] );\n"
new_hooks = """function cb_profiles_api_compatible( string $available, string $required ): bool {
\tif ( 1 !== preg_match( '/^(\\d+)\\.(\\d+)$/', $available, $a ) || 1 !== preg_match( '/^(\\d+)\\.(\\d+)$/', $required, $r ) ) {
\t\treturn false;
\t}
\treturn (int) $a[1] === (int) $r[1] && (int) $a[2] >= (int) $r[2];
}

function cb_profiles_base_ready(): bool {
\treturn defined( 'CB_CORE_API_VERSION' )
\t\t&& cb_profiles_api_compatible( (string) CB_CORE_API_VERSION, CB_PROFILES_REQUIRED_API )
\t\t&& class_exists( '\\\\CB\\\\Core\\\\ExtensionRegistry' )
\t\t&& class_exists( '\\\\CB\\\\Core\\\\Admin\\\\PageRegistry' );
}

function cb_profiles_activate(): void {
\tif ( ! cb_profiles_base_ready() ) {
\t\tif ( ! function_exists( 'deactivate_plugins' ) ) {
\t\t\trequire_once ABSPATH . 'wp-admin/includes/plugin.php';
\t\t}
\t\tdeactivate_plugins( CB_PROFILES_BASENAME );
\t\twp_die(
\t\t\tesc_html( 'Core Blueprint Profiles requires an active, Core API 1.x compatible Core Blueprint Base installation.' ),
\t\t\tesc_html( 'Core Blueprint dependency required' ),
\t\t\t[ 'back_link' => true ]
\t\t);
\t}
\t\\CB\\Profiles\\Install::activate();
}
register_activation_hook( __FILE__, 'cb_profiles_activate' );
register_deactivation_hook( __FILE__, [ '\\\\CB\\\\Profiles\\\\Install', 'deactivate' ] );
"""
s = once(s, old_hooks, new_hooks, 'activation hooks')
old_runtime = """add_action( 'plugins_loaded', static function (): void {
\tif ( ! defined( 'CB_CORE_API_VERSION' ) || version_compare( (string) CB_CORE_API_VERSION, '1.0', '<' ) ) {
\t\tadd_action( 'admin_notices', static function (): void {
\t\t\techo '<div class=\"notice notice-error\"><p><strong>Core Blueprint Profiles:</strong> ';
\t\t\techo esc_html__( 'Core Blueprint Base with API 1.0 or newer is required.', 'core-blueprint-profiles' );
\t\t\techo '</p></div>';
\t\t} );
\t\treturn;
\t}
\t\\CB\\Profiles\\Plugin::boot();
}, 20 );
"""
new_runtime = """add_action( 'plugins_loaded', static function (): void {
\tif ( ! cb_profiles_base_ready() ) {
\t\tif ( is_admin() ) {
\t\t\tadd_action( 'admin_notices', static function (): void {
\t\t\t\techo '<div class=\"notice notice-error\"><p><strong>Core Blueprint Profiles:</strong> ';
\t\t\t\techo esc_html__( 'Core Blueprint Base with API 1.0 or newer is required.', 'core-blueprint-profiles' );
\t\t\t\techo '</p></div>';
\t\t\t} );
\t\t}
\t\treturn;
\t}
\t\\CB\\Profiles\\Plugin::boot();
}, 20 );
"""
s = once(s, old_runtime, new_runtime, 'runtime Base guard')
p.write_text(s)
print('PROFILES_LAUNCH_PATCH_OK')
