# Lịch sử phát hành

## 0.3.2 — 2026-11-25

- Lõi trả 403 `rest_forbidden` (lớp chặn REST POST trên site lõi, không phải lõi) ⇒ hiện `ohcl_core_post_blocked` với câu tiếng Việt và việc cần làm, thay cho "REST POST blocked". Gặp thật khi bấm Kết nối trên veganhub.net.

## 0.3.1 — 2026-11-25

- Dán mã ghép nối nhầm vào ô "Mã Hub" (dạng `XXXX-XXXX`, khi chưa có bí mật `ohs_` hợp lệ) hoặc ô "Bí mật" của form nhập tay ⇒ site hiểu là mã ghép nối và kết nối luôn. Trước đây mã bị biến thành mã Hub sai và báo `ohcl_core_secret_invalid` (gặp thật trên veganhub.net). Mã Hub thật (`vegan`, `vegan-hub`) không bị nhận nhầm.
- admin-smoke 16/16.

## 0.3.0 — 2026-11-25 (ghép nối)

- **Kết nối lõi bằng mã ghép nối** (`OHCL_Core_Client::pair`): dán mã từ bizcity.vn › Sổ Hub ⇒ lõi trả bí mật thẳng máy chủ ⇒ lưu ⇒ tự kiểm 4 bước; báo nếu mã Hub ở lõi khác mã plugin ngành. Từ chối khi bí mật đang khoá bằng `OHCL_CORE_SECRET`.
- Màn Cài đặt: trạng thái ĐÃ / CHƯA KẾT NỐI, 3 bước hướng dẫn, ô mã; nhập tay chuyển vào "Nâng cao".
- `tests/e2e-core.php`: lõi thật + client thật qua transport trong bộ nhớ, 13/13 (mã sai / đúng / dùng lại, bí mật cũ hết hiệu lực, cấp khoá đầu tiên, bí mật không nằm trong yêu cầu nào). admin-smoke 12/12.

## 0.2.0 — 2026-11-25 (Đ1b)

- wp-admin › Hub (tên lấy từ dữ liệu ngành), theo mockup Owner Center:
  - **01 Tổng quan**: câu 2 (phễu dùng công nghệ) và câu 5 (việc cần làm) tính từ khoá thật của Hub ở lõi (`OHCL_Overview`, đệm 5 phút, không đệm kết quả lỗi); câu 1, 3, 4 hiện "—" kèm lý do, không ước số.
  - **03 Khoá dùng thử**: cấp khoá (khoá thô hiện một lần), danh sách, thu hồi qua bước xác nhận POST + nonce (không `window.confirm`).
  - **14 Cài đặt**: bật / tắt module (ghi rõ ai quyết), kết nối lõi (https, mã Hub, bí mật chỉ ghi, ưu tiên `OHCL_CORE_SECRET`), nút kiểm tra 4 bước, báo cáo dữ liệu ngành.
- Màu nhấn ngành qua biến CSS, chữ Manrope + IBM Plex Mono nếu máy có, không gradient (R-HUB-13).
- `tests/admin-smoke.php`: chạy ba màn với WordPress giả, 10/10 (escape, khoá hiện một lần, không lộ bí mật, lõi vắng không bịa số).

## 0.1.0 — 2026-11-25 (Đ1a)

- Hợp đồng ngành `bizcity_industry_hub@2` (`OHCL_Industry`): kiểm dữ liệu một lần, lưu bản đã kiểm; lỗi nặng ⇒ cấu hình mặc định + báo cáo; mục hỏng bị bỏ kèm cảnh báo; khoá lạ bị liệt kê. Cấm font viết tay, một màu nhấn.
- Registry 14 module (`OHCL_Modules`): 5 bắt buộc; quyết định chủ Hub > ngành > mặc định bật.
- Ô mở rộng chỉ hiển thị + sự kiện chỉ đọc (`OHCL_Slots`): lỗi của plugin ngành bị cô lập; payload sự kiện không chứa khoá thô.
- Một cửa gọi `open-hub-core/v1` (`OHCL_Core_Client`): Bearer bí mật Hub, chỉ https, lỗi bốn trường đi nguyên, mất mạng ⇒ `_degraded`, không lộ bí mật.
- CLI `wp ohcl validate-industry | modules | module | core-ping`.
- Ngành mẫu `examples/wp-sample-hub`.
- Test `php tests/run.php`: 43/43, kể cả kiểm ranh giới mã R-HUB-14 / R-HUB-15.
