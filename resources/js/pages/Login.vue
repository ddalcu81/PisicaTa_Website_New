<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import EditorialLayout from '../Components/EditorialLayout.vue'
import FieldError from '../Components/FieldError.vue'

const form = useForm({ email: '', password: '' })
</script>

<template>
  <Head title="Autentificare — Pisica Ta" />
  <EditorialLayout>
    <form class="editor-panel login-panel" @submit.prevent="form.post('/login', { onFinish: () => form.reset('password') })">
      <h1>Autentificare</h1>
      <label>Adresă de email<input v-model="form.email" type="email" name="email" required autocomplete="username" /></label>
      <FieldError :message="form.errors.email" />
      <label>Parolă<input v-model="form.password" type="password" name="password" required autocomplete="current-password" /></label>
      <FieldError :message="form.errors.password" />
      <button class="button primary" :disabled="form.processing">Intră în redacție</button>
    </form>
  </EditorialLayout>
</template>
