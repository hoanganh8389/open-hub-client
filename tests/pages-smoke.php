<?php
/**
 * Smoke test of every portal page of the registry (F2): menu, footer, module switches, takeover frame for
 * WordPress views, My Hub (linking, roadmap, profile edit, support), coach applications.
 * Run: php tests/pages-smoke.php — exit 0 = pass.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — chạy thử đủ trang của sổ trang, khung WordPress, My Hub, ứng tuyển Coach với WordPress giả.

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
function wp_head() { echo '<!--wp_head-->'; }
function wp_footer() { echo '<!--wp_footer-->'; }
function wp_body_open() {}
function body_class( $c ) { echo 'class="' . $c . '"'; }
function wp_login_url( $r ) { return 'https://hub.test/wp-login.php'; }
function get_posts( array $a ) { if ( isset( $a['post_type'] ) && 'post' === $a['post_type'] ) { return $GLOBALS['wp_posts']; } return array_reverse( array_values( array_filter( $GLOBALS['posts'], function ( $p ) use ( $a ) { return $p->post_type === $a['post_type']; } ) ) ); }
function get_post( $id ) { return isset( $GLOBALS['posts'][ $id ] ) ? $GLOBALS['posts'][ $id ] : null; }
function wp_insert_post( array $a ) { $id = count( $GLOBALS['posts'] ) + 201; $GLOBALS['posts'][ $id ] = (object) array( 'ID' => $id, 'post_type' => $a['post_type'], 'post_title' => $a['post_title'] ); return $id; }
function wp_update_post( array $a ) { $GLOBALS['posts'][ $a['ID'] ]->post_title = $a['post_title']; return $a['ID']; }
function get_post_meta( $id, $k, $s ) { return isset( $GLOBALS['meta'][ $id ][ $k ] ) ? $GLOBALS['meta'][ $id ][ $k ] : ''; }
function update_post_meta( $id, $k, $v ) { $GLOBALS['meta'][ $id ][ $k ] = $v; return true; }
function get_current_user_id() { return 1; }
function get_permalink( $p ) { return 'https://hub.test/tin/' . ( is_object( $p ) ? $p->ID : (int) $p ) . '/'; }
function has_post_thumbnail( $p = null ) { return false; }
function get_the_title( $p ) { return $p->post_title; }
function get_the_date( $f = "", $p = null ) { return '25/11/2026'; }
function wp_safe_redirect( $u ) { throw new Redirected( $u ); }

$dir = dirname( __DIR__ );
define( 'OHCL_DIR', $dir . '/' );
foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview', 'class-ohcl-members', 'class-ohcl-service', 'class-ohcl-benefits', 'class-ohcl-events', 'class-ohcl-audit', 'class-ohcl-export', 'class-ohcl-certs', 'class-ohcl-coach', 'class-ohcl-myhub', 'class-ohcl-front' ) as $f ) {
	require $dir . '/includes/' . $f . '.php';
}
require $dir . '/examples/wp-sample-hub/wp-sample-hub.php';

// WordPress loop / user stubs for the takeover frame and My Hub.
$GLOBALS['user'] = null; $GLOBALS['loop'] = array(); $GLOBALS['loop_i'] = -1; $GLOBALS['view'] = array();
function is_user_logged_in() { return null !== $GLOBALS['user']; }
function wp_get_current_user() { return $GLOBALS['user']; }
function get_userdata( $id ) { return (object) array( 'display_name' => 'U' . $id ); }
function v( $k ) { return ! empty( $GLOBALS['view'][ $k ] ); }
function is_home() { return v( 'home' ); } function is_archive() { return v( 'archive' ); } function is_search() { return v( 'search' ); } function is_404() { return v( '404' ); }
function is_singular( $t = '' ) { return v( 'single' ); } function is_category() { return false; } function get_search_query() { return '<x>'; }
function get_the_archive_title() { return 'Chuyên mục A'; } function get_the_archive_description() { return ''; }
function have_posts() { return $GLOBALS['loop_i'] + 1 < count( $GLOBALS['loop'] ); }
function the_post() { $GLOBALS['loop_i']++; }
function cur() { return $GLOBALS['loop'][ $GLOBALS['loop_i'] ]; }
function the_title() { echo esc_html( cur()['title'] ); } function the_permalink() { echo 'https://hub.test/p/' . cur()['id']; }
function the_content() { echo cur()['content']; } function get_post_type() { return cur()['type']; }
function get_the_excerpt() { return 'Tóm tắt'; } function wp_trim_words( $t, $n ) { return $t; } function wp_link_pages() {} function paginate_links( $a ) { return ''; }
function the_post_thumbnail() {} function wp_kses_post( $s ) { return $s; } function wp_strip_all_tags( $s ) { return strip_tags( $s ); }
function get_category_by_slug( $s ) { return null; }
function sanitize_textarea_field( $s ) { return trim( strip_tags( (string) $s ) ); }

$pass = 0; $fail = 0;
function check( $name, $ok ) { global $pass, $fail; if ( $ok ) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; } }

$now_s = gmdate( 'Y-m-d H:i:s' );
OHCL_Core_Client::$config    = array( 'base_url' => 'https://bizcity.test', 'hub' => 'sample', 'secret' => 'ohs_' . str_repeat( 'a', 32 ) );
OHCL_Core_Client::$transport = function () use ( $now_s ) {
	return array( 'status' => 200, 'body' => json_encode( array( 'items' => array( array( 'id' => 11, 'status' => 'active', 'plan_code' => 'ai_std', 'plan_until' => '2030-01-01 00:00:00', 'last_used_at' => $now_s, 'created_at' => $now_s, 'key_prefix' => 'biz-…1111', 'label' => '', 'site_url' => '', 'coach_ref' => '' ) ), 'page' => 1 ) ) );
};
$cfg = OHCL_Industry::current();
function mk( $title, array $meta ) { $id = wp_insert_post( array( 'post_type' => 'bz_hub_member', 'post_title' => $title ) ); foreach ( $meta as $k => $v ) { update_post_meta( $id, $k, $v ); } return $id; }
$verify = array( 'by' => 1, 'at' => $now_s, 'until' => '2030-01-01 00:00:00', 'evidence' => array( 'gpkd' ), 'note' => '' );
$a = mk( 'Cửa hàng An', array( '_bzm_status' => 'active', '_bzm_key_id' => 11, '_bzm_accepted_at' => $now_s, '_bzm_joined_at' => gmdate( 'Y-m-d H:i:s', time() - 20 * 86400 ), '_bzm_verify' => $verify, '_bzm_public_consent' => $now_s, '_bzm_lat' => '21.02', '_bzm_lng' => '105.85', '_bzm_segment' => 'cua-hang', '_bzm_province' => 'Hà Nội', '_bzm_email' => 'an@an.vn', '_bzm_phone' => '0912000111' ) );
$b = mk( 'Cửa hàng Bình', array( '_bzm_status' => 'active', '_bzm_email' => 'trung@x.vn' ) );
$c = mk( 'Cửa hàng Cường', array( '_bzm_status' => 'active', '_bzm_email' => 'trung@x.vn' ) );

/* sổ trang + menu — bật đủ module tuỳ chọn để thấy mọi trang */
update_option( OHCL_Modules::OPTION, array( '04' => true, '05' => true, '06' => true, '07' => true, '09' => true ) );
$keys = array_keys( OHCL_Front::pages() );
check( 'sổ trang có đủ 14 trang của sitemap §12 + My Hub', ! array_diff( array( 'home', 'map', 'trade', 'events', 'place', 'benefits', 'tech', 'join', 'academy', 'coaches', 'becoach', 'cert', 'myhub', 'sitemap' ), $keys ) );
foreach ( $keys as $k ) {
	if ( ! is_file( OHCL_DIR . 'templates/front/' . $k . '.php' ) ) { check( 'có tệp mẫu cho ' . $k, false ); }
}
check( 'mỗi trang trong sổ có tệp mẫu riêng', true );
$menu = OHCL_Front::menu();
check( 'menu ≤ 6 nhóm (R-LEAN-4), có Khám phá / Hội viên / Học & đồng hành / Chứng nhận', count( $menu ) <= 6 && isset( $menu['Khám phá'], $menu['Hội viên'], $menu['Học & đồng hành'], $menu['Chứng nhận'] ) );

