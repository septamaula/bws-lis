<script setup>
import { ref, computed, watch } from 'vue'
import {
  Search,
  Calendar,
  X,
  FileSpreadsheet,
  FileText,
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
  ArrowUpDown,
  ArrowUp,
  ArrowDown,
  CheckSquare,
  Square,
  MinusSquare,
  Filter
} from 'lucide-vue-next'
import { exportToCsv, exportToExcel, getNestedValue } from '@/utils/exportUtils'

const props = defineProps({
  // The raw dataset
  data: {
    type: Array,
    required: true,
    default: () => []
  },
  // Columns definition: [{ key: 'id', label: 'ID', sortable: true, searchable: true, format: fn, class: '' }]
  columns: {
    type: Array,
    required: true,
    default: () => []
  },
  // Property key used for date range filtering (e.g. 'created_at', 'date', 'start_date')
  dateKey: {
    type: String,
    default: ''
  },
  // Base filename for exports
  exportFilename: {
    type: String,
    default: 'bws_data_export'
  },
  // Default rows per page
  defaultPerPage: {
    type: Number,
    default: 10
  },
  // Table title or badge
  title: {
    type: String,
    default: ''
  },
  subtitle: {
    type: String,
    default: ''
  },
  // Show/Hide features
  showSearch: {
    type: Boolean,
    default: true
  },
  showDateRange: {
    type: Boolean,
    default: true
  },
  showExport: {
    type: Boolean,
    default: true
  },
  showSelection: {
    type: Boolean,
    default: true
  },
  showNumbering: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['row-click', 'selection-change'])

// State: Search & Date Range
const searchQuery = ref('')
const startDate = ref('')
const endDate = ref('')

// State: Pagination
const currentPage = ref(1)
const perPage = ref(props.defaultPerPage)
const perPageOptions = [5, 10, 25, 50, 100]

// State: Sorting
const sortKey = ref('')
const sortDirection = ref('asc') // 'asc' or 'desc'

// State: Selection (Set of row IDs or object references)
const selectedRows = ref(new Set())

// Notification message after export
const exportNotice = ref('')

// Watch search or filters to reset pagination to page 1
watch([searchQuery, startDate, endDate, perPage], () => {
  currentPage.value = 1
})

// Auto-detect dateKey if not explicitly provided
const resolvedDateKey = computed(() => {
  if (props.dateKey) return props.dateKey
  if (!props.data || !props.data.length) return ''
  const first = props.data[0]
  if (first.created_at !== undefined) return 'created_at'
  if (first.date !== undefined) return 'date'
  if (first.start_date !== undefined) return 'start_date'
  return ''
})

// ==========================================
// 1. FILTERING PIPELINE (HIGH PERFORMANCE)
// ==========================================
const filteredData = computed(() => {
  let list = props.data || []

  // 1a. Date Range Filter
  if (resolvedDateKey.value && (startDate.value || endDate.value)) {
    const start = startDate.value ? new Date(startDate.value + 'T00:00:00') : null
    const end = endDate.value ? new Date(endDate.value + 'T23:59:59') : null

    list = list.filter(item => {
      const val = getNestedValue(item, resolvedDateKey.value)
      if (!val) return false
      const itemDate = new Date(val)
      if (isNaN(itemDate.getTime())) return true
      if (start && itemDate < start) return false
      if (end && itemDate > end) return false
      return true
    })
  }

  // 1b. Search Query Filter across searchable columns
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    list = list.filter(item => {
      return props.columns.some(col => {
        if (col.searchable === false) return false
        const val = getNestedValue(item, col.key)
        if (val === null || val === undefined) return false
        return String(val).toLowerCase().includes(q)
      })
    })
  }

  // 1c. Sorting Pipeline
  if (sortKey.value) {
    const key = sortKey.value
    const dir = sortDirection.value === 'asc' ? 1 : -1
    list = [...list].sort((a, b) => {
      const valA = getNestedValue(a, key)
      const valB = getNestedValue(b, key)

      if (valA === valB) return 0
      if (valA === null || valA === undefined) return 1
      if (valB === null || valB === undefined) return -1

      // Numbers or Numeric Strings
      if (typeof valA === 'number' && typeof valB === 'number') {
        return (valA - valB) * dir
      }
      return String(valA).localeCompare(String(valB), undefined, { numeric: true, sensitivity: 'base' }) * dir
    })
  }

  return list
})

