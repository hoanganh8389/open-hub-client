<?php
/**
 * Portal header (mockup vegan-persona.html .hdr). Vars from OHCL_Front::render(): $page, $data, $cfg, $persona.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — đầu trang cổng cộng đồng: logo ngành, ô tìm (→ danh bạ), đăng nhập, Trở thành hội viên.

defined( 'ABSPATH' ) || exit;
$ohcl_name = $cfg['name'] ? $cfg['name'] : get_bloginfo( 'name' );
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
	<form class="search" method="get" action="<?php echo esc_url( OHCL_Front::url( 'map' ) ); ?>" role="search">
		<input name="q" placeholder="Tìm <?php echo esc_attr( OHCL_Industry::term( 'business' ) ); ?>, món, địa điểm…" aria-label="Tìm kiếm">
		<button aria-label="Tìm"><?php echo OHCL_Front::icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</form>
	<nav class="hlinks" aria-label="Tài khoản">
		<a class="txt" href="<?php echo esc_url( OHCL_Front::url( 'map' ) ); ?>">Bản đồ</a>
		<?php if ( OHCL_Front::cert_on() ) : // [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — tra cứu chứng nhận (module 06). ?>
			<a class="txt" href="<?php echo esc_url( OHCL_Front::url( 'cert' ) ); ?>">Chứng nhận</a>
		<?php endif; ?>
		<?php if ( is_user_logged_in() ) : ?>
			<a class="txt" href="<?php echo esc_url( admin_url() ); ?>">Tài khoản</a>
		<?php else : ?>
			<a class="txt" href="<?php echo esc_url( wp_login_url( home_url( '/' ) ) ); ?>">Đăng nhập</a>
		<?php endif; ?>
		<a class="btn g sm" href="<?php echo esc_url( OHCL_Front::url( 'join' ) ); ?>">Trở thành hội viên</a>
	</nav>
</div></header>
<main id="ohcl-main">
