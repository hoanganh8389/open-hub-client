<?php
/**
 * P2 · Directory + map (mockup #scr-map). V4 cards only; states: data · empty (no match / nobody yet) · error (core
 * unreachable ⇒ keys unknown ⇒ nothing can be proven V4).
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — danh bạ & bản đồ: lọc phía máy chủ (từ khoá, loại hình, tỉnh), chỉ V4, ba trạng thái.

defined( 'ABSPATH' ) || exit;

$ohcl_q = array(
	'q'        => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- bộ lọc công khai, chỉ đọc.
	'seg'      => isset( $_GET['seg'] ) ? sanitize_key( wp_unslash( $_GET['seg'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	'province' => isset( $_GET['province'] ) ? sanitize_text_field( wp_unslash( $_GET['province'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
);
$ohcl_pub   = OHCL_Members::public_list();
$ohcl_items = OHCL_Front::filter( $ohcl_pub['items'], $ohcl_q );
$ohcl_any   = '' !== $ohcl_q['q'] || '' !== $ohcl_q['seg'] || '' !== $ohcl_q['province'];
?>
<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › Bản đồ</p><h1>Bản đồ &amp; danh bạ <?php echo esc_html( $cfg['industry'] ? ( function_exists( 'mb_strtolower' ) ? mb_strtolower( $cfg['industry'], 'UTF-8' ) : $cfg['industry'] ) : '' ); ?></h1></div></div>
<div class="in dir">
	<form class="pnl" method="get" action="<?php echo esc_url( OHCL_Front::url( 'map' ) ); ?>"><h2>Bộ lọc</h2>
		<input class="fld" name="q" value="<?php echo esc_attr( $ohcl_q['q'] ); ?>" placeholder="Từ khoá…" aria-label="Từ khoá">
		<input class="fld" name="province" value="<?php echo esc_attr( $ohcl_q['province'] ); ?>" placeholder="Tỉnh / thành phố" aria-label="Tỉnh / thành phố">
		<b class="lbl">Loại hình</b>
		<label class="chk"><input type="radio" name="seg" value=""<?php checked( '', $ohcl_q['seg'] ); ?>> Tất cả</label>
		<?php foreach ( $cfg['segments'] as $ohcl_s => $ohcl_l ) : ?>
			<label class="chk"><input type="radio" name="seg" value="<?php echo esc_attr( $ohcl_s ); ?>"<?php checked( $ohcl_s, $ohcl_q['seg'] ); ?>> <?php echo esc_html( $ohcl_l ); ?></label>
		<?php endforeach; ?>
		<button class="btn g" style="width:100%;margin-top:10px">Lọc</button>
		<?php if ( $ohcl_any ) : ?><p class="note"><a href="<?php echo esc_url( OHCL_Front::url( 'map' ) ); ?>">Bỏ lọc</a></p><?php endif; ?>
	</form>

	<div class="pnl">
		<?php if ( $ohcl_pub['degraded'] ) : ?>
			<div class="state err"><b>Không tải được danh bạ</b>Thử lại sau ít phút.<br><small>Mã: ohcl_directory_unavailable<?php echo current_user_can( 'manage_options' ) && get_transient( 'ohcl_dir_error' ) ? ' · lõi: ' . esc_html( get_transient( 'ohcl_dir_error' ) ) . ' (chỉ quản trị viên thấy)' : ''; // [2026-11-26 01:11 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX3 ?></small></div>
		<?php elseif ( ! $ohcl_items ) : ?>
			<div class="state"><b><?php echo $ohcl_any ? 'Chưa có cơ sở nào khớp bộ lọc' : 'Danh bạ đang chờ cơ sở đầu tiên'; ?></b>
				<?php echo $ohcl_any ? 'Bỏ bớt điều kiện lọc, hoặc mời cơ sở của bạn tham gia.' : 'Chỉ cơ sở đã được ban điều hành xác minh và đồng ý công khai mới hiện ở đây.'; ?>
				<div style="margin-top:12px"><a class="btn g sm" href="<?php echo esc_url( OHCL_Front::url( 'join' ) ); ?>">Đăng ký cơ sở</a></div></div>
		<?php else : ?>
			<h2><?php echo esc_html( number_format_i18n( count( $ohcl_items ) ) ); ?> kết quả</h2>
			<?php foreach ( $ohcl_items as $ohcl_it ) : ?>
				<a class="item" href="<?php echo esc_url( OHCL_Front::place_url( $ohcl_it['id'], $ohcl_it['title'] ) ); ?>">
					<span class="ph"><?php echo OHCL_Front::icon( 'store' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span><b><?php echo esc_html( $ohcl_it['title'] ); ?></b>
						<span class="sub"><?php echo esc_html( implode( ' · ', array_filter( array( $ohcl_it['seg_label'], $ohcl_it['province'] ) ) ) ); ?></span>
						<?php foreach ( $ohcl_it['fields'] as $ohcl_f ) : ?>
							<?php if ( 'multi' === $ohcl_f['type'] ) : ?>
								<?php foreach ( (array) $ohcl_f['value'] as $ohcl_v ) : ?><span class="badge"><?php echo esc_html( $ohcl_v ); ?></span><?php endforeach; ?>
							<?php endif; ?>
						<?php endforeach; ?>
						<?php echo OHCL_Front::trust_badge( $ohcl_it ); // phpcs:ignore WordPress.Security.EscapeOutput -- [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-DEMO ?>
					</span>
				</a>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<div class="pnl"><?php if ( $ohcl_items ) : ?><div class="map tall" data-ohcl-map data-pins="<?php echo esc_attr( OHCL_Front::pins_json( $ohcl_items ) ); ?>"></div><?php else : ?><div class="map tall state">Bản đồ hiện khi có cơ sở công khai.</div><?php endif; ?></div>
</div>
