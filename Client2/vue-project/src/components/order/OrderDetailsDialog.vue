<script setup lang="ts">
import { computed } from 'vue'

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

interface OrderItem {
  id?: string

  name: string

  quantity: number

  price: number

  subtotal: number
}

interface Order {
  id: string

  order_number: string

  status: string

  payment_type: string

  notes?: string

  guest?: {
    first_name: string

    last_name: string

    phone?: string

    email?: string
  }

  reservation?: {
    booking_reference: string

    check_in_date: string

    check_out_date: string
  }

  room?: {
    room_number: string
  }

  items: OrderItem[]

  subtotal: number

  tax: number

  discount: number

  total: number
}

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
  modelValue: boolean

  order: Order | null
}>()

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void

  (event: 'close'): void
}>()

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const statusClass = computed(() => {
  switch (props.order?.status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-700'

    case 'preparing':
      return 'bg-blue-100 text-blue-700'

    case 'served':
      return 'bg-green-100 text-green-700'

    case 'cancelled':
      return 'bg-red-100 text-red-700'

    default:
      return 'bg-gray-100 text-gray-700'
  }
})

/*
|--------------------------------------------------------------------------
| Close
|--------------------------------------------------------------------------
*/

function closeDialog() {
  emit('update:modelValue', false)

  emit('close')
}
</script>

