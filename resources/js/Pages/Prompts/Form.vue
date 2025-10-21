<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
const props = defineProps({ prompt: Object, allTags: Array });

const form = useForm({
  title: props.prompt?.title ?? '',
  content: props.prompt?.content ?? '',
  visibility: props.prompt?.visibility ?? 'private',
  tags: props.prompt?.tags?.map(t => t.id) ?? []
});

const submit = () => {
  props.prompt
    ? form.put(route('prompts.update', props.prompt.id))
    : form.post(route('prompts.store'));
};
</script>

<template>
  <Head :title="props.prompt ? 'Modifica' : 'Nuovo Prompt'" />
  <div class="max-w-3xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-semibold">{{ props.prompt ? 'Modifica' : 'Nuovo Prompt' }}</h1>
      <Link :href="route('prompts.index')" class="text-sm text-gray-600">Indietro</Link>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm mb-1">Titolo</label>
        <input v-model="form.title" class="w-full border rounded-lg px-3 py-2" required />
      </div>
      <div>
        <label class="block text-sm mb-1">Contenuto (prompt)</label>
        <textarea v-model="form.content" rows="8" class="w-full border rounded-lg px-3 py-2 whitespace-pre-wrap" required />
      </div>
      <div>
        <label class="block text-sm mb-1">Visibilità</label>
        <select v-model="form.visibility" class="border rounded-lg px-3 py-2">
          <option value="private">Privato</option>
          <option value="public">Pubblico</option>
          <option value="unlisted">Non in elenco (link)</option>
        </select>
      </div>
      <div>
        <label class="block text-sm mb-1">Tag</label>
        <div class="grid grid-cols-2 gap-2">
          <label v-for="t in allTags" :key="t.id" class="flex items-center gap-2 text-sm">
            <input type="checkbox" :value="t.id" v-model="form.tags"> {{ t.name }}
          </label>
        </div>
      </div>

      <div class="flex gap-3">
        <button :disabled="form.processing" class="bg-indigo-600 text-white px-4 py-2 rounded-lg">
          {{ form.processing ? 'Salvataggio...' : 'Salva' }}
        </button>
        <span v-if="form.errors" class="text-red-600 text-sm">{{ Object.values(form.errors)[0] }}</span>
      </div>
    </form>
  </div>
</template>
