<?php
/**
 * Coach role, referral, commission ledger (OHCL_Affiliate, wave Đ3a). Run: php tests/affiliate-smoke.php.
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 11:15 AM Johnny Chu - Chu Hoàng Anh] OHCL-D3A — test Coach + affiliate: cấp vai, mã giới thiệu, người đầu tiên thắng, ghi nhận từ dữ liệu nguồn, không nhân đôi, đảo dòng, duyệt → trả, kỳ đã duyệt không sửa.

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
class T_User { public $ID; public $display_name; public $user_email; public $roles = array(); public function __construct( $id, $n, $e, $r ) { $this->ID = $id; $this->display_name = $n; $this->user_email = $e; $this->roles = $r; } public function add_role( $r ) { $this->roles[] = $r; $GLOBALS['users'][ $this->ID ]->roles = $this->roles; } }
$GLOBALS['opts'] = array(); $GLOBALS['tr'] = array(); $GLOBALS['hooks'] = array(); $GLOBALS['posts'] = array(); $GLOBALS['meta'] = array(); $GLOBALS['users'] = array(); $GLOBALS['umeta'] = array(); $GLOBALS['notified'] = array(); $GLOBALS['roles'] = array();
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function add_filter( $h, $cb ) { $GLOBALS['hooks'][ $h ][] = $cb; return true; }
function add_action( $h, $cb ) { return add_filter( $h, $cb ); }
function apply_filters( $h, $v ) { return $v; }
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
function check_admin_referer( $a ) { return true; }
function esc_html__( $s, $d = '' ) { return esc_html( $s ); }
function number_format_i18n( $n ) { return number_format( (float) $n, 0, ',', '.' ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); }
function sanitize_email( $s ) { return trim( (string) $s ); }
function sanitize_user( $s, $strict = false ) { return strtolower( preg_replace( '/[^a-z0-9_.\-]/i', '', (string) $s ) ); }
function wp_unslash( $s ) { return $s; }
function wp_safe_redirect( $u ) { throw new Redirected( $u ); }
function current_user_can( $c ) { return ! empty( $GLOBALS['admin'] ); }
function wp_die( $m ) { throw new RuntimeException( 'wp_die: ' . $m ); }
function get_current_user_id() { return 1; }
function get_post( $id ) { return isset( $GLOBALS['posts'][ $id ] ) ? $GLOBALS['posts'][ $id ] : null; }
function get_posts( array $a ) { return array_reverse( array_values( array_filter( $GLOBALS['posts'], function ( $p ) use ( $a ) { return $p->post_type === $a['post_type']; } ) ) ); }
function wp_insert_post( array $a ) { $id = count( $GLOBALS['posts'] ) + 501; $GLOBALS['posts'][ $id ] = (object) array( 'ID' => $id, 'post_type' => $a['post_type'], 'post_title' => $a['post_title'], 'post_status' => 'publish' ); return $id; }
function wp_update_post( array $a ) { $GLOBALS['posts'][ $a['ID'] ]->post_title = $a['post_title']; return $a['ID']; }
function get_post_meta( $id, $k, $s = true ) { return isset( $GLOBALS['meta'][ $id ][ $k ] ) ? $GLOBALS['meta'][ $id ][ $k ] : ''; }
function update_post_meta( $id, $k, $v ) { $GLOBALS['meta'][ $id ][ $k ] = $v; return true; }
function get_user_meta( $id, $k, $s = true ) { return isset( $GLOBALS['umeta'][ $id ][ $k ] ) ? $GLOBALS['umeta'][ $id ][ $k ] : ''; }
function update_user_meta( $id, $k, $v ) { $GLOBALS['umeta'][ $id ][ $k ] = $v; return true; }
function get_users( array $a ) {
	$r = array_values( array_filter( $GLOBALS['users'], function ( $u ) use ( $a ) {
		if ( isset( $a['role'] ) && ! in_array( $a['role'], $u->roles, true ) ) { return false; }
		if ( isset( $a['meta_key'] ) && get_user_meta( $u->ID, $a['meta_key'] ) !== $a['meta_value'] ) { return false; }
		return true;
	} ) );
	return isset( $a['fields'] ) && 'ID' === $a['fields'] ? array_map( function ( $u ) { return $u->ID; }, $r ) : $r;
}
function get_user_by( $f, $v ) { foreach ( $GLOBALS['users'] as $u ) { if ( $u->user_email === $v ) { return $u; } } return false; }
function get_userdata( $id ) { return isset( $GLOBALS['users'][ $id ] ) ? $GLOBALS['users'][ $id ] : false; }
function username_exists( $l ) { return false; }
function wp_generate_password( $n = 12, $s = true ) { return str_repeat( 'x', $n ); }
function wp_rand( $a, $b ) { return $a; }
function wp_insert_user( array $a ) { $id = count( $GLOBALS['users'] ) + 70; $GLOBALS['users'][ $id ] = new T_User( $id, $a['display_name'], $a['user_email'], array( $a['role'] ) ); return $id; }
function wp_new_user_notification( $id, $d, $n ) { $GLOBALS['notified'][] = $id; }
function get_role( $r ) { return isset( $GLOBALS['roles'][ $r ] ) ? $GLOBALS['roles'][ $r ] : null; }
function add_role( $r, $l, $c ) { $GLOBALS['roles'][ $r ] = $c; }
function register_post_type() {}
function get_post_type() { return 'x'; }
function is_admin() { return false; }
function is_ssl() { return true; }
function is_user_logged_in() { return ! empty( $GLOBALS['me'] ); }
function wp_get_current_user() { return ! empty( $GLOBALS['me'] ) ? $GLOBALS['users'][ $GLOBALS['me'] ] : (object) array( 'ID' => 0, 'roles' => array() ); }
function wp_login_url( $r = '' ) { return 'https://hub.test/wp-login.php?redirect_to=' . $r; }
function get_bloginfo( $k ) { return 'Site'; }
function language_attributes() {}
function bloginfo( $k ) {}
function wp_head() {}
function wp_footer() {}
function wp_body_open() {}
function body_class( $c ) {}
function get_permalink( $p ) { return ''; }
function has_post_thumbnail( $p ) { return false; }

$dir = dirname( __DIR__ );
define( 'OHCL_DIR', $dir . '/' );
foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview', 'class-ohcl-members', 'class-ohcl-events', 'class-ohcl-audit', 'class-ohcl-coach', 'class-ohcl-affiliate', 'class-ohcl-certs', 'class-ohcl-front', 'class-ohcl-admin', 'class-ohcl-admin-coach' ) as $f ) {
	require $dir . '/includes/' . $f . '.php';
}
require $dir . '/examples/wp-sample-hub/wp-sample-hub.php';

$pass = 0; $fail = 0;
function check( $name, $ok ) { global $pass, $fail; if ( $ok ) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; } }
function flash() { $f = get_transient( 'ohcl_flash_1' ); delete_transient( 'ohcl_flash_1' ); return is_array( $f ) ? $f : array(); }
function post_admin( $fn, array $post ) { $_POST = $post; try { call_user_func( array( 'OHCL_Admin_Coach', $fn ) ); } catch ( Redirected $r ) { return $r->getMessage(); } return ''; }

/* ---------- thuần ---------- */
$NOW = strtotime( '2026-11-26 12:00:00 UTC' );
$pol = OHCL_Affiliate::clean_policy( array( 'referral_member' => '1.500.000', 'service_pct' => '12,5', 'ai_pct' => '250' ), $NOW );
check( 'chính sách: số tiền bỏ dấu chấm ngăn cách, % kẹp 0–100, ghi ngày đổi', 1500000 === $pol['referral_member'] && 12.5 === $pol['service_pct'] && 100.0 === $pol['ai_pct'] && '2026-11-26 12:00:00' === $pol['updated_at'] );
check( 'chính sách rác ⇒ 0 (không bịa số)', 0 === OHCL_Affiliate::clean_policy( array( 'referral_member' => 'abc', 'service_pct' => '-5' ), $NOW )['referral_member'] && 0.0 === OHCL_Affiliate::clean_policy( array( 'service_pct' => '-5' ), $NOW )['service_pct'] );
$c1 = OHCL_Affiliate::code_from_bytes( "\x00\x01\x02\x03\x04\x05" );
check( 'mã giới thiệu: 6 ký tự, không O / 0 / I / 1, hợp lệ', 6 === strlen( $c1 ) && OHCL_Affiliate::valid_code( $c1 ) && ! OHCL_Affiliate::valid_code( 'abc' ) && ! OHCL_Affiliate::valid_code( 'AB0O1I' ) );
check( 'giữ 12 tháng: trong hạn đúng, quá 12 tháng hoặc chưa ghi ⇒ hết', OHCL_Affiliate::ref_active( '2026-01-10 00:00:00', $NOW ) && ! OHCL_Affiliate::ref_active( '2025-10-01 00:00:00', $NOW ) && ! OHCL_Affiliate::ref_active( '', $NOW ) );
check( 'tháng ghi sổ: tạm tính ⇒ tháng này; đã duyệt / đã trả ⇒ tháng sau', '2026-11' === OHCL_Affiliate::target_month( $NOW, '' ) && '2026-11' === OHCL_Affiliate::target_month( $NOW, 'tentative' ) && '2026-12' === OHCL_Affiliate::target_month( $NOW, 'approved' ) && '2026-12' === OHCL_Affiliate::target_month( $NOW, 'paid' ) );
check( 'trạng thái chỉ đi một chiều: tạm tính → duyệt → trả', OHCL_Affiliate::can_move( 'tentative', 'approved' ) && OHCL_Affiliate::can_move( 'approved', 'paid' ) && ! OHCL_Affiliate::can_move( 'tentative', 'paid' ) && ! OHCL_Affiliate::can_move( 'paid', 'approved' ) && ! OHCL_Affiliate::can_move( 'approved', 'tentative' ) );
$ln = OHCL_Affiliate::make_line( 'member:5', 'referral_member', 'HV-000005', 500000, $NOW, '', 'x' );
list( $ls, $added ) = OHCL_Affiliate::add_line( array(), $ln );
list( $ls2, $added2 ) = OHCL_Affiliate::add_line( $ls, $ln );
check( 'dòng hoa hồng có khoá ổn định: thêm lần hai không nhân đôi', $added && ! $added2 && 1 === count( $ls2 ) && 500000 === OHCL_Affiliate::total( $ls2 ) );
$rev = OHCL_Affiliate::reversal( $ls, 'member:5', 'hội viên rời', $NOW );
check( 'dòng đảo: số âm, khoá rev:…, không đảo hai lần, không đảo dòng đảo, khoá lạ ⇒ không', $rev && -500000 === $rev['amount'] && 'rev:member:5' === $rev['key'] && null === OHCL_Affiliate::reversal( array_merge( $ls, array( $rev ) ), 'member:5', 'x', $NOW ) && null === OHCL_Affiliate::reversal( array_merge( $ls, array( $rev ) ), 'rev:member:5', 'x', $NOW ) && null === OHCL_Affiliate::reversal( $ls, 'member:99', 'x', $NOW ) && 0 === OHCL_Affiliate::total( array_merge( $ls, array( $rev ) ) ) );

