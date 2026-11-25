<?php
/**
 * Portal footer. "Powered by Bizcity Open" stays on every page (README licence note, R-HUB-8).
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — chân trang cổng cộng đồng.

defined( 'ABSPATH' ) || exit;
$ohcl_name = $cfg['name'] ? $cfg['name'] : get_bloginfo( 'name' );
?>
</main>
<footer class="ftr"><div class="in">
	<div class="brand"><span class="seal"><?php echo OHCL_Front::icon( 'leaf' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><b><?php echo esc_html( $ohcl_name ); ?></b><small><?php echo esc_html( $cfg['leader'] ? $cfg['leader'] : $persona['slogan'] ); ?></small></span></div>
	<div><h4>Khám phá</h4><ul><li><a href="<?php echo esc_url( OHCL_Front::url( 'map' ) ); ?>">Bản đồ &amp; danh bạ</a></li><li><a href="<?php echo esc_url( OHCL_Front::url( 'join' ) ); ?>">Trở thành hội viên</a></li></ul></div>
	<div><h4>Tài khoản</h4><ul><li><a href="<?php echo esc_url( wp_login_url( home_url( '/' ) ) ); ?>">Đăng nhập</a></li></ul></div>
</div><div class="copy"><div class="in"><span>© <?php echo esc_html( gmdate( 'Y' ) . ' ' . $ohcl_name ); ?> · phần mềm mã nguồn mở GPL</span><span class="runs">POWERED BY BIZCITY OPEN</span></div></div></footer>
<?php wp_footer(); ?>
</body>
</html>
