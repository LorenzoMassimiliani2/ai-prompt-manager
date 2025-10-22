<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
  folders: Array,   // flat: [{id, name, parent_id, sort, direct_prompts_count}, ...]
  current: Object,  // cartella selezionata con prompts
  myPrompts: Array, // ultimi prompt dell'utente
})

/* ----------------- UI: stato sidebar mobile ----------------- */
const showFolders = ref(false) // su mobile apre/chiude la sidebar

/* ----------------- toast ----------------- */
const toast = ref(null)
function pushToast(msg){ toast.value = msg; setTimeout(() => toast.value = null, 2000) }

/* ----------------- forms ----------------- */
const createForm = useForm({ name:'', parent_id: null })
const renameForm = useForm({ id:null, name:'' })
const moveForm   = useForm({ id:null, parent_id:null })
const attachForm = useForm({ prompt_id:null })
const detachForm = useForm({})

/* ----------------- T O G G L E   P A N E L S  ----------------- */
// partono nascosti
const showCreateSubfolder = ref(false)
const showAttachPrompt    = ref(false)

/* ----------------- navigation ----------------- */
function openFolder(id){
  showFolders.value = false // su mobile: chiudi il pannello quando navighi
  router.get(route('dashboard'), { folder:id }, { preserveScroll:true, preserveState:true })
}

/* ----------------- CRUD cartelle ----------------- */
function createFolder(parentId = props.current?.id ?? null){
  if(!createForm.name.trim()) return
  createForm.parent_id = parentId
  createForm.post(route('folders.store'), {
    preserveScroll:true,
    onSuccess: () => {
      createForm.reset('name')
      router.reload({ only:['folders','current'] })
      pushToast('Cartella creata')
    }
  })
}
function renameFolder(folder){ renameForm.id = folder.id; renameForm.name = folder.name }
function saveRename(){
  if(!renameForm.id) return
  renameForm.put(route('folders.update', renameForm.id), {
    preserveScroll:true,
    onSuccess: () => {
      renameForm.reset()
      router.reload({ only:['folders','current'] })
      pushToast('Rinominata')
    }
  })
}
function deleteFolder(folder){
  if(!confirm('Eliminare questa cartella e i suoi collegamenti ai prompt?')) return
  router.delete(route('folders.destroy', folder.id), {
    preserveScroll:true,
    onSuccess: () => {
      router.reload({ only:['folders','current'] })
      pushToast('Eliminata')
    }
  })
}

/* ----------------- attach/detach prompt ----------------- */
function attachPrompt(promptId){
  if(!props.current?.id || !promptId) return
  attachForm.post(route('folders.attach', { folder: props.current.id, prompt: promptId }), {
    preserveScroll:true,
    onSuccess: () => { router.reload({ only:['current'] }); pushToast('Aggiunto') }
  })
}
function detachPromptFromCurrent(promptId){
  if(!props.current?.id) return
  detachForm.delete(route('folders.detach', { folder: props.current.id, prompt: promptId }), {
    preserveScroll:true,
    onSuccess: () => { router.reload({ only:['current'] }); pushToast('Rimosso') }
  })
}

/* ----------------- build tree (∞ livelli) ----------------- */
const tree = computed(() => buildTree(props.folders || []))

function buildTree(rows){
  const byId = new Map()
  rows.forEach(r => byId.set(r.id, { ...r, children: [] }))
  const roots = []
  rows.forEach(r => {
    const node = byId.get(r.id)
    if (r.parent_id && byId.has(r.parent_id)) {
      byId.get(r.parent_id).children.push(node)
    } else {
      roots.push(node)
    }
  })
  const sortKids = (n) => {
    n.children.sort((a,b) => (a.sort - b.sort) || a.name.localeCompare(b.name))
    n.children.forEach(sortKids)
  }
  roots.forEach(sortKids)
  return roots
}

