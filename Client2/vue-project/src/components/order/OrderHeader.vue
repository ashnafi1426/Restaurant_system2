<template>
  <div
    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between pb-1"
  >
    <div>
      <h1
        class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight"
      >
        Order Management
      </h1>
      <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
        Manage, track, and process all restaurant orders
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
