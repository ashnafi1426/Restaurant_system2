<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  subtotal: number
  taxRate?: number
  serviceChargeRate?: number
  discount?: number
  currency?: string
}

const props = withDefaults(defineProps<Props>(), {
  taxRate: 0.15,
  serviceChargeRate: 0.10,
  discount: 0,
  currency: 'ETB',
})

const tax = computed(() => {
  return props.subtotal * props.taxRate
})

const serviceCharge = computed(() => {
  return props.subtotal * props.serviceChargeRate
})

const totalBeforeDiscount = computed(() => {
  return props.subtotal + tax.value + serviceCharge.value
})

const grandTotal = computed(() => {
  return totalBeforeDiscount.value - props.discount
})

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value)
}
</script>

<template>

<div
    class="rounded-3xl bg-white border border-slate-200 shadow-sm overflow-hidden"
>

    <!-- Header -->

    <div
        class="px-6 py-5 border-b border-slate-200 bg-slate-50"
    >

        <div class="flex items-center gap-3">

            <div
                class="w-12 h-12 rounded-2xl bg-teal-600 text-white flex items-center justify-center text-xl"
            >
                💳
            </div>

            <div>

                <h2
                    class="text-xl font-bold text-slate-900"
                >
                    Order Summary
                </h2>

                <p
                    class="text-sm text-slate-500"
                >
                    Review your order charges
                </p>

            </div>

        </div>

    </div>

    <!-- Body -->

    <div
        class="p-6 space-y-5"
    >

        <!-- Subtotal -->

        <div
            class="flex items-center justify-between"
        >

            <span
                class="text-slate-600"
            >
                Subtotal
            </span>

            <span
                class="font-semibold text-slate-900"
            >
                {{ formatCurrency(subtotal) }}

                {{ currency }}
            </span>

        </div>

        <!-- VAT -->

        <div
            class="flex items-center justify-between"
        >

            <span
                class="text-slate-600"
            >

                VAT ({{ (taxRate * 100).toFixed(0) }}%)

            </span>

            <span
                class="font-semibold text-slate-900"
            >

                {{ formatCurrency(tax) }}

                {{ currency }}

            </span>

        </div>

        <!-- Service Charge -->

        <div
            class="flex items-center justify-between"
        >

            <span
                class="text-slate-600"
            >

                Service Charge
                ({{ (serviceChargeRate * 100).toFixed(0) }}%)

            </span>

            <span
                class="font-semibold text-slate-900"
            >

                {{ formatCurrency(serviceCharge) }}

                {{ currency }}

            </span>

        </div>

        <div
            class="border-t border-dashed border-slate-300"
        />

        <!-- Continue in Part 10.2 -->
                <!-- Discount -->

        <div
            v-if="discount > 0"
            class="flex items-center justify-between"
        >

            <span
                class="text-slate-600"
            >
                Discount
            </span>

            <span
                class="font-semibold text-green-600"
            >

                - {{ formatCurrency(discount) }}

                {{ currency }}

            </span>

        </div>

        <!-- Total Before Discount -->

        <div
            v-if="discount > 0"
            class="flex items-center justify-between text-sm"
        >

            <span
                class="text-slate-500"
            >
                Total Before Discount
            </span>

            <span
                class="text-slate-700"
            >
                {{ formatCurrency(totalBeforeDiscount) }}

                {{ currency }}
            </span>

        </div>

        <div
            class="border-t border-slate-300 pt-5"
        >

            <div
                class="flex items-center justify-between"
            >

                <div>

                    <p
                        class="text-sm text-slate-500"
                    >
                        Grand Total
                    </p>

                    <p
                        class="text-xs text-slate-400 mt-1"
                    >
                        Including VAT & Service Charge
                    </p>

                </div>

                <div
                    class="text-right"
                >

                    <h2
                        class="text-3xl font-bold text-teal-600"
                    >

                        {{ formatCurrency(grandTotal) }}

                    </h2>

                    <p
                        class="text-sm text-slate-500"
                    >

                        {{ currency }}

                    </p>

                </div>

            </div>

        </div>

        <!-- Payment Information -->

        <div
            class="rounded-2xl bg-blue-50 border border-blue-200 p-5"
        >

            <div
                class="flex items-start gap-4"
            >

                <div
                    class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center text-xl"
                >
                    💳
                </div>

                <div
                    class="flex-1"
                >

                    <h3
                        class="font-bold text-slate-900"
                    >
                        Payment Information
                    </h3>

                    <p
                        class="mt-2 text-sm text-slate-600 leading-6"
                    >
                        Your food order can be charged directly to
                        your room account or paid at the reception,
                        depending on your hotel's payment policy.
                    </p>

                </div>

            </div>

        </div>

        <!-- Hotel Notice -->

        <div
            class="rounded-2xl bg-amber-50 border border-amber-200 p-5"
        >

            <div
                class="flex items-start gap-4"
            >

                <div
                    class="text-2xl"
                >
                    ℹ️
                </div>

                <div>

                    <h3
                        class="font-bold text-slate-900"
                    >
                        Hotel Room Service
                    </h3>

                    <ul
                        class="mt-3 space-y-2 text-sm text-slate-600"
                    >

                        <li>
                            • Orders are prepared immediately after confirmation.
                        </li>

                        <li>
                            • Estimated delivery time is approximately 20–30 minutes.
                        </li>

                        <li>
                            • Please keep your room phone available in case staff need to contact you.
                        </li>

                        <li>
                            • Contact Reception if you wish to cancel or modify your order.
                        </li>

                    </ul>

                </div>

            </div>

        </div>

    </div>

</div>

</template>