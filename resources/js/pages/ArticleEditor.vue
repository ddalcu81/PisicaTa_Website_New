<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import EditorialLayout from '../Components/EditorialLayout.vue'
import FieldError from '../Components/FieldError.vue'

const props = defineProps<{
  article: { id?: number; title?: string; slug?: string; summary?: string; body_html?: string; category_id?: number; status: 'draft' | 'published'; published_at?: string; image_alt?: string; has_image: boolean; public_url?: string }
  categories: { id: number; name: string }[]
  action: string
}>()
const form = useForm({
  _method: props.article.id ? 'patch' : 'post',
  title: props.article.title ?? '',
  slug: props.article.slug ?? '',
  summary: props.article.summary ?? '',
  body_html: props.article.body_html ?? '',
  category_id: props.article.category_id ?? '',
  status: props.article.status,
  published_at: props.article.published_at ?? '',
  image_alt: props.article.image_alt ?? '',
  image: null as File | null,
  remove_image: false,
})
function selectImage(event: Event) {
  form.image = (event.target as HTMLInputElement).files?.[0] ?? null
}
function suggestSlug() {
  if (!props.article.id && !form.slug) {
    form.slug = form.title.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase()
      .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 160).replace(/-$/, '')
  }
}
</script>

<template>
  <Head :title="article.id ? 'Editează articolul — Redacție' : 'Articol nou — Redacție'" />
  <EditorialLayout>
    <Link class="text-link" href="/admin/articole">← Articole</Link>
    <div class="editor-title">
      <h1>{{ article.id ? 'Editează articolul' : 'Articol nou' }}</h1>
      <a v-if="article.public_url" class="button secondary" :href="article.public_url">Vezi articolul ↗</a>
    </div>
    <form class="edit-grid" @submit.prevent="form.post(action, { forceFormData: true, preserveScroll: true })">
      <section class="editor-panel">
        <label>Titlu<input v-model="form.title" maxlength="255" required @blur="suggestSlug" /></label>
        <FieldError :message="form.errors.title" />
        <label>Adresă URL<input v-model="form.slug" maxlength="160" :readonly="!!article.id" required /></label>
        <FieldError :message="form.errors.slug" />
        <label>Rezumat<textarea v-model="form.summary" rows="3" maxlength="600" /></label>
        <FieldError :message="form.errors.summary" />
        <label>Conținut HTML<textarea v-model="form.body_html" class="html-input" rows="19" spellcheck="false" /></label>
        <FieldError :message="form.errors.body_html" />
        <p class="field-help">Poți folosi paragrafe, subtitluri, liste, tabele și legături.</p>
      </section>
      <div class="edit-sidebar">
        <section class="editor-panel">
          <h2>Publicare</h2>
          <label>Categorie<select v-model="form.category_id" required>
            <option disabled value="">Alege o categorie</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
          </select></label>
          <FieldError :message="form.errors.category_id" />
          <label>Stare<select v-model="form.status">
            <option value="draft">Ciornă</option>
            <option value="published">Publicat</option>
          </select></label>
          <FieldError :message="form.errors.status" />
          <label>Data publicării<input type="date" v-model="form.published_at" :required="form.status === 'published'" /></label>
          <FieldError :message="form.errors.published_at" />
          <button class="button primary" :disabled="form.processing">Salvează articolul</button>
        </section>
        <section class="editor-panel">
          <h2>Copertă</h2>
          <label>Imagine<input :key="article.has_image ? 'has-image' : 'generic'" type="file" accept="image/jpeg,image/png,image/webp" @change="selectImage" /></label>
          <FieldError :message="form.errors.image" />
          <label>Text alternativ<input v-model="form.image_alt" maxlength="255" /></label>
          <FieldError :message="form.errors.image_alt" />
          <label v-if="article.has_image" class="checkbox"><input v-model="form.remove_image" type="checkbox" /> Folosește coperta generică</label>
        </section>
      </div>
    </form>
  </EditorialLayout>
</template>