/* ---------- cấp vai Coach ---------- */
$GLOBALS['admin'] = true;
OHCL_Affiliate::register();
check( 'vai hub_coach được đăng ký', isset( $GLOBALS['roles']['hub_coach'] ) );
$cfg  = OHCL_Industry::current();
$app  = OHCL_Coach::save( array( 'name' => 'Coach Lan', 'skills' => array(), 'area' => 'Hà Nội', 'years' => 5, 'contact' => 'lan@example.com', 'note' => '' ) );
$err1 = OHCL_Affiliate::activate( $app, 'lan@example.com' );
check( 'hồ sơ chưa duyệt ⇒ không cấp vai (lỗi bốn trường)', is_wp_error( $err1 ) && 'ohcl_coach_not_approved' === $err1->get_error_code() && '' !== $err1->get_error_data()['hint'] );
OHCL_Coach::decide( $app, 'approved' );
$err2 = OHCL_Affiliate::activate( $app, 'khong-phai-email' );
check( 'email sai ⇒ từ chối', is_wp_error( $err2 ) && 'ohcl_coach_email_invalid' === $err2->get_error_code() );
$uid = OHCL_Affiliate::activate( $app, 'lan@example.com' );
$cs  = OHCL_Affiliate::coaches();
check( 'cấp vai: tài khoản mới có vai hub_coach, mã giới thiệu hợp lệ, gửi email đặt mật khẩu, hồ sơ ghi tài khoản', ! is_wp_error( $uid ) && isset( $cs[ $uid ] ) && OHCL_Affiliate::valid_code( $cs[ $uid ]['code'] ) && array( $uid ) === $GLOBALS['notified'] && (int) $uid === (int) get_post_meta( $app, '_bzca_user', true ) );
$again = OHCL_Affiliate::activate( $app, 'lan@example.com' );
check( 'cấp lần hai cho cùng hồ sơ ⇒ từ chối, không tạo tài khoản thứ hai', is_wp_error( $again ) && 'ohcl_coach_already' === $again->get_error_code() && 1 === count( $GLOBALS['users'] ) );
$code = $cs[ $uid ]['code'];
// tài khoản có sẵn (quản trị) được thêm vai, giữ vai cũ
$GLOBALS['users'][3] = new T_User( 3, 'Admin Hub', 'admin@example.com', array( 'administrator' ) );
$app2 = OHCL_Coach::save( array( 'name' => 'Admin Hub', 'skills' => array(), 'area' => '', 'years' => 0, 'contact' => 'admin@example.com', 'note' => '' ) );
OHCL_Coach::decide( $app2, 'approved' );
$u2 = OHCL_Affiliate::activate( $app2, 'admin@example.com' );
check( 'email đã có tài khoản ⇒ thêm vai Coach, giữ nguyên vai quản trị, không tạo tài khoản mới, không gửi email đặt mật khẩu', 3 === $u2 && in_array( 'administrator', $GLOBALS['users'][3]->roles, true ) && in_array( 'hub_coach', $GLOBALS['users'][3]->roles, true ) && 2 === count( $GLOBALS['users'] ) && array( $uid ) === $GLOBALS['notified'] );
check( 'tra mã giới thiệu: đúng mã ⇒ Coach; mã sai / thường ⇒ chữ hoa vẫn khớp; mã lạ ⇒ 0', $uid === OHCL_Affiliate::coach_by_code( $code ) && $uid === OHCL_Affiliate::coach_by_code( strtolower( $code ) ) && 0 === OHCL_Affiliate::coach_by_code( 'ZZZZZZ' ) && 0 === OHCL_Affiliate::coach_by_code( 'ab' ) );

