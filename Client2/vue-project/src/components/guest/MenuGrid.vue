<script setup lang="ts">
import MenuCard from './MenuCard.vue'

export interface MenuItem {
  id: string | number
  name: string
  description: string
  price: number
  image: string | null
  category: string

  rating?: number
  preparation_time?: number

  is_featured?: boolean
  is_popular?: boolean
  is_new?: boolean
  is_spicy?: boolean
  is_vegetarian?: boolean
}

interface Props {
  items: MenuItem[]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
})

const emit = defineEmits<{
  (e: 'add', item: MenuItem): void
}>()

const addItem = (item: MenuItem) => {
  emit('add', item)
}
</script>

<template>

<section>

    <!-- Loading -->

    <div
        v-if="loading"
        class="grid gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3"
    >

        <div
            v-for="i in 6"
            :key="i"
            class="rounded-3xl bg-white border border-slate-200 overflow-hidden"
        >

            <div class="h-56 bg-slate-200 animate-pulse"/>

            <div class="p-5 space-y-4">

                <div
                    class="h-6 rounded bg-slate-200 animate-pulse"
                />

                <div
                    class="h-4 rounded bg-slate-200 animate-pulse"
                />

                <div
                    class="h-4 w-2/3 rounded bg-slate-200 animate-pulse"
                />

                <div
                    class="flex justify-between pt-3"
                >

                    <div
                        class="h-8 w-24 rounded bg-slate-200 animate-pulse"
                    />

                    <div
                        class="h-10 w-28 rounded bg-slate-200 animate-pulse"
                    />

                </div>

            </div>

        </div>

    </div>

    <!-- Empty -->

    <div
        v-else-if="items.length===0"
        class="py-20"
    >

        <div
            class="max-w-md mx-auto text-center"
        >

            <div
                class="text-7xl"
            >
                🍽️
            </div>

            <h2
                class="mt-6 text-2xl font-bold text-slate-800"
            >
                No Menu Items Found
            </h2>

            <p
                class="mt-3 text-slate-500"
            >
                We couldn't find any dishes matching your search.
                Please choose another category or search again.
            </p>

        </div>

    </div>

    <!-- Grid -->

    <div
        v-else
        class="grid gap-7 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3"
    >

        <MenuCard

            v-for="item in items"

            :key="item.id"

            :item="item"

            @add-to-cart="addItem"

        />

    </div>

</section>

</template>