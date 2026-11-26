# Lịch sử phát hành

## 0.13.0 — 2026-11-26 (H3: "Hub đã mang lại gì" có số thật + kết quả hội viên tự khai)

- **Sổ đo** (`OHCL_Events`): lượt **xem hồ sơ** và lượt **bấm "Nhắn Zalo"** trên trang công khai. Mỗi lượt là một dòng JSONL `{t, e, m}` (giờ UTC, sự kiện, mã hội viên) trong `uploads/open-hub-client/events/` (có `.htaccess` chặn truy cập); **không IP, không user-agent, không liên hệ**. Cron hằng ngày gộp **tổng theo tháng** vào `_bzm_rollup` (giữ 14 tháng), xoá tệp quá 120 ngày; không dòng nào vào cơ sở dữ liệu.
- Không đếm: chủ hồ sơ, quản trị viên, máy tự động / xem trước liên kết, hồ sơ mẫu, hồ sơ không hoạt động; một khách chỉ tính một lượt mỗi giờ (khử trùng bằng transient băm, không lưu dữ liệu đọc được).
- **Chưa đo ≠ đo ra 0**: khi chưa từng có lượt nào, My Hub và Member 360° hiện "—"; có lượt rồi mới hiện số (kể cả 0).
- **My Hub**: hai ô "Lượt xem hồ sơ", "Lượt liên hệ từ Hub" ở Tổng quan nay là số thật. **Tab mới "Kết quả của tôi"**: hội viên tự khai 1–3 chỉ số trước / sau (kèm kỳ) và ô đồng ý công bố. Trạng thái: chưa khai · chỉ mình thấy · chờ Hub duyệt · đang hiện công khai; **mỗi lần sửa về "chờ duyệt"**, bỏ tích đồng ý thì gỡ ngay.
- **Chủ Hub**: Member 360° có "Hub đã mang lại (tháng này)" và "Kết quả hội viên tự khai" với nút **Duyệt hiện công khai / Gỡ**. Trang công khai chỉ hiện kết quả khi hội viên đồng ý **và** Hub duyệt, ghi rõ "do cơ sở tự khai; Hub không kiểm chứng số kinh doanh". Đây là đường duy nhất để công bố số liệu kết quả của hội viên.
- **API**: `GET/POST /me/outcomes`, `POST /owner/members/{id}/outcomes/review`, `POST /places/{id}/contact` (beacon công khai, 202), `GET /me` có `impact`. 23 route.
- events-smoke 36/36, rest-smoke 63/63, pages-smoke 47/47, admin-smoke 50/50 (đột biến: đếm quản trị viên, bỏ khử trùng, đếm hai lần sau rollup, kết quả chưa duyệt lên công khai, đếm máy tự động — đều bị bắt).

## 0.12.0 — 2026-11-26 (sổ quyền lợi: hội viên thấy mình nhận gì, chủ Hub thấy cả cộng đồng nhận gì)

- **Tám quyền lợi** (`OHCL_Benefits`): Công nghệ · Đào tạo · Khách hàng · Trợ lý AI · Coach đồng hành · Kết nối giao thương · Uy tín & xác minh · Cơ hội tăng trưởng. Trạng thái từng hội viên **tính từ dữ liệu có sẵn** (không lưu thêm, không ước): Chưa mở → Có thể nhận → Đã trao → Đã bật → Đang dùng; quyền lợi chưa thể đo dừng ở mức chứng minh được và ghi rõ vì sao (ví dụ Công nghệ dừng ở "Đã trao" tới khi site hội viên báo mức dùng; Giao thương không bao giờ "đang dùng" khi chưa đo lượt kết nối). "Cơ hội tăng trưởng" = **Sắp ra mắt**, không tính là đã nhận. Mỗi quyền lợi mang mức sẵn sàng (Đã nghiệm thu / Giới hạn / Lộ trình), ngành tinh chỉnh bằng bộ lọc `ohcl_benefit_availability`.
- **My Hub › Quyền lợi của tôi**: tám thẻ, trạng thái, lý do, ghi chú giới hạn, nút việc tiếp theo; Tổng quan có dòng "N quyền lợi bạn đã nhận". Quyền lợi mà Hub tắt module thì không hiện.
- **Tổng quan chủ Hub (câu 3)**: phễu "Quyền lợi hội viên đã nhận" — đã nhận / số hội viên đã được tiếp nhận, theo từng quyền lợi.
- **API**: `GET /me/benefits`, `GET /owner/benefits` (+ `?benefit=&state=` ra danh sách hội viên đứng sau con số). 19 route.
- run 74/74, rest-smoke 55/55, pages-smoke 45/45, admin-smoke 49/49 (đột biến: "chưa tiếp nhận vẫn nhận", "lộ trình vẫn tính đã nhận", "Công nghệ báo đang dùng", "Giao thương luôn đã trao" — đều bị bắt).

