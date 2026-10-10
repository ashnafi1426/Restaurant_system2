<script setup lang="ts">
interface MenuItem {
  id: string | number
  name: string
  description?: string
  price: number
  image?: string | null
  total_price?: number
  category?: string
  is_available?: boolean
  preparation_time?: number
  rating?: number
  is_popular?: boolean
  is_new?: boolean
  is_spicy?: boolean
  is_vegetarian?: boolean
  [key: string]: any
}

interface Props {
  items: MenuItem[]
}

defineProps<Props>()

const emit = defineEmits<{
  (e: 'add', item: MenuItem): void
}>()

const placeholder = 'https://images.unsplash.com/photo-1544025162-d76694265947?w=800'
</script>

<template>
  <section class="mt-12">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-3xl font-bold text-slate-900">🔥 Most Popular</h2>

        <p class="text-slate-500 mt-1">Guest favourites ordered most often.</p>
      </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <article
        v-for="item in items"
        :key="item.id"
        class="group rounded-3xl overflow-hidden bg-white border border-slate-200 shadow-sm hover:shadow-xl transition duration-300"
      >
        <div class="relative h-56 overflow-hidden">
          <img
            :src="item.image || placeholder"
            class="w-full h-full object-cover group-hover:scale-110 transition duration-500"
          />

          <div class="absolute top-3 left-3 flex flex-col gap-2">
            <span
              v-if="item.is_new"
              class="bg-green-500 text-white text-xs px-3 py-1 rounded-full font-semibold"
            >
              NEW
            </span>

            <span
              v-if="item.is_popular"
              class="bg-red-500 text-white text-xs px-3 py-1 rounded-full font-semibold"
            >
              BEST SELLER
            </span>

            <span
              v-if="item.is_spicy"
              class="bg-orange-500 text-white text-xs px-3 py-1 rounded-full font-semibold"
            >
              🌶 Spicy
            </span>

            <span
              v-if="item.is_vegetarian"
              class="bg-emerald-600 text-white text-xs px-3 py-1 rounded-full font-semibold"
            >
              🥗 Vegetarian
            </span>
          </div>

          <button
            class="absolute top-3 right-3 w-10 h-10 rounded-full bg-white/90 backdrop-blur flex items-center justify-center hover:bg-red-500 hover:text-white transition"
          >
            ♡
          </button>
        </div>

        <div class="p-5">
          <h3 class="font-bold text-lg text-slate-900">
            {{ item.name }}
          </h3>

          <p class="text-sm text-slate-500 mt-2 line-clamp-2">
            {{ item.description }}
          </p>

          <div class="flex items-center gap-4 mt-4 text-sm text-slate-500">
            <span> ⭐ {{ item.rating ?? '4.8' }} </span>

            <span> ⏱ {{ item.preparation_time ?? 20 }} min </span>
          </div>

          <div class="mt-6 flex items-center justify-between">
            <div>
              <p class="text-2xl font-bold text-teal-600">
                {{
                  item.total_price != null
                    ? Number(item.total_price).toFixed(2)
                    : Number(item.price).toFixed(2)
                }}

                ETB
              </p>
            </div>

            <button
              @click="emit('add', item)"
              class="px-5 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-semibold transition"
            >
              + Add
            </button>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>
