<script setup lang="ts">
import { reactive, watch } from 'vue'
import type { GuestForm } from '../../types/guest'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

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

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-6 border border-slate-200 dark:border-slate-700">
      <h2 class="text-xl font-semibold mb-6 text-slate-900 dark:text-white">{{ languageStore.t('personal_info', 'Personal Information') }}</h2>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block mb-2 font-medium text-slate-700 dark:text-slate-300"> {{ languageStore.t('first_name', 'First Name') }} * </label>

          <input
            v-model="form.first_name"
            type="text"
            class="w-full border dark:border-slate-700 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
            :placeholder="languageStore.t('enter_first_name', 'Enter first name')"
          />
        </div>

        <div>
          <label class="block mb-2 font-medium text-slate-700 dark:text-slate-300"> {{ languageStore.t('last_name', 'Last Name') }} * </label>

          <input
            v-model="form.last_name"
            type="text"
            class="w-full border dark:border-slate-700 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
            :placeholder="languageStore.t('enter_last_name', 'Enter last name')"
          />
        </div>

        <div>
          <label class="block mb-2 font-medium text-slate-700 dark:text-slate-300"> {{ languageStore.t('email', 'Email') }} </label>

          <input
            v-model="form.email"
            type="email"
            class="w-full border dark:border-slate-700 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
            placeholder="example@email.com"
          />
        </div>

        <div>
          <label class="block mb-2 font-medium text-slate-700 dark:text-slate-300"> {{ languageStore.t('phone', 'Phone') }} * </label>

          <input
            v-model="form.phone"
            type="text"
            class="w-full border dark:border-slate-700 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
            placeholder="+2519xxxxxxxx"
          />
        </div>
      </div>
    </div>
    <!-- Address -->

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-6 border border-slate-200 dark:border-slate-700">
      <h2 class="text-xl font-semibold mb-6 text-slate-900 dark:text-white">{{ languageStore.t('address', 'Address') }}</h2>

      <textarea
        v-model="form.address"
        rows="4"
        class="w-full border dark:border-slate-700 rounded-lg px-4 py-3 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
        :placeholder="languageStore.t('enter_guest_address', 'Enter guest address')"
      ></textarea>
    </div>

    <!-- Preferences -->

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-6 border border-slate-200 dark:border-slate-700">
      <h2 class="text-xl font-semibold mb-6 text-slate-900 dark:text-white">{{ languageStore.t('preferences', 'Preferences') }}</h2>

      <input
        :value="preferenceText()"
        @input="updatePreferences(($event.target as HTMLInputElement).value)"
        class="w-full border dark:border-slate-700 rounded-lg px-4 py-3 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
        placeholder="Non Smoking, Sea View, King Bed"
      />

      <p class="text-sm text-gray-500 dark:text-slate-400 mt-2">{{ languageStore.t('separate_preferences', 'Separate preferences using commas.') }}</p>
    </div>

    <!-- Buttons -->

    <div class="flex justify-end gap-4">
      <button type="button" @click="emit('cancel')" class="px-6 py-3 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
        {{ languageStore.t('cancel', 'Cancel') }}
      </button>

      <button
        type="submit"
        :disabled="loading"
        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg disabled:opacity-50 transition cursor-pointer"
      >
        {{ loading ? languageStore.t('saving', 'Saving...') : languageStore.t('save_guest', 'Save Guest') }}
      </button>
    </div>
  </form>
</template>
