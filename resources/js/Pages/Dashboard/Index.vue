<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import NavTabs from '@/Components/NavTabs.vue'

const props = defineProps({
  tree: Array,       // cartelle radice con children ricorsivi (limite 3 livelli in esempio)
  current: Object,   // cartella selezionata con prompts
  myPrompts: Array,  // ultimi prompt dell'utente per attach rapido
})

// stato UI
const toast = ref(null)
function pushToast(msg){ toast.value = msg; setTimeout(()=> toast.value=null, 2000) }

const createForm = useForm({ name:'', parent_id: null })
const renameForm = useForm({ id:null, name:'' })
const moveForm   = useForm({ id:null, parent_id:null })
const attachForm = useForm({ prompt_id:null })
const detachForm = useForm({})

function openFolder(id){
  router.get(route('dashboard'), { folder:id }, { preserveScroll:true, preserveState:true })
}

// crea cartella sotto la corrente (o radice se null)
function createFolder(parentId = props.current?.id ?? null){
  if(!createForm.name.trim()) return
  createForm.parent_id = parentId
  createForm.post(route('folders.store'), {
    preserveScroll:true,
    onSuccess: () => { createForm.reset('name'); router.reload({ only:['tree','current'] }); pushToast('Cartella creata') }
  })
}

function renameFolder(folder){
  renameForm.id = folder.id
  renameForm.name = folder.name
}
function saveRename(){
  if(!renameForm.id) return
  renameForm.put(route('folders.update', renameForm.id), {
    preserveScroll:true,
    onSuccess: ()=> { renameForm.reset(); router.reload({ only:['tree','current'] }); pushToast('Rinominata') }
  })
}

function moveFolder(folder, newParentId){
  moveForm.id = folder.id
  moveForm.parent_id = newParentId
  moveForm.post(route('folders.move', folder.id), {
    preserveScroll:true,
    onSuccess: ()=> { router.reload({ only:['tree','current'] }); pushToast('Spostata') }
  })
}

function deleteFolder(folder){
  if(!confirm('Eliminare questa cartella e i suoi collegamenti ai prompt?')) return
  router.delete(route('folders.destroy', folder.id), {
    preserveScroll:true,
    onSuccess: ()=> { router.reload({ only:['tree','current'] }); pushToast('Eliminata') }
  })
}

function attachPrompt(promptId){
  if(!props.current?.id) return
  attachForm.post(route('folders.attach', { folder: props.current.id, prompt: promptId }), {
    preserveScroll:true,
    onSuccess: ()=> { router.reload({ only:['current'] }); pushToast('Aggiunto') }
  })
}
function detachPromptFromCurrent(promptId){
  if(!props.current?.id) return
  detachForm.delete(route('folders.detach', { folder: props.current.id, prompt: promptId }), {
    preserveScroll:true,
    onSuccess: ()=> { router.reload({ only:['current'] }); pushToast('Rimosso') }
  })
}

// helper: stampa albero ricorsivo
const Node = {
  props: ['node'],
  methods: {
    openFolder,
    renameFolder,
    moveFolder,
    deleteFolder,
  },
  template: `
    <div class="pl-3">
      <div class="flex items-center justify-between group py-1">
        <button class="text-left truncate flex-1 text-sm hover:underline" @click="openFolder(node.id)">
          📁 {{ node.name }}
        </button>
        <div class="opacity-0 group-hover:opacity-100 transition flex gap-1">
          <button class="text-xs px-2 py-0.5 rounded bg-gray-100" @click="renameFolder(node)">Rinomina</button>
          <button class="text-xs px-2 py-0.5 rounded bg-gray-100" @click="moveFolder(node, null)">Sposta su</button>
          <button class="text-xs px-2 py-0.5 rounded bg-red-600 text-white" @click="deleteFolder(node)">Elimina</button>
        </div>
      </div>
      <div v-if="node.children?.length" class="pl-3 border-l">
        <div v-for="c in node.children" :key="c.id">
          <Node :node="c" />
        </div>
      </div>
    </div>
  `
};
</script>

