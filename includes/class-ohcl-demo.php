<?php
/**
 * Demo data of a Hub (OPEN-HUB-CLIENT §12a): sample businesses for the map + sample posts, supplied by the
 * industry plugin through the filter `open_hub_client_demo` (data only, R-HUB-1). Owner decisions CM-Q1/Q3:
 *
 *   - sample businesses are public with a "Mẫu" label while the Hub has fewer real public businesses
 *     than the threshold (default 15); they disappear by themselves after that, or when the owner turns
 *     the demo mode off. They are never counted in any number, never get a key or a certificate;
 *   - sample posts are published at once; the owner publishes / drafts / deletes all of them in one click.
 *
 * Every demo record carries a hidden meta (`_bzm_demo` on members, `_ohcl_demo` on posts) + a stable `ref`,
 * so seeding is idempotent and removal never touches real data. No new table.
 *
 * Pack shape: { threshold, categories{slug:name}, members[{ref,title,segment,province,address,hours,lat,lng,fields{}}],
 *               posts[{ref,title,category,excerpt,content}] }. Content may use {map} {join} {benefits} {place:<ref>}.
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-DEMO — dữ liệu mẫu theo ngành: cơ sở mẫu công khai có nhãn, tự tắt khi đủ cơ sở thật; bài mẫu đăng / nháp / xoá một lần.

defined( 'ABSPATH' ) || exit;

final class OHCL_Demo {

	const FILTER      = 'open_hub_client_demo';
	const MODE_OPTION = 'ohcl_demo_mode'; // 'auto' (mặc định) | 'off'
	const MEMBER_META = '_bzm_demo';
	const POST_META   = '_ohcl_demo';
	const REF_META    = '_ohcl_demo_ref';
	const THRESHOLD   = 15;

	/** @var array|null */
	private static $pack = null;

	/* ===================== pure ===================== */

	/**
	 * Clean the industry pack against the industry config. Pure.
	 *
	 * @return array{threshold:int, categories:array, members:array[], posts:array[], skipped:string[]}
	 */
	public static function clean_pack( $raw, array $industry ) {
		$raw  = is_array( $raw ) ? $raw : array();
		$out  = array( 'threshold' => self::THRESHOLD, 'categories' => array(), 'members' => array(), 'posts' => array(), 'skipped' => array() );
		if ( isset( $raw['threshold'] ) && is_numeric( $raw['threshold'] ) ) {
			$out['threshold'] = max( 1, min( 500, (int) $raw['threshold'] ) );
		}
		foreach ( isset( $raw['categories'] ) && is_array( $raw['categories'] ) ? $raw['categories'] : array() as $slug => $name ) {
			$slug = self::slug( $slug );
			$name = self::str( $name, 60 );
			if ( '' !== $slug && '' !== $name ) {
				$out['categories'][ $slug ] = $name;
			}
		}
		$fields = array();
		foreach ( isset( $industry['member_fields'] ) ? $industry['member_fields'] : array() as $f ) {
			$fields[ $f['key'] ] = $f;
		}
		$seen = array();
		foreach ( isset( $raw['members'] ) && is_array( $raw['members'] ) ? $raw['members'] : array() as $i => $m ) {
			$m   = is_array( $m ) ? $m : array();
			$ref = isset( $m['ref'] ) ? self::slug( $m['ref'] ) : '';
			$t   = isset( $m['title'] ) ? self::str( $m['title'], 110 ) : '';
			$seg = isset( $m['segment'] ) ? (string) $m['segment'] : '';
			$lat = isset( $m['lat'] ) && is_numeric( $m['lat'] ) ? (float) $m['lat'] : null;
			$lng = isset( $m['lng'] ) && is_numeric( $m['lng'] ) ? (float) $m['lng'] : null;
			if ( '' === $ref || isset( $seen[ $ref ] ) || '' === $t || ! isset( $industry['segments'][ $seg ] ) || null === $lat || null === $lng || abs( $lat ) > 90 || abs( $lng ) > 180 ) {
				$out['skipped'][] = 'members[' . $i . ']' . ( '' !== $ref ? ' ' . $ref : '' );
				continue;
			}
			$seen[ $ref ] = true;
			// Luật dữ liệu mẫu: tên luôn có "(mẫu)", không điện thoại / email / website (không gọi nhầm người thật).
			if ( ! preg_match( '/\(mẫu\)\s*$/u', $t ) ) {
				$t .= ' (mẫu)';
			}
			$ind = array();
			foreach ( isset( $m['fields'] ) && is_array( $m['fields'] ) ? $m['fields'] : array() as $k => $v ) {
				if ( ! isset( $fields[ $k ] ) ) {
					continue;
				}
				$f = $fields[ $k ];
				if ( 'multi' === $f['type'] ) {
					$v = is_array( $v ) ? array_values( array_intersect( array_map( 'strval', $v ), array_keys( $f['options'] ) ) ) : array();
				} elseif ( 'select' === $f['type'] ) {
					$v = is_string( $v ) && isset( $f['options'][ $v ] ) ? $v : '';
				} elseif ( in_array( $f['type'], array( 'text', 'textarea' ), true ) ) {
					$v = self::str( $v, 200 );
				} else {
					continue; // url / number / bool: không đưa dữ liệu mẫu vào các trường có thể bị hiểu là thật
				}
				$ind[ $k ] = $v;
			}
			$out['members'][] = array(
				'ref'      => $ref,
				'title'    => $t,
				'segment'  => $seg,
				'province' => isset( $m['province'] ) ? self::str( $m['province'], 60 ) : '',
				'address'  => isset( $m['address'] ) ? self::str( $m['address'], 200 ) : '',
				'hours'    => isset( $m['hours'] ) ? self::str( $m['hours'], 120 ) : '',
				'lat'      => (string) round( $lat, 6 ),
				'lng'      => (string) round( $lng, 6 ),
				'fields'   => $ind,
			);
		}
		$seen = array();
		foreach ( isset( $raw['posts'] ) && is_array( $raw['posts'] ) ? $raw['posts'] : array() as $i => $p ) {
			$p   = is_array( $p ) ? $p : array();
			$ref = isset( $p['ref'] ) ? self::slug( $p['ref'] ) : '';
			$t   = isset( $p['title'] ) ? self::str( $p['title'], 120 ) : '';
			$cat = isset( $p['category'] ) ? self::slug( $p['category'] ) : '';
			$c   = isset( $p['content'] ) && is_string( $p['content'] ) ? trim( $p['content'] ) : '';
			if ( '' === $ref || isset( $seen[ $ref ] ) || '' === $t || '' === $c || ! isset( $out['categories'][ $cat ] ) ) {
				$out['skipped'][] = 'posts[' . $i . ']' . ( '' !== $ref ? ' ' . $ref : '' );
				continue;
			}
			$seen[ $ref ]   = true;
			$out['posts'][] = array( 'ref' => $ref, 'title' => $t, 'category' => $cat, 'excerpt' => isset( $p['excerpt'] ) ? self::str( $p['excerpt'], 300 ) : '', 'content' => $c );
		}
		return $out;
	}

	/**
	 * Are sample businesses shown on the public portal? Pure.
	 *
	 * @param string $mode      'auto' | 'off'.
	 * @param int    $seeded    demo members in the database.
	 * @param int    $real      real public (V4) businesses.
	 * @param int    $threshold real businesses at which demo hides itself.
	 */
	public static function visible( $mode, $seeded, $real, $threshold ) {
		return 'off' !== $mode && (int) $seeded > 0 && (int) $real < max( 1, (int) $threshold );
	}

	/** Why the demo is (not) shown, one sentence for the owner. Pure. */
	public static function reason( $mode, $seeded, $real, $threshold ) {
		if ( ! $seeded ) {
			return 'Chưa nhập cơ sở mẫu.';
		}
		if ( 'off' === $mode ) {
			return 'Chủ Hub đã tắt dữ liệu mẫu.';
		}
		if ( $real >= $threshold ) {
			return 'Đã tự ẩn: Hub có ' . (int) $real . ' cơ sở thật công khai (ngưỡng ' . (int) $threshold . ').';
		}
		return 'Đang hiện công khai, có nhãn "Mẫu": ' . (int) $real . '/' . (int) $threshold . ' cơ sở thật — đủ ngưỡng thì tự ẩn.';
	}

	/** Replace {map} {join} {benefits} {place:<ref>} in post content. Pure. */
	public static function fill_links( $content, array $urls, array $places ) {
		$content = strtr( (string) $content, $urls );
		return preg_replace_callback(
			'/\{place:([a-z0-9-]+)\}/',
			function ( $mm ) use ( $places, $urls ) {
				return isset( $places[ $mm[1] ] ) ? $places[ $mm[1] ] : ( isset( $urls['{map}'] ) ? $urls['{map}'] : '#' );
			},
			$content
		);
	}

	private static function slug( $s ) {
		return is_scalar( $s ) ? strtolower( preg_replace( '/[^a-z0-9-]/i', '', (string) $s ) ) : '';
	}

	private static function str( $v, $max ) {
		if ( ! is_scalar( $v ) ) {
			return '';
		}
		$v = trim( preg_replace( '/\s+/u', ' ', strip_tags( (string) $v ) ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $v, 0, $max, 'UTF-8' ) : substr( $v, 0, $max );
	}

	/* ===================== WordPress ===================== */

	public static function pack() {
		if ( null === self::$pack ) {
			self::$pack = self::clean_pack( apply_filters( self::FILTER, array() ), OHCL_Industry::current() );
		}
		return self::$pack;
	}

	public static function reset() {
		self::$pack = null;
	}

	public static function mode() {
		return 'off' === get_option( self::MODE_OPTION, 'auto' ) ? 'off' : 'auto';
	}

	public static function set_mode( $mode ) {
		update_option( self::MODE_OPTION, 'off' === $mode ? 'off' : 'auto', false );
		OHCL_Members::flush_public();
	}

	/** Demo members in the database: ref => post id. */
	public static function member_ids() {
		$out = array();
		foreach ( get_posts( array( 'post_type' => OHCL_Members::CPT, 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'numberposts' => 1000, 'meta_key' => self::MEMBER_META, 'no_found_rows' => true ) ) as $p ) {
			if ( '1' === (string) get_post_meta( $p->ID, self::MEMBER_META, true ) ) {
				$ref         = (string) get_post_meta( $p->ID, self::REF_META, true );
				$out[ '' !== $ref ? $ref : 'id' . $p->ID ] = (int) $p->ID;
			}
		}
		return $out;
	}

	/** Seed the sample businesses (idempotent by ref). @return array{added:int, kept:int} */
	public static function seed_members() {
		$have = self::member_ids();
		$ind  = OHCL_Industry::current();
		$meta = array();
		foreach ( $ind['member_fields'] as $f ) {
			$meta[ $f['key'] ] = $f['meta'];
		}
		$res = array( 'added' => 0, 'kept' => 0 );
		$now = gmdate( 'Y-m-d H:i:s' );
		foreach ( self::pack()['members'] as $m ) {
			if ( isset( $have[ $m['ref'] ] ) ) {
				$res['kept']++;
				continue;
			}
			$id = wp_insert_post( array( 'post_type' => OHCL_Members::CPT, 'post_status' => 'publish', 'post_title' => $m['title'] ) );
			if ( ! $id || is_wp_error( $id ) ) {
				continue;
			}
			$id = (int) $id;
			update_post_meta( $id, self::MEMBER_META, '1' );
			update_post_meta( $id, self::REF_META, $m['ref'] );
			update_post_meta( $id, '_bzm_status', 'active' );
			update_post_meta( $id, '_bzm_public_consent', $now );
			update_post_meta( $id, '_bzm_joined_at', $now );
			update_post_meta( $id, '_bzm_source', 'demo' );
			foreach ( array( 'segment', 'province', 'address', 'hours', 'lat', 'lng' ) as $k ) {
				update_post_meta( $id, '_bzm_' . $k, $m[ $k ] );
			}
			foreach ( $m['fields'] as $k => $v ) {
				update_post_meta( $id, $meta[ $k ], $v );
			}
			$res['added']++;
		}
		OHCL_Members::flush_public();
		return $res;
	}

	/** Delete every demo member (never a real one). @return int */
	public static function delete_members() {
		$n = 0;
		foreach ( self::member_ids() as $id ) {
			if ( '1' === (string) get_post_meta( $id, self::MEMBER_META, true ) && wp_delete_post( $id, true ) ) {
				$n++;
			}
		}
		OHCL_Members::flush_public();
		return $n;
	}

	/** Demo posts: id => post object. */
	public static function posts() {
		$out = array();
		foreach ( get_posts( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'numberposts' => 500, 'meta_key' => self::POST_META, 'no_found_rows' => true ) ) as $p ) {
			if ( '1' === (string) get_post_meta( $p->ID, self::POST_META, true ) ) {
				$out[ (int) $p->ID ] = $p;
			}
		}
		return $out;
	}

	/** Ensure the pack's categories exist. @return array slug => term id */
	public static function ensure_categories() {
		$ids = array();
		foreach ( self::pack()['categories'] as $slug => $name ) {
			$t = term_exists( $slug, 'category' );
			if ( ! $t ) {
				$t = wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
			}
			if ( is_array( $t ) && ! empty( $t['term_id'] ) ) {
				$ids[ $slug ] = (int) $t['term_id'];
			} elseif ( is_numeric( $t ) ) {
				$ids[ $slug ] = (int) $t;
			}
		}
		return $ids;
	}

	/** Seed the sample posts (idempotent by ref), published by default (CM-Q3). @return array{added:int, kept:int} */
	public static function seed_posts( $status = 'publish' ) {
		$status = 'draft' === $status ? 'draft' : 'publish';
		$have   = array();
		foreach ( self::posts() as $p ) {
			$have[ (string) get_post_meta( $p->ID, self::REF_META, true ) ] = true;
		}
		$cats   = self::ensure_categories();
		$places = array();
		$titles = array();
		foreach ( self::pack()['members'] as $m ) {
			$titles[ $m['ref'] ] = $m['title'];
		}
		foreach ( self::member_ids() as $ref => $id ) {
			$places[ $ref ] = OHCL_Front::place_url( $id, isset( $titles[ $ref ] ) ? $titles[ $ref ] : '' );
		}
		$urls = array( '{map}' => OHCL_Front::url( 'map' ), '{join}' => OHCL_Front::url( 'join' ), '{benefits}' => OHCL_Front::url( 'benefits' ) );
		$hub  = OHCL_Industry::current()['name'];
		$res  = array( 'added' => 0, 'kept' => 0 );
		foreach ( self::pack()['posts'] as $p ) {
			if ( isset( $have[ $p['ref'] ] ) ) {
				$res['kept']++;
				continue;
			}
			$body = self::fill_links( $p['content'], $urls, $places ) . "\n<p class=\"ohcl-demo-note\"><em>Bài mẫu của " . esc_html( $hub ) . ' để xem thử cổng. Cơ sở có chữ "(mẫu)" không phải cơ sở có thật.</em></p>';
			$id   = wp_insert_post(
				array(
					'post_type'     => 'post',
					'post_status'   => $status,
					'post_title'    => $p['title'],
					'post_excerpt'  => $p['excerpt'],
					'post_content'  => function_exists( 'wp_kses_post' ) ? wp_kses_post( $body ) : $body,
					'post_category' => isset( $cats[ $p['category'] ] ) ? array( $cats[ $p['category'] ] ) : array(),
					'meta_input'    => array( self::POST_META => '1', self::REF_META => $p['ref'] ),
				)
			);
			if ( $id && ! is_wp_error( $id ) ) {
				$res['added']++;
			}
		}
		return $res;
	}

	/** Publish or draft every demo post. @return int changed */
	public static function set_posts_status( $status ) {
		$status = 'draft' === $status ? 'draft' : 'publish';
		$n      = 0;
		foreach ( self::posts() as $id => $p ) {
			if ( $p->post_status !== $status && wp_update_post( array( 'ID' => $id, 'post_status' => $status ) ) ) {
				$n++;
			}
		}
		return $n;
	}

	/** Delete every demo post (never a real one). @return int */
	public static function delete_posts() {
		$n = 0;
		foreach ( self::posts() as $id => $p ) {
			if ( wp_delete_post( $id, true ) ) {
				$n++;
			}
		}
		return $n;
	}

	/** Is the demo shown right now, given the real public count. */
	public static function visible_now( $real ) {
		return self::visible( self::mode(), count( self::member_ids() ), $real, self::pack()['threshold'] );
	}

	/** Owner status: pack counts, database counts, shown or not and why. */
	public static function status( $real ) {
		$pk    = self::pack();
		$seed  = count( self::member_ids() );
		$posts = array( 'publish' => 0, 'draft' => 0, 'other' => 0 );
		foreach ( self::posts() as $p ) {
			$k = isset( $posts[ $p->post_status ] ) ? $p->post_status : 'other';
			$posts[ $k ]++;
		}
		return array(
			'pack_members' => count( $pk['members'] ),
			'pack_posts'   => count( $pk['posts'] ),
			'skipped'      => $pk['skipped'],
			'threshold'    => $pk['threshold'],
			'seeded'       => $seed,
			'posts'        => $posts,
			'mode'         => self::mode(),
			'real'         => (int) $real,
			'visible'      => self::visible( self::mode(), $seed, $real, $pk['threshold'] ),
			'reason'       => self::reason( self::mode(), $seed, $real, $pk['threshold'] ),
		);
	}
}