/* ---------- giới thiệu: người đầu tiên thắng ---------- */
$mkm = function ( $title ) { $id = wp_insert_post( array( 'post_type' => OHCL_Members::CPT, 'post_title' => $title ) ); update_post_meta( $id, '_bzm_status', 'active' ); update_post_meta( $id, '_bzm_accepted_at', gmdate( 'Y-m-d H:i:s' ) ); return $id; };
$m1 = $mkm( 'Cơ sở một' );
$_COOKIE['ohcl_ref'] = $code;
$got = OHCL_Affiliate::attribute_member( $m1 );
check( 'đăng ký qua link Coach ⇒ hồ sơ ghi Coach + ngày; mã Coach ghi vào trường "Coach phụ trách"', $uid === $got && $uid === (int) get_post_meta( $m1, '_bzm_ref_coach', true ) && '' !== get_post_meta( $m1, '_bzm_ref_at', true ) && strtolower( $code ) === get_post_meta( $m1, '_bzm_coach', true ) );
$GLOBALS['users'][3]->roles[] = 'hub_coach';
update_user_meta( 3, 'ohcl_ref_code', 'HHHHHH' );
$_COOKIE['ohcl_ref'] = 'HHHHHH';
check( 'người giới thiệu sau không ghi đè (lần đầu thắng)', 0 === OHCL_Affiliate::attribute_member( $m1 ) && $uid === (int) get_post_meta( $m1, '_bzm_ref_coach', true ) );
$_COOKIE['ohcl_ref'] = 'QQQQQQ';
$m2 = $mkm( 'Cơ sở hai' );
check( 'cookie mã không phải Coach ⇒ không ghi nhận', 0 === OHCL_Affiliate::attribute_member( $m2 ) && '' === get_post_meta( $m2, '_bzm_ref_coach', true ) );
unset( $_COOKIE['ohcl_ref'] );

