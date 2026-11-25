<?php
/**
 * Academy (module 05, OPEN-HUB-CLIENT §12 "Học viện"): learning tracks + industry templates / SOP / AI skills
 * from the contract (`academy_tracks`, `knowledge_assets`). Course enrolment and results are a later wave.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — trang Học viện: lộ trình học + kho mẫu ngành từ hợp đồng ngành.

defined( 'ABSPATH' ) || exit;

$ohcl_types = array( 'template' => 'Mẫu', 'sop' => 'Quy trình (SOP)', 'ai_skill' => 'Kỹ năng trợ lý AI', 'guru' => 'Agent Guru mẫu', 'module' => 'Module' );
?>
<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › Học viện</p>
	<h1>Học viện <?php echo esc_html( $cfg['name'] ); ?></h1>
	<p class="lead">Lộ trình học cho chủ cơ sở và nhân viên, cùng kho mẫu dùng ngay của ngành.</p></div></div>
<div class="in">
	<h2 class="sec-h">Lộ trình học</h2>
	<?php if ( $cfg['academy_tracks'] ) : ?>
		<div class="apps">
			<?php foreach ( $cfg['academy_tracks'] as $ohcl_i => $ohcl_t ) : ?>
				<div class="pnl app"><span class="ic"><?php echo OHCL_Front::icon( 'cap' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="no"><?php echo esc_html( str_pad( (string) ( $ohcl_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><b><?php echo esc_html( $ohcl_t['label'] ); ?></b><?php echo ! empty( $ohcl_t['description'] ) ? '<p>' . esc_html( $ohcl_t['description'] ) . '</p>' : ''; ?></div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<div class="pnl state"><b>Chưa có lộ trình học</b>Ban điều hành đang soạn.</div>
	<?php endif; ?>
	<h2 class="sec-h">Kho mẫu của ngành</h2>
	<?php if ( $cfg['knowledge_assets'] ) : ?>
		<div class="apps">
			<?php foreach ( $cfg['knowledge_assets'] as $ohcl_a ) : ?>
				<div class="pnl app"><span class="ic"><?php echo OHCL_Front::icon( 'ai_skill' === $ohcl_a['type'] ? 'bot' : 'doc' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="tag ok"><?php echo esc_html( isset( $ohcl_types[ $ohcl_a['type'] ] ) ? $ohcl_types[ $ohcl_a['type'] ] : $ohcl_a['type'] ); ?></span><b><?php echo esc_html( $ohcl_a['label'] ); ?></b><?php echo ! empty( $ohcl_a['description'] ) ? '<p>' . esc_html( $ohcl_a['description'] ) . '</p>' : ''; ?></div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<div class="pnl state"><b>Kho mẫu đang được bổ sung</b></div>
	<?php endif; ?>
	<div class="pnl" style="margin:16px 0 32px"><h2>Dành cho hội viên</h2><p>Hội viên nhận mẫu và cài kỹ năng trợ lý vào site của chính mình. Đăng ký để được mời vào các buổi học.</p><div style="margin-top:12px"><a class="btn g" href="<?php echo esc_url( OHCL_Front::url( 'join' ) ); ?>">Trở thành hội viên</a></div></div>
</div>
