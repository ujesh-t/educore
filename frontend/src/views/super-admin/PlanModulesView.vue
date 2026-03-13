<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <div class="flex items-center space-x-3">
          <button @click="$router.back()" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <h1 class="text-2xl font-bold text-gray-900">Manage Plan Modules</h1>
        </div>
        <p class="text-sm text-gray-500 mt-1">Assign modules to {{ plan?.name || 'plan' }}</p>
      </div>
      <button @click="saveModules" :disabled="!hasChanges" class="btn-primary" :class="{ 'opacity-50 cursor-not-allowed': !hasChanges }">
        <span class="mr-2">💾</span> Save Changes
      </button>
    </div>

    <!-- Plan Info Card -->
    <div v-if="plan" class="card bg-gradient-to-r from-primary-50 to-blue-50">
      <div class="flex justify-between items-start">
        <div>
          <h2 class="text-xl font-bold text-gray-900">{{ plan.name }}</h2>
          <p class="text-sm text-gray-600 mt-1">{{ plan.description || 'No description' }}</p>
          <div class="flex items-center space-x-4 mt-3 text-sm">
            <span class="text-gray-600">
              <span class="font-medium">₹{{ plan.price }}</span> / {{ plan.billing_cycle }}
            </span>
            <span class="px-2 py-1 rounded text-xs" :class="plan.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'">
              {{ plan.is_active ? 'Active' : 'Inactive' }}
            </span>
            <span class="text-gray-600">
              {{ assignedModulesCount }} / {{ totalModulesCount }} modules
            </span>
          </div>
        </div>
        <div v-if="freeModulesCount > 0" class="text-right">
          <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
            ✓ {{ freeModulesCount }} Free Modules (Always Included)
          </span>
        </div>
      </div>
    </div>

    <!-- Free Modules Section -->
    <div class="card">
      <div class="flex items-center space-x-2 mb-4">
        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <h3 class="text-lg font-semibold text-gray-900">Free Modules</h3>
        <span class="text-sm text-gray-500">(Automatically included in all plans)</span>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <div v-for="module in freeModules" :key="module.id"
             class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
          <div class="flex items-center space-x-3">
            <span class="text-xl">{{ module.icon }}</span>
            <div>
              <div class="font-medium text-gray-900">{{ module.name }}</div>
              <div class="text-xs text-gray-500">{{ module.key }}</div>
            </div>
          </div>
          <span class="px-2 py-1 bg-green-200 text-green-800 text-xs rounded font-medium">Included</span>
        </div>
        <div v-if="freeModules.length === 0" class="col-span-full text-center py-6 text-gray-500">
          No modules marked as "free modules" yet.
          <a @click.prevent="navigateToModuleSettings" href="#" class="text-primary-600 hover:underline">Manage modules</a>
        </div>
      </div>
    </div>

    <!-- All Modules Selection -->
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-2">
          <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
          </svg>
          <h3 class="text-lg font-semibold text-gray-900">Additional Modules</h3>
          <span class="text-sm text-gray-500">(Select modules to include in this plan)</span>
        </div>
        <div class="flex items-center space-x-2">
          <button @click="selectAll" class="text-sm text-primary-600 hover:underline">Select All</button>
          <span class="text-gray-300">|</span>
          <button @click="deselectAll" class="text-sm text-gray-600 hover:underline">Deselect All</button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <div v-for="module in paidModules" :key="module.id"
             @click="toggleModule(module.key)"
             class="cursor-pointer p-3 border rounded-lg transition-all hover:shadow-md"
             :class="module.is_assigned ? 'bg-primary-50 border-primary-300' : 'bg-white border-gray-200 hover:border-primary-200'">
          <div class="flex items-start justify-between">
            <div class="flex items-start space-x-3">
              <span class="text-xl">{{ module.icon }}</span>
              <div>
                <div class="font-medium text-gray-900">{{ module.name }}</div>
                <div class="text-xs text-gray-500">{{ module.key }}</div>
                <div v-if="module.description" class="text-xs text-gray-500 mt-1 line-clamp-2">{{ module.description }}</div>
              </div>
            </div>
            <div class="flex items-center">
              <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                   :class="module.is_assigned ? 'bg-primary-600 border-primary-600' : 'border-gray-300'">
                <svg v-if="module.is_assigned" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary -->
    <div class="card bg-gray-50">
      <h4 class="font-semibold text-gray-900 mb-3">Summary</h4>
      <div class="flex items-center space-x-6 text-sm">
        <div class="flex items-center space-x-2">
          <span class="w-3 h-3 bg-green-500 rounded-full"></span>
          <span class="text-gray-600">Free Modules: <strong class="text-gray-900">{{ freeModulesCount }}</strong></span>
        </div>
        <div class="flex items-center space-x-2">
          <span class="w-3 h-3 bg-primary-500 rounded-full"></span>
          <span class="text-gray-600">Selected Modules: <strong class="text-gray-900">{{ selectedPaidModulesCount }}</strong></span>
        </div>
        <div class="flex items-center space-x-2">
          <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
          <span class="text-gray-600">Total in Plan: <strong class="text-gray-900">{{ assignedModulesCount }}</strong></span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const route = useRoute()

const plan = ref(null)
const allModules = ref([])
const originalModules = ref([])

const freeModules = computed(() => allModules.value.filter(m => m.is_free))
const paidModules = computed(() => allModules.value.filter(m => !m.is_free))

const freeModulesCount = computed(() => freeModules.value.length)
const selectedPaidModulesCount = computed(() => paidModules.value.filter(m => m.is_assigned).length)
const assignedModulesCount = computed(() => freeModulesCount.value + selectedPaidModulesCount.value)
const totalModulesCount = computed(() => allModules.value.length)

const hasChanges = computed(() => {
  const currentModules = allModules.value.filter(m => m.is_assigned).map(m => m.key).sort()
  const original = [...originalModules.value].sort()
  return JSON.stringify(currentModules) !== JSON.stringify(original)
})

const fetchPlanModules = async () => {
  try {
    const response = await api.get(`/super-admin/plans/${route.params.id}/modules`)
    plan.value = response.data.data.plan
    allModules.value = response.data.data.modules || []
    originalModules.value = allModules.value.filter(m => m.is_assigned).map(m => m.key)
  } catch (error) {
    console.error('Error fetching plan modules:', error)
    alert('Failed to load plan modules')
  }
}

const toggleModule = (moduleKey) => {
  const module = allModules.value.find(m => m.key === moduleKey)
  if (module) {
    module.is_assigned = !module.is_assigned
  }
}

const selectAll = () => {
  paidModules.value.forEach(m => m.is_assigned = true)
}

const deselectAll = () => {
  paidModules.value.forEach(m => m.is_assigned = false)
}

const saveModules = async () => {
  const selectedModules = allModules.value.filter(m => m.is_assigned).map(m => m.key)

  if (selectedModules.length === 0) {
    alert('Please select at least one module for this plan')
    return
  }

  try {
    await api.post(`/super-admin/plans/${route.params.id}/modules`, {
      modules: selectedModules
    })
    alert('Plan modules updated successfully!')
    originalModules.value = selectedModules
  } catch (error) {
    console.error('Error saving modules:', error)
    alert(error.response?.data?.message || 'Failed to save modules')
  }
}

const navigateToModuleSettings = () => {
  router.push('/super-admin/modules')
}

onMounted(() => {
  fetchPlanModules()
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
