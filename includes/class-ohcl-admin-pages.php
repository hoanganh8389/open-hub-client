<?php
/**
 * Hub › Trang & menu: the WordPress pages of the Hub (one per Hub page, template "Open Hub — …") and the
 * WordPress menu "Menu Hub". First wp-admin visit of an owner after install / update runs it once
 * automatically: creates the missing pages (published) and builds the menu; a theme menu location is
 * filled only when it is empty (never replaces the owner's menu). Afterwards the owner re-runs it here.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:53 PM Johnny Chu - Chu Hoàng Anh] OHCL-F3 — màn Trang & menu: tự tạo trang còn thiếu + dựng "Menu Hub" một lần, nút chạy lại, gán vị trí menu của theme.

defined( 'ABSPATH' ) || exit;

final class OHCL_Admin_Pages {

	const PAGE        = 'open-hub-pages';
	const SEED_OPTION = 'ohcl_pages_seeded';
	const SEED_VER    = '1';

	public static function boot() {
		add_action( 'admin_init', array( __CLASS__, 'seed_once' ) );
		add_action( 'admin_post_ohcl_pages_create', array( __CLASS__, 'handle_create' ) );
		add_action( 'admin_post_ohcl_pages_menu', array( __CLASS__, 'handle_menu' ) );
	}

	/** First theme location without a menu, or '' (we never replace the owner's menu automatically). */
	public static function free_location() {
		$locs = get_nav_menu_locations();
		foreach ( array_keys( (array) get_registered_nav_menus() ) as $l ) {
			if ( empty( $locs[ $l ] ) ) {
				return $l;
			}
		}
		return '';
	}

	/** Once per SEED_VER, for an owner in wp-admin: create missing pages + build the menu. */
	public static function seed_once() {
		if ( wp_doing_ajax() || ! current_user_can( OHCL_Admin::CAP ) || self::SEED_VER === get_option( self::SEED_OPTION ) ) {
			return;
		}
		update_option( self::SEED_OPTION, self::SEED_VER, false ); // ghi trước: lỗi giữa chừng không lặp mỗi lần tải trang
		$made = OHCL_Pages::create_missing( 'publish' );
		$menu = OHCL_Pages::sync_menu( self::free_location() );
		OHCL_Admin::flash_set( array( 'ok' => 'Open Hub đã tạo ' . count( $made ) . ' trang và ' . ( is_wp_error( $menu ) ? 'chưa tạo được menu' : 'menu "' . OHCL_Pages::MENU_NAME . '" (' . (int) $menu['added'] . ' mục' . ( $menu['location'] ? ', gán vị trí ' . $menu['location'] : '' ) . ')' ) . '. Xem ở Hub › Trang & menu.' ) );
	}

	public static function page() {
		OHCL_Admin::guard();
		$have = OHCL_Pages::find_all( array( 'publish', 'draft', 'pending', 'private', 'future' ) );
		OHCL_Admin::head( 'Hub › Trang & menu', 'Trang WordPress và menu của Hub', '<a class="button" href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">Giao diện › Menu</a>' );
		echo '<p class="mute">Mỗi trang Hub là một <b>Mẫu trang</b> "Open Hub — …" trong WordPress. Trang dùng mẫu được Hub dựng (ô soạn thảo bỏ qua); đổi tên, đường dẫn, trang cha như trang thường. Khi trang đã đăng, mọi link của Hub trỏ về trang đó.</p>';
		echo '<div class="ohcl-card"><table class="widefat striped"><thead><tr><th>Trang Hub</th><th>Đường dẫn gợi ý</th><th>Mẫu trang</th><th>Trang WordPress</th></tr></thead><tbody>';
		$missing = 0;
		foreach ( OHCL_Pages::creatable() as $k => $pg ) {
			$on = OHCL_Front::page_on( $k );
			if ( ! empty( $have[ $k ] ) ) {
				$p  = $have[ $k ][0];
				$st = '<a href="' . esc_url( get_edit_post_link( $p->ID ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a> — ' . ( 'publish' === $p->post_status ? '<span class="ohcl-st ok">đã đăng</span> <a href="' . esc_url( get_permalink( $p ) ) . '" target="_blank" rel="noopener">xem</a>' : '<span class="ohcl-st grace">' . esc_html( $p->post_status ) . '</span>' );
			} elseif ( ! $on ) {
				$st = '<span class="mute">module ' . esc_html( $pg['module'] ) . ' đang tắt</span>';
			} else {
				$st = '<span class="ohcl-st revoked">chưa có</span>';
				$missing++;
			}
			echo '<tr><td><b>' . esc_html( $pg['title'] ) . '</b>' . ( $pg['group'] ? '<br><span class="mute">' . esc_html( $pg['group'] ) . '</span>' : '' ) . '</td><td><code>/' . esc_html( $pg['slug'] ) . '/</code></td><td><code>Open Hub — ' . esc_html( $pg['title'] ) . '</code></td><td>' . $st . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $st đã escape từng phần.
		}
		echo '</tbody></table>';
		if ( $missing ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:12px">';
			wp_nonce_field( 'ohcl_pages_create' );
			echo '<input type="hidden" name="action" value="ohcl_pages_create"><label><input type="radio" name="status" value="publish" checked> Đăng ngay</label> <label><input type="radio" name="status" value="draft"> Tạo bản nháp</label> '
				. '<button class="button button-primary">Tạo ' . (int) $missing . ' trang còn thiếu</button></form>';
		} else {
			echo '<p class="mute">Đủ trang. Trang của module đang tắt sẽ tạo khi bật module.</p>';
		}
		echo '</div>';

		$mid   = OHCL_Pages::menu_id();
		$items = $mid ? wp_get_nav_menu_items( $mid ) : array();
		$reg   = (array) get_registered_nav_menus();
		$locs  = get_nav_menu_locations();
		echo '<div class="ohcl-card"><h3>Menu "' . esc_html( OHCL_Pages::MENU_NAME ) . '"</h3>';
		echo $mid ? '<p>' . (int) count( (array) $items ) . ' mục · <a href="' . esc_url( admin_url( 'nav-menus.php?action=edit&menu=' . $mid ) ) . '">sửa trong Giao diện › Menu</a>. Đầu trang và chân trang của Hub đọc menu này.</p>' : '<p class="mute">Chưa có menu. Đầu trang Hub đang dựng menu từ sổ trang.</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'ohcl_pages_menu' );
		echo '<input type="hidden" name="action" value="ohcl_pages_menu"><p><label>Gán vào vị trí menu của theme<br><select name="location"><option value="">Không gán (chỉ dùng trong khung Hub)</option>';
		foreach ( $reg as $l => $label ) {
			$cur = ! empty( $locs[ $l ] ) ? wp_get_nav_menu_object( $locs[ $l ] ) : null;
			echo '<option value="' . esc_attr( $l ) . '"' . selected( $mid && ! empty( $locs[ $l ] ) && (int) $locs[ $l ] === $mid, true, false ) . '>' . esc_html( $label . ( $cur ? ' — đang dùng "' . $cur->name . '"' : ' — trống' ) ) . '</option>';
		}
		echo '</select></label></p><p><button class="button button-primary">' . ( $mid ? 'Cập nhật menu' : 'Tạo menu' ) . '</button> <span class="mute">Chỉ thêm mục còn thiếu, không xoá mục bạn đã thêm / sắp xếp.</span></p></form></div></div>';
	}

	public static function handle_create() {
		OHCL_Admin::guard();
		check_admin_referer( 'ohcl_pages_create' );
		$made = OHCL_Pages::create_missing( isset( $_POST['status'] ) && 'draft' === $_POST['status'] ? 'draft' : 'publish' );
		OHCL_Admin::flash_set( array( 'ok' => 'Đã tạo ' . count( $made ) . ' trang. Bấm "Cập nhật menu" để đưa trang mới vào menu.' ) );
		wp_safe_redirect( OHCL_Admin::url( self::PAGE ) );
		exit;
	}

	public static function handle_menu() {
		OHCL_Admin::guard();
		check_admin_referer( 'ohcl_pages_menu' );
		$r = OHCL_Pages::sync_menu( isset( $_POST['location'] ) ? sanitize_key( wp_unslash( $_POST['location'] ) ) : '' );
		OHCL_Admin::flash_set( is_wp_error( $r ) ? OHCL_Admin::error_flash( $r ) : array( 'ok' => 'Menu đã cập nhật: thêm ' . (int) $r['added'] . ' mục' . ( $r['location'] ? ', gán vị trí ' . $r['location'] : '' ) . '.' ) );
		wp_safe_redirect( OHCL_Admin::url( self::PAGE ) );
		exit;
	}
}
