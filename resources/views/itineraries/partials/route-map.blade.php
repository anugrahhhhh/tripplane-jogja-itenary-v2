{{-- Peta rute per hari (Leaflet). Dipakai halaman itinerary dan halaman publik; taruh di slot head. --}}
@props(['mapPointsByDay'])
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
    window.itineraryDays = @json($mapPointsByDay);

    function itineraryMap() {
        return {
            activeDay: 1,
            map: null,
            layer: null,
            init() {
                this.map = L.map('route-map').setView([-7.8014, 110.3644], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(this.map);
                this.layer = L.layerGroup().addTo(this.map);
                this.renderDay();
            },
            renderDay() {
                this.layer.clearLayers();
                const points = window.itineraryDays[this.activeDay] || [];
                if (points.length === 0) {
                    return;
                }
                const latlngs = points.map((p, idx) => {
                    // Teks polos (bukan HTML) agar nama destinasi aman.
                    const popup = document.createElement('span');
                    popup.textContent = (idx + 1) + '. ' + p.name;
                    L.marker([p.lat, p.lng]).bindPopup(popup).addTo(this.layer);
                    return [p.lat, p.lng];
                });
                if (latlngs.length > 1) {
                    L.polyline(latlngs, { color: '#2F6B5A', weight: 3 }).addTo(this.layer);
                }
                this.map.fitBounds(latlngs, { padding: [30, 30] });
            },
        };
    }
</script>
