<?php
/**
 * Hub › 04 · Coach: coach applications from /tro-thanh-coach/ (new · approved · declined). Shown while module
 * 04 is on. Role `hub_coach`, assignments and commissions are wave Đ3.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — màn 04 · Coach: danh sách hồ sơ ứng tuyển, duyệt / không phù hợp.

defined( 'ABSPATH' ) || exit;

final class OHCL_Admin_Coach {

	const PAGE = 'open-hub-coach';

	public static function boot() {
		add_action( 'admin_post_ohcl_coach_decide', array( __CLASS__, 'handle_decide' ) );
	}

	public static function on() {
		return OHCL_Modules::enabled( '04' );
	}

	public static function page() {
		OHCL_Admin::guard();
		$cfg    = OHCL_Industry::current();
		$skills = array();
		foreach ( $cfg['coach_skills'] as $s ) {
			$skills[ $s['code'] ] = $s['label'];
		}
		$all = OHCL_Coach::all();
		OHCL_Admin::head( '04 · Coach & chuyên gia', 'Hồ sơ ứng tuyển Coach', '<a class="button" href="' . esc_url( OHCL_Front::url( 'becoach' ) ) . '" target="_blank" rel="noopener">Trang ứng tuyển ↗</a>' );
		$cnt = array( 'new' => 0, 'approved' => 0, 'declined' => 0 );
		foreach ( $all as $a ) {
			$cnt[ $a['status'] ]++;
		}
		echo '<div class="ohcl-g3">' . OHCL_Admin::kpi( 'Mới', number_format_i18n( $cnt['new'] ), 'chờ xem' ) . OHCL_Admin::kpi( 'Đã duyệt', number_format_i18n( $cnt['approved'] ) ) . OHCL_Admin::kpi( 'Không phù hợp', number_format_i18n( $cnt['declined'] ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- kpi() tự escape.
		echo '<div class="ohcl-card">';
		if ( ! $all ) {
			echo '<p class="mute">Chưa có hồ sơ ứng tuyển. Chia sẻ trang "Trở thành Coach" để tuyển.</p></div></div>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>Họ tên</th><th>Chuyên môn</th><th>Khu vực · năm</th><th>Liên hệ</th><th>Giới thiệu</th><th>Trạng thái</th><th></th></tr></thead><tbody>';
		foreach ( $all as $a ) {
			$sk = array_map( function ( $c ) use ( $skills ) { return isset( $skills[ $c ] ) ? $skills[ $c ] : $c; }, $a['skills'] );
			echo '<tr><td><b>' . esc_html( $a['name'] ) . '</b><br><span class="mute">' . esc_html( substr( $a['at'], 0, 10 ) ) . '</span></td><td>' . esc_html( implode( ', ', $sk ) ) . '</td><td>' . esc_html( trim( $a['area'] . ' · ' . $a['years'] . ' năm', ' ·' ) ) . '</td><td>' . esc_html( $a['contact'] ) . '</td><td>' . esc_html( $a['note'] ) . '</td>'
				. '<td><span class="ohcl-st ' . esc_attr( 'approved' === $a['status'] ? 'ok' : ( 'declined' === $a['status'] ? 'revoked' : 'trial' ) ) . '">' . esc_html( OHCL_Coach::STATUS[ $a['status'] ] ) . '</span></td><td>';
			foreach ( array( 'approved' => 'Duyệt', 'declined' => 'Không phù hợp' ) as $st => $lb ) {
				if ( $st === $a['status'] ) {
					continue;
				}
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
				wp_nonce_field( 'ohcl_coach_decide_' . $a['id'] );
				echo '<input type="hidden" name="action" value="ohcl_coach_decide"><input type="hidden" name="id" value="' . (int) $a['id'] . '"><input type="hidden" name="status" value="' . esc_attr( $st ) . '"><button class="button button-small">' . esc_html( $lb ) . '</button></form> ';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table><p class="mute">Duyệt là ghi nhận của ban điều hành. Cấp vai Coach, phân công hội viên và hoa hồng mở ở đợt Đ3.</p></div></div>';
	}

	public static function handle_decide() {
		OHCL_Admin::guard();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		check_admin_referer( 'ohcl_coach_decide_' . $id );
		$r = OHCL_Coach::decide( $id, isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '' );
		OHCL_Admin::flash_set( is_wp_error( $r ) ? OHCL_Admin::error_flash( $r ) : array( 'ok' => 'Đã cập nhật hồ sơ ứng tuyển.' ) );
		wp_safe_redirect( OHCL_Admin::url( self::PAGE ) );
		exit;
	}
}
