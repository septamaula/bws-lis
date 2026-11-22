<script setup>
import { ref, computed, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null
  },
  prefix: {
    type: String,
    default: ''
  },
  suffix: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: '0'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  required: {
    type: Boolean,
    default: false
  },
  min: {
    type: Number,
    default: undefined
  },
  max: {
    type: Number,
    default: undefined
  },
  allowDecimal: {
    type: Boolean,
    default: false
  },
  maxDecimals: {
    type: Number,
    default: 2
  },
  allowNegative: {
    type: Boolean,
    default: false
  },
  allowNull: {
    type: Boolean,
    default: false
  },
  align: {
    type: String,
    default: 'left' // 'left' | 'right' | 'center'
  },
  size: {
    type: String,
    default: 'md', // 'sm' | 'md' | 'lg'
    validator: (v) => ['sm', 'md', 'lg'].includes(v)
  },
  inputClass: {
    type: String,
    default: ''
  },
  id: {
    type: String,
    default: undefined
  },
  name: {
    type: String,
    default: undefined
  }
})

const emit = defineEmits(['update:modelValue', 'input', 'change', 'blur', 'focus'])

const inputRef = ref(null)
const displayValue = ref('')
const isUserTyping = ref(false)

// Format raw numeric/string value to Indonesian formatted string (e.g. 1.100.000.000)
function formatValue(val) {
  if (val === null || val === undefined || val === '') return ''

  // If val is a number
  if (typeof val === 'number') {
    if (isNaN(val)) return ''
    if (props.allowDecimal) {
      const isNeg = props.allowNegative && val < 0
      const parts = Math.abs(val).toFixed(props.maxDecimals).split('.')
      const intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.')
      const decPart = parts[1] || ''
      return (isNeg ? '-' : '') + intPart + (decPart ? ',' + decPart : '')
    } else {
      const rounded = Math.round(val)
      const isNeg = props.allowNegative && rounded < 0
      const absStr = String(Math.abs(rounded))
      return (isNeg ? '-' : '') + absStr.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
    }
  }

  const str = String(val).trim()
  if (!str) return ''

  if (props.allowDecimal) {
    const isNeg = props.allowNegative && str.startsWith('-')
    let clean = str
    if (clean.includes(',')) {
      clean = clean.replace(/\./g, '').replace(',', '.')
    }
    const parts = clean.replace(/[^0-9.]/g, '').split('.')
    const intPart = parts[0] ? parts[0].replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '0'
    let res = (isNeg ? '-' : '') + intPart
    if (parts.length > 1) {
      const decPart = parts.slice(1).join('').slice(0, props.maxDecimals)
      res += ',' + decPart
    }
    return res
  } else {
    // Integer mode: in Indonesian numbering, dots are thousand separators, not decimals
    let clean = str
    if (clean.endsWith('.00')) {
      clean = clean.replace(/\.00$/, '')
    }
    const isNeg = props.allowNegative && clean.startsWith('-')
    const digitsOnly = clean.replace(/\D/g, '')
    if (!digitsOnly) return ''
    const normalized = digitsOnly.replace(/^0+(?=\d)/, '')
    return (isNeg ? '-' : '') + normalized.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
  }
}

// Parse display string back to numeric Number (or null)
function parseValue(displayStr) {
  if (!displayStr || displayStr.trim() === '') {
    return props.allowNull ? null : 0
  }
  const isNeg = props.allowNegative && displayStr.startsWith('-')
  if (props.allowDecimal) {
    const clean = displayStr.replace(/[^0-9,]/g, '').replace(',', '.')
    if (!clean) return props.allowNull ? null : 0
    let num = parseFloat((isNeg ? '-' : '') + clean)
    if (isNaN(num)) return props.allowNull ? null : 0
    if (props.min !== undefined && num < props.min) num = props.min
    if (props.max !== undefined && num > props.max) num = props.max
    return num
  } else {
    const clean = displayStr.replace(/\D/g, '')
    if (!clean) return props.allowNull ? null : 0
    let num = parseInt((isNeg ? '-' : '') + clean, 10)
    if (isNaN(num)) return props.allowNull ? null : 0
    if (props.min !== undefined && num < props.min) num = props.min
    if (props.max !== undefined && num > props.max) num = props.max
    return num
  }
}

// Calculate new cursor position after dots/separators are inserted/deleted
function calculateNewCursor(oldVal, newVal, oldCursor) {
  const digitsBefore = oldVal.slice(0, oldCursor).replace(/\D/g, '').length
  if (digitsBefore === 0) return 0
  let count = 0
  for (let i = 0; i < newVal.length; i++) {
    if (/\d/.test(newVal[i])) count++
    if (count === digitsBefore) return i + 1
  }
  return newVal.length
}

// Watch external modelValue changes
watch(
  () => props.modelValue,
  (newVal) => {
    if (!isUserTyping.value) {
      displayValue.value = formatValue(newVal)
    }
  },
  { immediate: true }
)

// Handle typing in input
function onInput(event) {
  const el = event.target
  const rawTyped = el.value
  const oldCursor = el.selectionStart || 0

  isUserTyping.value = true

  const formatted = formatValue(rawTyped)
  const newCursor = calculateNewCursor(rawTyped, formatted, oldCursor)

  el.value = formatted
  displayValue.value = formatted

  const parsed = parseValue(formatted)
  emit('update:modelValue', parsed)
  emit('input', parsed, event)

  nextTick(() => {
    if (el) {
      el.setSelectionRange(newCursor, newCursor)
    }
    isUserTyping.value = false
  })
}

function onBlur(event) {
  isUserTyping.value = false
  displayValue.value = formatValue(props.modelValue)
  emit('blur', event)
}

function onFocus(event) {
  emit('focus', event)
}

function onChange(event) {
  const parsed = parseValue(displayValue.value)
  emit('change', parsed, event)
}

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'py-1.5 px-3 text-xs'
    case 'lg':
      return 'py-3 px-4 text-sm'
    case 'md':
    default:
      return 'py-2.5 px-3.5 text-xs'
  }
})

const alignClasses = computed(() => {
  switch (props.align) {
    case 'right': return 'text-right'
    case 'center': return 'text-center'
    case 'left':
    default: return 'text-left'
  }
})
</script>

<template>
  <div class="relative flex items-center w-full">
    <!-- Prefix Badge / Text -->
    <span
      v-if="prefix"
      class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-gray-400 select-none pointer-events-none"
      :class="size === 'sm' ? 'text-[11px]' : 'text-xs'"
    >
      {{ prefix }}
    </span>

    <!-- Formatted Native Input -->
    <input
      ref="inputRef"
      type="text"
      inputmode="numeric"
      :id="id"
      :name="name"
      :value="displayValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :required="required"
      @input="onInput"
      @blur="onBlur"
      @focus="onFocus"
      @change="onChange"
      class="w-full bg-white font-bold text-gray-900 rounded-xl border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs font-mono disabled:bg-gray-100 disabled:cursor-not-allowed"
      :class="[
        sizeClasses,
        alignClasses,
        prefix ? 'pl-9' : '',
        suffix ? 'pr-9' : '',
        inputClass
      ]"
    />

    <!-- Suffix Badge / Text -->
    <span
      v-if="suffix"
      class="absolute right-3 top-1/2 -translate-y-1/2 font-medium text-gray-400 select-none pointer-events-none"
      :class="size === 'sm' ? 'text-[11px]' : 'text-xs'"
    >
      {{ suffix }}
    </span>
  </div>
</template>
