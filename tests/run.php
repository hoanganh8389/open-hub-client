<?php
/**
 * Unit tests of open-hub-client Đ1 (no WordPress). Run: php tests/run.php — exit 0 = all pass.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 05:38 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1 — test: hợp đồng ngành @2, module, ô mở rộng, một cửa gọi lõi, ranh giới mã.

define( 'ABSPATH', __DIR__ . '/' );
define( 'OHCL_TESTING', true );

/* ---- WordPress stubs ---- */
class WP_Error {
	private $code; private $message; private $data;
	public function __construct( $code = '', $message = '', $data = null ) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
	public function get_error_data() { return $this->data; }
}
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function wp_json_encode( $v ) { return json_encode( $v ); }
$GLOBALS['hooks'] = array(); $GLOBALS['opts'] = array(); $GLOBALS['opt_writes'] = 0;
function add_filter( $h, $cb, $p = 10, $n = 1 ) { $GLOBALS['hooks'][ $h ][] = $cb; return true; }
function add_action( $h, $cb, $p = 10, $n = 1 ) { return add_filter( $h, $cb ); }
function remove_all_filters( $h ) { unset( $GLOBALS['hooks'][ $h ] ); }
function apply_filters( $h, $v ) { $a = func_get_args(); foreach ( isset( $GLOBALS['hooks'][ $h ] ) ? $GLOBALS['hooks'][ $h ] : array() as $cb ) { $a[1] = $v; $v = call_user_func_array( $cb, array_slice( $a, 1 ) ); } return $v; }
function do_action( $h ) { $a = array_slice( func_get_args(), 1 ); foreach ( isset( $GLOBALS['hooks'][ $h ] ) ? $GLOBALS['hooks'][ $h ] : array() as $cb ) { call_user_func_array( $cb, $a ); } }
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['opts'] ) ? $GLOBALS['opts'][ $k ] : $d; }
function update_option( $k, $v, $autoload = null ) { $GLOBALS['opt_writes']++; $GLOBALS['opts'][ $k ] = $v; return true; }

$dir = dirname( __DIR__ );
foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview', 'class-ohcl-members', 'class-ohcl-benefits' ) as $f ) {
	require $dir . '/includes/' . $f . '.php';
}

$pass = 0; $fail = 0;
function check( $name, $ok ) { global $pass, $fail; if ( $ok ) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; } }
function code_of( $x ) { return is_wp_error( $x ) ? $x->get_error_code() : ''; }
function has( array $list, $needle ) { foreach ( $list as $l ) { if ( false !== strpos( $l, $needle ) ) { return true; } } return false; }

/* ---- 1. Hợp đồng ngành @2 ---- */
require $dir . '/examples/wp-sample-hub/wp-sample-hub.php';
$sample = wp_sample_hub_industry();
$r      = OHCL_Industry::validate( $sample );
check( 'ngành mẫu đạt @2, không lỗi, không cảnh báo', $r['ok'] && ! $r['errors'] && ! $r['warnings'] && ! $r['ignored'] );
check( 'ngành mẫu: trường hồ sơ lưu ở meta _bzi_<key>', '_bzi_nhom_hang' === $r['config']['member_fields'][0]['meta'] );
check( 'ngành mẫu: thuật ngữ ghi đè, thiếu thì mặc định', 'cửa hàng' === $r['config']['terms']['business'] && 'hội viên' === $r['config']['terms']['member'] );

check( 'không có dữ liệu ⇒ cấu hình mặc định + lỗi no_industry', ! OHCL_Industry::validate( array() )['ok'] && has( OHCL_Industry::validate( array() )['errors'], 'no_industry' ) );
$bad = $sample; $bad['contract'] = 'bizcity_industry_hub@1';
check( 'sai phiên bản hợp đồng ⇒ lỗi, chạy mặc định', ! OHCL_Industry::validate( $bad )['ok'] && '' === OHCL_Industry::validate( $bad )['config']['id'] );
$bad = $sample; $bad['id'] = 'Vegan Hub';
check( 'id sai dạng ⇒ lỗi', has( OHCL_Industry::validate( $bad )['errors'], 'id:' ) );
$bad = $sample; $bad['brand']['accent'] = 'green';
check( 'màu nhấn không phải #rrggbb ⇒ lỗi (R-HUB-8)', has( OHCL_Industry::validate( $bad )['errors'], 'brand.accent' ) );
$bad = $sample; $bad['brand']['slogan'] = 'font: Caveat';
check( 'font viết tay ⇒ lỗi (R-HUB-13)', has( OHCL_Industry::validate( $bad )['errors'], 'R-HUB-13' ) );

