<?php
/**
 * Success ledger (OHCL_Events) + member reported results (OHCL_Service outcomes) + public beacon.
 * Run: php tests/onboarding-smoke.php — exit 0 = pass.
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 10:39 PM Johnny Chu - Chu Hoàng Anh] OHCL-H5 — test "Bắt đầu": 4 bước từ dữ liệu thật, hub_activated một lần.

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
$GLOBALS['updir'] = sys_get_temp_dir() . '/ohcl-ob-' . getmypid(); @mkdir( $GLOBALS['updir'] ); $GLOBALS['me'] = 0; $GLOBALS['opts'] = array(); $GLOBALS['tr'] = array(); $GLOBALS['hooks'] = array(); $GLOBALS['posts'] = array(); $GLOBALS['meta'] = array(); $GLOBALS['terms'] = array(); $GLOBALS['http'] = array();
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
foreach ( array( 'class-ohcl-error', 'class-ohcl-modules', 'class-ohcl-industry', 'class-ohcl-slots', 'class-ohcl-core-client', 'class-ohcl-overview', 'class-ohcl-members', 'class-ohcl-demo', 'class-ohcl-shop', 'class-ohcl-certs', 'class-ohcl-coach', 'class-ohcl-affiliate', 'class-ohcl-service', 'class-ohcl-benefits', 'class-ohcl-events', 'class-ohcl-audit', 'class-ohcl-export', 'class-ohcl-pages', 'class-ohcl-onboarding', 'class-ohcl-front', 'class-ohcl-rest' ) as $f ) {
	require $dir . '/includes/' . $f . '.php';
}
require $dir . '/examples/wp-sample-hub/wp-sample-hub.php';

$pass = 0; $fail = 0;
function check( $name, $ok ) { global $pass, $fail; if ( $ok ) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; } }
function rmrf( $d ) { foreach ( (array) glob( $d . '/{,.}*', GLOB_BRACE ) as $f ) { if ( in_array( basename( $f ), array( '.', '..' ), true ) ) { continue; } is_dir( $f ) ? rmrf( $f ) : @unlink( $f ); } @rmdir( $d ); }
$cfg = OHCL_Industry::current();
$NOW = time();

$F = array(
	'core' => array( 'base_url' => 'https://core.test', 'hub' => 'vegan', 'has_secret' => true ), 'industry_ok' => true, 'industry_errors' => 0,
	'pages_total' => 6, 'pages_made' => 6, 'policy_set' => true,
	'members_real' => 3, 'members_accepted' => 2, 'keys_issued' => 1, 'benefit_used' => true,
);
$r = OHCL_Onboarding::steps( $F );
check( 'steps: đủ dữ kiện ⇒ 4/4, 100%, activated', 4 === $r['done'] && 100 === $r['percent'] && true === $r['activated'] && array( 'done', 'done', 'done', 'done' ) === array_column( $r['steps'], 'status' ) );
$e = OHCL_Onboarding::steps( array_merge( $F, array( 'core' => array( 'base_url' => '', 'hub' => '', 'has_secret' => false ), 'industry_ok' => false, 'industry_errors' => 3, 'pages_made' => 2, 'policy_set' => false, 'members_accepted' => 0, 'keys_issued' => 0, 'benefit_used' => false ) ) );
check( 'steps: trống ⇒ 0/4, cả bốn "todo" (bước 3 có 0 / 2 phép kiểm đạt)', 0 === $e['done'] && ! $e['activated'] && array( 'todo', 'todo', 'todo', 'todo' ) === array_column( $e['steps'], 'status' ) );
$p = OHCL_Onboarding::steps( array_merge( $F, array( 'core' => array( 'base_url' => 'https://core.test', 'hub' => '', 'has_secret' => false ) ) ) );
check( 'steps: mới có địa chỉ lõi ⇒ bước 1 "doing" (1/3), không xong', 'doing' === $p['steps'][0]['status'] && 3 === $p['done'] && ! $p['activated'] );
$pg = OHCL_Onboarding::steps( array_merge( $F, array( 'pages_made' => 4 ) ) );
check( 'steps: thiếu trang (4/6) ⇒ bước 3 "doing"; nhãn ghi 4/6', 'doing' === $pg['steps'][2]['status'] && false !== strpos( $pg['steps'][2]['checks'][0]['label'], '4/6' ) );
$nz = OHCL_Onboarding::steps( array_merge( $F, array( 'pages_total' => 0, 'pages_made' => 0 ) ) );
check( 'steps: Hub không có trang nào để tạo (0/0) không tính là "đã xong" bước 3', 'done' !== $nz['steps'][2]['status'] );
$u = OHCL_Onboarding::steps( array_merge( $F, array( 'benefit_used' => null ) ) );
check( 'steps: không kiểm được quyền lợi (lõi không phản hồi) ⇒ phép kiểm là null (SKIP), bước 4 "doing", KHÔNG activated', null === $u['steps'][3]['checks'][2]['ok'] && 'doing' === $u['steps'][3]['status'] && ! $u['activated'] );
$nk = OHCL_Onboarding::steps( array_merge( $F, array( 'keys_issued' => 0, 'benefit_used' => false ) ) );
check( 'steps: có hội viên nhưng chưa có khoá ⇒ bước 4 "doing", chưa activated', 'doing' === $nk['steps'][3]['status'] && ! $nk['activated'] );

/* ---------- hub_activated ---------- */
check( 'maybe_activate: chưa đủ 4 bước ⇒ không bắn, không ghi', false === OHCL_Onboarding::maybe_activate( false ) && '' === get_option( OHCL_Onboarding::ACTIVATED_OPTION, '' ) );
check( 'maybe_activate: lần đầu đủ ⇒ true + ghi ngày; lần hai ⇒ false (chỉ một lần)', true === OHCL_Onboarding::maybe_activate( true ) && '' !== get_option( OHCL_Onboarding::ACTIVATED_OPTION, '' ) && false === OHCL_Onboarding::maybe_activate( true ) );
check( 'Slots: hub_activated là sự kiện hợp lệ, không mang bí mật', in_array( 'hub_activated', OHCL_Slots::EVENTS, true ) );

