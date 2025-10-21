<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
defineProps({ prompts: Object, filters: Object, tags: Array });

const form = useForm({ q: (new URLSearchParams(location.search)).get('q') ?? '' });
const search = () => form.get(route('prompts.index'), { preserveState: true, preserveScroll: true });
</script>

<template>
  <Head title="Prompts"/>
  <div class="max-w-6xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6 gap-3">
      <form @submit.prevent="search" class="flex-1">
        <input v-model="form.q" placeholder="Cerca prompt..." class="w-full border rounded-lg px-3 py-2" />
      </form>
      <Link :href="route('prompts.create')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg">Nuovo</Link>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      <article v-for="p in prompts.data" :key="p.id" class="border rounded-xl p-4 hover:shadow">
        <h3 class="font-semibold line-clamp-1">
          <Link :href="route('prompts.show', p.id)">{{ p.title }}</Link>
        </h3>
        <p class="text-sm text-gray-600 mt-2 line-clamp-3 whitespace-pre-wrap">{{ p.content }}</p>
        <div class="mt-3 flex flex-wrap gap-2">
          <span v-for="t in p.tags" :key="t.id" class="text-xs bg-gray-100 px-2 py-1 rounded">{{ t.name }}</span>
        </div>
      </article>
    </div>

    <div class="mt-6 flex justify-center gap-2" v-if="prompts.links">
      <Link v-for="l in prompts.links" :key="l.url + l.label" :href="l.url || '#'" v-html="l.label"
        :class="['px-3 py-1 rounded', l.active ? 'bg-indigo-600 text-white' : 'bg-gray-100']" />
    </div>
  </div>
</template>
