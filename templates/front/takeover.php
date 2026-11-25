<?php
/**
 * Entry returned by OHCL_Front::takeover() (template_include): any WordPress view drawn in the Hub frame.
 *
 * @package Open_Hub_Client
 */
// [2026-11-25 11:30 PM Johnny Chu - Chu Hoàng Anh] OHCL-F2 — cửa vào khung Hub cho mọi trang WordPress khác.

defined( 'ABSPATH' ) || exit;

echo OHCL_Front::html( 'wp', array() ); // phpcs:ignore WordPress.Security.EscapeOutput -- template tự escape; nội dung bài qua bộ lọc the_content của WordPress.
