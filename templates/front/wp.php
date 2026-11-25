<?php
/**
 * WordPress views in the Hub frame: single post / page, blog, archives, search, 404. Content goes through
 * WordPress' own filters (the_content, shortcodes of plugins); the theme's markup and CSS are not used.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — bài viết, trang, tin tức, chuyên mục, tìm kiếm, 404 trong khung Hub.

defined( 'ABSPATH' ) || exit;

$ohcl_is_list = is_home() || is_archive() || is_search();
$ohcl_title   = '';
if ( is_search() ) {
	$ohcl_title = 'Kết quả tìm kiếm: ' . get_search_query();
} elseif ( is_home() ) {
	$ohcl_title = 'Tin tức';
} elseif ( is_archive() ) {
	$ohcl_title = wp_strip_all_tags( get_the_archive_title() );
}

if ( is_404() || ( ! $ohcl_is_list && ! have_posts() ) ) : ?>
	<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › Không tìm thấy</p><h1>Không tìm thấy trang</h1></div></div>
	<div class="in"><div class="pnl state" style="margin:20px 0 32px"><b>Trang này không có hoặc đã được dời đi</b>Thử tìm lại, hoặc xem sơ đồ trang.
		<form class="lookup" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" style="max-width:420px;margin:14px auto 0"><input class="fld" name="s" placeholder="Từ khoá…" aria-label="Từ khoá"><button class="btn g">Tìm</button></form>
		<div style="margin-top:12px"><a class="btn o sm" href="<?php echo esc_url( OHCL_Front::url( 'sitemap' ) ); ?>">Sơ đồ trang</a></div></div></div>
	<?php
	return;
endif;

if ( $ohcl_is_list ) : ?>
	<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › <?php echo esc_html( $ohcl_title ); ?></p><h1><?php echo esc_html( $ohcl_title ); ?></h1>
		<?php if ( is_archive() && get_the_archive_description() ) : ?><div class="lead"><?php echo wp_kses_post( get_the_archive_description() ); ?></div><?php endif; ?></div></div>
	<div class="in">
		<?php if ( have_posts() ) : ?>
			<div class="posts">
				<?php while ( have_posts() ) : the_post(); ?>
					<a class="pnl post" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'medium_large', array( 'class' => 'thumb' ) ); ?>
						<?php else : ?>
							<span class="thumb ph"><?php echo OHCL_Front::icon( 'doc' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<?php endif; ?>
						<span class="note"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></span>
						<b><?php the_title(); ?></b>
						<span class="ex"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26 ) ); ?></span>
					</a>
				<?php endwhile; ?>
			</div>
			<div class="pager"><?php echo wp_kses_post( (string) paginate_links( array( 'prev_text' => '← Trước', 'next_text' => 'Sau →' ) ) ); ?></div>
		<?php else : ?>
			<div class="pnl state" style="margin:20px 0 32px"><b>Chưa có bài nào</b><?php echo is_search() ? 'Thử từ khoá khác.' : 'Ban điều hành sẽ đăng tin ở đây.'; ?></div>
		<?php endif; ?>
	</div>
	<?php
	return;
endif;

while ( have_posts() ) :
	the_post();
	$ohcl_is_post = 'post' === get_post_type();
	?>
	<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a><?php echo $ohcl_is_post && OHCL_Front::blog_url() ? ' › <a href="' . esc_url( OHCL_Front::blog_url() ) . '">Tin tức</a>' : ''; ?></p>
		<h1><?php the_title(); ?></h1>
		<?php if ( $ohcl_is_post ) : ?><p class="lead"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></p><?php endif; ?></div></div>
	<div class="in"><article class="pnl prose" style="margin:20px 0 32px">
		<?php if ( $ohcl_is_post && has_post_thumbnail() ) : ?><?php the_post_thumbnail( 'large', array( 'class' => 'hero-img' ) ); ?><?php endif; ?>
		<?php the_content(); ?>
		<?php wp_link_pages(); ?>
	</article></div>
	<?php
endwhile;