// ==========================================
// 2. PAGINATION COMPUTATION
// ==========================================
const totalItems = computed(() => filteredData.value.length)
const totalPages = computed(() => Math.max(1, Math.ceil(totalItems.value / perPage.value)))

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredData.value.slice(start, start + perPage.value)
})

const paginationStart = computed(() => (totalItems.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1))
const paginationEnd = computed(() => Math.min(totalItems.value, currentPage.value * perPage.value))

// Smart Page Numbers with Ellipsis
const pageNumbers = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const delta = 2
  const range = []

  for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
    range.push(i)
  }

  if (current - delta > 2) range.unshift('...')
  if (current + delta < total - 1) range.push('...')

  range.unshift(1)
  if (total > 1) range.push(total)
  return range
})

// ==========================================
// 3. SORTING HANDLER
// ==========================================
function handleSort(col) {
  if (!col.sortable) return
  if (sortKey.value === col.key) {
    if (sortDirection.value === 'asc') {
      sortDirection.value = 'desc'
    } else {
      sortKey.value = ''
      sortDirection.value = 'asc'
    }
  } else {
    sortKey.value = col.key
    sortDirection.value = 'asc'
  }
}

// ==========================================
// 4. CHECKBOX SELECTION LOGIC
// ==========================================
function getRowId(row, index) {
  return row.id !== undefined ? row.id : index
}

const isAllSelected = computed(() => {
  if (paginatedData.value.length === 0) return false
  return paginatedData.value.every((row, i) => selectedRows.value.has(getRowId(row, i)))
})

const isIndeterminate = computed(() => {
  if (isAllSelected.value) return false
  return paginatedData.value.some((row, i) => selectedRows.value.has(getRowId(row, i)))
})

function toggleSelectAll() {
  if (isAllSelected.value) {
    // Unselect current page
    paginatedData.value.forEach((row, i) => selectedRows.value.delete(getRowId(row, i)))
  } else {
    // Select all current page
    paginatedData.value.forEach((row, i) => selectedRows.value.add(getRowId(row, i)))
  }
  emitSelection()
}

function toggleRow(row, index) {
  const id = getRowId(row, index)
  if (selectedRows.value.has(id)) {
    selectedRows.value.delete(id)
  } else {
    selectedRows.value.add(id)
  }
  emitSelection()
}

function isRowSelected(row, index) {
  return selectedRows.value.has(getRowId(row, index))
}

function selectAllFiltered() {
  filteredData.value.forEach((row, i) => selectedRows.value.add(getRowId(row, i)))
  emitSelection()
}

function clearSelection() {
  selectedRows.value.clear()
  emitSelection()
}

function emitSelection() {
  const selectedItems = (props.data || []).filter((row, i) => selectedRows.value.has(getRowId(row, i)))
  emit('selection-change', selectedItems)
}

// ==========================================
// 5. EXPORT HANDLERS (CSV & EXCEL)
// ==========================================
function getExportTargetData() {
  // If user selected rows, export ONLY selected rows!
  if (selectedRows.value.size > 0) {
    return (props.data || []).filter((row, i) => selectedRows.value.has(getRowId(row, i)))
  }
  // Otherwise export all currently filtered rows
  return filteredData.value
}

function handleExportCsv() {
  const target = getExportTargetData()
  if (!target.length) return
  const isSelected = selectedRows.value.size > 0
  exportToCsv(target, props.columns, `${props.exportFilename}_${Date.now()}`)
  showExportNotice(`Berhasil mengekspor ${target.length} baris ${isSelected ? '(data terpilih)' : '(seluruh filter)'} ke CSV`)
}

