<?php
/**
 * Hub › 02 · Hội viên (mockup owner-center O4 + O5).
 *
 *   list        stage chips (counts), filters (stage, segment, search), table of independent states
 *   ?view=new   add a member · ?id=N&view=edit edit (core fields + industry fields of contract @2)
 *   ?id=N       Member 360°: header, independent states, journey, actions (accept, issue key from the
 *               profile, membership status), industry tabs from the slot ohcl_member360_tabs
 *
 * Keys come only through OHCL_Core_Client; the plain key is shown once and never stored (only
 * key_id + key_prefix). Writes: admin-post + nonce + manage_options. Errors: four fields.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 06:49 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1C — màn 02 Hội viên + Member 360°: chặng hành trình, lọc, thêm / sửa, tiếp nhận, cấp khoá từ hồ sơ, ô mở rộng ngành.

defined( 'ABSPATH' ) || exit;

final class OHCL_Admin_Members {

	const PAGE = 'open-hub-members';

	public static function boot() {
		foreach ( array( 'save', 'accept', 'key', 'status', 'verify', 'unverify' ) as $a ) {
			add_action( 'admin_post_ohcl_member_' . $a, array( __CLASS__, 'handle_' . $a ) );
		}
	}

	private static function u( array $args = array() ) {
		return OHCL_Admin::url( self::PAGE, $args );
	}

	private static function seg_label( $slug ) {
		$c = OHCL_Industry::current();
		return isset( $c['segments'][ $slug ] ) ? $c['segments'][ $slug ] : ( $slug ? $slug : '—' );
	}

	private static function form_open( $action, $nonce ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( $nonce );
		echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '">';
	}

	public static function page() {
		OHCL_Admin::guard();
		$id   = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ chọn màn.
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'new' === $view || ( $id && 'edit' === $view ) ) {
			self::page_form( $id );
		} elseif ( $id ) {
			self::page_one( $id );
		} else {
			self::page_list();
		}
	}

	/* ---------- O4 danh sách ---------- */

	private static function page_list() {
		$cfg   = OHCL_Industry::current();
		$term  = OHCL_Industry::term( 'member' );
		OHCL_Admin::head( '02 · Quản lý hội viên', ucfirst( $term ), '<a class="button button-primary" href="' . esc_url( self::u( array( 'view' => 'new' ) ) ) . '">+ Thêm ' . esc_html( $term ) . '</a>' );
		$now   = time();
		$all   = OHCL_Members::all();
		$keys  = OHCL_Members::keys_by_id();
		$cnt   = OHCL_Members::counts( $all, $keys, $now );
		$stage = isset( $_GET['stage'] ) ? (int) $_GET['stage'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$seg   = isset( $_GET['seg'] ) ? sanitize_key( wp_unslash( $_GET['seg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$q     = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		echo '<p class="ohcl-kick">Hành trình phát triển</p><div class="ohcl-stages">';
		echo '<a class="' . ( 0 === $stage ? 'on' : '' ) . '" href="' . esc_url( self::u() ) . '"><b>' . (int) $cnt['total'] . '</b><span>Tất cả</span></a>';
		foreach ( OHCL_Members::JOURNEY as $lv => $label ) {
			echo '<a class="' . ( $stage === $lv ? 'on' : '' ) . '" href="' . esc_url( self::u( array( 'stage' => $lv ) ) ) . '"><b>' . (int) $cnt['by_stage'][ $lv ] . '</b><span>' . (int) $lv . ' · ' . esc_html( $label ) . '</span></a>';
		}
		echo '</div>';

		echo '<form method="get" class="ohcl-filter"><input type="hidden" name="page" value="' . esc_attr( self::PAGE ) . '">' . ( $stage ? '<input type="hidden" name="stage" value="' . (int) $stage . '">' : '' )
			. '<select name="seg"><option value="">Mọi phân khúc</option>';
		foreach ( $cfg['segments'] as $s => $l ) {
			echo '<option value="' . esc_attr( $s ) . '"' . selected( $seg, $s, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select> <input type="search" name="q" value="' . esc_attr( $q ) . '" placeholder="Tìm tên, mã HV, tỉnh…"> <button class="button">Lọc</button></form>';

		$rows = array();
		foreach ( $all as $m ) {
			$k  = $m['key_id'] && isset( $keys[ $m['key_id'] ] ) ? $keys[ $m['key_id'] ] : null;
			$lv = OHCL_Members::journey( $m, $k, $now );
			if ( ( $stage && $lv !== $stage ) || ( $seg && $m['segment'] !== $seg ) ) {
				continue;
			}
			if ( '' !== $q && false === stripos( $m['title'] . ' ' . $m['code'] . ' ' . $m['province'], $q ) ) {
				continue;
			}
			$rows[] = array( $m, $k, $lv );
		}

		if ( ! $all ) {
			echo '<div class="ohcl-card"><b>Chưa có ' . esc_html( $term ) . ' nào.</b> <span class="mute">Thêm hồ sơ đầu tiên, rồi cấp khoá ngay trong hồ sơ.</span></div>';
		} elseif ( ! $rows ) {
			echo '<div class="ohcl-card"><b>Không có hồ sơ khớp bộ lọc.</b> <a href="' . esc_url( self::u() ) . '">Bỏ lọc</a></div>';
		} else {
			echo '<div class="ohcl-tw"><table class="ohcl-t"><thead><tr><th>' . esc_html( ucfirst( $term ) ) . '</th><th>Hành trình</th><th>Trợ lý AI</th><th>Xác minh</th><th>Hội viên</th><th>Coach</th><th></th></tr></thead><tbody>';
			foreach ( $rows as $r ) {
				list( $m, $k, $lv ) = $r;
				$ai = OHCL_Members::ai_state( $k, $now );
				echo '<tr><td><b>' . esc_html( $m['title'] ) . '</b><br><span class="mute">' . esc_html( $m['code'] . ' · ' . self::seg_label( $m['segment'] ) . ( $m['province'] ? ' · ' . $m['province'] : '' ) ) . '</span></td>'
					. '<td>' . esc_html( OHCL_Members::JOURNEY[ $lv ] ) . '</td>'
					. '<td><span class="ohcl-st ' . esc_attr( $ai['code'] ) . '">' . esc_html( $ai['label'] ) . '</span></td>'
					. '<td>' . ( OHCL_Members::verified( $m['verify'], $now ) ? '<span class="ohcl-st ok">Verified</span>' : '<span class="mute">Chưa</span>' ) . '</td>'
					. '<td>' . esc_html( OHCL_Members::STATUS[ $m['status'] ] ) . '</td>'
					. '<td>' . ( $m['coach'] ? esc_html( $m['coach'] ) : '<span class="mute">Chưa có</span>' ) . '</td>'
					. '<td><a class="button" href="' . esc_url( self::u( array( 'id' => $m['id'] ) ) ) . '">Mở</a></td></tr>';
			}
			echo '</tbody></table></div>';
		}
		echo '<p class="mute">Mỗi cột là một trạng thái độc lập. Trợ lý AI đọc từ khoá ở lõi Bizcity; hành trình tính từ các trạng thái, không gõ tay.' . ( count( $all ) >= 1000 ? ' Mới đọc 1 000 hồ sơ mới nhất.' : '' ) . '</p></div>';
	}

	/* ---------- thêm / sửa ---------- */

	private static function page_form( $id ) {
		$cfg  = OHCL_Industry::current();
		$m    = $id ? OHCL_Members::get( $id ) : null;
		$term = OHCL_Industry::term( 'business' );
		if ( $id && ! $m ) {
			OHCL_Admin::head( '02 · Quản lý hội viên', 'Không tìm thấy hồ sơ' );
			echo '<p><a href="' . esc_url( self::u() ) . '">← Danh sách</a></p></div>';
			return;
		}
		OHCL_Admin::head( '02 · Quản lý hội viên', $m ? 'Sửa hồ sơ ' . $m['title'] : 'Thêm ' . OHCL_Industry::term( 'member' ) );
		$v = function ( $k ) use ( $m ) {
			return $m && isset( $m[ $k ] ) ? (string) $m[ $k ] : '';
		};
		echo '<div class="ohcl-card ohcl-form">';
		self::form_open( 'ohcl_member_save', 'ohcl_member_save' );
		echo '<input type="hidden" name="id" value="' . (int) $id . '">'
			. '<p><label>Tên ' . esc_html( $term ) . ' <span class="req">*</span><br><input name="title" class="regular-text" required maxlength="120" value="' . esc_attr( $v( 'title' ) ) . '"></label></p>'
			. '<p><label>Phân khúc<br><select name="segment"><option value="">—</option>';
		foreach ( $cfg['segments'] as $s => $l ) {
			echo '<option value="' . esc_attr( $s ) . '"' . selected( $v( 'segment' ), $s, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></label></p>';
		foreach ( OHCL_Members::CORE_FIELDS as $k => $def ) {
			if ( 'segment' === $k ) {
				continue;
			}
			$type = 'email' === $k ? 'email' : ( in_array( $k, array( 'site_url', 'shop_url' ), true ) ? 'url' : 'text' ); // [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-SHOP
			echo '<p><label>' . esc_html( $def[0] ) . '<br><input name="' . esc_attr( $k ) . '" type="' . esc_attr( $type ) . '" class="regular-text" maxlength="' . (int) $def[1] . '" value="' . esc_attr( $v( $k ) ) . '"></label></p>';
		}
		// [2026-11-25 09:06 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — đồng ý hiện trên bản đồ / danh bạ (bắt buộc cho V4).
		echo '<p><label class="ohcl-chk"><input type="checkbox" name="public_consent" value="1"' . checked( $m && '' !== $m['consent'], true, false ) . '> Cơ sở đã đồng ý hiện trên bản đồ và danh bạ công khai</label></p>';
		// [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-SHOP — đồng ý hiện sản phẩm từ website bán hàng (Store API công khai) trên trang hồ sơ.
		echo '<p><label class="ohcl-chk"><input type="checkbox" name="shop_consent" value="1"' . checked( $m && '' !== $m['shop_consent'], true, false ) . '> Cơ sở đồng ý hiện sản phẩm từ website bán hàng trên trang hồ sơ</label></p>';
		if ( $cfg['member_fields'] ) {
			echo '<h3>Thông tin ngành (' . esc_html( $cfg['name'] ) . ')</h3>';
		}
		foreach ( $cfg['member_fields'] as $f ) {
			$cur = $m && isset( $m['industry'][ $f['key'] ] ) ? $m['industry'][ $f['key'] ] : '';
			$nm  = 'f_' . $f['key'];
			$req = ! empty( $f['required'] ) ? ' <span class="req">*</span>' : '';
			echo '<div class="ohcl-field"><label>' . esc_html( $f['label'] ) . $req . ( $f['public'] ? ' <span class="mute">(công khai khi hội viên đồng ý)</span>' : '' ) . '</label><br>';
			if ( 'multi' === $f['type'] ) {
				$cur = is_array( $cur ) ? $cur : array();
				foreach ( $f['options'] as $ok => $ol ) {
					echo '<label class="ohcl-chk"><input type="checkbox" name="' . esc_attr( $nm ) . '[]" value="' . esc_attr( $ok ) . '"' . checked( in_array( $ok, $cur, true ), true, false ) . '> ' . esc_html( $ol ) . '</label> ';
				}
			} elseif ( 'select' === $f['type'] ) {
				echo '<select name="' . esc_attr( $nm ) . '"><option value="">—</option>';
				foreach ( $f['options'] as $ok => $ol ) {
					echo '<option value="' . esc_attr( $ok ) . '"' . selected( (string) $cur, $ok, false ) . '>' . esc_html( $ol ) . '</option>';
				}
				echo '</select>';
			} elseif ( 'bool' === $f['type'] ) {
				echo '<label class="ohcl-chk"><input type="checkbox" name="' . esc_attr( $nm ) . '" value="1"' . checked( (bool) $cur, true, false ) . '> Có</label>';
			} else {
				$t = 'number' === $f['type'] ? 'number' : ( 'url' === $f['type'] ? 'url' : 'text' );
				echo '<input name="' . esc_attr( $nm ) . '" type="' . esc_attr( $t ) . '" class="regular-text" value="' . esc_attr( is_scalar( $cur ) ? (string) $cur : '' ) . '">';
			}
			echo '</div>';
		}
		echo '<p class="ohcl-bar"><button class="button button-primary">' . ( $m ? 'Lưu hồ sơ' : 'Thêm hồ sơ' ) . '</button> <a class="button" href="' . esc_url( $m ? self::u( array( 'id' => $m['id'] ) ) : self::u() ) . '">Huỷ</a></p></form></div></div>';
	}

	/* ---------- O5 Member 360° ---------- */

	private static function page_one( $id ) {
		$m = OHCL_Members::get( $id );
		if ( ! $m ) {
			OHCL_Admin::head( '02 · Quản lý hội viên', 'Không tìm thấy hồ sơ' );
			echo '<p><a href="' . esc_url( self::u() ) . '">← Danh sách</a></p></div>';
			return;
		}
		$now  = time();
		$keys = OHCL_Members::keys_by_id();
		$k    = $m['key_id'] && isset( $keys[ $m['key_id'] ] ) ? $keys[ $m['key_id'] ] : null;
		$lv   = OHCL_Members::journey( $m, $k, $now );
		$ai   = OHCL_Members::ai_state( $k, $now );
		$ver  = OHCL_Members::verified( $m['verify'], $now );
		// [2026-11-25 09:06 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — bậc bản đồ + lý do còn thiếu (chỉ V4 lên công khai).
		$map_lv  = OHCL_Members::level( $m, $k, $now );
		$missing = array();
		if ( $map_lv < 2 ) {
			$missing[] = 'khoá chạy và được dùng trong 30 ngày';
		}
		if ( ! $ver ) {
			$missing[] = 'leader xác minh';
		}
		if ( '' === $m['lat'] || '' === $m['lng'] ) {
			$missing[] = 'toạ độ';
		}
		if ( '' === $m['consent'] ) {
			$missing[] = 'cơ sở đồng ý công khai';
		}
		if ( 'active' !== $m['status'] ) {
			$missing[] = 'hội viên đang hoạt động';
		}
		$map_why = $missing ? 'còn thiếu: ' . implode( ', ', $missing ) : '';

		OHCL_Admin::head(
			'02 · Quản lý hội viên › Member 360°',
			$m['title'],
			'<a class="button" href="' . esc_url( self::u() ) . '">← Danh sách</a> <a class="button button-primary" href="' . esc_url( self::u( array( 'id' => $m['id'], 'view' => 'edit' ) ) ) . '">Sửa hồ sơ</a>'
		);
		echo '<p class="mute">' . esc_html( self::seg_label( $m['segment'] ) . ( $m['province'] ? ' · ' . $m['province'] : '' ) . ' · ' . $m['code'] . ( $m['key_prefix'] ? ' · khoá ' . $m['key_prefix'] : '' ) ) . '</p>';

		$tabs = OHCL_Slots::items( 'member360_tabs', $m );
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<nav class="ohcl-tabs"><a class="' . ( '' === $tab ? 'on' : '' ) . '" href="' . esc_url( self::u( array( 'id' => $m['id'] ) ) ) . '">Thông tin chung</a>';
		foreach ( $tabs as $t ) {
			echo '<a class="' . ( $tab === $t['id'] ? 'on' : '' ) . '" href="' . esc_url( self::u( array( 'id' => $m['id'], 'tab' => $t['id'] ) ) ) . '">' . esc_html( $t['label'] ) . '</a>';
		}
		echo '</nav>';

		if ( '' !== $tab ) {
			foreach ( $tabs as $t ) {
				if ( $tab === $t['id'] ) {
					echo '<div class="ohcl-card">' . wp_kses_post( OHCL_Slots::render( 'member360_tabs', $t, $m ) ) . '</div>';
				}
			}
			echo '</div>';
			return;
		}

		echo '<div class="ohcl-split"><div>';
		echo '<div class="ohcl-card"><h3>Trạng thái độc lập</h3><dl class="ohcl-dl">'
			. '<dt>Hành trình</dt><dd>' . esc_html( OHCL_Members::JOURNEY[ $lv ] ) . ' (' . (int) $lv . '/8)</dd>'
			. '<dt>Hội viên</dt><dd>' . esc_html( OHCL_Members::STATUS[ $m['status'] ] ) . ( $m['joined_at'] ? ' · vào ' . esc_html( substr( $m['joined_at'], 0, 10 ) ) : '' ) . '</dd>'
			. '<dt>Trợ lý AI</dt><dd><span class="ohcl-st ' . esc_attr( $ai['code'] ) . '">' . esc_html( $ai['label'] ) . '</span>' . ( $k && $k['last_used_at'] ? ' · dùng gần nhất ' . esc_html( $k['last_used_at'] ) : '' ) . '</dd>'
			. '<dt>Xác minh</dt><dd>' . ( $ver ? 'Verified · hạn ' . esc_html( substr( $m['verify']['until'], 0, 10 ) ) : 'Chưa' ) . '</dd>'
			. '<dt>Chứng nhận</dt><dd>' . esc_html( implode( ', ', OHCL_Members::valid_certs( $m, $now ) ) ?: '—' ) . '</dd>' // [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A
			. '<dt>Bản đồ</dt><dd>' . esc_html( OHCL_Members::LEVELS[ $map_lv ] ) . ( 4 === $map_lv ? ' · <a href="' . esc_url( OHCL_Front::place_url( $m['id'], $m['title'] ) ) . '" target="_blank" rel="noopener">xem trang công khai</a>' : ' <span class="mute">— ' . esc_html( $map_why ) . '</span>' ) . '</dd>'
			. '<dt>Coach</dt><dd>' . ( $m['coach'] ? esc_html( $m['coach'] ) : '<span class="mute">Chưa có</span>' ) . '</dd>'
			. '<dt>Liên hệ</dt><dd>' . esc_html( implode( ' · ', array_filter( array( $m['phone'], $m['email'], $m['contact_zalo'] ) ) ) ?: '—' ) . '</dd>'
			. '<dt>Website</dt><dd>' . ( $m['site_url'] ? esc_html( $m['site_url'] ) : '—' ) . '</dd>'
			. '<dt>Tài khoản My Hub</dt><dd>' . esc_html( self::user_label( $m['user'] ) ) . '</dd></dl></div>'; // [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2

		// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — yêu cầu hỗ trợ hội viên gửi từ My Hub.
		$sup = get_post_meta( $m['id'], '_bzm_support', true );
		if ( is_array( $sup ) && $sup ) {
			echo '<div class="ohcl-card"><h3>Yêu cầu hỗ trợ từ My Hub</h3><ul class="ohcl-reports">';
			foreach ( array_reverse( $sup ) as $x ) {
				echo '<li><span class="mute">' . esc_html( substr( $x['at'], 0, 16 ) ) . '</span><br>' . esc_html( $x['text'] ) . '</li>';
			}
			echo '</ul></div>';
		}

		$cfg = OHCL_Industry::current();
		if ( $cfg['member_fields'] ) {
			echo '<div class="ohcl-card"><h3>Thông tin ngành</h3><dl class="ohcl-dl">';
			foreach ( $cfg['member_fields'] as $f ) {
				$val = isset( $m['industry'][ $f['key'] ] ) ? $m['industry'][ $f['key'] ] : '';
				if ( is_array( $val ) ) {
					$val = implode( ', ', array_map( function ( $x ) use ( $f ) { return isset( $f['options'][ $x ] ) ? $f['options'][ $x ] : $x; }, $val ) );
				} elseif ( 'select' === $f['type'] && isset( $f['options'][ $val ] ) ) {
					$val = $f['options'][ $val ];
				} elseif ( 'bool' === $f['type'] ) {
					$val = $val ? 'Có' : 'Không';
				}
				echo '<dt>' . esc_html( $f['label'] ) . '</dt><dd>' . ( '' !== (string) $val ? esc_html( (string) $val ) : '—' ) . '</dd>';
			}
			echo '</dl></div>';
		}
		echo '</div><div>';

		// Hành động
		echo '<div class="ohcl-card"><h3>Việc tiếp theo</h3>';
		if ( ! $m['accepted_at'] ) {
			self::form_open( 'ohcl_member_accept', 'ohcl_member_accept_' . $m['id'] );
			echo '<input type="hidden" name="id" value="' . (int) $m['id'] . '"><p><button class="button button-primary">Tiếp nhận hội viên</button> <span class="mute">Chuyển sang "Đã tiếp nhận".</span></p></form>';
		}
		$live = $k && in_array( $k['status'], array( 'trial', 'active', 'grace' ), true );
		if ( ! $live ) {
			self::form_open( 'ohcl_member_key', 'ohcl_member_key_' . $m['id'] );
			echo '<input type="hidden" name="id" value="' . (int) $m['id'] . '"><p><button class="button button-primary">Cấp khoá dùng thử</button> <span class="mute">' . (int) $cfg['trial_days'] . ' ngày · khoá hiện một lần · gắn vào hồ sơ này.</span></p></form>';
		} else {
			echo '<p class="mute">Khoá ' . esc_html( $m['key_prefix'] ) . ' đang chạy. Thu hồi ở <a href="' . esc_url( OHCL_Admin::url( 'open-hub-keys' ) ) . '">03 · Khoá dùng thử</a>.</p>';
		}
		self::form_open( 'ohcl_member_status', 'ohcl_member_status_' . $m['id'] );
		echo '<input type="hidden" name="id" value="' . (int) $m['id'] . '"><p><label>Trạng thái hội viên<br><select name="status">';
		foreach ( OHCL_Members::STATUS as $s => $l ) {
			echo '<option value="' . esc_attr( $s ) . '"' . selected( $m['status'], $s, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></label> <button class="button">Lưu</button></p></form></div>';

		// [2026-11-25 09:06 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — xác minh V3 của leader (MAP-Q3: quản trị viên Hub, không vai riêng).
		echo '<div class="ohcl-card"><h3>Xác minh (V3)</h3>';
		if ( $ver ) {
			$ev = array_map( function ( $x ) { return isset( OHCL_Members::EVIDENCE[ $x ] ) ? OHCL_Members::EVIDENCE[ $x ] : $x; }, (array) $m['verify']['evidence'] );
			echo '<p><span class="ohcl-st ok">ĐÃ XÁC MINH</span> hạn ' . esc_html( substr( $m['verify']['until'], 0, 10 ) ) . '</p><p class="mute">Bằng chứng: ' . esc_html( implode( ', ', $ev ) ) . ( $m['verify']['note'] ? ' · ' . esc_html( $m['verify']['note'] ) : '' ) . '</p>';
			self::form_open( 'ohcl_member_unverify', 'ohcl_member_unverify_' . $m['id'] );
			echo '<input type="hidden" name="id" value="' . (int) $m['id'] . '"><p><button class="button">Bỏ xác minh</button> <span class="mute">Hồ sơ rời bản đồ công khai ngay.</span></p></form>';
		} else {
			self::form_open( 'ohcl_member_verify', 'ohcl_member_verify_' . $m['id'] );
			echo '<input type="hidden" name="id" value="' . (int) $m['id'] . '"><p class="mute">Đánh dấu bằng chứng đã kiểm:</p>';
			foreach ( OHCL_Members::EVIDENCE as $ek => $el ) {
				echo '<label class="ohcl-chk"><input type="checkbox" name="evidence[]" value="' . esc_attr( $ek ) . '"> ' . esc_html( $el ) . '</label> ';
			}
			echo '<p><label>Hiệu lực (tháng)<br><input type="number" name="months" min="1" max="36" value="12" style="width:80px"></label></p>'
				. '<p><label>Ghi chú<br><input name="note" class="regular-text" maxlength="300"></label></p>'
				. '<p><button class="button button-primary">Xác minh</button></p></form>';
		}
		echo '</div>';

		if ( class_exists( 'OHCL_Admin_Certs' ) && OHCL_Admin_Certs::on() ) {
			OHCL_Admin_Certs::member_card( $m ); // [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — cấp / gia hạn chứng nhận ngay trong Member 360°
		}

		echo '<div class="ohcl-card"><h3>Hành trình</h3><ol class="ohcl-journey">';
		foreach ( OHCL_Members::JOURNEY as $i => $l ) {
			echo '<li class="' . ( $i <= $lv ? 'done' : '' ) . ( $i === $lv ? ' now' : '' ) . '">' . esc_html( $l ) . '</li>';
		}
		echo '</ol><p class="mute">Bậc 6 khi đã xác minh và có chứng nhận còn hiệu lực. Bậc 7–8 mở với module Marketing (Đ3).</p></div>';
		echo '</div></div></div>';
	}

	/** Linked WordPress account, or how it will link. */
	private static function user_label( $uid ) {
		if ( ! $uid ) {
			return 'Chưa gắn — tự gắn khi người dùng có email trùng hồ sơ đăng nhập My Hub';
		}
		$u = function_exists( 'get_userdata' ) ? get_userdata( (int) $uid ) : null;
		return $u ? $u->display_name . ' (#' . (int) $uid . ')' : '#' . (int) $uid;
	}

	/* ---------- handlers ---------- */

	private static function back( $args ) {
		wp_safe_redirect( self::u( $args ) );
		exit;
	}

	public static function handle_save() {
		OHCL_Admin::guard();
		check_admin_referer( 'ohcl_member_save' );
		$id    = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$clean = OHCL_Members::clean( wp_unslash( $_POST ), OHCL_Industry::current() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- clean() bỏ thẻ, cắt độ dài, kiểm từng trường theo hợp đồng.
		if ( is_wp_error( $clean ) ) {
			OHCL_Admin::flash_set( OHCL_Admin::error_flash( $clean ) );
			self::back( $id ? array( 'id' => $id, 'view' => 'edit' ) : array( 'view' => 'new' ) );
		}
		$res = OHCL_Members::save( $clean, $id );
		if ( is_wp_error( $res ) ) {
			OHCL_Admin::flash_set( OHCL_Admin::error_flash( $res ) );
			self::back( array( 'view' => 'new' ) );
		}
		OHCL_Admin::flash_set( array( 'ok' => $id ? 'Đã lưu hồ sơ.' : 'Đã thêm hồ sơ. Bước tiếp: tiếp nhận và cấp khoá dùng thử.' ) );
		self::back( array( 'id' => (int) $res ) );
	}

	public static function handle_accept() {
		OHCL_Admin::guard();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		check_admin_referer( 'ohcl_member_accept_' . $id );
		if ( OHCL_Members::get( $id ) ) {
			OHCL_Members::accept( $id );
			OHCL_Slots::emit( 'member_stage_changed', array( 'member_id' => $id, 'to' => 2, 'label' => OHCL_Members::JOURNEY[2] ) );
			OHCL_Admin::flash_set( array( 'ok' => 'Đã tiếp nhận hội viên.' ) );
		}
		self::back( array( 'id' => $id ) );
	}

	public static function handle_key() {
		OHCL_Admin::guard();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		check_admin_referer( 'ohcl_member_key_' . $id );
		$m = OHCL_Members::get( $id );
		if ( ! $m ) {
			self::back( array() );
		}
		$keys = OHCL_Members::keys_by_id();
		$cur  = $m['key_id'] && isset( $keys[ $m['key_id'] ] ) ? $keys[ $m['key_id'] ] : null;
		if ( $cur && in_array( $cur['status'], array( 'trial', 'active', 'grace' ), true ) ) {
			OHCL_Admin::flash_set( OHCL_Admin::error_flash( OHCL_Error::make( 'ohcl_member_has_key', 'Hồ sơ này đã có khoá đang chạy.', 'Thu hồi khoá cũ ở 03 · Khoá dùng thử nếu cần cấp lại.', 409 ) ) );
			self::back( array( 'id' => $id ) );
		}
		$res = OHCL_Core_Client::issue_key( $m['title'], $m['site_url'], $m['coach'] );
		if ( is_wp_error( $res ) ) {
			OHCL_Admin::flash_set( OHCL_Admin::error_flash( $res ) );
			self::back( array( 'id' => $id ) );
		}
		OHCL_Members::link_key( $id, (int) $res['id'], (string) $res['key_prefix'] );
		OHCL_Overview::flush();
		OHCL_Slots::emit( 'key_issued', array( 'key_id' => (int) $res['id'], 'key_prefix' => (string) $res['key_prefix'], 'member_id' => $id, 'label' => $m['title'], 'coach_ref' => $m['coach'] ) );
		OHCL_Admin::flash_set( array( 'key' => (string) $res['key'], 'label' => $m['title'], 'plan_code' => (string) $res['plan_code'], 'plan_until' => (string) $res['plan_until'] ) );
		self::back( array( 'id' => $id ) );
	}

	public static function handle_verify() {
		// [2026-11-25 09:06 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2
		OHCL_Admin::guard();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		check_admin_referer( 'ohcl_member_verify_' . $id );
		$ev  = isset( $_POST['evidence'] ) && is_array( $_POST['evidence'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['evidence'] ) ) : array();
		$res = OHCL_Members::get( $id ) ? OHCL_Members::verify( $id, $ev, isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '', isset( $_POST['months'] ) ? (int) $_POST['months'] : 12 ) : OHCL_Error::make( 'ohcl_member_not_found', 'Không tìm thấy hồ sơ.', 'Mở lại từ danh sách hội viên.', 404 );
		if ( is_wp_error( $res ) ) {
			OHCL_Admin::flash_set( OHCL_Admin::error_flash( $res ) );
		} else {
			OHCL_Slots::emit( 'member_verified', array( 'member_id' => $id ) );
			OHCL_Admin::flash_set( array( 'ok' => 'Đã xác minh hồ sơ.' ) );
		}
		self::back( array( 'id' => $id ) );
	}

	public static function handle_unverify() {
		OHCL_Admin::guard();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		check_admin_referer( 'ohcl_member_unverify_' . $id );
		OHCL_Members::unverify( $id );
		OHCL_Admin::flash_set( array( 'ok' => 'Đã bỏ xác minh; hồ sơ không còn trên bản đồ công khai.' ) );
		self::back( array( 'id' => $id ) );
	}

	public static function handle_status() {
		OHCL_Admin::guard();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		check_admin_referer( 'ohcl_member_status_' . $id );
		$res = OHCL_Members::set_status( $id, isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '' );
		OHCL_Admin::flash_set( is_wp_error( $res ) ? OHCL_Admin::error_flash( $res ) : array( 'ok' => 'Đã lưu trạng thái hội viên.' ) );
		self::back( array( 'id' => $id ) );
	}
}
