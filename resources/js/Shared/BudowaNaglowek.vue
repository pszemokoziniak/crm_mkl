<template>
  <div class="mb-8">
    <!-- Jeden nagłówek dla wszystkich podstron budowy: zawsze widać, o którą
         budowę chodzi, i zawsze wraca się tak samo. -->
    <h1 class="text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/budowy">Budowa</Link>
      <span class="text-indigo-400 font-medium">/</span>
      <Link v-if="budId" :href="`/budowy/${budId}/edit`" class="hover:text-indigo-600">{{ nazwa || '—' }}</Link>
      <span v-else>{{ nazwa || '—' }}</span>
    </h1>
    <p v-if="tytul" class="mt-1 text-lg text-gray-500">{{ tytul }}</p>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'

export default {
  components: { Link },
  props: {
    budId: { type: [Number, String], default: null },
    nazwa: { type: String, default: '' },
    tytul: { type: String, default: '' },
  },
}
</script>