/* ----------------- indice figli & discendenti ----------------- */
const childrenMap = computed(() => {
  const map = new Map()
  for (const f of (props.folders || [])) {
    const key = f.parent_id ?? 0
    if (!map.has(key)) map.set(key, [])
    map.get(key).push(f.id)
  }
  return map
})
function getDescendantsIds(id){
  const out = []
  const stack = [id]
  while (stack.length){
    const cur = stack.pop()
    const kids = childrenMap.value.get(cur) || []
    for (const k of kids) {
      out.push(k)
      stack.push(k)
    }
  }
  return out
}

/* ----------------- expand/collapse state (persist) ----------------- */
const expanded = ref(new Set()) // Set<number>
const EXPAND_KEY = 'folders:expanded'

onMounted(() => {
  try {
    const saved = JSON.parse(localStorage.getItem(EXPAND_KEY) || '[]')
    expanded.value = new Set(saved)
  } catch { expanded.value = new Set() }
})
watch(expanded, (set) => {
  localStorage.setItem(EXPAND_KEY, JSON.stringify(Array.from(set)))
}, { deep:true })

function isOpen(id){ return expanded.value.has(id) }
function toggle(id){
  const s = new Set(expanded.value)
  s.has(id) ? s.delete(id) : s.add(id)
  expanded.value = s
}
// globali (opzionali)
function expandAll(){ expanded.value = new Set((props.folders || []).map(f => f.id)) }
function collapseAll(){ expanded.value = new Set() }
// per-cartella
function expandNodeAndChildren(id){
  const ids = getDescendantsIds(id)
  const s = new Set(expanded.value); s.add(id); ids.forEach(x => s.add(x)); expanded.value = s
}
function collapseNodeAndChildren(id){
  const ids = getDescendantsIds(id)
  const s = new Set(expanded.value); s.delete(id); ids.forEach(x => s.delete(x)); expanded.value = s
}

/* ----------------- lista visibile (no ricorsione nel DOM) ----------------- */
const visibleNodes = computed(() => {
  const out = []
  const walk = (node, level=0) => {
    out.push({ node, level })
    if (isOpen(node.id)) {
      for (const c of node.children) walk(c, level+1)
    }
  }
  for (const r of tree.value) walk(r, 0)
  return out
})
</script>

