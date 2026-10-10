<script setup lang="ts">
interface MenuCategory {
  category: string
  items: any[]
}

defineProps<{
  categories: MenuCategory[]
  selected: string
}>()

defineEmits<{
  select: [category: string]
}>()
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
      <button
        v-for="cat in categories"
        :key="cat.category"
        @click="$emit('select', cat.category)"
        :class="[
          'px-6 py-2 rounded-lg font-medium transition whitespace-nowrap',
          selected === cat.category
            ? 'bg-blue-600 text-white'
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
        ]"
      >
        {{ cat.category }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.scrollbar-hide {
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
</style>
