# QA Review — FIC11 Jilid 3 Distribution Readiness

Audit menyeluruh untuk memastikan source code yang dishare ke peserta workshop 7 Juni 2026 **works** dan bisa **dipakai untuk usaha mereka**.

## Ringkasan Eksekutif

**Verdict overall**: ⚠️ **GO WITH ACTIONS** — siap dishare ke peserta untuk **belajar &amp; demo**, tapi WAJIB:
1. Eksekusi **3 fix critical pre-distribution** (file `05-pre-distribution-checklist.md`)
2. Sampaikan **disclaimer untuk usaha real** ke peserta (file `06-disclaimer-untuk-peserta.md`)

| Area | Status | Score |
|---|---|---|
| Backend — Core CRUD | ✅ Works | 8/10 |
| Backend — Security | ⚠️ Beberapa gap | 6/10 |
| Backend — Deploy readiness | ⚠️ README incomplete | 6/10 |
| Frontend — Offline-first | ✅ Solid | 8/10 |
| Frontend — Auto sync | ⚠️ Idempotency missing | 6/10 |
| Frontend — Open/Close kasir | ✅ Lengkap | 8/10 |
| Frontend — CRUD master data | ❌ Category &amp; User tidak ada | 4/10 |
| Frontend — Printer | ✅ Bluetooth thermal OK | 8/10 |
| Frontend — Midtrans | 🔴 Server key di client | 4/10 |
| API Contract BE↔FE | ✅ Match | 9/10 |

**Overall readiness: 6.7 / 10** — cukup untuk workshop, **tidak cukup untuk produksi tanpa lanjutan**.

## Daftar File QA

| File | Isi |
|---|---|
| [01-manual-test-plan.md](01-manual-test-plan.md) | Checklist manual test 50+ skenario yang harus dijalanin sebelum share |
| [02-backend-findings.md](02-backend-findings.md) | Audit backend: security gaps, bugs, edge case, missing routes |
| [03-frontend-findings.md](03-frontend-findings.md) | Audit Flutter app: offline, sync, printer, midtrans, missing features |
| [04-api-contract.md](04-api-contract.md) | Status integrasi API BE↔FE (READY dengan 1-2 minor cosmetic) |
| [05-pre-distribution-checklist.md](05-pre-distribution-checklist.md) | **Action wajib SEBELUM share ke peserta** (~30 menit kerja) |
| [06-disclaimer-untuk-peserta.md](06-disclaimer-untuk-peserta.md) | Caveat yang harus dikomunikasikan saat share — penting untuk usaha real |

## Cara Pakai

1. Baca file [01-manual-test-plan.md](01-manual-test-plan.md) → jalankan minimal **Smoke Test (10 skenario)** sebelum share. Penuh 50+ kalau ada waktu.
2. Eksekusi [05-pre-distribution-checklist.md](05-pre-distribution-checklist.md) — fix wajib.
3. Saat share di akhir sesi, sampaikan disclaimer dari [06-disclaimer-untuk-peserta.md](06-disclaimer-untuk-peserta.md).

## Top 5 Concerns

1. 🔴 **Midtrans server key disimpan di Flutter app** — siapa pun yang reverse engineer APK bisa abuse. Wajib pindah ke backend proxy sebelum usaha live.
2. 🟠 **Tidak ada idempotency key di POST /api/orders** — kalau network blip, order bisa dobel di server.
3. 🟠 **CRUD Category &amp; User tidak ada di Flutter** — cafe pasti butuh tambah menu &amp; karyawan tiap hari.
4. 🟠 **Refund double-debit cash drawer** — variance tutup shift akan selalu salah kalau ada refund hari itu.
5. 🟡 **Revenue di dashboard/report tidak exclude refund** — laporan akuntansi misleading.

Detail full ada di file individual.
