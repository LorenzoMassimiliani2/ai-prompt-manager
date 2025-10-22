<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
  prompts: Object,
  filters: Object,
  tags: Array,
  can: Object,
  auth: Object
})

/* ----------------- stato UI responsive ----------------- */
const showFilters = ref(false) // mobile: apri/chiudi pannello tag

/* ----------------- form filtri ----------------- */
const form = useForm({
  q: props.filters?.q ?? '',
  tags: Array.isArray(props.filters?.tags) ? props.filters.tags.map(t => +t) : [],
  view: props.filters?.view ?? 'grid', // grid | list | details
})

const search = () =>
  form.get(route('prompts.index'), { preserveState: true, preserveScroll: true })

const toggleTag = (id) => {
  const i = form.tags.indexOf(id)
  if (i === -1) form.tags.push(id)
  else form.tags.splice(i, 1)
  search()
}

const setView = (v) => { form.view = v; search() }

/* ----------------- gestione tag (solo superuser) ----------------- */
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
  <AuthenticatedLayout>
    <Head title="Prompts" />

    <!-- Tipografia leggermente più grande + padding compatti -->
    <div class="min-h-screen bg-gray-50 text-[15px] md:text-base">
      <div class="max-w-screen-2xl mx-auto px-3 md:px-6 py-4 md:py-6 space-y-4 md:space-y-6">

        <!-- Header / Toolbar -->
        <div class="flex flex-col lg:flex-row lg:items-center gap-3">
          <!-- Ricerca -->
          <form @submit.prevent="search" class="flex-1">
            <div class="relative">
              <input
                v-model="form.q"
                placeholder="Cerca prompt…"
                class="w-full border rounded-xl pl-10 pr-3 py-2 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-200"
              />
              <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24">
                <path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79L20 21.49 21.49 20 15.5 14m-6 0C7 14 5 12 5 9.5S7 5 9.5 5 14 7 14 9.5 12 14 9.5 14Z"/>
              </svg>
            </div>
          </form>

          <!-- Vista -->
          <div class="flex items-center gap-2">
            <label for="view" class="sr-only">Vista</label>
            <select
              id="view"
              v-model="form.view"
              @change="setView(form.view)"
              class="border rounded-xl px-3 py-2 w-32 bg-white shadow-sm"
            >
              <option value="grid">Griglia</option>
              <option value="list">Lista</option>
              <option value="details">Dettagli</option>
            </select>

            <!-- Nuovo -->
            <Link
              v-if="can?.create || props.auth?.userId"
              :href="route('prompts.create', { from: 'prompts' })"
              class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl hover:bg-indigo-700 shadow-sm">
              <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="currentColor" d="M19 13H13v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
              <span>Nuovo</span>
            </Link>

            <!-- Mobile: apri filtri tag -->
            <button
              class="lg:hidden inline-flex items-center gap-2 px-3 py-2 rounded-xl border bg-white hover:bg-gray-50"
              @click="showFilters = true">
              <svg viewBox="0 0 24 24" class="w-4 h-4"><path fill="currentColor" d="M3 6h18v2H3V6m3 5h12v2H6v-2m3 5h6v2H9v-2z"/></svg>
              Filtri
            </button>
          </div>
        </div>

        <!-- Filtri tag (desktop) -->
        <div class="hidden lg:flex flex-wrap items-center gap-2">
          <button
            v-for="t in tags"
            :key="t.id"
            @click="toggleTag(t.id)"
            :class="[
              'px-3 py-1.5 rounded-full text-sm border shadow-sm transition',
              form.tags.includes(t.id)
                ? 'bg-indigo-50 border-indigo-300 text-indigo-700'
                : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50'
            ]">
            # {{ t.name }}
          </button>

          <button
            v-if="props.can?.manageTags"
            @click="openTagModal"
            class="ml-auto px-3 py-1.5 rounded-full text-sm border bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
            Gestisci tag
          </button>
        </div>

        <!-- Filtri tag (mobile drawer) -->
        <div
          v-if="showFilters"
          class="fixed inset-0 z-50 lg:hidden"
          aria-modal="true" role="dialog"
        >
          <div class="absolute inset-0 bg-black/40" @click="showFilters=false"></div>
          <div class="absolute inset-x-0 bottom-0 bg-white rounded-t-2xl shadow-2xl p-4 space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-base font-semibold">Filtra per tag</h3>
              <button class="px-3 py-1 rounded bg-gray-100" @click="showFilters=false">Chiudi</button>
            </div>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="t in tags"
                :key="t.id"
                @click="toggleTag(t.id)"
                :class="[
                  'px-3 py-1.5 rounded-full text-sm border shadow-sm transition',
                  form.tags.includes(t.id)
                    ? 'bg-indigo-50 border-indigo-300 text-indigo-700'
                    : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50'
                ]">
                # {{ t.name }}
              </button>
            </div>

            <div class="pt-2">
              <button
                class="w-full px-4 py-2 rounded-xl bg-indigo-600 text-white"
                @click="showFilters=false">
                Applica
              </button>
            </div>
          </div>
        </div>

        <!-- Viste -->
        <div v-if="form.view==='grid'" class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3 md:gap-4">
          <article
            v-for="p in prompts.data"
            :key="p.id"
            class="border rounded-2xl p-4 bg-white hover:shadow-sm transition">
            <h3 class="font-semibold text-[16px] line-clamp-1">
              <Link :href="route('prompts.show', { prompt: p.id, from:'prompts' })">{{ p.title }}</Link>
            </h3>
            <p class="text-[13px] md:text-sm text-gray-700 mt-2 line-clamp-4 whitespace-pre-wrap">
              {{ p.content }}
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
              <span
                v-for="t in p.tags"
                :key="t.id"
                class="text-xs bg-gray-100 px-2 py-1 rounded">
                #{{ t.name }}
              </span>
            </div>
          </article>
        </div>

        <div v-else-if="form.view==='list'" class="rounded-2xl border bg-white overflow-hidden">
          <div
            v-for="p in prompts.data"
            :key="p.id"
            class="p-4 flex justify-between gap-4 border-b last:border-b-0 hover:bg-gray-50">
            <div class="min-w-0">
              <Link :href="route('prompts.show', { prompt: p.id, from:'prompts' })" class="font-medium truncate">
                {{ p.title }}
              </Link>
              <div class="text-[13px] text-gray-500 mt-1 truncate">
                <span v-for="t in p.tags" :key="t.id" class="mr-2">#{{ t.name }}</span>
              </div>
            </div>
            <div class="shrink-0">
              <Link :href="route('prompts.edit', { prompt: p.id, from:'prompts' })"
                class="text-sm px-3 py-1.5 rounded-xl border bg-white hover:bg-gray-100">
                Modifica
              </Link>
            </div>
          </div>
        </div>

        <div v-else class="space-y-3 md:space-y-4">
          <article
            v-for="p in prompts.data"
            :key="p.id"
            class="border rounded-2xl p-4 bg-white hover:shadow-sm transition">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
              <h3 class="font-semibold text-[16px] sm:text-lg">{{ p.title }}</h3>
              <div class="flex gap-2">
                <Link :href="route('prompts.edit', { prompt: p.id, from:'prompts' })"
                  class="px-3 py-1.5 rounded-xl border bg-white hover:bg-gray-100 text-sm">
                  Modifica
                </Link>
                <Link :href="route('prompts.show', { prompt: p.id, from:'prompts' })"
                  class="px-3 py-1.5 rounded-xl bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                  Apri
                </Link>
              </div>
            </div>
            <div class="mt-2 text-[14px] md:text-[15px] text-gray-700 whitespace-pre-wrap">
              {{ p.content }}
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <span v-for="t in p.tags" :key="t.id" class="text-xs bg-gray-100 px-2 py-1 rounded">
                #{{ t.name }}
              </span>
            </div>
          </article>
        </div>

        <!-- Pagination -->
        <div v-if="prompts.links && prompts.links.length > 3" class="pt-2 flex flex-wrap justify-center gap-2">
          <Link
            v-for="l in prompts.links"
            :key="(l.url || '') + l.label"
            :href="l.url || '#'"
            v-html="l.label"
            :class="[
              'px-3 py-1.5 rounded-xl border',
              l.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white hover:bg-gray-50'
            ]"
          />
        </div>
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
            <input v-model="tagCreate.name" placeholder="Nuovo tag..." class="flex-1 border rounded-xl px-3 py-2" />
            <button @click="createTag" :disabled="tagCreate.processing" class="px-3 py-2 rounded-xl bg-indigo-600 text-white">
              Aggiungi
            </button>
          </div>

          <!-- Cerca -->
          <input v-model="tagQuery" placeholder="Cerca tag..." class="w-full border rounded-xl px-3 py-2" />

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
                  <button @click="updateTag" :disabled="tagEdit.processing" class="px-2 py-1 rounded bg-indigo-600 text-white text-sm">
                    Salva
                  </button>
                  <button @click="cancelEditTag" class="px-2 py-1 rounded bg-gray-100 text-sm">
                    Annulla
                  </button>
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
  </AuthenticatedLayout>
</template>