$w = $sample;
$w['member_fields'][] = array( 'key' => 'stage', 'label' => 'Giai đoạn', 'type' => 'text' );
$w['member_fields'][] = array( 'key' => 'mau', 'label' => 'Màu', 'type' => 'select' );
$w['member_fields'][] = array( 'key' => 'anh', 'label' => 'Ảnh', 'type' => 'file' );
$w['terms']['khach'] = 'khách';
$w['modules']['02'] = false;
$w['modules']['15'] = true;
$w['trial_days'] = 365;
$w['hack'] = 'x';
$w['standards'][] = array( 'code' => 'x', 'label' => '' );
$rw = OHCL_Industry::validate( $w );
check( 'mục hỏng bị bỏ, phần còn lại giữ (vẫn ok)', $rw['ok'] && 2 === count( $rw['config']['member_fields'] ) && 1 === count( $rw['config']['standards'] ) );
check( 'trường trùng trường lõi (stage) bị bỏ', has( $rw['warnings'], '"stage"' ) );
check( 'select thiếu options bị bỏ; type lạ bị bỏ', has( $rw['warnings'], 'cần options' ) && has( $rw['warnings'], 'không hỗ trợ' ) );
check( 'thuật ngữ ngoài bảng bị bỏ', has( $rw['warnings'], 'terms.khach' ) && ! isset( $rw['config']['terms']['khach'] ) );
check( 'module bắt buộc không tắt được, module 15 không tồn tại', has( $rw['warnings'], 'modules.02' ) && has( $rw['warnings'], 'modules.15' ) && ! isset( $rw['config']['modules']['02'] ) );
check( 'trial_days ngoài 1–90 ⇒ 30', 30 === $rw['config']['trial_days'] );
check( 'khoá lạ được liệt kê, không dùng', in_array( 'hack', $rw['ignored'], true ) && ! isset( $rw['config']['hack'] ) );
$x = $sample; $x['leader'] = '<script>alert(1)</script>Hội Chay';
check( 'chuỗi bị bỏ thẻ HTML', 'alert(1)Hội Chay' === OHCL_Industry::validate( $x )['config']['leader'] );

/* runtime: validate once, cache by hash */
OHCL_Industry::reset(); $GLOBALS['opt_writes'] = 0;
$cfg1 = OHCL_Industry::current();
OHCL_Industry::reset();
$cfg2 = OHCL_Industry::current();
check( 'runtime: kiểm một lần, lần sau đọc bản đã lưu', 'sample' === $cfg1['id'] && $cfg1 === $cfg2 && 1 === $GLOBALS['opt_writes'] );
check( 'term() đọc thuật ngữ ngành', 'cửa hàng' === OHCL_Industry::term( 'business' ) && 'xyz' === OHCL_Industry::term( 'xyz' ) );

/* ---- 2. Module ---- */
check( '14 module, 5 bắt buộc', 14 === count( OHCL_Modules::LIST ) && 5 === count( array_filter( OHCL_Modules::LIST, function ( $m ) { return $m['required']; } ) ) );
check( 'bắt buộc luôn bật kể cả chủ Hub tắt', OHCL_Modules::decide( '02', array( '02' => false ), array( '02' => false ) ) );
check( 'chủ Hub > ngành > mặc định bật', false === OHCL_Modules::decide( '04', array( '04' => false ), array( '04' => true ) ) && false === OHCL_Modules::decide( '04', array(), array( '04' => false ) ) && OHCL_Modules::decide( '04', array(), array() ) );
check( 'runtime: ngành mẫu tắt module 09', false === OHCL_Modules::enabled( '09' ) && OHCL_Modules::enabled( '08' ) );
check( 'set_enabled: tắt module bắt buộc ⇒ 409', 'ohcl_module_required' === code_of( OHCL_Modules::set_enabled( '12', false ) ) );
check( 'set_enabled: chủ Hub bật lại 09', true === OHCL_Modules::set_enabled( '09', true ) && OHCL_Modules::enabled( '09' ) && 'owner' === OHCL_Modules::all()['09']['source'] );

