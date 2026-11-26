<?php
/**
 * REST API open-hub-client/v1 (OHCL_Rest + OHCL_Service). Run: php tests/rest-smoke.php — exit 0 = pass.
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 12:05 PM Johnny Chu - Chu Hoàng Anh] OHCL-API — test REST: quyền theo nhóm, một cửa dịch vụ, khoá thô hiện một lần, hội viên chỉ thấy của mình, công khai có giới hạn tốc độ, lỗi bốn trường.

define( 'ABSPATH', __DIR__ . '/' );
define( 'OHCL_TESTING', true );
define( 'DAY_IN_SECONDS', 86400 );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );

class WP_Error {
	private $c; private $m; private $d;
	public function __construct( $c = '', $m = '', $d = null ) { $this->c = $c; $this->m = $m; $this->d = $d; }
	public function get_error_code() { return $this->c; }
	public function get_error_message() { return $this->m; }
	public function get_error_data() { return $this->d; }
}
class WP_REST_Response { public $data; public $status; public $headers = array(); public function __construct( $d = null, $s = 200 ) { $this->data = $d; $this->status = $s; } public function header( $k, $v ) { $this->headers[ $k ] = $v; } }
class WP_REST_Request {
	public $p; public $json;
	public function __construct( array $p = array(), $json = null ) { $this->p = $p; $this->json = $json; }
	public function get_param( $k ) { return isset( $this->p[ $k ] ) ? $this->p[ $k ] : null; }
	public function get_params() { return $this->p; }
	public function get_json_params() { return $this->json; }
	public function offsetGet( $k ) { return $this->get_param( $k ); }
}
class T_Req extends WP_REST_Request implements ArrayAccess {
	public function offsetExists( $k ): bool { return isset( $this->p[ $k ] ); }
	#[\ReturnTypeWillChange]
	public function offsetGet( $k ) { return isset( $this->p[ $k ] ) ? $this->p[ $k ] : null; }
	public function offsetSet( $k, $v ): void { $this->p[ $k ] = $v; }
	public function offsetUnset( $k ): void { unset( $this->p[ $k ] ); }
}
class T_User { public $ID; public $user_email; public $display_name = 'U'; public $roles = array(); public function __construct( $id, $e ) { $this->ID = $id; $this->user_email = $e; } }

$GLOBALS['opts'] = array(); $GLOBALS['tr'] = array(); $GLOBALS['hooks'] = array(); $GLOBALS['posts'] = array(); $GLOBALS['meta'] = array(); $GLOBALS['routes'] = array(); $GLOBALS['me'] = 0; $GLOBALS['admin'] = false; $GLOBALS['wp_posts'] = array(); $GLOBALS['users'] = array(); $GLOBALS['events'] = array();
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function add_filter( $h, $cb ) { $GLOBALS['hooks'][ $h ][] = $cb; return true; }
function add_action( $h, $cb ) { return add_filter( $h, $cb ); }
function apply_filters( $h, $v ) { $a = func_get_args(); foreach ( isset( $GLOBALS['hooks'][ $h ] ) ? $GLOBALS['hooks'][ $h ] : array() as $cb ) { $a[1] = $v; $v = call_user_func_array( $cb, array_slice( $a, 1 ) ); } return $v; }
function do_action( $h ) {}
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['opts'] ) ? $GLOBALS['opts'][ $k ] : $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['opts'][ $k ] = $v; return true; }
function get_transient( $k ) { return isset( $GLOBALS['tr'][ $k ] ) ? $GLOBALS['tr'][ $k ] : false; }
function set_transient( $k, $v, $t = 0 ) { $GLOBALS['tr'][ $k ] = $v; return true; }
function delete_transient( $k ) { unset( $GLOBALS['tr'][ $k ] ); return true; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return esc_html( $s ); }
function esc_url( $s ) { return esc_html( $s ); }
function home_url( $p = '/' ) { return 'https://hub.test' . $p; }
function admin_url( $p = '' ) { return 'https://hub.test/wp-admin/' . $p; }
function add_query_arg( array $a, $u ) { return $u . ( false === strpos( $u, '?' ) ? '?' : '&' ) . http_build_query( $a ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); }
function sanitize_title( $s ) { return trim( strtolower( preg_replace( '/[^a-z0-9]+/i', '-', (string) $s ) ), '-' ); }
function wp_unslash( $s ) { return $s; }
function get_current_user_id() { return $GLOBALS['me']; }
@mkdir( sys_get_temp_dir() . '/ohcl-rs-' . getmypid() );
function wp_upload_dir() { return array( 'basedir' => sys_get_temp_dir() . '/ohcl-rs-' . getmypid() ); }
function is_user_logged_in() { return (bool) $GLOBALS['me']; }
function current_user_can( $c ) { return ! empty( $GLOBALS['admin'] ); }
function wp_get_current_user() { return $GLOBALS['me'] ? $GLOBALS['users'][ $GLOBALS['me'] ] : (object) array( 'ID' => 0, 'user_email' => '' ); }
function register_rest_route( $ns, $path, array $a ) { $GLOBALS['routes'][] = array( $ns, $path, $a ); }
function get_posts( array $a ) { if ( isset( $a['post_type'] ) && 'post' === $a['post_type'] ) { return $GLOBALS['wp_posts']; } return array_reverse( array_values( array_filter( $GLOBALS['posts'], function ( $p ) use ( $a ) { return $p->post_type === $a['post_type']; } ) ) ); }
function get_post( $id ) { return isset( $GLOBALS['posts'][ $id ] ) ? $GLOBALS['posts'][ $id ] : null; }
function wp_insert_post( array $a ) { $id = count( $GLOBALS['posts'] ) + 401; $GLOBALS['posts'][ $id ] = (object) array( 'ID' => $id, 'post_type' => $a['post_type'], 'post_title' => $a['post_title'] ); return $id; }
function wp_update_post( array $a ) { $GLOBALS['posts'][ $a['ID'] ]->post_title = $a['post_title']; return $a['ID']; }
function get_post_meta( $id, $k, $s = true ) { return isset( $GLOBALS['meta'][ $id ][ $k ] ) ? $GLOBALS['meta'][ $id ][ $k ] : ''; }
function update_post_meta( $id, $k, $v ) { $GLOBALS['meta'][ $id ][ $k ] = $v; return true; }
function get_userdata( $id ) { return false; }
function get_users( array $a ) { return array(); }

$dir = dirname( __DIR__ );
define( 'OHCL_DIR', $dir . '/' );
foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview', 'class-ohcl-members', 'class-ohcl-demo', 'class-ohcl-shop', 'class-ohcl-certs', 'class-ohcl-coach', 'class-ohcl-affiliate', 'class-ohcl-service', 'class-ohcl-benefits', 'class-ohcl-events', 'class-ohcl-audit', 'class-ohcl-export', 'class-ohcl-onboarding', 'class-ohcl-myhub', 'class-ohcl-front', 'class-ohcl-rest' ) as $f ) {
	require $dir . '/includes/' . $f . '.php';
}
require $dir . '/examples/wp-sample-hub/wp-sample-hub.php';

$pass = 0; $fail = 0;
function check( $name, $ok ) { global $pass, $fail; if ( $ok ) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; } }
function req( array $p = array(), $json = null ) { return new T_Req( $p, $json ); }
function body( $r ) { return $r instanceof WP_REST_Response ? $r->data : null; }
function err4( $e ) { $d = (array) $e->get_error_data(); return is_wp_error( $e ) && '' !== $e->get_error_code() && '' !== $e->get_error_message() && ! empty( $d['hint'] ) && ! empty( $d['help_code'] ) && isset( $d['status'] ); }
function login( $uid, $admin = false ) { $GLOBALS['me'] = $uid; $GLOBALS['admin'] = $admin; }
function route_perm( $path, $method ) { foreach ( $GLOBALS['routes'] as $r ) { if ( $r[1] === $path && $r[2]['methods'] === $method ) { return call_user_func( $r[2]['permission_callback'] ); } } return 'NO_ROUTE'; }

OHCL_Rest::routes();
$now_s = gmdate( 'Y-m-d H:i:s' );
$GLOBALS['key_calls'] = 0;
OHCL_Core_Client::$config = array( 'base_url' => 'https://bizcity.test', 'hub' => 'sample', 'secret' => 'ohs_' . str_repeat( 'a', 32 ) );
OHCL_Core_Client::$transport = function ( $m, $u, $h, $b ) use ( $now_s ) {
	if ( 'POST' === $m && false !== strpos( $u, '/hub/keys' ) && false === strpos( $u, 'revoke' ) ) { $GLOBALS['key_calls']++; return array( 'status' => 201, 'body' => json_encode( array( 'id' => 31, 'key' => 'biz-' . str_repeat( 'f', 32 ), 'key_prefix' => 'biz-…ffff', 'plan_code' => 'trial', 'plan_until' => '2030-01-01 00:00:00' ) ) ); }
	if ( false !== strpos( $u, 'revoke' ) ) { return array( 'status' => 200, 'body' => json_encode( array( 'ok' => true ) ) ); }
	return array( 'status' => 200, 'body' => json_encode( array( 'items' => isset( $GLOBALS['core_keys'] ) ? $GLOBALS['core_keys'] : array(), 'page' => 1 ) ) );
};

/* ---------- đăng ký route + quyền ---------- */
check( 'đăng ký 23 route trong namespace open-hub-client/v1', 28 === count( $GLOBALS['routes'] ) && 'open-hub-client/v1' === $GLOBALS['routes'][0][0] );
login( 0 );
$ps = route_perm( '/owner/overview', 'GET' );
check( 'owner: chưa đăng nhập ⇒ 401 ohcl_rest_login_required (đủ bốn trường)', is_wp_error( $ps ) && 'ohcl_rest_login_required' === $ps->get_error_code() && 401 === $ps->get_error_data()['status'] && err4( $ps ) );
$GLOBALS['users'][5] = new T_User( 5, 'hv@example.com' );
login( 5, false );
$ps = route_perm( '/owner/members', 'POST' );
check( 'owner: tài khoản thường ⇒ 403 ohcl_rest_forbidden', is_wp_error( $ps ) && 'ohcl_rest_forbidden' === $ps->get_error_code() && 403 === $ps->get_error_data()['status'] );
login( 1, true );
$all_owner = true;
foreach ( $GLOBALS['routes'] as $r ) { if ( 0 === strpos( $r[1], '/owner/' ) && true !== call_user_func( $r[2]['permission_callback'] ) ) { $all_owner = false; } }
check( 'owner: chủ Hub qua cổng quyền ở MỌI route /owner/*', $all_owner );
$unprot = array();
login( 0 );
foreach ( $GLOBALS['routes'] as $r ) { if ( 0 === strpos( $r[1], '/owner/' ) || '/me' === $r[1] || 0 === strpos( $r[1], '/me/' ) ) { if ( true === call_user_func( $r[2]['permission_callback'] ) ) { $unprot[] = $r[1]; } } }
check( 'không route /owner/* hay /me nào mở cho khách', ! $unprot );

