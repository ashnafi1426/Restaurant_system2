<script setup lang="ts">
import { computed } from 'vue'

import { Plus, Building2, Users, CheckCircle } from 'lucide-vue-next'

import { useManagerStore } from '@/stores/managerStore'

const manager = useManagerStore()

/*
|--------------------------------------------------------------------------
| Departments
|--------------------------------------------------------------------------
*/

const departments = computed(() => {
  return manager.departments ?? []
})

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

const statistics = computed(() => {
  return {
    total: departments.value.length,

    active: departments.value.filter((d) => d.status === 'active').length,

    employees: departments.value.reduce((sum, d) => sum + d.employee_count, 0),
  }
})
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <!-- HEADER -->

    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-xl font-bold">Department Management</h2>

        <p class="text-sm text-slate-500">Manage hotel departments</p>
      </div>

      <button
        class="flex items-center gap-2 bg-blue-600 text-white px-5 py-3 rounded-xl hover:bg-blue-700"
      >
        <Plus class="w-5 h-5" />

        Add Department
      </button>
    </div>

    <!-- STAT CARDS -->

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-10">
      <div class="bg-blue-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Total Departments</p>

        <h3 class="text-3xl font-bold">
          {{ statistics.total }}
        </h3>
      </div>

      <div class="bg-green-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Active Departments</p>

        <h3 class="text-3xl font-bold">
          {{ statistics.active }}
        </h3>
      </div>

      <div class="bg-purple-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Total Employees</p>

        <h3 class="text-3xl font-bold">
          {{ statistics.employees }}
        </h3>
      </div>
    </div>

    <!-- DEPARTMENT LIST -->

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <div
        v-for="department in departments"
        :key="department.id"
        class="border rounded-2xl p-5 hover:shadow-md transition"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
              <Building2 class="w-6 text-blue-600" />
            </div>

            <div>
              <h3 class="font-bold">
                {{ department.name }}
              </h3>

              <p class="text-sm text-slate-500">
                {{ department.description }}
              </p>
            </div>
          </div>

          <CheckCircle v-if="department.status === 'active'" class="text-green-600 w-5" />
        </div>

        <div class="mt-5 flex justify-between text-sm">
          <span class="flex items-center gap-2">
            <Users class="w-4" />

            {{ department.employee_count }}
            Employees
          </span>

          <span class="text-slate-500">
            {{ department.status }}
          </span>
        </div>
      </div>
    </div>
  </section>
</template>
