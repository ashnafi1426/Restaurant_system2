<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
        @click.self="$emit('close')"
      >
        <div
          class="bg-white dark:bg-[#0b1527] border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-xl w-full max-h-[90vh] flex flex-col overflow-hidden text-slate-900 dark:text-white"
        >
          <!-- Header -->
          <div
            class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50"
          >
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Layers class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                Restaurant Sections
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Manage dining areas and waiter zones
              </p>
            </div>
            <button
              type="button"
              @click="$emit('close')"
              class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Body -->
          <div class="p-6 overflow-y-auto space-y-6 flex-1">
            <!-- Add / Edit Form -->
            <div
              class="p-4 rounded-xl border border-blue-100 dark:border-blue-900/40 bg-blue-50/30 dark:bg-blue-950/20 space-y-3"
            >
              <h3
                class="text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400"
              >
                {{ editingSectionId ? 'Edit Section' : 'Add New Section' }}
              </h3>

              <div class="space-y-3">
                <div>
                  <label
                    class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                  >
                    Section Name <span class="text-rose-500">*</span>
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    placeholder="e.g., Main Dining, Terrace, Rooftop Lounge"
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-xs text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <p v-if="validationError" class="text-rose-500 text-[11px] mt-1">
                    {{ validationError }}
                  </p>
                </div>

                <div>
                  <label
                    class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                  >
                    Description (Optional)
                  </label>
                  <input
                    v-model="form.description"
                    type="text"
                    placeholder="e.g., Ground floor indoor seating near bar"
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-xs text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                <div class="flex items-center justify-between pt-1">
                  <label
                    class="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-700 dark:text-slate-300"
                  >
                    <input
                      v-model="form.is_active"
                      type="checkbox"
                      class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    />
                    <span>Active Section</span>
                  </label>

                  <div class="flex items-center gap-2">
                    <button
                      v-if="editingSectionId"
                      type="button"
                      @click="cancelEdit"
                      class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                      Cancel
                    </button>
                    <button
                      type="button"
                      @click="handleSave"
                      :disabled="saving || !form.name.trim()"
                      class="px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition disabled:opacity-50 flex items-center gap-1.5 shadow-sm"
                    >
                      <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
                      <span>{{ editingSectionId ? 'Update Section' : 'Create Section' }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Existing Sections List -->
            <div>
              <div class="flex items-center justify-between mb-2.5">
                <h3
                  class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                >
                  Existing Sections ({{ sectionStore.sections.length }})
                </h3>
              </div>

              <div
                v-if="sectionStore.loading && !sectionStore.sections.length"
                class="text-center py-8"
              >
                <Loader2 class="w-6 h-6 animate-spin text-blue-600 mx-auto" />
                <p class="text-xs text-slate-500 mt-2">Loading sections...</p>
              </div>

              <div
                v-else-if="!sectionStore.sections.length"
                class="text-center py-6 text-slate-400 text-xs"
              >
                No sections defined yet. Create your first section above.
              </div>

              <div
                v-else
                class="divide-y divide-slate-100 dark:divide-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden"
              >
                <div
                  v-for="sec in sectionStore.sections"
                  :key="sec.id"
                  class="p-3.5 flex items-center justify-between hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition"
                >
                  <div class="min-w-0 pr-3">
                    <div class="flex items-center gap-2">
                      <span class="font-bold text-xs text-slate-900 dark:text-white truncate">
                        {{ sec.name }}
                      </span>
                      <span
                        class="px-2 py-0.2 rounded-full text-[10px] font-bold"
                        :class="
                          sec.is_active
                            ? 'bg-emerald-500/10 text-emerald-600'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-500'
                        "
                      >
                        {{ sec.is_active ? 'Active' : 'Inactive' }}
                      </span>
                      <span
                        v-if="sec.tables_count !== undefined"
                        class="px-1.5 py-0.2 rounded text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-medium"
                      >
                        {{ sec.tables_count }} tables
                      </span>
                    </div>
                    <p
                      v-if="sec.description"
                      class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate"
                    >
                      {{ sec.description }}
                    </p>
                  </div>

                  <div class="flex items-center gap-1">
                    <button
                      type="button"
                      @click="startEdit(sec)"
                      title="Edit Section"
                      class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition"
                    >
                      <Edit2 class="w-3.5 h-3.5" />
                    </button>
                    <button
                      type="button"
                      @click="handleDelete(sec)"
                      title="Delete Section"
                      class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div
            class="px-6 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end"
          >
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
            >
              Done
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRestaurantSectionStore } from '@/stores/restaurantSectionStore'
import type { RestaurantSection } from '@/types/restaurantSection'
import { Layers, X, Edit2, Trash2, Loader2 } from 'lucide-vue-next'

const emit = defineEmits<{
  close: []
  updated: []
}>()

const sectionStore = useRestaurantSectionStore()
const saving = ref(false)
const validationError = ref<string | null>(null)
const editingSectionId = ref<string | null>(null)

const form = reactive({
  name: '',
  description: '',
  is_active: true,
})

onMounted(async () => {
  await sectionStore.fetchSections()
})

const startEdit = (sec: RestaurantSection) => {
  editingSectionId.value = sec.id
  form.name = sec.name
  form.description = sec.description || ''
  form.is_active = sec.is_active
  validationError.value = null
}

const cancelEdit = () => {
  editingSectionId.value = null
  form.name = ''
  form.description = ''
  form.is_active = true
  validationError.value = null
}

const handleSave = async () => {
  if (!form.name.trim()) {
    validationError.value = 'Section name is required.'
    return
  }

  saving.value = true
  validationError.value = null

  try {
    if (editingSectionId.value) {
      await sectionStore.updateSection(editingSectionId.value, {
        name: form.name.trim(),
        description: form.description.trim() || null,
        is_active: form.is_active,
      })
    } else {
      await sectionStore.createSection({
        name: form.name.trim(),
        description: form.description.trim() || null,
        is_active: form.is_active,
      })
    }
    cancelEdit()
    emit('updated')
  } catch (err: any) {
    validationError.value = err.message || 'Failed to save section.'
  } finally {
    saving.value = false
  }
}

const handleDelete = async (sec: RestaurantSection) => {
  if (
    confirm(
      `Are you sure you want to delete the "${sec.name}" section? Tables in this section will be unassigned from it.`,
    )
  ) {
    try {
      await sectionStore.deleteSection(sec.id)
      if (editingSectionId.value === sec.id) {
        cancelEdit()
      }
      emit('updated')
    } catch (err: any) {
      alert(err.message || 'Failed to delete section.')
    }
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