<template>
  <AuthenticatedLayout>
    <!-- Tipografia più grande e padding ridotti -->
    <Head title="Dashboard" />
    <div class="min-h-screen bg-gray-50 text-[15px] md:text-base">
      <div class="max-w-screen-2xl mx-auto px-3 md:px-6 py-4 md:py-6">
        <div v-if="toast" class="fixed top-3 right-3 bg-black text-white px-3 py-2 rounded-lg shadow">
          {{ toast }}
        </div>

        <!-- Header: titolo + toggle sidebar su mobile -->
        <div class="flex items-center justify-between mb-3 md:mb-4">
          <div class="flex items-center gap-2">
            <button
              class="lg:hidden inline-flex items-center gap-2 px-3 py-2 rounded-xl border bg-white hover:bg-gray-50"
              @click="showFolders = !showFolders">
              <svg viewBox="0 0 24 24" class="w-4 h-4"><path fill="currentColor" d="M3 6h18v2H3V6m0 5h18v2H3v-2m0 5h18v2H3v-2z"/></svg>
              <span>Cartelle</span>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-12 gap-4 md:gap-6">
          <!-- sidebar albero -->
          <aside
            :class="[
              'col-span-12 lg:col-span-4',
              showFolders ? 'block' : 'hidden',
              'lg:block'
            ]"
          >
            <div class="bg-white border rounded-2xl shadow-sm p-3 md:p-4">
              <div class="flex items-center justify-between mb-3">
                <h2 class="text-base md:text-lg font-semibold">Le tue cartelle</h2>
                <div class="flex items-center gap-1">
                  <button @click="expandAll" class="text-xs md:text-[13px] px-2 py-1 rounded border bg-white hover:bg-gray-50">Espandi</button>
                  <button @click="collapseAll" class="text-xs md:text-[13px] px-2 py-1 rounded border bg-white hover:bg-gray-50">Compatta</button>
                </div>
              </div>

              <!-- creare nuova cartella -->
              <div class="flex items-center gap-2 mb-3">
                <input v-model="createForm.name" placeholder="Nuova cartella…"
                       class="flex-1 border rounded-lg px-3 py-2 text-sm md:text-[15px]" />
                <button @click="createFolder(null)"
                        class="px-3 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                  Crea
                </button>
              </div>

              <!-- lista piatta dei nodi visibili -->
              <div class="space-y-0.5">
                <div
                  v-for="row in visibleNodes"
                  :key="row.node.id"
                  class="select-none">
                  <div class="flex items-center justify-between group py-1.5">
                    <div class="flex items-center gap-1 min-w-0"
                         :style="{ paddingLeft: (row.level * 14) + 'px' }">
                      <!-- caret -->
                      <button
                        class="w-6 h-6 flex items-center justify-center rounded hover:bg-gray-100"
                        @click="toggle(row.node.id)"
                        :aria-label="isOpen(row.node.id) ? 'Chiudi' : 'Apri'">
                        <svg v-if="row.node.children?.length" viewBox="0 0 24 24" class="w-4 h-4 text-gray-500">
                          <path v-if="isOpen(row.node.id)" d="M19 13H5v-2h14v2z" fill="currentColor"/>
                          <path v-else d="M19 13H13v6h-2v-6H5v-2h6V5h2v6h6v2z" fill="currentColor"/>
                        </svg>
                        <span v-else class="inline-block w-4 h-4"></span>
                      </button>

                      <!-- label -->
                      <button class="text-left truncate flex-1 text-[15px] md:text-base hover:underline"
                              @click="openFolder(row.node.id)">
                        📁 {{ row.node.name }}
                        <span class="text-xs md:text-[13px] text-gray-500">({{ row.node.direct_prompts_count }})</span>
                      </button>
                    </div>

                    <!-- azioni del nodo -->
                    <div class="opacity-0 group-hover:opacity-100 transition flex items-center gap-1">
                      <button class="text-[11px] md:text-xs px-2 py-0.5 rounded bg-gray-100"
                              @click="expandNodeAndChildren(row.node.id)" title="Espandi discendenti">↘︎</button>
                      <button class="text-[11px] md:text-xs px-2 py-0.5 rounded bg-gray-100"
                              @click="collapseNodeAndChildren(row.node.id)" title="Compatta discendenti">↙︎</button>
                      <button class="text-[11px] md:text-xs px-2 py-0.5 rounded bg-gray-100"
                              @click="renameFolder(row.node)">Rinomina</button>
                      <button class="text-[11px] md:text-xs px-2 py-0.5 rounded bg-red-600 text-white"
                              @click="deleteFolder(row.node)">Elimina</button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- rename bar -->
              <div v-if="renameForm.id" class="mt-4 p-3 bg-gray-50 rounded-xl border">
                <div class="text-xs text-gray-500 mb-1">Rinomina cartella</div>
                <div class="flex gap-2">
                  <input v-model="renameForm.name" class="flex-1 border rounded px-2 py-2 text-sm md:text-[15px]" />
                  <button @click="saveRename" class="px-3 py-2 rounded bg-indigo-600 text-white">Salva</button>
                  <button @click="renameForm.reset()" class="px-3 py-2 rounded border">Annulla</button>
                </div>
              </div>
            </div>
          </aside>

          <!-- contenuto cartella -->
          <main class="col-span-12 md:col-span-8">
            <div class="bg-white border rounded-2xl shadow-sm">
              <div class="px-3 md:px-5 py-3 md:py-4 border-b">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <h2 class="text-base md:text-lg font-semibold">
                      {{ current ? `${current.name}` : 'Nessuna cartella selezionata' }}
                    </h2>
                    <p class="text-[13px] md:text-[14px] text-gray-500 mt-1" v-if="current">
                      {{ current.prompts?.length || 0 }} prompt
                    </p>
                  </div>

                  <!-- Toggle azioni (partono nascoste) -->
                  <div v-if="current" class="flex flex-wrap items-center gap-2">
                    <button
                      class="px-3 py-2 rounded-xl border bg-white hover:bg-gray-50 text-sm"
                      @click="showCreateSubfolder = !showCreateSubfolder">
                      {{ showCreateSubfolder ? 'Nascondi sottocartella' : 'Nuova sottocartella' }}
                    </button>
                    <button
                      class="px-3 py-2 rounded-xl border bg-white hover:bg-gray-50 text-sm"
                      @click="showAttachPrompt = !showAttachPrompt">
                      {{ showAttachPrompt ? 'Nascondi aggiungi prompt' : 'Aggiungi prompt' }}
                    </button>

                    <Link
                      :href="route('prompts.create', { from: 'dashboard', folder: current.id })"
                      class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-sm border border-indigo-200
                             bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                      Nuovo Prompt
                    </Link>
                  </div>
                </div>

                <!-- Form "Nuova sottocartella" (toggle) -->
                <div v-if="current && showCreateSubfolder" class="mt-3">
                  <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                    <input
                      v-model="createForm.name"
                      placeholder="Nuova sottocartella…"
                      class="border rounded px-3 py-2 text-sm md:text-[15px] flex-1"
                    />
                    <button
                      @click="createFolder(current.id)"
                      class="px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                      Crea
                    </button>
                  </div>
                </div>
              </div>

              <!-- attach rapido: aggiungi i tuoi prompt alla cartella (toggle) -->
              <div v-if="current && showAttachPrompt" class="px-3 md:px-5 py-3 border-b bg-gray-50">
                <div class="flex flex-wrap items-center gap-3 justify-between">
                  <div class="flex items-center gap-2">
                    <div class="relative">
                      <select
                        v-model="attachForm.prompt_id"
                        class="appearance-none border rounded-md pl-3 pr-8 py-2 text-sm md:text-[15px] text-gray-700 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-200 transition">
                        <option :value="null">Seleziona un prompt…</option>
                        <option v-for="p in myPrompts" :key="p.id" :value="p.id">{{ p.title }}</option>
                      </select>
                      <svg class="w-4 h-4 absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                           fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                      </svg>
                    </div>
                    <button
                      @click="attachPrompt(attachForm.prompt_id)"
                      :disabled="!attachForm.prompt_id"
                      class="inline-flex items-center gap-1 px-3 py-2 rounded-md text-sm md:text-[15px] font-medium border shadow-sm transition
                             bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                      <span>Aggiungi</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- lista prompt nella cartella -->
              <div class="p-3 md:p-5" v-if="current">
                <div v-if="current.prompts?.length" class="divide-y rounded-xl border">
                  <div v-for="p in current.prompts" :key="p.id" class="flex items-center justify-between px-3 py-3 md:px-4">
                    <div class="min-w-0">
                      <Link :href="route('prompts.show', { prompt: p.id, from: 'dashboard', folder: current.id })" class="font-medium truncate">
                        {{ p.title }}
                      </Link>
                      <div class="text-[13px] md:text-sm text-gray-500 mt-1 truncate">
                        <span v-for="t in p.tags" :key="t.id" class="mr-2">#{{ t.name }}</span>
                      </div>
                    </div>
                    <div class="shrink-0 flex items-center gap-2">
                      <Link :href="route('prompts.show', { prompt: p.id, from: 'dashboard', folder: current.id })"
                            class="text-xs md:text-[13px] px-2 py-1 rounded bg-gray-100">
                        Apri
                      </Link>
                      <button @click="detachPromptFromCurrent(p.id)"
                              class="text-xs md:text-[13px] px-2 py-1 rounded bg-red-600 text-white">
                        Rimuovi
                      </button>
                    </div>
                  </div>
                </div>
                <div v-else class="text-sm text-gray-500">Questa cartella è vuota.</div>
              </div>

              <div class="p-5" v-else>
                <p class="text-sm md:text-[15px] text-gray-600">Seleziona una cartella a sinistra oppure creane una nuova.</p>
              </div>
            </div>
          </main>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
