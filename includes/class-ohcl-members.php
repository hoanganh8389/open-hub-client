<?php
/**
 * Member 360° storage and states (OPEN-HUB-CLIENT §6; CHUAN-HO-SO-CO-SO-BAN-DO §4).
 *
 * One member = one post `bz_hub_member` on the Hub site ($wpdb->prefix, no new table).
 *   core meta  _bzm_*  (same for every Hub)      industry meta  _bzi_<key>  (contract @2 member_fields)
 *
 * Independent states, never one merged label: journey (computed), membership (admin), AI (from the
 * key at open-hub-core), verification (module 06, Đ2). Pure functions below take plain arrays so they
 * are tested without WordPress; the WP part only reads / writes post + meta.
 *
 * Linked to a key by key_id (number from the core), never the plain key.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 06:49 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1C — Member 360°: CPT bz_hub_member, meta lõi _bzm_*, trường ngành _bzi_*, trạng thái độc lập tính từ nguồn.

defined( 'ABSPATH' ) || exit;

final class OHCL_Members {

	const CPT = 'bz_hub_member';

	/** Journey stages in order (OPEN-HUB-CLIENT §6). Index = level 1..8. */
	const JOURNEY = array(
		1 => 'Mới đăng ký',
		2 => 'Đã tiếp nhận',
		3 => 'Đã kích hoạt',
		4 => 'Đang vận hành',
		5 => 'Đã xác minh',
		6 => 'Đạt chuẩn chuyên ngành',
		7 => 'Đang tăng trưởng',
		8 => 'Hội viên tiêu biểu',
	);

	const STATUS = array( 'active' => 'Đang hoạt động', 'paused' => 'Tạm dừng', 'left' => 'Đã rời' );

	/** Core text fields an admin edits: key => [label, max]. */
	const CORE_FIELDS = array(
		'segment'      => array( 'Phân khúc', 40 ),
		'province'     => array( 'Tỉnh / thành', 60 ),
		'address'      => array( 'Địa chỉ', 200 ),
		'phone'        => array( 'Điện thoại', 20 ),
		'email'        => array( 'Email', 100 ),
		'contact_zalo' => array( 'Zalo của cơ sở', 40 ),
		'site_url'     => array( 'Website', 200 ),
		'coach'        => array( 'Coach phụ trách (mã)', 40 ),
	);

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'label'           => 'Hội viên',
				'public'          => false,
				'show_ui'         => false, // một màn duy nhất: Hub › 02 · Hội viên (Member 360°)
				'show_in_rest'    => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}

	/* ===================== pure ===================== */

	/**
	 * AI state from the member's key view (core GET /hub/keys item) — or null when no key.
	 *
	 * @return array{code:string,label:string}
	 */
	public static function ai_state( $key, $now_ts ) {
		if ( ! is_array( $key ) ) {
			return array( 'code' => 'none', 'label' => 'Chưa bật' );
		}
		$st    = isset( $key['status'] ) ? $key['status'] : '';
		$until = ! empty( $key['plan_until'] ) ? strtotime( $key['plan_until'] . ' UTC' ) : 0;
		if ( 'trial' === $st ) {
			$days = $until ? max( 0, (int) ceil( ( $until - $now_ts ) / DAY_IN_SECONDS ) ) : 0;
			return array( 'code' => 'trial', 'label' => 'Dùng thử · còn ' . $days . ' ngày' );
		}
		if ( 'active' === $st || 'grace' === $st ) {
			return array( 'code' => 'active', 'label' => 'grace' === $st ? 'Đang dùng · ân hạn' : 'Đang dùng' );
		}
		if ( 'revoked' === $st ) {
			return array( 'code' => 'revoked', 'label' => 'Đã thu hồi' );
		}
		return array( 'code' => 'expired', 'label' => 'hub_inactive' === $st ? 'Hub tạm dừng' : 'Hết hạn' );
	}

	/** Verification still valid? meta _bzm_verify = {by, at, until, evidence[]}. */
	public static function verified( $verify, $now_ts ) {
		return is_array( $verify ) && ! empty( $verify['until'] ) && strtotime( $verify['until'] . ' UTC' ) >= $now_ts;
	}

	/**
	 * Journey level 1..5 computed from the other states (6–8 need modules 06/08, later waves).
	 *
	 * @param array      $m   member array from to_array().
	 * @param array|null $key key view or null.
	 * @return int
	 */
	public static function journey( array $m, $key, $now_ts ) {
		$level = 1;
		if ( ! empty( $m['accepted_at'] ) ) {
			$level = 2;
		}
		$live = is_array( $key ) && in_array( $key['status'], array( 'trial', 'active', 'grace' ), true );
		if ( $live ) {
			$level = 3;
			$used  = ! empty( $key['last_used_at'] ) ? strtotime( $key['last_used_at'] . ' UTC' ) : 0;
			if ( $used && $now_ts - $used <= 30 * DAY_IN_SECONDS ) {
				$level = 4;
				if ( self::verified( $m['verify'], $now_ts ) ) {
					$level = 5;
				}
			}
		}
		return $level;
	}

	/**
	 * Counts for dashboard question 1 and the stage chips.
	 *
	 * @param array[] $members to_array() rows.
	 * @param array   $keys_by_id key_id => key view.
	 * @return array{total:int, active:int, verified:int, by_stage:int[], by_segment:int[]}
	 */
	public static function counts( array $members, array $keys_by_id, $now_ts ) {
		$out = array( 'total' => 0, 'active' => 0, 'verified' => 0, 'by_stage' => array_fill( 1, 8, 0 ), 'by_segment' => array() );
		foreach ( $members as $m ) {
			$out['total']++;
			if ( 'active' === $m['status'] ) {
				$out['active']++;
			}
			if ( self::verified( $m['verify'], $now_ts ) ) {
				$out['verified']++;
			}
			$k = $m['key_id'] && isset( $keys_by_id[ $m['key_id'] ] ) ? $keys_by_id[ $m['key_id'] ] : null;
			$out['by_stage'][ self::journey( $m, $k, $now_ts ) ]++;
			$seg = $m['segment'] ? $m['segment'] : '—';
			$out['by_segment'][ $seg ] = ( isset( $out['by_segment'][ $seg ] ) ? $out['by_segment'][ $seg ] : 0 ) + 1;
		}
		arsort( $out['by_segment'] );
		return $out;
	}

	/**
	 * Clean an edit form against core fields + industry fields. Pure (no sanitize_* calls needed:
	 * strings are stripped and cut here).
	 *
	 * @param array $in      raw POST values (already unslashed).
	 * @param array $industry industry config (OHCL_Industry::current()).
	 * @return array|WP_Error { title, core{}, industry{} }
	 */
	public static function clean( array $in, array $industry ) {
		$title = isset( $in['title'] ) ? self::str( $in['title'], 120 ) : '';
		if ( '' === $title ) {
			return OHCL_Error::make( 'ohcl_member_title_required', 'Cần tên ' . ( isset( $industry['terms']['business'] ) ? $industry['terms']['business'] : 'cơ sở' ) . '.', 'Nhập tên rồi lưu lại.', 400 );
		}
		$core = array();
		foreach ( self::CORE_FIELDS as $k => $def ) {
			$core[ $k ] = isset( $in[ $k ] ) ? self::str( $in[ $k ], $def[1] ) : '';
		}
		if ( '' !== $core['segment'] && ! isset( $industry['segments'][ $core['segment'] ] ) ) {
			return OHCL_Error::make( 'ohcl_member_segment_invalid', 'Phân khúc không có trong danh sách của Hub.', 'Chọn một phân khúc trong ô chọn.', 400 );
		}
		if ( '' !== $core['email'] && ! filter_var( $core['email'], FILTER_VALIDATE_EMAIL ) ) {
			return OHCL_Error::make( 'ohcl_member_email_invalid', 'Email không đúng dạng.', 'Sửa email hoặc để trống.', 400 );
		}
		if ( '' !== $core['site_url'] && ! preg_match( '#^https?://[^\s<>"]+$#i', $core['site_url'] ) ) {
			return OHCL_Error::make( 'ohcl_member_url_invalid', 'Website phải bắt đầu bằng http:// hoặc https://.', 'Sửa địa chỉ website hoặc để trống.', 400 );
		}
		$core['coach'] = strtolower( preg_replace( '/[^a-z0-9-]/i', '', $core['coach'] ) );

		$ind = array();
		foreach ( $industry['member_fields'] as $f ) {
			$v = isset( $in[ 'f_' . $f['key'] ] ) ? $in[ 'f_' . $f['key'] ] : null;
			switch ( $f['type'] ) {
				case 'multi':
					$v = is_array( $v ) ? array_values( array_intersect( array_map( 'strval', $v ), array_keys( $f['options'] ) ) ) : array();
					break;
				case 'select':
					$v = is_string( $v ) && isset( $f['options'][ $v ] ) ? $v : '';
					break;
				case 'number':
					$v = is_numeric( $v ) ? $v + 0 : '';
					break;
				case 'bool':
					$v = ! empty( $v );
					break;
				case 'url':
					$v = is_string( $v ) && preg_match( '#^https?://[^\s<>"]+$#i', trim( $v ) ) ? trim( $v ) : '';
					break;
				default:
					$v = self::str( $v, 200 );
			}
			if ( ! empty( $f['required'] ) && ( '' === $v || array() === $v ) ) {
				return OHCL_Error::make( 'ohcl_member_field_required', 'Cần điền "' . $f['label'] . '".', 'Điền trường này rồi lưu lại.', 400 );
			}
			$ind[ $f['key'] ] = $v;
		}
		return array( 'title' => $title, 'core' => $core, 'industry' => $ind );
	}

	private static function str( $v, $max ) {
		if ( ! is_scalar( $v ) ) {
			return '';
		}
		$v = trim( preg_replace( '/\s+/u', ' ', strip_tags( (string) $v ) ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $v, 0, $max, 'UTF-8' ) : substr( $v, 0, $max );
	}

	/* ===================== WordPress ===================== */

	/** Post → plain array (core + industry meta). */
	public static function to_array( $post, array $industry ) {
		$id  = (int) $post->ID;
		$m   = array(
			'id'          => $id,
			'title'       => (string) $post->post_title,
			'code'        => 'HV-' . str_pad( (string) $id, 6, '0', STR_PAD_LEFT ),
			'status'      => (string) get_post_meta( $id, '_bzm_status', true ),
			'key_id'      => (int) get_post_meta( $id, '_bzm_key_id', true ),
			'key_prefix'  => (string) get_post_meta( $id, '_bzm_key_prefix', true ),
			'accepted_at' => (string) get_post_meta( $id, '_bzm_accepted_at', true ),
			'joined_at'   => (string) get_post_meta( $id, '_bzm_joined_at', true ),
			'verify'      => get_post_meta( $id, '_bzm_verify', true ),
			'industry'    => array(),
		);
		$m['status'] = isset( self::STATUS[ $m['status'] ] ) ? $m['status'] : 'active';
		foreach ( array_keys( self::CORE_FIELDS ) as $k ) {
			$m[ $k ] = (string) get_post_meta( $id, '_bzm_' . $k, true );
		}
		foreach ( $industry['member_fields'] as $f ) {
			$m['industry'][ $f['key'] ] = get_post_meta( $id, $f['meta'], true );
		}
		return $m;
	}

	/** All members (cap 1 000 for Đ1c), newest first. @return array[] */
	public static function all( $limit = 1000 ) {
		$ind   = OHCL_Industry::current();
		$posts = get_posts( array( 'post_type' => self::CPT, 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'numberposts' => (int) $limit, 'orderby' => 'ID', 'order' => 'DESC', 'no_found_rows' => true ) );
		$out   = array();
		foreach ( $posts as $p ) {
			$out[] = self::to_array( $p, $ind );
		}
		return $out;
	}

	/** @return array|null */
	public static function get( $id ) {
		$p = get_post( (int) $id );
		return $p && self::CPT === $p->post_type ? self::to_array( $p, OHCL_Industry::current() ) : null;
	}

	/**
	 * Create or update from a cleaned form.
	 *
	 * @return int|WP_Error post id
	 */
	public static function save( array $clean, $id = 0 ) {
		$id = (int) $id;
		if ( $id ) {
			$r = wp_update_post( array( 'ID' => $id, 'post_title' => $clean['title'] ), true );
		} else {
			$r = wp_insert_post( array( 'post_type' => self::CPT, 'post_status' => 'publish', 'post_title' => $clean['title'] ), true );
		}
		if ( is_wp_error( $r ) || ! $r ) {
			return OHCL_Error::make( 'ohcl_member_save_failed', 'Chưa lưu được hồ sơ hội viên.', 'Thử lại sau ít phút.', 500 );
		}
		$id = (int) $r;
		foreach ( $clean['core'] as $k => $v ) {
			update_post_meta( $id, '_bzm_' . $k, $v );
		}
		foreach ( OHCL_Industry::current()['member_fields'] as $f ) {
			if ( array_key_exists( $f['key'], $clean['industry'] ) ) {
				update_post_meta( $id, $f['meta'], $clean['industry'][ $f['key'] ] );
			}
		}
		if ( ! get_post_meta( $id, '_bzm_joined_at', true ) ) {
			update_post_meta( $id, '_bzm_joined_at', gmdate( 'Y-m-d H:i:s' ) );
			update_post_meta( $id, '_bzm_status', 'active' );
		}
		return $id;
	}

	/** Link a key issued for this member (id + prefix only, never the plain key). */
	public static function link_key( $id, $key_id, $key_prefix ) {
		update_post_meta( (int) $id, '_bzm_key_id', (int) $key_id );
		update_post_meta( (int) $id, '_bzm_key_prefix', (string) $key_prefix );
	}

	public static function accept( $id ) {
		if ( ! get_post_meta( (int) $id, '_bzm_accepted_at', true ) ) {
			update_post_meta( (int) $id, '_bzm_accepted_at', gmdate( 'Y-m-d H:i:s' ) );
		}
	}

	/** @return true|WP_Error */
	public static function set_status( $id, $status ) {
		if ( ! isset( self::STATUS[ $status ] ) ) {
			return OHCL_Error::make( 'ohcl_member_status_invalid', 'Trạng thái hội viên không hợp lệ.', 'Chọn Đang hoạt động, Tạm dừng hoặc Đã rời.', 400 );
		}
		update_post_meta( (int) $id, '_bzm_status', $status );
		return true;
	}

	/** key_id => key view, from the cached key list (empty when the core is unreachable). */
	public static function keys_by_id() {
		$all = OHCL_Overview::all_keys();
		$out = array();
		if ( ! is_wp_error( $all ) ) {
			foreach ( $all['items'] as $k ) {
				$out[ (int) $k['id'] ] = $k;
			}
		}
		return $out;
	}
}
