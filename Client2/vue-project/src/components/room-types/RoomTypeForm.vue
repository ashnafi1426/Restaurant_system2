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
  <form @submit.prevent="submit" class="space-y-4">
    <div>
      <label class="block mb-1">Name</label>
      <input v-model="form.name" class="border p-2 w-full rounded" />
    </div>

    <div>
      <label class="block mb-1">Description</label>
      <textarea v-model="form.description" class="border p-2 w-full rounded" />
    </div>

    <div>
      <label class="block mb-1">Price Per Night</label>
      <input type="number" v-model="form.base_price_per_night" class="border p-2 w-full rounded" />
    </div>

    <div>
      <label class="block mb-1">Capacity</label>
      <input type="number" v-model="form.capacity" class="border p-2 w-full rounded" />
    </div>

    <div class="flex items-center gap-2">
      <input type="checkbox" v-model="form.is_active" />
      <label>Active</label>
    </div>

    <button class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
  </form>
</template>
