<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <button @click="$router.back()" class="text-gray-500 hover:text-gray-700">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Subscription Details</h1>
          <p class="text-sm text-gray-500">{{ subscription?.school?.name }}</p>
        </div>
      </div>
      <div class="flex space-x-3">
        <button @click="showChangePlanModal = true" class="btn-secondary">
          <span class="mr-2">🔄</span> Change Plan
        </button>
        <button v-if="subscription?.status === 'active'" @click="cancelSubscription" class="btn-danger">
          <span class="mr-2">❌</span> Cancel
        </button>
        <button v-else @click="reactivateSubscription" class="btn-success">
          <span class="mr-2">✅</span> Reactivate
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button @click="activeTab = 'overview'" 
                :class="activeTab === 'overview' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="py-4 px-1 border-b-2 font-medium text-sm">
          Overview
        </button>
        <button @click="activeTab = 'modules'" 
                :class="activeTab === 'modules' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="py-4 px-1 border-b-2 font-medium text-sm">
          Modules
        </button>
        <button @click="activeTab = 'history'" 
                :class="activeTab === 'history' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="py-4 px-1 border-b-2 font-medium text-sm">
          History
        </button>
      </nav>
    </div>

    <!-- Overview Tab -->
    <div v-if="activeTab === 'overview'" class="space-y-6">
      <!-- Status Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="card">
          <div class="text-sm text-gray-500">Status</div>
          <div class="mt-1">
            <span :class="{
              'bg-green-100 text-green-700': subscription?.status === 'active',
              'bg-blue-100 text-blue-700': subscription?.status === 'trial',
              'bg-red-100 text-red-700': subscription?.status === 'expired' || subscription?.status === 'cancelled',
              'bg-yellow-100 text-yellow-700': subscription?.status === 'past_due'
            }" class="px-3 py-1 text-sm rounded-full capitalize font-medium">
              {{ subscription?.status }}
            </span>
          </div>
        </div>
        <div class="card">
          <div class="text-sm text-gray-500">Current Plan</div>
          <div class="mt-1 text-lg font-semibold">{{ subscription?.planModel?.name || subscription?.plan_name || subscription?.plan }}</div>
        </div>
        <div class="card">
          <div class="text-sm text-gray-500">Amount</div>
          <div class="mt-1 text-lg font-semibold">{{ subscription?.currency }} ₹{{ subscription?.amount }}</div>
        </div>
        <div class="card">
          <div class="text-sm text-gray-500">Billing Cycle</div>
          <div class="mt-1 text-lg font-semibold capitalize">{{ subscription?.billing_cycle }}</div>
        </div>
      </div>

      <!-- Dates -->
      <div class="card">
        <h3 class="text-lg font-semibold mb-4">Important Dates</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <div class="text-sm text-gray-500">Started At</div>
            <div class="text-base font-medium">{{ formatDate(subscription?.starts_at) }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Trial Ends</div>
            <div class="text-base font-medium">{{ subscription?.trial_ends_at ? formatDate(subscription.trial_ends_at) : 'N/A' }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Expires At</div>
            <div class="text-base font-medium">{{ subscription?.expires_at ? formatDate(subscription.expires_at) : 'Lifetime' }}</div>
          </div>
        </div>
        <div v-if="subscription?.expires_at" class="mt-4">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500">Days Remaining</span>
            <span class="font-medium" :class="daysRemaining <= 7 ? 'text-red-600' : 'text-green-600'">
              {{ daysRemaining }} days
            </span>
          </div>
          <div class="mt-2 bg-gray-200 rounded-full h-2">
            <div class="h-2 rounded-full transition-all" 
                 :class="daysRemaining <= 7 ? 'bg-red-500' : 'bg-green-500'"
                 :style="{ width: `${Math.min(100, (daysRemaining / 365) * 100)}%` }"></div>
          </div>
        </div>
      </div>

      <!-- School Info -->
      <div class="card">
        <h3 class="text-lg font-semibold mb-4">School Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <div class="text-sm text-gray-500">School Name</div>
            <div class="text-base font-medium">{{ subscription?.school?.name }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Email</div>
            <div class="text-base font-medium">{{ subscription?.school?.email }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Phone</div>
            <div class="text-base font-medium">{{ subscription?.school?.phone || 'N/A' }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Country</div>
            <div class="text-base font-medium">{{ subscription?.school?.country || 'N/A' }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modules Tab -->
    <div v-if="activeTab === 'modules'" class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold">Enabled Modules</h3>
          <p class="text-sm text-gray-500">Manage which modules are available for this school</p>
        </div>
        <button @click="saveModules" :disabled="!hasModuleChanges" class="btn-primary" :class="{ 'opacity-50 cursor-not-allowed': !hasModuleChanges }">
          <span class="mr-2">💾</span> Save Changes
        </button>
      </div>

      <!-- Free/Core Modules -->
      <div class="card">
        <h4 class="text-sm font-semibold mb-3 text-green-700">✓ Free & Core Modules (Always Enabled)</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div v-for="module in freeAndCoreModules" :key="module.id"
               class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center space-x-3">
              <span class="text-xl">{{ module.icon }}</span>
              <div>
                <div class="font-medium">{{ module.name }}</div>
                <div class="text-xs text-gray-500">{{ module.key }}</div>
              </div>
            </div>
            <span class="px-2 py-1 bg-green-200 text-green-800 text-xs rounded font-medium">Enabled</span>
          </div>
        </div>
      </div>

      <!-- Paid Modules -->
      <div class="card">
        <h4 class="text-sm font-semibold mb-3">Additional Modules</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div v-for="module in paidModules" :key="module.id"
               @click="toggleModule(module.id)"
               class="cursor-pointer p-3 border rounded-lg transition-all hover:shadow-md"
               :class="module.is_enabled ? 'bg-primary-50 border-primary-300' : 'bg-white border-gray-200'">
            <div class="flex items-start justify-between">
              <div class="flex items-start space-x-3">
                <span class="text-xl">{{ module.icon }}</span>
                <div>
                  <div class="font-medium">{{ module.name }}</div>
                  <div class="text-xs text-gray-500">{{ module.key }}</div>
                </div>
              </div>
              <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                   :class="module.is_enabled ? 'bg-primary-600 border-primary-600' : 'border-gray-300'">
                <svg v-if="module.is_enabled" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- History Tab -->
    <div v-if="activeTab === 'history'" class="space-y-4">
      <div class="card">
        <h3 class="text-lg font-semibold mb-4">Audit Log</h3>
        <div v-if="auditLogs.length > 0" class="space-y-3">
          <div v-for="log in auditLogs" :key="log.id"
               class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
            <div class="w-2 h-2 mt-2 rounded-full bg-primary-500"></div>
            <div class="flex-1">
              <div class="text-sm font-medium">{{ log.description }}</div>
              <div class="text-xs text-gray-500 mt-1">
                {{ formatDate(log.created_at) }} by {{ log.user?.name || 'System' }}
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8 text-gray-500">
          No audit logs available
        </div>
      </div>

      <div class="card">
        <h3 class="text-lg font-semibold mb-4">Plan Changes</h3>
        <div v-if="planChanges.length > 0" class="space-y-3">
          <div v-for="change in planChanges" :key="change.changed_at"
               class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
              <div class="text-sm">
                <span class="font-medium">{{ change.from_plan || 'Previous Plan' }}</span>
                <span class="mx-2 text-gray-400">→</span>
                <span class="font-medium text-primary-600">{{ change.to_plan }}</span>
              </div>
              <div class="text-xs text-gray-500 mt-1">{{ formatDate(change.changed_at) }}</div>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8 text-gray-500">
          No plan changes recorded
        </div>
      </div>
    </div>
  </div>

  <!-- Change Plan Modal -->
  <div v-if="showChangePlanModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Change Subscription Plan</h2>
        <p class="text-sm text-gray-500">Select a new plan for {{ subscription?.school?.name }}</p>
      </div>

      <div class="p-6 space-y-4">
        <!-- Current Plan -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <div class="text-sm text-gray-500">Current Plan</div>
          <div class="font-semibold text-lg">{{ subscription?.planModel?.name || subscription?.plan }}</div>
          <div class="text-sm text-gray-600">₹{{ subscription?.amount }} / {{ subscription?.billing_cycle }}</div>
        </div>

        <!-- Available Plans -->
        <div class="space-y-3">
          <label class="text-sm font-medium">Select New Plan</label>
          <div v-for="plan in availablePlans" :key="plan.id"
               @click="selectedPlanId = plan.id"
               class="cursor-pointer p-4 border-2 rounded-lg transition-all"
               :class="selectedPlanId === plan.id ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'">
            <div class="flex justify-between items-start">
              <div>
                <div class="font-semibold">{{ plan.name }}</div>
                <div class="text-sm text-gray-500">{{ plan.description || 'No description' }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ plan.modules?.length || 0 }} modules included</div>
              </div>
              <div class="text-right">
                <div class="text-xl font-bold">₹{{ plan.price }}</div>
                <div class="text-sm text-gray-500">/{{ plan.billing_cycle }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Proration Option -->
        <div class="flex items-center space-x-2">
          <input v-model="prorate" type="checkbox" id="prorate" class="w-4 h-4" />
          <label for="prorate" class="text-sm">Calculate proration (charge/credit based on remaining days)</label>
        </div>
      </div>

      <div class="p-6 border-t flex justify-end space-x-3">
        <button @click="showChangePlanModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
          Cancel
        </button>
        <button @click="changePlan" :disabled="!selectedPlanId" class="btn-primary" :class="{ 'opacity-50 cursor-not-allowed': !selectedPlanId }">
          Confirm Plan Change
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'
import { useAppToast } from '@/composables/useToast'

const route = useRoute()
const toast = useAppToast()
const subscription = ref(null)
const modules = ref([])
const auditLogs = ref([])
const planChanges = ref([])
const availablePlans = ref([])

const activeTab = ref('overview')
const showChangePlanModal = ref(false)
const selectedPlanId = ref(null)
const prorate = ref(false)
const originalModuleState = ref([])

const freeAndCoreModules = computed(() => 
  modules.value.filter(m => m.is_core || m.is_free)
)

const paidModules = computed(() => 
  modules.value.filter(m => !m.is_core && !m.is_free)
)

const hasModuleChanges = computed(() => {
  const current = modules.value.filter(m => m.is_enabled).map(m => m.id).sort().join(',')
  const original = [...originalModuleState.value].sort().join(',')
  return current !== original
})

const daysRemaining = computed(() => {
  if (!subscription.value?.expires_at) return 365
  const now = new Date()
  const expires = new Date(subscription.value.expires_at)
  const diff = expires - now
  return Math.max(0, Math.floor(diff / (1000 * 60 * 60 * 24)))
})

const fetchSubscription = async () => {
  try {
    const response = await api.get(`/super-admin/subscriptions/${route.params.id}`)
    subscription.value = response.data.data.subscription
    modules.value = response.data.data.modules || []
    availablePlans.value = response.data.data.availablePlans || []

    // Store original module state
    originalModuleState.value = modules.value.filter(m => m.is_enabled).map(m => m.id)
  } catch (error) {
    console.error('Error fetching subscription:', error)
    toast.error('Failed to load subscription details')
  }
}

const fetchHistory = async () => {
  try {
    const response = await api.get(`/super-admin/subscriptions/${route.params.id}/history`)
    auditLogs.value = response.data.data.audit_logs || []
    planChanges.value = response.data.data.plan_changes || []
  } catch (error) {
    console.error('Error fetching history:', error)
  }
}

const toggleModule = (moduleId) => {
  const module = modules.value.find(m => m.id === moduleId)
  if (module) {
    module.is_enabled = !module.is_enabled
  }
}

const saveModules = async () => {
  const enabledModules = modules.value.filter(m => m.is_enabled).map(m => m.id)

  try {
    await api.post(`/super-admin/subscriptions/${route.params.id}/modules`, {
      modules: enabledModules
    })
    toast.success('Modules updated successfully')
    originalModuleState.value = enabledModules
    await fetchSubscription()
  } catch (error) {
    console.error('Error saving modules:', error)
    toast.error(error.response?.data?.message || 'Failed to save modules')
  }
}

const changePlan = async () => {
  if (!selectedPlanId.value) return

  try {
    await api.post(`/super-admin/subscriptions/${route.params.id}/change-plan`, {
      subscription_plan_id: selectedPlanId.value,
      prorate: prorate.value
    })
    toast.success('Plan changed successfully')
    showChangePlanModal.value = false
    selectedPlanId.value = null
    prorate.value = false
    await fetchSubscription()
  } catch (error) {
    console.error('Error changing plan:', error)
    toast.error(error.response?.data?.message || 'Failed to change plan')
  }
}

const cancelSubscription = async () => {
  if (!window.confirm('Are you sure you want to cancel this subscription?')) return

  try {
    await api.post(`/super-admin/subscriptions/${route.params.id}/cancel`)
    toast.success('Subscription cancelled successfully')
    await fetchSubscription()
  } catch (error) {
    console.error('Error cancelling subscription:', error)
    toast.error('Failed to cancel subscription')
  }
}

const reactivateSubscription = async () => {
  try {
    await api.post(`/super-admin/subscriptions/${route.params.id}/reactivate`)
    toast.success('Subscription reactivated successfully')
    await fetchSubscription()
  } catch (error) {
    console.error('Error reactivating subscription:', error)
    toast.error('Failed to reactivate subscription')
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(() => {
  fetchSubscription()
  fetchHistory()
})
</script>
