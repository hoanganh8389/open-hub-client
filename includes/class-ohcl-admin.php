<?php
/**
 * wp-admin screens of the Hub owner (OPEN-HUB-CLIENT §2, §3; mockup owner-center.html O0, O1).
 *
 *   Hub › Tổng quan          01 — question 2 (technology funnel) + 5 (to-do) from the Hub's keys; 1 needs members (Đ1c)
 *   Hub › Khoá dùng thử       03 — issue a trial key (shown ONCE), list, revoke (two-step, no JS confirm)
 *   Hub › Cài đặt             14 — modules on / off, connection to open-hub-core, industry data report
 *
 * Every write: admin-post + nonce + manage_options. Keys and money live in open-hub-core; this screen
 * only calls OHCL_Core_Client (R-HUB-14). Errors show the four fields (R-ERROR-UX).
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 06:05 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1B — màn quản trị: Tổng quan, Khoá dùng thử, Cài đặt (module + kết nối lõi + báo cáo ngành).

defined( 'ABSPATH' ) || exit;

final class OHCL_Admin {

	const CAP = 'manage_options';

	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_ohcl_issue', array( __CLASS__, 'handle_issue' ) );
		add_action( 'admin_post_ohcl_revoke', array( __CLASS__, 'handle_revoke' ) );
		add_action( 'admin_post_ohcl_settings', array( __CLASS__, 'handle_settings' ) );
		add_action( 'admin_post_ohcl_ping', array( __CLASS__, 'handle_ping' ) );
		add_action( 'admin_post_ohcl_pair', array( __CLASS__, 'handle_pair' ) );
	}

	public static function menu() {
		$cfg  = OHCL_Industry::current();
		$name = $cfg['name'] ? $cfg['name'] : 'Hub';
		add_menu_page( $name, $name, self::CAP, 'open-hub', array( __CLASS__, 'page_overview' ), 'dashicons-networking', 3 );
		add_submenu_page( 'open-hub', 'Tổng quan', '01 · Tổng quan', self::CAP, 'open-hub', array( __CLASS__, 'page_overview' ) );
		if ( class_exists( 'OHCL_Admin_Members' ) ) {
			add_submenu_page( 'open-hub', 'Hội viên', '02 · Hội viên', self::CAP, 'open-hub-members', array( 'OHCL_Admin_Members', 'page' ) ); // [2026-11-25 06:49 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1C
		}
		add_submenu_page( 'open-hub', 'Khoá dùng thử', '03 · Khoá dùng thử', self::CAP, 'open-hub-keys', array( __CLASS__, 'page_keys' ) );
		if ( class_exists( 'OHCL_Admin_Coach' ) && OHCL_Admin_Coach::on() ) {
			add_submenu_page( 'open-hub', 'Coach', '04 · Coach', self::CAP, OHCL_Admin_Coach::PAGE, array( 'OHCL_Admin_Coach', 'page' ) ); // [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2
		}
		if ( class_exists( 'OHCL_Admin_Certs' ) && OHCL_Admin_Certs::on() ) {
			add_submenu_page( 'open-hub', 'Chứng nhận', '06 · Chứng nhận', self::CAP, OHCL_Admin_Certs::PAGE, array( 'OHCL_Admin_Certs', 'page' ) ); // [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A
		}
		if ( class_exists( 'OHCL_Admin_Pages' ) ) {
			add_submenu_page( 'open-hub', 'Trang & menu', 'Trang & menu', self::CAP, OHCL_Admin_Pages::PAGE, array( 'OHCL_Admin_Pages', 'page' ) ); // [2026-11-25 11:53 PM Johnny Chu - Chu Hoàng Anh] OHCL-F3
		}
		if ( class_exists( 'OHCL_Admin_Demo' ) ) {
			add_submenu_page( 'open-hub', 'Dữ liệu mẫu', 'Dữ liệu mẫu', self::CAP, OHCL_Admin_Demo::PAGE, array( 'OHCL_Admin_Demo', 'page' ) ); // [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-DEMO
		}
		add_submenu_page( 'open-hub', 'Cài đặt', '14 · Cài đặt', self::CAP, 'open-hub-settings', array( __CLASS__, 'page_settings' ) );
	}

	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'open-hub' ) ) {
			return;
		}
		wp_enqueue_style( 'ohcl-admin', OHCL_Front::asset( plugins_url( 'assets/admin.css', OHCL_DIR . 'open-hub-client.php' ) ), array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- [2026-11-26 12:09 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX1 phiên bản trong ?ohcl=
		$cfg = OHCL_Industry::current();
		wp_add_inline_style( 'ohcl-admin', '.ohcl{--a:' . $cfg['brand']['accent'] . '}' ); // accent đã kiểm #rrggbb
	}

	/* ---------- helpers ---------- */

	public static function url( $page, array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
	}

	private static function flash_key() {
		return 'ohcl_flash_' . get_current_user_id();
	}

	public static function flash_set( array $f ) {
		set_transient( self::flash_key(), $f, 2 * MINUTE_IN_SECONDS );
	}

	public static function error_flash( WP_Error $e ) {
		$d = (array) $e->get_error_data();
		return array(
			'error' => array(
				'code'      => $e->get_error_code(),
				'message'   => $e->get_error_message(),
				'hint'      => isset( $d['hint'] ) ? (string) $d['hint'] : '',
				'help_code' => isset( $d['help_code'] ) ? (string) $d['help_code'] : $e->get_error_code(),
			),
		);
	}

	public static function flash() {
		$f = get_transient( self::flash_key() );
		if ( ! is_array( $f ) ) {
			return;
		}
		delete_transient( self::flash_key() ); // khoá thô chỉ hiện một lần
		if ( ! empty( $f['key'] ) ) {
			echo '<div class="ohcl-note ok"><b>Đã cấp khoá cho ' . esc_html( $f['label'] ) . '.</b> Khoá chỉ hiện <b>một lần</b>, chép gửi cơ sở ngay:'
				. '<code class="ohcl-key">' . esc_html( $f['key'] ) . '</code>'
				. '<span class="mute">Gói ' . esc_html( $f['plan_code'] ) . ' đến ' . esc_html( $f['plan_until'] ) . ' (UTC). Hết hạn chỉ tạm dừng trợ lý AI, sổ sách của cơ sở không dừng.</span></div>';
		} elseif ( ! empty( $f['error'] ) ) {
			self::error_box( $f['error'] );
		} elseif ( ! empty( $f['ok'] ) ) {
			echo '<div class="ohcl-note ok">' . esc_html( $f['ok'] ) . '</div>';
		}
		if ( ! empty( $f['steps'] ) ) {
			echo '<div class="ohcl-card"><h3>Kiểm tra kết nối lõi</h3><table class="ohcl-t">';
			foreach ( $f['steps'] as $s ) {
				echo '<tr><td><span class="ohcl-st ' . ( $s['ok'] ? 'ok">PASS' : 'bad">FAIL' ) . '</span></td><td>' . esc_html( $s['step'] ) . '</td><td>' . esc_html( $s['note'] ) . '</td></tr>';
			}
			echo '</table></div>';
		}
	}

	public static function error_box( array $e ) {
		echo '<div class="ohcl-note bad"><b>' . esc_html( $e['message'] ) . '</b> ' . esc_html( $e['hint'] )
			. ' <code>' . esc_html( $e['code'] ) . '</code>'
			. ( ! empty( $e['help_code'] ) && $e['help_code'] !== $e['code'] ? ' <code>' . esc_html( $e['help_code'] ) . '</code>' : '' ) . '</div>';
	}

	public static function head( $kick, $title, $right = '' ) {
		echo '<div class="wrap ohcl"><div class="ohcl-top"><div><p class="ohcl-kick">' . esc_html( $kick ) . '</p><h1>' . esc_html( $title ) . '</h1></div>'
			. '<div class="mute">' . $right . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $right đã escape ở nơi gọi.
		self::flash();
	}

	public static function guard() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Bạn không có quyền quản trị Hub.', 'open-hub-client' ), 403 );
		}
	}

	public static function kpi( $label, $value, $note = '' ) {
		return '<div class="ohcl-card ohcl-kpi"><span>' . esc_html( $label ) . '</span><b>' . esc_html( $value ) . '</b>' . ( $note ? '<small>' . esc_html( $note ) . '</small>' : '' ) . '</div>';
	}

	/* ---------- 01 Tổng quan ---------- */

	public static function page_overview() {
		self::guard();
		$cfg = OHCL_Industry::current();
		self::head( '01 · Tổng quan Hub', ( $cfg['name'] ? $cfg['name'] : 'Hub' ) . ' hôm nay', esc_html( 'Số liệu thật, không ước tính · khoá đọc từ lõi Bizcity (đệm 5 phút)' ) );

		$keys = OHCL_Overview::all_keys();
		$ok   = ! is_wp_error( $keys );
		$s    = OHCL_Overview::from_keys( $ok ? $keys['items'] : array(), gmdate( 'Y-m-d H:i:s' ) );

		echo '<div class="ohcl-q"><i>1</i><h2>Cộng đồng của tôi đang phát triển thế nào?</h2></div>';
		// [2026-11-25 06:49 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1C — câu 1 đếm từ hồ sơ hội viên thật + khoá ở lõi.
		$mc = OHCL_Members::counts( OHCL_Members::all(), $ok ? array_column( $keys['items'], null, 'id' ) : array(), time() );
		echo '<div class="ohcl-g3">' . self::kpi( 'Tổng hội viên', number_format_i18n( $mc['total'] ), 'hồ sơ ở 02 · Hội viên' ) . self::kpi( 'Hội viên hoạt động', number_format_i18n( $mc['active'] ), $mc['total'] ? round( 100 * $mc['active'] / $mc['total'] ) . '% tổng' : '' ) . self::kpi( 'Đã xác minh', number_format_i18n( $mc['verified'] ), 'Verified còn hạn' ) . '</div>'; // phpcs:ignore
		if ( $mc['total'] ) {
			echo '<div class="ohcl-card ohcl-funnel"><h3>Theo phân khúc</h3>';
			$cfg_s = OHCL_Industry::current()['segments'];
			foreach ( array_slice( $mc['by_segment'], 0, 8, true ) as $sg => $n ) {
				echo '<div><span>' . esc_html( isset( $cfg_s[ $sg ] ) ? $cfg_s[ $sg ] : $sg ) . '</span><i><em style="width:' . (int) round( 100 * $n / $mc['total'] ) . '%"></em></i><b>' . (int) $n . '</b></div>';
			}
			echo '</div>';
		} else {
			echo '<p class="mute">Chưa có hồ sơ nào. <a href="' . esc_url( self::url( 'open-hub-members', array( 'view' => 'new' ) ) ) . '">Thêm hội viên đầu tiên</a>.</p>';
		}

		echo '<div class="ohcl-q"><i>2</i><h2>Bao nhiêu hội viên thực sự dùng công nghệ?</h2></div>';
		if ( ! $ok ) {
			self::error_box( self::error_flash( $keys )['error'] );
		} else {
			echo '<div class="ohcl-card ohcl-funnel">';
			foreach ( OHCL_Overview::funnel( $s ) as $r ) {
				echo '<div><span>' . esc_html( $r['label'] ) . '</span><i><em style="width:' . (int) $r['pct'] . '%"></em></i><b>' . (int) $r['n'] . '</b></div>';
			}
			echo '<p class="mute">' . ( $keys['capped'] ? 'Mới đọc 1 000 khoá đầu. ' : '' ) . 'Tính từ khoá của Hub ở lõi: trạng thái, hạn gói, lần dùng gần nhất. Hub không thấy nội dung hội thoại.</p></div>';
		}

		echo '<div class="ohcl-q"><i>3</i><h2>Tôi và các Coach đang giúp được gì?</h2></div><p class="mute">Có ở bản Coach (Đ3).</p>';
		echo '<div class="ohcl-q"><i>4</i><h2>Hub đang tạo ra bao nhiêu doanh thu?</h2></div><p class="mute">Có khi lõi Bizcity mở sổ doanh thu theo Hub (open-hub-core Đ3). Tới lúc đó hiện "—", không ước số.</p>';

		echo '<div class="ohcl-q"><i>5</i><h2>Tôi cần làm gì tiếp theo?</h2></div><div class="ohcl-card ohcl-todo">';
		$todo = OHCL_Overview::todo( $s, OHCL_Industry::report(), $ok );
		if ( ! $todo ) {
			echo '<p class="mute">Không có việc gấp. Cấp khoá cho cơ sở mới ở <a href="' . esc_url( self::url( 'open-hub-keys' ) ) . '">Khoá dùng thử</a>.</p>';
		}
		foreach ( $todo as $t ) {
			echo '<a href="' . esc_url( self::url( $t['page'] ) ) . '"><span>' . esc_html( $t['label'] ) . '</span><b>' . esc_html( (string) $t['n'] ) . ' →</b></a>';
		}
		echo '</div></div>';
	}

	/* ---------- 03 Khoá dùng thử ---------- */

	public static function page_keys() {
		self::guard();
		$cfg = OHCL_Industry::current();
		self::head( '03 · Công nghệ & gói tặng › Khoá dùng thử', 'Khoá trợ lý AI của ' . OHCL_Industry::term( 'business' ), esc_html( 'Khoá nằm ở lõi Bizcity · mỗi khoá thuộc mãi Hub đã cấp' ) );

		// Bước xác nhận thu hồi (không dùng window.confirm).
		$confirm = isset( $_GET['revoke'] ) ? (int) $_GET['revoke'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ hiện bước xác nhận.
		if ( $confirm ) {
			$prefix = isset( $_GET['p'] ) ? sanitize_text_field( wp_unslash( $_GET['p'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="ohcl-card ohcl-confirm"><h3>Thu hồi khoá ' . esc_html( $prefix ) . '?</h3><p>Trợ lý AI của cơ sở dùng khoá này dừng ngay. Sổ sách, bán hàng của cơ sở vẫn chạy. Không hoàn tác được; muốn dùng lại phải cấp khoá mới.</p>'
				. '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'ohcl_revoke_' . $confirm );
			echo '<input type="hidden" name="action" value="ohcl_revoke"><input type="hidden" name="key_id" value="' . (int) $confirm . '">'
				. '<button class="button button-primary ohcl-danger">Thu hồi khoá</button> <a class="button" href="' . esc_url( self::url( 'open-hub-keys' ) ) . '">Huỷ</a></form></div>';
		}

		echo '<div class="ohcl-split"><div>';
		$keys = OHCL_Overview::all_keys();
		if ( is_wp_error( $keys ) ) {
			self::error_box( self::error_flash( $keys )['error'] );
		} elseif ( ! $keys['items'] ) {
			echo '<div class="ohcl-card"><b>Chưa có khoá nào.</b> <span class="mute">Cấp khoá dùng thử đầu tiên ở khung bên phải.</span></div>';
		} else {
			$labels = array( 'trial' => 'Dùng thử', 'active' => 'Đang chạy', 'grace' => 'Ân hạn', 'expired' => 'Hết hạn', 'revoked' => 'Đã thu hồi', 'hub_inactive' => 'Hub tạm dừng' );
			echo '<div class="ohcl-tw"><table class="ohcl-t"><thead><tr><th>' . esc_html( ucfirst( OHCL_Industry::term( 'business' ) ) ) . '</th><th>Khoá</th><th>Gói</th><th>Trạng thái</th><th>Hạn (UTC)</th><th>Dùng gần nhất</th><th>Coach</th><th></th></tr></thead><tbody>';
			foreach ( $keys['items'] as $k ) {
				$st = isset( $labels[ $k['status'] ] ) ? $labels[ $k['status'] ] : $k['status'];
				echo '<tr><td><b>' . esc_html( $k['label'] ) . '</b>' . ( $k['site_url'] ? '<br><span class="mute">' . esc_html( $k['site_url'] ) . '</span>' : '' ) . '</td>'
					. '<td><code>' . esc_html( $k['key_prefix'] ) . '</code></td><td>' . esc_html( $k['plan_code'] ) . '</td>'
					. '<td><span class="ohcl-st ' . esc_attr( $k['status'] ) . '">' . esc_html( $st ) . '</span></td>'
					. '<td>' . esc_html( (string) $k['plan_until'] ) . '</td><td>' . esc_html( $k['last_used_at'] ? $k['last_used_at'] : 'chưa dùng' ) . '</td>'
					. '<td>' . esc_html( (string) $k['coach_ref'] ) . '</td><td>'
					. ( 'revoked' !== $k['status'] ? '<a href="' . esc_url( self::url( 'open-hub-keys', array( 'revoke' => (int) $k['id'], 'p' => $k['key_prefix'] ) ) ) . '">Thu hồi</a>' : '' ) . '</td></tr>';
			}
			echo '</tbody></table></div>';
		}
		echo '</div>';

		echo '<div class="ohcl-card"><h3>Cấp khoá dùng thử</h3><p class="mute">Gói dùng thử ' . (int) $cfg['trial_days'] . ' ngày theo chính sách Hub (lõi còn cắt theo trần). Khoá hiện một lần.</p>'
			. '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'ohcl_issue' );
		echo '<input type="hidden" name="action" value="ohcl_issue">'
			. '<p><label>Tên ' . esc_html( OHCL_Industry::term( 'business' ) ) . ' <span class="req">*</span><br><input name="label" class="regular-text" required maxlength="100"></label></p>'
			. '<p><label>Website của cơ sở<br><input name="site_url" type="url" class="regular-text" placeholder="https://"></label></p>'
			. '<p><label>Mã Coach giới thiệu<br><input name="coach_ref" class="regular-text" maxlength="40" pattern="[a-z0-9-]*"></label></p>'
			. '<p><button class="button button-primary">Cấp khoá</button></p></form></div>';
		echo '</div></div>';
	}

	public static function handle_issue() {
		self::guard();
		check_admin_referer( 'ohcl_issue' );
		$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
		$site  = isset( $_POST['site_url'] ) ? esc_url_raw( wp_unslash( $_POST['site_url'] ) ) : '';
		$coach = isset( $_POST['coach_ref'] ) ? sanitize_key( wp_unslash( $_POST['coach_ref'] ) ) : '';
		$res   = OHCL_Core_Client::issue_key( $label, $site, $coach );
		if ( is_wp_error( $res ) ) {
			self::flash_set( self::error_flash( $res ) );
		} else {
			OHCL_Overview::flush();
			self::flash_set( array( 'key' => (string) $res['key'], 'label' => $label, 'plan_code' => (string) $res['plan_code'], 'plan_until' => (string) $res['plan_until'] ) );
			OHCL_Slots::emit( 'key_issued', array( 'key_id' => (int) $res['id'], 'key_prefix' => (string) $res['key_prefix'], 'label' => $label, 'coach_ref' => $coach ) );
		}
		wp_safe_redirect( self::url( 'open-hub-keys' ) );
		exit;
	}

	public static function handle_revoke() {
		self::guard();
		$id = isset( $_POST['key_id'] ) ? (int) $_POST['key_id'] : 0;
		check_admin_referer( 'ohcl_revoke_' . $id );
		$res = OHCL_Core_Client::revoke_key( $id );
		if ( is_wp_error( $res ) ) {
			self::flash_set( self::error_flash( $res ) );
		} else {
			OHCL_Overview::flush();
			self::flash_set( array( 'ok' => 'Đã thu hồi khoá.' ) );
		}
		wp_safe_redirect( self::url( 'open-hub-keys' ) );
		exit;
	}

	/* ---------- 14 Cài đặt ---------- */

	public static function page_settings() {
		self::guard();
		self::head( '14 · Cài đặt & phân quyền', 'Module, kết nối lõi, dữ liệu ngành', esc_html( 'Kế thừa từ Open Hub Client · ngành chỉ thêm cấu hình' ) );
		echo '<div class="ohcl-split"><div>';

		// Module
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'ohcl_settings' );
		echo '<input type="hidden" name="action" value="ohcl_settings"><input type="hidden" name="part" value="modules">'
			. '<div class="ohcl-tw"><table class="ohcl-t"><thead><tr><th>#</th><th>Module</th><th>Bật</th><th>Quyết định bởi</th></tr></thead><tbody>';
		$src = array( 'required' => 'bắt buộc (lõi)', 'owner' => 'chủ Hub', 'industry' => 'mặc định ngành', 'default' => 'mặc định' );
		foreach ( OHCL_Modules::all() as $id => $m ) {
			echo '<tr><td><code>' . esc_html( $id ) . '</code></td><td>' . esc_html( $m['label'] ) . '</td><td><input type="checkbox" name="mod[' . esc_attr( $id ) . ']" value="1"'
				. checked( $m['enabled'], true, false ) . disabled( $m['required'], true, false ) . '></td><td class="mute">' . esc_html( $src[ $m['source'] ] ) . '</td></tr>';
		}
		echo '</tbody></table></div><p><button class="button button-primary">Lưu module</button> <span class="mute">Tắt module chỉ ẩn khỏi menu và trang; dữ liệu giữ nguyên.</span></p></form>';

		// [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — công tắc trang công khai (thay trang chủ của theme).
		echo '<div class="ohcl-card"><h3>Trang công khai của Hub</h3>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'ohcl_settings' );
		echo '<input type="hidden" name="action" value="ohcl_settings"><input type="hidden" name="part" value="front">'
			. '<p><label><input type="checkbox" name="front_home" value="1"' . checked( OHCL_Front::home_on(), true, false ) . '> Dùng trang chủ cổng ngành của Hub thay trang chủ của theme</label></p>'
			. '<p><label>Màu nhấn của Hub<br><input type="color" name="accent" value="' . esc_attr( OHCL_Industry::current()['brand']['accent'] ) . '"> <label><input type="checkbox" name="accent_reset" value="1"> Trả về màu của plugin ngành</label></label><br><span class="mute">Màu của plugin ngành: <code>' . esc_html( OHCL_Industry::report()['config']['brand']['accent'] ) . '</code>' . ( OHCL_Industry::report()['ok'] ? '' : ' (chưa có dữ liệu ngành — đang là màu mặc định)' ) . '</span></p>' // [2026-11-26 12:51 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX2
			. '<p><label><input type="checkbox" name="front_takeover" value="1"' . checked( OHCL_Front::takeover_on(), true, false ) . '> Vẽ cả site trong khung Hub (bài viết, trang, tin tức, tìm kiếm, 404) — bỏ giao diện theme</label></p>' // [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2
			. '<p><label>Trang giữ giao diện theme (ID, cách nhau bằng dấu phẩy)<br><input name="keep_theme" class="regular-text" value="' . esc_attr( implode( ',', OHCL_Front::keep_theme_ids() ) ) . '"></label><br><span class="mute">Dùng cho trang dựng bằng trình dựng của theme (UX Builder…) cần CSS của theme. Cửa hàng / sản phẩm WooCommerce luôn giữ theme.</span></p>'
			. '<p class="mute">Trang: <a href="' . esc_url( OHCL_Front::url() ) . '" target="_blank" rel="noopener">trang chủ</a> · <a href="' . esc_url( OHCL_Front::url( 'map' ) ) . '" target="_blank" rel="noopener">bản đồ</a> · <a href="' . esc_url( OHCL_Front::url( 'join' ) ) . '" target="_blank" rel="noopener">trở thành hội viên</a>. Chỉ hồ sơ V4 (đã xác minh, đồng ý công khai, có toạ độ, khoá đang dùng) hiện ra ngoài.</p>'
			. '<p><button class="button">Lưu</button></p></form></div>';

		// Báo cáo dữ liệu ngành
		$r = OHCL_Industry::report();
		$c = $r['config'];
		echo '<div class="ohcl-card"><h3>Dữ liệu ngành <code>' . esc_html( OHCL_Industry::CONTRACT ) . '</code></h3>';
		echo $r['ok'] ? '<p><span class="ohcl-st ok">ĐẠT</span> ' . esc_html( $c['name'] . ' · ' . $c['industry'] . ' · ' . ( $c['plugin'] ? $c['plugin'] . ' ' . $c['version'] : '' ) ) . '</p>' : '<p><span class="ohcl-st bad">CHƯA ĐẠT</span> site đang chạy cấu hình mặc định.</p>'; // phpcs:ignore
		foreach ( array_merge( $r['errors'], $r['warnings'] ) as $line ) {
			echo '<p class="mute">· ' . esc_html( $line ) . '</p>';
		}
		echo '<p class="mute">' . (int) count( $c['segments'] ) . ' phân khúc · ' . (int) count( $c['member_fields'] ) . ' trường hồ sơ · ' . (int) count( $c['standards'] ) . ' tiêu chuẩn · ' . (int) count( $c['knowledge_assets'] ) . ' tài sản tri thức. Kiểm lại: <code>wp ohcl validate-industry</code></p></div>';
		echo '</div>';

		// [2026-11-25 06:17 PM Johnny Chu - Chu Hoàng Anh] OHCL-PAIR — kết nối lõi bằng mã ghép nối (3 bước, không ai chép bí mật); dán bí mật tay chỉ còn ở "Nâng cao".
		$st   = OHCL_Core_Client::state();
		$post = esc_url( admin_url( 'admin-post.php' ) );
		echo '<div class="ohcl-card ohcl-pair"><h3>Kết nối lõi Bizcity</h3>';
		if ( $st['has_secret'] && $st['base_url'] ) {
			echo '<p><span class="ohcl-st ok">ĐÃ KẾT NỐI</span> Hub <code>' . esc_html( $st['hub'] ) . '</code> tại ' . esc_html( $st['base_url'] ) . '</p>';
		} else {
			echo '<p><span class="ohcl-st bad">CHƯA KẾT NỐI</span> chưa cấp được khoá cho cơ sở.</p>';
		}
		echo '<ol class="ohcl-steps"><li>Nhờ Bizcity (hoặc tự vào <b>bizcity.vn › Open Hub Core › Sổ Hub</b>) bấm <b>Tạo mã ghép nối</b> cho Hub này.</li><li>Dán mã 8 ký tự vào ô dưới (mã sống 10 phút, dùng một lần).</li><li>Bấm <b>Kết nối</b>. Site tự nhận bí mật từ lõi và tự kiểm tra.</li></ol>'
			. '<form method="post" action="' . $post . '">';
		wp_nonce_field( 'ohcl_pair' );
		echo '<input type="hidden" name="action" value="ohcl_pair">'
			. '<p><label>Địa chỉ lõi<br><input name="base_url" type="url" class="regular-text" required value="' . esc_attr( $st['base_url'] ? $st['base_url'] : 'https://bizcity.vn' ) . '"></label></p>'
			. '<p><label>Mã ghép nối<br><input name="code" class="ohcl-code" required maxlength="9" autocomplete="off" spellcheck="false" placeholder="ABCD-EFGH"' . disabled( $st['by_constant'], true, false ) . '></label></p>'
			. '<p><button class="button button-primary"' . disabled( $st['by_constant'], true, false ) . '>' . ( $st['has_secret'] ? 'Kết nối lại' : 'Kết nối' ) . '</button></p></form>';
		if ( $st['by_constant'] ) {
			echo '<p class="mute">Bí mật đang khoá bằng OHCL_CORE_SECRET trong wp-config.php. Xoá dòng đó để kết nối bằng mã.</p>';
		}
		echo '<form method="post" action="' . $post . '">';
		wp_nonce_field( 'ohcl_ping' );
		echo '<input type="hidden" name="action" value="ohcl_ping"><button class="button">Kiểm tra kết nối</button></form>';

		echo '<details class="ohcl-adv"><summary>Nâng cao: nhập tay</summary><form method="post" action="' . $post . '">';
		wp_nonce_field( 'ohcl_settings' );
		echo '<input type="hidden" name="action" value="ohcl_settings"><input type="hidden" name="part" value="core">'
			. '<p><label>Địa chỉ lõi (https)<br><input name="base_url" type="url" class="regular-text" placeholder="https://bizcity.vn" value="' . esc_attr( $st['base_url'] ) . '"></label></p>'
			. '<p><label>Mã Hub ở lõi<br><input name="hub" class="regular-text" pattern="[a-z0-9-]{2,20}" placeholder="' . esc_attr( OHCL_Industry::current()['id'] ) . '" value="' . esc_attr( $st['hub'] ) . '"></label></p>'
			. '<p><label>Bí mật Hub<br><input name="secret" type="password" class="regular-text" autocomplete="off"' . disabled( $st['by_constant'], true, false ) . ' placeholder="' . esc_attr( $st['has_secret'] ? 'đã đặt — nhập để thay' : 'ohs_…' ) . '"></label></p>'
			. '<p><button class="button">Lưu</button> <span class="mute">Không bao giờ hiện lại bí mật.</span></p></form></details></div>';
		echo '</div></div>';
	}

	public static function handle_settings() {
		self::guard();
		check_admin_referer( 'ohcl_settings' );
		$part = isset( $_POST['part'] ) ? sanitize_key( wp_unslash( $_POST['part'] ) ) : '';
		if ( 'modules' === $part ) {
			$on = isset( $_POST['mod'] ) && is_array( $_POST['mod'] ) ? array_map( 'sanitize_key', array_keys( wp_unslash( $_POST['mod'] ) ) ) : array();
			foreach ( OHCL_Modules::LIST as $id => $m ) {
				if ( ! $m['required'] ) {
					OHCL_Modules::set_enabled( $id, in_array( (string) $id, $on, true ) );
				}
			}
			self::flash_set( array( 'ok' => 'Đã lưu module.' ) );
		} elseif ( 'front' === $part ) {
			update_option( OHCL_Front::HOME_OPTION, empty( $_POST['front_home'] ) ? '0' : '1', false );
			update_option( OHCL_Front::TAKE_OPTION, empty( $_POST['front_takeover'] ) ? '0' : '1', false );
			$acc = isset( $_POST['accent'] ) ? strtolower( sanitize_text_field( wp_unslash( $_POST['accent'] ) ) ) : ''; // [2026-11-26 12:51 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX2
			// [2026-11-26 01:11 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX3 — màu chọn được lưu (trước đây ô "Dùng màu của plugin ngành" tích sẵn nên màu bị bỏ); trùng màu plugin ngành thì không cần lưu riêng.
			$ind_acc = OHCL_Industry::report()['config']['brand']['accent'];
			update_option( OHCL_Industry::ACCENT_OPTION, empty( $_POST['accent_reset'] ) && preg_match( '/^#[0-9a-f]{6}$/', $acc ) && $acc !== $ind_acc ? $acc : '', false ); // [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2
			update_option( OHCL_Front::KEEP_OPTION, implode( ',', array_filter( array_map( 'absint', explode( ',', isset( $_POST['keep_theme'] ) ? sanitize_text_field( wp_unslash( $_POST['keep_theme'] ) ) : '' ) ) ) ), false );
			self::flash_set( array( 'ok' => empty( $_POST['front_home'] ) ? 'Đã trả trang chủ về theme.' : 'Trang chủ giờ là cổng ngành của Hub.' ) );
		} elseif ( 'core' === $part ) {
			$base = isset( $_POST['base_url'] ) ? untrailingslashit( esc_url_raw( wp_unslash( $_POST['base_url'] ) ) ) : '';
			$hub  = isset( $_POST['hub'] ) ? sanitize_key( wp_unslash( $_POST['hub'] ) ) : '';
			$sec  = isset( $_POST['secret'] ) ? trim( (string) wp_unslash( $_POST['secret'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- kiểm dạng ngay dưới.
			// [2026-11-25 06:44 PM Johnny Chu - Chu Hoàng Anh] OHCL-PAIR2 — dán mã ghép nối vào ô "Mã Hub" / "Bí mật" ⇒ hiểu là mã ghép nối và kết nối luôn (trước đây sanitize_key biến 7C72-39ZP thành mã Hub sai).
			$raw_hub = isset( $_POST['hub'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['hub'] ) ) ) : '';
			$code    = OHCL_Admin::pair_code_in_manual_form( $raw_hub, $sec );
			if ( '' !== $code ) {
				self::finish_pair( OHCL_Core_Client::pair( $base ? $base : 'https://bizcity.vn', $code ) );
			}
			$test = OHCL_Core_Client::check_config( array( 'base_url' => $base, 'hub' => $hub ? $hub : 'xx', 'secret' => 'ohs_' . str_repeat( '0', 32 ) ) );
			if ( true !== $test ) {
				self::flash_set( self::error_flash( $test ) );
			} elseif ( '' !== $sec && ! preg_match( '/^ohs_[a-f0-9]{32}$/', $sec ) ) {
				self::flash_set( self::error_flash( OHCL_Error::make( 'ohcl_core_secret_invalid', 'Bí mật Hub không đúng dạng.', 'Dán đúng chuỗi ohs_… mà Bizcity cấp, không thêm khoảng trắng.', 400 ) ) );
			} else {
				update_option( OHCL_Core_Client::OPTION, array( 'base_url' => $base, 'hub' => $hub ), false );
				if ( '' !== $sec && ! defined( 'OHCL_CORE_SECRET' ) ) {
					update_option( OHCL_Core_Client::SECRET_OPTION, $sec, false );
				}
				OHCL_Overview::flush();
				self::flash_set( array( 'ok' => 'Đã lưu kết nối. Bấm "Kiểm tra kết nối" để thử.' ) );
			}
		}
		wp_safe_redirect( self::url( 'open-hub-settings' ) );
		exit;
	}

	public static function handle_ping() {
		self::guard();
		check_admin_referer( 'ohcl_ping' );
		$r = OHCL_Core_Client::ping();
		self::flash_set( array( 'ok' => $r['ok'] ? 'Kết nối lõi đạt.' : '', 'steps' => $r['steps'] ) );
		wp_safe_redirect( self::url( 'open-hub-settings' ) );
		exit;
	}

	public static function handle_pair() {
		// [2026-11-25 06:17 PM Johnny Chu - Chu Hoàng Anh] OHCL-PAIR — dán mã ⇒ lõi trả bí mật ⇒ lưu ⇒ tự kiểm 4 bước.
		self::guard();
		check_admin_referer( 'ohcl_pair' );
		$base = isset( $_POST['base_url'] ) ? esc_url_raw( wp_unslash( $_POST['base_url'] ) ) : '';
		$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
		self::finish_pair( OHCL_Core_Client::pair( $base, $code ) );
	}

	/**
	 * A pairing code pasted into the manual form. Pure.
	 * Secret box: any pairing-code shape. Hub box: only the exact "XXXX-XXXX" shape and only when the
	 * secret box holds no valid ohs_ secret — so a real slug like "vegan-hub" is never taken for a code.
	 *
	 * @return string the code, or ''
	 */
	public static function pair_code_in_manual_form( $hub_raw, $secret_raw ) {
		// [2026-11-25 06:44 PM Johnny Chu - Chu Hoàng Anh] OHCL-PAIR2 — nhận mã ghép nối dán nhầm ô, không nhầm mã Hub thật.
		$secret_raw = trim( (string) $secret_raw );
		if ( OHCL_Core_Client::looks_like_pair_code( $secret_raw ) ) {
			return $secret_raw;
		}
		$valid_secret = (bool) preg_match( '/^ohs_[a-f0-9]{32}$/', $secret_raw );
		if ( ! $valid_secret && preg_match( '/^[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/i', trim( (string) $hub_raw ) ) ) {
			return trim( (string) $hub_raw );
		}
		return '';
	}

	/** Flash the pairing result (+ the 4-step check) and go back to Settings. */
	private static function finish_pair( $res ) {
		// [2026-11-25 06:44 PM Johnny Chu - Chu Hoàng Anh] OHCL-PAIR2 — một chỗ kết thúc ghép nối cho cả ô mã và form nhập tay.
		if ( is_wp_error( $res ) ) {
			self::flash_set( self::error_flash( $res ) );
		} else {
			OHCL_Overview::flush();
			$ping = OHCL_Core_Client::ping();
			$msg  = 'Đã kết nối Hub ' . $res['hub']['name'] . ' (' . $res['hub']['slug'] . ').';
			if ( $res['mismatch'] ) {
				$msg .= ' Lưu ý: mã Hub ở lõi khác mã của plugin ngành (' . OHCL_Industry::current()['id'] . '); site dùng mã ở lõi.';
			}
			self::flash_set( array( 'ok' => $msg, 'steps' => $ping['steps'] ) );
		}
		wp_safe_redirect( self::url( 'open-hub-settings' ) );
		exit;
	}
}
