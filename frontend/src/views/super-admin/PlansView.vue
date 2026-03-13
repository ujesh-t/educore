<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Subscription Plans</h1>
        <p class="text-sm text-gray-500 mt-1">Create and manage subscription plans with custom modules</p>
      </div>
      <button @click="showCreateModal = true" class="btn-primary">
        <span class="mr-2">+</span> Create Plan
      </button>
    </div>

    <!-- Info Banner -->
    <div class="card bg-blue-50 border-blue-200">
      <div class="flex items-start space-x-3">
        <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="text-sm text-blue-800">
          <p class="font-semibold mb-1">Free Modules</p>
          <p>Modules marked as "Free Modules" are automatically included in all plans. Use the 
            <a href="/super-admin/modules" class="underline font-medium hover:text-blue-900">Modules page</a> 
            to mark modules as free. After creating a plan, click "Manage Modules" to assign additional modules.</p>
        </div>
      </div>
    </div>

    <!-- Plans Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="plan in sortedPlans" :key="plan.id" 
           class="card relative"
           :class="[
             plan.key === 'premium' ? 'border-2 border-yellow-300 bg-gradient-to-br from-yellow-50 to-white' :
             plan.key === 'standard' ? 'border-2 border-primary-200' :
             plan.key === 'basic' ? 'border-2 border-primary-200' :
             plan.is_custom ? 'border-2 border-purple-200' :
             'border-2 border-gray-200'
           ]">
        
        <!-- Edit/Delete/Manage Modules for custom plans -->
        <div v-if="plan.is_custom" class="absolute top-2 right-2 flex space-x-1">
          <button @click="manageModules(plan)" class="p-1 text-primary-600 hover:text-primary-800" title="Manage Modules">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </button>
          <button @click="editPlan(plan)" class="p-1 text-gray-400 hover:text-primary-600" title="Edit">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
          </button>
          <button @click="deletePlan(plan)" class="p-1 text-gray-400 hover:text-red-600" title="Delete">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>

        <!-- Plan Header -->
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-xl font-bold text-gray-900">{{ plan.name }}</h3>
            <p class="text-sm text-gray-500">{{ plan.description || (plan.key ? predefinedDescriptions[plan.key] : 'Custom plan') }}</p>
          </div>
          <span v-if="!plan.is_custom" class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded-full">System</span>
          <span v-else-if="plan.key === 'premium'" class="px-3 py-1 text-xs bg-yellow-400 text-yellow-900 rounded-full">★ Premium</span>
          <span v-else-if="plan.key === 'standard'" class="px-3 py-1 text-xs bg-green-100 text-green-700 rounded-full">Recommended</span>
          <span v-else-if="plan.key === 'basic'" class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">Popular</span>
        </div>

        <!-- Price -->
        <div class="mb-4">
          <span class="text-3xl font-bold">₹{{ plan.price }}</span>
          <span class="text-gray-500">/{{ plan.billing_cycle }}</span>
        </div>

        <!-- Modules -->
        <div class="mb-4">
          <h4 class="text-sm font-semibold mb-2">Included Modules ({{ plan.modules?.length || 0 }}):</h4>
          <div class="flex flex-wrap gap-2">
            <span v-for="mod in plan.modules" :key="mod" class="px-2 py-1 text-xs rounded"
                  :class="plan.is_custom ? 'bg-purple-50 text-purple-700' : 'bg-primary-50 text-primary-700'">
              {{ getModuleName(mod) }}
            </span>
          </div>
        </div>

        <!-- Trial -->
        <div v-if="plan.trial_days > 0" class="mb-4 text-sm">
          <span class="text-green-600 font-medium">✓ {{ plan.trial_days }} days free trial</span>
        </div>

        <!-- Schools Count -->
        <div class="text-sm text-gray-500">
          {{ plan.active_schools_count || 0 }} schools using this plan
        </div>
      </div>
    </div>
  </div>

  <!-- Create/Edit Plan Modal -->
  <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">{{ editingPlan ? 'Edit Plan' : 'Create New Plan' }}</h2>
        <p class="text-sm text-gray-500">Define pricing and details for this plan</p>
      </div>

      <div class="p-6 space-y-4">
        <!-- Info Note -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
          <p class="font-medium mb-1">Free Modules Included Automatically</p>
          <p>All plans automatically include modules marked as "Free Modules". After creating this plan, you can assign additional modules using the "Manage Modules" option.</p>
        </div>

        <!-- Plan Name -->
        <div>
          <label class="form-label">Plan Name</label>
          <input v-model="formData.name" type="text" class="form-input" placeholder="e.g., Enterprise, Startup" />
        </div>

        <!-- Description -->
        <div>
          <label class="form-label">Description</label>
          <textarea v-model="formData.description" class="form-input" rows="2" placeholder="Brief description of this plan"></textarea>
        </div>

        <!-- Pricing -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Price (INR)</label>
            <input v-model.number="formData.price" type="number" class="form-input" placeholder="0.00" min="0" step="0.01" />
          </div>
          <div>
            <label class="form-label">Billing Cycle</label>
            <select v-model="formData.billing_cycle" class="form-input">
              <option value="monthly">Monthly</option>
              <option value="yearly">Yearly</option>
              <option value="lifetime">Lifetime</option>
            </select>
          </div>
        </div>

        <!-- Trial Days -->
        <div>
          <label class="form-label">Free Trial (Days)</label>
          <input v-model.number="formData.trial_days" type="number" class="form-input" placeholder="0" min="0" />
          <p class="text-xs text-gray-500 mt-1">Number of days for free trial (0 = no trial)</p>
        </div>
      </div>

      <div class="p-6 border-t flex justify-end space-x-3">
        <button @click="closeModal" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
          Cancel
        </button>
        <button @click="savePlan" class="btn-primary">
          {{ editingPlan ? 'Update Plan' : 'Create Plan' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useAppToast } from '@/composables/useToast'

const router = useRouter()
const toast = useAppToast()
const plans = ref([])
const modules = ref([])
const showCreateModal = ref(false)
const editingPlan = ref(null)

const formData = ref({
  name: '',
  description: '',
  price: 0,
  currency: 'INR',
  billing_cycle: 'monthly',
  trial_days: 0,
})

const predefinedDescriptions = {
  free: 'Starter plan for small schools',
  basic: 'Essential features for growing schools',
  standard: 'Advanced features for established schools',
  premium: 'All features for large institutions',
}

const sortedPlans = computed(() => {
  // Sort: predefined plans first (by key order), then custom plans
  const keyOrder = { free: 1, basic: 2, standard: 3, premium: 4 }
  return [...plans.value].sort((a, b) => {
    // Non-custom plans first, sorted by key order
    if (!a.is_custom && !b.is_custom) {
      return (keyOrder[a.key] || 99) - (keyOrder[b.key] || 99)
    }
    // Custom plans after predefined
    if (a.is_custom && !b.is_custom) return 1
    if (!a.is_custom && b.is_custom) return -1
    // Custom plans sorted by name
    return a.name.localeCompare(b.name)
  })
})

const fetchPlans = async () => {
  try {
    const response = await api.get('/super-admin/plans')
    plans.value = response.data?.data?.plans || []
  } catch (error) {
    console.error('Error fetching plans:', error)
  }
}

const fetchModules = async () => {
  try {
    const response = await api.get('/super-admin/modules')
    modules.value = response.data?.data?.modules || []
  } catch (error) {
    console.error('Error fetching modules:', error)
  }
}

const getModuleName = (moduleKey) => {
  const module = modules.value.find(m => m.key === moduleKey)
  return module ? module.name : moduleKey
}

const editPlan = (plan) => {
  editingPlan.value = plan
  formData.value = {
    name: plan.name,
    description: plan.description || '',
    price: plan.price,
    billing_cycle: plan.billing_cycle,
    modules: plan.modules || [],
    trial_days: plan.trial_days || 0,
  }
  showCreateModal.value = true
}

const deletePlan = async (plan) => {
  if (!window.confirm(`Are you sure you want to delete the "${plan.name}" plan?`)) return

  try {
    await api.delete(`/super-admin/plans/${plan.id}`)
    await fetchPlans()
    toast.success('Plan deleted successfully')
  } catch (error) {
    console.error('Error deleting plan:', error)
    toast.error(error.response?.data?.message || 'Failed to delete plan')
  }
}

const savePlan = async () => {
  if (!formData.value.name) {
    toast.error('Please provide a plan name')
    return
  }

  try {
    const payload = {
      ...formData.value,
      is_custom: true,
      modules: [], // Empty - modules will be assigned via Manage Modules page
    }

    if (editingPlan.value) {
      await api.put(`/super-admin/plans/${editingPlan.value.id}`, payload)
    } else {
      await api.post('/super-admin/plans', payload)
    }

    closeModal()
    await fetchPlans()
    toast.success(editingPlan.value ? 'Plan updated successfully' : 'Plan created successfully')
  } catch (error) {
    console.error('Error saving plan:', error)
    toast.error('Failed to save plan')
  }
}

const manageModules = (plan) => {
  router.push(`/super-admin/plans/${plan.id}/modules`)
}

const closeModal = () => {
  showCreateModal.value = false
  editingPlan.value = null
  formData.value = {
    name: '',
    description: '',
    price: 0,
    billing_cycle: 'monthly',
    trial_days: 0,
  }
}

onMounted(() => {
  fetchPlans()
  fetchModules()
})
</script>