function handleExportExcel() {
  const target = getExportTargetData()
  if (!target.length) return
  const isSelected = selectedRows.value.size > 0
  exportToExcel(target, props.columns, `${props.exportFilename}_${Date.now()}`)
  showExportNotice(`Berhasil mengekspor ${target.length} baris ${isSelected ? '(data terpilih)' : '(seluruh filter)'} ke Excel`)
}

function showExportNotice(msg) {
  exportNotice.value = msg
  setTimeout(() => {
    exportNotice.value = ''
  }, 4000)
}

function clearDateRange() {
  startDate.value = ''
  endDate.value = ''
}
</script>

<template>
  <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col gap-4 w-full">
    
    <!-- Header / Title & Actions Slot -->
    <div v-if="title || subtitle || $slots.header || $slots.actions" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
      <div>
        <h3 v-if="title" class="font-bold text-base text-gray-900 flex items-center gap-2">
          <span>{{ title }}</span>
          <span v-if="totalItems !== undefined" class="text-xs bg-[#f4f6f5] text-gray-600 font-semibold px-2.5 py-0.5 rounded-full">
            {{ totalItems }} Data
          </span>
        </h3>
        <p v-if="subtitle" class="text-xs text-gray-400 mt-0.5">{{ subtitle }}</p>
        <slot name="header"></slot>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <slot name="actions"></slot>
      </div>
    </div>

    <!-- Toolbar: Search, Date Range, & Export Buttons -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-[#f8faf9] p-3 rounded-2xl border border-gray-100">
      
      <!-- Left: Search & Date Range -->
      <div class="flex flex-wrap items-center gap-2.5 flex-1">
        
        <!-- Search Input -->
        <div v-if="showSearch" class="relative min-w-[200px] flex-1 sm:max-w-xs">
          <Search class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari kata kunci..."
            class="w-full bg-white text-xs text-gray-800 rounded-full pl-9 pr-8 py-2 border border-gray-200 focus:border-[#157f4f] focus:outline-none transition shadow-2xs"
          />
          <button
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            title="Hapus pencarian"
          >
            <X class="w-3.5 h-3.5" />
          </button>
        </div>

        <!-- Date Range Filter -->
        <div v-if="showDateRange && resolvedDateKey" class="flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-full border border-gray-200 text-xs shadow-2xs">
          <Calendar class="w-3.5 h-3.5 text-[#157f4f] shrink-0" />
          <input
            v-model="startDate"
            type="date"
            class="text-[11px] text-gray-700 bg-transparent focus:outline-none cursor-pointer"
            title="Tanggal Mulai"
          />
          <span class="text-gray-400 text-[10px]">s/d</span>
          <input
            v-model="endDate"
            type="date"
            class="text-[11px] text-gray-700 bg-transparent focus:outline-none cursor-pointer"
            title="Tanggal Akhir"
          />
          <button
            v-if="startDate || endDate"
            @click="clearDateRange"
            class="ml-1 text-gray-400 hover:text-rose-600 transition"
            title="Hapus filter tanggal"
          >
            <X class="w-3.5 h-3.5" />
          </button>
        </div>

        <!-- Custom Filter Slot -->
        <slot name="filters"></slot>
      </div>

      <!-- Right: Export Buttons & Selection Pill -->
      <div class="flex items-center gap-2 self-end lg:self-auto shrink-0 flex-wrap">
        
        <!-- Selected Items Indicator -->
        <div v-if="showSelection && selectedRows.size > 0" class="flex items-center gap-1.5 bg-emerald-50 border border-emerald-200/80 text-[#157f4f] px-3 py-1.5 rounded-full text-xs font-semibold animate-fadeIn">
          <span>{{ selectedRows.size }} dipilih</span>
          <span class="text-emerald-300">|</span>
          <button @click="selectAllFiltered" class="text-[11px] hover:underline" title="Pilih seluruh data yang cocok">Semua</button>
          <button @click="clearSelection" class="hover:text-rose-600" title="Batalkan pilihan">&times;</button>
        </div>

        <!-- Export CSV Button -->
        <button
          v-if="showExport"
          @click="handleExportCsv"
          :disabled="totalItems === 0"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 text-xs font-semibold shadow-2xs hover:border-[#157f4f] hover:text-[#157f4f] transition disabled:opacity-40 disabled:cursor-not-allowed"
          :title="selectedRows.size > 0 ? `Export ${selectedRows.size} baris terpilih ke CSV` : `Export semua (${totalItems}) data ke CSV`"
        >
          <FileText class="w-3.5 h-3.5 text-blue-600" />
          <span>CSV</span>
          <span v-if="selectedRows.size > 0" class="w-1.5 h-1.5 rounded-full bg-[#157f4f]"></span>
        </button>

        <!-- Export Excel Button -->
        <button
          v-if="showExport"
          @click="handleExportExcel"
          :disabled="totalItems === 0"
          class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-[#157f4f] hover:bg-emerald-800 text-white text-xs font-semibold shadow-sm transition disabled:opacity-40 disabled:cursor-not-allowed"
          :title="selectedRows.size > 0 ? `Export ${selectedRows.size} baris terpilih ke Excel` : `Export semua (${totalItems}) data ke Excel`"
        >
          <FileSpreadsheet class="w-3.5 h-3.5 text-emerald-200" />
          <span>Excel</span>
          <span v-if="selectedRows.size > 0" class="text-[10px] bg-white/20 px-1.5 py-0.2 rounded-full">({{ selectedRows.size }})</span>
        </button>
      </div>
    </div>

    <!-- Export Toast Alert -->
    <div v-if="exportNotice" class="bg-emerald-50 border border-emerald-200 text-[#157f4f] px-4 py-2 rounded-xl text-xs flex items-center justify-between animate-fadeIn">
      <span>{{ exportNotice }}</span>
      <button @click="exportNotice = ''" class="text-emerald-700 hover:text-emerald-900">&times;</button>
    </div>

    <!-- Table Container Card with Border & Overflow -->
    <div class="border border-gray-200/90 rounded-2xl overflow-hidden bg-white shadow-2xs">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          
          <!-- Table Header -->
          <thead class="bg-[#f8faf9] border-b border-gray-200">
            <tr class="text-xs font-semibold text-gray-700">
              
              <!-- Checkbox Select All Column -->
              <th v-if="showSelection" class="py-3.5 px-3.5 w-12 text-center whitespace-nowrap">
                <button
                  type="button"
                  @click="toggleSelectAll"
                  class="text-gray-400 hover:text-[#157f4f] transition flex items-center justify-center mx-auto"
                  :title="isAllSelected ? 'Batalkan pilihan halaman ini' : 'Pilih semua di halaman ini'"
                >
                  <CheckSquare v-if="isAllSelected" class="w-4 h-4 text-[#157f4f]" />
                  <MinusSquare v-else-if="isIndeterminate" class="w-4 h-4 text-emerald-600" />
                  <Square v-else class="w-4 h-4 hover:text-gray-600" />
                </button>
              </th>

              <!-- Numbering Column (#) -->
              <th v-if="showNumbering" class="py-3.5 px-3 w-12 text-center text-gray-400 font-bold whitespace-nowrap font-mono text-[11px]">
                #
              </th>

              <!-- Dynamic Columns Header -->
              <th
                v-for="col in columns"
                :key="col.key"
                @click="handleSort(col)"
                class="py-3.5 px-4 font-semibold text-gray-700 select-none whitespace-nowrap"
                :class="[
                  col.sortable ? 'cursor-pointer hover:bg-gray-100/70 hover:text-[#157f4f] transition-colors' : '',
                  sortKey === col.key ? 'text-[#157f4f] font-bold bg-emerald-50/40' : '',
                  col.headerClass || '',
                  col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left'
                ]"
                :style="col.width ? { width: col.width } : {}"
              >
                <div
                  class="inline-flex items-center gap-1.5 whitespace-nowrap"
                  :class="col.align === 'right' ? 'justify-end w-full' : col.align === 'center' ? 'justify-center w-full' : ''"
                >
                  <span>{{ col.label || col.key }}</span>
                  <span v-if="col.sortable" class="inline-flex items-center text-gray-400 shrink-0">
                    <ArrowUp v-if="sortKey === col.key && sortDirection === 'asc'" class="w-3.5 h-3.5 text-[#157f4f]" />
                    <ArrowDown v-else-if="sortKey === col.key && sortDirection === 'desc'" class="w-3.5 h-3.5 text-[#157f4f]" />
                    <ArrowUpDown v-else class="w-3 h-3 hover:text-gray-600 opacity-60" />
                  </span>
                </div>
              </th>

              <!-- Action Column Header Slot -->
              <th v-if="$slots.action" class="py-3.5 px-4 text-right font-semibold text-gray-700 whitespace-nowrap w-28">
                Aksi
              </th>
            </tr>
          </thead>

          <!-- Table Body -->
          <tbody class="divide-y divide-gray-100 bg-white">
            
            <!-- Empty State -->
            <tr v-if="paginatedData.length === 0">
              <td
                :colspan="columns.length + (showSelection ? 1 : 0) + (showNumbering ? 1 : 0) + ($slots.action ? 1 : 0)"
                class="py-12 text-center text-gray-400"
              >
                <div class="flex flex-col items-center justify-center gap-2">
                  <div class="w-12 h-12 rounded-full bg-[#f4f6f5] flex items-center justify-center text-gray-400">
                    <Filter class="w-5 h-5" />
                  </div>
                  <p class="font-medium text-gray-600 text-sm">Tidak ada data yang cocok</p>
                  <p class="text-xs text-gray-400 max-w-sm">
                    Coba sesuaikan kata kunci pencarian atau rentang tanggal filter Anda.
                  </p>
                  <button
                    v-if="searchQuery || startDate || endDate"
                    @click="searchQuery = ''; clearDateRange()"
                    class="mt-1 text-xs text-[#157f4f] font-semibold hover:underline"
                  >
                    Reset Semua Filter
                  </button>
                </div>
              </td>
            </tr>

            <!-- Data Rows -->
            <tr
              v-for="(row, idx) in paginatedData"
              :key="getRowId(row, idx)"
              @click="emit('row-click', row)"
              class="hover:bg-[#fbfcfb] transition-colors group cursor-default"
              :class="isRowSelected(row, idx) ? 'bg-emerald-50/40' : ''"
            >
              <!-- Checkbox Row -->
              <td v-if="showSelection" class="py-3.5 px-3.5 text-center align-middle whitespace-nowrap" @click.stop>
                <button
                  type="button"
                  @click="toggleRow(row, idx)"
                  class="text-gray-400 hover:text-[#157f4f] transition flex items-center justify-center mx-auto"
                  :title="isRowSelected(row, idx) ? 'Hilangkan ceklis baris ini' : 'Ceklis baris ini untuk export'"
                >
                  <CheckSquare v-if="isRowSelected(row, idx)" class="w-4 h-4 text-[#157f4f]" />
                  <Square v-else class="w-4 h-4 group-hover:text-gray-500" />
                </button>
              </td>

              <!-- Row Numbering -->
              <td v-if="showNumbering" class="py-3.5 px-3 text-center text-gray-400 font-mono text-[11px] align-middle whitespace-nowrap">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>

              <!-- Row Cells -->
              <td
                v-for="col in columns"
                :key="col.key"
                class="py-3.5 px-4 align-middle whitespace-nowrap text-xs text-gray-800"
                :class="[
                  col.class || '',
                  col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left'
                ]"
              >
                <!-- Custom Cell Slot: #cell-[key-with-dashes]="{ row, value, index }" -->
                <slot
                  :name="`cell-${col.key.replace(/\./g, '-')}`"
                  :row="row"
                  :value="getNestedValue(row, col.key)"
                  :index="(currentPage - 1) * perPage + idx"
                >
                  <span v-if="col.format">
                    {{ col.format(getNestedValue(row, col.key), row) }}
                  </span>
                  <span v-else class="text-gray-800">
                    {{ getNestedValue(row, col.key) ?? '-' }}
                  </span>
                </slot>
              </td>

              <!-- Action Slot Column -->
              <td v-if="$slots.action" class="py-3.5 px-4 text-right align-middle whitespace-nowrap" @click.stop>
                <slot name="action" :row="row" :index="(currentPage - 1) * perPage + idx"></slot>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination & Summary Footer -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-gray-100 text-xs text-gray-500">
      
      <!-- Left: Status & Per-Page Selector -->
      <div class="flex items-center gap-3">
        <span>
          Menampilkan <strong class="text-gray-800 font-semibold">{{ paginationStart }}</strong> s/d
          <strong class="text-gray-800 font-semibold">{{ paginationEnd }}</strong> dari
          <strong class="text-gray-800 font-semibold">{{ totalItems }}</strong> data
        </span>

        <div class="flex items-center gap-1.5 pl-3 border-l border-gray-200">
          <label class="text-gray-400 text-[11px]">Baris:</label>
          <select
            v-model.number="perPage"
            class="bg-[#f4f6f5] text-xs font-semibold text-gray-700 px-2 py-1 rounded-lg border border-transparent focus:border-[#157f4f] focus:outline-none cursor-pointer"
          >
            <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
          </select>
        </div>
      </div>

      <!-- Right: Pagination Buttons -->
      <div v-if="totalPages > 1" class="flex items-center gap-1">
        
        <!-- First Page -->
        <button
          @click="currentPage = 1"
          :disabled="currentPage === 1"
          class="w-7 h-7 rounded-lg flex items-center justify-center border border-gray-200 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition"
          title="Halaman Pertama"
        >
          <ChevronsLeft class="w-3.5 h-3.5" />
        </button>

        <!-- Previous Page -->
        <button
          @click="currentPage--"
          :disabled="currentPage === 1"
          class="w-7 h-7 rounded-lg flex items-center justify-center border border-gray-200 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition"
          title="Halaman Sebelumnya"
        >
          <ChevronLeft class="w-3.5 h-3.5" />
        </button>

        <!-- Page Numbers -->
        <template v-for="(p, i) in pageNumbers" :key="i">
          <span v-if="p === '...'" class="px-1 text-gray-400 select-none">...</span>
          <button
            v-else
            @click="currentPage = p"
            class="min-w-[28px] h-7 px-2 rounded-lg font-semibold text-xs transition"
            :class="currentPage === p ? 'bg-[#157f4f] text-white shadow-xs' : 'border border-gray-200 text-gray-700 hover:bg-gray-100'"
          >
            {{ p }}
          </button>
        </template>

        <!-- Next Page -->
        <button
          @click="currentPage++"
          :disabled="currentPage === totalPages"
          class="w-7 h-7 rounded-lg flex items-center justify-center border border-gray-200 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition"
          title="Halaman Berikutnya"
        >
          <ChevronRight class="w-3.5 h-3.5" />
        </button>

        <!-- Last Page -->
        <button
          @click="currentPage = totalPages"
          :disabled="currentPage === totalPages"
          class="w-7 h-7 rounded-lg flex items-center justify-center border border-gray-200 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition"
          title="Halaman Terakhir"
        >
          <ChevronsRight class="w-3.5 h-3.5" />
        </button>
      </div>

    </div>

  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-2px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>