/* ---------- Hub Owner: tạo, xem, tiếp nhận, khoá ---------- */
login( 1, true );
$new = OHCL_Rest::owner_member_create( req( array( 'title' => 'Cửa hàng An', 'segment' => 'cua-hang', 'province' => 'Hà Nội', 'email' => 'hv@example.com', 'phone' => '0912345678' ) ) );
$mid = body( $new )['id'];
check( 'POST /owner/members: tạo hồ sơ ⇒ 201, nguồn "api"', 201 === $new->status && $mid > 0 && 'api' === get_post_meta( $mid, '_bzm_source', true ) );
$bad = OHCL_Rest::owner_member_create( req( array( 'segment' => 'cua-hang' ) ) );
check( 'tạo thiếu tên ⇒ lỗi bốn trường, không tạo gì', is_wp_error( $bad ) && 'ohcl_member_title_required' === $bad->get_error_code() && err4( $bad ) && 1 === count( OHCL_Members::all() ) );
$bad2 = OHCL_Rest::owner_member_create( req( array( 'title' => 'X', 'segment' => 'khong-co' ) ) );
check( 'tạo sai loại hình ⇒ 400 ohcl_member_segment_invalid', is_wp_error( $bad2 ) && 'ohcl_member_segment_invalid' === $bad2->get_error_code() );
$d = body( OHCL_Rest::owner_member( req( array( 'id' => $mid ) ) ) );
check( 'GET /owner/members/{id}: hồ sơ 360° — hành trình bậc 1, trợ lý "Chưa bật", có liên hệ (chủ Hub được thấy), khoá null, không khoá thô', 1 === $d['journey']['level'] && 'none' === $d['ai']['code'] && '0912345678' === $d['profile']['phone'] && null === $d['key'] && false === strpos( json_encode( $d ), 'biz-ffff' ) );
$nf = OHCL_Rest::owner_member( req( array( 'id' => 9999 ) ) );
check( 'hồ sơ không có ⇒ 404 ohcl_member_not_found', is_wp_error( $nf ) && 404 === $nf->get_error_data()['status'] && err4( $nf ) );
OHCL_Rest::owner_accept( req( array( 'id' => $mid ) ) );
check( 'POST …/accept: hành trình lên bậc 2 (đã tiếp nhận)', 2 === body( OHCL_Rest::owner_member( req( array( 'id' => $mid ) ) ) )['journey']['level'] );
$k1 = OHCL_Rest::owner_key( req( array( 'id' => $mid ) ) );
check( 'POST …/key: ⇒ 201, trả khoá thô MỘT lần (shown_once), kèm prefix và gói', 201 === $k1->status && 0 === strpos( body( $k1 )['key'], 'biz-' ) && true === body( $k1 )['shown_once'] && 'biz-…ffff' === body( $k1 )['key_prefix'] && 'no-store' === $k1->headers['Cache-Control'] );
$GLOBALS['core_keys'] = array( array( 'id' => 31, 'status' => 'trial', 'plan_code' => 'trial', 'plan_until' => '2030-01-01 00:00:00', 'last_used_at' => $now_s, 'created_at' => $now_s, 'key_prefix' => 'biz-…ffff', 'label' => 'Cửa hàng An', 'site_url' => '', 'coach_ref' => '' ) );
OHCL_Overview::flush();
$d2 = body( OHCL_Rest::owner_member( req( array( 'id' => $mid ) ) ) );
check( 'sau khi cấp: hồ sơ ghi key_id + prefix; KHÔNG chỗ nào còn khoá thô; hành trình lên bậc 4 khi đã dùng', 31 === $d2['key']['id'] && 'biz-…ffff' === $d2['key']['prefix'] && 4 === $d2['journey']['level'] && false === strpos( json_encode( $d2 ), 'biz-' . str_repeat( 'f', 32 ) ) && false === strpos( json_encode( $GLOBALS['meta'] ), 'biz-' . str_repeat( 'f', 32 ) ) );
$k2 = OHCL_Rest::owner_key( req( array( 'id' => $mid ) ) );
check( 'cấp khoá lần hai khi đã có khoá chạy ⇒ 409 ohcl_member_has_key, KHÔNG gọi lõi thêm', is_wp_error( $k2 ) && 409 === $k2->get_error_data()['status'] && 1 === $GLOBALS['key_calls'] );
OHCL_Core_Client::$transport = function () { return new WP_Error( 'x', 'down' ); };
$m2  = body( OHCL_Rest::owner_member_create( req( array( 'title' => 'Cơ sở hai' ) ) ) )['id'];
$kd  = OHCL_Rest::owner_key( req( array( 'id' => $m2 ) ) );
check( 'lõi không trả lời ⇒ 503 (không phải 200 giả) + _degraded, không lưu gì', is_wp_error( $kd ) && 503 === $kd->get_error_data()['status'] && 'ohcl_core_unreachable' === $kd->get_error_code() && 0 === (int) get_post_meta( $m2, '_bzm_key_id', true ) );
OHCL_Core_Client::$transport = function ( $m, $u ) { return array( 'status' => 200, 'body' => json_encode( array( 'ok' => true ) ) ); };
$rv = OHCL_Rest::owner_key_revoke( req( array( 'id' => $mid ) ) );
check( 'POST …/key/revoke ⇒ ok; hồ sơ chưa có khoá ⇒ 409', true === body( $rv )['ok'] && 409 === OHCL_Rest::owner_key_revoke( req( array( 'id' => $m2 ) ) )->get_error_data()['status'] );
$vr = OHCL_Rest::owner_verify( req( array( 'id' => $mid ), array( 'evidence' => array( 'gpkd' ), 'months' => 12, 'note' => 'ok' ) ) );
check( 'POST …/verify: xác minh với bằng chứng; thiếu bằng chứng ⇒ 400', true === body( $vr )['ok'] && true === body( OHCL_Rest::owner_member( req( array( 'id' => $mid ) ) ) )['verified'] && 400 === OHCL_Rest::owner_verify( req( array( 'id' => $m2 ), array( 'evidence' => array() ) ) )->get_error_data()['status'] );
OHCL_Rest::owner_unverify( req( array( 'id' => $mid ) ) );
check( 'DELETE …/verify: bỏ xác minh', false === body( OHCL_Rest::owner_member( req( array( 'id' => $mid ) ) ) )['verified'] );
$st = OHCL_Rest::owner_status( req( array( 'id' => $mid ), array( 'status' => 'paused' ) ) );
check( 'POST …/status: tạm dừng; trạng thái lạ ⇒ 400', true === body( $st )['ok'] && 'paused' === body( OHCL_Rest::owner_member( req( array( 'id' => $mid ) ) ) )['status'] && 400 === OHCL_Rest::owner_status( req( array( 'id' => $mid ), array( 'status' => 'bay' ) ) )->get_error_data()['status'] );
$lst = body( OHCL_Rest::owner_members( req( array( 'page' => 1, 'per_page' => 1 ) ) ) );
check( 'GET /owner/members: phân trang (per_page 1 ⇒ 1 mục, total_pages 2), mục tóm tắt không có liên hệ', 1 === count( $lst['items'] ) && 2 === $lst['total'] && 2 === $lst['total_pages'] && ! isset( $lst['items'][0]['profile'] ) );
check( 'lọc theo từ khoá và trạng thái', 1 === count( body( OHCL_Rest::owner_members( req( array( 'q' => 'cửa hàng an', 'page' => 1, 'per_page' => 20 ) ) ) )['items'] ) && 1 === count( body( OHCL_Rest::owner_members( req( array( 'status' => 'paused', 'page' => 1, 'per_page' => 20 ) ) ) )['items'] ) );
check( 'per_page bị kẹp ≤ 50', 50 === body( OHCL_Rest::owner_members( req( array( 'page' => 1, 'per_page' => 9999 ) ) ) )['per_page'] );
$ov = body( OHCL_Rest::owner_overview() );
check( 'GET /owner/overview: cộng đồng đếm thật, lõi ok, doanh thu null (chưa có nguồn, không ước số)', 2 === $ov['community']['total'] && true === $ov['core']['ok'] && null === $ov['revenue'] && isset( $ov['todo'] ) );