/* ---------- ghi nhận từ dữ liệu nguồn ---------- */
$now_s = gmdate( 'Y-m-d H:i:s' );
OHCL_Core_Client::$config = array( 'base_url' => 'https://bizcity.test', 'hub' => 'sample', 'secret' => 'ohs_' . str_repeat( 'a', 32 ) );
$GLOBALS['key_status'] = 'active';
OHCL_Core_Client::$transport = function () use ( $now_s ) { return array( 'status' => 200, 'body' => json_encode( array( 'items' => array( array( 'id' => 11, 'status' => $GLOBALS['key_status'], 'plan_code' => 'p', 'plan_until' => '2030-01-01 00:00:00', 'last_used_at' => $now_s, 'created_at' => $now_s, 'key_prefix' => 'k', 'label' => '', 'site_url' => '', 'coach_ref' => '' ) ), 'page' => 1 ) ) ); };
$s0 = OHCL_Affiliate::sync();
check( 'chưa đặt chính sách (0 đ) ⇒ không ghi nhận gì, không tạo kỳ chi trả', 0 === $s0['added'] && ! OHCL_Affiliate::payouts() );
OHCL_Affiliate::save_policy( array( 'referral_member' => '500000' ) );
OHCL_Overview::flush();
$s1 = OHCL_Affiliate::sync();
check( 'hội viên được giới thiệu nhưng chưa có khoá ⇒ chưa "Đã kích hoạt" ⇒ chưa ghi nhận', 0 === $s1['added'] );
update_post_meta( $m1, '_bzm_key_id', 11 );
OHCL_Overview::flush(); OHCL_Members::flush_public();
$s2 = OHCL_Affiliate::sync();
$py = array_values( OHCL_Affiliate::payouts() );
check( 'khoá đang chạy ⇒ ghi nhận đúng 1 dòng 500.000 đ vào kỳ tháng này của đúng Coach, trạng thái tạm tính', 1 === $s2['added'] && 1 === count( $py ) && $uid === $py[0]['coach'] && gmdate( 'Y-m' ) === $py[0]['month'] && 'tentative' === $py[0]['status'] && 500000 === $py[0]['total'] && 'member:' . $m1 === $py[0]['lines'][0]['key'] );
$s3 = OHCL_Affiliate::sync();
check( 'chạy lại ⇒ không nhân đôi (khoá dòng)', 0 === $s3['added'] && 500000 === array_values( OHCL_Affiliate::payouts() )[0]['total'] );
OHCL_Affiliate::save_policy( array( 'referral_member' => '900000' ) );
$m3 = $mkm( 'Cơ sở ba' );
update_post_meta( $m3, '_bzm_ref_coach', $uid ); update_post_meta( $m3, '_bzm_ref_at', gmdate( 'Y-m-d H:i:s' ) ); update_post_meta( $m3, '_bzm_key_id', 11 );
OHCL_Members::flush_public();
OHCL_Affiliate::sync();
$lines = array_values( OHCL_Affiliate::payouts() )[0]['lines'];
check( 'đổi chính sách chỉ áp cho dòng sau: dòng cũ giữ 500.000, dòng mới 900.000', 2 === count( $lines ) && 500000 === $lines[0]['amount'] && 900000 === $lines[1]['amount'] );
OHCL_Affiliate::save_override( $uid, '1.200.000' );
$m4 = $mkm( 'Cơ sở bốn' );
update_post_meta( $m4, '_bzm_ref_coach', $uid ); update_post_meta( $m4, '_bzm_ref_at', gmdate( 'Y-m-d H:i:s' ) ); update_post_meta( $m4, '_bzm_key_id', 11 );
OHCL_Members::flush_public();
OHCL_Affiliate::sync();
$lines = array_values( OHCL_Affiliate::payouts() )[0]['lines'];
check( 'số riêng của Coach thắng chính sách chung', 3 === count( $lines ) && 1200000 === $lines[2]['amount'] && 2600000 === array_values( OHCL_Affiliate::payouts() )[0]['total'] );
update_post_meta( $m3, '_bzm_ref_at', '2024-01-01 00:00:00' );
$m5 = $mkm( 'Cơ sở năm' ); update_post_meta( $m5, '_bzm_ref_coach', $uid ); update_post_meta( $m5, '_bzm_ref_at', '2024-01-01 00:00:00' ); update_post_meta( $m5, '_bzm_key_id', 11 );
OHCL_Members::flush_public();
OHCL_Affiliate::sync();
check( 'giới thiệu quá 12 tháng ⇒ không ghi nhận', 3 === count( array_values( OHCL_Affiliate::payouts() )[0]['lines'] ) );

