<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage<{ user: { name: string } | null; message: string | null }>()
const articlePage = computed(() => page.url.startsWith('/admin/articole'))
</script>

<template>
  <header class="editor-header">
    <div class="wrap editor-nav">
      <a class="brand" href="/"><span>Pisica <em>Ta</em><span class="brand-period">.</span></span></a>
      <nav v-if="page.props.user" aria-label="Redacție">
        <Link href="/admin/articole" :aria-current="articlePage ? 'page' : undefined">Articole</Link>
        <Link href="/admin/categorii" :aria-current="!articlePage ? 'page' : undefined">Categorii</Link>
        <Link href="/admin/logout" method="post" as="button">Deconectare</Link>
      </nav>
    </div>
  </header>
  <main class="wrap editor">
    <p v-if="page.props.message" class="editor-notice" role="status">{{ page.props.message }}</p>
    <slot />
  </main>
</template>
