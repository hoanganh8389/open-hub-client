# Hướng dẫn cho AI agent
<!-- [2026-11-25 01:33 PM Johnny Chu - Chu Hoàng Anh] OHCL-0 — luật cho agent làm việc trên open-hub-client. -->

Dự án: **open-hub-client** — nền tảng chung chạy trên mỗi site Hub ngành. Đặc tả: canon open-hub `docs/canon/OPEN-HUB-CLIENT.md`; luật R-HUB-1…13 ở `CHUAN-HUB-NGANH.md`. Đọc trước khi sửa.

1. **Giao diện bám mockup** đã duyệt (repo `wp-vegan-hub`, `mockup/owner-center.html`, `mockup/hub-frontend.html`); phong cách R-HUB-13 (Manrope + IBM Plex Mono, một màu nhấn ngành, không font viết tay).
2. **Không gì riêng của một ngành** trong repo này; ngành chỉ cấu hình qua `wp-<ngành>-hub` (R-HUB-1).
3. **Không khoá, không ví, không gọi LLM**: cấp khoá / đọc doanh thu khoá chỉ qua API của `open-hub-core`.
4. **Không bảng mới**: CPT + meta + option; lượt bấm chỉ JSONL, DB chỉ rollup tổng (OH-Q4); doanh thu dịch vụ từ WooCommerce (OH-Q3).
5. **Không bịa số**: số đếm tính từ dữ liệu; chưa có nguồn thì "—".
6. PHP 7.4; lỗi theo bốn trường `code · message · hint · help_code`.
7. <!-- [2026-11-25 05:35 PM Johnny Chu - Chu Hoàng Anh] OHCL-0 — ba ranh giới (R-HUB-14, R-HUB-15). --> **Ba ranh giới:** ngành chỉ vào qua hợp đồng `bizcity_industry_hub@2` + ô mở rộng (OPEN-HUB-CLIENT §10); core chỉ gọi qua một lớp `OHCL_Core_Client` (`open-hub-core/v1`); không gọi Cell, Zalo, LLM. Không có `if` theo mã ngành trong repo này. Yêu cầu ngành cần sửa core / Cell ⇒ làm thành tính năng chung có phiên bản.

Dấu thay đổi: `[YYYY-MM-DD HH:MM AM/PM Johnny Chu - Chu Hoàng Anh] OHCL-<mã> — <đổi gì>`, giờ đọc từ đồng hồ.
