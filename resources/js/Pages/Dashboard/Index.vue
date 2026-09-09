<template>
  <div>
    <Head title="Dashboard" />
    <h1 class="mb-8 text-3xl font-bold">Pulpit</h1>

    <!-- Kierownik nie wchodzi do Pracowników ani Sprzętu, więc te kafelki są
         u niego liczbą bez odnośnika (albo ich nie ma). Raport terminów ma
         już wersję zawężoną do jego budów, więc tam wchodzi. -->
    <div class="mb-8 grid grid-cols-2 gap-4" :class="kierownik ? 'sm:grid-cols-3' : 'sm:grid-cols-4'">
      <component
        :is="kierownik ? 'div' : 'Link'"
        :href="kierownik ? null : '/contacts'"
        class="block bg-white rounded-md shadow p-5 border-l-4 border-indigo-500"
        :class="kierownik ? '' : 'hover:shadow-md transition'"
      >
        <div class="text-3xl font-bold text-gray-900">{{ stats.pracownicy ?? 0 }}</div>
        <div class="mt-1 text-sm text-gray-500">{{ kierownik ? 'Pracownicy na Twoich budowach' : 'Pracownicy' }}</div>
      </component>
      <Link href="/budowy" class="block bg-white rounded-md shadow p-5 border-l-4 border-green-500 hover:shadow-md transition">
        <div class="text-3xl font-bold text-gray-900">{{ stats.budowy ?? 0 }}</div>
        <div class="mt-1 text-sm text-gray-500">{{ kierownik ? 'Twoje budowy' : 'Budowy (aktywne)' }}</div>
      </Link>
      <Link v-if="!kierownik" href="/narzedzia" class="block bg-white rounded-md shadow p-5 border-l-4 border-gray-400 hover:shadow-md transition">
        <div class="text-3xl font-bold text-gray-900">{{ stats.sprzet ?? 0 }}</div>
        <div class="mt-1 text-sm text-gray-500">Sprzęt</div>
      </Link>
      <Link
        href="/reports/koniecUprawinien"
        class="block bg-white rounded-md shadow p-5 border-l-4 hover:shadow-md transition"
        :class="(stats.wygasajace ?? 0) > 0 ? 'border-red-500' : 'border-green-500'"
      >
        <div class="text-3xl font-bold" :class="(stats.wygasajace ?? 0) > 0 ? 'text-red-600' : 'text-gray-900'">{{ stats.wygasajace ?? 0 }}</div>
        <div class="mt-1 text-sm text-gray-500">Wygasające terminy (30 dni)</div>
      </Link>
    </div>

    <!-- Przeniesienia pracowników czekające na aneksy — widok dla biura/kadr. -->
    <div v-if="zmiany_kadrowe.length" class="mb-8 bg-white rounded-md shadow overflow-hidden">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-700">Zmiany pobytów do obsłużenia przez kadry</h2>
        <span class="text-sm font-bold px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800">{{ zmiany_kadrowe_licznik }}</span>
      </div>
      <div>
        <Link
          v-for="z in zmiany_kadrowe"
          :key="z.id"
          href="/zmiany-kadrowe"
          class="flex flex-wrap items-baseline gap-x-2 px-6 py-3 border-t border-gray-50 hover:bg-gray-50 text-sm"
        >
          <span class="font-medium text-gray-800">{{ z.pracownik }}</span>
          <span class="text-gray-500">{{ z.typ_label }}</span>
          <span v-if="z.budowa_z && z.budowa_do && z.budowa_z !== z.budowa_do" class="text-gray-500">
            {{ z.budowa_z }} → {{ z.budowa_do }}
          </span>
          <span v-else-if="z.budowa_do" class="text-gray-500">{{ z.budowa_do }}</span>
          <span v-if="z.nowy_termin" class="text-gray-400">{{ z.nowy_termin }}</span>
          <span class="ml-auto text-xs text-gray-400">{{ z.kiedy }}</span>
        </Link>
        <Link href="/zmiany-kadrowe" class="block px-6 py-3 border-t border-gray-50 text-sm text-indigo-600 hover:bg-gray-50">
          Zobacz wszystkie zmiany kadrowe →
        </Link>
      </div>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <div v-if="!kierownik" class="bg-white rounded-md shadow overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-700">Budowy do archiwizacji</h2>
          <span class="text-sm font-bold px-2 py-0.5 rounded-full" :class="do_archiwizacji.length ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800'">{{ do_archiwizacji.length }}</span>
        </div>
        <div class="max-h-72 overflow-y-auto">
          <Link v-for="b in do_archiwizacji" :key="b.id" :href="`/budowy/${b.id}/edit`" class="block px-6 py-3 border-t border-gray-50 hover:bg-gray-50 text-sm">
            <span class="text-gray-400 mr-2">{{ b.numerBud }}</span>{{ b.nazwaBud }}
          </Link>
          <p v-if="!do_archiwizacji.length" class="px-6 py-4 text-sm text-gray-400">Brak — żadna budowa nie jest gotowa do archiwizacji.</p>
        </div>
      </div>

      <!-- Urlop albo zwolnienie zmienia kierownikowi plan dnia, więc widzi to
           od razu po wejściu, bez klikania po kartotekach. -->
      <div v-if="kierownik" class="bg-white rounded-md shadow overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-700">Nieobecni dziś</h2>
          <span class="text-sm font-bold px-2 py-0.5 rounded-full" :class="nieobecni_dzis.length ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800'">{{ nieobecni_dzis.length }}</span>
        </div>
        <div class="max-h-72 overflow-y-auto">
          <Link
            v-for="n in nieobecni_dzis"
            :key="n.id"
            :href="`/contacts/${n.contact_id}/holiday`"
            class="flex items-baseline justify-between gap-3 px-6 py-2 border-t border-gray-50 hover:bg-gray-50 text-sm"
          >
            <span>{{ n.pracownik }}</span>
            <span class="text-xs text-gray-500 whitespace-nowrap">
              {{ n.powod }}<template v-if="n.do"> — do {{ n.do }}</template>
            </span>
          </Link>
          <p v-if="!nieobecni_dzis.length" class="px-6 py-4 text-sm text-gray-400">Nikt — dziś wszyscy Twoi ludzie są dostępni.</p>
        </div>
      </div>

      <div class="bg-white rounded-md shadow overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-700">{{ kierownik ? 'Twoi pracownicy bez ważnego A1' : 'Pracownicy bez ważnego A1' }}</h2>
          <span class="text-sm font-bold px-2 py-0.5 rounded-full" :class="bez_a1.length ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'">{{ bez_a1.length }}</span>
        </div>
        <p v-if="bez_a1.length" class="px-6 pt-3 text-xs text-gray-500">
          {{ licznikA1.wygasle }} z wygasłym A1, {{ licznikA1.brak }} bez żadnego wpisu.
        </p>
        <!-- Lista bywa długa i spychała resztę pulpitu poza ekran, więc
             domyślnie pokazujemy kilka nazwisk. -->
        <div>
          <Link
            v-for="p in widoczneBezA1"
            :key="p.id"
            :href="`/contacts/${p.id}/a1`"
            class="flex items-baseline justify-between gap-3 px-6 py-2 border-t border-gray-50 hover:bg-gray-50 text-sm"
          >
            <span>{{ p.last_name }} {{ p.first_name }}</span>
            <span class="text-xs whitespace-nowrap" :class="p.ostatni_a1 ? 'text-orange-700' : 'text-gray-400'">
              {{ p.ostatni_a1 ? `wygasło ${p.ostatni_a1}` : 'brak wpisu' }}
            </span>
          </Link>
          <button
            v-if="bez_a1.length > 5"
            type="button"
            class="w-full px-6 py-2 text-sm text-left text-indigo-600 border-t border-gray-50 hover:bg-gray-50"
            @click="wszystkieBezA1 = !wszystkieBezA1"
          >
            {{ wszystkieBezA1 ? 'zwiń' : `pokaż wszystkich (${bez_a1.length})` }}
          </button>
          <p v-if="!bez_a1.length" class="px-6 py-4 text-sm text-gray-400">Brak — wszyscy przypisani mają ważne A1.</p>
        </div>
      </div>
    </div>

    <div v-if="expiring_items.length > 0" class="mb-8">
      <div class="flex items-center mb-4">
        <icon name="eligibility" class="mr-2 w-5 h-5 fill-red-600" />
        <h2 class="text-xl font-bold text-red-600">
          Terminy do pilnowania
          <span class="text-sm font-normal text-gray-500">— po terminie i kończące się w 60 dni</span>
        </h2>
      </div>
      <!-- Bez whitespace-nowrap szesc kolumn miesci sie bez wlasnego paska
           przewijania. overflow-x-auto zostaje jako zabezpieczenie: lepiej
           przewinac niz uciac tresc, gdyby nazwa budowy byla wyjatkowo dluga. -->
      <div class="hidden sm:block bg-white rounded-md shadow overflow-x-auto">
        <table class="w-full table-fixed">
          <thead>
            <tr class="text-left font-bold bg-red-50">
              <th class="pb-4 pt-6 px-4 w-1/6">Pracownik</th>
              <th class="pb-4 pt-6 px-4 w-1/6">Kategoria</th>
              <th class="pb-4 pt-6 px-4 w-1/5">Rodzaj / Typ</th>
              <th class="pb-4 pt-6 px-4 w-32">Data końcowa</th>
              <th class="pb-4 pt-6 px-4 w-1/6">Stan</th>
              <th class="pb-4 pt-6 px-4 w-1/6">Obecna budowa</th>
            </tr>
          </thead>
          <tbody>
            <!-- Caly wiersz prowadzi na karte pracownika; wyjatkiem jest
                 ostatnia kolumna, ktora ma wlasny cel — budowe. -->
            <tr v-for="(item, index) in expiring_items" :key="index" class="hover:bg-gray-100 focus-within:bg-gray-100">
              <td class="border-t">
                <Link class="block px-4 py-4 text-indigo-600 hover:underline" :href="`/contacts/${item.contact.id}/edit`">
                  {{ item.contact.first_name }} {{ item.contact.last_name }}
                </Link>
              </td>
              <td class="border-t">
                <Link class="block px-4 py-4" :href="`/contacts/${item.contact.id}/edit`">
                  <span class="inline-block px-2 py-1 rounded text-xs font-bold bg-gray-200 text-gray-800">{{ item.category }}</span>
                </Link>
              </td>
              <td class="border-t">
                <Link class="block px-4 py-4" :href="`/contacts/${item.contact.id}/edit`">
                  {{ item.type }}
                </Link>
              </td>
              <td class="border-t">
                <Link class="block px-4 py-4 font-bold tabular-nums whitespace-nowrap" :class="klasaTerminu(item)" :href="`/contacts/${item.contact.id}/edit`">
                  {{ item.end }}
                </Link>
              </td>
              <td class="border-t">
                <Link class="block px-4 py-4 text-sm" :class="klasaTerminu(item)" :href="`/contacts/${item.contact.id}/edit`">
                  {{ opisTerminu(item) }}
                </Link>
              </td>
              <td class="border-t">
                <Link v-if="item.organization" class="block px-4 py-4 text-indigo-600 hover:underline" :href="kierownik ? `/building/${item.organization.id}/time-sheet` : `/budowy/${item.organization.id}/edit`">
                  {{ item.organization.nazwaBud }}
                </Link>
                <span v-else class="block px-4 py-4 text-gray-400 italic">Brak przypisanej budowy</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Telefon: karty zamiast szesciu kolumn sciscietych do niczego. -->
      <div class="sm:hidden space-y-3">
        <div v-for="(item, index) in expiring_items" :key="`karta-${index}`" class="bg-white rounded-md shadow p-4">
          <Link class="font-medium text-indigo-600 hover:underline" :href="`/contacts/${item.contact.id}/edit`">
            {{ item.contact.first_name }} {{ item.contact.last_name }}
          </Link>
          <div class="mt-2">
            <span class="inline-block px-2 py-1 rounded text-xs font-bold bg-gray-200 text-gray-800">{{ item.category }}</span>
            <span class="ml-2 text-sm text-gray-600">{{ item.type }}</span>
          </div>
          <div class="mt-2 text-sm font-bold tabular-nums" :class="klasaTerminu(item)">
            {{ item.end }} <span class="font-normal">— {{ opisTerminu(item) }}</span>
          </div>
          <div class="mt-1 text-sm">
            <Link v-if="item.organization" class="text-indigo-600 hover:underline" :href="kierownik ? `/building/${item.organization.id}/time-sheet` : `/budowy/${item.organization.id}/edit`">
              {{ item.organization.nazwaBud }}
            </Link>
            <span v-else class="text-gray-400 italic">Brak przypisanej budowy</span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex items-center justify-between mb-6">
      <search-filter v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
        <label class="block text-gray-700">Wybierz:</label>
        <select v-model="form.trashed" class="form-select mt-1 w-full">
          <option :value="null">Aktywne budowy</option>
          <option value="my">Moje budowy</option>
          <option value="with">Wszystkie budowy</option>
          <option value="only">Usunięte budowy</option>
        </select>
      </search-filter>
    </div>
    <div v-if="user_owner[1]===3" class="my-3 font-bold mb-3">Twoje budowy</div>
    <div v-if="user_owner[1]===3" class="bg-white rounded-md shadow overflow-x-auto my-3">
      <table class="w-full whitespace-nowrap">
        <thead>
          <tr class="text-left font-bold">
            <th class="pb-4 pt-6 px-6">Nazwa</th>
            <th class="pb-4 pt-6 px-6">Ilość Pracowników</th>
            <th class="pb-4 pt-6 px-6">Inżynier budowy</th>
            <th class="pb-4 pt-6 px-6" colspan="2">Kraj</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in organizations_user" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <td class="border-t">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/building/${item.id}/time-sheet`">
                {{ item.nazwaBud }}
                <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/building/${item.id}/time-sheet`" tabindex="-1">
                {{ item.workers_count }}
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/building/${item.id}/time-sheet`" tabindex="-1">
                <div v-if="item.inzynier_name">
                  {{ item.inzynier_name }}
                </div>
                <div v-else-if="item.inzynier">
                  {{ item.inzynier.first_name }} {{ item.inzynier.last_name }}
                </div>
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/building/${item.id}/time-sheet`" tabindex="-1">
                <div v-if="item.country">
                  {{ item.country.name }}
                </div>
              </Link>
            </td>
            <!-- Wpisywanie godzin to codzienna czynność kierownika, a dotąd
                 nic na pulpicie nie mówiło, że wiersz prowadzi właśnie tam. -->
            <td class="w-px border-t">
              <Link class="flex items-center px-4 py-4" :href="`/building/${item.id}/time-sheet`">
                <span class="whitespace-nowrap rounded bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-700">Wpisz godziny</span>
              </Link>
            </td>
          </tr>
          <tr v-if="organizations_user.length === 0">
            <td class="px-6 py-4 border-t" colspan="4">Brak danych.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="user_owner[1]===1 || user_owner[1]===2" class="py-3 font-bold">Wszystkie aktywne budowy</div>
    <div v-if="user_owner[1]===1 || user_owner[1]===2" class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead>
          <tr class="text-left font-bold">
            <th class="pb-4 pt-6 px-6">Nazwa</th>
            <th class="pb-4 pt-6 px-6">Ilość Pracowników</th>
            <th class="pb-4 pt-6 px-6">Inżynier budowy</th>
            <th class="pb-4 pt-6 px-6" colspan="2">Kraj</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in organizations_biuro" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <td class="border-t">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/budowy/${item.id}/edit`">
                {{ item.nazwaBud }}
                <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
                {{ item.workers_count }}
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
                <div v-if="item.inzynier_name">
                  {{ item.inzynier_name }}
                </div>
                <div v-else-if="item.inzynier">
                  {{ item.inzynier.first_name }} {{ item.inzynier.last_name }}
                </div>
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
                <div v-if="item.country">
                  {{ item.country.name }}
                </div>
              </Link>
            </td>
            <td class="w-px border-t">
              <Link class="flex items-center px-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
                <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
              </Link>
            </td>
          </tr>
          <tr v-if="organizations_biuro.length === 0">
            <td class="px-6 py-4 border-t" colspan="4">Brak danych.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import SearchFilter from '@/Shared/SearchFilter'

