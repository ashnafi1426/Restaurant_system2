<script setup lang="ts">
import { computed } from 'vue'

import { Plus, Clock, CheckCircle, AlertTriangle, User } from 'lucide-vue-next'

import { useManagerStore } from '@/stores/managerStore'

const manager = useManagerStore()

/*
|--------------------------------------------------------------------------
| Tasks
|--------------------------------------------------------------------------
*/

const tasks = computed(() => {
  return manager.tasks ?? []
})

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

const statistics = computed(() => {
  return {
    total: tasks.value.length,

    pending: tasks.value.filter((t) => t.status === 'pending').length,

    progress: tasks.value.filter((t) => t.status === 'in_progress').length,

    completed: tasks.value.filter((t) => t.status === 'completed').length,
  }
})
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <!-- Header -->

    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-xl font-bold">Task Management</h2>

        <p class="text-sm text-slate-500">Manage hotel operations</p>
      </div>

      <button
        class="flex items-center gap-2 bg-blue-600 text-white px-5 py-3 rounded-xl hover:bg-blue-700"
      >
        <Plus class="w-5 h-5" />

        Create Task
      </button>
    </div>

    <!-- Statistics -->

    <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-10">
      <div class="bg-slate-50 p-5 rounded-2xl">
        <p class="text-sm text-slate-500">Total Tasks</p>

        <h3 class="text-3xl font-bold">
          {{ statistics.total }}
        </h3>
      </div>

      <div class="bg-yellow-50 p-5 rounded-2xl">
        <p class="text-sm text-slate-500">Pending</p>

        <h3 class="text-3xl font-bold">
          {{ statistics.pending }}
        </h3>
      </div>

      <div class="bg-blue-50 p-5 rounded-2xl">
        <p class="text-sm text-slate-500">In Progress</p>

        <h3 class="text-3xl font-bold">
          {{ statistics.progress }}
        </h3>
      </div>

      <div class="bg-green-50 p-5 rounded-2xl">
        <p class="text-sm text-slate-500">Completed</p>

        <h3 class="text-3xl font-bold">
          {{ statistics.completed }}
        </h3>
      </div>
    </div>

    <!-- Task List -->

    <div class="space-y-5">
      <div
        v-for="task in tasks"
        :key="task.id"
        class="border rounded-2xl p-5 flex justify-between items-center"
      >
        <div>
          <h3 class="font-bold">
            {{ task.title }}
          </h3>

          <p class="text-sm text-slate-500 mt-1">
            {{ task.department }}
          </p>

          <div class="flex gap-4 mt-3 text-sm">
            <span class="flex items-center gap-1">
              <User class="w-4" />

              {{ task.employee }}
            </span>

            <span class="flex items-center gap-1">
              <Clock class="w-4" />

              {{ task.status }}
            </span>
          </div>
        </div>

        <!-- Status -->

        <div>
          <span
            v-if="task.status === 'completed'"
            class="flex items-center gap-2 bg-green-100 text-green-700 px-4 py-2 rounded-full"
          >
            <CheckCircle class="w-4" />

            Completed
          </span>

          <span
            v-else-if="task.priority === 'high'"
            class="flex items-center gap-2 bg-red-100 text-red-700 px-4 py-2 rounded-full"
          >
            <AlertTriangle class="w-4" />

            High Priority
          </span>

          <span v-else class="bg-yellow-100 text-yellow-700 px-4 py-2 rounded-full"> Pending </span>
        </div>
      </div>
    </div>
  </section>
</template>
