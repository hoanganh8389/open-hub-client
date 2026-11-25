<?php
/**
 * Smoke test of module 06 certification with stubbed WordPress: issue (only verified, all criteria), renew
 * (same code), revoke (public reason), public lookup (four results, no private data), reports, module off.
 * Run: php tests/cert-smoke.php — exit 0 = pass.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — chạy thử chứng nhận (module 06) với WordPress giả: cấp, gia hạn, thu hồi, tra cứu, phản ánh, module tắt.

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
foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview', 'class-ohcl-members', 'class-ohcl-certs', 'class-ohcl-front' ) as $f ) {
	require $dir . '/includes/' . $f . '.php';
}
require $dir . '/examples/wp-sample-hub/wp-sample-hub.php';

function get_userdata( $id ) { return (object) array( 'display_name' => 'Leader ' . $id ); }

$pass = 0; $fail = 0;
function check( $name, $ok ) { global $pass, $fail; if ( $ok ) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; } }
function err_code( $x ) { return is_wp_error( $x ) ? $x->get_error_code() : ''; }

$now_s = gmdate( 'Y-m-d H:i:s' );
$year  = (int) gmdate( 'Y' );
OHCL_Core_Client::$config    = array( 'base_url' => 'https://bizcity.test', 'hub' => 'sample', 'secret' => 'ohs_' . str_repeat( 'a', 32 ) );
OHCL_Core_Client::$transport = function () use ( $now_s ) {
	return array( 'status' => 200, 'body' => json_encode( array( 'items' => array(
		array( 'id' => 11, 'status' => 'active', 'plan_code' => 'ai_std', 'plan_until' => '2030-01-01 00:00:00', 'last_used_at' => $now_s, 'created_at' => $now_s, 'key_prefix' => 'biz-…1111', 'label' => '', 'site_url' => '', 'coach_ref' => '' ),
	), 'page' => 1 ) ) );
};
$cfg = OHCL_Industry::current();
function mk( $title, array $meta ) { $id = wp_insert_post( array( 'post_type' => 'bz_hub_member', 'post_title' => $title ) ); foreach ( $meta as $k => $v ) { update_post_meta( $id, $k, $v ); } return $id; }
$verify = array( 'by' => 1, 'at' => $now_s, 'until' => '2030-01-01 00:00:00', 'evidence' => array( 'gpkd' ), 'note' => '' );
$a = mk( 'Cửa hàng <b>An</b>', array( '_bzm_status' => 'active', '_bzm_key_id' => 11, '_bzm_accepted_at' => $now_s, '_bzm_verify' => $verify, '_bzm_public_consent' => $now_s, '_bzm_lat' => '21.02', '_bzm_lng' => '105.85', '_bzm_province' => 'Hà Nội', '_bzm_phone' => '0912000111' ) );
$b = mk( 'Cửa hàng Bình', array( '_bzm_status' => 'active', '_bzm_key_id' => 11, '_bzm_verify' => $verify ) );
$u = mk( 'Cửa hàng Chưa XM', array( '_bzm_status' => 'active', '_bzm_key_id' => 11 ) );
$k11 = array( 'status' => 'active', 'last_used_at' => $now_s );

/* thuần */
check( 'tiền tố: cert_prefix của ngành, không có thì lấy id Hub', 'VH' === OHCL_Certs::prefix( array( 'cert_prefix' => 'VH', 'id' => 'vegan' ) ) && 'SAMPLE' === OHCL_Certs::prefix( $cfg ) && 'HUB' === OHCL_Certs::prefix( array( 'id' => '1' ) ) );
check( 'mã: <TIỀN TỐ>-CERT-<năm>-<4 số>', 'VH-CERT-2026-0042' === OHCL_Certs::code( 'VH', 2026, 42 ) );
check( 'chuẩn hoá mã gõ tay / quét', 'VH-CERT-2026-0042' === OHCL_Certs::normalize( ' vh-cert-2026–0042 ' ) && '' === OHCL_Certs::normalize( 'abc' ) && '' === OHCL_Certs::normalize( '<script>' ) );
$t = time();
check( 'trạng thái: thu hồi > hết hạn > còn hiệu lực', 'revoked' === OHCL_Certs::state( array( 'revoked_at' => '2020-01-01', 'until' => '2030-01-01' ), $t ) && 'expired' === OHCL_Certs::state( array( 'revoked_at' => '', 'until' => '2020-01-01' ), $t ) && 'valid' === OHCL_Certs::state( array( 'revoked_at' => '', 'until' => '2030-01-01' ), $t ) );
check( 'chuẩn không có trong dữ liệu ngành ⇒ lỗi', 'ohcl_cert_standard_unknown' === err_code( OHCL_Certs::clean_issue( array( 'standard' => 'khong-co' ), $cfg ) ) );
$part = OHCL_Certs::clean_issue( array( 'standard' => 'hang-chinh-hang', 'criteria' => array( '0' ), 'evidence' => array( '0' ) ), $cfg );
check( 'thiếu một tiêu chí ⇒ không cấp, nêu tên tiêu chí', 'ohcl_cert_criteria_incomplete' === err_code( $part ) && false !== strpos( $part->get_error_message(), 'Có tem truy xuất' ) );
check( 'không có bằng chứng ⇒ không cấp', 'ohcl_cert_evidence_required' === err_code( OHCL_Certs::clean_issue( array( 'standard' => 'hang-chinh-hang', 'criteria' => array( '0', '1' ) ), $cfg ) ) );
$ok = OHCL_Certs::clean_issue( array( 'standard' => 'hang-chinh-hang', 'criteria' => array( '0', '1' ), 'evidence' => array( '1', '9' ), 'note' => 'ghi-chu-<i>bi-mat</i>' ), $cfg );
check( 'đủ tiêu chí ⇒ hạn mặc định của chuẩn, bằng chứng lạ bị bỏ, ghi chú bỏ thẻ', is_array( $ok ) && 12 === $ok['months'] && array( 'Ảnh tem' ) === $ok['evidence'] && 'ghi-chu-bi-mat' === $ok['note'] );

