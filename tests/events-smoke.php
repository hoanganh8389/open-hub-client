<?php
/**
 * Success ledger (OHCL_Events) + member reported results (OHCL_Service outcomes) + public beacon.
 * Run: php tests/events-smoke.php — exit 0 = pass.
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 04:07 PM Johnny Chu - Chu Hoàng Anh] OHCL-H3 — test sổ đo + kết quả tự khai + beacon; (khung nạp lấy từ demo-smoke) — (công khai có nhãn, tự ẩn ở ngưỡng, không vào số liệu, bài đăng / nháp / xoá hết) và sản phẩm Store API.

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
class WP_REST_Response { public $data; public $status; public $headers = array(); public function __construct( $d = null, $s = 200 ) { $this->data = $d; $this->status = $s; } public function header( $k, $v ) { $this->headers[ $k ] = $v; } }
$GLOBALS['updir'] = sys_get_temp_dir() . '/ohcl-ev-' . getmypid(); @mkdir( $GLOBALS['updir'] ); $GLOBALS['me'] = 0; $GLOBALS['opts'] = array(); $GLOBALS['tr'] = array(); $GLOBALS['hooks'] = array(); $GLOBALS['posts'] = array(); $GLOBALS['meta'] = array(); $GLOBALS['terms'] = array(); $GLOBALS['http'] = array();
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
function is_user_logged_in() { return ! empty( $GLOBALS['me'] ); }
function wp_upload_dir() { return array( 'basedir' => $GLOBALS['updir'] ); }
function rest_url( $p = '' ) { return 'https://hub.test/wp-json/' . $p; }
function register_rest_route() {}
function get_users( $a ) { return array(); }
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
function get_current_user_id() { return empty( $GLOBALS['me'] ) ? 1 : $GLOBALS['me']; }
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
foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview', 'class-ohcl-members', 'class-ohcl-demo', 'class-ohcl-shop', 'class-ohcl-certs', 'class-ohcl-coach', 'class-ohcl-affiliate', 'class-ohcl-service', 'class-ohcl-benefits', 'class-ohcl-events', 'class-ohcl-audit', 'class-ohcl-export', 'class-ohcl-onboarding', 'class-ohcl-front', 'class-ohcl-rest' ) as $f ) {
	require $dir . '/includes/' . $f . '.php';
}
require $dir . '/examples/wp-sample-hub/wp-sample-hub.php';

$pass = 0; $fail = 0;
function check( $name, $ok ) { global $pass, $fail; if ( $ok ) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; } }
function rmrf( $d ) { foreach ( (array) glob( $d . '/{,.}*', GLOB_BRACE ) as $f ) { if ( in_array( basename( $f ), array( '.', '..' ), true ) ) { continue; } is_dir( $f ) ? rmrf( $f ) : @unlink( $f ); } @rmdir( $d ); }
$cfg = OHCL_Industry::current();
$NOW = time();

/* ---------- thuần ---------- */
$tl = array( array( 'm' => 5, 'e' => 'profile_view' ), array( 'm' => 5, 'e' => 'profile_view' ), array( 'm' => 5, 'e' => 'contact_click' ), array( 'm' => 6, 'e' => 'profile_view' ), array( 'm' => 5, 'e' => 'bay_la' ), 'rác', array( 'e' => 'profile_view' ) );
$t  = OHCL_Events::tally( $tl );
check( 'tally: đếm theo hội viên + sự kiện; bỏ dòng hỏng, sự kiện lạ, thiếu hội viên', 2 === $t[5]['profile_view'] && 1 === $t[5]['contact_click'] && 1 === $t[6]['profile_view'] && 2 === count( $t ) );
$ro = OHCL_Events::merge_rollup( array(), '2026-11', array( 'profile_view' => 3, 'bay_la' => 9 ) );
$ro = OHCL_Events::merge_rollup( $ro, '2026-11', array( 'profile_view' => 2, 'contact_click' => 1 ) );
check( 'merge_rollup: cộng dồn theo tháng, bỏ sự kiện lạ', 5 === $ro['2026-11']['profile_view'] && 1 === $ro['2026-11']['contact_click'] && ! isset( $ro['2026-11']['bay_la'] ) );
$many = array();
for ( $i = 0; $i < 20; $i++ ) { $many = OHCL_Events::merge_rollup( $many, gmdate( 'Y-m', strtotime( '2025-01-01 +' . $i . ' months' ) ), array( 'profile_view' => 1 ) ); }
check( 'merge_rollup: chỉ giữ 14 tháng gần nhất', 14 === count( $many ) && isset( $many['2026-08'] ) && ! isset( $many['2025-01'] ) );
check( 'is_bot: nhận ra máy tự động, xem trước liên kết, thiếu user-agent; người thường không', OHCL_Events::is_bot( 'Googlebot/2.1' ) && OHCL_Events::is_bot( 'facebookexternalhit/1.1' ) && OHCL_Events::is_bot( '' ) && OHCL_Events::is_bot( 'curl/8.0' ) && ! OHCL_Events::is_bot( 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) AppleWebKit/605.1.15 Safari/604.1' ) );
check( 'days_after: từ ngày sau mốc tới hết ngày đích', array( '2026-11-02', '2026-11-03' ) === OHCL_Events::days_after( '2026-11-01', '2026-11-03' ) && array() === OHCL_Events::days_after( '2026-11-03', '2026-11-03' ) );

/* ---------- tệp, riêng tư ---------- */
check( 'chưa ghi gì ⇒ chưa đo (active false); số "tháng này" là null (hiện "—", không phải 0)', ! OHCL_Events::active() && null === OHCL_Events::month_counts( 11, $NOW ) );
$mem = OHCL_Members::get( wp_insert_post( array( 'post_type' => OHCL_Members::CPT, 'post_title' => 'Cơ sở đo' ) ) );
$mid = $mem['id'];
update_post_meta( $mid, '_bzm_status', 'active' );
$mem = OHCL_Members::get( $mid );
$imp0 = OHCL_Service::impact( $mem, $NOW );
check( 'impact khi chưa đo: measured=false, views / contacts = null', false === $imp0['measured'] && null === $imp0['views'] && null === $imp0['contacts'] );
check( 'log: sự kiện lạ hoặc hội viên 0 ⇒ không ghi', false === OHCL_Events::log( 'bay_la', $mid ) && false === OHCL_Events::log( 'profile_view', 0 ) && ! OHCL_Events::active() );
check( 'log: ghi được một dòng; thư mục có .htaccess chặn truy cập + index.php', true === OHCL_Events::log( 'profile_view', $mid, $NOW ) && is_file( OHCL_Events::dir() . '/.htaccess' ) && false !== strpos( file_get_contents( OHCL_Events::dir() . '/.htaccess' ), 'Deny' ) && is_file( OHCL_Events::dir() . '/index.php' ) && OHCL_Events::active() );
$raw = file_get_contents( OHCL_Events::dir() . '/' . gmdate( 'Y-m-d', $NOW ) . '.jsonl' );
$row = json_decode( trim( $raw ), true );
check( 'dòng log CHỈ có t, e, m — không IP, không user-agent, không liên hệ', array( 'e', 'm', 't' ) === ( function ( $a ) { sort( $a ); return $a; } )( array_keys( $row ) ) && $mid === $row['m'] && 'profile_view' === $row['e'] );
$c1 = OHCL_Events::month_counts( $mid, $NOW );
check( 'đã đo: tháng này 1 lượt xem, 0 liên hệ (số 0 thật, không phải "—")', 1 === $c1['profile_view'] && 0 === $c1['contact_click'] && true === OHCL_Service::impact( OHCL_Members::get( $mid ), $NOW )['measured'] );

/* ---------- đếm lượt công khai: ai không được đếm ---------- */
$ua = 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/120 Safari/537.36';
$vis = array( 'ip' => '1.2.3.4', 'ua' => $ua, 'uid' => 0, 'admin' => false );
check( 'khách thường: tính 1 lượt; mở lại trong 1 giờ ⇒ không tính thêm (khử trùng)', true === OHCL_Events::record( 'contact_click', $mem, $vis ) && false === OHCL_Events::record( 'contact_click', $mem, $vis ) );
check( 'người khác (IP khác) tính được', true === OHCL_Events::record( 'contact_click', $mem, array_merge( $vis, array( 'ip' => '5.6.7.8' ) ) ) );
check( 'KHÔNG tính: quản trị viên, máy tự động, chính chủ hồ sơ', false === OHCL_Events::record( 'profile_view', $mem, array_merge( $vis, array( 'ip' => '9.9.9.9', 'admin' => true ) ) ) && false === OHCL_Events::record( 'profile_view', $mem, array_merge( $vis, array( 'ip' => '9.9.9.8', 'ua' => 'Googlebot' ) ) ) && false === OHCL_Events::record( 'profile_view', array_merge( $mem, array( 'user' => 7 ) ), array_merge( $vis, array( 'ip' => '9.9.9.7', 'uid' => 7 ) ) ) );
check( 'KHÔNG tính: hồ sơ mẫu (demo), hồ sơ không hoạt động', false === OHCL_Events::record( 'profile_view', array_merge( $mem, array( 'demo' => true ) ), array_merge( $vis, array( 'ip' => '8.8.8.1' ) ) ) && false === OHCL_Events::record( 'profile_view', array_merge( $mem, array( 'status' => 'left' ) ), array_merge( $vis, array( 'ip' => '8.8.8.2' ) ) ) );
$c2 = OHCL_Events::month_counts( $mid, $NOW );
check( 'tổng sau các lượt: 1 xem + 2 liên hệ', 1 === $c2['profile_view'] && 2 === $c2['contact_click'] );
check( 'nội dung log không chứa IP nào đã dùng', false === strpos( (string) file_get_contents( OHCL_Events::dir() . '/' . gmdate( 'Y-m-d', $NOW ) . '.jsonl' ), '1.2.3.4' ) && false === strpos( (string) file_get_contents( OHCL_Events::dir() . '/' . gmdate( 'Y-m-d', $NOW ) . '.jsonl' ), 'Mozilla' ) );

/* ---------- rollup ---------- */
foreach ( array( 3, 2 ) as $back ) { OHCL_Events::log( 'profile_view', $mid, $NOW - $back * DAY_IN_SECONDS ); OHCL_Events::log( 'contact_click', $mid, $NOW - $back * DAY_IN_SECONDS ); }
OHCL_Events::log( 'profile_view', $mid, $NOW - 130 * DAY_IN_SECONDS );
$same_month = function ( $days_back ) use ( $NOW ) { return gmdate( 'Y-m', $NOW ) === gmdate( 'Y-m', $NOW - $days_back * DAY_IN_SECONDS ); };
$expect_v = 1 + ( $same_month( 3 ) ? 1 : 0 ) + ( $same_month( 2 ) ? 1 : 0 );
$expect_c = 2 + ( $same_month( 3 ) ? 1 : 0 ) + ( $same_month( 2 ) ? 1 : 0 );
$before = OHCL_Events::month_counts( $mid, $NOW );
check( 'trước rollup: tháng này đọc thẳng từ tệp (đủ các ngày cùng tháng)', $expect_v === $before['profile_view'] && $expect_c === $before['contact_click'] );
$n = 0; for ( $i = 0; $i < 6; $i++ ) { $n += OHCL_Events::rollup(); }
$after = OHCL_Events::month_counts( $mid, $NOW );
$roll  = get_post_meta( $mid, '_bzm_rollup', true );
check( 'rollup: gộp ngày đã xong vào _bzm_rollup, đặt mốc; số tháng này KHÔNG đổi (không đếm hai lần)', $n > 0 && is_array( $roll ) && '' !== get_option( OHCL_Events::CURSOR, '' ) && $before === $after );
check( 'rollup: mốc = hôm qua; chạy lại không làm gì', gmdate( 'Y-m-d', $NOW - DAY_IN_SECONDS ) === get_option( OHCL_Events::CURSOR ) && 0 === OHCL_Events::rollup() );
check( 'rollup: tệp quá 120 ngày bị xoá sau khi gộp; tệp gần đây còn', ! is_file( OHCL_Events::dir() . '/' . gmdate( 'Y-m-d', $NOW - 130 * DAY_IN_SECONDS ) . '.jsonl' ) && is_file( OHCL_Events::dir() . '/' . gmdate( 'Y-m-d', $NOW ) . '.jsonl' ) );
OHCL_Events::log( 'profile_view', $mid, $NOW );
$after2 = OHCL_Events::month_counts( $mid, $NOW );
check( 'sau rollup, lượt mới hôm nay vẫn cộng vào số tháng này', $after['profile_view'] + 1 === $after2['profile_view'] );

/* ---------- kết quả tự khai ---------- */
$co = OHCL_Service::clean_outcomes( array( 'outcomes' => array(
	array( 'label' => 'Tỉ lệ giá vốn', 'before' => '38%', 'after' => '33%', 'period' => '90 ngày' ),
	array( 'label' => 'Thiếu số sau', 'before' => '10' ),
	array( 'label' => '<b>Đơn qua Zalo</b>', 'before' => '12', 'after' => '40' ),
	array( 'label' => 'Dòng 4', 'before' => '1', 'after' => '2' ),
	array( 'label' => 'Dòng 5', 'before' => '1', 'after' => '2' ),
), 'outcomes_consent' => '1' ) );
check( 'clean_outcomes: lấy tối đa 3 dòng đầu, bỏ dòng thiếu nhãn / trước / sau, bỏ thẻ HTML, nhận ô đồng ý', 2 === count( $co['items'] ) && 'Đơn qua Zalo' === $co['items'][1]['label'] && true === $co['consent'] );
$ov0 = OHCL_Service::outcomes_view( OHCL_Members::get( $mid ) );
check( 'chưa khai ⇒ trạng thái "none"', 'none' === $ov0['status'] );
OHCL_Service::save_outcomes( $mid, array( 'items' => $co['items'], 'consent' => false ) );
check( 'khai nhưng chưa đồng ý ⇒ "private" (chỉ hội viên thấy), không lên trang công khai', 'private' === OHCL_Service::outcomes_view( OHCL_Members::get( $mid ) )['status'] && array() === OHCL_Members::public_view( OHCL_Members::get( $mid ), $cfg )['outcomes'] );
$no = OHCL_Service::review_outcomes( $mid, true );
check( 'Hub duyệt khi hội viên chưa đồng ý ⇒ 409 ohcl_outcomes_no_consent (bốn trường)', is_wp_error( $no ) && 'ohcl_outcomes_no_consent' === $no->get_error_code() && 409 === $no->get_error_data()['status'] && ! empty( $no->get_error_data()['hint'] ) );
OHCL_Service::save_outcomes( $mid, array( 'items' => $co['items'], 'consent' => true ) );
check( 'đồng ý nhưng Hub chưa duyệt ⇒ "pending", VẪN chưa công khai', 'pending' === OHCL_Service::outcomes_view( OHCL_Members::get( $mid ) )['status'] && array() === OHCL_Members::public_view( OHCL_Members::get( $mid ), $cfg )['outcomes'] );
check( 'Hub duyệt ⇒ "public", thẻ công khai có kết quả', true === OHCL_Service::review_outcomes( $mid, true ) && 'public' === OHCL_Service::outcomes_view( OHCL_Members::get( $mid ) )['status'] && 2 === count( OHCL_Members::public_view( OHCL_Members::get( $mid ), $cfg )['outcomes'] ) );
OHCL_Service::save_outcomes( $mid, array( 'items' => array_slice( $co['items'], 0, 1 ), 'consent' => true ) );
check( 'hội viên sửa lại ⇒ về "pending", gỡ khỏi trang công khai tới khi Hub duyệt lại', 'pending' === OHCL_Service::outcomes_view( OHCL_Members::get( $mid ) )['status'] && array() === OHCL_Members::public_view( OHCL_Members::get( $mid ), $cfg )['outcomes'] );
OHCL_Service::review_outcomes( $mid, true );
OHCL_Service::save_outcomes( $mid, array( 'items' => $co['items'], 'consent' => false ) );
check( 'hội viên bỏ tích đồng ý ⇒ gỡ ngay khỏi công khai, "private"', 'private' === OHCL_Service::outcomes_view( OHCL_Members::get( $mid ) )['status'] && array() === OHCL_Members::public_view( OHCL_Members::get( $mid ), $cfg )['outcomes'] );

/* ---------- trang công khai + beacon ---------- */
OHCL_Service::save_outcomes( $mid, array( 'items' => $co['items'], 'consent' => true ) );
OHCL_Service::review_outcomes( $mid, true );
$card = array_merge( OHCL_Members::public_view( OHCL_Members::get( $mid ), $cfg ), array( 'zalo' => '0912000111', 'certs' => array(), 'demo' => false ) );
$ph = OHCL_Front::html( 'place', array( 'place' => $card ) );
check( 'trang công khai: bảng "Kết quả khi dùng công nghệ của Hub" + dòng "do cơ sở tự khai"; nút Zalo có beacon', false !== strpos( $ph, 'Kết quả khi dùng công nghệ của Hub' ) && false !== strpos( $ph, 'Tỉ lệ giá vốn' ) && false !== strpos( $ph, 'do cơ sở tự khai' ) && false !== strpos( $ph, 'data-ohcl-contact="https://hub.test/wp-json/open-hub-client/v1/places/' . $mid . '/contact"' ) );
$ph2 = OHCL_Front::html( 'place', array( 'place' => array_merge( $card, array( 'outcomes' => array() ) ) ) );
check( 'chưa được duyệt ⇒ trang công khai không có bảng kết quả', false === strpos( $ph2, 'Kết quả khi dùng công nghệ của Hub' ) );
$js = (string) file_get_contents( OHCL_DIR . 'assets/front.js' );
check( 'front.js: sendBeacon cho nút data-ohcl-contact, không chặn việc mở Zalo (không preventDefault)', false !== strpos( $js, 'data-ohcl-contact' ) && false !== strpos( $js, 'sendBeacon' ) && 0 === preg_match( '/data-ohcl-contact[^}]*preventDefault/s', $js ) );

/* beacon REST: cần cơ sở V4 công khai */
$now_s = gmdate( 'Y-m-d H:i:s' );
OHCL_Core_Client::$config    = array( 'base_url' => 'https://bizcity.test', 'hub' => 'sample', 'secret' => 'ohs_' . str_repeat( 'a', 32 ) );
OHCL_Core_Client::$transport = function () use ( $now_s ) { return array( 'status' => 200, 'body' => json_encode( array( 'items' => array( array( 'id' => 31, 'status' => 'trial', 'plan_code' => 'trial', 'plan_until' => '2030-01-01 00:00:00', 'last_used_at' => $now_s, 'created_at' => $now_s, 'key_prefix' => 'k', 'label' => '', 'site_url' => '', 'coach_ref' => '' ) ), 'page' => 1 ) ) ); };
foreach ( array( '_bzm_accepted_at' => $now_s, '_bzm_key_id' => 31, '_bzm_public_consent' => $now_s, '_bzm_lat' => '21.0', '_bzm_lng' => '105.8', '_bzm_verify' => array( 'by' => 1, 'at' => $now_s, 'until' => '2030-01-01 00:00:00', 'evidence' => array( 'gpkd' ), 'note' => '' ) ) as $k => $v ) { update_post_meta( $mid, $k, $v ); }
OHCL_Overview::flush(); OHCL_Members::flush_public();
$_SERVER['REMOTE_ADDR'] = '203.0.113.9'; $_SERVER['HTTP_USER_AGENT'] = $ua;
$cnt0 = OHCL_Events::month_counts( $mid, time() )['contact_click'];
$rs   = OHCL_Rest::place_contact( new class( array( 'id' => $mid ) ) implements ArrayAccess { public $p; public function __construct( $p ) { $this->p = $p; } public function offsetExists( $k ): bool { return isset( $this->p[ $k ] ); } #[\ReturnTypeWillChange] public function offsetGet( $k ) { return $this->p[ $k ]; } public function offsetSet( $k, $v ): void {} public function offsetUnset( $k ): void {} } );
check( 'POST /places/{id}/contact: trả 202 và tính 1 lượt liên hệ', 202 === $rs->status && $cnt0 + 1 === OHCL_Events::month_counts( $mid, time() )['contact_click'] );
$rs2 = OHCL_Rest::place_contact( new class( array( 'id' => $mid ) ) implements ArrayAccess { public $p; public function __construct( $p ) { $this->p = $p; } public function offsetExists( $k ): bool { return isset( $this->p[ $k ] ); } #[\ReturnTypeWillChange] public function offsetGet( $k ) { return $this->p[ $k ]; } public function offsetSet( $k, $v ): void {} public function offsetUnset( $k ): void {} } );
check( 'bấm lại ngay ⇒ vẫn 202 nhưng không tính thêm; cơ sở không công khai ⇒ vẫn 202, không tính', 202 === $rs2->status && $cnt0 + 1 === OHCL_Events::month_counts( $mid, time() )['contact_click'] );
$src = (string) file_get_contents( OHCL_DIR . 'includes/class-ohcl-events.php' );
check( 'sổ đo: không bảng mới, không gọi mạng, không đọc nội dung hội thoại', ! preg_match( '/CREATE\s+TABLE|dbDelta|wp_remote_|\$wpdb/i', $src ) );

rmrf( $GLOBALS['updir'] );
echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
