# Open Hub Client — nền tảng chung cho mọi Hub ngành

<!-- [2026-11-25 01:33 PM Johnny Chu - Chu Hoàng Anh] OHCL-0 — khởi tạo repo open-hub-client (OH-Q1): README định vị, chưa có mã. -->

> **POWERED BY BIZCITY OPEN — Tặng công nghệ. Trao tri thức. Kết nối khách hàng. Cùng nhau phát triển.**

**Hub là trung tâm phát triển của một ngành. Bizcity Open là nền tảng công nghệ mà Hub kế thừa để phục vụ toàn ngành đó.** `open-hub-client` là plugin WordPress chạy trên **mỗi site Hub ngành**, nhiệm vụ: phân phối giá trị của Bizcity Open tới từng ngành. Muốn làm Hub mới (baby, thời trang, mỹ phẩm, kim khí, OCOP…) chỉ cần thêm một gói cấu hình `wp-<ngành>-hub`; không xây lại hội viên, doanh thu, Coach, chứng nhận hay quản lý quyền lợi.

```text
bizcity.vn:      open-hub-core   khoá 1API · gói · doanh thu khoá · chia · hoa hồng · đối soát
                     ▲ API có chữ ký Hub
site Hub ngành:  open-hub-client (repo này)  +  wp-<ngành>-hub (chỉ cấu hình; case đầu: wp-vegan-hub)
```

## 14 module chuẩn (bật / tắt theo Hub)

Tổng quan Hub · Quản lý hội viên (Member 360°) · Công nghệ & gói tặng (tài nguyên & quyền lợi theo GitHub) · Coach & chuyên gia (tuyển Coach, affiliate, My Coach) · Học viện Hub · Xác minh & chứng nhận (4 loại ghi nhận, tra cứu QR) · Danh bạ & bản đồ ngành · Marketing & thị trường · Sự kiện & cộng đồng · Đối tác & nhà tài trợ · Kho tri thức ngành · Kinh doanh & doanh thu (Woo) · Báo cáo & tác động · Cài đặt & phân quyền. Hội viên có **My Hub**; Coach có **My Coach**.

## Tài liệu và mockup

- Đặc tả: [OPEN-HUB-CLIENT](https://github.com/hoanganh8389/open-hub/blob/main/docs/canon/OPEN-HUB-CLIENT.md) (canon open-hub) · luật Hub ngành [CHUAN-HUB-NGANH](https://github.com/hoanganh8389/open-hub/blob/main/docs/canon/CHUAN-HUB-NGANH.md) · lõi trung tâm [OPEN-HUB-CORE](https://github.com/hoanganh8389/open-hub/blob/main/docs/canon/OPEN-HUB-CORE.md).
- Mockup + ảnh (case đầu Vegan Hub): [wp-vegan-hub](https://github.com/hoanganh8389/wp-vegan-hub) — `mockup/owner-center.html`, `mockup/hub-frontend.html`, `docs/shots/{core,frontend,profile}/`.

## Trạng thái

| Đợt | Nội dung | Trạng thái |
|---|---|---|
| Đ1 | registry 14 module + bật / tắt · Member 360° · Tổng quan câu 1, 2, 5 · cấp khoá qua `open-hub-core` | chưa — bắt đầu sau `open-hub-core` Đ1 (đã xong) |
| Đ2 | Tài nguyên & quyền lợi (GitHub) · My Hub · Xác minh & chứng nhận + tra cứu QR | chưa |
| Đ3 | Coach + affiliate + My Coach · Marketing (JSONL + rollup) · Doanh thu (core + Woo) · tài sản cộng đồng | chưa |

Lưu trữ lean: CPT + meta + option, **không bảng mới trên site Hub**; lượt bấm chỉ ghi JSONL, DB chỉ giữ rollup tổng.

## Giấy phép

GPL-2.0-or-later. **Bizcity**, **Bizcity Open** là nhãn hiệu của Bizcity; dòng "Powered by Bizcity Open" phải giữ.