/* cấp */
check( 'chưa xác minh (V3) ⇒ không cấp', 'ohcl_cert_needs_verify' === err_code( OHCL_Certs::issue( $u, $ok ) ) );
$r1 = OHCL_Certs::issue( $a, $ok );
check( 'cấp lần đầu ⇒ mã SAMPLE-CERT-<năm>-0001', is_array( $r1 ) && OHCL_Certs::code( 'SAMPLE', $year, 1 ) === $r1['code'] && ! $r1['renewed'] );
$ma = OHCL_Members::get( $a );
check( 'hồ sơ giữ bản sao ngắn; hành trình lên bậc 6', isset( $ma['certs'][ $r1['code'] ] ) && 6 === OHCL_Members::journey( $ma, $k11, time() ) );
$posts_before = count( $GLOBALS['posts'] );
$r2 = OHCL_Certs::issue( $a, $ok );
$c1 = OHCL_Certs::get( $r1['id'] );
check( 'cấp lại cùng chuẩn = gia hạn: giữ mã, ghi kỳ cũ, không tạo giấy mới', $r2['renewed'] && $r2['code'] === $r1['code'] && 1 === count( $c1['history'] ) && $posts_before === count( $GLOBALS['posts'] ) );
$r3 = OHCL_Certs::issue( $b, $ok );
check( 'cơ sở khác ⇒ số kế tiếp 0002', OHCL_Certs::code( 'SAMPLE', $year, 2 ) === $r3['code'] );

