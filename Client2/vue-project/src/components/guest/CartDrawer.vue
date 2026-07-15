<script setup lang="ts">
import { computed, ref } from 'vue'

interface CartItem {
  id: string
  menu_item_id?: string
  quantity: number
  name: string
  price: number
  image?: string | null
}

interface Props {
  modelValue: boolean
  items: CartItem[]
  submitting?: boolean
  error?: string
}

const props = withDefaults(defineProps<Props>(), {
  submitting: false,
  error: '',
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'remove', id: string): void
  (e: 'update', payload: { id: string; quantity: number }): void
  (e: 'checkout', orderData: { special_requests?: string }): void
}>()

const specialRequest = ref('')

const taxRate = 0.15
const serviceChargeRate = 0.10

const subtotal = computed(() => {
  return props.items.reduce((sum, item) => sum + item.price * item.quantity, 0)
})

const tax = computed(() => {
  return subtotal.value * taxRate
})

const serviceCharge = computed(() => {
  return subtotal.value * serviceChargeRate
})

const grandTotal = computed(() => {
  return subtotal.value + tax.value + serviceCharge.value
})

const totalItems = computed(() => {
  return props.items.reduce((sum, item) => sum + item.quantity, 0)
})

const closeDrawer = () => {
  emit('update:modelValue', false)
}

const increase = (item: CartItem) => {
  const itemId = item.id || item.menu_item_id || ''
  emit('update', {
    id: itemId,
    quantity: item.quantity + 1,
  })
}

const decrease = (item: CartItem) => {
  if (item.quantity <= 1) return
  const itemId = item.id || item.menu_item_id || ''
  emit('update', {
    id: itemId,
    quantity: item.quantity - 1,
  })
}

const removeItem = (item: CartItem) => {
  const itemId = item.id || item.menu_item_id || ''
  emit('remove', itemId)
}

const checkout = () => {
  if (props.submitting) return
  
  emit('checkout', {
    special_requests: specialRequest.value || undefined,
  })
}

</script>

<template>

<Teleport to="body">

<Transition
    enter-active-class="duration-300 ease-out"
    leave-active-class="duration-200 ease-in"
    enter-from-class="opacity-0"
    leave-to-class="opacity-0"
>

<div

    v-if="modelValue"

    class="fixed inset-0 z-[999]"

