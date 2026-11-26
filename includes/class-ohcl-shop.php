<?php
/**
 * Products of a member's own WooCommerce shop on its public profile (OPEN-HUB-CLIENT §12a "đường B", CM-Q2).
 *
 * The Hub never sells on behalf of a member and never reads sales data (R-HUB): it reads the shop's PUBLIC
 * Store API (`/wp-json/wc/store/v1/products`, no key) only when the member ticked "đồng ý hiện sản phẩm",
 * shows at most 6 cards (image, name, price) and every link goes to the member's own shop.
 *
 *   - https only, through wp_safe_remote_get (refuses local / private addresses), 4 s timeout, 300 KB cap;
 *   - links kept only when they point to the same host as the shop address;
 *   - cached 6 hours (failures 30 minutes, so a dead shop does not slow every profile view).
 *
 * @package Open_Hub_Client
 */
// [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-SHOP — sản phẩm từ Woo của chính cơ sở (Store API công khai) trên trang hồ sơ, có đồng ý, đệm 6 giờ.

defined( 'ABSPATH' ) || exit;

final class OHCL_Shop {

	const LIMIT     = 6;
	const TTL_OK    = 21600; // 6 giờ
	const TTL_FAIL  = 1800;  // 30 phút
	const CACHE_PFX = 'ohcl_shop_';

	/* ===================== pure ===================== */

	/** Store API URL for a shop address, or '' when the address is not a plain https URL. Pure. */
	public static function endpoint( $shop_url, $limit = self::LIMIT ) {
		$u = is_string( $shop_url ) ? trim( $shop_url ) : '';
		if ( ! preg_match( '#^https://[a-z0-9.-]+\.[a-z]{2,}(:[0-9]{2,5})?(/[^\s<>"?\#]*)?$#i', $u ) ) {
			return '';
		}
		return rtrim( $u, '/' ) . '/wp-json/wc/store/v1/products?per_page=' . max( 1, min( 12, (int) $limit ) ) . '&orderby=popularity';
	}

	/** "45.000 ₫" from a Store API `prices` object. Pure. */
	public static function price( $prices ) {
		if ( ! is_array( $prices ) || ! isset( $prices['price'] ) || ! is_numeric( $prices['price'] ) ) {
			return '';
		}
		$minor = isset( $prices['currency_minor_unit'] ) ? max( 0, min( 4, (int) $prices['currency_minor_unit'] ) ) : 0;
		$val   = (float) $prices['price'] / pow( 10, $minor );
		$num   = number_format( $val, $val == floor( $val ) ? 0 : $minor, ',', '.' ); // phpcs:ignore Universal.Operators.StrictComparisons
		// Tiền tố / hậu tố giữ dấu cách của cửa hàng (" ₫"), chỉ bỏ thẻ và gộp khoảng trắng.
		$aff   = function ( $k ) use ( $prices ) { return isset( $prices[ $k ] ) ? preg_replace( '/s+/u', ' ', strip_tags( html_entity_decode( (string) $prices[ $k ], ENT_QUOTES, 'UTF-8' ) ) ) : ''; };
		$pre   = $aff( 'currency_prefix' );
		$suf   = $aff( 'currency_suffix' );
		if ( '' === trim( $pre . $suf ) ) {
			$suf = isset( $prices['currency_symbol'] ) ? ' ' . self::txt( $prices['currency_symbol'], 6 ) : '';
		}
		return trim( $pre . $num . $suf );
	}

	/**
	 * Cards from a Store API JSON body. Pure.
	 *
	 * @return array[] { name, url, price, image, sale }
	 */
	public static function parse( $body, $shop_url, $limit = self::LIMIT ) {
		$list = json_decode( (string) $body, true );
		$host = strtolower( (string) parse_url( (string) $shop_url, PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		if ( ! is_array( $list ) || '' === $host ) {
			return array();
		}
		$out = array();
		foreach ( $list as $p ) {
			if ( count( $out ) >= $limit ) {
				break;
			}
			if ( ! is_array( $p ) || empty( $p['name'] ) || empty( $p['permalink'] ) ) {
				continue;
			}
			$url = (string) $p['permalink'];
			if ( 0 !== strpos( $url, 'https://' ) || strtolower( (string) parse_url( $url, PHP_URL_HOST ) ) !== $host ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
				continue; // chỉ link về chính cửa hàng của cơ sở
			}
			$img = '';
			if ( ! empty( $p['images'][0] ) && is_array( $p['images'][0] ) ) {
				$i   = $p['images'][0];
				$img = ! empty( $i['thumbnail'] ) ? (string) $i['thumbnail'] : ( ! empty( $i['src'] ) ? (string) $i['src'] : '' );
				$img = 0 === strpos( $img, 'https://' ) ? $img : '';
			}
			$out[] = array(
				'name'  => self::txt( $p['name'], 90 ),
				'url'   => $url,
				'price' => isset( $p['prices'] ) ? self::price( $p['prices'] ) : '',
				'image' => $img,
				'sale'  => ! empty( $p['on_sale'] ),
			);
		}
		return $out;
	}

	private static function txt( $v, $max ) {
		$v = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( strip_tags( (string) $v ), ENT_QUOTES, 'UTF-8' ) ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $v, 0, $max, 'UTF-8' ) : substr( $v, 0, $max );
	}

	/* ===================== WordPress ===================== */

	/**
	 * Products of a shop, cached. Never throws; a failure is an empty list + an error code for the owner.
	 *
	 * @return array{items:array[], error:string}
	 */
	public static function products( $shop_url ) {
		$api = self::endpoint( $shop_url );
		if ( '' === $api ) {
			return array( 'items' => array(), 'error' => 'ohcl_shop_url_invalid' );
		}
		$key = self::CACHE_PFX . md5( $api );
		$c   = get_transient( $key );
		if ( is_array( $c ) ) {
			return $c;
		}
		$r   = wp_safe_remote_get( $api, array( 'timeout' => 4, 'redirection' => 2, 'limit_response_size' => 300000, 'headers' => array( 'Accept' => 'application/json' ) ) );
		$res = array( 'items' => array(), 'error' => '' );
		if ( is_wp_error( $r ) ) {
			$res['error'] = 'ohcl_shop_unreachable';
		} elseif ( 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
			$res['error'] = 'ohcl_shop_http_' . (int) wp_remote_retrieve_response_code( $r );
		} else {
			$res['items'] = self::parse( wp_remote_retrieve_body( $r ), $shop_url );
			if ( ! $res['items'] ) {
				$res['error'] = 'ohcl_shop_empty';
			}
		}
		set_transient( $key, $res, $res['items'] ? self::TTL_OK : self::TTL_FAIL );
		return $res;
	}

	public static function flush( $shop_url ) {
		$api = self::endpoint( $shop_url );
		if ( '' !== $api ) {
			delete_transient( self::CACHE_PFX . md5( $api ) );
		}
	}
}
