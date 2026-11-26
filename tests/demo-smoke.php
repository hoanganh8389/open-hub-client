<?php
/**
 * Demo data (OHCL_Demo, CM-Q1/Q3) + member shop products (OHCL_Shop, CM-Q2).
 * Run: php tests/demo-smoke.php — exit 0 = pass.
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-DEMO / OHCL-SHOP — test dữ liệu mẫu (công khai có nhãn, tự ẩn ở ngưỡng, không vào số liệu, bài đăng / nháp / xoá hết) và sản phẩm Store API.

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
$GLOBALS['opts'] = array(); $GLOBALS['tr'] = array(); $GLOBALS['hooks'] = array(); $GLOBALS['posts'] = array(); $GLOBALS['meta'] = array(); $GLOBALS['terms'] = array(); $GLOBALS['http'] = array();
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
function wp_create_nonce( $a ) { return 'nonce-ok'; }
function wp_verify_nonce( $v, $a ) { return 'nonce-ok' === $v; }
function check_admin_referer( $a ) { return true; }
function checked( $a, $b = true, $e = true ) { $o = (string) $a === (string) $b ? ' checked' : ''; if ( $e ) { echo $o; } return $o; }
function selected( $a, $b, $e = true ) { return (string) $a === (string) $b ? ' selected' : ''; }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); }
function sanitize_title( $s ) { return trim( strtolower( preg_replace( '/[^a-z0-9]+/i', '-', (string) $s ) ), '-' ); }
function wp_unslash( $s ) { return $s; }
function number_format_i18n( $n ) { return number_format( (float) $n, 0, ',', '.' ); }
function language_attributes() { echo 'lang="vi"'; }
function bloginfo( $k ) { echo 'UTF-8'; }
function get_bloginfo( $k ) { return 'Site'; }
function wp_head() {}
function wp_footer() {}
function wp_body_open() {}
function body_class( $c ) { echo 'class="' . $c . '"'; }
function is_user_logged_in() { return false; }
function current_user_can( $c ) { return ! empty( $GLOBALS['admin'] ); }
function esc_html__( $s, $d = "" ) { return esc_html( $s ); }
function wp_die( $m ) { throw new RuntimeException( 'wp_die: ' . $m ); }
function wp_login_url( $r ) { return 'https://hub.test/wp-login.php'; }
function get_posts( array $a ) {
	$r = array_values( array_filter( $GLOBALS['posts'], function ( $p ) use ( $a ) { return $p->post_type === $a['post_type'] && ( ! isset( $a['post_status'] ) || in_array( $p->post_status, (array) $a['post_status'], true ) ); } ) );
	return array_reverse( $r );
}
function get_post( $id ) { return isset( $GLOBALS['posts'][ $id ] ) ? $GLOBALS['posts'][ $id ] : null; }
function wp_insert_post( array $a ) {
	$id = count( $GLOBALS['posts'] ) + 301;
	while ( isset( $GLOBALS['posts'][ $id ] ) ) { $id++; }
	$GLOBALS['posts'][ $id ] = (object) array( 'ID' => $id, 'post_type' => $a['post_type'], 'post_title' => $a['post_title'], 'post_status' => isset( $a['post_status'] ) ? $a['post_status'] : 'publish', 'post_content' => isset( $a['post_content'] ) ? $a['post_content'] : '', 'post_category' => isset( $a['post_category'] ) ? $a['post_category'] : array() );
	foreach ( isset( $a['meta_input'] ) ? $a['meta_input'] : array() as $k => $v ) { $GLOBALS['meta'][ $id ][ $k ] = $v; }
	return $id;
}
function wp_update_post( array $a ) { foreach ( $a as $k => $v ) { if ( 'ID' !== $k ) { $GLOBALS['posts'][ $a['ID'] ]->$k = $v; } } return $a['ID']; }
function wp_delete_post( $id, $force ) { if ( ! isset( $GLOBALS['posts'][ $id ] ) ) { return false; } $p = $GLOBALS['posts'][ $id ]; unset( $GLOBALS['posts'][ $id ], $GLOBALS['meta'][ $id ] ); return $p; }
function get_post_meta( $id, $k, $s ) { return isset( $GLOBALS['meta'][ $id ][ $k ] ) ? $GLOBALS['meta'][ $id ][ $k ] : ''; }
function update_post_meta( $id, $k, $v ) { $GLOBALS['meta'][ $id ][ $k ] = $v; return true; }
function term_exists( $slug, $tax ) { return isset( $GLOBALS['terms'][ $slug ] ) ? array( 'term_id' => $GLOBALS['terms'][ $slug ] ) : null; }
function wp_insert_term( $name, $tax, $a ) { $GLOBALS['terms'][ $a['slug'] ] = count( $GLOBALS['terms'] ) + 11; return array( 'term_id' => $GLOBALS['terms'][ $a['slug'] ] ); }
function wp_kses_post( $s ) { return preg_replace( '#<script.*?</script>#is', '', $s ); }
function get_current_user_id() { return 1; }
function get_permalink( $p ) { return 'https://hub.test/tin/' . ( is_object( $p ) ? $p->ID : (int) $p ) . '/'; }
function has_post_thumbnail( $p ) { return false; }
function get_the_title( $p ) { return $p->post_title; }
function get_the_date( $f, $p ) { return '26/11/2026'; }
function wp_safe_redirect( $u ) { throw new Redirected( $u ); }
function wp_safe_remote_get( $url, $a ) { $GLOBALS['http'][] = $url; return isset( $GLOBALS['http_reply'] ) ? $GLOBALS['http_reply'] : new WP_Error( 'http_request_failed', 'x' ); }
function wp_remote_retrieve_response_code( $r ) { return $r['code']; }
function wp_remote_retrieve_body( $r ) { return $r['body']; }

$dir = dirname( __DIR__ );
define( 'OHCL_DIR', $dir . '/' );
foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview', 'class-ohcl-members', 'class-ohcl-demo', 'class-ohcl-shop', 'class-ohcl-certs', 'class-ohcl-front', 'class-ohcl-admin', 'class-ohcl-admin-demo' ) as $f ) {
	require $dir . '/includes/' . $f . '.php';
}
require $dir . '/examples/wp-sample-hub/wp-sample-hub.php';

$pass = 0; $fail = 0;
function check( $name, $ok ) { global $pass, $fail; if ( $ok ) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; } }
function handle( array $post ) { $_POST = $post; try { OHCL_Admin_Demo::handle(); } catch ( Redirected $r ) { return $r->getMessage(); } return ''; }
function flash() { $f = get_transient( 'ohcl_flash_1' ); delete_transient( 'ohcl_flash_1' ); return is_array( $f ) ? $f : array(); }

$cfg = OHCL_Industry::current();

/* ---------- gói dữ liệu mẫu (thuần) ---------- */
$raw = array(
	'threshold'  => 2,
	'categories' => array( 'kien-thuc' => 'Kiến thức', 'bad slug!' => 'X' ),
	'members'    => array(
		array( 'ref' => 'a-1', 'title' => 'Cửa hàng An', 'segment' => 'cua-hang', 'province' => 'Hà Nội', 'address' => 'Hoàn Kiếm', 'lat' => 21.0285, 'lng' => 105.8542, 'fields' => array( 'nhom_hang' => array( 'gia-dung', 'la' ), 'nam_thanh_lap' => 2010, 'la' => 'x' ), 'phone' => '0912345678' ),
		array( 'ref' => 'a-2', 'title' => 'Nhà phân phối Bình (mẫu)', 'segment' => 'nha-phan-phoi', 'province' => 'TP.HCM', 'lat' => 10.7769, 'lng' => 106.7009 ),
		array( 'ref' => 'a-1', 'title' => 'Trùng ref', 'segment' => 'cua-hang', 'lat' => 1, 'lng' => 1 ),
		array( 'ref' => 'a-3', 'title' => 'Sai loại hình', 'segment' => 'khong-co', 'lat' => 1, 'lng' => 1 ),
		array( 'ref' => 'a-4', 'title' => 'Thiếu toạ độ', 'segment' => 'cua-hang' ),
	),
	'posts'      => array(
		array( 'ref' => 'p-1', 'title' => 'Bài một', 'category' => 'kien-thuc', 'excerpt' => 'Mô tả', 'content' => '<p>Xem <a href="{place:a-1}">An</a> trên <a href="{map}">bản đồ</a>.</p><script>alert(1)</script>' ),
		array( 'ref' => 'p-2', 'title' => 'Sai chuyên mục', 'category' => 'khong-co', 'content' => '<p>x</p>' ),
	),
);
$pk = OHCL_Demo::clean_pack( $raw, $cfg );
check( 'gói mẫu: giữ 2 cơ sở hợp lệ, bỏ trùng ref / sai loại hình / thiếu toạ độ', 2 === count( $pk['members'] ) && 3 === count( array_filter( $pk['skipped'], function ( $s ) { return 0 === strpos( $s, 'members' ); } ) ) );
check( 'tên cơ sở mẫu luôn kết thúc "(mẫu)", không nhân đôi', 'Cửa hàng An (mẫu)' === $pk['members'][0]['title'] && 'Nhà phân phối Bình (mẫu)' === $pk['members'][1]['title'] );
check( 'trường ngành: chỉ trường có trong hợp đồng, lựa chọn hợp lệ, không trường số (không dữ liệu giả trông như thật), không điện thoại', array( 'nhom_hang' => array( 'gia-dung' ) ) === $pk['members'][0]['fields'] && ! isset( $pk['members'][0]['phone'] ) );
check( 'bài mẫu: bỏ bài sai chuyên mục; slug chuyên mục được làm sạch', 1 === count( $pk['posts'] ) && isset( $pk['categories']['kien-thuc'] ) && isset( $pk['categories']['badslug'] ) );
check( 'ngưỡng tự ẩn lấy từ gói ngành (mặc định 15)', 2 === $pk['threshold'] && 15 === OHCL_Demo::clean_pack( array(), $cfg )['threshold'] );
check( 'visible(): hiện khi tự động + đã nhập + ít hơn ngưỡng cơ sở thật', OHCL_Demo::visible( 'auto', 5, 14, 15 ) && ! OHCL_Demo::visible( 'auto', 5, 15, 15 ) && ! OHCL_Demo::visible( 'off', 5, 0, 15 ) && ! OHCL_Demo::visible( 'auto', 0, 0, 15 ) );
check( 'lý do hiện / ẩn nói bằng câu cho chủ Hub', false !== strpos( OHCL_Demo::reason( 'auto', 5, 15, 15 ), 'Đã tự ẩn' ) && false !== strpos( OHCL_Demo::reason( 'off', 5, 0, 15 ), 'tắt' ) );
check( 'fill_links(): thay {map} và {place:ref}; ref lạ ⇒ về bản đồ', '<a href="/m">x</a> <a href="/p/1">y</a> <a href="/m">z</a>' === OHCL_Demo::fill_links( '<a href="{map}">x</a> <a href="{place:a-1}">y</a> <a href="{place:zz}">z</a>', array( '{map}' => '/m' ), array( 'a-1' => '/p/1' ) ) );

