<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import type { RoomType } from '../../types/roomType'

defineProps<{
  roomTypes: RoomType[]
}>()

const emit = defineEmits(['view', 'edit', 'delete'])

const openMenuId = ref<number | null>(null)

const toggleMenu = (id: number) => {
  openMenuId.value = openMenuId.value === id ? null : id
}

const closeMenu = () => {
  openMenuId.value = null
}

const handleView = (rt: RoomType) => {
  emit('view', rt)
  closeMenu()
}

const handleEdit = (rt: RoomType) => {
  emit('edit', rt)
  closeMenu()
}

const handleDelete = (rt: RoomType) => {
  emit('delete', rt)
  closeMenu()
}

const handleClickOutside = (e: MouseEvent) => {
  const target = e.target as HTMLElement

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
  <table class="w-full border-collapse border border-gray-200 table-fixed">
    <thead class="bg-gray-100">
      <tr>
        <th class="border p-2 text-left">Name</th>
        <th class="border p-2 text-left">Price</th>
        <th class="border p-2 text-left">Capacity</th>
        <th class="border p-2 text-left">Status</th>
        <th class="border p-2 text-left">Actions</th>
      </tr>
    </thead>

    <tbody>
      <tr v-for="rt in roomTypes" :key="rt.id" class="hover:bg-gray-50">
        <td class="border p-2">{{ rt.name }}</td>

        <td class="border p-2">
          {{ rt.base_price_per_night }}
        </td>

        <td class="border p-2">
          {{ rt.capacity }}
        </td>

        <td class="border p-2">
          {{ rt.is_active ? 'Active' : 'Inactive' }}
        </td>

        <td class="border p-2 relative">
          <div class="action-menu inline-block relative">
            <button
              class="text-xl font-bold px-2 rounded hover:bg-gray-200"
              @click.stop="toggleMenu(rt.id)"
            >
              ⋮
            </button>

            <div
              v-if="openMenuId === rt.id"
              class="absolute right-0 mt-2 w-36 rounded-md border bg-white shadow-lg z-50"
            >
              <button
                class="block w-full px-3 py-2 text-left hover:bg-gray-100"
                @click="handleView(rt)"
              >
                View
              </button>

              <button
                class="block w-full px-3 py-2 text-left hover:bg-gray-100"
                @click="handleEdit(rt)"
              >
                Edit
              </button>

              <button
                class="block w-full px-3 py-2 text-left text-red-600 hover:bg-red-50"
                @click="handleDelete(rt)"
              >
                Delete
              </button>
            </div>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</template>
