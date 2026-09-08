<template>
  <div class="mb-6 sm:mb-8">
    <!-- Jeden nagłówek dla podstron pracownika: zawsze widać, czyją kartę
         się ogląda, i zawsze wraca się tak samo. -->
    <h1 class="text-2xl sm:text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/contacts">Pracownik</Link>
      <span class="text-indigo-400 font-medium">/</span>
      <Link v-if="id" :href="`/contacts/${id}/edit`" class="hover:text-indigo-600">{{ nazwa }}</Link>
      <span v-else>{{ nazwa }}</span>
    </h1>
    <p v-if="tytul" class="mt-1 text-base sm:text-lg text-gray-500">{{ tytul }}</p>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'

export default {
  components: { Link },
  props: {
    /**
     * Dane pracownika z kontrolera: { id, nazwa }. Wcześniej połowa ekranów
     * podawała sam tekst z nazwiskiem, a połowa obiekt — ten sam nagłówek
     * wywoływało się na dwa sposoby i łatwo było pomylić się o jeden.
     */
    pracownik: { type: Object, default: null },
    tytul: { type: String, default: '' },
  },
  computed: {
    id() {
      return this.pracownik ? this.pracownik.id : null
    },
    nazwa() {
      return (this.pracownik && this.pracownik.nazwa) || '—'
    },
  },
}
</script>
