<?php
/**
 * Plugin Name:       Open Hub Client
 * Description:       Nền tảng chung cho mọi site Hub ngành của Bizcity Open: 14 module bật / tắt, hợp đồng ngành bizcity_industry_hub@2, ô mở rộng cho plugin ngành, một cửa gọi open-hub-core. Đặc tả: canon open-hub OPEN-HUB-CLIENT.md.
 * Version:           0.8.0
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

define( 'OHCL_VERSION', '0.8.0' );
define( 'OHCL_DIR', __DIR__ . '/' );

foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview', 'class-ohcl-members', 'class-ohcl-certs', 'class-ohcl-coach', 'class-ohcl-myhub', 'class-ohcl-pages', 'class-ohcl-front' ) as $ohcl_f ) {
	$ohcl_p = OHCL_DIR . 'includes/' . $ohcl_f . '.php';
	if ( ! is_file( $ohcl_p ) || ! is_readable( $ohcl_p ) ) {
		return; // a missing artifact disables the plugin quietly instead of a fatal.
	}
	require_once $ohcl_p;
}
unset( $ohcl_f, $ohcl_p );

// [2026-11-25 06:49 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1C — hồ sơ hội viên là CPT trên site Hub (không bảng mới).
add_action( 'init', array( 'OHCL_Members', 'register' ) );
add_action( 'init', array( 'OHCL_Certs', 'register' ) );
add_action( 'init', array( 'OHCL_Coach', 'register' ) ); // [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — hồ sơ ứng tuyển Coach (CPT bz_hub_coach_app).
OHCL_MyHub::boot();
OHCL_Pages::boot(); // [2026-11-25 11:53 PM Johnny Chu - Chu Hoàng Anh] OHCL-F3 — mẫu trang "Open Hub — …", bản đồ trang → link. // [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — giấy chứng nhận là CPT bz_hub_cert.

// [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — cổng cộng đồng (trang chủ, bản đồ, hồ sơ cơ sở, trở thành hội viên); xả quy tắc đường dẫn một lần khi kích hoạt.
OHCL_Front::boot();
register_activation_hook(
	__FILE__,
	static function () {
		OHCL_Front::rewrites();
		flush_rewrite_rules( false );
	}
);
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

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
	// [2026-11-25 06:49 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1C — màn 02 Hội viên + Member 360°.
	if ( is_file( OHCL_DIR . 'includes/class-ohcl-admin-members.php' ) ) {
		require_once OHCL_DIR . 'includes/class-ohcl-admin-members.php';
		OHCL_Admin_Members::boot();
	}
	// [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — màn 06 · Chứng nhận.
	if ( is_file( OHCL_DIR . 'includes/class-ohcl-admin-pages.php' ) ) { // [2026-11-25 11:53 PM Johnny Chu - Chu Hoàng Anh] OHCL-F3 — màn Trang & menu, tự tạo trang + menu một lần.
		require_once OHCL_DIR . 'includes/class-ohcl-admin-pages.php';
		OHCL_Admin_Pages::boot();
	}
	if ( is_file( OHCL_DIR . 'includes/class-ohcl-admin-coach.php' ) ) { // [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — màn 04 · Coach.
		require_once OHCL_DIR . 'includes/class-ohcl-admin-coach.php';
		OHCL_Admin_Coach::boot();
	}
	if ( is_file( OHCL_DIR . 'includes/class-ohcl-admin-certs.php' ) ) {
		require_once OHCL_DIR . 'includes/class-ohcl-admin-certs.php';
		OHCL_Admin_Certs::boot();
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI && is_file( OHCL_DIR . 'includes/class-ohcl-cli.php' ) ) {
	require_once OHCL_DIR . 'includes/class-ohcl-cli.php';
	WP_CLI::add_command( 'ohcl', 'OHCL_Cli' );
}
