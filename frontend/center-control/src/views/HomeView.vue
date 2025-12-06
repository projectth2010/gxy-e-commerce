<script setup lang="ts">
import { ref } from 'vue'

const tenants = ref([
  { id: 1, name: 'Starlight Cafe', status: 'Active', plan: 'Pro', domain: 'starlight.gxy.com' },
  { id: 2, name: 'Evergreen Books', status: 'Active', plan: 'Pro', domain: 'books.gxy.com' },
  { id: 3, name: 'Digital Art Hub', status: 'Suspended', plan: 'Basic', domain: 'art.gxy.com' },
  { id: 4, name: 'Gourmet Gadgets', status: 'Active', plan: 'Enterprise', domain: 'gadgets.gxy-e.com' },
  { id: 5, name: 'Vintage Threads', status: 'Draft', plan: 'Basic', domain: 'vintage.gxy.com' },
])

const statusColor = (status: string) => {
  switch (status) {
    case 'Active': return 'bg-green-100 text-green-800';
    case 'Suspended': return 'bg-red-100 text-red-800';
    case 'Draft': return 'bg-yellow-100 text-yellow-800';
    default: return 'bg-gray-100 text-gray-800';
  }
}
</script>

<template>
  <div class="container mx-auto p-8">
    <header class="flex justify-between items-center mb-8">
      <h1 class="text-4xl font-bold text-gray-800">Tenant Management</h1>
      <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition duration-300">
        + Create Tenant
      </button>
    </header>

    <div class="bg-white rounded-lg shadow-xl overflow-hidden">
      <table class="min-w-full">
        <thead class="bg-gray-200">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tenant Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Plan</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Domain</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="tenant in tenants" :key="tenant.id" class="hover:bg-gray-50 transition duration-150">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ tenant.name }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="statusColor(tenant.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                {{ tenant.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ tenant.plan }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <a :href="'http://' + tenant.domain" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-900">
                {{ tenant.domain }}
              </a>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
              <button class="text-red-600 hover:text-red-900">Suspend</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
