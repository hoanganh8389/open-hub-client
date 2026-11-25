<?php
/**
 * Hub › 06 · Chứng nhận (mockup owner-center.html #scr-cert, O8): list of certificates with their state,
 * public reports, a printable certificate with the lookup QR; issue / renew from Member 360°, revoke here
 * with a public reason. Loaded in wp-admin only, menu shown while module 06 is on.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — màn 06 · Chứng nhận: danh sách, phản ánh, giấy in có QR; cấp / gia hạn từ Member 360°, thu hồi hai bước có lý do công khai.

defined( 'ABSPATH' ) || exit;

final class OHCL_Admin_Certs {

	const PAGE = 'open-hub-certs';

	public static function boot() {
		add_action( 'admin_post_ohcl_cert_issue', array( __CLASS__, 'handle_issue' ) );
		add_action( 'admin_post_ohcl_cert_revoke', array( __CLASS__, 'handle_revoke' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function on() {
		return OHCL_Modules::enabled( '06' );
	}

	public static function assets( $hook ) {
		if ( false !== strpos( (string) $hook, self::PAGE ) && isset( $_GET['cert'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ chọn màn.
			OHCL_Front::enqueue_qr();
		}
	}

	private static function u( array $args = array() ) {
		return OHCL_Admin::url( self::PAGE, $args );
	}

	/* ---------- màn 06 ---------- */

	public static function page() {
		OHCL_Admin::guard();
		$cid = isset( $_GET['cert'] ) ? (int) $_GET['cert'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ chọn màn.
		if ( $cid ) {
			self::page_one( $cid );
			return;
		}
		$cfg    = OHCL_Industry::current();
		$now    = time();
		$all    = OHCL_Certs::all();
		$filter = isset( $_GET['st'] ) ? sanitize_key( wp_unslash( $_GET['st'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$count  = array( 'valid' => 0, 'expired' => 0, 'revoked' => 0 );
		$soon   = 0;
		foreach ( $all as $c ) {
			$s = OHCL_Certs::state( $c, $now );
			$count[ $s ]++;
			if ( 'valid' === $s && strtotime( $c['until'] . ' UTC' ) - $now <= 60 * DAY_IN_SECONDS ) {
				$soon++;
			}
		}
		OHCL_Admin::head( '06 · Xác minh & chứng nhận', 'Uy tín của toàn ngành', '<a class="button" href="' . esc_url( OHCL_Front::url( 'cert' ) ) . '" target="_blank" rel="noopener">Trang tra cứu công khai ↗</a>' );
		echo '<div class="ohcl-g3 ohcl-g4">' . OHCL_Admin::kpi( 'Còn hiệu lực', number_format_i18n( $count['valid'] ) ) // phpcs:ignore WordPress.Security.EscapeOutput -- kpi() tự escape.
			. OHCL_Admin::kpi( 'Hết hạn trong 60 ngày', number_format_i18n( $soon ), 'cần gia hạn' ) // phpcs:ignore WordPress.Security.EscapeOutput
			. OHCL_Admin::kpi( 'Hết hạn', number_format_i18n( $count['expired'] ) ) // phpcs:ignore WordPress.Security.EscapeOutput
			. OHCL_Admin::kpi( 'Đã thu hồi', number_format_i18n( $count['revoked'] ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput

		if ( ! $cfg['standards'] ) {
			echo '<div class="ohcl-note">Dữ liệu ngành chưa có bộ tiêu chuẩn nào (<code>standards</code> trong hợp đồng ngành). Chưa cấp được chứng nhận.</div>';
		}

		echo '<div class="ohcl-split"><div class="ohcl-card"><h3>Giấy chứng nhận</h3><p class="ohcl-chips">';
		foreach ( array( '' => 'Tất cả', 'valid' => 'Còn hiệu lực', 'expired' => 'Hết hạn', 'revoked' => 'Đã thu hồi' ) as $k => $l ) {
			echo '<a class="' . ( $filter === $k ? 'on' : '' ) . '" href="' . esc_url( self::u( $k ? array( 'st' => $k ) : array() ) ) . '">' . esc_html( $l ) . '</a> ';
		}
		echo '</p>';
		$rows = array_filter( $all, function ( $c ) use ( $filter, $now ) { return '' === $filter || OHCL_Certs::state( $c, $now ) === $filter; } );
		if ( ! $rows ) {
			echo '<p class="mute">' . ( $all ? 'Không có giấy nào ở trạng thái này.' : 'Chưa cấp giấy chứng nhận nào. Cấp từ hồ sơ hội viên: 02 · Hội viên › mở Member 360° › Chứng nhận.' ) . '</p>';
		} else {
			$names = array();
			echo '<table class="widefat striped"><thead><tr><th>Mã</th><th>Cơ sở</th><th>Bộ tiêu chuẩn</th><th>Hiệu lực</th><th>Trạng thái</th></tr></thead><tbody>';
			foreach ( $rows as $c ) {
				if ( ! isset( $names[ $c['member'] ] ) ) {
					$m                    = OHCL_Members::get( $c['member'] );
					$names[ $c['member'] ] = $m ? $m['title'] : '—';
				}
				$s = OHCL_Certs::state( $c, $now );
				echo '<tr><td><a class="mono" href="' . esc_url( self::u( array( 'cert' => $c['id'] ) ) ) . '">' . esc_html( $c['code'] ) . '</a></td>'
					. '<td><a href="' . esc_url( OHCL_Admin::url( 'open-hub-members', array( 'id' => $c['member'] ) ) ) . '">' . esc_html( $names[ $c['member'] ] ) . '</a></td>'
					. '<td>' . esc_html( $c['label'] ) . '</td><td>' . esc_html( substr( $c['from'], 0, 10 ) . ' – ' . substr( $c['until'], 0, 10 ) ) . '</td>'
					. '<td><span class="ohcl-st ' . esc_attr( 'valid' === $s ? 'ok' : ( 'revoked' === $s ? 'revoked' : 'grace' ) ) . '">' . esc_html( OHCL_Certs::STATES[ $s ] ) . '</span></td></tr>';
			}
			echo '</tbody></table>';
		}
		echo '</div><div>';

		$reports = OHCL_Certs::reports();
		echo '<div class="ohcl-card"><h3>Phản ánh từ trang tra cứu</h3>';
		if ( ! $reports ) {
			echo '<p class="mute">Chưa có phản ánh nào.</p>';
		} else {
			echo '<ul class="ohcl-reports">';
			foreach ( array_slice( $reports, 0, 30 ) as $r ) {
				echo '<li><span class="mute">' . esc_html( substr( $r['at'], 0, 16 ) ) . ( $r['code'] ? ' · <span class="mono">' . esc_html( $r['code'] ) . '</span>' : '' ) . '</span><br>'
					. esc_html( $r['text'] ) . ( $r['contact'] ? '<br><span class="mute">Liên hệ: ' . esc_html( $r['contact'] ) . '</span>' : '' ) . '</li>';
			}
			echo '</ul>';
		}
		echo '<p class="mute">Giữ 100 phản ánh mới nhất. Chỉ quản trị viên Hub thấy.</p></div>';

		if ( $cfg['standards'] ) {
			echo '<div class="ohcl-card"><h3>Bộ tiêu chuẩn (từ dữ liệu ngành)</h3><ul>';
			foreach ( $cfg['standards'] as $st ) {
				echo '<li><b>' . esc_html( $st['label'] ) . '</b> · ' . count( $st['criteria'] ) . ' tiêu chí · hiệu lực ' . (int) $st['validity_months'] . ' tháng</li>';
			}
			echo '</ul><p class="mute">Sửa bộ tiêu chuẩn trong plugin ngành (' . esc_html( $cfg['plugin'] ? $cfg['plugin'] : 'hợp đồng ngành @2' ) . ').</p></div>';
		}
		echo '</div></div></div>';
	}

	/** One certificate: printable sheet with QR + revoke. */
	private static function page_one( $cid ) {
		$c = OHCL_Certs::get( $cid );
		if ( ! $c ) {
			OHCL_Admin::head( '06 · Chứng nhận', 'Không tìm thấy giấy chứng nhận' );
			echo '<p><a href="' . esc_url( self::u() ) . '">← Danh sách</a></p></div>';
			return;
		}
		$cfg = OHCL_Industry::current();
		$hub = $cfg['name'] ? $cfg['name'] : get_bloginfo( 'name' );
		$m   = OHCL_Members::get( $c['member'] );
		$s   = OHCL_Certs::state( $c, time() );
		$url = OHCL_Certs::lookup_url( $c['code'] );
		OHCL_Admin::head( '06 · Chứng nhận › ' . $c['code'], $m ? $m['title'] : '—', '<a class="button" href="' . esc_url( self::u() ) . '">← Danh sách</a> <button class="button button-primary" onclick="window.print()">In giấy</button>' );

		echo '<div class="ohcl-split"><div><div class="ohcl-card ohcl-certsheet">'
			. '<p class="ohcl-kick">' . esc_html( $hub ) . ' Certified</p><h2>' . esc_html( $c['label'] ) . '</h2>'
			. '<p>Chứng nhận <b>' . esc_html( $m ? $m['title'] : '—' ) . '</b>' . ( $m && $m['province'] ? ' · ' . esc_html( $m['province'] ) : '' ) . ' đạt đủ ' . count( $c['criteria'] ) . ' tiêu chí của bộ tiêu chuẩn.</p>'
			. '<ul>';
		foreach ( $c['criteria'] as $cr ) {
			echo '<li>' . esc_html( $cr ) . '</li>';
		}
		echo '</ul><div class="ohcl-certfoot"><div class="ohcl-qr" data-ohcl-qr="' . esc_attr( $url ) . '" role="img" aria-label="Mã QR tra cứu"></div><div>'
			. '<p class="mono"><b>' . esc_html( $c['code'] ) . '</b></p><p>Hiệu lực ' . esc_html( substr( $c['from'], 0, 10 ) . ' – ' . substr( $c['until'], 0, 10 ) ) . '</p>'
			. '<p class="mute">Tra cứu: ' . esc_html( $url ) . '</p><p><span class="ohcl-st ' . esc_attr( 'valid' === $s ? 'ok' : ( 'revoked' === $s ? 'revoked' : 'grace' ) ) . '">' . esc_html( OHCL_Certs::STATES[ $s ] ) . '</span></p></div></div></div></div><div>';

		echo '<div class="ohcl-card"><h3>Hồ sơ thẩm định (riêng tư)</h3><dl class="ohcl-dl">'
			. '<dt>Bằng chứng đã kiểm</dt><dd>' . esc_html( $c['evidence'] ? implode( ', ', $c['evidence'] ) : '—' ) . '</dd>'
			. '<dt>Người thẩm định</dt><dd>' . esc_html( self::user_name( $c['by'] ) ) . '</dd>'
			. '<dt>Ghi chú</dt><dd>' . esc_html( $c['note'] ? $c['note'] : '—' ) . '</dd>'
			. '<dt>Các kỳ trước</dt><dd>' . esc_html( $c['history'] ? implode( ' · ', array_map( function ( $h ) { return substr( $h['from'], 0, 10 ) . ' – ' . substr( $h['until'], 0, 10 ); }, $c['history'] ) ) : '—' ) . '</dd></dl>'
			. '<p class="mute">Gia hạn: mở hồ sơ hội viên, cấp lại cùng bộ tiêu chuẩn (giữ nguyên mã).</p></div>';

		if ( 'revoked' === $s ) {
			echo '<div class="ohcl-card"><h3>Đã thu hồi</h3><p>Ngày ' . esc_html( substr( $c['revoked_at'], 0, 10 ) ) . '. Lý do (công khai): ' . esc_html( $c['reason'] ) . '</p></div>';
		} else {
			echo '<div class="ohcl-card"><h3>Thu hồi</h3><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'ohcl_cert_revoke_' . $c['id'] );
			echo '<input type="hidden" name="action" value="ohcl_cert_revoke"><input type="hidden" name="cert" value="' . (int) $c['id'] . '">'
				. '<p><label>Lý do (hiện công khai ở trang tra cứu)<br><input name="reason" class="regular-text" maxlength="200" required></label></p>'
				. '<p><label class="ohcl-chk"><input type="checkbox" name="confirm" value="1" required> Tôi hiểu: thu hồi không hoàn tác được, cơ sở mất huy hiệu ngay.</label></p>'
				. '<p><button class="button button-link-delete">Thu hồi giấy chứng nhận</button></p></form></div>';
		}
		echo '</div></div></div>';
	}

	private static function user_name( $uid ) {
		if ( ! $uid || ! function_exists( 'get_userdata' ) ) {
			return '—';
		}
		$u = get_userdata( (int) $uid );
		return $u ? $u->display_name : '#' . (int) $uid;
	}

	/* ---------- card in Member 360° ---------- */

	public static function member_card( array $m ) {
		$cfg   = OHCL_Industry::current();
		$now   = time();
		$certs = OHCL_Certs::for_member( $m['id'] );
		echo '<div class="ohcl-card"><h3>Chứng nhận (06)</h3>';
		foreach ( $certs as $c ) {
			$s = OHCL_Certs::state( $c, $now );
			echo '<p><span class="ohcl-st ' . esc_attr( 'valid' === $s ? 'ok' : ( 'revoked' === $s ? 'revoked' : 'grace' ) ) . '">' . esc_html( OHCL_Certs::STATES[ $s ] ) . '</span> <b>' . esc_html( $c['label'] ) . '</b> · '
				. '<a class="mono" href="' . esc_url( self::u( array( 'cert' => $c['id'] ) ) ) . '">' . esc_html( $c['code'] ) . '</a> · hạn ' . esc_html( substr( $c['until'], 0, 10 ) ) . '</p>';
		}
		if ( ! $cfg['standards'] ) {
			echo '<p class="mute">Dữ liệu ngành chưa có bộ tiêu chuẩn.</p></div>';
			return;
		}
		if ( ! OHCL_Members::verified( $m['verify'], $now ) ) {
			echo '<p class="mute">Xác minh hồ sơ (V3) trước, rồi mới cấp chứng nhận.</p></div>';
			return;
		}
		foreach ( $cfg['standards'] as $st ) {
			$has = null;
			foreach ( $certs as $c ) {
				if ( $c['standard'] === $st['code'] && '' === $c['revoked_at'] ) {
					$has = $c;
				}
			}
			echo '<details class="ohcl-std"><summary>' . esc_html( ( $has ? 'Gia hạn · ' : 'Cấp · ' ) . $st['label'] ) . '</summary><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'ohcl_cert_issue_' . $m['id'] );
			echo '<input type="hidden" name="action" value="ohcl_cert_issue"><input type="hidden" name="id" value="' . (int) $m['id'] . '"><input type="hidden" name="standard" value="' . esc_attr( $st['code'] ) . '">'
				. '<p class="mute">Tiêu chí đã đạt (cần đủ tất cả):</p>';
			foreach ( $st['criteria'] as $i => $cr ) {
				echo '<label class="ohcl-chk"><input type="checkbox" name="criteria[]" value="' . (int) $i . '"> ' . esc_html( $cr ) . '</label><br>';
			}
			if ( $st['evidence'] ) {
				echo '<p class="mute">Bằng chứng đã kiểm:</p>';
				foreach ( $st['evidence'] as $i => $ev ) {
					echo '<label class="ohcl-chk"><input type="checkbox" name="evidence[]" value="' . (int) $i . '"> ' . esc_html( $ev ) . '</label> ';
				}
			}
			echo '<p><label>Hiệu lực (tháng)<br><input type="number" name="months" min="1" max="60" value="' . (int) $st['validity_months'] . '" style="width:80px"></label></p>'
				. '<p><label>Ghi chú thẩm định (riêng tư)<br><input name="note" class="regular-text" maxlength="300"></label></p>'
				. '<p><button class="button button-primary">' . ( $has ? 'Gia hạn (giữ mã ' . esc_html( $has['code'] ) . ')' : 'Cấp chứng nhận' ) . '</button></p></form></details>';
		}
		echo '</div>';
	}

	/* ---------- handlers ---------- */

	public static function handle_issue() {
		OHCL_Admin::guard();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		check_admin_referer( 'ohcl_cert_issue_' . $id );
		$back = OHCL_Admin::url( 'open-hub-members', array( 'id' => $id ) );
		if ( ! self::on() ) {
			OHCL_Admin::flash_set( OHCL_Admin::error_flash( OHCL_Error::make( 'ohcl_module_off', 'Module 06 · Xác minh & chứng nhận đang tắt.', 'Bật module ở 14 · Cài đặt rồi thử lại.', 409 ) ) );
			wp_safe_redirect( $back );
			exit;
		}
		$clean = OHCL_Certs::clean_issue( wp_unslash( $_POST ), OHCL_Industry::current() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- clean_issue() chỉ nhận chỉ số tiêu chí / bằng chứng có thật, bỏ thẻ ghi chú.
		$res   = is_wp_error( $clean ) ? $clean : OHCL_Certs::issue( $id, $clean );
		if ( is_wp_error( $res ) ) {
			OHCL_Admin::flash_set( OHCL_Admin::error_flash( $res ) );
		} else {
			OHCL_Slots::emit( 'cert_issued', array( 'member_id' => $id, 'code' => $res['code'], 'standard' => $clean['standard'], 'renewed' => $res['renewed'] ) );
			OHCL_Admin::flash_set( array( 'ok' => ( $res['renewed'] ? 'Đã gia hạn ' : 'Đã cấp ' ) . $res['code'] . '. In giấy ở 06 · Chứng nhận.' ) );
		}
		wp_safe_redirect( $back );
		exit;
	}

	public static function handle_revoke() {
		OHCL_Admin::guard();
		$cid = isset( $_POST['cert'] ) ? (int) $_POST['cert'] : 0;
		check_admin_referer( 'ohcl_cert_revoke_' . $cid );
		if ( empty( $_POST['confirm'] ) ) {
			$res = OHCL_Error::make( 'ohcl_cert_revoke_unconfirmed', 'Chưa xác nhận thu hồi.', 'Đánh dấu ô xác nhận rồi bấm lại.', 400 );
		} else {
			$res = OHCL_Certs::revoke( $cid, isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '' );
		}
		if ( is_wp_error( $res ) ) {
			OHCL_Admin::flash_set( OHCL_Admin::error_flash( $res ) );
		} else {
			$c = OHCL_Certs::get( $cid );
			OHCL_Slots::emit( 'cert_revoked', array( 'member_id' => $c ? $c['member'] : 0, 'code' => $c ? $c['code'] : '' ) );
			OHCL_Admin::flash_set( array( 'ok' => 'Đã thu hồi. Trang tra cứu hiện "Đã thu hồi" kèm lý do.' ) );
		}
		wp_safe_redirect( self::u( array( 'cert' => $cid ) ) );
		exit;
	}
}
