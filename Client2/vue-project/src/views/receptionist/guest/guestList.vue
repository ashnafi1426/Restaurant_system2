<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import GuestFilters from '../../../components/guest/guestFilter.vue'
import GuestTable from '../../../components/guest/guestTable.vue'
import DeleteGuestDialog from '../../../components/guest/deleteGuestDialog.vue'
import { useGuestStore } from '../../../stores/guestStore'
import type { Guest, GuestFilter } from '../../../types/guest'

const router = useRouter()
const guestStore = useGuestStore()
const loading = ref(false)
const deleteDialog = ref(false)
const selectedGuest = ref<Guest | null>(null)
const showSuccessMessage = ref(false)
const successMessage = ref('')

const filters = ref<GuestFilter>({
  search: '',
  nationality: '',
  page: 1,
  per_page: 10,
})

const totalGuests = computed(() => {
  const total = guestStore.pagination?.total ?? 0
  // Ensure it's a number, not an array
  return typeof total === 'number' ? total : Array.isArray(total) ? total[0] : 0
})
const currentPage = computed(() => {
  const page = guestStore.pagination?.current_page ?? 1
  // Ensure it's a number, not an array
  return typeof page === 'number' ? page : Array.isArray(page) ? page[0] : 1
})
const lastPage = computed(() => guestStore.pagination?.last_page ?? 1)
const guestsOnPage = computed(() => guestStore.guests?.length ?? 0)
const hasGuests = computed(() => guestsOnPage.value > 0)

const loadGuests = async () => {
  loading.value = true

  try {
    await guestStore.fetchGuests(filters.value)
  } catch (error) {
    showMessage('Failed to load guests. Please try again.', 'error')
  } finally {
    loading.value = false
  }
}

const refreshGuests = async () => {
  await loadGuests()
  showMessage('Guest list refreshed successfully', 'success')
}

const resetFilters = async () => {
  filters.value = {
    search: '',
    nationality: '',
    page: 1,
    per_page: 10,
  }
  await loadGuests()
}

const createGuest = () => {
  router.push('/guests/create')
}

const viewGuest = (guest: Guest) => {
  router.push(`/guests/${guest.id}`)
}

const editGuest = (guest: Guest) => {
  router.push(`/guests/${guest.id}/edit`)
}

const confirmDelete = (guest: Guest) => {
  selectedGuest.value = guest
  deleteDialog.value = true
}

const removeGuest = async () => {
  if (!selectedGuest.value) return
  try {
    await guestStore.removeGuest(String(selectedGuest.value.id))
    deleteDialog.value = false
    selectedGuest.value = null
    await loadGuests()
    showMessage('Guest deleted successfully', 'success')
  } catch (error) {
    showMessage('Failed to delete guest. Please try again.', 'error')
  }
}

const previousPage = async () => {
  if (filters.value.page! <= 1) return
  filters.value.page!--
  await loadGuests()
}

const nextPage = async () => {
  if (filters.value.page! >= lastPage.value) return
  filters.value.page!++
  await loadGuests()
}

const goToPage = async (page: number) => {
  filters.value.page = page
  await loadGuests()
}

const showMessage = (message: string, type: 'success' | 'error' = 'success') => {
  successMessage.value = message
  showSuccessMessage.value = true
  setTimeout(() => {
    showSuccessMessage.value = false
  }, 3000)
}

