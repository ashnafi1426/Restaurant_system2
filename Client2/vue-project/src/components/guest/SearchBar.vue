<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
  modelValue: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

<template>
  <section class="py-0.5 px-4 sm:px-6">
    <div class="max-w-3xl mx-auto">
      <!-- Search Bar - Ultra Compact Professional -->
      <div class="flex items-center gap-1.5 bg-gradient-to-r from-white to-slate-50 rounded-md border border-slate-200 px-2 py-0.5 hover:border-teal-400 hover:shadow-sm focus-within:border-teal-500 focus-within:shadow-md focus-within:ring-1 focus-within:ring-teal-500 transition-all duration-200">
        <!-- Search Icon -->
        <span class="material-symbols-rounded text-slate-400 text-sm flex-shrink-0">
          search
        </span>

        <!-- Input Field - Minimal -->
        <input
          :value="modelValue"
          @input="handleInput"
          type="text"
          placeholder="Search..."
          class="flex-1 outline-none text-xs text-slate-700 placeholder-slate-400 bg-transparent"
        />

        <!-- Clear Button - Minimal -->
        <transition name="fade">
          <button 
            v-if="modelValue"
            @click="emit('update:modelValue', '')"
            class="p-0.5 text-slate-400 hover:text-slate-600 rounded transition-all flex-shrink-0"
            title="Clear"
          >
            <span class="material-symbols-rounded text-xs">
              close
            </span>
          </button>
        </transition>
      </div>
    </div>
  </section>
</template>
