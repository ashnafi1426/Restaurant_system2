<script setup lang="ts">
import RoomHero from '@/components/guest/RoomHero.vue'
import RoomSearchBar from '@/components/guest/RoomSearchBar.vue'
import RoomFilters from '@/components/guest/RoomFilters.vue'
import RoomGrid from '@/components/guest/RoomGrid.vue'
import RoomPagination from '@/components/guest/RoomPagination.vue'
import RoomCTA from '@/components/guest/RoomCTA.vue'
import NoRoomsFound from '@/components/guest/NoRoomsFound.vue'

import { ref, computed } from 'vue'

/**
 * Temporary state.
 * Later this will come from roomStore.
 */
const rooms = ref<any[]>([
  {}, {}, {}, {}, {}, {}
])

const loading = ref(false)

/**
 * Later:
 * const filteredRooms = computed(() => roomStore.filteredRooms)
 */
const filteredRooms = computed(() => rooms.value)
</script>

<template>
  <div class="bg-slate-50">

    <!-- Hero -->
    <RoomHero />
    <RoomSearchBar />
    <RoomFilters />
    <section class="py-16">

      <div class="mx-auto max-w-7xl px-6">

        <!-- Loading -->
        <div
          v-if="loading"
          class="flex justify-center py-24"
        >
          <div
            class="h-12 w-12 animate-spin rounded-full border-4 border-amber-500 border-t-transparent"
          />
        </div>
        <NoRoomsFound
          v-else-if="filteredRooms.length === 0"
        />
        <RoomGrid
          v-else
          :rooms="filteredRooms"
        />
        <div
          v-if="filteredRooms.length"
          class="mt-16"
        >
          <RoomPagination />
        </div>

      </div>

    </section>

    <!-- Bottom CTA -->
    <RoomCTA />

  </div>
</template>