/* ---- 3. Ô mở rộng + sự kiện ---- */
add_filter( 'ohcl_member360_tabs', function ( $t ) { $t[] = array( 'id' => 'hong', 'label' => 'Hỏng', 'render' => function () { throw new RuntimeException( 'boom' ); } ); $t[] = array( 'id' => 'Sai Id', 'render' => 'strlen' ); $t[] = array( 'id' => 'khong-goi', 'render' => 'no_such_fn' ); return $t; } );
$items = OHCL_Slots::items( 'member360_tabs', array( 'id' => 1 ) );
check( 'ô: chỉ nhận item có id hợp lệ + render gọi được', array( 'hong', 'nhom-hang' ) === array_column( $items, 'id' ) );
$html = '';
foreach ( $items as $it ) { $html .= OHCL_Slots::render( 'member360_tabs', $it ); }
$lvl = ob_get_level();
check( 'ô: render lỗi bị ẩn, ô khác vẫn hiện, không rò output buffer', false !== strpos( $html, 'Nhóm hàng của cửa hàng' ) && has( OHCL_Slots::$errors, 'hong' ) && 0 === $lvl );
check( 'ô: tên ô lạ ⇒ rỗng', array() === OHCL_Slots::items( 'admin_menu' ) );
$seen = null;
add_action( 'ohcl_key_issued', function ( $p ) use ( &$seen ) { $seen = $p; throw new RuntimeException( 'listener lỗi' ); } );
OHCL_Slots::emit( 'key_issued', array( 'key_id' => 7, 'key' => 'biz-' . str_repeat( 'a', 32 ), 'key_prefix' => 'biz-…aaaa' ) );
check( 'sự kiện: bỏ khoá thô khỏi payload, lỗi listener không lan ra', is_array( $seen ) && ! isset( $seen['key'] ) && 7 === $seen['key_id'] && has( OHCL_Slots::$errors, 'listener' ) );

/* ---- 4. Một cửa gọi lõi ---- */
$secret = 'ohs_' . str_repeat( 'b', 32 );
OHCL_Core_Client::$config = array( 'base_url' => 'https://bizcity.vn', 'hub' => 'sample', 'secret' => $secret );
$calls = array();
OHCL_Core_Client::$transport = function ( $m, $u, $h, $b ) use ( &$calls ) {
	$calls[] = compact( 'm', 'u', 'h', 'b' );
	if ( false !== strpos( $u, '/hubs' ) ) { return array( 'status' => 200, 'body' => '{"success":true,"hubs":[{"slug":"sample","name":"Sample Hub","industry":"Ngành mẫu"}]}' ); }
	if ( false !== strpos( $u, '/revoke' ) ) { return array( 'status' => 404, 'body' => '{"code":"ohc_key_not_found","message":"Không thấy khoá này trong Hub.","data":{"status":404,"hint":"Tải lại danh sách khoá.","help_code":"ohc_key_not_found"}}' ); }
	if ( 'POST' === $m ) { return array( 'status' => 201, 'body' => '{"success":true,"id":3,"key":"biz-' . str_repeat( 'c', 32 ) . '","key_prefix":"biz-…cccc"}' ); }
	return array( 'status' => 200, 'body' => '{"success":true,"items":[],"page":1}' );
};
$k = OHCL_Core_Client::issue_key( 'Cửa hàng A', 'https://a.vn', 'coach-a' );
$last = end( $calls );
check( 'core: cấp khoá gửi X-OHC-Hub + Bearer, thân JSON', 3 === $k['id'] && 'sample' === $last['h']['X-OHC-Hub'] && 'Bearer ' . $secret === $last['h']['Authorization'] && 'https://bizcity.vn/wp-json/open-hub-core/v1/hub/keys' === $last['u'] && 'coach-a' === json_decode( $last['b'], true )['coach_ref'] );
check( 'core: thiếu tên cơ sở ⇒ lỗi trước khi gọi mạng', 'ohcl_key_label_required' === code_of( OHCL_Core_Client::issue_key( ' ' ) ) && count( $calls ) === 1 );
$rv = OHCL_Core_Client::revoke_key( 99 );
check( 'core: lỗi của lõi đi nguyên bốn trường', 'ohc_key_not_found' === code_of( $rv ) && 'Tải lại danh sách khoá.' === $rv->get_error_data()['hint'] && 404 === $rv->get_error_data()['status'] );
check( 'core: /hubs không gửi bí mật', ! isset( $calls[ count( $calls ) - 1 ]['h']['Authorization'] ) || false === strpos( implode( '', array_map( 'json_encode', array( OHCL_Core_Client::hubs() ) ) ), $secret ) );
$hubs_call = null; foreach ( $calls as $c ) { if ( false !== strpos( $c['u'], '/hubs' ) ) { $hubs_call = $c; } }
check( 'core: GET /hubs không kèm Authorization', $hubs_call && ! isset( $hubs_call['h']['Authorization'] ) );
$p = OHCL_Core_Client::ping();
check( 'core: ping đủ 4 bước PASS', $p['ok'] && 4 === count( $p['steps'] ) );

