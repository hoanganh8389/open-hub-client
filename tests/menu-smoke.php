<?php
/**
 * Smoke test of the WordPress pages + "Menu Hub" (F3): page templates, create missing pages, links to the
 * published page, menu build idempotent, theme location never overwritten, header / footer read the menu.
 * Run: php tests/menu-smoke.php — exit 0 = pass.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:53 PM Johnny Chu - Chu Hoàng Anh] OHCL-F3 — chạy thử tạo trang WordPress + dựng "Menu Hub" với WordPress giả.

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
function get_posts( array $a ) { if ( isset( $a['post_type'] ) && 'post' === $a['post_type'] ) { return $GLOBALS['wp_posts']; } $r = array_values( array_filter( $GLOBALS['posts'], function ( $p ) use ( $a ) { return $p->post_type === $a['post_type']; } ) ); return 'page' === $a['post_type'] ? $r : array_reverse( $r ); }
function get_post( $id ) { return isset( $GLOBALS['posts'][ $id ] ) ? $GLOBALS['posts'][ $id ] : null; }
function wp_insert_post( array $a ) { $id = count( $GLOBALS['posts'] ) + 201; $GLOBALS['posts'][ $id ] = (object) array( 'ID' => $id, 'post_type' => $a['post_type'], 'post_title' => $a['post_title'], 'post_status' => isset( $a['post_status'] ) ? $a['post_status'] : 'publish', 'post_name' => isset( $a['post_name'] ) ? $a['post_name'] : '' ); foreach ( isset( $a['meta_input'] ) ? $a['meta_input'] : array() as $k => $v ) { $GLOBALS['meta'][ $id ][ $k ] = $v; } return $id; }
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
foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview', 'class-ohcl-members', 'class-ohcl-certs', 'class-ohcl-coach', 'class-ohcl-myhub', 'class-ohcl-pages', 'class-ohcl-front', 'class-ohcl-admin', 'class-ohcl-admin-pages' ) as $f ) {
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

// Menu / page stubs.
$GLOBALS['menus'] = array(); $GLOBALS['mitems'] = array(); $GLOBALS['locs'] = array( 'top' => 77 ); $GLOBALS['is_page'] = 0;
function wp_get_nav_menu_object( $m ) { foreach ( $GLOBALS['menus'] as $id => $name ) { if ( $id === $m || $name === $m ) { return (object) array( 'term_id' => $id, 'name' => $name ); } } return 77 === $m ? (object) array( 'term_id' => 77, 'name' => 'Theme menu' ) : false; }
function wp_create_nav_menu( $name ) { $id = 500 + count( $GLOBALS['menus'] ); $GLOBALS['menus'][ $id ] = $name; return $id; }
function wp_get_nav_menu_items( $m ) { return array_values( array_filter( $GLOBALS['mitems'], function ( $i ) use ( $m ) { return $i->menu === $m; } ) ); }
function wp_update_nav_menu_item( $m, $id, array $a ) { $nid = 900 + count( $GLOBALS['mitems'] ); $GLOBALS['mitems'][ $nid ] = (object) array( 'ID' => $nid, 'menu' => $m, 'title' => $a['menu-item-title'], 'menu_item_parent' => $a['menu-item-parent-id'], 'type' => $a['menu-item-type'], 'object_id' => isset( $a['menu-item-object-id'] ) ? $a['menu-item-object-id'] : 0, 'url' => isset( $a['menu-item-url'] ) ? $a['menu-item-url'] : get_permalink( $a['menu-item-object-id'] ) ); return $nid; }
function get_registered_nav_menus() { return array( 'top' => 'Top bar', 'primary' => 'Primary' ); }
function get_nav_menu_locations() { return $GLOBALS['locs']; }
function set_theme_mod( $k, $v ) { if ( 'nav_menu_locations' === $k ) { $GLOBALS['locs'] = $v; } }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function is_page() { return (bool) $GLOBALS['is_page']; }
function get_queried_object_id() { return $GLOBALS['is_page']; }
function wp_doing_ajax() { return false; }
function current_user_can( $c ) { return true; }
function get_edit_post_link( $id ) { return 'https://hub.test/wp-admin/post.php?post=' . $id; }

$pass = 0; $fail = 0;
function check( $name, $ok ) { global $pass, $fail; if ( $ok ) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; } }

/* mẫu trang */
$tpl = OHCL_Pages::page_templates( array( 'theme.php' => 'Theme' ) );
check( 'mẫu trang "Open Hub — …" cho 13 trang Hub (không có trang chủ, hồ sơ cơ sở), giữ mẫu của theme', 14 === count( $tpl ) && 'Open Hub — Quyền lợi hội viên' === $tpl['open-hub:benefits'] && ! isset( $tpl['open-hub:home'] ) && ! isset( $tpl['open-hub:place'] ) );

