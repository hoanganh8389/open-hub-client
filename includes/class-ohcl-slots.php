<?php
/**
 * Extension slots and read-only events (OPEN-HUB-CLIENT §10.3, §10.4).
 *
 * Slots: an industry plugin adds items to a named filter to DISPLAY more. Each item is
 * { id, label, render } where render is a callable returning HTML. A render that throws or is not
 * callable is hidden and logged — the core screen keeps working.
 *
 * Events: fired AFTER the core has written; listeners cannot change the core result, and a listener
 * that throws never breaks the core flow.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 05:38 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1 — ô mở rộng chỉ hiển thị + sự kiện chỉ đọc, cô lập lỗi của plugin ngành.

defined( 'ABSPATH' ) || exit;

final class OHCL_Slots {

	const SLOTS = array( 'member360_tabs', 'dashboard_cards', 'myhub_cards', 'public_profile_blocks', 'persona_pages' );

	const EVENTS = array( 'member_stage_changed', 'member_verified', 'key_issued', 'cert_issued', 'cert_revoked', 'pack_granted', 'coach_activated', 'hub_activated' ); // [2026-11-26 10:39 PM Johnny Chu - Chu Hoàng Anh] OHCL-H5 — hub_activated: đủ 4 bước "Bắt đầu". // [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — thêm cert_revoked (chỉ đọc)

	/** Keys that must never travel in an event payload. */
	const SECRET_KEYS = array( 'key', 'plain_key', 'secret', 'key_hash', 'password', 'token' );

	/** @var string[] last errors, for the settings report and tests */
	public static $errors = array();

	/**
	 * Items registered for a slot, validated, sorted by id.
	 *
	 * @param string $slot    one of SLOTS.
	 * @param mixed  $context read-only context passed to the filter.
	 * @return array[] { id, label, render }
	 */
	public static function items( $slot, $context = null ) {
		if ( ! in_array( $slot, self::SLOTS, true ) ) {
			return array();
		}
		try {
			$raw = apply_filters( 'ohcl_' . $slot, array(), $context );
		} catch ( Throwable $e ) {
			self::fail( $slot, 'filter', $e->getMessage() );
			return array();
		}
		$out = array();
		foreach ( is_array( $raw ) ? $raw : array() as $it ) {
			$id = is_array( $it ) && isset( $it['id'] ) ? (string) $it['id'] : '';
			if ( ! preg_match( '/^[a-z0-9-]{2,40}$/', $id ) || ! isset( $it['render'] ) || ! is_callable( $it['render'] ) ) {
				self::fail( $slot, $id ? $id : '?', 'cần id [a-z0-9-] và render gọi được' );
				continue;
			}
			$out[ $id ] = array(
				'id'     => $id,
				'label'  => isset( $it['label'] ) ? (string) $it['label'] : $id,
				'render' => $it['render'],
			);
		}
		ksort( $out );
		return array_values( $out );
	}

	/**
	 * Render one item safely. Returns '' when the item fails.
	 *
	 * @return string HTML
	 */
	public static function render( $slot, array $item, $context = null ) {
		$level = ob_get_level();
		ob_start();
		try {
			$ret  = call_user_func( $item['render'], $context );
			$echo = (string) ob_get_clean();
			return $echo . ( is_string( $ret ) ? $ret : '' );
		} catch ( Throwable $e ) {
			while ( ob_get_level() > $level ) {
				ob_end_clean();
			}
			self::fail( $slot, $item['id'], $e->getMessage() );
			return '';
		}
	}

	/**
	 * Fire a read-only event. Secrets are stripped from the payload; listener errors are swallowed.
	 *
	 * @param string $event   one of EVENTS.
	 * @param array  $payload
	 */
	public static function emit( $event, array $payload ) {
		if ( ! in_array( $event, self::EVENTS, true ) ) {
			return;
		}
		foreach ( self::SECRET_KEYS as $k ) {
			unset( $payload[ $k ] );
		}
		try {
			do_action( 'ohcl_' . $event, $payload );
		} catch ( Throwable $e ) {
			self::fail( 'event:' . $event, 'listener', $e->getMessage() );
		}
	}

	private static function fail( $where, $id, $msg ) {
		$line           = $where . ' · ' . $id . ' · ' . substr( (string) $msg, 0, 160 );
		self::$errors[] = $line;
		if ( function_exists( 'error_log' ) && ( ! defined( 'OHCL_TESTING' ) ) ) {
			error_log( '[open-hub-client] ô mở rộng lỗi: ' . $line ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}
	}
}