/* ---------- lõi lỗi, đảo dòng, duyệt, trả ---------- */
OHCL_Core_Client::$transport = function () { return new WP_Error( 'down', 'x' ); };
OHCL_Overview::flush(); OHCL_Members::flush_public();
$m6 = $mkm( 'Cơ sở sáu' ); update_post_meta( $m6, '_bzm_ref_coach', $uid ); update_post_meta( $m6, '_bzm_ref_at', gmdate( 'Y-m-d H:i:s' ) ); update_post_meta( $m6, '_bzm_key_id', 11 );
$sd = OHCL_Affiliate::sync();
check( 'lõi lỗi ⇒ không ghi nhận gì (không đoán), báo skipped_core', 0 === $sd['added'] && $sd['skipped_core'] );
$r1 = OHCL_Affiliate::reverse( 'member:' . $m1, 'hội viên rời sớm' );
$pp = array_values( OHCL_Affiliate::payouts() )[0];
check( 'đảo dòng: thêm dòng âm, tổng giảm đúng, dòng gốc còn nguyên (không xoá)', true === $r1 && 4 === count( $pp['lines'] ) && 2100000 === $pp['total'] && 'member:' . $m1 === $pp['lines'][0]['key'] );
$r2 = OHCL_Affiliate::reverse( 'member:' . $m1, 'lần hai' );
check( 'đảo lần hai ⇒ lỗi bốn trường', is_wp_error( $r2 ) && 'ohcl_payout_reverse_invalid' === $r2->get_error_code() && '' !== $r2->get_error_data()['hint'] );
$pid = $pp['id'];
$bad = OHCL_Affiliate::move( $pid, 'paid' );
check( 'nhảy cóc tạm tính → đã trả ⇒ từ chối', is_wp_error( $bad ) && 'ohcl_payout_move_invalid' === $bad->get_error_code() );
post_admin( 'handle_affiliate', array( 'do' => 'move', 'payout' => $pid, 'to' => 'approved' ) );
check( 'nút Duyệt kỳ ⇒ đã duyệt, có ngày + người duyệt', 'approved' === OHCL_Affiliate::payouts()[ $pid ]['status'] && '' !== OHCL_Affiliate::payouts()[ $pid ]['decided_at'] && 1 === (int) get_post_meta( $pid, '_bzp_decided_by', true ) );
OHCL_Core_Client::$transport = function () use ( $now_s ) { return array( 'status' => 200, 'body' => json_encode( array( 'items' => array( array( 'id' => 11, 'status' => 'active', 'plan_code' => 'p', 'plan_until' => '2030-01-01 00:00:00', 'last_used_at' => $now_s, 'created_at' => $now_s, 'key_prefix' => 'k', 'label' => '', 'site_url' => '', 'coach_ref' => '' ) ), 'page' => 1 ) ) ); };
OHCL_Overview::flush(); OHCL_Members::flush_public();
$before = OHCL_Affiliate::payouts()[ $pid ];
OHCL_Affiliate::sync();
$after = OHCL_Affiliate::payouts();
$next  = array_values( array_filter( $after, function ( $p ) use ( $pid ) { return $p['id'] !== $pid; } ) );
check( 'kỳ đã duyệt không bị sửa; hội viên kích hoạt sau đó vào kỳ THÁNG SAU', $before['lines'] === $after[ $pid ]['lines'] && 1 === count( $next ) && 'tentative' === $next[0]['status'] && gmdate( 'Y-m', strtotime( 'first day of next month' ) ) === $next[0]['month'] && 'member:' . $m6 === $next[0]['lines'][0]['key'] );
$cnt = function () { $n = 0; foreach ( OHCL_Affiliate::payouts() as $p ) { $n += count( $p['lines'] ); } return $n; };
$n1 = $cnt();
OHCL_Affiliate::sync();
check( 'dòng gốc nằm ở kỳ đã duyệt: chạy lại không ghi nhận lại sang kỳ tháng sau (khoá dòng tìm trên mọi kỳ)', $n1 === $cnt() );
$seen = array();
foreach ( OHCL_Affiliate::payouts() as $p ) { foreach ( $p['lines'] as $l ) { $seen[ $l['key'] ] = ( isset( $seen[ $l['key'] ] ) ? $seen[ $l['key'] ] : 0 ) + 1; } }
check( 'mỗi khoá dòng (member:…, rev:…) xuất hiện đúng 1 lần trên mọi kỳ — hội viên đã tính ở kỳ đã duyệt không bị tính lại ở kỳ sau', $seen && 1 === max( $seen ) );
post_admin( 'handle_affiliate', array( 'do' => 'move', 'payout' => $pid, 'to' => 'paid' ) );
check( 'Đánh dấu đã trả ⇒ đã trả; sau đó không còn nút chuyển', 'paid' === OHCL_Affiliate::payouts()[ $pid ]['status'] && is_wp_error( OHCL_Affiliate::move( $pid, 'approved' ) ) );
$sm = OHCL_Affiliate::coach_summary( $uid, OHCL_Affiliate::payouts(), OHCL_Members::all() );
check( 'tổng theo Coach: đã trả 2.100.000, tạm tính tháng sau, hội viên giới thiệu đếm đủ', 2100000 === $sm['paid'] && $sm['tentative'] > 0 && $sm['members'] >= 5 );

