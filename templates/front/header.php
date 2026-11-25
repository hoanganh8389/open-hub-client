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
$ohcl_nav   = OHCL_Front::nav(); // [2026-11-25 11:53 PM Johnny Chu - Chu Hoàng Anh] OHCL-F3 — cây menu (menu WordPress "Menu Hub" hoặc sổ trang)
$ohcl_blogv = 'wp' === $page && ( is_home() || is_singular( 'post' ) || is_category() );
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
	<?php foreach ( $ohcl_nav as $ohcl_n ) : ?>
		<?php if ( ! $ohcl_n['children'] ) : ?>
			<a class="top<?php echo ( '' !== $ohcl_n['key'] && $page === $ohcl_n['key'] ) || ( 'blog' === $ohcl_n['key'] && $ohcl_blogv ) ? ' on' : ''; ?>" href="<?php echo esc_url( $ohcl_n['url'] ); ?>"><?php echo esc_html( $ohcl_n['title'] ); ?></a>
		<?php else : ?>
			<?php $ohcl_on = in_array( $page, array_column( $ohcl_n['children'], 'key' ), true ); ?>
			<details class="dd<?php echo $ohcl_on ? ' on' : ''; ?>"><summary class="top"><?php echo esc_html( $ohcl_n['title'] ); ?></summary>
				<div class="mega">
					<?php foreach ( $ohcl_n['children'] as $ohcl_c ) : ?>
						<a href="<?php echo esc_url( $ohcl_c['url'] ); ?>"<?php echo '' !== $ohcl_c['key'] && $page === $ohcl_c['key'] ? ' aria-current="page"' : ''; ?>><b><?php echo esc_html( $ohcl_c['title'] ); ?></b><?php echo '' !== $ohcl_c['desc'] ? '<span>' . esc_html( $ohcl_c['desc'] ) . '</span>' : ''; ?></a>
					<?php endforeach; ?>
				</div>
			</details>
		<?php endif; ?>
	<?php endforeach; ?>
</div></nav>
</header>
<main id="ohcl-main">