/* render từng trang */
$_GET = array();
$html = array();
foreach ( array( 'home', 'map', 'trade', 'events', 'benefits', 'tech', 'join', 'academy', 'coaches', 'becoach', 'cert', 'myhub', 'sitemap' ) as $k ) {
	$html[ $k ] = OHCL_Front::html( $k, array() );
}
$ok = true;
foreach ( $html as $k => $h ) {
	$ok = $ok && false !== strpos( $h, 'class="nav"' ) && false !== strpos( $h, 'POWERED BY BIZCITY OPEN' ) && false !== strpos( $h, '</html>' );
}
check( '13 trang dựng được, đủ đầu trang có menu + chân trang', $ok );
check( 'menu lớn: nhóm mở bằng details, có mô tả trang', false !== strpos( $html['home'], '<details class="dd' ) && false !== strpos( $html['home'], 'Nguồn cung, nhà sản xuất' ) );
check( 'chân trang liệt kê trang của mọi nhóm + Sơ đồ trang', false !== strpos( $html['home'], '<h4>Học &amp; đồng hành</h4>' ) && false !== strpos( $html['home'], 'https://hub.test/so-do-trang/' ) );
check( 'Quyền lợi: 8 trụ cột, thuật ngữ ngành', 8 === substr_count( $html['benefits'], 'class="pnl tile"' ) && false !== strpos( $html['benefits'], 'Làm chủ doanh nghiệp' ) );
check( 'Công nghệ 0đ: ứng dụng bật sẵn theo hợp đồng ngành (4) + 0 đ, không bịa giá AI', 4 === substr_count( $html['tech'], 'Bật sẵn</span>' ) && false !== strpos( $html['tech'], '<b>0 đ</b>' ) && false === strpos( $html['tech'], '000 đ' ) );
check( 'Giao thương: chỉ cơ sở V4, xếp theo loại hình', false !== strpos( $html['trade'], 'Cửa hàng An' ) && false === strpos( $html['trade'], 'Cửa hàng Bình' ) && false !== strpos( $html['trade'], '· 1 cơ sở' ) );
check( 'Sự kiện: chương trình theo dịp từ hợp đồng; chưa có bài ⇒ trạng thái trống', false !== strpos( $html['events'], 'Tuần lễ ngành' ) && false !== strpos( $html['events'], 'Chưa có sự kiện nào được đăng' ) );
check( 'Học viện: lộ trình + kho mẫu từ hợp đồng', false !== strpos( $html['academy'], 'Chuyển đổi số 30 ngày' ) && false !== strpos( $html['academy'], 'SOP nhập hàng' ) );
check( 'Coach: chưa có Coach duyệt ⇒ "—", không bịa số', false !== strpos( $html['coaches'], '<p class="big">—</p>' ) );
check( 'Trở thành Coach: form có nonce, bẫy bot, chuyên môn từ hợp đồng', false !== strpos( $html['becoach'], 'name="_ohcl_coach"' ) && false !== strpos( $html['becoach'], 'name="website_url_2"' ) && false !== strpos( $html['becoach'], 'value="ke-toan"' ) );
check( 'Sơ đồ trang: liệt kê nhóm + My Hub', false !== strpos( $html['sitemap'], 'Kết nối giao thương' ) && false !== strpos( $html['sitemap'], 'https://hub.test/my-hub/' ) );
check( 'trang chủ: lối tắt Quyền lợi trỏ /quyen-loi/', false !== strpos( $html['home'], 'https://hub.test/quyen-loi/' ) );

