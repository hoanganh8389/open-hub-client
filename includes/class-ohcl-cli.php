<?php
/**
 * WP-CLI `wp ohcl …` for Hub operators and industry developers.
 *
 *   wp ohcl validate-industry [--format=json]   check the industry data (contract @2); exit 1 on errors
 *   wp ohcl modules                             the 14 modules, on / off and why
 *   wp ohcl module <id> <on|off>                switch an optional module (Hub owner's choice)
 *   wp ohcl core-ping                           check the connection to open-hub-core (never prints the secret)
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 05:38 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1 — CLI: kiểm ngành, module, kiểm kết nối lõi.

defined( 'ABSPATH' ) || exit;

final class OHCL_Cli {

	/**
	 * Check the industry data returned by the active wp-<industry>-hub plugin.
	 *
	 * [--format=<format>]
	 * : table or json. Default table.
	 *
	 * @subcommand validate-industry
	 */
	public function validate_industry( $args, $assoc ) {
		$raw = apply_filters( OHCL_Industry::FILTER, array() );
		$r   = OHCL_Industry::validate( $raw );
		if ( isset( $assoc['format'] ) && 'json' === $assoc['format'] ) {
			WP_CLI::line( wp_json_encode( array( 'ok' => $r['ok'], 'errors' => $r['errors'], 'warnings' => $r['warnings'], 'ignored' => $r['ignored'], 'id' => $r['config']['id'] ) ) );
		} else {
			foreach ( $r['errors'] as $e ) {
				WP_CLI::line( 'FAIL  ' . $e );
			}
			foreach ( $r['warnings'] as $w ) {
				WP_CLI::line( 'WARN  ' . $w );
			}
			foreach ( $r['ignored'] as $i ) {
				WP_CLI::line( 'SKIP  khoá lạ: ' . $i );
			}
			$c = $r['config'];
			WP_CLI::line( sprintf( 'Hub %s (%s) · %d phân khúc · %d trường hồ sơ · %d tiêu chuẩn · %d tài sản tri thức', $c['id'] ? $c['id'] : '—', $c['name'], count( $c['segments'] ), count( $c['member_fields'] ), count( $c['standards'] ), count( $c['knowledge_assets'] ) ) );
		}
		if ( ! $r['ok'] ) {
			WP_CLI::error( 'Dữ liệu ngành chưa đạt hợp đồng ' . OHCL_Industry::CONTRACT . '.' );
		}
		WP_CLI::success( 'Dữ liệu ngành đạt ' . OHCL_Industry::CONTRACT . ( $r['warnings'] ? ' (có ' . count( $r['warnings'] ) . ' cảnh báo).' : '.' ) );
	}

	/** List the 14 modules with on / off and the reason. */
	public function modules() {
		$rows = array();
		foreach ( OHCL_Modules::all() as $id => $m ) {
			$rows[] = array( 'id' => $id, 'module' => $m['label'], 'on' => $m['enabled'] ? 'bật' : 'tắt', 'source' => $m['source'] );
		}
		WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'module', 'on', 'source' ) );
	}

	/**
	 * Switch an optional module.
	 *
	 * <id>
	 * : module id 01…14.
	 *
	 * <state>
	 * : on or off.
	 */
	public function module( $args ) {
		$res = OHCL_Modules::set_enabled( str_pad( (string) $args[0], 2, '0', STR_PAD_LEFT ), 'on' === ( isset( $args[1] ) ? $args[1] : '' ) );
		if ( is_wp_error( $res ) ) {
			$d = $res->get_error_data();
			WP_CLI::error( $res->get_error_message() . ' ' . $d['hint'] );
		}
		WP_CLI::success( 'Đã lưu.' );
	}

	/**
	 * Check the connection to open-hub-core.
	 *
	 * @subcommand core-ping
	 */
	public function core_ping() {
		$r = OHCL_Core_Client::ping();
		foreach ( $r['steps'] as $s ) {
			WP_CLI::line( ( $s['ok'] ? 'PASS  ' : 'FAIL  ' ) . $s['step'] . ' — ' . $s['note'] );
		}
		$r['ok'] ? WP_CLI::success( 'Kết nối lõi đạt.' ) : WP_CLI::error( 'Kết nối lõi chưa đạt.' );
	}
}
