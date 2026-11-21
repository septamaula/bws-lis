<script setup>
import { computed } from 'vue'

const props = defineProps({
  // Optional table title & subtitle
  title: {
    type: String,
    default: ''
  },
  subtitle: {
    type: String,
    default: ''
  },
  badge: {
    type: String,
    default: ''
  },
  // Columns definition: [{ key, label, align: 'left'|'center'|'right', width, class, headerClass, format }]
  columns: {
    type: Array,
    default: () => []
  },
  // Optional flat data list for automatic row rendering
  data: {
    type: Array,
    default: () => []
  },
  // Auto numbering
  showNumbering: {
    type: Boolean,
    default: false
  },
  // Striped rows or plain
  striped: {
    type: Boolean,
    default: false
  },
  // Compact padding
  compact: {
    type: Boolean,
    default: false
  },
  // Empty state message
  emptyText: {
    type: String,
    default: 'Tidak ada data untuk ditampilkan'
  }
})

defineEmits(['row-click'])
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-200/90 shadow-2xs overflow-hidden flex flex-col w-full">
    
    <!-- Optional Header / Title Bar -->
    <div
      v-if="title || subtitle || badge || $slots.header || $slots.actions"
      class="px-5 py-3.5 bg-white border-b border-gray-100 flex flex-wrap items-center justify-between gap-3"
    >
      <div class="flex items-center gap-2.5">
        <slot name="header">
          <div class="flex flex-col">
            <div class="flex items-center gap-2">
              <h3 class="font-bold text-sm text-gray-900">{{ title }}</h3>
              <span v-if="badge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-[#0067AC]">
                {{ badge }}
              </span>
            </div>
            <p v-if="subtitle" class="text-xs text-gray-400 mt-0.5">{{ subtitle }}</p>
          </div>
        </slot>
      </div>

      <div class="flex items-center gap-2">
        <slot name="actions"></slot>
      </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-x-auto w-full">
      <table class="w-full text-left border-collapse text-xs">
        
        <!-- Standard Header -->
        <thead class="bg-[#f8faf9] border-b border-gray-200">
          <slot name="thead">
            <tr class="text-xs font-semibold text-gray-700">
              <th v-if="showNumbering" class="py-3 px-3 w-12 text-center text-gray-400 font-bold whitespace-nowrap font-mono text-[11px]">
                #
              </th>
              <th
                v-for="col in columns"
                :key="col.key"
                class="py-3 px-4 font-semibold text-gray-700 whitespace-nowrap"
                :class="[
                  col.headerClass || '',
                  col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left'
                ]"
                :style="col.width ? { width: col.width } : {}"
              >
                {{ col.label || col.key }}
              </th>
            </tr>
          </slot>
        </thead>

        <!-- Body: Slot-based or Auto Data Rendering -->
        <tbody class="divide-y divide-gray-100 bg-white">
          <slot name="tbody">
            <!-- If flat data is passed -->
            <template v-if="data && data.length > 0">
              <tr
                v-for="(row, idx) in data"
                :key="row.id || idx"
                @click="$emit('row-click', row)"
                class="hover:bg-blue-50/20 transition-colors"
                :class="striped && idx % 2 === 1 ? 'bg-gray-50/40' : ''"
              >
                <td v-if="showNumbering" class="py-3 px-3 text-center text-gray-400 font-mono text-[11px] align-middle whitespace-nowrap">
                  {{ idx + 1 }}
                </td>
                <td
                  v-for="col in columns"
                  :key="col.key"
                  class="py-3 px-4 align-middle whitespace-nowrap text-xs text-gray-800"
                  :class="[
                    col.class || '',
                    col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left'
                  ]"
                >
                  <slot :name="`cell-${col.key.replace(/\./g, '-')}`" :row="row" :value="row[col.key]" :index="idx">
                    <span v-if="col.format">{{ col.format(row[col.key], row) }}</span>
                    <span v-else>{{ row[col.key] ?? '-' }}</span>
                  </slot>
                </td>
              </tr>
            </template>

            <!-- Empty State -->
            <tr v-else>
              <td :colspan="columns.length + (showNumbering ? 1 : 0)" class="py-10 text-center text-gray-400">
                <slot name="empty">
                  <p class="text-xs text-gray-500 font-medium">{{ emptyText }}</p>
                </slot>
              </td>
            </tr>
          </slot>
        </tbody>

        <!-- Standard Footer / Totals -->
        <tfoot v-if="$slots.tfoot" class="border-t-2 border-gray-200 bg-[#f8faf9] font-bold text-xs">
          <slot name="tfoot"></slot>
        </tfoot>

      </table>
    </div>

    <!-- Optional Bottom / Card Footer (e.g. progress bar, note, buttons) -->
    <div v-if="$slots.bottom" class="border-t border-gray-100 bg-gray-50/50">
      <slot name="bottom"></slot>
    </div>

  </div>
</template>
