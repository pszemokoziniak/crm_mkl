<template>
  <div>
    <Head title="Ustawienia" />
    <h1 class="mb-6 text-3xl font-bold">Ustawienia</h1>

    <!-- Trzy grupy, żeby nie szukać jednego kafelka wśród dwunastu. -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200">
      <button
        v-for="zakladka in widoczneZakladki"
        :key="zakladka.klucz"
        type="button"
        class="px-4 py-2 -mb-px text-sm font-medium border-b-2"
        :class="wybrana === zakladka.klucz
          ? 'border-indigo-600 text-indigo-700'
          : 'border-transparent text-gray-500 hover:text-gray-800'"
        @click="wybrana = zakladka.klucz"
      >
        {{ zakladka.nazwa }}
      </button>
    </div>

    <p class="mb-4 text-sm text-gray-500">{{ opisWybranej }}</p>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
      <Link v-for="kafelek in widoczneKafelki" :key="kafelek.adres" class="btn-indigo text-center" :href="kafelek.adres">
        <span>{{ kafelek.nazwa }}</span>
      </Link>
    </div>

    <p v-if="widoczneKafelki.length === 0" class="text-sm text-gray-400">
      Nic tu dla Twoich uprawnień.
    </p>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'

export default {
  components: { Head, Link },
  layout: Layout,
  data() {
    return {
      wybrana: 'slowniki',
      zakladki: [
        { klucz: 'logowania', nazwa: 'Logowania', opis: 'Kto i kiedy wchodził do systemu.' },
        { klucz: 'slowniki', nazwa: 'Słowniki', opis: 'Listy, z których wybiera się wartości w kartotekach.' },
        { klucz: 'ustawienia', nazwa: 'Ustawienia', opis: 'Zachowanie samego systemu.' },
      ],
      kafelki: [
        { zakladka: 'logowania', nazwa: 'Rejestr logowań', adres: '/logowania', tylkoAdmin: true },

        { zakladka: 'slowniki', nazwa: 'Stanowisko', adres: '/funkcja', tylkoAdmin: false },
        { zakladka: 'slowniki', nazwa: 'Godziny Pracy', adres: '/shiftStatusTyp', tylkoAdmin: false },
        { zakladka: 'slowniki', nazwa: 'Uprawnienia Typ', adres: '/uprawnieniaTyp', tylkoAdmin: false },
        { zakladka: 'slowniki', nazwa: 'Badania Lekarskie', adres: '/badaniaTyp', tylkoAdmin: true },
        { zakladka: 'slowniki', nazwa: 'Szkolenia BHP', adres: '/bhpTyp', tylkoAdmin: true },
        { zakladka: 'slowniki', nazwa: 'Języki', adres: '/jezykTyp', tylkoAdmin: true },
        { zakladka: 'slowniki', nazwa: 'Kraj', adres: '/krajTyp', tylkoAdmin: true },
        { zakladka: 'slowniki', nazwa: 'Dokumenty', adres: '/dokumentyTyp', tylkoAdmin: true },
        { zakladka: 'slowniki', nazwa: 'Narzędzia Typ', adres: '/narzedziaTyp', tylkoAdmin: false },
        { zakladka: 'slowniki', nazwa: 'Grupy sprzętu', adres: '/grupy-sprzetu', tylkoAdmin: false },

        { zakladka: 'ustawienia', nazwa: 'Wykres prognozy', adres: '/ustawienia', tylkoAdmin: false },
      ],
    }
  },
  computed: {
    admin() {
      return !!this.$page.props.permissions.admin
    },
    dostepneKafelki() {
      return this.kafelki.filter((k) => this.admin || !k.tylkoAdmin)
    },
    // Zakładka bez ani jednego kafelka dla tych uprawnień w ogóle się nie pokazuje.
    widoczneZakladki() {
      return this.zakladki.filter((z) => this.dostepneKafelki.some((k) => k.zakladka === z.klucz))
    },
    widoczneKafelki() {
      return this.dostepneKafelki.filter((k) => k.zakladka === this.wybrana)
    },
    opisWybranej() {
      const z = this.zakladki.find((x) => x.klucz === this.wybrana)
      return z ? z.opis : ''
    },
  },
  mounted() {
    // Kadry nie widzą Logowań, więc otwieramy pierwszą zakładkę, którą widzą.
    if (!this.widoczneZakladki.some((z) => z.klucz === this.wybrana)) {
      this.wybrana = this.widoczneZakladki.length ? this.widoczneZakladki[0].klucz : ''
    }
  },
}
</script>
