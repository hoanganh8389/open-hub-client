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
// [2026-11-25 06:49 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1C — bài viết / meta giả cho Member 360°; chuyển trang ném ngoại lệ để test tiếp tục.
class Redirected extends RuntimeException {}
$GLOBALS['posts'] = array(); $GLOBALS['meta'] = array();
function selected( $a, $b, $e ) { return (string) $a === (string) $b ? ' selected' : ''; }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); }
function number_format_i18n( $n ) { return number_format( (float) $n, 0, ',', '.' ); }
function wp_kses_post( $s ) { return $s; }
function home_url( $p = '/' ) { return 'https://hub.test' . $p; }
function sanitize_title( $s ) { return strtolower( preg_replace( '/[^a-z0-9]+/i', '-', (string) $s ) ); }
function check_admin_referer( $a ) { return true; }
function absint( $v ) { return abs( (int) $v ); }
function wp_safe_redirect( $u ) { throw new Redirected( $u ); }
function wp_insert_post( array $a ) { $id = count( $GLOBALS['posts'] ) + 101; $GLOBALS['posts'][ $id ] = (object) array( 'ID' => $id, 'post_type' => $a['post_type'], 'post_title' => $a['post_title'] ); return $id; }
function wp_update_post( array $a ) { $GLOBALS['posts'][ $a['ID'] ]->post_title = $a['post_title']; return $a['ID']; }
function get_post( $id ) { return isset( $GLOBALS['posts'][ $id ] ) ? $GLOBALS['posts'][ $id ] : null; }
function get_posts( array $a ) { return array_reverse( array_values( array_filter( $GLOBALS['posts'], function ( $p ) use ( $a ) { return $p->post_type === $a['post_type']; } ) ) ); }
function get_post_meta( $id, $k, $single ) { return isset( $GLOBALS['meta'][ $id ][ $k ] ) ? $GLOBALS['meta'][ $id ][ $k ] : ''; }
function update_post_meta( $id, $k, $v ) { $GLOBALS['meta'][ $id ][ $k ] = $v; return true; }
function handle( $class, $fn, array $post ) { $_POST = $post; try { call_user_func( array( $class, $fn ) ); } catch ( Redirected $r ) { return $r->getMessage(); } return ''; }

$dir = dirname( __DIR__ );
foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview', 'class-ohcl-members', 'class-ohcl-service', 'class-ohcl-benefits', 'class-ohcl-events', 'class-ohcl-certs', 'class-ohcl-coach', 'class-ohcl-front', 'class-ohcl-admin', 'class-ohcl-admin-members', 'class-ohcl-admin-certs', 'class-ohcl-admin-coach' ) as $f ) {
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
check( 'Tổng quan: hiện 5 câu hỏi; câu 1 đếm từ hồ sơ thật (0 hồ sơ ⇒ số 0 + lời mời thêm, không phải số bịa)', 5 === substr_count( $o, 'class="ohcl-q"' ) && false !== strpos( $o, '<span>Tổng hội viên</span><b>0</b>' ) && false !== strpos( $o, 'Thêm hội viên đầu tiên' ) );
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
// [2026-11-25 06:17 PM Johnny Chu - Chu Hoàng Anh] OHCL-PAIR — màn kết nối bằng mã ghép nối.
check( 'Cài đặt: đã kết nối ⇒ hiện Hub + nút "Kết nối lại", ô mã ghép nối, nhập tay nằm trong Nâng cao', false !== strpos( $s, 'ĐÃ KẾT NỐI</span> Hub <code>sample</code>' ) && false !== strpos( $s, 'Kết nối lại' ) && false !== strpos( $s, 'name="code"' ) && false !== strpos( $s, '<details class="ohcl-adv">' ) );
check( 'Không trang nào in bí mật Hub', false === strpos( $o . $k . $kc . $k1 . $s, $secret ) );

