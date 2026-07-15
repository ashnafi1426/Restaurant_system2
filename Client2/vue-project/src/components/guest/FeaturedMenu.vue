<script setup lang="ts">

interface MenuItem{
    id: string | number
    name:string
    description:string
    price:number
    image:string|null
    preparation_time?:number
    is_popular?:boolean
    is_featured?:boolean
}

interface Props{
    items:MenuItem[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
    (e:'add',item:MenuItem):void
}>()

const placeholderImage =
'https://images.unsplash.com/photo-1544025162-d76694265947?w=800'

</script>

<template>

<section v-if="items.length" class="mb-10">

    <!-- Header -->

    <div class="flex items-center justify-between mb-6">

        <div>

            <h2 class="text-2xl font-bold text-slate-900">
                ⭐ Chef's Recommendations
            </h2>

            <p class="text-slate-500 mt-1">
                Signature dishes carefully prepared by our chefs.
            </p>

        </div>

    </div>

    <!-- Cards -->

    <div
        class="flex gap-6 overflow-x-auto pb-3 snap-x snap-mandatory"
    >

        <div

            v-for="item in items"

            :key="item.id"

            class="min-w-[330px] max-w-[330px] bg-white rounded-3xl overflow-hidden shadow-sm border border-slate-200 hover:shadow-xl transition duration-300 snap-start"

        >

            <!-- Image -->

            <div class="relative h-56">

                <img

                    :src="item.image || placeholderImage"

                    class="w-full h-full object-cover"

                />

                <div
                    class="absolute top-4 left-4 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold"
                >
                    🔥 Popular
                </div>

                <div
                    class="absolute top-4 right-4 bg-amber-500 text-white px-3 py-1 rounded-full text-xs font-bold"
                >
                    ⭐ Chef Choice
                </div>

            </div>

            <!-- Body -->

            <div class="p-6">

                <h3 class="text-xl font-bold text-slate-900">

                    {{item.name}}

                </h3>

                <p
                    class="text-slate-500 text-sm mt-2 line-clamp-2"
                >

                    {{item.description}}

                </p>

                <div
                    class="flex items-center gap-3 mt-4 text-sm text-slate-500"
                >

                    ⭐⭐⭐⭐⭐

                    <span>

                        •

                    </span>

                    <span>

                        ⏱

                        {{item.preparation_time || 20}}

                        mins

                    </span>

                </div>

                <div
                    class="flex items-center justify-between mt-6"
                >

                    <div>

                        <p class="text-2xl font-bold text-teal-600">

                            {{item.price}}

                            ETB

                        </p>

                    </div>

                    <button

                        @click="emit('add',item)"

                        class="px-5 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-semibold transition"

                    >

                        Add To Cart
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
</template>