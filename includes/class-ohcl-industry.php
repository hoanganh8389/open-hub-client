<?php
/**
 * Industry contract bizcity_industry_hub@2 (OPEN-HUB-CLIENT §10).
 *
 * A wp-<industry>-hub plugin returns ONE data array through the filter `open_hub_client_industry`.
 * This class validates it (pure function, no WordPress), keeps the validated copy in a
 * non-autoloaded option, and re-validates only when the array changes.
 *
 *   - fatal problems (missing id / name / industry / accent, wrong contract) ⇒ the site runs on the
 *     neutral default config and the report says why;
 *   - a bad item (one field, one standard…) is dropped with a warning; the rest is kept;
 *   - unknown keys are ignored and listed.
 *
 * Nothing here talks to open-hub-core, the Cell, Zalo or an LLM (R-HUB-14, R-HUB-15).
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 05:38 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1 — hợp đồng ngành @2: kiểm dữ liệu, cấu hình mặc định khi lỗi, lưu bản đã kiểm.

defined( 'ABSPATH' ) || exit;

final class OHCL_Industry {

	const CONTRACT = 'bizcity_industry_hub@2';
	const FILTER   = 'open_hub_client_industry';
	const OPTION   = 'ohcl_industry';

	/** Term keys an industry may rename, with the neutral default label. */
	const TERMS = array(
		'member'   => 'hội viên',
		'business' => 'cơ sở',
		'product'  => 'sản phẩm',
		'coach'    => 'Coach',
		'standard' => 'tiêu chuẩn',
		'campaign' => 'chiến dịch',
		'course'   => 'khoá học',
		'event'    => 'sự kiện',
	);

	const FIELD_TYPES = array( 'text', 'select', 'multi', 'number', 'bool', 'url' );

	/** Member meta keys owned by the client core; an industry field may not reuse them. */
	const RESERVED_FIELDS = array( 'name', 'status', 'stage', 'key_id', 'coach', 'segment', 'lat', 'lng', 'address', 'phone', 'email', 'verified', 'level', 'consent' );

	const ASSET_TYPES = array( 'sop', 'template', 'ai_skill', 'guru', 'document' );

	/** Script / decorative fonts that break Vietnamese diacritics (R-HUB-13). */
	const FORBIDDEN_FONTS = '/caveat|dancing\s*script|pacifico|great\s*vibes|lobster|satisfy|sacramento|kaushan/i';

	const KNOWN_KEYS = array( 'contract', 'id', 'name', 'industry', 'leader', 'brand', 'modules', 'terms', 'segments', 'member_fields', 'standards', 'coach_skills', 'academy_tracks', 'campaign_templates', 'apps', 'trial_days', 'knowledge_assets', 'plugin', 'version' );

	/** @var array|null per-request cache */
	private static $current = null;

	/** Neutral config used when no industry plugin is active or its data is unusable. */
	public static function defaults() {
		return array(
			'contract'           => self::CONTRACT,
			'id'                 => '',
			'name'               => 'Hub',
			'industry'           => '',
			'leader'             => '',
			'brand'              => array( 'accent' => '#c8102e', 'logo' => '', 'slogan' => '' ),
			'modules'            => array(),
			'terms'              => self::TERMS,
			'segments'           => array(),
			'member_fields'      => array(),
			'standards'          => array(),
			'coach_skills'       => array(),
			'academy_tracks'     => array(),
			'campaign_templates' => array(),
			'apps'               => array(),
			'trial_days'         => 30,
			'knowledge_assets'   => array(),
			'plugin'             => '',
			'version'            => '',
		);
	}

	/**
	 * Validate a raw industry array. Pure: no WordPress calls.
	 *
	 * @param mixed $raw
	 * @return array { ok:bool, config:array, errors:string[], warnings:string[], ignored:string[] }
	 */
	public static function validate( $raw ) {
		$errors   = array();
		$warnings = array();
		$ignored  = array();
		$cfg      = self::defaults();

		if ( ! is_array( $raw ) || array() === $raw ) {
			return array( 'ok' => false, 'config' => $cfg, 'errors' => array( 'no_industry: chưa có plugin ngành nào trả dữ liệu qua filter ' . self::FILTER ), 'warnings' => array(), 'ignored' => array() );
		}

		foreach ( array_keys( $raw ) as $k ) {
			if ( ! in_array( $k, self::KNOWN_KEYS, true ) ) {
				$ignored[] = (string) $k;
			}
		}

		$contract = isset( $raw['contract'] ) ? (string) $raw['contract'] : '';
		if ( self::CONTRACT !== $contract ) {
			$errors[] = 'contract: cần "' . self::CONTRACT . '", nhận "' . $contract . '"';
		}

		$id = isset( $raw['id'] ) ? (string) $raw['id'] : '';
		if ( ! preg_match( '/^[a-z0-9-]{2,20}$/', $id ) ) {
			$errors[] = 'id: cần [a-z0-9-]{2,20} (trùng mã Hub ở open-hub-core)';
		}
		foreach ( array( 'name' => 80, 'industry' => 80 ) as $k => $max ) {
			$v = isset( $raw[ $k ] ) ? trim( (string) $raw[ $k ] ) : '';
			if ( '' === $v || self::len( $v ) > $max ) {
				$errors[] = $k . ': bắt buộc, ≤ ' . $max . ' ký tự';
			}
		}

		$brand  = isset( $raw['brand'] ) && is_array( $raw['brand'] ) ? $raw['brand'] : array();
		$accent = isset( $brand['accent'] ) ? strtolower( (string) $brand['accent'] ) : '';
		if ( ! preg_match( '/^#[0-9a-f]{6}$/', $accent ) ) {
			$errors[] = 'brand.accent: cần một màu #rrggbb (R-HUB-8, một màu nhấn)';
		}
		foreach ( $brand as $bk => $bv ) {
			if ( is_string( $bv ) && preg_match( self::FORBIDDEN_FONTS, $bv ) ) {
				$errors[] = 'brand.' . $bk . ': font viết tay / trang trí bị cấm (R-HUB-13)';
			}
			if ( ! in_array( $bk, array( 'accent', 'logo', 'slogan' ), true ) ) {
				$ignored[] = 'brand.' . $bk;
			}
		}

		if ( $errors ) {
			return array( 'ok' => false, 'config' => $cfg, 'errors' => $errors, 'warnings' => $warnings, 'ignored' => $ignored );
		}

		$cfg['id']       = $id;
		$cfg['name']     = trim( (string) $raw['name'] );
		$cfg['industry'] = trim( (string) $raw['industry'] );
		$cfg['leader']   = isset( $raw['leader'] ) ? self::str( $raw['leader'], 120 ) : '';
		$cfg['plugin']   = isset( $raw['plugin'] ) ? self::str( $raw['plugin'], 60 ) : '';
		$cfg['version']  = isset( $raw['version'] ) ? self::str( $raw['version'], 20 ) : '';
		$cfg['brand']    = array(
			'accent' => $accent,
			'logo'   => isset( $brand['logo'] ) && self::is_url( $brand['logo'] ) ? (string) $brand['logo'] : '',
			'slogan' => isset( $brand['slogan'] ) ? self::str( $brand['slogan'], 120 ) : '',
		);

		// modules: '01'…'14' => bool; required modules are forced on later by OHCL_Modules.
		if ( isset( $raw['modules'] ) && is_array( $raw['modules'] ) ) {
			foreach ( $raw['modules'] as $mid => $on ) {
				$mid = str_pad( (string) $mid, 2, '0', STR_PAD_LEFT );
				if ( ! OHCL_Modules::exists( $mid ) ) {
					$warnings[] = 'modules.' . $mid . ': không có module này, bỏ qua';
					continue;
				}
				if ( ! $on && OHCL_Modules::is_required( $mid ) ) {
					$warnings[] = 'modules.' . $mid . ': module bắt buộc, luôn bật';
					continue;
				}
				$cfg['modules'][ $mid ] = (bool) $on;
			}
		}

		if ( isset( $raw['terms'] ) && is_array( $raw['terms'] ) ) {
			foreach ( $raw['terms'] as $tk => $tv ) {
				if ( ! array_key_exists( $tk, self::TERMS ) ) {
					$warnings[] = 'terms.' . $tk . ': không có trong bảng thuật ngữ, bỏ qua';
					continue;
				}
				$tv = self::str( $tv, 40 );
				if ( '' !== $tv ) {
					$cfg['terms'][ $tk ] = $tv;
				}
			}
		}

		$cfg['segments'] = self::slug_map( isset( $raw['segments'] ) ? $raw['segments'] : array(), 'segments', $warnings );

		if ( isset( $raw['member_fields'] ) && is_array( $raw['member_fields'] ) ) {
			$seen = array();
			foreach ( $raw['member_fields'] as $i => $f ) {
				$p   = 'member_fields[' . $i . ']';
				$key = is_array( $f ) && isset( $f['key'] ) ? (string) $f['key'] : '';
				if ( ! preg_match( '/^[a-z][a-z0-9_]{1,29}$/', $key ) ) {
					$warnings[] = $p . ': key cần [a-z][a-z0-9_]{1,29}, bỏ trường';
					continue;
				}
				if ( in_array( $key, self::RESERVED_FIELDS, true ) || isset( $seen[ $key ] ) ) {
					$warnings[] = $p . ': key "' . $key . '" trùng trường lõi hoặc trùng nhau, bỏ trường';
					continue;
				}
				$type = isset( $f['type'] ) ? (string) $f['type'] : 'text';
				if ( ! in_array( $type, self::FIELD_TYPES, true ) ) {
					$warnings[] = $p . ': type "' . $type . '" không hỗ trợ, bỏ trường';
					continue;
				}
				$options = array();
				if ( in_array( $type, array( 'select', 'multi' ), true ) ) {
					$options = self::slug_map( isset( $f['options'] ) ? $f['options'] : array(), $p . '.options', $warnings );
					if ( ! $options ) {
						$warnings[] = $p . ': select / multi cần options, bỏ trường';
						continue;
					}
				}
				$label = isset( $f['label'] ) ? self::str( $f['label'], 60 ) : '';
				if ( '' === $label ) {
					$warnings[] = $p . ': thiếu label, bỏ trường';
					continue;
				}
				$seen[ $key ]           = true;
				$cfg['member_fields'][] = array(
					'key'      => $key,
					'meta'     => '_bzi_' . $key,
					'label'    => $label,
					'type'     => $type,
					'options'  => $options,
					'public'   => ! empty( $f['public'] ),
					'required' => ! empty( $f['required'] ),
				);
			}
		}

		if ( isset( $raw['standards'] ) && is_array( $raw['standards'] ) ) {
			foreach ( $raw['standards'] as $i => $st ) {
				$p    = 'standards[' . $i . ']';
				$code = is_array( $st ) && isset( $st['code'] ) ? (string) $st['code'] : '';
				$lab  = is_array( $st ) && isset( $st['label'] ) ? self::str( $st['label'], 80 ) : '';
				if ( ! preg_match( '/^[a-z0-9-]{2,30}$/', $code ) || '' === $lab ) {
					$warnings[] = $p . ': cần code [a-z0-9-] và label, bỏ';
					continue;
				}
				$months = isset( $st['validity_months'] ) ? (int) $st['validity_months'] : 12;
				if ( $months < 1 || $months > 60 ) {
					$warnings[] = $p . ': validity_months ngoài 1–60, dùng 12';
					$months     = 12;
				}
				$cfg['standards'][] = array(
					'code'            => $code,
					'label'           => $lab,
					'criteria'        => self::str_list( isset( $st['criteria'] ) ? $st['criteria'] : array(), 200 ),
					'evidence'        => self::str_list( isset( $st['evidence'] ) ? $st['evidence'] : array(), 120 ),
					'validity_months' => $months,
				);
			}
		}

		foreach ( array( 'coach_skills', 'academy_tracks', 'campaign_templates' ) as $list ) {
			$cfg[ $list ] = self::coded_list( isset( $raw[ $list ] ) ? $raw[ $list ] : array(), $list, $warnings );
		}

		if ( isset( $raw['apps'] ) && is_array( $raw['apps'] ) ) {
			foreach ( $raw['apps'] as $a ) {
				$a = (string) $a;
				if ( preg_match( '/^[a-z0-9-]{2,40}$/', $a ) ) {
					$cfg['apps'][] = $a;
				} else {
					$warnings[] = 'apps: "' . self::str( $a, 40 ) . '" không phải mã app, bỏ';
				}
			}
			$cfg['apps'] = array_values( array_unique( $cfg['apps'] ) );
		}

		if ( isset( $raw['trial_days'] ) ) {
			$td = (int) $raw['trial_days'];
			if ( $td < 1 || $td > 90 ) {
				$warnings[] = 'trial_days: ngoài 1–90, dùng 30 (open-hub-core còn cắt theo trần chính sách)';
				$td         = 30;
			}
			$cfg['trial_days'] = $td;
		}

		if ( isset( $raw['knowledge_assets'] ) && is_array( $raw['knowledge_assets'] ) ) {
			foreach ( $raw['knowledge_assets'] as $i => $as ) {
				$p    = 'knowledge_assets[' . $i . ']';
				$code = is_array( $as ) && isset( $as['code'] ) ? (string) $as['code'] : '';
				$lab  = is_array( $as ) && isset( $as['label'] ) ? self::str( $as['label'], 100 ) : '';
				$type = is_array( $as ) && isset( $as['type'] ) ? (string) $as['type'] : '';
				if ( ! preg_match( '/^[a-z0-9-]{2,40}$/', $code ) || '' === $lab || ! in_array( $type, self::ASSET_TYPES, true ) ) {
					$warnings[] = $p . ': cần code, label, type ∈ ' . implode( '|', self::ASSET_TYPES ) . ', bỏ';
					continue;
				}
				$cfg['knowledge_assets'][] = array(
					'code'        => $code,
					'label'       => $lab,
					'type'        => $type,
					'url'         => isset( $as['url'] ) && self::is_url( $as['url'] ) ? (string) $as['url'] : '',
					'description' => isset( $as['description'] ) ? self::str( $as['description'], 300 ) : '',
				);
			}
		}

		return array( 'ok' => true, 'config' => $cfg, 'errors' => array(), 'warnings' => $warnings, 'ignored' => $ignored );
	}

	/**
	 * Validated config of this site (runtime). Re-validates only when the industry array changes.
	 *
	 * @return array config
	 */
	public static function current() {
		$r = self::report();
		return $r['config'];
	}

	/** Full report of the current industry (config + errors / warnings). */
	public static function report() {
		if ( null !== self::$current ) {
			return self::$current;
		}
		$raw    = apply_filters( self::FILTER, array() );
		$hash   = md5( (string) wp_json_encode( $raw ) );
		$stored = get_option( self::OPTION );
		if ( is_array( $stored ) && isset( $stored['hash'], $stored['report'] ) && $stored['hash'] === $hash ) {
			self::$current = $stored['report'];
			return self::$current;
		}
		$report        = self::validate( $raw );
		self::$current = $report;
		update_option( self::OPTION, array( 'hash' => $hash, 'report' => $report, 'at' => gmdate( 'Y-m-d H:i:s' ) ), false );
		return $report;
	}

	/** Label of a term, e.g. term('member') ⇒ "cơ sở chay". */
	public static function term( $key ) {
		$c = self::current();
		return isset( $c['terms'][ $key ] ) ? $c['terms'][ $key ] : ( isset( self::TERMS[ $key ] ) ? self::TERMS[ $key ] : $key );
	}

	/** Tests only. */
	public static function reset() {
		self::$current = null;
	}

	/* ---------- helpers ---------- */

	private static function len( $s ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $s, 'UTF-8' ) : strlen( $s );
	}

	private static function str( $v, $max ) {
		if ( ! is_scalar( $v ) ) {
			return '';
		}
		$v = trim( preg_replace( '/\s+/u', ' ', strip_tags( (string) $v ) ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $v, 0, $max, 'UTF-8' ) : substr( $v, 0, $max );
	}

	private static function is_url( $v ) {
		return is_string( $v ) && (bool) preg_match( '#^https?://[^\s<>"]+$#i', $v );
	}

	private static function str_list( $v, $max ) {
		$o = array();
		if ( is_array( $v ) ) {
			foreach ( $v as $x ) {
				$x = self::str( $x, $max );
				if ( '' !== $x ) {
					$o[] = $x;
				}
			}
		}
		return $o;
	}

	private static function slug_map( $v, $path, array &$warnings ) {
		$o = array();
		if ( ! is_array( $v ) ) {
			return $o;
		}
		foreach ( $v as $slug => $label ) {
			$slug  = (string) $slug;
			$label = self::str( $label, 80 );
			if ( ! preg_match( '/^[a-z0-9-]{2,40}$/', $slug ) || '' === $label ) {
				$warnings[] = $path . '.' . self::str( $slug, 40 ) . ': cần slug [a-z0-9-] và nhãn, bỏ';
				continue;
			}
			$o[ $slug ] = $label;
		}
		return $o;
	}

	private static function coded_list( $v, $path, array &$warnings ) {
		$o = array();
		if ( ! is_array( $v ) ) {
			return $o;
		}
		foreach ( $v as $i => $it ) {
			$code = is_array( $it ) && isset( $it['code'] ) ? (string) $it['code'] : '';
			$lab  = is_array( $it ) && isset( $it['label'] ) ? self::str( $it['label'], 100 ) : '';
			if ( ! preg_match( '/^[a-z0-9-]{2,40}$/', $code ) || '' === $lab ) {
				$warnings[] = $path . '[' . $i . ']: cần code [a-z0-9-] và label, bỏ';
				continue;
			}
			$o[] = array(
				'code'        => $code,
				'label'       => $lab,
				'description' => isset( $it['description'] ) ? self::str( $it['description'], 300 ) : '',
			);
		}
		return $o;
	}
}
