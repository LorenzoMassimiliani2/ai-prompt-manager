<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
  prompt: Object,
  allTags: Array,
  can: Object,
  flash: Object,
})

const editMode = ref(false)
const confirmOpen = ref(false)
const toasts = ref([])

const form = useForm({
  title: props.prompt.title,
  content: props.prompt.content,
  visibility: props.prompt.visibility ?? 'private',
  tags: props.prompt.tags?.map(t => t.id) ?? [],
})

const save = () => {
  form.put(route('prompts.update', props.prompt.id), {
    preserveScroll: true,
    onSuccess: () => {
      editMode.value = false
      pushToast('Salvato ✅')
    }
  })
}

const destroyPrompt = () => {
  form.delete(route('prompts.destroy', props.prompt.id), {
    preserveScroll: true,
    onSuccess: () => pushToast('Eliminato 🗑️')
  })
}

const toggleTag = (id) => {
  const i = form.tags.indexOf(id)
  i === -1 ? form.tags.push(id) : form.tags.splice(i,1)
}

const pushToast = (msg) => {
  const id = Date.now()
  toasts.value.push({ id, msg })
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id) }, 2500)
}

onMounted(() => {
  if (props.flash?.success) pushToast(props.flash.success)
})
</script>

<template>
  <Head :title="props.prompt.title" />
  <div class="min-h-screen bg-gray-50">
    <!-- toasts -->
    <div class="fixed top-4 right-4 space-y-2 z-50">
      <div v-for="t in toasts" :key="t.id" class="bg-gray-900 text-white px-4 py-2 rounded-xl shadow">
        {{ t.msg }}
      </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-8">
      <!-- header -->
      <div class="flex items-start justify-between mb-6">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">{{ form.title }}</h1>
          <div class="mt-2 flex items-center gap-2 text-sm text-gray-600">
            <span class="px-2 py-0.5 rounded-full border">
              {{ form.visibility }}
            </span>
            <span>•</span>
            <span>di {{ props.prompt.user?.name }}</span>
            <span>•</span>
            <span>{{ new Date(props.prompt.created_at).toLocaleDateString() }}</span>
          </div>
        </div>

        <div class="flex gap-2">
          <Link :href="route('prompts.index')" class="px-3 py-2 rounded-xl border bg-white hover:bg-gray-50">Indietro</Link>
          <button
            v-if="can?.update"
            @click="editMode = !editMode"
            class="px-3 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">
            {{ editMode ? 'Annulla' : 'Modifica' }}
          </button>
          <button
            v-if="can?.delete"
            @click="confirmOpen = true"
            class="px-3 py-2 rounded-xl bg-red-600 text-white hover:bg-red-700">
            Elimina
          </button>
        </div>
      </div>

      <!-- card -->
      <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
        <!-- tags + visibility -->
        <div class="px-6 py-4 border-b bg-gray-50 flex flex-wrap items-center gap-2">
          <span v-for="t in props.prompt.tags" :key="t.id" class="text-xs bg-white border px-2 py-1 rounded-full">
            #{{ t.name }}
          </span>
        </div>

        <!-- content / edit form -->
        <div class="p-6">
          <!-- VIEW MODE -->
          <div v-if="!editMode" class="prose max-w-none">
            <pre class="whitespace-pre-wrap font-sans text-gray-800">{{ form.content }}</pre>
          </div>

          <!-- EDIT MODE -->
          <div v-else class="space-y-5">
            <div>
              <label class="block text-sm font-medium mb-1">Titolo</label>
              <input v-model="form.title" class="w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
              <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">{{ form.errors.title }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Prompt</label>
              <textarea v-model="form.content" rows="10" class="w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
              <p v-if="form.errors.content" class="text-sm text-red-600 mt-1">{{ form.errors.content }}</p>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Visibilità</label>
                <select v-model="form.visibility" class="w-full border rounded-xl px-3 py-2">
                  <option value="private">Privato</option>
                  <option value="public">Pubblico</option>
                  <option value="unlisted">Non in elenco</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Tag</label>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="t in allTags"
                    :key="t.id"
                    type="button"
                    @click="toggleTag(t.id)"
                    :class="[
                      'px-2 py-1 rounded-full border text-sm',
                      form.tags.includes(t.id) ? 'bg-indigo-50 border-indigo-300 text-indigo-700' : 'bg-white border-gray-200'
                    ]"
                  >
                    #{{ t.name }}
                  </button>
                </div>
                <p v-if="form.errors.tags" class="text-sm text-red-600 mt-1">{{ form.errors.tags }}</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <button @click="save" :disabled="form.processing"
                class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60">
                {{ form.processing ? 'Salvataggio…' : 'Salva' }}
              </button>
              <button @click="editMode=false" class="px-4 py-2 rounded-xl border bg-white hover:bg-gray-50">
                Annulla
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- sezione info secondaria -->
      <div class="mt-6 grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border p-5">
          <h3 class="font-medium mb-2">Dettagli</h3>
          <dl class="text-sm text-gray-600 space-y-1">
            <div class="flex justify-between"><dt>ID</dt><dd>#{{ props.prompt.id }}</dd></div>
            <div class="flex justify-between"><dt>Creato</dt><dd>{{ new Date(props.prompt.created_at).toLocaleString() }}</dd></div>
            <div class="flex justify-between"><dt>Aggiornato</dt><dd>{{ new Date(props.prompt.updated_at).toLocaleString() }}</dd></div>
          </dl>
        </div>
        <div class="bg-white rounded-2xl border p-5">
          <h3 class="font-medium mb-2">Suggerimenti</h3>
          <ul class="text-sm text-gray-600 list-disc ml-5 space-y-1">
            <li>Usa frasi brevi e chiare.</li>
            <li>Aggiungi esempi di input/contesto.</li>
            <li>Imposta obiettivi: tono, stile, vincoli.</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- modal conferma delete -->
    <div v-if="confirmOpen" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold">Eliminare questo prompt?</h3>
        <p class="text-sm text-gray-600 mt-1">L’azione non è reversibile.</p>
        <div class="mt-5 flex justify-end gap-2">
          <button @click="confirmOpen=false" class="px-4 py-2 rounded-xl border bg-white hover:bg-gray-50">Annulla</button>
          <button @click="destroyPrompt" :disabled="form.processing" class="px-4 py-2 rounded-xl bg-red-600 text-white hover:bg-red-700">
            Elimina
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
