<template>
  <div class="space-y-6">
    <!-- Welcome card -->
    <div class="card">
      <h2 class="text-2xl font-bold text-gray-900 mb-2">
        Welcome back, {{ authStore.user?.name }}!
      </h2>
      <p class="text-gray-600">
        Here's what's happening at your school today.
      </p>
    </div>

    <!-- Stats grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="card">
        <div class="flex items-center">
          <div class="p-3 bg-primary-100 rounded-lg">
            <span class="text-2xl">👨‍🎓</span>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Total Students</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.students }}</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center">
          <div class="p-3 bg-green-100 rounded-lg">
            <span class="text-2xl">👩‍🏫</span>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Teachers</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.teachers }}</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center">
          <div class="p-3 bg-yellow-100 rounded-lg">
            <span class="text-2xl">🏫</span>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Classes</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.classes }}</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="flex items-center">
          <div class="p-3 bg-red-100 rounded-lg">
            <span class="text-2xl">💰</span>
          </div>
          <div class="ml-4">
            <p class="text-sm text-gray-600">Pending Fees</p>
            <p class="text-2xl font-bold text-gray-900">${{ stats.pendingFees }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Today's Attendance</h3>
        <div class="space-y-3">
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Present</span>
            <span class="font-medium text-green-600">{{ attendance.present }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Absent</span>
            <span class="font-medium text-red-600">{{ attendance.absent }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Late</span>
            <span class="font-medium text-yellow-600">{{ attendance.late }}</span>
          </div>
        </div>
      </div>

      <div class="card">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Announcements</h3>
        <div class="space-y-3">
          <div v-for="i in 3" :key="i" class="border-b pb-3 last:border-0">
            <p class="text-sm font-medium text-gray-900">Announcement {{ i }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ i }} days ago</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const stats = ref({
  students: 1250,
  teachers: 85,
  classes: 42,
  pendingFees: 15420,
})

const attendance = ref({
  present: 1180,
  absent: 45,
  late: 25,
})
</script>