OHCL_Core_Client::$transport = function () { return new WP_Error( 'http_request_failed', 'cURL error 28 Authorization: Bearer ' . 'ohs_' . str_repeat( 'b', 32 ) ); };
$e = OHCL_Core_Client::list_keys();
check( 'core: mất mạng ⇒ _degraded, không lộ bí mật trong lỗi', 'ohcl_core_unreachable' === code_of( $e ) && ! empty( $e->get_error_data()['_degraded'] ) && false === strpos( json_encode( array( $e->get_error_message(), $e->get_error_data() ) ), $secret ) );
OHCL_Core_Client::$transport = function () { return array( 'status' => 502, 'body' => '<html>Bad gateway</html>' ); };
check( 'core: trả HTML (Cloudflare 502) ⇒ bad_response, degraded', 'ohcl_core_bad_response' === code_of( OHCL_Core_Client::list_keys() ) );
OHCL_Core_Client::$config = array( 'base_url' => 'http://bizcity.vn', 'hub' => 'sample', 'secret' => $secret );
check( 'core: http (không phải https) bị từ chối', 'ohcl_core_not_configured' === code_of( OHCL_Core_Client::list_keys() ) );
OHCL_Core_Client::$config = array( 'base_url' => 'https://bizcity.vn', 'hub' => 'sample', 'secret' => 'sai' );
check( 'core: bí mật sai dạng ⇒ chưa cấu hình, không gọi mạng', 'ohcl_core_not_configured' === code_of( OHCL_Core_Client::list_keys() ) && ! OHCL_Core_Client::ping()['ok'] );

/* ---- 4b. Tổng quan từ khoá thật (OHCL-D1B) ---- */
if ( ! defined( 'DAY_IN_SECONDS' ) ) { define( 'DAY_IN_SECONDS', 86400 ); }
$now  = '2026-11-25 12:00:00';
$keys = array(
	array( 'status' => 'trial', 'plan_code' => 'trial', 'plan_until' => '2026-11-28 00:00:00', 'last_used_at' => '2026-11-24 10:00:00', 'created_at' => '2026-11-01 00:00:00' ),
	array( 'status' => 'trial', 'plan_code' => 'trial', 'plan_until' => '2026-12-20 00:00:00', 'last_used_at' => null, 'created_at' => '2026-11-10 00:00:00' ),
	array( 'status' => 'trial', 'plan_code' => 'trial', 'plan_until' => '2026-12-24 00:00:00', 'last_used_at' => null, 'created_at' => '2026-11-24 00:00:00' ),
	array( 'status' => 'active', 'plan_code' => 'ai_std', 'plan_until' => '2027-01-01 00:00:00', 'last_used_at' => '2026-09-01 00:00:00', 'created_at' => '2026-08-01 00:00:00' ),
	array( 'status' => 'revoked', 'plan_code' => 'trial', 'plan_until' => '2026-12-01 00:00:00', 'last_used_at' => '2026-11-20 00:00:00', 'created_at' => '2026-10-01 00:00:00' ),
	array( 'status' => 'expired', 'plan_code' => 'trial', 'plan_until' => '2026-11-01 00:00:00', 'last_used_at' => null, 'created_at' => '2026-10-01 00:00:00' ),
);
$s = OHCL_Overview::from_keys( $keys, $now );
check( 'tổng quan: đếm đúng cấp / còn dùng / đã dùng / 30 ngày / trả phí', 6 === $s['issued'] && 4 === $s['live'] && 2 === $s['used_ever'] && 1 === $s['used_30d'] && 1 === $s['paid'] && 1 === $s['revoked'] && 1 === $s['expired'] );
check( 'tổng quan: sắp hết hạn 7 ngày = 1; nhận ≥ 7 ngày chưa dùng = 1 (khoá mới hôm qua không tính)', 1 === $s['expiring_7d'] && 1 === $s['unused_7d'] );
$fn = OHCL_Overview::funnel( $s );
check( 'phễu: 5 bậc, % theo số đã cấp', 5 === count( $fn ) && 100 === $fn[0]['pct'] && 67 === $fn[1]['pct'] && 17 === $fn[4]['pct'] );
check( 'phễu: không khoá ⇒ 0%, không chia 0', 0 === OHCL_Overview::funnel( OHCL_Overview::from_keys( array(), $now ) )[0]['pct'] );
$todo = OHCL_Overview::todo( $s, array( 'ok' => true, 'errors' => array(), 'warnings' => array() ), false );
check( 'việc cần làm: lõi chưa kết nối đứng đầu, kèm hết hạn + chưa dùng', 'open-hub-settings' === $todo[0]['page'] && 3 === count( $todo ) );
check( 'việc cần làm: không có gì ⇒ rỗng (không bịa việc)', array() === OHCL_Overview::todo( OHCL_Overview::from_keys( array(), $now ), array( 'ok' => true, 'errors' => array(), 'warnings' => array() ), true ) );

