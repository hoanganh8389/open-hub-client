<?php
/**
 * F2 · Benefits (mockup hub-frontend.html #scr-ben): 8 pillars (generic, OPEN-HUB-CLIENT §12) + the
 * industry's own benefits (persona) + call to action.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — trang Quyền lợi hội viên: 8 trụ cột + quyền lợi riêng của ngành.

defined( 'ABSPATH' ) || exit;
?>
<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › Quyền lợi hội viên</p>
	<h1>8 quyền lợi đặc biệt</h1><?php // [2026-11-26 10:25 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX4 — chủ Hub đổi chữ ?>
	<p class="lead">Công nghệ 0đ là lý do để bắt đầu. Đào tạo, khách hàng, uy tín và giao thương là lý do để gắn bó lâu dài.</p></div></div>
<div class="in">
	<div class="tiles">
		<?php foreach ( OHCL_Front::pillars() as $ohcl_i => $ohcl_p ) : ?>
			<div class="pnl tile"><span class="ic"><?php echo OHCL_Front::icon( $ohcl_p[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="no"><?php echo esc_html( str_pad( (string) ( $ohcl_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><b><?php echo esc_html( $ohcl_p[1] ); ?></b><p><?php echo esc_html( $ohcl_p[2] ); ?></p></div>
		<?php endforeach; ?>
	</div>
	<div class="g2 cert-row" style="margin:16px 0 32px">
		<div class="pnl"><h2>Riêng cho <?php echo esc_html( OHCL_Industry::term( 'member' ) ); ?></h2>
			<?php foreach ( $persona['benefits'] as $ohcl_b ) : ?>
				<div class="ben<?php echo $ohcl_b['highlight'] ? ' hl' : ''; ?>"><i><?php echo OHCL_Front::icon( $ohcl_b['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></i><div><b><?php echo esc_html( str_replace( '{days}', (string) $cfg['trial_days'], $ohcl_b['title'] ) ); ?></b><span><?php echo esc_html( str_replace( '{days}', (string) $cfg['trial_days'], $ohcl_b['text'] ) ); ?></span></div></div>
			<?php endforeach; ?>
		</div>
		<div class="pnl cta"><h2>Bắt đầu thế nào</h2>
			<ol class="steps">
				<li><b>Đăng ký</b> trên trang này, không cần thẻ thanh toán.</li>
				<li><b>Ban điều hành liên hệ</b> qua Zalo hoặc email để tiếp nhận.</li>
				<li><b>Nhận bộ công nghệ</b> và trợ lý AI dùng thử <?php echo (int) $cfg['trial_days']; ?> ngày.</li>
				<li><b>Đi lộ trình 90 ngày</b> trong My Hub, có Coach đồng hành.</li>
			</ol>
			<a class="btn g" href="<?php echo esc_url( OHCL_Front::url( 'join' ) ); ?>">Trở thành hội viên</a>
			<?php if ( OHCL_Front::page_on( 'tech' ) ) : ?><a class="btn o" href="<?php echo esc_url( OHCL_Front::url( 'tech' ) ); ?>">Xem bộ công nghệ 0đ</a><?php endif; ?>
		</div>
	</div>
</div>
