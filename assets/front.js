/* [2026-11-25 09:08 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — bản đồ cổng cộng đồng: Leaflet đóng gói trong plugin + nền OpenStreetMap (MAP-Q1). Ghim chỉ từ dữ liệu V4 do máy chủ dựng (toạ độ đã làm tròn); không gọi API nào khác. */
(function () {
	'use strict';
	if (!window.L) { return; }
	var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
	// [2026-11-25 09:17 PM Johnny Chu - Chu Hoàng Anh] OHCL-F1 — ghim màu ngành (CSS .ohcl-pin), không dùng ghim xanh dương mặc định.
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
		if (bounds.length === 1) { map.setView(bounds[0], 15); } else { map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 }); }
	});
})();
