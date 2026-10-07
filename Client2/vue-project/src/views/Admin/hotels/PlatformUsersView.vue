<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import { platformService, type Hotel } from '@/services/platformService'
import {
  Users,
  Search,
  RefreshCw,
  Building2,
  AlertCircle,
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight
} from 'lucide-vue-next'

const users = ref<any[]>([])
const hotels = ref<Hotel[]>([])
const loading = ref(true)
const searchQuery = ref('')
const selectedRole = ref('all')
const selectedHotelId = ref('all')
const errorMessage = ref('')

const currentPage = ref(1)
const lastPage = ref(1)
const totalUsers = ref(0)
const perPage = ref(10)
const perPageOptions = [5, 10, 20, 50]

// Computed property for pagination display
const paginationInfo = computed(() => {
  const start = ((currentPage.value - 1) * perPage.value) + 1
  const end = Math.min(currentPage.value * perPage.value, totalUsers.value)
  return { start, end }
})

// Computed property for page numbers to display
const visiblePages = computed(() => {
  const pages: (number | string)[] = []
  const total = lastPage.value
  const current = currentPage.value
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else if (current <= 4) {
    for (let i = 1; i <= 5; i++) pages.push(i)
    pages.push('ellipsis', total)
  } else if (current >= total - 3) {
    pages.push(1, 'ellipsis')
    for (let i = total - 4; i <= total; i++) pages.push(i)
  } else {
    pages.push(1, 'ellipsis', current - 1, current, current + 1, 'ellipsis', total)
  }
  
  return pages
})

const loadUsers = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await platformService.getAllUsers({
      search: searchQuery.value || undefined,
      role: selectedRole.value !== 'all' ? selectedRole.value : undefined,
      hotel_id: selectedHotelId.value !== 'all' ? selectedHotelId.value : undefined,
      page: currentPage.value,
      per_page: perPage.value,
    })
    users.value = res.data || []
    currentPage.value = res.current_page || 1
    lastPage.value = res.last_page || 1
    totalUsers.value = res.total || 0
  } catch (err: any) {
    console.error('[PlatformUsersView] Error loading users:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to load platform users.'
  } finally {
    loading.value = false
  }
}

const loadHotels = async () => {
  try {
    const res = await platformService.getHotels({ per_page: 100 })
    hotels.value = res.data || []
  } catch (e) {
    console.warn('Could not load hotels list:', e)
  }
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= lastPage.value && page !== currentPage.value) {
    currentPage.value = page
    loadUsers()
  }
}

onMounted(() => {
  loadUsers()
  loadHotels()
})

watch([searchQuery, selectedRole, selectedHotelId, perPage], () => {
  currentPage.value = 1
  loadUsers()
})

const roleBadgeClasses: Record<string, string> = {
  admin: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
  manager: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
  receptionist: 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/20',
  chef: 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-500/20',
  waiter: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
  cashier: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
}

