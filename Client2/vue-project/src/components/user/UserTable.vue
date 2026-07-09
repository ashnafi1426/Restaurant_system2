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
  <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
    <table class="w-full">
      <thead class="bg-slate-100">
        <tr>
          <th class="p-4 text-left">Name</th>
          <th class="p-4 text-left">Email</th>
          <th class="p-4 text-left">Phone</th>
          <th class="p-4 text-left">Role</th>
          <th class="p-4 text-left">Status</th>
          <th class="p-4 text-center">Actions</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="user in users" :key="user.id" class="border-t hover:bg-slate-50">
          <td class="p-4 font-medium">{{ user.full_name }}</td>
          <td class="p-4">{{ user.email }}</td>
          <td class="p-4">{{ user.phone }}</td>
          <td class="p-4 capitalize">{{ user.role }}</td>

          <td class="p-4">
            <span
              v-if="user.is_active"
              class="px-3 py-1 rounded-full text-sm bg-green-100 text-green-700"
            >
              Active
            </span>

            <span v-else class="px-3 py-1 rounded-full text-sm bg-red-100 text-red-700">
              Inactive
            </span>
          </td>

          <!-- ACTIONS -->
          <td class="p-4 text-center relative">
            <div class="menu-wrapper inline-block relative">
              <!-- 3 DOT BUTTON -->
              <button
                class="w-8 h-8 flex items-center justify-center rounded hover:bg-slate-200 text-xl"
                @click.stop="toggleMenu(user.id)"
              >
                ⋮
              </button>

              <!-- DROPDOWN -->
              <div
                v-if="activeMenu === user.id"
                class="absolute right-0 mt-2 w-36 bg-white border rounded-md shadow-lg z-50"
              >
                <button
                  class="w-full text-left px-3 py-2 hover:bg-slate-100"
                  @click="editUser(user.id)"
                >
                  Edit
                </button>

                <button
                  class="w-full text-left px-3 py-2 text-red-600 hover:bg-slate-100"
                  @click="deleteUser(user.id)"
                >
                  Delete
                </button>
              </div>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
