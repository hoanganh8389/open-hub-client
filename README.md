# Open Hub Client — nền tảng chung cho mọi Hub ngành

<!-- [2026-11-25 01:33 PM Johnny Chu - Chu Hoàng Anh] OHCL-0 — khởi tạo repo open-hub-client (OH-Q1): README định vị, chưa có mã. -->

> **POWERED BY BIZCITY OPEN — Tặng công nghệ. Trao tri thức. Kết nối khách hàng. Cùng nhau phát triển.**

**Hub là trung tâm phát triển của một ngành. Bizcity Open là nền tảng công nghệ mà Hub kế thừa để phục vụ toàn ngành đó.** `open-hub-client` là plugin WordPress chạy trên **mỗi site Hub ngành**, nhiệm vụ: phân phối giá trị của Bizcity Open tới từng ngành. Muốn làm Hub mới (baby, thời trang, mỹ phẩm, kim khí, OCOP…) chỉ cần thêm một gói cấu hình `wp-<ngành>-hub`; không xây lại hội viên, doanh thu, Coach, chứng nhận hay quản lý quyền lợi.

```text
bizcity.vn:      open-hub-core   khoá 1API · gói · doanh thu khoá · chia · hoa hồng · đối soát
                     ▲ API open-hub-core/v1, Bearer bí mật Hub — chỉ qua OHCL_Core_Client
site Hub ngành:  open-hub-client (repo này)  +  wp-<ngành>-hub (chỉ cấu hình; case đầu: wp-vegan-hub)
```

## 14 module chuẩn (bật / tắt theo Hub)

Tổng quan Hub · Quản lý hội viên (Member 360°) · Công nghệ & gói tặng (tài nguyên & quyền lợi theo GitHub) · Coach & chuyên gia (tuyển Coach, affiliate, My Coach) · Học viện Hub · Xác minh & chứng nhận (4 loại ghi nhận, tra cứu QR) · Danh bạ & bản đồ ngành · Marketing & thị trường · Sự kiện & cộng đồng · Đối tác & nhà tài trợ · Kho tri thức ngành · Kinh doanh & doanh thu (Woo) · Báo cáo & tác động · Cài đặt & phân quyền. Hội viên có **My Hub**; Coach có **My Coach**.

<!-- [2026-11-25 05:38 PM Johnny Chu - Chu Hoàng Anh] OHCL-D1 — hướng dẫn làm Hub ngành mới + nối lõi. -->
## Làm Hub cho một ngành mới (dev ngành)

1. Chép `examples/wp-sample-hub/` thành `wp-<ngành>-hub/`, đổi dữ liệu trong hàm trả mảng: `contract`, `id` (= mã Hub ở lõi), `brand.accent`, `terms`, `segments`, `member_fields`, `standards`…
2. Dữ liệu mẫu (tuỳ chọn): trả gói qua filter `open_hub_client_demo` (`threshold`, `categories`, `members`, `posts`) — xem `wp-vegan-hub/data/demo.php`.
3. Muốn hiển thị thêm: móc vào ô `ohcl_member360_tabs`, `ohcl_dashboard_cards`, `ohcl_myhub_cards`, `ohcl_public_profile_blocks`, `ohcl_persona_pages`; nghe sự kiện chỉ đọc `ohcl_member_stage_changed`, `ohcl_member_verified`, `ohcl_key_issued`, `ohcl_cert_issued`, `ohcl_pack_granted`.
4. Không: khoá, tiền, bảng mới, gọi `open-hub-core` / Cell / Zalo / LLM, sửa repo này cho riêng một ngành (R-HUB-14, R-HUB-15).
5. Kiểm: `wp ohcl validate-industry` phải PASS; đủ bộ shots R-HUB-11 / R-HUB-12.

<!-- [2026-11-25 06:23 PM Johnny Chu - Chu Hoàng Anh] OHCL-PAIR -->
Chủ Hub nối lõi **trên giao diện**: nhận mã ghép nối từ bizcity.vn › Sổ Hub → **Hub › 14 · Cài đặt › Kết nối lõi Bizcity** → dán mã → **Kết nối**. Site tự nhận bí mật từ lõi (máy chủ → máy chủ) và tự kiểm bốn bước: cấu hình · liên lạc · Hub có trong sổ · bí mật được nhận. Nhập tay hoặc hằng `OHCL_CORE_SECRET` chỉ còn ở mục Nâng cao.

## Test

```bash
php tests/run.php          # dịch vụ, hợp đồng, ranh giới
php tests/admin-smoke.php  # ba màn quản trị với WordPress giả
php tests/front-smoke.php  # 4 trang công khai + form đăng ký
php tests/cert-smoke.php   # chứng nhận: cấp, gia hạn, thu hồi, tra cứu, phản ánh
php tests/pages-smoke.php  # mọi trang, menu, khung WordPress, My Hub, ứng tuyển Coach
php tests/menu-smoke.php   # trang WordPress + "Menu Hub"
php tests/fix-smoke.php    # lỗi đã gặp trên site thật
php tests/affiliate-smoke.php # Coach: cấp vai, mã giới thiệu, ghi nhận hoa hồng, kỳ chi trả
php tests/demo-smoke.php   # dữ liệu mẫu (hiện có nhãn, tự ẩn, bài đăng / nháp / xoá) + sản phẩm Store API
php tests/e2e-core.php     # liên thông với ../open-hub-core: tạo Hub → mã ghép nối → kết nối → cấp khoá
```

