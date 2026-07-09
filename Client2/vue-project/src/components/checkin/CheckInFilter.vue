<script setup lang="ts">
import { reactive, watch } from 'vue'

const emit = defineEmits<{
  (e: 'search', value: any): void
}>()

const filters = reactive({
  guest: '',

  room: '',

  reservation: '',
})

watch(
  filters,
  () => {
    emit('search', filters)
  },
  {
    deep: true,
  },
)
</script>

<template>
  <v-card class="mb-5">
    <v-card-text>
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.guest"
            label="Guest"
            prepend-inner-icon="mdi-account"
            clearable
          />
        </v-col>

        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.room"
            label="Room"
            prepend-inner-icon="mdi-door"
            clearable
          />
        </v-col>

        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.reservation"
            label="Reservation"
            prepend-inner-icon="mdi-calendar"
            clearable
          />
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>
