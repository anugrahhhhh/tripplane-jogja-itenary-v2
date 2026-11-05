@php $destination = $destination ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="name" value="Nama Destinasi" />
        <x-text-input id="name" name="name" type="text" class="mt-1.5 block w-full" value="{{ old('name', $destination?->name) }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="category_id" value="Kategori" />
        <select id="category_id" name="category_id" required class="input mt-1.5">
            <option value="">Pilih kategori...</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $destination?->category_id) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
    </div>
</div>

<div>
    <x-input-label for="description" value="Deskripsi" />
    <textarea id="description" name="description" rows="4" required class="input mt-1.5">{{ old('description', $destination?->description) }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-1" />
</div>

<div>
    <x-input-label for="recommendation_reason" value="Alasan Direkomendasikan (opsional)" />
    <textarea id="recommendation_reason" name="recommendation_reason" rows="2" class="input mt-1.5">{{ old('recommendation_reason', $destination?->recommendation_reason) }}</textarea>
    <x-input-error :messages="$errors->get('recommendation_reason')" class="mt-1" />
</div>

<div>
    <x-input-label for="address" value="Alamat" />
    <x-text-input id="address" name="address" type="text" class="mt-1.5 block w-full" value="{{ old('address', $destination?->address) }}" required />
    <x-input-error :messages="$errors->get('address')" class="mt-1" />
</div>

<div>
    <x-input-label value="Koordinat Lokasi" />
    <p class="text-xs text-ink/50 mb-2">Klik pada peta untuk menentukan titik koordinat, atau isi manual di bawah.</p>
    <div id="picker-map" class="h-72 rounded-2xl ring-1 ring-primary-100 overflow-hidden z-0"></div>

    <div class="grid grid-cols-2 gap-4 mt-3">
        <div>
            <x-input-label for="latitude" value="Latitude" />
            <x-text-input id="latitude" name="latitude" type="text" class="mt-1.5 block w-full" value="{{ old('latitude', $destination?->latitude) }}" required />
            <x-input-error :messages="$errors->get('latitude')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="longitude" value="Longitude" />
            <x-text-input id="longitude" name="longitude" type="text" class="mt-1.5 block w-full" value="{{ old('longitude', $destination?->longitude) }}" required />
            <x-input-error :messages="$errors->get('longitude')" class="mt-1" />
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="price_min" value="Harga Minimum (Rp)" />
        <x-text-input id="price_min" name="price_min" type="number" min="0" class="mt-1.5 block w-full" value="{{ old('price_min', $destination?->price_min ?? 0) }}" required />
        <x-input-error :messages="$errors->get('price_min')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="price_max" value="Harga Maksimum (Rp)" />
        <x-text-input id="price_max" name="price_max" type="number" min="0" class="mt-1.5 block w-full" value="{{ old('price_max', $destination?->price_max ?? 0) }}" required />
        <x-input-error :messages="$errors->get('price_max')" class="mt-1" />
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="open_time" value="Jam Buka" />
        <x-text-input id="open_time" name="open_time" type="time" class="mt-1.5 block w-full" value="{{ old('open_time', $destination ? \Illuminate\Support\Carbon::parse($destination->open_time)->format('H:i') : '') }}" required />
        <x-input-error :messages="$errors->get('open_time')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="close_time" value="Jam Tutup" />
        <x-text-input id="close_time" name="close_time" type="time" class="mt-1.5 block w-full" value="{{ old('close_time', $destination ? \Illuminate\Support\Carbon::parse($destination->close_time)->format('H:i') : '') }}" required />
        <p class="mt-1 text-xs text-ink/50">Untuk destinasi yang buka lewat tengah malam, isi jam tutup lebih kecil dari jam buka (contoh: buka 18:00, tutup 02:00).</p>
        <x-input-error :messages="$errors->get('close_time')" class="mt-1" />
    </div>
</div>

<div>
    <x-input-label value="Hari Tutup (mingguan)" />
    <div class="mt-2 flex flex-wrap gap-2">
        @foreach (\App\Support\Weekday::all() as $iso => $dayName)
            <label class="inline-flex items-center gap-2 rounded-xl border border-primary-100 bg-white px-3 py-2 cursor-pointer hover:border-primary-300 transition has-[:checked]:border-red-300 has-[:checked]:bg-red-50">
                <input type="checkbox" name="closed_days[]" value="{{ $iso }}" class="rounded border-ink/20 text-red-500 focus:ring-red-300" @checked(in_array($iso, array_map('intval', (array) old('closed_days', $destination?->closed_days ?? [])), true))>
                <span class="text-sm font-medium text-ink/80">{{ $dayName }}</span>
            </label>
        @endforeach
    </div>
    <p class="mt-1 text-xs text-ink/50">Kosongkan bila destinasi buka setiap hari.</p>
    <x-input-error :messages="$errors->get('closed_days')" class="mt-1" />
    <x-input-error :messages="$errors->get('closed_days.*')" class="mt-1" />
</div>

<div>
    <label class="flex items-center gap-3 rounded-xl bg-primary-50/60 ring-1 ring-primary-100 px-4 py-3 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" class="w-5 h-5 rounded-md border-ink/20 text-primary focus:ring-primary" @checked(old('is_active', $destination?->is_active ?? true))>
        <span>
            <span class="block text-sm font-semibold text-ink">Tampilkan destinasi ini di katalog (aktif)</span>
            <span class="block text-xs text-ink/50">Destinasi nonaktif tidak terlihat oleh wisatawan.</span>
        </span>
    </label>
</div>

<div>
    <x-input-label for="cover_photo" value="Foto Sampul" />
    @if ($destination?->cover_photo)
        <img src="{{ asset('storage/'.$destination->cover_photo) }}" alt="Foto sampul saat ini" class="mt-2 h-36 rounded-xl object-cover ring-1 ring-primary-100" loading="lazy" decoding="async">
    @endif
    <input id="cover_photo" name="cover_photo" type="file" accept="image/*" class="mt-2 block w-full text-sm text-ink/70 rounded-xl border-2 border-dashed border-primary-100 bg-cream/50 p-3 file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-white file:text-sm file:font-semibold hover:border-primary-300 transition">
    <x-input-error :messages="$errors->get('cover_photo')" class="mt-1" />
</div>

@if ($destination?->photos->isNotEmpty())
    <div>
        <x-input-label value="Galeri Foto Saat Ini" />
        <div class="mt-2 grid grid-cols-3 sm:grid-cols-5 gap-2">
            @foreach ($destination->photos as $photo)
                <div class="relative">
                    <img src="{{ asset('storage/'.$photo->path) }}" alt="Foto galeri" class="h-24 w-full object-cover rounded-xl ring-1 ring-primary-100" loading="lazy" decoding="async">
                    <button type="submit" form="delete-photo-{{ $photo->id }}" title="Hapus foto" aria-label="Hapus foto" class="absolute top-1.5 right-1.5 w-7 h-7 rounded-full bg-white/95 text-red-500 shadow flex items-center justify-center hover:bg-red-50">&times;</button>
                </div>
                <form id="delete-photo-{{ $photo->id }}" action="{{ route('admin.destinations.photos.destroy', [$destination, $photo]) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        </div>
    </div>
@endif

<div>
    <x-input-label for="photos" value="Tambah Foto Galeri (bisa pilih beberapa)" />
    <input id="photos" name="photos[]" type="file" accept="image/*" multiple class="mt-2 block w-full text-sm text-ink/70 rounded-xl border-2 border-dashed border-primary-100 bg-cream/50 p-3 file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-white file:text-sm file:font-semibold hover:border-primary-300 transition">
    <x-input-error :messages="$errors->get('photos.*')" class="mt-1" />
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const startLat = parseFloat(latInput.value) || -7.8014;
        const startLng = parseFloat(lngInput.value) || 110.3644;

        const map = L.map('picker-map').setView([startLat, startLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        let marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);

        function updateInputs(lat, lng) {
            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
        }

        marker.on('dragend', function () {
            const pos = marker.getLatLng();
            updateInputs(pos.lat, pos.lng);
        });

        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            updateInputs(e.latlng.lat, e.latlng.lng);
        });
    });
</script>