/* module tắt */
update_option( OHCL_Modules::OPTION, array( '04' => false, '09' => false ) );
$m2 = OHCL_Front::menu();
$h2 = OHCL_Front::html( 'home', array() );
check( 'module 04, 09 tắt ⇒ Coach, Giao thương, Sự kiện biến khỏi menu và chân trang', ! OHCL_Front::page_on( 'coaches' ) && ! OHCL_Front::page_on( 'trade' ) && ! isset( $m2['Học & đồng hành']['becoach'] ) && false === strpos( $h2, '/giao-thuong/' ) && false === strpos( $h2, '/tro-thanh-coach/' ) );
update_option( OHCL_Modules::OPTION, array( '04' => true, '09' => true ) );

/* khung WordPress (takeover) */
check( 'thay toàn bộ: bật mặc định; bỏ qua feed, embed, admin, Woo shop / sản phẩm, trang giữ theme', OHCL_Front::takeover_on() && OHCL_Front::should_take( array( 'on' => true ) ) && ! OHCL_Front::should_take( array( 'on' => true, 'woo' => true ) ) && ! OHCL_Front::should_take( array( 'on' => true, 'feed' => true ) ) && ! OHCL_Front::should_take( array( 'on' => true, 'keep' => true ) ) && ! OHCL_Front::should_take( array( 'on' => false ) ) );
update_option( OHCL_Front::KEEP_OPTION, '12, 7,x' );
check( 'danh sách trang giữ theme đọc đúng ID', array( 12, 7 ) === array_values( OHCL_Front::keep_theme_ids() ) );
$GLOBALS['view'] = array( 'single' => true ); $GLOBALS['loop'] = array( array( 'id' => 5, 'title' => 'Giới thiệu <i>Hub</i>', 'content' => '<p>Nội dung [shortcode đã chạy]</p>', 'type' => 'page' ) ); $GLOBALS['loop_i'] = -1;
$wp1 = OHCL_Front::html( 'wp', array() );
check( 'trang WordPress: tiêu đề escape, nội dung qua the_content, trong khung Hub', false !== strpos( $wp1, 'Giới thiệu &lt;i&gt;Hub&lt;/i&gt;' ) && false !== strpos( $wp1, 'Nội dung [shortcode đã chạy]' ) && false !== strpos( $wp1, 'class="nav"' ) && false !== strpos( $wp1, 'class="pnl prose"' ) );
$GLOBALS['view'] = array( 'search' => true ); $GLOBALS['loop'] = array(); $GLOBALS['loop_i'] = -1;
$wp2 = OHCL_Front::html( 'wp', array() );
check( 'tìm kiếm không có kết quả ⇒ trạng thái trống, từ khoá đã escape', false !== strpos( $wp2, 'Chưa có bài nào' ) && false !== strpos( $wp2, '&lt;x&gt;' ) );
$GLOBALS['view'] = array( '404' => true );
check( '404 trong khung Hub, có ô tìm + Sơ đồ trang', false !== strpos( OHCL_Front::html( 'wp', array() ), 'Không tìm thấy trang' ) );
$GLOBALS['view'] = array( 'home' => true ); $GLOBALS['loop'] = array( array( 'id' => 8, 'title' => 'Tin 1', 'content' => '', 'type' => 'post' ) ); $GLOBALS['loop_i'] = -1;
check( 'Tin tức (blog) ⇒ lưới bài trong khung Hub', false !== strpos( OHCL_Front::html( 'wp', array() ), 'class="pnl post"' ) );
$GLOBALS['view'] = array();

