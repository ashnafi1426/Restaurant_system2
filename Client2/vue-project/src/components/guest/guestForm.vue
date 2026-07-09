<script setup lang="ts">
import { reactive, watch } from 'vue'
import type { GuestForm } from '../../types/guest'

const props = defineProps<{
  modelValue: GuestForm
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: GuestForm): void
  (e: 'submit'): void
  (e: 'cancel'): void
}>()

const form = reactive<GuestForm>({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  address: '',
  preferences: [],
})

watch(
  () => props.modelValue,
  (value) => {
    Object.assign(form, value)
  },
  {
    immediate: true,
    deep: true,
  },
)

watch(
  form,
  () => {
    emit('update:modelValue', { ...form })
  },
  {
    deep: true,
  },
)

const preferenceText = () => {
  return form.preferences.join(', ')
}

const updatePreferences = (value: string) => {
  form.preferences = value
    .split(',')
    .map((item) => item.trim())
    .filter((item) => item !== '')
}
</script>

<template>
  <form class="space-y-8" @submit.prevent="emit('submit')">
    <!-- Personal Information -->

    <div class="bg-white rounded-xl shadow p-6">
      <h2 class="text-xl font-semibold mb-6">Personal Information</h2>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block mb-2 font-medium"> First Name * </label>

          <input
            v-model="form.first_name"
            type="text"
            class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
            placeholder="Enter first name"
          />
        </div>

        <div>
          <label class="block mb-2 font-medium"> Last Name * </label>

          <input
            v-model="form.last_name"
            type="text"
            class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
            placeholder="Enter last name"
          />
        </div>

        <div>
          <label class="block mb-2 font-medium"> Email </label>

          <input
            v-model="form.email"
            type="email"
            class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
            placeholder="example@email.com"
          />
        </div>

        <div>
          <label class="block mb-2 font-medium"> Phone * </label>

          <input
            v-model="form.phone"
            type="text"
            class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
            placeholder="+2519xxxxxxxx"
          />
        </div>
      </div>
    </div>
    <!-- Address -->

    <div class="bg-white rounded-xl shadow p-6">
      <h2 class="text-xl font-semibold mb-6">Address</h2>

      <textarea
        v-model="form.address"
        rows="4"
        class="w-full border rounded-lg px-4 py-3"
        placeholder="Enter guest address"
      ></textarea>
    </div>

    <!-- Preferences -->

    <div class="bg-white rounded-xl shadow p-6">
      <h2 class="text-xl font-semibold mb-6">Preferences</h2>

      <input
        :value="preferenceText()"
        @input="updatePreferences(($event.target as HTMLInputElement).value)"
        class="w-full border rounded-lg px-4 py-3"
        placeholder="Non Smoking, Sea View, King Bed"
      />

      <p class="text-sm text-gray-500 mt-2">Separate preferences using commas.</p>
    </div>

    <!-- Buttons -->

    <div class="flex justify-end gap-4">
      <button type="button" @click="emit('cancel')" class="px-6 py-3 rounded-lg border">
        Cancel
      </button>

      <button
        type="submit"
        :disabled="loading"
        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg disabled:opacity-50"
      >
        {{ loading ? 'Saving...' : 'Save Guest' }}
      </button>
    </div>
  </form>
</template>