/* tra cứu công khai */
$lk = OHCL_Certs::lookup( strtolower( $r1['code'] ) );
$lj = json_encode( $lk );
check( 'tra cứu (chữ thường cũng được) ⇒ còn hiệu lực, tên cơ sở, đủ 2/2 tiêu chí', 'valid' === $lk['state'] && 'Cửa hàng <b>An</b>' === $lk['business'] && 2 === $lk['criteria_count'] );
check( 'tra cứu không lộ bằng chứng, ghi chú, người thẩm định, điện thoại', false === strpos( $lj, 'Ảnh tem' ) && false === strpos( $lj, 'bi-mat' ) && false === strpos( $lj, '0912000111' ) && ! isset( $lk['by'] ) );
$_GET = array( 'ma' => $r1['code'] );
$pg = OHCL_Front::html( 'cert', array( 'lookup' => $lk, 'asked' => $r1['code'] ) );
check( 'trang tra cứu: thẻ Còn hiệu lực + QR trỏ đúng URL tra cứu + tên đã escape', false !== strpos( $pg, 'tag ok">Còn hiệu lực' ) && false !== strpos( $pg, 'data-ohcl-qr="https://hub.test/chung-nhan/?ma=' . $r1['code'] . '"' ) && false !== strpos( $pg, 'Cửa hàng &lt;b&gt;An&lt;/b&gt;' ) );
check( 'trang có bốn loại ghi nhận + bộ tiêu chuẩn + form phản ánh (nonce, bẫy bot)', false !== strpos( $pg, 'Hội viên tiêu biểu' ) && false !== strpos( $pg, 'Hàng chính hãng' ) && false !== strpos( $pg, 'name="_ohcl_report"' ) && false !== strpos( $pg, 'name="website_url_2"' ) && false === strpos( $pg, 'Ảnh tem' ) );
$none = OHCL_Certs::lookup( 'SAMPLE-CERT-1999-0001' );
$pn   = OHCL_Front::html( 'cert', array( 'lookup' => $none, 'asked' => '"><script>alert(1)</script>' ) );
check( 'mã không có ⇒ Không tìm thấy, chuỗi gõ vào đã escape', 'none' === $none['state'] && false !== strpos( $pn, 'Không tìm thấy' ) && false === strpos( $pn, '<script>alert(1)' ) );
OHCL_Members::flush_public();
$place = OHCL_Front::find_place( $a );
check( 'hồ sơ công khai có huy hiệu Certified trỏ trang tra cứu', isset( $place['certs'][ $r1['code'] ] ) && false !== strpos( OHCL_Front::html( 'place', array( 'place' => $place ) ), 'Certified · Hàng chính hãng' ) );
check( 'đầu trang có mục Chứng nhận khi module 06 bật', false !== strpos( $pg, '>Chứng nhận</a>' ) );

/* hết hạn, thu hồi */
update_post_meta( $r3['id'], '_bzc_until', '2020-01-01 00:00:00' );
OHCL_Certs::sync_member( $b );
check( 'hết hạn ⇒ trang ghi Hết hạn; hồ sơ không còn chứng nhận hợp lệ', 'expired' === OHCL_Certs::lookup( $r3['code'] )['state'] && array() === OHCL_Members::valid_certs( OHCL_Members::get( $b ), time() ) );
check( 'thu hồi không lý do ⇒ lỗi', 'ohcl_cert_reason_required' === err_code( OHCL_Certs::revoke( $r1['id'], '   ' ) ) );
check( 'thu hồi có lý do', true === OHCL_Certs::revoke( $r1['id'], 'Dùng <b>nguyên liệu</b> không rõ nguồn' ) );
$lr = OHCL_Certs::lookup( $r1['code'] );
check( 'tra cứu sau thu hồi: Đã thu hồi + ngày + lý do công khai (bỏ thẻ)', 'revoked' === $lr['state'] && '' !== $lr['revoked_at'] && 'Dùng nguyên liệu không rõ nguồn' === $lr['reason'] );
$ma = OHCL_Members::get( $a );
check( 'sau thu hồi: hành trình về bậc 5, mất huy hiệu công khai', 5 === OHCL_Members::journey( $ma, $k11, time() ) && array() === OHCL_Members::valid_certs( $ma, time() ) );
check( 'thu hồi lần hai ⇒ báo đã thu hồi', 'ohcl_cert_already_revoked' === err_code( OHCL_Certs::revoke( $r1['id'], 'x' ) ) );
$r4 = OHCL_Certs::issue( $a, $ok );
check( 'cấp lại sau thu hồi ⇒ giấy mới, mã mới (mã cũ giữ trạng thái thu hồi)', ! $r4['renewed'] && $r4['code'] !== $r1['code'] && 'revoked' === OHCL_Certs::lookup( $r1['code'] )['state'] );