// [2026-11-26 01:10 PM Johnny Chu - Chu Hoàng Anh] OHCL-Q15
check( 'GET /owner/overview: có mới / rời 6 tháng (tháng này có 2 hồ sơ mới) và việc cần làm từ hồ sơ (chờ tiếp nhận)', 6 === count( $ov['community']['growth'] ) && 2 === end( $ov['community']['growth'] )['new'] && (bool) array_filter( $ov['todo'], function ( $t ) { return 'Hồ sơ mới chờ ban điều hành tiếp nhận' === $t['label'] && array( 'stage' => 1 ) === $t['args']; } ) );
OHCL_Rest::owner_status( req( array( 'id' => $m2 ), array( 'status' => 'left' ) ) );
$l1 = get_post_meta( $m2, '_bzm_left_at', true );
OHCL_Rest::owner_status( req( array( 'id' => $m2 ), array( 'status' => 'left' ) ) );
check( 'đánh dấu "Đã rời" ghi ngày rời MỘT lần (gọi lại không đổi ngày); hiện ở rời của tháng này', '' !== $l1 && $l1 === get_post_meta( $m2, '_bzm_left_at', true ) && 1 === end( body( OHCL_Rest::owner_overview() )['community']['growth'] )['left'] );
OHCL_Rest::owner_status( req( array( 'id' => $m2 ), array( 'status' => 'active' ) ) );
check( 'quay lại hoạt động ⇒ xoá ngày rời, không còn đếm là rời', '' === get_post_meta( $m2, '_bzm_left_at', true ) && 0 === end( body( OHCL_Rest::owner_overview() )['community']['growth'] )['left'] );

