<?php
/**
 * Portal footer: every menu group of the page registry + account + sitemap. "Powered by Bizcity Open" stays
 * on every page (README licence note, R-HUB-8).
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — chân trang cổng cộng đồng.
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — chân trang liệt kê đủ nhóm trang, tài khoản, sơ đồ trang.

defined( 'ABSPATH' ) || exit;
$ohcl_name = $cfg['name'] ? $cfg['name'] : get_bloginfo( 'name' );
$ohcl_blog = OHCL_Front::blog_url();
?>
</main>
<footer class="ftr"><div class="in">
	<div class="brand"><span class="seal"><?php echo OHCL_Front::icon( 'leaf' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><b><?php echo esc_html( $ohcl_name ); ?></b><small><?php echo esc_html( $cfg['leader'] ? $cfg['leader'] : $persona['slogan'] ); ?></small></span></div>
	<?php foreach ( OHCL_Front::menu() as $ohcl_g => $ohcl_items ) : ?>
		<div><h4><?php echo esc_html( $ohcl_g ); ?></h4><ul>
			<?php foreach ( $ohcl_items as $ohcl_k => $ohcl_it ) : ?>
				<li><a href="<?php echo esc_url( OHCL_Front::url( $ohcl_k ) ); ?>"><?php echo esc_html( $ohcl_it['title'] ); ?></a></li>
			<?php endforeach; ?>
		</ul></div>
	<?php endforeach; ?>
	<div><h4>Tài khoản</h4><ul>
		<?php if ( is_user_logged_in() ) : ?>
			<li><a href="<?php echo esc_url( OHCL_Front::myhub_url() ); ?>">My Hub</a></li>
		<?php else : ?>
			<li><a href="<?php echo esc_url( wp_login_url( OHCL_Front::myhub_url() ) ); ?>">Đăng nhập</a></li>
		<?php endif; ?>
		<?php if ( $ohcl_blog ) : ?><li><a href="<?php echo esc_url( $ohcl_blog ); ?>">Tin tức</a></li><?php endif; ?>
		<li><a href="<?php echo esc_url( OHCL_Front::url( 'sitemap' ) ); ?>">Sơ đồ trang</a></li>
	</ul></div>
</div><div class="copy"><div class="in"><span>© <?php echo esc_html( gmdate( 'Y' ) . ' ' . $ohcl_name ); ?> · phần mềm mã nguồn mở GPL</span><span class="runs">POWERED BY BIZCITY OPEN</span></div></div></footer>
<?php wp_footer(); ?>
</body>
</html>