/* phản ánh */
function report_post( array $p, $ip = '5.5.5.5' ) { $_POST = $p + array( '_ohcl_report' => 'nonce-ok' ); $_SERVER['REMOTE_ADDR'] = $ip; try { OHCL_Front::handle_report(); } catch ( Redirected $r ) { return $r->getMessage(); } return ''; }
check( 'phản ánh quá ngắn ⇒ lỗi', false !== strpos( report_post( array( 'text' => 'ngắn' ) ), 'err=ohcl_report_too_short' ) );
check( 'phản ánh nonce sai ⇒ hết hạn', false !== strpos( report_post( array( '_ohcl_report' => 'sai', 'text' => 'Cơ sở này bán hàng giả' ) ), 'err=ohcl_join_expired' ) );
$rr = report_post( array( 'text' => 'Cơ sở <b>này</b> dùng mã giả', 'code' => strtolower( $r3['code'] ), 'contact' => '0909000111' ), '6.6.6.6' );
$rep = OHCL_Certs::reports();
check( 'phản ánh hợp lệ ⇒ lưu (bỏ thẻ, mã chuẩn hoá), chỉ ở quản trị', false !== strpos( $rr, 'reported=1' ) && 'Cơ sở này dùng mã giả' === $rep[0]['text'] && $r3['code'] === $rep[0]['code'] && false === strpos( OHCL_Front::html( 'cert', array() ), 'dùng mã giả' ) );
report_post( array( 'text' => 'Phản ánh lần hai rất dài', '_x' => 1 ), '7.7.7.7' ); report_post( array( 'text' => 'Phản ánh lần ba rất dài' ), '7.7.7.7' ); report_post( array( 'text' => 'Phản ánh lần bốn rất dài' ), '7.7.7.7' );
check( 'phản ánh: quá 3 lần / giờ / IP ⇒ chặn', false !== strpos( report_post( array( 'text' => 'Phản ánh lần năm rất dài' ), '7.7.7.7' ), 'err=ohcl_report_rate_limited' ) );
check( 'phản ánh: bẫy bot ⇒ từ chối', false !== strpos( report_post( array( 'text' => 'Phản ánh của bot rất dài', 'website_url_2' => 'x' ), '8.8.8.8' ), 'err=ohcl_report_rejected' ) );

/* hợp đồng ngành + module tắt */
$v = OHCL_Industry::validate( array_merge( apply_filters( 'open_hub_client_industry', array() ), array( 'cert_prefix' => 'vh' ) ) );
check( 'cert_prefix sai dạng ⇒ cảnh báo, bỏ', $v['ok'] && '' === $v['config']['cert_prefix'] && false !== strpos( implode( '|', $v['warnings'] ), 'cert_prefix' ) );
update_option( OHCL_Modules::OPTION, array( '06' => false ) );
OHCL_Industry::reset();
check( 'module 06 tắt ⇒ không có trang, không có mục trên đầu trang', ! OHCL_Front::cert_on() && false === strpos( OHCL_Front::html( 'map', array() ), '>Chứng nhận</a>' ) );
check( 'module 06 tắt ⇒ phản ánh không nhận', false !== strpos( report_post( array( 'text' => 'Phản ánh khi module tắt' ), '9.9.9.9' ), 'https://hub.test/' ) && 4 === count( OHCL_Certs::reports() ) );

echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
