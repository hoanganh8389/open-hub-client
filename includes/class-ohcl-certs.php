<?php
/**
 * Module 06 · Certification (OPEN-HUB-CLIENT §9): "<Hub> Certified" = passed ONE industry standard, with
 * criteria, evidence, a validity period, a public lookup by code / QR, renewal and revocation.
 *
 * Standards come from the industry contract @2 (`standards`: code, label, criteria, evidence, validity_months).
 * One certificate = one post `bz_hub_cert` on the Hub site ($wpdb->prefix, no new table); title = code.
 *   meta _bzc_member, _bzc_standard, _bzc_label, _bzc_criteria[], _bzc_evidence[], _bzc_from, _bzc_until,
 *        _bzc_by, _bzc_note (private), _bzc_revoked_at, _bzc_reason (public), _bzc_history[]
 * The member keeps a short copy `_bzm_certs` (code => {standard,label,until,revoked}) so lists, the journey
 * (level 6) and the public profile never query certificates one by one.
 *
 * Code: <PREFIX>-CERT-<year>-<0001> (PREFIX = contract `cert_prefix`, else from the Hub id).
 * Public lookup shows: state, business name, standard, period, criteria count, revocation date + reason.
 * Never: evidence, assessor, private note, contacts.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — chứng nhận ngành (module 06): cấp theo bộ tiêu chuẩn của hợp đồng ngành, gia hạn, thu hồi có lý do công khai, tra cứu theo mã / QR, phản ánh.

defined( 'ABSPATH' ) || exit;

final class OHCL_Certs {

	const CPT          = 'bz_hub_cert';
	const REPORTS      = 'ohcl_cert_reports';
	const REPORT_LIMIT = 3;   // phản ánh / giờ / IP
	const REPORT_KEEP  = 100; // giữ 100 phản ánh mới nhất

	const STATES = array(
		'valid'   => 'Còn hiệu lực',
		'expired' => 'Hết hạn',
		'revoked' => 'Đã thu hồi',
		'none'    => 'Không tìm thấy',
	);

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'label'           => 'Chứng nhận',
				'public'          => false,
				'show_ui'         => false, // một màn: Hub › 06 · Chứng nhận
				'show_in_rest'    => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}

	/* ===================== pure ===================== */

	/** Code prefix: contract `cert_prefix` (A–Z, 2–6) or the Hub id in capitals. */
	public static function prefix( array $cfg ) {
		if ( ! empty( $cfg['cert_prefix'] ) ) {
			return $cfg['cert_prefix'];
		}
		$p = strtoupper( preg_replace( '/[^a-z]/i', '', isset( $cfg['id'] ) ? (string) $cfg['id'] : '' ) );
		$p = substr( $p, 0, 6 );
		return strlen( $p ) >= 2 ? $p : 'HUB';
	}

	public static function code( $prefix, $year, $seq ) {
		return sprintf( '%s-CERT-%04d-%04d', $prefix, (int) $year, (int) $seq );
	}

	/** Typed / scanned code ⇒ canonical form, or '' when it cannot be a certificate code. */
	public static function normalize( $raw ) {
		$c = strtoupper( preg_replace( '/\s+/', '', (string) $raw ) );
		$c = str_replace( array( '–', '—', '_' ), '-', $c );
		return preg_match( '/^[A-Z]{2,6}-CERT-\d{4}-\d{4,6}$/', $c ) ? $c : '';
	}

	/** revoked > expired > valid. */
	public static function state( array $c, $now_ts ) {
		if ( ! empty( $c['revoked_at'] ) ) {
			return 'revoked';
		}
		if ( empty( $c['until'] ) || strtotime( $c['until'] . ' UTC' ) < $now_ts ) {
			return 'expired';
		}
		return 'valid';
	}

	/** Standard of the industry contract by code, or null. */
	public static function standard( $code, array $cfg ) {
		foreach ( $cfg['standards'] as $st ) {
			if ( $st['code'] === $code ) {
				return $st;
			}
		}
		return null;
	}

	/**
	 * Validate an issue / renew form. Every criterion of the standard must be ticked: "Certified" means the
	 * WHOLE standard was met, never part of it.
	 *
	 * @param array $in  raw POST (unslashed): standard, criteria[] (indexes), evidence[] (indexes), months, note.
	 * @param array $cfg industry config.
	 * @return array|WP_Error { standard, label, criteria[], evidence[], months, note }
	 */
	public static function clean_issue( array $in, array $cfg ) {
		$st = self::standard( isset( $in['standard'] ) ? (string) $in['standard'] : '', $cfg );
		if ( ! $st ) {
			return OHCL_Error::make( 'ohcl_cert_standard_unknown', 'Bộ tiêu chuẩn không có trong dữ liệu ngành.', 'Chọn một bộ tiêu chuẩn trong danh sách.', 400 );
		}
		$ticked = isset( $in['criteria'] ) && is_array( $in['criteria'] ) ? array_map( 'intval', $in['criteria'] ) : array();
		$miss   = array();
		foreach ( $st['criteria'] as $i => $label ) {
			if ( ! in_array( $i, $ticked, true ) ) {
				$miss[] = $label;
			}
		}
		if ( $miss ) {
			return OHCL_Error::make( 'ohcl_cert_criteria_incomplete', 'Chưa đạt đủ tiêu chí: ' . implode( ', ', array_slice( $miss, 0, 3 ) ) . ( count( $miss ) > 3 ? '…' : '' ) . '.', 'Chỉ cấp khi cơ sở đạt mọi tiêu chí; đánh dấu đủ rồi cấp lại.', 400 );
		}
		$ev = array();
		foreach ( isset( $in['evidence'] ) && is_array( $in['evidence'] ) ? array_map( 'intval', $in['evidence'] ) : array() as $i ) {
			if ( isset( $st['evidence'][ $i ] ) ) {
				$ev[] = $st['evidence'][ $i ];
			}
		}
		if ( $st['evidence'] && ! $ev ) {
			return OHCL_Error::make( 'ohcl_cert_evidence_required', 'Cần ít nhất một bằng chứng đã kiểm.', 'Đánh dấu bằng chứng đã xem (ảnh, giấy tờ…) rồi cấp lại.', 400 );
		}
		$months = isset( $in['months'] ) && '' !== (string) $in['months'] ? (int) $in['months'] : (int) $st['validity_months'];
		$note   = isset( $in['note'] ) && is_scalar( $in['note'] ) ? trim( strip_tags( (string) $in['note'] ) ) : '';
		return array(
			'standard' => $st['code'],
			'label'    => $st['label'],
			'criteria' => $st['criteria'],
			'evidence' => $ev,
			'months'   => max( 1, min( 60, $months ) ),
			'note'     => function_exists( 'mb_substr' ) ? mb_substr( $note, 0, 300, 'UTF-8' ) : substr( $note, 0, 300 ),
		);
	}

	/** Revocation reason: required, public, ≤ 200 characters. @return string|WP_Error */
	public static function clean_reason( $raw ) {
		$r = trim( preg_replace( '/\s+/u', ' ', strip_tags( (string) $raw ) ) );
		if ( '' === $r ) {
			return OHCL_Error::make( 'ohcl_cert_reason_required', 'Cần lý do thu hồi.', 'Lý do hiện công khai ở trang tra cứu; viết ngắn, đúng sự việc.', 400 );
		}
		return function_exists( 'mb_substr' ) ? mb_substr( $r, 0, 200, 'UTF-8' ) : substr( $r, 0, 200 );
	}

	/**
	 * What the public lookup shows. Pure. Evidence, assessor, note and contacts are never included.
	 *
	 * @param array|null $c      to_array() certificate or null.
	 * @param array|null $member OHCL_Members row or null.
	 * @return array { state, state_label, code, business, province, standard, criteria_count, from, until, revoked_at, reason }
	 */
	public static function public_view( $c, $member, $now_ts ) {
		if ( ! is_array( $c ) ) {
			return array( 'state' => 'none', 'state_label' => self::STATES['none'] );
		}
		$st = self::state( $c, $now_ts );
		return array(
			'state'          => $st,
			'state_label'    => self::STATES[ $st ],
			'code'           => $c['code'],
			'business'       => is_array( $member ) ? $member['title'] : '',
			'province'       => is_array( $member ) ? $member['province'] : '',
			'standard'       => $c['label'],
			'criteria_count' => count( $c['criteria'] ),
			'from'           => substr( $c['from'], 0, 10 ),
			'until'          => substr( $c['until'], 0, 10 ),
			'revoked_at'     => 'revoked' === $st ? substr( $c['revoked_at'], 0, 10 ) : '',
			'reason'         => 'revoked' === $st ? $c['reason'] : '',
		);
	}

	/** Short copy kept on the member (`_bzm_certs`). */
	public static function member_copy( array $certs ) {
		$out = array();
		foreach ( $certs as $c ) {
			$out[ $c['code'] ] = array( 'standard' => $c['standard'], 'label' => $c['label'], 'until' => $c['until'], 'revoked' => '' !== (string) $c['revoked_at'] );
		}
		return $out;
	}

	/** Clean a public report (phản ánh). Pure. @return array|WP_Error { code, text, contact } */
	public static function clean_report( array $in ) {
		if ( ! empty( $in['website_url_2'] ) ) {
			return OHCL_Error::make( 'ohcl_report_rejected', 'Chưa gửi được phản ánh.', 'Tải lại trang rồi gửi lại.', 400 );
		}
		$text = isset( $in['text'] ) ? trim( preg_replace( '/\s+/u', ' ', strip_tags( (string) $in['text'] ) ) ) : '';
		$len  = function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
		if ( $len < 10 ) {
			return OHCL_Error::make( 'ohcl_report_too_short', 'Phản ánh quá ngắn.', 'Viết rõ cơ sở nào, việc gì (ít nhất 10 ký tự).', 400 );
		}
		$cut = function ( $s, $n ) {
			return function_exists( 'mb_substr' ) ? mb_substr( $s, 0, $n, 'UTF-8' ) : substr( $s, 0, $n );
		};
		return array(
			'code'    => self::normalize( isset( $in['code'] ) ? $in['code'] : '' ),
			'text'    => $cut( $text, 1000 ),
			'contact' => $cut( trim( strip_tags( isset( $in['contact'] ) ? (string) $in['contact'] : '' ) ), 100 ),
		);
	}

	/* ===================== WordPress ===================== */

	public static function to_array( $post ) {
		$id = (int) $post->ID;
		$g  = function ( $k ) use ( $id ) {
			return get_post_meta( $id, '_bzc_' . $k, true );
		};
		return array(
			'id'         => $id,
			'code'       => (string) $post->post_title,
			'member'     => (int) $g( 'member' ),
			'standard'   => (string) $g( 'standard' ),
			'label'      => (string) $g( 'label' ),
			'criteria'   => (array) ( $g( 'criteria' ) ? $g( 'criteria' ) : array() ),
			'evidence'   => (array) ( $g( 'evidence' ) ? $g( 'evidence' ) : array() ),
			'from'       => (string) $g( 'from' ),
			'until'      => (string) $g( 'until' ),
			'by'         => (int) $g( 'by' ),
			'note'       => (string) $g( 'note' ),
			'revoked_at' => (string) $g( 'revoked_at' ),
			'reason'     => (string) $g( 'reason' ),
			'history'    => (array) ( $g( 'history' ) ? $g( 'history' ) : array() ),
		);
	}

	/** All certificates, newest first (cap 2 000). @return array[] */
	public static function all( $limit = 2000 ) {
		$posts = get_posts( array( 'post_type' => self::CPT, 'post_status' => 'publish', 'numberposts' => (int) $limit, 'orderby' => 'ID', 'order' => 'DESC', 'no_found_rows' => true ) );
		$out   = array();
		foreach ( $posts as $p ) {
			$out[] = self::to_array( $p );
		}
		return $out;
	}

	/** @return array|null */
	public static function get( $id ) {
		$p = get_post( (int) $id );
		return $p && self::CPT === $p->post_type ? self::to_array( $p ) : null;
	}

	/** @return array|null */
	public static function find_by_code( $code ) {
		$code = self::normalize( $code );
		if ( '' === $code ) {
			return null;
		}
		foreach ( self::all() as $c ) {
			if ( $c['code'] === $code ) {
				return $c;
			}
		}
		return null;
	}

	/** @return array[] */
	public static function for_member( $member_id ) {
		return array_values( array_filter( self::all(), function ( $c ) use ( $member_id ) { return (int) $c['member'] === (int) $member_id; } ) );
	}

	/** Next free sequence number for this prefix and year (checked against existing codes). */
	private static function next_code( $prefix, $year ) {
		$opt  = 'ohcl_cert_seq_' . $year;
		$seq  = (int) get_option( $opt, 0 );
		$used = array();
		foreach ( self::all() as $c ) {
			$used[ $c['code'] ] = true;
		}
		do {
			$seq++;
			$code = self::code( $prefix, $year, $seq );
		} while ( isset( $used[ $code ] ) );
		update_option( $opt, $seq, false );
		return $code;
	}

	/**
	 * Issue a certificate, or renew the member's existing one for the same standard (same code, new period,
	 * the old period kept in history). The member must be Verified (V3) first.
	 *
	 * @param int   $member_id member post id.
	 * @param array $clean     clean_issue() result.
	 * @return array|WP_Error { id, code, renewed }
	 */
	public static function issue( $member_id, array $clean ) {
		$m = OHCL_Members::get( $member_id );
		if ( ! $m ) {
			return OHCL_Error::make( 'ohcl_member_not_found', 'Không tìm thấy hồ sơ.', 'Mở lại từ danh sách hội viên.', 404 );
		}
		$now = time();
		if ( ! OHCL_Members::verified( $m['verify'], $now ) ) {
			return OHCL_Error::make( 'ohcl_cert_needs_verify', 'Cơ sở chưa được xác minh (V3).', 'Xác minh hồ sơ trước, rồi mới cấp chứng nhận.', 409 );
		}
		if ( 'active' !== $m['status'] ) {
			return OHCL_Error::make( 'ohcl_cert_member_inactive', 'Hội viên không ở trạng thái đang hoạt động.', 'Chuyển hội viên về "Đang hoạt động" rồi cấp lại.', 409 );
		}
		$from  = gmdate( 'Y-m-d H:i:s', $now );
		$until = gmdate( 'Y-m-d H:i:s', strtotime( '+' . (int) $clean['months'] . ' months', $now ) );

		$prev = null;
		foreach ( self::for_member( $member_id ) as $c ) {
			if ( $c['standard'] === $clean['standard'] && '' === $c['revoked_at'] ) {
				$prev = $c;
				break;
			}
		}
		if ( $prev ) {
			$id   = $prev['id'];
			$code = $prev['code'];
			$hist = $prev['history'];
			$hist[] = array( 'from' => $prev['from'], 'until' => $prev['until'], 'by' => $prev['by'] );
			update_post_meta( $id, '_bzc_history', array_slice( $hist, -20 ) );
		} else {
			$code = self::next_code( self::prefix( OHCL_Industry::current() ), (int) gmdate( 'Y', $now ) );
			$id   = wp_insert_post( array( 'post_type' => self::CPT, 'post_status' => 'publish', 'post_title' => $code ), true );
			if ( is_wp_error( $id ) || ! $id ) {
				return OHCL_Error::make( 'ohcl_cert_save_failed', 'Chưa lưu được giấy chứng nhận.', 'Thử lại sau ít phút.', 500 );
			}
			$id = (int) $id;
			update_post_meta( $id, '_bzc_member', (int) $member_id );
			update_post_meta( $id, '_bzc_standard', $clean['standard'] );
		}
		foreach ( array( 'label' => $clean['label'], 'criteria' => $clean['criteria'], 'evidence' => $clean['evidence'], 'from' => $from, 'until' => $until, 'by' => get_current_user_id(), 'note' => $clean['note'] ) as $k => $v ) {
			update_post_meta( $id, '_bzc_' . $k, $v );
		}
		self::sync_member( $member_id );
		return array( 'id' => $id, 'code' => $code, 'renewed' => (bool) $prev );
	}

	/** @return true|WP_Error */
	public static function revoke( $cert_id, $reason ) {
		$c = self::get( $cert_id );
		if ( ! $c ) {
			return OHCL_Error::make( 'ohcl_cert_not_found', 'Không tìm thấy giấy chứng nhận.', 'Mở lại từ danh sách chứng nhận.', 404 );
		}
		if ( '' !== $c['revoked_at'] ) {
			return OHCL_Error::make( 'ohcl_cert_already_revoked', 'Giấy chứng nhận này đã bị thu hồi.', 'Không cần làm gì thêm.', 409 );
		}
		$r = self::clean_reason( $reason );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
		update_post_meta( $c['id'], '_bzc_revoked_at', gmdate( 'Y-m-d H:i:s' ) );
		update_post_meta( $c['id'], '_bzc_reason', $r );
		self::sync_member( $c['member'] );
		return true;
	}

	/** Rebuild the member's short copy and the public directory cache. */
	public static function sync_member( $member_id ) {
		update_post_meta( (int) $member_id, '_bzm_certs', self::member_copy( self::for_member( $member_id ) ) );
		OHCL_Members::flush_public();
	}

	/** Public lookup by a typed / scanned code. */
	public static function lookup( $raw ) {
		$c = self::find_by_code( $raw );
		return self::public_view( $c, $c ? OHCL_Members::get( $c['member'] ) : null, time() );
	}

	/** Public lookup URL of a code (target of the QR). */
	public static function lookup_url( $code ) {
		return OHCL_Front::url( 'cert', array( 'ma' => $code ) );
	}

	public static function add_report( array $clean ) {
		$list   = get_option( self::REPORTS, array() );
		$list   = is_array( $list ) ? $list : array();
		$list[] = array_merge( $clean, array( 'at' => gmdate( 'Y-m-d H:i:s' ) ) );
		update_option( self::REPORTS, array_slice( $list, -self::REPORT_KEEP ), false );
	}

	/** Newest first. @return array[] */
	public static function reports() {
		$list = get_option( self::REPORTS, array() );
		return is_array( $list ) ? array_reverse( $list ) : array();
	}
}
