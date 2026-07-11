<template>
  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between px-4 sm:px-6 md:px-8 lg:px-10 py-4 sm:py-5 md:py-6 lg:py-8">
    <div>
      <h1 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 dark:text-gray-100">
        Order Management
      </h1>
      <p class="mt-1 text-xs sm:text-sm md:text-base text-gray-600 dark:text-gray-400">
        Manage all restaurant orders
      </p>
    </div>
    <div class="flex gap-2 sm:gap-3 md:gap-4">
      <router-link
        to="/orders/create"
        class="flex items-center justify-center gap-2 px-3 sm:px-4 md:px-5 py-2 sm:py-2.5 md:py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs sm:text-sm md:text-base font-medium rounded-lg transition min-h-10 touch-none"
      >
        <span class="text-lg sm:text-xl font-bold">+</span>
        <span class="hidden sm:inline">New Order</span>
        <span class="sm:hidden">Add</span>
      </router-link>
    </div>
  </div>
</template>