/* ---- 4b. Việc cần làm từ hồ sơ hội viên + mới / rời theo tháng (OHCL-Q15) ---- */
// [2026-11-26 01:10 PM Johnny Chu - Chu Hoàng Anh] OHCL-Q15
$T   = strtotime( $now . ' UTC' );
$mk  = function ( array $o ) {
	return array_merge( array( 'id' => 1, 'code' => 'HV-000001', 'title' => 'X', 'status' => 'active', 'key_id' => 0, 'accepted_at' => '', 'joined_at' => '2026-11-10 00:00:00', 'left_at' => '', 'verify' => '', 'coach' => '', 'support_at' => '', 'certs' => '' ), $o );
};
$live = array( 11 => array( 'id' => 11, 'status' => 'active', 'plan_code' => 'p', 'plan_until' => '2030-01-01 00:00:00', 'last_used_at' => '2026-11-24 00:00:00', 'created_at' => '2026-09-01 00:00:00' ) );
$mem  = array(
	$mk( array( 'id' => 1 ) ),                                                                                                   // chờ tiếp nhận
	$mk( array( 'id' => 2 ) ),                                                                                                   // chờ tiếp nhận
	$mk( array( 'id' => 3, 'accepted_at' => '2026-11-11 00:00:00' ) ),                                                           // đã tiếp nhận, chưa khoá
	$mk( array( 'id' => 4, 'accepted_at' => '2026-11-11 00:00:00', 'key_id' => 11 ) ),                                           // đang dùng, chưa xác minh
	$mk( array( 'id' => 5, 'accepted_at' => '2026-11-11 00:00:00', 'key_id' => 11, 'verify' => array( 'until' => '2026-12-10 00:00:00' ), 'coach' => 'c1', 'support_at' => '2026-11-23 10:00:00' ) ), // xác minh sắp hết, có hỗ trợ mới
	$mk( array( 'id' => 6, 'status' => 'left' ) ),                                                                                // đã rời: không tính
	$mk( array( 'id' => 7, 'status' => 'paused' ) ),                                                                              // tạm dừng: không tính
);
$td  = OHCL_Overview::member_todo( $mem, $live, $T, true );
$by  = array();
foreach ( $td as $t ) { $by[ $t['label'] ] = $t; }
check( 'việc từ hồ sơ: 2 hồ sơ chờ tiếp nhận (không tính đã rời / tạm dừng), liên kết lọc bậc 1', 2 === $by['Hồ sơ mới chờ ban điều hành tiếp nhận']['n'] && array( 'stage' => 1 ) === $by['Hồ sơ mới chờ ban điều hành tiếp nhận']['args'] && 'open-hub-members' === $by['Hồ sơ mới chờ ban điều hành tiếp nhận']['page'] );
check( 'việc từ hồ sơ: 1 đã tiếp nhận chưa khoá (bậc 2); 1 đang dùng chờ xác minh (bậc 4)', 1 === $by['Đã tiếp nhận, chờ cấp khoá trợ lý AI']['n'] && 1 === $by['Đang dùng trợ lý, chờ xác minh để lên bản đồ']['n'] && array( 'stage' => 4 ) === $by['Đang dùng trợ lý, chờ xác minh để lên bản đồ']['args'] );
check( 'việc từ hồ sơ: xác minh sắp hết trong 30 ngày = 1; hỗ trợ mới trong 7 ngày = 1 và đứng ĐẦU danh sách', 1 === $by['Xác minh sắp hết hạn trong 30 ngày']['n'] && 1 === $by['Yêu cầu hỗ trợ mới của hội viên trong 7 ngày']['n'] && 'Yêu cầu hỗ trợ mới của hội viên trong 7 ngày' === $td[0]['label'] );
check( 'việc từ hồ sơ: "chưa có Coach" chỉ khi module Coach bật và có Coach; đếm hội viên đã tiếp nhận chưa gắn Coach (3, 4)', 2 === $by['Hội viên chưa có Coach phụ trách']['n'] && ! array_filter( OHCL_Overview::member_todo( $mem, $live, $T, false ), function ( $t ) { return 'Hội viên chưa có Coach phụ trách' === $t['label']; } ) );
check( 'lõi không đọc được (không có khoá) ⇒ KHÔNG bịa việc "chờ xác minh"; việc chờ tiếp nhận vẫn đúng', ! array_filter( OHCL_Overview::member_todo( $mem, array(), $T, false ), function ( $t ) { return 0 === strpos( $t['label'], 'Đang dùng trợ lý' ); } ) && 2 === OHCL_Overview::member_todo( $mem, array(), $T, false )[1]['n'] );
check( 'không hồ sơ nào cần làm ⇒ rỗng (không bịa việc)', array() === OHCL_Overview::member_todo( array(), array(), $T, true ) );
$gr = OHCL_Members::growth( array( $mk( array( 'joined_at' => '2026-11-03 00:00:00' ) ), $mk( array( 'joined_at' => '2026-11-20 00:00:00' ) ), $mk( array( 'joined_at' => '2026-09-15 00:00:00', 'status' => 'left', 'left_at' => '2026-10-02 00:00:00' ) ), $mk( array( 'joined_at' => '2025-01-01 00:00:00' ) ), $mk( array( 'joined_at' => '2026-10-05 00:00:00', 'status' => 'active', 'left_at' => '2026-10-09 00:00:00' ) ) ), $T, 6 );
$mo = array();
foreach ( $gr as $g ) { $mo[ $g['month'] ] = $g; }
check( 'mới / rời theo tháng: 6 tháng liên tục, tháng 11 có 2 mới, tháng 9 có 1 mới, tháng 10 có 1 mới + 1 rời', 6 === count( $gr ) && '2026-06' === $gr[0]['month'] && '2026-11' === $gr[5]['month'] && 2 === $mo['2026-11']['new'] && 1 === $mo['2026-09']['new'] && 1 === $mo['2026-10']['new'] && 1 === $mo['2026-10']['left'] );
check( 'mới / rời: hồ sơ ngoài 6 tháng không đếm; người có left_at nhưng đã quay lại hoạt động không đếm là rời', 0 === array_sum( array_column( $gr, 'new' ) ) - 4 && 1 === array_sum( array_column( $gr, 'left' ) ) );

