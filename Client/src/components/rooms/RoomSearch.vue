<script setup lang="ts">
defineProps<{
  modelValue: string
}>()

const emit = defineEmits(['update:modelValue'])
</script>
<template>
  <div class="relative">
    <input
      :value="modelValue"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      type="text"
      placeholder="Search room..."
      class="w-full md:w-96 border border-slate-300 rounded-lg pl-10 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
    />

    <svg
      class="absolute left-3 top-3 w-5 h-5 text-gray-400"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M21 21l-5.2-5.2M16 10a6 6 0 11-12 0 6 6 0 0112 0z"
      />
    </svg>
  </div>
</template>