/* tạo trang */
$made = OHCL_Pages::create_missing( 'publish' );
check( 'tạo trang: chỉ trang có module bật (mẫu ngành tắt 09 ⇒ không tạo Giao thương, Sự kiện)', isset( $made['benefits'], $made['myhub'], $made['cert'] ) && ! isset( $made['trade'] ) && ! isset( $made['events'] ) );
$p = $GLOBALS['posts'][ $made['benefits'] ];
check( 'trang tạo ra: đã đăng, đúng tên, đường dẫn, mẫu trang', 'publish' === $p->post_status && 'Quyền lợi hội viên' === $p->post_title && 'quyen-loi' === $p->post_name && 'open-hub:benefits' === $GLOBALS['meta'][ $made['benefits'] ]['_wp_page_template'] );
check( 'chạy lại không tạo trùng', array() === OHCL_Pages::create_missing( 'publish' ) );
check( 'link Hub trỏ về trang WordPress đã đăng', 'https://hub.test/tin/' . $made['benefits'] . '/' === OHCL_Front::url( 'benefits' ) );
$GLOBALS['is_page'] = $made['cert'];
check( 'trang WordPress dùng mẫu ⇒ định tuyến nhận ra trang Hub', 'cert' === OHCL_Pages::key_of( get_queried_object_id() ) );
$GLOBALS['is_page'] = 0;
$draft = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Nháp', 'post_name' => 'nhap', 'meta_input' => array( '_wp_page_template' => 'open-hub:sitemap' ) ) );
OHCL_Pages::forget();
check( 'trang nháp không thay link (chỉ trang đã đăng)', $made['sitemap'] === OHCL_Pages::page_id( 'sitemap' ) );

/* menu */
$r = OHCL_Pages::sync_menu( '' );
$items = wp_get_nav_menu_items( $r['menu_id'] );
$titles = array_map( function ( $i ) { return $i->title; }, $items );
check( 'tạo "Menu Hub": Trang chủ + nhóm + trang con', 'Menu Hub' === $GLOBALS['menus'][ $r['menu_id'] ] && in_array( 'Trang chủ', $titles, true ) && in_array( 'Hội viên', $titles, true ) && in_array( 'Quyền lợi hội viên', $titles, true ) && in_array( 'Chứng nhận', $titles, true ) );
$kid = null; $grp = null;
foreach ( $items as $i ) { if ( 'Quyền lợi hội viên' === $i->title ) { $kid = $i; } if ( 'Hội viên' === $i->title ) { $grp = $i; } }
check( 'trang con nằm dưới nhóm, là mục kiểu trang (object_id = trang WordPress)', $kid && $grp && (int) $kid->menu_item_parent === (int) $grp->ID && 'post_type' === $kid->type && $made['benefits'] === (int) $kid->object_id );
check( 'không gán vị trí khi không chọn', array( 'top' => 77 ) === $GLOBALS['locs'] );
wp_update_nav_menu_item( $r['menu_id'], 0, array( 'menu-item-title' => 'Liên hệ', 'menu-item-parent-id' => 0, 'menu-item-type' => 'custom', 'menu-item-url' => 'https://hub.test/lien-he/' ) );
$n = count( wp_get_nav_menu_items( $r['menu_id'] ) );
$r2 = OHCL_Pages::sync_menu( '' );
check( 'cập nhật lại: không thêm trùng, giữ mục chủ Hub tự thêm', 0 === $r2['added'] && $n === count( wp_get_nav_menu_items( $r['menu_id'] ) ) && $r2['menu_id'] === $r['menu_id'] );
check( 'vị trí trống đầu tiên là "primary" (không đè "top" đang có menu)', 'primary' === OHCL_Admin_Pages::free_location() );
$r3 = OHCL_Pages::sync_menu( 'primary' );
check( 'gán vị trí "primary", vị trí "top" giữ menu cũ', $r['menu_id'] === $GLOBALS['locs']['primary'] && 77 === $GLOBALS['locs']['top'] && 'primary' === $r3['location'] );
check( 'vị trí không có trong theme ⇒ bỏ qua', '' === OHCL_Pages::sync_menu( 'khong-co' )['location'] );