const getRoleBadgeClass = (role: string) => {
  return roleBadgeClasses[String(role).toLowerCase()] || 'bg-slate-500/10 text-slate-600 border-slate-500/20'
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full font-sans">
      <!-- Header -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="p-3 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
            <Users class="w-6 h-6" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                All Users & Staff
              </h1>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                {{ totalUsers }} Users
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              Super Admin cross-hotel staff directory across all properties.
            </p>
          </div>
        </div>

        <button
          @click="loadUsers"
          :disabled="loading"
          class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer border border-slate-200 dark:border-slate-700 font-bold text-xs flex items-center gap-1.5 self-start md:self-auto"
        >
          <RefreshCw :class="['w-4 h-4', loading && 'animate-spin']" />
          <span>Refresh</span>
        </button>
      </div>

      <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-bold flex items-center gap-2">
        <AlertCircle class="w-4 h-4 flex-shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Filters Toolbar -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-xs flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="relative w-full md:w-80">
          <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by name, email..."
            class="w-full pl-9 pr-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium"
          />
        </div>

        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
          <!-- Role Filter -->
          <div class="flex items-center gap-1.5">
            <span class="text-xs font-bold text-slate-400">Role:</span>
            <select
              v-model="selectedRole"
              class="px-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white font-medium focus:outline-none"
            >
              <option value="all">All Roles</option>
              <option value="admin">Admin</option>
              <option value="manager">Manager</option>
              <option value="receptionist">Receptionist</option>
              <option value="chef">Chef</option>
              <option value="waiter">Waiter</option>
              <option value="cashier">Cashier</option>
            </select>
          </div>

          <!-- Hotel Filter -->
          <div class="flex items-center gap-1.5">
            <span class="text-xs font-bold text-slate-400">Hotel:</span>
            <div class="w-56">
              <SearchableSelect
                v-model="selectedHotelId"
                :options="[{ id: 'all', name: 'All Hotels', city: '' }, ...hotels]"
                label-key="name"
                value-key="id"
                sublabel-key="city"
                placeholder="All Hotels"
                search-placeholder="Filter by hotel..."
                :format-option-label="(opt) => opt.id === 'all' ? 'All Hotels' : `${opt.name}${opt.city ? ` (${opt.city})` : ''}`"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-100/70 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200 dark:border-slate-700/80">
              <tr>
                <th class="py-4 px-5">User</th>
                <th class="py-4 px-4">Hotel Property</th>
                <th class="py-4 px-4">Role</th>
                <th class="py-4 px-4">Status</th>
                <th class="py-4 px-4">Joined Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300 font-medium">
              <tr v-if="loading">
                <td colspan="5" class="p-12 text-center text-xs text-slate-400">
                  <RefreshCw class="w-6 h-6 text-indigo-500 animate-spin mx-auto mb-2" />
                  <span>Loading platform staff...</span>
                </td>
              </tr>
              <tr v-else-if="users.length === 0">
                <td colspan="5" class="p-12 text-center text-xs text-slate-400">
                  <Users class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                  <p class="font-bold">No users found.</p>
                </td>
              </tr>
              <tr
                v-else
                v-for="u in users"
                :key="u.id"
                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition"
              >
                <!-- User -->
                <td class="py-4 px-5">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-xs">
                      {{ (u.first_name || 'U').charAt(0) }}{{ (u.last_name || 'S').charAt(0) }}
                    </div>
                    <div>
                      <span class="font-bold text-slate-900 dark:text-white block">
                        {{ u.first_name }} {{ u.last_name }}
                      </span>
                      <span class="text-[11px] font-mono text-slate-400 block">
                        {{ u.email }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Hotel Property -->
                <td class="py-4 px-4">
                  <div v-if="u.hotels && u.hotels.length > 0" class="flex flex-wrap gap-1">
                    <span
                      v-for="h in u.hotels"
                      :key="h.id"
                      class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-[11px] flex items-center gap-1"
                    >
                      <Building2 class="w-3 h-3 text-indigo-500" />
                      <span>{{ h.name }}</span>
                    </span>
                  </div>
                  <span v-else class="text-slate-400 italic">Platform Level / Global</span>
                </td>

                <!-- Role -->
                <td class="py-4 px-4">
                  <span
                    :class="['px-2.5 py-1 rounded-lg border font-black text-[11px] uppercase tracking-wide', getRoleBadgeClass(u.role)]"
                  >
                    {{ u.role }}
                  </span>
                </td>

                <!-- Status -->
                <td class="py-4 px-4">
                  <span
                    class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                    :class="u.is_active ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-slate-500/10 text-slate-500 border-slate-500/20'"
                  >
                    {{ u.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>

                <!-- Joined -->
                <td class="py-4 px-4 text-slate-400 text-[11px]">
                  {{ new Date(u.created_at).toLocaleDateString() }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Enhanced Pagination -->
        <div v-if="lastPage > 1 || totalUsers > perPageOptions[0]" class="p-4 border-t border-slate-200 dark:border-slate-800">
          <!-- Pagination Info and Per Page Selector -->
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
            <div class="flex items-center gap-4">
              <!-- Per Page Selector -->
              <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-600 dark:text-slate-400">Per page:</label>
                <select 
                  v-model="perPage" 
                  class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                  <option v-for="option in perPageOptions" :key="option" :value="option">
                    {{ option }}
                  </option>
                </select>
              </div>
              
              <!-- Results Info -->
              <div class="text-xs text-slate-500 dark:text-slate-400">
                Showing {{ paginationInfo.start }}-{{ paginationInfo.end }} of {{ totalUsers }} users
              </div>
            </div>
            
            <!-- Quick Page Jump -->
            <div v-if="lastPage > 1" class="flex items-center gap-2">
              <label class="text-xs font-bold text-slate-600 dark:text-slate-400">Go to page:</label>
              <select 
                :value="currentPage" 
                @change="goToPage(Number(($event.target as HTMLSelectElement).value))"
                class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option v-for="page in lastPage" :key="page" :value="page">
                  {{ page }}
                </option>
              </select>
            </div>
          </div>

          <!-- Pagination Controls -->
          <div v-if="lastPage > 1" class="flex items-center justify-center gap-1">
            <!-- First Page -->
            <button
              :disabled="currentPage <= 1"
              @click="goToPage(1)"
              class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-slate-100 dark:disabled:hover:bg-slate-800"
              title="First page"
            >
              <ChevronsLeft class="w-4 h-4" />
            </button>
            
            <!-- Previous Page -->
            <button
              :disabled="currentPage <= 1"
              @click="goToPage(currentPage - 1)"
              class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-slate-100 dark:disabled:hover:bg-slate-800"
              title="Previous page"
            >
              <ChevronLeft class="w-4 h-4" />
            </button>

            <!-- Page Numbers -->
            <div class="flex items-center gap-1 mx-2">
              <template v-for="(page, index) in visiblePages" :key="index">
                <button
                  v-if="page !== 'ellipsis'"
                  :class="[
                    'px-3 py-2 rounded-lg text-xs font-bold transition',
                    page === currentPage
                      ? 'bg-indigo-500 text-white shadow-lg'
                      : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300'
                  ]"
                  @click="goToPage(Number(page))"
                >
                  {{ page }}
                </button>
                <span 
                  v-else 
                  class="px-2 py-2 text-slate-400 text-xs"
                >
                  ...
                </span>
              </template>
            </div>

            <!-- Next Page -->
            <button
              :disabled="currentPage >= lastPage"
              @click="goToPage(currentPage + 1)"
              class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-slate-100 dark:disabled:hover:bg-slate-800"
              title="Next page"
            >
              <ChevronRight class="w-4 h-4" />
            </button>
            
            <!-- Last Page -->
            <button
              :disabled="currentPage >= lastPage"
              @click="goToPage(lastPage)"
              class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-slate-100 dark:disabled:hover:bg-slate-800"
              title="Last page"
            >
              <ChevronsRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