/* ---------- nhập, hiển thị, số liệu ---------- */
add_filter( OHCL_Demo::FILTER, function () use ( $raw ) { return $raw; } );
OHCL_Demo::reset();
$real = wp_insert_post( array( 'post_type' => OHCL_Members::CPT, 'post_title' => 'Cơ sở thật' ) );
update_post_meta( $real, '_bzm_status', 'active' );
$r1 = OHCL_Demo::seed_members();
$r2 = OHCL_Demo::seed_members();
check( 'nhập cơ sở mẫu: thêm 2, nhập lại không tạo trùng', 2 === $r1['added'] && 0 === $r2['added'] && 2 === $r2['kept'] && 2 === count( OHCL_Demo::member_ids() ) );
$ids = OHCL_Demo::member_ids();
check( 'cơ sở mẫu: không khoá, không xác minh, có dấu ẩn _bzm_demo + ref', '1' === get_post_meta( $ids['a-1'], '_bzm_demo', true ) && '' === get_post_meta( $ids['a-1'], '_bzm_key_id', true ) && '' === get_post_meta( $ids['a-1'], '_bzm_verify', true ) && 'a-1' === get_post_meta( $ids['a-1'], OHCL_Demo::REF_META, true ) );
check( 'danh sách hội viên / số liệu không thấy cơ sở mẫu', 1 === count( OHCL_Members::all() ) && 3 === count( OHCL_Members::all( 1000, true ) ) && 1 === OHCL_Members::counts( OHCL_Members::all(), array(), time() )['total'] );
$pl = OHCL_Members::public_list();
check( 'danh bạ công khai: 0 cơ sở thật + 2 cơ sở mẫu có cờ demo', 0 === $pl['real'] && 2 === $pl['demo'] && 2 === count( $pl['items'] ) && $pl['items'][0]['demo'] );
$h = OHCL_Front::html( 'home', array() );
check( 'trang chủ: bản đồ có điểm mẫu (d:1), không còn khối chú thích dưới bản đồ (OHCL-FIX4); số "Đã xác minh, công khai" vẫn là 0', false !== strpos( $h, '&quot;d&quot;:1' ) && false === strpos( $h, 'class="demo-note"' ) && 1 === preg_match( '#<b>0</b><span>Đã xác minh, công khai#', $h ) && false === strpos( $h, 'Bản đồ đang chờ cơ sở đầu tiên' ) );
$_GET = array();
$mh = OHCL_Front::html( 'map', array() );
check( 'trang bản đồ: cơ sở mẫu mang nhãn "Mẫu", không bao giờ "Đã xác minh"', false !== strpos( $mh, 'class="badge demo"' ) && false === strpos( $mh, '<span class="badge mem">Đã xác minh</span>' ) );
$place = OHCL_Front::find_place( $ids['a-1'] );
$ph    = OHCL_Front::html( 'place', array( 'place' => $place ) );
check( 'hồ sơ cơ sở mẫu: mở được, có lời báo "cơ sở mẫu", không nút Nhắn Zalo', $place && false !== strpos( $ph, 'cơ sở mẫu' ) && false === strpos( $ph, 'zalo.me' ) && false !== strpos( $ph, 'Cơ sở mẫu không có số Zalo' ) );

