<template>
  <div class="space-y-3">
    <h4 class="font-semibold text-sm mb-2">
      {{ languageStore.t('customer_details', 'Customer Details') }}
    </h4>

    <div>
      <label class="text-xs text-slate-600 mb-1 block"
        >{{ languageStore.t('first_name', 'First Name') }} *</label
      >
      <input
        v-model="localValue.first_name"
        type="text"
        required
        placeholder="John"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent"
      />
    </div>

    <div>
      <label class="text-xs text-slate-600 mb-1 block"
        >{{ languageStore.t('last_name', 'Last Name') }} *</label
      >
      <input
        v-model="localValue.last_name"
        type="text"
        required
        placeholder="Doe"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent"
      />
    </div>

    <div>
      <label class="text-xs text-slate-600 mb-1 block"
        >{{ languageStore.t('email', 'Email') }} *</label
      >
      <input
        v-model="localValue.email"
        type="email"
        required
        placeholder="john@example.com"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent"
      />
    </div>

    <div>
      <label class="text-xs text-slate-600 mb-1 block"
        >{{ languageStore.t('phone_number', 'Phone Number') }} *</label
      >
      <input
        v-model="localValue.phone"
        type="tel"
        required
        placeholder="+251912345678"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent"
      />
    </div>

    <div class="border-t pt-3"></div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { PaymentForm, OrderContext } from '@/types/qrMenu'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

interface Props {
  modelValue: PaymentForm
  orderContext: OrderContext
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: PaymentForm]
}>()

const localValue = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
})
</script>
