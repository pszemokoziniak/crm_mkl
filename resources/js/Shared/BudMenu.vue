<template>
  <!-- Pasek zakładek budowy. Było tu logo z pustym <span> po szablonie
       startowym; osiem pozycji było wpisanych ręcznie jedna po drugiej. -->
  <nav class="mb-6 md:-mx-1 md:border-b md:border-gray-200">
    <!-- Telefon: kafelki zawijane w wiersze — pasek przewijany w bok był
         niewygodny kciukiem i chował pozycje za krawędzią. -->
    <div class="flex flex-wrap gap-2 md:hidden">
      <Link
        v-for="zakladka in widoczne"
        :key="`k-${zakladka.klucz}`"
        :href="zakladka.adres(budId)"
        class="px-3 py-1.5 rounded-full border text-sm transition-colors"
        :class="isUrl(zakladka.klucz)
          ? 'bg-green-700 border-green-700 text-white font-semibold'
          : 'bg-white border-gray-300 text-gray-700 hover:border-indigo-400'"
      >{{ zakladka.nazwa }}</Link>
    </div>
    <!-- Zawijanie: dwanaście zakładek nie mieści się w jednej linii na węższym
         monitorze i wychodziło poza ekran; druga linia jest lepsza niż ucięcie. -->
    <div class="hidden md:flex md:flex-wrap gap-x-4 gap-y-1 whitespace-nowrap px-1 pb-2">
      <Link
        v-for="zakladka in widoczne"
        :key="zakladka.klucz"
        :href="zakladka.adres(budId)"
        class="flex-shrink-0 pb-1 border-b-2 transition-colors"
        :class="isUrl(zakladka.klucz)
          ? 'border-green-700 text-green-800 font-bold'
          : 'border-transparent text-indigo-300 hover:text-indigo-600'"
      >{{ zakladka.nazwa }}</Link>
    </div>
  </nav>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'

export default {
  name: 'BudMenu',
  components: { Link },
  props: {
    budId: Number,
  },
  data() {
    return {
      zakladki: [
        { klucz: 'pracownicy', nazwa: 'Pracownicy', adres: (id) => `/pracownicy/${id}/` },
        { klucz: 'kierownictwo', nazwa: 'Kierownictwo', adres: (id) => `/budowy/${id}/kierownictwo` },
        { klucz: 'edit', nazwa: 'Dane budowy', adres: (id) => `/budowy/${id}/edit/` },
        { klucz: 'time-sheet', nazwa: 'KCP budowy', adres: (id) => `/building/${id}/time-sheet/` },
        { klucz: 'klient', nazwa: 'Dane klienta', adres: (id) => `/budowy/${id}/klient/` },
        { klucz: 'narzedzia', nazwa: 'Sprzęt', adres: (id) => `/budowy/${id}/narzedzia/` },
        { klucz: 'a1', nazwa: 'A1', adres: (id) => `/budowy/${id}/a1/` },
        { klucz: 'prognoza', nazwa: 'Prognoza', adres: (id) => `/budowy/${id}/prognoza` },
      ],
    }
  },
  computed: {
    /**
     * A1 potwierdza ubezpieczenie przy wysyłce za granicę, więc na budowie
     * w Polsce ta zakładka nie ma czego pokazać. Kraj bierzemy z budowy
     * z adresu — o wymogu decyduje słownik krajów, nie ten plik.
     */
    widoczne() {
      const budowa = this.$page.props.budowa

      if (budowa && budowa.wymaga_a1 === false) {
        return this.zakladki.filter((z) => z.klucz !== 'a1')
      }

      return this.zakladki
    },
  },
  methods: {
    isUrl(klucz) {
      return this.$page.url.includes(klucz)
    },
  },
}
</script>
