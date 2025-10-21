<script setup>
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const props = defineProps({
  prompt: Object,      // null in create, valorizzato in edit
  allTags: Array,       // [{id, name}, ...]
  can: Object           // { manageTags: boolean }
})

const isEdit = computed(() => !!props.prompt)

const form = useForm({
  title: props.prompt?.title ?? '',
  content: props.prompt?.content ?? '',
  visibility: props.prompt?.visibility ?? 'private', // private | public | unlisted
  tags: props.prompt?.tags?.map(t => t.id) ?? []
})

const wordCount = computed(() => (form.content || '').trim().split(/\s+/).filter(Boolean).length)

// ricerca tag
const tagQuery = ref('')
const filteredTags = computed(() => {
  const q = tagQuery.value.toLowerCase().trim()
  if (!q) return props.allTags
  return props.allTags.filter(t => t.name.toLowerCase().includes(q))
})

function toggleTag(id){
  const i = form.tags.indexOf(id)
  i === -1 ? form.tags.push(id) : form.tags.splice(i,1)
}

// crea tag al volo
const newTag = useForm({ name: '' })
async function createTagQuick(){
  const name = tagQuery.value.trim() || newTag.name.trim()
  if (!name) return
    await router.post(route('tags.store'), { name }, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['allTags'] })
            newTag.reset()
            tagQuery.value = ''
        }
    })
}

function submit() {
  // 1. Prepara i query parameters da inoltrare al controller
  const queryParams = {}
  if (from.value) {
    queryParams.from = from.value
  }
  if (folder.value) {
    queryParams.folder = folder.value
  }

  // 2. Invia il form
  if (isEdit.value) {
    // Invia i queryParams anche in modifica, per gestire il redirect corretto
    form.put(route('prompts.update', { 
      prompt: props.prompt.id, // Parametro della rotta
      ...queryParams          // Parametri query: ?from=...&folder=...
    }))
  } else {
    // Invia i queryParams in creazione
    console.log('Creating with query params:', queryParams); 
    form.post(route('prompts.store', queryParams))
  }
}

const page = usePage()

// 1. Oggetto reattivo per tutti i parametri dell'URL
const urlParams = computed(() => {
  const queryString = page.url.split('?')[1] || ''
  return new URLSearchParams(queryString)
})

// 2. Estrai 'from' e 'folder' in modo reattivo
const from = computed(() => urlParams.value.get('from'))
const folder = computed(() => urlParams.value.get('folder'))

// 3. Crea il link "Indietro" che include anche il folder se necessario
const backUrl = computed(() => {
  if (from.value === 'dashboard') {
    const params = {}
    if (folder.value) {
      params.folder = folder.value
    }
    // Ritorna a 'dashboard', con ?folder=3 se presente
    return route('dashboard', params)
  }
  
  // Ritorna all'indice dei prompt
  return route('prompts.index')
})
</script>

