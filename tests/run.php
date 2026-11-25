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
foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client' ) as $f ) {
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
	if ( 'POST' === $m ) { return array( 'status' => 201, 'body' => '{"success":true,"key_id":3,"key":"biz-' . str_repeat( 'c', 32 ) . '","key_prefix":"biz-…cccc"}' ); }
	return array( 'status' => 200, 'body' => '{"success":true,"items":[],"page":1}' );
};
$k = OHCL_Core_Client::issue_key( 'Cửa hàng A', 'https://a.vn', 'coach-a' );
$last = end( $calls );
check( 'core: cấp khoá gửi X-OHC-Hub + Bearer, thân JSON', 3 === $k['key_id'] && 'sample' === $last['h']['X-OHC-Hub'] && 'Bearer ' . $secret === $last['h']['Authorization'] && 'https://bizcity.vn/wp-json/open-hub-core/v1/hub/keys' === $last['u'] && 'coach-a' === json_decode( $last['b'], true )['coach_ref'] );
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