## 0.12.0 — 2026-11-26 (sổ quyền lợi: hội viên thấy mình nhận gì, chủ Hub thấy cả cộng đồng nhận gì)

- **Tám quyền lợi** (`OHCL_Benefits`): Công nghệ · Đào tạo · Khách hàng · Trợ lý AI · Coach đồng hành · Kết nối giao thương · Uy tín & xác minh · Cơ hội tăng trưởng. Trạng thái từng hội viên **tính từ dữ liệu có sẵn** (không lưu thêm, không ước): Chưa mở → Có thể nhận → Đã trao → Đã bật → Đang dùng; quyền lợi chưa thể đo dừng ở mức chứng minh được và ghi rõ vì sao (ví dụ Công nghệ dừng ở "Đã trao" tới khi site hội viên báo mức dùng; Giao thương không bao giờ "đang dùng" khi chưa đo lượt kết nối). "Cơ hội tăng trưởng" = **Sắp ra mắt**, không tính là đã nhận. Mỗi quyền lợi mang mức sẵn sàng (Đã nghiệm thu / Giới hạn / Lộ trình), ngành tinh chỉnh bằng bộ lọc `ohcl_benefit_availability`.
- **My Hub › Quyền lợi của tôi**: tám thẻ, trạng thái, lý do, ghi chú giới hạn, nút việc tiếp theo; Tổng quan có dòng "N quyền lợi bạn đã nhận". Quyền lợi mà Hub tắt module thì không hiện.
- **Tổng quan chủ Hub (câu 3)**: phễu "Quyền lợi hội viên đã nhận" — đã nhận / số hội viên đã được tiếp nhận, theo từng quyền lợi.
- **API**: `GET /me/benefits`, `GET /owner/benefits` (+ `?benefit=&state=` ra danh sách hội viên đứng sau con số). 19 route.
- run 74/74, rest-smoke 55/55, pages-smoke 45/45, admin-smoke 49/49 (đột biến: "chưa tiếp nhận vẫn nhận", "lộ trình vẫn tính đã nhận", "Công nghệ báo đang dùng", "Giao thương luôn đã trao" — đều bị bắt).

## 0.12.0 — 2026-11-26 (sổ quyền lợi: hội viên thấy mình nhận gì, chủ Hub thấy cả cộng đồng nhận gì)

- **Tám quyền lợi** (`OHCL_Benefits`): Công nghệ · Đào tạo · Khách hàng · Trợ lý AI · Coach đồng hành · Kết nối giao thương · Uy tín & xác minh · Cơ hội tăng trưởng. Trạng thái từng hội viên **tính từ dữ liệu có sẵn** (không lưu thêm, không ước): Chưa mở → Có thể nhận → Đã trao → Đã bật → Đang dùng; quyền lợi chưa thể đo dừng ở mức chứng minh được và ghi rõ vì sao (ví dụ Công nghệ dừng ở "Đã trao" tới khi site hội viên báo mức dùng; Giao thương không bao giờ "đang dùng" khi chưa đo lượt kết nối). "Cơ hội tăng trưởng" = **Sắp ra mắt**, không tính là đã nhận. Mỗi quyền lợi mang mức sẵn sàng (Đã nghiệm thu / Giới hạn / Lộ trình), ngành tinh chỉnh bằng bộ lọc `ohcl_benefit_availability`.
- **My Hub › Quyền lợi của tôi**: tám thẻ, trạng thái, lý do, ghi chú giới hạn, nút việc tiếp theo; Tổng quan có dòng "N quyền lợi bạn đã nhận". Quyền lợi mà Hub tắt module thì không hiện.
- **Tổng quan chủ Hub (câu 3)**: phễu "Quyền lợi hội viên đã nhận" — đã nhận / số hội viên đã được tiếp nhận, theo từng quyền lợi.
- **API**: `GET /me/benefits`, `GET /owner/benefits` (+ `?benefit=&state=` ra danh sách hội viên đứng sau con số). 19 route.
- run 74/74, rest-smoke 55/55, pages-smoke 45/45, admin-smoke 49/49 (đột biến: "chưa tiếp nhận vẫn nhận", "lộ trình vẫn tính đã nhận", "Công nghệ báo đang dùng", "Giao thương luôn đã trao" — đều bị bắt).

## 0.12.0 — 2026-11-26 (sổ quyền lợi: hội viên thấy mình nhận gì, chủ Hub thấy cả cộng đồng nhận gì)

