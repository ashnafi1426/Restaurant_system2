<template>
  <div class="eligible-items-list">
    <div class="header mb-6">
      <h2 class="text-2xl font-bold">{{ languageStore.t('items_you_can_review', 'Items You Can Review') }}</h2>
      <p class="text-gray-600 text-sm mt-1">{{ languageStore.t('review_past_orders_desc', 'You can review menu items from your past orders') }}</p>
    </div>

    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="i in 4" :key="i" class="animate-pulse h-40 bg-gray-200 rounded-lg"></div>
    </div>

    <div v-else-if="items.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
      <p class="text-gray-600 text-lg">{{ languageStore.t('no_items_for_review', 'No items available for review yet.') }}</p>
      <p class="text-gray-500 text-sm mt-2">{{ languageStore.t('no_items_for_review_sub', "Once you complete an order, you'll be able to review the items.") }}</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div 
        v-for="item in items" 
        :key="item.id"
        class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow"
      >
        <div class="relative h-48 bg-gray-200 overflow-hidden">
          <img
            v-if="item.image"
            :src="item.image"
            :alt="item.name"
            class="w-full h-full object-cover hover:scale-105 transition-transform"
          />
          <div v-else class="w-full h-full flex items-center justify-center">
            <span class="text-4xl"></span>
          </div>
          
          <div class="absolute top-2 right-2 bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold">
            {{ languageStore.t('order_num', 'Order #') }}{{ item.order_number }}
          </div>
        </div>

        <div class="p-4">
          <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ item.name }}</h3>
          <p class="text-sm text-gray-600 mb-3">{{ truncateText(item.description, 80) }}</p>
          
          <div class="flex items-center justify-between mb-4">
            <span class="text-lg font-bold text-green-600">{{ formatPrice(item.price) }}</span>
            <span class="text-sm text-gray-500">{{ languageStore.t('order_date', 'Order Date') }}</span>
          </div>

          <button
            @click="$emit('select', item)"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors cursor-pointer"
          >
            {{ languageStore.t('review_this_item', 'Review This Item') }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 mt-6">
      <button
        @click="currentPage = Math.max(1, currentPage - 1)"
        :disabled="currentPage === 1"
        class="px-3 py-2 border border-gray-300 rounded disabled:opacity-50 cursor-pointer"
      >
        {{ languageStore.t('previous', 'Previous') }}
      </button>
      
      <span class="text-sm text-gray-600">
        {{ languageStore.t('page', 'Page') }} {{ currentPage }} {{ languageStore.t('of', 'of') }} {{ totalPages }}
      </span>

      <button
        @click="currentPage = Math.min(totalPages, currentPage + 1)"
        :disabled="currentPage === totalPages"
        class="px-3 py-2 border border-gray-300 rounded disabled:opacity-50 cursor-pointer"
      >
        {{ languageStore.t('next', 'Next') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import reviewService from '@/services/reviewService'
import { EligibleMenuItem } from '@/types/review'
import { useLanguageStore } from '@/stores/language'

interface Props {
  guestId: string
}

interface Emits {
  (e: 'select', item: EligibleMenuItem): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()
const languageStore = useLanguageStore()

const items = ref<EligibleMenuItem[]>([])
const loading = ref(true)
const currentPage = ref(1)
const totalPages = ref(1)

const formatPrice = (price: number) => {
  return `$${price.toFixed(2)}`
}

const truncateText = (text: string, length: number) => {
  if (text.length <= length) return text
  return text.substring(0, length) + '...'
}

const loadItems = async () => {
  loading.value = true
  try {
    const data = await reviewService.getEligibleItems(props.guestId)
    items.value = data || []
    totalPages.value = Math.ceil(items.value.length / 9)
  } catch (error) {
    console.error('[EligibleItemsList] Failed to load eligible items:', error)
    items.value = []
  } finally {
    loading.value = false
  }
}

watch(() => props.guestId, () => {
  if (props.guestId) {
    loadItems()
  }
}, { immediate: true })

onMounted(() => {
  if (props.guestId) {
    loadItems()
  }
})
</script>

<style scoped>
.eligible-items-list {
  background: white;
  border-radius: 8px;
  padding: 20px;
}
</style>