/* ---------- hội viên: chỉ thấy của mình ---------- */
update_post_meta( $mid, '_bzm_status', 'active' );
login( 0 );
$g = route_perm( '/me', 'GET' );
check( '/me: khách ⇒ 401', is_wp_error( $g ) && 401 === $g->get_error_data()['status'] );
login( 5, false );
check( '/me: tài khoản đăng nhập qua cổng quyền', true === route_perm( '/me', 'GET' ) );
$me = body( OHCL_Rest::me() );
check( '/me: email trùng hồ sơ ⇒ gắn tài khoản và trả đúng hồ sơ của mình + lộ trình 90 ngày', is_array( $me ) && $mid === $me['id'] && isset( $me['roadmap']['phases'] ) && 5 === (int) get_post_meta( $mid, '_bzm_user', true ) );
check( '/me: KHÔNG lộ trường nội bộ của chủ Hub (nguồn, Coach giới thiệu, tài khoản) và không lộ ghi chú hỗ trợ', ! isset( $me['source'] ) && ! isset( $me['ref_coach'] ) && ! isset( $me['user'] ) && ! isset( $me['support'] ) );
$GLOBALS['users'][6] = new T_User( 6, 'la@example.com' );
login( 6, false );
$noone = OHCL_Rest::me();
check( '/me: tài khoản không có hồ sơ ⇒ 404 ohcl_me_no_member (bốn trường), không lộ hồ sơ người khác', is_wp_error( $noone ) && 'ohcl_me_no_member' === $noone->get_error_code() && 404 === $noone->get_error_data()['status'] && err4( $noone ) );
$GLOBALS['users'][7] = new T_User( 7, 'hv@example.com' );
login( 7, false );
check( '/me: hồ sơ đã gắn tài khoản khác ⇒ 404, không cướp hồ sơ', is_wp_error( OHCL_Rest::me() ) && 5 === (int) get_post_meta( $mid, '_bzm_user', true ) );
login( 5, false );
$pf = OHCL_Rest::me_profile( req( array(), array( 'address' => 'Số 1 Phố Mới', 'hours' => '8-17', 'contact_zalo' => '0911222333', 'public_consent' => true ) ) );
check( 'POST /me/profile: sửa hồ sơ công khai của chính mình; đồng ý công khai ghi ngày', true === body( $pf )['ok'] && 'Số 1 Phố Mới' === get_post_meta( $mid, '_bzm_address', true ) && '' !== get_post_meta( $mid, '_bzm_public_consent', true ) );
$pb = OHCL_Rest::me_profile( req( array(), array( 'site_url' => 'ftp://x' ) ) );
check( 'profile sai website ⇒ 400 ohcl_member_url_invalid (bốn trường)', is_wp_error( $pb ) && 'ohcl_member_url_invalid' === $pb->get_error_code() && err4( $pb ) );
$pn = OHCL_Rest::me_profile( req( array(), array( 'title' => 'Đổi tên trái phép', 'segment' => 'nha-phan-phoi', 'email' => 'x@y.z', 'coach' => 'hack' ) ) );
check( 'cập nhật từng phần: chỉ gửi title (bị bỏ qua) không xoá địa chỉ, giờ mở cửa, Zalo, ô đồng ý công khai', 'Số 1 Phố Mới' === get_post_meta( $mid, '_bzm_address', true ) && '8-17' === get_post_meta( $mid, '_bzm_hours', true ) && '0911222333' === get_post_meta( $mid, '_bzm_contact_zalo', true ) && '' !== get_post_meta( $mid, '_bzm_public_consent', true ) );
check( 'hội viên KHÔNG sửa được tên, loại hình, email, Coach qua /me/profile', 'Cửa hàng An' === $GLOBALS['posts'][ $mid ]->post_title && 'cua-hang' === get_post_meta( $mid, '_bzm_segment', true ) && 'hv@example.com' === get_post_meta( $mid, '_bzm_email', true ) && '' === get_post_meta( $mid, '_bzm_coach', true ) );
$sp = OHCL_Rest::me_support( req( array(), array( 'text' => 'ngắn' ) ) );
check( '/me/support: quá ngắn ⇒ 400 ohcl_report_too_short', is_wp_error( $sp ) && 'ohcl_report_too_short' === $sp->get_error_code() );
$ok = OHCL_Rest::me_support( req( array(), array( 'text' => 'Trợ lý Zalo không trả lời khách từ sáng nay' ) ) );
check( '/me/support: gửi được ⇒ 201; chủ Hub đọc thấy ở hồ sơ', 201 === $ok->status && 1 === count( body( ( function () use ( $mid ) { login( 1, true ); return OHCL_Rest::owner_member( req( array( 'id' => $mid ) ) ); } )() )['support'] ) );
login( 5, false );
for ( $i = 0; $i < 6; $i++ ) { $last = OHCL_Rest::me_support( req( array(), array( 'text' => 'Yêu cầu hỗ trợ số ' . $i . ' của tôi' ) ) ); }
check( 'quá 5 yêu cầu / ngày ⇒ 429 ohcl_report_rate_limited', is_wp_error( $last ) && 429 === $last->get_error_data()['status'] );

