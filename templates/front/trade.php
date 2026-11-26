<?php
/**
 * Supply & trade (OPEN-HUB-CLIENT §12 "Kết nối giao thương"): public V4 members grouped by segment, so a
 * buyer finds producers, suppliers and partners. Only V4 cards (R-MAP), same cache as the directory.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — trang Kết nối giao thương: cơ sở công khai theo loại hình, dữ liệu thật.

defined( 'ABSPATH' ) || exit;

$ohcl_pub   = OHCL_Members::public_list();
$ohcl_group = array();
foreach ( $ohcl_pub['items'] as $ohcl_it ) {
	$ohcl_group[ $ohcl_it['segment'] ? $ohcl_it['segment'] : '—' ][] = $ohcl_it;
}
?>
<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › Kết nối giao thương</p>
	<h1>Kết nối giao thương</h1>
	<p class="lead">Tìm nguồn cung, nhà sản xuất, đối tác trong cộng đồng <?php echo esc_html( $cfg['name'] ); ?>. Cơ sở thật dưới đây đã được xác minh<?php echo ! empty( $ohcl_pub['demo'] ) ? '; cơ sở có nhãn "Mẫu" là dữ liệu xem thử' : ''; ?>.</p></div></div>
<div class="in">
	<?php if ( $ohcl_pub['degraded'] ) : ?>
		<div class="pnl state err" style="margin:20px 0 32px"><b>Không tải được danh sách</b>Thử lại sau ít phút.<br><small>Mã: ohcl_directory_unavailable<?php echo current_user_can( 'manage_options' ) && get_transient( 'ohcl_dir_error' ) ? ' · lõi: ' . esc_html( get_transient( 'ohcl_dir_error' ) ) . ' (chỉ quản trị viên thấy)' : ''; // [2026-11-26 01:11 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX3 ?></small></div>
	<?php elseif ( ! $ohcl_group ) : ?>
		<div class="pnl state" style="margin:20px 0 32px"><b>Chưa có cơ sở công khai</b>Cơ sở được ban điều hành xác minh và đồng ý công khai sẽ hiện ở đây, xếp theo loại hình.<div style="margin-top:12px"><a class="btn g sm" href="<?php echo esc_url( OHCL_Front::url( 'join' ) ); ?>">Đăng ký cơ sở</a></div></div>
	<?php else : ?>
		<div class="segs">
			<?php foreach ( $cfg['segments'] + array( '—' => 'Khác' ) as $ohcl_s => $ohcl_l ) : ?>
				<?php if ( empty( $ohcl_group[ $ohcl_s ] ) ) { continue; } ?>
				<section class="pnl seg"><h2><span><?php echo esc_html( $ohcl_l ); ?> <span class="note">· <?php echo (int) count( $ohcl_group[ $ohcl_s ] ); ?> cơ sở</span></span>
					<?php if ( OHCL_Front::page_on( 'map' ) && '—' !== $ohcl_s ) : ?><a href="<?php echo esc_url( OHCL_Front::url( 'map', array( 'seg' => $ohcl_s ) ) ); ?>">Xem trên bản đồ</a><?php endif; ?></h2>
					<?php foreach ( $ohcl_group[ $ohcl_s ] as $ohcl_it ) : ?>
						<a class="item" href="<?php echo esc_url( OHCL_Front::place_url( $ohcl_it['id'], $ohcl_it['title'] ) ); ?>"><span class="ph"><?php echo OHCL_Front::icon( 'store' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span><b><?php echo esc_html( $ohcl_it['title'] ); ?></b><span class="sub"><?php echo esc_html( $ohcl_it['province'] ); ?></span><?php echo OHCL_Front::trust_badge( $ohcl_it ); // phpcs:ignore WordPress.Security.EscapeOutput -- [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-DEMO ?>
								<?php foreach ( $ohcl_it['fields'] as $ohcl_f ) : ?><?php if ( 'multi' === $ohcl_f['type'] ) : ?><?php foreach ( (array) $ohcl_f['value'] as $ohcl_v ) : ?><span class="badge"><?php echo esc_html( $ohcl_v ); ?></span><?php endforeach; ?><?php endif; ?><?php endforeach; ?>
							</span></a>
					<?php endforeach; ?>
				</section>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<div class="pnl" style="margin:16px 0 32px"><h2>Bạn đang tìm nguồn cung hoặc đối tác?</h2><p>Nhắn trực tiếp cho cơ sở qua nút Zalo trên hồ sơ của họ. <?php echo esc_html( $cfg['name'] ); ?> không đứng giữa giao dịch và không đọc tin nhắn.</p></div>
</div>
