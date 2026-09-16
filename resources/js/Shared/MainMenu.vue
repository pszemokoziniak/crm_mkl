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
      // Kolejność wg ustaleń z Tomaszem. "moze" to uprawnienie, o które
      // pyta też trasa pod adresem — nikt nie widzi linku, w który nie
      // wejdzie. Brak pola = każdy zalogowany.
      pozycje: [
        { nazwa: 'Home', adres: '/', ikona: 'home', dopasowanie: '' },
        { nazwa: 'Budowy', adres: '/budowy', ikona: 'office', dopasowanie: 'budowy', moze: 'budowy.podglad' },
        { nazwa: 'Pracownicy', adres: '/contacts', ikona: 'users', dopasowanie: 'contacts', moze: 'kartoteki.lista' },
        { nazwa: 'Kierownicy / Inżynierowie', adres: '/kierownicy', ikona: 'kierownictwo', dopasowanie: 'kierownicy', moze: 'kartoteki.lista' },
        { nazwa: 'Zmiany kadrowe', adres: '/zmiany-kadrowe', ikona: 'zmiany', dopasowanie: 'zmiany-kadrowe', moze: 'zmiany_kadrowe.obsluga' },
        { nazwa: 'Sprzęt', adres: '/narzedzia', ikona: 'sprzet2', dopasowanie: 'narzedzia', moze: 'sprzet.obsluga' },
        { nazwa: 'Termin uprawnień', adres: '/reports/koniecUprawinien', ikona: 'eligibility', dopasowanie: 'reports', moze: 'raport_terminow.podglad' },
        { nazwa: 'Prognoza pracowników', adres: '/prognoza', ikona: 'forecast-workers', dopasowanie: 'prognoza', moze: 'prognoza.obsluga' },
        { nazwa: 'Ustawienia', adres: '/tools', ikona: 'tools', dopasowanie: 'tools', moze: 'slowniki.biura' },
        { nazwa: 'Zadania', adres: '/zadania', ikona: 'zadania', dopasowanie: 'zadania' },
        { nazwa: 'Baza wiedzy', adres: '/baza-wiedzy', ikona: 'baza-wiedzy', dopasowanie: 'baza-wiedzy' },
        { nazwa: 'Statystyki', adres: '/statystyki', ikona: 'monthlyReport', dopasowanie: 'statystyki', moze: 'statystyki.podglad' },
        { nazwa: 'Raport miesięczny', adres: '/building/time-sheet/month-report', ikona: 'monthlyReport', dopasowanie: 'month-report', moze: 'kcp.raporty' },
      ],
    }
  },
  computed: {
    widoczne() {
      const uprawnienia = this.$page.props.permissions || {}

      const moze = uprawnienia.moze || {}

      return this.pozycje.filter((p) => !p.moze || moze[p.moze])
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
