<template>
  <div class="w-full">
    <!-- Desktop Table View (md and above) -->
    <div
      class="hidden md:block bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden"
    >
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr
              class="border-b border-slate-200 bg-slate-50/70 text-xs font-bold text-slate-400 uppercase tracking-widest select-none"
            >
              <th class="py-4 px-8 w-[35%]">Item Name</th>
              <th class="py-4 px-6 w-[18%]">Category</th>
              <th class="py-4 px-6 w-[16%]">Price</th>
              <th class="py-4 px-6 w-[16%]">Status</th>
              <th class="py-4 px-8 text-right w-[15%]">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-sm font-medium text-slate-600">
            <tr
              v-for="item in items"
              :key="item.id"
              class="hover:bg-slate-50/50 transition-colors duration-150 group"
            >
              <!-- Item Name & Thumbnail -->
              <td class="py-5 px-8 flex items-center gap-4">
                <div class="relative flex-shrink-0">
                  <img
                    :src="item.image_url || '/images/placeholder.png'"
                    class="w-12 h-12 rounded-xl object-cover border border-slate-200 bg-slate-50 shadow-sm transition-transform duration-200 group-hover:scale-[1.02]"
                    alt="Food preview"
                    onerror="this.src = '/images/placeholder.png'"
                  />
                </div>
                <div class="min-w-0 flex flex-col justify-center gap-0.5">
                  <div
                    class="font-extrabold text-slate-900 group-hover:text-indigo-600 transition-colors text-base tracking-wide truncate"
                  >
                    {{ item.name }}
                  </div>
                  <div class="text-xs text-slate-400 font-medium truncate max-w-[200px]">
                    {{ item.description || 'Served hot and fresh...' }}
                  </div>
                </div>
              </td>
              <!-- Category Badge -->
              <td class="py-5 px-6 align-middle whitespace-nowrap">
                <span
                  :class="getCategoryBadgeClass(item.category)"
                  class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-extrabold tracking-wider uppercase border"
                >
                  {{ item.category }}
                </span>
              </td>
              <!-- Price -->
              <td
                class="py-5 px-6 align-middle font-black text-slate-900 text-base tracking-wide font-mono"
              >
                ${{
                  typeof item.price === 'number'
                    ? item.price.toFixed(2)
                    : parseFloat(item.price || 0).toFixed(2)
                }}
              </td>
              <!-- Status -->
              <td class="py-5 px-6 align-middle whitespace-nowrap">
                <span
                  @click="$emit('toggle', item)"
                  class="inline-flex items-center gap-2 font-bold cursor-pointer select-none group/status transition text-sm"
                >
                  <span class="w-2 h-2 rounded-full relative flex flex-shrink-0">
                    <span
                      :class="item.is_available ? 'bg-emerald-400' : 'bg-rose-400'"
                      class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-40"
                    ></span>
                    <span
                      :class="item.is_available ? 'bg-emerald-500' : 'bg-rose-500'"
                      class="relative inline-flex rounded-full h-full w-full"
                    ></span>
                  </span>
                  <span
                    class="text-sm font-bold text-slate-600 group-hover/status:text-slate-900 transition-colors"
                  >
                    {{ item.is_available ? 'Available' : 'Out of Stock' }}
                  </span>
                </span>
              </td>
              <!-- Actions Menu -->
              <td
                class="py-5 px-8 text-right align-middle relative whitespace-nowrap"
                data-menu-container
              >
                <div class="flex justify-end">
                  <button
                    @click.stop="toggleRowMenu(item.id)"
                    class="text-slate-400 hover:text-slate-800 p-2 rounded-xl transition hover:bg-slate-100 inline-flex items-center justify-center min-h-10 w-10"
                  >
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                      <path
                        d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"
                      />
                    </svg>
                  </button>
                </div>
                <!-- Desktop Dropdown Menu -->
                <div
                  v-if="activeMenuId === item.id"
                  class="absolute right-8 top-14 bg-white shadow-xl border border-slate-200/80 rounded-xl py-1 z-40 w-44 text-left animate-in fade-in zoom-in-95 duration-100"
                >
                  <button
                    @click="
                      () => {
                        $emit('edit', item)
                        activeMenuId = null
                      }
                    "
                    class="w-full text-xs font-bold px-4 py-2.5 text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors min-h-10"
                  >
                    <svg
                      class="w-4 h-4 text-slate-400 flex-shrink-0"
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
                    <span class="truncate">Modify Item</span>
                  </button>
                  <button
                    @click="
                      () => {
                        $emit('toggle', item)
                        activeMenuId = null
                      }
                    "
                    class="w-full text-xs font-bold px-4 py-2.5 text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors min-h-10"
                  >
                    <svg
                      class="w-4 h-4 text-slate-400 flex-shrink-0"
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
                    <span class="truncate">Check Status</span>
                  </button>
                  <div class="border-t border-slate-100 my-1"></div>
                  <button
                    @click="
                      () => {
                        $emit('delete', item)
                        activeMenuId = null
                      }
                    "
                    class="w-full text-xs font-extrabold px-4 py-2.5 text-rose-600 hover:bg-rose-50 flex items-center gap-3 transition-colors min-h-10"
                  >
                    <svg
                      class="w-4 h-4 text-rose-500 flex-shrink-0"
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
                    <span class="truncate">Remove Record</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tablet View (sm to md) -->
    <div
      class="hidden sm:block md:hidden bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden"
    >
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr
              class="border-b border-slate-200 bg-slate-50/70 text-xs font-bold text-slate-400 uppercase tracking-wider select-none"
            >
              <th class="py-3 px-4 w-[40%]">Item</th>
              <th class="py-3 px-3 w-[20%]">Category</th>
              <th class="py-3 px-3 w-[15%]">Status</th>
              <th class="py-3 px-4 text-right w-[12%]">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-600">
            <tr
              v-for="item in items"
              :key="item.id"
              class="hover:bg-slate-50/50 transition-colors duration-150 group"
            >
              <!-- Item Name & Thumbnail -->
              <td class="py-4 px-4 flex items-center gap-3">
                <div class="relative flex-shrink-0">
                  <img
                    :src="item.image_url || '/images/placeholder.png'"
                    class="w-10 h-10 rounded-lg object-cover border border-slate-200 bg-slate-50 shadow-sm transition-transform duration-200 group-hover:scale-[1.02]"
                    alt="Food preview"
                    onerror="this.src = '/images/placeholder.png'"
                  />
                </div>
                <div class="min-w-0 flex flex-col justify-center gap-0.5">
                  <div
                    class="font-bold text-slate-900 group-hover:text-indigo-600 transition-colors text-xs tracking-wide truncate"
                  >
                    {{ item.name }}
                  </div>
                  <div class="text-xs text-slate-400 font-medium truncate">
                    Price: ${{
                      typeof item.price === 'number'
                        ? item.price.toFixed(2)
                        : parseFloat(item.price || 0).toFixed(2)
                    }}
                  </div>
                </div>
              </td>
              <!-- Category Badge -->
              <td class="py-4 px-3 align-middle whitespace-nowrap">
                <span
                  :class="getCategoryBadgeClass(item.category)"
                  class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs font-bold tracking-wider uppercase border"
                >
                  {{ item.category }}
                </span>
              </td>
              <!-- Status -->
              <td class="py-4 px-3 align-middle whitespace-nowrap">
                <span
                  @click="$emit('toggle', item)"
                  class="inline-flex items-center gap-1.5 font-bold cursor-pointer select-none group/status transition text-xs"
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
                  <span
                    class="text-xs font-bold text-slate-600 group-hover/status:text-slate-900 transition-colors hidden sm:inline"
                  >
                    {{ item.is_available ? 'Avail' : 'OOS' }}
                  </span>
                </span>
              </td>
              <!-- Actions Menu -->
              <td
                class="py-4 px-4 text-right align-middle relative whitespace-nowrap"
                data-menu-container
              >
                <div class="flex justify-end">
                  <button
                    @click.stop="toggleRowMenu(item.id)"
                    class="text-slate-400 hover:text-slate-800 p-1.5 rounded-lg transition hover:bg-slate-100 inline-flex items-center justify-center min-h-9 w-9"
                  >
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                      <path
                        d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"
                      />
                    </svg>
                  </button>
                </div>
                <!-- Tablet Dropdown Menu -->
                <div
                  v-if="activeMenuId === item.id"
                  class="absolute right-4 top-10 bg-white shadow-xl border border-slate-200/80 rounded-lg py-1 z-40 w-40 text-left animate-in fade-in zoom-in-95 duration-100"
                >
                  <button
                    @click="
                      () => {
                        $emit('edit', item)
                        activeMenuId = null
                      }
                    "
                    class="w-full text-xs font-bold px-3 py-2 text-slate-700 hover:bg-slate-50 flex items-center gap-2 transition-colors min-h-9"
                  >
                    <svg
                      class="w-3 h-3 text-slate-400 flex-shrink-0"
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
                    <span class="truncate text-xs">Edit</span>
                  </button>
                  <button
                    @click="
                      () => {
                        $emit('toggle', item)
                        activeMenuId = null
                      }
                    "
                    class="w-full text-xs font-bold px-3 py-2 text-slate-700 hover:bg-slate-50 flex items-center gap-2 transition-colors min-h-9"
                  >
                    <svg
                      class="w-3 h-3 text-slate-400 flex-shrink-0"
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
                    <span class="truncate text-xs">Status</span>
                  </button>
                  <div class="border-t border-slate-100 my-1"></div>
                  <button
                    @click="
                      () => {
                        $emit('delete', item)
                        activeMenuId = null
                      }
                    "
                    class="w-full text-xs font-extrabold px-3 py-2 text-rose-600 hover:bg-rose-50 flex items-center gap-2 transition-colors min-h-9"
                  >
                    <svg
                      class="w-3 h-3 text-rose-500 flex-shrink-0"
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
                    <span class="truncate text-xs">Delete</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Mobile View (below sm) -->
    <div class="sm:hidden space-y-3">
      <div
        v-for="item in items"
        :key="item.id"
        class="bg-white rounded-lg border border-slate-200/80 shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-150"
      >
        <!-- Card Header with Image and Basic Info -->
        <div class="p-4 border-b border-slate-100" data-menu-container>
          <div class="flex items-start gap-3 mb-3">
            <!-- Image -->
            <div class="relative flex-shrink-0">
              <img
                :src="item.image_url || '/images/placeholder.png'"
                class="w-16 h-16 rounded-lg object-cover border border-slate-200 bg-slate-50 shadow-sm"
                alt="Food preview"
                onerror="this.src = '/images/placeholder.png'"
              />
            </div>
            <!-- Title, Price, Category -->
            <div class="min-w-0 flex-1">
              <h3 class="font-bold text-slate-900 text-sm leading-tight mb-1 truncate">
                {{ item.name }}
              </h3>
              <p class="text-xs text-slate-400 mb-2 truncate">
                {{ item.description || 'Served hot and fresh...' }}
              </p>
              <div class="flex items-center gap-2 flex-wrap">
                <span
                  :class="getCategoryBadgeClass(item.category)"
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold tracking-wider uppercase border"
                >
                  {{ item.category }}
                </span>
                <span class="font-black text-slate-900 text-sm font-mono">
                  ${{
                    typeof item.price === 'number'
                      ? item.price.toFixed(2)
                      : parseFloat(item.price || 0).toFixed(2)
                  }}
                </span>
              </div>
            </div>
            <!-- Three-Dot Menu Button -->
            <div class="flex-shrink-0">
              <button
                @click.stop="toggleRowMenu(item.id)"
                class="text-slate-400 hover:text-slate-800 p-1.5 rounded-lg transition hover:bg-slate-100 inline-flex items-center justify-center min-h-9 w-9"
              >
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                  <path
                    d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"
                  />
                </svg>
              </button>
            </div>
          </div>

          <!-- Status Badge -->
          <div class="flex items-center gap-2">
            <span
              @click="$emit('toggle', item)"
              class="inline-flex items-center gap-2 font-bold cursor-pointer select-none group/status transition text-xs"
            >
              <span class="w-2 h-2 rounded-full relative flex flex-shrink-0">
                <span
                  :class="item.is_available ? 'bg-emerald-400' : 'bg-rose-400'"
                  class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-40"
                ></span>
                <span
                  :class="item.is_available ? 'bg-emerald-500' : 'bg-rose-500'"
                  class="relative inline-flex rounded-full h-full w-full"
                ></span>
              </span>
              <span
                class="text-xs font-bold text-slate-600 group-hover/status:text-slate-900 transition-colors"
              >
                {{ item.is_available ? 'Available' : 'Out of Stock' }}
              </span>
            </span>
          </div>
        </div>

        <!-- Mobile Dropdown Menu -->
        <div
          v-if="activeMenuId === item.id"
          class="bg-slate-50/50 border-t border-slate-100"
          data-menu-container
        >
          <button
            @click="
              () => {
                $emit('edit', item)
                activeMenuId = null
              }
            "
            class="w-full px-4 py-3 text-left flex items-center gap-3 text-slate-700 hover:bg-slate-100 transition-colors border-b border-slate-100"
          >
            <svg
              class="w-4 h-4 text-slate-400 flex-shrink-0"
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
            <div class="text-sm font-bold">Modify Item</div>
          </button>
          <button
            @click="
              () => {
                $emit('toggle', item)
                activeMenuId = null
              }
            "
            class="w-full px-4 py-3 text-left flex items-center gap-3 text-slate-700 hover:bg-slate-100 transition-colors border-b border-slate-100"
          >
            <svg
              class="w-4 h-4 text-slate-400 flex-shrink-0"
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
            <div class="text-sm font-bold">Check Status</div>
          </button>
          <button
            @click="
              () => {
                $emit('delete', item)
                activeMenuId = null
              }
            "
            class="w-full px-4 py-3 text-left flex items-center gap-3 text-rose-600 hover:bg-rose-50 transition-colors"
          >
            <svg
              class="w-4 h-4 text-rose-500 flex-shrink-0"
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
            <div class="text-sm font-bold">Remove Record</div>
          </button>
        </div>
      </div>
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
  const target = e.target as HTMLElement
  const isMenuOpen = activeMenuId.value !== null

  // Check if click is inside any menu button or dropdown
  const isInsideMenu = target.closest('[data-menu-container]')

  // Close menu only if click is outside of it
  if (isMenuOpen && !isInsideMenu) {
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
