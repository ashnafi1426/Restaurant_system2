<script setup lang="ts">
import { computed } from 'vue'

import { Plus, Users, Building2, Briefcase, CheckCircle } from 'lucide-vue-next'

import { useManagerStore } from '@/stores/managerStore'

const manager = useManagerStore()

const employees = computed(() => {
  return manager.employees ?? []
})

const statistics = computed(() => {
  return {
    total: employees.value.length,

    active: employees.value.filter((e) => e.status === 'active').length,

    departments: new Set(employees.value.map((e) => e.department)).size,
  }
})
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <!-- HEADER -->

    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-xl font-bold">Employee Management</h2>

        <p class="text-sm text-slate-500">Manage hotel staff</p>
      </div>

      <button
        class="flex items-center gap-2 bg-blue-600 text-white px-5 py-3 rounded-xl hover:bg-blue-700"
      >
        <Plus class="w-5 h-5" />

        Add Employee
      </button>
    </div>

    <!-- STATISTICS -->

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-10">
      <div class="bg-blue-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Total Employees</p>

        <h3 class="text-3xl font-bold">
          {{ statistics.total }}
        </h3>
      </div>

      <div class="bg-green-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Active Employees</p>

        <h3 class="text-3xl font-bold">
          {{ statistics.active }}
        </h3>
      </div>

      <div class="bg-purple-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Departments</p>

        <h3 class="text-3xl font-bold">
          {{ statistics.departments }}
        </h3>
      </div>
    </div>

    <!-- EMPLOYEE TABLE -->

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="border-b text-left text-sm text-slate-500">
            <th class="p-4">Employee</th>

            <th class="p-4">Department</th>

            <th class="p-4">Position</th>

            <th class="p-4">Status</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="employee in employees" :key="employee.id" class="border-b hover:bg-slate-50">
            <td class="p-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                  <Users class="w-5 text-blue-600" />
                </div>

                <div>
                  <p class="font-semibold">
                    {{ employee.name }}
                  </p>

                  <p class="text-xs text-slate-500">
                    {{ employee.phone }}
                  </p>
                </div>
              </div>
            </td>

            <td class="p-4">
              <div class="flex items-center gap-2">
                <Building2 class="w-4" />

                {{ employee.department }}
              </div>
            </td>

            <td class="p-4">
              <div class="flex items-center gap-2">
                <Briefcase class="w-4" />

                {{ employee.position }}
              </div>
            </td>

            <td class="p-4">
              <span
                v-if="employee.status === 'active'"
                class="flex items-center gap-2 text-green-700 bg-green-100 px-3 py-1 rounded-full w-fit"
              >
                <CheckCircle class="w-4" />

                Active
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
