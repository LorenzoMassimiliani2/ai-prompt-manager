<script setup>
import { Head, Link, useForm, router, } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import NavTabs from '@/Components/NavTabs.vue'  
const props = defineProps({ prompts:Object, filters:Object, tags:Array, can:Object })

const form = useForm({
  q: props.filters?.q ?? '',
  tags: Array.isArray(props.filters?.tags) ? props.filters.tags.map(t=>+t) : [],
  view: props.filters?.view ?? 'grid', // grid | list | details
})

const search = () => form.get(route('prompts.index'), { preserveState: true, preserveScroll: true })

const toggleTag = (id) => {
  const i = form.tags.indexOf(id)
  if (i === -1) form.tags.push(id); else form.tags.splice(i,1)
  search()
}

const setView = (v) => { form.view = v; search() }

const del = useForm({})

// --- TAG MANAGEMENT (solo superuser) ---
const tagModal = ref(false)
const tagQuery = ref('')
const filteredTags = computed(() => {
  const q = tagQuery.value.toLowerCase().trim()
  if (!q) return props.tags
  return props.tags.filter(t => t.name.toLowerCase().includes(q))
})

const tagCreate = useForm({ name: '' })
const tagEdit   = useForm({ id: null, name: '' })
const tagDelete = useForm({})

function openTagModal(){ tagModal.value = true }
function closeTagModal(){ tagModal.value = false; tagQuery.value=''; tagCreate.reset(); tagEdit.reset() }

function startEditTag(t){ tagEdit.id = t.id; tagEdit.name = t.name }
function cancelEditTag(){ tagEdit.reset() }

function createTag(){
  if(!tagCreate.name.trim()) return
  tagCreate.post(route('tags.store'), {
    preserveScroll: true,
    onSuccess: () => { router.reload({ only:['tags'] }); tagCreate.reset() }
  })
}

function updateTag(){
  if(!tagEdit.id || !tagEdit.name.trim()) return
  tagEdit.put(route('tags.update', tagEdit.id), {
    preserveScroll: true,
    onSuccess: () => { router.reload({ only:['tags'] }); tagEdit.reset() }
  })
}

function destroyTag(id){
  if(!confirm('Eliminare questo tag?')) return
  tagDelete.delete(route('tags.destroy', id), {
    preserveScroll: true,
    onSuccess: () => router.reload({ only:['tags'] })
  })
}

</script>

