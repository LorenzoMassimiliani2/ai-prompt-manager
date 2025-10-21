<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
const props = defineProps({ prompt: Object });

const fav = useForm({});
const toggle = () => fav.post(route('prompts.favorite', props.prompt.id), { preserveScroll: true });
</script>

<template>
  <Head :title="props.prompt.title" />
  <div class="max-w-3xl mx-auto p-6 space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold">{{ props.prompt.title }}</h1>
      <div class="flex gap-2">
        <Link :href="route('prompts.edit', props.prompt.id)" class="px-3 py-1 rounded bg-gray-200">Modifica</Link>
        <button @click="toggle" class="px-3 py-1 rounded bg-amber-200">★ Preferito</button>
      </div>
    </div>
    <div class="flex gap-2">
      <span v-for="t in props.prompt.tags" :key="t.id" class="text-xs bg-gray-100 px-2 py-1 rounded">{{ t.name }}</span>
      <span class="text-xs px-2 py-1 rounded border">{{ props.prompt.visibility }}</span>
    </div>
    <pre class="bg-gray-50 rounded p-4 whitespace-pre-wrap">{{ props.prompt.content }}</pre>
  </div>
</template>
