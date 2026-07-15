<script setup lang="ts">
import { computed } from 'vue'

type OrderStatus =
  | 'pending'
  | 'confirmed'
  | 'preparing'
  | 'ready'
  | 'delivered'

interface Props {
  orderNumber: string
  status: OrderStatus
  estimatedMinutes?: number
  createdAt?: string
}

const props = withDefaults(defineProps<Props>(), {
  estimatedMinutes: 20,
  createdAt: '',
})

const steps = computed(() => [
  {
    key: 'pending',
    title: 'Order Received',
    description: 'Your order has been received.',
    icon: '📝',
  },
  {
    key: 'confirmed',
    title: 'Confirmed',
    description: 'Restaurant has accepted your order.',
    icon: '✅',
  },
  {
    key: 'preparing',
    title: 'Preparing',
    description: 'Our chefs are preparing your meal.',
    icon: '👨‍🍳',
  },
  {
    key: 'ready',
    title: 'Ready',
    description: 'Your meal is ready for delivery.',
    icon: '🍽️',
  },
  {
    key: 'delivered',
    title: 'Delivered',
    description: 'Enjoy your meal!',
    icon: '🚪',
  },
])

const currentStep = computed(() => {
  return steps.value.findIndex(step => step.key === props.status)
})

const progress = computed(() => {
  if (steps.value.length <= 1) return 0

  return (
    (currentStep.value / (steps.value.length - 1)) *
    100
  )
})

const statusColor = computed(() => {
  switch (props.status) {
    case 'pending':
      return 'bg-yellow-500'

    case 'confirmed':
      return 'bg-blue-500'

    case 'preparing':
      return 'bg-orange-500'

    case 'ready':
      return 'bg-green-500'

    case 'delivered':
      return 'bg-emerald-600'

    default:
      return 'bg-slate-500'
  }
})
</script>

<template>

<div
    class="rounded-3xl bg-white shadow-lg border border-slate-200 overflow-hidden"
>

    <!-- Header -->

    <div
        class="px-6 py-5 border-b border-slate-200 bg-slate-50"
    >

        <div
            class="flex items-center justify-between"
        >

            <div>

                <h2
                    class="text-xl font-bold text-slate-900"
                >
                    Order Status
                </h2>

                <p
                    class="mt-1 text-sm text-slate-500"
                >
                    Track your room service order
                </p>

            </div>

            <span
                class="px-4 py-2 rounded-full text-white text-sm font-semibold"
                :class="statusColor"
            >
                {{ status.toUpperCase() }}
            </span>

        </div>

    </div>

    <!-- Order Information -->

    <div
        class="px-6 py-5 border-b border-slate-200 bg-white"
    >

        <div
            class="grid grid-cols-2 gap-4"
        >

            <div>

                <p
                    class="text-xs uppercase text-slate-500"
                >
                    Order Number
                </p>

                <p
                    class="font-bold text-slate-900 mt-1"
                >
                    #{{ orderNumber }}
                </p>

            </div>

            <div
                class="text-right"
            >

                <p
                    class="text-xs uppercase text-slate-500"
                >
                    Estimated Time
                </p>

                <p
                    class="font-bold text-teal-600 mt-1"
                >
                    {{ estimatedMinutes }} Minutes
                </p>

            </div>

        </div>

    </div>

    <!-- Progress -->

    <div class="p-6">

        <div
            class="w-full h-2 rounded-full bg-slate-200 overflow-hidden"
        >

            <div
                class="h-full bg-teal-600 transition-all duration-700"
                :style="{ width: progress + '%' }"
            />

        </div>

        <!-- Timeline -->

        <div
            class="mt-8 space-y-8"
        >
            <div
                v-for="(step, index) in steps"
                :key="step.key"
                class="relative flex gap-5"
            >

                <!-- Timeline Line -->

                <div
                    v-if="index < steps.length - 1"
                    class="absolute left-6 top-14 w-1 h-16 rounded-full"
                    :class="index < currentStep ? 'bg-teal-600' : 'bg-slate-200'"
                />

                <!-- Status Icon -->

                <div
                    class="relative z-10 flex h-12 w-12 items-center justify-center rounded-full text-xl transition-all duration-300"
                    :class="[
                        index < currentStep
                            ? 'bg-teal-600 text-white'
                            : index === currentStep
                                ? 'bg-blue-600 text-white ring-4 ring-blue-100'
                                : 'bg-slate-200 text-slate-500'
                    ]"
                >

                    <span v-if="index < currentStep">
                        ✓
                    </span>

                    <span v-else>
                        {{ step.icon }}
                    </span>

                </div>

                <!-- Content -->

                <div class="flex-1 pb-8">

                    <div class="flex items-center justify-between">

                        <h3
                            class="text-lg font-semibold"
                            :class="[
                                index <= currentStep
                                    ? 'text-slate-900'
                                    : 'text-slate-400'
                            ]"
                        >
                            {{ step.title }}
                        </h3>

                        <span
                            v-if="index < currentStep"
                            class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700"
                        >
                            Completed
                        </span>

                        <span
                            v-else-if="index === currentStep"
                            class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 animate-pulse"
                        >
                            Current
                        </span>

                        <span
                            v-else
                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500"
                        >
                            Waiting
                        </span>

                    </div>

                    <p
                        class="mt-2 text-sm leading-6"
                        :class="[
                            index <= currentStep
                                ? 'text-slate-600'
                                : 'text-slate-400'
                        ]"
                    >
                        {{ step.description }}
                    </p>

                </div>

            </div>

        </div>

    </div>

    <!-- Estimated Delivery Card -->

    <div
        class="border-t border-slate-200 bg-teal-50 px-6 py-5"
    >

        <div class="flex items-center gap-4">

            <div
                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-600 text-2xl text-white"
            >
                🚚
            </div>

            <div class="flex-1">

                <h3
                    class="font-bold text-slate-900"
                >
                    Estimated Delivery
                </h3>

                <p
                    class="mt-1 text-sm text-slate-600"
                >
                    Your order should arrive within
                    <strong>{{ estimatedMinutes }} minutes</strong>.
                </p>

            </div>

        </div>

    </div>

    <!-- Hotel Assistance -->

    <div
        class="border-t border-slate-200 bg-amber-50 px-6 py-5"
    >

        <div class="flex items-start gap-4">

            <div class="text-2xl">
                ℹ️
            </div>

            <div>

                <h3
                    class="font-semibold text-slate-900"
                >
                    Need Assistance?
                </h3>

                <p
                    class="mt-2 text-sm leading-6 text-slate-600"
                >
                    If you need to modify or cancel your order,
                    please contact the hotel reception immediately.
                    Once the kitchen has started preparing the meal,
                    changes may no longer be possible.
                </p>

            </div>

        </div>

    </div>

</div>

</template>