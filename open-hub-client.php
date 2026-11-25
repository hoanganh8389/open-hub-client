<?php
/**
 * Plugin Name:       Open Hub Client
 * Description:       Nền tảng chung cho mọi site Hub ngành của Bizcity Open: 14 module bật / tắt, hợp đồng ngành bizcity_industry_hub@2, ô mở rộng cho plugin ngành, một cửa gọi open-hub-core. Đặc tả: canon open-hub OPEN-HUB-CLIENT.md.
 * Version:           0.3.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Johnny Chu – Chu Hoàng Anh
 * License:           GPL-2.0-or-later
 * Text Domain:       open-hub-client
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 05:38 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1 — khởi tạo plugin: hợp đồng ngành @2, registry module, ô mở rộng, một cửa gọi lõi, CLI.

defined( 'ABSPATH' ) || exit;

if ( defined( 'OHCL_VERSION' ) ) {
	return;
}

define( 'OHCL_VERSION', '0.3.0' );
define( 'OHCL_DIR', __DIR__ . '/' );

foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview' ) as $ohcl_f ) {
	$ohcl_p = OHCL_DIR . 'includes/' . $ohcl_f . '.php';
	if ( ! is_file( $ohcl_p ) || ! is_readable( $ohcl_p ) ) {
		return; // a missing artifact disables the plugin quietly instead of a fatal.
	}
	require_once $ohcl_p;
}
unset( $ohcl_f, $ohcl_p );

// Industry data problems are shown to the Hub owner in wp-admin only, never on public pages.
add_action(
	'admin_notices',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$r = OHCL_Industry::report();
		if ( $r['ok'] ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>Open Hub Client:</strong> dữ liệu ngành chưa đạt hợp đồng '
			. esc_html( OHCL_Industry::CONTRACT ) . ' — site đang chạy cấu hình mặc định. '
			. esc_html( implode( ' · ', array_slice( $r['errors'], 0, 3 ) ) )
			. ' <em>Chạy <code>wp ohcl validate-industry</code> để xem đủ.</em></p></div>';
	}
);

// [2026-11-25 06:05 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1B — màn quản trị chỉ nạp trong wp-admin.
if ( is_admin() && is_file( OHCL_DIR . 'includes/class-ohcl-admin.php' ) && is_readable( OHCL_DIR . 'includes/class-ohcl-admin.php' ) ) {
	require_once OHCL_DIR . 'includes/class-ohcl-admin.php';
	OHCL_Admin::boot();
}

if ( defined( 'WP_CLI' ) && WP_CLI && is_file( OHCL_DIR . 'includes/class-ohcl-cli.php' ) ) {
	require_once OHCL_DIR . 'includes/class-ohcl-cli.php';
	WP_CLI::add_command( 'ohcl', 'OHCL_Cli' );
}
