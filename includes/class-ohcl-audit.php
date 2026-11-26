<?php
/**
 * Audit log of sensitive actions (HUB-THANH-CONG §5.7): who did what, when, to which member — one JSONL line,
 * one file per month, `uploads/open-hub-client/audit/YYYY-MM.jsonl` behind a deny-all rule. No database row.
 *
 * A line holds { t (UTC), a (action), by (WordPress user id, 0 = the member / system), m (member id or 0), n (short
 * note) }. Never a key, a secret, contact data or message content: callers pass only a status word or a count, and
 * the note is scrubbed of anything that looks like a key before it is written.
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 04:31 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6 — nhật ký thao tác nhạy cảm: ai làm gì, lúc nào, với hồ sơ nào; JSONL theo tháng, không bí mật, không liên hệ.

defined( 'ABSPATH' ) || exit;

final class OHCL_Audit {

	const ACTIONS = array(
		'member_accepted'   => 'Tiếp nhận hội viên',
		'key_issued'        => 'Cấp khoá trợ lý',
		'key_revoked'       => 'Thu hồi khoá trợ lý',
		'member_verified'   => 'Xác minh hồ sơ',
		'member_unverified' => 'Bỏ xác minh',
		'member_status'     => 'Đổi trạng thái hội viên',
		'outcomes_reviewed' => 'Duyệt / gỡ kết quả hội viên',
		'policy_changed'    => 'Đổi chính sách affiliate',
		'payout_moved'      => 'Đổi trạng thái kỳ chi trả',
		'export_hub'        => 'Xuất danh sách hội viên',
		'export_member'     => 'Hội viên tải dữ liệu của mình',
		'member_left'       => 'Hội viên rời Hub',
		'member_anonymized' => 'Xoá thông tin hội viên',
	);

	/** Remove anything that looks like a key or a long secret from a note, cut to 120 characters. Pure. */
	public static function scrub( $note ) {
		$n = trim( preg_replace( '/\s+/u', ' ', strip_tags( (string) $note ) ) );
		$n = preg_replace( '/biz-[0-9a-f]{16,}|ohs_[0-9a-f]{16,}|[A-Za-z0-9+\/=_-]{32,}/', '[đã ẩn]', $n );
		return function_exists( 'mb_substr' ) ? mb_substr( $n, 0, 120, 'UTF-8' ) : substr( $n, 0, 120 );
	}

	/** Append one line. Never throws; false when it could not be written or the action is unknown. */
	public static function log( $action, $member_id = 0, $note = '', $by = null, $now_ts = 0 ) {
		if ( ! isset( self::ACTIONS[ $action ] ) ) {
			return false;
		}
		$now = $now_ts ? (int) $now_ts : time();
		$d   = OHCL_Events::dir( 'audit' );
		if ( '' === $d ) {
			return false;
		}
		$line = wp_json_encode( array( 't' => gmdate( 'Y-m-d H:i:s', $now ), 'a' => $action, 'by' => null === $by ? (int) get_current_user_id() : (int) $by, 'm' => (int) $member_id, 'n' => self::scrub( $note ) ) ) . "\n";
		return false !== @file_put_contents( $d . '/' . gmdate( 'Y-m', $now ) . '.jsonl', $line, FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
	}

	/**
	 * Newest lines first, across the latest months.
	 *
	 * @return array[] { t, a, label, by, m, n }
	 */
	public static function recent( $limit = 100 ) {
		$d = OHCL_Events::dir( 'audit' );
		if ( '' === $d ) {
			return array();
		}
		$files = (array) glob( $d . '/*.jsonl' );
		rsort( $files );
		$out = array();
		foreach ( array_slice( $files, 0, 3 ) as $f ) {
			$rows = array();
			foreach ( (array) file( $f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $row ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
				$j = json_decode( $row, true );
				if ( is_array( $j ) && isset( $j['a'], self::ACTIONS[ $j['a'] ] ) ) {
					$rows[] = array( 't' => (string) $j['t'], 'a' => $j['a'], 'label' => self::ACTIONS[ $j['a'] ], 'by' => (int) $j['by'], 'm' => (int) $j['m'], 'n' => isset( $j['n'] ) ? (string) $j['n'] : '' );
				}
			}
			$out = array_merge( $out, array_reverse( $rows ) );
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return array_slice( $out, 0, max( 1, min( 500, (int) $limit ) ) );
	}
}
