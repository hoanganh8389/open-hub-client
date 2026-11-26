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
	 * Demo data from the industry plugin (OPEN-HUB-CLIENT §12a). Only records with the demo mark are touched.
	 *
	 * <action>
	 * : status | seed (businesses + posts) | remove (businesses + posts) | posts | mode
	 *
	 * [<arg>]
	 * : for posts: publish | draft | delete. For mode: auto | off.
	 *
	 * [--members-only]
	 * : seed / remove only the sample businesses.
	 *
	 * [--yes]
	 * : confirm a delete.
	 */
	public function demo( $args, $assoc ) {
		// [2026-11-26 09:59 AM Johnny Chu - Chu Hoàng Anh] OHCL-DEMO — wp ohcl demo status|seed|remove|posts|mode.
		$act  = isset( $args[0] ) ? $args[0] : 'status';
		$arg  = isset( $args[1] ) ? $args[1] : '';
		$only = ! empty( $assoc['members-only'] );
		$pub  = OHCL_Members::public_list();
		if ( 'seed' === $act ) {
			$m = OHCL_Demo::seed_members();
			WP_CLI::line( 'Cơ sở mẫu: thêm ' . $m['added'] . ', đã có ' . $m['kept'] );
			if ( ! $only ) {
				$p = OHCL_Demo::seed_posts( 'draft' === $arg ? 'draft' : 'publish' );
				WP_CLI::line( 'Bài mẫu: thêm ' . $p['added'] . ', đã có ' . $p['kept'] );
			}
		} elseif ( 'remove' === $act || ( 'posts' === $act && 'delete' === $arg ) ) {
			if ( empty( $assoc['yes'] ) ) {
				WP_CLI::error( 'Thêm --yes để xác nhận xoá dữ liệu mẫu.' );
			}
			if ( 'remove' === $act ) {
				WP_CLI::line( 'Đã xoá ' . OHCL_Demo::delete_members() . ' cơ sở mẫu.' );
			}
			if ( ! $only ) {
				WP_CLI::line( 'Đã xoá ' . OHCL_Demo::delete_posts() . ' bài mẫu.' );
			}
		} elseif ( 'posts' === $act && in_array( $arg, array( 'publish', 'draft' ), true ) ) {
			WP_CLI::line( 'Đã đổi ' . OHCL_Demo::set_posts_status( $arg ) . ' bài mẫu sang ' . $arg . '.' );
		} elseif ( 'mode' === $act && in_array( $arg, array( 'auto', 'off' ), true ) ) {
			OHCL_Demo::set_mode( $arg );
		} elseif ( 'status' !== $act ) {
			WP_CLI::error( 'Dùng: wp ohcl demo status | seed [draft] | remove --yes | posts publish|draft|delete | mode auto|off' );
		}
		$st = OHCL_Demo::status( isset( $pub['real'] ) ? $pub['real'] : 0 );
		WP_CLI::line( sprintf( 'Dữ liệu ngành: %d cơ sở mẫu, %d bài mẫu · Đã nhập: %d cơ sở · Bài: %d đăng, %d nháp', $st['pack_members'], $st['pack_posts'], $st['seeded'], $st['posts']['publish'], $st['posts']['draft'] ) );
		foreach ( $st['skipped'] as $s ) {
			WP_CLI::line( 'SKIP  sai dạng: ' . $s );
		}
		WP_CLI::success( $st['reason'] );
	}

	/**
	 * Coach commissions (OPEN-HUB-CLIENT §7).
	 *
	 * <action>
	 * : sync | payouts | reverse
	 *
	 * [<key>]
	 * : for reverse: the line key, e.g. member:123.
	 *
	 * [--reason=<text>]
	 * : reason written on the reversal line.
	 */
	public function affiliate( $args, $assoc ) {
		// [2026-11-26 11:15 AM Johnny Chu - Chu Hoàng Anh] OHCL-D3A — wp ohcl affiliate sync|payouts|reverse.
		$act = isset( $args[0] ) ? $args[0] : 'payouts';
		if ( 'sync' === $act ) {
			$s = OHCL_Affiliate::sync();
			if ( $s['skipped_core'] ) {
				WP_CLI::warning( 'Chưa đọc được khoá từ lõi: không ghi nhận được.' );
			}
			WP_CLI::success( 'Ghi nhận ' . $s['added'] . ' dòng mới.' );
		} elseif ( 'reverse' === $act ) {
			$r = OHCL_Affiliate::reverse( isset( $args[1] ) ? $args[1] : '', isset( $assoc['reason'] ) ? $assoc['reason'] : '' );
			is_wp_error( $r ) ? WP_CLI::error( $r->get_error_message() ) : WP_CLI::success( 'Đã thêm dòng đảo.' );
		} else {
			$rows = array();
			foreach ( OHCL_Affiliate::payouts() as $p ) {
				$rows[] = array( 'id' => $p['id'], 'month' => $p['month'], 'coach' => $p['coach'], 'lines' => count( $p['lines'] ), 'total' => $p['total'], 'status' => $p['status'] );
			}
			WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'month', 'coach', 'lines', 'total', 'status' ) );
		}
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