onMounted(() => {
  loadGuests()
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Success/Error Toast -->
      <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
      >
        <div
          v-if="showSuccessMessage"
          class="fixed top-4 right-4 z-50 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-3"
        >
          <span class="material-symbols-rounded">check_circle</span>
          <span>{{ successMessage }}</span>
        </div>
      </transition>

      <!-- Breadcrumb -->
      <nav class="flex items-center text-sm text-slate-500">
        <a href="/dashboard" class="hover:text-slate-700 transition">Dashboard</a>
        <span class="mx-2">/</span>
        <span class="font-medium text-slate-800">Guest Management</span>
      </nav>

      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl shadow-xl p-8 text-white">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h1 class="text-4xl font-bold mb-2">Guest Management</h1>
            <p class="text-blue-100 text-lg">Manage hotel guest records and information</p>
          </div>
          <div class="flex gap-2">
            <button
              @click="createGuest"
              class="flex items-center gap-2 rounded-lg bg-white text-blue-600 px-6 py-3 hover:bg-blue-50 transition shadow-lg"
            >
              <span class="material-symbols-rounded text-xl">add</span>
              <span class="font-semibold">New Guest</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Guests -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-blue-50 to-white p-6 shadow-sm hover:shadow-md transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Total Guests</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900">
                {{ totalGuests }}
              </h2>
            </div>
            <div class="rounded-full bg-blue-100 p-3">
              <span class="material-symbols-rounded text-3xl text-blue-600">groups</span>
            </div>
          </div>
        </div>

        <!-- Current Page -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-purple-50 to-white p-6 shadow-sm hover:shadow-md transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Current Page</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900">
                {{ currentPage }}
              </h2>
            </div>
            <div class="rounded-full bg-purple-100 p-3">
              <span class="material-symbols-rounded text-3xl text-purple-600">menu_book</span>
            </div>
          </div>
        </div>

        <!-- Per Page -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-green-50 to-white p-6 shadow-sm hover:shadow-md transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Per Page</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900">
                {{ filters.per_page }}
              </h2>
            </div>
            <div class="rounded-full bg-green-100 p-3">
              <span class="material-symbols-rounded text-3xl text-green-600">view_list</span>
            </div>
          </div>
        </div>

        <!-- On This Page -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-amber-50 to-white p-6 shadow-sm hover:shadow-md transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">On This Page</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900">
                {{ guestsOnPage }}
              </h2>
            </div>
            <div class="rounded-full bg-amber-100 p-3">
              <span class="material-symbols-rounded text-3xl text-amber-600">article</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <GuestFilters
        :filters="filters"
        @update:filters="filters = $event"
        @search="loadGuests"
        @reset="resetFilters"
        @create="createGuest"
      />

      <!-- Table -->
      <GuestTable
        :guests="guestStore.guests"
        :loading="loading"
        @view="viewGuest"
        @edit="editGuest"
        @delete="confirmDelete"
      />

      <!-- Empty State -->
      <div
        v-if="!loading && !hasGuests"
        class="rounded-2xl border-2 border-dashed border-slate-300 bg-white p-16 text-center"
      >
        <div
          class="mx-auto w-24 h-24 rounded-full bg-slate-100 flex items-center justify-center mb-6"
        >
          <span class="material-symbols-rounded text-5xl text-slate-400">groups</span>
        </div>
        <h2 class="text-2xl font-bold text-slate-700 mb-2">No Guests Found</h2>
        <p class="text-slate-500 mb-6 max-w-md mx-auto">
          There are currently no guests matching your filters. Create your first guest to get
          started.
        </p>
        <button
          @click="createGuest"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-white hover:bg-blue-700 transition shadow-lg font-medium"
        >
          <span class="material-symbols-rounded">add</span>
          Create First Guest
        </button>
      </div>

      <!-- Pagination -->
      <div
        v-if="hasGuests"
        class="flex flex-col gap-4 rounded-xl border bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="text-slate-600">
          Showing
          <span class="font-semibold text-slate-900">{{ guestsOnPage }}</span>
          of
          <span class="font-semibold text-slate-900">{{ totalGuests }}</span>
          guests
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="previousPage"
            :disabled="currentPage <= 1"
            class="flex items-center gap-1 rounded-lg border border-slate-300 px-4 py-2 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-white transition"
          >
            <span class="material-symbols-rounded text-sm">chevron_left</span>
            <span>Previous</span>
          </button>

          <div class="flex items-center gap-1 px-4">
            <span class="font-semibold text-slate-700">Page {{ currentPage }}</span>
            <span class="text-slate-500">of {{ lastPage }}</span>
          </div>

          <button
            @click="nextPage"
            :disabled="currentPage >= lastPage"
            class="flex items-center gap-1 rounded-lg border border-slate-300 px-4 py-2 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-white transition"
          >
            <span>Next</span>
            <span class="material-symbols-rounded text-sm">chevron_right</span>
          </button>
        </div>
      </div>

      <!-- Delete Dialog -->
      <DeleteGuestDialog
        :open="deleteDialog"
        :guest="selectedGuest"
        :loading="guestStore.loading"
        @close="deleteDialog = false"
        @confirm="removeGuest"
      />
    </div>
  </DashboardLayout>
</template>
