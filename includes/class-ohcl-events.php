<?php
/**
 * "Hub đã mang lại gì" — the success ledger (HUB-THANH-CONG §5.3, canon OH-Q4): one JSONL line per measured event,
 * a daily rollup of TOTALS per member per month into meta `_bzm_rollup`; no per-event row in the database.
 *
 * Measured today (public pages, no login needed):
 *   profile_view   the public profile of a member was opened by a visitor
 *   contact_click  the visitor pressed "Nhắn Zalo" on that profile
 *
 * Privacy: a line holds only { t (UTC), e (event), m (member id) }. No IP, no user agent, no email, no phone, no
 * conversation. A visitor is de-duplicated for one hour through a transient keyed by a hash, which stores nothing
 * readable and expires. The owner of a profile, administrators and obvious bots are never counted. Sample
 * (demo) businesses are never logged.
 *
 * "Not measured yet" is different from "measured zero": while no event has ever been written the reader shows
 * "—" (R-HUB-6), never 0.
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 04:07 PM Johnny Chu - Chu Hoàng Anh] OHCL-H3 — sổ đo: JSONL xoay vòng theo ngày + rollup tổng theo tháng; lượt xem hồ sơ, lượt bấm liên hệ; không IP, không liên hệ thô.

defined( 'ABSPATH' ) || exit;

final class OHCL_Events {

	const EVENTS      = array( 'profile_view' => 'Lượt xem hồ sơ', 'contact_click' => 'Lượt liên hệ từ Hub' );
	const DIR         = 'open-hub-client';
	const CURSOR      = 'ohcl_events_cursor'; // ngày cuối cùng đã gộp vào rollup (Y-m-d)
	const KEEP_DAYS   = 120;
	const MAX_ROLL    = 45; // số ngày gộp tối đa mỗi lần chạy
	const DEDUPE_SECS = 3600;

	/* ===================== pure ===================== */

	/** Count lines per member per event. @param array[] $lines decoded lines. @return array member id => event => n */
	public static function tally( array $lines ) {
		$out = array();
		foreach ( $lines as $l ) {
			if ( ! is_array( $l ) || empty( $l['m'] ) || empty( $l['e'] ) || ! isset( self::EVENTS[ $l['e'] ] ) ) {
				continue; // dòng hỏng hoặc sự kiện lạ: bỏ, không làm hỏng số đếm
			}
			$m = (int) $l['m'];
			$out[ $m ][ $l['e'] ] = ( isset( $out[ $m ][ $l['e'] ] ) ? $out[ $m ][ $l['e'] ] : 0 ) + 1;
		}
		return $out;
	}

	/** Add per-event counts of one day into a rollup map month => event => n. Pure. */
	public static function merge_rollup( array $roll, $month, array $per_event ) {
		foreach ( $per_event as $e => $n ) {
			if ( isset( self::EVENTS[ $e ] ) ) {
				$roll[ $month ][ $e ] = ( isset( $roll[ $month ][ $e ] ) ? (int) $roll[ $month ][ $e ] : 0 ) + (int) $n;
			}
		}
		ksort( $roll );
		return array_slice( $roll, -14, null, true ); // giữ 14 tháng gần nhất
	}

	/** Is this request an obvious bot or link preview? Pure. */
	public static function is_bot( $ua ) {
		return '' === trim( (string) $ua ) || 1 === preg_match( '/bot|crawl|spider|slurp|preview|facebookexternalhit|embedly|headless|python-requests|curl|wget|monitor|uptime/i', (string) $ua );
	}

	/** Days (Y-m-d) strictly after $cursor up to and including $until. Pure. */
	public static function days_after( $cursor, $until, $max = 400 ) {
		$out = array();
		$t   = '' === $cursor ? strtotime( $until . ' UTC' ) - ( $max - 1 ) * DAY_IN_SECONDS : strtotime( $cursor . ' UTC' ) + DAY_IN_SECONDS;
		$end = strtotime( $until . ' UTC' );
		for ( $n = 0; $t <= $end && $n < $max; $t += DAY_IN_SECONDS, $n++ ) {
			$out[] = gmdate( 'Y-m-d', $t );
		}
		return $out;
	}

	/* ===================== files ===================== */

	/** Log directory, created with a deny-all rule; '' when it cannot be made. */
	public static function dir() {
		if ( ! function_exists( 'wp_upload_dir' ) ) {
			return '';
		}
		$u = wp_upload_dir();
		if ( ! is_array( $u ) || empty( $u['basedir'] ) ) {
			return '';
		}
		$d = rtrim( $u['basedir'], '/\\' ) . '/' . self::DIR . '/events';
		if ( ! is_dir( $d ) ) {
			if ( ! @mkdir( $d, 0755, true ) && ! is_dir( $d ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
				return '';
			}
			@file_put_contents( $d . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
			@file_put_contents( $d . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		}
		return $d;
	}

	private static function file( $day ) {
		$d = self::dir();
		return '' === $d ? '' : $d . '/' . $day . '.jsonl';
	}

	/** Append one event. Never throws; false when it could not be written. */
	public static function log( $event, $member_id, $now_ts = 0 ) {
		if ( ! isset( self::EVENTS[ $event ] ) || (int) $member_id <= 0 ) {
			return false;
		}
		$now = $now_ts ? (int) $now_ts : time();
		$f   = self::file( gmdate( 'Y-m-d', $now ) );
		if ( '' === $f ) {
			return false;
		}
		$line = wp_json_encode( array( 't' => gmdate( 'Y-m-d H:i:s', $now ), 'e' => $event, 'm' => (int) $member_id ) ) . "\n";
		return false !== @file_put_contents( $f, $line, FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
	}

	/** @return array[] decoded lines of one day. */
	public static function read_day( $day ) {
		$f = self::file( $day );
		if ( '' === $f || ! is_file( $f ) ) {
			return array();
		}
		$out = array();
		foreach ( (array) file( $f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $row ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			$j = json_decode( $row, true );
			if ( is_array( $j ) ) {
				$out[] = $j;
			}
		}
		return $out;
	}

	/** True once at least one event has ever been written (otherwise "chưa đo", not zero). */
	public static function active() {
		$d = self::dir();
		return '' !== $d && (bool) glob( $d . '/*.jsonl' );
	}

	/* ===================== recording (public requests) ===================== */

	/**
	 * Record one public event for a member unless it should not count. De-duplicates one visitor for an hour.
	 *
	 * @param array  $m      member row (to_array).
	 * @param array  $visitor { ip, ua, uid, admin } — the caller passes facts; nothing here reads superglobals.
	 * @return bool counted
	 */
	public static function record( $event, array $m, array $visitor ) {
		if ( ! isset( self::EVENTS[ $event ] ) || ! empty( $m['demo'] ) || 'active' !== $m['status'] ) {
			return false;
		}
		if ( ! empty( $visitor['admin'] ) || ( ! empty( $visitor['uid'] ) && (int) $visitor['uid'] === (int) $m['user'] ) || self::is_bot( isset( $visitor['ua'] ) ? $visitor['ua'] : '' ) ) {
			return false;
		}
		$key = 'ohcl_ev_' . md5( $event . '|' . (int) $m['id'] . '|' . ( isset( $visitor['ip'] ) ? $visitor['ip'] : '' ) . '|' . ( isset( $visitor['ua'] ) ? $visitor['ua'] : '' ) );
		if ( get_transient( $key ) ) {
			return false;
		}
		set_transient( $key, 1, self::DEDUPE_SECS );
		return self::log( $event, (int) $m['id'] );
	}

	/** Facts about the current request (admin / own profile / bot decisions use these). */
	public static function visitor() {
		return array(
			'ip'    => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			'ua'    => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'uid'   => is_user_logged_in() ? get_current_user_id() : 0,
			'admin' => function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ),
		);
	}

	/* ===================== rollup + reading ===================== */

	/** Daily cron: fold finished days into `_bzm_rollup`, drop old files. @return int days folded */
	public static function rollup() {
		$d = self::dir();
		if ( '' === $d ) {
			return 0;
		}
		$yesterday = gmdate( 'Y-m-d', time() - DAY_IN_SECONDS );
		$cursor    = (string) get_option( self::CURSOR, '' );
		if ( '' === $cursor ) {
			$files = (array) glob( $d . '/*.jsonl' );
			sort( $files );
			$first = $files ? basename( $files[0], '.jsonl' ) : '';
			$cursor = '' !== $first ? gmdate( 'Y-m-d', strtotime( $first . ' UTC' ) - DAY_IN_SECONDS ) : '';
		}
		if ( '' === $cursor || $cursor >= $yesterday ) {
			return 0;
		}
		$days = array_slice( self::days_after( $cursor, $yesterday, self::MAX_ROLL ), 0, self::MAX_ROLL );
		$adds = array(); // member => month => event => n
		foreach ( $days as $day ) {
			foreach ( self::tally( self::read_day( $day ) ) as $m => $per ) {
				foreach ( $per as $e => $n ) {
					$mo = substr( $day, 0, 7 );
					$adds[ $m ][ $mo ][ $e ] = ( isset( $adds[ $m ][ $mo ][ $e ] ) ? $adds[ $m ][ $mo ][ $e ] : 0 ) + $n;
				}
			}
		}
		foreach ( $adds as $m => $months ) {
			$roll = get_post_meta( $m, '_bzm_rollup', true );
			$roll = is_array( $roll ) ? $roll : array();
			foreach ( $months as $mo => $per ) {
				$roll = self::merge_rollup( $roll, $mo, $per );
			}
			update_post_meta( $m, '_bzm_rollup', $roll );
		}
		update_option( self::CURSOR, end( $days ), false );
		$old = gmdate( 'Y-m-d', time() - self::KEEP_DAYS * DAY_IN_SECONDS );
		foreach ( (array) glob( $d . '/*.jsonl' ) as $f ) {
			if ( basename( $f, '.jsonl' ) < $old && basename( $f, '.jsonl' ) <= end( $days ) ) {
				@unlink( $f ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
			}
		}
		return count( $days );
	}

	/**
	 * This month's counts for a member: rollup of finished days + the raw files not yet rolled up.
	 *
	 * @return array|null event => n, or null when nothing has ever been measured ("—").
	 */
	public static function month_counts( $member_id, $now_ts ) {
		if ( ! self::active() ) {
			return null;
		}
		$month = gmdate( 'Y-m', $now_ts );
		$roll  = get_post_meta( (int) $member_id, '_bzm_rollup', true );
		$out   = array_fill_keys( array_keys( self::EVENTS ), 0 );
		if ( is_array( $roll ) && isset( $roll[ $month ] ) ) {
			foreach ( $roll[ $month ] as $e => $n ) {
				if ( isset( $out[ $e ] ) ) {
					$out[ $e ] = (int) $n;
				}
			}
		}
		$cursor = (string) get_option( self::CURSOR, '' );
		$today  = gmdate( 'Y-m-d', $now_ts );
		foreach ( self::days_after( $cursor, $today, 40 ) as $day ) {
			if ( 0 !== strpos( $day, $month ) ) {
				continue;
			}
			$t = self::tally( self::read_day( $day ) );
			if ( isset( $t[ (int) $member_id ] ) ) {
				foreach ( $t[ (int) $member_id ] as $e => $n ) {
					$out[ $e ] += $n;
				}
			}
		}
		return $out;
	}
}
