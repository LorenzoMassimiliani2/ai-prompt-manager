<script setup>
import { router } from '@inertiajs/vue3'

function logout() {
  if (confirm('Vuoi davvero uscire?')) {
    router.post(route('logout'))
  }
}
</script>

<template>
  <button
    @click="logout"
    class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium text-gray-700 border hover:bg-gray-50 transition"
  >
    <svg viewBox="0 0 24 24" class="w-4 h-4 text-gray-500">
      <path
        d="M16 13v-2H7V8l-5 4 5 4v-3h9zM20 3h-8v2h8v14h-8v2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"
        fill="currentColor"
      />
    </svg>
    <span>Logout</span>
  </button>
</template>
