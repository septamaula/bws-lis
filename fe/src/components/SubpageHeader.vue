<script setup>
import { ArrowLeft, ChevronRight } from 'lucide-vue-next'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
  // Back button text
  backLabel: {
    type: String,
    default: ''
  },
  // Main title of the subpage
  title: {
    type: String,
    default: ''
  },
  // Subtitle or description
  subtitle: {
    type: String,
    default: ''
  },
  // Category / Module badge above title
  badge: {
    type: String,
    default: ''
  },
  // Status badge to display next to title
  statusBadge: {
    type: String,
    default: ''
  },
  // Status badge class
  statusClass: {
    type: String,
    default: 'bg-blue-50 text-[#0067AC] border-blue-200'
  }
})

const emit = defineEmits(['back'])
</script>

<template>
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-3 border-b border-gray-100 w-full">
    <!-- Left: Back Button & Title / Hierarchy -->
    <div class="flex items-center gap-3 min-w-0 flex-1">
      <button
        type="button"
        @click="emit('back')"
        class="w-9 h-9 rounded-full bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 flex items-center justify-center transition shadow-2xs cursor-pointer shrink-0"
        :title="backLabel || t('common.back')"
      >
        <ArrowLeft class="w-4 h-4 text-gray-600" />
      </button>

      <div class="flex flex-col min-w-0">
        <!-- Breadcrumb / Badge -->
        <div v-if="badge || backLabel" class="flex items-center gap-1.5 text-xs text-gray-400 truncate">
          <span class="hover:text-[#0067AC] cursor-pointer" @click="emit('back')">{{ backLabel || t('common.back') }}</span>
          <template v-if="badge">
            <ChevronRight class="w-3 h-3 text-gray-300 shrink-0" />
            <span class="font-semibold text-gray-600">{{ badge }}</span>
          </template>
        </div>

        <!-- Title & Status Badge -->
        <div class="flex items-center gap-2.5 mt-0.5 flex-wrap">
          <h1 v-if="title" class="text-xl sm:text-2xl font-extrabold text-gray-900 tracking-tight leading-tight">
            {{ title }}
          </h1>
          <span
            v-if="statusBadge"
            class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border shrink-0 whitespace-nowrap inline-flex items-center"
            :class="statusClass"
          >
            {{ statusBadge }}
          </span>
        </div>

        <!-- Subtitle -->
        <p v-if="subtitle" class="text-xs text-gray-500 mt-0.5">
          {{ subtitle }}
        </p>
      </div>
    </div>

    <!-- Right: Contextual Actions Slot (Single Source of Action) -->
    <div class="flex items-center gap-2 sm:gap-2.5 shrink-0 self-start sm:self-center">
      <slot name="actions"></slot>
      <slot></slot>
    </div>
  </div>
</template>
