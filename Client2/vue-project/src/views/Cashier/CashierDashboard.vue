<script setup lang="ts">
import DashboardLayout from '../../layouts/DashboardLayout.vue'

const stats = [
  {
    title: "Today's Revenue",
    value: '$12,450',
  },
  {
    title: 'Pending Payments',
    value: 18,
  },
  {
    title: 'Completed Payments',
    value: 126,
  },
  {
    title: 'Refund Requests',
    value: 3,
  },
]

const payments = [
  {
    invoice: 'INV-1001',
    guest: 'John Doe',
    amount: '$350',
    method: 'Cash',
    status: 'Paid',
  },
  {
    invoice: 'INV-1002',
    guest: 'Sarah Smith',
    amount: '$480',
    method: 'Card',
    status: 'Pending',
  },
  {
    invoice: 'INV-1003',
    guest: 'Michael Lee',
    amount: '$220',
    method: 'Bank Transfer',
    status: 'Paid',
  },
]
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold text-slate-800">Cashier Dashboard</h1>

        <p class="text-slate-500 mt-1">Manage invoices, payments, transactions and refunds.</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <div
          v-for="stat in stats"
          :key="stat.title"
          class="bg-white rounded-xl border shadow-sm p-6"
        >
          <p class="text-sm text-slate-500">
            {{ stat.title }}
          </p>

          <h2 class="text-4xl font-bold text-slate-800 mt-3">
            {{ stat.value }}
          </h2>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-xl border shadow-sm p-6">
        <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <button class="bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg">
            Create Invoice
          </button>

          <button class="bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg">
            Accept Payment
          </button>

          <button class="bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg">
            Transactions
          </button>

          <button class="bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg">
            Process Refund
          </button>
        </div>
      </div>

      <!-- Payment Status -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Paid Payments</h3>

          <p class="text-4xl font-bold text-green-600 mt-4">126</p>
        </div>

        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Pending Payments</h3>

          <p class="text-4xl font-bold text-yellow-500 mt-4">18</p>
        </div>

        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Refunded Payments</h3>

          <p class="text-4xl font-bold text-red-500 mt-4">3</p>
        </div>
      </div>

      <!-- Recent Payments -->
      <div class="bg-white rounded-xl border shadow-sm">
        <div class="px-6 py-4 border-b">
          <h2 class="text-lg font-semibold">Recent Payments</h2>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50">
              <tr>
                <th class="text-left p-4">Invoice</th>

                <th class="text-left p-4">Guest</th>

                <th class="text-left p-4">Amount</th>

                <th class="text-left p-4">Method</th>

                <th class="text-left p-4">Status</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="payment in payments" :key="payment.invoice" class="border-t">
                <td class="p-4">
                  {{ payment.invoice }}
                </td>

                <td class="p-4">
                  {{ payment.guest }}
                </td>

                <td class="p-4">
                  {{ payment.amount }}
                </td>

                <td class="p-4">
                  {{ payment.method }}
                </td>

                <td class="p-4">
                  <span
                    v-if="payment.status === 'Paid'"
                    class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm"
                  >
                    Paid
                  </span>

                  <span v-else class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-sm">
                    Pending
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
