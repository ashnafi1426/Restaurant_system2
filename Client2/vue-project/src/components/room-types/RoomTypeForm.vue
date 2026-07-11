<script setup lang="ts">
import { reactive, watch } from 'vue'
import type { RoomType } from '../../types/roomType'

const props = defineProps<{
  modelValue: RoomType
}>()

const emit = defineEmits(['update:modelValue', 'submit'])
const form = reactive<RoomType>({ ...props.modelValue })

watch(
  () => form,
  () => emit('update:modelValue', form),
  { deep: true },
)

const submit = () => {
  emit('submit', form)
}
</script>

<template>
  <form @submit.prevent="submit" class="space-y-4 sm:space-y-5 md:space-y-6">
    <!-- Name -->
    <div>
      <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
        Room Type Name <span class="text-red-500">*</span>
      </label>
      <input
        v-model="form.name"
        type="text"
        placeholder="e.g., Deluxe Room"
        class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
        required
      />
    </div>

    <!-- Description -->
    <div>
      <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
        Description <span class="text-slate-400 text-xs">(Optional)</span>
      </label>
      <textarea
        v-model="form.description"
        rows="4"
        placeholder="Describe the room type features, amenities, etc..."
        class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 resize-none"
      />
    </div>

    <!-- Price and Capacity in Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 md:gap-5">
      <!-- Price Per Night -->
      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
          Price Per Night <span class="text-red-500">*</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 sm:left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-xs sm:text-sm font-medium">₹</span>
          <input
            type="number"
            v-model="form.base_price_per_night"
            placeholder="e.g., 2500"
            class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 pl-6 sm:pl-7 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
            min="0"
            step="100"
            required
          />
        </div>
      </div>

      <!-- Capacity -->
      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
          Capacity (Guests) <span class="text-red-500">*</span>
        </label>
        <input
          type="number"
          v-model="form.capacity"
          placeholder="e.g., 2"
          class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
          min="1"
          max="10"
          required
        />
      </div>
    </div>

    <!-- Active Checkbox -->
    <div class="flex items-start gap-2 sm:gap-3 p-3 sm:p-4 bg-slate-50 border border-slate-200 rounded-lg">
      <input
        type="checkbox"
        v-model="form.is_active"
        id="active-checkbox"
        class="w-4 h-4 sm:w-5 sm:h-5 mt-0.5 sm:mt-0 accent-blue-600 cursor-pointer"
      />
      <div class="flex-1 min-w-0">
        <label for="active-checkbox" class="text-xs sm:text-sm font-semibold text-slate-900 cursor-pointer">
          Active Room Type
        </label>
        <p class="text-xs text-slate-500 mt-0.5">
          Available for new room creation and reservations
        </p>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 pt-2 sm:pt-4">
      <button
        type="button"
        @click="$emit('cancel')"
        class="px-4 sm:px-6 py-2 sm:py-2.5 border border-slate-300 rounded-lg hover:bg-slate-50 text-xs sm:text-sm font-medium text-slate-700 transition-colors duration-200"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="px-4 sm:px-6 py-2 sm:py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-medium rounded-lg transition-colors duration-200"
      >
        💾 Save Room Type
      </button>
    </div>
  </form>
</template>
