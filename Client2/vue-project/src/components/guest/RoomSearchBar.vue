<template>
  <div class="room-search-wrap">
    <!-- Glassmorphism search card -->
    <div class="search-glass">
      <!-- Search Icon + Input -->
      <div class="search-input-group">
        <Search class="search-icon" :size="19" :stroke-width="2" />
        <input
          :value="modelValue"
          @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
          type="text"
          :placeholder="languageStore.t('search_rooms_guest_placeholder', 'Search by room number, type or floor…')"
          class="search-input"
          autocomplete="off"
        />
        <button
          v-if="modelValue"
          class="clear-btn cursor-pointer"
          @click="$emit('update:modelValue', '')"
          :title="languageStore.t('clear_search', 'Clear search')"
        >
          <X :size="15" :stroke-width="2.5" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Search, X } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

defineProps<{
  modelValue: string
  type?: string
}>()

defineEmits<{
  'update:modelValue': [value: string]
  'update:type': [value: string]
}>()
</script>

<style scoped>
.room-search-wrap {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* Glass card */
.search-glass {
  display: flex;
  align-items: center;
  gap: 0;
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.9);
  border-radius: 20px;
  box-shadow:
    0 20px 60px rgba(0, 0, 0, 0.10),
    0 4px 16px rgba(0, 0, 0, 0.06),
    inset 0 1px 0 rgba(255, 255, 255, 0.8);
  padding: 12px 20px;
}

/* Search input group */
.search-input-group {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  min-width: 200px;
}

.search-icon {
  width: 20px;
  height: 20px;
  color: #94a3b8;
  flex-shrink: 0;
}

.search-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 15px;
  color: #1e293b;
  font-family: inherit;
  font-weight: 500;
}

.search-input::placeholder {
  color: #94a3b8;
  font-weight: 400;
}

.clear-btn {
  background: #f1f5f9;
  border: none;
  border-radius: 8px;
  padding: 4px;
  cursor: pointer;
  color: #64748b;
  display: flex;
  align-items: center;
  transition: all 0.2s;
}
.clear-btn:hover {
  background: #e2e8f0;
  color: #1e293b;
}
</style>