// [2026-11-26 02:20 PM Johnny Chu - Chu Hoàng Anh] OHCL-H2
login( 5, false );
$mb = OHCL_Rest::me_benefits();
$mbd = body( $mb );
$byk = array();
foreach ( $mbd['benefits'] as $b ) { $byk[ $b['key'] ] = $b; }
check( 'GET /me/benefits: đủ 8 quyền lợi của chính mình, có trạng thái + lý do + việc tiếp theo; no-store', 8 === count( $mbd['benefits'] ) && isset( $byk['tech']['state'] ) && '' !== $byk['ai']['why'] && isset( $byk['ai']['next']['label'] ) && 'no-store' === $mb->headers['Cache-Control'] && 'soon' === $byk['growth']['state'] );
check( 'GET /me/benefits: "đã nhận" khớp số quyền lợi từ đã trao trở lên', $mbd['received'] === count( array_filter( $mbd['benefits'], function ( $b ) { return in_array( $b['state'], array( 'granted', 'activated', 'used' ), true ); } ) ) );
login( 6, false );
check( 'GET /me/benefits: tài khoản không có hồ sơ ⇒ 404 ohcl_me_no_member', is_wp_error( OHCL_Rest::me_benefits() ) && 'ohcl_me_no_member' === OHCL_Rest::me_benefits()->get_error_code() );
login( 1, true );
$ob = body( OHCL_Rest::owner_benefits( req( array() ) ) );
$obk = array();
foreach ( $ob['benefits'] as $b ) { $obk[ $b['key'] ] = $b; }
check( 'GET /owner/benefits: phễu 8 quyền lợi, mỗi dòng có đã nhận / mẫu số / %; sắp ra mắt đánh dấu', 8 === count( $ob['benefits'] ) && isset( $obk['ai']['reached'], $obk['ai']['of'], $obk['ai']['pct'] ) && true === $obk['growth']['soon'] );
$ow = body( OHCL_Rest::owner_benefits( req( array( 'benefit' => 'ai', 'state' => 'eligible' ) ) ) );
check( '/owner/benefits?benefit=ai&state=eligible: trả danh sách hội viên đứng sau con số (id, mã, tên)', is_array( $ow['members'] ) && ( ! $ow['members'] || isset( $ow['members'][0]['id'], $ow['members'][0]['title'], $ow['members'][0]['code'] ) ) );
$bk = OHCL_Rest::owner_benefits( req( array( 'benefit' => 'bay' ) ) );
check( 'quyền lợi lạ ⇒ 400 ohcl_benefit_unknown (bốn trường)', is_wp_error( $bk ) && 'ohcl_benefit_unknown' === $bk->get_error_code() && err4( $bk ) );
login( 0 );
check( 'cả hai route quyền lợi đóng với khách', is_wp_error( route_perm( '/owner/benefits', 'GET' ) ) && is_wp_error( route_perm( '/me/benefits', 'GET' ) ) );

