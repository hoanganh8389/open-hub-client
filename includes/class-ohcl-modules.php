<?php
/**
 * Registry of the 14 standard Hub modules (OPEN-HUB-CLIENT §2).
 *
 * Fixed list: an industry plugin may switch optional modules on / off and rename labels through
 * terms, never add a module (R-HUB-14). Order of decision for an optional module:
 * Hub owner's choice (option ohcl_modules) > industry default (contract @2 `modules`) > on.
 * Required modules (01, 02, 03, 12, 14) are always on. Turning a module off hides it; data stays.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 05:38 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1 — registry 14 module, bật / tắt theo chủ Hub > ngành > mặc định.

defined( 'ABSPATH' ) || exit;

final class OHCL_Modules {

	const OPTION = 'ohcl_modules';

	const LIST = array(
		'01' => array( 'label' => 'Tổng quan Hub', 'required' => true ),
		'02' => array( 'label' => 'Quản lý hội viên', 'required' => true ),
		'03' => array( 'label' => 'Công nghệ & gói tặng', 'required' => true ),
		'04' => array( 'label' => 'Coach & chuyên gia', 'required' => false ),
		'05' => array( 'label' => 'Học viện Hub', 'required' => false ),
		'06' => array( 'label' => 'Xác minh & chứng nhận', 'required' => false ),
		'07' => array( 'label' => 'Danh bạ & bản đồ ngành', 'required' => false ),
		'08' => array( 'label' => 'Marketing & thị trường', 'required' => false ),
		'09' => array( 'label' => 'Sự kiện & cộng đồng', 'required' => false ),
		'10' => array( 'label' => 'Đối tác & nhà tài trợ', 'required' => false ),
		'11' => array( 'label' => 'Kho tri thức ngành', 'required' => false ),
		'12' => array( 'label' => 'Kinh doanh & doanh thu', 'required' => true ),
		'13' => array( 'label' => 'Báo cáo & tác động', 'required' => false ),
		'14' => array( 'label' => 'Cài đặt & phân quyền', 'required' => true ),
	);

	public static function exists( $id ) {
		return isset( self::LIST[ (string) $id ] );
	}

	public static function is_required( $id ) {
		return self::exists( $id ) && self::LIST[ (string) $id ]['required'];
	}

	/**
	 * Pure decision, testable without WordPress.
	 *
	 * @param string $id            module id '01'…'14'.
	 * @param array  $owner         owner choices id => bool.
	 * @param array  $industry      industry defaults id => bool.
	 * @return bool
	 */
	public static function decide( $id, array $owner, array $industry ) {
		$id = (string) $id;
		if ( ! self::exists( $id ) ) {
			return false;
		}
		if ( self::is_required( $id ) ) {
			return true;
		}
		if ( array_key_exists( $id, $owner ) ) {
			return (bool) $owner[ $id ];
		}
		if ( array_key_exists( $id, $industry ) ) {
			return (bool) $industry[ $id ];
		}
		return true;
	}

	/** Runtime: is this module on for the current site? */
	public static function enabled( $id ) {
		$owner = get_option( self::OPTION );
		$cfg   = OHCL_Industry::current();
		return self::decide( $id, is_array( $owner ) ? $owner : array(), $cfg['modules'] );
	}

	/** id => {label, required, enabled, source} for the settings screen and CLI. */
	public static function all() {
		$owner = get_option( self::OPTION );
		$owner = is_array( $owner ) ? $owner : array();
		$cfg   = OHCL_Industry::current();
		$out   = array();
		foreach ( self::LIST as $id => $m ) {
			$src = $m['required'] ? 'required' : ( array_key_exists( $id, $owner ) ? 'owner' : ( array_key_exists( $id, $cfg['modules'] ) ? 'industry' : 'default' ) );
			$out[ $id ] = array(
				'label'    => $m['label'],
				'required' => $m['required'],
				'enabled'  => self::decide( $id, $owner, $cfg['modules'] ),
				'source'   => $src,
			);
		}
		return $out;
	}

	/**
	 * Hub owner switches an optional module. Caller checks capability.
	 *
	 * @return true|WP_Error
	 */
	public static function set_enabled( $id, $on ) {
		$id = (string) $id;
		if ( ! self::exists( $id ) ) {
			return OHCL_Error::make( 'ohcl_module_unknown', 'Không có module ' . $id . '.', 'Chọn một module từ 01 đến 14.', 404 );
		}
		if ( self::is_required( $id ) && ! $on ) {
			return OHCL_Error::make( 'ohcl_module_required', 'Module "' . self::LIST[ $id ]['label'] . '" là bắt buộc, không tắt được.', 'Giữ module này bật; tắt các module tuỳ chọn khác nếu cần.', 409 );
		}
		$owner        = get_option( self::OPTION );
		$owner        = is_array( $owner ) ? $owner : array();
		$owner[ $id ] = (bool) $on;
		update_option( self::OPTION, $owner, false );
		return true;
	}
}
