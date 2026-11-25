<?php
/**
 * Portal header (mockup vegan-persona.html .hdr) + the full menu from OHCL_Front::menu() (groups of the page
 * registry, ≤ 6 entries, R-LEAN-4). Menus are <details> so they open by tap on phones without JS.
 * Vars from OHCL_Front::html(): $page, $data, $cfg, $persona.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — đầu trang cổng cộng đồng: logo ngành, ô tìm (→ danh bạ), đăng nhập, Trở thành hội viên.
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — thêm thanh menu đủ nhóm trang (sổ trang), My Hub khi đã đăng nhập, Tin tức khi site có bài.

defined( 'ABSPATH' ) || exit;
$ohcl_name  = $cfg['name'] ? $cfg['name'] : get_bloginfo( 'name' );
$ohcl_menu  = OHCL_Front::menu();
$ohcl_pages = OHCL_Front::pages();
$ohcl_cur   = isset( $ohcl_pages[ $page ] ) ? $ohcl_pages[ $page ]['group'] : '';
$ohcl_blog  = OHCL_Front::blog_url();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'ohcl-front ohcl-p-' . $page ); ?>>
<?php wp_body_open(); ?>
<header class="hdr"><div class="in">
	<a class="brand" href="<?php echo esc_url( OHCL_Front::url() ); ?>">
		<?php if ( $cfg['brand']['logo'] ) : ?>
			<img class="seal img" src="<?php echo esc_url( $cfg['brand']['logo'] ); ?>" alt="">
		<?php else : ?>
			<span class="seal"><?php echo OHCL_Front::icon( 'leaf' ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG cố định. ?></span>
		<?php endif; ?>
		<span><b><?php echo esc_html( $ohcl_name ); ?></b><small><?php echo esc_html( $cfg['brand']['slogan'] ? $cfg['brand']['slogan'] : $persona['slogan'] ); ?></small></span>
	</a>
	<form class="search" method="get" action="<?php echo esc_url( OHCL_Front::page_on( 'map' ) ? OHCL_Front::url( 'map' ) : home_url( '/' ) ); ?>" role="search">
		<input name="<?php echo OHCL_Front::page_on( 'map' ) ? 'q' : 's'; ?>" placeholder="Tìm <?php echo esc_attr( OHCL_Industry::term( 'business' ) ); ?>, món, địa điểm…" aria-label="Tìm kiếm">
		<button aria-label="Tìm"><?php echo OHCL_Front::icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</form>
	<nav class="hlinks" aria-label="Tài khoản">
		<?php if ( is_user_logged_in() ) : ?>
			<a class="txt" href="<?php echo esc_url( OHCL_Front::myhub_url() ); ?>"><?php echo OHCL_Front::icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>My Hub</a>
		<?php else : ?>
			<a class="txt" href="<?php echo esc_url( wp_login_url( OHCL_Front::myhub_url() ) ); ?>">Đăng nhập</a>
		<?php endif; ?>
		<a class="btn g sm" href="<?php echo esc_url( OHCL_Front::url( 'join' ) ); ?>">Trở thành hội viên</a>
	</nav>
</div>
<nav class="nav" aria-label="Menu chính"><div class="in">
	<a class="top<?php echo 'home' === $page ? ' on' : ''; ?>" href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a>
	<?php foreach ( $ohcl_menu as $ohcl_g => $ohcl_items ) : ?>
		<?php if ( 1 === count( $ohcl_items ) ) : ?>
			<?php foreach ( $ohcl_items as $ohcl_k => $ohcl_it ) : ?>
				<a class="top<?php echo $page === $ohcl_k ? ' on' : ''; ?>" href="<?php echo esc_url( OHCL_Front::url( $ohcl_k ) ); ?>"><?php echo esc_html( $ohcl_g ); ?></a>
			<?php endforeach; ?>
		<?php else : ?>
			<details class="dd<?php echo $ohcl_cur === $ohcl_g ? ' on' : ''; ?>"><summary class="top"><?php echo esc_html( $ohcl_g ); ?></summary>
				<div class="mega">
					<?php foreach ( $ohcl_items as $ohcl_k => $ohcl_it ) : ?>
						<a href="<?php echo esc_url( OHCL_Front::url( $ohcl_k ) ); ?>"<?php echo $page === $ohcl_k ? ' aria-current="page"' : ''; ?>><b><?php echo esc_html( $ohcl_it['title'] ); ?></b><?php echo ! empty( $ohcl_it['desc'] ) ? '<span>' . esc_html( $ohcl_it['desc'] ) . '</span>' : ''; ?></a>
					<?php endforeach; ?>
				</div>
			</details>
		<?php endif; ?>
	<?php endforeach; ?>
	<?php if ( $ohcl_blog ) : ?>
		<a class="top<?php echo 'wp' === $page && ( is_home() || is_singular( 'post' ) || is_category() ) ? ' on' : ''; ?>" href="<?php echo esc_url( $ohcl_blog ); ?>">Tin tức</a>
	<?php endif; ?>
</div></nav>
</header>
<main id="ohcl-main">