<template>
  <Head :title="isEdit ? 'Modifica Prompt' : 'Nuovo Prompt'" />

  <div class="min-h-screen bg-gray-50">
    <div class="max-w-5xl mx-auto px-4 py-8 space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">
            {{ isEdit ? 'Modifica Prompt' : 'Crea un nuovo Prompt' }}
          </h1>
          <p class="text-sm text-gray-600 mt-1">
            Scrivi il testo del prompt, scegli visibilità e tag. Mantieni il testo chiaro e riutilizzabile.
          </p>
        </div>
        <Link
          :href="backUrl"
          class="px-3 py-2 rounded-xl border bg-white hover:bg-gray-50 flex items-center gap-2"
        >
          <svg viewBox="0 0 24 24" class="w-4 h-4 opacity-60"><path d="M15 18l-6-6 6-6" fill="currentColor" /></svg>
          <span>Indietro</span>
        </Link>
      </div>

      <!-- Card -->
      <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-sm border overflow-hidden">

        <!-- Title + Visibility -->
        <div class="p-6 border-b grid md:grid-cols-3 gap-4">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium mb-1">Titolo</label>
            <input
              v-model="form.title"
              placeholder="Es. Prompt per descrizioni prodotto SEO-friendly"
              class="w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            />
            <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">{{ form.errors.title }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Visibilità</label>
            <div class="grid grid-cols-3 gap-2">
              <button type="button"
                @click="form.visibility='private'"
                :class="['px-2 py-2 rounded-xl border text-sm',
                  form.visibility==='private' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white']">
                Privato
              </button>
              <button type="button"
                @click="form.visibility='public'"
                :class="['px-2 py-2 rounded-xl border text-sm',
                  form.visibility==='public' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white']">
                Pubblico
              </button>
              <button type="button"
                @click="form.visibility='unlisted'"
                :class="['px-2 py-2 rounded-xl border text-sm',
                  form.visibility==='unlisted' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white']">
                Link
              </button>
            </div>
            <p v-if="form.errors.visibility" class="text-sm text-red-600 mt-1">{{ form.errors.visibility }}</p>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6">
          <label class="block text-sm font-medium mb-1">Prompt</label>
          <textarea
            v-model="form.content"
            rows="12"
            placeholder="Definisci il ruolo dell'AI, obiettivo, tono, struttura output, vincoli e esempi..."
            class="w-full border rounded-2xl px-4 py-3 leading-6 focus:outline-none focus:ring-2 focus:ring-indigo-200"
          />
          <div class="mt-2 text-xs text-gray-500 flex items-center justify-between">
            <span>Consiglio: specifica contesto, obiettivo e formato di risposta.</span>
            <span>{{ wordCount }} parole</span>
          </div>
          <p v-if="form.errors.content" class="text-sm text-red-600 mt-1">{{ form.errors.content }}</p>
        </div>

        <!-- Tags -->
        <div class="px-6 pb-6">
          <label class="block text-sm font-medium mb-1">Tag</label>

        <!-- Ricerca/aggiunta rapida solo per superuser -->
        <div v-if="props.can?.manageTags" class="flex gap-2 mb-3">
            <input v-model="tagQuery" placeholder="Cerca o scrivi per creare…"
                class="flex-1 border rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
            <button type="button" @click="createTagQuick"
                    class="px-3 py-2 rounded-xl border bg-white hover:bg-gray-50">Aggiungi</button>
        </div>

        <!-- Se non superuser: solo filtro tag esistenti -->
        <div v-else class="mb-3">
            <input v-model="tagQuery" placeholder="Filtra tag…"
                class="w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
        </div>

          <!-- Lista tag selezionabili -->
          <div class="flex flex-wrap gap-2 max-h-40 overflow-y-auto p-2 border rounded-xl bg-gray-50">
            <button
              v-for="t in filteredTags"
              :key="t.id"
              type="button"
              @click="toggleTag(t.id)"
              :class="[
                'px-2 py-1 rounded-full border text-sm',
                form.tags.includes(t.id) ? 'bg-indigo-50 border-indigo-300 text-indigo-700' : 'bg-white border-gray-200'
              ]">
              #{{ t.name }}
            </button>
            <span v-if="!filteredTags.length" class="text-sm text-gray-500">Nessun tag trovato.</span>
          </div>

          <p v-if="form.errors.tags" class="text-sm text-red-600 mt-1">{{ form.errors.tags }}</p>
        </div>

        <!-- Sticky actions -->
        <div class="border-t bg-white/90 backdrop-blur supports-[backdrop-filter]:sticky supports-[backdrop-filter]:bottom-0 p-4 flex items-center justify-between">
          <div class="text-sm text-gray-500">
            <span v-if="isEdit">Stai modificando il prompt #{{ props.prompt.id }}</span>
            <span v-else>Crea un nuovo prompt</span>
          </div>
          <div class="flex gap-2">
            <Link :href="route('prompts.index')" class="px-4 py-2 rounded-xl border bg-white hover:bg-gray-50">Annulla</Link>
            <button :disabled="form.processing" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60">
              {{ form.processing ? 'Salvataggio…' : (isEdit ? 'Salva modifiche' : 'Crea prompt') }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>