- **Tám quyền lợi** (`OHCL_Benefits`): Công nghệ · Đào tạo · Khách hàng · Trợ lý AI · Coach đồng hành · Kết nối giao thương · Uy tín & xác minh · Cơ hội tăng trưởng. Trạng thái từng hội viên **tính từ dữ liệu có sẵn** (không lưu thêm, không ước): Chưa mở → Có thể nhận → Đã trao → Đã bật → Đang dùng; quyền lợi chưa thể đo dừng ở mức chứng minh được và ghi rõ vì sao (ví dụ Công nghệ dừng ở "Đã trao" tới khi site hội viên báo mức dùng; Giao thương không bao giờ "đang dùng" khi chưa đo lượt kết nối). "Cơ hội tăng trưởng" = **Sắp ra mắt**, không tính là đã nhận. Mỗi quyền lợi mang mức sẵn sàng (Đã nghiệm thu / Giới hạn / Lộ trình), ngành tinh chỉnh bằng bộ lọc `ohcl_benefit_availability`.
- **My Hub › Quyền lợi của tôi**: tám thẻ, trạng thái, lý do, ghi chú giới hạn, nút việc tiếp theo; Tổng quan có dòng "N quyền lợi bạn đã nhận". Quyền lợi mà Hub tắt module thì không hiện.
- **Tổng quan chủ Hub (câu 3)**: phễu "Quyền lợi hội viên đã nhận" — đã nhận / số hội viên đã được tiếp nhận, theo từng quyền lợi.
- **API**: `GET /me/benefits`, `GET /owner/benefits` (+ `?benefit=&state=` ra danh sách hội viên đứng sau con số). 19 route.
- run 74/74, rest-smoke 55/55, pages-smoke 45/45, admin-smoke 49/49 (đột biến: "chưa tiếp nhận vẫn nhận", "lộ trình vẫn tính đã nhận", "Công nghệ báo đang dùng", "Giao thương luôn đã trao" — đều bị bắt).

## 0.12.0 — 2026-11-26 (sổ quyền lợi: hội viên thấy mình nhận gì, chủ Hub thấy cả cộng đồng nhận gì)

- **Tám quyền lợi** (`OHCL_Benefits`): Công nghệ · Đào tạo · Khách hàng · Trợ lý AI · Coach đồng hành · Kết nối giao thương · Uy tín & xác minh · Cơ hội tăng trưởng. Trạng thái từng hội viên **tính từ dữ liệu có sẵn** (không lưu thêm, không ước): Chưa mở → Có thể nhận → Đã trao → Đã bật → Đang dùng; quyền lợi chưa thể đo dừng ở mức chứng minh được và ghi rõ vì sao (ví dụ Công nghệ dừng ở "Đã trao" tới khi site hội viên báo mức dùng; Giao thương không bao giờ "đang dùng" khi chưa đo lượt kết nối). "Cơ hội tăng trưởng" = **Sắp ra mắt**, không tính là đã nhận. Mỗi quyền lợi mang mức sẵn sàng (Đã nghiệm thu / Giới hạn / Lộ trình), ngành tinh chỉnh bằng bộ lọc `ohcl_benefit_availability`.
- **My Hub › Quyền lợi của tôi**: tám thẻ, trạng thái, lý do, ghi chú giới hạn, nút việc tiếp theo; Tổng quan có dòng "N quyền lợi bạn đã nhận". Quyền lợi mà Hub tắt module thì không hiện.
- **Tổng quan chủ Hub (câu 3)**: phễu "Quyền lợi hội viên đã nhận" — đã nhận / số hội viên đã được tiếp nhận, theo từng quyền lợi.
- **API**: `GET /me/benefits`, `GET /owner/benefits` (+ `?benefit=&state=` ra danh sách hội viên đứng sau con số). 19 route.
- run 74/74, rest-smoke 55/55, pages-smoke 45/45, admin-smoke 49/49 (đột biến: "chưa tiếp nhận vẫn nhận", "lộ trình vẫn tính đã nhận", "Công nghệ báo đang dùng", "Giao thương luôn đã trao" — đều bị bắt).

## 0.11.1 — 2026-11-26 (Tổng quan: câu 1 và câu 5 đủ việc từ hồ sơ hội viên)

