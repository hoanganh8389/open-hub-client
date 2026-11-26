<?php
/**
 * "Bắt đầu" — set a Hub up in four steps (HUB-THANH-CONG §5.6): Kết nối → Ngành → Nội dung → Mở cửa.
 *
 * Every step's state is computed from real data (nothing is stored as "ticked"): the core link is configured, the
 * industry data passes contract @2, the Hub pages and the affiliate policy exist, and at least one real member has
 * been accepted, holds a key and has used a benefit. When all four are done the Hub is *activated*: the event
 * `hub_activated` fires once (the date is kept in the option `ohcl_hub_activated_at`).
 *
 * The same checks feed `wp ohcl doctor`.
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 10:39 PM Johnny Chu - Chu Hoàng Anh] OHCL-H5 — màn "Bắt đầu" 4 bước tính từ dữ liệu thật, doctor, sự kiện hub_activated.

defined( 'ABSPATH' ) || exit;

final class OHCL_Onboarding {

	const ACTIVATED_OPTION = 'ohcl_hub_activated_at';

	/**
	 * The four steps from a set of facts. Pure.
	 *
	 * @param array $f {
	 *   core: {base_url:string, hub:string, has_secret:bool}, industry_ok:bool, industry_errors:int,
	 *   pages_total:int, pages_made:int, policy_set:bool,
	 *   members_real:int, members_accepted:int, keys_issued:int, benefit_used:bool|null (null = could not be checked)
	 * }
	 * @return array{steps:array[], done:int, percent:int, activated:bool}
	 */
	public static function steps( array $f ) {
		$core = $f['core'];
		$s1c  = array(
			array( 'label' => 'Địa chỉ lõi Bizcity đã nhập', 'ok' => '' !== (string) $core['base_url'] ),
			array( 'label' => 'Mã Hub đã nhập', 'ok' => '' !== (string) $core['hub'] ),
			array( 'label' => 'Bí mật ghép nối đã lưu', 'ok' => ! empty( $core['has_secret'] ) ),
		);
		$s2c  = array(
			array( 'label' => 'Dữ liệu ngành đạt hợp đồng ' . OHCL_Industry::CONTRACT, 'ok' => ! empty( $f['industry_ok'] ), 'note' => empty( $f['industry_ok'] ) ? (int) $f['industry_errors'] . ' lỗi · chạy wp ohcl validate-industry' : '' ),
		);
		$s3c  = array(
			array( 'label' => 'Các trang của Hub đã được tạo (' . (int) $f['pages_made'] . '/' . (int) $f['pages_total'] . ')', 'ok' => (int) $f['pages_total'] > 0 && (int) $f['pages_made'] >= (int) $f['pages_total'] ),
			array( 'label' => 'Chính sách affiliate đã được đặt', 'ok' => ! empty( $f['policy_set'] ) ),
		);
		$s4c  = array(
			array( 'label' => 'Đã tiếp nhận hội viên thật đầu tiên', 'ok' => (int) $f['members_accepted'] >= 1 ),
			array( 'label' => 'Đã cấp khoá trợ lý cho một hội viên', 'ok' => (int) $f['keys_issued'] >= 1 ),
			array( 'label' => 'Có một quyền lợi ở trạng thái "đã dùng"', 'ok' => null === $f['benefit_used'] ? null : (bool) $f['benefit_used'], 'note' => null === $f['benefit_used'] ? 'chưa kiểm được (lõi không phản hồi)' : '' ),
		);

		$defs = array(
			array( 1, 'connect', 'Kết nối', 'Ghép nối lõi Bizcity bằng mã', $s1c, 'Mở Cài đặt', 'open-hub-settings' ),
			array( 2, 'industry', 'Ngành', 'Plugin ngành đạt hợp đồng, có persona, tiêu chuẩn, trường hồ sơ', $s2c, 'Xem ngành', 'open-hub-settings' ),
			array( 3, 'content', 'Nội dung', 'Trang, menu, dữ liệu mẫu hoặc thật, chính sách', $s3c, 'Mở Trang & menu', class_exists( 'OHCL_Admin_Pages' ) ? OHCL_Admin_Pages::PAGE : 'open-hub' ),
			array( 4, 'open', 'Mở cửa', 'Tiếp nhận hội viên đầu tiên, cấp khoá, có quyền lợi được dùng', $s4c, 'Mở Hội viên', 'open-hub-members' ),
		);
		$steps = array();
		$done  = 0;
		foreach ( $defs as $d ) {
			$ok   = count( array_filter( $d[4], function ( $c ) { return true === $c['ok']; } ) );
			$st   = $ok === count( $d[4] ) ? 'done' : ( $ok > 0 ? 'doing' : 'todo' );
			$done += 'done' === $st ? 1 : 0;
			$steps[] = array( 'n' => $d[0], 'key' => $d[1], 'title' => $d[2], 'what' => $d[3], 'status' => $st, 'checks' => $d[4], 'action_label' => $d[5], 'action_page' => $d[6] );
		}
		return array( 'steps' => $steps, 'done' => $done, 'percent' => (int) round( 100 * $done / 4 ), 'activated' => 4 === $done );
	}

	/** Facts from the site. Reads the core only through keys_by_id() (cached); never writes. */
	public static function facts() {
		$raw     = apply_filters( OHCL_Industry::FILTER, array() );
		$v       = OHCL_Industry::validate( $raw );
		$creat   = OHCL_Pages::creatable();
		$made    = count( array_intersect_key( OHCL_Pages::map(), $creat ) );
		$pol     = OHCL_Affiliate::policy();
		$members = OHCL_Members::all();
		$acc = 0;
		$keys = 0;
		foreach ( $members as $m ) {
			$acc  += '' !== (string) $m['accepted_at'] ? 1 : 0;
			$keys += $m['key_id'] ? 1 : 0;
		}
		$used = false;
		if ( $keys ) {
			$kb = OHCL_Members::keys_by_id();
			if ( ! $kb ) {
				$used = null;
			} else {
				$ctx = OHCL_Benefits::context( time() );
				foreach ( $members as $m ) {
					if ( $m['key_id'] && isset( $kb[ $m['key_id'] ] ) ) {
						foreach ( OHCL_Benefits::for_member( $m, $kb[ $m['key_id'] ], $ctx ) as $b ) {
							if ( 'used' === $b['state'] ) {
								$used = true;
								break 2;
							}
						}
					}
				}
			}
		}
		return array(
			'core' => OHCL_Core_Client::state(), 'industry_ok' => $v['ok'], 'industry_errors' => count( $v['errors'] ),
			'pages_total' => count( $creat ), 'pages_made' => $made, 'policy_set' => '' !== (string) $pol['updated_at'],
			'members_real' => count( $members ), 'members_accepted' => $acc, 'keys_issued' => $keys, 'benefit_used' => $used,
		);
	}

	/** Compute now and, the first time all four steps are done, fire `hub_activated` once. */
	public static function evaluate() {
		$r = self::steps( self::facts() );
		self::maybe_activate( $r['activated'] );
		$r['activated_at'] = (string) get_option( self::ACTIVATED_OPTION, '' );
		return $r;
	}

	/** @return bool true when the event fired on this call. */
	public static function maybe_activate( $activated ) {
		if ( ! $activated || '' !== (string) get_option( self::ACTIVATED_OPTION, '' ) ) {
			return false;
		}
		$at = gmdate( 'Y-m-d H:i:s' );
		update_option( self::ACTIVATED_OPTION, $at, false );
		OHCL_Slots::emit( 'hub_activated', array( 'industry' => OHCL_Industry::current()['id'], 'activated_at' => $at ) );
		return true;
	}
}
