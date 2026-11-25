<?php
/**
 * P3 · Public profile of one V4 member (mockup #scr-place). Not V4 ⇒ 404 page (status set by the router).
 * The Zalo button goes to the member's OWN assistant (R-HUB-12); the Hub never reads the conversation.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — hồ sơ cơ sở công khai: chỉ trường công khai, nút Zalo tới trợ lý của chính cơ sở.

defined( 'ABSPATH' ) || exit;

$ohcl_p = isset( $data['place'] ) ? $data['place'] : null;
if ( ! $ohcl_p ) : ?>
	<div class="page-h"><div class="in"><h1>Không tìm thấy cơ sở</h1></div></div>
	<div class="in"><div class="pnl state" style="margin:20px 0"><b>Cơ sở này không có hoặc chưa công khai</b>Chỉ cơ sở đã được xác minh và đồng ý công khai mới có trang.<div style="margin-top:12px"><a class="btn g sm" href="<?php echo esc_url( OHCL_Front::url( 'map' ) ); ?>">Về bản đồ</a></div></div></div>
	<?php
	return;
endif;
$ohcl_zalo = preg_replace( '/[^0-9]/', '', (string) $ohcl_p['zalo'] );
?>
<div class="cover"><span class="ph"><?php echo OHCL_Front::icon( 'store' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></div>
<div class="in prof">
	<div class="pnl">
		<p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url( 'map' ) ); ?>">Bản đồ</a><?php echo $ohcl_p['province'] ? ' › ' . esc_html( $ohcl_p['province'] ) : ''; ?></p>
		<h1><?php echo esc_html( $ohcl_p['title'] ); ?></h1>
		<?php if ( $ohcl_p['seg_label'] ) : ?><span class="badge"><?php echo esc_html( $ohcl_p['seg_label'] ); ?></span><?php endif; ?>
		<span class="badge mem">Đã xác minh</span>
		<dl class="kv">
			<?php if ( $ohcl_p['address'] ) : ?><dt>Địa chỉ</dt><dd><?php echo esc_html( $ohcl_p['address'] ); ?></dd><?php endif; ?>
			<?php if ( $ohcl_p['hours'] ) : ?><dt>Giờ mở cửa</dt><dd><?php echo esc_html( $ohcl_p['hours'] ); ?></dd><?php endif; ?>
			<?php if ( $ohcl_p['site_url'] ) : ?><dt>Website</dt><dd><a href="<?php echo esc_url( $ohcl_p['site_url'] ); ?>" rel="nofollow noopener" target="_blank"><?php echo esc_html( preg_replace( '#^https?://#', '', $ohcl_p['site_url'] ) ); ?></a></dd><?php endif; ?>
			<?php foreach ( $ohcl_p['fields'] as $ohcl_f ) : ?>
				<dt><?php echo esc_html( $ohcl_f['label'] ); ?></dt>
				<dd>
					<?php if ( 'url' === $ohcl_f['type'] ) : ?>
						<a href="<?php echo esc_url( $ohcl_f['value'] ); ?>" rel="nofollow noopener" target="_blank"><?php echo esc_html( $ohcl_f['value'] ); ?></a>
					<?php elseif ( 'bool' === $ohcl_f['type'] ) : ?>
						Có
					<?php else : ?>
						<?php echo esc_html( is_array( $ohcl_f['value'] ) ? implode( ', ', $ohcl_f['value'] ) : (string) $ohcl_f['value'] ); ?>
					<?php endif; ?>
				</dd>
			<?php endforeach; ?>
		</dl>
	</div>
	<div>
		<div class="pnl zalo"><b>Nhắn Zalo cho <?php echo esc_html( OHCL_Industry::term( 'business' ) ); ?></b>Trợ lý AI của cơ sở trả lời, ghi đơn vào sổ bán hàng của cơ sở.
			<?php if ( $ohcl_zalo ) : ?>
				<div style="margin-top:12px"><a class="btn g" style="width:100%" href="<?php echo esc_url( 'https://zalo.me/' . $ohcl_zalo ); ?>" rel="nofollow noopener" target="_blank">Nhắn Zalo</a></div>
			<?php else : ?>
				<p class="note">Cơ sở chưa công khai số Zalo.</p>
			<?php endif; ?>
			<p class="note">Tin nhắn đi tới trợ lý của chính cơ sở. Hub không đọc nội dung.</p>
		</div>
		<div class="pnl" style="margin-top:14px"><div class="map short" data-ohcl-map data-pins="<?php echo esc_attr( OHCL_Front::pins_json( array( $ohcl_p ) ) ); ?>"></div></div>
		<p class="runs" style="margin-top:10px">Cơ sở dùng bộ phần mềm mở do <?php echo esc_html( $cfg['name'] ); ?> tặng · chạy trên Bizcity Open</p>
	</div>
</div>