// [2026-11-25 06:44 PM Johnny Chu - Chu Hoàng Anh] OHCL-PAIR2 — ca thật trên veganhub.net: dán mã ghép nối vào ô "Mã Hub".
check( 'form tay: mã ghép nối dán vào ô Mã Hub ⇒ nhận là mã', '7C72-39ZP' === OHCL_Admin::pair_code_in_manual_form( '7C72-39ZP', '123456789' ) );
check( 'form tay: mã ghép nối dán vào ô Bí mật ⇒ nhận là mã', 'abcd efgh' === OHCL_Admin::pair_code_in_manual_form( '', 'abcd efgh' ) );
check( 'form tay: mã Hub thật (vegan, vegan-hub) không bị nhận nhầm', '' === OHCL_Admin::pair_code_in_manual_form( 'vegan', '' ) && '' === OHCL_Admin::pair_code_in_manual_form( 'vegan-hub', '' ) );
check( 'form tay: có bí mật ohs_ hợp lệ ⇒ ô Mã Hub dạng XXXX-XXXX vẫn là mã Hub', '' === OHCL_Admin::pair_code_in_manual_form( 'abcd-efgh', 'ohs_' . str_repeat( 'a', 32 ) ) );

OHCL_Core_Client::$config = array( 'base_url' => '', 'hub' => '', 'secret' => '' );
$sn = page( 'page_settings' );
check( 'Cài đặt: chưa kết nối ⇒ "CHƯA KẾT NỐI", 3 bước hướng dẫn, địa chỉ mặc định bizcity.vn', false !== strpos( $sn, 'CHƯA KẾT NỐI' ) && 3 === substr_count( $sn, '<li>' ) && false !== strpos( $sn, 'value="https://bizcity.vn"' ) );
OHCL_Core_Client::$config = array( 'base_url' => 'https://bizcity.test', 'hub' => 'sample', 'secret' => $secret );

OHCL_Core_Client::$transport = function () { return new WP_Error( 'x', 'down' ); };
OHCL_Overview::flush();
$od = page( 'page_overview' );
check( 'Lõi vắng: hiện lỗi bốn trường, việc cần làm báo kết nối, không có số bịa', false !== strpos( $od, 'ohcl_core_unreachable' ) && false !== strpos( $od, 'Kết nối lõi Bizcity chưa đạt' ) && false === strpos( $od, 'Đã nhận khoá' ) );

/* ---- Member 360° (OHCL-D1C) ---- */
// [2026-11-25 06:49 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1C — thêm hồ sơ → tiếp nhận → cấp khoá từ hồ sơ → danh sách + Tổng quan câu 1.
function mpage( array $get ) { $_GET = $get; ob_start(); OHCL_Admin_Members::page(); return ob_get_clean(); }
$issued = array();
OHCL_Core_Client::$transport = function ( $m, $u, $h, $b ) use ( &$issued ) {
	if ( 'POST' === $m && false !== strpos( $u, '/hub/keys' ) ) {
		$issued[] = json_decode( $b, true );
		return array( 'status' => 201, 'body' => json_encode( array( 'success' => true, 'id' => 7, 'key' => 'biz-' . str_repeat( 'f', 32 ), 'key_prefix' => 'biz-…ffff', 'plan_code' => 'trial', 'plan_until' => gmdate( 'Y-m-d H:i:s', time() + 30 * 86400 ) ) ) );
	}
	$items = $issued ? array( array( 'id' => 7, 'key_prefix' => 'biz-…ffff', 'label' => 'x', 'site_url' => '', 'plan_code' => 'trial', 'plan_until' => gmdate( 'Y-m-d H:i:s', time() + 30 * 86400 ), 'status' => 'trial', 'coach_ref' => '', 'last_used_at' => gmdate( 'Y-m-d H:i:s', time() - 3600 ), 'created_at' => gmdate( 'Y-m-d H:i:s' ) ) ) : array();
	return array( 'status' => 200, 'body' => json_encode( array( 'items' => $items, 'page' => 1 ) ) );
};
OHCL_Overview::flush();
$GLOBALS['tr'] = array();

