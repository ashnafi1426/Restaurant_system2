<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import type { Guest } from '../../types/guest'

interface Props {
  guests: Guest[]
  loading?: boolean
}

defineProps<Props>()

const emit = defineEmits<{
  (e: 'view', guest: Guest): void
  (e: 'edit', guest: Guest): void
  (e: 'delete', guest: Guest): void
}>()

const formatDate = (date: string | null) => {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const openMenu = ref<string | null>(null)

const toggleMenu = (id: string) => {
  openMenu.value = openMenu.value === id ? null : id
}

const closeMenu = () => {
  openMenu.value = null
}

const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement
  if (!target.closest('.action-menu')) {
    closeMenu()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <!-- Header -->
    <div
      class="flex items-center justify-between border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-6 py-4"
    >
      <div class="flex items-center gap-3">
        <div class="rounded-lg bg-blue-100 p-2">
          <span class="material-symbols-rounded text-blue-600">groups</span>
        </div>
        <div>
          <h2 class="text-lg font-semibold text-slate-800">Guest Records</h2>
          <p class="text-sm text-slate-500">Complete list of registered guests</p>
        </div>
      </div>
      <div v-if="!loading && guests.length > 0" class="text-sm text-slate-500">
        {{ guests.length }} guest{{ guests.length !== 1 ? 's' : '' }}
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex flex-col items-center justify-center p-16 text-slate-500">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
      <p class="font-medium">Loading guests...</p>
    </div>

    <!-- Empty -->
    <div v-else-if="guests.length === 0" class="p-16 text-center">
      <div
        class="mx-auto w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mb-4"
      >
        <span class="material-symbols-rounded text-4xl text-slate-400">groups</span>
      </div>
      <h3 class="text-xl font-semibold text-slate-700 mb-2">No Guests Found</h3>
      <p class="text-slate-500">No guest records match your current filters</p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th
              class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider"
            >
              Guest Information
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider"
            >
              Contact
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider"
            >
              Nationality
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider"
            >
              Passport
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider"
            >
              Registered
            </th>
            <th
              class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider w-24"
            >
              Actions
            </th>
          </tr>
        </thead>

        <tbody class="bg-white divide-y divide-slate-200">
          <tr v-for="guest in guests" :key="guest.id" class="hover:bg-slate-50 transition-colors">
            <!-- Guest Information -->
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div
                  class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold"
                >
                  {{ guest.first_name.charAt(0) }}{{ guest.last_name.charAt(0) }}
                </div>
                <div>
                  <div class="font-semibold text-slate-900">
                    {{ guest.full_name }}
                  </div>
                  <div class="text-sm text-slate-500">
                    {{ guest.email || 'No email provided' }}
                  </div>
                </div>
              </div>
            </td>

            <!-- Contact -->
            <td class="px-6 py-4">
              <div class="flex items-center gap-2 text-sm text-slate-700">
                <span class="material-symbols-rounded text-sm text-slate-400">phone</span>
                {{ guest.phone }}
              </div>
            </td>

            <!-- Nationality -->
            <td class="px-6 py-4">
              <div
                v-if="guest.nationality"
                class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm font-medium"
              >
                <span class="material-symbols-rounded text-sm">public</span>
                {{ guest.nationality }}
              </div>
              <span v-else class="text-slate-400 text-sm">Not specified</span>
            </td>

            <!-- Passport -->
            <td class="px-6 py-4">
              <div v-if="guest.passport_number" class="flex items-center gap-2 text-sm">
                <span class="material-symbols-rounded text-sm text-slate-400">badge</span>
                <span class="font-mono text-slate-700">{{ guest.passport_number }}</span>
              </div>
              <span v-else class="text-slate-400 text-sm">-</span>
            </td>

            <!-- Registered Date -->
            <td class="px-6 py-4">
              <div class="flex items-center gap-2 text-sm text-slate-600">
                <span class="material-symbols-rounded text-sm text-slate-400">calendar_today</span>
                {{ formatDate(guest.created_at) }}
              </div>
            </td>

            <!-- Actions -->
            <td class="relative px-6 py-4 text-center">
              <div class="action-menu inline-block relative">
                <button
                  @click.stop="toggleMenu(guest.id)"
                  class="flex h-9 w-9 items-center justify-center rounded-lg hover:bg-slate-100 transition-colors mx-auto"
                  :class="{ 'bg-slate-100': openMenu === guest.id }"
                >
                  <span class="material-symbols-rounded text-slate-600">more_vert</span>
                </button>

                <transition
                  enter-active-class="transition duration-150 ease-out"
                  leave-active-class="transition duration-100 ease-in"
                  enter-from-class="opacity-0 scale-95"
                  enter-to-class="opacity-100 scale-100"
                  leave-from-class="opacity-100 scale-100"
                  leave-to-class="opacity-0 scale-95"
                >
                  <div
                    v-if="openMenu === guest.id"
                    class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
                  >
                    <!-- View -->
                    <button
                      @click="
                        () => {
                          emit('view', guest)
                          closeMenu()
                        }
                      "
                      class="flex w-full items-center gap-3 px-4 py-3 hover:bg-blue-50 transition-colors text-left"
                    >
                      <span class="material-symbols-rounded text-blue-600">visibility</span>
                      <span class="font-medium text-slate-700">View Details</span>
                    </button>

                    <!-- Edit -->
                    <button
                      @click="
                        () => {
                          emit('edit', guest)
                          closeMenu()
                        }
                      "
                      class="flex w-full items-center gap-3 px-4 py-3 hover:bg-green-50 transition-colors text-left"
                    >
                      <span class="material-symbols-rounded text-green-600">edit</span>
                      <span class="font-medium text-slate-700">Edit Guest</span>
                    </button>

                    <div class="border-t border-slate-200"></div>

                    <!-- Delete -->
                    <button
                      @click="
                        () => {
                          emit('delete', guest)
                          closeMenu()
                        }
                      "
                      class="flex w-full items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 transition-colors text-left"
                    >
                      <span class="material-symbols-rounded">delete</span>
                      <span class="font-medium">Delete Guest</span>
                    </button>
                  </div>
                </transition>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
