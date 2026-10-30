# Paket 1 (Cache Aman, Update Library, Biaya, Hari Tutup, Link Berbagi) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membersihkan cache halaman pribadi saat logout, menutup celah library, menambah estimasi biaya, peringatan hari tutup, dan link publik berbagi itinerary.

**Architecture:** Perubahan memakai ulang titik yang ada: `ScheduleValidator` (peringatan), `buildDayInsights` (dipindah ke service `ItineraryInsights` untuk dipakai halaman itinerary dan halaman publik), dan `public/sw.js`. Helper kecil (`ClearSiteCache`, `Rupiah`, `Weekday`) menghindari duplikasi.

**Tech Stack:** Laravel 12.69, PHP 8.2, Blade + Alpine.js + Tailwind, PHPUnit (sqlite in-memory), DomPDF (`Barryvdh\DomPDF\Facade\Pdf`, sudah terpasang).

**Spec:** `docs/superpowers/specs/2026-10-30-paket-1-perbaikan-fitur-design.md`

## Global Constraints

- Semua teks UI dan pesan error **berbahasa Indonesia**.
- Format rupiah: `Rp 50.000` (spasi setelah `Rp`, pemisah ribuan titik); rentang memakai ` – ` (en dash berspasi).
- Hari ISO 8601: `1`=Senin … `7`=Minggu; nama: `Senin, Selasa, Rabu, Kamis, Jumat, Sabtu, Minggu`.
- `traveler_count`: integer 1–50, default 1; pesan: "Jumlah peserta harus antara 1 dan 50."
- `share_token`: `Str::random(40)`, kolom `string(64)` nullable unique, tidak di `$fillable`.
- Halaman publik berbagi: tanpa login, `throttle:60,1`, header `X-Robots-Tag: noindex, nofollow`, tanpa nama/email pemilik, kolaborator, atau `notes`.
- Header logout: `Clear-Site-Data: "cache"` (persis, dengan tanda kutip).
- Header halaman sensitif: `Cache-Control` mengandung `no-store` dan `private`.
- Cache SW versi baru: `jogjatrip-v2`.
- Tidak ada data hari tutup yang dikarang di seeder.
- Seluruh test yang ada (198) harus tetap lulus.

## Review Focus

1. **Itinerary lama (sebelum migrasi)** memiliki `traveler_count` default 1 → estimasi tetap tampil tanpa error. Test di Task 4.
2. **Destinasi dengan `closed_days` null** (semua destinasi lama) → tidak ada peringatan hari tutup, tidak error. Test di Task 5.
3. **Itinerary tanpa destinasi** → kartu biaya menampilkan "Gratis"/tidak error, halaman publik tetap terbuka. Test di Task 6 dan Task 7.
4. **Pemilik membuat ulang link** → link lama langsung 404. Test di Task 7.
5. **Kolaborator membuka halaman itinerary** → tidak melihat kontrol link berbagi, dan POST/DELETE berbagi → 403. Test di Task 7.

---

## File Structure

| File | Tanggung jawab |
|---|---|
| `app/Support/ClearSiteCache.php` | Pasang header `Clear-Site-Data` |
| `app/Http/Middleware/PreventSensitiveCaching.php` | `Cache-Control: no-store, private` untuk route sensitif |
| `public/sw.js` | Aturan simpan halaman yang lebih ketat, versi v2 |
| `resources/views/partials/clear-page-cache.blade.php` | Skrip pembersih cache di halaman login |
| `app/Support/Rupiah.php` | Format rupiah |
| `app/Support/Weekday.php` | Nama hari ISO |
| `app/Services/ItineraryCostEstimator.php` | Estimasi biaya |
| `app/Services/ItineraryInsights.php` | Peta/peringatan/waktu tempuh per hari (pindahan dari controller) |
| `app/Http/Controllers/ItineraryShareController.php` | Buat/matikan link (pemilik) |
| `app/Http/Controllers/SharedItineraryController.php` | Halaman publik |
| `resources/views/itineraries/partials/cost-summary.blade.php` | Kartu biaya (dipakai show, publik) |
| `resources/views/itineraries/shared.blade.php` | Halaman publik |

---

### Task 1: Update library (A2)

**Files:** `composer.json`, `composer.lock`

