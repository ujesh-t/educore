<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-2xl font-bold text-primary-600">EduCore</h1>
          </div>
          <div class="flex items-center space-x-4">
            <a href="/login" class="text-gray-600 hover:text-gray-900">Login</a>
            <a href="/register" class="btn-primary">Register</a>
          </div>
        </div>
      </div>
    </nav>

    <main class="py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">
            Welcome to EduCore
          </h2>
          <p class="text-xl text-gray-600 mb-8">
            Comprehensive School Management System
          </p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
            <div class="card">
              <div class="text-primary-600 text-4xl mb-4">📚</div>
              <h3 class="text-lg font-semibold mb-2">Academic Management</h3>
              <p class="text-gray-600">Courses, grades, attendance, and timetables</p>
            </div>
            <div class="card">
              <div class="text-primary-600 text-4xl mb-4">💰</div>
              <h3 class="text-lg font-semibold mb-2">Financial Management</h3>
              <p class="text-gray-600">Fees, invoices, and payment tracking</p>
            </div>
            <div class="card">
              <div class="text-primary-600 text-4xl mb-4">👥</div>
              <h3 class="text-lg font-semibold mb-2">User Management</h3>
              <p class="text-gray-600">Students, teachers, parents, and staff</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
// Home view - landing page
</script>
