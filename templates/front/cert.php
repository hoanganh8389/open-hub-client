<?php
/**
 * F3 · Verification & certification (mockup hub-frontend.html #scr-trust): four kinds of recognition, the
 * industry standards (public criteria), certificate lookup by code / QR (four results), report form.
 * Vars: $page, $data (lookup, asked), $cfg, $persona.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — trang xác minh & chứng nhận: bốn loại ghi nhận, bộ tiêu chuẩn, tra cứu mã / QR (bốn kết quả), gửi phản ánh.

defined( 'ABSPATH' ) || exit;

$ohcl_hub   = $cfg['name'] ? $cfg['name'] : get_bloginfo( 'name' );
$ohcl_lk    = isset( $data['lookup'] ) ? $data['lookup'] : null;
$ohcl_asked = isset( $data['asked'] ) ? $data['asked'] : '';
$ohcl_err   = isset( $_GET['err'] ) ? OHCL_Front::join_error( sanitize_key( wp_unslash( $_GET['err'] ) ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ chọn câu báo lỗi từ danh sách cố định.
$ohcl_sent  = ! empty( $_GET['reported'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ohcl_kinds = array(
	array( 'users', 'Hội viên ' . $ohcl_hub, 'Cơ sở đã tham gia cộng đồng.', 'Không có nghĩa đã được thẩm định chất lượng.' ),
	array( 'check', $ohcl_hub . ' Verified', 'Ban điều hành đã xác minh danh tính, cơ sở và thông tin theo phạm vi công bố.', 'Có hạn. Không có nghĩa đạt bộ tiêu chuẩn nào.' ),
	array( 'shield', $ohcl_hub . ' Certified', 'Đạt một bộ tiêu chuẩn ngành cụ thể, có tiêu chí, bằng chứng, thời hạn.', 'Tra cứu được bằng mã hoặc QR. Không có nghĩa đạt mọi bộ tiêu chuẩn.' ),
	array( 'star', 'Hội viên tiêu biểu', 'Ghi nhận đóng góp hoặc thành tích theo tiêu chí công bố, xét theo kỳ.', 'Không phải chứng nhận chất lượng.' ),
);
$ohcl_tone = array( 'valid' => 'ok', 'expired' => 'soon', 'revoked' => 'bad', 'none' => 'idle' );
?>
<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › Xác minh &amp; chứng nhận</p>
	<h1>Uy tín được kiểm chứng</h1>
	<p class="lead">Bốn loại ghi nhận khác nhau. Không loại nào được hiểu thay cho loại khác.</p></div></div>

<div class="in certpage<?php echo $ohcl_lk ? ' has-lookup' : ''; ?>">
	<?php // [2026-11-26 10:50 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX5 — ô tra cứu lên đầu, căn giữa; các thẻ thông tin ở dưới. ?>
	<div class="pnl lookup-box" id="tra-cuu"><h2>Tra cứu chứng nhận</h2>
		<p class="note" style="margin:0 0 10px">Nhập mã in trên giấy chứng nhận, hoặc quét mã QR trên giấy.</p>
		<form method="get" action="<?php echo esc_url( OHCL_Front::url( 'cert' ) ); ?>#tra-cuu" class="lookup">
			<input class="fld" name="ma" value="<?php echo esc_attr( $ohcl_asked ); ?>" placeholder="<?php echo esc_attr( OHCL_Certs::code( OHCL_Certs::prefix( $cfg ), (int) gmdate( 'Y' ), 1 ) ); ?>" aria-label="Mã chứng nhận" autocomplete="off" spellcheck="false">
			<button class="btn g">Tra cứu</button>
		</form>
		<?php if ( $ohcl_lk ) : ?>
			<div class="result" aria-live="polite">
				<?php if ( 'none' === $ohcl_lk['state'] ) : ?>
					<span class="tag idle"><?php echo esc_html( $ohcl_lk['state_label'] ); ?></span>
					<p>Mã <b class="mono"><?php echo esc_html( $ohcl_asked ); ?></b> không do <?php echo esc_html( $ohcl_hub ); ?> cấp, hoặc gõ sai. Kiểm lại từng ký tự trên giấy.</p>
				<?php else : ?>
					<div class="cert-card">
						<?php if ( 'valid' === $ohcl_lk['state'] ) : ?>
							<div class="qr" data-ohcl-qr="<?php echo esc_attr( OHCL_Certs::lookup_url( $ohcl_lk['code'] ) ); ?>" role="img" aria-label="Mã QR tra cứu chứng nhận này"></div>
						<?php endif; ?>
						<div>
							<span class="tag <?php echo esc_attr( $ohcl_tone[ $ohcl_lk['state'] ] ); ?>"><?php echo esc_html( $ohcl_lk['state_label'] ); ?></span>
							<h3><?php echo esc_html( $ohcl_lk['business'] ? $ohcl_lk['business'] : '—' ); ?></h3>
							<p><?php echo esc_html( $ohcl_hub ); ?> Certified · <b><?php echo esc_html( $ohcl_lk['standard'] ); ?></b><?php echo $ohcl_lk['province'] ? ' · ' . esc_html( $ohcl_lk['province'] ) : ''; ?></p>
							<p class="mono"><?php echo esc_html( $ohcl_lk['code'] ); ?></p>
							<p>Hiệu lực <?php echo esc_html( $ohcl_lk['from'] . ' – ' . $ohcl_lk['until'] ); ?> · đạt <?php echo (int) $ohcl_lk['criteria_count']; ?>/<?php echo (int) $ohcl_lk['criteria_count']; ?> tiêu chí</p>
							<?php if ( 'revoked' === $ohcl_lk['state'] ) : ?>
								<p class="bad-txt">Thu hồi ngày <?php echo esc_html( $ohcl_lk['revoked_at'] ); ?>. Lý do: <?php echo esc_html( $ohcl_lk['reason'] ); ?></p>
							<?php elseif ( 'expired' === $ohcl_lk['state'] ) : ?>
								<p class="note">Cơ sở đã từng đạt, chưa gia hạn.</p>
							<?php endif; ?>
							<p class="note">Đơn vị cấp: <?php echo esc_html( $ohcl_hub ); ?></p>
						</div>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="kinds">
		<?php foreach ( $ohcl_kinds as $ohcl_k ) : ?>
			<div class="pnl kind"><span class="ic"><?php echo OHCL_Front::icon( $ohcl_k[0] ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG cố định. ?></span>
				<b><?php echo esc_html( $ohcl_k[1] ); ?></b><p><?php echo esc_html( $ohcl_k[2] ); ?></p><p class="note"><?php echo esc_html( $ohcl_k[3] ); ?></p></div>
		<?php endforeach; ?>
	</div>

	<div class="pnl" style="margin-top:16px"><h2>Kết quả có thể gặp</h2>
		<ul class="states">
			<li><span class="tag ok">Còn hiệu lực</span> đạt bộ tiêu chuẩn, trong thời hạn</li>
			<li><span class="tag soon">Hết hạn</span> đã từng đạt, chưa gia hạn</li>
			<li><span class="tag bad">Đã thu hồi</span> kèm ngày và lý do công khai</li>
			<li><span class="tag idle">Không tìm thấy</span> mã không do <?php echo esc_html( $ohcl_hub ); ?> cấp</li>
		</ul>
		<p class="note">Có thắc mắc về một cơ sở? <a href="#phan-anh">Gửi phản ánh →</a></p>
	</div>

	<?php if ( $cfg['standards'] ) : ?>
		<div class="pnl" style="margin-top:16px"><h2>Bộ tiêu chuẩn của <?php echo esc_html( $ohcl_hub ); ?></h2>
			<div class="stds">
				<?php foreach ( $cfg['standards'] as $ohcl_st ) : ?>
					<div class="std"><b><?php echo esc_html( $ohcl_st['label'] ); ?></b> <span class="note">· hiệu lực <?php echo (int) $ohcl_st['validity_months']; ?> tháng</span>
						<?php if ( $ohcl_st['criteria'] ) : ?>
							<ul class="crit"><?php foreach ( $ohcl_st['criteria'] as $ohcl_c ) : ?><li><?php echo esc_html( $ohcl_c ); ?></li><?php endforeach; ?></ul>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="note">Chỉ cấp khi cơ sở đạt mọi tiêu chí của bộ tiêu chuẩn và đã được xác minh. Bằng chứng thẩm định được giữ riêng, không công bố.</p>
		</div>
	<?php endif; ?>

	<div class="pnl" id="phan-anh" style="margin:16px 0 28px"><h2>Gửi phản ánh</h2>
		<?php if ( $ohcl_sent ) : ?>
			<div class="state ok sm"><b>Đã nhận phản ánh</b>Ban điều hành <?php echo esc_html( $ohcl_hub ); ?> sẽ xem xét. Cảm ơn bạn.</div>
		<?php else : ?>
			<?php if ( $ohcl_err ) : ?>
				<div class="state err sm"><b><?php echo esc_html( $ohcl_err['message'] ); ?></b><?php echo esc_html( $ohcl_err['hint'] ); ?><br><small>Mã: <?php echo esc_html( $ohcl_err['code'] ); ?></small></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="report">
				<input type="hidden" name="action" value="ohcl_cert_report">
				<?php wp_nonce_field( 'ohcl_cert_report', '_ohcl_report' ); ?>
				<input class="hp" name="website_url_2" tabindex="-1" autocomplete="off" aria-hidden="true">
				<input class="fld" name="code" value="<?php echo esc_attr( $ohcl_lk && 'none' !== $ohcl_lk['state'] ? $ohcl_lk['code'] : '' ); ?>" placeholder="Mã chứng nhận (nếu có)" aria-label="Mã chứng nhận">
				<textarea class="fld" name="text" rows="4" maxlength="1000" required placeholder="Cơ sở nào, việc gì, khi nào…" aria-label="Nội dung phản ánh"></textarea>
				<input class="fld" name="contact" maxlength="100" placeholder="Số Zalo hoặc email để ban điều hành hỏi lại (không bắt buộc)" aria-label="Liên hệ">
				<button class="btn g">Gửi phản ánh</button>
				<p class="note">Chỉ ban điều hành đọc phản ánh. Không công khai.</p>
			</form>
		<?php endif; ?>
	</div>
</div>
