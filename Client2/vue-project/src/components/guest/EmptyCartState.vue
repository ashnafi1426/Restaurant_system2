<script setup lang="ts">
defineEmits<{
  (e: 'browse'): void
}>()
</script>

<template>
  <div class="text-center py-16 bg-gradient-to-br from-gray-50 to-teal-50 rounded-2xl border-2 border-gray-200">
    <!-- Icon -->
    <div class="text-7xl mb-4">🛒</div>

    <!-- Title -->
    <h3 class="text-2xl font-bold text-gray-900 mb-2">Your Cart is Empty</h3>

    <!-- Message -->
    <p class="text-gray-600 mb-6 text-lg">
      Start exploring our delicious menu and add your favorite items
    </p>

    <!-- Suggestions -->
    <div class="inline-flex gap-2 mb-8 flex-wrap justify-center">
      <span class="px-3 py-1 bg-teal-100 text-teal-700 rounded-full text-sm font-semibold">
        🍔 Try a Burger
      </span>
      <span class="px-3 py-1 bg-teal-100 text-teal-700 rounded-full text-sm font-semibold">
        🍕 Popular Lunch
      </span>
      <span class="px-3 py-1 bg-teal-100 text-teal-700 rounded-full text-sm font-semibold">
        ☕ Drinks
      </span>
    </div>

    <!-- Button -->
    <button
      @click="$emit('browse')"
      class="bg-gradient-to-r from-teal-600 to-teal-700 text-white px-8 py-3 rounded-full font-bold hover:shadow-lg transition-all"
    >
      👉 Browse Menu
    </button>
  </div>
</template>

<style scoped>
</style>