// [2026-11-26 04:07 PM Johnny Chu - Chu Hoàng Anh] OHCL-H3
login( 5, false );
$oc0 = body( OHCL_Rest::me_outcomes() );
check( 'GET /me/outcomes: chưa khai ⇒ status "none"', 'none' === $oc0['status'] && array() === $oc0['items'] );
$oc1 = OHCL_Rest::me_outcomes_save( req( array(), array( 'outcomes' => array( array( 'label' => 'Tỉ lệ giá vốn', 'before' => '38%', 'after' => '33%', 'period' => '90 ngày' ), array( 'label' => 'Thiếu số' ) ), 'outcomes_consent' => true ) ) );
check( 'POST /me/outcomes: lưu 1 dòng hợp lệ (bỏ dòng thiếu số) ⇒ 201, trạng thái "pending" (chờ Hub duyệt), chưa công khai', 201 === $oc1->status && 1 === count( body( $oc1 )['items'] ) && 'pending' === body( $oc1 )['status'] );
login( 6, false );
check( '/me/outcomes: tài khoản không có hồ sơ ⇒ 404 (không lộ / không ghi vào hồ sơ người khác)', is_wp_error( OHCL_Rest::me_outcomes() ) && is_wp_error( OHCL_Rest::me_outcomes_save( req( array(), array( 'outcomes' => array() ) ) ) ) );
login( 1, true );
$rv = OHCL_Rest::owner_outcomes_review( req( array( 'id' => $mid ), array( 'approve' => true ) ) );
check( 'POST /owner/members/{id}/outcomes/review: Hub duyệt ⇒ ok, hội viên thấy "public"', true === body( $rv )['ok'] && ( function () { login( 5, false ); return 'public' === body( OHCL_Rest::me_outcomes() )['status']; } )() );
login( 1, true );
OHCL_Rest::owner_outcomes_review( req( array( 'id' => $mid ), array( 'approve' => false ) ) );
login( 5, false );
check( 'Hub gỡ (approve=false) ⇒ về "pending"', 'pending' === body( OHCL_Rest::me_outcomes() )['status'] );
login( 1, true );
$rn = OHCL_Rest::owner_outcomes_review( req( array( 'id' => $m2 ), array( 'approve' => true ) ) );
check( 'duyệt hồ sơ chưa khai / chưa đồng ý ⇒ 409 ohcl_outcomes_no_consent', is_wp_error( $rn ) && 409 === $rn->get_error_data()['status'] && 'ohcl_outcomes_no_consent' === $rn->get_error_code() );
login( 5, false );
$me2 = body( OHCL_Rest::me() );
check( 'GET /me: có khối impact (chưa đo ⇒ measured=false, views / contacts null — không phải 0)', isset( $me2['impact'] ) && false === $me2['impact']['measured'] && null === $me2['impact']['views'] && null === $me2['impact']['contacts'] );
login( 0 );
check( 'đường outcomes đóng với khách; beacon /places/{id}/contact mở công khai', is_wp_error( route_perm( '/me/outcomes', 'GET' ) ) && is_wp_error( route_perm( '/me/outcomes', 'POST' ) ) && is_wp_error( route_perm( '/owner/members/(?P<id>\\d+)/outcomes/review', 'POST' ) ) && true === route_perm( '/places/(?P<id>\\d+)/contact', 'POST' ) );

