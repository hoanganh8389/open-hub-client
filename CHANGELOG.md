# Lịch sử phát hành

## 0.1.0 — 2026-11-25 (Đ1a)

- Hợp đồng ngành `bizcity_industry_hub@2` (`OHCL_Industry`): kiểm dữ liệu một lần, lưu bản đã kiểm; lỗi nặng ⇒ cấu hình mặc định + báo cáo; mục hỏng bị bỏ kèm cảnh báo; khoá lạ bị liệt kê. Cấm font viết tay, một màu nhấn.
- Registry 14 module (`OHCL_Modules`): 5 bắt buộc; quyết định chủ Hub > ngành > mặc định bật.
- Ô mở rộng chỉ hiển thị + sự kiện chỉ đọc (`OHCL_Slots`): lỗi của plugin ngành bị cô lập; payload sự kiện không chứa khoá thô.
- Một cửa gọi `open-hub-core/v1` (`OHCL_Core_Client`): Bearer bí mật Hub, chỉ https, lỗi bốn trường đi nguyên, mất mạng ⇒ `_degraded`, không lộ bí mật.
- CLI `wp ohcl validate-industry | modules | module | core-ping`.
- Ngành mẫu `examples/wp-sample-hub`.
- Test `php tests/run.php`: 43/43, kể cả kiểm ranh giới mã R-HUB-14 / R-HUB-15.
