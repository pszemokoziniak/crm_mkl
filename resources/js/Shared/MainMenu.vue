<template>
  <!-- Wygląd dorównany do CRM: te same odstępy, wielkość ikon i podświetlenie
       aktywnej pozycji. Kolejność ustala lista poniżej — wcześniej każda
       pozycja była osobnym blokiem HTML i przestawienie jednej wymagało
       przenoszenia kilkunastu linii. -->
  <div class="space-y-1">
    <Link
      v-for="pozycja in widoczne"
      :key="pozycja.adres"
      class="group flex items-center px-4 py-3 rounded-lg transition-all duration-200"
      :href="pozycja.adres"
      :class="czyAktywna(pozycja)
        ? 'bg-indigo-900 text-white shadow-inner'
        : 'text-indigo-100 hover:bg-indigo-700 hover:text-white'"
    >
      <icon
        :name="pozycja.ikona"
        class="flex-shrink-0 mr-3 w-5 h-5 transition-colors duration-200"
        :class="czyAktywna(pozycja) ? 'fill-white' : 'fill-indigo-400 group-hover:fill-white'"
      />
      <div class="font-medium">{{ pozycja.nazwa }}</div>
    </Link>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'

export default {
  components: { Icon, Link },
  data() {
    return {
      // Kolejność wg ustaleń z Tomaszem. "widzi" mówi, kto ma pozycję
      // zobaczyć; brak pola = każdy zalogowany.
      pozycje: [
        { nazwa: 'Home', adres: '/', ikona: 'home', dopasowanie: '' },
        { nazwa: 'Budowy', adres: '/budowy', ikona: 'office', dopasowanie: 'budowy', widzi: ['admin', 'biuro', 'kierownik'] },
        { nazwa: 'Pracownicy', adres: '/contacts', ikona: 'users', dopasowanie: 'contacts', widzi: ['admin', 'biuro'] },
        { nazwa: 'Kierownicy / Inżynierowie', adres: '/kierownicy', ikona: 'kierownictwo', dopasowanie: 'kierownicy', widzi: ['admin', 'biuro'] },
        { nazwa: 'Zmiany kadrowe', adres: '/zmiany-kadrowe', ikona: 'zmiany', dopasowanie: 'zmiany-kadrowe', widzi: ['admin', 'biuro'] },
        { nazwa: 'Sprzęt', adres: '/narzedzia', ikona: 'sprzet2', dopasowanie: 'narzedzia', widzi: ['admin', 'biuro'] },
        // Kierownik ma tu wersję zawężoną do swoich budów, więc też widzi pozycję.
        { nazwa: 'Termin uprawnień', adres: '/reports/koniecUprawinien', ikona: 'eligibility', dopasowanie: 'reports', widzi: ['admin', 'biuro', 'kierownik'] },
        { nazwa: 'Prognoza pracowników', adres: '/prognoza', ikona: 'forecast-workers', dopasowanie: 'prognoza', widzi: ['admin', 'biuro'] },
        { nazwa: 'Ustawienia', adres: '/tools', ikona: 'tools', dopasowanie: 'tools', widzi: ['admin', 'biuro'] },
        { nazwa: 'Zadania', adres: '/zadania', ikona: 'zadania', dopasowanie: 'zadania' },
        { nazwa: 'Baza wiedzy', adres: '/baza-wiedzy', ikona: 'baza-wiedzy', dopasowanie: 'baza-wiedzy' },
        { nazwa: 'Raport miesięczny', adres: '/building/time-sheet/month-report', ikona: 'monthlyReport', dopasowanie: 'month-report', widzi: ['admin', 'biuro'] },
      ],
    }
  },
  computed: {
    widoczne() {
      const uprawnienia = this.$page.props.permissions || {}

      return this.pozycje.filter((p) => !p.widzi || p.widzi.some((rola) => uprawnienia[rola]))
    },
  },
  methods: {
    czyAktywna(pozycja) {
      const adres = this.$page.url.substr(1)

      return pozycja.dopasowanie === '' ? adres === '' : adres.startsWith(pozycja.dopasowanie)
    },
  },
}
</script>
