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
    <!-- Kierownik ogląda kartę tylko do odczytu; brak dokumentu zgłasza
         kadrom stąd, z każdej podstrony, z domyślnie wybraną tą zakładką. -->
    <div v-if="pracownik && pracownik.zgloszenie" class="mt-3">
      <button type="button" class="text-sm text-indigo-600 hover:underline" @click="zgloszenieOtwarte = true">
        Zgłoś brak dokumentu do kadr
      </button>
      <zgloszenie-do-kadr
        :otwarte="zgloszenieOtwarte"
        :organization-id="pracownik.zgloszenie.organization_id"
        :budowa="pracownik.zgloszenie.budowa"
        :pracownik="{ id: pracownik.id, nazwa: pracownik.nazwa }"
        :start="{ rodzaj: 'dokument', dokument: dokumentZAdresu }"
        tytul="Zgłoś brak dokumentu"
        @zamknij="zgloszenieOtwarte = false"
      />
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'
import ZgloszenieDoKadr from '@/Shared/ZgloszenieDoKadr.vue'

export default {
  components: { Link, ZgloszenieDoKadr },
  props: {
    /**
     * Dane pracownika z kontrolera: { id, nazwa }. Wcześniej połowa ekranów
     * podawała sam tekst z nazwiskiem, a połowa obiekt — ten sam nagłówek
     * wywoływało się na dwa sposoby i łatwo było pomylić się o jeden.
     */
    pracownik: { type: Object, default: null },
    tytul: { type: String, default: '' },
  },
  data() {
    return { zgloszenieOtwarte: false }
  },
  computed: {
    // Z której zakładki otwarto formularz — ta jest domyślnym brakującym dokumentem.
    dokumentZAdresu() {
      const czesc = (this.$page.url.split('?')[0].split('/')[3] || '')
      return ['a1', 'badania', 'uprawnienia', 'bhp', 'pbioz'].includes(czesc) ? czesc : 'inne'
    },
    id() {
      return this.pracownik ? this.pracownik.id : null
    },
    nazwa() {
      return (this.pracownik && this.pracownik.nazwa) || '—'
    },
  },
}
</script>
