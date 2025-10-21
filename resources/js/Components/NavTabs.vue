// ...existing code...
<script setup>
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const currentUrl = page.url // es. "/prompts?view=grid"

function isActive(nameOrStartsWith){
  // usa route() se hai Ziggy con nomi rotta, altrimenti fallback sull'URL
  try {
    return route().current(nameOrStartsWith)
  } catch {
    return currentUrl.startsWith(nameOrStartsWith)
  }
}
</script>

<template>
  <nav class="w-full flex justify-end">
    <div class="inline-flex rounded-lg border bg-white p-1 space-x-1">
      <!-- Prompts (icon) -->
      <Link
        :href="route?.('prompts.index') ?? '/prompts'"
        :class="[
          'p-2 rounded-md text-sm transition flex items-center justify-center',
          isActive('prompts.*') || isActive('/prompts')
            ? 'bg-indigo-600 text-white'
            : 'text-gray-700 hover:bg-gray-100'
        ]"
        title="Prompts"
        aria-label="Prompts">
        <!-- sparkles / prompt icon -->
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.5 3 3 1.5-3 1.5L12 12l-1.5-3L7 7.5 10 6 12 3zM5 13l.8 1.6L8 16l-1.2 1.4L6 19l-.8-1.6L4 16l1.2-1.4L5 13zM19 13l.8 1.6L22 16l-1.2 1.4L20 19l-.8-1.6L18 16l1.2-1.4L19 13z"/>
        </svg>
      </Link>

      <!-- Dashboard (icon) -->
      <Link
        :href="route?.('dashboard') ?? '/dashboard'"
        :class="[
          'p-2 rounded-md text-sm transition flex items-center justify-center',
          isActive('dashboard') || isActive('/dashboard')
            ? 'bg-indigo-600 text-white'
            : 'text-gray-700 hover:bg-gray-100'
        ]"
        title="Dashboard"
        aria-label="Dashboard">
        <!-- chart / dashboard icon -->
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 13v6M12 8v11M17 3v16"/>
        </svg>
      </Link>
    </div>
  </nav>
</template>
//