/* ---------- My Coach (trang công khai của Coach) ---------- */
// [2026-11-26 11:40 AM Johnny Chu - Chu Hoàng Anh] OHCL-D3A
$GLOBALS['me'] = 0;
$g = OHCL_Front::html( 'mycoach', array() );
check( 'My Coach: khách ⇒ mời đăng nhập, không lộ dữ liệu Coach', false !== strpos( $g, 'Đăng nhập để vào My Coach' ) && false === strpos( $g, 'Link giới thiệu của bạn' ) && false === strpos( $g, $code ) );
$GLOBALS['users'][9] = new T_User( 9, 'Người thường', 'nt@example.com', array( 'subscriber' ) );
$GLOBALS['me'] = 9;
$g = OHCL_Front::html( 'mycoach', array() );
check( 'My Coach: tài khoản không phải Coach ⇒ báo chưa là Coach + link ứng tuyển, không có số liệu', false !== strpos( $g, 'chưa là Coach' ) && false !== strpos( $g, 'Trở thành Coach' ) && false === strpos( $g, 'Lịch sử chi trả' ) );
$GLOBALS['me'] = $uid;
update_user_meta( $uid, 'ohcl_coach_skills', array() );
$g = OHCL_Front::html( 'mycoach', array() );
$vw = OHCL_Affiliate::coach_view( OHCL_Affiliate::coaches()[ $uid ], OHCL_Members::all(), OHCL_Members::keys_by_id(), OHCL_Affiliate::payouts(), OHCL_Affiliate::policy(), time() );
check( 'My Coach: Coach thấy link giới thiệu của mình, mã, số hội viên, bốn ô tiền, lịch sử chi trả', false !== strpos( $g, 'Link giới thiệu của bạn' ) && false !== strpos( $g, 'ref=' . $code ) && false !== strpos( $g, 'Lịch sử chi trả' ) && false !== strpos( $g, 'Tạm tính' ) && false !== strpos( $g, 'Đã trả' ) && false !== strpos( $g, '<b>' . (int) $vw['sum']['members'] . '</b>' ) );
check( 'My Coach: liệt kê hội viên do mình giới thiệu (tên, mã, giai đoạn), đánh dấu "Đã ghi nhận"; KHÔNG có email / điện thoại của hội viên', false !== strpos( $g, 'Cơ sở một' ) && false !== strpos( $g, 'Đã ghi nhận' ) && false === strpos( $g, '_bzm_' ) );
$ph = wp_insert_post( array( 'post_type' => OHCL_Members::CPT, 'post_title' => 'Có liên hệ riêng' ) );
foreach ( array( '_bzm_status' => 'active', '_bzm_ref_coach' => $uid, '_bzm_ref_at' => gmdate( 'Y-m-d H:i:s' ), '_bzm_email' => 'bi-mat@example.com', '_bzm_phone' => '0912345678', '_bzm_contact_zalo' => '0987654321' ) as $k => $x ) { update_post_meta( $ph, $k, $x ); }
$g = OHCL_Front::html( 'mycoach', array() );
check( 'My Coach: hội viên có email / điện thoại / Zalo ⇒ hiện tên, nhưng không lộ liên hệ', false !== strpos( $g, 'Có liên hệ riêng' ) && false === strpos( $g, 'bi-mat@example.com' ) && false === strpos( $g, '0912345678' ) && false === strpos( $g, '0987654321' ) );
$other = $GLOBALS['users'][3]; // Coach khác (HHHHHH)
$GLOBALS['me'] = 3;
$g3 = OHCL_Front::html( 'mycoach', array() );
check( 'My Coach: Coach khác không thấy hội viên / hoa hồng của Coach này', false !== strpos( $g3, 'HHHHHH' ) && false === strpos( $g3, 'Cơ sở một' ) && false === strpos( $g3, 'ref=' . $code ) );
$GLOBALS['me'] = $uid;
$hd = OHCL_Front::html( 'home', array() );
check( 'đầu trang: Coach thấy liên kết "My Coach"; người thường không', false !== strpos( $hd, '>My Coach</a>' ) );
$GLOBALS['me'] = 9;
check( 'đầu trang: người không phải Coach không thấy liên kết My Coach', false === strpos( OHCL_Front::html( 'home', array() ), '>My Coach</a>' ) );
$GLOBALS['me'] = 0;
OHCL_Modules::set_enabled( '04', false );
check( 'module 04 tắt ⇒ trang My Coach cũng biến khỏi sổ trang', ! OHCL_Front::page_on( 'mycoach' ) );
OHCL_Modules::set_enabled( '04', true );

