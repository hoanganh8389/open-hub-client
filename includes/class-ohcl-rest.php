<?php
/**
 * REST API of the Hub site: namespace `open-hub-client/v1` (OPEN-HUB-CLIENT §12b).
 *
 *   Hub Owner  (capability manage_options; cookie + X-WP-Nonce, or an Application Password for an app)
 *     GET  /owner/overview                    five-question numbers + to-do
 *     GET  /owner/members                     list (q, status, stage, page, per_page ≤ 50)
 *     POST /owner/members                     create
 *     GET  /owner/members/{id}                full 360° record
 *     POST /owner/members/{id}                update
 *     POST /owner/members/{id}/accept | status | key | key/revoke | verify     ·  DELETE …/verify
 *   Member     (logged-in user linked to one member record)
 *     GET  /me · POST /me/profile · POST /me/support
 *   Public     (no login, rate limited, cached 60 s)
 *     GET  /meta · GET /places (q, seg, province, page, per_page) · GET /places/{id}
 *
 * Rules: one door per action (OHCL_Service — same as wp-admin); the plain key appears ONLY in the answer of
 * POST …/key and never again; owner / me answers are `Cache-Control: no-store`; errors carry code, message,
 * hint, help_code (R-ERROR-UX); a member only ever sees own data; the public list shows V4 cards only (+ the
 * "Mẫu" flag of sample data). No new table, no core call except key issue / revoke / list (OHCL_Core_Client).
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 12:05 PM Johnny Chu - Chu Hoàng Anh] OHCL-API — REST open-hub-client/v1: Hub Owner, hội viên (/me), công khai (/places); một cửa dịch vụ với wp-admin.

defined( 'ABSPATH' ) || exit;

final class OHCL_Rest {

	const NS         = 'open-hub-client/v1';
	const CAP        = 'manage_options'; // cùng quyền với wp-admin (OHCL_Admin::CAP)
	const PER_PAGE   = 20;
	const MAX_PER    = 50;
	const PUBLIC_RPM = 120;

	public static function boot() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/* ---------- permissions ---------- */

	public static function can_owner() {
		if ( ! is_user_logged_in() ) {
			return OHCL_Error::make( 'ohcl_rest_login_required', 'Cần đăng nhập.', 'Đăng nhập tài khoản quản trị Hub, hoặc dùng Application Password.', 401 );
		}
		return current_user_can( self::CAP ) ? true : OHCL_Error::make( 'ohcl_rest_forbidden', 'Tài khoản này không có quyền quản trị Hub.', 'Dùng tài khoản chủ Hub (quyền quản trị site).', 403 );
	}

	public static function can_member() {
		return is_user_logged_in() ? true : OHCL_Error::make( 'ohcl_rest_login_required', 'Cần đăng nhập.', 'Đăng nhập bằng tài khoản hội viên rồi gọi lại.', 401 );
	}

	/** Public routes: per-IP limit, 120 calls a minute. */
	public static function can_public() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'ohcl_rl_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= self::PUBLIC_RPM ) {
			return OHCL_Error::make( 'ohcl_rest_rate_limited', 'Gọi quá nhiều lần.', 'Đợi một phút rồi gọi lại.', 429 );
		}
		set_transient( $key, $n + 1, MINUTE_IN_SECONDS );
		return true;
	}

	/* ---------- routes ---------- */

	public static function routes() {
		$id   = array( 'id' => array( 'validate_callback' => function ( $v ) { return is_numeric( $v ) && (int) $v > 0; } ) );
		$o    = array( __CLASS__, 'can_owner' );
		$m    = array( __CLASS__, 'can_member' );
		$p    = array( __CLASS__, 'can_public' );
		$list = array( 'page' => array( 'default' => 1 ), 'per_page' => array( 'default' => self::PER_PAGE ) );
		$r    = function ( $path, $methods, $cb, $perm, array $args = array() ) {
			register_rest_route( self::NS, $path, array( 'methods' => $methods, 'callback' => array( __CLASS__, $cb ), 'permission_callback' => $perm, 'args' => $args ) );
		};
		$r( '/owner/overview', 'GET', 'owner_overview', $o );
		$r( '/owner/members', 'GET', 'owner_members', $o, $list );
		$r( '/owner/members', 'POST', 'owner_member_create', $o );
		$r( '/owner/members/(?P<id>\d+)', 'GET', 'owner_member', $o, $id );
		$r( '/owner/members/(?P<id>\d+)', 'POST', 'owner_member_update', $o, $id );
		$r( '/owner/members/(?P<id>\d+)/accept', 'POST', 'owner_accept', $o, $id );
		$r( '/owner/members/(?P<id>\d+)/status', 'POST', 'owner_status', $o, $id );
		$r( '/owner/members/(?P<id>\d+)/key', 'POST', 'owner_key', $o, $id );
		$r( '/owner/members/(?P<id>\d+)/key/revoke', 'POST', 'owner_key_revoke', $o, $id );
		$r( '/owner/members/(?P<id>\d+)/verify', 'POST', 'owner_verify', $o, $id );
		$r( '/owner/members/(?P<id>\d+)/verify', 'DELETE', 'owner_unverify', $o, $id );
		$r( '/me', 'GET', 'me', $m );
		$r( '/me/profile', 'POST', 'me_profile', $m );
		$r( '/me/support', 'POST', 'me_support', $m );
		$r( '/meta', 'GET', 'meta', $p );
		$r( '/places', 'GET', 'places', $p, $list );
		$r( '/places/(?P<id>\d+)', 'GET', 'place', $p, $id );
	}

	/* ---------- helpers ---------- */

	private static function out( $data, $status = 200, $public = false ) {
		$res = new WP_REST_Response( $data, $status );
		$res->header( 'Cache-Control', $public ? 'public, max-age=60' : 'no-store' );
		return $res;
	}

	/** Core unreachable is a 200-with-_degraded inside the plugin (fail open); for an API caller it is 503. */
	private static function fail( $e ) {
		// [2026-11-26 12:05 PM Johnny Chu - Chu Hoàng Anh] OHCL-API — lõi không trả lời ⇒ 503 để ứng dụng biết thử lại
		$d = (array) $e->get_error_data();
		if ( ! empty( $d['_degraded'] ) ) {
			$d['status'] = 503;
			return new WP_Error( $e->get_error_code(), $e->get_error_message(), $d );
		}
		return $e;
	}

	/** JSON body merged over query / form params. */
	private static function in( $req ) {
		$j = $req->get_json_params();
		return array_merge( (array) $req->get_params(), is_array( $j ) ? $j : array() );
	}

	private static function page_args( $req ) {
		$pp = max( 1, min( self::MAX_PER, (int) $req->get_param( 'per_page' ) ) );
		return array( max( 1, (int) $req->get_param( 'page' ) ), $pp );
	}

	private static function paged( array $items, $page, $pp, array $extra = array() ) {
		$total = count( $items );
		return array_merge( array( 'items' => array_values( array_slice( $items, ( $page - 1 ) * $pp, $pp ) ), 'total' => $total, 'page' => $page, 'per_page' => $pp, 'total_pages' => (int) max( 1, ceil( $total / $pp ) ) ), $extra );
	}

	private static function keys() {
		return OHCL_Members::keys_by_id();
	}

	private static function key_of( array $m, array $keys ) {
		return $m['key_id'] && isset( $keys[ $m['key_id'] ] ) ? $keys[ $m['key_id'] ] : null;
	}

	/* ---------- owner ---------- */

	public static function owner_overview() {
		$all = OHCL_Overview::all_keys();
		$ok  = ! is_wp_error( $all );
		$s   = OHCL_Overview::from_keys( $ok ? $all['items'] : array(), gmdate( 'Y-m-d H:i:s' ) );
		$mc  = OHCL_Members::counts( OHCL_Members::all(), $ok ? array_column( $all['items'], null, 'id' ) : array(), time() );
		$seg = array();
		$cfg = OHCL_Industry::current();
		foreach ( $mc['by_segment'] as $k => $n ) {
			$seg[] = array( 'segment' => $k, 'label' => isset( $cfg['segments'][ $k ] ) ? $cfg['segments'][ $k ] : $k, 'n' => $n );
		}
		return self::out(
			array(
				'community' => array( 'total' => $mc['total'], 'active' => $mc['active'], 'verified' => $mc['verified'], 'by_stage' => $mc['by_stage'], 'by_segment' => $seg ),
				'adoption'  => $ok ? array( 'keys' => $s, 'funnel' => OHCL_Overview::funnel( $s ), 'capped' => $all['capped'] ) : null,
				'core'      => array( 'ok' => $ok, 'error' => $ok ? '' : $all->get_error_code() ),
				'todo'      => OHCL_Overview::todo( $s, OHCL_Industry::report(), $ok ),
				'revenue'   => null, // chưa có nguồn: lõi mở sổ doanh thu theo Hub ở Đ3 (R-HUB-6: không ước số)
			)
		);
	}

	public static function owner_members( $req ) {
		$ind    = OHCL_Industry::current();
		$keys   = self::keys();
		$now    = time();
		$q      = strtolower( trim( (string) $req->get_param( 'q' ) ) );
		$status = (string) $req->get_param( 'status' );
		$stage  = (int) $req->get_param( 'stage' );
		$items  = array();
		foreach ( OHCL_Members::all() as $m ) {
			if ( '' !== $status && $m['status'] !== $status ) {
				continue;
			}
			if ( '' !== $q && false === strpos( strtolower( $m['title'] . ' ' . $m['code'] . ' ' . $m['province'] . ' ' . $m['email'] ), $q ) ) {
				continue;
			}
			$v = OHCL_Service::member_view( $m, self::key_of( $m, $keys ), $ind, $now, false, true );
			if ( $stage && $v['journey']['level'] !== $stage ) {
				continue;
			}
			$items[] = $v;
		}
		list( $page, $pp ) = self::page_args( $req );
		return self::out( self::paged( $items, $page, $pp, array( 'core_ok' => (bool) $keys ) ) );
	}

	private static function member_or_404( $req ) {
		$m = OHCL_Members::get( (int) $req['id'] );
		return $m ? $m : OHCL_Error::make( 'ohcl_member_not_found', 'Không tìm thấy hồ sơ hội viên.', 'Kiểm tra mã hồ sơ rồi gọi lại.', 404 );
	}

	public static function owner_member( $req ) {
		$m = self::member_or_404( $req );
		if ( is_wp_error( $m ) ) {
			return $m;
		}
		$keys = self::keys();
		$v    = OHCL_Service::member_view( $m, self::key_of( $m, $keys ), OHCL_Industry::current(), time(), true, true );
		$sup  = get_post_meta( $m['id'], '_bzm_support', true );
		$v['support'] = is_array( $sup ) ? array_values( $sup ) : array();
		return self::out( $v );
	}

	public static function owner_member_create( $req ) {
		$id = OHCL_Service::save_member( self::in( $req ), 0, 'api' );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		return self::out( array( 'id' => (int) $id ), 201 );
	}

	public static function owner_member_update( $req ) {
		$id = OHCL_Service::save_member( self::in( $req ), (int) $req['id'] );
		return is_wp_error( $id ) ? $id : self::out( array( 'id' => (int) $id ) );
	}

	public static function owner_accept( $req ) {
		$r = OHCL_Service::accept( (int) $req['id'] );
		return is_wp_error( $r ) ? $r : self::out( array( 'ok' => true ) );
	}

	public static function owner_status( $req ) {
		$in = self::in( $req );
		$r  = OHCL_Service::set_status( (int) $req['id'], isset( $in['status'] ) ? (string) $in['status'] : '' );
		return is_wp_error( $r ) ? $r : self::out( array( 'ok' => true ) );
	}

	/** The ONLY place the plain key is returned, once. */
	public static function owner_key( $req ) {
		$r = OHCL_Service::issue_key( (int) $req['id'] );
		if ( is_wp_error( $r ) ) {
			return self::fail( $r );
		}
		return self::out( array( 'key' => (string) $r['key'], 'key_id' => (int) $r['id'], 'key_prefix' => (string) $r['key_prefix'], 'plan_code' => (string) $r['plan_code'], 'plan_until' => (string) $r['plan_until'], 'shown_once' => true ), 201 );
	}

	public static function owner_key_revoke( $req ) {
		$r = OHCL_Service::revoke_key( (int) $req['id'] );
		return is_wp_error( $r ) ? self::fail( $r ) : self::out( array( 'ok' => true ) );
	}

	public static function owner_verify( $req ) {
		$in = self::in( $req );
		$ev = isset( $in['evidence'] ) && is_array( $in['evidence'] ) ? array_map( 'sanitize_key', $in['evidence'] ) : array();
		$r  = OHCL_Service::verify( (int) $req['id'], $ev, isset( $in['note'] ) ? sanitize_text_field( (string) $in['note'] ) : '', isset( $in['months'] ) ? (int) $in['months'] : 12 );
		return is_wp_error( $r ) ? $r : self::out( array( 'ok' => true ) );
	}

	public static function owner_unverify( $req ) {
		$r = OHCL_Service::unverify( (int) $req['id'] );
		return is_wp_error( $r ) ? $r : self::out( array( 'ok' => true ) );
	}

	/* ---------- member ---------- */

	private static function me_member() {
		$cur = OHCL_MyHub::current();
		if ( $cur['member'] ) {
			return $cur['member'];
		}
		$why = array(
			'none'      => array( 'Tài khoản chưa gắn với hồ sơ hội viên.', 'Đăng ký hội viên, hoặc nhờ ban điều hành gắn tài khoản cho bạn.' ),
			'ambiguous' => array( 'Có nhiều hồ sơ cùng email.', 'Nhờ ban điều hành chọn hồ sơ đúng cho tài khoản của bạn.' ),
			'taken'     => array( 'Hồ sơ đã gắn với tài khoản khác.', 'Nhờ ban điều hành kiểm tra.' ),
		);
		$w = isset( $why[ $cur['why'] ] ) ? $why[ $cur['why'] ] : $why['none'];
		return OHCL_Error::make( 'ohcl_me_no_member', $w[0], $w[1], 404 );
	}

	public static function me() {
		$m = self::me_member();
		if ( is_wp_error( $m ) ) {
			return $m;
		}
		$keys = self::keys();
		$key  = self::key_of( $m, $keys );
		$now  = time();
		$v    = OHCL_Service::member_view( $m, $key, OHCL_Industry::current(), $now, true, false );
		$v['roadmap'] = OHCL_MyHub::road( $m, $key, $now );
		return self::out( $v );
	}

	public static function me_profile( $req ) {
		$m = self::me_member();
		if ( is_wp_error( $m ) ) {
			return $m;
		}
		// [2026-11-26 12:05 PM Johnny Chu - Chu Hoàng Anh] OHCL-API — API cập nhật TỪNG PHẦN: trường không gửi giữ nguyên (form web gửi đủ ô nên không bị ảnh hưởng).
		$in  = self::in( $req );
		$cur = array( 'address' => $m['address'], 'hours' => $m['hours'], 'site_url' => $m['site_url'], 'shop_url' => $m['shop_url'], 'contact_zalo' => $m['contact_zalo'], 'public_consent' => '' !== (string) $m['consent'], 'shop_consent' => '' !== (string) $m['shop_consent'] );
		foreach ( $cur as $k => $v ) {
			if ( array_key_exists( $k, $in ) ) {
				$cur[ $k ] = $in[ $k ];
			}
		}
		$c = OHCL_MyHub::clean_profile( $cur );
		if ( is_wp_error( $c ) ) {
			return $c;
		}
		OHCL_Service::save_profile( $m['id'], $c );
		return self::out( array( 'ok' => true ) );
	}

	public static function me_support( $req ) {
		$m = self::me_member();
		if ( is_wp_error( $m ) ) {
			return $m;
		}
		$in = self::in( $req );
		$r  = OHCL_Service::add_support( $m['id'], isset( $in['text'] ) ? (string) $in['text'] : '' );
		return is_wp_error( $r ) ? $r : self::out( array( 'ok' => true ), 201 );
	}

	/* ---------- public ---------- */

	public static function meta() {
		$cfg = OHCL_Industry::current();
		$pub = OHCL_Members::public_list();
		$prov = array();
		foreach ( $pub['items'] as $it ) {
			if ( '' !== $it['province'] ) {
				$prov[ $it['province'] ] = true;
			}
		}
		ksort( $prov );
		return self::out(
			array(
				'hub'       => array( 'name' => $cfg['name'], 'industry' => $cfg['industry'], 'accent' => $cfg['brand']['accent'], 'contract' => OHCL_Industry::CONTRACT ),
				'terms'     => $cfg['terms'],
				'segments'  => $cfg['segments'],
				'provinces' => array_keys( $prov ),
				'fields'    => array_map( function ( $f ) { return array( 'key' => $f['key'], 'label' => $f['label'], 'type' => $f['type'], 'options' => isset( $f['options'] ) ? $f['options'] : array() ); }, array_values( array_filter( $cfg['member_fields'], function ( $f ) { return ! empty( $f['public'] ); } ) ) ),
				'counts'    => array( 'places' => isset( $pub['real'] ) ? (int) $pub['real'] : count( $pub['items'] ) ),
			),
			200,
			true
		);
	}

	public static function places( $req ) {
		$pub = OHCL_Members::public_list();
		if ( $pub['degraded'] ) {
			return OHCL_Error::make( 'ohcl_directory_unavailable', 'Chưa tải được danh bạ.', 'Thử lại sau ít phút.', 503 );
		}
		$items = OHCL_Front::filter(
			$pub['items'],
			array(
				'q'        => trim( (string) $req->get_param( 'q' ) ),
				'seg'      => sanitize_key( (string) $req->get_param( 'seg' ) ),
				'province' => trim( (string) $req->get_param( 'province' ) ),
			)
		);
		list( $page, $pp ) = self::page_args( $req );
		$res               = self::paged( array_map( array( 'OHCL_Service', 'place_view' ), $items ), $page, $pp, array( 'sample_shown' => ! empty( $pub['demo'] ) ) );
		return self::out( $res, 200, true );
	}

	public static function place( $req ) {
		$it = OHCL_Front::find_place( (int) $req['id'] );
		return $it ? self::out( OHCL_Service::place_view( $it ), 200, true ) : OHCL_Error::make( 'ohcl_place_not_found', 'Cơ sở không có hoặc chưa công khai.', 'Chỉ cơ sở đã xác minh và đồng ý công khai mới có hồ sơ.', 404 );
	}
}
