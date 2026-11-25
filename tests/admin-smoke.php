<?php
/**
 * Smoke test of the three admin screens with stubbed WordPress: no fatal, right content, escaping,
 * the plain key shown once, no secret on the page. Run: php tests/admin-smoke.php — exit 0 = pass.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 06:05 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1B — chạy thử ba màn quản trị với WordPress giả.

define( 'ABSPATH', __DIR__ . '/' );
define( 'OHCL_TESTING', true );
define( 'DAY_IN_SECONDS', 86400 );
define( 'MINUTE_IN_SECONDS', 60 );

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
function esc_html__( $s ) { return $s; }
function admin_url( $p = '' ) { return 'https://hub.test/wp-admin/' . $p; }
function add_query_arg( array $a, $u ) { return $u . '?' . http_build_query( $a ); }
function wp_nonce_field( $a ) { echo '<input type="hidden" name="_wpnonce" value="n">'; }
function checked( $a, $b, $e ) { return $a === $b ? ' checked' : ''; }
function disabled( $a, $b, $e ) { return $a === $b ? ' disabled' : ''; }
function current_user_can( $c ) { return true; }
function get_current_user_id() { return 1; }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function wp_unslash( $s ) { return $s; }
function wp_die( $m ) { throw new RuntimeException( 'wp_die: ' . $m ); }

$dir = dirname( __DIR__ );
foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview', 'class-ohcl-admin' ) as $f ) {
	require $dir . '/includes/' . $f . '.php';
}
require $dir . '/examples/wp-sample-hub/wp-sample-hub.php';

$pass = 0; $fail = 0;
function check( $name, $ok ) { global $pass, $fail; if ( $ok ) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; } }
function page( $fn ) { ob_start(); call_user_func( array( 'OHCL_Admin', $fn ) ); return ob_get_clean(); }

$secret = 'ohs_' . str_repeat( 'e', 32 );
OHCL_Core_Client::$config    = array( 'base_url' => 'https://bizcity.test', 'hub' => 'sample', 'secret' => $secret );
OHCL_Core_Client::$transport = function ( $m, $u ) {
	if ( false !== strpos( $u, '/hubs' ) ) { return array( 'status' => 200, 'body' => '{"hubs":[{"slug":"sample"}]}' ); }
	return array( 'status' => 200, 'body' => json_encode( array( 'items' => array(
		array( 'id' => 1, 'key_prefix' => 'biz-…a1f3', 'label' => '<b>Cửa hàng A</b>', 'site_url' => 'https://a.vn', 'plan_code' => 'trial', 'plan_until' => gmdate( 'Y-m-d H:i:s', time() + 3 * 86400 ), 'status' => 'trial', 'coach_ref' => 'coach-a', 'last_used_at' => null, 'created_at' => gmdate( 'Y-m-d H:i:s', time() - 10 * 86400 ) ),
	), 'page' => 1 ) ) );
};

$o = page( 'page_overview' );
check( 'Tổng quan: hiện 5 câu hỏi, câu 1 là "—" (chưa có nguồn)', 5 === substr_count( $o, 'class="ohcl-q"' ) && false !== strpos( $o, 'Đ1c' ) );
check( 'Tổng quan: phễu từ khoá thật + việc "sắp hết hạn", "chưa dùng"', false !== strpos( $o, 'Đã nhận khoá' ) && false !== strpos( $o, 'sắp hết hạn trong 7 ngày' ) && false !== strpos( $o, 'chưa dùng trợ lý' ) );

$GLOBALS['tr'] = array();
$k = page( 'page_keys' );
check( 'Khoá: bảng có cơ sở, tên đã escape (không HTML thô)', false !== strpos( $k, '&lt;b&gt;Cửa hàng A' ) && false === strpos( $k, '<b>Cửa hàng A' ) );
check( 'Khoá: thuật ngữ ngành ("cửa hàng") trên tiêu đề', false !== strpos( $k, 'Khoá trợ lý AI của cửa hàng' ) );
$_GET['revoke'] = 1; $_GET['p'] = 'biz-…a1f3';
$kc = page( 'page_keys' );
check( 'Khoá: thu hồi qua bước xác nhận (form POST + nonce), không window.confirm', false !== strpos( $kc, 'Thu hồi khoá biz-…a1f3?' ) && false !== strpos( $kc, 'value="ohcl_revoke"' ) && false === strpos( $kc, 'confirm(' ) );
unset( $_GET['revoke'], $_GET['p'] );

set_transient( 'ohcl_flash_1', array( 'key' => 'biz-' . str_repeat( 'd', 32 ), 'label' => 'Cửa hàng B', 'plan_code' => 'trial', 'plan_until' => '2026-12-25 00:00:00' ) );
$k1 = page( 'page_keys' );
$k2 = page( 'page_keys' );
check( 'Khoá thô hiện đúng một lần', false !== strpos( $k1, 'biz-' . str_repeat( 'd', 32 ) ) && false === strpos( $k2, 'biz-' . str_repeat( 'd', 32 ) ) );

$s = page( 'page_settings' );
check( 'Cài đặt: 14 module, 5 ô bắt buộc bị khoá, module 09 tắt theo ngành', 14 === substr_count( $s, 'name="mod[' ) && 5 === substr_count( $s, ' disabled>' ) && false !== strpos( $s, 'mặc định ngành' ) );
check( 'Cài đặt: báo cáo ngành ĐẠT', false !== strpos( $s, 'ĐẠT</span> Sample Hub' ) );
check( 'Không trang nào in bí mật Hub', false === strpos( $o . $k . $kc . $k1 . $s, $secret ) );

OHCL_Core_Client::$transport = function () { return new WP_Error( 'x', 'down' ); };
OHCL_Overview::flush();
$od = page( 'page_overview' );
check( 'Lõi vắng: hiện lỗi bốn trường, việc cần làm báo kết nối, không có số bịa', false !== strpos( $od, 'ohcl_core_unreachable' ) && false !== strpos( $od, 'Kết nối lõi Bizcity chưa đạt' ) && false === strpos( $od, 'Đã nhận khoá' ) );

echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
