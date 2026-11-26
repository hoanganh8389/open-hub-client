<?php
/**
 * Eight member benefits and where each member stands (HUB-THANH-CONG §3, §5.2). The Hub Owner sees how many
 * members reach each benefit; a member sees "Quyền lợi của tôi" in My Hub with the next step for each.
 *
 * States are COMPUTED from data the plugin already holds (journey, key, map level, verification, certificate,
 * Coach, modules) — nothing new is stored, nothing is estimated. A benefit that Bizcity or the Hub cannot yet
 * measure stops at the highest state it can prove and says so (`note`), instead of claiming "in use".
 *
 *   locked → eligible → granted → activated → used        (plus `soon` for a benefit not offered yet)
 *
 * Availability is the honest readiness of the benefit TODAY (accepted · limited · roadmap) and is part of the
 * code until the resource register (wave H1) takes over; an industry plugin may refine it with the filter
 * `ohcl_benefit_availability`. A `roadmap` benefit is never counted as received.
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 02:20 PM Johnny Chu - Chu Hoàng Anh] OHCL-H2 — sổ quyền lợi tính từ dữ liệu có sẵn: tám quyền lợi, trạng thái từng hội viên, phễu cho chủ Hub, việc tiếp theo cho hội viên.

defined( 'ABSPATH' ) || exit;

final class OHCL_Benefits {

	/** key => { label, icon, module (null = always), availability today, what Bizcity brings }. */
	const DEFS = array(
		'tech'      => array( 'Công nghệ', 'gift', '03', 'accepted', 'Bộ ứng dụng mã nguồn mở và khoá trợ lý, bản quyền 0đ.' ),
		'training'  => array( 'Đào tạo', 'book', '05', 'limited', 'Lộ trình học và kho mẫu của ngành.' ),
		'marketing' => array( 'Khách hàng', 'pin', '07', 'accepted', 'Hồ sơ công khai trên bản đồ và danh bạ ngành.' ),
		'ai'        => array( 'Trợ lý AI', 'chat', null, 'accepted', 'Trợ lý trên Zalo, dùng thử rồi theo mức dùng.' ),
		'coach'     => array( 'Coach đồng hành', 'coach', '04', 'limited', 'Người phụ trách cùng bạn lập kế hoạch 90 ngày.' ),
		'network'   => array( 'Kết nối giao thương', 'users', '09', 'limited', 'Có tên trong danh sách giao thương theo loại hình.' ),
		'trust'     => array( 'Uy tín & xác minh', 'shield', '06', 'accepted', 'Xác minh, giấy chứng nhận, tra cứu bằng mã hoặc QR.' ),
		'growth'    => array( 'Cơ hội tăng trưởng', 'flow', null, 'roadmap', 'Kết nối mở rộng và đối tác; đang xây dựng.' ),
	);

	const STATES = array(
		'locked'    => 'Chưa mở',
		'eligible'  => 'Có thể nhận',
		'granted'   => 'Đã trao',
		'activated' => 'Đã bật',
		'used'      => 'Đang dùng',
		'soon'      => 'Sắp ra mắt',
		'off'       => 'Hub chưa bật',
	);

	const RANK = array( 'locked' => 0, 'eligible' => 1, 'granted' => 2, 'activated' => 3, 'used' => 4 );

	const AVAILABILITY = array( 'accepted' => 'Đã nghiệm thu', 'limited' => 'Giới hạn', 'beta' => 'Beta', 'roadmap' => 'Lộ trình' );

	/* ===================== pure ===================== */

	/**
	 * Where one member stands on one benefit. Pure.
	 *
	 * @param string     $key  benefit key.
	 * @param array      $m    OHCL_Members::to_array() row.
	 * @param array|null $kv   key view from the core, or null.
	 * @param array      $ctx  { modules: id=>bool, availability: key=>string, tracks: int, now: int }.
	 * @return array { key, label, icon, availability, state, state_label, why, note, next:{label,target} }
	 */
	public static function state( $key, array $m, $kv, array $ctx ) {
		$d    = self::DEFS[ $key ];
		$now  = (int) $ctx['now'];
		$avail = isset( $ctx['availability'][ $key ] ) ? $ctx['availability'][ $key ] : $d[3];
		$out  = array( 'key' => $key, 'label' => $d[0], 'icon' => $d[1], 'brings' => $d[4], 'availability' => $avail, 'state' => 'locked', 'state_label' => '', 'why' => '', 'note' => '', 'next' => array( 'label' => '', 'target' => '' ) );
		$set  = function ( $state, $why, $next_label = '', $target = '', $note = '' ) use ( &$out ) {
			$out['state']       = $state;
			$out['state_label'] = self::STATES[ $state ];
			$out['why']         = $why;
			$out['note']        = $note;
			$out['next']        = array( 'label' => $next_label, 'target' => $target );
		};
		if ( 'roadmap' === $avail ) {
			$set( 'soon', 'Quyền lợi này đang được xây dựng; chưa có gì để nhận.' );
			return $out;
		}
		if ( null !== $d[2] && empty( $ctx['modules'][ $d[2] ] ) ) {
			$set( 'off', 'Hub của bạn chưa bật quyền lợi này.' );
			return $out;
		}
		$lvl      = OHCL_Members::journey( $m, $kv, $now );
		$accepted = 'active' === $m['status'] && $lvl >= 2;
		if ( ! $accepted ) {
			$set( 'locked', 'active' === $m['status'] ? 'Cần ban điều hành tiếp nhận hồ sơ của bạn.' : 'Hồ sơ đang tạm dừng hoặc đã rời Hub.', '', '' );
			return $out;
		}
		$live  = is_array( $kv ) && in_array( $kv['status'], array( 'trial', 'active', 'grace' ), true );
		$ever  = $live && ! empty( $kv['last_used_at'] );
		$used  = $live && $lvl >= 4;
		$map   = OHCL_Members::level( $m, $kv, $now );
		$ver   = OHCL_Members::verified( $m['verify'], $now );
		switch ( $key ) {
			case 'tech':
				if ( $live ) {
					$set( 'granted', 'Bạn đã có khoá và bộ công nghệ của ngành.', 'Xem bộ công nghệ của tôi', 'cong-nghe', 'Mức dùng từng ứng dụng sẽ hiện khi site của bạn báo về; hiện chỉ biết trợ lý AI có dùng hay chưa.' );
				} else {
					$set( 'eligible', 'Hồ sơ đã được tiếp nhận; chờ ban điều hành cấp khoá.', 'Nhắn ban điều hành cấp khoá', 'ho-tro' );
				}
				break;
			case 'ai':
				if ( $used ) {
					$set( 'used', 'Trợ lý AI đã được dùng trong 30 ngày gần đây.', 'Xem trợ lý của tôi', 'cong-nghe' );
				} elseif ( $ever ) {
					$set( 'activated', 'Bạn đã dùng trợ lý nhưng chưa dùng trong 30 ngày qua.', 'Dùng lại trợ lý trên Zalo', 'cong-nghe' );
				} elseif ( $live ) {
					$set( 'granted', 'Đã có khoá trợ lý; chưa dùng lần nào.', 'Bật trợ lý trên Zalo', 'cong-nghe' );
				} else {
					$set( 'eligible', 'Có thể nhận khoá dùng thử.', 'Nhắn ban điều hành cấp khoá', 'ho-tro' );
				}
				break;
			case 'training':
				$ctx['tracks'] > 0
					? $set( 'eligible', 'Hub đã có lộ trình học cho ngành.', 'Xem lộ trình học', 'page:academy', 'Ghi danh và theo dõi tiến độ học sẽ mở khi Hub đưa khoá học lên.' )
					: $set( 'eligible', 'Hub chưa công bố lộ trình học.', '', '', 'Ghi danh và theo dõi tiến độ học chưa mở.' );
				break;
			case 'marketing':
				if ( 4 === $map ) {
					$set( 'used', 'Hồ sơ của bạn đang hiện công khai trên bản đồ và danh bạ.', 'Xem trang công khai', 'place' );
				} elseif ( $ver ) {
					$set( 'granted', 'Bạn đã xác minh; còn thiếu đồng ý công khai, toạ độ hoặc trợ lý đang chạy.', 'Hoàn thiện hồ sơ công khai', 'ho-so' );
				} else {
					$set( 'eligible', 'Sau khi xác minh, hồ sơ của bạn có thể lên bản đồ ngành.', 'Hoàn thiện hồ sơ', 'ho-so' );
				}
				break;
			case 'coach':
				'' !== (string) $m['coach']
					? $set( 'granted', 'Bạn đã có Coach phụ trách.', 'Xem lộ trình 90 ngày', 'lo-trinh', 'Lịch gặp và kế hoạch riêng với Coach sẽ hiện ở đây khi Hub mở tính năng này.' )
					: $set( 'eligible', 'Ban điều hành sẽ phân công Coach theo loại hình của bạn.', '', '' );
				break;
			case 'network':
				4 === $map
					? $set( 'granted', 'Bạn có tên trong danh sách giao thương theo loại hình.', 'Xem trang giao thương', 'page:trade', 'Số lượt kết nối chưa đo được.' )
					: $set( 'eligible', 'Có tên trong giao thương khi hồ sơ lên bản đồ công khai.', 'Hoàn thiện hồ sơ', 'ho-so' );
				break;
			case 'trust':
				if ( OHCL_Members::certified( $m, $now ) ) {
					$set( 'used', 'Bạn đang có huy hiệu Certified còn hiệu lực.', 'Xem chứng nhận của tôi', 'chung-nhan' );
				} elseif ( $ver ) {
					$set( 'granted', 'Hồ sơ của bạn đã được xác minh (Verified).', 'Xem chứng nhận của tôi', 'chung-nhan' );
				} else {
					$set( 'eligible', 'Ban điều hành có thể xác minh hồ sơ của bạn.', '', '' );
				}
				break;
		}
		return $out;
	}

	/**
	 * Funnel over members: per benefit how many are at each state, and how many have received it
	 * (granted, activated or used) out of the accepted members. Pure.
	 *
	 * @param array[] $rows state() results grouped by member: member_id => [ benefit key => state row ].
	 * @return array[] { key, label, availability, counts, reached, of, pct }
	 */
	public static function funnel( array $rows ) {
		$out = array();
		foreach ( self::DEFS as $key => $d ) {
			$c = array( 'locked' => 0, 'eligible' => 0, 'granted' => 0, 'activated' => 0, 'used' => 0 );
			$of = 0;
			$off = 0;
			$soon = false;
			foreach ( $rows as $per ) {
				$s = $per[ $key ]['state'];
				if ( 'soon' === $s ) {
					$soon = true;
					continue;
				}
				if ( 'off' === $s ) {
					$off++;
					continue;
				}
				$c[ $s ]++;
				if ( 'locked' !== $s ) {
					$of++;
				}
			}
			$reached = $c['granted'] + $c['activated'] + $c['used'];
			$avail   = $rows ? reset( $rows )[ $key ]['availability'] : $d[3];
			$out[]   = array( 'key' => $key, 'label' => $d[0], 'availability' => $avail, 'counts' => $c, 'reached' => $reached, 'of' => $of, 'pct' => $of ? (int) round( 100 * $reached / $of ) : 0, 'soon' => $soon, 'off' => $off > 0 && 0 === $of && ! $soon );
		}
		return $out;
	}

	/** Benefits the member has received (granted or further). Pure. */
	public static function received( array $states ) {
		return count( array_filter( $states, function ( $s ) { return isset( self::RANK[ $s['state'] ] ) && self::RANK[ $s['state'] ] >= 2; } ) );
	}

	/* ===================== WordPress ===================== */

	/** Context shared by every member of the Hub (modules, readiness, learning tracks). */
	public static function context( $now_ts ) {
		$cfg  = OHCL_Industry::current();
		$mods = array();
		foreach ( array( '03', '04', '05', '06', '07', '09' ) as $id ) {
			$mods[ $id ] = OHCL_Modules::enabled( $id );
		}
		return array(
			'modules'      => $mods,
			'availability' => (array) apply_filters( 'ohcl_benefit_availability', array() ),
			'tracks'       => count( $cfg['academy_tracks'] ),
			'now'          => (int) $now_ts,
		);
	}

	/** Link for a "next step": page:<key> = a Hub page, place = own public profile, else a My Hub tab. '' when not available. */
	public static function next_url( $target, array $m ) {
		if ( '' === $target ) {
			return '';
		}
		if ( 0 === strpos( $target, 'page:' ) ) {
			$k = substr( $target, 5 );
			return OHCL_Front::page_on( $k ) ? OHCL_Front::url( $k ) : '';
		}
		if ( 'place' === $target ) {
			return OHCL_Front::place_url( $m['id'], $m['title'] );
		}
		return OHCL_Front::myhub_url( 'ho-tro' === $target || isset( OHCL_MyHub::TABS[ $target ] ) ? $target : '' );
	}

	/** All eight states of one member. @return array key => state row */
	public static function for_member( array $m, $kv, array $ctx ) {
		$out = array();
		foreach ( array_keys( self::DEFS ) as $key ) {
			$out[ $key ] = self::state( $key, $m, $kv, $ctx );
		}
		return $out;
	}

	/** Funnel of the whole Hub, with the members behind a benefit / state when asked. */
	public static function hub_view( $benefit = '', $state = '' ) {
		$now  = time();
		$ctx  = self::context( $now );
		$keys = OHCL_Members::keys_by_id();
		$rows = array();
		$who  = array();
		foreach ( OHCL_Members::all() as $m ) {
			$kv   = $m['key_id'] && isset( $keys[ $m['key_id'] ] ) ? $keys[ $m['key_id'] ] : null;
			$per  = self::for_member( $m, $kv, $ctx );
			$rows[ $m['id'] ] = $per;
			if ( '' !== $benefit && isset( $per[ $benefit ] ) && ( '' === $state || $per[ $benefit ]['state'] === $state ) ) {
				$who[] = array( 'id' => (int) $m['id'], 'code' => $m['code'], 'title' => $m['title'], 'state' => $per[ $benefit ]['state'] );
			}
		}
		return array( 'funnel' => self::funnel( $rows ), 'members' => $who, 'core_ok' => (bool) $keys );
	}
}