$l0 = mpage( array() );
check( 'Hội viên: trống ⇒ lời mời thêm hồ sơ, thuật ngữ ngành', false !== strpos( $l0, 'Chưa có hội viên nào' ) && false !== strpos( $l0, '+ Thêm hội viên' ) );
$f0 = mpage( array( 'view' => 'new' ) );
check( 'Thêm: form có trường lõi + trường ngành (multi, number)', false !== strpos( $f0, 'name="title"' ) && false !== strpos( $f0, 'name="f_nhom_hang[]"' ) && false !== strpos( $f0, 'name="f_nam_thanh_lap"' ) && false !== strpos( $f0, 'value="cua-hang"' ) );

$r = handle( 'OHCL_Admin_Members', 'handle_save', array( 'id' => 0, 'title' => '' ) );
check( 'Lưu: thiếu tên ⇒ lỗi bốn trường, quay lại form', false !== strpos( $r, 'view=new' ) && 'ohcl_member_title_required' === get_transient( 'ohcl_flash_1' )['error']['code'] );
delete_transient( 'ohcl_flash_1' );
$r = handle( 'OHCL_Admin_Members', 'handle_save', array( 'id' => 0, 'title' => '<b>Cửa hàng Bình An</b>', 'segment' => 'cua-hang', 'province' => 'Hà Nội', 'email' => 'a@b.vn', 'f_nhom_hang' => array( 'gia-dung', 'hack' ), 'f_nam_thanh_lap' => '2015' ) );
$mid = (int) preg_replace( '/.*id=(\d+).*/', '$1', $r );
$mem = OHCL_Members::get( $mid );
check( 'Lưu: tạo hồ sơ, bỏ thẻ HTML, lọc lựa chọn lạ, trường ngành ở meta _bzi_*', $mid > 0 && 'Cửa hàng Bình An' === $mem['title'] && array( 'gia-dung' ) === $GLOBALS['meta'][ $mid ]['_bzi_nhom_hang'] && 2015 === $GLOBALS['meta'][ $mid ]['_bzi_nam_thanh_lap'] && 'active' === $mem['status'] );
check( 'Hồ sơ mới: hành trình bậc 1 "Mới đăng ký"', 1 === OHCL_Members::journey( $mem, null, time() ) );

handle( 'OHCL_Admin_Members', 'handle_accept', array( 'id' => $mid ) );
check( 'Tiếp nhận ⇒ bậc 2', 2 === OHCL_Members::journey( OHCL_Members::get( $mid ), null, time() ) );
delete_transient( 'ohcl_flash_1' );

handle( 'OHCL_Admin_Members', 'handle_key', array( 'id' => $mid ) );
$fl = get_transient( 'ohcl_flash_1' );
check( 'Cấp khoá từ hồ sơ: gửi tên + gắn key_id / prefix, không lưu khoá thô', 1 === count( $issued ) && 'Cửa hàng Bình An' === $issued[0]['label'] && 7 === $GLOBALS['meta'][ $mid ]['_bzm_key_id'] && 'biz-…ffff' === $GLOBALS['meta'][ $mid ]['_bzm_key_prefix'] && false === strpos( json_encode( $GLOBALS['meta'] ), 'biz-' . str_repeat( 'f', 32 ) ) && ! empty( $fl['key'] ) );
$one = mpage( array( 'id' => $mid ) );
$one2 = mpage( array( 'id' => $mid ) );
check( 'Member 360°: khoá thô hiện một lần, AI "Dùng thử", hành trình "Đang vận hành", tab ngành', false !== strpos( $one, 'biz-' . str_repeat( 'f', 32 ) ) && false === strpos( $one2, 'biz-' . str_repeat( 'f', 32 ) ) && false !== strpos( $one2, 'Dùng thử · còn' ) && false !== strpos( $one2, 'Đang vận hành (4/8)' ) && false !== strpos( $one2, '>Nhóm hàng</a>' ) );
check( 'Member 360°: khoá đang chạy ⇒ không còn nút cấp khoá', false === strpos( $one2, 'Cấp khoá dùng thử</button>' ) );
handle( 'OHCL_Admin_Members', 'handle_key', array( 'id' => $mid ) );
check( 'Cấp lần hai khi khoá đang chạy ⇒ 409, không gọi lõi', 1 === count( $issued ) && 'ohcl_member_has_key' === get_transient( 'ohcl_flash_1' )['error']['code'] );
delete_transient( 'ohcl_flash_1' );