/* ---------- công khai ---------- */
login( 0 );
OHCL_Core_Client::$transport = function () { return array( 'status' => 200, 'body' => json_encode( array( 'items' => $GLOBALS['core_keys'], 'page' => 1 ) ) ); };
$v = array( 'by' => 1, 'at' => $now_s, 'until' => '2030-01-01 00:00:00', 'evidence' => array( 'gpkd' ), 'note' => '' );
update_post_meta( $mid, '_bzm_public_consent', $now_s ); update_post_meta( $mid, '_bzm_verify', $v ); update_post_meta( $mid, '_bzm_lat' , '21.03' ); update_post_meta( $mid, '_bzm_lng', '105.85' ); update_post_meta( $mid, '_bzm_key_id', 31 ); update_post_meta( $mid, '_bzm_status', 'active' );
OHCL_Overview::flush(); OHCL_Members::flush_public();
$pl = OHCL_Rest::places( req( array( 'page' => 1, 'per_page' => 20 ) ) );
$pd = body( $pl );
check( 'GET /places: chỉ V4, có url hồ sơ, cache công khai 60 giây', 1 === $pd['total'] && $mid === $pd['items'][0]['id'] && false !== strpos( $pd['items'][0]['url'], '/co-so/' . $mid ) && 'public, max-age=60' === $pl->headers['Cache-Control'] );
check( 'GET /places: không lộ email, điện thoại, khoá, hồ sơ nội bộ', false === strpos( json_encode( $pd ), 'hv@example.com' ) && false === strpos( json_encode( $pd ), '0912345678' ) && false === strpos( json_encode( $pd ), 'key' ) && false === strpos( json_encode( $pd ), 'support' ) );
check( 'GET /places: bộ lọc loại hình / tỉnh / từ khoá', 0 === body( OHCL_Rest::places( req( array( 'seg' => 'nha-phan-phoi', 'page' => 1, 'per_page' => 20 ) ) ) )['total'] && 1 === body( OHCL_Rest::places( req( array( 'seg' => 'cua-hang', 'page' => 1, 'per_page' => 20 ) ) ) )['total'] );
$pc = body( OHCL_Rest::place( req( array( 'id' => $mid ) ) ) );
check( 'GET /places/{id}: hồ sơ công khai', $mid === $pc['id'] && isset( $pc['url'] ) );
$pn = OHCL_Rest::place( req( array( 'id' => $m2 ) ) );
check( 'GET /places/{id} của cơ sở chưa công khai ⇒ 404 (không lộ có tồn tại)', is_wp_error( $pn ) && 404 === $pn->get_error_data()['status'] && 'ohcl_place_not_found' === $pn->get_error_code() && err4( $pn ) );
$mt = body( OHCL_Rest::meta() );
check( 'GET /meta: tên Hub, loại hình, trường công khai, số địa điểm; không có khoá / bí mật', 'Sample Hub' === $mt['hub']['name'] && isset( $mt['segments']['cua-hang'] ) && 1 === $mt['counts']['places'] && false === stripos( json_encode( $mt ), 'secret' ) );
for ( $i = 0; $i < 130; $i++ ) { $gate = OHCL_Rest::can_public(); }
check( 'công khai: quá 120 lần / phút / IP ⇒ 429 ohcl_rest_rate_limited', is_wp_error( $gate ) && 429 === $gate->get_error_data()['status'] && 'ohcl_rest_rate_limited' === $gate->get_error_code() );

