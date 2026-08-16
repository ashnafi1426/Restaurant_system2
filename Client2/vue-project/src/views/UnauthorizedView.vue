<script setup lang="ts">
import { useRouter } from 'vue-router'
import { ShieldAlert, ArrowLeft, Home } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const goBack = () => {
  router.back()
}

const goHome = () => {
  const role = String(auth.user?.role || '').toLowerCase().trim()

  if (auth.isAdmin || role === 'admin') {
    router.push('/admin')
  } else if (role === 'receptionist') {
    router.push('/receptionist')
  } else if (role === 'manager') {
    router.push('/manager')
  } else if (role === 'cashier') {
    router.push('/cashier')
  } else if (role === 'chef') {
    router.push('/chef/pending-orders')
  } else if (role === 'waiter') {
    router.push('/waiter/ready-pickup')
  } else if (auth.can('orders.view')) {
    router.push('/orders')
  } else if (auth.can('kitchen.view') || auth.can('kitchen.accept')) {
    router.push('/chef/pending-orders')
  } else if (auth.can('menu.view')) {
    router.push('/menu-management')
  } else if (auth.can('rooms.view') || auth.can('guests.view') || auth.can('dashboard.view')) {
    router.push('/receptionist')
  } else if (auth.can('payments.view')) {
    router.push('/cashier/payments')
  } else if (auth.can('delivery.pickup') || auth.can('delivery.deliver')) {
    router.push('/waiter/ready-pickup')
  } else {
    router.push('/login')
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 flex flex-col items-center justify-center p-6 text-slate-900 dark:text-slate-100 font-sans transition-colors duration-300">
    <div class="max-w-md w-full text-center space-y-6 bg-white dark:bg-slate-900 p-8 sm:p-10 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl">
      <div class="w-16 h-16 mx-auto rounded-3xl bg-rose-500/10 dark:bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-rose-600 dark:text-rose-400 shadow-xs">
        <ShieldAlert class="w-8 h-8" />
      </div>

      <div class="space-y-2">
        <span class="text-xs font-black uppercase tracking-widest text-rose-600 dark:text-rose-400 bg-rose-500/10 px-3 py-1 rounded-full border border-rose-500/20">
          403 Access Denied
        </span>
        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white pt-2">
          Unauthorized Access
        </h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
          You do not possess the required permission or scope to access this page or perform this action. Contact system administrator for authorization.
        </p>
      </div>

      <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-4">
        <button
          @click="goBack"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-xs font-bold transition cursor-pointer"
        >
          <ArrowLeft class="w-4 h-4" />
          <span>Go Back</span>
        </button>

        <button
          @click="goHome"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-black shadow-md shadow-amber-500/20 transition cursor-pointer"
        >
          <Home class="w-4 h-4" />
          <span>Return to Dashboard</span>
        </button>
      </div>
    </div>
  </div>
</template>
