<?php
/**
 * Regression test of the veganhub.net fixes (2026-11-26): stale industry cache without persona, theme
 * wp_footer markup on Hub pages, asset version that survives ?ver= stripping.
 * Run: php tests/fix-smoke.php — exit 0 = pass.
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 12:09 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX1 — chạy lại đúng hai lỗi thấy trên veganhub.net.

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
class Redirected extends RuntimeException {}
$GLOBALS['opts'] = array(); $GLOBALS['tr'] = array(); $GLOBALS['hooks'] = array(); $GLOBALS['posts'] = array(); $GLOBALS['meta'] = array(); $GLOBALS['wp_posts'] = array();
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
function admin_url( $p = '' ) { return 'https://hub.test/wp-admin/' . $p; }
function home_url( $p = '/' ) { return 'https://hub.test' . $p; }
function add_query_arg( array $a, $u ) { return $u . ( false === strpos( $u, '?' ) ? '?' : '&' ) . http_build_query( $a ); }
function wp_nonce_field( $a, $n = '_wpnonce' ) { echo '<input type="hidden" name="' . $n . '" value="nonce-ok">'; }
function wp_verify_nonce( $v, $a ) { return 'nonce-ok' === $v; }
function checked( $a, $b, $e = true ) { $o = (string) $a === (string) $b ? ' checked' : ''; if ( $e ) { echo $o; } return $o; }
function selected( $a, $b, $e = true ) { return (string) $a === (string) $b ? ' selected' : ''; }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); }
function sanitize_title( $s ) { return trim( strtolower( preg_replace( '/[^a-z0-9]+/i', '-', (string) $s ) ), '-' ); }
function wp_unslash( $s ) { return $s; }
function number_format_i18n( $n ) { return number_format( (float) $n, 0, ',', '.' ); }
function language_attributes() { echo 'lang="vi"'; }
function bloginfo( $k ) { echo 'UTF-8'; }
function get_bloginfo( $k ) { return 'Site'; }
function wp_head() { echo '<!--wp_head-->'; }
function wp_footer() { echo '<!--wp_footer-->'; }
function wp_body_open() {}
function body_class( $c ) { echo 'class="' . $c . '"'; }
function is_user_logged_in() { return false; }
function wp_login_url( $r ) { return 'https://hub.test/wp-login.php'; }
function get_posts( array $a ) { if ( isset( $a['post_type'] ) && 'post' === $a['post_type'] ) { return $GLOBALS['wp_posts']; } return array_reverse( array_values( array_filter( $GLOBALS['posts'], function ( $p ) use ( $a ) { return $p->post_type === $a['post_type']; } ) ) ); }
function get_post( $id ) { return isset( $GLOBALS['posts'][ $id ] ) ? $GLOBALS['posts'][ $id ] : null; }
function wp_insert_post( array $a ) { $id = count( $GLOBALS['posts'] ) + 201; $GLOBALS['posts'][ $id ] = (object) array( 'ID' => $id, 'post_type' => $a['post_type'], 'post_title' => $a['post_title'] ); return $id; }
function wp_update_post( array $a ) { $GLOBALS['posts'][ $a['ID'] ]->post_title = $a['post_title']; return $a['ID']; }
function get_post_meta( $id, $k, $s ) { return isset( $GLOBALS['meta'][ $id ][ $k ] ) ? $GLOBALS['meta'][ $id ][ $k ] : ''; }
function update_post_meta( $id, $k, $v ) { $GLOBALS['meta'][ $id ][ $k ] = $v; return true; }
function get_current_user_id() { return 1; }
function get_permalink( $p ) { return 'https://hub.test/tin/' . ( is_object( $p ) ? $p->ID : (int) $p ) . '/'; }
function has_post_thumbnail( $p ) { return false; }
function get_the_title( $p ) { return $p->post_title; }
function get_the_date( $f, $p ) { return '25/11/2026'; }
function wp_safe_redirect( $u ) { throw new Redirected( $u ); }

$dir = dirname( __DIR__ );
define( 'OHCL_DIR', $dir . '/' );
foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview', 'class-ohcl-members', 'class-ohcl-front' ) as $f ) {
	require $dir . '/includes/' . $f . '.php';
}
require $dir . '/examples/wp-sample-hub/wp-sample-hub.php';

// Fake theme folder with one wp_footer callback; plugin callback stays outside it.
$GLOBALS['theme_dir'] = sys_get_temp_dir() . '/ohcl-fake-theme';
@mkdir( $GLOBALS['theme_dir'] );
file_put_contents( $GLOBALS['theme_dir'] . '/functions.php', '<?php function ohcl_fake_theme_mobile_menu() { echo "<ul>MENU THEME</ul>"; }' );
require $GLOBALS['theme_dir'] . '/functions.php';
function ohcl_fake_plugin_chat() { echo 'CHAT PLUGIN'; }
function wp_normalize_path( $p ) { return str_replace( '\\', '/', $p ); }
function trailingslashit( $p ) { return rtrim( $p, '/\\' ) . '/'; }
function get_template_directory() { return $GLOBALS['theme_dir']; }
function get_stylesheet_directory() { return $GLOBALS['theme_dir']; }
class Fake_Hook { public $callbacks = array(); }
$GLOBALS['wp_filter'] = array( 'wp_footer' => new Fake_Hook(), 'wp_body_open' => new Fake_Hook() );
$GLOBALS['wp_filter']['wp_footer']->callbacks = array( 10 => array( array( 'function' => 'ohcl_fake_theme_mobile_menu' ), array( 'function' => 'ohcl_fake_plugin_chat' ) ), 20 => array( array( 'function' => function () {} ) ) );
function remove_action( $hook, $fn, $prio ) { foreach ( $GLOBALS['wp_filter'][ $hook ]->callbacks[ $prio ] as $i => $cb ) { if ( $cb['function'] === $fn ) { unset( $GLOBALS['wp_filter'][ $hook ]->callbacks[ $prio ][ $i ] ); } } return true; }

$pass = 0; $fail = 0;
function check( $name, $ok ) { global $pass, $fail; if ( $ok ) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; } }

/* bộ đệm cũ thiếu persona (veganhub.net 2026-11-26) */
$old = OHCL_Industry::defaults();
unset( $old['persona'], $old['cert_prefix'] );
update_option( OHCL_Industry::OPTION, array( 'hash' => md5( (string) wp_json_encode( apply_filters( OHCL_Industry::FILTER, array() ) ) ), 'report' => array( 'ok' => true, 'config' => $old, 'errors' => array(), 'warnings' => array(), 'ignored' => array() ) ) );
OHCL_Industry::reset();
$cfg = OHCL_Industry::current();
check( 'bản đệm của client cũ (khoá không có phiên bản) không còn được dùng lại ⇒ dữ liệu ngành mẫu', 'Sample Hub' === $cfg['name'] );
check( 'cấu hình luôn có persona đủ chữ', '' !== $cfg['persona']['title'] && isset( $cfg['cert_prefix'] ) );
$filled = OHCL_Industry::fill( array( 'name' => 'X', 'persona' => array( 'title' => 'T' ) ) );
check( 'fill(): giữ giá trị có sẵn, bù khoá thiếu từ mặc định', 'X' === $filled['name'] && 'T' === $filled['persona']['title'] && '' !== $filled['persona']['subtitle'] && is_array( $filled['segments'] ) );
$h = OHCL_Front::html( 'home', array() );
check( 'trang chủ có tiêu đề lớn, không còn <h1> trống', false === strpos( $h, '<h1> <span></span></h1>' ) && false !== strpos( $h, '<!-- open-hub-client' ) && false !== strpos( $h, 'ngành: sample' ) );

