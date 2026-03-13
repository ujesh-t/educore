<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Subscriptions</h1>
        <p class="text-sm text-gray-500 mt-1">Manage school subscriptions and plans</p>
      </div>
      <button @click="showCreateModal = true" class="btn-primary">
        <span class="mr-2">+</span> New Subscription
      </button>
    </div>

    <!-- Filters -->
    <div class="card">
      <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
          <label class="form-label">Search School</label>
          <input v-model="filters.search" type="text" class="form-input" placeholder="Search by school name..." />
        </div>
        <div>
          <label class="form-label">Status</label>
          <select v-model="filters.status" class="form-input">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="trial">Trial</option>
            <option value="cancelled">Cancelled</option>
            <option value="expired">Expired</option>
            <option value="past_due">Past Due</option>
          </select>
        </div>
        <div>
          <label class="form-label">Plan</label>
          <select v-model="filters.plan" class="form-input">
            <option value="">All Plans</option>
            <option v-for="plan in availablePlans" :key="plan.key" :value="plan.key">
              {{ plan.name }}
            </option>
          </select>
        </div>
        <div class="flex items-end">
          <button @click="fetchSubscriptions" class="btn-primary">
            <span class="mr-2">🔍</span> Filter
          </button>
        </div>
      </div>
    </div>

    <!-- Subscriptions Table -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="table-th">School</th>
              <th class="table-th">Plan</th>
              <th class="table-th">Status</th>
              <th class="table-th">Amount</th>
              <th class="table-th">Billing Cycle</th>
              <th class="table-th">Expires</th>
              <th class="table-th">Days Left</th>
              <th class="table-th">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="sub in subscriptions" :key="sub.id" 
                class="hover:bg-gray-50 cursor-pointer"
                @click="viewDetails(sub.id)">
              <td class="table-td">
                <div>
                  <div class="font-medium">{{ sub.school?.name }}</div>
                  <div class="text-xs text-gray-500">{{ sub.school?.email }}</div>
                </div>
              </td>
              <td class="table-td">
                <span class="px-2 py-1 text-xs rounded-full bg-primary-100 text-primary-700 capitalize">
                  {{ sub.planModel?.name || sub.plan }}
                </span>
              </td>
              <td class="table-td">
                <span :class="{
                  'bg-green-100 text-green-700': sub.status === 'active',
                  'bg-blue-100 text-blue-700': sub.status === 'trial',
                  'bg-red-100 text-red-700': sub.status === 'expired' || sub.status === 'cancelled',
                  'bg-yellow-100 text-yellow-700': sub.status === 'past_due'
                }" class="px-2 py-1 text-xs rounded-full capitalize font-medium">
                  {{ sub.status }}
                </span>
              </td>
              <td class="table-td">{{ sub.currency }} ₹{{ sub.amount }}</td>
              <td class="table-td capitalize">{{ sub.billing_cycle }}</td>
              <td class="table-td">{{ formatDate(sub.expires_at) }}</td>
              <td class="table-td">
                <span v-if="sub.expires_at" :class="getDaysLeftColor(sub.expires_at)" class="font-medium">
                  {{ getDaysLeft(sub.expires_at) }} days
                </span>
                <span v-else class="text-gray-500">Lifetime</span>
              </td>
              <td class="table-td" @click.stop>
                <button @click="viewDetails(sub.id)" class="text-primary-600 hover:text-primary-800 font-medium">
                  View →
                </button>
              </td>
            </tr>
            <tr v-if="subscriptions.length === 0">
              <td colspan="8" class="table-td text-center text-gray-500 py-8">
                No subscriptions found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="p-4 border-t flex justify-between items-center">
        <div class="text-sm text-gray-500">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
        </div>
        <div class="flex space-x-2">
          <button @click="changePage(pagination.current_page - 1)" 
                  :disabled="pagination.current_page <= 1"
                  class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
            Previous
          </button>
          <button v-for="page in pagination.last_page" :key="page"
                  @click="changePage(page)"
                  :class="page === pagination.current_page ? 'bg-primary-600 text-white' : 'hover:bg-gray-50'"
                  class="px-3 py-1 border rounded">
            {{ page }}
          </button>
          <button @click="changePage(pagination.current_page + 1)" 
                  :disabled="pagination.current_page >= pagination.last_page"
                  class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
            Next
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Create Subscription Modal -->
  <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Create New Subscription</h2>
        <p class="text-sm text-gray-500">Set up a subscription for a school</p>
      </div>

      <div class="p-6 space-y-4">
        <!-- School Selection -->
        <div>
          <label class="form-label">School</label>
          <select v-model="formData.school_id" class="form-input">
            <option value="">Select a school...</option>
            <option v-for="school in schools" :key="school.id" :value="school.id">
              {{ school.name }} ({{ school.email }})
            </option>
          </select>
        </div>

        <!-- Plan Selection -->
        <div>
          <label class="form-label">Subscription Plan</label>
          <div class="space-y-2">
            <div v-for="plan in availablePlans" :key="plan.id"
                 @click="formData.subscription_plan_id = plan.id"
                 class="cursor-pointer p-3 border-2 rounded-lg transition-all"
                 :class="formData.subscription_plan_id === plan.id ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'">
              <div class="flex justify-between items-start">
                <div>
                  <div class="font-semibold">{{ plan.name }}</div>
                  <div class="text-sm text-gray-500">{{ plan.description || 'No description' }}</div>
                  <div class="text-xs text-gray-500 mt-1">{{ plan.modules?.length || 0 }} modules included</div>
                </div>
                <div class="text-right">
                  <div class="text-lg font-bold">₹{{ plan.price }}</div>
                  <div class="text-sm text-gray-500">/{{ plan.billing_cycle }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Status -->
        <div>
          <label class="form-label">Status</label>
          <select v-model="formData.status" class="form-input">
            <option value="active">Active</option>
            <option value="trial">Trial</option>
            <option value="cancelled">Cancelled</option>
            <option value="expired">Expired</option>
            <option value="past_due">Past Due</option>
          </select>
        </div>

        <!-- Trial Days -->
        <div>
          <label class="form-label">Trial Days</label>
          <input v-model.number="formData.trial_days" type="number" class="form-input" placeholder="0" min="0" />
          <p class="text-xs text-gray-500 mt-1">Number of days for free trial (0 = no trial)</p>
        </div>

        <!-- Custom Amount -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Amount (INR)</label>
            <input v-model.number="formData.amount" type="number" class="form-input" placeholder="0.00" min="0" step="0.01" />
          </div>
          <div>
            <label class="form-label">Currency</label>
            <input v-model="formData.currency" type="text" class="form-input" placeholder="INR" maxlength="10" />
          </div>
        </div>
      </div>

      <div class="p-6 border-t flex justify-end space-x-3">
        <button @click="closeCreateModal" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
          Cancel
        </button>
        <button @click="createSubscription" class="btn-primary">
          Create Subscription
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
const subscriptions = ref([])
const schools = ref([])
const availablePlans = ref([])
const showCreateModal = ref(false)

const filters = ref({
  search: '',
  status: '',
  plan: '',
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
})

const formData = ref({
  school_id: '',
  subscription_plan_id: '',
  status: 'active',
  trial_days: 0,
  amount: 0,
  currency: 'INR',
})

const fetchSubscriptions = async () => {
  try {
    // Only include non-empty filters
    const params = {}
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.plan) params.plan = filters.value.plan
    params.page = pagination.value.current_page
    
    const response = await api.get('/super-admin/subscriptions', { params })
    console.log('Subscriptions response:', response.data)
    const subsData = response.data?.data?.subscriptions
    subscriptions.value = subsData?.data || []
    console.log('Subscriptions assigned:', subscriptions.value.length)
    pagination.value = {
      current_page: subsData?.current_page || 1,
      last_page: subsData?.last_page || 1,
      from: subsData?.from || 0,
      to: subsData?.to || 0,
      total: subsData?.total || 0,
    }
  } catch (error) {
    console.error('Error fetching subscriptions:', error)
  }
}