$tabp = mpage( array( 'id' => $mid, 'tab' => 'nhom-hang' ) );
check( 'Tab ngành (ô mở rộng) hiện nội dung của plugin ngành', false !== strpos( $tabp, 'Nhóm hàng của cửa hàng hiển thị ở đây' ) );

handle( 'OHCL_Admin_Members', 'handle_status', array( 'id' => $mid, 'status' => 'xoá-hết' ) );
check( 'Trạng thái lạ ⇒ lỗi, giữ nguyên', 'active' === OHCL_Members::get( $mid )['status'] );
delete_transient( 'ohcl_flash_1' );

$l1 = mpage( array( 'stage' => 4 ) );
$l2 = mpage( array( 'stage' => 1 ) );
check( 'Danh sách: lọc chặng 4 có hồ sơ, chặng 1 rỗng', false !== strpos( $l1, 'Cửa hàng Bình An' ) && false === strpos( $l2, 'Cửa hàng Bình An' ) );
$ov = page( 'page_overview' );
check( 'Tổng quan câu 1: 1 hội viên, 1 hoạt động, theo phân khúc "Cửa hàng"', false !== strpos( $ov, '<span>Tổng hội viên</span><b>1</b>' ) && false !== strpos( $ov, '<span>Cửa hàng</span>' ) );

// [2026-11-26 01:10 PM Johnny Chu - Chu Hoàng Anh] OHCL-Q15 — Tổng quan: mới / rời theo tháng và việc cần làm từ hồ sơ.
check( 'Tổng quan câu 1: có khối "Mới và rời theo tháng" (6 dòng tháng)', false !== strpos( $ov, 'Mới và rời theo tháng' ) && 6 === preg_match_all( '#<span>20[0-9]{2}-[0-9]{2}</span>#', $ov ) );
check( 'Tổng quan câu 5: việc từ hồ sơ có liên kết lọc theo chặng (đang dùng, chờ xác minh ⇒ stage=4)', false !== strpos( $ov, 'chờ xác minh để lên bản đồ' ) && false !== strpos( $ov, 'stage=4' ) );

// [2026-11-26 02:20 PM Johnny Chu - Chu Hoàng Anh] OHCL-H2 — Tổng quan câu 3: phễu tám quyền lợi.
check( 'Tổng quan câu 3: có khối "Quyền lợi hội viên đã nhận" đủ 8 dòng, "Cơ hội tăng trưởng" ghi "Sắp ra mắt", quyền lợi giới hạn mang nhãn', false !== strpos( $ov, 'Quyền lợi hội viên đã nhận' ) && false !== strpos( $ov, 'Cơ hội tăng trưởng' ) && false !== strpos( $ov, 'Sắp ra mắt' ) && false !== strpos( $ov, 'Giới hạn' ) && false === strpos( $ov, 'Có ở bản Coach (Đ3)' ) );

