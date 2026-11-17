<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import FieldError from './FieldError.vue'

type Category = { id: number; name: string; slug: string; description?: string; position: number; parent_id: number | null; articles_count: number }
const props = defineProps<{ category?: Category; parents: Category[] }>()
const form = useForm({
  name: props.category?.name ?? '',
  slug: props.category?.slug ?? '',
  description: props.category?.description ?? '',
  position: props.category?.position ?? 99,
  parent_id: props.category?.parent_id ?? '',
})
function save() {
  if (props.category) {
    form.patch(`/admin/categorii/${props.category.id}`, { preserveScroll: true })
  } else {
    form.post('/admin/categorii', { preserveScroll: true, onSuccess: () => form.reset() })
  }
}
</script>

<template>
  <form class="category-form" @submit.prevent="save">
    <label>Nume<input v-model="form.name" required maxlength="255" /></label>
    <FieldError :message="form.errors.name" />
    <label>Adresă URL<input v-model="form.slug" required maxlength="160" :readonly="!!category" /></label>
    <FieldError :message="form.errors.slug" />
    <label>Descriere<textarea v-model="form.description" rows="2" maxlength="400" /></label>
    <FieldError :message="form.errors.description" />
    <div class="form-row">
      <label>Categorie părinte<select v-model="form.parent_id">
        <option value="">Categorie principală</option>
        <option v-for="parent in parents.filter(item => item.id !== category?.id)" :key="parent.id" :value="parent.id">{{ parent.name }}</option>
      </select></label>
      <label>Ordine<input type="number" v-model="form.position" min="0" max="999" required /></label>
    </div>
    <FieldError :message="form.errors.parent_id || form.errors.position" />
    <button class="button primary" :disabled="form.processing">Salvează categoria</button>
  </form>
</template>