/* ---- 4c. Sổ quyền lợi: tám quyền lợi tính từ dữ liệu có sẵn (OHCL-H2) ---- */
// [2026-11-26 02:20 PM Johnny Chu - Chu Hoàng Anh] OHCL-H2
$bctx = array( 'modules' => array( '03' => true, '04' => true, '05' => true, '06' => true, '07' => true, '09' => true ), 'availability' => array(), 'tracks' => 2, 'now' => $T );
$bm   = function ( array $o ) use ( $mk ) { return $mk( array_merge( array( 'accepted_at' => '2026-11-11 00:00:00', 'segment' => 'x', 'lat' => '', 'lng' => '', 'consent' => '', 'certs' => '' ), $o ) ); };
$st   = function ( $key, array $m, $kv = null, array $ctx = null ) use ( $bctx ) { return OHCL_Benefits::state( $key, $m, $kv, null === $ctx ? $bctx : $ctx ); };
check( 'quyền lợi: tám khoá, mỗi khoá có tên, biểu tượng, mức sẵn sàng', 8 === count( OHCL_Benefits::DEFS ) && array( 'tech', 'training', 'marketing', 'ai', 'coach', 'network', 'trust', 'growth' ) === array_keys( OHCL_Benefits::DEFS ) );
check( 'chưa được tiếp nhận ⇒ mọi quyền lợi (trừ sắp ra mắt) "Chưa mở" kèm lý do', 'locked' === $st( 'tech', $mk( array() ) )['state'] && 'locked' === $st( 'ai', $mk( array() ) )['state'] && false !== strpos( $st( 'ai', $mk( array() ) )['why'], 'tiếp nhận' ) );
check( 'tạm dừng / đã rời ⇒ "Chưa mở", lý do nói đúng', 'locked' === $st( 'tech', $bm( array( 'status' => 'left' ) ) )['state'] && false !== strpos( $st( 'tech', $bm( array( 'status' => 'left' ) ) )['why'], 'tạm dừng' ) );
check( '"Cơ hội tăng trưởng" là lộ trình ⇒ "Sắp ra mắt", không bao giờ tính là đã nhận', 'soon' === $st( 'growth', $bm( array() ) )['state'] && 'soon' === $st( 'growth', $bm( array( 'key_id' => 11 ) ), $live[11] )['state'] );
$ai0 = $st( 'ai', $bm( array() ) );
check( 'AI: đã tiếp nhận, chưa khoá ⇒ "Có thể nhận" + việc nhắn ban điều hành', 'eligible' === $ai0['state'] && 'Nhắn ban điều hành cấp khoá' === $ai0['next']['label'] );
$kv_unused = array( 'id' => 11, 'status' => 'trial', 'plan_code' => 'trial', 'plan_until' => '2030-01-01 00:00:00', 'last_used_at' => null, 'created_at' => '2026-11-20 00:00:00' );
$kv_old    = array_merge( $kv_unused, array( 'last_used_at' => '2026-08-01 00:00:00' ) );
check( 'AI: có khoá chưa dùng ⇒ "Đã trao"; từng dùng nhưng quá 30 ngày ⇒ "Đã bật"; dùng trong 30 ngày ⇒ "Đang dùng"', 'granted' === $st( 'ai', $bm( array( 'key_id' => 11 ) ), $kv_unused )['state'] && 'activated' === $st( 'ai', $bm( array( 'key_id' => 11 ) ), $kv_old )['state'] && 'used' === $st( 'ai', $bm( array( 'key_id' => 11 ) ), $live[11] )['state'] );
$tech = $st( 'tech', $bm( array( 'key_id' => 11 ) ), $live[11] );
check( 'Công nghệ: có khoá chạy ⇒ dừng ở "Đã trao" (chưa có tín hiệu ứng dụng nên KHÔNG nói "đang dùng"), có ghi chú vì sao', 'granted' === $tech['state'] && false !== strpos( $tech['note'], 'hiện chỉ biết' ) && 'used' !== $tech['state'] );
check( 'Đào tạo: có lộ trình ⇒ "Có thể nhận" + liên kết trang Học viện + ghi chú ghi danh chưa mở; không lộ trình ⇒ vẫn "Có thể nhận" nhưng không có việc tiếp theo', 'eligible' === $st( 'training', $bm( array() ) )['state'] && 'page:academy' === $st( 'training', $bm( array() ) )['next']['target'] && '' === $st( 'training', $bm( array() ), null, array_merge( $bctx, array( 'tracks' => 0 ) ) )['next']['label'] && 'limited' === $st( 'training', $bm( array() ) )['availability'] );
$ver = array( 'until' => '2026-12-30 00:00:00' );
check( 'Khách hàng: chưa xác minh ⇒ "Có thể nhận"; đã xác minh nhưng thiếu đồng ý / toạ độ ⇒ "Đã trao"; lên bản đồ V4 ⇒ "Đang dùng"', 'eligible' === $st( 'marketing', $bm( array( 'key_id' => 11 ) ), $live[11] )['state'] && 'granted' === $st( 'marketing', $bm( array( 'key_id' => 11, 'verify' => $ver ) ), $live[11] )['state'] && 'used' === $st( 'marketing', $bm( array( 'key_id' => 11, 'verify' => $ver, 'lat' => '21', 'lng' => '105', 'consent' => '2026-11-12 00:00:00' ) ), $live[11] )['state'] );
check( 'Coach: chưa có ⇒ "Có thể nhận"; có mã Coach ⇒ "Đã trao" (dừng, Workspace chưa mở, có ghi chú)', 'eligible' === $st( 'coach', $bm( array() ) )['state'] && 'granted' === $st( 'coach', $bm( array( 'coach' => 'c1' ) ) )['state'] && '' !== $st( 'coach', $bm( array( 'coach' => 'c1' ) ) )['note'] );
check( 'Giao thương: chỉ "Đã trao" khi hồ sơ công khai (V4), không bao giờ "đang dùng" (chưa đo lượt kết nối)', 'eligible' === $st( 'network', $bm( array() ) )['state'] && 'granted' === $st( 'network', $bm( array( 'key_id' => 11, 'verify' => $ver, 'lat' => '21', 'lng' => '105', 'consent' => '2026-11-12 00:00:00' ) ), $live[11] )['state'] );
$cert = array( 'thuan-chay' => array( 'label' => 'Thuần chay', 'until' => '2026-12-30 00:00:00', 'revoked' => false ) );
check( 'Uy tín: chưa ⇒ "Có thể nhận"; Verified ⇒ "Đã trao"; Certified còn hạn ⇒ "Đang dùng"', 'eligible' === $st( 'trust', $bm( array() ) )['state'] && 'granted' === $st( 'trust', $bm( array( 'verify' => $ver ) ) )['state'] && 'used' === $st( 'trust', $bm( array( 'verify' => $ver, 'certs' => $cert ) ) )['state'] );
check( 'module bị tắt ⇒ "Hub chưa bật", không đếm', 'off' === $st( 'trust', $bm( array() ), null, array_merge( $bctx, array( 'modules' => array_merge( $bctx['modules'], array( '06' => false ) ) ) ) )['state'] );
check( 'bộ lọc ohcl_benefit_availability (ngành tinh chỉnh) đổi mức sẵn sàng; "lộ trình" ⇒ sắp ra mắt', 'soon' === $st( 'coach', $bm( array() ), null, array_merge( $bctx, array( 'availability' => array( 'coach' => 'roadmap' ) ) ) )['state'] );
$per = function ( array $m, $kv ) use ( $bctx ) { return OHCL_Benefits::for_member( $m, $kv, $bctx ); };
$rows = array( 1 => $per( $bm( array( 'id' => 1 ) ), null ), 2 => $per( $bm( array( 'id' => 2, 'key_id' => 11 ) ), $live[11] ), 3 => $per( $mk( array( 'id' => 3 ) ), null ) );
$fn   = array();
foreach ( OHCL_Benefits::funnel( $rows ) as $f ) { $fn[ $f['key'] ] = $f; }
check( 'phễu: AI — 3 hội viên, 1 chưa tiếp nhận (không tính vào mẫu số), 1 có thể nhận, 1 đang dùng ⇒ đã nhận 1 / 2 = 50%', 1 === $fn['ai']['counts']['eligible'] && 1 === $fn['ai']['counts']['used'] && 1 === $fn['ai']['counts']['locked'] && 1 === $fn['ai']['reached'] && 2 === $fn['ai']['of'] && 50 === $fn['ai']['pct'] );
check( 'phễu: "Cơ hội tăng trưởng" hiện "sắp ra mắt", không có số đã nhận', true === $fn['growth']['soon'] && 0 === $fn['growth']['reached'] );
check( 'received(): đếm đúng quyền lợi đã trao trở lên (công nghệ, AI, khách hàng, Coach, uy tín = 5; giao thương mới "có thể nhận" và tăng trưởng "sắp ra mắt" không tính)', 5 === OHCL_Benefits::received( $per( $bm( array( 'id' => 2, 'key_id' => 11, 'verify' => $ver, 'coach' => 'c1' ) ), $live[11] ) ) );