const fetchSchools = async () => {
  try {
    const response = await api.get('/super-admin/schools')
    // Handle paginated response
    const schoolsData = response.data?.data?.schools
    schools.value = schoolsData?.data || schoolsData || []
  } catch (error) {
    console.error('Error fetching schools:', error)
  }
}

const fetchPlans = async () => {
  try {
    const response = await api.get('/super-admin/plans')
    availablePlans.value = response.data?.data?.plans || []
  } catch (error) {
    console.error('Error fetching plans:', error)
  }
}

const viewDetails = (id) => {
  router.push(`/super-admin/subscriptions/${id}`)
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    pagination.value.current_page = page
    fetchSubscriptions()
  }
}

const createSubscription = async () => {
  if (!formData.value.school_id || !formData.value.subscription_plan_id) {
    toast.error('Please select a school and plan')
    return
  }

  try {
    await api.post('/super-admin/subscriptions', formData.value)
    toast.success('Subscription created successfully')
    closeCreateModal()
    await fetchSubscriptions()
  } catch (error) {
    console.error('Error creating subscription:', error)
    toast.error(error.response?.data?.message || 'Failed to create subscription')
  }
}

const closeCreateModal = () => {
  showCreateModal.value = false
  formData.value = {
    school_id: '',
    subscription_plan_id: '',
    status: 'active',
    trial_days: 0,
    amount: 0,
    currency: 'USD',
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'Lifetime'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getDaysLeft = (expiresAt) => {
  if (!expiresAt) return null
  const now = new Date()
  const expires = new Date(expiresAt)
  const diff = expires - now
  return Math.max(0, Math.floor(diff / (1000 * 60 * 60 * 24)))
}

const getDaysLeftColor = (expiresAt) => {
  const days = getDaysLeft(expiresAt)
  if (days === null) return 'text-gray-500'
  if (days <= 7) return 'text-red-600'
  if (days <= 30) return 'text-yellow-600'
  return 'text-green-600'
}

onMounted(() => {
  fetchSubscriptions()
  fetchSchools()
  fetchPlans()
})
</script>