- [ ] **Step 1:** `composer audit --format=plain` — catat advisory `league/commonmark` dan `symfony/mime` (kondisi awal).
- [ ] **Step 2:** `composer update league/commonmark symfony/mime --with-dependencies`. Jika composer menolak tanpa upgrade major / mengubah `composer.json` constraint utama → BERHENTI, laporkan BLOCKED dengan output.
- [ ] **Step 3:** `composer audit --format=plain` → Expected: tidak ada advisory untuk kedua package.
- [ ] **Step 4:** `php artisan test` → Expected: 198 passed.
- [ ] **Step 5: Commit** — `chore(deps): update league/commonmark and symfony/mime for security advisories`

---

### Task 2: Header logout & halaman sensitif (A1 server)

**Files:**
- Create: `app/Support/ClearSiteCache.php`, `app/Http/Middleware/PreventSensitiveCaching.php`
- Modify: `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (`destroy`), `app/Http/Middleware/LogoutIdleSessions.php` (cabang idle: redirect & JSON), `app/Http/Controllers/Auth/NotMeController.php` (`confirm`), `bootstrap/app.php`
- Test: `tests/Feature/Security/CacheHeadersTest.php`

**Interfaces — Produces:**
- `ClearSiteCache::apply(\Symfony\Component\HttpFoundation\Response $response): \Symfony\Component\HttpFoundation\Response` — set `Clear-Site-Data: "cache"`, return response.
- `PreventSensitiveCaching` — didaftarkan di grup `web` (append, setelah middleware yang ada). Route sensitif: `two-factor.*`, `profile.*`, `security.not-me*`, `notifications.*`, `admin.*`, `password.*`, `login`, `register`, `verification.*` → `$response->headers->set('Cache-Control', 'no-store, private')`.

- [ ] **Step 1: Tulis test yang gagal:**

```php
test_manual_logout_clears_site_cache()            // post(route('logout'))->assertHeader('Clear-Site-Data', '"cache"')
test_idle_form_logout_clears_site_cache()         // post(route('logout'), ['reason'=>'idle'])->assertHeader(...)
test_idle_expiry_redirect_clears_site_cache()     // actingAs, get('/profile'), travel(121) seconds, get('/profile')->assertHeader(...)
test_idle_expiry_json_clears_site_cache()         // ... getJson('/profile')->assertStatus(401)->assertHeader(...)
test_not_me_confirm_clears_site_cache()           // signed URL POST → assertHeader(...)
test_sensitive_pages_are_not_cacheable()          // as user: get(route('profile.edit')), get(route('notifications.index')); as guest: get(route('login')) → Cache-Control contains 'no-store' and 'private'
test_public_pages_remain_cacheable()              // get(route('destinations.index')) → Cache-Control does not contain 'no-store'
```

- [ ] **Step 2:** `php artisan test --filter=CacheHeadersTest` → FAIL.
- [ ] **Step 3:** Implementasi. Wrap respons logout/idle/not-me dengan `ClearSiteCache::apply(...)`.
- [ ] **Step 4:** `php artisan test` → semua PASS.
- [ ] **Step 5: Commit** — `feat(security): clear browser cache on logout and mark sensitive pages no-store`

---

### Task 3: Service worker & pembersih cache di halaman login (A1 klien)

**Files:**
- Modify: `public/sw.js`, `resources/views/auth/login.blade.php`, `resources/views/auth/admin-login.blade.php`
- Create: `resources/views/partials/clear-page-cache.blade.php`
- Test: `tests/Feature/Security/ServiceWorkerTest.php`

**Interfaces:** Consumes header `Cache-Control: no-store` dari Task 2.

- [ ] **Step 1: Tulis test yang gagal:**

```php
test_service_worker_uses_v2_cache()                    // file_get_contents(public_path('sw.js')) contains "jogjatrip-v2"
test_service_worker_skips_no_store_and_redirects()     // sw.js contains 'no-store' and 'redirected' and "response.type === 'basic'"
test_login_pages_include_page_cache_cleanup()          // get(route('login')) & get(route('admin.login')) assertSee('data-clear-page-cache', false)
```

- [ ] **Step 2:** Jalankan → FAIL.
- [ ] **Step 3:** `sw.js`: `CACHE_VERSION = 'jogjatrip-v2'`; di `networkFirstForPages` hanya `cache.put` bila `response.ok && !response.redirected && response.type === 'basic' && !(response.headers.get('Cache-Control') || '').includes('no-store')`. Partial: `<script data-clear-page-cache>` yang, jika `'caches' in window`, memanggil `caches.keys()` lalu `caches.delete` untuk nama yang diawali `jogjatrip-` dan diakhiri `-pages`; error ditelan. Include partial di kedua view login.
- [ ] **Step 4:** `php artisan test` → PASS.
- [ ] **Step 5: Commit** — `feat(pwa): stop caching private pages and purge page cache on login screens`

---

### Task 4: Jumlah peserta & estimator biaya (B1 data)

**Files:**
- Create: migrasi `database/migrations/2026_10_31_000001_add_traveler_count_to_itineraries_table.php`, `app/Support/Rupiah.php`, `app/Services/ItineraryCostEstimator.php`
- Modify: `app/Models/Itinerary.php` (`$fillable`, cast), `app/Http/Controllers/ItineraryController.php` (`store`, `update` validasi), `resources/views/itineraries/create.blade.php`, `resources/views/itineraries/edit.blade.php`, `database/factories/ItineraryFactory.php` (tambah `traveler_count` => 1)
- Test: `tests/Unit/RupiahTest.php`, `tests/Feature/Itinerary/ItineraryCostEstimatorTest.php`, `tests/Feature/Itinerary/TravelerCountTest.php`

**Interfaces — Produces:**
- `Rupiah::format(int $amount): string` → `"Rp 50.000"`; `Rupiah::range(int $min, int $max): string` → `"Gratis"` jika keduanya 0, `"Rp 50.000"` jika sama, selain itu `"Rp 50.000 – Rp 120.000"`.
- `ItineraryCostEstimator::estimate(Itinerary $itinerary): array` → `['days' => [int day => ['min'=>int,'max'=>int]], 'total' => ['min'=>int,'max'=>int], 'travelers' => int, 'group_total' => ['min'=>int,'max'=>int]]`. Pakai `$itinerary->items` + `destination` yang sudah di-load (`loadMissing('items.destination')`). Hari tanpa item tidak ada di `days`.

- [ ] **Step 1: Tulis test yang gagal:**

```php
// RupiahTest
test_format_uses_dot_thousands_separator()   // format(50000) === 'Rp 50.000'; format(0) === 'Rp 0'
test_range_variants()                        // range(0,0)==='Gratis'; range(15000,15000)==='Rp 15.000'; range(50000,120000)==='Rp 50.000 – Rp 120.000'
// ItineraryCostEstimatorTest
test_sums_per_day_and_total()                // 2 hari: day1 [10k–20k]+[0–0], day2 [5k–5k] → days[1]=[10000,20000], days[2]=[5000,5000], total=[15000,25000]
test_same_destination_twice_counts_twice()
test_group_total_multiplies_by_travelers()   // traveler_count=4 → group_total = total*4, travelers=4
test_empty_itinerary_has_zero_total()        // days=[], total=[0,0]                                   (Review Focus 3)
// TravelerCountTest
test_store_saves_traveler_count()            // post itineraries.store with traveler_count=3 → DB 3
test_traveler_count_out_of_range_rejected()  // 0 and 51 → assertSessionHasErrors(['traveler_count' => 'Jumlah peserta harus antara 1 dan 50.'])
test_update_changes_traveler_count()
test_existing_itineraries_default_to_one()   // row inserted without traveler_count → model value 1     (Review Focus 1)
```

- [ ] **Step 2:** Jalankan → FAIL.
- [ ] **Step 3:** Migrasi: `unsignedTinyInteger('traveler_count')->default(1)->after('end_date')`; `down()` drop. Validasi `traveler_count`: `['required','integer','min:1','max:50']` dengan pesan custom untuk `min`/`max`/`integer`. Form: input number `min=1 max=50`, default `old('traveler_count', $itinerary->traveler_count ?? 1)`, label "Jumlah peserta". AI & Today controller tidak diubah (default DB 1).
- [ ] **Step 4:** `php artisan test` → PASS.
- [ ] **Step 5: Commit** — `feat(itinerary): add traveler count and ticket cost estimator`

---

### Task 5: Hari tutup + `ItineraryInsights` (B3)

**Files:**
- Create: migrasi `2026_10_31_000002_add_closed_days_to_destinations_table.php`, `app/Support/Weekday.php`, `app/Services/ItineraryInsights.php`
- Modify: `app/Models/Destination.php` (`$fillable` + cast `array`), `app/Http/Controllers/Admin/DestinationController.php` (validasi + data), `resources/views/admin/destinations/_form.blade.php`, `resources/views/destinations/show.blade.php`, `app/Services/ScheduleValidator.php`, `app/Http/Controllers/ItineraryController.php` (pakai `ItineraryInsights`, hapus `buildDayInsights`/`travelInfoBetween` dari controller)
- Test: `tests/Unit/WeekdayTest.php`, `tests/Feature/Itinerary/ClosedDaysTest.php`, `tests/Feature/Admin/DestinationClosedDaysTest.php`

**Interfaces — Produces:**
- `Weekday::name(int $iso): string`; `Weekday::names(?array $isoDays): string` → `"Senin, Selasa"` (urut), `""` untuk null/kosong.
- `ScheduleValidator::validateDay(Collection $dayItems, ?\Carbon\CarbonInterface $date = null): array` — tambahan pesan `"Tutup setiap hari {Hari}."` bila `$date?->isoWeekday()` ada di `destination->closed_days`.
- `ItineraryInsights::build(Itinerary $itinerary): array` → `[$mapPointsByDay, $warningsByDay, $travelInfoByDay]` (perilaku sama dengan `buildDayInsights` lama + `$date = $itinerary->start_date->copy()->addDays($day - 1)`).

- [ ] **Step 1: Tulis test yang gagal:**

```php
// WeekdayTest: name(1)==='Senin', name(7)==='Minggu', names([3,1])==='Senin, Rabu', names(null)===''
// DestinationClosedDaysTest (admin)
test_admin_can_save_closed_days_sorted()         // closed_days=[3,1] → DB [1,3]
test_no_checkbox_saves_null()
test_invalid_closed_days_rejected()              // [0], [8], [1,1] → errors on closed_days.*
test_detail_page_shows_closed_days()             // closed_days=[1] → get(destinations.show) assertSee('Tutup: Senin')
// ClosedDaysTest (itinerary)
test_warning_when_visit_falls_on_closed_day()    // start_date Monday 2026-11-02, item day 1, destination closed [1] → show page assertSee('Tutup setiap hari Senin.')
test_warning_uses_correct_date_across_weeks()    // start_date Sunday 2026-11-01, item day 2 (Monday) closed [1] → warning; item day 1 → none
test_no_warning_when_closed_days_null()          //                                                 (Review Focus 2)
test_validate_day_without_date_has_no_closed_warning()
test_pdf_includes_closed_day_warning()           // get(itineraries.pdf)->assertOk(); and render view('itineraries.pdf', the same data the controller passes) to a string containing 'Tutup setiap hari Senin.' (DomPDF output is binary)
```

- [ ] **Step 2:** Jalankan → FAIL.
- [ ] **Step 3:** Migrasi `json('closed_days')->nullable()->after('close_time')`. Admin: validasi `closed_days` `nullable|array`, `closed_days.*` `integer|between:1,7|distinct`; simpan `collect(...)->map(intval)->unique()->sort()->values()->all() ?: null`. Form: 7 checkbox `name="closed_days[]"` label `Weekday::name`. Detail destinasi: baris "Tutup: {Weekday::names}" di bawah jam operasional, hanya bila tidak kosong. Pindahkan logika insights ke `ItineraryInsights` (dependency `DistanceService` lewat container) dan pakai di `show` dan `pdf`.
- [ ] **Step 4:** `php artisan test` → PASS (termasuk test itinerary/PDF lama).
- [ ] **Step 5: Commit** — `feat(destinations): add closed days and warn when itinerary visits fall on them`

---

### Task 6: Tampilan biaya di itinerary & PDF (B1 tampilan)

**Files:**
- Create: `resources/views/itineraries/partials/cost-summary.blade.php` (props: `$cost` hasil estimator)
- Modify: `ItineraryController@show` & `@pdf` (kirim `cost`), `resources/views/itineraries/show.blade.php`, `resources/views/itineraries/pdf.blade.php`
- Test: `tests/Feature/Itinerary/CostDisplayTest.php`

**Interfaces:** Consumes `ItineraryCostEstimator::estimate`, `Rupiah::range` (Task 4).

Teks kartu: judul "Estimasi biaya", baris total `"{Rupiah::range(total)} per orang"` + bila travelers > 1 dan total > 0: `" · {Rupiah::range(group_total)} untuk {N} orang"`; baris per hari `"Hari {n}: {Rupiah::range(day)}"`; catatan `"Estimasi tiket masuk saja, belum termasuk transportasi, makan, dan parkir."`. Bila total 0–0: tampil `"Gratis"` tanpa "per orang".

- [ ] **Step 1: Tulis test yang gagal:**

```php
test_show_page_displays_cost_per_person_and_group()   // 4 orang, total 50k–120k → assertSee('Rp 50.000 – Rp 120.000 per orang') & assertSee('Rp 200.000 – Rp 480.000 untuk 4 orang')
test_single_traveler_hides_group_total()              // assertDontSee('untuk 1 orang')
test_free_itinerary_shows_gratis()
test_empty_itinerary_page_loads()                     // no items → assertOk() & assertSee('Gratis')          (Review Focus 3)
test_pdf_view_contains_cost()                         // render pdf view string contains 'Estimasi biaya'
```

- [ ] **Step 2–4:** FAIL → implementasi → `php artisan test` PASS.
- [ ] **Step 5: Commit** — `feat(itinerary): show ticket cost estimate on itinerary page and PDF`

---

### Task 7: Link berbagi itinerary (B4)

**Files:**
- Create: migrasi `2026_10_31_000003_add_share_token_to_itineraries_table.php`, `app/Http/Controllers/ItineraryShareController.php`, `app/Http/Controllers/SharedItineraryController.php`, `resources/views/itineraries/shared.blade.php`
- Modify: `routes/web.php`, `resources/views/itineraries/show.blade.php` (kartu "Bagikan itinerary", hanya `$isOwner`), `app/Models/Itinerary.php` (`$hidden` menyertakan `share_token` tidak perlu; cukup tidak di `$fillable`)
- Test: `tests/Feature/Itinerary/ShareLinkTest.php`

**Interfaces:**
- Consumes: `ItineraryInsights::build`, `ItineraryCostEstimator::estimate`, partial `cost-summary`.
- Routes: `POST /itinerary/{itinerary}/bagikan` → `itineraries.share.store`; `DELETE /itinerary/{itinerary}/bagikan` → `itineraries.share.destroy` (dalam grup `auth`, pemilik saja → 403); `GET /bagikan/{token}` → `itineraries.shared` (di luar `auth`, `throttle:60,1`).
- Status flash setelah buat/matikan: `'Link berbagi dibuat.'` / `'Link berbagi dimatikan.'`.

- [ ] **Step 1: Tulis test yang gagal:**

```php
test_owner_can_create_share_link()                    // POST → share_token length 40, redirect back with status
test_regenerating_invalidates_old_link()              // old token GET → 404; new → 200                     (Review Focus 4)
test_owner_can_disable_share_link()                   // DELETE → null; old token → 404
test_collaborator_and_stranger_cannot_manage_link()   // POST/DELETE → 403                                   (Review Focus 5)
test_collaborator_does_not_see_share_controls()       // as collaborator get(itineraries.show)->assertDontSee('Bagikan itinerary')
test_guest_can_view_shared_itinerary()                // GET /bagikan/{token} as guest → 200, sees title, destination names, 'Estimasi biaya'
test_shared_page_hides_private_data()                 // owner email/name, collaborator name, item notes → assertDontSee
test_shared_page_has_noindex_header()                 // assertHeader('X-Robots-Tag', 'noindex, nofollow')
test_unknown_token_is_404()
test_shared_page_with_no_items_loads()                // (Review Focus 3)
test_share_token_is_not_mass_assignable()             // Itinerary::create([... 'share_token' => 'x']) leaves it null
```

- [ ] **Step 2:** FAIL.
- [ ] **Step 3:** Implementasi. Halaman publik memakai `x-app-layout`, tanpa tombol edit/hapus/PDF/kolaborator; nama destinasi tertaut ke `destinations.show`. Kartu kelola di `show`: field `readonly` berisi `route('itineraries.shared', $token)`, tombol "Salin" (Alpine + `navigator.clipboard`, fallback teks "Salin manual"), "Buat link baru" (`onsubmit confirm('Link lama tidak akan bisa dibuka lagi. Lanjutkan?')`), "Matikan link"; bila belum ada token: tombol "Buat link berbagi".
- [ ] **Step 4:** `php artisan test` dan `npm run build` → PASS / sukses.
- [ ] **Step 5: Commit** — `feat(itinerary): add public read-only share link managed by the owner`
