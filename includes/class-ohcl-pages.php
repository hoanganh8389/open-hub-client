<?php
/**
 * WordPress pages + WordPress menu for the Hub pages (same pattern as bizcity-llm-router templates/v2,
 * plus the menu):
 *
 *  1. Every Hub page is a page template "Open Hub — <title>" (key `open-hub:<page key>`). A WordPress page
 *     using it is drawn by the Hub (its editor content is ignored), at whatever slug the owner gives it.
 *  2. Once such a page is published, every Hub link points to it (map template → page id, cached, flushed
 *     when a page is saved / trashed / deleted).
 *  3. "Tạo trang còn thiếu" creates the missing pages (published, or drafts) with the template set.
 *  4. "Tạo / cập nhật menu" builds the WordPress menu "Menu Hub": Trang chủ · menu groups (parent item) →
 *     their pages · Tin tức. Idempotent: it only ADDS what is missing, never removes the owner's items.
 *     It can be assigned to the theme's menu location. The Hub header and footer read this menu, so the
 *     owner edits the menu once in Giao diện › Menu.
 *
 * No new table: pages are posts, the menu is the WordPress menu, ids in two options (no autoload).
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:53 PM Johnny Chu - Chu Hoàng Anh] OHCL-F3 — trang WordPress cho từng trang Hub (mẫu trang), tự tạo trang còn thiếu, tự dựng menu WordPress "Menu Hub" và đầu trang đọc menu đó (giống templates/v2 của router).

defined( 'ABSPATH' ) || exit;

final class OHCL_Pages {

	const PREFIX      = 'open-hub:';
	const MAP_CACHE   = 'ohcl_page_map';
	const MENU_OPTION = 'ohcl_nav_menu';
	const MENU_NAME   = 'Menu Hub';

	/** @var array|null */
	private static $map = null;

	public static function boot() {
		add_filter( 'theme_page_templates', array( __CLASS__, 'page_templates' ) );
		add_action( 'save_post_page', array( __CLASS__, 'forget' ) );
		add_action( 'deleted_post', array( __CLASS__, 'forget' ) );
		add_action( 'trashed_post', array( __CLASS__, 'forget' ) );
	}

	/** Hub pages that can be a WordPress page (not home: that is the front page; not place: one per member). */
	public static function creatable() {
		$out = array();
		foreach ( OHCL_Front::pages() as $k => $pg ) {
			if ( ! in_array( $k, array( 'home', 'place' ), true ) ) {
				$out[ $k ] = $pg;
			}
		}
		return $out;
	}

	public static function page_templates( $templates ) {
		foreach ( self::creatable() as $k => $pg ) {
			$templates[ self::PREFIX . $k ] = 'Open Hub — ' . $pg['title'];
		}
		return $templates;
	}

	/** Template key of a page post ('' when it does not use an Open Hub template). */
	public static function key_of( $post_id ) {
		$t = (string) get_post_meta( (int) $post_id, '_wp_page_template', true );
		return 0 === strpos( $t, self::PREFIX ) ? substr( $t, strlen( self::PREFIX ) ) : '';
	}

	/** page key => published page id (oldest wins). */
	public static function map() {
		if ( null !== self::$map ) {
			return self::$map;
		}
		$m = get_transient( self::MAP_CACHE );
		if ( ! is_array( $m ) ) {
			$m = array();
			foreach ( self::find_all( array( 'publish' ) ) as $k => $posts ) {
				$m[ $k ] = (int) $posts[0]->ID;
			}
			set_transient( self::MAP_CACHE, $m, DAY_IN_SECONDS );
		}
		self::$map = $m;
		return $m;
	}

	/** page key => [posts using its template, oldest first], for the given statuses. */
	public static function find_all( array $status ) {
		$posts = get_posts(
			array(
				'post_type'   => 'page',
				'post_status' => $status,
				'numberposts' => 200,
				'orderby'     => 'ID',
				'order'       => 'ASC',
				'meta_query'  => array( array( 'key' => '_wp_page_template', 'value' => self::PREFIX, 'compare' => 'LIKE' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery -- chạy khi lưu / tạo trang, kết quả đệm 1 ngày.
			)
		);
		$out = array();
		foreach ( $posts as $p ) {
			$k = self::key_of( $p->ID );
			if ( '' !== $k && in_array( $p->post_status, $status, true ) ) {
				$out[ $k ][] = $p;
			}
		}
		return $out;
	}

	public static function forget() {
		self::$map = null;
		delete_transient( self::MAP_CACHE );
	}

	/** Published page of a Hub page key, or 0. */
	public static function page_id( $key ) {
		$m = self::map();
		return isset( $m[ $key ] ) ? (int) $m[ $key ] : 0;
	}

	/* ---------- tạo trang ---------- */

	/**
	 * Create the missing pages (module on, no page in any status yet).
	 *
	 * @param string $status 'publish' | 'draft'.
	 * @return int[] key => new page id
	 */
	public static function create_missing( $status = 'publish' ) {
		$status = 'draft' === $status ? 'draft' : 'publish';
		$have   = self::find_all( array( 'publish', 'draft', 'pending', 'private', 'future' ) );
		$made   = array();
		foreach ( self::creatable() as $k => $pg ) {
			if ( isset( $have[ $k ] ) || ! OHCL_Front::page_on( $k ) ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => $status,
					'post_title'   => $pg['title'],
					'post_name'    => $pg['slug'],
					'post_content' => '',
					'meta_input'   => array( '_wp_page_template' => self::PREFIX . $k ),
				),
				true
			);
			if ( ! is_wp_error( $id ) && $id ) {
				$made[ $k ] = (int) $id;
			}
		}
		self::forget();
		return $made;
	}

	/* ---------- menu ---------- */

	/**
	 * The menu we want, from the page registry: [ {title, key?, url?, children:[...] } ]. Pure apart from URLs.
	 */
	public static function wanted() {
		$out = array( array( 'title' => 'Trang chủ', 'url' => home_url( '/' ), 'children' => array() ) );
		foreach ( OHCL_Front::menu() as $group => $items ) {
			if ( 1 === count( $items ) ) {
				foreach ( $items as $k => $pg ) {
					$out[] = array( 'title' => $group, 'key' => $k, 'children' => array() );
				}
				continue;
			}
			$kids = array();
			foreach ( $items as $k => $pg ) {
				$kids[] = array( 'title' => $pg['title'], 'key' => $k, 'children' => array() );
			}
			$out[] = array( 'title' => $group, 'url' => '#', 'children' => $kids );
		}
		$pf = (int) get_option( 'page_for_posts' );
		if ( $pf ) {
			$out[] = array( 'title' => 'Tin tức', 'page' => $pf, 'children' => array() );
		}
		return $out;
	}

	/** Existing menu id (option, else a menu with our name), or 0. */
	public static function menu_id() {
		$id = (int) get_option( self::MENU_OPTION, 0 );
		if ( $id && wp_get_nav_menu_object( $id ) ) {
			return $id;
		}
		$m = wp_get_nav_menu_object( self::MENU_NAME );
		return $m ? (int) $m->term_id : 0;
	}

	/**
	 * Create or complete "Menu Hub". Adds missing items only; a Hub page with no published WordPress page is
	 * added as a custom link to its Hub URL (still works through the Hub routes).
	 *
	 * @param string $location theme location to assign ('' = do not touch locations).
	 * @return array|WP_Error { menu_id, added, location }
	 */
	public static function sync_menu( $location = '' ) {
		$menu = self::menu_id();
		if ( ! $menu ) {
			$menu = wp_create_nav_menu( self::MENU_NAME );
			if ( is_wp_error( $menu ) || ! $menu ) {
				return OHCL_Error::make( 'ohcl_menu_create_failed', 'Chưa tạo được menu.', 'Vào Giao diện › Menu xem có menu trùng tên "' . self::MENU_NAME . '" không, rồi thử lại.', 500 );
			}
			$menu = (int) $menu;
		}
		update_option( self::MENU_OPTION, $menu, false );
		$items = wp_get_nav_menu_items( $menu );
		$items = is_array( $items ) ? $items : array();
		$added = 0;
		$pos   = count( $items );
		foreach ( self::wanted() as $node ) {
			$top = self::ensure( $menu, $items, $node, 0, $pos, $added );
			foreach ( $node['children'] as $kid ) {
				self::ensure( $menu, $items, $kid, $top, $pos, $added );
			}
		}
		$loc = '';
		if ( '' !== $location ) {
			$reg = get_registered_nav_menus();
			if ( isset( $reg[ $location ] ) ) {
				$locs              = get_nav_menu_locations();
				$locs[ $location ] = $menu;
				set_theme_mod( 'nav_menu_locations', $locs );
				$loc = $location;
			}
		}
		return array( 'menu_id' => $menu, 'added' => $added, 'location' => $loc );
	}

	/** Find an item matching the node under $parent, or add it. Returns its db id. */
	private static function ensure( $menu, array &$items, array $node, $parent, &$pos, &$added ) {
		$page = ! empty( $node['key'] ) ? self::page_id( $node['key'] ) : ( ! empty( $node['page'] ) ? (int) $node['page'] : 0 );
		$url  = ! empty( $node['key'] ) ? OHCL_Front::url( $node['key'] ) : ( isset( $node['url'] ) ? $node['url'] : '' );
		foreach ( $items as $it ) {
			if ( (int) $it->menu_item_parent !== (int) $parent ) {
				continue;
			}
			$same = $page ? ( 'post_type' === $it->type && (int) $it->object_id === $page ) : false;
			if ( $same || ( 'custom' === $it->type && trim( $it->title ) === $node['title'] ) || trim( $it->title ) === $node['title'] ) {
				return (int) $it->ID;
			}
		}
		$pos++;
		$args = array( 'menu-item-title' => $node['title'], 'menu-item-status' => 'publish', 'menu-item-parent-id' => (int) $parent, 'menu-item-position' => $pos );
		if ( $page ) {
			$args += array( 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $page );
		} else {
			$args += array( 'menu-item-type' => 'custom', 'menu-item-url' => $url );
		}
		$id = wp_update_nav_menu_item( $menu, 0, $args );
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}
		$added++;
		$items[] = (object) array( 'ID' => (int) $id, 'title' => $node['title'], 'menu_item_parent' => (int) $parent, 'type' => $page ? 'post_type' : 'custom', 'object_id' => $page, 'url' => $url );
		return (int) $id;
	}

	/**
	 * Header / footer tree from "Menu Hub" (2 levels), or null when there is no such menu / it is empty.
	 *
	 * @return array|null [ {title, url, key, desc, children:[...]} ]
	 */
	public static function nav_tree() {
		$menu = (int) get_option( self::MENU_OPTION, 0 );
		if ( ! $menu || ! function_exists( 'wp_get_nav_menu_items' ) ) {
			return null;
		}
		$items = wp_get_nav_menu_items( $menu );
		if ( ! is_array( $items ) || ! $items ) {
			return null;
		}
		$by_page = array_flip( self::map() );
		$pages   = OHCL_Front::pages();
		$nodes   = array();
		foreach ( $items as $it ) {
			$key = 'post_type' === $it->type && isset( $by_page[ (int) $it->object_id ] ) ? $by_page[ (int) $it->object_id ] : '';
			if ( '' === $key && 'custom' === $it->type ) {
				foreach ( $pages as $k => $pg ) {
					if ( '' !== $pg['slug'] && untrailingslashit( (string) $it->url ) === untrailingslashit( OHCL_Front::url( $k ) ) ) {
						$key = $k;
					}
				}
			}
			if ( '' !== $key && ! OHCL_Front::page_on( $key ) ) {
				continue; // module tắt ⇒ mục biến mất, menu giữ nguyên
			}
			$nodes[ (int) $it->ID ] = array( 'title' => (string) $it->title, 'url' => (string) $it->url, 'key' => $key, 'desc' => '' !== $key && ! empty( $pages[ $key ]['desc'] ) ? $pages[ $key ]['desc'] : '', 'parent' => (int) $it->menu_item_parent, 'children' => array() );
		}
		$tree = array();
		foreach ( $nodes as $id => $n ) {
			if ( $n['parent'] && isset( $nodes[ $n['parent'] ] ) ) {
				$nodes[ $n['parent'] ]['children'][] = $n;
			}
		}
		foreach ( $nodes as $id => $n ) {
			if ( ! $n['parent'] || ! isset( $nodes[ $n['parent'] ] ) ) {
				if ( '#' === $n['url'] && ! $nodes[ $id ]['children'] ) {
					continue; // nhóm rỗng (mọi trang con đã tắt)
				}
				$tree[] = $nodes[ $id ];
			}
		}
		return $tree;
	}
}
