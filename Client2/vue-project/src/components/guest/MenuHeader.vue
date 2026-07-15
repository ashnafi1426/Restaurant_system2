<script setup lang="ts">
defineProps<{
  guestName: string
  roomNumber: string
  cartCount: number
}>()
</script>

<template>
  <div class="bg-white border-b border-gray-200 sticky top-0 z-10 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">🍽️ Room Service</h1>
          <p class="text-sm text-gray-600 mt-1">
            Room <span class="font-semibold">{{ roomNumber }}</span> • 
            Welcome, <span class="font-semibold">{{ guestName }}</span>!
          </p>
        </div>
        <div class="flex items-center gap-3 px-4 py-2 bg-blue-50 rounded-lg border border-blue-200">
          <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
          </svg>
          <span class="font-bold text-blue-900">{{ cartCount }} Items</span>
        </div>
      </div>
    </div>
  </div>
</template>
