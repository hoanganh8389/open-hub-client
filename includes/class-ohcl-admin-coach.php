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
		add_action( 'admin_post_ohcl_coach_activate', array( __CLASS__, 'handle_activate' ) ); // [2026-11-26 11:15 AM Johnny Chu - Chu Hoàng Anh] OHCL-D3A
		add_action( 'admin_post_ohcl_affiliate', array( __CLASS__, 'handle_affiliate' ) );
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
			echo '<p class="mute">Chưa có hồ sơ ứng tuyển. Chia sẻ trang "Trở thành Coach" để tuyển.</p></div>';
			self::affiliate_blocks();
			echo '</div>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>Họ tên</th><th>Chuyên môn</th><th>Khu vực · năm</th><th>Liên hệ</th><th>Giới thiệu</th><th>Trạng thái</th><th></th></tr></thead><tbody>';
		foreach ( $all as $a ) {
			$sk = array_map( function ( $c ) use ( $skills ) { return isset( $skills[ $c ] ) ? $skills[ $c ] : $c; }, $a['skills'] );
			echo '<tr><td><b>' . esc_html( $a['name'] ) . '</b><br><span class="mute">' . esc_html( substr( $a['at'], 0, 10 ) ) . '</span></td><td>' . esc_html( implode( ', ', $sk ) ) . '</td><td>' . esc_html( trim( $a['area'] . ' · ' . $a['years'] . ' năm', ' ·' ) ) . '</td><td>' . esc_html( $a['contact'] ) . '</td><td>' . esc_html( $a['note'] ) . '</td>'
				. '<td><span class="ohcl-st ' . esc_attr( 'approved' === $a['status'] ? 'ok' : ( 'declined' === $a['status'] ? 'revoked' : 'trial' ) ) . '">' . esc_html( OHCL_Coach::STATUS[ $a['status'] ] ) . '</span></td><td>';
			if ( 'approved' === $a['status'] && class_exists( 'OHCL_Affiliate' ) ) { // [2026-11-26 11:15 AM Johnny Chu - Chu Hoàng Anh] OHCL-D3A — cấp vai Coach cho hồ sơ đã duyệt
				$uid = (int) get_post_meta( $a['id'], '_bzca_user', true );
				if ( $uid ) {
					echo '<span class="ohcl-st ok">Đã cấp tài khoản</span> ';
				} else {
					echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
					wp_nonce_field( 'ohcl_coach_activate_' . $a['id'] );
					echo '<input type="hidden" name="action" value="ohcl_coach_activate"><input type="hidden" name="id" value="' . (int) $a['id'] . '"><input type="email" name="email" required placeholder="email của Coach" value="' . esc_attr( false !== strpos( $a['contact'], '@' ) ? $a['contact'] : '' ) . '" style="width:180px"> <button class="button button-primary button-small">Cấp vai Coach</button></form> ';
				}
			}
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
		echo '</tbody></table><p class="mute">Duyệt là ghi nhận của ban điều hành. "Cấp vai Coach" tạo tài khoản (hoặc gắn vai vào tài khoản có sẵn cùng email), gửi link đặt mật khẩu và cấp mã giới thiệu.</p></div>';
		self::affiliate_blocks();
		echo '</div>';
	}

	private static function vnd( $n ) {
		return number_format_i18n( (int) $n ) . ' đ';
	}

	/** Policy, Coaches (referral link, totals) and monthly payouts. */
	private static function affiliate_blocks() {
		// [2026-11-26 11:15 AM Johnny Chu - Chu Hoàng Anh] OHCL-D3A — chính sách affiliate, danh sách Coach + hoa hồng, kỳ chi trả theo tháng.
		if ( ! class_exists( 'OHCL_Affiliate' ) ) {
			return;
		}
		$pol     = OHCL_Affiliate::policy();
		$coaches = OHCL_Affiliate::coaches();
		$pays    = OHCL_Affiliate::payouts();
		$members = OHCL_Members::all();
		$act     = esc_url( admin_url( 'admin-post.php' ) );

		echo '<div class="ohcl-card"><h3>Chính sách affiliate</h3><form method="post" action="' . $act . '">'; // phpcs:ignore WordPress.Security.EscapeOutput -- esc_url ở trên.
		wp_nonce_field( 'ohcl_affiliate' );
		echo '<input type="hidden" name="action" value="ohcl_affiliate"><input type="hidden" name="do" value="policy">'
			. '<p><label>Giới thiệu hội viên mới — đồng cho mỗi hội viên đã kích hoạt<br><input name="referral_member" value="' . esc_attr( number_format_i18n( $pol['referral_member'] ) ) . '" size="12"> đ</label></p>'
			. '<p><label>Dịch vụ Coach bán — % đơn hoàn tất <input name="service_pct" value="' . esc_attr( (string) $pol['service_pct'] ) . '" size="5"> %</label> <span class="mute">(ghi nhận khi mở nguồn đơn Woo)</span></p>'
			. '<p><label>Trợ lý AI của hội viên phụ trách — % doanh thu AI <input name="ai_pct" value="' . esc_attr( (string) $pol['ai_pct'] ) . '" size="5"> %</label> <span class="mute">(ghi nhận khi lõi chốt tháng)</span></p>'
			. '<p><button class="button button-primary">Lưu chính sách</button> <span class="mute">Số 0 = chưa ghi nhận nguồn đó. Đổi chính sách chỉ áp cho hoa hồng ghi nhận sau ngày đổi' . ( $pol['updated_at'] ? ' (đổi lần cuối ' . esc_html( $pol['updated_at'] ) . ' UTC)' : '' ) . '.</span></p></form></div>';

		echo '<div class="ohcl-card"><h3>Coach đang hoạt động</h3>';
		if ( ! $coaches ) {
			echo '<p class="mute">Chưa có Coach. Duyệt hồ sơ rồi bấm "Cấp vai Coach".</p>';
		} else {
			echo '<table class="widefat striped"><thead><tr><th>Coach</th><th>Link giới thiệu</th><th>Hội viên giới thiệu</th><th>Tạm tính</th><th>Đã duyệt</th><th>Đã trả</th><th>Số riêng / hội viên</th></tr></thead><tbody>';
			foreach ( $coaches as $c ) {
				$s = OHCL_Affiliate::coach_summary( $c['id'], $pays, $members );
				echo '<tr><td><b>' . esc_html( $c['name'] ) . '</b><br><span class="mute">' . esc_html( $c['email'] ) . '</span></td><td><code>' . esc_html( OHCL_Affiliate::referral_url( $c['code'] ) ) . '</code></td><td>' . (int) $s['members'] . '</td><td>' . esc_html( self::vnd( $s['tentative'] ) ) . '</td><td>' . esc_html( self::vnd( $s['approved'] ) ) . '</td><td>' . esc_html( self::vnd( $s['paid'] ) ) . '</td><td>'
					. '<form method="post" action="' . $act . '" style="display:inline">'; // phpcs:ignore WordPress.Security.EscapeOutput
				wp_nonce_field( 'ohcl_affiliate' );
				echo '<input type="hidden" name="action" value="ohcl_affiliate"><input type="hidden" name="do" value="override"><input type="hidden" name="coach" value="' . (int) $c['id'] . '"><input name="amount" size="9" value="' . esc_attr( null === $c['override'] ? '' : number_format_i18n( $c['override'] ) ) . '" placeholder="theo chính sách"> <button class="button button-small">Lưu</button></form></td></tr>';
			}
			echo '</tbody></table>';
		}
		echo '</div>';

		echo '<div class="ohcl-card"><h3>Kỳ chi trả theo tháng</h3>';
		echo '<form method="post" action="' . $act . '" style="margin-bottom:10px">'; // phpcs:ignore WordPress.Security.EscapeOutput
		wp_nonce_field( 'ohcl_affiliate' );
		echo '<input type="hidden" name="action" value="ohcl_affiliate"><input type="hidden" name="do" value="sync"><button class="button">Ghi nhận hoa hồng ngay</button> <span class="mute">Tự chạy mỗi ngày: hội viên do Coach giới thiệu đã "Đã kích hoạt" trong 12 tháng ⇒ một dòng, không nhân đôi.</span></form>';
		if ( ! $pays ) {
			echo '<p class="mute">Chưa có kỳ chi trả. Cần: Coach đã cấp, chính sách có số, hội viên giới thiệu đã kích hoạt.</p>';
		} else {
			echo '<table class="widefat striped"><thead><tr><th>Kỳ</th><th>Coach</th><th>Dòng</th><th>Tổng</th><th>Trạng thái</th><th></th></tr></thead><tbody>';
			foreach ( $pays as $p ) {
				$u = isset( $coaches[ $p['coach'] ] ) ? $coaches[ $p['coach'] ]['name'] : '#' . $p['coach'];
				$ln = array();
				foreach ( $p['lines'] as $l ) {
					$ln[] = esc_html( $l['ref'] . ' · ' . OHCL_Affiliate::SOURCES[ $l['source'] ] . ' · ' . self::vnd( $l['amount'] ) . ( 0 === strpos( $l['key'], 'rev:' ) ? ' (đảo)' : '' ) );
				}
				echo '<tr><td><b>' . esc_html( $p['month'] ) . '</b></td><td>' . esc_html( $u ) . '</td><td>' . implode( '<br>', $ln ) . '</td><td><b>' . esc_html( self::vnd( $p['total'] ) ) . '</b></td><td><span class="ohcl-st ' . esc_attr( 'paid' === $p['status'] ? 'ok' : ( 'approved' === $p['status'] ? 'grace' : 'trial' ) ) . '">' . esc_html( OHCL_Affiliate::STATUS[ $p['status'] ] ) . '</span></td><td>'; // phpcs:ignore WordPress.Security.EscapeOutput -- từng dòng đã esc_html.
				$next = 'tentative' === $p['status'] ? array( 'approved', 'Duyệt kỳ này' ) : ( 'approved' === $p['status'] ? array( 'paid', 'Đánh dấu đã trả' ) : null );
				if ( $next ) {
					echo '<form method="post" action="' . $act . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
					wp_nonce_field( 'ohcl_affiliate' );
					echo '<input type="hidden" name="action" value="ohcl_affiliate"><input type="hidden" name="do" value="move"><input type="hidden" name="payout" value="' . (int) $p['id'] . '"><input type="hidden" name="to" value="' . esc_attr( $next[0] ) . '"><button class="button button-small">' . esc_html( $next[1] ) . '</button></form>';
				}
				echo '</td></tr>';
			}
			echo '</tbody></table><p class="mute">Hoa hồng tính từ dữ liệu nguồn, không gõ tay. Dòng đã ghi không xoá: huỷ thì thêm dòng đảo (<code>wp ohcl affiliate reverse &lt;member:ID&gt; --reason=…</code>). Kỳ đã duyệt / đã trả không bị sửa; dòng mới vào kỳ tháng sau.</p>';
		}
		echo '</div>';
	}

	public static function handle_activate() {
		OHCL_Admin::guard();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		check_admin_referer( 'ohcl_coach_activate_' . $id );
		$r = OHCL_Affiliate::activate( $id, isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '' );
		OHCL_Admin::flash_set( is_wp_error( $r ) ? OHCL_Admin::error_flash( $r ) : array( 'ok' => 'Đã cấp vai Coach. Coach nhận email đặt mật khẩu; mã giới thiệu có trong bảng "Coach đang hoạt động".' ) );
		wp_safe_redirect( OHCL_Admin::url( self::PAGE ) );
		exit;
	}

	public static function handle_affiliate() {
		OHCL_Admin::guard();
		check_admin_referer( 'ohcl_affiliate' );
		$do  = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$res = array( 'ok' => 'Không có gì để làm.' );
		if ( 'policy' === $do ) {
			OHCL_Affiliate::save_policy( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- clean_policy() ép số.
			OHCL_Audit::log( 'policy_changed', 0, 'chính sách affiliate' ); // [2026-11-26 04:31 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6
			$res = array( 'ok' => 'Đã lưu chính sách affiliate. Áp cho hoa hồng ghi nhận từ bây giờ.' );
		} elseif ( 'override' === $do ) {
			OHCL_Affiliate::save_override( isset( $_POST['coach'] ) ? (int) $_POST['coach'] : 0, isset( $_POST['amount'] ) ? sanitize_text_field( wp_unslash( $_POST['amount'] ) ) : '' );
			$res = array( 'ok' => 'Đã lưu số riêng của Coach (để trống = theo chính sách chung).' );
		} elseif ( 'sync' === $do ) {
			$s   = OHCL_Affiliate::sync();
			$res = array( 'ok' => $s['skipped_core'] ? 'Chưa ghi nhận được: chưa đọc được khoá từ lõi Bizcity (kiểm tra kết nối lõi ở Cài đặt).' : 'Đã ghi nhận ' . (int) $s['added'] . ' dòng hoa hồng mới.' );
		} elseif ( 'move' === $do ) {
			$r   = OHCL_Affiliate::move( isset( $_POST['payout'] ) ? (int) $_POST['payout'] : 0, isset( $_POST['to'] ) ? sanitize_key( wp_unslash( $_POST['to'] ) ) : '' );
			$res = is_wp_error( $r ) ? OHCL_Admin::error_flash( $r ) : array( 'ok' => 'Đã cập nhật kỳ chi trả.' );
			if ( ! is_wp_error( $r ) ) {
				OHCL_Audit::log( 'payout_moved', 0, isset( $_POST['to'] ) ? sanitize_key( wp_unslash( $_POST['to'] ) ) : '' ); // [2026-11-26 04:31 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6
			}
		}
		OHCL_Admin::flash_set( $res );
		wp_safe_redirect( OHCL_Admin::url( self::PAGE ) );
		exit;
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