## Tài liệu và mockup

- Đặc tả: [OPEN-HUB-CLIENT](https://github.com/hoanganh8389/open-hub/blob/main/docs/canon/OPEN-HUB-CLIENT.md) (canon open-hub) · luật Hub ngành [CHUAN-HUB-NGANH](https://github.com/hoanganh8389/open-hub/blob/main/docs/canon/CHUAN-HUB-NGANH.md) · lõi trung tâm [OPEN-HUB-CORE](https://github.com/hoanganh8389/open-hub/blob/main/docs/canon/OPEN-HUB-CORE.md).
- Mockup + ảnh (case đầu Vegan Hub): [wp-vegan-hub](https://github.com/hoanganh8389/wp-vegan-hub) — `mockup/owner-center.html`, `mockup/hub-frontend.html`, `docs/shots/{core,frontend,profile}/`.

## Trạng thái

| Đợt | Nội dung | Trạng thái |
|---|---|---|
| **Đ1a** | hợp đồng ngành `bizcity_industry_hub@2` + bộ kiểm · registry 14 module bật / tắt · ô mở rộng + sự kiện · `OHCL_Core_Client` (một cửa gọi lõi) · CLI `wp ohcl` · ngành mẫu `examples/wp-sample-hub` | **xong mã + test (43/43)**, chưa chạy trên site thật |
| **Đ1b** | màn quản trị (mockup O0, O1): **Tổng quan** câu 2 + 5 từ khoá thật ở lõi · **Khoá dùng thử** cấp (hiện một lần) / danh sách / thu hồi hai bước · **Cài đặt** module, kết nối lõi + kiểm tra, báo cáo dữ liệu ngành | **xong mã + test** (49/49 + chạy thử màn 10/10), chưa chạy trên site thật |
| **F1** | cổng cộng đồng: trang chủ ngành (thay trang chủ theme, có công tắc), `/ban-do/`, `/co-so/…`, `/tro-thanh-hoi-vien/`; xác minh V3 + bậc V1–V4 | **xong mã + test** (front-smoke 25/25) · chưa chạy trên site thật |
| **F3** | mẫu trang "Open Hub — …", tự tạo trang WordPress, menu WordPress "Menu Hub" (đầu / chân trang đọc menu), chạy tự động một lần | **xong mã + test** (menu-smoke 21/21) · chưa chạy trên site thật |
| **F2** | đủ trang sitemap §12 + My Hub (6 mục) + ứng tuyển Coach; sổ trang một chỗ; mặc định vẽ cả site trong khung Hub (giữ theme cho Woo shop / trang chọn) | **xong mã + test** (pages-smoke 41/41) · chưa chạy trên site thật |
| **Đ2a** | xác minh & chứng nhận (module 06): cấp đủ tiêu chí, gia hạn, thu hồi, `/chung-nhan/` tra cứu mã / QR, phản ánh, bậc hành trình 6 | **xong mã + test** (cert-smoke 35/35) · chưa chạy trên site thật |
| **Đ1c** | Member 360° (CPT `bz_hub_member`, trường ngành `_bzi_*`, tiếp nhận, cấp khoá từ hồ sơ) · Tổng quan câu 1 | **xong mã + test** (admin-smoke 30/30) · kết nối lõi đã chạy thật trên veganhub.net ↔ bizcity.vn (4/4) |
| **CM-2/3** | dữ liệu mẫu theo ngành (cơ sở mẫu có nhãn, tự ẩn ở ngưỡng cơ sở thật; bài mẫu đăng / nháp / xoá hết) · sản phẩm từ Woo của cơ sở trên hồ sơ | **xong mã + test** (demo-smoke 40/40) · chưa chạy trên site thật |
| **Đ3a** | Coach + affiliate (backend): vai `hub_coach`, mã + cookie giới thiệu 12 tháng, hoa hồng giới thiệu hội viên từ dữ liệu nguồn, kỳ chi trả tạm tính → duyệt → trả, dòng đảo | **xong mã + test** (affiliate-smoke 42/42) · chưa chạy trên site thật · My Coach, nguồn Woo / AI: chưa |
| Đ2 | Tài nguyên & quyền lợi (GitHub) · My Hub · Xác minh & chứng nhận + tra cứu QR | chưa |
| Đ3 | Coach + affiliate + My Coach · Marketing (JSONL + rollup) · Doanh thu (core + Woo) · tài sản cộng đồng | chưa |

Lưu trữ lean: CPT + meta + option, **không bảng mới trên site Hub**; lượt bấm chỉ ghi JSONL, DB chỉ giữ rollup tổng.

## Giấy phép

GPL-2.0-or-later. **Bizcity**, **Bizcity Open** là nhãn hiệu của Bizcity; dòng "Powered by Bizcity Open" phải giữ.
