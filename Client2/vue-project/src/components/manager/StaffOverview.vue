<script setup lang="ts">
import { computed } from 'vue'
import { Users, UserCheck, Briefcase, Clock } from 'lucide-vue-next'
import { useManagerStore } from '@/stores/managerStore'

const manager = useManagerStore()

const staffStats = computed(() => {
  const totalStaff = manager.staff.length
  const activeStaff = manager.availableStaffCount
  const inactiveStaff = totalStaff - activeStaff
  const departments = new Set(manager.staff.map((s) => s.department))

  return {
    totalStaff,
    activeStaff,
    inactiveStaff,
    departmentCount: departments.size,
  }
})
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <!-- HEADER -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-xl font-bold">Staff Overview</h2>
        <p class="text-sm text-slate-500">Current staff status and availability</p>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-blue-100 flex items-center justify-center">
        <Users class="w-6 h-6 text-blue-600" />
      </div>
    </div>

    <!-- STATISTICS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-blue-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Total Staff</p>
        <h3 class="text-3xl font-bold mt-2">{{ staffStats.totalStaff }}</h3>
      </div>

      <div class="bg-green-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Active Today</p>
        <h3 class="text-3xl font-bold text-green-700 mt-2">{{ staffStats.activeStaff }}</h3>
      </div>

      <div class="bg-red-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Inactive</p>
        <h3 class="text-3xl font-bold text-red-700 mt-2">{{ staffStats.inactiveStaff }}</h3>
      </div>

      <div class="bg-purple-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Departments</p>
        <h3 class="text-3xl font-bold text-purple-700 mt-2">{{ staffStats.departmentCount }}</h3>
      </div>
    </div>

    <!-- STAFF LIST -->
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b text-left text-slate-500">
            <th class="py-3 px-4">Staff Name</th>
            <th class="py-3 px-4">Department</th>
            <th class="py-3 px-4">Position</th>
            <th class="py-3 px-4">Status</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="staff in manager.staff.slice(0, 8)"
            :key="staff.id"
            class="border-b hover:bg-slate-50 transition"
          >
            <td class="py-4 px-4">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                  <UserCheck class="w-4 h-4 text-blue-600" />
                </div>
                <span class="font-medium">{{ staff.name }}</span>
              </div>
            </td>

            <td class="py-4 px-4">
              <div class="flex items-center gap-2 text-slate-600">
                <Briefcase class="w-4 h-4" />
                {{ staff.department }}
              </div>
            </td>

            <td class="py-4 px-4 text-slate-600">{{ staff.position }}</td>

            <td class="py-4 px-4">
              <span
                v-if="staff.status === 'active'"
                class="inline-flex items-center gap-2 bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-medium"
              >
                <span class="w-2 h-2 rounded-full bg-green-600"></span>
                Active
              </span>
              <span
                v-else-if="staff.status === 'on_leave'"
                class="inline-flex items-center gap-2 bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-medium"
              >
                <span class="w-2 h-2 rounded-full bg-yellow-600"></span>
                On Leave
              </span>
              <span
                v-else
                class="inline-flex items-center gap-2 bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-medium"
              >
                <span class="w-2 h-2 rounded-full bg-red-600"></span>
                Inactive
              </span>
            </td>
          </tr>

          <tr v-if="manager.staff.length === 0">
            <td colspan="4" class="py-8 text-center text-slate-500">No staff data available</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- FOOTER -->
    <div v-if="manager.staff.length > 8" class="mt-4 pt-4 border-t text-center">
      <button class="text-blue-600 hover:text-blue-700 font-medium text-sm">
        View All Staff →
      </button>
    </div>
  </section>
</template>