- **Câu 5 "Tôi cần làm gì tiếp theo?"** nay có việc từ hồ sơ, không chỉ từ khoá: yêu cầu hỗ trợ mới (7 ngày), hồ sơ chờ tiếp nhận, đã tiếp nhận chờ cấp khoá, đang dùng trợ lý chờ xác minh, xác minh sắp hết hạn (30 ngày), hội viên chưa có Coach (khi module Coach bật và đã có Coach). Mỗi việc là liên kết lọc danh sách hội viên theo chặng; chỉ hiện khi có số > 0; lõi không đọc được ⇒ không bịa việc "chờ xác minh".
- **Câu 1 "Cộng đồng phát triển thế nào?"** thêm **mới / rời theo 6 tháng** (ngày tạo hồ sơ; ngày rời ghi một lần khi chuyển "Đã rời", xoá khi quay lại; hồ sơ cũ chưa có ngày rời không được đếm).
- API `GET /owner/overview` trả thêm `community.growth` và các việc từ hồ sơ (có `args` để lọc `/owner/members`).
- run 57/57, admin-smoke 48/48, rest-smoke 48/48.

## 0.11.0 — 2026-11-26 (REST API cho trục Hub Owner ↔ hội viên)

- **REST `open-hub-client/v1`** (17 route): Hub Owner (`/owner/overview`, `/owner/members…`, tiếp nhận, trạng thái, cấp / thu hồi khoá, xác minh), hội viên (`/me`, `/me/profile`, `/me/support`), công khai (`/meta`, `/places`, `/places/{id}`). Cookie + nonce hoặc Application Password.
- **Một cửa dịch vụ** `OHCL_Service`: màn quản trị và My Hub chuyển sang dùng chung với REST (tiếp nhận, trạng thái, cấp khoá, xác minh, hồ sơ, hỗ trợ) ⇒ cùng luật, cùng sự kiện chỉ đọc; trước đây logic nằm rải ở handler.
- Khoá thô **chỉ** nằm trong câu trả lời `POST …/key`, một lần, `no-store`; không vào DB, danh sách, sự kiện. Cấp lần hai khi còn khoá chạy ⇒ 409, không gọi lõi.
- Lõi không trả lời ⇒ **503** (trong plugin vẫn "fail open" cho giao diện; với ứng dụng gọi API là 503 để biết thử lại).
- `/me/profile` cập nhật từng phần; hội viên không sửa được tên, loại hình, email, Coach. `/places` giới hạn 120 lần / phút / IP, đệm 60 giây, không lộ liên hệ riêng.
- rest-smoke 45/45 (đột biến: mở quyền chủ Hub, ghi đè toàn phần, lộ trường nội bộ cho hội viên, cấp khoá trùng, báo 200 giả khi lõi lỗi — đều bị bắt).

## 0.10.1 — 2026-11-26 (My Coach)

