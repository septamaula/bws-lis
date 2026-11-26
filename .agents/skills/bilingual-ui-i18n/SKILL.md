---
name: bilingual-ui-i18n
description: Enforces bilingual (English default + Bahasa Indonesia switcher) UI development standards across BWS LIS frontend components with zero language mixing and natural corporate banking copy. Always apply whenever creating, updating, or reviewing any Vue/React UI template, form, table, modal, navigation, or copy.
---

# Bilingual UI Development Standard (English & Bahasa Indonesia)

Whenever creating or editing ANY user-facing UI component (pages, modules, modals, tables, navigation, placeholders, validation messages, tooltips, status badges, export headers), you MUST provide **full bilingual support** out of the box without waiting to be asked.

## Core Rules

1. **English Default + Top-Right Switcher**
   - The application defaults to **English (`en`)**.
   - A prominent language switcher pill (`EN | ID`) MUST remain accessible in the **top-right header bar** (and top-right of the Login page), persisted via `localStorage` (`bws_lis_locale`).

2. **Strict Purity — ZERO Language Mixing ("No Campur-Campur")**
   - **When English (`en`) is active**: 100% of visible UI text MUST be pure, natural English. Never leave Indonesian words behind (e.g., *Diklat, Usulan, Peserta, Sandi, Pagu, Unggah, Kembali, Simpan, Cabang*).
   - **When Bahasa Indonesia (`id`) is active**: 100% of visible UI text MUST be pure, natural Bahasa Indonesia. Avoid half-English/half-Indonesian hybrid phrases (e.g., never write *"Daftar Class"*, *"Upload Document"*, *"Submit Pengajuan"*, *"Monitoring Bulanan"*, *"Remaining Budget"*, *"Search..."*, *"Loading..."*). Use natural Indonesian equivalents (*"Daftar Kelas"*, *"Unggah Dokumen"*, *"Kirim Pengajuan"*, *"Pemantauan Bulanan"*, *"Sisa Anggaran"*, *"Cari..."*, *"Memuat..."*).
   - **Allowed Technical Proper Nouns / Banking Acronyms Only**: Established banking/system acronyms or official document names may stay as proper terms when appropriate (e.g., `BWS`, `LIS`, `TNA`, `RAB`, `OJK`, `BI`, `HRIS`, `RBAC`, `NIK`, `PIC`, `SPPD`, `PDF`, `Excel`, `CSV`, `Pre-Test`, `Post-Test`, `E-Learning`, `E-Certificate` / `E-Sertifikat`, `E-Commitment`, `Hybrid`, `Email`). Everything else must match the active language strictly.

3. **Natural, Idiomatic Copy (Not Robotic Translation)**
   - Write as an experienced corporate banking & L&D product designer would write in each language:
     - *Bad (robotic)*: `"Do saving of training need analysis data"` / `"Lakukan penyimpanan data analisis kebutuhan pelatihan"`
     - *Good (EN)*: `"Save Training Needs Analysis"` / *Good (ID)*: `"Simpan Analisis Kebutuhan Pelatihan"`
     - *Good (EN)*: `"No records found matching your filters."` / *Good (ID)*: `"Tidak ada data yang sesuai dengan filter."`
     - *Good (EN)*: `"Pending Checker Approval"` / *Good (ID)*: `"Menunggu Persetujuan Pemeriksa"`
     - *Good (EN)*: `"Budget Ceiling vs. Realization"` / *Good (ID)*: `"Pagu Anggaran vs. Realisasi"`

4. **Technical Implementation Pattern (`fe/src/i18n/index.js`)**
   - In Vue templates: use `$l('Pure English copy', 'Bahasa Indonesia murni')` (with optional interpolation params `$l('Total {count} items', 'Total {count} item', { count })`) or `$t('common.save')`.
   - Never hardcode static strings in `const columns = [...]` or `const options = [...]`; always wrap them inside `computed(() => [...])` using `l(...)` so switching language updates table headers, dropdown options, placeholders, and validation alerts **immediately without page reload**.
   - Make `AppSearchableSelect` placeholders, `SubpageHeader` back labels, `DataTable` empty/pagination strings, and browser date formatting reactive to the active locale (`en-US` vs `id-ID`).

5. **Post-Edit Purity Check**
   - Before finalizing any UI change, verify with search/grep that no untranslated hardcoded strings were introduced outside `$l(...)` / `$t(...)`, and check that `en` and `id` strings are clean and unmixed.
