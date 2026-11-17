<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import CategoryForm from '../Components/CategoryForm.vue'
import EditorialLayout from '../Components/EditorialLayout.vue'

type Category = { id: number; name: string; slug: string; description?: string; position: number; parent_id: number | null; articles_count: number }
const props = defineProps<{ categories: Category[] }>()
const parents = computed(() => props.categories.filter(category => category.parent_id === null))
const editing = ref<number | null>(null)
const active = computed(() => props.categories.find(category => category.id === editing.value))
</script>

<template>
  <Head title="Categorii — Redacție" />
  <EditorialLayout>
    <div class="editor-title"><h1>Categorii</h1></div>
    <div class="edit-grid categories-grid">
      <div class="editor-panel category-list">
        <div v-for="category in categories" :key="category.id">
          <span>{{ category.name }} <small>{{ category.articles_count }} articole</small></span>
          <button class="button secondary" type="button" @click="editing = category.id">Editează</button>
        </div>
      </div>
      <section class="editor-panel">
        <div class="section-heading">
          <h2>{{ active ? 'Editează categoria' : 'Categorie nouă' }}</h2>
          <button v-if="active" class="button secondary" type="button" @click="editing = null">Categorie nouă</button>
        </div>
        <CategoryForm :key="active?.id ?? 'new'" :category="active" :parents="parents" />
      </section>
    </div>
  </EditorialLayout>
</template>
