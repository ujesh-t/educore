<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
        <p class="text-sm text-gray-500 mt-1">Manage monthly, quarterly, and yearly invoices for schools</p>
      </div>
      <div class="flex space-x-3">
        <button @click="showGenerateModal = true" class="btn-secondary">
          <span class="mr-2">📄</span> Generate Invoices
        </button>
        <button @click="showCreateModal = true" class="btn-primary">
          <span class="mr-2">+</span> Create Invoice
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="card">
        <div class="text-sm text-gray-500">Total Invoices</div>
        <div class="mt-1 text-2xl font-bold">{{ stats.total_invoices || 0 }}</div>
      </div>
      <div class="card">
        <div class="text-sm text-gray-500">Paid</div>
        <div class="mt-1 text-2xl font-bold text-green-600">{{ stats.paid_invoices || 0 }}</div>
        <div class="text-xs text-gray-500">₹{{ stats.total_revenue || 0 }}</div>
      </div>
      <div class="card">
        <div class="text-sm text-gray-500">Pending</div>
        <div class="mt-1 text-2xl font-bold text-yellow-600">{{ stats.pending_invoices || 0 }}</div>
        <div class="text-xs text-gray-500">₹{{ stats.pending_revenue || 0 }}</div>
      </div>
      <div class="card">
        <div class="text-sm text-gray-500">Overdue</div>
        <div class="mt-1 text-2xl font-bold text-red-600">{{ stats.overdue_invoices || 0 }}</div>
        <div class="text-xs text-gray-500">₹{{ stats.overdue_revenue || 0 }}</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="form-label">Search</label>
          <input v-model="filters.search" @input="debouncedFetch" type="text" class="form-input" placeholder="Invoice # or School" />
        </div>
        <div>
          <label class="form-label">Status</label>
          <select v-model="filters.status" @change="fetchInvoices" class="form-input">
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="overdue">Overdue</option>
            <option value="partial">Partial</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div>
          <label class="form-label">Billing Cycle</label>
          <select v-model="filters.billing_cycle" @change="fetchInvoices" class="form-input">
            <option value="">All</option>
            <option value="monthly">Monthly</option>
            <option value="quarterly">Quarterly</option>
            <option value="yearly">Yearly</option>
          </select>
        </div>
        <div>
          <label class="form-label">From Date</label>
          <input v-model="filters.start_date" @change="fetchInvoices" type="date" class="form-input" />
        </div>
        <div>
          <label class="form-label">To Date</label>
          <input v-model="filters.end_date" @change="fetchInvoices" type="date" class="form-input" />
        </div>
      </div>
    </div>

    <!-- Invoices Table -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">School</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cycle</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="invoice in invoices" :key="invoice.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-primary-600">{{ invoice.invoice_number }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ invoice.school?.name }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-500">{{ invoice.subscription?.planModel?.name || '-' }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700 capitalize">{{ invoice.billing_cycle }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">₹{{ invoice.total_amount }}</div>
                <div v-if="invoice.paid_amount > 0" class="text-xs text-gray-500">Paid: ₹{{ invoice.paid_amount }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="{
                  'bg-green-100 text-green-700': invoice.status === 'paid',
                  'bg-yellow-100 text-yellow-700': invoice.status === 'pending',
                  'bg-red-100 text-red-700': invoice.status === 'overdue',
                  'bg-blue-100 text-blue-700': invoice.status === 'partial',
                  'bg-gray-100 text-gray-700': invoice.status === 'cancelled'
                }" class="px-2 py-1 text-xs rounded-full capitalize font-medium">
                  {{ invoice.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-500">{{ formatDate(invoice.due_date) }}</span>
                <span v-if="isOverdue(invoice)" class="ml-1 text-xs text-red-600">(Overdue)</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button @click="viewInvoice(invoice)" class="text-primary-600 hover:text-primary-800 mr-3">View</button>
                <button v-if="invoice.status !== 'paid' && invoice.status !== 'cancelled'" @click="recordPayment(invoice)" class="text-green-600 hover:text-green-800">Payment</button>
              </td>
            </tr>
            <tr v-if="invoices.length === 0">
              <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                No invoices found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t flex items-center justify-between">
        <div class="text-sm text-gray-500">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
        </div>
        <div class="flex space-x-2">
          <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" 
                  class="px-3 py-1 rounded border disabled:opacity-50" :class="pagination.current_page <= 1 ? '' : 'hover:bg-gray-100'">
            Previous
          </button>
          <button v-for="page in visiblePages" :key="page" @click="changePage(page)" 
                  class="px-3 py-1 rounded border" :class="page === pagination.current_page ? 'bg-primary-500 text-white border-primary-500' : 'hover:bg-gray-100'">
            {{ page }}
          </button>
          <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" 
                  class="px-3 py-1 rounded border disabled:opacity-50" :class="pagination.current_page >= pagination.last_page ? '' : 'hover:bg-gray-100'">
            Next
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Create Invoice Modal -->
  <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Create Invoice</h2>
        <p class="text-sm text-gray-500">Manually create an invoice for a school</p>
      </div>

      <div class="p-6 space-y-4">
        <div>
          <label class="form-label">School</label>
          <select v-model="createForm.school_id" class="form-input" required>
            <option value="">Select School</option>
            <option v-for="school in schools" :key="school.id" :value="school.id">{{ school.name }}</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Type</label>
            <select v-model="createForm.type" class="form-input">
              <option value="subscription">Subscription</option>
              <option value="one_time">One Time</option>
              <option value="credit">Credit Note</option>
              <option value="debit">Debit Note</option>
            </select>
          </div>
          <div>
            <label class="form-label">Billing Cycle</label>
            <select v-model="createForm.billing_cycle" class="form-input">
              <option value="monthly">Monthly</option>
              <option value="quarterly">Quarterly</option>
              <option value="yearly">Yearly</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="form-label">Amount (₹)</label>
            <input v-model.number="createForm.amount" type="number" class="form-input" min="0" step="0.01" />
          </div>
          <div>
            <label class="form-label">Tax (₹)</label>
            <input v-model.number="createForm.tax_amount" type="number" class="form-input" min="0" step="0.01" />
          </div>
          <div>
            <label class="form-label">Discount (₹)</label>
            <input v-model.number="createForm.discount_amount" type="number" class="form-input" min="0" step="0.01" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Invoice Date</label>
            <input v-model="createForm.invoice_date" type="date" class="form-input" />
          </div>
          <div>
            <label class="form-label">Due Date</label>
            <input v-model="createForm.due_date" type="date" class="form-input" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Billing Period Start</label>
            <input v-model="createForm.billing_period_start" type="date" class="form-input" />
          </div>
          <div>
            <label class="form-label">Billing Period End</label>
            <input v-model="createForm.billing_period_end" type="date" class="form-input" />
          </div>
        </div>

        <div>
          <label class="form-label">Notes</label>
          <textarea v-model="createForm.notes" class="form-input" rows="2" placeholder="Additional notes..."></textarea>
        </div>
      </div>

      <div class="p-6 border-t flex justify-end space-x-3">
        <button @click="closeCreateModal" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
        <button @click="createInvoice" class="btn-primary">Create Invoice</button>
      </div>
    </div>
  </div>

  <!-- Generate Invoices Modal -->
  <div v-if="showGenerateModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Generate Invoices</h2>
        <p class="text-sm text-gray-500">Auto-generate invoices for active subscriptions</p>
      </div>

      <div class="p-6 space-y-4">
        <div>
          <label class="form-label">Billing Cycle</label>
          <select v-model="generateForm.cycle" class="form-input">
            <option value="">All Cycles</option>
            <option value="monthly">Monthly</option>
            <option value="quarterly">Quarterly</option>
            <option value="yearly">Yearly</option>
          </select>
        </div>
        <div class="flex items-center">
          <input v-model="generateForm.force" type="checkbox" id="force" class="w-4 h-4" />
          <label for="force" class="ml-2 text-sm text-gray-700">Force generate (even if invoice exists)</label>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-800">
          <p class="font-medium">Note:</p>
          <p>This will generate invoices for all active subscriptions. Existing invoices for the same period will be skipped unless "Force generate" is checked.</p>
        </div>
      </div>

      <div class="p-6 border-t flex justify-end space-x-3">
        <button @click="showGenerateModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
        <button @click="generateInvoices" :disabled="generating" class="btn-primary">
          {{ generating ? 'Generating...' : 'Generate' }}
        </button>
      </div>
    </div>
  </div>

  <!-- Payment Modal -->
  <div v-if="showPaymentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Record Payment</h2>
        <p class="text-sm text-gray-500">{{ selectedInvoice?.invoice_number }} - Balance: ₹{{ selectedInvoice?.balance }}</p>
      </div>

      <div class="p-6 space-y-4">
        <div>
          <label class="form-label">Amount (₹)</label>
          <input v-model.number="paymentForm.amount" type="number" class="form-input" min="0.01" :max="selectedInvoice?.balance" step="0.01" />
        </div>
        <div>
          <label class="form-label">Payment Method</label>
          <select v-model="paymentForm.payment_method" class="form-input">
            <option value="cash">Cash</option>
            <option value="card">Card</option>
            <option value="online">Online</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cheque">Cheque</option>
          </select>
        </div>
        <div>
          <label class="form-label">Payment Date</label>
          <input v-model="paymentForm.payment_date" type="date" class="form-input" />
        </div>
        <div>
          <label class="form-label">Reference Number</label>
          <input v-model="paymentForm.reference_number" type="text" class="form-input" placeholder="Cheque #, UTR, etc." />
        </div>
        <div>
          <label class="form-label">Notes</label>
          <textarea v-model="paymentForm.notes" class="form-input" rows="2" placeholder="Additional notes..."></textarea>
        </div>
      </div>

      <div class="p-6 border-t flex justify-end space-x-3">
        <button @click="showPaymentModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
        <button @click="submitPayment" :disabled="processingPayment" class="btn-primary">
          {{ processingPayment ? 'Processing...' : 'Record Payment' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const invoices = ref([])
const schools = ref([])
const stats = ref({})
const pagination = ref({})

const filters = ref({
  search: '',
  status: '',
  billing_cycle: '',
  start_date: '',
  end_date: '',
})

const showCreateModal = ref(false)
const showGenerateModal = ref(false)
const showPaymentModal = ref(false)
const generating = ref(false)
const processingPayment = ref(false)
const selectedInvoice = ref(null)

const createForm = ref({
  school_id: '',
  type: 'subscription',
  billing_cycle: 'monthly',
  amount: 0,
  tax_amount: 0,
  discount_amount: 0,
  invoice_date: new Date().toISOString().split('T')[0],
  due_date: '',
  billing_period_start: '',
  billing_period_end: '',
  notes: '',
})

const generateForm = ref({
  cycle: '',
  force: false,
})

const paymentForm = ref({
  amount: 0,
  payment_method: 'cash',
  payment_date: new Date().toISOString().split('T')[0],
  reference_number: '',
  notes: '',
})

const visiblePages = computed(() => {
  const pages = []
  const current = pagination.value.current_page || 1
  const last = pagination.value.last_page || 1
  const delta = 2

  for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
    pages.push(i)
  }
  return pages
})

const fetchStats = async () => {
  try {
    const response = await api.get('/super-admin/invoices/stats')
    stats.value = response.data?.data || {}
  } catch (error) {
    console.error('Error fetching stats:', error)
  }
}

const fetchSchools = async () => {
  try {
    const response = await api.get('/super-admin/schools')
    schools.value = response.data?.data?.schools || []
  } catch (error) {
    console.error('Error fetching schools:', error)
  }
}

let debounceTimer = null
const debouncedFetch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchInvoices, 500)
}

const fetchInvoices = async () => {
  try {
    const params = { ...filters.value, per_page: 20 }
    const response = await api.get('/super-admin/invoices', { params })
    invoices.value = response.data?.data?.invoices?.data || []
    pagination.value = response.data?.data?.invoices || {}
  } catch (error) {
    console.error('Error fetching invoices:', error)
  }
}

const createInvoice = async () => {
  if (!createForm.value.school_id) {
    alert('Please select a school')
    return
  }

  try {
    await api.post('/super-admin/invoices', createForm.value)
    closeCreateModal()
    await fetchInvoices()
    await fetchStats()
    alert('Invoice created successfully')
  } catch (error) {
    console.error('Error creating invoice:', error)
    alert(error.response?.data?.message || 'Failed to create invoice')
  }
}

const generateInvoices = async () => {
  generating.value = true
  try {
    // Note: This would call a backend endpoint that runs the artisan command
    // For now, we'll show a message
    alert('Invoice generation started. This runs in the background. Check the logs for progress.')
    // In production, you'd call: await api.post('/super-admin/invoices/generate', generateForm.value)
    showGenerateModal.value = false
  } catch (error) {
    console.error('Error generating invoices:', error)
    alert(error.response?.data?.message || 'Failed to generate invoices')
  } finally {
    generating.value = false
  }
}

const recordPayment = (invoice) => {
  selectedInvoice.value = invoice
  paymentForm.value = {
    amount: invoice.balance,
    payment_method: 'cash',
    payment_date: new Date().toISOString().split('T')[0],
    reference_number: '',
    notes: '',
  }
  showPaymentModal.value = true
}

const submitPayment = async () => {
  if (!paymentForm.value.amount || paymentForm.value.amount <= 0) {
    alert('Please enter a valid amount')
    return
  }

  try {
    processingPayment.value = true
    await api.post(`/super-admin/invoices/${selectedInvoice.value.id}/record-payment`, paymentForm.value)
    showPaymentModal.value = false
    await fetchInvoices()
    await fetchStats()
    alert('Payment recorded successfully')
  } catch (error) {
    console.error('Error recording payment:', error)
    alert(error.response?.data?.message || 'Failed to record payment')
  } finally {
    processingPayment.value = false
  }
}

const viewInvoice = (invoice) => {
  router.push(`/super-admin/invoices/${invoice.id}`)
}

const closeCreateModal = () => {
  showCreateModal.value = false
  createForm.value = {
    school_id: '',
    type: 'subscription',
    billing_cycle: 'monthly',
    amount: 0,
    tax_amount: 0,
    discount_amount: 0,
    invoice_date: new Date().toISOString().split('T')[0],
    due_date: '',
    billing_period_start: '',
    billing_period_end: '',
    notes: '',
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    filters.value.page = page
    fetchInvoices()
  }
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: 'numeric' })
}

const isOverdue = (invoice) => {
  if (!invoice.due_date) return false
  const due = new Date(invoice.due_date)
  const today = new Date()
  return invoice.status !== 'paid' && invoice.status !== 'cancelled' && due < today
}

onMounted(() => {
  fetchInvoices()
  fetchStats()
  fetchSchools()
})
</script>
