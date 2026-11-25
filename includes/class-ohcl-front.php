<?php
/**
 * Public community portal of the Hub (persona layer, R-HUB-12; mockup vegan-persona.html P1–P4).
 *
 *   /                      P1 home (takes over the front page; switch: option ohcl_front_home, default on)
 *   /ban-do/               P2 directory + map — V4 members only (R-MAP)
 *   /co-so/<id>-<slug>/    P3 member profile — V4 only, else 404
 *   /tro-thanh-hoi-vien/   P4 benefits + registration form ⇒ a member record in Hub › 02 · Hội viên
 *
 * Own full-page templates (theme styles are dropped on these pages; wp_head / wp_footer kept for the
 * admin bar and SEO plugins). Map: Leaflet bundled in the plugin + OpenStreetMap tiles (MAP-Q1).
 * Words come from the industry contract @2 `persona`; numbers only from real data (R-HUB-6).
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — cổng cộng đồng: định tuyến 4 trang, thay trang chủ có công tắc, bỏ CSS theme, form đăng ký có chống spam.

defined( 'ABSPATH' ) || exit;

final class OHCL_Front {

	const QV          = 'ohcl_page';
	const REWRITE_VER = '1';
	const HOME_OPTION = 'ohcl_front_home';
	const JOIN_LIMIT  = 5; // đăng ký / giờ / IP

	/** @var string current page key or '' */
	private static $page = '';

	public static function slugs() {
		return apply_filters( 'ohcl_front_slugs', array( 'map' => 'ban-do', 'place' => 'co-so', 'join' => 'tro-thanh-hoi-vien' ) );
	}

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'rewrites' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_flush' ) );
		add_action( 'template_redirect', array( __CLASS__, 'route' ), 1 );
		add_action( 'admin_post_nopriv_ohcl_join', array( __CLASS__, 'handle_join' ) );
		add_action( 'admin_post_ohcl_join', array( __CLASS__, 'handle_join' ) );
	}

	public static function rewrites() {
		$s = self::slugs();
		add_rewrite_rule( '^' . preg_quote( $s['map'], '/' ) . '/?$', 'index.php?' . self::QV . '=map', 'top' );
		add_rewrite_rule( '^' . preg_quote( $s['place'], '/' ) . '/([0-9]+)(?:-[^/]*)?/?$', 'index.php?' . self::QV . '=place&ohcl_id=$matches[1]', 'top' );
		add_rewrite_rule( '^' . preg_quote( $s['join'], '/' ) . '/?$', 'index.php?' . self::QV . '=join', 'top' );
	}

	public static function query_vars( $v ) {
		$v[] = self::QV;
		$v[] = 'ohcl_id';
		return $v;
	}

	/** One flush per rule version, in wp-admin only (never on a public request, never time()-based). */
	public static function maybe_flush() {
		if ( get_option( 'ohcl_rewrite_ver' ) !== self::REWRITE_VER . '|' . implode( ',', self::slugs() ) ) {
			flush_rewrite_rules( false );
			update_option( 'ohcl_rewrite_ver', self::REWRITE_VER . '|' . implode( ',', self::slugs() ), false );
		}
	}

	public static function home_on() {
		return '0' !== (string) get_option( self::HOME_OPTION, '1' );
	}

	/* ---------- URLs ---------- */

	public static function url( $page = 'home', array $args = array() ) {
		$s    = self::slugs();
		$base = 'home' === $page ? home_url( '/' ) : home_url( '/' . $s[ $page ] . '/' );
		return $args ? add_query_arg( $args, $base ) : $base;
	}

	public static function place_url( $id, $title ) {
		$s    = self::slugs();
		$slug = function_exists( 'sanitize_title' ) ? sanitize_title( $title ) : '';
		return home_url( '/' . $s['place'] . '/' . (int) $id . ( $slug ? '-' . $slug : '' ) . '/' );
	}

	/* ---------- routing ---------- */

	public static function route() {
		$p = (string) get_query_var( self::QV );
		if ( '' === $p && is_front_page() && self::home_on() && ! is_paged() ) {
			$p = 'home';
		}
		if ( ! in_array( $p, array( 'home', 'map', 'place', 'join' ), true ) ) {
			return;
		}
		$data = array();
		if ( 'place' === $p ) {
			$data['place'] = self::find_place( (int) get_query_var( 'ohcl_id' ) );
			if ( ! $data['place'] ) {
				status_header( 404 );
				nocache_headers();
			}
		}
		self::$page = $p;
		self::enqueue( $p );
		echo self::html( $p, $data ); // phpcs:ignore WordPress.Security.EscapeOutput -- template tự escape từng giá trị.
		exit;
	}

	/** V4 card by id, or null (non-public members are never shown, R-MAP). */
	public static function find_place( $id ) {
		$list = OHCL_Members::public_list();
		foreach ( $list['items'] as $it ) {
			if ( (int) $it['id'] === (int) $id ) {
				return $it;
			}
		}
		return null;
	}

	/**
	 * Filter the public list. Pure.
	 *
	 * @param array[] $items public_view() cards.
	 * @param array   $q     { q, seg, province }.
	 * @return array[]
	 */
	public static function filter( array $items, array $q ) {
		$out = array();
		foreach ( $items as $it ) {
			if ( ! empty( $q['seg'] ) && $it['segment'] !== $q['seg'] ) {
				continue;
			}
			if ( ! empty( $q['province'] ) && false === stripos( $it['province'], $q['province'] ) ) {
				continue;
			}
			if ( ! empty( $q['q'] ) ) {
				$hay = $it['title'] . ' ' . $it['province'] . ' ' . $it['address'];
				foreach ( $it['fields'] as $f ) {
					$hay .= ' ' . ( is_array( $f['value'] ) ? implode( ' ', $f['value'] ) : $f['value'] );
				}
				if ( false === stripos( $hay, $q['q'] ) ) {
					continue;
				}
			}
			$out[] = $it;
		}
		return $out;
	}

	/* ---------- render ---------- */

	public static function assets() {
		// Bỏ CSS / JS của theme trên trang Hub; giữ thanh quản trị và tài sản của plugin này.
		global $wp_styles, $wp_scripts;
		$keep_css = array( 'admin-bar', 'dashicons', 'ohcl-front', 'ohcl-leaflet', 'ohcl-fonts' );
		if ( $wp_styles instanceof WP_Styles ) {
			foreach ( (array) $wp_styles->queue as $h ) {
				if ( ! in_array( $h, $keep_css, true ) ) {
					wp_dequeue_style( $h );
				}
			}
		}
		if ( $wp_scripts instanceof WP_Scripts ) {
			foreach ( (array) $wp_scripts->queue as $h ) {
				if ( ! in_array( $h, array( 'admin-bar', 'ohcl-front', 'ohcl-leaflet' ), true ) ) {
					wp_dequeue_script( $h );
				}
			}
		}
	}

	private static function enqueue( $page ) {
		// Gọi đúng lúc wp_enqueue_scripts (gọi sớm hơn sẽ bị WordPress báo _doing_it_wrong).
		add_action(
			'wp_enqueue_scripts',
			function () use ( $page ) {
				OHCL_Front::enqueue_now( $page );
			},
			20
		);
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 9999 );
	}

	public static function enqueue_now( $page ) {
		$u = plugins_url( 'assets/', OHCL_DIR . 'open-hub-client.php' );
		wp_enqueue_style( 'ohcl-fonts', 'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500;600&family=Manrope:wght@400;500;600;700;800&display=swap', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'ohcl-front', $u . 'front.css', array(), OHCL_VERSION );
		$cfg = OHCL_Industry::current();
		wp_add_inline_style( 'ohcl-front', '.ohcl-front{--g:' . $cfg['brand']['accent'] . '}' );
		if ( in_array( $page, array( 'home', 'map', 'place' ), true ) ) {
			wp_enqueue_style( 'ohcl-leaflet', $u . 'vendor/leaflet/leaflet.css', array(), '1.9.4' );
			wp_enqueue_script( 'ohcl-leaflet', $u . 'vendor/leaflet/leaflet.js', array(), '1.9.4', true );
			wp_enqueue_script( 'ohcl-front', $u . 'front.js', array( 'ohcl-leaflet' ), OHCL_VERSION, true );
		}
	}

	public static function title( $page, array $data ) {
		$cfg = OHCL_Industry::current();
		$hub = $cfg['name'] ? $cfg['name'] : get_bloginfo( 'name' );
		switch ( $page ) {
			case 'map':
				return 'Bản đồ & danh bạ · ' . $hub;
			case 'join':
				return 'Trở thành hội viên · ' . $hub;
			case 'place':
				return ! empty( $data['place'] ) ? $data['place']['title'] . ' · ' . $hub : 'Không tìm thấy · ' . $hub;
		}
		return $hub . ' · ' . $cfg['persona']['title'] . ' ' . $cfg['persona']['title_em'];
	}

	/** Full page HTML (testable; route() echoes it). */
	public static function html( $page, array $data ) {
		$title = self::title( $page, $data );
		add_filter( 'pre_get_document_title', function () use ( $title ) { return $title; }, 99 );
		$cfg     = OHCL_Industry::current();
		$persona = $cfg['persona'];
		$tpl     = OHCL_DIR . 'templates/front/';
		ob_start();
		include $tpl . 'header.php';
		include $tpl . $page . '.php';
		include $tpl . 'footer.php';
		return (string) ob_get_clean();
	}

	/** Line icon (SVG, currentColor). Icons from the mockup set only. */
	public static function icon( $k ) {
		static $I = array(
			'leaf'    => '<path d="M5 19c0-8 5-14 15-14 0 10-6 15-14 15"/><path d="M5 19l8-8"/>',
			'search'  => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
			'heart'   => '<path d="M12 20s-7-4.4-7-10a4 4 0 017-2.6A4 4 0 0119 10c0 5.6-7 10-7 10z"/>',
			'globe'   => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18"/>',
			'users'   => '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M15 14.5c3 0 6 2 6 5.5"/>',
			'factory' => '<path d="M3 20V10l6 3V10l6 3V6h6v14z"/>',
			'bag'     => '<path d="M5 8h14l-1 12H6zM9 8V6a3 3 0 016 0v2"/>',
			'pin'     => '<path d="M12 21s-7-6.5-7-12a7 7 0 0114 0c0 5.5-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
			'gift'    => '<path d="M4 10h16v10H4zM3 7h18v3H3zM12 7v13M12 7c-2-4-6-3-5 0M12 7c2-4 6-3 5 0"/>',
			'cal'     => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4"/>',
			'doc'     => '<path d="M6 3h9l4 4v14H6z"/><path d="M9 12h7M9 16h7"/>',
			'book'    => '<path d="M3 5c3-1 6-1 9 1 3-2 6-2 9-1v14c-3-1-6-1-9 1-3-2-6-2-9-1zM12 6v14"/>',
			'store'   => '<path d="M4 9l1.5-5h13L20 9M4 9h16v1.5a2.7 2.7 0 01-5.3 0 2.7 2.7 0 01-5.4 0A2.7 2.7 0 014 10.5zM5 13v7h14v-7"/>',
			'truck'   => '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>',
			'chat'    => '<path d="M4 5h16v11H9l-5 4z"/>',
		);
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( isset( $I[ $k ] ) ? $I[ $k ] : $I['leaf'] ) . '</svg>';
	}

	/** Pins JSON for front.js (public V4 cards only; coordinates already rounded). */
	public static function pins_json( array $items ) {
		$out = array();
		foreach ( $items as $it ) {
			$out[] = array( 't' => $it['title'], 's' => $it['seg_label'], 'p' => $it['province'], 'lat' => $it['lat'], 'lng' => $it['lng'], 'u' => self::place_url( $it['id'], $it['title'] ) );
		}
		return wp_json_encode( $out );
	}

	/* ---------- P4 đăng ký ---------- */

	/**
	 * Validate a public registration. Pure.
	 *
	 * @return array|WP_Error { title, core{}, industry{}, consent }
	 */
	public static function clean_join( array $in, array $industry ) {
		if ( ! empty( $in['website_url_2'] ) ) { // bẫy bot: ô ẩn, người thật để trống
			return OHCL_Error::make( 'ohcl_join_rejected', 'Chưa gửi được đăng ký.', 'Tải lại trang rồi gửi lại.', 400 );
		}
		$zalo = isset( $in['contact_zalo'] ) ? preg_replace( '/[^0-9+]/', '', (string) $in['contact_zalo'] ) : '';
		$mail = isset( $in['email'] ) ? trim( (string) $in['email'] ) : '';
		if ( '' === $zalo && '' === $mail ) {
			return OHCL_Error::make( 'ohcl_join_contact_required', 'Cần số Zalo hoặc email để ban điều hành liên hệ.', 'Nhập ít nhất một cách liên hệ rồi gửi lại.', 400 );
		}
		if ( '' !== $zalo && ! preg_match( '/^\+?[0-9]{9,12}$/', $zalo ) ) {
			return OHCL_Error::make( 'ohcl_join_phone_invalid', 'Số Zalo chưa đúng.', 'Nhập số điện thoại 10 chữ số, ví dụ 0912345678.', 400 );
		}
		$keep = array(
			'title'          => isset( $in['title'] ) ? $in['title'] : '',
			'segment'        => isset( $in['segment'] ) ? $in['segment'] : '',
			'province'       => isset( $in['province'] ) ? $in['province'] : '',
			'contact_zalo'   => $zalo,
			'email'          => $mail,
			'public_consent' => ! empty( $in['public_consent'] ),
		);
		$industry['member_fields'] = array(); // khách điền trường ngành sau, trong hồ sơ
		$c = OHCL_Members::clean( $keep, $industry );
		return $c;
	}

	public static function handle_join() {
		$back = self::url( 'join' );
		if ( ! isset( $_POST['_ohcl_join'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_ohcl_join'] ) ), 'ohcl_join' ) ) {
			wp_safe_redirect( add_query_arg( array( 'err' => 'ohcl_join_expired' ), $back ) . '#dang-ky' );
			exit;
		}
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$tk  = 'ohcl_join_' . md5( $ip );
		$cnt = (int) get_transient( $tk );
		if ( $cnt >= self::JOIN_LIMIT ) {
			wp_safe_redirect( add_query_arg( array( 'err' => 'ohcl_join_rate_limited' ), $back ) . '#dang-ky' );
			exit;
		}
		set_transient( $tk, $cnt + 1, HOUR_IN_SECONDS );
		$clean = self::clean_join( wp_unslash( $_POST ), OHCL_Industry::current() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- clean_join / OHCL_Members::clean bỏ thẻ, cắt độ dài, kiểm từng trường.
		if ( is_wp_error( $clean ) ) {
			wp_safe_redirect( add_query_arg( array( 'err' => $clean->get_error_code() ), $back ) . '#dang-ky' );
			exit;
		}
		$id = OHCL_Members::save( $clean, 0 );
		if ( is_wp_error( $id ) ) {
			wp_safe_redirect( add_query_arg( array( 'err' => 'ohcl_member_save_failed' ), $back ) . '#dang-ky' );
			exit;
		}
		update_post_meta( $id, '_bzm_source', 'web' );
		OHCL_Slots::emit( 'member_stage_changed', array( 'member_id' => (int) $id, 'to' => 1, 'label' => OHCL_Members::JOURNEY[1], 'source' => 'web' ) );
		wp_safe_redirect( add_query_arg( array( 'sent' => 1 ), $back ) . '#dang-ky' );
		exit;
	}

	/** Error code ⇒ four-field message for the join page (codes from the query string only). */
	public static function join_error( $code ) {
		$map = array(
			'ohcl_join_expired'           => array( 'Trang đã mở quá lâu.', 'Tải lại trang rồi gửi lại.' ),
			'ohcl_join_rate_limited'      => array( 'Đã gửi quá nhiều đăng ký từ mạng này.', 'Thử lại sau 1 giờ hoặc nhắn Zalo cho ban điều hành.' ),
			'ohcl_join_rejected'          => array( 'Chưa gửi được đăng ký.', 'Tải lại trang rồi gửi lại.' ),
			'ohcl_join_contact_required'  => array( 'Cần số Zalo hoặc email để ban điều hành liên hệ.', 'Nhập ít nhất một cách liên hệ.' ),
			'ohcl_join_phone_invalid'     => array( 'Số Zalo chưa đúng.', 'Nhập số điện thoại 10 chữ số, ví dụ 0912345678.' ),
			'ohcl_member_title_required'  => array( 'Cần tên cơ sở.', 'Nhập tên cơ sở rồi gửi lại.' ),
			'ohcl_member_segment_invalid' => array( 'Loại hình không có trong danh sách.', 'Chọn loại hình trong ô chọn.' ),
			'ohcl_member_email_invalid'   => array( 'Email không đúng dạng.', 'Sửa email hoặc để trống.' ),
			'ohcl_member_save_failed'     => array( 'Chưa lưu được đăng ký.', 'Thử lại sau ít phút.' ),
		);
		$code = (string) $code;
		return isset( $map[ $code ] ) ? array( 'code' => $code, 'message' => $map[ $code ][0], 'hint' => $map[ $code ][1] ) : null;
	}
}
