<script setup lang="ts">
import { computed, ref } from 'vue'
import { Users, Plus, Clock, TrendingUp, MapPin, Phone } from 'lucide-vue-next'
import { useManagerStore } from '@/stores/managerStore'

const manager = useManagerStore()

const waiterStats = computed(() => {
  const waiters = manager.waiters
  return {
    total: waiters.length,
    active: waiters.filter((w) => w.status === 'active').length,
    onBreak: waiters.filter((w) => w.status === 'break').length,
    inactive: waiters.filter((w) => w.status === 'inactive').length,
  }
})

const showAddModal = ref(false)
const formData = ref({
  name: '',
  phone: '',
  shift: 'morning',
})

const handleAddWaiter = () => {
  if (formData.value.name && formData.value.phone) {
    manager.addWaiter({
      name: formData.value.name,
      phone: formData.value.phone,
      shift: formData.value.shift,
    })

    formData.value = { name: '', phone: '', shift: 'morning' }
    showAddModal.value = false
  }
}
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <!-- HEADER -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-xl font-bold">Waiter Management</h2>
        <p class="text-sm text-slate-500">Staff performance and assignments</p>
      </div>
      <button
        @click="showAddModal = true"
        class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-xl hover:bg-blue-700 transition"
      >
        <Plus class="w-5 h-5" />
        Add Waiter
      </button>
    </div>

    <!-- STATISTICS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-blue-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Total Waiters</p>
        <h3 class="text-3xl font-bold text-blue-700 mt-2">{{ waiterStats.total }}</h3>
      </div>

      <div class="bg-green-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Active</p>
        <h3 class="text-3xl font-bold text-green-700 mt-2">{{ waiterStats.active }}</h3>
      </div>

      <div class="bg-yellow-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">On Break</p>
        <h3 class="text-3xl font-bold text-yellow-700 mt-2">{{ waiterStats.onBreak }}</h3>
      </div>

      <div class="bg-red-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Inactive</p>
        <h3 class="text-3xl font-bold text-red-700 mt-2">{{ waiterStats.inactive }}</h3>
      </div>
    </div>

    <!-- WAITERS TABLE -->
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b text-left text-slate-500">
            <th class="py-3 px-4">Waiter Name</th>
            <th class="py-3 px-4">Shift</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4">Active Orders</th>
            <th class="py-3 px-4">Performance</th>
            <th class="py-3 px-4">Contact</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="waiter in manager.waiters.slice(0, 8)"
            :key="waiter.id"
            class="border-b hover:bg-slate-50 transition"
          >
            <td class="py-4 px-4">
              <div class="flex items-center gap-3">
                <div
                  class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium text-xs"
                >
                  {{ waiter.name.charAt(0) }}
                </div>
                <span class="font-medium">{{ waiter.name }}</span>
              </div>
            </td>

            <td class="py-4 px-4">
              <span
                class="inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-50 text-blue-700 text-xs"
              >
                <Clock class="w-3 h-3" />
                {{ waiter.shift }}
              </span>
            </td>

            <td class="py-4 px-4">
              <span
                :class="[
                  'inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium',
                  waiter.status === 'active' && 'bg-green-100 text-green-700',
                  waiter.status === 'break' && 'bg-yellow-100 text-yellow-700',
                  waiter.status === 'inactive' && 'bg-red-100 text-red-700',
                ]"
              >
                <span
                  :class="[
                    'w-2 h-2 rounded-full',
                    waiter.status === 'active' && 'bg-green-600',
                    waiter.status === 'break' && 'bg-yellow-600',
                    waiter.status === 'inactive' && 'bg-red-600',
                  ]"
                ></span>
                {{ waiter.status.toUpperCase() }}
              </span>
            </td>

            <td class="py-4 px-4">
              <span class="font-medium">{{ waiter.activeOrders || 0 }}</span>
            </td>

            <td class="py-4 px-4">
              <div class="flex items-center gap-1">
                <TrendingUp class="w-4 h-4 text-green-600" />
                <span class="text-green-700 font-medium">{{ waiter.rating || '4.5' }}★</span>
              </div>
            </td>

            <td class="py-4 px-4">
              <div class="flex items-center gap-2 text-slate-600">
                <Phone class="w-4 h-4" />
                <span class="text-xs">{{ waiter.phone }}</span>
              </div>
            </td>
          </tr>

          <tr v-if="manager.waiters.length === 0">
            <td colspan="6" class="py-8 text-center text-slate-500">No waiters assigned yet</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ADD WAITER MODAL -->
    <div
      v-if="showAddModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 rounded-xl"
    >
      <div class="bg-white rounded-xl p-6 w-96 max-w-full mx-4">
        <h3 class="text-lg font-bold mb-4">Add New Waiter</h3>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Name</label>
            <input
              v-model="formData.name"
              type="text"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Waiter name"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Phone</label>
            <input
              v-model="formData.phone"
              type="tel"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Phone number"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Shift</label>
            <select
              v-model="formData.shift"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="morning">Morning</option>
              <option value="afternoon">Afternoon</option>
              <option value="evening">Evening</option>
              <option value="night">Night</option>
            </select>
          </div>
        </div>

        <div class="flex gap-3 mt-6">
          <button
            @click="showAddModal = false"
            class="flex-1 px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 font-medium"
          >
            Cancel
          </button>
          <button
            @click="handleAddWaiter"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
          >
            Add Waiter
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