<template>
  <Head title="Prompts" />
   <div class="flex items-center justify-between">
      <NavTabs />
    </div>
  <div class="max-w-7xl mx-auto p-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center gap-3">
      <form @submit.prevent="search" class="flex-1">
        <input v-model="form.q" placeholder="Cerca prompt..." class="w-full border rounded-lg px-3 py-2" />
      </form>
    <div>
        <label for="view" class="sr-only">Vista</label>
        <select id="view" v-model="form.view" @change="setView(form.view)" class="border rounded-lg px-3 py-2 w-28">
            <option value="grid">Griglia</option>
            <option value="list">Lista</option>
            <option value="details">Dettagli</option>
        </select>
    </div>
      <div v-if="can?.create">
        <Link :href="route('prompts.create', { from: 'prompts' })" class="bg-green-600 text-white px-4 py-2 rounded-lg">+</Link>
      </div>
    </div>

    <!-- Filtri tag -->
    <div class="flex flex-wrap gap-2">
      <button
        v-for="t in tags" :key="t.id"
        @click="toggleTag(t.id)"
        :class="['px-2 py-1 rounded text-sm border',
          form.tags.includes(t.id) ? 'bg-indigo-50 border-indigo-300 text-indigo-700' : 'bg-white border-gray-200']">
        # {{ t.name }}
      </button>

      <!-- ... i tuoi toggle vista ... -->
      <button v-if="props.can?.manageTags"
              @click="openTagModal"
              class="px-2 py-1 rounded text-sm border bg-blue-600 hover:bg-blue-600 text-white">
        Gestisci tag
      </button>
      <div v-if="props.auth?.userId">
        <Link :href="route('prompts.create', { from: 'prompts' })" class="bg-indigo-600 text-white px-4 py-2 rounded-lg">Nuovo</Link>
      </div>
    </div>

    <!-- Viste -->
    <div v-if="form.view==='grid'" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      <article v-for="p in prompts.data" :key="p.id" class="border rounded-xl p-4 hover:shadow">
        <h3 class="font-semibold line-clamp-1">
          <Link :href="route('prompts.show', p.id)">{{ p.title }}</Link>
        </h3>
        <p class="text-sm text-gray-600 mt-2 line-clamp-3 whitespace-pre-wrap">{{ p.content }}</p>
        <div class="mt-3 flex flex-wrap gap-2">
          <span v-for="t in p.tags" :key="t.id" class="text-xs bg-gray-100 px-2 py-1 rounded">#{{ t.name }}</span>
        </div>
      </article>
    </div>

    <div v-else-if="form.view==='list'" class="divide-y border rounded-xl bg-white">
      <div v-for="p in prompts.data" :key="p.id" class="p-4 flex justify-between gap-4">
        <div class="min-w-0">
          <Link :href="route('prompts.show', p.id)" class="font-medium truncate">{{ p.title }}</Link>
          <div class="text-xs text-gray-500 mt-1 truncate">
            <span v-for="t in p.tags" :key="t.id" class="mr-2">#{{t.name}}</span>
          </div>
        </div>
        <div class="shrink-0">
          <Link :href="route('prompts.edit', p.id)" class="text-sm px-3 py-1 rounded bg-gray-100">Modifica</Link>
        </div>
      </div>
    </div>

    <div v-else class="space-y-4">
      <article v-for="p in prompts.data" :key="p.id" class="border rounded-xl p-4">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-lg">{{ p.title }}</h3>
          <div class="flex gap-2">
            <Link :href="route('prompts.edit', p.id)" class="px-3 py-1 rounded bg-gray-100 text-sm">Modifica</Link>
            <Link :href="route('prompts.show', p.id)" class="px-3 py-1 rounded bg-indigo-600 text-white text-sm">Apri</Link>
          </div>
        </div>
        <div class="mt-2 text-sm text-gray-600 whitespace-pre-wrap">{{ p.content }}</div>
        <div class="mt-3 flex flex-wrap gap-2">
          <span v-for="t in p.tags" :key="t.id" class="text-xs bg-gray-100 px-2 py-1 rounded">#{{ t.name }}</span>
        </div>
      </article>
    </div>

    <!-- Pagination -->
    <div v-if="prompts.links && prompts.links.length > 12" class="mt-6 flex justify-center gap-2">
      <Link v-for="l in prompts.links" :key="l.url + l.label" :href="l.url || '#'" v-html="l.label"
      :class="['px-3 py-1 rounded', l.active ? 'bg-indigo-600 text-white' : 'bg-gray-100']" />
    </div>
  </div>

  <!-- Modal Gestione Tag (solo superuser) -->
  <div v-if="tagModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden">
      <div class="p-4 border-b flex items-center justify-between">
        <h3 class="text-lg font-semibold">Gestisci tag</h3>
        <button @click="closeTagModal" class="px-3 py-1 rounded bg-gray-100">Chiudi</button>
      </div>

      <div class="p-4 space-y-4">
        <!-- Crea -->
        <div class="flex gap-2">
          <input v-model="tagCreate.name" placeholder="Nuovo tag..."
                class="flex-1 border rounded-xl px-3 py-2" />
          <button @click="createTag" :disabled="tagCreate.processing"
                  class="px-3 py-2 rounded-xl bg-indigo-600 text-white">
            Aggiungi
          </button>
        </div>

        <!-- Cerca -->
        <input v-model="tagQuery" placeholder="Cerca tag..."
              class="w-full border rounded-xl px-3 py-2" />

        <!-- Lista -->
        <div class="border rounded-xl overflow-hidden">
          <div class="grid grid-cols-12 bg-gray-50 px-3 py-2 text-sm">
            <div class="col-span-2">ID</div>
            <div class="col-span-6">Nome</div>
            <div class="col-span-4 text-right">Azioni</div>
          </div>

          <div v-for="t in filteredTags" :key="t.id" class="grid grid-cols-12 items-center px-3 py-2 border-t">
            <div class="col-span-2 text-sm text-gray-600">#{{ t.id }}</div>

            <div class="col-span-6">
              <template v-if="tagEdit.id === t.id">
                <input v-model="tagEdit.name" class="w-full border rounded-lg px-2 py-1 text-sm" />
              </template>
              <template v-else>
                <span class="text-sm">#{{ t.name }}</span>
              </template>
            </div>

            <div class="col-span-4 flex justify-end gap-2">
              <template v-if="tagEdit.id === t.id">
                <button @click="updateTag" :disabled="tagEdit.processing"
                        class="px-2 py-1 rounded bg-indigo-600 text-white text-sm">Salva</button>
                <button @click="cancelEditTag" class="px-2 py-1 rounded bg-gray-100 text-sm">Annulla</button>
              </template>
              <template v-else>
                <button @click="startEditTag(t)" class="px-2 py-1 rounded bg-gray-100 text-sm">Modifica</button>
                <button @click="destroyTag(t.id)" class="px-2 py-1 rounded bg-red-600 text-white text-sm">Elimina</button>
              </template>
            </div>
          </div>

          <div v-if="!filteredTags.length" class="px-3 py-6 text-center text-sm text-gray-500">
            Nessun tag trovato.
          </div>
        </div>
      </div>
    </div>
  </div>

</template>