/* My Hub */
$GLOBALS['user'] = null;
check( 'My Hub khi chưa đăng nhập ⇒ mời đăng nhập', false !== strpos( OHCL_Front::html( 'myhub', array() ), 'Đăng nhập để vào My Hub' ) );
$all = OHCL_Members::all();
check( 'gắn hồ sơ: email trùng duy nhất ⇒ gắn; trùng 2 hồ sơ ⇒ không đoán', $a === OHCL_MyHub::pick( $all, 5, 'AN@an.vn' )['id'] && OHCL_MyHub::pick( $all, 5, 'AN@an.vn' )['link'] && 'ambiguous' === OHCL_MyHub::pick( $all, 6, 'trung@x.vn' )['why'] && 'none' === OHCL_MyHub::pick( $all, 7, 'khac@x.vn' )['why'] );
update_post_meta( $b, '_bzm_user', 9 );
check( 'hồ sơ đã thuộc tài khoản khác ⇒ không gắn lại', 'taken' === OHCL_MyHub::pick( array( OHCL_Members::get( $b ) ), 6, 'trung@x.vn' )['why'] );
$GLOBALS['user'] = (object) array( 'ID' => 5, 'user_email' => 'an@an.vn', 'display_name' => 'An' );
$my = OHCL_Front::html( 'myhub', array( 'tab' => '' ) );
check( 'My Hub: tự gắn theo email, hiện hồ sơ, Verified, 0 đ, số chưa đo là "—"', 5 === (int) $GLOBALS['meta'][ $a ]['_bzm_user'] && false !== strpos( $my, '<h1>Cửa hàng An</h1>' ) && false !== strpos( $my, 'Verified' ) && false !== strpos( $my, '<b>0 đ</b>' ) && false !== strpos( $my, '<b>—</b>' ) );
check( 'My Hub: không lộ khoá đầy đủ, không lộ điện thoại nội bộ', false === strpos( $my, '0912000111' ) );
$road = OHCL_MyHub::road( OHCL_Members::get( $a ), array( 'status' => 'active', 'last_used_at' => $now_s ), time() );
check( 'lộ trình: ngày 21, Khởi tạo chưa xong vì chưa có Coach (tính từ dữ liệu thật)', 21 === $road['day'] && 0 === $road['phase'] && false === $road['phases'][0]['steps'][3]['done'] && true === $road['phases'][1]['steps'][1]['done'] );
$rp = OHCL_Front::html( 'myhub', array( 'tab' => 'lo-trinh' ) );
check( 'trang lộ trình: bốn chặng, ngày 21/90', 4 === substr_count( $rp, '<small>Ngày ' ) && false !== strpos( $rp, 'ngày 21/90' ) );
$pf = OHCL_Front::html( 'myhub', array( 'tab' => 'ho-so' ) );
check( 'mục Hồ sơ: form có nonce, ô đồng ý đang tích, bậc bản đồ', false !== strpos( $pf, 'name="_ohcl_myhub"' ) && false !== strpos( $pf, 'name="public_consent" value="1" checked' ) && false !== strpos( $pf, 'V4 · Công khai' ) );
foreach ( array( 'cong-nghe', 'chung-nhan', 'ho-tro' ) as $t ) { $x = OHCL_Front::html( 'myhub', array( 'tab' => $t ) ); if ( false === strpos( $x, '</html>' ) ) { check( 'mục ' . $t, false ); } }
check( 'mục Công nghệ, Chứng nhận, Hỗ trợ dựng được', true );
// [2026-11-26 02:20 PM Johnny Chu - Chu Hoàng Anh] OHCL-H2 — tab "Quyền lợi của tôi" và tóm tắt ở Tổng quan.
$bn = OHCL_Front::html( 'myhub', array( 'tab' => 'quyen-loi' ) );
check( 'My Hub › Quyền lợi của tôi: có mục trong menu bên, 7 thẻ quyền lợi hiện có + thẻ "Sắp ra mắt" cho Cơ hội tăng trưởng; không có "Hub chưa bật" lọt ra', false !== strpos( $bn, 'Quyền lợi của tôi' ) && 8 === substr_count( $bn, 'class="pnl bn bn-' ) && false !== strpos( $bn, 'Sắp ra mắt' ) && false !== strpos( $bn, 'Cơ hội tăng trưởng' ) );
check( 'My Hub › Quyền lợi: mỗi thẻ nói lý do, thẻ giới hạn mang nhãn "Giới hạn", có dòng quyền làm chủ', false !== strpos( $bn, 'class="tag idle">Giới hạn' ) && false !== strpos( $bn, 'Quyền làm chủ của bạn' ) );
check( 'My Hub › Tổng quan: có tóm tắt "quyền lợi bạn đã nhận" và liên kết sang tab', false !== strpos( $my, 'quyền lợi bạn đã nhận' ) && false !== strpos( $my, 'quyen-loi' ) );
// [2026-11-26 04:07 PM Johnny Chu - Chu Hoàng Anh] OHCL-H3
$kq = OHCL_Front::html( 'myhub', array( 'tab' => 'ket-qua' ) );
check( 'My Hub › Kết quả của tôi: có trong menu, form 3 dòng trước / sau, ô đồng ý, nonce, nói rõ "bạn tự khai"', false !== strpos( $kq, 'Kết quả của tôi' ) && 3 === substr_count( $kq, 'name="outcomes[' ) / 4 && false !== strpos( $kq, 'name="outcomes_consent"' ) && false !== strpos( $kq, 'name="_ohcl_myhub"' ) && false !== strpos( $kq, 'tự khai' ) );
check( 'My Hub › Tổng quan: chưa đo ⇒ lượt xem / liên hệ hiện "—" và có câu nói chưa đo', false !== strpos( $my, '<span>Lượt xem hồ sơ</span><b>—</b>' ) && false !== strpos( $my, 'Chưa đo' ) );
// [2026-11-26 04:36 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6
$dl = OHCL_Front::html( 'myhub', array( 'tab' => 'du-lieu' ) );
check( 'My Hub › Dữ liệu của tôi: nút tải JSON, giải thích rời Hub, ô xác nhận + nút Rời Hub, nonce; không dùng confirm() của trình duyệt', false !== strpos( $dl, 'Dữ liệu của tôi' ) && false !== strpos( $dl, 'name="action" value="ohcl_myhub_export"' ) && false !== strpos( $dl, 'name="action" value="ohcl_myhub_leave"' ) && false !== strpos( $dl, 'name="confirm"' ) && false !== strpos( $dl, 'name="_ohcl_myhub"' ) && false === strpos( $dl, 'confirm(' ) );
check( 'My Hub › Quyền lợi: không lộ điện thoại nội bộ / khoá đầy đủ', false === strpos( $bn, '0912000111' ) && false === strpos( $bn, 'biz-' . str_repeat( 'f', 32 ) ) );
function my_post( $action, array $p ) { $_POST = $p + array( '_ohcl_myhub' => 'nonce-ok' ); try { call_user_func( array( 'OHCL_MyHub', $action ) ); } catch ( Redirected $r ) { return $r->getMessage(); } return ''; }
$r = my_post( 'handle_profile', array( 'address' => '<b>12 Phố Huế</b>', 'hours' => '8-22', 'site_url' => 'javascript:x', 'contact_zalo' => '0912 333 444' ) );
check( 'sửa hồ sơ: website sai dạng ⇒ lỗi, không lưu', false !== strpos( $r, 'err=ohcl_member_url_invalid' ) && '' === get_post_meta( $a, '_bzm_address', true ) );
$r = my_post( 'handle_profile', array( 'address' => '<b>12 Phố Huế</b>', 'hours' => '8-22', 'site_url' => 'https://an.vn', 'contact_zalo' => '0912 333 444' ) );
check( 'sửa hồ sơ: lưu (bỏ thẻ, chuẩn hoá Zalo); bỏ tích đồng ý ⇒ rời bản đồ', false !== strpos( $r, 'saved=1' ) && '12 Phố Huế' === get_post_meta( $a, '_bzm_address', true ) && '0912333444' === get_post_meta( $a, '_bzm_contact_zalo', true ) && '' === get_post_meta( $a, '_bzm_public_consent', true ) );
$r = my_post( 'handle_support', array( 'text' => 'Cần hướng dẫn cài POS cho quầy 2' ) );
check( 'hỗ trợ: lưu vào hồ sơ hội viên', false !== strpos( $r, 'sent=1' ) && 1 === count( get_post_meta( $a, '_bzm_support', true ) ) );
check( 'hỗ trợ: nonce sai ⇒ không lưu', false !== strpos( my_post( 'handle_support', array( '_ohcl_myhub' => 'sai', 'text' => 'Yêu cầu thứ hai dài' ) ), 'err=ohcl_join_expired' ) && 1 === count( get_post_meta( $a, '_bzm_support', true ) ) );
$GLOBALS['user'] = (object) array( 'ID' => 6, 'user_email' => 'trung@x.vn', 'display_name' => 'T' );
check( 'tài khoản có 2 hồ sơ cùng email ⇒ My Hub giải thích, không sửa được', false !== strpos( OHCL_Front::html( 'myhub', array() ), 'Có nhiều hồ sơ cùng email' ) && false !== strpos( my_post( 'handle_profile', array( 'address' => 'x' ) ), 'err=' ) );
$GLOBALS['user'] = null;