/* ---------- màn quản trị ---------- */
ob_start(); OHCL_Admin_Coach::page(); $ap = ob_get_clean();
check( 'màn 04: có chính sách, danh sách Coach với link giới thiệu, kỳ chi trả, nút Duyệt / Đánh dấu đã trả hoặc kết quả đã trả', false !== strpos( $ap, 'Chính sách affiliate' ) && false !== strpos( $ap, 'ref=' . $code ) && false !== strpos( $ap, 'Kỳ chi trả theo tháng' ) && false !== strpos( $ap, 'Đã trả' ) && false !== strpos( $ap, 'Duyệt kỳ này' ) );
post_admin( 'handle_affiliate', array( 'do' => 'policy', 'referral_member' => '700000', 'service_pct' => '10', 'ai_pct' => '5' ) );
check( 'form chính sách lưu đúng', 700000 === OHCL_Affiliate::policy()['referral_member'] && 10.0 === OHCL_Affiliate::policy()['service_pct'] );
post_admin( 'handle_activate', array( 'id' => $app2, 'email' => 'admin@example.com' ) );
$fe = flash();
check( 'form cấp vai: hồ sơ đã cấp rồi ⇒ lỗi ohcl_coach_already hiện trong flash đủ code, message, hint', isset( $fe['error'] ) && 'ohcl_coach_already' === $fe['error']['code'] && '' !== $fe['error']['hint'] && '' !== $fe['error']['message'] );
$app3 = OHCL_Coach::save( array( 'name' => 'Coach Minh', 'skills' => array(), 'area' => '', 'years' => 0, 'contact' => 'minh@example.com', 'note' => '' ) );
OHCL_Coach::decide( $app3, 'approved' );
post_admin( 'handle_activate', array( 'id' => $app3, 'email' => 'x@y' ) );
$fe = flash();
check( 'form cấp vai: email sai ⇒ báo lỗi ohcl_coach_email_invalid, chưa tạo tài khoản', isset( $fe['error'] ) && 'ohcl_coach_email_invalid' === $fe['error']['code'] && '' === get_post_meta( $app3, '_bzca_user', true ) );
post_admin( 'handle_activate', array( 'id' => $app3, 'email' => 'minh@example.com' ) );
check( 'form cấp vai: email đúng ⇒ cấp được, flash báo thành công', isset( flash()['ok'] ) || '' !== (string) get_post_meta( $app3, '_bzca_user', true ) );
$GLOBALS['admin'] = false;
$threw = false;
try { post_admin( 'handle_affiliate', array( 'do' => 'move', 'payout' => $pid, 'to' => 'approved' ) ); } catch ( RuntimeException $e ) { $threw = true; }
check( 'người không có quyền quản trị ⇒ bị chặn', $threw );
$src = (string) file_get_contents( $dir . '/includes/class-ohcl-affiliate.php' );
check( 'không bảng mới, không gọi lõi trực tiếp (chỉ đọc khoá qua OHCL_Members)', ! preg_match( '/CREATE\s+TABLE|dbDelta|OHCL_Core_Client|wp_remote_/i', $src ) );

echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
