<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { ChevronDown, Search, X, Check } from 'lucide-vue-next'
import { useI18n } from '@/i18n'

const { t, l } = useI18n()

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean, null],
    default: ''
  },
  options: {
    type: Array,
    default: () => []
  },
  placeholder: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  searchPlaceholder: {
    type: String,
    default: ''
  },
  clearable: {
    type: Boolean,
    default: false
  },
  valueKey: {
    type: String,
    default: 'value'
  },
  labelKey: {
    type: String,
    default: 'label'
  },
  inputClass: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

const isOpen = ref(false)
const searchQuery = ref('')
const searchInputRef = ref(null)
const containerRef = ref(null)
const highlightedIndex = ref(-1)

// Normalize options to { value, label, subLabel } format
const normalizedOptions = computed(() => {
  return props.options.map(opt => {
    if (typeof opt === 'object' && opt !== null) {
      const val = opt[props.valueKey] !== undefined ? opt[props.valueKey] : (opt.value !== undefined ? opt.value : opt.id)
      const lbl = opt[props.labelKey] !== undefined ? opt[props.labelKey] : (opt.label !== undefined ? opt.label : (opt.name || opt.title || String(val ?? '')))
      const sub = opt.subLabel || opt.description || ''
      return {
        value: val,
        label: String(lbl ?? ''),
        subLabel: String(sub ?? ''),
        raw: opt
      }
    }
    return {
      value: opt,
      label: String(opt ?? ''),
      subLabel: '',
      raw: opt
    }
  })
})

// Filtered options based on searchQuery
const filteredOptions = computed(() => {
  if (!searchQuery.value.trim()) return normalizedOptions.value
  const q = searchQuery.value.toLowerCase().trim()
  return normalizedOptions.value.filter(opt => {
    return opt.label.toLowerCase().includes(q) || (opt.subLabel && opt.subLabel.toLowerCase().includes(q))
  })
})

// Current selected item
const selectedOption = computed(() => {
  return normalizedOptions.value.find(opt => {
    if (props.modelValue === '' || props.modelValue === null || props.modelValue === undefined) {
      return opt.value === '' || opt.value === null || opt.value === undefined
    }
    return String(opt.value) === String(props.modelValue)
  })
})

const displayLabel = computed(() => {
  if (selectedOption.value && selectedOption.value.label) {
    return selectedOption.value.label
  }
  return props.modelValue ? String(props.modelValue) : ''
})

function toggleDropdown() {
  if (props.disabled) return
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    searchQuery.value = ''
    highlightedIndex.value = -1
    nextTick(() => {
      searchInputRef.value?.focus()
    })
  }
}

function selectOption(opt) {
  const newVal = opt.value
  emit('update:modelValue', newVal)
  emit('change', newVal)
  isOpen.value = false
  searchQuery.value = ''
  highlightedIndex.value = -1
}

function clearSelection(e) {
  e?.stopPropagation()
  emit('update:modelValue', '')
  emit('change', '')
  searchQuery.value = ''
  highlightedIndex.value = -1
}

function handleClickOutside(event) {
  if (containerRef.value && !containerRef.value.contains(event.target)) {
    isOpen.value = false
  }
}

