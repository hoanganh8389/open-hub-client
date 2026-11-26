<?php
/**
 * Coach role, referral codes and commissions (OPEN-HUB-CLIENT §7, OH-Q2). Wave Đ3a: source 1 "giới thiệu
 * hội viên mới" end to end; sources 2 (Woo service orders) and 3 (AI revenue closed by the core) are listed
 * but not recognised yet.
 *
 * Rules kept here:
 *   - a commission comes from SOURCE DATA, never typed by hand: a referred member who reached the journey
 *     stage "Đã kích hoạt" (live key) inside the 12-month holding window;
 *   - one member has ONE referring Coach: the first referral wins and is kept 12 months;
 *   - every line has a stable key (`member:<id>`) ⇒ recognising twice never doubles it; the amount is a SNAPSHOT
 *     of the policy at recognition, so changing the policy only affects later lines;
 *   - a cancelled line is never deleted: a reversal line (negative, key `rev:<key>`) is added;
 *   - payout = CPT `bz_hub_payout`, one per Coach per month, lines in meta: tentative → approved (Hub Owner)
 *     → paid. A month already approved / paid is never edited: new lines go to the next month;
 *   - the Hub sets the amounts; defaults are 0 ⇒ nothing is recognised until the Hub Owner sets a policy.
 * No new table: CPT + user meta + option.
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 11:15 AM Johnny Chu - Chu Hoàng Anh] OHCL-D3A — Coach + affiliate: vai hub_coach, mã giới thiệu, cookie 12 tháng, sổ hoa hồng theo tháng (tạm tính → duyệt → đã trả), dòng đảo.

defined( 'ABSPATH' ) || exit;

final class OHCL_Affiliate {

	const PAYOUT_CPT    = 'bz_hub_payout';
	const ROLE          = 'hub_coach';
	const POLICY_OPTION = 'ohcl_affiliate_policy';
	const COOKIE        = 'ohcl_ref';
	const HOLD_MONTHS   = 12;
	const MAX_AMOUNT    = 100000000;
	const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

	const SOURCES = array(
		'referral_member' => 'Giới thiệu hội viên mới',
		'service'         => 'Dịch vụ Coach bán',
		'ai'              => 'Trợ lý AI của hội viên phụ trách',
	);

	const STATUS = array( 'tentative' => 'Tạm tính', 'approved' => 'Đã duyệt', 'paid' => 'Đã trả' );

	/* ===================== pure ===================== */

	/** @return array{referral_member:int, service_pct:float, ai_pct:float, updated_at:string} */
	public static function default_policy() {
		return array( 'referral_member' => 0, 'service_pct' => 0.0, 'ai_pct' => 0.0, 'updated_at' => '' );
	}

	/** Clean a policy form. Pure. Amounts are whole đồng, percentages 0–100 with one decimal. */
	public static function clean_policy( array $in, $now_ts ) {
		$int = function ( $v ) {
			return is_numeric( str_replace( array( '.', ',', ' ' ), '', (string) $v ) ) ? max( 0, min( self::MAX_AMOUNT, (int) str_replace( array( '.', ',', ' ' ), '', (string) $v ) ) ) : 0;
		};
		$pct = function ( $v ) {
			$v = str_replace( ',', '.', trim( (string) $v ) );
			return is_numeric( $v ) ? round( max( 0, min( 100, (float) $v ) ), 1 ) : 0.0;
		};
		return array(
			'referral_member' => $int( isset( $in['referral_member'] ) ? $in['referral_member'] : 0 ),
			'service_pct'     => $pct( isset( $in['service_pct'] ) ? $in['service_pct'] : 0 ),
			'ai_pct'          => $pct( isset( $in['ai_pct'] ) ? $in['ai_pct'] : 0 ),
			'updated_at'      => gmdate( 'Y-m-d H:i:s', (int) $now_ts ),
		);
	}

	public static function valid_code( $c ) {
		return is_string( $c ) && 1 === preg_match( '/^[A-HJ-NP-Z2-9]{6,10}$/', $c );
	}

	/** 6-character referral code from random bytes. Pure (bytes are passed in). */
	public static function code_from_bytes( $bytes ) {
		$out = '';
		$n   = strlen( self::CODE_ALPHABET );
		for ( $i = 0; $i < 6; $i++ ) {
			$out .= self::CODE_ALPHABET[ ord( isset( $bytes[ $i ] ) ? $bytes[ $i ] : "\0" ) % $n ];
		}
		return $out;
	}

	public static function month( $ts ) {
		return gmdate( 'Y-m', (int) $ts );
	}

	/** Is a referral still inside the holding window (12 months from the first referral)? */
	public static function ref_active( $ref_at, $now_ts ) {
		$t = '' === (string) $ref_at ? 0 : strtotime( $ref_at . ' UTC' );
		return $t > 0 && $now_ts <= strtotime( '+' . self::HOLD_MONTHS . ' months', $t ) && $now_ts >= $t;
	}

	/** Month a new line is booked in: this month, or the next one when this month is already approved / paid. */
	public static function target_month( $now_ts, $status ) {
		return ( '' === (string) $status || 'tentative' === $status ) ? self::month( $now_ts ) : self::month( strtotime( 'first day of next month', (int) $now_ts ) );
	}

	/** Legal status moves: tentative → approved → paid. Nothing goes back. */
	public static function can_move( $from, $to ) {
		return ( 'tentative' === $from && 'approved' === $to ) || ( 'approved' === $from && 'paid' === $to );
	}

	public static function make_line( $key, $source, $ref, $amount, $now_ts, $policy_at, $note ) {
		return array(
			'key'       => (string) $key,
			'source'    => isset( self::SOURCES[ $source ] ) ? $source : 'referral_member',
			'ref'       => (string) $ref,
			'amount'    => (int) $amount,
			'at'        => gmdate( 'Y-m-d H:i:s', (int) $now_ts ),
			'policy_at' => (string) $policy_at,
			'note'      => (string) $note,
		);
	}

	/** @return array{0:array[],1:bool} lines and whether the line was added (same key ⇒ no-op). */
	public static function add_line( array $lines, array $line ) {
		foreach ( $lines as $l ) {
			if ( $l['key'] === $line['key'] ) {
				return array( $lines, false );
			}
		}
		$lines[] = $line;
		return array( $lines, true );
	}

	public static function total( array $lines ) {
		$t = 0;
		foreach ( $lines as $l ) {
			$t += (int) $l['amount'];
		}
		return $t;
	}

	public static function line_keys( array $payouts_lines ) {
		$k = array();
		foreach ( $payouts_lines as $lines ) {
			foreach ( $lines as $l ) {
				$k[ $l['key'] ] = true;
			}
		}
		return $k;
	}

	/**
	 * Reversal line for a recognised line, or null (unknown key, already reversed, or itself a reversal).
	 *
	 * @param array[] $all_lines every line of every payout (the original may sit in another month).
	 */
	public static function reversal( array $all_lines, $key, $reason, $now_ts ) {
		$orig = null;
		foreach ( $all_lines as $l ) {
			if ( $l['key'] === 'rev:' . $key ) {
				return null;
			}
			if ( $l['key'] === $key && 0 !== strpos( $key, 'rev:' ) ) {
				$orig = $l;
			}
		}
		return $orig && $orig['amount'] > 0 ? self::make_line( 'rev:' . $key, $orig['source'], $orig['ref'], -$orig['amount'], $now_ts, $orig['policy_at'], 'Đảo: ' . $reason ) : null;
	}

	/**
	 * Commission lines to book now for source 1. Pure.
	 *
	 * @param array[] $members     to_array() rows with ref_coach / ref_at.
	 * @param array   $keys_by_id  key views (empty when the core is unreachable ⇒ nothing is recognised).
	 * @param array   $policy      default / current policy.
	 * @param array   $overrides   coach user id => amount (per-Coach override of the referral amount).
	 * @param array   $have        line key => true already booked anywhere.
	 * @return array[] { coach, member, line }
	 */
	public static function recognize( array $members, array $keys_by_id, array $policy, array $overrides, array $have, $now_ts ) {
		$out = array();
		foreach ( $members as $m ) {
			$coach = (int) $m['ref_coach'];
			$key   = 'member:' . (int) $m['id'];
			if ( ! $coach || isset( $have[ $key ] ) || ! self::ref_active( $m['ref_at'], $now_ts ) || 'left' === $m['status'] ) {
				continue;
			}
			$kv = $m['key_id'] && isset( $keys_by_id[ $m['key_id'] ] ) ? $keys_by_id[ $m['key_id'] ] : null;
			if ( OHCL_Members::journey( $m, $kv, $now_ts ) < 3 ) {
				continue; // chưa "Đã kích hoạt"
			}
			$amount = isset( $overrides[ $coach ] ) && null !== $overrides[ $coach ] ? (int) $overrides[ $coach ] : (int) $policy['referral_member'];
			if ( $amount <= 0 ) {
				continue; // chính sách chưa đặt số ⇒ không ghi nhận (không tự bịa số)
			}
			$out[] = array( 'coach' => $coach, 'member' => (int) $m['id'], 'line' => self::make_line( $key, 'referral_member', (string) $m['code'], $amount, $now_ts, $policy['updated_at'], 'Hội viên ' . $m['code'] . ' đã kích hoạt' ) );
		}
		return $out;
	}

	/* ===================== WordPress ===================== */

	public static function register() {
		register_post_type( self::PAYOUT_CPT, array( 'label' => 'Chi trả Coach', 'public' => false, 'show_ui' => false, 'show_in_rest' => false, 'supports' => array( 'title' ), 'capability_type' => 'post', 'map_meta_cap' => true ) );
		if ( function_exists( 'get_role' ) && ! get_role( self::ROLE ) ) {
			add_role( self::ROLE, 'Coach của Hub', array( 'read' => true ) );
		}
	}

	public static function policy() {
		$p = get_option( self::POLICY_OPTION, array() );
		return is_array( $p ) ? array_merge( self::default_policy(), $p ) : self::default_policy();
	}

	public static function save_policy( array $in ) {
		update_option( self::POLICY_OPTION, self::clean_policy( $in, time() ), false );
	}

	/** Coach user ids → referral code. */
	public static function coaches() {
		$out = array();
		foreach ( get_users( array( 'role' => self::ROLE, 'number' => 500 ) ) as $u ) {
			$out[ (int) $u->ID ] = array(
				'id'       => (int) $u->ID,
				'name'     => (string) $u->display_name,
				'email'    => (string) $u->user_email,
				'code'     => (string) get_user_meta( $u->ID, 'ohcl_ref_code', true ),
				'override' => '' === (string) get_user_meta( $u->ID, 'ohcl_ref_amount', true ) ? null : (int) get_user_meta( $u->ID, 'ohcl_ref_amount', true ),
			);
		}
		return $out;
	}

	public static function coach_by_code( $code ) {
		$code = strtoupper( (string) $code );
		if ( ! self::valid_code( $code ) ) {
			return 0;
		}
		$ids = get_users( array( 'role' => self::ROLE, 'meta_key' => 'ohcl_ref_code', 'meta_value' => $code, 'number' => 1, 'fields' => 'ID' ) );
		return $ids ? (int) $ids[0] : 0;
	}

	public static function referral_url( $code ) {
		return add_query_arg( array( 'ref' => $code ), home_url( '/' ) );
	}

	private static function new_code() {
		for ( $i = 0; $i < 8; $i++ ) {
			$c = self::code_from_bytes( function_exists( 'random_bytes' ) ? random_bytes( 6 ) : substr( md5( uniqid( '', true ) ), 0, 6 ) );
			if ( ! self::coach_by_code( $c ) ) {
				return $c;
			}
		}
		return '';
	}

	/**
	 * Turn an approved application into a Coach: the account (new, or an existing one by email) gets the
	 * `hub_coach` role and a referral code. Hub Owner only (caller checks capability).
	 *
	 * @return int|WP_Error user id
	 */
	public static function activate( $app_id, $email ) {
		$p = get_post( (int) $app_id );
		if ( ! $p || OHCL_Coach::CPT !== $p->post_type || 'approved' !== get_post_meta( $p->ID, '_bzca_status', true ) ) {
			return OHCL_Error::make( 'ohcl_coach_not_approved', 'Hồ sơ chưa được duyệt.', 'Bấm Duyệt hồ sơ trước, rồi cấp vai Coach.', 400 );
		}
		$email = is_string( $email ) ? trim( $email ) : '';
		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return OHCL_Error::make( 'ohcl_coach_email_invalid', 'Cần email hợp lệ để tạo tài khoản Coach.', 'Nhập email của Coach rồi bấm lại.', 400 );
		}
		if ( get_post_meta( $p->ID, '_bzca_user', true ) ) {
			return OHCL_Error::make( 'ohcl_coach_already', 'Hồ sơ này đã được cấp tài khoản Coach.', 'Xem trong danh sách Coach bên dưới.', 409 );
		}
		$u = get_user_by( 'email', $email );
		if ( $u ) {
			$uid = (int) $u->ID;
			$u->add_role( self::ROLE ); // giữ nguyên vai khác của tài khoản (không hạ quyền quản trị)
		} else {
			$login = sanitize_user( strstr( $email, '@', true ), true );
			$login = '' !== $login ? $login : 'coach';
			for ( $i = 0; username_exists( $login ) && $i < 20; $i++ ) {
				$login .= wp_rand( 0, 9 );
			}
			$uid = wp_insert_user( array( 'user_login' => $login, 'user_email' => $email, 'user_pass' => wp_generate_password( 20, true ), 'display_name' => (string) $p->post_title, 'role' => self::ROLE ) );
			if ( is_wp_error( $uid ) ) {
				return OHCL_Error::make( 'ohcl_coach_user_failed', 'Chưa tạo được tài khoản Coach.', 'Kiểm tra email chưa bị dùng ở tài khoản khác, rồi thử lại.', 500 );
			}
			if ( function_exists( 'wp_new_user_notification' ) ) {
				wp_new_user_notification( $uid, null, 'user' ); // gửi link đặt mật khẩu cho Coach
			}
		}
		$code = (string) get_user_meta( $uid, 'ohcl_ref_code', true );
		if ( ! self::valid_code( $code ) ) {
			$code = self::new_code();
			if ( '' === $code ) {
				return OHCL_Error::make( 'ohcl_coach_code_failed', 'Chưa tạo được mã giới thiệu.', 'Thử lại sau ít giây.', 500 );
			}
			update_user_meta( $uid, 'ohcl_ref_code', $code );
		}
		update_user_meta( $uid, 'ohcl_coach_skills', (array) get_post_meta( $p->ID, '_bzca_skills', true ) );
		update_post_meta( $p->ID, '_bzca_user', $uid );
		OHCL_Slots::emit( 'coach_activated', array( 'coach_id' => $uid, 'code' => $code ) );
		return (int) $uid;
	}

	public static function save_override( $user_id, $amount ) {
		$a = is_numeric( str_replace( array( '.', ',', ' ' ), '', (string) $amount ) ) && '' !== trim( (string) $amount ) ? max( 0, min( self::MAX_AMOUNT, (int) str_replace( array( '.', ',', ' ' ), '', (string) $amount ) ) ) : '';
		update_user_meta( (int) $user_id, 'ohcl_ref_amount', $a );
	}

	/** init: a visit with ?ref=CODE of a real Coach sets the 12-month cookie (first referrer keeps it). */
	public static function capture_ref() {
		if ( is_admin() || empty( $_GET['ref'] ) || ! empty( $_COOKIE[ self::COOKIE ] ) || headers_sent() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- đọc mã giới thiệu công khai, chỉ so khớp mã Coach.
			return;
		}
		$code = strtoupper( sanitize_text_field( wp_unslash( $_GET['ref'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::coach_by_code( $code ) ) {
			setcookie( self::COOKIE, $code, time() + 365 * DAY_IN_SECONDS, '/', '', is_ssl(), true );
			$_COOKIE[ self::COOKIE ] = $code;
		}
	}

	/** After a public registration: remember the referring Coach once (first wins, never overwritten). */
	public static function attribute_member( $member_id ) {
		$code = isset( $_COOKIE[ self::COOKIE ] ) ? strtoupper( sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) ) : '';
		$uid  = '' !== $code ? self::coach_by_code( $code ) : 0;
		if ( ! $uid || get_post_meta( (int) $member_id, '_bzm_ref_coach', true ) ) {
			return 0;
		}
		update_post_meta( (int) $member_id, '_bzm_ref_coach', $uid );
		update_post_meta( (int) $member_id, '_bzm_ref_at', gmdate( 'Y-m-d H:i:s' ) );
		if ( '' === (string) get_post_meta( (int) $member_id, '_bzm_coach', true ) ) {
			update_post_meta( (int) $member_id, '_bzm_coach', strtolower( $code ) );
		}
		return $uid;
	}

	/** Payouts: id => array { id, coach, month, status, lines[], total, decided_at, paid_at }. */
	public static function payouts() {
		$out = array();
		foreach ( get_posts( array( 'post_type' => self::PAYOUT_CPT, 'post_status' => 'publish', 'numberposts' => 500, 'orderby' => 'ID', 'order' => 'DESC', 'no_found_rows' => true ) ) as $p ) {
			$lines = get_post_meta( $p->ID, '_bzp_lines', true );
			$lines = is_array( $lines ) ? $lines : array();
			$st    = (string) get_post_meta( $p->ID, '_bzp_status', true );
			$out[ (int) $p->ID ] = array(
				'id'         => (int) $p->ID,
				'coach'      => (int) get_post_meta( $p->ID, '_bzp_coach', true ),
				'month'      => (string) get_post_meta( $p->ID, '_bzp_month', true ),
				'status'     => isset( self::STATUS[ $st ] ) ? $st : 'tentative',
				'lines'      => $lines,
				'total'      => self::total( $lines ),
				'decided_at' => (string) get_post_meta( $p->ID, '_bzp_decided_at', true ),
				'paid_at'    => (string) get_post_meta( $p->ID, '_bzp_paid_at', true ),
			);
		}
		return $out;
	}

	private static function book( $coach, array $line, $now_ts ) {
		$payouts = self::payouts();
		$cur     = null;
		foreach ( $payouts as $p ) {
			if ( $p['coach'] === (int) $coach && $p['month'] === self::month( $now_ts ) ) {
				$cur = $p;
			}
		}
		$month = self::target_month( $now_ts, $cur ? $cur['status'] : '' );
		$tgt   = null;
		foreach ( $payouts as $p ) {
			if ( $p['coach'] === (int) $coach && $p['month'] === $month ) {
				$tgt = $p;
			}
		}
		if ( ! $tgt ) {
			$u  = get_userdata( (int) $coach );
			$id = wp_insert_post( array( 'post_type' => self::PAYOUT_CPT, 'post_status' => 'publish', 'post_title' => $month . ' · ' . ( $u ? $u->display_name : '#' . (int) $coach ) ) );
			if ( ! $id || is_wp_error( $id ) ) {
				return false;
			}
			update_post_meta( $id, '_bzp_coach', (int) $coach );
			update_post_meta( $id, '_bzp_month', $month );
			update_post_meta( $id, '_bzp_status', 'tentative' );
			update_post_meta( $id, '_bzp_lines', array() );
			$tgt = array( 'id' => (int) $id, 'lines' => array(), 'status' => 'tentative' );
		}
		if ( 'tentative' !== $tgt['status'] ) {
			return false; // không sửa tháng đã duyệt / đã trả
		}
		list( $lines, $added ) = self::add_line( $tgt['lines'], $line );
		if ( $added ) {
			update_post_meta( $tgt['id'], '_bzp_lines', $lines );
		}
		return $added;
	}

	/** Recognise new commissions from current member data. @return array{added:int, skipped_core:bool} */
	public static function sync() {
		$keys = OHCL_Members::keys_by_id();
		$mem  = OHCL_Members::all();
		$over = array();
		foreach ( self::coaches() as $c ) {
			$over[ $c['id'] ] = $c['override'];
		}
		$have = array();
		foreach ( self::payouts() as $p ) {
			$have += self::line_keys( array( $p['lines'] ) );
		}
		$now   = time();
		$added = 0;
		foreach ( self::recognize( $mem, $keys, self::policy(), $over, $have, $now ) as $r ) {
			if ( self::book( $r['coach'], $r['line'], $now ) ) {
				$added++;
			}
		}
		return array( 'added' => $added, 'skipped_core' => ! $keys );
	}

	/** Reverse a recognised line (never delete). @return true|WP_Error */
	public static function reverse( $key, $reason ) {
		$all   = array();
		$coach = 0;
		foreach ( self::payouts() as $p ) {
			foreach ( $p['lines'] as $l ) {
				$all[] = $l;
				if ( $l['key'] === $key ) {
					$coach = $p['coach'];
				}
			}
		}
		$rev = self::reversal( $all, $key, '' !== trim( (string) $reason ) ? trim( (string) $reason ) : 'ban điều hành đảo dòng', time() );
		if ( ! $rev || ! $coach ) {
			return OHCL_Error::make( 'ohcl_payout_reverse_invalid', 'Không đảo được dòng này.', 'Dòng không tồn tại, đã được đảo, hoặc là dòng đảo.', 400 );
		}
		return self::book( $coach, $rev, time() ) ? true : OHCL_Error::make( 'ohcl_payout_reverse_failed', 'Chưa ghi được dòng đảo.', 'Thử lại sau ít phút.', 500 );
	}

	/** @return true|WP_Error */
	public static function move( $payout_id, $to ) {
		$p = get_post( (int) $payout_id );
		if ( ! $p || self::PAYOUT_CPT !== $p->post_type ) {
			return OHCL_Error::make( 'ohcl_payout_not_found', 'Không tìm thấy kỳ chi trả.', 'Tải lại danh sách.', 404 );
		}
		$from = (string) get_post_meta( $p->ID, '_bzp_status', true );
		if ( ! self::can_move( $from, $to ) ) {
			return OHCL_Error::make( 'ohcl_payout_move_invalid', 'Không chuyển được trạng thái này.', 'Kỳ chi trả đi theo thứ tự Tạm tính → Đã duyệt → Đã trả.', 400 );
		}
		update_post_meta( $p->ID, '_bzp_status', $to );
		update_post_meta( $p->ID, 'approved' === $to ? '_bzp_decided_at' : '_bzp_paid_at', gmdate( 'Y-m-d H:i:s' ) );
		update_post_meta( $p->ID, 'approved' === $to ? '_bzp_decided_by' : '_bzp_paid_by', get_current_user_id() );
		return true;
	}

	/** Does this user hold the hub_coach role? */
	public static function is_coach( $user ) {
		return is_object( $user ) && ! empty( $user->roles ) && in_array( self::ROLE, (array) $user->roles, true );
	}

	/**
	 * What a Coach sees on My Coach ([2026-11-26 11:40 AM Johnny Chu - Chu Hoàng Anh] OHCL-D3A). Pure. Own data only; referred members show business name,
	 * code, journey stage and dates — never contact details.
	 *
	 * @param array   $coach    coaches() row.
	 * @param array[] $members  to_array() rows.
	 * @param array   $keys     key_id => key view (empty when the core is unreachable).
	 * @param array[] $payouts  payouts() rows.
	 * @param array   $policy   current policy.
	 */
	public static function coach_view( array $coach, array $members, array $keys, array $payouts, array $policy, $now_ts ) {
		$have = array();
		$mine = array();
		foreach ( $payouts as $p ) {
			if ( $p['coach'] !== (int) $coach['id'] ) {
				continue;
			}
			$mine[] = $p;
			foreach ( $p['lines'] as $l ) {
				$have[ $l['key'] ] = true;
			}
		}
		usort( $mine, function ( $a, $b ) { return strcmp( $b['month'], $a['month'] ); } );
		$refs = array();
		foreach ( $members as $m ) {
			if ( (int) $m['ref_coach'] !== (int) $coach['id'] ) {
				continue;
			}
			$kv     = $m['key_id'] && isset( $keys[ $m['key_id'] ] ) ? $keys[ $m['key_id'] ] : null;
			$lvl    = OHCL_Members::journey( $m, $kv, $now_ts );
			$refs[] = array(
				'name'      => $m['title'],
				'code'      => $m['code'],
				'since'     => substr( (string) $m['ref_at'], 0, 10 ),
				'stage'     => OHCL_Members::JOURNEY[ $lvl ],
				'level'     => $lvl,
				'holding'   => self::ref_active( $m['ref_at'], $now_ts ),
				'recognised' => isset( $have[ 'member:' . (int) $m['id'] ] ),
			);
		}
		$amount = null !== $coach['override'] ? (int) $coach['override'] : (int) $policy['referral_member'];
		return array(
			'amount'  => $amount,
			'members' => $refs,
			'payouts' => $mine,
			'sum'     => self::coach_summary( $coach['id'], $payouts, $members ),
			'core_ok' => (bool) $keys,
		);
	}

	/** Totals of one Coach: tentative / approved / paid (đồng) and referred members. */
	public static function coach_summary( $coach_id, array $payouts, array $members ) {
		$s = array( 'tentative' => 0, 'approved' => 0, 'paid' => 0, 'members' => 0 );
		foreach ( $payouts as $p ) {
			if ( $p['coach'] === (int) $coach_id ) {
				$s[ $p['status'] ] += $p['total'];
			}
		}
		foreach ( $members as $m ) {
			if ( (int) $m['ref_coach'] === (int) $coach_id ) {
				$s['members']++;
			}
		}
		return $s;
	}
}