// [2026-11-26 04:07 PM Johnny Chu - Chu Hoàng Anh] OHCL-H3 — Member 360°: Hub đã mang lại gì + kết quả tự khai.
check( 'Member 360°: có thẻ "Hub đã mang lại (tháng này)" — chưa đo ⇒ "—"', false !== strpos( mpage( array( 'id' => $mid ) ), 'Hub đã mang lại (tháng này)' ) && false !== strpos( mpage( array( 'id' => $mid ) ), '<dt>Lượt xem hồ sơ</dt><dd>—</dd>' ) );
/* ---- 06 · Chứng nhận (OHCL-D2A) ---- */
// [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — Member 360° → xác minh → cấp chứng nhận (đủ tiêu chí) → màn 06 → giấy in có QR → thu hồi hai bước.
if ( ! function_exists( 'get_userdata' ) ) { function get_userdata( $id ) { return (object) array( 'display_name' => 'Leader ' . $id ); } }
function cpage( array $get ) { $_GET = $get; ob_start(); OHCL_Admin_Certs::page(); return ob_get_clean(); }
delete_transient( 'ohcl_flash_1' );
check( 'Member 360°: chưa xác minh ⇒ thẻ chứng nhận nhắc xác minh trước', false !== strpos( mpage( array( 'id' => $mid ) ), 'Xác minh hồ sơ (V3) trước' ) );
handle( 'OHCL_Admin_Members', 'handle_verify', array( 'id' => $mid, 'evidence' => array( 'gpkd' ), 'months' => 12 ) );
delete_transient( 'ohcl_flash_1' );
$one3 = mpage( array( 'id' => $mid ) );
check( 'Member 360°: đã xác minh ⇒ form cấp theo từng bộ tiêu chuẩn, đủ ô tiêu chí', false !== strpos( $one3, 'Cấp · Hàng chính hãng' ) && 2 === substr_count( $one3, 'name="criteria[]"' ) );
handle( 'OHCL_Admin_Certs', 'handle_issue', array( 'id' => $mid, 'standard' => 'hang-chinh-hang', 'criteria' => array( '0' ), 'evidence' => array( '0' ) ) );
check( 'Cấp thiếu tiêu chí ⇒ lỗi bốn trường, không có giấy', 'ohcl_cert_criteria_incomplete' === get_transient( 'ohcl_flash_1' )['error']['code'] && array() === OHCL_Certs::all() );
delete_transient( 'ohcl_flash_1' );
handle( 'OHCL_Admin_Certs', 'handle_issue', array( 'id' => $mid, 'standard' => 'hang-chinh-hang', 'criteria' => array( '0', '1' ), 'evidence' => array( '0' ), 'months' => '6', 'note' => 'đã xem kho' ) );
$cl   = OHCL_Certs::all();
$code = $cl ? $cl[0]['code'] : '';
check( 'Cấp đủ tiêu chí ⇒ thông báo mã, hành trình bậc 6', 1 === count( $cl ) && false !== strpos( get_transient( 'ohcl_flash_1' )['ok'], $code ) && false !== strpos( mpage( array( 'id' => $mid ) ), 'Đạt chuẩn chuyên ngành (6/8)' ) );
delete_transient( 'ohcl_flash_1' );
$list = cpage( array() );
check( 'Màn 06: bảng có mã, cơ sở, Còn hiệu lực; ô số liệu đúng', false !== strpos( $list, $code ) && false !== strpos( $list, 'Cửa hàng Bình An' ) && false !== strpos( $list, 'ok">Còn hiệu lực' ) && false !== strpos( $list, '<span>Còn hiệu lực</span><b>1</b>' ) );
$sheet = cpage( array( 'cert' => $cl[0]['id'] ) );
check( 'Giấy in: QR trỏ trang tra cứu, đủ tiêu chí, hồ sơ thẩm định riêng tư', false !== strpos( $sheet, 'data-ohcl-qr="https://hub.test/chung-nhan/?ma=' . $code . '"' ) && false !== strpos( $sheet, 'Có tem truy xuất' ) && false !== strpos( $sheet, 'đã xem kho' ) && false !== strpos( $sheet, 'Thu hồi giấy chứng nhận' ) );
handle( 'OHCL_Admin_Certs', 'handle_revoke', array( 'cert' => $cl[0]['id'], 'reason' => 'Bán hàng không rõ nguồn' ) );
check( 'Thu hồi chưa tích xác nhận ⇒ lỗi, giấy vẫn hiệu lực', 'ohcl_cert_revoke_unconfirmed' === get_transient( 'ohcl_flash_1' )['error']['code'] && 'valid' === OHCL_Certs::state( OHCL_Certs::get( $cl[0]['id'] ), time() ) );
delete_transient( 'ohcl_flash_1' );
handle( 'OHCL_Admin_Certs', 'handle_revoke', array( 'cert' => $cl[0]['id'], 'reason' => 'Bán hàng không rõ nguồn', 'confirm' => '1' ) );
check( 'Thu hồi có xác nhận ⇒ Đã thu hồi; lọc "Đã thu hồi" thấy giấy', 'revoked' === OHCL_Certs::state( OHCL_Certs::get( $cl[0]['id'] ), time() ) && false !== strpos( cpage( array( 'st' => 'revoked' ) ), $code ) && false === strpos( cpage( array( 'st' => 'valid' ) ), $code ) );
delete_transient( 'ohcl_flash_1' );