function handleKeyDown(e) {
  if (!isOpen.value) {
    if (e.key === 'ArrowDown' || e.key === 'Enter') {
      toggleDropdown()
      e.preventDefault()
    }
    return
  }

  if (e.key === 'Escape') {
    isOpen.value = false
    e.preventDefault()
  } else if (e.key === 'ArrowDown') {
    e.preventDefault()
    if (filteredOptions.value.length === 0) return
    highlightedIndex.value = (highlightedIndex.value + 1) % filteredOptions.value.length
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    if (filteredOptions.value.length === 0) return
    highlightedIndex.value = (highlightedIndex.value - 1 + filteredOptions.value.length) % filteredOptions.value.length
  } else if (e.key === 'Enter') {
    e.preventDefault()
    if (highlightedIndex.value >= 0 && highlightedIndex.value < filteredOptions.value.length) {
      selectOption(filteredOptions.value[highlightedIndex.value])
    }
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div
    ref="containerRef"
    class="relative w-full text-xs"
    @keydown="handleKeyDown"
  >
    <!-- Trigger Button -->
    <div
      @click="toggleDropdown"
      class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-700 flex items-center justify-between cursor-pointer transition select-none hover:border-gray-300 focus-within:border-[#0067AC]"
      :class="[
        inputClass,
        disabled ? 'opacity-60 cursor-not-allowed bg-gray-100' : '',
        isOpen ? 'border-[#0067AC] ring-2 ring-blue-100 bg-white' : ''
      ]"
      tabindex="0"
    >
      <span
        class="truncate pr-2"
        :class="displayLabel ? 'text-gray-800 font-semibold' : 'text-gray-400'"
      >
        {{ displayLabel || placeholder || t('common.selectPlaceholder') }}
      </span>

      <div class="flex items-center gap-1 shrink-0 text-gray-400">
        <button
          v-if="clearable && modelValue !== '' && modelValue !== null && modelValue !== undefined"
          type="button"
          @click="clearSelection"
          class="w-4 h-4 rounded-full hover:bg-gray-200 text-gray-400 hover:text-gray-600 flex items-center justify-center transition cursor-pointer"
          :title="t('common.clearSelection')"
        >
          <X class="w-3 h-3" />
        </button>
        <ChevronDown
          class="w-3.5 h-3.5 transition-transform duration-200"
          :class="{ 'rotate-180 text-[#0067AC]': isOpen }"
        />
      </div>
    </div>

    <!-- Dropdown Panel -->
    <div
      v-if="isOpen"
      class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-gray-200 rounded-2xl shadow-xl z-50 overflow-hidden flex flex-col py-1 max-h-72 min-w-[240px]"
    >
      <!-- Search Input Header -->
      <div class="p-2 border-b border-gray-100 bg-[#f8fafc] sticky top-0 z-10">
        <div class="relative flex items-center">
          <Search class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 pointer-events-none" />
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            class="w-full bg-white border border-gray-200 rounded-lg pl-8 pr-7 py-1.5 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#0067AC] focus:ring-1 focus:ring-[#0067AC]"
            :placeholder="searchPlaceholder || t('common.searchPlaceholder')"
            @click.stop
          />
          <button
            v-if="searchQuery"
            type="button"
            @click="searchQuery = ''"
            class="absolute right-2 text-gray-400 hover:text-gray-600 cursor-pointer"
          >
            <X class="w-3 h-3" />
          </button>
        </div>
      </div>

      <!-- Options List -->
      <div class="overflow-y-auto max-h-56 divide-y divide-gray-50 py-1">
        <div
          v-for="(opt, idx) in filteredOptions"
          :key="String(opt.value) + '-' + idx"
          @click="selectOption(opt)"
          class="px-3 py-2 text-xs cursor-pointer flex items-center justify-between gap-2 transition"
          :class="{
            'bg-[#EBF4FC] text-[#0067AC] font-bold': String(opt.value) === String(modelValue),
            'bg-gray-100': idx === highlightedIndex && String(opt.value) !== String(modelValue),
            'text-gray-700 hover:bg-[#F8FAFC] hover:text-[#0067AC]': String(opt.value) !== String(modelValue) && idx !== highlightedIndex
          }"
        >
          <div class="flex flex-col min-w-0 flex-1">
            <span class="truncate" :class="{ 'font-bold text-[#0067AC]': String(opt.value) === String(modelValue) }">{{ opt.label }}</span>
            <span v-if="opt.subLabel" class="text-[10px] text-gray-400 truncate font-normal mt-0.5">{{ opt.subLabel }}</span>
          </div>
          <Check
            v-if="String(opt.value) === String(modelValue)"
            class="w-3.5 h-3.5 text-[#0067AC] shrink-0"
          />
        </div>

        <div
          v-if="filteredOptions.length === 0"
          class="px-3 py-4 text-center text-gray-400 text-xs italic"
        >
          {{ t('common.noResults') }}
        </div>
      </div>
    </div>
  </div>
</template>