/* hook của theme */
$n = OHCL_Front::strip_theme_hooks();
$left = array();
foreach ( $GLOBALS['wp_filter']['wp_footer']->callbacks as $cbs ) { foreach ( $cbs as $cb ) { $left[] = is_string( $cb['function'] ) ? $cb['function'] : 'closure'; } }
check( 'gỡ hook wp_footer của theme (menu điện thoại…), giữ hook của plugin', 1 === $n && ! in_array( 'ohcl_fake_theme_mobile_menu', $left, true ) && in_array( 'ohcl_fake_plugin_chat', $left, true ) && in_array( 'closure', $left, true ) );
check( 'tài sản mang ?ohcl=<phiên bản> (qua được plugin xoá ?ver= và Cloudflare)', false !== strpos( OHCL_Front::asset( 'https://hub.test/a/front.css' ), '?ohcl=' ) );

/* màu nhấn (OHCL-FIX2) */
// [2026-11-26 12:51 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX2 — chủ Hub đặt màu > màu plugin ngành > mặc định.
$ind_accent = OHCL_Industry::report()['config']['brand']['accent'];
update_option( OHCL_Industry::ACCENT_OPTION, '#1F7A3A' );
check( 'chủ Hub đặt màu xanh ⇒ cổng dùng màu đó (không phân biệt hoa / thường)', '#1f7a3a' === OHCL_Industry::current()['brand']['accent'] );
update_option( OHCL_Industry::ACCENT_OPTION, 'red;}body{display:none' );
check( 'màu sai dạng bị bỏ ⇒ về màu của plugin ngành (không chèn được CSS)', $ind_accent === OHCL_Industry::current()['brand']['accent'] );
update_option( OHCL_Industry::ACCENT_OPTION, '' );
$e = OHCL_Industry::validate( array() );
check( 'không plugin ngành nào cấp dữ liệu ⇒ lỗi nói rõ cần bật plugin ngành', ! $e['ok'] && false !== strpos( $e['errors'][0], 'bật plugin ngành' ) );

