<?php
/**
 * F5 / F6 · My Hub (mockup hub-frontend.html #scr-my, #scr-road): the logged-in member's centre.
 * Tabs: overview · my technology · business profile (editable public fields + consent) · my certificates ·
 * 90-day roadmap · support. Guests see a login card; users without a member record see why.
 * Vars: $data['tab'].
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — My Hub: 6 mục, số liệu thật, sửa hồ sơ công khai, lộ trình 90 ngày, gửi hỗ trợ.

defined( 'ABSPATH' ) || exit;

$ohcl_tab = isset( $data['tab'] ) && isset( OHCL_MyHub::TABS[ $data['tab'] ] ) ? $data['tab'] : '';
$ohcl_cur = OHCL_MyHub::current();
$ohcl_m   = $ohcl_cur['member'];
$ohcl_err = isset( $_GET['err'] ) ? OHCL_Front::join_error( sanitize_key( wp_unslash( $_GET['err'] ) ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ chọn câu báo lỗi.

if ( ! $ohcl_m ) :
	$ohcl_why = array(
		'guest'     => array( 'Đăng nhập để vào My Hub', 'My Hub là trung tâm quyền lợi của hội viên: công nghệ, chứng nhận, lộ trình 90 ngày.' ),
		'none'      => array( 'Tài khoản chưa gắn với hồ sơ hội viên', 'Chưa có hồ sơ hội viên nào mang email của tài khoản này. Đăng ký hội viên, hoặc nhắn ban điều hành gắn tài khoản cho bạn.' ),
		'ambiguous' => array( 'Có nhiều hồ sơ cùng email', 'Ban điều hành cần chọn hồ sơ đúng cho tài khoản của bạn. Nhắn ban điều hành để được gắn.' ),
		'taken'     => array( 'Hồ sơ đã gắn với tài khoản khác', 'Hồ sơ mang email này đang thuộc một tài khoản khác. Nhắn ban điều hành để kiểm tra.' ),
	);
	$ohcl_w = isset( $ohcl_why[ $ohcl_cur['why'] ] ) ? $ohcl_why[ $ohcl_cur['why'] ] : $ohcl_why['none'];
	?>
	<div class="page-h"><div class="in"><p class="crumb"><a href="<?php echo esc_url( OHCL_Front::url() ); ?>">Trang chủ</a> › My Hub</p><h1>My Hub</h1></div></div>
	<div class="in"><div class="pnl state" style="margin:20px 0 32px"><b><?php echo esc_html( $ohcl_w[0] ); ?></b><?php echo esc_html( $ohcl_w[1] ); ?>
		<div style="margin-top:14px">
			<?php if ( 'guest' === $ohcl_cur['why'] ) : ?>
				<a class="btn g" href="<?php echo esc_url( wp_login_url( OHCL_Front::myhub_url() ) ); ?>">Đăng nhập</a>
			<?php endif; ?>
			<a class="btn o" href="<?php echo esc_url( OHCL_Front::url( 'join' ) ); ?>">Trở thành hội viên</a>
		</div></div></div>
	<?php
	return;
endif;

$ohcl_now  = time();
$ohcl_keys = OHCL_Members::keys_by_id();
$ohcl_key  = $ohcl_m['key_id'] && isset( $ohcl_keys[ $ohcl_m['key_id'] ] ) ? $ohcl_keys[ $ohcl_m['key_id'] ] : null;
$ohcl_ai   = OHCL_Members::ai_state( $ohcl_key, $ohcl_now );
$ohcl_ver  = OHCL_Members::verified( $ohcl_m['verify'], $ohcl_now );
$ohcl_cat  = OHCL_Front::app_catalog();
$ohcl_apps = array_values( array_intersect( $cfg['apps'], array_keys( $ohcl_cat ) ) );
$ohcl_road = OHCL_MyHub::road( $ohcl_m, $ohcl_key, $ohcl_now );
$ohcl_lv   = OHCL_Members::level( $ohcl_m, $ohcl_key, $ohcl_now );
?>
<div class="in mine">
	<nav class="side" aria-label="My Hub">
		<?php foreach ( OHCL_MyHub::TABS as $ohcl_k => $ohcl_t ) : ?>
			<a class="<?php echo $ohcl_tab === $ohcl_k ? 'on' : ''; ?>" href="<?php echo esc_url( OHCL_Front::myhub_url( $ohcl_k ) ); ?>"><?php echo OHCL_Front::icon( $ohcl_t[1] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $ohcl_t[0] ); ?></a>
		<?php endforeach; ?>
	</nav>
	<div class="body">
		<div class="pnl who"><span class="ic big"><?php echo OHCL_Front::icon( 'store' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><h1><?php echo esc_html( $ohcl_m['title'] ); ?></h1><p class="note"><?php echo esc_html( OHCL_Members::JOURNEY[ OHCL_Members::journey( $ohcl_m, $ohcl_key, $ohcl_now ) ] . ' · ' . $ohcl_m['code'] ); ?></p>
			<?php if ( $ohcl_ver ) : ?><span class="tag ok"><?php echo esc_html( $cfg['name'] ); ?> Verified</span><?php endif; ?>
			<?php foreach ( OHCL_Members::valid_certs( $ohcl_m, $ohcl_now ) as $ohcl_l ) : ?><span class="tag ok">Certified · <?php echo esc_html( $ohcl_l ); ?></span> <?php endforeach; ?></div></div>
		<?php if ( $ohcl_err ) : ?><div class="state err sm pnl"><b><?php echo esc_html( $ohcl_err['message'] ); ?></b><?php echo esc_html( $ohcl_err['hint'] ); ?><br><small>Mã: <?php echo esc_html( $ohcl_err['code'] ); ?></small></div><?php endif; ?>

		<?php if ( '' === $ohcl_tab ) : ?>
			<div class="gift0 pnl"><span class="kick">Bản quyền bộ công nghệ mã nguồn mở</span><b>0 đ</b><p><?php echo (int) count( $ohcl_apps ); ?> ứng dụng của ngành · không giới hạn người dùng</p></div>
			<?php // [2026-11-26 02:20 PM Johnny Chu - Chu Hoàng Anh] OHCL-H2 — tóm tắt quyền lợi + liên kết sang tab đầy đủ.
			$ohcl_bn  = OHCL_Benefits::for_member( $ohcl_m, $ohcl_key, OHCL_Benefits::context( $ohcl_now ) );
			$ohcl_got = OHCL_Benefits::received( $ohcl_bn );
			?>
			<div class="pnl bn-sum"><div><b><?php echo (int) $ohcl_got; ?></b> quyền lợi bạn đã nhận · <a href="<?php echo esc_url( OHCL_Front::myhub_url( 'quyen-loi' ) ); ?>">Xem 8 quyền lợi của tôi</a></div></div>
			<p class="kick">Hub mang lại cho bạn tháng này</p>
			<div class="kpis">
				<?php $ohcl_im = OHCL_Service::impact( $ohcl_m, $ohcl_now ); // [2026-11-26 04:07 PM Johnny Chu - Chu Hoàng Anh] OHCL-H3 — số đo thật; chưa đo thì "—", đã đo mà 0 thì hiện 0 ?>
				<div class="pnl kpi"><span>Lượt xem hồ sơ</span><b><?php echo $ohcl_im['measured'] ? esc_html( number_format_i18n( $ohcl_im['views'] ) ) : '—'; ?></b></div>
				<div class="pnl kpi"><span>Lượt liên hệ từ Hub</span><b><?php echo $ohcl_im['measured'] ? esc_html( number_format_i18n( $ohcl_im['contacts'] ) ) : '—'; ?></b></div>
				<div class="pnl kpi"><span>Trợ lý AI</span><b class="sm"><?php echo esc_html( $ohcl_ai['label'] ); ?></b></div>
				<div class="pnl kpi"><span>Bản đồ</span><b class="sm"><?php echo esc_html( OHCL_Members::LEVELS[ $ohcl_lv ] ); ?></b></div>
			</div>
			<p class="note"><?php echo $ohcl_im['measured'] ? 'Đếm lượt khách mở trang công khai và bấm "Nhắn Zalo" từ ' . esc_html( $ohcl_im['month'] ) . '-01; không đếm lượt của chính bạn, của quản trị viên hay của máy tự động. Hồ sơ chưa lên bản đồ thì chưa có lượt.' : 'Chưa đo: hiện "—" tới khi có lượt đầu tiên trên trang công khai của Hub.'; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<div class="g2 cert-row">
				<div class="pnl"><h2>Việc nên làm tiếp</h2>
					<?php
					$ohcl_next = array();
					foreach ( $ohcl_road['phases'] as $ohcl_ph ) {
						foreach ( $ohcl_ph['steps'] as $ohcl_s ) {
							if ( ! $ohcl_s['done'] ) {
								$ohcl_next[] = $ohcl_s['label'];
							}
						}
					}
					?>
					<?php if ( $ohcl_next ) : ?><ul class="todo"><?php foreach ( array_slice( $ohcl_next, 0, 4 ) as $ohcl_n ) : ?><li><?php echo esc_html( $ohcl_n ); ?></li><?php endforeach; ?></ul><?php else : ?><p>Bạn đã đi hết lộ trình 90 ngày.</p><?php endif; ?>
					<a class="btn o sm" href="<?php echo esc_url( OHCL_Front::myhub_url( 'lo-trinh' ) ); ?>">Xem lộ trình 90 ngày</a>
				</div>
				<div class="pnl"><h2>Coach đồng hành</h2>
					<?php if ( $ohcl_m['coach'] ) : ?><p>Coach phụ trách: <b class="mono"><?php echo esc_html( $ohcl_m['coach'] ); ?></b></p><?php else : ?><p class="note">Chưa có Coach phụ trách. Ban điều hành sẽ phân công theo loại hình của bạn.</p><?php endif; ?>
				</div>
			</div>

		<?php elseif ( 'quyen-loi' === $ohcl_tab ) : ?>
			<?php
			// [2026-11-26 02:20 PM Johnny Chu - Chu Hoàng Anh] OHCL-H2 — Quyền lợi của tôi: tám thẻ, trạng thái thật, lý do và việc tiếp theo; quyền lợi chưa mở hiện riêng.
			$ohcl_bn   = OHCL_Benefits::for_member( $ohcl_m, $ohcl_key, OHCL_Benefits::context( $ohcl_now ) );
			$ohcl_tone = array( 'locked' => 'idle', 'eligible' => 'soon', 'granted' => 'ok', 'activated' => 'ok', 'used' => 'ok', 'soon' => 'idle', 'off' => 'idle' );
			$ohcl_show = array_filter( $ohcl_bn, function ( $b ) { return 'off' !== $b['state']; } );
			?>
			<div class="pnl"><h2>Quyền lợi của tôi</h2>
				<p>Bạn đã nhận <b><?php echo (int) OHCL_Benefits::received( $ohcl_bn ); ?></b> trong <?php echo (int) count( array_filter( $ohcl_show, function ( $b ) { return 'soon' !== $b['state']; } ) ); ?> quyền lợi hiện có. Mỗi quyền lợi cho biết bạn đang ở đâu và việc tiếp theo.</p></div>
			<div class="bn-grid">
				<?php foreach ( $ohcl_show as $ohcl_b ) : $ohcl_u = OHCL_Benefits::next_url( $ohcl_b['next']['target'], $ohcl_m ); ?>
					<div class="pnl bn bn-<?php echo esc_attr( $ohcl_b['state'] ); ?>">
						<span class="ic"><?php echo OHCL_Front::icon( $ohcl_b['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<b><?php echo esc_html( $ohcl_b['label'] ); ?></b>
						<span class="tag <?php echo esc_attr( $ohcl_tone[ $ohcl_b['state'] ] ); ?>"><?php echo esc_html( $ohcl_b['state_label'] ); ?></span>
						<?php if ( 'accepted' !== $ohcl_b['availability'] ) : ?><span class="tag idle"><?php echo esc_html( OHCL_Benefits::AVAILABILITY[ $ohcl_b['availability'] ] ); ?></span><?php endif; ?>
						<p><?php echo esc_html( $ohcl_b['why'] ); ?></p>
						<?php if ( $ohcl_b['note'] ) : ?><p class="note"><?php echo esc_html( $ohcl_b['note'] ); ?></p><?php endif; ?>
						<?php if ( $ohcl_b['next']['label'] && $ohcl_u ) : ?><a class="btn o sm" href="<?php echo esc_url( $ohcl_u ); ?>"><?php echo esc_html( $ohcl_b['next']['label'] ); ?></a>
						<?php elseif ( $ohcl_b['next']['label'] ) : ?><span class="note"><?php echo esc_html( $ohcl_b['next']['label'] ); ?></span><?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="note">Quyền làm chủ của bạn áp dụng cho cả tám: dữ liệu của bạn thuộc về bạn, bạn có thể rút đồng ý công khai bất cứ lúc nào ở mục Hồ sơ doanh nghiệp.</p>

		<?php elseif ( 'du-lieu' === $ohcl_tab ) : ?>
			<?php // [2026-11-26 04:31 PM Johnny Chu - Chu Hoàng Anh] OHCL-H6 — Dữ liệu của tôi: quyền làm chủ — tải về, rút đồng ý, rời Hub (hai bước). ?>
			<?php if ( isset( $_GET['left'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?><div class="pnl state ok sm"><b>Bạn đã rời Hub</b>Hồ sơ của bạn đã rời bản đồ công khai. Cảm ơn bạn đã đồng hành.</div><?php endif; ?>
			<div class="pnl"><h2>Dữ liệu của tôi</h2>
				<p>Dữ liệu của bạn thuộc về bạn. Bạn có thể tải toàn bộ những gì Hub đang giữ về bạn, rút đồng ý công khai, hoặc rời Hub bất cứ lúc nào.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ohcl_myhub_export"><?php wp_nonce_field( 'ohcl_myhub_export', '_ohcl_myhub' ); ?>
					<button class="btn g">Tải dữ liệu của tôi (JSON)</button>
				</form>
				<p class="note">Gồm hồ sơ, đồng ý, hành trình, quyền lợi, chứng nhận, kết quả bạn khai, yêu cầu hỗ trợ của bạn, số đo tháng này, trạng thái khoá (không có khoá đầy đủ). Không gồm ghi chú nội bộ và dữ liệu hội viên khác. Mỗi lần tải đều được ghi nhật ký.</p>
			</div>
			<div class="pnl" style="margin-top:14px"><h2>Rút đồng ý công khai</h2>
				<p>Bỏ tích "Đồng ý công khai" ở mục Hồ sơ doanh nghiệp: hồ sơ rời bản đồ và danh bạ ngay. Bỏ tích ở mục Kết quả của tôi: kết quả bạn khai bị gỡ khỏi trang công khai.</p>
				<a class="btn o sm" href="<?php echo esc_url( OHCL_Front::myhub_url( 'ho-so' ) ); ?>">Mở Hồ sơ doanh nghiệp</a></div>
			<?php if ( 'left' !== $ohcl_m['status'] ) : ?>
			<form class="pnl form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:14px"><h2>Rời Hub</h2>
				<input type="hidden" name="action" value="ohcl_myhub_leave"><?php wp_nonce_field( 'ohcl_myhub_leave', '_ohcl_myhub' ); ?>
				<ul class="todo">
					<li>Hồ sơ của bạn rời bản đồ, danh bạ và trang giao thương ngay.</li>
					<li>Trợ lý AI chạy tiếp tới hết thời gian đã trả; khoá không chuyển sang Hub khác (muốn sang Hub khác thì Hub đó cấp khoá mới).</li>
					<li>Hub giữ hồ sơ của bạn cho tới khi bạn nhờ ban điều hành xoá thông tin cá nhân.</li>
				</ul>
				<label class="chk"><input type="checkbox" name="confirm" value="1"> Tôi hiểu và muốn rời Hub.</label>
				<button class="btn o" style="margin-top:10px">Rời Hub</button>
			</form>
			<?php else : ?><div class="pnl state sm" style="margin-top:14px"><b>Hồ sơ này đã rời Hub</b>Muốn xoá thông tin cá nhân, nhắn ban điều hành ở mục Hỗ trợ.</div><?php endif; ?>

		<?php elseif ( 'ket-qua' === $ohcl_tab ) : ?>
			<?php
			// [2026-11-26 04:07 PM Johnny Chu - Chu Hoàng Anh] OHCL-H3 — Kết quả của tôi: tự khai trước / sau, đồng ý công bố, Hub duyệt rồi mới lên trang công khai.
			$ohcl_ov   = OHCL_Service::outcomes_view( $ohcl_m );
			$ohcl_rows = array_pad( $ohcl_ov['items'], 3, array( 'label' => '', 'before' => '', 'after' => '', 'period' => '' ) );
			$ohcl_stw  = array( 'none' => array( 'Chưa khai', 'idle' ), 'private' => array( 'Chỉ mình bạn thấy', 'idle' ), 'pending' => array( 'Chờ Hub duyệt', 'soon' ), 'public' => array( 'Đang hiện công khai', 'ok' ) );
			?>
			<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?><div class="pnl state ok sm"><b>Đã lưu kết quả</b></div><?php endif; ?>
			<form class="pnl form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><h2>Kết quả của tôi</h2>
				<p class="note">Khai 1–3 chỉ số của chính bạn, trước và sau khi dùng công nghệ của Hub (ví dụ "Tỉ lệ giá vốn": 38% → 33%). Đây là số do <b>bạn tự khai</b>; Hub không đọc số kinh doanh của bạn.</p>
				<p><span class="tag <?php echo esc_attr( $ohcl_stw[ $ohcl_ov['status'] ][1] ); ?>"><?php echo esc_html( $ohcl_stw[ $ohcl_ov['status'] ][0] ); ?></span></p>
				<input type="hidden" name="action" value="ohcl_myhub_outcomes"><?php wp_nonce_field( 'ohcl_myhub_outcomes', '_ohcl_myhub' ); ?>
				<?php foreach ( $ohcl_rows as $ohcl_i => $ohcl_r ) : ?>
					<div class="oc-row">
						<input class="fld" name="outcomes[<?php echo (int) $ohcl_i; ?>][label]" maxlength="60" placeholder="Chỉ số (ví dụ Tỉ lệ giá vốn)" value="<?php echo esc_attr( $ohcl_r['label'] ); ?>" aria-label="Chỉ số">
						<input class="fld" name="outcomes[<?php echo (int) $ohcl_i; ?>][before]" maxlength="30" placeholder="Trước" value="<?php echo esc_attr( $ohcl_r['before'] ); ?>" aria-label="Trước">
						<input class="fld" name="outcomes[<?php echo (int) $ohcl_i; ?>][after]" maxlength="30" placeholder="Sau" value="<?php echo esc_attr( $ohcl_r['after'] ); ?>" aria-label="Sau">
						<input class="fld" name="outcomes[<?php echo (int) $ohcl_i; ?>][period]" maxlength="30" placeholder="Kỳ (ví dụ 90 ngày)" value="<?php echo esc_attr( $ohcl_r['period'] ); ?>" aria-label="Kỳ">
					</div>
				<?php endforeach; ?>
				<label class="chk"><input type="checkbox" name="outcomes_consent" value="1"<?php checked( $ohcl_ov['consent'] ); ?>> Tôi đồng ý cho Hub đưa các kết quả này lên trang công khai của tôi, ghi rõ "do cơ sở tự khai". Hub duyệt trước khi hiện; tôi bỏ tích bất cứ lúc nào để gỡ.</label>
				<button class="btn g" style="width:100%;margin-top:10px">Lưu kết quả</button>
				<p class="note">Mỗi lần sửa, kết quả về trạng thái chờ Hub duyệt lại.</p>
			</form>

		<?php elseif ( 'cong-nghe' === $ohcl_tab ) : ?>
			<div class="pnl"><h2>Trợ lý AI trên Zalo</h2><p><span class="tag <?php echo esc_attr( in_array( $ohcl_ai['code'], array( 'trial', 'active' ), true ) ? 'ok' : 'idle' ); ?>"><?php echo esc_html( $ohcl_ai['label'] ); ?></span>
				<?php echo $ohcl_m['key_prefix'] ? ' · khoá <span class="mono">' . esc_html( $ohcl_m['key_prefix'] ) . '</span>' : ''; ?><?php echo $ohcl_key && $ohcl_key['last_used_at'] ? ' · dùng gần nhất ' . esc_html( substr( $ohcl_key['last_used_at'], 0, 10 ) ) : ''; ?></p>
				<p class="note">Khoá đầy đủ chỉ hiện một lần lúc cấp và nằm trong site của bạn. Mất khoá thì nhắn ban điều hành cấp lại.</p></div>
			<h2 class="sec-h">Bộ công nghệ của tôi</h2>
			<div class="apps">
				<?php foreach ( $ohcl_apps as $ohcl_k ) : ?>
					<div class="pnl app"><span class="ic"><?php echo OHCL_Front::icon( $ohcl_cat[ $ohcl_k ][2] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><b><?php echo esc_html( $ohcl_cat[ $ohcl_k ][0] ); ?></b><p><?php echo esc_html( $ohcl_cat[ $ohcl_k ][1] ); ?></p></div>
				<?php endforeach; ?>
			</div>
			<div class="pnl"><h2>Chi phí của tôi</h2><table class="tbl"><tbody><tr><td>Bản quyền phần mềm</td><td class="n"><b>0 đ</b></td></tr><tr><td>Trợ lý AI</td><td class="n"><?php echo esc_html( $ohcl_ai['label'] ); ?></td></tr><tr><td>Đám mây</td><td class="n">theo site</td></tr></tbody></table><p class="note">Số tiền AI theo tháng hiện khi lõi Bizcity mở sổ dùng theo khoá.</p></div>

		<?php elseif ( 'ho-so' === $ohcl_tab ) : ?>
			<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?><div class="pnl state ok sm"><b>Đã lưu hồ sơ</b></div><?php endif; ?>
			<div class="g2 cert-row">
				<form class="pnl form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><h2>Thông tin công khai</h2>
					<input type="hidden" name="action" value="ohcl_myhub_profile"><?php wp_nonce_field( 'ohcl_myhub_profile', '_ohcl_myhub' ); ?>
					<label class="lbl" for="mh-a">Địa chỉ</label><input class="fld" id="mh-a" name="address" maxlength="200" value="<?php echo esc_attr( $ohcl_m['address'] ); ?>">
					<label class="lbl" for="mh-h">Giờ mở cửa</label><input class="fld" id="mh-h" name="hours" maxlength="120" value="<?php echo esc_attr( $ohcl_m['hours'] ); ?>">
					<label class="lbl" for="mh-w">Website</label><input class="fld" id="mh-w" name="site_url" maxlength="200" value="<?php echo esc_attr( $ohcl_m['site_url'] ); ?>" placeholder="https://">
					<?php // [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-SHOP — website bán hàng + đồng ý hiện sản phẩm. ?>
					<label class="lbl" for="mh-s">Website bán hàng (WooCommerce)</label><input class="fld" id="mh-s" name="shop_url" maxlength="200" value="<?php echo esc_attr( $ohcl_m['shop_url'] ); ?>" placeholder="https://">
					<label class="chk"><input type="checkbox" name="shop_consent" value="1"<?php checked( '' !== $ohcl_m['shop_consent'] ); ?>> Hiện 6 sản phẩm của cửa hàng trên trang hồ sơ (người mua bấm sang cửa hàng của tôi)</label>
					<label class="lbl" for="mh-z">Zalo của cơ sở (nút Nhắn Zalo)</label><input class="fld" id="mh-z" name="contact_zalo" maxlength="20" value="<?php echo esc_attr( $ohcl_m['contact_zalo'] ); ?>">
					<label class="chk"><input type="checkbox" name="public_consent" value="1"<?php checked( '' !== $ohcl_m['consent'] ); ?>> Đồng ý công khai hồ sơ trên bản đồ ngành</label>
					<button class="btn g" style="width:100%;margin-top:10px">Lưu hồ sơ</button>
					<p class="note">Tên, loại hình, toạ độ do ban điều hành sửa (cần xác minh lại).</p>
				</form>
				<div class="pnl"><h2>Trên bản đồ ngành</h2><p><b><?php echo esc_html( OHCL_Members::LEVELS[ $ohcl_lv ] ); ?></b></p>
					<?php if ( 4 === $ohcl_lv ) : ?><p><a class="btn o sm" href="<?php echo esc_url( OHCL_Front::place_url( $ohcl_m['id'], $ohcl_m['title'] ) ); ?>">Xem trang công khai</a></p>
					<?php else : ?><p class="note">Hồ sơ lên bản đồ khi: trợ lý AI chạy và được dùng trong 30 ngày, đã xác minh, có toạ độ, bạn đồng ý công khai và hội viên đang hoạt động.</p><?php endif; ?>
				</div>
			</div>

		<?php elseif ( 'chung-nhan' === $ohcl_tab ) : ?>
			<div class="pnl"><h2>Xác minh</h2><?php echo $ohcl_ver ? '<p><span class="tag ok">' . esc_html( $cfg['name'] ) . ' Verified</span> hạn ' . esc_html( substr( $ohcl_m['verify']['until'], 0, 10 ) ) . '</p>' : '<p class="note">Chưa xác minh. Ban điều hành xác minh khi đã tiếp nhận và kiểm giấy tờ.</p>'; ?></div>
			<h2 class="sec-h">Chứng nhận của tôi</h2>
			<?php $ohcl_certs = class_exists( 'OHCL_Certs' ) ? OHCL_Certs::for_member( $ohcl_m['id'] ) : array(); ?>
			<?php if ( $ohcl_certs ) : ?>
				<div class="apps">
					<?php foreach ( $ohcl_certs as $ohcl_c ) : ?>
						<?php $ohcl_st = OHCL_Certs::state( $ohcl_c, $ohcl_now ); ?>
						<div class="pnl app"><div class="qr sm" data-ohcl-qr="<?php echo esc_attr( OHCL_Certs::lookup_url( $ohcl_c['code'] ) ); ?>" role="img" aria-label="QR tra cứu"></div>
							<span class="tag <?php echo esc_attr( 'valid' === $ohcl_st ? 'ok' : ( 'revoked' === $ohcl_st ? 'bad' : 'soon' ) ); ?>"><?php echo esc_html( OHCL_Certs::STATES[ $ohcl_st ] ); ?></span>
							<b><?php echo esc_html( $ohcl_c['label'] ); ?></b><p class="mono"><?php echo esc_html( $ohcl_c['code'] ); ?></p><p>Hạn <?php echo esc_html( substr( $ohcl_c['until'], 0, 10 ) ); ?></p>
							<a class="note" href="<?php echo esc_url( OHCL_Certs::lookup_url( $ohcl_c['code'] ) ); ?>">Trang tra cứu →</a></div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="pnl state"><b>Chưa có chứng nhận</b>Xem các bộ tiêu chuẩn và điều kiện ở trang Xác minh &amp; chứng nhận.<?php if ( OHCL_Front::page_on( 'cert' ) ) : ?><div style="margin-top:12px"><a class="btn o sm" href="<?php echo esc_url( OHCL_Front::url( 'cert' ) ); ?>">Bộ tiêu chuẩn</a></div><?php endif; ?></div>
			<?php endif; ?>

		<?php elseif ( 'lo-trinh' === $ohcl_tab ) : ?>
			<div class="pnl"><p class="kick">Lộ trình 90 ngày</p><h2><?php echo $ohcl_road['phase'] < 4 ? 'Đang ở bước ' . esc_html( $ohcl_road['phases'][ $ohcl_road['phase'] ]['label'] ) : 'Đã hoàn thành lộ trình'; ?> · ngày <?php echo (int) min( $ohcl_road['day'], 999 ); ?>/90</h2>
				<div class="road"><?php foreach ( $ohcl_road['phases'] as $ohcl_i => $ohcl_ph ) : ?><div class="<?php echo $ohcl_ph['done'] ? 'done' : ( $ohcl_i === $ohcl_road['phase'] ? 'on' : '' ); ?>"><b><?php echo esc_html( $ohcl_ph['label'] ); ?></b><small>Ngày <?php echo (int) $ohcl_ph['from']; ?>–<?php echo (int) $ohcl_ph['to']; ?></small></div><?php endforeach; ?></div></div>
			<div class="g2 cert-row">
				<?php foreach ( $ohcl_road['phases'] as $ohcl_ph ) : ?>
					<div class="pnl"><h2><?php echo esc_html( $ohcl_ph['label'] ); ?> <span class="note"><?php echo $ohcl_ph['done'] ? 'xong' : ''; ?></span></h2>
						<ul class="tl"><?php foreach ( $ohcl_ph['steps'] as $ohcl_s ) : ?><li class="<?php echo $ohcl_s['done'] ? 'done' : ''; ?>"><i></i><span><?php echo esc_html( $ohcl_s['label'] ); ?></span><small><?php echo $ohcl_s['done'] ? '✓' : 'chưa'; ?></small></li><?php endforeach; ?></ul></div>
				<?php endforeach; ?>
			</div>
			<p class="note">Mỗi bước tính từ dữ liệu thật của hồ sơ. Mục tiêu là cải thiện kinh doanh, không phải dùng hết mọi phần mềm.</p>

		<?php elseif ( 'ho-tro' === $ohcl_tab ) : ?>
			<?php if ( isset( $_GET['sent'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?><div class="pnl state ok sm"><b>Đã gửi yêu cầu</b>Ban điều hành sẽ trả lời qua Zalo hoặc email của bạn.</div><?php endif; ?>
			<form class="pnl form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><h2>Gửi yêu cầu hỗ trợ</h2>
				<input type="hidden" name="action" value="ohcl_myhub_support"><?php wp_nonce_field( 'ohcl_myhub_support', '_ohcl_myhub' ); ?>
				<textarea class="fld" name="text" rows="5" maxlength="1000" required placeholder="Bạn cần giúp việc gì? Góp ý, đề xuất tính năng cũng gửi ở đây."></textarea>
				<button class="btn g">Gửi</button><p class="note">Yêu cầu nằm trong hồ sơ hội viên của bạn; chỉ ban điều hành đọc.</p>
			</form>
		<?php endif; ?>
	</div>
</div>
