<?php
/**
 * Smoke test of the public community portal (P1–P4) with stubbed WordPress: only V4 members are public,
 * no private data leaks, escaping, registration creates a member, anti-spam, four-field errors.
 * Run: php tests/front-smoke.php — exit 0 = pass.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — chạy thử 4 trang công khai + form đăng ký với WordPress giả.

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

$pass = 0; $fail = 0;
function check( $name, $ok ) { global $pass, $fail; if ( $ok ) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; } }

// Lõi: khoá 11 đang chạy và mới dùng; khoá 12 dùng thử nhưng chưa dùng lần nào.
$now_s = gmdate( 'Y-m-d H:i:s' );
OHCL_Core_Client::$config    = array( 'base_url' => 'https://bizcity.test', 'hub' => 'sample', 'secret' => 'ohs_' . str_repeat( 'a', 32 ) );
OHCL_Core_Client::$transport = function () use ( $now_s ) {
	return array( 'status' => 200, 'body' => json_encode( array( 'items' => array(
		array( 'id' => 11, 'status' => 'active', 'plan_code' => 'ai_std', 'plan_until' => '2030-01-01 00:00:00', 'last_used_at' => $now_s, 'created_at' => $now_s, 'key_prefix' => 'biz-…1111', 'label' => '', 'site_url' => '', 'coach_ref' => '' ),
		array( 'id' => 12, 'status' => 'trial', 'plan_code' => 'trial', 'plan_until' => '2030-01-01 00:00:00', 'last_used_at' => null, 'created_at' => $now_s, 'key_prefix' => 'biz-…1212', 'label' => '', 'site_url' => '', 'coach_ref' => '' ),
	), 'page' => 1 ) ) );
};

$ind = OHCL_Industry::current();
function mk( $title, array $meta ) { $id = wp_insert_post( array( 'post_type' => 'bz_hub_member', 'post_title' => $title ) ); foreach ( $meta as $k => $v ) { update_post_meta( $id, $k, $v ); } return $id; }
$verify = array( 'by' => 1, 'at' => $now_s, 'until' => '2030-01-01 00:00:00', 'evidence' => array( 'gpkd' ), 'note' => '' );
$v4 = mk( 'Cửa hàng <script>x</script> An', array( '_bzm_status' => 'active', '_bzm_key_id' => 11, '_bzm_verify' => $verify, '_bzm_public_consent' => $now_s, '_bzm_lat' => '21.0285123', '_bzm_lng' => '105.8541987', '_bzm_segment' => 'cua-hang', '_bzm_province' => 'Hà Nội', '_bzm_phone' => '0912000111', '_bzm_email' => 'kin@an.vn', '_bzm_contact_zalo' => '0912 000 222', '_bzi_nhom_hang' => array( 'gia-dung' ), '_bzi_nam_thanh_lap' => 2015 ) );
$no_consent = mk( 'Cửa hàng Kín', array( '_bzm_status' => 'active', '_bzm_key_id' => 11, '_bzm_verify' => $verify, '_bzm_lat' => '10.1', '_bzm_lng' => '106.1' ) );
$unused_key = mk( 'Cửa hàng Mới', array( '_bzm_status' => 'active', '_bzm_key_id' => 12, '_bzm_verify' => $verify, '_bzm_public_consent' => $now_s, '_bzm_lat' => '10.2', '_bzm_lng' => '106.2' ) );
$paused     = mk( 'Cửa hàng Nghỉ', array( '_bzm_status' => 'paused', '_bzm_key_id' => 11, '_bzm_verify' => $verify, '_bzm_public_consent' => $now_s, '_bzm_lat' => '10.3', '_bzm_lng' => '106.3' ) );

/* bậc V1–V4 */
$all  = OHCL_Members::all();
$byid = array(); foreach ( $all as $m ) { $byid[ $m['id'] ] = $m; }
$k11  = array( 'status' => 'active', 'last_used_at' => $now_s );
$k12  = array( 'status' => 'trial', 'last_used_at' => null );
check( 'V4: đồng ý + toạ độ + khoá đang dùng + đã xác minh', 4 === OHCL_Members::level( $byid[ $v4 ], $k11, time() ) );
check( 'V3: thiếu đồng ý công khai', 3 === OHCL_Members::level( $byid[ $no_consent ], $k11, time() ) );
check( 'V1: khoá chưa dùng lần nào (chưa đạt V2)', 1 === OHCL_Members::level( $byid[ $unused_key ], $k12, time() ) );
check( 'V3: hội viên tạm dừng không lên công khai', 3 === OHCL_Members::level( $byid[ $paused ], $k11, time() ) );