/* ---- 04 · Coach + công tắc khung Hub (OHCL-F2) ---- */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — duyệt hồ sơ Coach; tắt / bật "vẽ cả site trong khung Hub", danh sách trang giữ theme.
update_option( OHCL_Modules::OPTION, array( '04' => true ) );
function kpage( array $get ) { $_GET = $get; ob_start(); OHCL_Admin_Coach::page(); return ob_get_clean(); }
check( '04 · Coach trống ⇒ lời mời chia sẻ trang ứng tuyển', false !== strpos( kpage( array() ), 'Chưa có hồ sơ ứng tuyển' ) );
$cid = OHCL_Coach::save( array( 'name' => 'Lan <i>x</i>', 'skills' => array( 'ke-toan' ), 'area' => 'Huế', 'years' => 5, 'contact' => 'lan@x.vn', 'note' => '' ) );
$kp  = kpage( array() );
check( '04 · Coach: hồ sơ hiện tên đã escape, chuyên môn theo nhãn ngành, nút Duyệt', false !== strpos( $kp, 'Lan &lt;i&gt;x&lt;/i&gt;' ) && false !== strpos( $kp, 'Kế toán cửa hàng' ) && false !== strpos( $kp, '>Duyệt</button>' ) );
handle( 'OHCL_Admin_Coach', 'handle_decide', array( 'id' => $cid, 'status' => 'approved' ) );
check( 'Duyệt hồ sơ Coach ⇒ trạng thái Đã duyệt', 'approved' === OHCL_Coach::all()[0]['status'] );
delete_transient( 'ohcl_flash_1' );
handle( 'OHCL_Admin', 'handle_settings', array( 'part' => 'front', 'front_home' => '1', 'keep_theme' => '12, abc, 7' ) );
check( 'Cài đặt: bỏ tích "vẽ cả site" ⇒ tắt; ID trang giữ theme lọc số', ! OHCL_Front::takeover_on() && '12,7' === get_option( OHCL_Front::KEEP_OPTION ) );
handle( 'OHCL_Admin', 'handle_settings', array( 'part' => 'front', 'front_home' => '1', 'front_takeover' => '1', 'keep_theme' => '' ) );
check( 'Cài đặt: tích lại ⇒ bật', OHCL_Front::takeover_on() && false !== strpos( page( 'page_settings' ), 'name="front_takeover" value="1" checked' ) );
delete_transient( 'ohcl_flash_1' );

/* màu nhấn (OHCL-FIX3: chọn màu rồi Lưu phải giữ màu) */
// [2026-11-26 01:11 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX3
handle( 'OHCL_Admin', 'handle_settings', array( 'part' => 'front', 'front_home' => '1', 'front_takeover' => '1', 'accent' => '#1F7A3A' ) );
check( 'chọn màu xanh, không tích "trả về" ⇒ lưu màu xanh', '#1f7a3a' === OHCL_Industry::owner_accent() && '#1f7a3a' === OHCL_Industry::current()['brand']['accent'] );
check( 'ô "Trả về màu của plugin ngành" không tích sẵn', false === strpos( page( 'page_settings' ), 'name="accent_reset" value="1" checked' ) );
handle( 'OHCL_Admin', 'handle_settings', array( 'part' => 'front', 'front_home' => '1', 'front_takeover' => '1', 'accent' => '#1f7a3a', 'accent_reset' => '1' ) );
check( 'tích "trả về" ⇒ bỏ màu riêng', '' === OHCL_Industry::owner_accent() );
delete_transient( 'ohcl_flash_1' );

echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
