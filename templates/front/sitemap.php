<?php
/**
 * Sitemap: every live Hub page by group + the site's WordPress pages (they are drawn in the Hub frame too).
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — trang Sơ đồ trang: trang Hub theo nhóm + trang WordPress của site.

defined( 'ABSPATH' ) || exit;

$ohcl_wp = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'numberposts' => 100, 'orderby' => 'title', 'order' => 'ASC' ) );
?>
<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › Sơ đồ trang</p><h1>Sơ đồ trang</h1></div></div>
<div class="in sitemap">
	<?php foreach ( OHCL_Front::menu() as $ohcl_g => $ohcl_items ) : ?>
		<div class="pnl"><h2><?php echo esc_html( $ohcl_g ); ?></h2><ul>
			<?php foreach ( $ohcl_items as $ohcl_k => $ohcl_it ) : ?><li><a href="<?php echo esc_url( OHCL_Front::url( $ohcl_k ) ); ?>"><?php echo esc_html( $ohcl_it['title'] ); ?></a><?php echo ! empty( $ohcl_it['desc'] ) ? '<span class="note">' . esc_html( $ohcl_it['desc'] ) . '</span>' : ''; ?></li><?php endforeach; ?>
		</ul></div>
	<?php endforeach; ?>
	<div class="pnl"><h2>Tài khoản</h2><ul><li><a href="<?php echo esc_url( OHCL_Front::myhub_url() ); ?>">My Hub</a><span class="note">Trung tâm quyền lợi của hội viên</span></li><?php if ( OHCL_Front::blog_url() ) : ?><li><a href="<?php echo esc_url( OHCL_Front::blog_url() ); ?>">Tin tức</a></li><?php endif; ?></ul></div>
	<?php if ( $ohcl_wp ) : ?>
		<div class="pnl"><h2>Trang khác</h2><ul><?php foreach ( $ohcl_wp as $ohcl_p ) : ?><li><a href="<?php echo esc_url( get_permalink( $ohcl_p ) ); ?>"><?php echo esc_html( get_the_title( $ohcl_p ) ); ?></a></li><?php endforeach; ?></ul></div>
	<?php endif; ?>
</div>
