<?php
/**
 * P4 · Become a member (mockup #scr-member). The form creates a member record (V1, source web) in
 * Hub › 02 · Hội viên; anti-spam: nonce, hidden honeypot, 5 per hour per IP. Errors: four fields.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — trang trở thành hội viên: quyền lợi từ persona, form đăng ký tạo hồ sơ thật.

defined( 'ABSPATH' ) || exit;

$ohcl_sent = isset( $_GET['sent'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ hiện lời cảm ơn.
$ohcl_err  = isset( $_GET['err'] ) ? OHCL_Front::join_error( sanitize_key( wp_unslash( $_GET['err'] ) ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ohcl_term = OHCL_Industry::term( 'member' );
?>
<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › Hội viên</p><h1>Trở thành hội viên <?php echo esc_html( $cfg['name'] ); ?></h1></div></div>
<div class="in g2">
	<div class="pnl"><h2>Quyền lợi <?php echo esc_html( $ohcl_term ); ?></h2>
		<?php foreach ( $persona['benefits'] as $ohcl_b ) : ?>
			<div class="ben<?php echo $ohcl_b['highlight'] ? ' hl' : ''; ?>"><i><?php echo OHCL_Front::icon( $ohcl_b['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></i><div><b><?php echo esc_html( str_replace( '{days}', (string) $cfg['trial_days'], $ohcl_b['title'] ) ); ?></b><span><?php echo esc_html( str_replace( '{days}', (string) $cfg['trial_days'], $ohcl_b['text'] ) ); ?></span></div></div>
		<?php endforeach; ?>
	</div>
	<div class="pnl form" id="dang-ky">
		<?php if ( $ohcl_sent ) : ?>
			<div class="state ok"><b>Đã nhận đăng ký</b>Ban điều hành sẽ liên hệ qua Zalo hoặc email để tiếp nhận và cấp bộ công nghệ cho cơ sở.<div style="margin-top:12px"><a class="btn o sm" href="<?php echo esc_url( OHCL_Front::url() ); ?>">Về trang chủ</a></div></div>
		<?php else : ?>
			<h2>Đăng ký <?php echo esc_html( $ohcl_term ); ?></h2>
			<?php if ( $ohcl_err ) : ?>
				<div class="alert" role="alert"><b><?php echo esc_html( $ohcl_err['message'] ); ?></b> <?php echo esc_html( $ohcl_err['hint'] ); ?> <code><?php echo esc_html( $ohcl_err['code'] ); ?></code></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ohcl_join">
				<?php wp_nonce_field( 'ohcl_join', '_ohcl_join' ); ?>
				<label class="hp" aria-hidden="true">Website<input name="website_url_2" tabindex="-1" autocomplete="off"></label>
				<label><span>Tên <?php echo esc_html( OHCL_Industry::term( 'business' ) ); ?> <span class="req">*</span></span><input name="title" required maxlength="120"></label>
				<label>Loại hình<select name="segment"><option value="">—</option>
					<?php foreach ( $cfg['segments'] as $ohcl_s => $ohcl_l ) : ?>
						<option value="<?php echo esc_attr( $ohcl_s ); ?>"><?php echo esc_html( $ohcl_l ); ?></option>
					<?php endforeach; ?>
				</select></label>
				<label>Tỉnh / thành<input name="province" maxlength="60"></label>
				<label>Số Zalo<input name="contact_zalo" inputmode="tel" maxlength="16" placeholder="09…"></label>
				<label>Email<input name="email" type="email" maxlength="100"></label>
				<label class="inline"><input type="checkbox" name="public_consent" value="1"> Đồng ý hiện tên cơ sở trên bản đồ và danh bạ sau khi được xác minh</label>
				<button class="btn g" style="width:100%">Gửi đăng ký</button>
				<p class="note"><?php echo esc_html( $persona['join_note'] ); ?></p>
			</form>
		<?php endif; ?>
	</div>
</div>
