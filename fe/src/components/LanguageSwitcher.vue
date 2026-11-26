<script setup>
import { Globe } from 'lucide-vue-next'
import { locale, setLocale } from '@/i18n'

defineProps({
  compact: {
    type: Boolean,
    default: false
  }
})
</script>

<template>
  <div
    class="inline-flex items-center bg-[#f4f6f5] p-0.5 rounded-full border border-gray-200/80 shadow-2xs select-none"
    role="group"
    :aria-label="locale === 'en' ? 'Switch Language' : 'Ubah Bahasa'"
  >
    <span v-if="!compact" class="pl-2 pr-1 text-gray-400 flex items-center" :title="locale === 'en' ? 'Language' : 'Bahasa'">
      <Globe class="w-3.5 h-3.5 text-[#0067AC]" />
    </span>
    <button
      type="button"
      @click="setLocale('en')"
      class="px-2.5 py-1 rounded-full text-[11px] font-extrabold tracking-wide transition cursor-pointer"
      :class="locale === 'en' ? 'bg-[#0067AC] text-white shadow-2xs' : 'text-gray-500 hover:text-gray-800'"
      title="English"
    >
      EN
    </button>
    <button
      type="button"
      @click="setLocale('id')"
      class="px-2.5 py-1 rounded-full text-[11px] font-extrabold tracking-wide transition cursor-pointer"
      :class="locale === 'id' ? 'bg-[#0067AC] text-white shadow-2xs' : 'text-gray-500 hover:text-gray-800'"
      title="Bahasa Indonesia"
    >
      ID
    </button>
  </div>
</template>
