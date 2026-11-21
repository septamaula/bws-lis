<script setup>
import { computed, useSlots } from 'vue'
import {
  Loader2,
  Plus,
  Edit,
  Edit3,
  Trash2,
  Check,
  Download,
  Upload,
  ArrowLeft,
  X,
  Search,
  RefreshCw,
  FileText,
  Eye,
  Send,
  Receipt,
  FolderPlus,
  UserPlus
} from 'lucide-vue-next'

const iconMap = {
  plus: Plus,
  edit: Edit3,
  trash: Trash2,
  delete: Trash2,
  check: Check,
  download: Download,
  upload: Upload,
  back: ArrowLeft,
  'arrow-left': ArrowLeft,
  close: X,
  search: Search,
  refresh: RefreshCw,
  file: FileText,
  eye: Eye,
  send: Send,
  receipt: Receipt,
  'folder-plus': FolderPlus,
  'user-plus': UserPlus
}

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'success', 'danger', 'outline', 'ghost', 'white', 'purple'].includes(v)
  },
  size: {
    type: String,
    default: 'sm',
    validator: (v) => ['xs', 'sm', 'md', 'lg'].includes(v)
  },
  pill: {
    type: Boolean,
    default: false
  },
  icon: {
    type: [Object, Function, String],
    default: null
  },
  iconPosition: {
    type: String,
    default: 'left',
    validator: (v) => ['left', 'right'].includes(v)
  },
  label: {
    type: String,
    default: ''
  },
  loading: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'button'
  }
})

// Clean label: automatically strip accidental leading '+' or trailing '[+]'
const cleanLabel = computed(() => {
  if (!props.label) return ''
  return props.label
    .trim()
    .replace(/^(\+|-)\s*/, '')
    .replace(/\s*\[\+\]$/, '')
})

const resolvedIcon = computed(() => {
  if (typeof props.icon === 'string') {
    return iconMap[props.icon.toLowerCase()] || null
  }
  return props.icon
})

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'primary':
      return 'bg-[#0067AC] text-white hover:bg-[#00528c] shadow-xs border border-transparent'
    case 'secondary':
      return 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 shadow-xs'
    case 'white':
      return 'bg-white text-gray-800 hover:bg-gray-50 border border-gray-100 shadow-xs'
    case 'success':
      return 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs border border-transparent'
    case 'danger':
      return 'bg-rose-600 text-white hover:bg-rose-700 shadow-xs border border-transparent'
    case 'outline':
      return 'bg-transparent text-[#0067AC] border border-[#0067AC] hover:bg-blue-50/80'
    case 'ghost':
      return 'bg-transparent text-gray-600 hover:bg-gray-100 border border-transparent'
    case 'purple':
      return 'bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 shadow-xs'
    default:
      return 'bg-[#0067AC] text-white hover:bg-[#00528c] shadow-xs border border-transparent'
  }
})

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'xs':
      return 'px-2.5 py-1 text-xs gap-1 font-semibold'
    case 'sm':
      return 'px-3.5 py-1.5 text-xs font-bold gap-1.5'
    case 'md':
      return 'px-4 py-2 text-xs md:text-sm font-bold gap-2'
    case 'lg':
      return 'px-5 py-2.5 text-sm font-extrabold gap-2'
    default:
      return 'px-3.5 py-1.5 text-xs font-bold gap-1.5'
  }
})

const roundedClasses = computed(() => {
  if (props.pill) return 'rounded-full'
  switch (props.size) {
    case 'xs':
      return 'rounded-lg'
    case 'sm':
      return 'rounded-xl'
    default:
      return 'rounded-xl'
  }
})

const iconSizeClasses = computed(() => {
  switch (props.size) {
    case 'xs':
      return 'w-3 h-3'
    case 'sm':
      return 'w-3.5 h-3.5'
    default:
      return 'w-4 h-4'
  }
})
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center transition cursor-pointer select-none shrink-0',
      'disabled:opacity-50 disabled:cursor-not-allowed',
      variantClasses,
      sizeClasses,
      roundedClasses
    ]"
  >
    <!-- Loading Spinner -->
    <Loader2 v-if="loading" :class="[iconSizeClasses, 'animate-spin shrink-0']" />

    <!-- Left Icon -->
    <template v-else-if="iconPosition === 'left'">
      <slot name="icon">
        <component :is="resolvedIcon" v-if="resolvedIcon" :class="[iconSizeClasses, 'shrink-0']" />
      </slot>
    </template>

    <!-- Content / Label -->
    <span v-if="cleanLabel">{{ cleanLabel }}</span>
    <slot v-else></slot>

    <!-- Right Icon -->
    <template v-if="!loading && iconPosition === 'right'">
      <slot name="icon">
        <component :is="resolvedIcon" v-if="resolvedIcon" :class="[iconSizeClasses, 'shrink-0']" />
      </slot>
    </template>
  </button>
</template>
