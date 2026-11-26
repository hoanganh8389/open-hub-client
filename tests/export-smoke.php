<?php
/**
 * Success ledger (OHCL_Events) + member reported results (OHCL_Service outcomes) + public beacon.
 * Run: php tests/export-smoke.php — exit 0 = pass.
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 04:36 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6 — test xuất dữ liệu, nhật ký, rời Hub, xoá thông tin.

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
$GLOBALS['updir'] = sys_get_temp_dir() . '/ohcl-ex-' . getmypid(); @mkdir( $GLOBALS['updir'] ); $GLOBALS['me'] = 0; $GLOBALS['opts'] = array(); $GLOBALS['tr'] = array(); $GLOBALS['hooks'] = array(); $GLOBALS['posts'] = array(); $GLOBALS['meta'] = array(); $GLOBALS['terms'] = array(); $GLOBALS['http'] = array();
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

$mk = function ( $title, $extra = array() ) {
	$id = wp_insert_post( array( 'post_type' => OHCL_Members::CPT, 'post_title' => $title ) );
	update_post_meta( $id, '_bzm_status', 'active' );
	foreach ( $extra as $k => $v ) { update_post_meta( $id, $k, $v ); }
	return $id;
};
$a = $mk( 'Quán An', array( '_bzm_phone' => '0901234567', '_bzm_email' => 'an@x.vn', '_bzm_address' => '1 Lê Lợi', '_bzm_public_consent' => '2026-11-01 10:00:00', '_bzm_support' => array( array( 'at' => '2026-11-02', 'text' => 'Cần hỗ trợ AN-RIENG' ) ) ) );
$b = $mk( '=HYPERLINK("http://e.vn")', array( '_bzm_phone' => '0907777777', '_bzm_support' => array( array( 'at' => '2026-11-03', 'text' => 'BINH-BI-MAT' ) ) ) );

/* ---------- thuần ---------- */
check( 'csv_cell: tiền tố nháy cho = + - @ (chống công thức), bọc ngoặc kép, nhân đôi nháy kép, bỏ xuống dòng', "\"'=SUM(A1)\"" === OHCL_Export::csv_cell( '=SUM(A1)' ) && "\"'+1\"" === OHCL_Export::csv_cell( '+1' ) && "\"'@x\"" === OHCL_Export::csv_cell( '@x' ) && '"a ""b"" c d"' === OHCL_Export::csv_cell( "a \"b\" c\nd" ) && '"x; y"' === OHCL_Export::csv_cell( array( 'x', 'y' ) ) );
$csvt = OHCL_Export::csv( array( 'a' => 'A' ), array( array( 'a' => 1 ), array( 'a' => 2 ) ) );
check( 'csv: có BOM UTF-8, hàng tiêu đề, một hàng mỗi hồ sơ', 0 === strpos( $csvt, "\xEF\xBB\xBF" ) && 3 === count( array_filter( explode( "\r\n", $csvt ) ) ) );
check( 'scrub: che khoá biz-… / ohs_… / chuỗi dài, bỏ thẻ, cắt 120', false === strpos( OHCL_Audit::scrub( 'khoá biz-' . str_repeat( 'a1', 16 ) ), 'a1a1' ) && false !== strpos( OHCL_Audit::scrub( 'x biz-' . str_repeat( 'a1', 16 ) ), '[đã ẩn]' ) && 'ab' === OHCL_Audit::scrub( '<b>ab</b>' ) && 120 === mb_strlen( OHCL_Audit::scrub( str_repeat( 'é', 300 ) ), 'UTF-8' ) );

/* ---------- nhật ký ---------- */
check( 'log: hành động lạ bị từ chối; hợp lệ ghi được', false === OHCL_Audit::log( 'bay_la', $a ) && true === OHCL_Audit::log( 'key_issued', $a, 'biz-' . str_repeat( 'f0', 20 ), 1, $NOW ) );
$au = OHCL_Audit::recent( 10 );
check( 'recent: một dòng, đủ ai / việc / hồ sơ; khoá không nằm trong ghi chú', 1 === count( $au ) && 'key_issued' === $au[0]['a'] && 1 === $au[0]['by'] && $a === $au[0]['m'] && false === strpos( $au[0]['n'], 'f0f0' ) );
$rawf = file_get_contents( OHCL_Events::dir( 'audit' ) . '/' . gmdate( 'Y-m', $NOW ) . '.jsonl' );
check( 'tệp nhật ký có .htaccess chặn truy cập; không chứa khoá, điện thoại, email', is_file( OHCL_Events::dir( 'audit' ) . '/.htaccess' ) && false === strpos( $rawf, 'f0f0' ) && false === strpos( $rawf, '0901234567' ) && false === strpos( $rawf, 'an@x.vn' ) );
check( 'dir: tên thư mục lạ bị từ chối', '' === OHCL_Events::dir( '../x' ) );

