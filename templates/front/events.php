<?php
/**
 * Events & programmes (OPEN-HUB-CLIENT §12 "Sự kiện"): the industry's recurring programmes (contract
 * `campaign_templates`) + the site's posts in the category with slug "su-kien" (if the Hub posts events).
 * Campaign tracking per member is Marketing, wave Đ3.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — trang Sự kiện & chương trình: chương trình theo dịp của ngành + bài trong chuyên mục su-kien.

defined( 'ABSPATH' ) || exit;

$ohcl_cat   = function_exists( 'get_category_by_slug' ) ? get_category_by_slug( apply_filters( 'ohcl_events_category', 'su-kien' ) ) : null;
$ohcl_posts = $ohcl_cat ? get_posts( array( 'numberposts' => 9, 'post_status' => 'publish', 'cat' => (int) $ohcl_cat->term_id ) ) : array();
?>
<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › Sự kiện &amp; chương trình</p>
	<h1>Sự kiện &amp; chương trình</h1>
	<p class="lead">Chương trình chung của ngành và sự kiện do <?php echo esc_html( $cfg['name'] ); ?> tổ chức. Hội viên được ưu tiên chỗ.</p></div></div>
<div class="in">
	<?php if ( $cfg['campaign_templates'] ) : ?>
		<h2 class="sec">Chương trình theo dịp</h2>
		<div class="apps">
			<?php foreach ( $cfg['campaign_templates'] as $ohcl_c ) : ?>
				<div class="pnl app"><span class="ic"><?php echo OHCL_Front::icon( 'cal' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><b><?php echo esc_html( $ohcl_c['label'] ); ?></b><?php echo ! empty( $ohcl_c['description'] ) ? '<p>' . esc_html( $ohcl_c['description'] ) . '</p>' : ''; ?></div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<h2 class="sec">Sự kiện sắp tới</h2>
	<?php if ( $ohcl_posts ) : ?>
		<div class="posts">
			<?php foreach ( $ohcl_posts as $ohcl_p ) : ?>
				<a class="pnl post" href="<?php echo esc_url( get_permalink( $ohcl_p ) ); ?>">
					<?php if ( has_post_thumbnail( $ohcl_p ) ) : ?><?php echo get_the_post_thumbnail( $ohcl_p, 'medium_large', array( 'class' => 'thumb' ) ); ?><?php else : ?><span class="thumb ph"><?php echo OHCL_Front::icon( 'cal' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
					<span class="note"><?php echo esc_html( get_the_date( 'd/m/Y', $ohcl_p ) ); ?></span><b><?php echo esc_html( get_the_title( $ohcl_p ) ); ?></b>
				</a>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<div class="pnl state" style="margin-bottom:32px"><b>Chưa có sự kiện nào được đăng</b>Theo dõi trang này hoặc đăng ký hội viên để nhận thông báo sớm.</div>
	<?php endif; ?>
	<div style="height:24px"></div>
</div>
