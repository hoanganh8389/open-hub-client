<?php
/**
 * End-to-end: the real open-hub-core services + the real open-hub-client, wired by an in-memory
 * transport (no network, no WordPress). Proves the pairing flow and the first key.
 * Needs ../open-hub-core (or OHC_DIR). Run: php tests/e2e-core.php — 0 pass, 1 fail, 2 SKIP.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 06:17 PM Johnny Chu - Chu Hoàng Anh] OHCL-PAIR — chạy liên thông lõi + client: tạo Hub → mã ghép nối → kết nối → kiểm → cấp khoá.

define( 'ABSPATH', __DIR__ . '/' );
define( 'OHCL_TESTING', true );
define( 'DAY_IN_SECONDS', 86400 );
define( 'MINUTE_IN_SECONDS', 60 );

$dir  = dirname( __DIR__ );
$core = getenv( 'OHC_DIR' ) ? rtrim( getenv( 'OHC_DIR' ), '/\\' ) : dirname( $dir ) . '/open-hub-core';
if ( ! is_file( $core . '/includes/class-ohc-pairing.php' ) ) {
	echo "SKIP  không thấy open-hub-core có ghép nối ở $core (đặt OHC_DIR)\n";
	exit( 2 );
}

class WP_Error {
	private $c; private $m; private $d;
	public function __construct( $c = '', $m = '', $d = null ) { $this->c = $c; $this->m = $m; $this->d = $d; }
	public function get_error_code() { return $this->c; }
	public function get_error_message() { return $this->m; }
	public function get_error_data() { return $this->d; }
}
$GLOBALS['opts'] = array(); $GLOBALS['tr'] = array(); $GLOBALS['hooks'] = array();
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function add_filter( $h, $cb ) { $GLOBALS['hooks'][ $h ][] = $cb; return true; }
function add_action( $h, $cb ) { return add_filter( $h, $cb ); }
function apply_filters( $h, $v ) { foreach ( isset( $GLOBALS['hooks'][ $h ] ) ? $GLOBALS['hooks'][ $h ] : array() as $cb ) { $v = call_user_func( $cb, $v ); } return $v; }
function do_action( $h ) {}
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['opts'] ) ? $GLOBALS['opts'][ $k ] : $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['opts'][ $k ] = $v; return true; }
function get_transient( $k ) { return isset( $GLOBALS['tr'][ $k ] ) ? $GLOBALS['tr'][ $k ] : false; }
function set_transient( $k, $v, $t = 0 ) { $GLOBALS['tr'][ $k ] = $v; return true; }
function delete_transient( $k ) { unset( $GLOBALS['tr'][ $k ] ); return true; }

foreach ( array( 'interface-ohc-store', 'class-ohc-error', 'class-ohc-hubs', 'class-ohc-keys', 'class-ohc-plans', 'class-ohc-pairing' ) as $f ) {
	require $core . '/includes/' . $f . '.php';
}
foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview' ) as $f ) {
	require $dir . '/includes/' . $f . '.php';
}
require $dir . '/examples/wp-sample-hub/wp-sample-hub.php';

final class Mem_Store implements OHC_Store {
	public $hubs = array(); public $keys = array(); public $plans = array(); public $pairs = array(); public $tries = array();
	public function hub_by_slug( $s ) { foreach ( $this->hubs as $h ) { if ( $h['slug'] === $s ) { return $h; } } return null; }
	public function hub_by_id( $id ) { return isset( $this->hubs[ $id ] ) ? $this->hubs[ $id ] : null; }
	public function hub_insert( array $r ) { $id = count( $this->hubs ) + 1; $r['id'] = $id; $this->hubs[ $id ] = $r; return $id; }
	public function hub_update( $id, array $f ) { $this->hubs[ $id ] = array_merge( $this->hubs[ $id ], $f ); return true; }
	public function hubs_public() { $o = array(); foreach ( $this->hubs as $h ) { if ( 'active' === $h['status'] ) { $o[] = array( 'slug' => $h['slug'], 'name' => $h['name'], 'industry' => $h['industry'] ); } } return $o; }
	public function hubs_all() { return array_values( $this->hubs ); }
	public function key_insert( array $r ) { $id = count( $this->keys ) + 1; $r['id'] = $id; $this->keys[ $id ] = $r; return $id; }
	public function key_by_hash( $h ) { foreach ( $this->keys as $k ) { if ( $k['key_hash'] === $h ) { return $k; } } return null; }
	public function key_by_id( $id ) { return isset( $this->keys[ $id ] ) ? $this->keys[ $id ] : null; }
	public function key_update( $id, array $f ) { $this->keys[ $id ] = array_merge( $this->keys[ $id ], $f ); return true; }
	public function keys_by_hub( $hub, $l, $o ) { $r = array_values( array_filter( $this->keys, function ( $k ) use ( $hub ) { return (int) $k['hub_id'] === (int) $hub; } ) ); return array_slice( array_reverse( $r ), $o, $l ); }
	public function count_keys_created_since( $hub, $since ) { return 0; }
	public function count_active_keys( $hub ) { return 0; }
	public function plan_by_code( $c ) { return isset( $this->plans[ $c ] ) ? $this->plans[ $c ] : null; }
	public function plan_upsert( array $r ) { $this->plans[ $r['code'] ] = $r; return true; }
	public function pair_put( $h, array $d, $t ) { $this->pairs[ $h ] = $d; return true; }
	public function pair_take( $h ) { $v = isset( $this->pairs[ $h ] ) ? $this->pairs[ $h ] : null; unset( $this->pairs[ $h ] ); return $v; }
	public function attempt_hit( $b, $w ) { $this->tries[ $b ] = ( isset( $this->tries[ $b ] ) ? $this->tries[ $b ] : 0 ) + 1; return $this->tries[ $b ]; }
}

$store = new Mem_Store();
$now   = gmdate( 'Y-m-d H:i:s' );
OHC_Plans::seed( $store );

// Lõi giả lập REST open-hub-core/v1 trên chính các dịch vụ thật.
$wire = array();
OHCL_Core_Client::$transport = function ( $m, $u, $h, $b ) use ( $store, $now, &$wire ) {
	$wire[] = $m . ' ' . $u . ' ' . (string) $b;
	$path   = preg_replace( '#^https://bizcity\.test/wp-json/open-hub-core/v1#', '', $u );
	$body   = $b ? json_decode( $b, true ) : array();
	$out    = function ( $r, $ok = 200 ) {
		if ( is_wp_error( $r ) ) {
			$d = $r->get_error_data();
			return array( 'status' => $d['status'], 'body' => json_encode( array( 'code' => $r->get_error_code(), 'message' => $r->get_error_message(), 'data' => $d ) ) );
		}
		return array( 'status' => $ok, 'body' => json_encode( array( 'success' => true ) + $r ) );
	};
	if ( 'GET' === $m && '/hubs' === $path ) {
		return $out( array( 'hubs' => $store->hubs_public() ) );
	}
	if ( 'POST' === $m && '/hub/pair' === $path ) {
		return $out( OHC_Pairing::redeem( $store, isset( $body['code'] ) ? $body['code'] : '', '9.9.9.9' ) );
	}
	$bearer = isset( $h['Authorization'] ) ? substr( $h['Authorization'], 7 ) : '';
	$hub    = OHC_Hubs::authenticate( $store, isset( $h['X-OHC-Hub'] ) ? $h['X-OHC-Hub'] : '', $bearer );
	if ( is_wp_error( $hub ) ) {
		return $out( $hub );
	}
	if ( 'POST' === $m && '/hub/keys' === $path ) {
		return $out( OHC_Keys::issue( $store, $hub, $body, $now ), 201 );
	}
	if ( 'GET' === $m && 0 === strpos( $path, '/hub/keys' ) ) {
		return $out( OHC_Keys::list_for_hub( $store, $hub, 1, $now ) );
	}
	return array( 'status' => 404, 'body' => '{"code":"rest_no_route","message":"no route","data":{"status":404}}' );
};

$pass = 0; $fail = 0;
function check( $name, $ok ) { global $pass, $fail; if ( $ok ) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; } }

// 1. bizcity.vn › Sổ Hub: thêm Hub (bí mật tạo kèm bị bỏ) → mã ghép nối
$hub  = OHC_Hubs::create( $store, array( 'slug' => 'sample', 'name' => 'Sample Hub', 'industry' => 'Ngành mẫu' ), $now );
$pair = OHC_Pairing::issue( $store, 'sample', time() );
check( '1. lõi: thêm Hub + tạo mã ghép nối', ! is_wp_error( $pair ) && 9 === strlen( $pair['code'] ) );

// 2. site Hub: chưa kết nối ⇒ ping không đạt, không gọi mạng
$GLOBALS['opts'] = array();
check( '2. Hub: chưa kết nối ⇒ ping dừng ở bước cấu hình', false === OHCL_Core_Client::ping()['ok'] && array() === $wire );

// 3. site Hub: dán mã sai rồi mã đúng
check( '3a. Hub: mã sai ⇒ lỗi bốn trường từ lõi', 'ohc_pair_invalid' === OHCL_Core_Client::pair( 'https://bizcity.test', 'ABCD-EFGH' )->get_error_code() );
$res = OHCL_Core_Client::pair( 'https://bizcity.test/', strtolower( $pair['code'] ) );
check( '3b. Hub: mã đúng ⇒ đã kết nối Sample Hub, mã khớp plugin ngành', ! is_wp_error( $res ) && 'sample' === $res['hub']['slug'] && false === $res['mismatch'] );
$st = OHCL_Core_Client::state();
check( '3c. Hub: lưu địa chỉ + mã Hub + bí mật (không phải bí mật lúc tạo Hub)', 'https://bizcity.test' === $st['base_url'] && 'sample' === $st['hub'] && $st['has_secret'] && $GLOBALS['opts']['ohcl_core_secret'] !== $hub['secret'] );
check( '3d. bí mật lúc tạo Hub đã hết hiệu lực', is_wp_error( OHC_Hubs::authenticate( $store, 'sample', $hub['secret'] ) ) );
check( '3e. mã đã dùng không dùng lại được', 'ohc_pair_invalid' === OHCL_Core_Client::pair( 'https://bizcity.test', $pair['code'] )->get_error_code() );

// 4. tự kiểm 4 bước
$ping = OHCL_Core_Client::ping();
check( '4. Hub: kiểm 4 bước đều PASS', $ping['ok'] && 4 === count( $ping['steps'] ) );

// 5. cấp khoá đầu tiên + danh sách + tổng quan
$k = OHCL_Core_Client::issue_key( 'Cửa hàng đầu tiên', 'https://a.vn', 'coach-a' );
check( '5a. cấp khoá thật: biz-…, gói dùng thử', ! is_wp_error( $k ) && (bool) preg_match( '/^biz-[a-f0-9]{32}$/', $k['key'] ) && 'trial' === $k['plan_code'] );
$all = OHCL_Overview::all_keys();
check( '5b. danh sách khoá của Hub có 1 khoá, không có khoá thô', 1 === count( $all['items'] ) && false === strpos( json_encode( $all ), $k['key'] ) );
$s = OHCL_Overview::from_keys( $all['items'], $now );
check( '5c. tổng quan: 1 khoá đã nhận, còn dùng được', 1 === $s['issued'] && 1 === $s['live'] );
check( '5d. khoá thuộc đúng Hub ở lõi, Coach ghi kèm', (int) $store->keys[1]['hub_id'] === (int) $hub['id'] && 'coach-a' === $store->keys[1]['coach_ref'] );

// 6. không gói nào trên đường dây chứa bí mật Hub, trừ đúng câu trả lời ghép nối
$secret = $GLOBALS['opts']['ohcl_core_secret'];
$leaks  = 0;
foreach ( $wire as $w ) { $leaks += false !== strpos( $w, $secret ) ? 1 : 0; }
check( '6. bí mật không nằm trong URL hay thân yêu cầu nào', 0 === $leaks );

echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
