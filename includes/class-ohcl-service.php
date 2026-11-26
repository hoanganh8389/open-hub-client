<?php
/**
 * Hub Owner ↔ member core actions in ONE place (OPEN-HUB-CLIENT §3, §6): accept, status, issue key, verify,
 * save, member profile, support. wp-admin handlers and the REST API (OHCL_Rest) both call these, so a person
 * clicking and an app calling go through the same rules and emit the same read-only events.
 *
 * Every method returns data or a WP_Error with the four fields (code, message, hint, help_code). The plain key
 * is returned once by issue_key() to the caller and is never stored (R-HUB-2).
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 12:05 PM Johnny Chu - Chu Hoàng Anh] OHCL-API — lớp dịch vụ chung của trục Hub Owner ↔ hội viên: màn quản trị và REST dùng cùng một cửa.

defined( 'ABSPATH' ) || exit;

final class OHCL_Service {

	const SUPPORT_LIMIT = 5;

	private static function not_found() {
		return OHCL_Error::make( 'ohcl_member_not_found', 'Không tìm thấy hồ sơ hội viên.', 'Mở lại từ danh sách hội viên.', 404 );
	}

	/** @return true|WP_Error */
	public static function accept( $id ) {
		if ( ! OHCL_Members::get( (int) $id ) ) {
			return self::not_found();
		}
		OHCL_Members::accept( (int) $id );
		OHCL_Slots::emit( 'member_stage_changed', array( 'member_id' => (int) $id, 'to' => 2, 'label' => OHCL_Members::JOURNEY[2] ) );
		OHCL_Audit::log( 'member_accepted', (int) $id ); // [2026-11-26 04:31 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6
		return true;
	}

	/** @return true|WP_Error */
	public static function set_status( $id, $status ) {
		if ( ! OHCL_Members::get( (int) $id ) ) {
			return self::not_found();
		}
		$res = OHCL_Members::set_status( (int) $id, (string) $status );
		if ( ! is_wp_error( $res ) ) {
			OHCL_Audit::log( 'member_status', (int) $id, (string) $status ); // [2026-11-26 04:31 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6
		}
		return $res;
	}

	/**
	 * Create (id 0) or update a member from raw form / JSON values.
	 *
	 * @return int|WP_Error member id
	 */
	public static function save_member( array $in, $id = 0, $source = '' ) {
		$id = (int) $id;
		if ( $id && ! OHCL_Members::get( $id ) ) {
			return self::not_found();
		}
		$clean = OHCL_Members::clean( $in, OHCL_Industry::current() );
		if ( is_wp_error( $clean ) ) {
			return $clean;
		}
		$res = OHCL_Members::save( $clean, $id );
		if ( ! is_wp_error( $res ) && ! $id && '' !== $source ) {
			update_post_meta( (int) $res, '_bzm_source', $source );
		}
		return $res;
	}

	/**
	 * Issue the AI trial key of a member through the core. One live key per member.
	 *
	 * @return array|WP_Error core answer (key shown once: caller must not store it)
	 */
	public static function issue_key( $id ) {
		$m = OHCL_Members::get( (int) $id );
		if ( ! $m ) {
			return self::not_found();
		}
		$keys = OHCL_Members::keys_by_id();
		$cur  = $m['key_id'] && isset( $keys[ $m['key_id'] ] ) ? $keys[ $m['key_id'] ] : null;
		if ( $cur && in_array( $cur['status'], array( 'trial', 'active', 'grace' ), true ) ) {
			return OHCL_Error::make( 'ohcl_member_has_key', 'Hồ sơ này đã có khoá đang chạy.', 'Thu hồi khoá cũ ở 03 · Khoá dùng thử nếu cần cấp lại.', 409 );
		}
		$res = OHCL_Core_Client::issue_key( $m['title'], $m['site_url'], $m['coach'] );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		OHCL_Members::link_key( (int) $id, (int) $res['id'], (string) $res['key_prefix'] );
		OHCL_Overview::flush();
		OHCL_Slots::emit( 'key_issued', array( 'key_id' => (int) $res['id'], 'key_prefix' => (string) $res['key_prefix'], 'member_id' => (int) $id, 'label' => $m['title'], 'coach_ref' => $m['coach'] ) );
		OHCL_Audit::log( 'key_issued', (int) $id, (string) $res['key_prefix'] ); // [2026-11-26 04:31 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6 — chỉ tiền tố, không khoá
		return $res;
	}

	/** Revoke the member's key at the core. @return array|WP_Error */
	public static function revoke_key( $id ) {
		$m = OHCL_Members::get( (int) $id );
		if ( ! $m ) {
			return self::not_found();
		}
		if ( ! $m['key_id'] ) {
			return OHCL_Error::make( 'ohcl_member_no_key', 'Hồ sơ này chưa có khoá.', 'Cấp khoá trước khi thu hồi.', 409 );
		}
		$res = OHCL_Core_Client::revoke_key( (int) $m['key_id'] );
		if ( ! is_wp_error( $res ) ) {
			OHCL_Overview::flush();
			OHCL_Members::flush_public();
			OHCL_Audit::log( 'key_revoked', (int) $id, (string) $m['key_prefix'] ); // [2026-11-26 04:31 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6
		}
		return $res;
	}

	/** @return true|WP_Error */
	public static function verify( $id, array $evidence, $note, $months ) {
		if ( ! OHCL_Members::get( (int) $id ) ) {
			return self::not_found();
		}
		$res = OHCL_Members::verify( (int) $id, $evidence, (string) $note, (int) $months );
		if ( ! is_wp_error( $res ) ) {
			OHCL_Slots::emit( 'member_verified', array( 'member_id' => (int) $id ) );
			OHCL_Audit::log( 'member_verified', (int) $id, (int) $months . ' tháng' ); // [2026-11-26 04:31 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6
		}
		return $res;
	}

	/** @return true|WP_Error */
	public static function unverify( $id ) {
		if ( ! OHCL_Members::get( (int) $id ) ) {
			return self::not_found();
		}
		OHCL_Members::unverify( (int) $id );
		OHCL_Audit::log( 'member_unverified', (int) $id ); // [2026-11-26 04:31 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6
		return true;
	}

	/** A member's own public profile fields (already cleaned by OHCL_MyHub::clean_profile). */
	public static function save_profile( $member_id, array $c ) {
		$id = (int) $member_id;
		foreach ( array( 'address', 'hours', 'site_url', 'shop_url', 'contact_zalo' ) as $k ) {
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
		return true;
	}

	/** A support request from a member (≥ 10 characters, 5 / day, last 20 kept). @return true|WP_Error */
	public static function add_support( $member_id, $text ) {
		$text = trim( preg_replace( '/\s+/u', ' ', strip_tags( (string) $text ) ) );
		if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text ) ) < 10 ) {
			return OHCL_Error::make( 'ohcl_report_too_short', 'Nội dung hơi ngắn.', 'Viết rõ việc cần hỗ trợ (ít nhất 10 ký tự).', 400 );
		}
		$id    = (int) $member_id;
		$list  = get_post_meta( $id, '_bzm_support', true );
		$list  = is_array( $list ) ? $list : array();
		$today = gmdate( 'Y-m-d' );
		if ( count( array_filter( $list, function ( $x ) use ( $today ) { return 0 === strpos( $x['at'], $today ); } ) ) >= self::SUPPORT_LIMIT ) {
			return OHCL_Error::make( 'ohcl_report_rate_limited', 'Hôm nay bạn đã gửi đủ số yêu cầu.', 'Thử lại vào ngày mai hoặc nhắn trực tiếp ban điều hành.', 429 );
		}
		$list[] = array( 'at' => gmdate( 'Y-m-d H:i:s' ), 'text' => function_exists( 'mb_substr' ) ? mb_substr( $text, 0, 1000, 'UTF-8' ) : substr( $text, 0, 1000 ) );
		update_post_meta( $id, '_bzm_support', array_slice( $list, -20 ) );
		return true;
	}

	/* ===================== outcomes (kết quả tự khai) ===================== */

	/**
	 * Clean what a member reports about the results they got: up to 3 rows {label, before, after, period}, each
	 * needs a label and both numbers; plus the consent to show them publicly. Pure.
	 */
	public static function clean_outcomes( array $in ) {
		// [2026-11-26 04:07 PM Johnny Chu - Chu Hoàng Anh] OHCL-H3 — kết quả tự khai: tối đa 3 dòng, đủ nhãn + trước + sau; ô đồng ý riêng.
		$cut = function ( $v, $n ) {
			$v = is_scalar( $v ) ? trim( preg_replace( '/\s+/u', ' ', strip_tags( (string) $v ) ) ) : '';
			return function_exists( 'mb_substr' ) ? mb_substr( $v, 0, $n, 'UTF-8' ) : substr( $v, 0, $n );
		};
		$items = array();
		foreach ( array_slice( isset( $in['outcomes'] ) && is_array( $in['outcomes'] ) ? $in['outcomes'] : array(), 0, 3 ) as $r ) {
			$r = is_array( $r ) ? $r : array();
			$row = array( 'label' => $cut( isset( $r['label'] ) ? $r['label'] : '', 60 ), 'before' => $cut( isset( $r['before'] ) ? $r['before'] : '', 30 ), 'after' => $cut( isset( $r['after'] ) ? $r['after'] : '', 30 ), 'period' => $cut( isset( $r['period'] ) ? $r['period'] : '', 30 ) );
			if ( '' !== $row['label'] && '' !== $row['before'] && '' !== $row['after'] ) {
				$items[] = $row;
			}
		}
		return array( 'items' => $items, 'consent' => ! empty( $in['outcomes_consent'] ) || ! empty( $in['consent'] ) );
	}

	/** Member saves own reported results. Any edit takes the set off the public page until the Hub approves again. */
	public static function save_outcomes( $member_id, array $clean ) {
		$id  = (int) $member_id;
		$old = get_post_meta( $id, '_bzm_outcomes', true );
		$old = is_array( $old ) ? $old : array();
		$had = isset( $old['consent_at'] ) ? (string) $old['consent_at'] : '';
		update_post_meta( $id, '_bzm_outcomes', array( 'items' => $clean['items'], 'consent_at' => $clean['consent'] && $clean['items'] ? ( '' !== $had ? $had : gmdate( 'Y-m-d H:i:s' ) ) : '', 'approved' => false, 'approved_at' => '', 'approved_by' => 0, 'saved_at' => gmdate( 'Y-m-d H:i:s' ) ) );
		OHCL_Members::flush_public();
		return true;
	}

	/** Hub Owner approves (or hides) the member's reported results for the public page. @return true|WP_Error */
	public static function review_outcomes( $member_id, $approve ) {
		$id = (int) $member_id;
		if ( ! OHCL_Members::get( $id ) ) {
			return self::not_found();
		}
		$o = get_post_meta( $id, '_bzm_outcomes', true );
		if ( ! is_array( $o ) || empty( $o['items'] ) || empty( $o['consent_at'] ) ) {
			return OHCL_Error::make( 'ohcl_outcomes_no_consent', 'Hội viên chưa khai kết quả hoặc chưa đồng ý công bố.', 'Chỉ duyệt khi hội viên đã khai và tích đồng ý công khai.', 409 );
		}
		$o['approved']    = (bool) $approve;
		$o['approved_at'] = $approve ? gmdate( 'Y-m-d H:i:s' ) : '';
		$o['approved_by'] = $approve ? get_current_user_id() : 0;
		update_post_meta( $id, '_bzm_outcomes', $o );
		OHCL_Members::flush_public();
		OHCL_Audit::log( 'outcomes_reviewed', $id, $approve ? 'duyệt' : 'gỡ' ); // [2026-11-26 04:31 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6
		return true;
	}

	/* ===================== rời Hub và xoá thông tin (H6) ===================== */

	/**
	 * A member leaves the Hub. Their public traces go at once: status "Đã rời", public-profile / shop / results
	 * consents withdrawn (the profile leaves the map). Their AI key is NOT revoked here (it runs out at the end
	 * of what was paid; the Hub Owner may revoke it) and the record is kept until an erase is requested.
	 *
	 * @return true|WP_Error
	 */
	public static function leave( $member_id ) {
		// [2026-11-26 04:31 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6
		$id = (int) $member_id;
		$m  = OHCL_Members::get( $id );
		if ( ! $m ) {
			return self::not_found();
		}
		if ( 'left' === $m['status'] ) {
			return OHCL_Error::make( 'ohcl_member_already_left', 'Hồ sơ này đã rời Hub.', 'Nếu muốn xoá thông tin, nhờ ban điều hành xử lý.', 409 );
		}
		OHCL_Members::set_status( $id, 'left' );
		update_post_meta( $id, '_bzm_public_consent', '' );
		update_post_meta( $id, '_bzm_shop_consent', '' );
		$o = get_post_meta( $id, '_bzm_outcomes', true );
		if ( is_array( $o ) && $o ) {
			$o['consent_at'] = '';
			$o['approved']   = false;
			update_post_meta( $id, '_bzm_outcomes', $o );
		}
		OHCL_Members::flush_public();
		OHCL_Audit::log( 'member_left', $id, 'tự rời', 0 );
		return true;
	}

	/**
	 * Erase the personal data of a member who has left. The record keeps its code, dates and the monthly totals
	 * (no person in them); contact, address, website, coordinates, industry fields, support requests, reported
	 * results, verification notes, the linked account and the Coach link are cleared and the name is replaced.
	 * The key at the core is untouched (keys do not move between Hubs and expire by themselves).
	 *
	 * @return true|WP_Error
	 */
	public static function anonymize( $member_id ) {
		$id = (int) $member_id;
		$m  = OHCL_Members::get( $id );
		if ( ! $m ) {
			return self::not_found();
		}
		if ( 'left' !== $m['status'] ) {
			return OHCL_Error::make( 'ohcl_member_not_left', 'Chỉ xoá thông tin của hội viên đã rời Hub.', 'Chuyển hồ sơ sang "Đã rời" (hoặc để hội viên tự rời) rồi xoá thông tin.', 409 );
		}
		if ( '' !== (string) $m['anonymized_at'] ) {
			return OHCL_Error::make( 'ohcl_member_already_erased', 'Thông tin của hồ sơ này đã được xoá.', 'Không còn gì để xoá.', 409 );
		}
		foreach ( array( 'phone', 'email', 'address', 'contact_zalo', 'site_url', 'shop_url', 'lat', 'lng', 'hours', 'coach', 'segment', 'province' ) as $k ) {
			update_post_meta( $id, '_bzm_' . $k, '' );
		}
		foreach ( OHCL_Industry::current()['member_fields'] as $f ) {
			update_post_meta( $id, $f['meta'], '' );
		}
		foreach ( array( '_bzm_support', '_bzm_outcomes', '_bzm_verify', '_bzm_certs', '_bzm_public_consent', '_bzm_shop_consent', '_bzm_ref_coach', '_bzm_ref_at' ) as $k ) {
			update_post_meta( $id, $k, '' );
		}
		update_post_meta( $id, '_bzm_user', 0 );
		update_post_meta( $id, '_bzm_anonymized_at', gmdate( 'Y-m-d H:i:s' ) );
		wp_update_post( array( 'ID' => $id, 'post_title' => 'Đã xoá thông tin · ' . $m['code'] ) );
		OHCL_Members::flush_public();
		OHCL_Audit::log( 'member_anonymized', $id, $m['code'] );
		return true;
	}

	/** Reported results as the owner / member sees them (status in words). */
	public static function outcomes_view( array $m ) {
		$o = is_array( $m['outcomes'] ) ? $m['outcomes'] : array();
		$items = isset( $o['items'] ) ? $o['items'] : array();
		$cons  = ! empty( $o['consent_at'] );
		$app   = ! empty( $o['approved'] );
		return array( 'items' => $items, 'consent' => $cons, 'approved' => $app, 'status' => ! $items ? 'none' : ( ! $cons ? 'private' : ( $app ? 'public' : 'pending' ) ) );
	}

	/** "Hub đã mang lại gì" this month: null counts when nothing has been measured yet. */
	public static function impact( array $m, $now_ts ) {
		$c = OHCL_Events::month_counts( (int) $m['id'], $now_ts );
		return array( 'measured' => null !== $c, 'month' => gmdate( 'Y-m', $now_ts ), 'views' => null === $c ? null : $c['profile_view'], 'contacts' => null === $c ? null : $c['contact_click'] );
	}

	/* ===================== pure serializers (REST + tests) ===================== */

	private static function key_view( $key ) {
		return is_array( $key ) ? array(
			'id'           => (int) $key['id'],
			'prefix'       => isset( $key['key_prefix'] ) ? (string) $key['key_prefix'] : '',
			'status'       => (string) $key['status'],
			'plan_code'    => isset( $key['plan_code'] ) ? (string) $key['plan_code'] : '',
			'plan_until'   => isset( $key['plan_until'] ) ? (string) $key['plan_until'] : '',
			'last_used_at' => isset( $key['last_used_at'] ) ? (string) $key['last_used_at'] : '',
		) : null;
	}

	/**
	 * One member as the API shows it. Never the plain key, never support text unless $owner.
	 *
	 * @param array      $m      to_array() row.
	 * @param array|null $key    key view from the core, or null.
	 * @param array      $ind    industry config.
	 * @param bool       $detail full record (else list summary).
	 * @param bool       $owner  Hub Owner view (adds internal fields).
	 */
	public static function member_view( array $m, $key, array $ind, $now_ts, $detail, $owner ) {
		$lvl   = OHCL_Members::journey( $m, $key, $now_ts );
		$mapl  = OHCL_Members::level( $m, $key, $now_ts );
		$ai    = OHCL_Members::ai_state( $key, $now_ts );
		$out   = array(
			'id'        => (int) $m['id'],
			'code'      => $m['code'],
			'title'     => $m['title'],
			'status'    => $m['status'],
			'segment'   => $m['segment'],
			'seg_label' => isset( $ind['segments'][ $m['segment'] ] ) ? $ind['segments'][ $m['segment'] ] : '',
			'province'  => $m['province'],
			'journey'   => array( 'level' => $lvl, 'label' => OHCL_Members::JOURNEY[ $lvl ] ),
			'ai'        => $ai,
			'map_level' => array( 'level' => $mapl, 'label' => OHCL_Members::LEVELS[ $mapl ] ),
			'verified'  => OHCL_Members::verified( $m['verify'], $now_ts ),
			'certs'     => OHCL_Members::valid_certs( $m, $now_ts ),
			'key'       => self::key_view( $key ),
			'joined_at' => $m['joined_at'],
		);
		if ( ! $detail ) {
			return $out;
		}
		$out['verify_until'] = is_array( $m['verify'] ) && ! empty( $m['verify']['until'] ) ? $m['verify']['until'] : '';
		$out['accepted_at']  = $m['accepted_at'];
		$out['public_consent'] = '' !== (string) $m['consent'];
		$out['profile']      = array();
		foreach ( array( 'address', 'hours', 'phone', 'email', 'contact_zalo', 'site_url', 'shop_url', 'lat', 'lng', 'coach' ) as $k ) {
			$out['profile'][ $k ] = (string) $m[ $k ];
		}
		$out['fields'] = array();
		foreach ( $ind['member_fields'] as $f ) {
			$out['fields'][ $f['key'] ] = isset( $m['industry'][ $f['key'] ] ) ? $m['industry'][ $f['key'] ] : '';
		}
		if ( $owner ) {
			$out['source']    = $m['source'];
			$out['ref_coach'] = (int) $m['ref_coach'];
			$out['user']      = (int) $m['user'];
		}
		return $out;
	}

	/** Public place card + page URL (no contact data beyond what the public profile shows). */
	public static function place_view( array $card ) {
		$card['url']  = OHCL_Front::place_url( $card['id'], $card['title'] );
		$card['demo'] = ! empty( $card['demo'] );
		unset( $card['shop'] );
		return $card;
	}
}