/* tự ẩn khi đủ ngưỡng cơ sở thật (ngưỡng 2 trong gói thử) */
$GLOBALS['V4'] = true;
OHCL_Core_Client::$config    = array( 'base_url' => 'https://bizcity.test', 'hub' => 'sample', 'secret' => 'ohs_' . str_repeat( 'a', 32 ) );
$now_s = gmdate( 'Y-m-d H:i:s' );
OHCL_Core_Client::$transport = function () use ( $now_s ) { $it = array(); for ( $i = 1; $i <= 2; $i++ ) { $it[] = array( 'id' => $i, 'status' => 'active', 'plan_code' => 'p', 'plan_until' => '2030-01-01 00:00:00', 'last_used_at' => $now_s, 'created_at' => $now_s, 'key_prefix' => 'k', 'label' => '', 'site_url' => '', 'coach_ref' => '' ); } return array( 'status' => 200, 'body' => json_encode( array( 'items' => $it, 'page' => 1 ) ) ); };
$v = array( 'by' => 1, 'at' => $now_s, 'until' => '2030-01-01 00:00:00', 'evidence' => array( 'gpkd' ), 'note' => '' );
$mk = function ( $kid ) use ( $v, $now_s ) { $id = wp_insert_post( array( 'post_type' => OHCL_Members::CPT, 'post_title' => 'Thật ' . $kid ) ); foreach ( array( '_bzm_status' => 'active', '_bzm_verify' => $v, '_bzm_public_consent' => $now_s, '_bzm_lat' => '21', '_bzm_lng' => '105', '_bzm_key_id' => $kid ) as $k => $x ) { update_post_meta( $id, $k, $x ); } return $id; };
$mk( 1 );
OHCL_Members::flush_public(); OHCL_Overview::flush();
$pl = OHCL_Members::public_list();
check( '1 cơ sở thật < ngưỡng 2 ⇒ vẫn hiện mẫu, cơ sở thật đứng trước', 1 === $pl['real'] && 2 === $pl['demo'] && empty( $pl['items'][0]['demo'] ) );
$mk( 2 );
OHCL_Members::flush_public(); OHCL_Overview::flush();
$pl = OHCL_Members::public_list();
check( 'đủ ngưỡng cơ sở thật ⇒ cơ sở mẫu tự ẩn khỏi bản đồ, danh bạ', 2 === $pl['real'] && 0 === $pl['demo'] && 2 === count( $pl['items'] ) );
check( 'đã ẩn ⇒ hồ sơ cơ sở mẫu không còn mở công khai', null === OHCL_Front::find_place( $ids['a-1'] ) );

