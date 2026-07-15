<script setup lang="ts">
import { ref, computed } from 'vue'

export interface MenuItem {
  id: number
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
  item: MenuItem
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'add-to-cart', item: MenuItem): void
}>()

const quantity = ref(1)

const favorite = ref(false)

const imageLoaded = ref(false)

const imageError = ref(false)

const displayImage = computed(() => {

  if (imageError.value || !props.item.image) {
    return 'https://images.unsplash.com/photo-1544025162-d76694265947?w=1200'
  }

  return props.item.image

})

const rating = computed(() => {

  return props.item.rating ?? 4.8

})

const preparationTime = computed(() => {

  return props.item.preparation_time ?? 20

})

const increase = () => {

  quantity.value++

}

const decrease = () => {

  if (quantity.value > 1) {
    quantity.value--
  }

}

const toggleFavorite = () => {

  favorite.value = !favorite.value

}

const addToCart = () => {

  for (let i = 0; i < quantity.value; i++) {

    emit('add-to-cart', props.item)

  }

}

const onImageLoaded = () => {

  imageLoaded.value = true

}

const onImageError = () => {

  imageError.value = true

}
</script>

<template>

<div
  class="group overflow-hidden rounded-3xl bg-white border border-slate-200 shadow-sm hover:shadow-2xl transition-all duration-500"
>

  <!-- Image -->

  <div class="relative h-64 overflow-hidden">

    <!-- Loading Skeleton -->

    <div
      v-if="!imageLoaded"
      class="absolute inset-0 bg-slate-200 animate-pulse"
    />

    <!-- Image -->

    <img
      :src="displayImage"
      :alt="item.name"
      @load="onImageLoaded"
      @error="onImageError"
      class="w-full h-full object-cover transition duration-700 group-hover:scale-110"
      :class="{
        'opacity-0': !imageLoaded,
        'opacity-100': imageLoaded
      }"
    />

    <!-- Gradient -->

    <div
      class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"
    />

    <!-- Badges -->

    <div
      class="absolute top-4 left-4 flex flex-col gap-2"
    >

      <span
        v-if="item.is_new"
        class="px-3 py-1 rounded-full bg-green-600 text-white text-xs font-semibold"
      >
        NEW
      </span>

      <span
        v-if="item.is_featured"
        class="px-3 py-1 rounded-full bg-amber-500 text-white text-xs font-semibold"
      >
        CHEF'S CHOICE
      </span>

      <span
        v-if="item.is_popular"
        class="px-3 py-1 rounded-full bg-red-500 text-white text-xs font-semibold"
      >
        BEST SELLER
      </span>

      <span
        v-if="item.is_spicy"
        class="px-3 py-1 rounded-full bg-orange-600 text-white text-xs font-semibold"
      >
        🌶 Spicy
      </span>

      <span
        v-if="item.is_vegetarian"
        class="px-3 py-1 rounded-full bg-emerald-600 text-white text-xs font-semibold"
      >
        🥗 Vegetarian
      </span>

    </div>

    <!-- Favorite -->

    <button

      @click.stop="toggleFavorite"

      class="absolute top-4 right-4 w-11 h-11 rounded-full bg-white/90 backdrop-blur flex items-center justify-center transition hover:bg-red-500"

      :class="favorite ? 'text-white bg-red-500' : 'text-slate-600'"

    >

      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="w-5 h-5"
        fill="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5A5.5
          5.5 0 017.5 3c1.74 0 3.41.81
          4.5 2.09A6.01 6.01 0 0116.5
          3 5.5 5.5 0 0122 8.5c0
          3.78-3.4 6.86-8.55
          11.54L12 21.35z"
        />
      </svg>

    </button>

    <!-- Bottom Overlay -->

    <div
      class="absolute bottom-0 left-0 right-0 p-5 text-white"
    >

      <h3
        class="text-2xl font-bold"
      >
        {{ item.name }}
      </h3>

      <div
        class="flex items-center gap-4 mt-2 text-sm"
      >

        <span>

          ⭐ {{ rating }}

        </span>

        <span>

          ⏱ {{ preparationTime }} mins

        </span>

      </div>

    </div>

  </div>

  <!-- Continue in Part 7.2 -->
    <!-- Card Body -->

  <div class="p-6">

    <!-- Description -->

    <p
      class="text-slate-600 text-sm leading-6 line-clamp-2 min-h-[48px]"
    >
      {{ item.description }}
    </p>

    <!-- Information -->

    <div
      class="mt-5 flex flex-wrap items-center gap-3"
    >

      <span
        class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700"
      >
        🍽 {{ item.category }}
      </span>

      <span
        class="inline-flex items-center rounded-full bg-teal-50 px-3 py-1 text-xs font-medium text-teal-700"
      >
        Freshly Prepared
      </span>

    </div>

    <!-- Price -->

    <div
      class="mt-6 flex items-end justify-between"
    >

      <div>

        <p class="text-xs uppercase tracking-wide text-slate-500">
          Price
        </p>

        <h2
          class="text-3xl font-bold text-teal-600"
        >
          {{ item.price.toFixed(2) }}

          <span class="text-base font-medium">
            ETB
          </span>

        </h2>

      </div>

      <div class="text-right">

        <p class="text-xs text-slate-500">
          Estimated Time
        </p>

        <p class="font-semibold text-slate-700">
          {{ preparationTime }} mins
        </p>

      </div>

    </div>

    <!-- Quantity -->

    <div
      class="mt-6 flex items-center justify-between"
    >

      <span
        class="font-semibold text-slate-700"
      >
        Quantity
      </span>

      <div
        class="flex items-center rounded-xl border border-slate-200 overflow-hidden"
      >

        <button

          @click="decrease"

          class="w-11 h-11 flex items-center justify-center hover:bg-slate-100 transition"

        >

          −

        </button>

        <div
          class="w-12 text-center font-semibold"
        >
          {{ quantity }}
        </div>

        <button

          @click="increase"

          class="w-11 h-11 flex items-center justify-center hover:bg-slate-100 transition"

        >

          +

        </button>

      </div>

    </div>

    <!-- Divider -->

    <div
      class="my-6 border-t border-slate-200"
    />

    <!-- Footer -->

    <div
      class="flex items-center justify-between gap-4"
    >

      <div>

        <p class="text-sm text-slate-500">

          Total

        </p>

        <p
          class="text-2xl font-bold text-slate-900"
        >

          {{ (item.price * quantity).toFixed(2) }}

          ETB

        </p>

      </div>

      <button

        @click="addToCart"

        class="flex-1 rounded-2xl bg-teal-600 px-6 py-4 font-semibold text-white transition-all duration-300 hover:bg-teal-700 hover:shadow-lg active:scale-95"

      >

        <div class="flex items-center justify-center gap-2">

          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >

            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8L5.4 5M7 13l-2 6h14M10 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"
            />

          </svg>

          <span>

            Add {{ quantity }}

            {{ quantity > 1 ? 'Items' : 'Item' }}

          </span>

        </div>

      </button>

    </div>

  </div>

</div>

</template>