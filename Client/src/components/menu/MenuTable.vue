<template>
  <div class="w-full">
    <!-- Loading State -->
    <div
      v-if="loading"
      class="py-20 px-4 text-center flex flex-col items-center justify-center space-y-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs"
    >
      <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
      <div class="space-y-0.5">
        <p class="text-xs sm:text-sm font-extrabold text-slate-800 dark:text-slate-200">
          {{ languageStore.t('loading_menu_items', 'Loading Menu Items...') }}
        </p>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
          {{
            languageStore.t(
              'fetching_dishes_info',
              'Fetching dishes, prices, and kitchen availability',
            )
          }}
        </p>
      </div>
    </div>

    <!-- Unified Responsive Table -->
    <div
      v-else-if="items.length > 0"
      class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden font-sans w-full"
    >
      <!-- Mobile Horizontal Scroll Hint -->
      <div
        class="md:hidden flex items-center justify-between px-3.5 py-2 bg-slate-50/90 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400 select-none"
      >
        <span class="flex items-center gap-1.5 font-semibold">
          <span>👈</span>
          <span>{{
            languageStore.t('scroll_horizontal', 'Swipe table horizontally to see all columns')
          }}</span>
          <span>👉</span>
        </span>
        <span class="font-bold text-blue-600 dark:text-blue-400">
          {{ items.length }} {{ languageStore.t('dishes', 'dishes') }}
        </span>
      </div>

      <div class="overflow-x-auto w-full min-h-[360px]" style="-webkit-overflow-scrolling: touch">
        <table class="w-full min-w-[850px] text-left border-collapse">
          <thead
            class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]"
          >
            <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
              <th class="py-3 px-4 pl-5 whitespace-nowrap">
                {{ languageStore.t('item_name', 'Item Name') }}
              </th>
              <th class="py-3 px-4 whitespace-nowrap">
                {{ languageStore.t('category', 'Category') }}
              </th>
              <th class="py-3 px-4 whitespace-nowrap font-mono">
                {{ languageStore.t('price', 'Price') }}
              </th>
              <th class="py-3 px-4 text-center whitespace-nowrap">
                {{ languageStore.t('status', 'Status') }}
              </th>
              <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                {{ languageStore.t('actions', 'Actions') }}
              </th>
            </tr>
          </thead>
          <tbody
            class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs font-medium text-slate-600 dark:text-slate-300"
          >
            <tr
              v-for="(item, index) in items"
              :key="item.id"
              class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
              :class="{
                'relative z-30 bg-blue-50/30 dark:bg-[#13233c]/40': activeMenuId === item.id,
              }"
            >
              <!-- Item Column -->
              <td class="py-3 px-4 pl-5 whitespace-nowrap">
                <div class="flex items-center gap-3">
                  <div class="relative flex-shrink-0">
                    <img
                      :src="item.image_url || '/images/placeholder.png'"
                      class="w-10 h-10 rounded-xl object-cover border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 shadow-xs transition-transform duration-200 group-hover:scale-[1.03]"
                      alt="Food preview"
                      onerror="this.src = '/images/placeholder.png'"
                    />
                  </div>
                  <div class="min-w-0 flex flex-col justify-center gap-0.5">
                    <div
                      class="font-extrabold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors text-xs sm:text-sm tracking-wide truncate max-w-[220px]"
                    >
                      {{ item.name }}
                    </div>
                    <div
                      class="text-[10px] text-slate-400 dark:text-slate-500 font-medium truncate max-w-[240px]"
                    >
                      {{
                        item.description ||
                        languageStore.t('fresh_dish_desc', 'Served hot and fresh...')
                      }}
                    </div>
                  </div>
                </div>
              </td>

              <!-- Category Column -->
              <td class="py-3 px-4 align-middle whitespace-nowrap">
                <span
                  :class="getCategoryBadgeClass(item.category)"
                  class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold tracking-wider uppercase border"
                >
                  {{ getCategoryName(item.category) }}
                </span>
              </td>

              <!-- Price Column -->
              <td class="py-3 px-4 align-middle font-mono whitespace-nowrap">
                <div
                  class="font-black text-slate-900 dark:text-white text-xs sm:text-sm tracking-wide"
                >
                  ETB
                  {{
                    (item.total_price !== undefined
                      ? item.total_price
                      : Number(item.price)
                    ).toFixed(2)
                  }}
                </div>
                <div
                  v-if="item.tax_rate && Number(item.tax_rate.rate) > 0"
                  class="text-[10px] font-sans font-medium text-slate-400 dark:text-slate-500"
                >
                  <span
                    v-if="item.tax_included"
                    class="text-emerald-600 dark:text-emerald-400 font-semibold"
                  >
                    Incl. {{ item.tax_rate.rate }}% {{ item.tax_rate.name || 'VAT' }}
                  </span>
                  <span v-else>
                    ETB {{ Number(item.price).toFixed(2) }} + {{ item.tax_rate.rate }}% tax
                  </span>
                </div>
                <div v-else class="text-[10px] font-sans text-slate-400">
                  {{ languageStore.t('no_tax', 'No Tax') }}
                </div>
              </td>

              <!-- Status Column -->
              <td class="py-3 px-4 align-middle text-center whitespace-nowrap">
                <button
                  type="button"
                  @click.stop="$emit('toggle', item)"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-extrabold border transition cursor-pointer select-none"
                  :class="
                    item.is_available
                      ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/20'
                      : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20 hover:bg-rose-500/20'
                  "
                  :title="
                    languageStore.t('toggle_availability_tip', 'Click to toggle availability')
                  "
                >
                  <span class="w-1.5 h-1.5 rounded-full relative flex flex-shrink-0">
                    <span
                      :class="item.is_available ? 'bg-emerald-400' : 'bg-rose-400'"
                      class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-40"
                    ></span>
                    <span
                      :class="item.is_available ? 'bg-emerald-500' : 'bg-rose-500'"
                      class="relative inline-flex rounded-full h-full w-full"
                    ></span>
                  </span>
                  <span>{{
                    item.is_available
                      ? languageStore.t('available', 'Available')
                      : languageStore.t('out_of_stock', 'Out of Stock')
                  }}</span>
                </button>
              </td>

              <!-- Actions Column -->
              <td
                class="py-3 px-4 text-right pr-5 align-middle relative whitespace-nowrap"
                :class="{ 'z-50': activeMenuId === item.id }"
                @click.stop
              >
                <div class="flex items-center justify-end">
                  <div class="relative inline-block text-left action-dropdown-container">
                    <button
                      type="button"
                      @click.stop="toggleRowMenu(item.id, $event)"
                      class="inline-flex items-center justify-center w-8 h-8 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#13233c] text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-300 dark:hover:border-blue-500/50 hover:bg-blue-50/50 dark:hover:bg-blue-950/30 shadow-xs transition cursor-pointer"
                      :class="{
                        'bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border-blue-400 dark:border-blue-500 shadow-sm ring-2 ring-blue-500/20':
                          activeMenuId === item.id,
                      }"
                      :title="languageStore.t('actions', 'Actions')"
                    >
                      <MoreVertical class="w-4 h-4 stroke-[2.5]" />
                    </button>

                    <Transition
                      enter-active-class="transition duration-100 ease-out"
                      enter-from-class="transform scale-95 opacity-0 -translate-y-2"
                      enter-to-class="transform scale-100 opacity-100 translate-y-0"
                      leave-active-class="transition duration-75 ease-in"
                      leave-from-class="transform scale-100 opacity-100 translate-y-0"
                      leave-to-class="transform scale-95 opacity-0 -translate-y-2"
                    >
                      <div
                        v-if="activeMenuId === item.id"
                        class="absolute right-0 z-50 w-48 rounded-2xl bg-white dark:bg-[#0f1d32] border border-slate-200 dark:border-slate-800 shadow-2xl p-1.5 space-y-0.5 text-left focus:outline-none"
                        :class="[
                          index >= items.length - 2 && items.length > 2
                            ? 'bottom-full mb-1.5'
                            : 'top-full mt-1.5',
                        ]"
                        @click.stop
                      >
                        <button
                          type="button"
                          @click="handleAction(() => $emit('edit', item))"
                          class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-blue-950/40 hover:text-blue-600 dark:hover:text-blue-400 transition cursor-pointer text-left"
                        >
                          <Edit class="w-4 h-4 text-blue-500 flex-shrink-0" />
                          <span>{{ languageStore.t('edit', 'Edit') }}</span>
                        </button>

                        <button
                          type="button"
                          @click="handleAction(() => $emit('toggle', item))"
                          class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-600 dark:hover:text-emerald-400 transition cursor-pointer text-left"
                        >
                          <CheckCircle2
                            v-if="!item.is_available"
                            class="w-4 h-4 text-emerald-500 flex-shrink-0"
                          />
                          <XCircle v-else class="w-4 h-4 text-amber-500 flex-shrink-0" />
                          <span>{{
                            item.is_available
                              ? languageStore.t('mark_out_of_stock', 'Mark Out of Stock')
                              : languageStore.t('mark_available', 'Mark Available')
                          }}</span>
                        </button>

                        <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>

                        <button
                          type="button"
                          @click="handleAction(() => $emit('delete', item))"
                          class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer text-left"
                        >
                          <Trash2 class="w-4 h-4 text-rose-500 flex-shrink-0" />
                          <span>{{ languageStore.t('remove_record', 'Remove Record') }}</span>
                        </button>
                      </div>
                    </Transition>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Loader2, MoreVertical, Edit, CheckCircle2, XCircle, Trash2 } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import type { MenuItem } from '@/types/menu'

