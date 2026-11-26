<?php
/**
 * Data ownership (HUB-THANH-CONG §5.7): a member exports what the Hub holds about them; the Hub Owner exports
 * the member list. Neither package ever holds a key, a secret, another member's data, or the owner's private
 * notes — and each export is written to the audit log.
 *
 *   member-export@1   one member: profile, consents, journey, benefits, certificates, reported results, own
 *                     support requests, this month's measured impact, key STATUS (prefix, plan) — not the key
 *   member-list@1     the Hub's members: profile, status, journey, consents, key status; CSV or JSON
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 04:31 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6 — xuất dữ liệu: gói của hội viên và danh sách của Hub; không khoá, không bí mật, có nhật ký.

defined( 'ABSPATH' ) || exit;

final class OHCL_Export {

	const MEMBER_FORMAT = 'open-hub-client/member-export@1';
	const LIST_FORMAT   = 'open-hub-client/member-list@1';

	/* ===================== pure ===================== */

	/** One CSV cell: formula-injection safe (= + - @ at the start get a leading apostrophe), quoted. Pure. */
	public static function csv_cell( $v ) {
		$v = is_array( $v ) ? implode( '; ', array_map( 'strval', $v ) ) : (string) $v;
		$v = str_replace( array( "\r\n", "\r", "\n" ), ' ', $v );
		if ( '' !== $v && false !== strpos( "=+-@\t", $v[0] ) ) {
			$v = "'" . $v;
		}
		return '"' . str_replace( '"', '""', $v ) . '"';
	}

	/** CSV text with UTF-8 BOM (opens right in Excel). @param array[] $rows list of assoc rows with the same keys. */
	public static function csv( array $headers, array $rows ) {
		$out = "\xEF\xBB\xBF" . implode( ',', array_map( array( __CLASS__, 'csv_cell' ), $headers ) ) . "\r\n";
		foreach ( $rows as $r ) {
			$line = array();
			foreach ( array_keys( $headers ) as $k ) {
				$line[] = self::csv_cell( isset( $r[ $k ] ) ? $r[ $k ] : '' );
			}
			$out .= implode( ',', $line ) . "\r\n";
		}
		return $out;
	}

	/** Flat row of one member for the list export. Pure. */
	public static function list_row( array $m, $key, array $ind, $now_ts ) {
		$v   = OHCL_Service::member_view( $m, $key, $ind, $now_ts, true, false );
		$row = array(
			'code' => $m['code'], 'title' => $m['title'], 'status' => $m['status'], 'segment' => $v['seg_label'], 'province' => $m['province'], 'address' => $m['address'],
			'phone' => $m['phone'], 'email' => $m['email'], 'contact_zalo' => $m['contact_zalo'], 'site_url' => $m['site_url'], 'joined_at' => $m['joined_at'], 'accepted_at' => $m['accepted_at'],
			'journey' => $v['journey']['label'], 'ai' => $v['ai']['label'], 'map_level' => $v['map_level']['label'], 'verified' => $v['verified'] ? 'có' : 'không',
			'certs' => implode( '; ', $v['certs'] ), 'coach' => $m['coach'], 'public_consent' => $v['public_consent'] ? 'có' : 'không',
			'key_status' => $v['key'] ? $v['key']['status'] : '', 'key_prefix' => $v['key'] ? $v['key']['prefix'] : '',
		);
		foreach ( $ind['member_fields'] as $f ) {
			$val = isset( $m['industry'][ $f['key'] ] ) ? $m['industry'][ $f['key'] ] : '';
			if ( is_array( $val ) ) {
				$val = array_map( function ( $x ) use ( $f ) { return isset( $f['options'][ $x ] ) ? $f['options'][ $x ] : $x; }, $val );
			} elseif ( 'select' === $f['type'] && isset( $f['options'][ $val ] ) ) {
				$val = $f['options'][ $val ];
			}
			$row[ 'f_' . $f['key'] ] = $val;
		}
		return $row;
	}

	public static function list_headers( array $ind ) {
		$h = array( 'code' => 'Mã', 'title' => 'Tên', 'status' => 'Trạng thái', 'segment' => 'Loại hình', 'province' => 'Tỉnh / thành', 'address' => 'Địa chỉ', 'phone' => 'Điện thoại', 'email' => 'Email', 'contact_zalo' => 'Zalo', 'site_url' => 'Website', 'joined_at' => 'Ngày vào', 'accepted_at' => 'Ngày tiếp nhận', 'journey' => 'Hành trình', 'ai' => 'Trợ lý AI', 'map_level' => 'Bản đồ', 'verified' => 'Đã xác minh', 'certs' => 'Chứng nhận', 'coach' => 'Coach', 'public_consent' => 'Đồng ý công khai', 'key_status' => 'Trạng thái khoá', 'key_prefix' => 'Khoá (tiền tố)' );
		foreach ( $ind['member_fields'] as $f ) {
			$h[ 'f_' . $f['key'] ] = $f['label'];
		}
		return $h;
	}

	/**
	 * The member's own export. Pure.
	 *
	 * @param array      $m       to_array() row of THE MEMBER ONLY.
	 * @param array|null $key     key view (status / plan only).
	 * @param array      $benefits OHCL_Benefits::for_member() result.
	 * @param array      $impact  OHCL_Service::impact().
	 * @param array      $support the member's own support requests.
	 */
	public static function member_package( array $m, $key, array $ind, array $benefits, array $impact, array $support, $now_ts ) {
		$v   = OHCL_Service::member_view( $m, $key, $ind, $now_ts, true, false );
		$oc  = OHCL_Service::outcomes_view( $m );
		$ben = array();
		foreach ( $benefits as $b ) {
			$ben[] = array( 'benefit' => $b['key'], 'label' => $b['label'], 'state' => $b['state'], 'availability' => $b['availability'] );
		}
		$ver = is_array( $m['verify'] ) ? array( 'until' => isset( $m['verify']['until'] ) ? $m['verify']['until'] : '', 'evidence' => isset( $m['verify']['evidence'] ) ? (array) $m['verify']['evidence'] : array() ) : null;
		return array(
			'format'      => self::MEMBER_FORMAT,
			'exported_at' => gmdate( 'Y-m-d H:i:s', $now_ts ) . ' UTC',
			'hub'         => array( 'name' => $ind['name'], 'industry' => $ind['industry'] ),
			'member'      => array( 'code' => $m['code'], 'title' => $m['title'], 'status' => $m['status'], 'joined_at' => $m['joined_at'], 'accepted_at' => $m['accepted_at'], 'segment' => $m['segment'], 'province' => $m['province'] ),
			'profile'     => $v['profile'],
			'industry_fields' => $v['fields'],
			'consents'    => array( 'public_profile' => (string) $m['consent'], 'shop_products' => (string) $m['shop_consent'], 'reported_results' => is_array( $m['outcomes'] ) && isset( $m['outcomes']['consent_at'] ) ? (string) $m['outcomes']['consent_at'] : '' ),
			'journey'     => $v['journey'],
			'ai'          => $v['ai'],
			'ai_key'      => $v['key'], // trạng thái, gói, tiền tố — KHÔNG có khoá
			'map_level'   => $v['map_level'],
			'verification' => $ver,
			'certificates' => $v['certs'],
			'benefits'    => $ben,
			'reported_results' => array( 'status' => $oc['status'], 'items' => $oc['items'] ),
			'support_requests' => $support,
			'impact_this_month' => $impact,
			'not_included' => array( 'Khoá trợ lý đầy đủ (chỉ hiện một lần lúc cấp)', 'Ghi chú nội bộ của ban điều hành', 'Dữ liệu của hội viên khác', 'Số liệu kinh doanh trong site của bạn (Hub không đọc)' ),
		);
	}

	/** The Hub's member list as one package. Pure. */
	public static function list_package( array $rows, array $ind, $now_ts ) {
		return array( 'format' => self::LIST_FORMAT, 'exported_at' => gmdate( 'Y-m-d H:i:s', $now_ts ) . ' UTC', 'hub' => array( 'name' => $ind['name'], 'industry' => $ind['industry'] ), 'count' => count( $rows ), 'members' => $rows, 'not_included' => array( 'Khoá trợ lý đầy đủ', 'Nội dung yêu cầu hỗ trợ', 'Ghi chú nội bộ' ) );
	}

	/* ===================== WordPress ===================== */

	/** The member's own package; logs the export. */
	public static function for_member( array $m ) {
		$now  = time();
		$keys = OHCL_Members::keys_by_id();
		$key  = $m['key_id'] && isset( $keys[ $m['key_id'] ] ) ? $keys[ $m['key_id'] ] : null;
		$sup  = get_post_meta( $m['id'], '_bzm_support', true );
		$ctx  = OHCL_Benefits::context( $now );
		$pkg  = self::member_package( $m, $key, OHCL_Industry::current(), OHCL_Benefits::for_member( $m, $key, $ctx ), OHCL_Service::impact( $m, $now ), is_array( $sup ) ? array_values( $sup ) : array(), $now );
		OHCL_Audit::log( 'export_member', $m['id'], '', 0 );
		return $pkg;
	}

	/** Rows of every member (sample businesses excluded by OHCL_Members::all()). @return array[] */
	public static function hub_rows() {
		$now  = time();
		$ind  = OHCL_Industry::current();
		$keys = OHCL_Members::keys_by_id();
		$rows = array();
		foreach ( OHCL_Members::all() as $m ) {
			$rows[] = self::list_row( $m, $m['key_id'] && isset( $keys[ $m['key_id'] ] ) ? $keys[ $m['key_id'] ] : null, $ind, $now );
		}
		return $rows;
	}

	/** JSON package for the Hub Owner; logs the export. */
	public static function for_hub() {
		$rows = self::hub_rows();
		OHCL_Audit::log( 'export_hub', 0, count( $rows ) . ' hồ sơ (JSON)' );
		return self::list_package( $rows, OHCL_Industry::current(), time() );
	}

	/** CSV text for the Hub Owner; logs the export. */
	public static function for_hub_csv() {
		$rows = self::hub_rows();
		OHCL_Audit::log( 'export_hub', 0, count( $rows ) . ' hồ sơ (CSV)' );
		return self::csv( self::list_headers( OHCL_Industry::current() ), $rows );
	}
}
