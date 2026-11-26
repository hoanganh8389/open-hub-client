/* [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — bản đồ cổng cộng đồng: Leaflet đóng gói trong plugin + nền OpenStreetMap (MAP-Q1). Ghim chỉ từ dữ liệu V4 do máy chủ dựng (toạ độ đã làm tròn); không gọi API nào khác. */
(function () {
	'use strict';
	if (!window.L) { return; }
	var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
	// [2026-11-25 09:17 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — ghim màu ngành (CSS .ohcl-pin), không dùng ghim xanh dương mặc định.
	var maps = [];
	var icon = L.divIcon({ className: 'ohcl-pin', iconSize: [22, 22], popupAnchor: [0, -24] });
	document.querySelectorAll('[data-ohcl-map]').forEach(function (el) {
		var pins = [];
		try { pins = JSON.parse(el.getAttribute('data-pins') || '[]'); } catch (e) { pins = []; }
		pins = pins.filter(function (p) { return typeof p.lat === 'number' && typeof p.lng === 'number' && (p.lat || p.lng); });
		var map = L.map(el, { scrollWheelZoom: false, attributionControl: true });
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 18,
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" rel="noopener" target="_blank">OpenStreetMap</a>'
		}).addTo(map);
		if (!pins.length) { map.setView([16.05, 107.5], 5); return; } // Việt Nam
		var bounds = [];
		pins.forEach(function (p) {
			var m = L.marker([p.lat, p.lng], { icon: icon, title: p.t }).addTo(map);
			m.bindPopup('<b>' + esc(p.t) + '</b><br>' + esc([p.s, p.p].filter(Boolean).join(' · ')) + (p.u ? '<br><a href="' + esc(p.u) + '">Xem chi tiết →</a>' : ''));
			bounds.push([p.lat, p.lng]);
		});
		var c = (el.getAttribute('data-center') || '').split(',').map(Number);
		if (c.length === 2 && isFinite(c[0]) && isFinite(c[1])) { map.setView(c, Number(el.getAttribute('data-zoom')) || 12); } // [2026-11-26 10:25 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX4 — tâm do ngành / chủ Hub đặt
		else if (bounds.length === 1) { map.setView(bounds[0], 15); } else { map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 }); }
		maps.push({ map: map, pins: pins, el: el });
	});

	/* [2026-11-26 10:25 AM Johnny Chu - Chu Hoàng Anh] OHCL-FIX4 — "Gần tôi": vị trí lấy trong trình duyệt, không gửi lên máy chủ; xếp cơ sở theo khoảng cách, đưa bản đồ về vị trí người xem. */
	var km = function (a, b) {
		var r = Math.PI / 180, dLat = (b[0] - a[0]) * r, dLng = (b[1] - a[1]) * r;
		var h = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(a[0] * r) * Math.cos(b[0] * r) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
		return 12742 * Math.asin(Math.sqrt(h));
	};
	document.querySelectorAll('[data-ohcl-locate]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var scope = btn.closest('section, .dir, .pnl') || document;
			var box = scope.querySelector('[data-ohcl-near]') || document.querySelector('[data-ohcl-near]');
			var m = maps.filter(function (x) { return scope.contains(x.el); })[0] || maps[0];
			var say = function (html) { if (box) { box.hidden = false; box.innerHTML = html; } };
			if (!m) { return; }
			if (!navigator.geolocation) { say('<p class="note">Trình duyệt không hỗ trợ lấy vị trí.</p>'); return; }
			btn.disabled = true; btn.textContent = 'Đang tìm…';
			navigator.geolocation.getCurrentPosition(function (pos) {
				btn.disabled = false; btn.textContent = 'Gần tôi';
				var me = [pos.coords.latitude, pos.coords.longitude];
				if (m.me) { m.map.removeLayer(m.me); }
				m.me = L.circleMarker(me, { radius: 8, color: '#fff', weight: 3, fillColor: '#1a73e8', fillOpacity: 1 }).addTo(m.map).bindTooltip('Bạn ở đây');
				var near = m.pins.map(function (p) { return { p: p, d: km(me, [p.lat, p.lng]) }; }).sort(function (x, y) { return x.d - y.d; }).slice(0, 5);
				if (!near.length) { m.map.setView(me, 14); say('<p class="note">Chưa có cơ sở nào trên bản đồ.</p>'); return; }
				m.map.fitBounds([me].concat(near.slice(0, 3).map(function (x) { return [x.p.lat, x.p.lng]; })), { padding: [40, 40], maxZoom: 15 });
				say('<b>Gần bạn nhất</b><ol>' + near.map(function (x) {
					var d = x.d < 1 ? Math.round(x.d * 1000) + ' m' : (Math.round(x.d * 10) / 10).toString().replace('.', ',') + ' km';
					return '<li><a href="' + esc(x.p.u) + '">' + esc(x.p.t) + '</a><span>' + esc([x.p.s, d].filter(Boolean).join(' · ')) + '</span></li>';
				}).join('') + '</ol>');
			}, function (err) {
				btn.disabled = false; btn.textContent = 'Gần tôi';
				say('<p class="note">' + (err && err.code === 1 ? 'Bạn chưa cho phép lấy vị trí. Bật quyền vị trí cho trang này rồi bấm lại.' : 'Chưa lấy được vị trí. Thử lại sau ít giây.') + '</p>');
			}, { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 });
		});
	});
})();