const languageStore = useLanguageStore()

defineProps<{
  items: MenuItem[]
  loading: boolean
}>()

defineEmits(['edit', 'delete', 'toggle'])

const activeMenuId = ref<string | number | null>(null)

function toggleRowMenu(id: string | number, e?: MouseEvent) {
  if (e) e.stopPropagation()
  activeMenuId.value = activeMenuId.value === id ? null : id
}

function closeRowMenu() {
  activeMenuId.value = null
}

function handleAction(actionFn: () => void) {
  closeRowMenu()
  actionFn()
}

function getCategoryName(category: any): string {
  if (!category) return 'General'
  if (typeof category === 'string') return category
  if (typeof category === 'object') {
    return category.name || category.slug || category.category_name || 'General'
  }
  return String(category)
}

function getCategoryBadgeClass(category: any) {
  const catName = getCategoryName(category).toLowerCase()
  switch (catName) {
    case 'dinner':
      return 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/40'
    case 'lunch':
      return 'bg-purple-50 dark:bg-purple-950/30 text-purple-600 dark:text-purple-400 border-purple-200 dark:border-purple-800/40'
    case 'breakfast':
      return 'bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-800/40'
    case 'drinks':
    case 'beverages':
      return 'bg-orange-50 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400 border-orange-200 dark:border-orange-800/40'
    case 'dessert':
    case 'desserts':
      return 'bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800/40'
    case 'pasta':
      return 'bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800/40'
    default:
      return 'bg-slate-50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'
  }
}

onMounted(() => {
  window.addEventListener('click', closeRowMenu)
})

onUnmounted(() => {
  window.removeEventListener('click', closeRowMenu)
})
</script>
