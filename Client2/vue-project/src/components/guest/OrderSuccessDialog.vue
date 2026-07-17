<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  modelValue: boolean
  orderId: number | string
  roomNumber?: string
  estimatedMinutes?: number
  totalAmount?: number
}

const props = withDefaults(
  defineProps<Props>(),
  {
    roomNumber: '',
    estimatedMinutes: 20,
    totalAmount: 0,
  }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value:boolean): void
  (e:'track-order'): void
}>()

const close = () => {
  emit('update:modelValue', false)
}

const trackOrder = () => {
  emit('track-order')
}

const formattedOrder = computed(() => {
  return `#${props.orderId}`
})

const formattedPrice = computed(() => {
  return `$${(props.totalAmount || 0).toFixed(2)}`
})
</script>
<template>
  <Teleport to="body">
    <Transition
      enter-active-class="duration-300 ease-out"
      leave-active-class="duration-200 ease-in"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/90 p-3"
      >
        <div
          class="relative w-full max-w-sm max-h-[90vh] overflow-y-auto rounded-3xl bg-white shadow-[0_25px_70px_rgba(0,0,0,.45)]"
        >
          <!-- Header -->
          <div
            class="relative overflow-hidden bg-gradient-to-r from-amber-700 via-amber-600 to-yellow-500 px-6 py-5"
          >
            <div
              class="absolute -top-10 -right-10 h-24 w-24 rounded-full bg-white/10"
            ></div>

            <div class="flex flex-col items-center">
              <div
                class="flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-lg ring-4 ring-white/20"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-8 w-8 text-green-600"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="3"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M5 13l4 4L19 7"
                  />
                </svg>
              </div>

              <h2 class="mt-3 text-2xl font-bold text-white">
                Order Confirmed
              </h2>

              <p class="mt-1 text-sm text-amber-100">
                Thank you for your order
              </p>

              <div
                class="mt-3 max-w-full truncate rounded-full bg-white/20 px-4 py-1.5 text-xs font-medium text-white"
              >
                {{ formattedOrder }}
              </div>
            </div>
          </div>

          <!-- Body -->
          <div class="space-y-3 p-4">
            <!-- Info Cards -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
              <div
                class="rounded-xl border border-amber-100 bg-amber-50 py-3 text-center"
              >
                <p class="text-[10px] uppercase text-gray-500 font-semibold">
                  Room
                </p>

                <p class="mt-1 text-lg font-bold text-amber-700">
                  {{ roomNumber || "--" }}
                </p>
              </div>

              <div
                class="rounded-xl border border-blue-100 bg-blue-50 py-3 text-center"
              >
                <p class="text-[10px] uppercase text-gray-500 font-semibold">
                  ETA
                </p>

                <p class="mt-1 text-lg font-bold text-blue-700">
                  {{ estimatedMinutes }}m
                </p>
              </div>

              <div
                class="rounded-xl border border-green-100 bg-green-50 py-3 text-center"
              >
                <p class="text-[10px] uppercase text-gray-500 font-semibold">
                  Status
                </p>

                <p class="mt-1 text-lg">
                  👨‍🍳
                </p>
              </div>

              <div
                class="rounded-xl border border-purple-100 bg-purple-50 py-3 text-center"
              >
                <p class="text-[10px] uppercase text-gray-500 font-semibold">
                  Total
                </p>

                <p class="mt-1 text-lg font-bold text-purple-700">
                  {{ formattedPrice }}
                </p>
              </div>
            </div>

            <!-- Kitchen Status -->
            <div class="rounded-2xl border bg-gray-50 p-4">
              <div class="flex items-start gap-3">
                <div
                  class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 text-green-700 font-bold"
                >
                  ✓
                </div>

                <div class="flex-1">
                  <h3 class="font-semibold text-gray-900">
                    Kitchen Preparing
                  </h3>

                  <p class="mt-1 text-xs text-gray-500">
                    Your order has been received and is currently being prepared.
                  </p>
                </div>
              </div>

              <!-- Progress -->
              <div class="mt-4">
                <div class="flex items-center">
                  <div class="h-2.5 w-2.5 rounded-full bg-green-500"></div>

                  <div class="h-1 flex-1 bg-green-500"></div>

                  <div class="h-2.5 w-2.5 rounded-full bg-amber-500"></div>

                  <div class="h-1 flex-1 bg-gray-300"></div>

                  <div class="h-2.5 w-2.5 rounded-full bg-gray-300"></div>
                </div>

                <div
                  class="mt-2 flex justify-between text-[10px] text-gray-500"
                >
                  <span>Confirmed</span>
                  <span>Preparing</span>
                  <span>Delivered</span>
                </div>
              </div>
            </div>

            <!-- Message -->
            <div
              class="rounded-xl bg-gradient-to-r from-amber-50 to-yellow-50 p-3 text-center"
            >
              <p class="text-xs leading-5 text-gray-600">
                Your meal will be delivered directly to your room shortly.
              </p>
            </div>

            <!-- Total Amount Display -->
            <div
              class="rounded-xl border-2 border-purple-200 bg-gradient-to-r from-purple-50 to-pink-50 p-4 text-center"
            >
              <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-1">
                Order Total
              </p>
              <p class="text-3xl font-bold text-purple-700">
                {{ formattedPrice }}
              </p>
              <p class="text-xs text-gray-500 mt-2">
                Including tax and service charge
              </p>
            </div>
          </div>

          <!-- Footer -->
          <div class="border-t bg-gray-50 p-3">
            <div class="flex gap-3">
              <button
                @click="close"
                class="flex-1 rounded-lg border border-gray-300 bg-white py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100"
              >
                Continue
              </button>

              <button
                @click="trackOrder"
                class="flex-1 rounded-lg bg-gradient-to-r from-amber-600 to-yellow-500 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:from-amber-700 hover:to-yellow-600"
              >
                Track Order
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>