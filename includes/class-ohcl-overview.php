<?php
/**
 * Numbers of the Owner dashboard (OPEN-HUB-CLIENT §3) computed from real data only (R-HUB-6).
 *
 * Đ1b: question 2 (who really uses the technology) and question 5 (what to do next) come from the
 * Hub's keys at open-hub-core. Question 1 needs members (Member 360°, Đ1c) and stays "—".
 * Pure functions; the admin screen feeds them the key list.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 06:05 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1B — số của Tổng quan tính từ khoá thật ở lõi: phễu dùng công nghệ, việc cần làm.

defined( 'ABSPATH' ) || exit;

final class OHCL_Overview {

	const KEYS_CACHE = 'ohcl_keys_all';
	const MAX_PAGES  = 20; // 20 × 50 = 1 000 khoá; vượt thì báo "≥"

	/**
	 * @param array[] $keys items from open-hub-core GET /hub/keys.
	 * @param string  $now  'Y-m-d H:i:s' UTC.
	 * @return array
	 */
	public static function from_keys( array $keys, $now ) {
		$t       = strtotime( $now . ' UTC' );
		$out     = array(
			'issued'      => 0,
			'live'        => 0, // trial + active + grace
			'used_ever'   => 0,
			'used_30d'    => 0,
			'paid'        => 0,
			'trial'       => 0,
			'expiring_7d' => 0,
			'unused_7d'   => 0, // cấp ≥ 7 ngày mà chưa dùng lần nào
			'revoked'     => 0,
			'expired'     => 0,
		);
		foreach ( $keys as $k ) {
			$st = isset( $k['status'] ) ? (string) $k['status'] : '';
			$out['issued']++;
			if ( 'revoked' === $st ) {
				$out['revoked']++;
				continue;
			}
			if ( 'expired' === $st ) {
				$out['expired']++;
			}
			$live = in_array( $st, array( 'trial', 'active', 'grace' ), true );
			if ( $live ) {
				$out['live']++;
			}
			if ( 'trial' === $st ) {
				$out['trial']++;
			}
			if ( 'active' === $st && isset( $k['plan_code'] ) && 'trial' !== $k['plan_code'] ) {
				$out['paid']++;
			}
			$last = ! empty( $k['last_used_at'] ) ? strtotime( $k['last_used_at'] . ' UTC' ) : 0;
			if ( $last ) {
				$out['used_ever']++;
				if ( $t - $last <= 30 * DAY_IN_SECONDS ) {
					$out['used_30d']++;
				}
			} elseif ( ! empty( $k['created_at'] ) && $t - strtotime( $k['created_at'] . ' UTC' ) >= 7 * DAY_IN_SECONDS && $live ) {
				$out['unused_7d']++;
			}
			$until = ! empty( $k['plan_until'] ) ? strtotime( $k['plan_until'] . ' UTC' ) : 0;
			if ( $live && $until && $until >= $t && $until - $t <= 7 * DAY_IN_SECONDS ) {
				$out['expiring_7d']++;
			}
		}
		return $out;
	}

	/** Funnel of question 2: rows {label, n, pct}. */
	public static function funnel( array $s ) {
		$base = max( 1, $s['issued'] );
		$rows = array(
			array( 'Đã nhận khoá', $s['issued'] ),
			array( 'Khoá còn dùng được', $s['live'] ),
			array( 'Đã dùng trợ lý ít nhất một lần', $s['used_ever'] ),
			array( 'Dùng trong 30 ngày qua', $s['used_30d'] ),
			array( 'Đang trả phí', $s['paid'] ),
		);
		$out = array();
		foreach ( $rows as $r ) {
			$out[] = array( 'label' => $r[0], 'n' => $r[1], 'pct' => (int) round( 100 * $r[1] / $base ) );
		}
		return $out;
	}

	/**
	 * To-do list of question 5. Only items with a count > 0 or a real problem.
	 *
	 * @param array $s      from from_keys().
	 * @param array $report industry report (OHCL_Industry::report()).
	 * @param bool  $core_ok connection to the core works.
	 * @return array[] {label, n, href_page}
	 */
	public static function todo( array $s, array $report, $core_ok ) {
		$out = array();
		if ( ! $core_ok ) {
			$out[] = array( 'label' => 'Kết nối lõi Bizcity chưa đạt — chưa cấp được khoá', 'n' => '!', 'page' => 'open-hub-settings' );
		}
		if ( ! $report['ok'] ) {
			$out[] = array( 'label' => 'Dữ liệu ngành chưa đạt hợp đồng — site đang chạy cấu hình mặc định', 'n' => count( $report['errors'] ), 'page' => 'open-hub-settings' );
		} elseif ( $report['warnings'] ) {
			$out[] = array( 'label' => 'Dữ liệu ngành có mục bị bỏ qua', 'n' => count( $report['warnings'] ), 'page' => 'open-hub-settings' );
		}
		if ( $s['expiring_7d'] ) {
			$out[] = array( 'label' => 'Trợ lý AI sắp hết hạn trong 7 ngày', 'n' => $s['expiring_7d'], 'page' => 'open-hub-keys' );
		}
		if ( $s['unused_7d'] ) {
			$out[] = array( 'label' => 'Cơ sở nhận khoá ≥ 7 ngày mà chưa dùng trợ lý', 'n' => $s['unused_7d'], 'page' => 'open-hub-keys' );
		}
		return $out;
	}

	/**
	 * To-do items that come from MEMBER records (question 5): waiting for acceptance, accepted without a key,
	 * using the assistant but not verified, verification ending, no Coach, new support requests. Only items with
	 * a count > 0. Each item links to the member list filtered by journey stage when one fits. Pure.
	 *
	 * @param array[] $members  to_array() rows.
	 * @param array   $keys     key_id => key view (empty when the core is unreachable: stage counts then stay honest).
	 * @param bool    $coach_on module 04 on and at least one Coach exists.
	 * @return array[] { label, n, page, args }
	 */
	public static function member_todo( array $members, array $keys, $now_ts, $coach_on = false ) {
		// [2026-11-26 01:10 PM Johnny Chu - Chu Hoàng Anh] OHCL-Q15 — "Tôi cần làm gì tiếp theo?" có việc từ hồ sơ hội viên, không chỉ từ khoá.
		$c = array( 'wait' => 0, 'nokey' => 0, 'unverified' => 0, 'verify_end' => 0, 'nocoach' => 0, 'support' => 0 );
		foreach ( $members as $m ) {
			if ( 'active' !== $m['status'] ) {
				continue;
			}
			$k   = $m['key_id'] && isset( $keys[ $m['key_id'] ] ) ? $keys[ $m['key_id'] ] : null;
			$lvl = OHCL_Members::journey( $m, $k, $now_ts );
			if ( 1 === $lvl ) {
				$c['wait']++;
			} elseif ( 2 === $lvl && ! $m['key_id'] ) {
				$c['nokey']++;
			} elseif ( 4 === $lvl ) {
				$c['unverified']++;
			}
			if ( is_array( $m['verify'] ) && ! empty( $m['verify']['until'] ) ) {
				$u = strtotime( $m['verify']['until'] . ' UTC' );
				if ( $u >= $now_ts && $u - $now_ts <= 30 * DAY_IN_SECONDS ) {
					$c['verify_end']++;
				}
			}
			if ( $coach_on && '' === (string) $m['coach'] && $lvl >= 2 ) {
				$c['nocoach']++;
			}
			if ( '' !== (string) $m['support_at'] && $now_ts - strtotime( $m['support_at'] . ' UTC' ) <= 7 * DAY_IN_SECONDS ) {
				$c['support']++;
			}
		}
		$out = array();
		$add = function ( $n, $label, $args ) use ( &$out ) {
			if ( $n > 0 ) {
				$out[] = array( 'label' => $label, 'n' => $n, 'page' => 'open-hub-members', 'args' => $args );
			}
		};
		$add( $c['support'], 'Yêu cầu hỗ trợ mới của hội viên trong 7 ngày', array() );
		$add( $c['wait'], 'Hồ sơ mới chờ ban điều hành tiếp nhận', array( 'stage' => 1 ) );
		$add( $c['nokey'], 'Đã tiếp nhận, chờ cấp khoá trợ lý AI', array( 'stage' => 2 ) );
		$add( $c['unverified'], 'Đang dùng trợ lý, chờ xác minh để lên bản đồ', array( 'stage' => 4 ) );
		$add( $c['verify_end'], 'Xác minh sắp hết hạn trong 30 ngày', array() );
		$add( $c['nocoach'], 'Hội viên chưa có Coach phụ trách', array() );
		return $out;
	}

	/**
	 * All keys of this Hub (pages of 50), cached 5 minutes. Flushed after issue / revoke.
	 *
	 * @return array{items:array[], capped:bool}|WP_Error
	 */
	public static function all_keys() {
		$c = get_transient( self::KEYS_CACHE );
		if ( is_array( $c ) ) {
			return $c;
		}
		$items = array();
		$capped = false;
		for ( $p = 1; $p <= self::MAX_PAGES; $p++ ) {
			$r = OHCL_Core_Client::list_keys( $p );
			if ( is_wp_error( $r ) ) {
				return $r; // never cache a degraded result as if it were valid
			}
			$page = isset( $r['items'] ) && is_array( $r['items'] ) ? $r['items'] : array();
			$items = array_merge( $items, $page );
			if ( count( $page ) < 50 ) {
				break;
			}
			$capped = self::MAX_PAGES === $p;
		}
		$out = array( 'items' => $items, 'capped' => $capped );
		set_transient( self::KEYS_CACHE, $out, 5 * MINUTE_IN_SECONDS );
		return $out;
	}

	public static function flush() {
		delete_transient( self::KEYS_CACHE );
	}
}
