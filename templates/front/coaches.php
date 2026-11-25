<?php
/**
 * Coaches & experts (module 04, OPEN-HUB-CLIENT §12): the areas coaches work in (contract `coach_skills`),
 * how coaching works, the number of approved coaches. Public coach profiles and booking are wave Đ3.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — trang Coach & chuyên gia: lĩnh vực, cách đồng hành, số Coach đã duyệt (số thật).

defined( 'ABSPATH' ) || exit;

$ohcl_n = class_exists( 'OHCL_Coach' ) ? OHCL_Coach::approved_count() : 0;
?>
<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › Coach &amp; chuyên gia</p>
	<h1>Coach &amp; chuyên gia đồng hành</h1>
	<p class="lead">Mỗi <?php echo esc_html( OHCL_Industry::term( 'member' ) ); ?> có thể có một Coach phụ trách: cùng lập kế hoạch 90 ngày, rà vận hành, hướng dẫn dùng bộ công nghệ.</p></div></div>
<div class="in">
	<div class="g2 cert-row" style="margin-top:18px">
		<div class="pnl"><h2>Lĩnh vực đồng hành</h2>
			<?php if ( $cfg['coach_skills'] ) : ?>
				<div class="chips"><?php foreach ( $cfg['coach_skills'] as $ohcl_s ) : ?><span class="badge"><?php echo esc_html( $ohcl_s['label'] ); ?></span><?php endforeach; ?></div>
			<?php else : ?>
				<p class="note">Ban điều hành chưa công bố lĩnh vực.</p>
			<?php endif; ?>
			<h2 style="margin-top:18px">Coach làm gì cùng bạn</h2>
			<ol class="steps">
				<li><b>Đánh giá hiện trạng</b> cơ sở trong tuần đầu.</li>
				<li><b>Lập kế hoạch 90 ngày</b>: khởi tạo, chuẩn hoá, tăng trưởng, nâng tầm.</li>
				<li><b>Gặp định kỳ</b> để rà số liệu, giao việc, gỡ vướng.</li>
				<li><b>Ghi nhận kết quả</b> trong My Hub của bạn.</li>
			</ol>
		</div>
		<div class="pnl cta"><h2>Đội Coach</h2>
			<p class="big"><?php echo esc_html( $ohcl_n ? number_format_i18n( $ohcl_n ) : '—' ); ?></p>
			<p class="note"><?php echo $ohcl_n ? 'Coach đã được ban điều hành duyệt.' : 'Hub đang tuyển những Coach đầu tiên.'; ?></p>
			<a class="btn g" href="<?php echo esc_url( OHCL_Front::url( 'join' ) ); ?>">Đăng ký để có Coach</a>
			<a class="btn o" href="<?php echo esc_url( OHCL_Front::url( 'becoach' ) ); ?>">Trở thành Coach</a>
		</div>
	</div>
	<div style="height:32px"></div>
</div>