/* ---------- màn quản trị ---------- */
$GLOBALS['admin'] = true;
OHCL_Demo::set_mode( 'off' );
check( 'chế độ Tắt ⇒ status nói "đã tắt"', ! OHCL_Demo::status( 0 )['visible'] && false !== strpos( OHCL_Demo::status( 0 )['reason'], 'tắt' ) );
handle( array( 'do' => 'mode', 'mode' => 'auto' ) );
check( 'nút Lưu chế độ ⇒ về tự động', 'auto' === OHCL_Demo::mode() );
flash();
ob_start(); OHCL_Admin_Demo::page(); $ap = ob_get_clean();
check( 'màn Dữ liệu mẫu: số đã nhập, nút xoá có ô "chắc chắn", nút nhập bài', false !== strpos( $ap, '2 / 2' ) && false !== strpos( $ap, 'name="confirm"' ) && false !== strpos( $ap, 'Nhập bài mẫu (đăng ngay)' ) );

/* ---------- bài mẫu: đăng ngay, đăng hết / nháp hết / xoá hết ---------- */
$realpost = wp_insert_post( array( 'post_type' => 'post', 'post_title' => 'Bài thật', 'post_status' => 'publish' ) );
handle( array( 'do' => 'seed_posts' ) );
$dp = OHCL_Demo::posts();
$p1 = reset( $dp );
check( 'nhập bài mẫu: đăng ngay, có dấu ẩn _ohcl_demo, gắn chuyên mục tạo mới', 1 === count( $dp ) && 'publish' === $p1->post_status && '1' === get_post_meta( $p1->ID, '_ohcl_demo', true ) && array( $GLOBALS['terms']['kien-thuc'] ) === $p1->post_category );
check( 'nội dung bài: link {place:a-1} thành hồ sơ thật, {map} thành bản đồ, bỏ <script>, có dòng "Bài mẫu"', false !== strpos( $p1->post_content, 'https://hub.test/co-so/' . $ids['a-1'] . '-' ) && false === strpos( $p1->post_content, '{map}' ) && false === strpos( $p1->post_content, '<script' ) && false !== strpos( $p1->post_content, 'Bài mẫu của' ) );
handle( array( 'do' => 'seed_posts' ) );
check( 'nhập lại bài mẫu không tạo trùng', 1 === count( OHCL_Demo::posts() ) && false !== strpos( flash()['ok'], '1 đã có' ) );
handle( array( 'do' => 'draft_posts' ) );
check( 'Chuyển nháp hết ⇒ bài mẫu thành nháp, bài thật vẫn đăng', 'draft' === $GLOBALS['posts'][ $p1->ID ]->post_status && 'publish' === $GLOBALS['posts'][ $realpost ]->post_status );
handle( array( 'do' => 'publish_posts' ) );
check( 'Đăng hết ⇒ bài mẫu đăng lại', 'publish' === $GLOBALS['posts'][ $p1->ID ]->post_status );
flash();
handle( array( 'do' => 'delete_posts' ) );
$f = flash();
check( 'Xoá hết bài mẫu khi chưa tích "chắc chắn" ⇒ từ chối, báo lỗi bốn trường', isset( $GLOBALS['posts'][ $p1->ID ] ) && 'ohcl_demo_confirm' === $f['error']['code'] && '' !== $f['error']['hint'] );
handle( array( 'do' => 'delete_posts', 'confirm' => '1' ) );
check( 'Xoá hết bài mẫu ⇒ chỉ bài mẫu mất, bài thật còn', ! isset( $GLOBALS['posts'][ $p1->ID ] ) && isset( $GLOBALS['posts'][ $realpost ] ) );
handle( array( 'do' => 'delete_members', 'confirm' => '1' ) );
check( 'Xoá hết cơ sở mẫu ⇒ chỉ cơ sở mẫu mất, hội viên thật còn đủ', 0 === count( OHCL_Demo::member_ids() ) && 3 === count( OHCL_Members::all() ) && isset( $GLOBALS['posts'][ $real ] ) );
handle( array( 'do' => 'lam-bay' ) );
check( 'thao tác lạ ⇒ lỗi, không làm gì', 'ohcl_demo_action' === flash()['error']['code'] );
$GLOBALS['admin'] = false;
$threw = false;
try { handle( array( 'do' => 'seed_members' ) ); } catch ( RuntimeException $e ) { $threw = true; }
check( 'người không có quyền quản trị ⇒ bị chặn', $threw && 0 === count( OHCL_Demo::member_ids() ) );

