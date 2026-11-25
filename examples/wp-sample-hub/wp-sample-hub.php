<?php
/**
 * Plugin Name:       Sample Hub (mẫu ngành)
 * Description:       Khuôn cho một plugin ngành wp-<ngành>-hub theo hợp đồng bizcity_industry_hub@2. Chỉ dữ liệu + ô hiển thị; không khoá, không tiền, không gọi lõi / Cell / Zalo / LLM. Chép thư mục này, đổi tên và dữ liệu.
 * Version:           0.1.0
 * Requires PHP:      7.4
 * Requires Plugins:  open-hub-client
 * License:           GPL-2.0-or-later
 *
 * @package Wp_Sample_Hub
 */
// [2026-11-25 05:38 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1 — ngành mẫu: khuôn cho dev ngành mới và dữ liệu cho bộ kiểm.

defined( 'ABSPATH' ) || exit;

/**
 * 1. Dữ liệu ngành — vùng tự do (OPEN-HUB-CLIENT §10.2). Trả MỘT mảng.
 */
function wp_sample_hub_industry() {
	return array(
		'contract'           => 'bizcity_industry_hub@2',
		'id'                 => 'sample',
		'name'               => 'Sample Hub',
		'industry'           => 'Ngành mẫu',
		'leader'             => '',
		'brand'              => array( 'accent' => '#2f5d8a', 'logo' => '', 'slogan' => 'Cùng nhau phát triển ngành' ),
		'modules'            => array( '09' => false ),
		'terms'              => array( 'business' => 'cửa hàng', 'product' => 'sản phẩm' ),
		'segments'           => array( 'cua-hang' => 'Cửa hàng', 'nha-phan-phoi' => 'Nhà phân phối' ),
		'member_fields'      => array(
			array( 'key' => 'nhom_hang', 'label' => 'Nhóm hàng chính', 'type' => 'multi', 'options' => array( 'gia-dung' => 'Gia dụng', 'thuc-pham' => 'Thực phẩm' ), 'public' => true ),
			array( 'key' => 'nam_thanh_lap', 'label' => 'Năm thành lập', 'type' => 'number', 'public' => true ),
		),
		'standards'          => array(
			array( 'code' => 'hang-chinh-hang', 'label' => 'Hàng chính hãng', 'criteria' => array( 'Có hoá đơn đầu vào', 'Có tem truy xuất' ), 'evidence' => array( 'Ảnh hoá đơn', 'Ảnh tem' ), 'validity_months' => 12 ),
		),
		'coach_skills'       => array( array( 'code' => 'ke-toan', 'label' => 'Kế toán cửa hàng' ) ),
		'academy_tracks'     => array( array( 'code' => 'chuyen-doi-so', 'label' => 'Chuyển đổi số 30 ngày' ) ),
		'campaign_templates' => array( array( 'code' => 'tuan-le-nganh', 'label' => 'Tuần lễ ngành' ) ),
		'apps'               => array( 'open-pos', 'open-inventory', 'open-ke-toan', 'open-brain' ),
		'trial_days'         => 30,
		'knowledge_assets'   => array(
			array( 'code' => 'sop-nhap-hang', 'label' => 'SOP nhập hàng', 'type' => 'sop', 'description' => 'Quy trình nhập, kiểm, lưu kho.' ),
		),
		'plugin'             => 'wp-sample-hub',
		'version'            => '0.1.0',
	);
}
add_filter( 'open_hub_client_industry', 'wp_sample_hub_industry' );

/**
 * 2. Ô mở rộng — chỉ hiển thị (OPEN-HUB-CLIENT §10.3). Không ghi gì vào lõi.
 */
add_filter(
	'ohcl_member360_tabs',
	static function ( $tabs ) {
		$tabs[] = array(
			'id'     => 'nhom-hang',
			'label'  => 'Nhóm hàng',
			'render' => static function ( $member ) {
				return '<p>Nhóm hàng của cửa hàng hiển thị ở đây.</p>';
			},
		);
		return $tabs;
	}
);

/**
 * 3. Sự kiện — chỉ đọc (OPEN-HUB-CLIENT §10.4). Ví dụ: ghi lời chào vào nội dung persona.
 */
add_action(
	'ohcl_member_verified',
	static function ( $payload ) {
		// Gửi email chúc mừng, cập nhật trang persona… Không sửa kết quả của lõi.
	}
);
