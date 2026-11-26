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
		'shop_url'     => array( 'Website bán hàng (WooCommerce)', 200 ), // [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-SHOP — đường B: sản phẩm từ Woo của chính cơ sở
		'coach'        => array( 'Coach phụ trách (mã)', 40 ),
		'hours'        => array( 'Giờ mở cửa', 120 ),
		'lat'          => array( 'Vĩ độ (bản đồ)', 12 ),
		'lng'          => array( 'Kinh độ (bản đồ)', 12 ),
	);

	/** Public map levels (CHUAN-HO-SO-CO-SO-BAN-DO §3). Only V4 is ever public. */
	const LEVELS = array( 1 => 'V1 · Đã đăng ký', 2 => 'V2 · Có khoá đang chạy', 3 => 'V3 · Leader xác minh', 4 => 'V4 · Công khai' );

	/** Evidence a leader may tick when verifying (V3). */
	const EVIDENCE = array( 'gpkd' => 'Giấy phép kinh doanh', 'chung-nhan' => 'Chứng nhận ngành', 'ghe-tham' => 'Đã ghé thăm', 'goi-video' => 'Gọi video' );

	const PUBLIC_CACHE = 'ohcl_public_dir';

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

	/** Holds at least one certificate that is neither revoked nor expired? (`_bzm_certs` short copy). */
	public static function certified( array $m, $now_ts ) {
		return (bool) self::valid_certs( $m, $now_ts );
	}

	/** Valid certificates from the member's short copy: code => label. */
	public static function valid_certs( array $m, $now_ts ) {
		// [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — chứng nhận còn hiệu lực đọc từ bản sao ngắn trên hồ sơ (không truy vấn từng giấy).
		$out = array();
		foreach ( isset( $m['certs'] ) && is_array( $m['certs'] ) ? $m['certs'] : array() as $code => $c ) {
			if ( empty( $c['revoked'] ) && ! empty( $c['until'] ) && strtotime( $c['until'] . ' UTC' ) >= $now_ts ) {
				$out[ $code ] = $c['label'];
			}
		}
		return $out;
	}

	/**
	 * Journey level 1..6 computed from the other states (6 = verified + a valid certificate, module 06;
	 * 7–8 need module 08, later wave).
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
					// [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — bậc 6 "Đạt chuẩn chuyên ngành" khi có chứng nhận còn hiệu lực.
					if ( self::certified( $m, $now_ts ) ) {
						$level = 6;
					}
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
		// [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-SHOP — cửa hàng phải là https (Store API công khai đọc qua https).
		if ( '' !== $core['shop_url'] && class_exists( 'OHCL_Shop' ) && '' === OHCL_Shop::endpoint( $core['shop_url'] ) ) {
			return OHCL_Error::make( 'ohcl_member_shop_invalid', 'Website bán hàng phải là địa chỉ https:// của site có WooCommerce.', 'Sửa địa chỉ (ví dụ https://tencoso.vn) hoặc để trống.', 400 );
		}
		$core['coach'] = strtolower( preg_replace( '/[^a-z0-9-]/i', '', $core['coach'] ) );
		// [2026-11-25 09:06 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — toạ độ phải là số hợp lệ, có đủ cặp; đồng ý công khai là ô riêng.
		foreach ( array( 'lat' => 90, 'lng' => 180 ) as $k => $lim ) {
			if ( '' !== $core[ $k ] && ( ! is_numeric( str_replace( ',', '.', $core[ $k ] ) ) || abs( (float) str_replace( ',', '.', $core[ $k ] ) ) > $lim ) ) {
				return OHCL_Error::make( 'ohcl_member_geo_invalid', 'Toạ độ không hợp lệ.', 'Nhập vĩ độ, kinh độ dạng số (ví dụ 21.0285 và 105.8542) hoặc để trống cả hai.', 400 );
			}
			$core[ $k ] = '' === $core[ $k ] ? '' : (string) round( (float) str_replace( ',', '.', $core[ $k ] ), 6 );
		}
		if ( ( '' === $core['lat'] ) !== ( '' === $core['lng'] ) ) {
			return OHCL_Error::make( 'ohcl_member_geo_pair', 'Cần đủ cả vĩ độ và kinh độ.', 'Nhập cả hai số hoặc để trống cả hai.', 400 );
		}

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
		return array( 'title' => $title, 'core' => $core, 'industry' => $ind, 'consent' => ! empty( $in['public_consent'] ), 'shop_consent' => ! empty( $in['shop_consent'] ) );
	}

	/**
	 * Map level V1–V4 (computed, never stored). V4 = consent + coords + V2 (live key used ≤ 30 days)
	 * + V3 (leader verification still valid) + membership not paused / left.
	 */
	public static function level( array $m, $key, $now_ts ) {
		// [2026-11-25 09:06 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — bậc bản đồ tính lại mỗi lần; khoá ngưng / xác minh hết hạn ⇒ rớt bậc, ẩn khỏi công khai.
		$v2 = false;
		if ( is_array( $key ) && in_array( $key['status'], array( 'trial', 'active', 'grace' ), true ) && ! empty( $key['last_used_at'] ) ) {
			$v2 = $now_ts - strtotime( $key['last_used_at'] . ' UTC' ) <= 30 * DAY_IN_SECONDS;
		}
		$v3 = self::verified( $m['verify'], $now_ts );
		if ( ! $v2 ) {
			return 1;
		}
		if ( ! $v3 ) {
			return 2;
		}
		$geo = '' !== (string) $m['lat'] && '' !== (string) $m['lng'];
		return ( $geo && '' !== (string) $m['consent'] && 'active' === $m['status'] ) ? 4 : 3;
	}

	/** Public card of a V4 member: public fields only, coordinates rounded to 4 decimals (~11 m). */
	public static function public_view( array $m, array $industry ) {
		$ind = array();
		foreach ( $industry['member_fields'] as $f ) {
			if ( empty( $f['public'] ) ) {
				continue;
			}
			$v = isset( $m['industry'][ $f['key'] ] ) ? $m['industry'][ $f['key'] ] : '';
			if ( is_array( $v ) ) {
				$v = array_values( array_map( function ( $x ) use ( $f ) { return isset( $f['options'][ $x ] ) ? $f['options'][ $x ] : $x; }, $v ) );
			} elseif ( 'select' === $f['type'] && isset( $f['options'][ $v ] ) ) {
				$v = $f['options'][ $v ];
			}
			if ( '' !== $v && array() !== $v && null !== $v ) {
				$ind[] = array( 'key' => $f['key'], 'label' => $f['label'], 'type' => $f['type'], 'value' => $v );
			}
		}
		return array(
			'id'       => (int) $m['id'],
			'title'    => $m['title'],
			'segment'  => $m['segment'],
			'seg_label' => isset( $industry['segments'][ $m['segment'] ] ) ? $industry['segments'][ $m['segment'] ] : '',
			'province' => $m['province'],
			'address'  => $m['address'],
			'hours'    => $m['hours'],
			'zalo'     => $m['contact_zalo'],
			'site_url' => $m['site_url'],
			'lat'      => round( (float) $m['lat'], 4 ),
			'lng'      => round( (float) $m['lng'], 4 ),
			'fields'   => $ind,
			'certs'    => self::valid_certs( $m, time() ),
			'demo'     => ! empty( $m['demo'] ), // [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-DEMO — cơ sở mẫu: nhãn "Mẫu", không nút Zalo
			'shop'     => ! empty( $m['shop_consent'] ) && ! empty( $m['shop_url'] ) ? $m['shop_url'] : '', // [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-SHOP — chỉ khi cơ sở đồng ý // [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — huy hiệu chứng nhận trên trang công khai
		);
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
			'consent'     => (string) get_post_meta( $id, '_bzm_public_consent', true ), // ngày đồng ý, rỗng = chưa
			'source'      => (string) get_post_meta( $id, '_bzm_source', true ),
			'user'        => (int) get_post_meta( $id, '_bzm_user', true ), // [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — tài khoản WordPress của hội viên (My Hub)
			'certs'       => get_post_meta( $id, '_bzm_certs', true ), // [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A
			'industry'    => array(),
			'demo'        => '1' === (string) get_post_meta( $id, '_bzm_demo', true ), // [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-DEMO
			'shop_consent' => (string) get_post_meta( $id, '_bzm_shop_consent', true ), // [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-SHOP — ngày đồng ý hiện sản phẩm
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

	/** All members (cap 1 000 for Đ1c), newest first; sample businesses only when asked (never in a count). @return array[] */
	public static function all( $limit = 1000, $with_demo = false ) {
		$ind   = OHCL_Industry::current();
		$posts = get_posts( array( 'post_type' => self::CPT, 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'numberposts' => (int) $limit, 'orderby' => 'ID', 'order' => 'DESC', 'no_found_rows' => true ) );
		$out   = array();
		foreach ( $posts as $p ) {
			$m = self::to_array( $p, $ind );
			if ( $with_demo || ! $m['demo'] ) { // [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-DEMO — số liệu, danh sách quản trị, My Hub không thấy cơ sở mẫu
				$out[] = $m;
			}
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
		if ( array_key_exists( 'consent', $clean ) ) {
			// [2026-11-25 09:06 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — đồng ý công khai lưu kèm ngày; bỏ đồng ý thì xoá (hồ sơ rời bản đồ ngay).
			$had = (string) get_post_meta( $id, '_bzm_public_consent', true );
			update_post_meta( $id, '_bzm_public_consent', $clean['consent'] ? ( $had ? $had : gmdate( 'Y-m-d H:i:s' ) ) : '' );
		}
		if ( array_key_exists( 'shop_consent', $clean ) ) {
			// [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-SHOP — đồng ý hiện sản phẩm lưu kèm ngày; đổi địa chỉ thì xoá bộ đệm.
			$had = (string) get_post_meta( $id, '_bzm_shop_consent', true );
			update_post_meta( $id, '_bzm_shop_consent', $clean['shop_consent'] ? ( $had ? $had : gmdate( 'Y-m-d H:i:s' ) ) : '' );
			if ( class_exists( 'OHCL_Shop' ) ) { // [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-SHOP — thiếu lớp cửa hàng (nạp lỗi) thì bỏ qua, không làm hỏng hồ sơ.
				OHCL_Shop::flush( $clean['core']['shop_url'] );
			}
		}
		self::flush_public();
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
		self::flush_public();
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
		self::flush_public();
		return true;
	}

	/**
	 * Leader verification (V3). Caller checks capability.
	 *
	 * @return true|WP_Error
	 */
	public static function verify( $id, array $evidence, $note, $months ) {
		// [2026-11-25 09:06 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — xác minh V3: người, ngày, hạn (mặc định 12 tháng, MAP-Q2), bằng chứng; bỏ xác minh = xoá.
		$evidence = array_values( array_intersect( $evidence, array_keys( self::EVIDENCE ) ) );
		if ( ! $evidence ) {
			return OHCL_Error::make( 'ohcl_verify_evidence_required', 'Cần chọn ít nhất một bằng chứng.', 'Đánh dấu bằng chứng đã kiểm (giấy phép, chứng nhận, ghé thăm…) rồi xác minh lại.', 400 );
		}
		$months = max( 1, min( 36, (int) $months ) );
		update_post_meta( (int) $id, '_bzm_verify', array( 'by' => get_current_user_id(), 'at' => gmdate( 'Y-m-d H:i:s' ), 'until' => gmdate( 'Y-m-d H:i:s', strtotime( '+' . $months . ' months' ) ), 'evidence' => $evidence, 'note' => self::str( $note, 300 ) ) );
		self::flush_public();
		return true;
	}

	public static function unverify( $id ) {
		update_post_meta( (int) $id, '_bzm_verify', '' );
		self::flush_public();
	}

	/**
	 * Public V4 cards for the portal, cached 10 minutes (flushed on any member write).
	 * Degraded core (keys unknown) ⇒ nobody is V2 ⇒ empty list, flagged so the page says why.
	 *
	 * @return array{items:array[], degraded:bool}
	 */
	public static function public_list() {
		$c = get_transient( self::PUBLIC_CACHE );
		if ( is_array( $c ) ) {
			return $c;
		}
		// [2026-11-26 01:11 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX3 — chưa hội viên nào có khoá thì không cần hỏi lõi (không ai đạt V2) ⇒ không báo lỗi danh bạ.
		$every   = self::all( 1000, true );
		$members = array_values( array_filter( $every, function ( $m ) { return ! $m['demo']; } ) );
		$need    = (bool) array_filter( $members, function ( $m ) { return (int) $m['key_id'] > 0; } );
		$keys    = $need ? OHCL_Overview::all_keys() : array( 'items' => array() );
		$deg     = is_wp_error( $keys );
		if ( $deg ) {
			set_transient( 'ohcl_dir_error', $keys->get_error_code(), 10 * MINUTE_IN_SECONDS );
		}
		$byid = array();
		if ( ! $deg ) {
			foreach ( $keys['items'] as $k ) {
				$byid[ (int) $k['id'] ] = $k;
			}
		}
		$ind = OHCL_Industry::current();
		$now = time();
		$out = array();
		foreach ( $members as $m ) {
			$k = $m['key_id'] && isset( $byid[ $m['key_id'] ] ) ? $byid[ $m['key_id'] ] : null;
			if ( 4 === self::level( $m, $k, $now ) ) {
				$out[] = self::public_view( $m, $ind );
			}
		}
		// [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-DEMO — cơ sở mẫu ghép sau cơ sở thật khi Hub còn ít hơn ngưỡng cơ sở thật (CM-Q1); lõi lỗi thì không che lỗi bằng dữ liệu mẫu.
		$real = count( $out );
		$demo = 0;
		if ( ! $deg && class_exists( 'OHCL_Demo' ) && OHCL_Demo::visible_now( $real ) ) {
			foreach ( $every as $m ) {
				if ( $m['demo'] && 'active' === $m['status'] && '' !== (string) $m['lat'] && '' !== (string) $m['lng'] ) {
					$out[] = self::public_view( $m, $ind );
					$demo++;
				}
			}
		}
		$res = array( 'items' => $out, 'degraded' => $deg, 'real' => $real, 'demo' => $demo );
		if ( ! $deg ) {
			set_transient( self::PUBLIC_CACHE, $res, 10 * MINUTE_IN_SECONDS );
		}
		return $res;
	}

	public static function flush_public() {
		delete_transient( self::PUBLIC_CACHE );
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
