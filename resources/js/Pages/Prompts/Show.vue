<script setup>
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3'
import { ref, watch, onMounted, computed } from 'vue'

const props = defineProps({
  prompt: Object,
  allTags: Array,
  can: Object,
  flash: Object,
  auth: Object,
  comments: Object,
  services: Array,
  myFolders: Array,
  attachedFolders: Array,
  from: String
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
  // 1. Prepara i query parameters da inoltrare al controller
  //    (usiamo i computed 'from' e 'folder' che hai già definito)
  const queryParams = {}
  if (from.value) {
    queryParams.from = from.value
  }
  if (folder.value) {
    queryParams.folder = folder.value
  }

  // 2. Invia il form includendo i parametri
  form.put(route('prompts.update', {
    prompt: props.prompt.id, // Parametro della rotta
    ...queryParams          // Parametri query: ?from=...&folder=...
  }), {
    preserveScroll: true,
    onSuccess: () => {
      editMode.value = false
      // La toast non è necessaria se il controller fa un redirect
      // Ma la lasciamo, male non fa (verrà mostrata nella pagina di destinazione)
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
  i === -1 ? form.tags.push(id) : form.tags.splice(i, 1)
}

const pushToast = (msg) => {
  const id = Date.now()
  toasts.value.push({ id, msg })
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id) }, 2500)
}

onMounted(() => {
  if (props.flash?.success) pushToast(props.flash.success)
})

const cform = useForm({ body: '' })
const addComment = () => {
  if (!cform.body.trim()) return
  cform.post(route('prompts.comments.store', props.prompt.id), {
    preserveScroll: true,
    onSuccess: () => {
      cform.reset('body')
      pushToast('Commento aggiunto ✅')
      // ricarica solo la lista commenti
      router.reload({ only: ['comments'] })
    }
  })
}

// delete comment
const cdel = useForm({})
const canDeleteComment = (comment) => {
  // owner o superuser
  return props?.auth?.isSuper || props?.auth?.userId === comment.user_id
}
const deleteComment = (comment) => {
  if (!canDeleteComment(comment)) return
  if (!confirm('Eliminare questo commento?')) return
  cdel.delete(route('prompts.comments.destroy', { prompt: props.prompt.id, comment: comment.id }), {
    preserveScroll: true,
    onSuccess: () => {
      pushToast('Commento eliminato 🗑️')
      router.reload({ only: ['comments'] })
    }
  })
}

async function copyText(text) {
  try {
    await navigator.clipboard.writeText(text || '')
    pushToast('Prompt copiato negli appunti ✂️')
  } catch (e) {
    // fallback
    const ta = document.createElement('textarea')
    ta.value = text || ''
    document.body.appendChild(ta)
    ta.select(); document.execCommand('copy')
    document.body.removeChild(ta)
    pushToast('Prompt copiato (fallback) ✂️')
  }
}

function openService(svc) {
  const body = (/* in edit? */ typeof form !== 'undefined' ? form.content : props.prompt.content) || ''
  copyText(body)

  if (svc.supports_query) {
    const q = encodeURIComponent(body.slice(0, 5000))
    const u = `${svc.base_url}${svc.base_url.includes('?') ? '&' : '?'}q=${q}`
    window.open(u, '_blank', 'noopener')
  } else {
    window.open(svc.base_url, '_blank', 'noopener')
  }
}

const page = usePage()
// 1. Creiamo un oggetto reattivo per i parametri dell'URL
const urlParams = computed(() => {
  // page.url è una stringa tipo "/prompts/10?folder=3&from=dashboard"
  // Estraiamo solo la parte della query string
  const queryString = page.url.split('?')[1] || ''
  return new URLSearchParams(queryString)
})

// 2. Estraiamo i valori 'from' e 'folder' in modo pulito
const from = computed(() => urlParams.value.get('from'))
const folder = computed(() => urlParams.value.get('folder'))

// 3. Costruiamo il backUrl in modo condizionale
const backUrl = computed(() => {

  if (from.value === 'dashboard') {
    // Se 'from' è 'dashboard', prepariamo i parametri
    const params = {}

    // Aggiungiamo 'folder' ai parametri SOLO se esiste nell'URL
    if (folder.value) {
      params.folder = folder.value
    }

    // La rotta sarà 'dashboard' con eventuali parametri (es. { folder: 3 })
    return route('dashboard', params)
  }

  if (from.value === 'prompts') {
    // Se 'from' è 'prompts', torna all'indice (senza parametri)
    return route('prompts.index')
  }

  // Fallback: se 'from' non è presente, torna a una destinazione di default
  return route('prompts.index')
})

// toggle pannello (parte nascosto)
const folderOpen = ref(false)
const attachFolderForm = useForm({ folder_id: null })

const attachToFolder = () => {
  if (!attachFolderForm.folder_id) return
  attachFolderForm.post(route('prompts.folders.attach', props.prompt.id), {
    preserveScroll: true,
    onSuccess: () => {
      attachFolderForm.reset('folder_id')
      pushToast('Aggiunto alla cartella ✅')
      router.reload({ only: ['attachedFolders'] })
    }
  })
}

const detachForm = useForm({})
const detachFromFolder = (folderId) => {
  detachForm.delete(route('prompts.folders.detach', { prompt: props.prompt.id, folder: folderId }), {
    preserveScroll: true,
    onSuccess: () => {
      pushToast('Rimosso dalla cartella 🗑️')
      router.reload({ only: ['attachedFolders'] })
    }
  })
}

// ⭐ mini modal state
const favOpen = ref(false)
const favSearch = ref('')
const favSelected = ref(null)
const favRemember = ref(false)
const showAttached = ref(false) // per il blocco collassabile

const LAST_FOLDER_KEY = 'pm:lastFolderId'

// elenco cartelle filtrate (per la modale)
const favFiltered = computed(() => {
  const q = favSearch.value.trim().toLowerCase()
  const base = props.myFolders || []
  if (!q) return base
  return base.filter(f => f.name.toLowerCase().includes(q))
})

function openFav () {
  // pre-seleziona l’ultima cartella usata (se presente)
  const last = Number(localStorage.getItem(LAST_FOLDER_KEY) || 0)
  favSelected.value = last && (props.myFolders || []).some(f => f.id === last) ? last : null
  favOpen.value = true
}

function closeFav () {
  favOpen.value = false
  favSearch.value = ''
  // non resetto favSelected per comodità
}

function confirmFav () {
  if (!favSelected.value) return
  attachFolderForm.folder_id = favSelected.value
  attachToFolder()
  if (favRemember.value) {
    localStorage.setItem(LAST_FOLDER_KEY, String(favSelected.value))
  }
  closeFav()
}

// ⭐ gialla se il prompt è in una o più cartelle dell’utente
const isFaved = computed(() => (props.attachedFolders?.length ?? 0) > 0)

// testo tooltip: elenco cartelle oppure suggerimento
const favTooltip = computed(() => {
  return isFaved.value
    ? (props.attachedFolders || []).map(f => f.name).join(', ')
    : 'Aggiungi a una tua cartella'
})

const attachedIds = computed(() => new Set((props.attachedFolders || []).map(f => f.id)))

const isSelectedAttached = computed(() =>
  !!favSelected.value && attachedIds.value.has(favSelected.value)
)

function removeFav () {
  if (!favSelected.value) return
  // riusa la tua funzione già esistente
  detachFromFolder(favSelected.value)
  closeFav()
}

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
      <div class="flex items-start justify-between mb-4">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">{{ form.title }}</h1>
          <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-600">
            <span class="px-2 py-0.5 rounded-full border bg-white">
              {{ form.visibility }}
            </span>
            <span class="hidden sm:inline">•</span>
            <span>di {{ props.prompt.user?.name }}</span>
            <span class="hidden sm:inline">•</span>
            <span>{{ new Date(props.prompt.created_at).toLocaleDateString() }}</span>
          </div>
        </div>
        <div class="flex gap-2 shrink-0">
          <Link :href="backUrl" class="px-3 py-2 rounded-xl border bg-white hover:bg-gray-50 flex items-center gap-2">
          <svg viewBox="0 0 24 24" class="w-4 h-4 opacity-60">
            <path d="M15 18l-6-6 6-6" fill="currentColor" />
          </svg>
          <span>Indietro</span>
          </Link>
        <!-- ★ preferiti / aggiungi a cartella (con stato + tooltip al hover) -->
        <button
          v-if="myFolders?.length"
          @click="openFav()"
          :aria-label="isFaved ? 'Gestisci cartelle del prompt' : 'Aggiungi a cartella'"
          :class="[
            'px-2.5 py-2 rounded-xl border bg-white hover:bg-gray-50 transition',
            isFaved ? 'border-yellow-300 bg-yellow-50' : ''
          ]"
        >
          <svg viewBox="0 0 24 24" class="w-4 h-4"
              :class="isFaved ? 'text-yellow-500' : 'text-gray-700'">
            <path fill="currentColor"
              d="M12 17.3l-5.5 3.2l1.4-6.1L3 9.8l6.2-.5L12 3.5l2.8 5.8l6.2.5l-4.9 4.6l1.4 6.1z"/>
          </svg>
        </button>
          <!-- edit / delete -->
          <button v-if="can?.update" @click="editMode = !editMode"
            class="px-3 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">
            {{ editMode ? 'Annulla' : 'Modifica' }}
          </button>
          <button v-if="can?.delete" @click="confirmOpen = true"
            class="px-3 py-2 rounded-xl bg-red-600 text-white hover:bg-red-700">
            Elimina
          </button>
        </div>
      </div>

      <!-- quick actions toolbar -->
      <div v-if="!editMode" class="mb-6">
        <div class="bg-white rounded-2xl shadow-sm">


          <!-- servizi: carosello orizzontale scrollabile -->
          <div class="px-3 sm:px-4 py-3">
            <div
              class="flex gap-2 overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent pb-1">
              <button @click="copyText((typeof form !== 'undefined' ? form.content : props.prompt.content) || '')"
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-yellow-200 border hover:brightness-95 shadow-sm">
                <svg viewBox="0 0 24 24" class="w-4 h-4 opacity-90">
                  <path
                    d="M16 1H4a2 2 0 0 0-2 2v12h2V3h12zM20 5H8a2 2 0 0 0-2 2v14h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm0 16H8V7h12z"
                    fill="currentColor" />
                </svg>
                <span class="text-sm">Copia prompt</span>
              </button>
              <button v-for="s in services" :key="s.id" @click="openService(s)"
                class="group flex items-center gap-2 px-3 py-2 rounded-xl border bg-white hover:bg-gray-50 shadow-sm">
                <svg v-if="s.meta?.icon_path" :viewBox="s.meta?.viewBox || '0 0 24 24'"
                  class="w-4 h-4 opacity-80 group-hover:opacity-100">
                  <path :d="s.meta.icon_path" fill="currentColor" />
                </svg>
                <span class="text-sm font-medium">{{ s.name }}</span>
                <span v-if="s.supports_query"
                  class="text-[10px] leading-none px-1.5 py-0.5 rounded border bg-indigo-50 text-indigo-700 border-indigo-200">
                  auto
                </span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- main card -->
      <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
        <!-- tags -->
        <div class="px-6 py-4 border-b bg-gray-50 flex flex-wrap items-center gap-2">
          <span v-for="t in props.prompt.tags" :key="t.id"
            class="text-xs bg-white border border-gray-200 text-gray-700 px-2 py-1 rounded-full">
            #{{ t.name }}
          </span>
        </div>
          
        <div class="p-6">
          <!-- VIEW MODE -->
          <div v-if="!editMode" class="max-w-none">
            <pre class="whitespace-pre-wrap text-[15px] leading-7 text-gray-800 mb-2">{{ form.content }}</pre>
          </div>

          <!-- EDIT MODE -->
          <div v-else class="space-y-5">
            <div>
              <label class="block text-sm font-medium mb-1">Titolo</label>
              <input v-model="form.title"
                class="w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
              <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">{{ form.errors.title }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Prompt</label>
              <textarea v-model="form.content" rows="10"
                class="w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
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
                  <button v-for="t in allTags" :key="t.id" type="button" @click="toggleTag(t.id)" :class="[
                    'px-2 py-1 rounded-full border text-sm transition',
                    form.tags.includes(t.id)
                      ? 'bg-indigo-50 border-indigo-300 text-indigo-700'
                      : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'
                  ]">
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
              <button @click="editMode = false" class="px-4 py-2 rounded-xl border bg-white hover:bg-gray-50">
                Annulla
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- meta columns -->
      <div class="mt-6 grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border p-5">
          <h3 class="font-medium mb-2">Dettagli</h3>
          <dl class="text-sm text-gray-600 space-y-1">
            <div class="flex justify-between">
              <dt>ID</dt>
              <dd>#{{ props.prompt.id }}</dd>
            </div>
            <div class="flex justify-between">
              <dt>Creato</dt>
              <dd>{{ new Date(props.prompt.created_at).toLocaleString() }}</dd>
            </div>
            <div class="flex justify-between">
              <dt>Aggiornato</dt>
              <dd>{{ new Date(props.prompt.updated_at).toLocaleString() }}</dd>
            </div>
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

      <!-- Commenti -->
      <div class="mt-8 bg-white rounded-2xl border">
        <div class="px-6 py-4 border-b flex items-center justify-between">
          <h3 class="font-medium">Commenti</h3>
          <span class="text-sm text-gray-500">{{ comments?.total ?? 0 }}</span>
        </div>

        <!-- Nuovo commento -->
        <div v-if="can?.commentCreate" class="p-6 border-b">
          <form @submit.prevent="addComment" class="space-y-3">
            <textarea v-model="cform.body" rows="4" placeholder="Aggiungi un commento…"
              class="w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
            <div class="flex justify-end">
              <button :disabled="cform.processing || !cform.body.trim()"
                class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60">
                Pubblica
              </button>
            </div>
            <p v-if="cform.errors.body" class="text-sm text-red-600">{{ cform.errors.body }}</p>
          </form>
        </div>

        <!-- Lista commenti -->
        <div v-if="comments && comments.data?.length" class="divide-y">
          <div v-for="c in comments.data" :key="c.id" class="p-6">
            <div class="flex items-start justify-between">
              <div class="min-w-0">
                <div class="text-sm font-medium">
                  {{ c.user?.name || 'Utente' }}
                  <span class="text-gray-400 ml-2 text-xs">{{ new Date(c.created_at).toLocaleString() }}</span>
                </div>
                <p class="mt-2 whitespace-pre-wrap text-gray-800">{{ c.body }}</p>
              </div>
              <div class="shrink-0 ml-3" v-if="canDeleteComment(c)">
                <button @click="deleteComment(c)"
                  class="px-3 py-1 rounded bg-red-600 text-white text-sm">Elimina</button>
              </div>
            </div>
          </div>
          <!-- paginazione -->
          <div class="p-4 flex justify-center gap-2" v-if="comments.links">
            <Link v-for="l in comments.links" :key="l.url + l.label" :href="l.url || '#'" v-html="l.label"
              :class="['px-3 py-1 rounded', l.active ? 'bg-indigo-600 text-white' : 'bg-gray-100']" />
          </div>
        </div>
        <div v-else class="p-6 text-sm text-gray-500">Ancora nessun commento.</div>
      </div>
    </div>

    <!-- modal conferma delete -->
    <div v-if="confirmOpen" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold">Eliminare questo prompt?</h3>
        <p class="text-sm text-gray-600 mt-1">L’azione non è reversibile.</p>
        <div class="mt-5 flex justify-end gap-2">
          <button @click="confirmOpen = false"
            class="px-4 py-2 rounded-xl border bg-white hover:bg-gray-50">Annulla</button>
          <button @click="destroyPrompt" :disabled="form.processing"
            class="px-4 py-2 rounded-xl bg-red-600 text-white hover:bg-red-700">
            Elimina
          </button>
        </div>
      </div>
    </div>
    <!-- Mini modal: aggiungi a cartella -->
    <div v-if="favOpen" class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/40" @click="closeFav()"></div>

      <div class="absolute inset-x-4 sm:left-1/2 sm:-translate-x-1/2 sm:w-[420px] top-24
              bg-white rounded-2xl shadow-2xl border overflow-hidden">
        <div class="px-4 py-3 border-b flex items-center justify-between">
          <h3 class="text-sm font-semibold">Aggiungi a cartella</h3>
          <button @click="closeFav()" class="px-2 py-1 rounded-lg bg-gray-100">✕</button>
        </div>

        <div class="p-3 space-y-2">
          <!-- ricerca -->
          <div class="relative">
            <input v-model="favSearch" placeholder="Cerca cartella…"
              class="w-full border rounded-xl pl-9 pr-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24">
              <path fill="currentColor"
                d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5A6.5 6.5 0 1 0 9.5 16a6.47 6.47 0 0 0 4.23-1.57l.27.28h.79L20 20.5L21.5 19L15.5 14Z" />
            </svg>
          </div>

          <!-- elenco cartelle -->
          <div class="max-h-64 overflow-auto rounded-xl border">
            <button
              v-for="f in favFiltered"
              :key="f.id"
              @click="favSelected = f.id"
              class="w-full text-left px-3 py-2 text-sm flex items-center justify-between hover:bg-gray-50"
              :class="[
                favSelected === f.id ? 'bg-indigo-50' : '',
                attachedIds.has(f.id) ? 'bg-yellow-50/60' : ''
              ]"
            >
              <span class="truncate flex items-center gap-2">
                <!-- spunta se già presente -->
                <svg v-if="attachedIds.has(f.id)" viewBox="0 0 24 24" class="w-4 h-4 text-yellow-600 shrink-0">
                  <path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/>
                </svg>
                <span :class="attachedIds.has(f.id) ? 'text-gray-800' : ''">{{ f.name }}</span>
                <span v-if="attachedIds.has(f.id)" class="text-[11px] px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-800 border border-yellow-200">
                  già presente
                </span>
              </span>

              <span class="text-xs text-gray-400">#{{ f.id }}</span>
            </button>

            <div v-if="!favFiltered.length" class="px-3 py-6 text-center text-sm text-gray-500">
              Nessuna cartella trovata.
            </div>
          </div>


         <!-- azioni -->
        <div class="flex items-center justify-between">
          <label class="flex items-center gap-2 text-xs text-gray-600">
            <input type="checkbox" v-model="favRemember" class="rounded">
            Ricorda scelta
          </label>
          <div class="flex items-center gap-2">
            <button @click="closeFav()"
              class="px-3 py-1.5 rounded-xl border bg-white hover:bg-gray-50 text-sm">Annulla</button>

            <!-- Se la cartella selezionata contiene già il prompt: mostra RIMUOVI -->
            <button
              v-if="isSelectedAttached"
              @click="removeFav()"
              :disabled="attachFolderForm.processing"
              class="px-3 py-1.5 rounded-xl bg-red-600 text-white text-sm hover:bg-red-700 disabled:opacity-50">
              Rimuovi
            </button>

            <!-- Altrimenti mostra AGGIUNGI -->
            <button
              v-else
              @click="confirmFav()"
              :disabled="!favSelected || attachFolderForm.processing"
              class="px-3 py-1.5 rounded-xl bg-indigo-600 text-white text-sm hover:bg-indigo-700 disabled:opacity-50">
              Aggiungi
            </button>
          </div>
        </div>

        </div>
      </div>
    </div>
  </div>
</template>
