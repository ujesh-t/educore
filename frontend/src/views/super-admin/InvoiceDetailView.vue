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
          <h1 class="text-2xl font-bold text-gray-900">Invoice Details</h1>
          <p class="text-sm text-gray-500">{{ invoice?.invoice_number }}</p>
        </div>
      </div>
      <div class="flex space-x-3">
        <button v-if="invoice?.status !== 'paid' && invoice?.status !== 'cancelled'" @click="recordPayment" class="btn-success">
          <span class="mr-2">💰</span> Record Payment
        </button>
        <button v-if="invoice?.status === 'pending'" @click="cancelInvoice" class="btn-danger">
          <span class="mr-2">❌</span> Cancel Invoice
        </button>
        <button @click="printInvoice" class="btn-secondary">
          <span class="mr-2">🖨️</span> Print
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"></div>
    </div>

    <div v-else-if="invoice" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Invoice Details -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Invoice Card -->
        <div class="card">
          <div class="flex justify-between items-start mb-6">
            <div>
              <h2 class="text-2xl font-bold text-gray-900">INVOICE</h2>
              <p class="text-gray-500">{{ invoice.invoice_number }}</p>
            </div>
            <div class="text-right">
              <span :class="{
                'bg-green-100 text-green-700': invoice.status === 'paid',
                'bg-yellow-100 text-yellow-700': invoice.status === 'pending',
                'bg-red-100 text-red-700': invoice.status === 'overdue',
                'bg-blue-100 text-blue-700': invoice.status === 'partial',
                'bg-gray-100 text-gray-700': invoice.status === 'cancelled'
              }" class="px-4 py-2 text-sm rounded-full capitalize font-semibold">
                {{ invoice.status }}
              </span>
            </div>
          </div>

          <!-- School Info -->
          <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Bill To</h3>
            <div class="text-lg font-bold text-gray-900">{{ invoice.school?.name }}</div>
            <div class="text-gray-600">{{ invoice.school?.email }}</div>
            <div class="text-gray-600">{{ invoice.school?.phone }}</div>
            <div class="text-gray-600">{{ invoice.school?.address }}</div>
          </div>

          <!-- Invoice Details Grid -->
          <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
              <div class="text-sm text-gray-500">Invoice Date</div>
              <div class="font-semibold">{{ formatDate(invoice.invoice_date) }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-500">Due Date</div>
              <div class="font-semibold" :class="isOverdue ? 'text-red-600' : ''">{{ formatDate(invoice.due_date) }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-500">Billing Period</div>
              <div class="font-semibold">{{ formatDate(invoice.billing_period_start) }} - {{ formatDate(invoice.billing_period_end) }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-500">Billing Cycle</div>
              <div class="font-semibold capitalize">{{ invoice.billing_cycle }}</div>
            </div>
          </div>

          <!-- Amount Breakdown -->
          <div class="border-t pt-4">
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-600">Base Amount</span>
                <span class="font-medium">₹{{ invoice.amount }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Tax</span>
                <span class="font-medium">₹{{ invoice.tax_amount }}</span>
              </div>
              <div v-if="invoice.discount_amount > 0" class="flex justify-between text-green-600">
                <span>Discount</span>
                <span>-₹{{ invoice.discount_amount }}</span>
              </div>
              <div class="flex justify-between border-t pt-2 text-lg">
                <span class="font-bold">Total</span>
                <span class="font-bold">₹{{ invoice.total_amount }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Paid</span>
                <span class="text-green-600">₹{{ invoice.paid_amount }}</span>
              </div>
              <div class="flex justify-between text-lg font-bold">
                <span>Balance Due</span>
                <span :class="invoice.balance > 0 ? 'text-red-600' : 'text-green-600'">₹{{ invoice.balance }}</span>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="invoice.notes" class="border-t pt-4 mt-4">
            <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Notes</h3>
            <p class="text-gray-600">{{ invoice.notes }}</p>
          </div>
        </div>

        <!-- Payments History -->
        <div class="card">
          <h3 class="text-lg font-bold text-gray-900 mb-4">Payment History</h3>
          <div v-if="payments.length === 0" class="text-gray-500 text-center py-4">
            No payments recorded yet
          </div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Transaction ID</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="payment in payments" :key="payment.id">
                  <td class="px-4 py-2 text-sm font-medium text-primary-600">{{ payment.transaction_id }}</td>
                  <td class="px-4 py-2 text-sm text-gray-500">{{ formatDate(payment.payment_date) }}</td>
                  <td class="px-4 py-2 text-sm text-gray-500 capitalize">{{ payment.payment_method }}</td>
                  <td class="px-4 py-2 text-sm font-semibold">₹{{ payment.amount }}</td>
                  <td class="px-4 py-2">
                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">{{ payment.status }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Subscription Info -->
        <div v-if="invoice.subscription" class="card">
          <h3 class="text-lg font-bold text-gray-900 mb-4">Subscription</h3>
          <div class="space-y-3">
            <div>
              <div class="text-sm text-gray-500">Plan</div>
              <div class="font-semibold">{{ invoice.subscription.planModel?.name || invoice.subscription.plan }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-500">Status</div>
              <span class="px-2 py-1 text-xs rounded-full capitalize" :class="{
                'bg-green-100 text-green-700': invoice.subscription.status === 'active',
                'bg-gray-100 text-gray-700': invoice.subscription.status !== 'active'
              }">{{ invoice.subscription.status }}</span>
            </div>
            <div>
              <div class="text-sm text-gray-500">Amount</div>
              <div class="font-semibold">₹{{ invoice.subscription.amount }}/{{ invoice.subscription.billing_cycle }}</div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
          <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
          <div class="space-y-2">
            <button @click="viewSchool" class="w-full text-left px-4 py-2 text-sm rounded-lg hover:bg-gray-100">
              🏫 View School
            </button>
            <button @click="viewSubscription" class="w-full text-left px-4 py-2 text-sm rounded-lg hover:bg-gray-100">
              📋 View Subscription
            </button>
            <button @click="sendReminder" class="w-full text-left px-4 py-2 text-sm rounded-lg hover:bg-gray-100">
              📧 Send Reminder
            </button>
          </div>
        </div>

        <!-- Metadata -->
        <div v-if="invoice.metadata" class="card">
          <h3 class="text-lg font-bold text-gray-900 mb-4">Details</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Type</span>
              <span class="font-medium capitalize">{{ invoice.type }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Currency</span>
              <span class="font-medium">{{ invoice.currency }}</span>
            </div>
            <div v-if="invoice.created_by" class="flex justify-between">
              <span class="text-gray-500">Created By</span>
              <span class="font-medium">{{ invoice.creator?.name || 'System' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Created</span>
              <span class="font-medium">{{ formatDateTime(invoice.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Payment Modal -->
  <div v-if="showPaymentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold">Record Payment</h2>
        <p class="text-sm text-gray-500">Balance: ₹{{ invoice?.balance }}</p>
      </div>

      <div class="p-6 space-y-4">
        <div>
          <label class="form-label">Amount (₹)</label>
          <input v-model.number="paymentForm.amount" type="number" class="form-input" min="0.01" :max="invoice?.balance" step="0.01" />
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
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useAppToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const toast = useAppToast()

const invoice = ref(null)
const payments = ref([])
const loading = ref(true)
const showPaymentModal = ref(false)
const processingPayment = ref(false)

const paymentForm = ref({
  amount: 0,
  payment_method: 'cash',
  payment_date: new Date().toISOString().split('T')[0],
  reference_number: '',
  notes: '',
})

const isOverdue = computed(() => {
  if (!invoice.value?.due_date) return false
  const due = new Date(invoice.value.due_date)
  const today = new Date()
  return invoice.value.status !== 'paid' && invoice.value.status !== 'cancelled' && due < today
})

const fetchInvoice = async () => {
  try {
    loading.value = true
    const response = await api.get(`/super-admin/invoices/${route.params.id}`)
    invoice.value = response.data?.data?.invoice
    payments.value = response.data?.data?.invoice?.payments || []
  } catch (error) {
    console.error('Error fetching invoice:', error)
    toast.error('Failed to load invoice details')
  } finally {
    loading.value = false
  }
}

const recordPayment = () => {
  paymentForm.value = {
    amount: invoice.value?.balance || 0,
    payment_method: 'cash',
    payment_date: new Date().toISOString().split('T')[0],
    reference_number: '',
    notes: '',
  }
  showPaymentModal.value = true
}

const submitPayment = async () => {
  if (!paymentForm.value.amount || paymentForm.value.amount <= 0) {
    toast.error('Please enter a valid amount')
    return
  }

  try {
    processingPayment.value = true
    await api.post(`/super-admin/invoices/${invoice.value.id}/record-payment`, paymentForm.value)
    showPaymentModal.value = false
    await fetchInvoice()
    toast.success('Payment recorded successfully')
  } catch (error) {
    console.error('Error recording payment:', error)
    toast.error(error.response?.data?.message || 'Failed to record payment')
  } finally {
    processingPayment.value = false
  }
}

const cancelInvoice = async () => {
  if (!window.confirm('Are you sure you want to cancel this invoice?')) return

  try {
    await api.post(`/super-admin/invoices/${invoice.value.id}/cancel`)
    await fetchInvoice()
    toast.success('Invoice cancelled successfully')
  } catch (error) {
    console.error('Error cancelling invoice:', error)
    toast.error(error.response?.data?.message || 'Failed to cancel invoice')
  }
}

const printInvoice = () => {
  window.print()
}

const viewSchool = () => {
  if (invoice.value?.school_id) {
    router.push(`/super-admin/schools`)
  }
}

const viewSubscription = () => {
  if (invoice.value?.subscription_id) {
    router.push(`/super-admin/subscriptions/${invoice.value.subscription_id}`)
  }
}

const sendReminder = () => {
  toast.info('Reminder email functionality to be implemented')
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('en-IN', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
  fetchInvoice()
})
</script>

<style scoped>
@media print {
  .btn-secondary, .btn-success, .btn-danger {
    display: none !important;
  }
}
</style>
