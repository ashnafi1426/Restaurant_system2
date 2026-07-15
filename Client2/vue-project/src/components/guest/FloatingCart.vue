<script setup lang="ts">
import { computed } from 'vue'

interface CartItem {
  id?: string
  menu_item_id?: string
  quantity: number
  name: string
  price: number
}

interface Props {
  items?: CartItem[]
  count?: number
  total?: number
}

const props = withDefaults(defineProps<Props>(), {
  items: () => [],
  count: 0,
  total: 0,
})

const emit = defineEmits<{
  (e: 'open-cart'): void
  (e: 'open'): void
}>()

const totalItems = computed(() => {
  // Use count if provided, otherwise calculate from items
  if (props.count > 0) {
    return props.count
  }
  
  if (!props.items || props.items.length === 0) {
    return 0
  }
  
  return props.items.reduce(
    (sum, item) => sum + (item.quantity || 0),
    0
  )
})

const cartTotal = computed(() => {
  return props.total || 0
})

const formattedTotal = computed(() => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cartTotal.value)
})

const isVisible = computed(() => {
  return totalItems.value > 0
})

const openCart = () => {
  // Emit both events for compatibility
  emit('open-cart')
  emit('open')
}
</script>

<template>

<transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="translate-y-full opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-300 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-full opacity-0"
>

<div
    v-if="isVisible"
    class="fixed bottom-5 left-1/2 -translate-x-1/2 z-50 w-[95%] max-w-lg"
>

    <div
        class="rounded-2xl bg-teal-600 shadow-2xl overflow-hidden"
    >

        <!-- Button -->

        <button

            @click="openCart"

            class="w-full px-6 py-5 text-white"

        >

            <div
                class="flex items-center justify-between"
            >

                <!-- Left -->

                <div
                    class="flex items-center gap-4"
                >

                    <!-- Cart -->

                    <div
                        class="relative"
                    >

                        <div
                            class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center"
                        >

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-7 h-7"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8L5.4 5M7 13l-2 6h14M10 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2"
                                />

                            </svg>

                        </div>

                        <!-- Badge -->

                        <div
                            class="absolute -top-2 -right-2 w-7 h-7 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center"
                        >

                            {{ totalItems }}

                        </div>

                    </div>

                    <!-- Info -->

                    <div
                        class="text-left"
                    >

                        <h3
                            class="text-lg font-bold"
                        >

                            {{ totalItems }}

                            {{ totalItems === 1 ? 'Item' : 'Items' }}

                        </h3>

                        <p
                            class="text-sm text-teal-100"
                        >

                            Ready for checkout

                        </p>

                    </div>

                </div>

                <!-- Right -->

                <div
                    class="text-right"
                >

                    <p
                        class="text-sm text-teal-100"
                    >

                        Total

                    </p>

                    <h2
                        class="text-2xl font-bold"
                    >

                        {{ formattedTotal }}

                        ETB

                    </h2>

                </div>

            </div>

            <!-- Continue in Part 8.2 -->
                        <!-- Bottom Section -->

            <div
                class="mt-5 flex items-center justify-between border-t border-white/20 pt-4"
            >

                <!-- Room Service -->

                <div class="flex items-center gap-2 text-sm text-teal-100">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>

                    <span>

                        Room Service Available

                    </span>

                </div>

                <!-- View Cart -->

                <div
                    class="flex items-center gap-2 font-semibold"
                >

                    <span>

                        View Cart

                    </span>

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5 transition-transform group-hover:translate-x-1"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />

                    </svg>

                </div>

            </div>

        </button>

    </div>

</div>

</transition>

</template>