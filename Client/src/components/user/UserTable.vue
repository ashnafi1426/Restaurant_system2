<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import type { User } from '@/types/user'
import { useLanguageStore } from '@/stores/language'
import {
  Search,
  Filter,
  X,
  RefreshCw,
  Maximize2,
  Minimize2,
  Plus,
  RotateCcw,
  MoreVertical,
  Eye,
  Edit,
  Trash2,
  ChevronLeft,
  ChevronRight,
  Shield,
  Loader2,
} from 'lucide-vue-next'

const props = defineProps<{
  users: User[]
  loading?: boolean
}>()

const languageStore = useLanguageStore()

const emit = defineEmits<{
  (e: 'view', user: User): void
  (e: 'edit', user: User): void
  (e: 'delete', user: User): void
  (e: 'refresh'): void
  (e: 'create'): void
}>()

const activeMenu = ref<string | null>(null)
const isFilterOpen = ref(false)
const isFullscreen = ref(false)

const search = ref('')
const roleFilter = ref('')
const statusFilter = ref('')

const currentPage = ref(1)
const perPage = ref(10)

const filteredList = computed(() => {
  let list = props.users || []

  if (search.value.trim()) {
    const q = search.value.toLowerCase().trim()
    list = list.filter((u) => {
      const name = `${u.first_name || ''} ${u.last_name || ''}`.toLowerCase()
      const email = (u.email || '').toLowerCase()
      const phone = (u.phone || '').toLowerCase()
      const role = (u.role || '').toLowerCase()
      return name.includes(q) || email.includes(q) || phone.includes(q) || role.includes(q)
    })
  }

  if (roleFilter.value) {
    list = list.filter((u) => (u.role || '').toLowerCase() === roleFilter.value.toLowerCase())
  }

  if (statusFilter.value) {
    const isActive = statusFilter.value === 'active'
    list = list.filter((u) => u.is_active === isActive)
  }

  return list
})

const total = computed(() => filteredList.value.length)
const lastPage = computed(() => Math.ceil(total.value / perPage.value) || 1)

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredList.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() =>
  total.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1,
)
const showingTo = computed(() => Math.min(currentPage.value * perPage.value, total.value))

const paginationPages = computed(() => {
  const pages: number[] = []
  const max = lastPage.value
  const cur = currentPage.value

  for (let i = Math.max(1, cur - 2); i <= Math.min(max, cur + 2); i++) {
    pages.push(i)
  }
  return pages
})

// Reset to page 1 on filter or perPage changes
watch([search, roleFilter, statusFilter, perPage], () => {
  currentPage.value = 1
})

const goToPage = (p: number) => {
  if (p >= 1 && p <= lastPage.value) {
    currentPage.value = p
  }
}

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--
}

const nextPage = () => {
  if (currentPage.value < lastPage.value) currentPage.value++
}