/* danh bạ khi chưa ai có khoá (OHCL-FIX3, veganhub.net "Không tải được danh bạ") */
// [2026-11-26 01:11 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX3
$GLOBALS['core_calls'] = 0;
OHCL_Core_Client::$config    = array( 'base_url' => 'https://bizcity.test', 'hub' => 'sample', 'secret' => 'ohs_' . str_repeat( 'a', 32 ) );
OHCL_Core_Client::$transport = function () { $GLOBALS['core_calls']++; return new WP_Error( 'x', 'down' ); };
OHCL_Members::flush_public(); OHCL_Overview::flush();
$mid = wp_insert_post( array( 'post_type' => 'bz_hub_member', 'post_title' => 'Chưa có khoá' ) );
update_post_meta( $mid, '_bzm_status', 'active' );
$pl = OHCL_Members::public_list();
check( 'chưa hội viên nào có khoá ⇒ không hỏi lõi, danh bạ không báo lỗi', ! $pl['degraded'] && 0 === $GLOBALS['core_calls'] );
update_post_meta( $mid, '_bzm_key_id', 9 );
OHCL_Members::flush_public();
$pl = OHCL_Members::public_list();
check( 'có hội viên có khoá mà lõi lỗi ⇒ báo lỗi, ghi mã lỗi cho quản trị viên', $pl['degraded'] && $GLOBALS['core_calls'] > 0 && 0 === strpos( (string) get_transient( 'ohcl_dir_error' ), 'ohcl_' ) );
$h = OHCL_Front::html( 'home', array() );
check( 'trang chủ: bản đồ lớn ngay sau lối tắt, luôn có khung bản đồ (data-ohcl-map) + lời giải thích khi trống', false !== strpos( $h, 'class="in mapband"' ) && strpos( $h, 'class="in mapband"' ) < strpos( $h, 'class="in row"' ) && false !== strpos( $h, 'class="map big" data-ohcl-map' ) && false !== strpos( $h, 'Bản đồ đang chờ cơ sở đầu tiên' ) );
check( 'không còn lớp "in sec" làm lệch khối danh mục', false === strpos( $h, 'class="in sec"' ) );

echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
