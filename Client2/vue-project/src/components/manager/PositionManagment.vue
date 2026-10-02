<script setup lang="ts">
import { computed } from 'vue'
import { Plus, BriefcaseBusiness, Building2, Users, CheckCircle } from 'lucide-vue-next'
import { useManagerStore } from '@/stores/managerStore'

interface PositionItem {
  id: string | number
  name: string
  department: string
  status: string
  employee_count: number
}

const manager = useManagerStore()

const positions = computed<PositionItem[]>(() => {
  const staffList = manager.staff ?? []
  if (!staffList.length) return []

  const posMap = new Map<string, { department: string; total: number; active: number }>()
  for (const s of staffList) {
    const posName = s.position || 'General Staff'
    const cur = posMap.get(posName) || { department: s.department || 'General', total: 0, active: 0 }
    cur.total += 1
    if (s.status === 'active') cur.active += 1
    posMap.set(posName, cur)
  }

  return Array.from(posMap.entries()).map(([name, data], idx) => ({
    id: idx + 1,
    name,
    department: data.department,
    status: data.active > 0 ? 'active' : 'inactive',
    employee_count: data.total,
  }))
})

const statistics = computed(() => {
  return {
    total: positions.value.length,
    active: positions.value.filter((p) => p.status === 'active').length,
    employees: positions.value.reduce((sum, p) => sum + (p.employee_count || 0), 0),
  }
})
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-xl font-bold">Position Management</h2>
        <p class="text-sm text-slate-500">Manage employee job positions</p>
      </div>

      <button
        class="flex items-center gap-2 bg-blue-600 text-white px-5 py-3 rounded-xl hover:bg-blue-700"
      >
        <Plus class="w-5 h-5" />
        Add Position
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-10">
      <div class="bg-blue-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Total Positions</p>
        <h3 class="text-3xl font-bold">
          {{ statistics.total }}
        </h3>
      </div>

      <div class="bg-green-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Active Positions</p>
        <h3 class="text-3xl font-bold">
          {{ statistics.active }}
        </h3>
      </div>

      <div class="bg-purple-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Assigned Employees</p>
        <h3 class="text-3xl font-bold">
          {{ statistics.employees }}
        </h3>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <div
        v-for="position in positions"
        :key="position.id"
        class="border rounded-2xl p-5 hover:shadow-md transition"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">
              <BriefcaseBusiness class="w-6 text-blue-600" />
            </div>

            <div>
              <h3 class="font-bold">
                {{ position.name }}
              </h3>
              <p class="text-sm text-slate-500">
                {{ position.department }}
              </p>
            </div>
          </div>

          <CheckCircle v-if="position.status === 'active'" class="w-5 text-green-600" />
        </div>

        <div class="mt-5 space-y-2 text-sm">
          <div class="flex items-center gap-2">
            <Building2 class="w-4" />
            Department:
            <strong>
              {{ position.department }}
            </strong>
          </div>

          <div class="flex items-center gap-2">
            <Users class="w-4" />
            Employees:
            <strong>
              {{ position.employee_count }}
            </strong>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
