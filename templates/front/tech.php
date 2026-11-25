<?php
/**
 * 0đ technology set (OPEN-HUB-CLIENT §12 "Bộ công nghệ 0đ"): the apps the industry contract turns on, plus
 * the other open apps a member can add. Licence 0đ; AI by usage; cloud by site (no invented prices).
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — trang Bộ công nghệ 0đ: ứng dụng bật sẵn theo ngành + ứng dụng thêm được.

defined( 'ABSPATH' ) || exit;

$ohcl_cat  = OHCL_Front::app_catalog();
$ohcl_on   = array_values( array_intersect( $cfg['apps'], array_keys( $ohcl_cat ) ) );
$ohcl_more = array_values( array_diff( array_keys( $ohcl_cat ), $ohcl_on ) );
?>
<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › Bộ công nghệ 0đ</p>
	<h1>Bộ công nghệ 0đ cho <?php echo esc_html( OHCL_Industry::term( 'member' ) ); ?></h1>
	<p class="lead">Phần mềm mã nguồn mở của Bizcity Open. Bản quyền 0đ, không giới hạn người dùng, dữ liệu thuộc về cơ sở.</p></div></div>
<div class="in">
	<div class="gift0 pnl"><span class="kick">Bản quyền phần mềm</span><b>0 đ</b><p><?php echo (int) count( $ohcl_on ); ?> ứng dụng bật sẵn cho ngành · trợ lý AI dùng thử <?php echo (int) $cfg['trial_days']; ?> ngày, sau đó tính theo mức dùng</p></div>
	<?php if ( $ohcl_on ) : ?>
		<h2 class="sec">Bật sẵn cho <?php echo esc_html( $cfg['industry'] ? $cfg['industry'] : 'ngành' ); ?></h2>
		<div class="apps">
			<?php foreach ( $ohcl_on as $ohcl_k ) : ?>
				<div class="pnl app"><span class="ic"><?php echo OHCL_Front::icon( $ohcl_cat[ $ohcl_k ][2] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><b><?php echo esc_html( $ohcl_cat[ $ohcl_k ][0] ); ?></b><p><?php echo esc_html( $ohcl_cat[ $ohcl_k ][1] ); ?></p><span class="tag ok">Bật sẵn</span></div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<?php if ( $ohcl_more ) : ?>
		<h2 class="sec">Thêm khi cần</h2>
		<div class="apps">
			<?php foreach ( $ohcl_more as $ohcl_k ) : ?>
				<div class="pnl app"><span class="ic"><?php echo OHCL_Front::icon( $ohcl_cat[ $ohcl_k ][2] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><b><?php echo esc_html( $ohcl_cat[ $ohcl_k ][0] ); ?></b><p><?php echo esc_html( $ohcl_cat[ $ohcl_k ][1] ); ?></p></div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<div class="pnl" style="margin:16px 0 32px"><h2>Chi phí</h2>
		<table class="tbl"><tbody>
			<tr><td>Bản quyền phần mềm</td><td class="n"><b>0 đ</b></td></tr>
			<tr><td>Trợ lý AI</td><td class="n">dùng thử <?php echo (int) $cfg['trial_days']; ?> ngày, sau đó theo mức dùng</td></tr>
			<tr><td>Đám mây (nếu không tự cài)</td><td class="n">theo site, báo giá khi đăng ký</td></tr>
		</tbody></table>
		<p class="note">Hết dùng thử, phần mềm và sổ sách vẫn chạy; chỉ trợ lý AI tạm dừng.</p>
		<div style="margin-top:12px"><a class="btn g" href="<?php echo esc_url( OHCL_Front::url( 'join' ) ); ?>">Nhận bộ công nghệ</a></div>
	</div>
</div>