$pub = OHCL_Members::public_list();
check( 'danh sách công khai: chỉ đúng 1 hồ sơ V4', 1 === count( $pub['items'] ) && $v4 === $pub['items'][0]['id'] && ! $pub['degraded'] );
$pj = json_encode( $pub );
check( 'bản công khai: toạ độ làm tròn 4 số, không có điện thoại / email', 21.0285 === $pub['items'][0]['lat'] && false === strpos( $pj, '0912000111' ) && false === strpos( $pj, 'kin@an.vn' ) );

/* P1 */
$h = OHCL_Front::html( 'home', array() );
check( 'P1: chữ persona, nút trở thành hội viên, chân trang Powered by Bizcity Open', false !== strpos( $h, 'Ngành của chúng ta' ) && false !== strpos( $h, 'Trở thành hội viên' ) && false !== strpos( $h, 'POWERED BY BIZCITY OPEN' ) );
check( 'P1: số hội viên đang hoạt động thật (3), sự kiện "—"', false !== strpos( $h, '<b>3</b><span>Hội viên</span>' ) && false !== strpos( $h, '<b class="na">—</b><span>Sự kiện</span>' ) );
check( 'P1: ghim bản đồ chỉ có hồ sơ V4; tên đã escape', false !== strpos( $h, 'data-pins' ) && false === strpos( $h, 'Cửa hàng Kín' ) && false === strpos( $h, 'Cửa hàng Mới' ) && false === strpos( $h, '<script>x</script>' ) );
check( 'P1: chưa có bài viết ⇒ "Chưa có tin", không bịa tin', false !== strpos( $h, 'Chưa có tin' ) );

/* P2 */
$_GET = array();
$m1 = OHCL_Front::html( 'map', array() );
check( 'P2: 1 kết quả, có nhãn trường ngành công khai (Gia dụng)', false !== strpos( $m1, '1 kết quả' ) && false !== strpos( $m1, 'Gia dụng' ) );
$_GET = array( 'q' => 'không-có-gì' );
$m2 = OHCL_Front::html( 'map', array() );
check( 'P2: lọc không khớp ⇒ trạng thái trống + Bỏ lọc', false !== strpos( $m2, 'Chưa có cơ sở nào khớp bộ lọc' ) && false !== strpos( $m2, 'Bỏ lọc' ) );
$_GET = array( 'seg' => 'nha-phan-phoi' );
check( 'P2: lọc theo loại hình', false !== strpos( OHCL_Front::html( 'map', array() ), 'Chưa có cơ sở nào khớp' ) );
$_GET = array();

/* P3 */
$pl = OHCL_Front::html( 'place', array( 'place' => OHCL_Front::find_place( $v4 ) ) );
check( 'P3: hồ sơ V4 hiện tên (escape), nút Zalo zalo.me/<số>, trường ngành', false !== strpos( $pl, 'Cửa hàng &lt;script&gt;x&lt;/script&gt; An' ) && false !== strpos( $pl, 'https://zalo.me/0912000222' ) && false !== strpos( $pl, 'Năm thành lập' ) );
check( 'P3: hồ sơ không phải V4 ⇒ không tìm thấy', null === OHCL_Front::find_place( $no_consent ) && false !== strpos( OHCL_Front::html( 'place', array( 'place' => null ) ), 'Không tìm thấy cơ sở' ) );

