<?php
/**
 * My Coach (OPEN-HUB-CLIENT §7, mockup hub-frontend.html #scr-coachme): a Coach's own page — referral link,
 * referred members and their journey stage, commissions tentative · approved · paid, payout history.
 * Own data only; referred members show no contact details. Guests / non-Coaches see why they cannot enter.
 * Vars: $cfg.
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 11:40 AM Johnny Chu - Chu Hoàng Anh] OHCL-D3A — trang My Coach: link giới thiệu, hội viên giới thiệu, hoa hồng tạm tính / đã duyệt / đã trả, lịch sử chi trả.

defined( 'ABSPATH' ) || exit;

$ohcl_user  = is_user_logged_in() ? wp_get_current_user() : null;
$ohcl_coach = null;
if ( $ohcl_user && class_exists( 'OHCL_Affiliate' ) && OHCL_Affiliate::is_coach( $ohcl_user ) ) {
	$ohcl_all   = OHCL_Affiliate::coaches();
	$ohcl_coach = isset( $ohcl_all[ (int) $ohcl_user->ID ] ) ? $ohcl_all[ (int) $ohcl_user->ID ] : null;
}
if ( ! $ohcl_coach ) :
	$ohcl_msg = $ohcl_user
		? array( 'Tài khoản này chưa là Coach của Hub', 'Ứng tuyển Coach; ban điều hành duyệt rồi cấp vai và gửi mã giới thiệu cho bạn.' )
		: array( 'Đăng nhập để vào My Coach', 'My Coach dành cho Coach của Hub: link giới thiệu, hội viên phụ trách và hoa hồng.' );
	?>
	<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › My Coach</p><h1>My Coach</h1></div></div>
	<div class="in"><div class="pnl state" style="margin:20px 0 32px"><b><?php echo esc_html( $ohcl_msg[0] ); ?></b><?php echo esc_html( $ohcl_msg[1] ); ?>
		<div style="margin-top:14px">
			<?php if ( ! $ohcl_user ) : ?><a class="btn g" href="<?php echo esc_url( wp_login_url( OHCL_Front::url( 'mycoach' ) ) ); ?>">Đăng nhập</a><?php endif; ?>
			<?php if ( OHCL_Front::page_on( 'becoach' ) ) : ?><a class="btn o" href="<?php echo esc_url( OHCL_Front::url( 'becoach' ) ); ?>">Trở thành Coach</a><?php endif; ?>
		</div></div></div>
	<?php
	return;
endif;

$ohcl_v    = OHCL_Affiliate::coach_view( $ohcl_coach, OHCL_Members::all(), OHCL_Members::keys_by_id(), OHCL_Affiliate::payouts(), OHCL_Affiliate::policy(), time() );
$ohcl_link = OHCL_Affiliate::referral_url( $ohcl_coach['code'] );
$ohcl_vnd  = function ( $n ) { return number_format_i18n( (int) $n ) . ' đ'; };
$ohcl_skl  = array();
foreach ( (array) get_user_meta( $ohcl_coach['id'], 'ohcl_coach_skills', true ) as $ohcl_c ) {
	foreach ( $cfg['coach_skills'] as $ohcl_s ) {
		if ( $ohcl_s['code'] === $ohcl_c ) {
			$ohcl_skl[] = $ohcl_s['label'];
		}
	}
}
$ohcl_tone = array( 'tentative' => 'idle', 'approved' => 'soon', 'paid' => 'ok' );
?>
<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › My Coach</p>
	<h1>My Coach</h1>
	<p class="lead">Xin chào <?php echo esc_html( $ohcl_coach['name'] ); ?>. <?php echo $ohcl_skl ? esc_html( 'Chuyên môn: ' . implode( ', ', $ohcl_skl ) . '.' ) : ''; ?></p></div></div>
<div class="in">
	<div class="coach-kpis">
		<div class="pnl kpi"><span>Hội viên giới thiệu</span><b><?php echo (int) $ohcl_v['sum']['members']; ?></b></div>
		<div class="pnl kpi"><span>Tạm tính</span><b><?php echo esc_html( $ohcl_vnd( $ohcl_v['sum']['tentative'] ) ); ?></b></div>
		<div class="pnl kpi"><span>Đã duyệt</span><b><?php echo esc_html( $ohcl_vnd( $ohcl_v['sum']['approved'] ) ); ?></b></div>
		<div class="pnl kpi"><span>Đã trả</span><b><?php echo esc_html( $ohcl_vnd( $ohcl_v['sum']['paid'] ) ); ?></b></div>
	</div>

	<div class="pnl" style="margin-top:16px"><h2>Link giới thiệu của bạn</h2>
		<p class="note" style="margin:0 0 10px">Hội viên đăng ký sau khi mở link này được ghi cho bạn trong 12 tháng. Người giới thiệu đầu tiên được giữ.</p>
		<input class="fld mono" readonly value="<?php echo esc_attr( $ohcl_link ); ?>" onclick="this.select()" aria-label="Link giới thiệu">
		<p class="note">Mã: <b class="mono"><?php echo esc_html( $ohcl_coach['code'] ); ?></b>
			<?php echo $ohcl_v['amount'] > 0 ? ' · Mỗi hội viên được giới thiệu và đã kích hoạt: <b>' . esc_html( $ohcl_vnd( $ohcl_v['amount'] ) ) . '</b>.' : ' · Hub chưa đặt mức hoa hồng giới thiệu.'; // phpcs:ignore WordPress.Security.EscapeOutput -- số đã escape. ?></p>
	</div>

	<div class="pnl" style="margin-top:16px"><h2>Hội viên bạn giới thiệu</h2>
		<?php if ( ! $ohcl_v['members'] ) : ?>
			<p class="note">Chưa có hội viên nào đăng ký qua link của bạn.</p>
		<?php else : ?>
			<?php if ( ! $ohcl_v['core_ok'] ) : ?><p class="note">Chưa đọc được trạng thái trợ lý từ lõi Bizcity; bậc hành trình có thể chưa cập nhật.</p><?php endif; ?>
			<table class="tbl"><thead><tr><th>Cơ sở</th><th>Mã</th><th>Từ ngày</th><th>Giai đoạn</th><th>Hoa hồng</th></tr></thead><tbody>
				<?php foreach ( $ohcl_v['members'] as $ohcl_m ) : ?>
					<tr><td><?php echo esc_html( $ohcl_m['name'] ); ?></td><td class="mono"><?php echo esc_html( $ohcl_m['code'] ); ?></td><td><?php echo esc_html( $ohcl_m['since'] ); ?></td><td><?php echo esc_html( $ohcl_m['stage'] ); ?></td>
						<td><?php echo $ohcl_m['recognised'] ? '<span class="tag ok">Đã ghi nhận</span>' : ( $ohcl_m['holding'] ? '<span class="tag idle">Chờ kích hoạt</span>' : '<span class="tag soon">Hết hạn 12 tháng</span>' ); // phpcs:ignore WordPress.Security.EscapeOutput -- chuỗi cố định. ?></td></tr>
				<?php endforeach; ?>
			</tbody></table>
			<p class="note">Chỉ hiện tên cơ sở, mã và giai đoạn; thông tin liên hệ của hội viên không hiện ở đây.</p>
		<?php endif; ?>
	</div>

	<div class="pnl" style="margin:16px 0 32px"><h2>Lịch sử chi trả</h2>
		<?php if ( ! $ohcl_v['payouts'] ) : ?>
			<p class="note">Chưa có kỳ chi trả. Hoa hồng được ghi nhận khi hội viên bạn giới thiệu đã kích hoạt trợ lý.</p>
		<?php else : ?>
			<table class="tbl"><thead><tr><th>Kỳ</th><th>Chi tiết</th><th class="n">Tổng</th><th>Trạng thái</th></tr></thead><tbody>
				<?php foreach ( $ohcl_v['payouts'] as $ohcl_p ) : ?>
					<tr><td><b><?php echo esc_html( $ohcl_p['month'] ); ?></b></td>
						<td><?php foreach ( $ohcl_p['lines'] as $ohcl_l ) : ?><?php echo esc_html( $ohcl_l['ref'] . ' · ' . OHCL_Affiliate::SOURCES[ $ohcl_l['source'] ] . ' · ' . $ohcl_vnd( $ohcl_l['amount'] ) . ( 0 === strpos( $ohcl_l['key'], 'rev:' ) ? ' (đảo)' : '' ) ); ?><br><?php endforeach; ?></td>
						<td class="n"><b><?php echo esc_html( $ohcl_vnd( $ohcl_p['total'] ) ); ?></b></td>
						<td><span class="tag <?php echo esc_attr( $ohcl_tone[ $ohcl_p['status'] ] ); ?>"><?php echo esc_html( OHCL_Affiliate::STATUS[ $ohcl_p['status'] ] ); ?></span></td></tr>
				<?php endforeach; ?>
			</tbody></table>
			<p class="note">Tạm tính: đang ghi nhận. Đã duyệt: ban điều hành đã duyệt kỳ. Đã trả: đã chi. Khoản bị huỷ hiện thành dòng đảo, không bị xoá.</p>
		<?php endif; ?>
	</div>
</div>
