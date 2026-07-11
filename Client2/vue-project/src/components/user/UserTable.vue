<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import type { User } from '../../type/user'
import { useRouter } from 'vue-router'
import { useUserStore } from '../../stores/user'

defineProps<{
  users: User[]
}>()
const router = useRouter()
const userStore = useUserStore()

const activeMenu = ref<string | null>(null)

const toggleMenu = (id: string) => {
  activeMenu.value = activeMenu.value === id ? null : id
}

const closeMenu = () => {
  activeMenu.value = null
}

const editUser = (id: string) => {
  router.push(`/users/${id}/edit`)
  closeMenu()
}

const deleteUser = async (id: string) => {
  const confirmed = confirm('Are you sure you want to delete this user?')
  if (!confirmed) return

  await userStore.deleteUser(id)
  closeMenu()
}

const handleClickOutside = (e: MouseEvent) => {
  const target = e.target as HTMLElement
  if (!target.closest('.menu-wrapper')) {
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
  <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <!-- Desktop Table View (md and up) -->
    <div class="hidden md:block overflow-x-auto">
      <table class="w-full">
        <thead class="bg-slate-50/80 border-b border-slate-200">
          <tr>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
              Name
            </th>
            <th class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
              Email
            </th>
            <th class="hidden md:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
              Phone
            </th>
            <th class="hidden lg:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
              Role
            </th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
              Status
            </th>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-600">
              Actions
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200">
          <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/60 transition duration-150">
            <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 font-medium text-xs sm:text-sm md:text-base text-slate-900">
              {{ user.full_name }}
            </td>
            <td class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-xs sm:text-sm text-slate-600">
              {{ user.email }}
            </td>
            <td class="hidden md:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-xs sm:text-sm text-slate-600">
              {{ user.phone }}
            </td>
            <td class="hidden lg:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-xs sm:text-sm capitalize text-slate-600">
              {{ user.role }}
            </td>

            <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4">
              <span
                v-if="user.is_active"
                class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs sm:text-xs font-medium bg-green-50 text-green-700 border border-green-200"
              >
                <span class="h-1.5 w-1.5 sm:h-2 sm:w-2 rounded-full bg-green-500 mr-1 sm:mr-1.5"></span>
                Active
              </span>

              <span v-else class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs sm:text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                <span class="h-1.5 w-1.5 sm:h-2 sm:w-2 rounded-full bg-red-500 mr-1 sm:mr-1.5"></span>
                Inactive
              </span>
            </td>

            <!-- ACTIONS -->
            <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-center relative">
              <div class="menu-wrapper inline-block relative">
                <!-- 3 DOT BUTTON -->
                <button
                  class="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-lg hover:bg-slate-100 transition duration-150 text-base sm:text-lg text-slate-600 hover:text-slate-900"
                  @click.stop="toggleMenu(user.id)"
                  :aria-label="`Options for ${user.full_name}`"
                >
                  ⋮
                </button>

                <!-- DROPDOWN -->
                <div
                  v-if="activeMenu === user.id"
                  class="absolute right-0 mt-2 w-32 sm:w-36 bg-white border border-slate-200 rounded-lg shadow-lg z-50"
                >
                  <button
                    class="w-full text-left px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm hover:bg-slate-50 text-slate-700 hover:text-slate-900 transition duration-150"
                    @click="editUser(user.id)"
                  >
                    ✏️ Edit
                  </button>

                  <button
                    class="w-full text-left px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition duration-150 border-t border-slate-100"
                    @click="deleteUser(user.id)"
                  >
                    🗑️ Delete
                  </button>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile Card View (below md) -->
    <div class="md:hidden">
      <div v-if="users.length === 0" class="px-4 py-8 text-center text-slate-500 text-sm">
        No users found
      </div>

      <div v-for="user in users" :key="user.id" class="border-b border-slate-200 last:border-b-0 p-4 sm:p-5 hover:bg-slate-50/50 transition duration-150">
        <!-- Name and Status -->
        <div class="flex items-start justify-between gap-2 mb-3">
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-sm text-slate-900 truncate">{{ user.full_name }}</h3>
            <p class="text-xs text-slate-500 mt-1 truncate">{{ user.email }}</p>
          </div>
          <span
            v-if="user.is_active"
            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200 flex-shrink-0 ml-2"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-green-500 mr-1"></span>
            Active
          </span>

          <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200 flex-shrink-0 ml-2">
            <span class="h-1.5 w-1.5 rounded-full bg-red-500 mr-1"></span>
            Inactive
          </span>
        </div>

        <!-- Additional Info -->
        <div class="grid grid-cols-2 gap-2 mb-3 text-xs">
          <div>
            <p class="text-slate-500">Phone</p>
            <p class="font-medium text-slate-900">{{ user.phone || 'N/A' }}</p>
          </div>
          <div>
            <p class="text-slate-500">Role</p>
            <p class="font-medium text-slate-900 capitalize">{{ user.role }}</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-2 pt-3 border-t border-slate-100">
          <button
            @click="editUser(user.id)"
            class="flex-1 px-3 py-2 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition duration-150"
          >
            ✏️ Edit
          </button>
          <button
            @click="deleteUser(user.id)"
            class="flex-1 px-3 py-2 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition duration-150"
          >
            🗑️ Delete
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