- Trang **/my-coach/** (module 04): Coach đăng nhập thấy link giới thiệu + mã, mức hoa hồng giới thiệu, bốn ô (hội viên giới thiệu · tạm tính · đã duyệt · đã trả), danh sách hội viên mình giới thiệu (tên cơ sở, mã, giai đoạn, "Đã ghi nhận / Chờ kích hoạt / Hết hạn 12 tháng") và lịch sử chi trả từng kỳ.
- Riêng tư: chỉ dữ liệu của chính Coach; hội viên **không lộ email, điện thoại, Zalo**; Coach khác không thấy. Khách → mời đăng nhập; tài khoản không phải Coach → link ứng tuyển.
- Đầu trang hiện "My Coach" cho người có vai Coach. Module 04 tắt ⇒ trang biến mất.
- affiliate-smoke 51/51 (đột biến: ai cũng là Coach, Coach thấy mọi hội viên — đều bị bắt).

## 0.10.0 — 2026-11-26 (Đ3a: Coach + affiliate, backend)

- **Vai `hub_coach`**: ở Hub › 04 · Coach, hồ sơ đã duyệt bấm "Cấp vai Coach" + email ⇒ tạo tài khoản (hoặc thêm vai vào tài khoản cùng email, giữ vai cũ), gửi email đặt mật khẩu, cấp **mã giới thiệu** 6 ký tự.
- **Giới thiệu**: link `?ref=<mã>` đặt cookie 12 tháng; đăng ký hội viên ghi Coach + ngày, điền "Coach phụ trách"; **người đầu tiên thắng**, không ghi đè.
- **Hoa hồng nguồn 1 (giới thiệu hội viên)** từ dữ liệu nguồn: hội viên được giới thiệu đã "Đã kích hoạt" (khoá đang chạy) trong 12 tháng ⇒ một dòng khoá `member:<id>`, không nhân đôi; số tiền chụp theo chính sách lúc ghi nhận (đổi chính sách chỉ áp dòng sau); số riêng của từng Coach thắng chính sách chung; chính sách 0 đ ⇒ không ghi nhận; lõi lỗi ⇒ không đoán.
- **Kỳ chi trả** (CPT `bz_hub_payout`, mỗi Coach mỗi tháng, dòng trong meta): Tạm tính → Đã duyệt → Đã trả, một chiều; kỳ đã duyệt / đã trả không bị sửa, dòng mới vào tháng sau; huỷ ⇒ **dòng đảo** (số âm), không xoá.
- Cron hằng ngày `ohcl_affiliate_daily`; nút "Ghi nhận ngay"; `wp ohcl affiliate sync|payouts|reverse`. Sự kiện chỉ đọc `coach_activated`.
- Chưa làm: nguồn 2 (đơn Woo `_bz_hub_service`), nguồn 3 (doanh thu AI do lõi chốt tháng), My Coach (Coach tự xem), Coach Workspace.
- affiliate-smoke 42/42 (đột biến: bỏ chặn trùng, bỏ hạn 12 tháng, sửa kỳ đã duyệt, bỏ điều kiện kích hoạt — đều bị test bắt).

## 0.9.2 — 2026-11-26 (góp ý trang chủ, tra cứu)

- **Gần tôi**: danh sách thành thẻ từng cơ sở — thứ hạng, tên, loại hình · tỉnh, **số km nổi bật** (dưới 1 km hiện mét), Xem chi tiết, Chỉ đường (Google Maps), nút đóng; ghi rõ khoảng cách đường chim bay.
- **Trang Chứng nhận**: ô tra cứu lên đầu, căn giữa; dưới là các thẻ thông tin, "Kết quả có thể gặp" thành 4 ô, bộ tiêu chuẩn, phản ánh.
- **Thống kê trang chủ thật**: hội viên, cơ sở xác minh công khai, địa điểm, bài viết, bài review (nếu ngành khai `persona.review_category`), chứng nhận còn hiệu lực, Coach. Bỏ ô "Sự kiện —" chưa có nguồn. Đệm 10 phút.
- demo-smoke 53/53.

## 0.9.1 — 2026-11-26 (góp ý trang chủ)

- Bỏ khối chú thích dữ liệu mẫu dưới bản đồ trang chủ; nhãn "Mẫu" vẫn ở từng cơ sở mẫu.
- **Tâm bản đồ** theo ngành (`persona.map` = `{ lat, lng, zoom }`) hoặc chủ Hub đặt ở 14 · Cài đặt (option `ohcl_map_view`); trang chủ và trang bản đồ (khi không lọc) mở ở tâm đó.
- **Gần tôi**: nút trên bản đồ trang chủ và trang bản đồ; vị trí lấy trong trình duyệt (không gửi lên máy chủ), hiện chấm vị trí + 5 cơ sở gần nhất kèm khoảng cách; bị từ chối quyền thì nói cách bật.
- **Ảnh nền dưới hero** theo ngành (`persona.hero_bg`) hoặc chủ Hub đặt (option `ohcl_hero_bg`); chỉ nhận URL ảnh https / đường dẫn tương đối, không chèn được CSS.
- Trang Quyền lợi: "8 quyền lợi đặc biệt".
- demo-smoke 47/47.

## 0.9.0 — 2026-11-26 (dữ liệu mẫu + sản phẩm của cơ sở, chủ Hub chốt CM-Q1…Q4)

- **Dữ liệu mẫu theo ngành** (`OHCL_Demo`, filter `open_hub_client_demo`): plugin ngành cấp cơ sở mẫu, chuyên mục, bài mẫu. Cơ sở mẫu hiện công khai trên bản đồ, danh bạ, giao thương với nhãn **"Mẫu"** (không bao giờ "Đã xác minh"), không nút Zalo, **không vào số liệu**; tự ẩn khi Hub có đủ cơ sở thật công khai (ngưỡng do ngành đặt, mặc định 15). Chủ Hub tắt được bất cứ lúc nào.
- **Bài mẫu** đăng ngay; một nút **Đăng hết / Chuyển nháp hết / Xoá hết**. Dấu ẩn `_ohcl_demo` + `_ohcl_demo_ref` (cơ sở: `_bzm_demo`) ⇒ nhập lại không trùng, xoá không đụng dữ liệu thật. Link `{place:<ref>}` trong bài thành đường dẫn hồ sơ thật.
- Màn **Hub › Dữ liệu mẫu**; lệnh `wp ohcl demo status|seed|remove --yes|posts publish|draft|delete --yes|mode auto|off`.
- **Sản phẩm của cơ sở** (`OHCL_Shop`, đường B): trường "Website bán hàng (WooCommerce)" + ô đồng ý (quản trị và My Hub). Trang hồ sơ hiện tối đa 6 sản phẩm đọc từ Store API **công khai** của site cơ sở (https, `wp_safe_remote_get`, 4 giây, đệm 6 giờ / lỗi 30 phút); link chỉ về chính cửa hàng đó.
- Bài test mới `demo-smoke.php` 40/40; các bộ cũ vẫn đạt.

## 0.8.3 — 2026-11-26 (góp ý trên veganhub.net)

- **Màu chọn không lưu**: ô "Dùng màu của plugin ngành" được tích sẵn nên màu bị bỏ. Nay là ô "Trả về màu của plugin ngành", mặc định không tích.
- **Trang chủ**: bản đồ lớn ngay dưới phần "Cùng kiến tạo"; luôn có nền bản đồ Việt Nam, chưa có cơ sở thì phủ một khung giải thích + nút đăng ký. Hàng giữa: Tìm kiếm · Danh mục · Tin tức.
- **Khối Danh mục lệch trái**: lớp CSS `sec` (tiêu đề, F2) trùng lớp khung trang chủ — đổi thành `sec-h`.
- **"Không tải được danh bạ" khi chưa có hội viên**: danh bạ chỉ hỏi lõi khi có hội viên đã có khoá; lõi lỗi thì quản trị viên thấy mã lỗi thật trên trang.
- Dòng ghi nguồn bản đồ (Leaflet / OpenStreetMap) giữ cỡ chữ nhỏ.
- Bài test: fix-smoke 13/13, admin-smoke 46/46.

## 0.8.2 — 2026-11-26

- **Màu nhấn của Hub** ở 14 · Cài đặt: chủ Hub đặt màu (ô chọn màu) hoặc dùng màu của plugin ngành; thứ tự chủ Hub > plugin ngành > mặc định. Màu sai dạng bị bỏ.
- Khi không plugin ngành nào cấp dữ liệu, thông báo wp-admin nói rõ: bật plugin ngành (ví dụ WP Vegan Hub). Màu đỏ `#c8102e` là màu mặc định của Bizcity trong trường hợp này.
- `tests/fix-smoke.php` 9/9.

## 0.8.1 — 2026-11-26 (sửa lỗi giao diện trên veganhub.net)

- **Chữ trang chủ trống**: bản đệm dữ liệu ngành của client cũ (0.4, chưa có `persona`) bị dùng lại. Khoá đệm nay tính cả phiên bản client; cấu hình đọc ra luôn được bù đủ khoá từ mặc định.
- **Menu điện thoại, cờ ngôn ngữ, hộp đăng nhập của theme lộ ra cuối trang Hub**: trên trang Hub, hook `wp_body_open` / `wp_footer` của theme bị gỡ (hook của plugin giữ nguyên); thêm lưới CSS ẩn khung lightbox / menu điện thoại của Flatsome.
- **CSS mới không tới khách**: site xoá `?ver=` và Cloudflare giữ tệp tĩnh 1 năm ⇒ tài sản của plugin nay mang `?ohcl=<phiên bản>`.
- Dòng chẩn đoán trong mã nguồn trang: `<!-- open-hub-client 0.8.1 · ngành: … -->` (đang dùng dữ liệu ngành nào, hay mặc định).
- `tests/fix-smoke.php` 6/6 (trả khoá đệm về kiểu cũ ⇒ FAIL).

## 0.8.0 — 2026-11-25 (F3: trang WordPress + menu, giống templates/v2 của router)

- **Mẫu trang** "Open Hub — <tên trang>" (khoá `open-hub:<trang>`) cho 12 trang Hub. Trang WordPress dùng mẫu được Hub dựng, ở đường dẫn chủ Hub đặt; khi đã đăng, mọi link của Hub trỏ về trang đó (đệm 1 ngày, xoá khi lưu / xoá trang).
- **Tự tạo trang còn thiếu** (đăng ngay hoặc nháp), chỉ cho module đang bật; chạy lại không tạo trùng.
- **Menu WordPress "Menu Hub"**: Trang chủ · nhóm → trang con · Tin tức. Chỉ thêm mục còn thiếu, không xoá / không sắp lại mục chủ Hub đã sửa. Gán được vào vị trí menu của theme; **đầu trang và chân trang của Hub đọc menu này** (sửa một lần ở Giao diện › Menu). Module tắt ⇒ mục ẩn khỏi đầu trang, menu giữ nguyên.
- **Chạy tự động một lần** khi chủ Hub vào wp-admin sau khi cài / cập nhật: tạo trang (đăng), dựng menu, gán vào vị trí menu **còn trống** đầu tiên của theme (không bao giờ đè menu đang dùng).
- Màn mới **Hub › Trang & menu**: bảng trang (đã đăng / nháp / chưa có / module tắt), nút tạo, nút cập nhật menu + chọn vị trí.
- My Hub đổi đường dẫn ⇒ các mục đi qua `?tab=`.
- `tests/menu-smoke.php` 21/21 (cho phép đè vị trí menu đang dùng ⇒ FAIL; bỏ kiểm tra mục đã có ⇒ FAIL).

## 0.7.0 — 2026-11-25 (F2: đủ trang, mặc định thay toàn bộ giao diện theme)

- **Sổ trang một chỗ** (`OHCL_Front::pages()`, filter `ohcl_front_pages`), giống `templates/v2` của router: thêm trang = một dòng + một tệp mẫu. Trang có module tắt biến khỏi menu, chân trang, đường dẫn.
- **Trang mới** theo sitemap §12: `/quyen-loi/` (8 trụ cột), `/cong-nghe-0d/`, `/giao-thuong/` (cơ sở V4 theo loại hình), `/su-kien/` (chương trình theo dịp + chuyên mục `su-kien`), `/hoc-vien/`, `/coach/`, `/tro-thanh-coach/` (form ứng tuyển), `/so-do-trang/`.
- **My Hub** `/my-hub/<mục>/` (§13, mockup F5–F6): tổng quan, công nghệ của tôi, hồ sơ doanh nghiệp (hội viên tự sửa địa chỉ, giờ, website, Zalo, đồng ý công khai), chứng nhận của tôi (QR), lộ trình 90 ngày tính từ dữ liệu thật, gửi hỗ trợ. Gắn tài khoản theo `_bzm_user`, lần đầu theo email trùng **duy nhất**; trùng nhiều hồ sơ / đã thuộc tài khoản khác ⇒ không đoán, báo rõ. Số chưa đo hiện "—".
- **Đầu trang hai tầng** + menu lớn (details: mở bằng chạm trên điện thoại, không JS), My Hub khi đã đăng nhập, Tin tức khi site có bài. **Chân trang** liệt kê đủ nhóm.
- **Mặc định vẽ cả site trong khung Hub** (`template_include`): bài viết, trang, tin tức, chuyên mục, tìm kiếm, 404. Ở đó chỉ bỏ CSS / JS **của theme**; tài sản của plugin khác giữ nguyên (shortcode vẫn chạy). Cửa hàng / sản phẩm WooCommerce giữ theme. Công tắc + danh sách ID trang giữ theme ở 14 · Cài đặt; filter `ohcl_front_takeover`.
- **Ứng tuyển Coach** (`OHCL_Coach`, CPT `bz_hub_coach_app`): nonce, bẫy bot, 3 lần / giờ / IP; màn **04 · Coach** duyệt / không phù hợp. Member 360° hiện tài khoản My Hub và yêu cầu hỗ trợ.
- `tests/pages-smoke.php` 41/41 (gỡ điều kiện "email duy nhất" ⇒ FAIL; bỏ loại trừ WooCommerce ⇒ FAIL); `admin-smoke` 43/43.

## 0.6.0 — 2026-11-25 (Đ2 bước 1: xác minh & chứng nhận, module 06)

- **Giấy chứng nhận ngành** (`OHCL_Certs`, CPT `bz_hub_cert`, không bảng mới): cấp theo bộ tiêu chuẩn của hợp đồng ngành, **chỉ khi đạt mọi tiêu chí**, có bằng chứng, cơ sở đã Verified (V3). Mã `<TIỀN TỐ>-CERT-<năm>-<số>`.
- **Gia hạn** = cấp lại cùng chuẩn: giữ mã, lưu kỳ cũ. **Thu hồi** hai bước, lý do công khai; cấp lại sau thu hồi là giấy mới.
- **Trang `/chung-nhan/`** (mockup F3): bốn loại ghi nhận, bộ tiêu chuẩn, tra cứu theo mã / QR với bốn kết quả (còn hiệu lực · hết hạn · đã thu hồi · không tìm thấy), gửi phản ánh (nonce, bẫy bot, 3 lần / giờ / IP). Có `?ma=` (quét QR) ⇒ kết quả lên đầu trang.
- **Màn 06 · Chứng nhận** (mockup O8): số liệu, lọc trạng thái, phản ánh, giấy in có QR; cấp / gia hạn ngay trong Member 360°.
- Hành trình **bậc 6 "Đạt chuẩn chuyên ngành"**; huy hiệu "Certified · <chuẩn>" trên hồ sơ công khai.
- QR: qrcode-generator 1.4.4 (MIT) đóng gói trong plugin (`assets/vendor/qrcode`), không gọi dịch vụ ngoài.
- Hợp đồng ngành @2: khoá tuỳ chọn `cert_prefix`; sự kiện chỉ đọc mới `cert_revoked`.
- Module 06 tắt ⇒ không menu, không trang, không huy hiệu; dữ liệu giữ nguyên.
- `tests/cert-smoke.php` 35/35 (gỡ điều kiện "đủ tiêu chí" ⇒ FAIL; để lộ ghi chú ra tra cứu ⇒ FAIL); `admin-smoke` thêm 8 bước (38/38).

## 0.5.0 — 2026-11-25 (cổng cộng đồng P1–P4)

- **Cổng cộng đồng** (`OHCL_Front`, mockup persona P1–P4), khung riêng, bỏ CSS của theme trên các trang Hub:
  - **Trang chủ cổng ngành** thay trang chủ của theme (công tắc ở 14 · Cài đặt): hero theo persona, lối tắt, tìm nhanh, bản đồ, tin tức thật (bài viết của site), danh mục, số liệu thật (sự kiện "—").
  - **`/ban-do/`** danh bạ + bản đồ: lọc từ khoá / loại hình / tỉnh phía máy chủ; ba trạng thái có dữ liệu · trống · lỗi.
  - **`/co-so/<id>-<tên>/`** hồ sơ cơ sở: chỉ trường công khai, nút Zalo tới trợ lý của chính cơ sở; không phải V4 ⇒ 404.
  - **`/tro-thanh-hoi-vien/`** quyền lợi + form đăng ký ⇒ hồ sơ mới ở 02 · Hội viên (nguồn web); chống spam: nonce, bẫy bot, 5 lần / giờ / IP; lỗi bốn trường.
- **Chỉ hồ sơ V4 ra ngoài** (R-MAP): đồng ý công khai + toạ độ + khoá đang chạy và dùng trong 30 ngày + leader xác minh + đang hoạt động. Toạ độ công khai làm tròn 4 số; không lộ điện thoại / email. Danh sách công khai đệm 10 phút, xoá khi hồ sơ đổi; lõi vắng ⇒ báo lỗi, không đệm.
- Member 360°: trường giờ mở cửa, toạ độ, ô **đồng ý công khai**, dòng **Bản đồ V1–V4** kèm lý do còn thiếu, khối **Xác minh (V3)** (bằng chứng, hạn mặc định 12 tháng, bỏ xác minh).
- Hợp đồng ngành @2 thêm khoá tuỳ chọn **`persona`** (khẩu hiệu, tiêu đề, giá trị, quyền lợi, danh mục) — bản nhỏ, plugin ngành cũ vẫn chạy.
- Bản đồ: Leaflet 1.9.4 đóng gói trong plugin (BSD-2, `assets/vendor/leaflet`) + nền OpenStreetMap; ghim màu ngành.
- `tests/front-smoke.php` 25/25 (bậc V1–V4, chỉ V4 công khai, không lộ dữ liệu riêng, escape, đăng ký, chống spam, lõi vắng); gỡ điều kiện "đồng ý công khai" ⇒ 6 FAIL.

## 0.4.0 — 2026-11-25 (Đ1c Member 360°)

- Hồ sơ hội viên = CPT `bz_hub_member` trên site Hub (không bảng mới): meta lõi `_bzm_*` (phân khúc, tỉnh, liên hệ, website, Coach, trạng thái, khoá `key_id` + `key_prefix`), trường ngành `_bzi_*` theo hợp đồng @2.
- **02 · Hội viên**: 8 chặng hành trình có số đếm, lọc chặng / phân khúc / tìm; bảng trạng thái độc lập (hành trình, trợ lý AI từ khoá ở lõi, xác minh, hội viên, Coach); thêm / sửa hồ sơ với trường ngành theo kiểu (multi, select, number, bool, url, text).
- **Member 360°**: trạng thái độc lập, thông tin ngành, **tiếp nhận**, **cấp khoá dùng thử ngay từ hồ sơ** (khoá hiện một lần, hồ sơ chỉ giữ `key_id` + tiền tố; khoá đang chạy ⇒ không cấp lần hai), đổi trạng thái hội viên, hành trình 8 bậc (tính tới bậc 5; 6–8 chờ module 06/08), tab của plugin ngành qua ô `ohcl_member360_tabs`.
- **Tổng quan câu 1** đếm từ hồ sơ thật: tổng, đang hoạt động, đã xác minh, theo phân khúc.
- admin-smoke 30/30 (thêm → tiếp nhận → cấp khoá từ hồ sơ → lọc chặng → Tổng quan).

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
