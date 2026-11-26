<?php
/**
 * My Hub (OPEN-HUB-CLIENT §13; mockup hub-frontend.html F5, F6): the member's own centre at /my-hub/<tab>/.
 *
 * Who is the member? The WordPress user linked on the member record (`_bzm_user`). First visit: when exactly
 * one member record carries the user's email and no other user holds it, it is linked once. Otherwise the
 * page says so and points to the Hub board (no guessing, no merging).
 *
 * Everything shown comes from real records: member states, the key at open-hub-core, certificates, the
 * industry contract (apps). Numbers the Hub does not measure yet (profile views, contacts — Marketing Đ3)
 * show "—", never an invented figure (R-HUB-6).
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — My Hub: trung tâm quyền lợi của hội viên đăng nhập; gắn hồ sơ theo tài khoản / email duy nhất; lộ trình 90 ngày tính từ dữ liệu thật.

defined( 'ABSPATH' ) || exit;

final class OHCL_MyHub {

	const TABS = array(
		''           => array( 'Tổng quan', 'grid' ),
		'cong-nghe'  => array( 'Công nghệ của tôi', 'gift' ),
		'ho-so'      => array( 'Hồ sơ doanh nghiệp', 'store' ),
		'chung-nhan' => array( 'Chứng nhận của tôi', 'check' ),
		'lo-trinh'   => array( 'Lộ trình 90 ngày', 'flow' ),
		'ho-tro'     => array( 'Hỗ trợ', 'chat' ),
	);

	const PHASES = array(
		array( 'Khởi tạo', 1, 7 ),
		array( 'Chuẩn hoá', 8, 30 ),
		array( 'Tăng trưởng', 31, 60 ),
		array( 'Nâng tầm', 61, 90 ),
	);

	const SUPPORT_LIMIT = 5; // yêu cầu / ngày / hội viên

	public static function boot() {
		add_action( 'admin_post_ohcl_myhub_profile', array( __CLASS__, 'handle_profile' ) );
		add_action( 'admin_post_ohcl_myhub_support', array( __CLASS__, 'handle_support' ) );
	}

	/* ===================== pure ===================== */

	/**
	 * Which member record belongs to this user? Pure.
	 *
	 * @param array[] $members to_array() rows (with 'user').
	 * @return array { id:int, link:bool, why:string } id 0 = none / ambiguous.
	 */
	public static function pick( array $members, $uid, $email ) {
		foreach ( $members as $m ) {
			if ( (int) $m['user'] === (int) $uid && $uid ) {
				return array( 'id' => (int) $m['id'], 'link' => false, 'why' => 'linked' );
			}
		}
		$email = strtolower( trim( (string) $email ) );
		if ( '' === $email ) {
			return array( 'id' => 0, 'link' => false, 'why' => 'none' );
		}
		$hits = array_values( array_filter( $members, function ( $m ) use ( $email ) { return strtolower( trim( (string) $m['email'] ) ) === $email; } ) );
		if ( 1 !== count( $hits ) ) {
			return array( 'id' => 0, 'link' => false, 'why' => $hits ? 'ambiguous' : 'none' );
		}
		if ( (int) $hits[0]['user'] && (int) $hits[0]['user'] !== (int) $uid ) {
			return array( 'id' => 0, 'link' => false, 'why' => 'taken' );
		}
		return array( 'id' => (int) $hits[0]['id'], 'link' => true, 'why' => 'email' );
	}

	/**
	 * 90-day roadmap from real states. Pure.
	 *
	 * @param array      $m   member row.
	 * @param array|null $key key view.
	 * @return array { day:int, phase:int (0..3, 4 = done), phases: [ {label, from, to, steps:[{label,done}]} ] }
	 */
	public static function road( array $m, $key, $now_ts ) {
		$start = ! empty( $m['joined_at'] ) ? strtotime( $m['joined_at'] . ' UTC' ) : $now_ts;
		$day   = max( 1, (int) floor( ( $now_ts - $start ) / DAY_IN_SECONDS ) + 1 );
		$live  = is_array( $key ) && in_array( $key['status'], array( 'trial', 'active', 'grace' ), true );
		$used  = $live && ! empty( $key['last_used_at'] ) && $now_ts - strtotime( $key['last_used_at'] . ' UTC' ) <= 30 * DAY_IN_SECONDS;
		$ver   = OHCL_Members::verified( $m['verify'], $now_ts );
		$steps = array(
			array(
				array( 'Hoàn thiện hồ sơ (loại hình, tỉnh, liên hệ)', '' !== $m['segment'] && '' !== $m['province'] && ( '' !== $m['contact_zalo'] || '' !== $m['email'] || '' !== $m['phone'] ) ),
				array( 'Được ban điều hành tiếp nhận', '' !== (string) $m['accepted_at'] ),
				array( 'Nhận bộ công nghệ và khoá trợ lý AI', $live ),
				array( 'Có Coach phụ trách', '' !== $m['coach'] ),
			),
			array(
				array( 'Dùng trợ lý AI trong 30 ngày gần nhất', $used ),
				array( 'Được xác minh (Verified)', $ver ),
				array( 'Có toạ độ và đồng ý lên bản đồ', '' !== (string) $m['lat'] && '' !== (string) $m['consent'] ),
			),
			array(
				array( 'Đạt một bộ tiêu chuẩn ngành (Certified)', OHCL_Members::certified( $m, $now_ts ) ),
				array( 'Hồ sơ công khai trên bản đồ ngành (V4)', 4 === OHCL_Members::level( $m, $key, $now_ts ) ),
			),
			array(
				array( 'Duy trì xác minh và chứng nhận còn hạn', $ver && OHCL_Members::certified( $m, $now_ts ) ),
			),
		);
		$phases = array();
		$cur    = 4;
		foreach ( self::PHASES as $i => $ph ) {
			$st  = array();
			$all = true;
			foreach ( $steps[ $i ] as $s ) {
				$st[] = array( 'label' => $s[0], 'done' => (bool) $s[1] );
				$all  = $all && $s[1];
			}
			if ( ! $all && 4 === $cur ) {
				$cur = $i;
			}
			$phases[] = array( 'label' => $ph[0], 'from' => $ph[1], 'to' => $ph[2], 'steps' => $st, 'done' => $all );
		}
		return array( 'day' => $day, 'phase' => $cur, 'phases' => $phases );
	}

	/** Profile fields a member may edit themselves. Pure. @return array|WP_Error */
	public static function clean_profile( array $in ) {
		$s   = function ( $k, $n ) use ( $in ) {
			$v = isset( $in[ $k ] ) && is_scalar( $in[ $k ] ) ? trim( preg_replace( '/\s+/u', ' ', strip_tags( (string) $in[ $k ] ) ) ) : '';
			return function_exists( 'mb_substr' ) ? mb_substr( $v, 0, $n, 'UTF-8' ) : substr( $v, 0, $n );
		};
		$out = array( 'address' => $s( 'address', 200 ), 'hours' => $s( 'hours', 120 ), 'site_url' => $s( 'site_url', 200 ), 'shop_url' => $s( 'shop_url', 200 ), 'contact_zalo' => preg_replace( '/[^0-9+]/', '', $s( 'contact_zalo', 20 ) ) );
		// [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-SHOP — cơ sở tự nhập website bán hàng (https) và tự đồng ý hiện sản phẩm.
		if ( '' !== $out['shop_url'] && class_exists( 'OHCL_Shop' ) && '' === OHCL_Shop::endpoint( $out['shop_url'] ) ) {
			return OHCL_Error::make( 'ohcl_member_shop_invalid', 'Website bán hàng phải là địa chỉ https:// của site có WooCommerce.', 'Sửa địa chỉ (ví dụ https://tencoso.vn) hoặc để trống.', 400 );
		}
		if ( '' !== $out['site_url'] && ! preg_match( '#^https?://[^\s<>"]+$#i', $out['site_url'] ) ) {
			return OHCL_Error::make( 'ohcl_member_url_invalid', 'Website phải bắt đầu bằng http:// hoặc https://.', 'Sửa địa chỉ website hoặc để trống.', 400 );
		}
		if ( '' !== $out['contact_zalo'] && ! preg_match( '/^\+?[0-9]{9,12}$/', $out['contact_zalo'] ) ) {
			return OHCL_Error::make( 'ohcl_join_phone_invalid', 'Số Zalo chưa đúng.', 'Nhập số điện thoại 10 chữ số, ví dụ 0912345678.', 400 );
		}
		$out['consent']      = ! empty( $in['public_consent'] );
		$out['shop_consent'] = ! empty( $in['shop_consent'] );
		return $out;
	}

	/* ===================== WordPress ===================== */

	/** The current user's member row, linking by unique email on first visit. @return array { member|null, why } */
	public static function current() {
		if ( ! is_user_logged_in() ) {
			return array( 'member' => null, 'why' => 'guest' );
		}
		$u   = wp_get_current_user();
		$pk  = self::pick( OHCL_Members::all(), (int) $u->ID, (string) $u->user_email );
		if ( ! $pk['id'] ) {
			return array( 'member' => null, 'why' => $pk['why'] );
		}
		if ( $pk['link'] ) {
			update_post_meta( $pk['id'], '_bzm_user', (int) $u->ID );
		}
		return array( 'member' => OHCL_Members::get( $pk['id'] ), 'why' => $pk['why'] );
	}

	private static function back( $tab, array $args ) {
		wp_safe_redirect( add_query_arg( $args, OHCL_Front::myhub_url( $tab ) ) );
		exit;
	}

	public static function handle_profile() {
		$cur = self::current();
		if ( ! $cur['member'] || ! isset( $_POST['_ohcl_myhub'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_ohcl_myhub'] ) ), 'ohcl_myhub_profile' ) ) {
			self::back( 'ho-so', array( 'err' => 'ohcl_join_expired' ) );
		}
		$c = self::clean_profile( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- clean_profile() bỏ thẻ, cắt độ dài, kiểm dạng.
		if ( is_wp_error( $c ) ) {
			self::back( 'ho-so', array( 'err' => $c->get_error_code() ) );
		}
		$id = (int) $cur['member']['id'];
		foreach ( array( 'address', 'hours', 'site_url', 'shop_url', 'contact_zalo' ) as $k ) { // [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-SHOP
			update_post_meta( $id, '_bzm_' . $k, $c[ $k ] );
		}
		$had = (string) get_post_meta( $id, '_bzm_public_consent', true );
		update_post_meta( $id, '_bzm_public_consent', $c['consent'] ? ( $had ? $had : gmdate( 'Y-m-d H:i:s' ) ) : '' );
		$had = (string) get_post_meta( $id, '_bzm_shop_consent', true );
		update_post_meta( $id, '_bzm_shop_consent', $c['shop_consent'] ? ( $had ? $had : gmdate( 'Y-m-d H:i:s' ) ) : '' );
		if ( class_exists( 'OHCL_Shop' ) ) {
			OHCL_Shop::flush( $c['shop_url'] );
		}
		OHCL_Members::flush_public();
		self::back( 'ho-so', array( 'saved' => 1 ) );
	}

	public static function handle_support() {
		$cur = self::current();
		if ( ! $cur['member'] || ! isset( $_POST['_ohcl_myhub'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_ohcl_myhub'] ) ), 'ohcl_myhub_support' ) ) {
			self::back( 'ho-tro', array( 'err' => 'ohcl_join_expired' ) );
		}
		$text = isset( $_POST['text'] ) ? trim( preg_replace( '/\s+/u', ' ', sanitize_textarea_field( wp_unslash( $_POST['text'] ) ) ) ) : '';
		if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text ) ) < 10 ) {
			self::back( 'ho-tro', array( 'err' => 'ohcl_report_too_short' ) );
		}
		$id   = (int) $cur['member']['id'];
		$list = get_post_meta( $id, '_bzm_support', true );
		$list = is_array( $list ) ? $list : array();
		$today = gmdate( 'Y-m-d' );
		if ( count( array_filter( $list, function ( $x ) use ( $today ) { return 0 === strpos( $x['at'], $today ); } ) ) >= self::SUPPORT_LIMIT ) {
			self::back( 'ho-tro', array( 'err' => 'ohcl_report_rate_limited' ) );
		}
		$list[] = array( 'at' => gmdate( 'Y-m-d H:i:s' ), 'text' => function_exists( 'mb_substr' ) ? mb_substr( $text, 0, 1000, 'UTF-8' ) : substr( $text, 0, 1000 ) );
		update_post_meta( $id, '_bzm_support', array_slice( $list, -20 ) );
		self::back( 'ho-tro', array( 'sent' => 1 ) );
	}
}