/* ứng tuyển Coach */
function coach_post( array $p, $ip = '4.4.4.4' ) { $_POST = $p + array( '_ohcl_coach' => 'nonce-ok' ); $_SERVER['REMOTE_ADDR'] = $ip; try { OHCL_Front::handle_coach_apply(); } catch ( Redirected $r ) { return $r->getMessage(); } return ''; }
check( 'Coach: thiếu chuyên môn ⇒ lỗi', false !== strpos( coach_post( array( 'name' => 'Lan', 'contact' => 'lan@x.vn' ) ), 'err=ohcl_coach_skill_required' ) );
check( 'Coach: chuyên môn lạ bị bỏ ⇒ vẫn thiếu', false !== strpos( coach_post( array( 'name' => 'Lan', 'contact' => 'lan@x.vn', 'skills' => array( 'hack' ) ), '4.4.4.5' ), 'err=ohcl_coach_skill_required' ) );
$r = coach_post( array( 'name' => '<b>Lan</b>', 'contact' => 'lan@x.vn', 'skills' => array( 'ke-toan' ), 'years' => '99', 'area' => 'Huế' ), '4.4.4.6' );
$apps = OHCL_Coach::all();
check( 'Coach: hồ sơ hợp lệ ⇒ lưu trạng thái Mới, bỏ thẻ, năm ≤ 60', false !== strpos( $r, 'sent=1' ) && 1 === count( $apps ) && 'Lan' === $apps[0]['name'] && 'new' === $apps[0]['status'] && 60 === $apps[0]['years'] );
OHCL_Coach::decide( $apps[0]['id'], 'approved' );
check( 'Coach: duyệt ⇒ trang Coach hiện số thật 1', 1 === OHCL_Coach::approved_count() && false !== strpos( OHCL_Front::html( 'coaches', array() ), '<p class="big">1</p>' ) );
for ( $i = 0; $i < 3; $i++ ) { coach_post( array( 'name' => 'S' . $i, 'contact' => 'x', 'skills' => array( 'ke-toan' ) ), '3.3.3.3' ); }
check( 'Coach: quá 3 lần / giờ / IP ⇒ chặn', false !== strpos( coach_post( array( 'name' => 'S9', 'contact' => 'x', 'skills' => array( 'ke-toan' ) ), '3.3.3.3' ), 'err=ohcl_report_rate_limited' ) );

echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
