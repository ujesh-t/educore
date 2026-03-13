<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Modules</h1>
        <p class="text-sm text-gray-500 mt-1">Manage module availability across schools</p>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="card">
        <div class="text-sm text-gray-600">Total Modules</div>
        <div class="text-2xl font-bold">{{ modules.length }}</div>
      </div>
      <div class="card">
        <div class="text-sm text-gray-600">Core Modules</div>
        <div class="text-2xl font-bold">{{ modules.filter(m => m.is_core).length }}</div>
      </div>
      <div class="card">
        <div class="text-sm text-gray-600">Active Modules</div>
        <div class="text-2xl font-bold">{{ modules.filter(m => m.is_active).length }}</div>
      </div>
      <div class="card">
        <div class="text-sm text-gray-600">Inactive Modules</div>
        <div class="text-2xl font-bold">{{ modules.filter(m => !m.is_active).length }}</div>
      </div>
    </div>

    <!-- Modules Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="module in modules" :key="module.id" class="card">
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center">
            <span class="text-3xl mr-3">{{ module.icon }}</span>
            <div>
              <h3 class="font-semibold text-lg">{{ module.name }}</h3>
              <p class="text-sm text-gray-500">{{ module.key }}</p>
            </div>
          </div>
          <span v-if="module.is_core" class="px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded-full" title="Always enabled for all schools">
            Core
          </span>
        </div>
        <p class="text-sm text-gray-600 mb-4">{{ module.description || 'No description' }}</p>
        
        <!-- Usage Stats -->
        <div class="mb-4 p-3 bg-gray-50 rounded-lg">
          <div class="text-xs text-gray-500 mb-1">Enabled in Schools</div>
          <div class="text-lg font-semibold">{{ getModuleStats(module.key) }} schools</div>
        </div>

        <div class="flex items-center justify-between">
          <span :class="module.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                class="px-2 py-1 text-xs rounded-full">
            {{ module.is_active ? 'Active' : 'Inactive' }}
          </span>
          <button @click="showSchoolsForModule(module)" class="text-primary-600 hover:text-primary-800 text-sm">
            View Schools
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useAppToast } from '@/composables/useToast'

const modules = ref([])
const moduleStats = ref({})
const toast = useAppToast()

const fetchModules = async () => {
  try {
    const response = await api.get('/super-admin/modules')
    modules.value = response.data?.data?.modules || []
    fetchModuleStats()
  } catch (error) {
    console.error('Error fetching modules:', error)
  }
}

const fetchModuleStats = async () => {
  try {
    const response = await api.get('/super-admin/modules-stats')
    const stats = response.data?.data?.stats || []
    stats.forEach(stat => {
      moduleStats.value[stat.key] = stat.enabled_schools
    })
  } catch (error) {
    console.error('Error fetching module stats:', error)
  }
}

const getModuleStats = (moduleKey) => {
  return moduleStats.value[moduleKey] || 0
}

const showSchoolsForModule = (module) => {
  // Navigate to schools view with filter
  toast.info(`Module: ${module.name}\nEnabled in ${getModuleStats(module.key)} schools\n\n(This would open a modal/list of schools with this module enabled)`)
}

onMounted(() => {
  fetchModules()
})
</script>
