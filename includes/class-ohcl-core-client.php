<?php
/**
 * The ONLY door from a Hub site to open-hub-core (R-HUB-14 ranh giới ②).
 *
 * Talks to `open-hub-core/v1` with `X-OHC-Hub: <slug>` + `Authorization: Bearer ohs_…` (R-CORE-7).
 * Industry plugins never call this class's transport or the core directly; they use client screens.
 *
 * Configuration (Hub owner, module 14):
 *   option ohcl_core = { base_url, hub }   (hub defaults to the industry id)
 *   secret: constant OHCL_CORE_SECRET in wp-config.php (preferred) or option ohcl_core_secret (not autoloaded)
 *
 * Failure is never fatal: an unreachable / unconfigured core returns a WP_Error with `_degraded`
 * so screens show "—" with the reason (R-GW-8, R-HUB-6). The secret is never put in an error,
 * a log line or a response.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 05:38 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1 — một cửa gọi open-hub-core/v1: cấu hình, xác thực Hub, map lỗi bốn trường, degrade khi core vắng.

defined( 'ABSPATH' ) || exit;

final class OHCL_Core_Client {

	const OPTION        = 'ohcl_core';
	const SECRET_OPTION = 'ohcl_core_secret';
	const NS            = '/wp-json/open-hub-core/v1';
	const TIMEOUT       = 8;

	/**
	 * Transport override for tests: fn( $method, $url, array $headers, $body ) => array{status,body} | WP_Error.
	 *
	 * @var callable|null
	 */
	public static $transport = null;

	/** @var array|null config override for tests */
	public static $config = null;

	/** @return array{base_url:string, hub:string, secret:string} */
	private static function config() {
		if ( is_array( self::$config ) ) {
			return self::$config + array( 'base_url' => '', 'hub' => '', 'secret' => '' );
		}
		$o      = get_option( self::OPTION );
		$o      = is_array( $o ) ? $o : array();
		$secret = defined( 'OHCL_CORE_SECRET' ) ? (string) OHCL_CORE_SECRET : (string) get_option( self::SECRET_OPTION, '' );
		$hub    = isset( $o['hub'] ) && '' !== $o['hub'] ? (string) $o['hub'] : '';
		if ( '' === $hub ) {
			$ind = OHCL_Industry::current();
			$hub = $ind['id'];
		}
		return array(
			'base_url' => isset( $o['base_url'] ) ? rtrim( (string) $o['base_url'], '/' ) : '',
			'hub'      => $hub,
			'secret'   => $secret,
		);
	}

	/** Pure check of a config, also used by the settings screen. @return true|WP_Error */
	public static function check_config( array $c ) {
		if ( '' === $c['base_url'] || ! preg_match( '#^https://[a-z0-9.-]+(:\d+)?$#i', $c['base_url'] ) && ! preg_match( '#^http://(localhost|127\.0\.0\.1)(:\d+)?$#i', $c['base_url'] ) ) {
			return OHCL_Error::make( 'ohcl_core_not_configured', 'Chưa có địa chỉ lõi Bizcity (open-hub-core) hợp lệ.', 'Mở Cài đặt & phân quyền → Kết nối lõi và nhập địa chỉ https của bizcity.vn.', 200, '', array( '_degraded' => true ) );
		}
		if ( ! preg_match( '/^[a-z0-9-]{2,20}$/', $c['hub'] ) ) {
			return OHCL_Error::make( 'ohcl_core_not_configured', 'Chưa có mã Hub hợp lệ để gọi lõi Bizcity.', 'Kiểm tra mã Hub trong plugin ngành hoặc ở Cài đặt → Kết nối lõi.', 200, '', array( '_degraded' => true ) );
		}
		if ( ! preg_match( '/^ohs_[a-f0-9]{32}$/', $c['secret'] ) ) {
			return OHCL_Error::make( 'ohcl_core_not_configured', 'Chưa có bí mật Hub hợp lệ.', 'Xin bí mật Hub từ Bizcity rồi đặt OHCL_CORE_SECRET trong wp-config.php.', 200, '', array( '_degraded' => true ) );
		}
		return true;
	}

	/** Public registry of Hubs (no auth). @return array|WP_Error */
	public static function hubs() {
		return self::call( 'GET', '/hubs', null, false );
	}

	/**
	 * Issue a trial key for a member site. The plain key comes back ONCE; the caller shows it and
	 * stores only key_id / key_prefix.
	 *
	 * @return array|WP_Error {key_id, key, key_prefix, plan_code, plan_until, …}
	 */
	public static function issue_key( $label, $site_url = '', $coach_ref = '' ) {
		$label = trim( (string) $label );
		if ( '' === $label ) {
			return OHCL_Error::make( 'ohcl_key_label_required', 'Cần tên cơ sở để cấp khoá.', 'Nhập tên cơ sở hội viên rồi bấm Cấp khoá lại.', 400 );
		}
		return self::call( 'POST', '/hub/keys', array( 'label' => $label, 'site_url' => (string) $site_url, 'coach_ref' => (string) $coach_ref ), true );
	}

	/** Keys of this Hub, 50 per page. @return array|WP_Error */
	public static function list_keys( $page = 1 ) {
		return self::call( 'GET', '/hub/keys?page=' . max( 1, (int) $page ), null, true );
	}

	/** Revoke one key of this Hub. @return array|WP_Error */
	public static function revoke_key( $key_id ) {
		return self::call( 'POST', '/hub/keys/' . (int) $key_id . '/revoke', array(), true );
	}

	/**
	 * Connect this Hub site with a pairing code from bizcity.vn › Sổ Hub (no person copies the secret).
	 * The core rotates the Hub secret and returns it once, server to server; we store base URL, the Hub
	 * slug the core answered and the secret (option, not autoloaded). Refused when OHCL_CORE_SECRET is
	 * set in wp-config.php, because the constant would win over the new secret.
	 *
	 * @return array|WP_Error { hub: {slug, name, industry}, mismatch: bool }
	 */
	public static function pair( $base_url, $code ) {
		// [2026-11-25 06:17 PM Johnny Chu - Chu Hoàng Anh] OHCL-PAIR — kết nối lõi bằng mã ghép nối; bí mật đi thẳng máy chủ → máy chủ, không qua người.
		$base_url = rtrim( trim( (string) $base_url ), '/' );
		$check    = self::check_config( array( 'base_url' => $base_url, 'hub' => 'xx', 'secret' => 'ohs_' . str_repeat( '0', 32 ) ) );
		if ( true !== $check ) {
			return $check;
		}
		$code = strtoupper( preg_replace( '/[\s-]+/', '', (string) $code ) );
		if ( ! preg_match( '/^[A-HJ-NP-Z2-9]{8}$/', $code ) ) {
			return OHCL_Error::make( 'ohcl_pair_code_format', 'Mã ghép nối gồm 8 ký tự, dạng ABCD-EFGH.', 'Dán đúng mã hiện ở bizcity.vn › Sổ Hub.', 400 );
		}
		if ( defined( 'OHCL_CORE_SECRET' ) ) {
			return OHCL_Error::make( 'ohcl_pair_locked_by_constant', 'Site đang khoá bí mật Hub bằng OHCL_CORE_SECRET trong wp-config.php.', 'Xoá dòng OHCL_CORE_SECRET khỏi wp-config.php rồi kết nối lại bằng mã.', 409 );
		}
		$prev          = self::$config;
		self::$config  = array( 'base_url' => $base_url, 'hub' => 'xx', 'secret' => '' );
		$res           = self::call( 'POST', '/hub/pair', array( 'code' => $code ), false );
		self::$config  = $prev;
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$slug   = isset( $res['hub']['slug'] ) ? (string) $res['hub']['slug'] : '';
		$secret = isset( $res['secret'] ) ? (string) $res['secret'] : '';
		if ( ! preg_match( '/^[a-z0-9-]{2,20}$/', $slug ) || ! preg_match( '/^ohs_[a-f0-9]{32}$/', $secret ) ) {
			return OHCL_Error::make( 'ohcl_core_bad_response', 'Lõi Bizcity trả về kết quả ghép nối không đọc được.', 'Tạo mã mới ở bizcity.vn rồi thử lại; nếu còn lỗi, báo Bizcity.', 200, '', array( '_degraded' => true ) );
		}
		if ( ! is_array( self::$config ) ) {
			update_option( self::OPTION, array( 'base_url' => $base_url, 'hub' => $slug ), false );
			update_option( self::SECRET_OPTION, $secret, false );
		} else {
			self::$config = array( 'base_url' => $base_url, 'hub' => $slug, 'secret' => $secret ); // test mode
		}
		$ind = OHCL_Industry::current();
		return array(
			'hub'      => array( 'slug' => $slug, 'name' => isset( $res['hub']['name'] ) ? (string) $res['hub']['name'] : $slug, 'industry' => isset( $res['hub']['industry'] ) ? (string) $res['hub']['industry'] : '' ),
			'mismatch' => '' !== $ind['id'] && $ind['id'] !== $slug,
		);
	}

	/** Connection state for the settings card (never the secret). */
	public static function state() {
		$c = self::config();
		return array( 'base_url' => $c['base_url'], 'hub' => $c['hub'], 'has_secret' => '' !== $c['secret'], 'by_constant' => defined( 'OHCL_CORE_SECRET' ) );
	}

	/**
	 * Connection check for the settings screen: config, reachability, Hub listed, auth accepted.
	 *
	 * @return array{ok:bool, steps:array[]}
	 */
	public static function ping() {
		$steps = array();
		$c     = self::config();
		$cfg   = self::check_config( $c );
		$steps[] = array( 'step' => 'config', 'ok' => true === $cfg, 'note' => true === $cfg ? 'đủ địa chỉ, mã Hub, bí mật' : $cfg->get_error_message() );
		if ( true !== $cfg ) {
			return array( 'ok' => false, 'steps' => $steps );
		}
		$hubs    = self::hubs();
		$listed  = false;
		if ( ! is_wp_error( $hubs ) && isset( $hubs['hubs'] ) && is_array( $hubs['hubs'] ) ) {
			foreach ( $hubs['hubs'] as $h ) {
				$listed = $listed || ( isset( $h['slug'] ) && $h['slug'] === $c['hub'] );
			}
		}
		$steps[] = array( 'step' => 'reach', 'ok' => ! is_wp_error( $hubs ), 'note' => is_wp_error( $hubs ) ? $hubs->get_error_message() : 'lõi trả lời' );
		$steps[] = array( 'step' => 'hub_listed', 'ok' => $listed, 'note' => $listed ? 'Hub có trong sổ' : 'Hub chưa có hoặc đang tạm dừng ở lõi' );
		$auth    = is_wp_error( $hubs ) ? $hubs : self::list_keys( 1 );
		$steps[] = array( 'step' => 'auth', 'ok' => ! is_wp_error( $auth ), 'note' => is_wp_error( $auth ) ? $auth->get_error_message() : 'bí mật Hub được nhận' );
		$ok      = true;
		foreach ( $steps as $s ) {
			$ok = $ok && $s['ok'];
		}
		return array( 'ok' => $ok, 'steps' => $steps );
	}

	/**
	 * @param string     $method GET|POST
	 * @param string     $path   below NS
	 * @param array|null $body   JSON body for POST
	 * @param bool       $auth   send Hub credentials
	 * @return array|WP_Error decoded JSON object
	 */
	private static function call( $method, $path, $body, $auth ) {
		$c   = self::config();
		$cfg = self::check_config( $auth ? $c : array( 'base_url' => $c['base_url'], 'hub' => 'xx', 'secret' => 'ohs_' . str_repeat( '0', 32 ) ) );
		if ( true !== $cfg ) {
			return $cfg;
		}
		$headers = array( 'Accept' => 'application/json' );
		if ( null !== $body ) {
			$headers['Content-Type'] = 'application/json';
		}
		if ( $auth ) {
			$headers['X-OHC-Hub']     = $c['hub'];
			$headers['Authorization'] = 'Bearer ' . $c['secret'];
		}
		$url  = $c['base_url'] . self::NS . $path;
		$json = null === $body ? null : wp_json_encode( $body );

		if ( is_callable( self::$transport ) ) {
			$res = call_user_func( self::$transport, $method, $url, $headers, $json );
		} else {
			$r = wp_remote_request( $url, array( 'method' => $method, 'headers' => $headers, 'body' => $json, 'timeout' => self::TIMEOUT, 'redirection' => 0 ) );
			$res = is_wp_error( $r ) ? $r : array( 'status' => (int) wp_remote_retrieve_response_code( $r ), 'body' => (string) wp_remote_retrieve_body( $r ) );
		}

		if ( is_wp_error( $res ) ) {
			return OHCL_Error::make( 'ohcl_core_unreachable', 'Chưa liên lạc được lõi Bizcity.', 'Thử lại sau ít phút; nếu còn lỗi, kiểm tra Cài đặt → Kết nối lõi.', 200, '', array( '_degraded' => true ) );
		}
		$data = json_decode( (string) $res['body'], true );
		if ( ! is_array( $data ) ) {
			return OHCL_Error::make( 'ohcl_core_bad_response', 'Lõi Bizcity trả về dữ liệu không đọc được (HTTP ' . (int) $res['status'] . ').', 'Thử lại sau; nếu còn lỗi, báo Bizcity kèm mã lỗi này.', 200, '', array( '_degraded' => true ) );
		}
		if ( $res['status'] >= 200 && $res['status'] < 300 ) {
			return $data;
		}
		// Core speaks R-ERROR-UX: {code, message, data{status, hint, help_code}}. Pass it through.
		$d = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : array();
		return OHCL_Error::make(
			isset( $data['code'] ) ? (string) $data['code'] : 'ohcl_core_http_' . (int) $res['status'],
			isset( $data['message'] ) ? (string) $data['message'] : 'Lõi Bizcity từ chối yêu cầu (HTTP ' . (int) $res['status'] . ').',
			isset( $d['hint'] ) ? (string) $d['hint'] : 'Kiểm tra lại thao tác hoặc Cài đặt → Kết nối lõi.',
			(int) $res['status'],
			isset( $d['help_code'] ) ? (string) $d['help_code'] : ''
		);
	}
}
