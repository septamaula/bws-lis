import { ref, computed } from 'vue'

const STORAGE_KEY = 'bws_lis_locale'

function getInitialLocale() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    if (saved === 'en' || saved === 'id') {
      return saved
    }
  } catch (_) {}
  return 'en'
}

export const locale = ref(getInitialLocale())

if (typeof document !== 'undefined') {
  document.documentElement.lang = locale.value
}

export function setLocale(lang) {
  if (lang !== 'en' && lang !== 'id') return
  locale.value = lang
  try {
    localStorage.setItem(STORAGE_KEY, lang)
  } catch (_) {}
  if (typeof document !== 'undefined') {
    document.documentElement.lang = lang
  }
}

export function toggleLocale() {
  setLocale(locale.value === 'en' ? 'id' : 'en')
}

function interpolate(str, params) {
  if (!params || typeof str !== 'string') return str
  return str.replace(/\{(\w+)\}/g, (_, k) => (params[k] !== undefined ? String(params[k]) : `{${k}}`))
}

/**
 * Inline bilingual copy helper:
 *   l('Save Changes', 'Simpan Perubahan')
 *   l('{count} records found', '{count} data ditemukan', { count: 5 })
 */
export function l(en, id, params = null) {
  const raw = locale.value === 'id' ? (id ?? en) : en
  return params ? interpolate(raw, params) : raw
}

export const messages = {
  en: {
    common: {
      save: 'Save',
      saving: 'Saving...',
      cancel: 'Cancel',
      close: 'Close',
      back: 'Back',
      delete: 'Delete',
      deleting: 'Deleting...',
      edit: 'Edit',
      view: 'View',
      detail: 'Details',
      create: 'Create',
      add: 'Add',
      submit: 'Submit',
      approve: 'Approve',
      reject: 'Reject',
      revise: 'Request Revision',
      refresh: 'Refresh Data',
      search: 'Search...',
      selectPlaceholder: 'Select...',
      searchPlaceholder: 'Type to search...',
      noResults: 'No matching results',
      clearSelection: 'Clear selection',
      emptyData: 'No records available',
      loading: 'Loading data...',
      actions: 'Actions',
      status: 'Status',
      active: 'Active',
      inactive: 'Inactive',
      yes: 'Yes',
      no: 'No',
      required: 'Required',
      optional: 'Optional',
      all: 'All',
      rows: 'Rows',
      showing: 'Showing',
      to: 'to',
      of: 'of',
      records: 'records',
      exportCsv: 'Export CSV',
      exportExcel: 'Export Excel',
      confirmDelete: 'Confirm Deletion'
    }
  },
  id: {
    common: {
      save: 'Simpan',
      saving: 'Menyimpan...',
      cancel: 'Batal',
      close: 'Tutup',
      back: 'Kembali',
      delete: 'Hapus',
      deleting: 'Menghapus...',
      edit: 'Ubah',
      view: 'Lihat',
      detail: 'Rincian',
      create: 'Buat',
      add: 'Tambah',
      submit: 'Kirim',
      approve: 'Setujui',
      reject: 'Tolak',
      revise: 'Minta Revisi',
      refresh: 'Segarkan Data',
      search: 'Cari...',
      selectPlaceholder: 'Pilih...',
      searchPlaceholder: 'Ketik untuk mencari...',
      noResults: 'Tidak ada hasil yang cocok',
      clearSelection: 'Hapus pilihan',
      emptyData: 'Tidak ada data untuk ditampilkan',
      loading: 'Memuat data...',
      actions: 'Aksi',
      status: 'Status',
      active: 'Aktif',
      inactive: 'Nonaktif',
      yes: 'Ya',
      no: 'Tidak',
      required: 'Wajib',
      optional: 'Opsional',
      all: 'Semua',
      rows: 'Baris',
      showing: 'Menampilkan',
      to: 's/d',
      of: 'dari',
      records: 'data',
      exportCsv: 'Ekspor CSV',
      exportExcel: 'Ekspor Excel',
      confirmDelete: 'Konfirmasi Hapus Data'
    }
  }
}

export function t(key, params = null) {
  const parts = String(key).split('.')
  let cur = messages[locale.value] || messages.en
  for (const part of parts) {
    if (cur && typeof cur === 'object' && part in cur) {
      cur = cur[part]
    } else {
      cur = null
      break
    }
  }
  if (typeof cur === 'string') {
    return params ? interpolate(cur, params) : cur
  }
  // Fallback to English if missing
  let fallback = messages.en
  for (const part of parts) {
    if (fallback && typeof fallback === 'object' && part in fallback) {
      fallback = fallback[part]
    } else {
      return key
    }
  }
  return typeof fallback === 'string' ? (params ? interpolate(fallback, params) : fallback) : key
}

export function formatLocaleDate(dateStr, options = { day: '2-digit', month: 'short', year: 'numeric' }) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  if (Number.isNaN(d.getTime())) return String(dateStr)
  return d.toLocaleDateString(locale.value === 'id' ? 'id-ID' : 'en-US', options)
}

export function useI18n() {
  return {
    locale: computed(() => locale.value),
    isEn: computed(() => locale.value === 'en'),
    isId: computed(() => locale.value === 'id'),
    setLocale,
    toggleLocale,
    l,
    t,
    formatLocaleDate
  }
}

export const i18nPlugin = {
  install(app) {
    app.config.globalProperties.$locale = locale
    app.config.globalProperties.$setLocale = setLocale
    app.config.globalProperties.$toggleLocale = toggleLocale
    app.config.globalProperties.$l = l
    app.config.globalProperties.$t = t
  }
}
