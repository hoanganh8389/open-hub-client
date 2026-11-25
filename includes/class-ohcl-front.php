<?php
/**
 * Public community portal of the Hub (persona layer, R-HUB-12; mockups vegan-persona.html P1–P4,
 * hub-frontend.html F1–F10). Same pattern as bizcity-llm-router templates/v2: one page registry, one
 * header with the full menu, one footer, and by default the WHOLE site is drawn in the Hub frame.
 *
 *   Pages of the Hub (registry pages(), filter `ohcl_front_pages`; a page whose module is off disappears):
 *     /                      home                 /quyen-loi/            8 benefit pillars
 *     /cong-nghe-0d/         0đ technology set    /tro-thanh-hoi-vien/   registration form
 *     /ban-do/  /co-so/<id>  directory + profile  /giao-thuong/          supply & trade (V4 members)
 *     /su-kien/              programmes + events  /hoc-vien/             tracks + industry templates
 *     /coach/                coach areas          /tro-thanh-coach/      coach application form
 *     /chung-nhan/?ma=…      certificate lookup   /my-hub/<tab>/         member's own centre (login)
 *     /so-do-trang/          sitemap
 *   Every other front-end view (posts, pages, blog, archives, search, 404) is drawn in the same frame
 *   ("takeover", option ohcl_front_takeover, default on). Theme CSS / JS are removed there; plugin assets
 *   stay so shortcodes of other plugins keep working. WooCommerce shop / product views keep the theme.
 *   One page can keep the theme: option ohcl_front_keep_theme (ids) or filter `ohcl_front_takeover`.
 *
 * Map: Leaflet bundled + OpenStreetMap tiles (MAP-Q1). Words from the industry contract @2 `persona`;
 * numbers only from real data (R-HUB-6).
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — cổng cộng đồng: định tuyến 4 trang, thay trang chủ có công tắc, bỏ CSS theme, form đăng ký có chống spam.
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — đủ trang theo sitemap §12 + My Hub; sổ trang một chỗ; mặc định vẽ cả site trong khung Hub (giống templates/v2).

defined( 'ABSPATH' ) || exit;

final class OHCL_Front {

	const QV            = 'ohcl_page';
	const REWRITE_VER   = '3'; // [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — đủ trang + /my-hub/<tab>/
	const HOME_OPTION   = 'ohcl_front_home';
	const TAKE_OPTION   = 'ohcl_front_takeover';
	const KEEP_OPTION   = 'ohcl_front_keep_theme';
	const JOIN_LIMIT    = 5; // đăng ký / giờ / IP

	/** @var string current page key or '' */
	private static $page = '';

	/**
	 * Page registry: key => { slug, title, group, module?, menu? (shown in the header), desc? }.
	 * group order = header order. A page whose module is off is gone from menus, footer and routing.
	 */
	public static function pages() {
		// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — sổ trang (thêm trang = thêm một dòng + một tệp templates/front/<khoá>.php).
		$p = array(
			'home'     => array( 'slug' => '', 'title' => 'Trang chủ', 'group' => '' ),
			'map'      => array( 'slug' => 'ban-do', 'title' => 'Bản đồ & danh bạ', 'group' => 'Khám phá', 'module' => '07', 'menu' => true, 'desc' => 'Cơ sở đã xác minh, theo loại hình và tỉnh.' ),
			'trade'    => array( 'slug' => 'giao-thuong', 'title' => 'Kết nối giao thương', 'group' => 'Khám phá', 'module' => '09', 'menu' => true, 'desc' => 'Nguồn cung, nhà sản xuất, đối tác trong ngành.' ),
			'events'   => array( 'slug' => 'su-kien', 'title' => 'Sự kiện & chương trình', 'group' => 'Khám phá', 'module' => '09', 'menu' => true, 'desc' => 'Chương trình theo dịp, hội thảo, hội chợ.' ),
			'place'    => array( 'slug' => 'co-so', 'title' => 'Hồ sơ cơ sở', 'group' => 'Khám phá', 'module' => '07' ),
			'benefits' => array( 'slug' => 'quyen-loi', 'title' => 'Quyền lợi hội viên', 'group' => 'Hội viên', 'menu' => true, 'desc' => '8 trụ cột quyền lợi khi tham gia Hub.' ),
			'tech'     => array( 'slug' => 'cong-nghe-0d', 'title' => 'Bộ công nghệ 0đ', 'group' => 'Hội viên', 'module' => '03', 'menu' => true, 'desc' => 'Phần mềm mã nguồn mở tặng hội viên.' ),
			'join'     => array( 'slug' => 'tro-thanh-hoi-vien', 'title' => 'Trở thành hội viên', 'group' => 'Hội viên', 'menu' => true, 'desc' => 'Đăng ký, ban điều hành liên hệ trong ít ngày.' ),
			'academy'  => array( 'slug' => 'hoc-vien', 'title' => 'Học viện', 'group' => 'Học & đồng hành', 'module' => '05', 'menu' => true, 'desc' => 'Lộ trình học và kho mẫu của ngành.' ),
			'coaches'  => array( 'slug' => 'coach', 'title' => 'Coach & chuyên gia', 'group' => 'Học & đồng hành', 'module' => '04', 'menu' => true, 'desc' => 'Người đồng hành chuyển đổi số và vận hành.' ),
			'becoach'  => array( 'slug' => 'tro-thanh-coach', 'title' => 'Trở thành Coach', 'group' => 'Học & đồng hành', 'module' => '04', 'menu' => true, 'desc' => 'Ứng tuyển Coach của Hub.' ),
			'cert'     => array( 'slug' => 'chung-nhan', 'title' => 'Xác minh & chứng nhận', 'group' => 'Chứng nhận', 'module' => '06', 'menu' => true, 'desc' => 'Bốn loại ghi nhận, tra cứu giấy chứng nhận.' ),
			'myhub'    => array( 'slug' => 'my-hub', 'title' => 'My Hub', 'group' => '' ),
			'sitemap'  => array( 'slug' => 'so-do-trang', 'title' => 'Sơ đồ trang', 'group' => '' ),
		);
		$p = apply_filters( 'ohcl_front_pages', $p );
		// Back-compat: the old slug filter still renames a page.
		$slugs = apply_filters( 'ohcl_front_slugs', array() );
		foreach ( (array) $slugs as $k => $s ) {
			if ( isset( $p[ $k ] ) && is_string( $s ) && preg_match( '/^[a-z0-9-]+$/', $s ) ) {
				$p[ $k ]['slug'] = $s;
			}
		}
		return $p;
	}

	/** Pages whose module is on. */
	public static function live_pages() {
		$out = array();
		foreach ( self::pages() as $k => $pg ) {
			if ( self::page_on( $k ) ) {
				$out[ $k ] = $pg;
			}
		}
		return $out;
	}

	public static function page_on( $key ) {
		$p = self::pages();
		if ( ! isset( $p[ $key ] ) ) {
			return false;
		}
		if ( 'cert' === $key && ! class_exists( 'OHCL_Certs' ) ) {
			return false;
		}
		return empty( $p[ $key ]['module'] ) || OHCL_Modules::enabled( $p[ $key ]['module'] );
	}

	/** key => slug (compat). */
	public static function slugs() {
		$out = array();
		foreach ( self::pages() as $k => $pg ) {
			$out[ $k ] = $pg['slug'];
		}
		return $out;
	}

	/** Header / footer menu groups: group => [key => page]. */
	public static function menu() {
		$out = array();
		foreach ( self::live_pages() as $k => $pg ) {
			if ( ! empty( $pg['menu'] ) && '' !== $pg['group'] ) {
				$out[ $pg['group'] ][ $k ] = $pg;
			}
		}
		return $out;
	}

	/** Blog URL (posts page, else the post archive), '' when the site has no published post. */
	public static function blog_url() {
		$pf = (int) get_option( 'page_for_posts' );
		if ( $pf ) {
			return get_permalink( $pf );
		}
		$one = get_posts( array( 'numberposts' => 1, 'post_type' => 'post', 'post_status' => 'publish', 'fields' => 'ids' ) );
		if ( ! $one ) {
			return '';
		}
		return 'page' === get_option( 'show_on_front' ) ? '' : add_query_arg( array( 'post_type' => 'post' ), home_url( '/' ) );
	}

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'rewrites' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_flush' ) );
		add_action( 'template_redirect', array( __CLASS__, 'route' ), 1 );
		add_filter( 'template_include', array( __CLASS__, 'takeover' ), 9999 );
		add_action( 'admin_post_nopriv_ohcl_join', array( __CLASS__, 'handle_join' ) );
		add_action( 'admin_post_ohcl_join', array( __CLASS__, 'handle_join' ) );
		add_action( 'admin_post_nopriv_ohcl_cert_report', array( __CLASS__, 'handle_report' ) );
		add_action( 'admin_post_ohcl_cert_report', array( __CLASS__, 'handle_report' ) );
		add_action( 'admin_post_nopriv_ohcl_coach_apply', array( __CLASS__, 'handle_coach_apply' ) );
		add_action( 'admin_post_ohcl_coach_apply', array( __CLASS__, 'handle_coach_apply' ) );
	}

	public static function rewrites() {
		foreach ( self::pages() as $k => $pg ) {
			if ( '' === $pg['slug'] ) {
				continue;
			}
			$s = preg_quote( $pg['slug'], '/' );
			if ( 'place' === $k ) {
				add_rewrite_rule( '^' . $s . '/([0-9]+)(?:-[^/]*)?/?$', 'index.php?' . self::QV . '=place&ohcl_id=$matches[1]', 'top' );
			} elseif ( 'myhub' === $k ) {
				add_rewrite_rule( '^' . $s . '(?:/([a-z-]+))?/?$', 'index.php?' . self::QV . '=myhub&ohcl_tab=$matches[1]', 'top' );
			} else {
				add_rewrite_rule( '^' . $s . '/?$', 'index.php?' . self::QV . '=' . $k, 'top' );
			}
		}
	}

	public static function query_vars( $v ) {
		$v[] = self::QV;
		$v[] = 'ohcl_id';
		$v[] = 'ohcl_tab';
		return $v;
	}

	/** One flush per rule version, in wp-admin only (never on a public request, never time()-based). */
	public static function maybe_flush() {
		$ver = self::REWRITE_VER . '|' . implode( ',', self::slugs() );
		if ( get_option( 'ohcl_rewrite_ver' ) !== $ver ) {
			flush_rewrite_rules( false );
			update_option( 'ohcl_rewrite_ver', $ver, false );
		}
	}

	/** Certification page exists only while module 06 is on and the certificate class is loaded. */
	public static function cert_on() {
		return self::page_on( 'cert' );
	}

	public static function home_on() {
		return '0' !== (string) get_option( self::HOME_OPTION, '1' );
	}

	public static function takeover_on() {
		return '0' !== (string) get_option( self::TAKE_OPTION, '1' );
	}

	/** Post ids that keep the theme (comma list in an option). */
	public static function keep_theme_ids() {
		return array_filter( array_map( 'intval', explode( ',', (string) get_option( self::KEEP_OPTION, '' ) ) ) );
	}

	/* ---------- URLs ---------- */

	public static function url( $page = 'home', array $args = array() ) {
		// [2026-11-25 11:53 PM Johnny Chu - Chu Hoàng Anh] OHCL-F3 — có trang WordPress dùng mẫu "Open Hub — …" đã đăng ⇒ link trỏ về trang đó.
		$pid = 'home' !== $page && class_exists( 'OHCL_Pages' ) ? OHCL_Pages::page_id( $page ) : 0;
		$pl  = $pid ? get_permalink( $pid ) : '';
		if ( $pl ) {
			return $args ? add_query_arg( $args, $pl ) : $pl;
		}
		$p    = self::pages();
		$slug = isset( $p[ $page ] ) ? $p[ $page ]['slug'] : '';
		$base = '' === $slug ? home_url( '/' ) : home_url( '/' . $slug . '/' );
		return $args ? add_query_arg( $args, $base ) : $base;
	}

	public static function place_url( $id, $title ) {
		$slug = function_exists( 'sanitize_title' ) ? sanitize_title( $title ) : '';
		return home_url( '/' . self::pages()['place']['slug'] . '/' . (int) $id . ( $slug ? '-' . $slug : '' ) . '/' );
	}

	public static function myhub_url( $tab = '' ) {
		$pid = class_exists( 'OHCL_Pages' ) ? OHCL_Pages::page_id( 'myhub' ) : 0;
		$pl  = $pid ? get_permalink( $pid ) : '';
		if ( $pl && untrailingslashit( $pl ) !== untrailingslashit( home_url( '/' . self::pages()['myhub']['slug'] ) ) ) {
			return $tab ? add_query_arg( array( 'tab' => $tab ), $pl ) : $pl; // [2026-11-25 11:53 PM Johnny Chu - Chu Hoàng Anh] OHCL-F3 — trang My Hub đổi đường dẫn ⇒ mục qua ?tab=
		}
		return home_url( '/' . self::pages()['myhub']['slug'] . '/' . ( $tab ? $tab . '/' : '' ) );
	}

	/**
	 * Header / footer tree: the WordPress menu "Menu Hub" when it exists (owner edits it in Giao diện › Menu),
	 * else built from the page registry. [ {title, url, key, desc, children} ]
	 */
	public static function nav() {
		// [2026-11-25 11:53 PM Johnny Chu - Chu Hoàng Anh] OHCL-F3 — đầu / chân trang đọc menu WordPress "Menu Hub"; chưa có menu thì dựng từ sổ trang.
		if ( class_exists( 'OHCL_Pages' ) ) {
			$t = OHCL_Pages::nav_tree();
			if ( null !== $t ) {
				return $t;
			}
		}
		$t = array( array( 'title' => 'Trang chủ', 'url' => home_url( '/' ), 'key' => 'home', 'desc' => '', 'children' => array() ) );
		foreach ( self::menu() as $g => $items ) {
			$kids = array();
			foreach ( $items as $k => $pg ) {
				$kids[] = array( 'title' => $pg['title'], 'url' => self::url( $k ), 'key' => $k, 'desc' => isset( $pg['desc'] ) ? $pg['desc'] : '', 'children' => array() );
			}
			if ( 1 === count( $kids ) ) {
				$kids[0]['title'] = $g;
				$t[]              = $kids[0];
			} else {
				$t[] = array( 'title' => $g, 'url' => '#', 'key' => '', 'desc' => '', 'children' => $kids );
			}
		}
		$blog = self::blog_url();
		if ( $blog ) {
			$t[] = array( 'title' => 'Tin tức', 'url' => $blog, 'key' => 'blog', 'desc' => '', 'children' => array() );
		}
		return $t;
	}

	/* ---------- routing ---------- */

	public static function route() {
		$p = (string) get_query_var( self::QV );
		if ( '' === $p && is_front_page() && self::home_on() && ! is_paged() ) {
			$p = 'home';
		}
		if ( '' === $p && class_exists( 'OHCL_Pages' ) && is_page() ) {
			$p = OHCL_Pages::key_of( get_queried_object_id() ); // [2026-11-25 11:53 PM Johnny Chu - Chu Hoàng Anh] OHCL-F3 — trang WordPress dùng mẫu "Open Hub — …"
		}
		if ( '' === $p || ! self::page_on( $p ) ) {
			return; // không phải trang Hub, hoặc module đang tắt ⇒ WordPress xử lý (404 / khung chung)
		}
		$data = array();
		if ( 'cert' === $p && isset( $_GET['ma'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- tra cứu công khai, chỉ đọc; normalize() chỉ nhận đúng dạng mã.
			$data['lookup'] = OHCL_Certs::lookup( sanitize_text_field( wp_unslash( $_GET['ma'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$data['asked']  = sanitize_text_field( wp_unslash( $_GET['ma'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			nocache_headers();
		}
		if ( 'place' === $p ) {
			$data['place'] = self::find_place( (int) get_query_var( 'ohcl_id' ) );
			if ( ! $data['place'] ) {
				status_header( 404 );
				nocache_headers();
			}
		}
		if ( 'myhub' === $p ) {
			nocache_headers(); // trang riêng của người đăng nhập
			$data['tab'] = sanitize_key( (string) get_query_var( 'ohcl_tab' ) );
			if ( '' === $data['tab'] && isset( $_GET['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ chọn mục.
				$data['tab'] = sanitize_key( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}
		global $wp_query;
		if ( $wp_query && 'place' !== $p ) {
			$wp_query->is_404 = false;
			status_header( 200 );
		}
		self::$page = $p;
		self::enqueue( $p );
		echo self::html( $p, $data ); // phpcs:ignore WordPress.Security.EscapeOutput -- template tự escape từng giá trị.
		exit;
	}

	/**
	 * Should this WordPress view be drawn in the Hub frame? Pure enough to test: takes the facts.
	 *
	 * @param array $v { on, feed, embed, admin, woo, keep } booleans.
	 */
	public static function should_take( array $v ) {
		return ! empty( $v['on'] ) && empty( $v['feed'] ) && empty( $v['embed'] ) && empty( $v['admin'] ) && empty( $v['woo'] ) && empty( $v['keep'] );
	}

	/** template_include: every other front-end view in the Hub frame (posts, pages, blog, archives, search, 404). */
	public static function takeover( $template ) {
		// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — vẽ cả site trong khung Hub (mặc định bật); giữ theme cho Woo shop / sản phẩm và trang chủ Hub chọn giữ.
		$woo  = function_exists( 'is_woocommerce' ) && is_woocommerce();
		$id   = is_singular() ? (int) get_queried_object_id() : 0;
		$keep = $id && in_array( $id, self::keep_theme_ids(), true );
		$take = self::should_take( array( 'on' => self::takeover_on(), 'feed' => is_feed(), 'embed' => is_embed(), 'admin' => is_admin(), 'woo' => $woo, 'keep' => $keep ) );
		if ( ! apply_filters( 'ohcl_front_takeover', $take, $template ) ) {
			return $template;
		}
		self::$page = 'wp';
		self::enqueue( 'wp' );
		return OHCL_DIR . 'templates/front/takeover.php';
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
		// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — trang Hub: bỏ mọi CSS / JS lạ; trang WordPress vẽ trong khung Hub: chỉ bỏ CSS / JS của theme, giữ của plugin (shortcode Woo, form… vẫn chạy).
		global $wp_styles, $wp_scripts;
		$keep_css = array( 'admin-bar', 'dashicons', 'ohcl-front', 'ohcl-leaflet', 'ohcl-fonts' );
		$keep_js  = array( 'admin-bar', 'ohcl-front', 'ohcl-leaflet', 'ohcl-qrlib', 'ohcl-qr' );
		$only_theme = 'wp' === self::$page;
		$theme_urls = array_filter( array( function_exists( 'get_template_directory_uri' ) ? get_template_directory_uri() : '', function_exists( 'get_stylesheet_directory_uri' ) ? get_stylesheet_directory_uri() : '' ) );
		$drop = function ( $deps, $h, array $keep ) use ( $only_theme, $theme_urls ) {
			if ( in_array( $h, $keep, true ) || 0 === strpos( $h, 'query-monitor' ) || 0 === strpos( $h, 'qm-' ) ) {
				return false;
			}
			if ( ! $only_theme ) {
				return true;
			}
			$src = isset( $deps->registered[ $h ] ) ? (string) $deps->registered[ $h ]->src : '';
			foreach ( $theme_urls as $u ) {
				if ( '' !== $src && 0 === strpos( preg_replace( '#^https?:#', '', $src ), preg_replace( '#^https?:#', '', $u ) ) ) {
					return true;
				}
			}
			return false;
		};
		if ( $wp_styles instanceof WP_Styles ) {
			foreach ( (array) $wp_styles->queue as $h ) {
				if ( $drop( $wp_styles, $h, $keep_css ) ) {
					wp_dequeue_style( $h );
				}
			}
		}
		if ( $wp_scripts instanceof WP_Scripts ) {
			foreach ( (array) $wp_scripts->queue as $h ) {
				if ( $drop( $wp_scripts, $h, $keep_js ) ) {
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
		if ( in_array( $page, array( 'home', 'map', 'place', 'trade' ), true ) ) {
			wp_enqueue_style( 'ohcl-leaflet', $u . 'vendor/leaflet/leaflet.css', array(), '1.9.4' );
			wp_enqueue_script( 'ohcl-leaflet', $u . 'vendor/leaflet/leaflet.js', array(), '1.9.4', true );
			wp_enqueue_script( 'ohcl-front', $u . 'front.js', array( 'ohcl-leaflet' ), OHCL_VERSION, true );
		}
		if ( 'cert' === $page || 'myhub' === $page ) {
			self::enqueue_qr();
		}
	}

	/** QR renderer (qrcode-generator 1.4.4, MIT, bundled) for the lookup page and the printable certificate. */
	public static function enqueue_qr() {
		$u = plugins_url( 'assets/', OHCL_DIR . 'open-hub-client.php' );
		wp_enqueue_script( 'ohcl-qrlib', $u . 'vendor/qrcode/qrcode.js', array(), '1.4.4', true );
		wp_enqueue_script( 'ohcl-qr', $u . 'qr.js', array( 'ohcl-qrlib' ), OHCL_VERSION, true );
	}

	/** Document title of a Hub page; '' for WordPress views (their own title stays). */
	public static function title( $page, array $data ) {
		$cfg = OHCL_Industry::current();
		$hub = $cfg['name'] ? $cfg['name'] : get_bloginfo( 'name' );
		if ( 'wp' === $page ) {
			return '';
		}
		if ( 'place' === $page ) {
			return ! empty( $data['place'] ) ? $data['place']['title'] . ' · ' . $hub : 'Không tìm thấy · ' . $hub;
		}
		if ( 'home' === $page ) {
			return $hub . ' · ' . $cfg['persona']['title'] . ' ' . $cfg['persona']['title_em'];
		}
		$p = self::pages();
		return ( isset( $p[ $page ] ) ? $p[ $page ]['title'] : $hub ) . ' · ' . $hub;
	}

	/** Full page HTML (testable; route() / takeover.php echo it). */
	public static function html( $page, array $data ) {
		$title = self::title( $page, $data );
		if ( '' !== $title ) {
			add_filter( 'pre_get_document_title', function () use ( $title ) { return $title; }, 99 );
		}
		self::$page = $page;
		$cfg     = OHCL_Industry::current();
		$persona = $cfg['persona'];
		$tpl     = OHCL_DIR . 'templates/front/';
		ob_start();
		include $tpl . 'header.php';
		include $tpl . $page . '.php';
		include $tpl . 'footer.php';
		return (string) ob_get_clean();
	}

	public static function current_page() {
		return self::$page;
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
			// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — biểu tượng cho các trang mới.
			'grid'    => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
			'cap'     => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/>',
			'hand'    => '<path d="M3 13l4-4 4 3 3-2 7 6-3 3-4-3-2 2-4-3-2 2z"/>',
			'calc'    => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 12h2M12 12h2M16 12v5M8 16h2M12 16h2"/>',
			'box'     => '<path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/>',
			'web'     => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M7 6.5h.01M10 6.5h.01"/>',
			'bot'     => '<rect x="5" y="8" width="14" height="11" rx="3"/><path d="M12 4v4M9 13h.01M15 13h.01M9 16h6"/>',
			'flow'    => '<circle cx="6" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="M8.5 6H14a4 4 0 014 4v5.5M6 8.5V14a4 4 0 004 4h5.5"/>',
			'trend'   => '<path d="M3 17l6-6 4 4 8-8M15 7h6v6"/>',
			'net'     => '<circle cx="12" cy="5" r="2.5"/><circle cx="5" cy="18" r="2.5"/><circle cx="19" cy="18" r="2.5"/><path d="M11 7.5L6 15.5M13 7.5l5 8M7.5 18h9"/>',
			'coach'   => '<circle cx="12" cy="7" r="3.5"/><path d="M5 21c0-4 3-7 7-7s7 3 7 7M15 14l-3 4-3-4"/>',
			'lock'    => '<rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/>',
			'mega'    => '<path d="M3 10v4h4l7 4V6L7 10zM17 9a4 4 0 010 6"/>',
			'wallet'  => '<rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18M16 15h.01"/>',
			'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
			'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>',
			// [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — biểu tượng bốn loại ghi nhận.
			'check'   => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 10"/>',
			'shield'  => '<path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z"/><path d="M9 12l2 2 4-4"/>',
			'star'    => '<path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9 6.8 19.6l1-5.8-4.3-4.1 5.9-.9z"/>',
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

	/* ---------- F2 nội dung chung (không phụ thuộc ngành) ---------- */

	/**
	 * Bizcity Open apps a Hub can gift (contract `apps` picks which). Generic words; an industry plugin may
	 * rename them through filter `ohcl_app_catalog`.
	 */
	public static function app_catalog() {
		// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — danh mục ứng dụng mở cho trang Bộ công nghệ 0đ và My Hub (client chạy độc lập, không đọc dữ liệu của router).
		return apply_filters(
			'ohcl_app_catalog',
			array(
				'open-pos'        => array( 'Bán hàng (POS)', 'Bán tại quầy, in hoá đơn, theo dõi ca, nhiều điểm bán.', 'store' ),
				'open-ke-toan'    => array( 'Kế toán', 'Sổ sách theo chuẩn, công nợ, thuế, báo cáo tài chính.', 'calc' ),
				'open-inventory'  => array( 'Kho', 'Nhập, xuất, tồn, hạn dùng, định mức nguyên liệu.', 'box' ),
				'open-crm'        => array( 'Khách hàng (CRM)', 'Khách quen, lịch sử mua, chăm sóc, nhắc lịch.', 'users' ),
				'open-web'        => array( 'Website', 'Website đặt hàng / đặt bàn của chính cơ sở.', 'web' ),
				'open-brain'      => array( 'Trợ lý AI trên Zalo', 'Trả lời khách, nhận đơn, báo cáo; tính theo mức dùng.', 'bot' ),
				'open-automation' => array( 'Tự động hoá', 'Quy trình lặp lại chạy tự động.', 'flow' ),
				'open-notebook'   => array( 'Kho tri thức', 'Tài liệu, quy trình, sổ tay vận hành.', 'book' ),
			)
		);
	}

	/** The 8 benefit pillars (OPEN-HUB-CLIENT §12), words generic, industry term substituted. */
	public static function pillars() {
		$b = OHCL_Industry::term( 'business' );
		return array(
			array( 'gift', 'Công nghệ 0đ', 'Trọn bộ phần mềm quản trị, bán hàng, kế toán, khách hàng, website. Không thu phí bản quyền.' ),
			array( 'trend', 'Có thêm khách hàng', 'Được giới thiệu qua danh bạ, bản đồ, chương trình và chiến dịch của ngành.' ),
			array( 'shield', 'Nâng cao uy tín', 'Được xác minh hồ sơ và đánh giá theo bộ tiêu chuẩn ngành, tra cứu công khai.' ),
			array( 'cap', 'Được đào tạo', 'Lộ trình học, workshop, mẫu và quy trình chuyên ngành.' ),
			array( 'coach', 'Coach đồng hành', 'Người hướng dẫn chuyển đổi số, vận hành và phát triển ' . $b . '.' ),
			array( 'net', 'Kết nối giao thương', 'Tìm nguồn cung, đối tác, nhà phân phối trong cộng đồng.' ),
			array( 'bot', 'Ứng dụng AI', 'Trợ lý trên Zalo trả lời khách, nhận đơn, báo cáo; dữ liệu nằm ở ' . $b . '.' ),
			array( 'lock', 'Làm chủ doanh nghiệp', 'Làm chủ dữ liệu, công nghệ và quy trình; không bị khoá vào nhà cung cấp.' ),
		);
	}

	/* ---------- F10 ứng tuyển Coach ---------- */

	public static function handle_coach_apply() {
		// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — ứng tuyển Coach: nonce, bẫy bot, 3 lần / giờ / IP; hồ sơ vào 04 · Coach.
		$back = self::url( 'becoach' );
		if ( ! self::page_on( 'becoach' ) || ! class_exists( 'OHCL_Coach' ) ) {
			wp_safe_redirect( self::url() );
			exit;
		}
		if ( ! isset( $_POST['_ohcl_coach'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_ohcl_coach'] ) ), 'ohcl_coach_apply' ) ) {
			wp_safe_redirect( add_query_arg( array( 'err' => 'ohcl_join_expired' ), $back ) . '#ung-tuyen' );
			exit;
		}
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$tk  = 'ohcl_coach_' . md5( $ip );
		$cnt = (int) get_transient( $tk );
		if ( $cnt >= OHCL_Coach::LIMIT ) {
			wp_safe_redirect( add_query_arg( array( 'err' => 'ohcl_report_rate_limited' ), $back ) . '#ung-tuyen' );
			exit;
		}
		set_transient( $tk, $cnt + 1, HOUR_IN_SECONDS );
		$clean = OHCL_Coach::clean_apply( wp_unslash( $_POST ), OHCL_Industry::current() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- clean_apply() bỏ thẻ, cắt độ dài, lọc chuyên môn theo hợp đồng.
		if ( is_wp_error( $clean ) ) {
			wp_safe_redirect( add_query_arg( array( 'err' => $clean->get_error_code() ), $back ) . '#ung-tuyen' );
			exit;
		}
		$id = OHCL_Coach::save( $clean );
		wp_safe_redirect( add_query_arg( is_wp_error( $id ) ? array( 'err' => 'ohcl_coach_save_failed' ) : array( 'sent' => 1 ), $back ) . '#ung-tuyen' );
		exit;
	}

	/* ---------- F3 phản ánh ---------- */

	public static function handle_report() {
		// [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — phản ánh công khai về một cơ sở / giấy chứng nhận: nonce, bẫy bot, 3 lần / giờ / IP; chỉ quản trị viên đọc.
		$back = self::url( 'cert' );
		if ( ! self::cert_on() ) {
			wp_safe_redirect( self::url() );
			exit;
		}
		if ( ! isset( $_POST['_ohcl_report'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_ohcl_report'] ) ), 'ohcl_cert_report' ) ) {
			wp_safe_redirect( add_query_arg( array( 'err' => 'ohcl_join_expired' ), $back ) . '#phan-anh' );
			exit;
		}
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$tk  = 'ohcl_report_' . md5( $ip );
		$cnt = (int) get_transient( $tk );
		if ( $cnt >= OHCL_Certs::REPORT_LIMIT ) {
			wp_safe_redirect( add_query_arg( array( 'err' => 'ohcl_report_rate_limited' ), $back ) . '#phan-anh' );
			exit;
		}
		set_transient( $tk, $cnt + 1, HOUR_IN_SECONDS );
		$clean = OHCL_Certs::clean_report( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- clean_report() bỏ thẻ, cắt độ dài, chuẩn hoá mã.
		if ( is_wp_error( $clean ) ) {
			wp_safe_redirect( add_query_arg( array( 'err' => $clean->get_error_code() ), $back ) . '#phan-anh' );
			exit;
		}
		OHCL_Certs::add_report( $clean );
		wp_safe_redirect( add_query_arg( array( 'reported' => 1 ), $back ) . '#phan-anh' );
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
			'ohcl_report_rejected'        => array( 'Chưa gửi được phản ánh.', 'Tải lại trang rồi gửi lại.' ),
			'ohcl_report_too_short'       => array( 'Phản ánh quá ngắn.', 'Viết rõ cơ sở nào, việc gì (ít nhất 10 ký tự).' ),
			'ohcl_report_rate_limited'    => array( 'Đã gửi quá nhiều lần từ mạng này.', 'Thử lại sau 1 giờ.' ),
			'ohcl_coach_rejected'         => array( 'Chưa gửi được hồ sơ.', 'Tải lại trang rồi gửi lại.' ),
			'ohcl_coach_name_required'    => array( 'Cần họ tên.', 'Nhập họ tên rồi gửi lại.' ),
			'ohcl_coach_contact_required' => array( 'Cần email hoặc số Zalo.', 'Nhập một cách liên hệ rồi gửi lại.' ),
			'ohcl_coach_skill_required'   => array( 'Cần chọn ít nhất một chuyên môn.', 'Đánh dấu chuyên môn của bạn rồi gửi lại.' ),
			'ohcl_coach_save_failed'      => array( 'Chưa lưu được hồ sơ.', 'Thử lại sau ít phút.' ),
			'ohcl_member_url_invalid'     => array( 'Website phải bắt đầu bằng http:// hoặc https://.', 'Sửa địa chỉ website hoặc để trống.' ),
		);
		$code = (string) $code;
		return isset( $map[ $code ] ) ? array( 'code' => $code, 'message' => $map[ $code ][0], 'hint' => $map[ $code ][1] ) : null;
	}
}