const resetFilters = () => {
  search.value = ''
  roleFilter.value = ''
  statusFilter.value = ''
  currentPage.value = 1
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const toggleMenu = (id: string, e: MouseEvent) => {
  e.stopPropagation()
  activeMenu.value = activeMenu.value === id ? null : id
}

const closeMenu = () => {
  activeMenu.value = null
}

const handleAction = (action: 'view' | 'edit' | 'delete', user: User) => {
  if (action === 'view') emit('view', user)
  else if (action === 'edit') emit('edit', user)
  else if (action === 'delete') emit('delete', user)
  closeMenu()
}

const getUserInitial = (user: User) => (user.first_name?.[0] || 'U').toUpperCase()

const getRoleBadgeClass = (role?: string): string => {
  const r = (role || '').toLowerCase()
  if (r.includes('admin'))
    return 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20'
  if (r.includes('manager'))
    return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'
  if (r.includes('reception'))
    return 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20'
  if (r.includes('waiter') || r.includes('staff'))
    return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'
  if (r.includes('chef') || r.includes('kitchen'))
    return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
  return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20'
}

onMounted(() => {
  window.addEventListener('click', closeMenu)
})

onBeforeUnmount(() => {
  window.removeEventListener('click', closeMenu)
})
</script>

<template>
  <div
    class="space-y-3 font-sans w-full"
    :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
  >
    <div
      class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-3 sm:p-4 shadow-xs transition-all"
    >
      <div class="flex flex-1 items-center gap-2.5 min-w-[280px] max-w-2xl">
        <div class="relative flex-1">
          <Search
            class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500"
          />
          <input
            v-model="search"
            type="text"
            :placeholder="
              languageStore.t(
                'search_users_placeholder',
                'Search staff by name, email, phone, role...',
              )
            "
            class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-10 pr-4 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
          />
        </div>

        <button
          type="button"
          @click="toggleFilter"
          class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer flex-shrink-0"
          :class="[
            isFilterOpen
              ? 'bg-blue-600/10 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-blue-500/40'
              : 'border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356]',
          ]"
        >
          <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
          <span>{{
            isFilterOpen
              ? languageStore.t('hide_filter', 'Hide Filter')
              : languageStore.t('filter', 'Filter')
          }}</span>
        </button>
      </div>

      <div class="flex items-center gap-2 sm:gap-2.5">
        <button
          type="button"
          @click="emit('refresh')"
          :disabled="loading"
          :title="languageStore.t('refresh', 'Refresh')"
          class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
        >
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        </button>

        <button
          type="button"
          @click="toggleFullscreen"
          :title="languageStore.t('fullscreen', 'Toggle Fullscreen')"
          class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
        >
          <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
        </button>

        <button
          type="button"
          @click="emit('create')"
          class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 dark:bg-[#0066FF] dark:hover:bg-[#0055DD] px-3.5 sm:px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm shadow-blue-600/30 transition active:scale-98 cursor-pointer flex-shrink-0"
        >
          <Plus class="w-4 h-4" />
          <span>{{ languageStore.t('create_user', 'Create User') }}</span>
        </button>
      </div>
    </div>

    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform -translate-y-2 opacity-0 scale-98"
      enter-to-class="transform translate-y-0 opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="transform translate-y-0 opacity-100 scale-100"
      leave-to-class="transform -translate-y-2 opacity-0 scale-98"
    >
      <div
        v-if="isFilterOpen"
        class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-4 sm:p-5 shadow-sm space-y-4"
      >
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
              {{ languageStore.t('Role', 'Staff Role') }}
            </label>
            <select
              v-model="roleFilter"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
            >
              <option value="">{{ languageStore.t('All', 'All Roles') }}</option>
              <option value="admin">{{ languageStore.t('admin', 'Administrator') }}</option>
              <option value="manager">{{ languageStore.t('manager', 'Manager') }}</option>
              <option value="receptionist">
                {{ languageStore.t('receptionist', 'Receptionist') }}
              </option>
              <option value="waiter">{{ languageStore.t('waiter', 'Waiter') }}</option>
              <option value="chef">{{ languageStore.t('chef', 'Chef / Kitchen') }}</option>
              <option value="staff">{{ languageStore.t('Staff', 'Staff') }}</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
              {{ languageStore.t('Status', 'Account Status') }}
            </label>
            <select
              v-model="statusFilter"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
            >
              <option value="">{{ languageStore.t('all_statuses', 'All Statuses') }}</option>
              <option value="active">
                {{ languageStore.t('active_accounts', 'Active Accounts') }}
              </option>
              <option value="inactive">
                {{ languageStore.t('inactive_accounts', 'Inactive Accounts') }}
              </option>
            </select>
          </div>

          <div class="flex items-end">
            <button
              type="button"
              @click="resetFilters"
              class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer"
            >
              <RotateCcw class="w-3.5 h-3.5" />
              <span>{{ languageStore.t('reset_filters', 'Reset Filters') }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <div
      class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full min-h-[220px]"
    >
      <div
        v-if="loading"
        class="py-20 px-4 text-center flex flex-col items-center justify-center space-y-3"
      >
        <div
          class="p-3.5 rounded-2xl bg-blue-50 dark:bg-blue-950/60 border border-blue-100 dark:border-blue-900/50 shadow-xs"
        >
          <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
        </div>
        <div>
          <p class="text-sm font-bold text-slate-800 dark:text-slate-200">
            {{ languageStore.t('Loading...', 'Loading staff accounts...') }}
          </p>
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
            {{ languageStore.t('please_wait', 'Please wait while fetching user data') }}
          </p>
        </div>
      </div>

      <template v-else>
        <div class="hidden md:block overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead
              class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]"
            >
              <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
                <th class="px-3 py-3 pl-5 whitespace-nowrap">
                  {{ languageStore.t('full_name', 'Staff Name') }}
                </th>
                <th class="px-3 py-3 whitespace-nowrap">
                  {{ languageStore.t('email_address', 'Email') }}
                </th>
                <th class="px-3 py-3 whitespace-nowrap">
                  {{ languageStore.t('phone_number', 'Phone') }}
                </th>
                <th class="px-3 py-3 whitespace-nowrap">{{ languageStore.t('Role', 'Role') }}</th>
                <th class="px-3 py-3 text-center whitespace-nowrap">
                  {{ languageStore.t('Status', 'Status') }}
                </th>
                <th class="px-3 py-3 text-right pr-5 whitespace-nowrap">
                  {{ languageStore.t('Actions', 'Actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
              <tr
                v-for="user in paginatedUsers"
                :key="user.id"
                class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
              >
                <td class="px-3 py-3 pl-5 whitespace-nowrap">
                  <div class="flex items-center gap-2 max-w-[180px]">
                    <div
                      class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 font-bold text-xs flex items-center justify-center flex-shrink-0"
                    >
                      {{ getUserInitial(user) }}
                    </div>
                    <div class="min-w-0 flex-1">
                      <div
                        class="font-bold text-slate-900 dark:text-white truncate text-xs sm:text-sm"
                      >
                        {{ user.first_name }} {{ user.last_name }}
                      </div>
                    </div>
                  </div>
                </td>

                <td
                  class="px-3 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300 font-medium"
                >
                  {{ user.email }}
                </td>

                <td class="px-3 py-3 whitespace-nowrap text-slate-600 dark:text-slate-400">
                  {{ user.phone || '-' }}
                </td>

                <td class="px-3 py-3 whitespace-nowrap">
                  <span
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold border uppercase tracking-wider"
                    :class="getRoleBadgeClass(user.role)"
                  >
                    <Shield class="w-3 h-3" />
                    {{ languageStore.t(user.role, user.role || 'Staff') }}
                  </span>
                </td>

                <td class="px-3 py-3 text-center whitespace-nowrap">
                  <span
                    v-if="user.is_active"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    {{ languageStore.t('Active', 'Active') }}
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    {{ languageStore.t('Inactive', 'Inactive') }}
                  </span>
                </td>

                <td class="px-3 py-3 text-right whitespace-nowrap pr-5 relative" @click.stop>
                  <div class="relative inline-block text-left">
                    <button
                      @click="toggleMenu(String(user.id), $event)"
                      class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                      :class="{
                        'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white':
                          activeMenu === String(user.id),
                      }"
                      :title="languageStore.t('Actions', 'Actions')"
                    >
                      <MoreVertical class="w-4 h-4" />
                    </button>

                    <transition
                      enter-active-class="transition duration-100 ease-out"
                      leave-active-class="transition duration-75 ease-in"
                      enter-from-class="opacity-0 scale-95 -translate-y-2"
                      enter-to-class="opacity-100 scale-100 translate-y-0"
                      leave-from-class="opacity-100 scale-100 translate-y-0"
                      leave-to-class="opacity-0 scale-95 -translate-y-2"
                    >
                      <div
                        v-if="activeMenu === String(user.id)"
                        class="absolute right-0 top-8 z-50 w-36 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-1.5 space-y-1 text-left"
                      >
                        <button
                          @click="handleAction('view', user)"
                          class="flex w-full items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                        >
                          <Eye class="w-3.5 h-3.5 text-blue-500" />
                          <span>{{ languageStore.t('View', 'View') }}</span>
                        </button>

                        <button
                          @click="handleAction('edit', user)"
                          class="flex w-full items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-500/10 transition cursor-pointer"
                        >
                          <Edit class="w-3.5 h-3.5 text-amber-500" />
                          <span>{{ languageStore.t('Edit', 'Edit') }}</span>
                        </button>

                        <div class="border-t border-slate-100 dark:border-slate-800 my-0.5"></div>

                        <button
                          @click="handleAction('delete', user)"
                          class="flex w-full items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition cursor-pointer"
                        >
                          <Trash2 class="w-3.5 h-3.5 text-rose-500" />
                          <span>{{ languageStore.t('Delete', 'Delete') }}</span>
                        </button>
                      </div>
                    </transition>
                  </div>
                </td>
              </tr>

              <tr v-if="paginatedUsers.length === 0">
                <td
                  colspan="6"
                  class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold"
                >
                  {{
                    languageStore.t(
                      'no_users_match',
                      'No users match your current search or filter criteria.',
                    )
                  }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
          <div
            v-if="paginatedUsers.length === 0"
            class="p-8 text-center text-slate-500 dark:text-slate-400 text-xs font-bold"
          >
            {{
              languageStore.t(
                'no_users_match',
                'No users match your current search or filter criteria.',
              )
            }}
          </div>
          <div
            v-else
            v-for="user in paginatedUsers"
            :key="user.id"
            class="p-4 space-y-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
          >
            <div class="flex items-center justify-between">
              <span class="font-bold text-slate-900 dark:text-white text-sm">
                {{ user.first_name }} {{ user.last_name }}
              </span>
              <span
                class="px-2 py-0.5 rounded-lg text-[10px] font-bold border uppercase"
                :class="getRoleBadgeClass(user.role)"
              >
                {{ languageStore.t(user.role, user.role) }}
              </span>
            </div>

            <div class="text-xs text-slate-500 dark:text-slate-400">
              {{ user.email }}
            </div>

            <div class="flex gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
              <button
                @click="handleAction('view', user)"
                class="flex-1 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 rounded-lg cursor-pointer"
              >
                {{ languageStore.t('View', 'View') }}
              </button>
              <button
                @click="handleAction('edit', user)"
                class="flex-1 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 rounded-lg cursor-pointer"
              >
                {{ languageStore.t('Edit', 'Edit') }}
              </button>
              <button
                @click="handleAction('delete', user)"
                class="flex-1 py-1.5 text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 rounded-lg cursor-pointer"
              >
                {{ languageStore.t('Delete', 'Delete') }}
              </button>
            </div>
          </div>
        </div>

        <div
          v-if="total > 0"
          class="border-t border-slate-200 dark:border-slate-800/80 px-4 sm:px-6 py-3 sm:py-4 bg-slate-50/50 dark:bg-[#0c182c] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
        >
          <div class="text-slate-500 dark:text-slate-400 font-medium">
            {{ languageStore.t('Showing', 'Showing') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ showingFrom }}</span>
            {{ languageStore.t('to', 'to') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ showingTo }}</span>
            {{ languageStore.t('of', 'of') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ total }}</span>
            {{ languageStore.t('records', 'records') }}
          </div>

          <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-1.5">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{
                languageStore.t('per_page', 'Per page:')
              }}</span>
              <select
                v-model.number="perPage"
                class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#13233c] text-slate-900 dark:text-white px-2 py-1 text-xs outline-none cursor-pointer"
              >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
            </div>

            <div class="flex items-center gap-1">
              <button
                @click="prevPage"
                :disabled="currentPage === 1"
                class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
              >
                <ChevronLeft class="w-4 h-4" />
              </button>

              <button
                v-for="page in paginationPages"
                :key="page"
                @click="goToPage(page)"
                class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer"
                :class="[
                  currentPage === page
                    ? 'bg-blue-600 text-white'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
                ]"
              >
                {{ page }}
              </button>

              <button
                @click="nextPage"
                :disabled="currentPage === lastPage"
                class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
              >
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