/* P4 */
$j = OHCL_Front::html( 'join', array() );
check( 'P4: quyền lợi persona + form có nonce, bẫy bot, ô đồng ý', false !== strpos( $j, 'Bộ phần mềm chuyển đổi số' ) && false !== strpos( $j, 'name="_ohcl_join"' ) && false !== strpos( $j, 'name="website_url_2"' ) && false !== strpos( $j, 'name="public_consent"' ) );
$_GET = array( 'err' => 'ohcl_join_phone_invalid' );
check( 'P4: lỗi hiện đủ thông điệp + gợi ý + mã', false !== strpos( OHCL_Front::html( 'join', array() ), 'Số Zalo chưa đúng.</b> Nhập số điện thoại 10 chữ số' ) );
$_GET = array( 'err' => '<script>' );
check( 'P4: mã lỗi lạ trên URL bị bỏ qua', false === strpos( OHCL_Front::html( 'join', array() ), 'role="alert"' ) );
$_GET = array();

function join_post( array $p ) { $_POST = $p + array( '_ohcl_join' => 'nonce-ok' ); $_SERVER['REMOTE_ADDR'] = isset( $p['_ip'] ) ? $p['_ip'] : '1.2.3.4'; try { OHCL_Front::handle_join(); } catch ( Redirected $r ) { return $r->getMessage(); } return ''; }
$before = count( $GLOBALS['posts'] );
check( 'đăng ký: bẫy bot có giá trị ⇒ từ chối, không tạo hồ sơ', false !== strpos( join_post( array( 'title' => 'Bot', 'contact_zalo' => '0912345678', 'website_url_2' => 'x' ) ), 'err=ohcl_join_rejected' ) && $before === count( $GLOBALS['posts'] ) );
check( 'đăng ký: nonce sai ⇒ hết hạn', false !== strpos( join_post( array( '_ohcl_join' => 'sai', 'title' => 'A', 'contact_zalo' => '0912345678' ) ), 'err=ohcl_join_expired' ) );
check( 'đăng ký: thiếu cả Zalo và email ⇒ lỗi', false !== strpos( join_post( array( 'title' => 'A' ) ), 'err=ohcl_join_contact_required' ) );
$r = join_post( array( 'title' => 'Quán Mới Mở', 'segment' => 'cua-hang', 'province' => 'Huế', 'contact_zalo' => '0912 345 678', 'public_consent' => '1' ) );
$new = max( array_keys( $GLOBALS['posts'] ) );
check( 'đăng ký hợp lệ ⇒ cảm ơn + hồ sơ mới (nguồn web, đã đồng ý, Zalo chuẩn hoá)', false !== strpos( $r, 'sent=1' ) && 'Quán Mới Mở' === $GLOBALS['posts'][ $new ]->post_title && 'web' === $GLOBALS['meta'][ $new ]['_bzm_source'] && '' !== $GLOBALS['meta'][ $new ]['_bzm_public_consent'] && '0912345678' === $GLOBALS['meta'][ $new ]['_bzm_contact_zalo'] );
check( 'đăng ký mới chưa lên công khai (V1)', 1 === count( OHCL_Members::public_list()['items'] ) );
for ( $i = 0; $i < 5; $i++ ) { join_post( array( 'title' => 'Spam ' . $i, 'contact_zalo' => '0912345678', '_ip' => '9.9.9.9' ) ); }
check( 'đăng ký: quá 5 lần / giờ / IP ⇒ chặn', false !== strpos( join_post( array( 'title' => 'Spam 6', 'contact_zalo' => '0912345678', '_ip' => '9.9.9.9' ) ), 'err=ohcl_join_rate_limited' ) );

/* lõi vắng */
OHCL_Members::flush_public(); OHCL_Overview::flush();
OHCL_Core_Client::$transport = function () { return new WP_Error( 'x', 'down' ); };
$pd = OHCL_Members::public_list();
check( 'lõi vắng ⇒ danh bạ báo lỗi, không lưu tạm kết quả lỗi, không hiện hồ sơ', $pd['degraded'] && array() === $pd['items'] && false === get_transient( OHCL_Members::PUBLIC_CACHE ) && false !== strpos( OHCL_Front::html( 'map', array() ), 'ohcl_directory_unavailable' ) );

echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
