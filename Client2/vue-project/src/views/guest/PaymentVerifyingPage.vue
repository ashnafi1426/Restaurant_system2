<template>
  <div class="min-h-screen bg-amber-50 flex items-center justify-center p-4">
    <div class="text-center">
      <!-- Large Play Button Style Loader -->
      <div class="relative w-40 h-40 mx-auto mb-8">
        <!-- Outer pulsing circle -->
        <div class="absolute inset-0 bg-gray-300 rounded-full opacity-20 animate-ping"></div>

        <!-- Main circle background -->
        <div
          class="absolute inset-0 bg-white rounded-full shadow-2xl flex items-center justify-center"
        >
          <!-- Play triangle -->
          <div
            class="w-0 h-0 border-l-[40px] border-l-gray-400 border-y-[25px] border-y-transparent ml-2"
          ></div>
        </div>
      </div>

      <!-- Loading text -->
      <h2 class="text-xl font-medium text-gray-700 mb-3">Verifying payment...</h2>

      <!-- Subtle loading indicator -->
      <div class="flex justify-center gap-1.5">
        <div
          class="w-2 h-2 bg-gray-400 rounded-full animate-pulse"
          style="animation-delay: 0ms"
        ></div>
        <div
          class="w-2 h-2 bg-gray-400 rounded-full animate-pulse"
          style="animation-delay: 200ms"
        ></div>
        <div
          class="w-2 h-2 bg-gray-400 rounded-full animate-pulse"
          style="animation-delay: 400ms"
        ></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

onMounted(() => {
  // Automatically redirect to payment summary after 2 seconds
  setTimeout(() => {
    const orderId = route.query.order_id as string
    if (orderId) {
      router.push({
        name: 'payment-summary',
        params: { orderId },
      })
    }
  }, 2000)
})
</script>
