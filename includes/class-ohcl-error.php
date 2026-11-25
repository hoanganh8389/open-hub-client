<?php
/**
 * One error shape for every open-hub-client failure: code · message · hint · help_code (R-ERROR-UX).
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 05:38 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1 — lỗi bốn trường cho client.

defined( 'ABSPATH' ) || exit;

final class OHCL_Error {

	/**
	 * @param string $code      machine code, also the help_code unless given.
	 * @param string $message   what happened, Vietnamese, ≤ 120 chars.
	 * @param string $hint      what to do next, starts with a verb.
	 * @param int    $status    HTTP status when shown through REST.
	 * @param string $help_code key in the help catalog.
	 * @param array  $extra     extra data (never secrets).
	 * @return WP_Error
	 */
	public static function make( $code, $message, $hint, $status = 400, $help_code = '', array $extra = array() ) {
		return new WP_Error(
			$code,
			$message,
			array_merge(
				$extra,
				array(
					'status'    => (int) $status,
					'hint'      => $hint,
					'help_code' => $help_code ? $help_code : $code,
				)
			)
		);
	}
}