/* đầu trang đọc menu */
$GLOBALS['mitems'][ $kid->ID ]->title = 'Quyền lợi (đổi tên trong Menu)';
$h = OHCL_Front::html( 'benefits', array() );
check( 'đầu trang đọc "Menu Hub": tên chủ Hub đổi hiện ra, mục tự thêm hiện ra, mô tả trang giữ', false !== strpos( $h, 'Quyền lợi (đổi tên trong Menu)' ) && false !== strpos( $h, '>Liên hệ</a>' ) && false !== strpos( $h, '8 quyền lợi đặc biệt khi tham gia Hub.' ) );
check( 'mục của trang đang mở được đánh dấu', false !== strpos( $h, 'aria-current="page"><b>Quyền lợi (đổi tên trong Menu)' ) );
check( 'chân trang cùng cây menu (cột nhóm + cột Trang)', false !== strpos( $h, '<h4>Hội viên</h4>' ) && false !== strpos( $h, '<h4>Trang</h4>' ) );
update_option( OHCL_Modules::OPTION, array( '06' => false ) );
$h2 = OHCL_Front::html( 'home', array() );
check( 'module 06 tắt ⇒ mục Chứng nhận ẩn khỏi đầu trang, menu WordPress giữ nguyên', false === strpos( $h2, '>Chứng nhận</a>' ) && in_array( 'Chứng nhận', array_map( function ( $i ) { return $i->title; }, wp_get_nav_menu_items( $r['menu_id'] ) ), true ) );
update_option( OHCL_Modules::OPTION, array() );

/* chạy tự động một lần */
$GLOBALS['opts'] = array_diff_key( $GLOBALS['opts'], array( 'ohcl_pages_seeded' => 1 ) );
$before = count( $GLOBALS['posts'] );
OHCL_Admin_Pages::seed_once();
check( 'tự chạy lần đầu: đặt cờ, báo kết quả, không tạo trùng trang', '1' === get_option( 'ohcl_pages_seeded' ) && $before === count( $GLOBALS['posts'] ) && false !== strpos( get_transient( 'ohcl_flash_1' )['ok'], 'Menu Hub' ) );
delete_transient( 'ohcl_flash_1' );
OHCL_Admin_Pages::seed_once();
check( 'lần sau không chạy lại', false === get_transient( 'ohcl_flash_1' ) );

/* màn Trang & menu */
ob_start(); OHCL_Admin_Pages::page(); $ap = ob_get_clean();
check( 'màn Trang & menu: bảng trang (đã đăng / module tắt), số mục menu, chọn vị trí theme', false !== strpos( $ap, 'đã đăng' ) && false !== strpos( $ap, 'module 09 đang tắt' ) && false !== strpos( $ap, 'value="primary" selected' ) && false !== strpos( $ap, 'Cập nhật menu' ) );

echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
