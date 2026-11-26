<?php
/**
 * Hub › Dữ liệu mẫu: sample businesses + sample posts supplied by the industry plugin (OHCL_Demo).
 * One button each: seed, publish all, draft all, delete all; deleting needs a confirm tick. Real data is
 * never touched (only records with the hidden demo meta).
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-DEMO — màn Dữ liệu mẫu: nhập / xoá cơ sở mẫu, đăng hết / nháp hết / xoá hết bài mẫu, chế độ tự ẩn.

defined( 'ABSPATH' ) || exit;

final class OHCL_Admin_Demo {

	const PAGE = 'open-hub-demo';

	const ACTIONS = array( 'seed_members', 'delete_members', 'seed_posts', 'publish_posts', 'draft_posts', 'delete_posts', 'mode' );

	public static function boot() {
		add_action( 'admin_post_ohcl_demo', array( __CLASS__, 'handle' ) );
	}

	private static function form( $do, $label, $class = 'button', $extra = '' ) {
		$h  = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin:0 8px 8px 0">';
		$h .= '<input type="hidden" name="_wpnonce" value="' . esc_attr( wp_create_nonce( 'ohcl_demo' ) ) . '"><input type="hidden" name="action" value="ohcl_demo"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
		return $h . $extra . '<button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';
	}

	public static function page() {
		OHCL_Admin::guard();
		$pub = OHCL_Members::public_list();
		$st  = OHCL_Demo::status( isset( $pub['real'] ) ? $pub['real'] : 0 );
		$cfg = OHCL_Industry::current();
		$src = $cfg['plugin'] ? $cfg['plugin'] : 'plugin ngành';
		OHCL_Admin::head( 'Hub › Dữ liệu mẫu', 'Dữ liệu mẫu để xem thử cổng' );
		echo '<p class="mute">Dữ liệu mẫu do <b>' . esc_html( $src ) . '</b> cung cấp. Cơ sở mẫu có chữ "(mẫu)", không điện thoại, không khoá, không chứng nhận, <b>không tính vào số liệu</b>. Bài mẫu ghi rõ là bài mẫu ở cuối bài. Chỉ bản ghi có dấu mẫu mới bị xoá; dữ liệu thật không bị đụng tới.</p>';

		if ( ! $st['pack_members'] && ! $st['pack_posts'] ) {
			echo '<div class="ohcl-card"><p class="ohcl-st grace">Plugin ngành chưa có dữ liệu mẫu</p><p class="mute">Plugin ngành cung cấp qua filter <code>' . esc_html( OHCL_Demo::FILTER ) . '</code> (OPEN-HUB-CLIENT §12a).</p></div></div>';
			return;
		}
		echo '<div class="ohcl-g3 ohcl-g4">'
			. OHCL_Admin::kpi( 'Cơ sở mẫu đã nhập', number_format_i18n( $st['seeded'] ) . ' / ' . number_format_i18n( $st['pack_members'] ) ) // phpcs:ignore WordPress.Security.EscapeOutput -- kpi() tự escape.
			. OHCL_Admin::kpi( 'Cơ sở thật công khai', number_format_i18n( $st['real'] ), 'ngưỡng tự ẩn: ' . $st['threshold'] ) // phpcs:ignore WordPress.Security.EscapeOutput
			. OHCL_Admin::kpi( 'Bài mẫu đang đăng', number_format_i18n( $st['posts']['publish'] ), 'nháp: ' . $st['posts']['draft'] ) // phpcs:ignore WordPress.Security.EscapeOutput
			. OHCL_Admin::kpi( 'Trên cổng', $st['visible'] ? 'Đang hiện' : 'Đang ẩn' ) // phpcs:ignore WordPress.Security.EscapeOutput
			. '</div>';
		echo '<div class="ohcl-card"><h3>Trạng thái</h3><p><span class="ohcl-st ' . ( $st['visible'] ? 'ok' : 'grace' ) . '">' . esc_html( $st['reason'] ) . '</span></p>';
		if ( $st['skipped'] ) {
			echo '<p class="mute">Bỏ qua ' . (int) count( $st['skipped'] ) . ' dòng sai dạng trong dữ liệu ngành: ' . esc_html( implode( ', ', array_slice( $st['skipped'], 0, 8 ) ) ) . '</p>';
		}
		echo '</div>';

		$confirm = '<label style="margin-right:6px"><input type="checkbox" name="confirm" value="1"> chắc chắn</label>';
		echo '<div class="ohcl-split"><div class="ohcl-card"><h3>Cơ sở mẫu (bản đồ, danh bạ, giao thương)</h3>';
		echo '<p class="mute">' . (int) $st['pack_members'] . ' cơ sở mẫu trong dữ liệu ngành. Nhập lại không tạo trùng.</p>';
		echo self::form( 'seed_members', $st['seeded'] ? 'Nhập phần còn thiếu' : 'Nhập ' . $st['pack_members'] . ' cơ sở mẫu', 'button button-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput -- form() tự escape.
		if ( $st['seeded'] ) {
			echo self::form( 'delete_members', 'Xoá hết cơ sở mẫu', 'button', $confirm ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '<h3 style="margin-top:16px">Chế độ</h3>';
		$radio = '<label><input type="radio" name="mode" value="auto"' . checked( 'auto', $st['mode'], false ) . '> Tự động: hiện công khai có nhãn "Mẫu", tự ẩn khi đủ ' . (int) $st['threshold'] . ' cơ sở thật</label><br>'
			. '<label><input type="radio" name="mode" value="off"' . checked( 'off', $st['mode'], false ) . '> Tắt: ẩn cơ sở mẫu ngay (dữ liệu vẫn giữ)</label><br>';
		echo self::form( 'mode', 'Lưu chế độ', 'button', $radio ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div>';

		echo '<div class="ohcl-card"><h3>Bài viết mẫu</h3>';
		echo '<p class="mute">' . (int) $st['pack_posts'] . ' bài mẫu (' . (int) count( OHCL_Demo::pack()['categories'] ) . ' chuyên mục). Nhập là đăng ngay; nhập cơ sở mẫu trước để link trong bài trỏ đúng hồ sơ.</p>';
		echo self::form( 'seed_posts', 'Nhập bài mẫu (đăng ngay)', 'button button-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput
		if ( array_sum( $st['posts'] ) ) {
			echo '<br>' . self::form( 'publish_posts', 'Đăng hết bài mẫu' ) . self::form( 'draft_posts', 'Chuyển nháp hết' ) . self::form( 'delete_posts', 'Xoá hết bài mẫu', 'button', $confirm ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<p class="mute">Bài mẫu bạn đã sửa vẫn là bài mẫu (còn dấu ẩn). Muốn giữ thành bài thật: xoá đoạn "Bài mẫu…" cuối bài và bỏ trường tuỳ chỉnh <code>' . esc_html( OHCL_Demo::POST_META ) . '</code>.</p>';
		}
		echo '<p class="mute">Lệnh tương ứng: <code>wp ohcl demo seed</code> · <code>wp ohcl demo posts publish|draft|delete</code> · <code>wp ohcl demo remove</code></p>';
		echo '</div></div></div>';
	}

	public static function handle() {
		OHCL_Admin::guard();
		check_admin_referer( 'ohcl_demo' );
		$do = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		if ( ! in_array( $do, self::ACTIONS, true ) ) {
			$do = '';
		}
		$sure = ! empty( $_POST['confirm'] );
		if ( in_array( $do, array( 'delete_members', 'delete_posts' ), true ) && ! $sure ) {
			OHCL_Admin::flash_set( OHCL_Admin::error_flash( OHCL_Error::make( 'ohcl_demo_confirm', 'Chưa xoá: cần đánh dấu "chắc chắn".', 'Đánh dấu ô "chắc chắn" cạnh nút rồi bấm lại.', 400 ) ) );
			wp_safe_redirect( OHCL_Admin::url( self::PAGE ) );
			exit;
		}
		switch ( $do ) {
			case 'seed_members':
				$r   = OHCL_Demo::seed_members();
				$msg = 'Đã nhập ' . $r['added'] . ' cơ sở mẫu' . ( $r['kept'] ? ' (' . $r['kept'] . ' đã có, giữ nguyên)' : '' ) . '.';
				break;
			case 'delete_members':
				$msg = 'Đã xoá ' . OHCL_Demo::delete_members() . ' cơ sở mẫu.';
				break;
			case 'seed_posts':
				$r   = OHCL_Demo::seed_posts( 'publish' );
				$msg = 'Đã đăng ' . $r['added'] . ' bài mẫu' . ( $r['kept'] ? ' (' . $r['kept'] . ' đã có)' : '' ) . '.';
				break;
			case 'publish_posts':
				$msg = 'Đã đăng ' . OHCL_Demo::set_posts_status( 'publish' ) . ' bài mẫu.';
				break;
			case 'draft_posts':
				$msg = 'Đã chuyển ' . OHCL_Demo::set_posts_status( 'draft' ) . ' bài mẫu về nháp.';
				break;
			case 'delete_posts':
				$msg = 'Đã xoá ' . OHCL_Demo::delete_posts() . ' bài mẫu.';
				break;
			case 'mode':
				OHCL_Demo::set_mode( isset( $_POST['mode'] ) && 'off' === $_POST['mode'] ? 'off' : 'auto' );
				$msg = 'off' === OHCL_Demo::mode() ? 'Đã tắt dữ liệu mẫu trên cổng.' : 'Dữ liệu mẫu chạy chế độ tự động.';
				break;
			default:
				$msg = '';
		}
		OHCL_Admin::flash_set( '' !== $msg ? array( 'ok' => $msg ) : OHCL_Admin::error_flash( OHCL_Error::make( 'ohcl_demo_action', 'Thao tác không hợp lệ.', 'Tải lại trang rồi bấm lại nút.', 400 ) ) );
		wp_safe_redirect( OHCL_Admin::url( self::PAGE ) );
		exit;
	}
}
