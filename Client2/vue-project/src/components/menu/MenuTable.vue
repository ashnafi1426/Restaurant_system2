<template>
  <div class="w-full bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse table-fixed min-w-[800px]">
        <thead>
          <!-- High-Contrast Clean Header Row Layout with explicit tracking and color matching -->
          <tr
            class="border-b border-slate-200 bg-slate-50/70 text-[11px] font-bold text-slate-400 uppercase tracking-widest select-none"
          >
            <th class="py-4.5 px-8 w-[38%]">Item Name</th>
            <th class="py-4.5 px-6 w-[18%]">Category</th>
            <th class="py-4.5 px-6 w-[16%]">Price</th>
            <th class="py-4.5 px-6 w-[16%]">Status</th>
            <th class="py-4.5 px-8 text-right w-[12%]">Actions</th>
          </tr>
        </thead>

        <!-- Expanded spatial margins between row lines -->
        <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-600">
          <tr
            v-for="item in items"
            :key="item.id"
            class="hover:bg-slate-50/50 transition-colors duration-150 group"
          >
            <!-- Item Name & Thumbnail Preview Stack Column with explicit py-5 padding -->
            <td class="py-5 px-8 flex items-center gap-4">
              <div class="relative flex-shrink-0">
                <img
                  :src="item.image_url || '/images/placeholder.png'"
                  class="w-12 h-12 rounded-xl object-cover border border-slate-200 bg-slate-50 shadow-sm transition-transform duration-200 group-hover:scale-[1.02]"
                  alt="Food asset preview"
                  onerror="this.src = '/images/placeholder.png'"
                />
              </div>
              <div class="min-w-0 flex flex-col justify-center gap-0.5">
                <div
                  class="font-extrabold text-slate-900 group-hover:text-indigo-600 transition-colors text-[13px] tracking-wide truncate"
                >
                  {{ item.name }}
                </div>
                <div class="text-[11px] text-slate-400 font-medium truncate max-w-[220px]">
                  {{ item.description || 'Served hot and fresh...' }}
                </div>
              </div>
            </td>

            <!-- Dynamic pill badge column with proportional padding scales -->
            <td class="py-5 px-6 align-middle whitespace-nowrap">
              <span
                class="inline-flex items-center justify-center px-3 py-1 rounded-full text-[10px] font-extrabold tracking-wider uppercase border"
                :class="getCategoryBadgeClass(item.category)"
              >
                {{ item.category }}
              </span>
            </td>

            <!-- Tabular-nums price styling for absolute grid consistency alignment -->
            <td
              class="py-5 px-6 align-middle font-black text-slate-900 text-[14px] tracking-wide font-mono"
            >
              ${{
                typeof item.price === 'number'
                  ? item.price.toFixed(2)
                  : parseFloat(item.price || 0).toFixed(2)
              }}
            </td>

            <!-- Real-time availability indicator block row mapping layout -->
            <td class="py-5 px-6 align-middle whitespace-nowrap">
              <span
                @click="$emit('toggle', item)"
                class="inline-flex items-center gap-2 font-bold cursor-pointer select-none group/status transition"
              >
                <span class="w-2 h-2 rounded-full relative flex">
                  <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-40"
                    :class="item.is_available ? 'bg-emerald-400' : 'bg-rose-400'"
                  ></span>
                  <span
                    class="relative inline-flex rounded-full h-2 w-2"
                    :class="item.is_available ? 'bg-emerald-500' : 'bg-rose-500'"
                  ></span>
                </span>
                <span
                  class="text-[11px] font-bold text-slate-600 group-hover/status:text-slate-900 transition-colors"
                >
                  {{ item.is_available ? 'Available' : 'Out of Stock' }}
                </span>
              </span>
            </td>

            <!-- Context popover menu alignment -->
            <td class="py-5 px-8 text-right align-middle relative whitespace-nowrap">
              <button
                @click.stop="toggleRowMenu(item.id)"
                class="text-slate-400 hover:text-slate-800 p-2 rounded-xl transition hover:bg-slate-100 inline-flex items-center justify-center"
              >
                <!-- Custom SVG matching the sleek, spaced, three-dot horizontal interface layout -->
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                  <path
                    d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"
                  />
                </svg>
              </button>

              <!-- Refined overlay dropdown dialog window with high index separation -->
              <div
                v-if="activeMenuId === item.id"
                class="absolute right-8 top-13 bg-white shadow-xl border border-slate-200/80 rounded-xl py-1.5 z-40 w-44 text-left animate-in fade-in zoom-in-95 duration-100"
              >
                <button
                  @click="() => {
                    $emit('edit', item)
                    activeMenuId = null
                  }"
                  class="w-full text-[11px] font-bold px-4 py-2.5 text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors"
                >
                  <svg
                    class="w-4 h-4 text-slate-400"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    />
                  </svg>
                  Modify Item
                </button>

                <button
                  @click="() => {
                    $emit('toggle', item)
                    activeMenuId = null
                  }"
                  class="w-full text-[11px] font-bold px-4 py-2.5 text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors"
                >
                  <svg
                    class="w-4 h-4 text-slate-400"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                  </svg>
                  Check Status
                </button>

                <div class="border-t border-slate-100 my-1"></div>

                <button
                  @click="() => {
                    $emit('delete', item)
                    activeMenuId = null
                  }"
                  class="w-full text-[11px] font-extrabold px-4 py-2.5 text-rose-600 hover:bg-rose-50 flex items-center gap-3 transition-colors"
                >
                  <svg
                    class="w-4 h-4 text-rose-500"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                  Remove Record
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import type { MenuItem } from '@/types/menu'

defineProps<{
  items: MenuItem[]
  loading: boolean
}>()

defineEmits(['edit', 'delete', 'toggle'])

const activeMenuId = ref<number | null>(null)

function toggleRowMenu(id: number) {
  activeMenuId.value = activeMenuId.value === id ? null : id
}

/**
 * Pixel-Perfect category badge style overrides mapped to the source image configuration
 */
function getCategoryBadgeClass(category: string) {
  if (!category) return 'bg-slate-100 text-slate-500 border-slate-200'
  switch (category.toLowerCase()) {
    case 'dinner':
      return 'bg-emerald-50 text-emerald-600 border-emerald-100/60'
    case 'lunch':
      return 'bg-purple-50 text-purple-600 border-purple-100/60'
    case 'breakfast':
      return 'bg-blue-50 text-blue-600 border-blue-100/60'
    case 'drinks':
      return 'bg-orange-50 text-orange-600 border-orange-100/60'
    case 'dessert':
      return 'bg-rose-50 text-rose-600 border-rose-100/60'
    default:
      return 'bg-slate-50 text-slate-500 border-slate-200'
  }
}

const globalClickOverlayDismiss = (e: MouseEvent) => {
  if (activeMenuId.value !== null && !(e.target as HTMLElement).closest('.relative')) {
    activeMenuId.value = null
  }
}

onMounted(() => {
  window.addEventListener('click', globalClickOverlayDismiss)
})

onUnmounted(() => {
  window.removeEventListener('click', globalClickOverlayDismiss)
})
</script>