/* ---- 5. Ranh giới mã (R-HUB-14, R-HUB-15) ---- */
$src = '';
foreach ( glob( $dir . '/includes/*.php' ) as $f ) { $src .= file_get_contents( $f ); }
$remote_files = array();
foreach ( glob( $dir . '/includes/*.php' ) as $f ) { if ( preg_match( '/wp_remote_(request|get|post)\s*\(/', file_get_contents( $f ) ) ) { $remote_files[] = basename( $f ); } }
check( 'ranh giới ②: chỉ OHCL_Core_Client gọi mạng', array( 'class-ohcl-core-client.php' ) === $remote_files );
check( 'ranh giới ③: client không gọi Cell / Zalo / LLM', ! preg_match( '/zalo-hub|\/wp\/harness|BizCity_LLM_Client|openrouter|chat\/completions/i', $src ) );
check( 'không tạo bảng (lean, no new table)', ! preg_match( '/CREATE\s+TABLE|dbDelta/i', $src ) );
check( 'không rẽ nhánh theo mã ngành trong client', ! preg_match( "/===\s*'(vegan|baby|ocop|retail|tool|expo|sample)'|'(vegan|baby|ocop|retail|tool|expo)'\s*===/", $src ) );
$sample_src = file_get_contents( $dir . '/examples/wp-sample-hub/wp-sample-hub.php' );
check( 'ngành mẫu: không gọi lõi / mạng / DB', ! preg_match( '/OHCL_Core_Client|wp_remote_|\$wpdb|update_option/', $sample_src ) );

echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
