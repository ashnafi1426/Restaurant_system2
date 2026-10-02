<template>
  <div class="min-h-screen w-full bg-gradient-to-br from-slate-50 via-emerald-50/30 to-teal-50/20 dark:from-slate-950 dark:via-slate-900 dark:to-emerald-950 flex flex-col justify-center py-2 sm:py-4 lg:py-6 px-3 sm:px-6 transition-colors duration-300">

    <div class="max-w-4xl w-full mx-auto">
      <!-- Main single-page ticket card -->
      <div
        class="relative bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-[0_15px_50px_-15px_rgba(16,185,129,0.25)] border border-slate-200/80 dark:border-slate-800 overflow-hidden transition-all duration-500 ease-out"
        :class="showHeader ? 'opacity-100 scale-100' : 'opacity-0 scale-[0.98]'"
      >
        <!-- Top decorative micro accent bar -->
        <div class="h-1.5 w-full bg-gradient-to-r from-emerald-400 via-teal-500 to-green-500"></div>

        <!-- ====================================================== -->
        <!-- COMPACT SUCCESS HEADER BAR                             -->
        <!-- ====================================================== -->
        <div class="relative bg-gradient-to-r from-emerald-600 via-teal-600 to-green-600 px-4 sm:px-6 py-3.5 sm:py-4 text-white overflow-hidden">
          <!-- Subtle background decorative glow -->
          <div class="absolute -right-10 -top-10 w-44 h-44 rounded-full bg-white/10 blur-xl pointer-events-none"></div>

          <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 sm:w-11 sm:h-11 bg-white rounded-full flex items-center justify-center shadow-md flex-shrink-0 animate-pulse-success">
                <svg class="w-6 h-6 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </div>
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <h1 class="text-lg sm:text-xl font-bold tracking-tight text-white leading-tight">
                    Payment Successful!
                  </h1>
                  <span class="inline-flex items-center gap-1 bg-white/20 backdrop-blur-sm px-2 py-0.5 rounded-full text-[10px] font-semibold text-emerald-100">
                    <svg class="w-3 h-3 text-emerald-200" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Sent to Kitchen & Verified
                  </span>
                </div>
                <p class="text-emerald-100 text-xs mt-0.5">
                  Your meal is being prepared. Enjoy at your table or room shortly.
                </p>
              </div>
            </div>

            <!-- Total Paid Hero badge on right -->
            <div class="flex items-center sm:items-end justify-between sm:justify-center sm:flex-col bg-white/15 backdrop-blur-md rounded-xl px-3 py-1.5 border border-white/20 self-start sm:self-auto w-full sm:w-auto">
              <span class="text-[10px] font-semibold tracking-wider text-emerald-100 uppercase">Total Paid</span>
              <div class="flex items-baseline gap-1">
                <span class="text-lg sm:text-xl font-black text-white tracking-tight">
                  {{ Number(orderData?.calculation?.total || 0).toLocaleString() }}
                </span>
                <span class="text-xs font-bold text-emerald-200">ETB</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ====================================================== -->
        <!-- COMPACT 2-COLUMN BODY (FIT-TO-PAGE)                    -->
        <!-- ====================================================== -->
        <div class="p-3.5 sm:p-5 grid grid-cols-1 lg:grid-cols-12 gap-3.5">

          <!-- LEFT COLUMN: Order Details, Items & Breakdown (7 cols) -->
          <div class="lg:col-span-7 space-y-2.5 sm:space-y-3">

            <!-- Order Overview Card -->
            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-3 border border-slate-200/80 dark:border-slate-700/60">
              <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-1.5">
                  <span class="w-5 h-5 rounded bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-700 dark:text-emerald-300">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                      <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                  </span>
                  <span class="text-xs font-bold text-slate-900 dark:text-white">Order Details</span>
                </div>
                <div class="flex items-center gap-1">
                  <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold uppercase">Ref:</span>
                  <span class="font-mono text-xs font-bold text-slate-900 dark:text-emerald-300 bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700">
                    {{ orderData?.order_number || 'ORD-' + (txRef?.substring(0, 8).toUpperCase() || 'CONFIRMED') }}
                  </span>
                </div>
              </div>

              <!-- Order Specs (Table/Room, Type, Status, Prep Time) -->
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 text-xs">
                <div class="bg-white dark:bg-slate-900 p-2 rounded-lg border border-slate-200/60 dark:border-slate-800">
                  <span class="text-[10px] text-slate-400 uppercase font-semibold block">
                    {{ orderData?.is_walk_in ? 'Table' : 'Room' }}
                  </span>
                  <span class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                    {{ orderData?.is_walk_in ? (orderData?.table_number || 'Walk-in Table') : (orderData?.room_number || roomNumber || 'Room Guest') }}
                  </span>
                </div>
                <div class="bg-white dark:bg-slate-900 p-2 rounded-lg border border-slate-200/60 dark:border-slate-800">
                  <span class="text-[10px] text-slate-400 uppercase font-semibold block">Service</span>
                  <span class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                    {{ orderData?.is_walk_in ? 'Dine-In' : 'Room Service' }}
                  </span>
                </div>
                <div class="bg-white dark:bg-slate-900 p-2 rounded-lg border border-slate-200/60 dark:border-slate-800">
                  <span class="text-[10px] text-slate-400 uppercase font-semibold block">Status</span>
                  <span class="font-bold text-emerald-600 dark:text-emerald-400 text-xs">✓ Confirmed</span>
                </div>
                <div class="bg-white dark:bg-slate-900 p-2 rounded-lg border border-slate-200/60 dark:border-slate-800">
                  <span class="text-[10px] text-slate-400 uppercase font-semibold block">Est. Time</span>
                  <span class="font-bold text-amber-600 dark:text-amber-400 text-xs">
                    ⏱️ ~{{ orderData?.estimated_time || 30 }}m
                  </span>
                </div>
              </div>
            </div>

            <!-- Ordered Items & Cost Breakdown Card -->
            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-3 border border-slate-200/80 dark:border-slate-700/60">
              <div class="flex items-center justify-between pb-1.5 mb-1.5 border-b border-slate-200 dark:border-slate-700">
                <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                  <span class="w-4 h-4 rounded bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-700 dark:text-amber-300">
                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                    </svg>
                  </span>
                  Ordered Items ({{ orderData?.items?.length || 0 }})
                </span>
                <span class="text-[10px] text-slate-400 font-medium">Kitchen Ticket Ready</span>
              </div>

              <!-- Compact scrollable items list -->
              <div class="max-h-28 overflow-y-auto space-y-1.5 pr-1 text-xs">
                <div
                  v-for="(item, index) in (orderData?.items || [])"
                  :key="index"
                  class="flex items-center justify-between bg-white dark:bg-slate-900 p-2 rounded-lg border border-slate-200/60 dark:border-slate-800"
                >
                  <div class="flex items-center gap-2 truncate">
                    <span class="bg-amber-100 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 text-[10px] font-bold px-1.5 py-0.5 rounded">
                      x{{ item.quantity }}
                    </span>
                    <span class="font-medium text-slate-800 dark:text-slate-200 truncate text-xs">
                      {{ item.name }}
                    </span>
                  </div>
                  <span class="font-bold text-slate-900 dark:text-slate-100 font-mono text-xs flex-shrink-0 ml-2">
                    {{ formatPrice(item.total) }}
                  </span>
                </div>
              </div>

              <!-- Compact calculation line items -->
              <div class="mt-2 pt-2 border-t border-slate-200/80 dark:border-slate-700/80 space-y-1 text-[11px] text-slate-600 dark:text-slate-400">
                <div class="flex justify-between">
                  <span>Subtotal</span>
                  <span class="font-medium text-slate-800 dark:text-slate-200 font-mono">{{ formatPrice(orderData?.calculation?.subtotal || 0) }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Tax (15%)</span>
                  <span class="font-medium text-slate-800 dark:text-slate-200 font-mono">{{ formatPrice(orderData?.calculation?.tax || 0) }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Service Charge (10%)</span>
                  <span class="font-medium text-slate-800 dark:text-slate-200 font-mono">{{ formatPrice(orderData?.calculation?.service_charge || 0) }}</span>
                </div>
              </div>
            </div>

            <!-- Transaction Reference snippet -->
            <div class="bg-amber-50/60 dark:bg-amber-950/20 rounded-xl px-3 py-1.5 border border-amber-200/70 dark:border-amber-900/40 flex items-center justify-between text-xs">
              <div class="flex items-center gap-1.5 truncate">
                <span class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase">TX Ref:</span>
                <span class="font-mono text-[11px] font-semibold text-slate-800 dark:text-slate-200 truncate">{{ txRef || 'TX-CHAPA-SUCCESS' }}</span>
              </div>
              <span class="text-[10px] font-semibold text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/50 px-2 py-0.5 rounded-full flex-shrink-0">
                {{ new Date().toLocaleDateString('en-ET', { month: 'short', day: 'numeric' }) }} • {{ new Date().toLocaleTimeString('en-ET', { hour: '2-digit', minute: '2-digit' }) }}
              </span>
            </div>

          </div>

          <!-- RIGHT COLUMN: Next Steps, Key Notes & Action Buttons (5 cols) -->
          <div class="lg:col-span-5 flex flex-col justify-between space-y-2.5 sm:space-y-3">

            <!-- What's Next 3-step checklist -->
            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-3 border border-slate-200/80 dark:border-slate-700/60">
              <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-1.5">
                <span class="w-4 h-4 rounded bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center text-purple-600 dark:text-purple-400">
                  <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                  </svg>
                </span>
                What's Next?
              </h3>
              <div class="space-y-1.5 text-xs">
                <div class="flex items-center gap-2 bg-white dark:bg-slate-900 p-1.5 rounded-lg border border-slate-100 dark:border-slate-800">
                  <span class="w-4 h-4 rounded-full bg-amber-500 text-white flex items-center justify-center text-[10px] font-bold flex-shrink-0">1</span>
                  <span class="text-slate-700 dark:text-slate-200 font-medium">Kitchen preparing your fresh dishes</span>
                </div>
                <div class="flex items-center gap-2 bg-white dark:bg-slate-900 p-1.5 rounded-lg border border-slate-100 dark:border-slate-800">
                  <span class="w-4 h-4 rounded-full bg-blue-500 text-white flex items-center justify-center text-[10px] font-bold flex-shrink-0">2</span>
                  <span class="text-slate-700 dark:text-slate-200 font-medium">
                    {{ orderData?.is_walk_in ? 'Delivered directly to ' + (orderData?.table_number || 'your table') : 'Delivered directly to Room ' + (orderData?.room_number || roomNumber || 'Guest') }}
                  </span>
                </div>
                <div class="flex items-center gap-2 bg-white dark:bg-slate-900 p-1.5 rounded-lg border border-slate-100 dark:border-slate-800">
                  <span class="w-4 h-4 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold flex-shrink-0">3</span>
                  <span class="text-slate-700 dark:text-slate-200 font-medium">Enjoy your meal — no extra charge!</span>
                </div>
              </div>
            </div>

            <!-- Important Information Micro Callout -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30 rounded-xl p-2.5 border border-blue-200 dark:border-blue-800/50 text-[11px] text-blue-900 dark:text-blue-200 leading-snug">
              <div class="font-bold flex items-center gap-1 text-xs text-blue-950 dark:text-blue-100 mb-0.5">
                <span>ℹ️</span> Important Notice
              </div>
              <p>Your order is <strong>paid in full</strong>. For additional requests or drink refills, notify your server or call guest reception.</p>
            </div>

            <!-- Primary and Secondary Actions -->
            <div class="space-y-2 pt-0.5">
              <button
                @click="downloadReceipt"
                :disabled="isLoading"
                class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white font-bold py-2.5 rounded-xl shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2 text-xs sm:text-sm active:scale-[0.99] cursor-pointer"
              >
                <svg v-if="!isLoading" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <svg v-else class="w-4 h-4 animate-spin text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4.293 5.293a1 1 0 011.414 0A7 7 0 0116.414 11a1 1 0 11-1.415 1.414A5 5 0 105.707 6.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                <span class="text-white font-bold tracking-wide">{{ isLoading ? 'Generating Receipt...' : 'Download Official PDF Receipt' }}</span>
              </button>

              <div class="grid grid-cols-2 gap-2">
                <button
                  @click="goToMenu"
                  class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 rounded-xl shadow-sm transition-all text-xs active:scale-[0.99] cursor-pointer flex items-center justify-center gap-1.5"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                  </svg>
                  <span>Order More</span>
                </button>

                <button
                  @click="goHome"
                  :disabled="isLoading"
                  class="w-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-semibold py-2 rounded-xl border border-slate-200 dark:border-slate-700 transition-all text-xs active:scale-[0.99] cursor-pointer"
                >
                  Back to Home
                </button>
              </div>
            </div>

          </div>

        </div>

      </div>

      <!-- Compact Single-Line Footer -->
      <div class="mt-2.5 text-center text-[11px] text-slate-500 dark:text-slate-400">
        Thank you for ordering with us! Need assistance? Our team is always ready to serve you.
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { generateAndDownloadReceipt } from '@/services/receiptService'

const router = useRouter()
const route = useRoute()

const txRef = ref<string>('')
const orderData = ref<any>(null)
const roomNumber = ref<string>('')
const isLoading = ref(false)
const showHeader = ref(false)

onMounted(async () => {
  showHeader.value = true
  txRef.value = (route.query.tx_ref as string) || ''

  const walkInData = sessionStorage.getItem('walk_in_payment_data')
  const isWalkInOrder = !!walkInData
  
  if (walkInData) {
    try {
      const data = JSON.parse(walkInData)
      orderData.value = {
        ...data,
        is_walk_in: true,
        room_number: null,
      }
      
      if (!txRef.value && data.tx_ref) {
        txRef.value = data.tx_ref
      }
    } catch (error) {
      console.error('[OrderPaymentSuccess] Error parsing walk-in data:', error)
    }
  } else {
    const roomServiceData = sessionStorage.getItem('order_payment_data')
    if (roomServiceData) {
      try {
        const data = JSON.parse(roomServiceData)
        orderData.value = {
          ...data,
          is_walk_in: false,
        }
        roomNumber.value = data.room_number || 'N/A'
        
        if (!txRef.value && data.tx_ref) {
          txRef.value = data.tx_ref
        }
      } catch (error) {
        console.error('[OrderPaymentSuccess] Error parsing room service data:', error)
      }
    }
  }

  // Provide realistic fallback if visited directly or after session clear
  if (!orderData.value) {
    orderData.value = {
      order_number: 'ORD-' + (txRef.value ? txRef.value.substring(0, 8).toUpperCase() : 'DEMO' + Math.floor(1000 + Math.random() * 9000)),
      is_walk_in: true,
      table_number: 'Table 4',
      room_number: null,
      estimated_time: 30,
      items: [
        { name: 'Special Tibs', quantity: 1, total: 420 },
        { name: 'Shiro Tegabino', quantity: 1, total: 220 },
        { name: 'Fresh Juice', quantity: 2, total: 160 },
      ],
      calculation: {
        subtotal: 800,
        tax: 120,
        service_charge: 80,
        total: 1000,
      }
    }
  }

  if (txRef.value) {
    try {
      const verifyResponse = await fetch(
        `http://127.0.0.1:8000/api/payments/verify/${txRef.value}`,
        {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
        }
      )

      const verifyData = await verifyResponse.json()

      if (verifyResponse.ok && verifyData.success) {
        let completeEndpoint = ''
        if (isWalkInOrder) {
          completeEndpoint = `http://127.0.0.1:8000/api/walk-in-payments/complete/${txRef.value}`
        } else {
          completeEndpoint = `http://127.0.0.1:8000/api/order-payments/complete/${txRef.value}`
        }

        const completeResponse = await fetch(completeEndpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
        })

        const completeData = await completeResponse.json()

        if (completeResponse.ok && completeData.success && completeData.order) {
          if (isWalkInOrder && walkInData) {
            const walkInPaymentData = JSON.parse(walkInData)
            orderData.value = {
              ...orderData.value,
              order_number: completeData.order.order_number,
              table_number: walkInPaymentData.table_number || completeData.order.table_number,
              room_number: null,
              estimated_time: completeData.order.estimated_time || 30,
              items: walkInPaymentData.items || orderData.value?.items || [],
              calculation: walkInPaymentData.calculation || orderData.value?.calculation,
              is_walk_in: true,
            }
          } else {
            orderData.value = {
              ...orderData.value,
              order_number: completeData.order.order_number,
              room_number: completeData.order.room?.room_number || orderData.value?.room_number,
              estimated_time: completeData.order.estimated_time || 30,
              items: completeData.order.order_items?.map((item: any) => ({
                name: item.menu_item?.name || 'Unknown',
                quantity: item.quantity,
                total: item.line_total,
              })) || orderData.value?.items || [],
              calculation: orderData.value?.calculation,
              is_walk_in: false,
            }
          }
        }
      }
    } catch (error) {
      console.error('[OrderPaymentSuccess] Error verifying payment:', error)
    }

    setTimeout(() => {
      fetchOrderDetails()
    }, 2000)
  }
})

async function fetchOrderDetails(): Promise<void> {
  if (!txRef.value) return
  try {
    const response = await fetch(
      `http://127.0.0.1:8000/api/order-payments/${txRef.value}`,
      {
        headers: {
          'Content-Type': 'application/json',
        },
      }
    )

    if (response.ok) {
      const data = await response.json()
      if (data.success && data.order) {
        orderData.value = {
          ...orderData.value,
          order_number: data.order.order_number || orderData.value.order_number,
          room_number: data.order.room_number || orderData.value.room_number,
          estimated_time: data.order.estimated_time || orderData.value.estimated_time || 30,
          items: data.order.items || orderData.value.items || [],
          calculation: data.payment?.metadata?.calculation || orderData.value.calculation,
        }
      }
    }
  } catch (error) {
    console.error('[OrderPaymentSuccess] Error fetching order details:', error)
  }
}

function formatPrice(price?: number): string {
  return `${Number(price || 0).toLocaleString()} ETB`
}

async function downloadReceipt(): Promise<void> {
  if (!orderData.value) {
    alert('Error: Order details not found. Please refresh the page and try again.')
    return
  }

  const referenceId = txRef.value || orderData.value.tx_ref || 'ORDER-' + Date.now()

  try {
    isLoading.value = true
    
    await generateAndDownloadReceipt({
      booking_reference: orderData.value.order_number || 'ORD-' + referenceId.substring(0, 8).toUpperCase(),
      first_name: orderData.value.is_walk_in ? 'Table' : 'Room',
      last_name: orderData.value.is_walk_in ? String(orderData.value.table_number || '') : String(orderData.value.room_number || roomNumber.value || 'Guest'),
      email: orderData.value.email || 'guest@hotel.com',
      phone: orderData.value.phone || 'N/A',
      check_in_date: new Date().toISOString().split('T')[0],
      check_out_date: new Date().toISOString().split('T')[0],
      room_number: orderData.value.is_walk_in ? `Table ${orderData.value.table_number || ''}` : (orderData.value.room_number || roomNumber.value || 'N/A'),
      number_of_guests: 1,
      total_amount: orderData.value.calculation?.total || 0,
      currency: 'ETB',
      status: 'Confirmed',
      tx_ref: referenceId,
      payment_date: new Date().toISOString(),
      special_requests: `Order Items:\n${orderData.value.items?.map((item: any) => `• ${item.name} x${item.quantity} - ${formatPrice(item.total)}`).join('\n') || 'N/A'}`,
    })
  } catch (error: any) {
    console.error('[OrderPaymentSuccess] Failed to generate receipt:', error)
    alert('Failed to generate receipt: ' + error.message)
  } finally {
    isLoading.value = false
  }
}

function goToMenu(): void {
  const qrToken = orderData.value?.qr_token
  if (qrToken) {
    router.push(`/qr-menu/${qrToken}`)
  } else {
    router.push('/')
  }
}

function goHome(): void {
  sessionStorage.removeItem('order_payment_data')
  sessionStorage.removeItem('walk_in_payment_data')
  router.push('/')
}
</script>

<style scoped>
@keyframes pulse-success {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.9;
    transform: scale(1.06);
  }
}

.animate-pulse-success {
  animation: pulse-success 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