/* ---------- gói của hội viên ---------- */
$GLOBALS['admin'] = false;
$pkgA = OHCL_Export::for_member( OHCL_Members::get( $a ) );
$js   = json_encode( $pkgA, JSON_UNESCAPED_UNICODE );
check( 'gói hội viên: đúng định dạng, có hồ sơ + yêu cầu hỗ trợ CỦA MÌNH + mục "không gồm"', OHCL_Export::MEMBER_FORMAT === $pkgA['format'] && false !== strpos( $js, 'AN-RIENG' ) && false !== strpos( $js, '0901234567' ) && ! empty( $pkgA['not_included'] ) );
check( 'gói hội viên: KHÔNG có dữ liệu hội viên khác', false === strpos( $js, 'BINH-BI-MAT' ) && false === strpos( $js, '0907777777' ) );
check( 'gói hội viên: không có khoá đầy đủ / bí mật', 0 === preg_match( '/biz-[0-9a-f]{16,}|ohs_[0-9a-f]{16,}|secret/i', $js ) );
check( 'gói hội viên: chỉ có các khoá cấp cao đã khai báo (không lọt bản ghi thô)', array() === array_diff( array_keys( $pkgA ), array( 'format', 'exported_at', 'hub', 'member', 'profile', 'industry_fields', 'consents', 'journey', 'ai', 'ai_key', 'map_level', 'verification', 'certificates', 'benefits', 'reported_results', 'support_requests', 'impact_this_month', 'not_included' ) ) );
check( 'tải gói ⇒ có dòng nhật ký export_member', in_array( 'export_member', array_column( OHCL_Audit::recent( 20 ), 'a' ), true ) );

/* ---------- danh sách của Hub ---------- */
$csv = OHCL_Export::for_hub_csv();
$hub = OHCL_Export::for_hub();
check( 'danh sách Hub: 2 hồ sơ; CSV chống công thức ở tên "=HYPERLINK"', 2 === $hub['count'] && false !== strpos( $csv, "\"'=HYPERLINK" ) && false === strpos( $csv, ',"=HYPERLINK' ) );
check( 'danh sách Hub: không chứa nội dung yêu cầu hỗ trợ, không khoá', false === strpos( $csv . json_encode( $hub ), 'AN-RIENG' ) && false === strpos( $csv . json_encode( $hub ), 'BINH-BI-MAT' ) && 0 === preg_match( '/biz-[0-9a-f]{16,}/', $csv ) );
check( 'xuất danh sách ⇒ nhật ký export_hub (CSV và JSON)', 2 === count( array_filter( OHCL_Audit::recent( 50 ), function ( $r ) { return 'export_hub' === $r['a']; } ) ) );

/* ---------- rời Hub ---------- */
$r  = OHCL_Service::leave( $a );
$mA = OHCL_Members::get( $a );
check( 'leave: trạng thái "left", rút đồng ý công khai, ghi nhật ký member_left', true === $r && 'left' === $mA['status'] && '' === (string) $mA['consent'] && in_array( 'member_left', array_column( OHCL_Audit::recent( 50 ), 'a' ), true ) );
$r2 = OHCL_Service::leave( $a );
check( 'leave lần hai ⇒ 409 ohcl_member_already_left', is_wp_error( $r2 ) && 'ohcl_member_already_left' === $r2->get_error_code() );
check( 'leave: không tồn tại ⇒ lỗi, không đụng hồ sơ khác', is_wp_error( OHCL_Service::leave( 99999 ) ) && 'active' === OHCL_Members::get( $b )['status'] );

/* ---------- xoá thông tin ---------- */
$e1 = OHCL_Service::anonymize( $b );
check( 'anonymize: hồ sơ còn hoạt động ⇒ 409 ohcl_member_not_left, chưa đụng gì', is_wp_error( $e1 ) && 'ohcl_member_not_left' === $e1->get_error_code() && '0907777777' === OHCL_Members::get( $b )['phone'] );
$e2 = OHCL_Service::anonymize( $a );
$mA = OHCL_Members::get( $a );
check( 'anonymize: xoá điện thoại, email, địa chỉ, yêu cầu hỗ trợ; tên "Đã xoá thông tin"; giữ mã', true === $e2 && '' === $mA['phone'] && '' === $mA['email'] && '' === $mA['address'] && '' === get_post_meta( $a, '_bzm_support', true ) && 0 === strpos( $mA['title'], 'Đã xoá thông tin' ) && '' !== $mA['anonymized_at'] && '' !== $mA['code'] );
check( 'anonymize: nhật ký member_anonymized; hồ sơ khác nguyên vẹn', in_array( 'member_anonymized', array_column( OHCL_Audit::recent( 50 ), 'a' ), true ) && '0907777777' === OHCL_Members::get( $b )['phone'] );
$e3 = OHCL_Service::anonymize( $a );
check( 'anonymize lần hai ⇒ 409 ohcl_member_already_erased', is_wp_error( $e3 ) && 'ohcl_member_already_erased' === $e3->get_error_code() );
$pk2 = json_encode( OHCL_Export::for_member( $mA ), JSON_UNESCAPED_UNICODE );
check( 'sau khi xoá: gói xuất không còn điện thoại / yêu cầu hỗ trợ cũ', false === strpos( $pk2, '0901234567' ) && false === strpos( $pk2, 'AN-RIENG' ) );

/* ---------- quy ước ---------- */
$xs = file_get_contents( $dir . '/includes/class-ohcl-export.php' ) . file_get_contents( $dir . '/includes/class-ohcl-audit.php' );
check( 'xuất + nhật ký: không bảng mới, không gọi mạng / LLM, không đọc khoá đầy đủ', 0 === preg_match( '/\$wpdb|dbDelta|wp_remote_|wp_safe_remote_|openai|get_secret/i', $xs ) );
rmrf( $GLOBALS['updir'] );
echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
