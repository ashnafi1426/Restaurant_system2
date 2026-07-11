<script setup lang="ts">
import { computed } from 'vue'

import type { OrderItem } from '@/types/order'

const props = defineProps<{
  items: OrderItem[]

  menuItems?: any[]

  editable?: boolean

  readonly?: boolean

  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'update-items', items: OrderItem[]): void
}>()

const localItems = computed(() => props.items)

function formatCurrency(value: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value)
}

/*
|--------------------------------------------------------------------------
| Quantity Update
|--------------------------------------------------------------------------
*/

function increase(index: number) {
  const updated = localItems.value.map((item, i) => {
    if (i === index) {
      return {
        ...item,

        quantity: item.quantity + 1,
      }
    }

    return item
  })

  emit('update-items', updated)
}

function decrease(index: number) {
  const updated = localItems.value.map((item, i) => {
    if (i === index) {
      return {
        ...item,

        quantity: item.quantity > 1 ? item.quantity - 1 : 1,
      }
    }

    return item
  })

  emit('update-items', updated)
}

function removeItem(index: number) {
  const updated = localItems.value.filter((_, i) => i !== index)

  emit('update-items', updated)
}

function lineTotal(item: OrderItem) {
  return item.quantity * (item.item_price_at_order ?? item.price ?? 0)
}

function itemName(item: any) {
  return item.menu_item?.name ?? item.name ?? 'Unknown Item'
}

function itemPrice(item: any) {
  return item.item_price_at_order ?? item.price ?? 0
}
</script>
<template>
  <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <!-- ===================================================== -->
    <!-- Header -->
    <!-- ===================================================== -->

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 px-4 sm:px-5 md:px-6 lg:px-8 py-3 sm:py-4 md:py-5 lg:py-6">
      <div>
        <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-semibold text-gray-900">Order Items</h3>

        <p class="mt-1 text-xs sm:text-sm md:text-base text-gray-500">Menu items included in this order.</p>
      </div>

      <div class="rounded-lg bg-indigo-50 px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 text-xs sm:text-sm md:text-base font-semibold text-indigo-700 w-fit">
        {{ items.length }} Item(s)
      </div>
    </div>

    <!-- ===================================================== -->
    <!-- Loading -->
    <!-- ===================================================== -->

    <div v-if="loading" class="flex items-center justify-center py-12 sm:py-14 md:py-16 lg:py-20">
      <div
        class="h-10 w-10 sm:h-12 sm:w-12 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent"
      />

      <span class="ml-3 sm:ml-4 text-xs sm:text-sm md:text-base text-gray-600">Loading order items...</span>
    </div>

    <!-- ===================================================== -->
    <!-- Empty -->
    <!-- ===================================================== -->

    <div v-else-if="items.length === 0" class="py-12 sm:py-14 md:py-16 lg:py-20 text-center px-4">
      <div class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl">🍽️</div>

      <h3 class="mt-3 sm:mt-4 md:mt-5 text-base sm:text-lg md:text-xl lg:text-2xl font-semibold text-gray-900">No Items</h3>

      <p class="mt-2 text-xs sm:text-sm md:text-base text-gray-500">This order doesn't contain any menu items.</p>
    </div>

    <!-- ===================================================== -->
    <!-- Table -->
    <!-- ===================================================== -->

    <div v-else class="overflow-x-auto -mx-4 sm:mx-0">
      <table class="min-w-full divide-y divide-gray-200">
        <!-- =============================================== -->
        <!-- Header -->
        <!-- =============================================== -->

        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 sm:px-4 md:px-6 lg:px-8 py-2 sm:py-3 text-left text-xs font-semibold uppercase text-gray-600">
              Menu Item
            </th>

            <th class="px-3 sm:px-4 md:px-6 lg:px-8 py-2 sm:py-3 text-center text-xs font-semibold uppercase text-gray-600">
              Qty
            </th>

            <th class="px-3 sm:px-4 md:px-6 lg:px-8 py-2 sm:py-3 text-right text-xs font-semibold uppercase text-gray-600">
              Unit Price
            </th>

            <th class="px-3 sm:px-4 md:px-6 lg:px-8 py-2 sm:py-3 text-right text-xs font-semibold uppercase text-gray-600">
              Line Total
            </th>

            <th
              v-if="editable && !readonly"
              class="px-3 sm:px-4 md:px-6 lg:px-8 py-2 sm:py-3 text-center text-xs font-semibold uppercase text-gray-600"
            >
              Action
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-for="(item, index) in items" :key="item.id ?? index" class="hover:bg-gray-50 transition">
            <!-- Menu Item -->

            <td class="px-3 sm:px-4 md:px-6 lg:px-8 py-3 sm:py-4">
              <div class="font-semibold text-xs sm:text-sm md:text-base text-gray-900 truncate">
                {{ item.menu_item?.name }}
              </div>

              <div class="mt-1 text-xs text-gray-500 line-clamp-2">
                {{ item.notes || 'No notes' }}
              </div>
            </td>

            <!-- Quantity -->

            <td class="px-3 sm:px-4 md:px-6 lg:px-8 py-3 sm:py-4 text-center">
              <div v-if="editable && !readonly" class="inline-flex items-center rounded-lg border border-gray-200">
                <button class="px-2 sm:px-3 py-1.5 sm:py-2 hover:bg-gray-100 text-xs sm:text-sm min-h-10 min-w-10 flex items-center justify-center" @click="decrease(index)">−</button>

                <div class="min-w-[40px] sm:min-w-[50px] border-x border-gray-200 px-3 sm:px-4 py-1.5 sm:py-2 text-center text-xs sm:text-sm font-medium">
                  {{ item.quantity }}
                </div>

                <button class="px-2 sm:px-3 py-1.5 sm:py-2 hover:bg-gray-100 text-xs sm:text-sm min-h-10 min-w-10 flex items-center justify-center" @click="increase(index)">+</button>
              </div>

              <span v-else class="font-semibold text-xs sm:text-sm md:text-base">
                {{ item.quantity }}
              </span>
            </td>

            <!-- Price -->

            <td class="px-3 sm:px-4 md:px-6 lg:px-8 py-3 sm:py-4 text-right font-medium text-xs sm:text-sm md:text-base">
              {{ formatCurrency(item.item_price_at_order) }}
            </td>

            <!-- Line Total -->

            <td class="px-3 sm:px-4 md:px-6 lg:px-8 py-3 sm:py-4 text-right font-bold text-xs sm:text-sm md:text-base text-indigo-700">
              {{ formatCurrency(lineTotal(item)) }}
            </td>
            <td v-if="editable && !readonly" class="px-3 sm:px-4 md:px-6 lg:px-8 py-3 sm:py-4 text-center">
              <button
                class="rounded-lg bg-red-600 px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-white transition hover:bg-red-700 min-h-10 min-w-10"
                @click="removeItem(index)"
              >
                Remove
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