/* ---------- một cửa: màn quản trị và REST cùng lớp dịch vụ ---------- */
$adm = (string) file_get_contents( $dir . '/includes/class-ohcl-admin-members.php' );
$myh = (string) file_get_contents( $dir . '/includes/class-ohcl-myhub.php' );
check( 'màn quản trị và My Hub gọi OHCL_Service (không còn bản sao logic cấp khoá / xác minh / hỗ trợ)', substr_count( $adm, 'OHCL_Service::' ) >= 5 && false === strpos( $adm, 'OHCL_Core_Client::issue_key' ) && false !== strpos( $myh, 'OHCL_Service::save_profile' ) && false !== strpos( $myh, 'OHCL_Service::add_support' ) );
$rest = (string) file_get_contents( $dir . '/includes/class-ohcl-rest.php' );
check( 'REST: không bảng mới, không gọi Cell / Zalo / LLM, không tự gọi lõi (chỉ qua OHCL_Service)', ! preg_match( '/CREATE\s+TABLE|dbDelta|OHCL_Core_Client::|zalo-hub|chat\/completions|BizCity_LLM/i', $rest ) );

// [2026-11-26 04:36 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6 — xuất dữ liệu, rời Hub, xoá thông tin, nhật ký qua REST.
login( 0 );
check( 'đường H6 đóng với khách: /me/export, /me/leave, /owner/export, /owner/audit, /owner/members/{id}/anonymize', is_wp_error( route_perm( '/me/export', 'GET' ) ) && is_wp_error( route_perm( '/me/leave', 'POST' ) ) && is_wp_error( route_perm( '/owner/export', 'GET' ) ) && is_wp_error( route_perm( '/owner/audit', 'GET' ) ) && is_wp_error( route_perm( '/owner/members/(?P<id>\\d+)/anonymize', 'POST' ) ) );
login( 5, false );
check( 'hội viên thường không vào được /owner/export, /owner/audit', is_wp_error( route_perm( '/owner/export', 'GET' ) ) && is_wp_error( route_perm( '/owner/audit', 'GET' ) ) );
$ex = body( OHCL_Rest::me_export() );
check( 'GET /me/export: gói của chính mình, đúng định dạng, không khoá', OHCL_Export::MEMBER_FORMAT === $ex['format'] && 0 === preg_match( '/biz-[0-9a-f]{16,}|ohs_[0-9a-f]{16,}/', json_encode( $ex ) ) );
$lv0 = OHCL_Rest::me_leave( req( array(), array() ) );
check( 'POST /me/leave thiếu confirm ⇒ 400 ohcl_confirm_required, hồ sơ chưa đổi', is_wp_error( $lv0 ) && 400 === $lv0->get_error_data()['status'] && 'ohcl_confirm_required' === $lv0->get_error_code() && 'left' !== OHCL_Members::get( $mid )['status'] );
login( 6, false );
check( '/me/export, /me/leave: tài khoản không có hồ sơ ⇒ lỗi', is_wp_error( OHCL_Rest::me_export() ) && is_wp_error( OHCL_Rest::me_leave( req( array(), array( 'confirm' => true ) ) ) ) );
login( 1, true );
$an0 = OHCL_Rest::owner_anonymize( req( array( 'id' => $mid ), array() ) );
check( 'POST /owner/members/{id}/anonymize thiếu confirm ⇒ 400; hồ sơ chưa rời ⇒ 409', is_wp_error( $an0 ) && 400 === $an0->get_error_data()['status'] && 409 === OHCL_Rest::owner_anonymize( req( array( 'id' => $mid ), array( 'confirm' => true ) ) )->get_error_data()['status'] );
$oe = body( OHCL_Rest::owner_export() );
check( 'GET /owner/export: danh sách hồ sơ, không chứa khoá', OHCL_Export::LIST_FORMAT === $oe['format'] && $oe['count'] >= 1 && 0 === preg_match( '/biz-[0-9a-f]{16,}/', json_encode( $oe ) ) );
login( 5, false );
$lv1 = OHCL_Rest::me_leave( req( array(), array( 'confirm' => true ) ) );
check( 'POST /me/leave confirm ⇒ ok, trạng thái "left"', true === body( $lv1 )['ok'] && 'left' === OHCL_Members::get( $mid )['status'] );
login( 1, true );
$au = body( OHCL_Rest::owner_audit( req() ) );
check( 'GET /owner/audit: thấy member_left + export_hub, mới nhất trước', in_array( 'member_left', array_column( $au['items'], 'a' ), true ) && in_array( 'export_hub', array_column( $au['items'], 'a' ), true ) );
check( 'POST /owner/members/{id}/anonymize sau khi rời + confirm ⇒ ok', true === body( OHCL_Rest::owner_anonymize( req( array( 'id' => $mid ), array( 'confirm' => true ) ) ) )['ok'] && '' !== OHCL_Members::get( $mid )['anonymized_at'] );

echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
