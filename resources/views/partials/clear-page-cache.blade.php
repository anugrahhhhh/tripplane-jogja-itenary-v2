{{-- Pengaman untuk browser tanpa Clear-Site-Data: hapus cache halaman SW. Aset (*-assets) dibiarkan. --}}
@once
<script data-clear-page-cache>
    (function () {
        try {
            if (!('caches' in window)) return;
            caches.keys().then(function (keys) {
                keys.forEach(function (key) {
                    if (key.indexOf('jogjatrip-') === 0 && key.slice(-6) === '-pages') {
                        caches.delete(key).catch(function () {});
                    }
                });
            }).catch(function () {});
        } catch (e) {}
    })();
</script>
@endonce
