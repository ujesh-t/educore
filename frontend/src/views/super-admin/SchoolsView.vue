<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Schools</h1>
        <p class="text-sm text-gray-500 mt-1">Manage schools, subscriptions, and modules</p>
      </div>
      <button @click="openAddModal()" class="btn-primary">
        <span class="mr-2">+</span> Add School
      </button>
    </div>

    <!-- Filters -->
    <div class="card">
      <div class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
          <label class="form-label">Search</label>
          <input v-model="filters.search" @keyup.enter="fetchSchools" type="text" class="form-input" placeholder="Search by name, email, code..." />
        </div>
        <div>
          <label class="form-label">Status</label>
          <select v-model="filters.status" @change="fetchSchools" class="form-input">
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div>
          <label class="form-label">Plan</label>
          <select v-model="filters.plan" @change="fetchSchools" class="form-input">
            <option value="">All Plans</option>
            <option value="free">Free</option>
            <option value="basic">Basic</option>
            <option value="standard">Standard</option>
            <option value="premium">Premium</option>
            <option value="custom">Custom</option>
          </select>
        </div>
        <div class="flex items-end gap-2">
          <button @click="fetchSchools" class="btn-primary">
            <span class="mr-2">🔍</span> Filter
          </button>
          <button @click="resetFilters" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Schools Table -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="table-th">School Name</th>
              <th class="table-th">Code</th>
              <th class="table-th">Email</th>
              <th class="table-th">Plan</th>
              <th class="table-th">Subscription Status</th>
              <th class="table-th">School Status</th>
              <th class="table-th">Created</th>
              <th class="table-th">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="school in schools" :key="school.id" class="hover:bg-gray-50">
              <td class="table-td">
                <div class="font-medium">{{ school.name }}</div>
                <div class="text-xs text-gray-500">{{ school.city }}, {{ school.country }}</div>
              </td>
              <td class="table-td">{{ school.code }}</td>
              <td class="table-td">{{ school.email || '-' }}</td>
              <td class="table-td">
                <span :class="getPlanColor(school.subscription?.plan)" class="px-2 py-1 text-xs rounded-full capitalize font-medium">
                  {{ school.subscription?.plan || 'free' }}
                </span>
              </td>
              <td class="table-td">
                <span v-if="school.subscription" :class="getSubscriptionStatusColor(school.subscription.status)" class="px-2 py-1 text-xs rounded-full capitalize">
                  {{ school.subscription.status }}
                </span>
                <span v-else class="text-gray-400 text-xs">No subscription</span>
              </td>
              <td class="table-td">
                <span :class="school.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                      class="px-2 py-1 text-xs rounded-full">
                  {{ school.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="table-td">{{ formatDate(school.created_at) }}</td>
              <td class="table-td">
                <div class="flex space-x-2">
                  <button @click="openEditModal(school)" class="text-primary-600 hover:text-primary-800 text-sm font-medium">
                    Edit
                  </button>
                  <button @click="openSubscriptionModal(school)" class="text-green-600 hover:text-green-800 text-sm font-medium">
                    Subscription
                  </button>
                  <button @click="toggleSchoolStatus(school)" class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">
                    {{ school.is_active ? 'Deactivate' : 'Activate' }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="schools.length === 0">
              <td colspan="8" class="table-td text-center text-gray-500 py-8">No schools found</td>
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

  <!-- Add/Edit School Modal -->
  <div v-if="showSchoolModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto my-8">
      <div class="p-6 border-b sticky top-0 bg-white z-10">
        <h2 class="text-xl font-bold">{{ isEditMode ? 'Edit School' : 'Add New School' }}</h2>
        <p class="text-sm text-gray-500">{{ isEditMode ? 'Update school information' : 'Create a new school with admin account' }}</p>
      </div>

      <div class="p-6 space-y-6">
        <!-- School Information Section -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4">School Information</h3>
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="form-label">School Name <span class="text-red-500">*</span></label>
              <input v-model="schoolForm.name" type="text" class="form-input" placeholder="e.g., Springfield High School" required />
            </div>

            <div>
              <label class="form-label">Email</label>
              <input v-model="schoolForm.email" type="email" class="form-input" placeholder="school@example.com" />
            </div>

            <div>
              <label class="form-label">Phone</label>
              <input v-model="schoolForm.phone" type="text" class="form-input" placeholder="+1 234 567 8900" />
            </div>

            <div>
              <label class="form-label">Subdomain</label>
              <input v-model="schoolForm.subdomain" type="text" class="form-input" placeholder="springfield" />
              <p class="text-xs text-gray-500 mt-1">For access via subdomain.example.com</p>
            </div>

            <div>
              <label class="form-label">Domain</label>
              <input v-model="schoolForm.domain" type="text" class="form-input" placeholder="school.example.com" />
            </div>

            <div class="col-span-2">
              <label class="form-label">Address</label>
              <textarea v-model="schoolForm.address" class="form-input" rows="2" placeholder="Street address"></textarea>
            </div>

            <div>
              <label class="form-label">City</label>
              <input v-model="schoolForm.city" type="text" class="form-input" placeholder="Springfield" />
            </div>

            <div>
              <label class="form-label">State</label>
              <input v-model="schoolForm.state" type="text" class="form-input" placeholder="Illinois" />
            </div>

            <div>
              <label class="form-label">Country</label>
              <input v-model="schoolForm.country" type="text" class="form-input" placeholder="United States" />
            </div>

            <div>
              <label class="form-label">Timezone</label>
              <select v-model="schoolForm.timezone" class="form-input">
                <option value="Asia/Kolkata">India (IST)</option>
                <option value="UTC">UTC</option>
                <option value="America/New_York">Eastern Time (ET)</option>
                <option value="America/Chicago">Central Time (CT)</option>
                <option value="America/Denver">Mountain Time (MT)</option>
                <option value="America/Los_Angeles">Pacific Time (PT)</option>
                <option value="Europe/London">London (GMT)</option>
                <option value="Europe/Paris">Paris (CET)</option>
                <option value="Asia/Dubai">Dubai (GST)</option>
                <option value="Australia/Sydney">Sydney (AEST)</option>
              </select>
            </div>

            <div>
              <label class="form-label">School Status</label>
              <select v-model="schoolForm.is_active" class="form-input">
                <option :value="true">Active</option>
                <option :value="false">Inactive</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Admin Account Section (only for new schools) -->
        <div v-if="!isEditMode" class="border-t pt-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Admin Account</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Admin Name <span class="text-red-500">*</span></label>
              <input v-model="schoolForm.admin_name" type="text" class="form-input" placeholder="John Doe" required />
            </div>

            <div>
              <label class="form-label">Admin Email <span class="text-red-500">*</span></label>
              <input v-model="schoolForm.admin_email" type="email" class="form-input" placeholder="admin@school.com" required />
            </div>

            <div>
              <label class="form-label">Admin Password <span class="text-red-500">*</span></label>
              <input v-model="schoolForm.admin_password" type="password" class="form-input" placeholder="Min 8 characters" required />
            </div>
          </div>
        </div>

        <!-- Subscription Section -->
        <div class="border-t pt-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Subscription Plan</h3>
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="form-label">Select Plan</label>
              <div class="space-y-2">
                <div v-for="plan in availablePlans" :key="plan.id"
                     @click="schoolForm.subscription_plan_id = plan.id; schoolForm.plan = plan.key"
                     class="cursor-pointer p-4 border-2 rounded-lg transition-all hover:border-gray-300"
                     :class="schoolForm.subscription_plan_id === plan.id ? 'border-primary-500 bg-primary-50' : 'border-gray-200'">
                  <div class="flex justify-between items-start">
                    <div>
                      <div class="font-semibold text-gray-900">{{ plan.name }}</div>
                      <div class="text-sm text-gray-500 mt-1">{{ plan.description || 'No description' }}</div>
                      <div class="text-xs text-gray-500 mt-2">
                        <span class="inline-block bg-gray-100 px-2 py-1 rounded mr-1">{{ plan.modules?.length || 0 }} modules</span>
                        <span v-if="plan.trial_days" class="inline-block bg-green-100 text-green-700 px-2 py-1 rounded">{{ plan.trial_days }} days trial</span>
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="text-xl font-bold text-gray-900">₹{{ plan.price }}</div>
                      <div class="text-sm text-gray-500">/{{ plan.billing_cycle }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <label class="form-label">Billing Cycle</label>
              <select v-model="schoolForm.billing_cycle" class="form-input">
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
                <option value="lifetime">Lifetime</option>
              </select>
            </div>

            <div>
              <label class="form-label">Trial Days</label>
              <input v-model.number="schoolForm.trial_days" type="number" class="form-input" placeholder="0" min="0" max="365" />
            </div>

            <div>
              <label class="form-label">Amount</label>
              <input v-model.number="schoolForm.amount" type="number" class="form-input" placeholder="0.00" min="0" step="0.01" />
            </div>

            <div>
              <label class="form-label">Currency</label>
              <input v-model="schoolForm.currency" type="text" class="form-input" placeholder="INR" maxlength="10" />
            </div>
          </div>
        </div>
      </div>

      <div class="p-6 border-t sticky bottom-0 bg-white flex justify-end space-x-3">
        <button @click="closeSchoolModal" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
          Cancel
        </button>
        <button @click="saveSchool" class="btn-primary" :disabled="isSaving">
          <span v-if="isSaving" class="mr-2">⏳</span>
          {{ isSaving ? 'Saving...' : (isEditMode ? 'Update School' : 'Create School') }}
        </button>
      </div>
    </div>
  </div>

  <!-- Subscription Management Modal -->
  <div v-if="showSubscriptionModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto my-8">
      <div class="p-6 border-b sticky top-0 bg-white z-10">
        <h2 class="text-xl font-bold">Manage Subscription</h2>
        <p class="text-sm text-gray-500">{{ selectedSchool?.name }}</p>
      </div>

      <div v-if="subscriptionData" class="p-6 space-y-6">
        <!-- Current Subscription Info -->
        <div class="grid grid-cols-3 gap-4">
          <div class="p-4 bg-gray-50 rounded-lg">
            <div class="text-sm text-gray-500">Current Plan</div>
            <div class="text-xl font-bold capitalize">{{ subscriptionData.subscription?.plan || 'free' }}</div>
          </div>
          <div class="p-4 bg-gray-50 rounded-lg">
            <div class="text-sm text-gray-500">Status</div>
            <div class="text-xl font-bold capitalize" :class="getSubscriptionStatusColor(subscriptionData.subscription?.status)">{{ subscriptionData.subscription?.status }}</div>
          </div>
          <div class="p-4 bg-gray-50 rounded-lg">
            <div class="text-sm text-gray-500">Expires</div>
            <div class="text-xl font-bold">{{ formatDate(subscriptionData.subscription?.expires_at) }}</div>
          </div>
        </div>

        <!-- Change Plan -->
        <div class="border-t pt-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Change Plan</h3>
          <div class="space-y-2">
            <div v-for="plan in availablePlans" :key="plan.id"
                 @click="subscriptionForm.subscription_plan_id = plan.id"
                 class="cursor-pointer p-4 border-2 rounded-lg transition-all hover:border-gray-300"
                 :class="subscriptionForm.subscription_plan_id === plan.id ? 'border-primary-500 bg-primary-50' : 'border-gray-200'">
              <div class="flex justify-between items-start">
                <div>
                  <div class="font-semibold">{{ plan.name }}</div>
                  <div class="text-sm text-gray-500 mt-1">{{ plan.description || 'No description' }}</div>
                  <div class="text-xs text-gray-500 mt-2">{{ plan.modules?.length || 0 }} modules included</div>
                </div>
                <div class="text-right">
                  <div class="text-lg font-bold">₹{{ plan.price }}</div>
                  <div class="text-sm text-gray-500">/{{ plan.billing_cycle }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Subscription Status -->
        <div class="border-t pt-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Subscription Status</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Status</label>
              <select v-model="subscriptionForm.status" class="form-input">
                <option value="active">Active</option>
                <option value="trial">Trial</option>
                <option value="cancelled">Cancelled</option>
                <option value="expired">Expired</option>
                <option value="past_due">Past Due</option>
              </select>
            </div>
            <div>
              <label class="form-label">Expires At</label>
              <input v-model="subscriptionForm.expires_at" type="date" class="form-input" />
            </div>
          </div>
        </div>

        <!-- Module Management -->
        <div class="border-t pt-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Enabled Modules</h3>
          <div class="grid grid-cols-2 gap-3">
            <div v-for="module in subscriptionData.modules" :key="module.id"
                 @click="toggleModule(module.id)"
                 class="p-3 border-2 rounded-lg cursor-pointer transition-all"
                 :class="module.is_enabled || module.is_core ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <span class="text-lg mr-2">{{ module.icon || '📦' }}</span>
                  <div>
                    <div class="font-medium">{{ module.name }}</div>
                    <div class="text-xs text-gray-500">{{ module.key }}</div>
                  </div>
                </div>
                <div class="flex space-x-1">
                  <span v-if="module.is_core" class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">Core</span>
                  <span v-if="module.is_free" class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">Free</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="p-6 border-t sticky bottom-0 bg-white flex justify-end space-x-3">
        <button @click="closeSubscriptionModal" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
          Cancel
        </button>
        <button @click="cancelSubscription" class="px-4 py-2 text-red-700 bg-red-100 rounded-lg hover:bg-red-200"
                v-if="subscriptionData?.subscription?.status !== 'cancelled'">
          Cancel Subscription
        </button>
        <button @click="saveSubscription" class="btn-primary" :disabled="isSaving">
          <span v-if="isSaving" class="mr-2">⏳</span>
          {{ isSaving ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useAppToast } from '@/composables/useToast'

const toast = useAppToast()
const schools = ref([])
const availablePlans = ref([])
const showSchoolModal = ref(false)
const showSubscriptionModal = ref(false)
const isEditMode = ref(false)
const isSaving = ref(false)
const selectedSchool = ref(null)
const subscriptionData = ref(null)

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

const schoolForm = ref({
  name: '',
  email: '',
  phone: '',
  subdomain: '',
  domain: '',
  address: '',
  city: '',
  state: '',
  country: 'India',
  timezone: 'Asia/Kolkata',
  is_active: true,
  admin_name: '',
  admin_email: '',
  admin_password: '',
  subscription_plan_id: '',
  plan: 'free',
  billing_cycle: 'monthly',
  trial_days: 14,
  amount: 0,
  currency: 'INR',
})

const subscriptionForm = ref({
  subscription_plan_id: '',
  status: 'active',
  expires_at: '',
  modules: [],
})

const fetchSchools = async () => {
  try {
    const params = {
      page: pagination.value.current_page,
      search: filters.value.search || '',
      status: filters.value.status || '',
      plan: filters.value.plan || '',
    }
    const response = await api.get('/super-admin/schools', { params })
    const schoolsData = response.data?.data?.schools
    schools.value = schoolsData?.data || []
    pagination.value = {
      current_page: schoolsData?.current_page || 1,
      last_page: schoolsData?.last_page || 1,
      from: schoolsData?.from || 0,
      to: schoolsData?.to || 0,
      total: schoolsData?.total || 0,
    }
  } catch (error) {
    console.error('Error fetching schools:', error)
    toast.error('Failed to fetch schools')
  }
}

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    plan: '',
  }
  pagination.value.current_page = 1
  fetchSchools()
}

const fetchPlans = async () => {
  try {
    const response = await api.get('/super-admin/plans')
    availablePlans.value = response.data?.data?.plans || []
    if (availablePlans.value.length > 0 && !schoolForm.value.subscription_plan_id) {
      schoolForm.value.subscription_plan_id = availablePlans.value[0].id
      schoolForm.value.plan = availablePlans.value[0].key
    }
  } catch (error) {
    console.error('Error fetching plans:', error)
  }
}

const openAddModal = () => {
  isEditMode.value = false
  selectedSchool.value = null
  schoolForm.value = {
    name: '',
    email: '',
    phone: '',
    subdomain: '',
    domain: '',
    address: '',
    city: '',
    state: '',
    country: 'India',
    timezone: 'Asia/Kolkata',
    is_active: true,
    admin_name: '',
    admin_email: '',
    admin_password: '',
    subscription_plan_id: availablePlans.value[0]?.id || '',
    plan: availablePlans.value[0]?.key || 'free',
    billing_cycle: 'monthly',
    trial_days: 14,
    amount: availablePlans.value[0]?.price || 0,
    currency: 'INR',
  }
  showSchoolModal.value = true
}

const openEditModal = (school) => {
  isEditMode.value = true
  selectedSchool.value = school
  schoolForm.value = {
    name: school.name || '',
    email: school.email || '',
    phone: school.phone || '',
    subdomain: school.subdomain || '',
    domain: school.domain || '',
    address: school.address || '',
    city: school.city || '',
    state: school.state || '',
    country: school.country || '',
    timezone: school.timezone || 'UTC',
    is_active: school.is_active ?? true,
  }
  showSchoolModal.value = true
}

const closeSchoolModal = () => {
  showSchoolModal.value = false
  selectedSchool.value = null
}

const saveSchool = async () => {
  if (!schoolForm.value.name) {
    toast.error('School name is required')
    return
  }

  if (!isEditMode.value) {
    if (!schoolForm.value.admin_name || !schoolForm.value.admin_email || !schoolForm.value.admin_password) {
      toast.error('Admin account details are required for new schools')
      return
    }
  }

  isSaving.value = true
  try {
    if (isEditMode.value) {
      await api.put(`/super-admin/schools/${selectedSchool.value.id}`, schoolForm.value)
      toast.success('School updated successfully')
    } else {
      await api.post('/super-admin/schools', schoolForm.value)
      toast.success('School created successfully')
    }
    closeSchoolModal()
    await fetchSchools()
  } catch (error) {
    console.error('Error saving school:', error)
    toast.error(error.response?.data?.message || 'Failed to save school')
  } finally {
    isSaving.value = false
  }
}

const openSubscriptionModal = async (school) => {
  selectedSchool.value = school
  try {
    const response = await api.get(`/super-admin/subscriptions?school_id=${school.id}`)
    const subs = response.data?.data?.subscriptions?.data || []
    const currentSub = subs[0] || school.subscription

    if (currentSub) {
      const detailResponse = await api.get(`/super-admin/subscriptions/${currentSub.id}`)
      subscriptionData.value = detailResponse.data?.data
      subscriptionForm.value = {
        subscription_plan_id: currentSub.subscription_plan_id || '',
        status: currentSub.status || 'active',
        expires_at: currentSub.expires_at ? currentSub.expires_at.split('T')[0] : '',
        modules: (subscriptionData.value.modules || []).filter(m => m.is_enabled).map(m => m.id),
      }
    } else {
      subscriptionData.value = { subscription: {}, modules: [] }
      subscriptionForm.value = {
        subscription_plan_id: '',
        status: 'active',
        expires_at: '',
        modules: [],
      }
    }
  } catch (error) {
    console.error('Error fetching subscription:', error)
    subscriptionData.value = { subscription: {}, modules: [] }
  }
  showSubscriptionModal.value = true
}

const closeSubscriptionModal = () => {
  showSubscriptionModal.value = false
  selectedSchool.value = null
  subscriptionData.value = null
}

const toggleModule = (moduleId) => {
  const module = subscriptionData.value.modules.find(m => m.id === moduleId)
  if (!module || module.is_core) return

  module.is_enabled = !module.is_enabled
  if (module.is_enabled) {
    subscriptionForm.value.modules.push(moduleId)
  } else {
    subscriptionForm.value.modules = subscriptionForm.value.modules.filter(id => id !== moduleId)
  }
}

const saveSubscription = async () => {
  if (!subscriptionData.value?.subscription?.id) {
    toast.error('No subscription found for this school')
    return
  }

  isSaving.value = true
  try {
    const subscriptionId = subscriptionData.value.subscription.id

    if (subscriptionForm.value.subscription_plan_id) {
      await api.post(`/super-admin/subscriptions/${subscriptionId}/change-plan`, {
        subscription_plan_id: subscriptionForm.value.subscription_plan_id,
      })
    }

    await api.put(`/super-admin/subscriptions/${subscriptionId}`, {
      status: subscriptionForm.value.status,
      expires_at: subscriptionForm.value.expires_at,
    })

    if (subscriptionForm.value.modules.length > 0) {
      await api.post(`/super-admin/subscriptions/${subscriptionId}/modules`, {
        modules: subscriptionForm.value.modules,
      })
    }

    toast.success('Subscription updated successfully')
    closeSubscriptionModal()
    await fetchSchools()
  } catch (error) {
    console.error('Error updating subscription:', error)
    toast.error(error.response?.data?.message || 'Failed to update subscription')
  } finally {
    isSaving.value = false
  }
}

const cancelSubscription = async () => {
  if (!window.confirm('Are you sure you want to cancel this subscription?')) return

  isSaving.value = true
  try {
    const subscriptionId = subscriptionData.value.subscription.id
    await api.post(`/super-admin/subscriptions/${subscriptionId}/cancel`)
    toast.success('Subscription cancelled successfully')
    closeSubscriptionModal()
    await fetchSchools()
  } catch (error) {
    console.error('Error cancelling subscription:', error)
    toast.error(error.response?.data?.message || 'Failed to cancel subscription')
  } finally {
    isSaving.value = false
  }
}

const toggleSchoolStatus = async (school) => {
  if (!window.confirm(`Are you sure you want to ${school.is_active ? 'deactivate' : 'activate'} this school?`)) return

  try {
    await api.post(`/super-admin/schools/${school.id}/toggle-status`)
    toast.success('School status updated')
    await fetchSchools()
  } catch (error) {
    console.error('Error toggling school status:', error)
    toast.error('Failed to update school status')
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    pagination.value.current_page = page
    fetchSchools()
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

const getPlanColor = (plan) => {
  const colors = {
    free: 'bg-gray-100 text-gray-700',
    basic: 'bg-blue-100 text-blue-700',
    standard: 'bg-green-100 text-green-700',
    premium: 'bg-purple-100 text-purple-700',
    custom: 'bg-orange-100 text-orange-700',
  }
  return colors[plan] || colors.free
}

const getSubscriptionStatusColor = (status) => {
  const colors = {
    active: 'text-green-600',
    trial: 'text-blue-600',
    cancelled: 'text-red-600',
    expired: 'text-red-600',
    past_due: 'text-yellow-600',
  }
  return colors[status] || 'text-gray-600'
}

onMounted(() => {
  fetchSchools()
  fetchPlans()
})
</script>