/* ---------- facts từ site ---------- */
$GLOBALS['opts'] = array();
$fx = OHCL_Onboarding::facts();
check( 'facts: site trống ⇒ 0 hội viên, 0 khoá, quyền lợi chưa dùng (false), chưa có chính sách', 0 === $fx['members_real'] && 0 === $fx['keys_issued'] && false === $fx['benefit_used'] && false === $fx['policy_set'] );
$m1 = wp_insert_post( array( 'post_type' => OHCL_Members::CPT, 'post_title' => 'Quán 1' ) );
update_post_meta( $m1, '_bzm_status', 'active' );
update_post_meta( $m1, '_bzm_accepted_at', '2026-11-20 10:00:00' );
update_post_meta( $m1, '_bzm_key_id', 77 );
$fy = OHCL_Onboarding::facts();
check( 'facts: 1 hội viên đã tiếp nhận + có khoá; lõi không trả khoá nào ⇒ benefit_used = null (không khẳng định)', 1 === $fy['members_accepted'] && 1 === $fy['keys_issued'] && null === $fy['benefit_used'] );
$dm = wp_insert_post( array( 'post_type' => OHCL_Members::CPT, 'post_title' => 'Mẫu' ) );
update_post_meta( $dm, '_bzm_demo', 1 );
update_post_meta( $dm, '_bzm_accepted_at', '2026-11-20 10:00:00' );
check( 'facts: hồ sơ mẫu KHÔNG tính vào hội viên thật', 1 === OHCL_Onboarding::facts()['members_real'] );
OHCL_Affiliate::save_policy( array( 'referral_member' => 100000, 'service_pct' => 10, 'ai_pct' => 5 ) );
check( 'facts: đặt chính sách affiliate ⇒ policy_set', true === OHCL_Onboarding::facts()['policy_set'] );

/* ---------- quy ước ---------- */
$os = file_get_contents( $dir . '/includes/class-ohcl-onboarding.php' );
check( 'onboarding: không bảng mới, không gọi mạng trực tiếp, không lưu "đã tick"', 0 === preg_match( '/\$wpdb|dbDelta|wp_remote_|wp_safe_remote_|OHCL_Core_Client::(ping|pair|issue)/', $os ) );
rmrf( $GLOBALS['updir'] );
echo "\n$pass pass, $fail fail\n";
exit( $fail ? 1 : 0 );
