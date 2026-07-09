<script setup lang="ts">
const search = defineModel<string>()
</script>

<template>
  <div class="mb-4">
    <input v-model="search" placeholder="Search room types..." class="border p-2 w-full rounded" />
  </div>
</template>