export default {
  components: {
    Head,
    Icon,
    Link,
    SearchFilter,
  },
  layout: Layout,
  props: {
    filters: Object,
    stats: { type: Object, default: () => ({}) },
    do_archiwizacji: { type: Array, default: () => [] },
    zmiany_kadrowe: { type: Array, default: () => [] },
    zmiany_kadrowe_licznik: { type: Number, default: 0 },
    bez_a1: { type: Array, default: () => [] },
    nieobecni_dzis: { type: Array, default: () => [] },
    expiring_items: Array,
    organizations_user: Object,
    organizations_biuro: Object,
    buildings: Object,
    inzynier: Object,
    user_owner: Array,
  },
  data() {
    return {
      wszystkieBezA1: false,
      form: {
        search: this.filters.search,
        trashed: this.filters.trashed,
      },
    }
  },
  computed: {
    // user_owner niesie rolę pod indeksem 1 — tak jak w reszcie tego widoku.
    kierownik() {
      return this.user_owner[1] === 3
    },
    widoczneBezA1() {
      return this.wszystkieBezA1 ? this.bez_a1 : this.bez_a1.slice(0, 5)
    },
    // Brak wpisu to zadanie dla kadr, wygasłe A1 — do odnowienia. Bez tego
    // podziału cała lista wyglądała jak jeden fałszywy alarm.
    licznikA1() {
      const wygasle = this.bez_a1.filter((p) => p.ostatni_a1).length

      return { wygasle, brak: this.bez_a1.length - wygasle }
    },
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/', pickBy(this.form), { preserveState: true })
      }, 150),
    },
  },
  methods: {
    opisTerminu(item) {
      if (item.dni < 0) return `po terminie od ${Math.abs(item.dni)} dni`
      if (item.dni === 0) return 'kończy się dziś'
      return `zostało ${item.dni} dni`
    },
    klasaTerminu(item) {
      if (item.dni < 0) return 'text-red-700'
      return item.dni <= 30 ? 'text-orange-700' : 'text-gray-600'
    },
    reset() {
      this.form = mapValues(this.form, () => null)
    },
  },
}
</script>
