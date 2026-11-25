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
	}

	public static function menu() {
		$cfg  = OHCL_Industry::current();
		$name = $cfg['name'] ? $cfg['name'] : 'Hub';
		add_menu_page( $name, $name, self::CAP, 'open-hub', array( __CLASS__, 'page_overview' ), 'dashicons-networking', 3 );
		add_submenu_page( 'open-hub', 'Tổng quan', '01 · Tổng quan', self::CAP, 'open-hub', array( __CLASS__, 'page_overview' ) );
		add_submenu_page( 'open-hub', 'Khoá dùng thử', '03 · Khoá dùng thử', self::CAP, 'open-hub-keys', array( __CLASS__, 'page_keys' ) );
		add_submenu_page( 'open-hub', 'Cài đặt', '14 · Cài đặt', self::CAP, 'open-hub-settings', array( __CLASS__, 'page_settings' ) );
	}

	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'open-hub' ) ) {
			return;
		}
		wp_enqueue_style( 'ohcl-admin', plugins_url( 'assets/admin.css', OHCL_DIR . 'open-hub-client.php' ), array(), OHCL_VERSION );
		$cfg = OHCL_Industry::current();
		wp_add_inline_style( 'ohcl-admin', '.ohcl{--a:' . $cfg['brand']['accent'] . '}' ); // accent đã kiểm #rrggbb
	}

	/* ---------- helpers ---------- */

	private static function url( $page, array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
	}

	private static function flash_key() {
		return 'ohcl_flash_' . get_current_user_id();
	}

	private static function flash_set( array $f ) {
		set_transient( self::flash_key(), $f, 2 * MINUTE_IN_SECONDS );
	}

	private static function error_flash( WP_Error $e ) {
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

	private static function flash() {
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

	private static function error_box( array $e ) {
		echo '<div class="ohcl-note bad"><b>' . esc_html( $e['message'] ) . '</b> ' . esc_html( $e['hint'] )
			. ' <code>' . esc_html( $e['code'] ) . '</code>'
			. ( ! empty( $e['help_code'] ) && $e['help_code'] !== $e['code'] ? ' <code>' . esc_html( $e['help_code'] ) . '</code>' : '' ) . '</div>';
	}

	private static function head( $kick, $title, $right = '' ) {
		echo '<div class="wrap ohcl"><div class="ohcl-top"><div><p class="ohcl-kick">' . esc_html( $kick ) . '</p><h1>' . esc_html( $title ) . '</h1></div>'
			. '<div class="mute">' . $right . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $right đã escape ở nơi gọi.
		self::flash();
	}

	private static function guard() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Bạn không có quyền quản trị Hub.', 'open-hub-client' ), 403 );
		}
	}

	private static function kpi( $label, $value, $note = '' ) {
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
		echo '<div class="ohcl-g3">' . self::kpi( 'Tổng hội viên', '—', 'có ở bản Member 360° (Đ1c)' ) . self::kpi( 'Hội viên hoạt động', '—', 'chưa có nguồn đếm' ) . self::kpi( 'Đã xác minh', '—', 'chưa có nguồn đếm' ) . '</div>'; // phpcs:ignore

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

		// Kết nối lõi
		$o        = get_option( OHCL_Core_Client::OPTION );
		$o        = is_array( $o ) ? $o : array();
		$by_const = defined( 'OHCL_CORE_SECRET' );
		$has      = $by_const || '' !== (string) get_option( OHCL_Core_Client::SECRET_OPTION, '' );
		echo '<div class="ohcl-card"><h3>Kết nối lõi Bizcity</h3><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'ohcl_settings' );
		echo '<input type="hidden" name="action" value="ohcl_settings"><input type="hidden" name="part" value="core">'
			. '<p><label>Địa chỉ lõi (https)<br><input name="base_url" type="url" class="regular-text" placeholder="https://bizcity.vn" value="' . esc_attr( isset( $o['base_url'] ) ? $o['base_url'] : '' ) . '"></label></p>'
			. '<p><label>Mã Hub ở lõi<br><input name="hub" class="regular-text" pattern="[a-z0-9-]{2,20}" placeholder="' . esc_attr( OHCL_Industry::current()['id'] ) . '" value="' . esc_attr( isset( $o['hub'] ) ? $o['hub'] : '' ) . '"></label><br><span class="mute">Để trống: dùng mã của plugin ngành.</span></p>'
			. '<p><label>Bí mật Hub<br><input name="secret" type="password" class="regular-text" autocomplete="off"' . disabled( $by_const, true, false ) . ' placeholder="' . esc_attr( $has ? 'đã đặt — nhập để thay' : 'ohs_…' ) . '"></label><br><span class="mute">'
			. ( $by_const ? 'Đang đặt bằng OHCL_CORE_SECRET trong wp-config.php.' : 'Nên đặt bằng hằng OHCL_CORE_SECRET trong wp-config.php. Không bao giờ hiện lại.' ) . '</span></p>'
			. '<p><button class="button button-primary">Lưu kết nối</button></p></form>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'ohcl_ping' );
		echo '<input type="hidden" name="action" value="ohcl_ping"><button class="button">Kiểm tra kết nối</button></form></div>';
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
		} elseif ( 'core' === $part ) {
			$base = isset( $_POST['base_url'] ) ? untrailingslashit( esc_url_raw( wp_unslash( $_POST['base_url'] ) ) ) : '';
			$hub  = isset( $_POST['hub'] ) ? sanitize_key( wp_unslash( $_POST['hub'] ) ) : '';
			$sec  = isset( $_POST['secret'] ) ? trim( (string) wp_unslash( $_POST['secret'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- kiểm dạng ngay dưới.
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
}