<template>
  <Transition name="fade">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-2 sm:p-3 md:p-4 lg:p-4"
    >
      <div
        class="flex max-h-[90vh] w-full max-w-2xl sm:max-w-3xl md:max-w-4xl lg:max-w-5xl flex-col overflow-hidden rounded-lg sm:rounded-xl md:rounded-2xl bg-white shadow-lg sm:shadow-xl"
      >
        <!-- Header -->

        <div class="flex items-center justify-between border-b border-gray-200 px-4 sm:px-5 md:px-6 lg:px-8 py-3 sm:py-4 md:py-5 lg:py-6">
          <div>
            <h2 class="text-base sm:text-lg md:text-xl lg:text-2xl font-bold text-gray-800">Order Details</h2>

            <p class="mt-0.5 sm:mt-1 text-xs sm:text-sm text-gray-500">Order #{{ order?.order_number }}</p>
          </div>

          <button @click="closeDialog" class="h-8 w-8 sm:h-9 sm:w-9 md:h-10 md:w-10 rounded-lg text-gray-400 hover:bg-gray-100 transition-colors inline-flex items-center justify-center flex-shrink-0 min-h-10">
            ✕
          </button>
        </div>

        <!-- Body -->

        <div class="overflow-y-auto px-4 sm:px-5 md:px-6 lg:px-8 py-4 sm:py-5 md:py-6 lg:py-8" v-if="order">
          <!-- Status -->

          <div class="mb-4 sm:mb-5 md:mb-6 lg:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 md:gap-6 rounded-lg bg-gray-50 p-3 sm:p-4 md:p-5 lg:p-6">
            <div>
              <p class="text-xs sm:text-sm md:text-base text-gray-500">Current Status</p>

              <span
                class="mt-0.5 sm:mt-1 inline-flex rounded-full px-2 sm:px-3 md:px-4 py-0.5 sm:py-1 md:py-1.5 text-xs sm:text-sm md:text-base font-medium"
                :class="statusClass"
              >
                {{ order.status }}
              </span>
            </div>

            <div>
              <p class="text-xs sm:text-sm md:text-base text-gray-500">Payment</p>

              <p class="mt-0.5 sm:mt-1 font-medium text-gray-800 text-xs sm:text-sm md:text-base">
                {{ order.payment_type }}
              </p>
            </div>
          </div>

          <!-- Guest Information -->

          <section class="mb-4 sm:mb-5 md:mb-6 lg:mb-8">
            <h3 class="mb-3 sm:mb-4 text-xs sm:text-sm md:text-base font-semibold uppercase text-gray-600">Guest Information</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 sm:gap-3 md:gap-4 lg:gap-6">
              <div>
                <p class="text-xs sm:text-sm text-gray-500">Name</p>

                <p class="mt-0.5 sm:mt-1 font-medium text-xs sm:text-sm md:text-base">
                  {{ order.guest?.first_name }}

                  {{ order.guest?.last_name }}
                </p>
              </div>

              <div>
                <p class="text-xs sm:text-sm text-gray-500">Phone</p>

                <p class="mt-0.5 sm:mt-1 font-medium text-xs sm:text-sm md:text-base">
                  {{ order.guest?.phone || '-' }}
                </p>
              </div>

              <div>
                <p class="text-xs sm:text-sm text-gray-500">Email</p>

                <p class="mt-0.5 sm:mt-1 font-medium text-xs sm:text-sm md:text-base truncate">
                  {{ order.guest?.email || '-' }}
                </p>
              </div>
            </div>
          </section>

          <!-- Reservation + Room -->

          <section class="mb-4 sm:mb-5 md:mb-6 lg:mb-8">
            <h3 class="mb-3 sm:mb-4 text-xs sm:text-sm md:text-base font-semibold uppercase text-gray-600">
              Reservation Information
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 sm:gap-3 md:gap-4 lg:gap-6">
              <div>
                <p class="text-xs sm:text-sm text-gray-500">Booking Reference</p>

                <p class="mt-0.5 sm:mt-1 font-medium text-xs sm:text-sm md:text-base">
                  {{ order.reservation?.booking_reference }}
                </p>
              </div>

              <div>
                <p class="text-xs sm:text-sm text-gray-500">Check In</p>

                <p class="mt-0.5 sm:mt-1 font-medium text-xs sm:text-sm md:text-base">
                  {{ order.reservation?.check_in_date }}
                </p>
              </div>

              <div>
                <p class="text-xs sm:text-sm text-gray-500">Room</p>

                <p class="mt-0.5 sm:mt-1 font-medium text-xs sm:text-sm md:text-base">Room {{ order.room?.room_number }}</p>
              </div>
            </div>
          </section>

          <!-- Items -->

          <section class="mb-4 sm:mb-5 md:mb-6 lg:mb-8">
            <h3 class="mb-3 sm:mb-4 text-xs sm:text-sm md:text-base font-semibold uppercase text-gray-600">Order Items</h3>

            <div class="overflow-x-auto -mx-4 sm:mx-0 rounded-lg border border-gray-200">
              <table class="w-full text-xs sm:text-sm md:text-base">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left font-semibold text-gray-600">Item</th>

                    <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-center font-semibold text-gray-600">Qty</th>

                    <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-right font-semibold text-gray-600">Price</th>

                    <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-right font-semibold text-gray-600">Subtotal</th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                  <tr v-for="item in order.items" :key="item.id" class="hover:bg-gray-50">
                    <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-3">
                      {{ item.name }}
                    </td>

                    <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-center">
                      {{ item.quantity }}
                    </td>

                    <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-right">
                      {{ item.price }}
                    </td>

                    <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-right font-medium">
                      {{ item.subtotal }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <!-- Summary -->

          <section class="mb-4 sm:mb-5 md:mb-6 lg:mb-8">
            <h3 class="mb-3 sm:mb-4 text-xs sm:text-sm md:text-base font-semibold uppercase text-gray-600">Summary</h3>

            <div class="space-y-2 sm:space-y-3 rounded-lg bg-gray-50 p-3 sm:p-4 md:p-5 lg:p-6">
              <div class="flex justify-between text-xs sm:text-sm md:text-base">
                <span>Subtotal</span>

                <span>
                  {{ order.subtotal }}
                </span>
              </div>

              <div class="flex justify-between text-xs sm:text-sm md:text-base">
                <span>Tax</span>

                <span>
                  {{ order.tax }}
                </span>
              </div>

              <div class="flex justify-between text-xs sm:text-sm md:text-base">
                <span>Discount</span>

                <span>
                  {{ order.discount }}
                </span>
              </div>

              <div class="flex justify-between border-t border-gray-200 pt-2 sm:pt-3 text-sm sm:text-base md:text-lg font-bold">
                <span>Total</span>

                <span>
                  {{ order.total }}
                </span>
              </div>
            </div>
          </section>

          <!-- Notes -->

          <section v-if="order.notes" class="mb-4 sm:mb-5 md:mb-6 lg:mb-8">
            <h3 class="mb-2 sm:mb-3 text-xs sm:text-sm md:text-base font-semibold uppercase text-gray-600">Notes</h3>

            <div class="rounded-lg bg-gray-50 p-3 sm:p-4 md:p-5 lg:p-6 text-xs sm:text-sm md:text-base text-gray-700">
              {{ order.notes }}
            </div>
          </section>
        </div>

        <!-- Footer -->

        <div class="flex justify-end gap-2 sm:gap-3 border-t border-gray-200 bg-gray-50 px-4 sm:px-5 md:px-6 lg:px-8 py-3 sm:py-4 md:py-5 lg:py-6">
          <button
            @click="closeDialog"
            class="rounded-lg bg-gray-800 px-3 sm:px-4 md:px-6 py-2 sm:py-2.5 md:py-3 text-xs sm:text-sm md:text-base font-medium text-white hover:bg-gray-900 transition-colors min-h-10 inline-flex items-center"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
