<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Mobile sidebar overlay -->
    <div 
      v-if="mobileSidebarOpen" 
      class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
      @click="mobileSidebarOpen = false"
    ></div>

    <!-- Sidebar -->
    <aside 
      class="fixed inset-y-0 left-0 z-50 flex flex-col bg-white shadow-lg transition-all duration-300 ease-in-out"
      :class="[
        sidebarCollapsed ? 'w-20' : 'w-64',
        mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
    >
      <!-- Sidebar header -->
      <div class="flex items-center justify-between h-16 px-4 border-b shrink-0">
        <h1 v-if="!sidebarCollapsed" class="text-xl font-bold text-primary-600 truncate">EduCore</h1>
        <span v-else class="text-xl font-bold text-primary-600">E</span>
        <button 
          @click="toggleSidebar" 
          class="p-2 rounded-lg hover:bg-gray-100 transition-colors hidden lg:block"
          title="Toggle sidebar"
        >
          <svg v-if="sidebarCollapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
          </svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7 7" />
          </svg>
        </button>
        <button @click="mobileSidebarOpen = false" class="lg:hidden">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Sidebar nav -->
      <nav class="flex-1 overflow-y-auto p-3 space-y-1">
        <router-link
          v-for="item in menuItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center px-3 py-3 rounded-lg transition-colors group"
          :class="[
            $route.path === item.path 
              ? 'bg-primary-50 text-primary-600' 
              : 'text-gray-700 hover:bg-gray-100'
          ]"
          :title="sidebarCollapsed ? item.name : ''"
        >
          <span class="text-xl shrink-0">{{ item.icon }}</span>
          <span v-if="!sidebarCollapsed" class="ml-3 whitespace-nowrap">{{ item.name }}</span>
        </router-link>
      </nav>

      <!-- Sidebar footer -->
      <div class="p-3 border-t shrink-0">
        <button 
          @click="handleLogout"
          class="flex items-center w-full px-3 py-3 text-red-600 rounded-lg hover:bg-red-50 transition-colors"
          :title="sidebarCollapsed ? 'Logout' : ''"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span v-if="!sidebarCollapsed" class="ml-3">Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div 
      class="transition-all duration-300 ease-in-out min-h-screen"
      :class="[sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64']"
    >
      <!-- Top navbar -->
      <header class="bg-white shadow-sm sticky top-0 z-30">
        <div class="flex items-center justify-between h-16 px-6">
          <div class="flex items-center">
            <button 
              @click="mobileSidebarOpen = true" 
              class="lg:hidden mr-4 p-2 rounded-lg hover:bg-gray-100"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
            <h2 class="text-xl font-semibold text-gray-800 capitalize">{{ pageTitle }}</h2>
          </div>
          <div class="flex items-center space-x-4">
            <div class="hidden sm:flex items-center space-x-3">
              <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                <span class="text-sm font-medium text-primary-600">
                  {{ authStore.user?.name?.charAt(0) || 'A' }}
                </span>
              </div>
              <div>
                <span class="block text-sm font-medium text-gray-700">{{ authStore.user?.name }}</span>
                <span class="block text-xs text-gray-500 capitalize">{{ authStore.user?.role?.name }}</span>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const sidebarCollapsed = ref(false)
const mobileSidebarOpen = ref(false)

const pageTitle = computed(() => {
  const name = route.name || ''
  return name.toString().replace(/([A-Z])/g, ' $1').trim()
})

function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value
}

const menuItems = computed(() => {
  const role = authStore.user?.role?.name
  const isSuperAdmin = authStore.isSuperAdmin
  const enabledModules = authStore.enabledModules || []
  
  // Helper to check if module is enabled
  const isModuleEnabled = (moduleKey) => {
    // Super admins see all modules
    if (isSuperAdmin) return true
    // Check if module is in enabled modules list
    return enabledModules.some(m => m.key === moduleKey)
  }

  const items = [
    { name: 'Dashboard', path: '/dashboard', icon: '📊', module: 'dashboard' },
  ]

  // Super Admin Section
  if (isSuperAdmin) {
    items.push(
      { name: 'Schools', path: '/super-admin/schools', icon: '🏛️', module: 'admin' },
      { name: 'Plans', path: '/super-admin/plans', icon: '💎', module: 'admin' },
      { name: 'Modules', path: '/super-admin/modules', icon: '🧩', module: 'admin' },
      { name: 'Subscriptions', path: '/super-admin/subscriptions', icon: '💳', module: 'admin' },
      { name: 'Invoices', path: '/super-admin/invoices', icon: '📄', module: 'admin' },
    )
  }

  // Student Management
  if (isModuleEnabled('students') && ['admin', 'staff', 'teacher'].includes(role)) {
    items.push({ name: 'Students', path: '/students', icon: '👨‍🎓', module: 'students' })
  }

  // Academics (Classes)
  if (isModuleEnabled('academics') && ['admin', 'staff'].includes(role)) {
    items.push({ name: 'Classes', path: '/classes', icon: '🏫', module: 'academics' })
  }

  // Fees
  if (isModuleEnabled('fees') && ['admin', 'staff'].includes(role)) {
    items.push({ name: 'Fees', path: '/fees', icon: '💰', module: 'fees' })
  }

  // Attendance
  if (isModuleEnabled('attendance') && ['admin', 'teacher'].includes(role)) {
    items.push({ name: 'Attendance', path: '/attendance', icon: '✅', module: 'attendance' })
  }

  // Grades/Examinations
  if (isModuleEnabled('examinations') && ['admin', 'teacher'].includes(role)) {
    items.push({ name: 'Grades', path: '/grades', icon: '📝', module: 'examinations' })
  }

  // Communication (always enabled - core module)
  items.push(
    { name: 'Announcements', path: '/announcements', icon: '📢', module: 'communication' },
    { name: 'Messages', path: '/messages', icon: '💬', module: 'communication' },
    { name: 'Profile', path: '/profile', icon: '👤', module: 'profile' }
  )

  // Settings (admin and super admin)
  if (['admin', 'super_admin'].includes(role) || isSuperAdmin) {
    items.push({ name: 'Settings', path: '/settings', icon: '⚙️', module: 'admin' })
  }

  return items
})

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>
