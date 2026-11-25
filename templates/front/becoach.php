<?php
/**
 * F10 · Become a coach (mockup hub-frontend.html #scr-becoach): what a coach gets + the application form
 * (CPT bz_hub_coach_app, reviewed in Hub › 04 · Coach). Commission rules come with the approval (Đ3).
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — trang Trở thành Coach: quyền lợi Coach + form ứng tuyển có chống spam.

defined( 'ABSPATH' ) || exit;

$ohcl_sent = isset( $_GET['sent'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ hiện lời cảm ơn.
$ohcl_err  = isset( $_GET['err'] ) ? OHCL_Front::join_error( sanitize_key( wp_unslash( $_GET['err'] ) ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ohcl_ind  = $cfg['industry'] ? ( function_exists( 'mb_strtolower' ) ? mb_strtolower( $cfg['industry'], 'UTF-8' ) : $cfg['industry'] ) : 'ngành';
?>
<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › Trở thành Coach</p>
	<h1>Đồng hành cùng <?php echo esc_html( $ohcl_ind ); ?></h1>
	<p class="lead">Coach giúp <?php echo esc_html( OHCL_Industry::term( 'business' ) ); ?> chuyển đổi số, vận hành tốt hơn và có thêm khách. <?php echo esc_html( $cfg['name'] ); ?> giới thiệu hội viên và cung cấp công cụ.</p></div></div>
<div class="in g2 cert-row" style="margin-top:18px;margin-bottom:32px">
	<div>
		<?php
		$ohcl_why = array(
			array( 'users', 'Hội viên được phân công', 'Theo chuyên môn và khu vực của bạn.' ),
			array( 'flow', 'Công cụ đồng hành', 'Kế hoạch 30–60–90 ngày, lịch tư vấn, tài liệu chung.' ),
			array( 'wallet', 'Hoa hồng minh bạch', 'Chính sách gửi kèm khi hồ sơ được duyệt; mỗi khoản có căn cứ.' ),
			array( 'cap', 'Được đào tạo về Bizcity Open', 'Nắm bộ công nghệ 0đ để hướng dẫn hội viên.' ),
		);
		foreach ( $ohcl_why as $ohcl_w ) :
			?>
			<div class="pnl ben" style="margin-bottom:10px"><i><?php echo OHCL_Front::icon( $ohcl_w[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></i><div><b><?php echo esc_html( $ohcl_w[1] ); ?></b><span><?php echo esc_html( $ohcl_w[2] ); ?></span></div></div>
		<?php endforeach; ?>
	</div>
	<div class="pnl form" id="ung-tuyen">
		<?php if ( $ohcl_sent ) : ?>
			<div class="state ok"><b>Đã nhận hồ sơ</b>Ban điều hành sẽ liên hệ sau khi xem hồ sơ.</div>
		<?php else : ?>
			<h2>Ứng tuyển Coach</h2>
			<?php if ( $ohcl_err ) : ?>
				<div class="alert" role="alert"><b><?php echo esc_html( $ohcl_err['message'] ); ?></b> <?php echo esc_html( $ohcl_err['hint'] ); ?> <code><?php echo esc_html( $ohcl_err['code'] ); ?></code></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ohcl_coach_apply">
				<?php wp_nonce_field( 'ohcl_coach_apply', '_ohcl_coach' ); ?>
				<label class="hp" aria-hidden="true">Website<input name="website_url_2" tabindex="-1" autocomplete="off"></label>
				<label class="lbl" for="ohcl-cn">Họ tên <span class="req">*</span></label><input class="fld" id="ohcl-cn" name="name" maxlength="80" required>
				<?php if ( $cfg['coach_skills'] ) : ?>
					<b class="lbl">Chuyên môn <span class="req">*</span></b>
					<?php foreach ( $cfg['coach_skills'] as $ohcl_s ) : ?>
						<label class="chk"><input type="checkbox" name="skills[]" value="<?php echo esc_attr( $ohcl_s['code'] ); ?>"> <?php echo esc_html( $ohcl_s['label'] ); ?></label>
					<?php endforeach; ?>
				<?php endif; ?>
				<label class="lbl" for="ohcl-ca">Khu vực</label><input class="fld" id="ohcl-ca" name="area" maxlength="80" placeholder="Tỉnh / thành phố">
				<label class="lbl" for="ohcl-cy">Số năm kinh nghiệm</label><input class="fld" id="ohcl-cy" name="years" type="number" min="0" max="60">
				<label class="lbl" for="ohcl-cc">Email hoặc Zalo <span class="req">*</span></label><input class="fld" id="ohcl-cc" name="contact" maxlength="100" required>
				<label class="lbl" for="ohcl-cno">Giới thiệu ngắn</label><textarea class="fld" id="ohcl-cno" name="note" rows="3" maxlength="600"></textarea>
				<button class="btn g" style="width:100%">Gửi hồ sơ</button>
				<p class="note">Ban điều hành <?php echo esc_html( $cfg['name'] ); ?> duyệt hồ sơ; thông tin chỉ ban điều hành xem.</p>
			</form>
		<?php endif; ?>
	</div>
</div>
