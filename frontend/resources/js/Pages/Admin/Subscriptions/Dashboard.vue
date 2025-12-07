<template>
  <div>
    <Head title="Subscription Dashboard" />
    
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Time Range Selector -->
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-semibold text-gray-900">Subscription Dashboard</h2>
          <div class="flex space-x-4">
            <select
              v-model="timeRange"
              @change="fetchChartData"
              class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            >
              <option value="7d">Last 7 days</option>
              <option value="30d">Last 30 days</option>
              <option value="90d">Last 90 days</option>
              <option value="12m">Last 12 months</option>
            </select>
          </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid gap-6 mb-8 md:grid-cols-2 lg:grid-cols-4">
          <!-- MRR Card -->
          <div class="p-5 bg-white rounded-lg shadow">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-medium text-gray-500">Monthly Recurring Revenue</h3>
              <div class="p-2 bg-blue-50 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ formatCurrency(metrics.mrr || 0) }}</p>
            <div v-if="metrics.mrr_growth_rate !== undefined" class="mt-2 flex items-center text-sm" :class="metrics.mrr_growth_rate >= 0 ? 'text-green-600' : 'text-red-600'">
              <svg v-if="metrics.mrr_growth_rate >= 0" class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12 7a1 1 0 01-1 1H9v1h2a1 1 0 110 2H9v1a1 1 0 11-2 0v-1H5a1 1 0 110-2h2V8a1 1 0 011-1h4z" clip-rule="evenodd" />
              </svg>
              <svg v-else class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12 13a1 1 0 01-1 1H9v1a1 1 0 11-2 0v-1H5a1 1 0 110-2h2v-1a1 1 0 112 0v1h2a1 1 0 011 1z" clip-rule="evenodd" />
              </svg>
              {{ Math.abs(metrics.mrr_growth_rate || 0).toFixed(1) }}%
              <span class="ml-1 text-xs text-gray-500">vs last period</span>
            </div>
          </div>

          <!-- Active Subscriptions Card -->
          <div class="p-5 bg-white rounded-lg shadow">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-medium text-gray-500">Active Subscriptions</h3>
              <div class="p-2 bg-green-50 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ (metrics.active_subscriptions || 0).toLocaleString() }}</p>
          </div>

          <!-- Churn Rate Card -->
          <div class="p-5 bg-white rounded-lg shadow">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-medium text-gray-500">Churn Rate</h3>
              <div class="p-2 bg-red-50 rounded-lg">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                </svg>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ (metrics.churn_rate * 100 || 0).toFixed(2) }}%</p>
          </div>

          <!-- ARPU Card -->
          <div class="p-5 bg-white rounded-lg shadow">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-medium text-gray-500">Avg. Revenue Per User</h3>
              <div class="p-2 bg-purple-50 rounded-lg">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ formatCurrency(metrics.average_revenue_per_user || 0) }}</p>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="grid gap-6 mb-8 md:grid-cols-2">
          <!-- MRR Chart -->
          <div class="p-6 bg-white rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Recurring Revenue</h3>
            <div class="h-64">
              <!-- Chart will be rendered here -->
              <div class="h-full flex items-center justify-center text-gray-500">
                MRR Chart ({{ timeRange }})
              </div>
            </div>
          </div>

          <!-- Subscriptions Chart -->
          <div class="p-6 bg-white rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Subscriptions</h3>
            <div class="h-64">
              <!-- Chart will be rendered here -->
              <div class="h-full flex items-center justify-center text-gray-500">
                Subscriptions Chart ({{ timeRange }})
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3';

export default {
  components: {
    Head,
  },
  props: {
    metrics: {
      type: Object,
      required: true,
      default: () => ({
        mrr: 0,
        active_subscriptions: 0,
        churn_rate: 0,
        average_revenue_per_user: 0,
        mrr_growth_rate: 0,
      }),
    },
    auth: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      timeRange: '30d',
      loading: false,
    };
  },
  methods: {
    formatCurrency(value) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      }).format(value);
    },
    async fetchChartData() {
      this.loading = true;
      try {
        // Fetch chart data based on timeRange
        // const response = await axios.get(`/api/subscriptions/metrics/chart?range=${this.timeRange}`);
        // this.chartData = response.data;
      } catch (error) {
        console.error('Error fetching chart data:', error);
      } finally {
        this.loading = false;
      }
    },
  },
  mounted() {
    this.fetchChartData();
  },
};
</script>
