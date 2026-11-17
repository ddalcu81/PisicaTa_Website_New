<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import EditorialLayout from '../Components/EditorialLayout.vue'

type Entry = { id: number; title: string; category: string; status: string; published_on: string | null; edit_url: string; public_url: string | null }
const props = defineProps<{
  articles: { data: Entry[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null }
  filters: { q: string; status: string }
}>()
const filters = reactive({ ...props.filters })
const search = () => router.get('/admin/articole', filters, { preserveState: true })
</script>

<template>
  <Head title="Articole — Redacție" />
  <EditorialLayout>
    <div class="editor-title">
      <h1>Articole</h1>
      <Link class="button primary" href="/admin/articole/create">Articol nou <span aria-hidden="true">+</span></Link>
    </div>
    <form class="editor-filters" @submit.prevent="search">
      <label class="sr-only" for="editor-query">Caută după titlu</label>
      <input id="editor-query" v-model="filters.q" type="search" placeholder="Caută după titlu…" />
      <label class="sr-only" for="editor-status">Stare</label>
      <select id="editor-status" v-model="filters.status">
        <option value="">Toate stările</option>
        <option value="published">Publicate</option>
        <option value="draft">Ciorne</option>
      </select>
      <button class="button secondary">Caută</button>
    </form>
    <div v-if="articles.data.length" class="editor-panel editor-table">
      <table>
        <thead><tr><th>Titlu</th><th>Categorie</th><th>Stare</th><th>Data publicării</th><th><span class="sr-only">Acțiuni</span></th></tr></thead>
        <tbody>
          <tr v-for="article in articles.data" :key="article.id">
            <th scope="row"><Link :href="article.edit_url">{{ article.title }}</Link></th>
            <td>{{ article.category }}</td>
            <td><span class="state" :class="article.status">{{ article.public_url ? 'Publicat' : article.status === 'published' ? 'Programat' : 'Ciornă' }}</span></td>
            <td>{{ article.published_on || '—' }}</td>
            <td><Link class="text-link" :href="article.edit_url">Editează</Link></td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="empty">Nu sunt articole pentru această selecție.</p>
    <nav v-if="articles.last_page > 1" class="pagination" aria-label="Paginare">
      <Link v-if="articles.prev_page_url" :href="articles.prev_page_url">← Anterioară</Link>
      <span>Pagina {{ articles.current_page }} din {{ articles.last_page }}</span>
      <Link v-if="articles.next_page_url" :href="articles.next_page_url">Următoare →</Link>
    </nav>
  </EditorialLayout>
</template>
