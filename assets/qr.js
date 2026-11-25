/* [2026-11-25 11:02 PM Johnny Chu - Chu Hoàng Anh] OHCL-D2A — vẽ mã QR tra cứu chứng nhận ([data-ohcl-qr] = URL tra cứu) bằng qrcode-generator 1.4.4 (MIT, đóng gói trong plugin). Không gọi dịch vụ ngoài. */
(function () {
	'use strict';
	if (typeof window.qrcode !== 'function') { return; }
	document.querySelectorAll('[data-ohcl-qr]').forEach(function (el) {
		var url = el.getAttribute('data-ohcl-qr') || '';
		if (!/^https?:\/\//.test(url)) { return; }
		try {
			var q = window.qrcode(0, 'M');
			q.addData(url);
			q.make();
			el.innerHTML = q.createSvgTag({ cellSize: 4, margin: 2, scalable: true });
		} catch (e) { /* URL quá dài cho QR: để trống, mã chữ vẫn tra được */ }
	});
})();
