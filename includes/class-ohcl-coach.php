<?php
/**
 * Coach applications (OPEN-HUB-CLIENT §11: CPT `bz_hub_coach_app`, no new table). The public page
 * /tro-thanh-coach/ files an application; the Hub board reviews it in Hub › 04 · Coach (new · approved ·
 * declined). Granting the `hub_coach` role, referral codes and commissions are wave Đ3 (module 04 + core).
 *
 * meta _bzca_skills[] (codes of contract `coach_skills`), _bzca_area, _bzca_years, _bzca_contact, _bzca_note,
 *      _bzca_status, _bzca_decided_at, _bzca_decided_by
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — hồ sơ ứng tuyển Coach: form công khai, ban điều hành duyệt ở 04 · Coach.

defined( 'ABSPATH' ) || exit;

final class OHCL_Coach {

	const CPT    = 'bz_hub_coach_app';
	const LIMIT  = 3; // hồ sơ / giờ / IP
	const STATUS = array( 'new' => 'Mới', 'approved' => 'Đã duyệt', 'declined' => 'Không phù hợp' );

	public static function register() {
		register_post_type( self::CPT, array( 'label' => 'Ứng tuyển Coach', 'public' => false, 'show_ui' => false, 'show_in_rest' => false, 'supports' => array( 'title' ), 'capability_type' => 'post', 'map_meta_cap' => true ) );
	}

	/**
	 * Validate a public application. Pure.
	 *
	 * @return array|WP_Error { name, skills[], area, years, contact, note }
	 */
	public static function clean_apply( array $in, array $cfg ) {
		if ( ! empty( $in['website_url_2'] ) ) {
			return OHCL_Error::make( 'ohcl_coach_rejected', 'Chưa gửi được hồ sơ.', 'Tải lại trang rồi gửi lại.', 400 );
		}
		$s    = function ( $k, $n ) use ( $in ) {
			$v = isset( $in[ $k ] ) && is_scalar( $in[ $k ] ) ? trim( preg_replace( '/\s+/u', ' ', strip_tags( (string) $in[ $k ] ) ) ) : '';
			return function_exists( 'mb_substr' ) ? mb_substr( $v, 0, $n, 'UTF-8' ) : substr( $v, 0, $n );
		};
		$name = $s( 'name', 80 );
		if ( '' === $name ) {
			return OHCL_Error::make( 'ohcl_coach_name_required', 'Cần họ tên.', 'Nhập họ tên rồi gửi lại.', 400 );
		}
		$contact = $s( 'contact', 100 );
		if ( '' === $contact ) {
			return OHCL_Error::make( 'ohcl_coach_contact_required', 'Cần email hoặc số Zalo.', 'Nhập một cách liên hệ rồi gửi lại.', 400 );
		}
		$codes  = array_map( function ( $x ) { return $x['code']; }, $cfg['coach_skills'] );
		$skills = isset( $in['skills'] ) && is_array( $in['skills'] ) ? array_values( array_intersect( array_map( 'strval', $in['skills'] ), $codes ) ) : array();
		if ( $codes && ! $skills ) {
			return OHCL_Error::make( 'ohcl_coach_skill_required', 'Cần chọn ít nhất một chuyên môn.', 'Đánh dấu chuyên môn của bạn rồi gửi lại.', 400 );
		}
		return array(
			'name'    => $name,
			'skills'  => $skills,
			'area'    => $s( 'area', 80 ),
			'years'   => max( 0, min( 60, isset( $in['years'] ) ? (int) $in['years'] : 0 ) ),
			'contact' => $contact,
			'note'    => $s( 'note', 600 ),
		);
	}

	/** @return int|WP_Error */
	public static function save( array $clean ) {
		$id = wp_insert_post( array( 'post_type' => self::CPT, 'post_status' => 'publish', 'post_title' => $clean['name'] ), true );
		if ( is_wp_error( $id ) || ! $id ) {
			return OHCL_Error::make( 'ohcl_coach_save_failed', 'Chưa lưu được hồ sơ.', 'Thử lại sau ít phút.', 500 );
		}
		foreach ( array( 'skills', 'area', 'years', 'contact', 'note' ) as $k ) {
			update_post_meta( (int) $id, '_bzca_' . $k, $clean[ $k ] );
		}
		update_post_meta( (int) $id, '_bzca_status', 'new' );
		update_post_meta( (int) $id, '_bzca_at', gmdate( 'Y-m-d H:i:s' ) );
		return (int) $id;
	}

	public static function to_array( $p ) {
		$id = (int) $p->ID;
		$g  = function ( $k ) use ( $id ) { return get_post_meta( $id, '_bzca_' . $k, true ); };
		$st = (string) $g( 'status' );
		return array(
			'id'      => $id,
			'name'    => (string) $p->post_title,
			'skills'  => (array) ( $g( 'skills' ) ? $g( 'skills' ) : array() ),
			'area'    => (string) $g( 'area' ),
			'years'   => (int) $g( 'years' ),
			'contact' => (string) $g( 'contact' ),
			'note'    => (string) $g( 'note' ),
			'at'      => (string) $g( 'at' ),
			'status'  => isset( self::STATUS[ $st ] ) ? $st : 'new',
		);
	}

	/** @return array[] newest first */
	public static function all() {
		$out = array();
		foreach ( get_posts( array( 'post_type' => self::CPT, 'post_status' => 'publish', 'numberposts' => 500, 'orderby' => 'ID', 'order' => 'DESC', 'no_found_rows' => true ) ) as $p ) {
			$out[] = self::to_array( $p );
		}
		return $out;
	}

	/** @return true|WP_Error */
	public static function decide( $id, $status ) {
		$p = get_post( (int) $id );
		if ( ! $p || self::CPT !== $p->post_type ) {
			return OHCL_Error::make( 'ohcl_coach_not_found', 'Không tìm thấy hồ sơ ứng tuyển.', 'Tải lại danh sách.', 404 );
		}
		if ( ! in_array( $status, array( 'approved', 'declined', 'new' ), true ) ) {
			return OHCL_Error::make( 'ohcl_coach_status_invalid', 'Trạng thái không hợp lệ.', 'Chọn Duyệt hoặc Không phù hợp.', 400 );
		}
		update_post_meta( (int) $id, '_bzca_status', $status );
		update_post_meta( (int) $id, '_bzca_decided_at', gmdate( 'Y-m-d H:i:s' ) );
		update_post_meta( (int) $id, '_bzca_decided_by', get_current_user_id() );
		return true;
	}

	/** Approved count (public page shows it; names stay private until Coach profiles exist, Đ3). */
	public static function approved_count() {
		return count( array_filter( self::all(), function ( $a ) { return 'approved' === $a['status']; } ) );
	}
}
