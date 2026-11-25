<?php
/**
 * P1 · Home of the community portal (mockup vegan-persona.html #scr-home).
 * Numbers: members = active member records, businesses / places = public V4 cards; events have no source
 * yet ⇒ "—" (R-HUB-6). News = the site's latest posts, panel hidden when there are none.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — trang chủ cổng ngành: hero persona, lối tắt, tìm nhanh, bản đồ V4, tin tức thật, danh mục, số liệu thật.

defined( 'ABSPATH' ) || exit;

$ohcl_pub     = OHCL_Members::public_list();
$ohcl_members = OHCL_Members::counts( OHCL_Members::all(), array(), time() );
$ohcl_geo     = count( array_filter( $ohcl_pub['items'], function ( $x ) { return $x['lat'] || $x['lng']; } ) );
$ohcl_posts   = get_posts( array( 'numberposts' => 3, 'post_status' => 'publish', 'post_type' => 'post' ) );
$ohcl_segs    = $cfg['segments'];
$ohcl_blog    = (int) get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : '';
$ohcl_ind     = $cfg['industry'] ? ( function_exists( 'mb_strtolower' ) ? mb_strtolower( $cfg['industry'], 'UTF-8' ) : $cfg['industry'] ) : 'ngành';
?>
<section class="hero"><div class="in">
	<p class="slogan"><?php echo esc_html( $persona['slogan'] ); ?></p>
	<div>
		<p class="kick"><?php echo esc_html( $persona['kicker'] ); ?></p>
		<h1><?php echo esc_html( $persona['title'] ); ?> <span><?php echo esc_html( $persona['title_em'] ); ?></span></h1>
		<p><?php echo esc_html( $persona['subtitle'] ); ?></p>
		<a class="btn g" href="<?php echo esc_url( OHCL_Front::url( 'map' ) ); ?>">Khám phá bản đồ</a>
		<a class="btn o" href="<?php echo esc_url( OHCL_Front::url( 'join' ) ); ?>">Trở thành hội viên</a>
	</div>
	<div class="bowl" aria-hidden="true"><?php echo OHCL_Front::icon( 'leaf' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<div class="vals">
		<?php foreach ( $persona['values'] as $ohcl_v ) : ?>
			<div class="val"><i><?php echo OHCL_Front::icon( $ohcl_v['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></i><?php echo esc_html( $ohcl_v['label'] ); ?></div>
		<?php endforeach; ?>
	</div>
</div></section>

<div class="in quick"><nav class="bar" aria-label="Lối tắt">
	<a href="<?php echo esc_url( OHCL_Front::url( 'join' ) ); ?>"><?php echo OHCL_Front::icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Hội viên</a>
	<a href="<?php echo esc_url( OHCL_Front::url( 'map' ) ); ?>"><?php echo OHCL_Front::icon( 'factory' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Doanh nghiệp</a>
	<a href="<?php echo esc_url( OHCL_Front::url( 'map' ) ); ?>"><?php echo OHCL_Front::icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Địa điểm</a>
	<a href="<?php echo esc_url( OHCL_Front::url( 'join' ) ); ?>"><?php echo OHCL_Front::icon( 'gift' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Quyền lợi</a>
	<?php if ( $ohcl_posts && $ohcl_blog ) : ?>
		<a href="<?php echo esc_url( $ohcl_blog ); ?>"><?php echo OHCL_Front::icon( 'doc' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Tin tức</a>
	<?php endif; ?>
</nav></div>

<div class="in row">
	<div class="pnl"><h2><span><span class="num">1</span>Tìm kiếm nhanh</span></h2>
		<form method="get" action="<?php echo esc_url( OHCL_Front::url( 'map' ) ); ?>">
			<input class="fld" name="q" placeholder="Từ khoá…" aria-label="Từ khoá">
			<select class="fld" name="seg" aria-label="Loại hình"><option value="">Mọi loại hình</option>
				<?php foreach ( $ohcl_segs as $ohcl_s => $ohcl_l ) : ?>
					<option value="<?php echo esc_attr( $ohcl_s ); ?>"><?php echo esc_html( $ohcl_l ); ?></option>
				<?php endforeach; ?>
			</select>
			<input class="fld" name="province" placeholder="Tỉnh / thành phố" aria-label="Tỉnh / thành phố">
			<button class="btn g" style="width:100%">Tìm kiếm</button>
		</form>
	</div>
	<div class="pnl wide"><h2><span><span class="num">2</span>Bản đồ <?php echo esc_html( $ohcl_ind ); ?></span><a href="<?php echo esc_url( OHCL_Front::url( 'map' ) ); ?>">Xem bản đồ đầy đủ</a></h2>
		<?php if ( $ohcl_pub['items'] ) : ?>
			<div class="map" data-ohcl-map data-pins="<?php echo esc_attr( OHCL_Front::pins_json( $ohcl_pub['items'] ) ); ?>"></div>
		<?php else : ?>
			<div class="map state"><b>Bản đồ đang chờ cơ sở đầu tiên</b>Chỉ cơ sở đã được ban điều hành xác minh và đồng ý công khai mới hiện ở đây.</div>
		<?php endif; ?>
	</div>
	<div class="pnl"><h2><span><span class="num">3</span>Tin tức</span></h2>
		<?php if ( $ohcl_posts ) : ?>
			<?php foreach ( $ohcl_posts as $ohcl_p ) : ?>
				<a class="news" href="<?php echo esc_url( get_permalink( $ohcl_p ) ); ?>">
					<?php if ( has_post_thumbnail( $ohcl_p ) ) : ?>
						<?php echo get_the_post_thumbnail( $ohcl_p, 'thumbnail', array( 'class' => 'ph' ) ); ?>
					<?php else : ?>
						<span class="ph"><?php echo OHCL_Front::icon( 'doc' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<?php endif; ?>
					<span><b><?php echo esc_html( get_the_title( $ohcl_p ) ); ?></b><small><?php echo esc_html( get_the_date( 'd/m/Y', $ohcl_p ) ); ?></small></span>
				</a>
			<?php endforeach; ?>
		<?php else : ?>
			<p class="state sm"><b>Chưa có tin</b>Tin tức của Hub sẽ hiện ở đây.</p>
		<?php endif; ?>
	</div>
</div>

<?php if ( $persona['categories'] || $ohcl_segs ) : ?>
<div class="in sec"><div class="pnl"><h2><span><span class="num">4</span>Danh mục</span></h2>
	<div class="cats">
		<?php if ( $persona['categories'] ) : ?>
			<?php foreach ( $persona['categories'] as $ohcl_c ) : ?>
				<a class="cat" href="<?php echo esc_url( OHCL_Front::url( 'map', $ohcl_c['segment'] ? array( 'seg' => $ohcl_c['segment'] ) : array() ) ); ?>"><span class="ph"><?php echo OHCL_Front::icon( $ohcl_c['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><?php echo esc_html( $ohcl_c['label'] ); ?></span></a>
			<?php endforeach; ?>
		<?php else : ?>
			<?php foreach ( array_slice( $ohcl_segs, 0, 8, true ) as $ohcl_s => $ohcl_l ) : ?>
				<a class="cat" href="<?php echo esc_url( OHCL_Front::url( 'map', array( 'seg' => $ohcl_s ) ) ); ?>"><span class="ph"><?php echo OHCL_Front::icon( 'store' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><?php echo esc_html( $ohcl_l ); ?></span></a>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div></div>
<?php endif; ?>

<section class="stats"><div class="in">
	<div class="st"><i><?php echo OHCL_Front::icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></i><div><b><?php echo esc_html( number_format_i18n( $ohcl_members['active'] ) ); ?></b><span><?php echo esc_html( ucfirst( OHCL_Industry::term( 'member' ) ) ); ?></span></div></div>
	<div class="st"><i><?php echo OHCL_Front::icon( 'factory' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></i><div><b><?php echo esc_html( $ohcl_pub['degraded'] ? '—' : number_format_i18n( count( $ohcl_pub['items'] ) ) ); ?></b><span>Đã xác minh, công khai</span></div></div>
	<div class="st"><i><?php echo OHCL_Front::icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></i><div><b><?php echo esc_html( $ohcl_pub['degraded'] ? '—' : number_format_i18n( $ohcl_geo ) ); ?></b><span>Địa điểm trên bản đồ</span></div></div>
	<div class="st"><i><?php echo OHCL_Front::icon( 'cal' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></i><div><b class="na">—</b><span>Sự kiện</span></div></div>
	<p class="motto"><?php echo esc_html( $persona['motto'] ); ?></p>
</div></section>