<template>
  <Head title="Dashboard" />
  <div class="min-h-screen bg-gray-50">
    <div class="flex items-center justify-between">
      <NavTabs />
    </div>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div v-if="toast" class="fixed top-4 right-4 bg-black text-white px-3 py-2 rounded">{{ toast }}</div>

      <div class="grid grid-cols-12 gap-6">
        <!-- sidebar albero -->
        <aside class="col-span-12 md:col-span-6 lg:col-span-5">
          <div class="bg-white border rounded-2xl shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-sm font-semibold">Le tue cartelle</h2>
              <div class="flex gap-1">
                <input v-model="createForm.name" placeholder="Nuova cartella…" class="w-36 border rounded px-2 py-1 text-sm" />
                <button @click="createFolder(null)" class="text-sm px-2 py-1 rounded bg-indigo-600 text-white">Crea</button>
              </div>
            </div>

            <div class="space-y-1">

              <div v-for="n in tree" :key="n.id" class="mb-1">
                <div class="flex items-center justify-between group">
                  <button class="text-left truncate flex-1 px-2 py-1 rounded hover:bg-gray-50" @click="openFolder(n.id)">
                    📁 {{ n.name }} 
                  </button>
                  <div class="opacity-0 group-hover:opacity-100 transition flex gap-1">
                    <button class="text-xs px-2 py-0.5 rounded bg-gray-100" @click="renameFolder(n)">Rinomina</button>
                    <button class="text-xs px-2 py-0.5 rounded bg-red-600 text-white" @click="deleteFolder(n)">Elimina</button>
                  </div>
                </div>
                <div v-if="n.children?.length" class="pl-3 border-l mt-1">
                  <div v-for="c in n.children" :key="c.id" class="mb-1">
                    <div class="flex items-center justify-between group">
                      <button class="text-left truncate flex-1 px-2 py-1 rounded hover:bg-gray-50" @click="openFolder(c.id)">
                        📁 {{ c.name }}
                      </button>
                      <div class="opacity-0 group-hover:opacity-100 transition flex gap-1">
                        <button class="text-xs px-2 py-0.5 rounded bg-gray-100" @click="renameFolder(c)">Rinomina</button>
                        <button class="text-xs px-2 py-0.5 rounded bg-gray-100" @click="moveFolder(c, n.parent_id)">Sposta su</button>
                        <button class="text-xs px-2 py-0.5 rounded bg-red-600 text-white" @click="deleteFolder(c)">Elimina</button>
                      </div>
                    </div>
                    <div v-if="c.children?.length" class="pl-3 border-l mt-1">
                      <div v-for="g in c.children" :key="g.id" class="mb-1">
                        <div class="flex items-center justify-between group">
                          <button class="text-left truncate flex-1 px-2 py-1 rounded hover:bg-gray-50" @click="openFolder(g.id)">
                            📁 {{ g.name }}
                          </button>
                          <div class="opacity-0 group-hover:opacity-100 transition flex gap-1">
                            <button class="text-xs px-2 py-0.5 rounded bg-gray-100" @click="renameFolder(g)">Rinomina</button>
                            <button class="text-xs px-2 py-0.5 rounded bg-gray-100" @click="moveFolder(g, c.parent_id)">Sposta su</button>
                            <button class="text-xs px-2 py-0.5 rounded bg-red-600 text-white" @click="deleteFolder(g)">Elimina</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div><!-- /roots -->
            </div>

            <!-- rename bar -->
            <div v-if="renameForm.id" class="mt-4 p-3 bg-gray-50 rounded-xl border">
              <div class="text-xs text-gray-500 mb-1">Rinomina cartella</div>
              <div class="flex gap-2">
                <input v-model="renameForm.name" class="flex-1 border rounded px-2 py-1 text-sm" />
                <button @click="saveRename" class="text-sm px-3 py-1 rounded bg-indigo-600 text-white">Salva</button>
                <button @click="renameForm.reset()" class="text-sm px-3 py-1 rounded border">Annulla</button>
              </div>
            </div>
          </div>
        </aside>

        <!-- contenuto cartella -->
        <main class="col-span-12 md:col-span-6 lg:col-span-7">
          <div class="bg-white border rounded-2xl shadow-sm">
            <div class="px-4 sm:px-6 py-4 border-b flex items-center justify-between">
              <div>
                <h2 class="text-sm font-semibold">
                  {{ current ? `Contenuto: ${current.name}` : 'Nessuna cartella selezionata' }}
                </h2>
                <p class="text-xs text-gray-500" v-if="current">ID #{{ current.id }} • {{ current.prompts?.length || 0 }} prompt</p>
              </div>
              <div class="flex items-center gap-2" v-if="current">
                <!-- crea sottocartella -->
                <div class="flex items-center gap-1">
                  <input v-model="createForm.name" placeholder="Nuova sottocartella…" class="border rounded px-2 py-1 text-sm" />
                  <button @click="createFolder(current.id)" class="text-sm px-2 py-1 rounded bg-indigo-600 text-white">Crea</button>
                </div>
              </div>
            </div>

            <!-- attach rapido: aggiungi i tuoi prompt alla cartella -->
            <div class="px-4 sm:px-6 py-3 border-b" v-if="current">
                <div class="flex flex-col gap-2">
                <select v-model="attachForm.prompt_id" class="border rounded px-2 py-1 text-sm">
                  <option :value="null">Seleziona prompt…</option>
                  <option v-for="p in myPrompts" :key="p.id" :value="p.id">{{ p.title }}</option>
                </select>
                <button @click="attachPrompt(attachForm.prompt_id)" v-if="attachForm.prompt_id"
                  class="text-sm px-3 py-1 rounded bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">Aggiungi alla cartella</button>
                <Link :href="route('prompts.create', { from: 'dashboard', folder: current.id })" class="text-sm px-3 py-1 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                  Crea nuovo prompt qui
                </Link>
                </div>
            </div>

            <!-- lista prompt nella cartella -->
            <div class="p-4 sm:p-6" v-if="current">
              <div v-if="current.prompts?.length" class="divide-y rounded-xl border">
                <div v-for="p in current.prompts" :key="p.id" class="flex items-center justify-between px-4 py-3">
                  <div class="min-w-0">
                    <Link :href="route('prompts.show', { prompt: p.id, from: 'dashboard', folder: current.id })" class="font-medium truncate">{{ p.title }}</Link>
                    <div class="text-xs text-gray-500 mt-1 truncate">
                      <span v-for="t in p.tags" :key="t.id" class="mr-2">#{{ t.name }}</span>
                    </div>
                  </div>
                  <div class="shrink-0 flex items-center gap-2">
                    <Link :href="route('prompts.show', { prompt: p.id, from: 'dashboard', folder: current.id })"  class="text-xs px-2 py-1 rounded bg-gray-100">Apri</Link>
                    <button @click="detachPromptFromCurrent(p.id)" class="text-xs px-2 py-1 rounded bg-red-600 text-white">Rimuovi</button>
                  </div>
                </div>
              </div>
              <div v-else class="text-sm text-gray-500">Questa cartella è vuota.</div>
            </div>

            <div class="p-6" v-else>
              <p class="text-sm text-gray-600">Seleziona una cartella a sinistra oppure creane una nuova.</p>
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>
