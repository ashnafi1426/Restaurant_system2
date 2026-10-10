<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DashboardLayout from '../../../Layouts/DashboardLayout.vue'
import { rbacService } from '../../../services/rbacService'
import type { RbacAuditLogItem } from '../../../types/rbacTypes'
import {
  FileSpreadsheet,
  RefreshCw,
  Search,
  ChevronLeft,
  ChevronRight,
  ShieldAlert,
  Loader2,
} from 'lucide-vue-next'

const logs = ref<RbacAuditLogItem[]>([])
const loading = ref(true)
const errorMessage = ref('')
const currentPage = ref(1)
const lastPage = ref(1)

const fetchLogs = async (page = 1) => {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await rbacService.getAuditLogs(page)
    logs.value = res.data
    currentPage.value = res.pagination.current_page
    lastPage.value = res.pagination.last_page
  } catch (err: any) {
    console.error('[AuditLogView] Fetch logs error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to load security audit logs.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchLogs()
})
</script>

<template>
  <DashboardLayout>
    <template #header>
      <div
        class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-4 border-b border-slate-200 dark:border-slate-800"
      >
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="p-2 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
              <FileSpreadsheet class="w-5 h-5" />
            </span>
            <h1
              class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white"
            >
              Security Audit Logs
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium">
            Complete audit trail tracking all role modifications, permission assignments, and
            security events.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="fetchLogs(currentPage)"
            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition"
            title="Refresh Logs"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>
        </div>
      </div>
    </template>

    <div class="py-6 space-y-6">
      <div
        v-if="errorMessage"
        class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2"
      >
        <ShieldAlert class="w-4 h-4 text-rose-500 flex-shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>

      <div
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm"
      >
        <table class="w-full text-left border-collapse">
          <thead>
            <tr
              class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-xs font-black uppercase text-slate-700 dark:text-slate-300"
            >
              <th class="p-4">Timestamp</th>
              <th class="p-4">Performed By</th>
              <th class="p-4">Action</th>
              <th class="p-4">Target Entity</th>
              <th class="p-4">IP Address</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
            <tr v-if="loading">
              <td colspan="5" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center justify-center gap-3">
                  <Loader2 class="w-8 h-8 text-amber-600 dark:text-amber-400 animate-spin" />
                  <span class="text-xs font-bold text-slate-600 dark:text-slate-400"
                    >Loading audit log entries...</span
                  >
                </div>
              </td>
            </tr>

            <template v-else>
              <tr
                v-for="log in logs"
                :key="log.id"
                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition"
              >
                <td class="p-4 font-mono text-slate-500 dark:text-slate-400">
                  {{ new Date(log.created_at).toLocaleString() }}
                </td>

                <td class="p-4 font-extrabold text-slate-900 dark:text-white">
                  {{ log.user?.full_name || 'System / Service' }}
                </td>

                <td class="p-4">
                  <span
                    class="px-2.5 py-1 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-300 font-mono font-bold"
                  >
                    {{ log.action }}
                  </span>
                </td>

                <td class="p-4 font-mono text-slate-600 dark:text-slate-300">
                  {{ log.target_type ? `${log.target_type} #${log.target_id}` : 'N/A' }}
                </td>

                <td class="p-4 font-mono text-slate-400">
                  {{ log.ip_address || '127.0.0.1' }}
                </td>
              </tr>

              <tr v-if="logs.length === 0">
                <td colspan="5" class="p-8 text-center text-slate-400 font-medium">
                  No audit logs recorded yet.
                </td>
              </tr>
            </template>
          </tbody>
        </table>

        <div
          class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/60"
        >
          <span class="text-xs text-slate-500 font-medium">
            Page {{ currentPage }} of {{ lastPage }}
          </span>

          <div class="flex items-center gap-2">
            <button
              @click="fetchLogs(currentPage - 1)"
              :disabled="currentPage <= 1"
              class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 disabled:opacity-40 hover:bg-slate-200 dark:hover:bg-slate-800 transition"
            >
              <ChevronLeft class="w-4 h-4" />
            </button>
            <button
              @click="fetchLogs(currentPage + 1)"
              :disabled="currentPage >= lastPage"
              class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 disabled:opacity-40 hover:bg-slate-200 dark:hover:bg-slate-800 transition"
            >
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
