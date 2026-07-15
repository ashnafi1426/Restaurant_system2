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

<template>
  <section class="py-8 px-6">
    <div class="max-w-2xl mx-auto">
      <div class="flex items-center bg-white rounded-full shadow-lg px-6 py-4 border-2 border-transparent hover:border-teal-200 focus-within:border-teal-500 transition-all">
        <span class="text-2xl"></span>
        <input
          :value="modelValue"
          @input="handleInput"
          type="text"
          placeholder="Search for delicious meals, drinks, or desserts..."
          class="flex-1 ml-4 outline-none text-gray-700 placeholder-gray-400 bg-transparent text-lg"
        />
        <button 
          v-if="modelValue"
          @click="emit('update:modelValue', '')"
          class="text-gray-400 hover:text-gray-600 transition text-xl"
        >
          ✕
        </button>
      </div>
    </div>
  </section>
</template>