/* ---------- ảnh nền hero + tâm bản đồ + Gần tôi (OHCL-FIX4) ---------- */
// [2026-11-26 10:25 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX4
check( 'ảnh nền: nhận https / đường dẫn tương đối; từ chối http, javascript:, dấu nháy, ngoặc (không chèn được CSS)', '/wp-content/plugins/x/hero.svg' === OHCL_Industry::clean_image_url( '/wp-content/plugins/x/hero.svg' ) && 'https://a.vn/b.jpg?v=2' === OHCL_Industry::clean_image_url( 'https://a.vn/b.jpg?v=2' ) && '' === OHCL_Industry::clean_image_url( 'http://a.vn/b.jpg' ) && '' === OHCL_Industry::clean_image_url( 'javascript:alert(1)' ) && '' === OHCL_Industry::clean_image_url( "https://a.vn/b.jpg');x:url('y" ) && '' === OHCL_Industry::clean_image_url( 'https://a.vn/b).jpg' ) && '' === OHCL_Industry::clean_image_url( 'https://a.vn/x\\.jpg' ) );
check( 'tâm bản đồ: số hợp lệ, zoom kẹp 3–17; sai ⇒ rỗng', array( 'lat' => 21.0285, 'lng' => 105.8542, 'zoom' => 17 ) === OHCL_Industry::clean_map( array( 'lat' => '21.0285', 'lng' => 105.8542, 'zoom' => 30 ) ) && array() === OHCL_Industry::clean_map( array( 'lat' => 'x', 'lng' => 1 ) ) && array() === OHCL_Industry::clean_map( array( 'lat' => 95, 'lng' => 1 ) ) );
$w = array();
$pp = OHCL_Industry::validate_persona( array( 'hero_bg' => 'javascript:x', 'map' => array( 'lat' => 'x' ) ), array(), $w );
check( 'plugin ngành khai sai hero_bg / map ⇒ bỏ, có cảnh báo', '' === $pp['hero_bg'] && array() === $pp['map'] && 2 === count( $w ) );
update_option( OHCL_Industry::HERO_OPTION, 'https://hub.test/nen.jpg' );
update_option( OHCL_Industry::MAP_OPTION, array( 'lat' => 21.0285, 'lng' => 105.8542, 'zoom' => 12 ) );
$h = OHCL_Front::html( 'home', array() );
check( 'chủ Hub đặt ảnh nền ⇒ hero có nền ở dưới', false !== strpos( $h, '<section class="hero has-bg" style="background-image:url(\'https://hub.test/nen.jpg\')">' ) );
check( 'chủ Hub đặt tâm Hà Nội ⇒ bản đồ trang chủ mở ở tâm + zoom đó; có nút Gần tôi + ô danh sách gần nhất', false !== strpos( $h, 'data-center="21.0285,105.8542" data-zoom="12"' ) && false !== strpos( $h, 'data-ohcl-locate' ) && false !== strpos( $h, 'data-ohcl-near hidden' ) );
update_option( OHCL_Industry::HERO_OPTION, 'red;}body{display:none' );
update_option( OHCL_Industry::MAP_OPTION, array() );
$h = OHCL_Front::html( 'home', array() );
check( 'ảnh nền sai dạng / bỏ tâm ⇒ không chèn gì, bản đồ vừa khung các điểm', false !== strpos( $h, '<section class="hero">' ) && false === strpos( $h, 'data-center=' ) );
update_option( OHCL_Industry::HERO_OPTION, '' );
$bh = OHCL_Front::html( 'benefits', array() );
check( 'trang quyền lợi: "8 quyền lợi đặc biệt"', false !== strpos( $bh, '<h1>8 quyền lợi đặc biệt</h1>' ) && false === strpos( $bh, 'trụ cột quyền lợi' ) );