>

    <!-- Overlay -->

    <div

        class="absolute inset-0 bg-black/60 backdrop-blur-sm"

        @click="closeDrawer"

    />

    <!-- Drawer -->

    <Transition
        enter-active-class="duration-300 ease-out"
        leave-active-class="duration-300 ease-in"
        enter-from-class="translate-x-full"
        leave-to-class="translate-x-full"
    >

    <aside

        class="absolute right-0 top-0 h-full w-full sm:w-[470px] bg-white shadow-2xl flex flex-col"

    >

        <!-- Header -->

        <div
            class="border-b border-slate-200 px-6 py-5"
        >

            <div
                class="flex items-center justify-between"
            >

                <div>

                    <h2
                        class="text-2xl font-bold text-slate-900"
                    >
                        Your Order
                    </h2>

                    <p
                        class="mt-1 text-sm text-slate-500"
                    >
                        {{ totalItems }}

                        {{ totalItems === 1 ? 'item' : 'items' }}

                        selected
                    </p>

                </div>

                <button

                    @click="closeDrawer"
                    :disabled="props.submitting"

                    class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 transition flex items-center justify-center disabled:opacity-50"

                >

                    ✕

                </button>

            </div>

        </div>

        <!-- Error Alert -->
        <div v-if="props.error" class="bg-red-50 border-b border-red-200 px-6 py-3">
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0-12a9 9 0 110 18 9 9 0 010-18z" />
            </svg>
            <div>
              <p class="text-sm font-medium text-red-800">{{ props.error }}</p>
            </div>
          </div>
        </div>

        <!-- Scroll Area -->

        <div
            class="flex-1 overflow-y-auto"
        >

            <!-- Continue in Part 9.2 -->
                        <!-- Empty Cart -->

            <div
                v-if="items.length === 0"
                class="flex flex-col items-center justify-center h-full px-8 py-16 text-center"
            >

                <div
                    class="w-32 h-32 rounded-full bg-slate-100 flex items-center justify-center text-6xl"
                >
                    🛒
                </div>

                <h3
                    class="mt-6 text-2xl font-bold text-slate-800"
                >
                    Your cart is empty
                </h3>

                <p
                    class="mt-3 text-slate-500 leading-relaxed"
                >
                    Browse our delicious menu and add your favourite meals.
                </p>

            </div>

            <!-- Cart Items -->

            <div
                v-else
                class="p-6 space-y-5"
            >

                <div

                    v-for="item in items"

                    :key="item.menu_item_id"

                    class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden"

                >

                    <div
                        class="flex gap-4 p-4"
                    >

                        <!-- Image -->

                        <img

                            :src="item.image || 'https://images.unsplash.com/photo-1544025162-d76694265947?w=500'"

                            :alt="item.name"

                            class="w-24 h-24 rounded-xl object-cover"

                        />

                        <!-- Content -->

                        <div
                            class="flex-1"
                        >

                            <div
                                class="flex justify-between items-start"
                            >

                                <div>

                                    <h4
                                        class="text-lg font-bold text-slate-900"
                                    >

                                        {{ item.name }}

                                    </h4>

                                    <p
                                        class="text-sm text-slate-500 mt-1"
                                    >

                                        Freshly prepared by our chefs

                                    </p>

                                </div>

                                <!-- Remove -->

                                <button

                                    @click="removeItem(item)"

                                    class="text-red-500 hover:text-red-700 transition"

                                >

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
                                            d="M6 18L18 6M6 6l12 12"
                                        />

                                    </svg>

                                </button>

                            </div>

                            <!-- Price -->

                            <div
                                class="mt-3 flex items-center justify-between"
                            >

                                <div>

                                    <p
                                        class="text-xl font-bold text-teal-600"
                                    >

                                        {{ item.price.toFixed(2) }}

                                        ETB

                                    </p>

                                </div>

                                <!-- Quantity -->

                                <div
                                    class="flex items-center rounded-xl border border-slate-200 overflow-hidden"
                                >

                                    <button

                                        @click="decrease(item)"

                                        class="w-10 h-10 hover:bg-slate-100 transition"

                                    >

                                        −

                                    </button>

                                    <div
                                        class="w-12 text-center font-semibold"
                                    >

                                        {{ item.quantity }}

                                    </div>

                                    <button

                                        @click="increase(item)"

                                        class="w-10 h-10 hover:bg-slate-100 transition"

                                    >

                                        +

                                    </button>

                                </div>

                            </div>

                            <!-- Item Total -->

                            <div
                                class="mt-4 flex justify-between items-center border-t pt-3"
                            >

                                <span
                                    class="text-sm text-slate-500"
                                >

                                    Item Total

                                </span>

                                <span
                                    class="font-bold text-slate-900"
                                >

                                    {{ (item.price * item.quantity).toFixed(2) }}

                                    ETB

                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Continue in Part 9.3 -->
                        <!-- Bottom Summary -->

            <div
                v-if="items.length > 0"
                class="border-t border-slate-200 bg-slate-50 p-6 space-y-6"
            >

                <!-- Special Request -->

                <div>

                    <label
                        class="block text-sm font-semibold text-slate-700 mb-2"
                    >
                        Special Request
                    </label>

                    <textarea

                        v-model="specialRequest"

                        rows="3"

                        placeholder="Example: No onions, extra spicy, less salt..."

                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-200 resize-none"

                    />

                </div>

                <!-- Estimated Delivery -->

                <div
                    class="rounded-2xl bg-teal-50 border border-teal-200 p-4"
                >

                    <div class="flex items-center justify-between">

                        <div class="flex items-center gap-3">

                            <div
                                class="w-12 h-12 rounded-full bg-teal-600 text-white flex items-center justify-center text-xl"
                            >
                                ⏱
                            </div>

                            <div>

                                <h4
                                    class="font-semibold text-slate-800"
                                >
                                    Estimated Delivery
                                </h4>

                                <p
                                    class="text-sm text-slate-500"
                                >
                                    Your meal will be delivered to your room.
                                </p>

                            </div>

                        </div>

                        <div
                            class="text-right"
                        >

                            <span
                                class="text-2xl font-bold text-teal-600"
                            >
                                20–25
                            </span>

                            <p
                                class="text-sm text-slate-500"
                            >
                                Minutes
                            </p>

                        </div>

                    </div>

                </div>

                <!-- Order Summary -->

                <div
                    class="rounded-2xl bg-white border border-slate-200 p-5"
                >

                    <h3
                        class="text-lg font-bold text-slate-900 mb-5"
                    >
                        Order Summary
                    </h3>

                    <div class="space-y-3">

                        <div
                            class="flex justify-between text-slate-600"
                        >

                            <span>

                                Subtotal

                            </span>

                            <span>

                                {{ subtotal.toFixed(2) }} ETB

                            </span>

                        </div>

                        <div
                            class="flex justify-between text-slate-600"
                        >

                            <span>

                                VAT (15%)

                            </span>

                            <span>

                                {{ tax.toFixed(2) }} ETB

                            </span>

                        </div>

                        <div
                            class="flex justify-between text-slate-600"
                        >

                            <span>

                                Service Charge (10%)

                            </span>

                            <span>

                                {{ serviceCharge.toFixed(2) }} ETB

                            </span>

                        </div>

                        <div
                            class="border-t border-dashed border-slate-300 my-4"
                        />

                        <div
                            class="flex justify-between items-center"
                        >

                            <span
                                class="text-lg font-bold text-slate-900"
                            >

                                Grand Total

                            </span>

                            <span
                                class="text-3xl font-bold text-teal-600"
                            >

                                {{ grandTotal.toFixed(2) }}

                                ETB

                            </span>

                        </div>

                    </div>

                </div>

                <!-- Hotel Information -->

                <div
                    class="rounded-2xl bg-amber-50 border border-amber-200 p-4"
                >

                    <div class="flex items-start gap-3">

                        <div
                            class="text-2xl"
                        >
                            ℹ️
                        </div>

                        <div>

                            <h4
                                class="font-semibold text-slate-800"
                            >
                                Room Service Information
                            </h4>

                            <ul
                                class="mt-2 space-y-1 text-sm text-slate-600"
                            >
                                <li>• Your order will be delivered directly to your room.</li>
                                <li>• Payment can be charged to your room account.</li>
                                <li>• Please contact Reception if you need assistance.</li>
                            </ul>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Continue in Part 9.4 -->
                        <!-- Footer -->

            <div
                v-if="items.length > 0"
                class="border-t border-slate-200 bg-white p-6"
            >

                <!-- Checkout Button -->

                <button

                    @click="checkout"
                    :disabled="props.submitting"

                    class="w-full rounded-2xl bg-teal-600 hover:bg-teal-700 active:scale-[0.99] transition-all duration-300 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"

                >

                    <div
                        class="flex items-center justify-center gap-3 py-4"
                    >
                        <!-- Loading Spinner -->
                        <div v-if="props.submitting" class="animate-spin">
                          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11z" />
                          </svg>
                        </div>

                        <!-- Cart Icon -->
                        <svg v-else
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-6 h-6 text-white"
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

                        <div class="text-left">

                            <p
                                class="text-lg font-bold text-white"
                            >

                                {{ props.submitting ? 'Placing Order...' : 'Place Order' }}

                            </p>

                            <p
                                class="text-sm text-teal-100"
                            >

                                Total:

                                {{ grandTotal.toFixed(2) }}

                                ETB

                            </p>

                        </div>

                    </div>

                </button>

                <!-- Notice -->

                <p
                    class="mt-4 text-center text-xs text-slate-500 leading-relaxed"
                >

                    By placing this order, you agree to the hotel's
                    room service policy. Your order will be prepared
                    by our kitchen and delivered directly to your room.

                </p>

            </div>

        </div>

    </aside>

    </Transition>

</div>

</Transition>

</Teleport>
</template>