<template>
  <div class="w-full">
    <div class="relative">
      <input
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        type="text"
        placeholder="Search rooms by number or type..."
        class="w-full px-4 sm:px-6 md:px-8 py-3 sm:py-4 md:py-5 rounded-lg md:rounded-2xl border-2 border-slate-200 focus:border-amber-500 focus:outline-none text-slate-900 placeholder-slate-500 bg-white shadow-lg transition-all text-sm sm:text-base"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: string
}>()

defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<style scoped>
/* Room search bar styles */
</style>