/* ---------- sản phẩm từ cửa hàng của cơ sở (đường B) ---------- */
check( 'endpoint: chỉ https tên miền thật; http, IP, có query ⇒ rỗng', 'https://shop.vn/wp-json/wc/store/v1/products?per_page=6&orderby=popularity' === OHCL_Shop::endpoint( 'https://shop.vn/' ) && '' === OHCL_Shop::endpoint( 'http://shop.vn' ) && '' === OHCL_Shop::endpoint( 'https://127.0.0.1' ) && '' === OHCL_Shop::endpoint( 'https://shop.vn/?a=1' ) );
check( 'giá: 45000 VND (minor 0) ⇒ "45.000 ₫"; 1250 USD minor 2 ⇒ "$12,50"', '45.000 ₫' === OHCL_Shop::price( array( 'price' => '45000', 'currency_minor_unit' => 0, 'currency_symbol' => '₫', 'currency_prefix' => '', 'currency_suffix' => '' ) ) && '$12,50' === OHCL_Shop::price( array( 'price' => '1250', 'currency_minor_unit' => 2, 'currency_prefix' => '$' ) ) );
$body = json_encode(
	array(
		array( 'name' => 'Đậu hũ non &amp; <b>tươi</b>', 'permalink' => 'https://shop.vn/sp/dau-hu', 'prices' => array( 'price' => '15000', 'currency_minor_unit' => 0, 'currency_suffix' => ' ₫' ), 'images' => array( array( 'src' => 'https://shop.vn/a.jpg', 'thumbnail' => 'https://shop.vn/a-150.jpg' ) ), 'on_sale' => true ),
		array( 'name' => 'Link lạ', 'permalink' => 'https://lua-dao.vn/x', 'prices' => array( 'price' => '1' ) ),
		array( 'name' => 'Ảnh http', 'permalink' => 'https://shop.vn/sp/2', 'images' => array( array( 'src' => 'http://shop.vn/b.jpg' ) ) ),
		'rác',
	)
);
$cards = OHCL_Shop::parse( $body, 'https://shop.vn' );
check( 'parse: bỏ link sang tên miền khác, bỏ ảnh http, giải mã tên, cờ giảm giá', 2 === count( $cards ) && 'Đậu hũ non & tươi' === $cards[0]['name'] && 'https://shop.vn/a-150.jpg' === $cards[0]['image'] && '15.000 ₫' === $cards[0]['price'] && $cards[0]['sale'] && '' === $cards[1]['image'] );
check( 'parse: JSON hỏng ⇒ rỗng, giới hạn 6 thẻ', array() === OHCL_Shop::parse( 'not json', 'https://shop.vn' ) && 6 === count( OHCL_Shop::parse( json_encode( array_fill( 0, 9, array( 'name' => 'x', 'permalink' => 'https://shop.vn/x' ) ) ), 'https://shop.vn' ) ) );
$GLOBALS['http_reply'] = array( 'code' => 200, 'body' => $body );
$a = OHCL_Shop::products( 'https://shop.vn' );
$b = OHCL_Shop::products( 'https://shop.vn' );
check( 'products(): gọi Store API một lần rồi dùng bộ đệm', 2 === count( $a['items'] ) && $a === $b && 1 === count( $GLOBALS['http'] ) );
unset( $GLOBALS['http_reply'] );
$e = OHCL_Shop::products( 'https://shop-chet.vn' );
check( 'cửa hàng không trả lời ⇒ danh sách rỗng + mã lỗi, không lỗi trang', array() === $e['items'] && 'ohcl_shop_unreachable' === $e['error'] );
$cl = OHCL_Members::clean( array( 'title' => 'X', 'shop_url' => 'http://shop.vn' ), $cfg );
check( 'hồ sơ: website bán hàng không https ⇒ lỗi rõ ràng', is_wp_error( $cl ) && 'ohcl_member_shop_invalid' === $cl->get_error_code() );
$cl = OHCL_Members::clean( array( 'title' => 'X', 'shop_url' => 'https://shop.vn', 'shop_consent' => '1' ), $cfg );
$sid = OHCL_Members::save( $cl );
$sv  = OHCL_Members::public_view( OHCL_Members::get( $sid ), $cfg );
check( 'đồng ý hiện sản phẩm ⇒ thẻ công khai có địa chỉ cửa hàng; bỏ đồng ý ⇒ rỗng', 'https://shop.vn' === $sv['shop'] && '' === OHCL_Members::public_view( array_merge( OHCL_Members::get( $sid ), array( 'shop_consent' => '' ) ), $cfg )['shop'] );
$GLOBALS['http_reply'] = array( 'code' => 200, 'body' => $body );
$ph = OHCL_Front::html( 'place', array( 'place' => array_merge( $sv, array( 'zalo' => '', 'certs' => array() ) ) ) );
check( 'hồ sơ cơ sở thật có cửa hàng: khối "Sản phẩm của …", link sang cửa hàng, rel nofollow', false !== strpos( $ph, 'class="shop"' ) && false !== strpos( $ph, 'href="https://shop.vn/sp/dau-hu" rel="nofollow noopener"' ) && false !== strpos( $ph, 'Mua tại cửa hàng' ) );

echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
