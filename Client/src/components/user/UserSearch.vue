<script setup lang="ts">
defineProps<{
  modelValue: string
}>()

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <input
    :value="modelValue"
    @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    type="text"
    placeholder="Search users..."
    class="w-full md:w-80 border border-slate-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
  />
</template>
