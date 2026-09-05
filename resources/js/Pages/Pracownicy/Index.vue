<template>
  <div>
    <Head title="Budowa" />
    <BudMenu :budId="organization_id" />
    <budowa-naglowek :bud-id="organization.id" :nazwa="organization.nazwaBud" tytul="Pracownicy na budowie" />
    <p class="mb-6 text-sm text-gray-500">
      Na budowie obecnie: <span class="font-bold text-gray-700">{{ naBudowie }}</span>
      z {{ contactworkdates.data.length }} wpisów na tej stronie.
      <span v-if="zakonczone > 0">Pozostałe {{ zakonczone }} to zakończone pobyty — zostają jako historia.</span>
      <span v-if="sortowanie.sort === 'nazwisko'" class="block mt-1">
        Sortowanie po nazwisku: najpierw obecni na budowie, pod nimi ci, którzy zjechali.
      </span>
      <span v-else-if="sortowanie.sort === 'data'" class="block mt-1">
        Sortowanie po terminie zjazdu z budowy; pobyty bez daty końca na końcu listy.
      </span>
    </p>
    <div class="flex flex-col items-stretch gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
      <search-filter-no-filtr v-model="form.search" class="w-full sm:max-w-md" @reset="reset">
        <label class="block text-gray-700">Wybierz:</label>
        <select v-model="form.trashed" class="form-select mt-1 w-full">
          <option value="with">Wszystkie</option>
          <option value="only">Usunięte</option>
        </select>
      </search-filter-no-filtr>
      <Link v-if="user_owner !== 3" class="btn-indigo text-center whitespace-nowrap" :href="`/pracownicy/${organization_id}/create`">
        <span>Dodaj</span>
        <span class="hidden md:inline">&nbsp;Pracownika</span>
      </Link>
    </div>
    <!-- Zbiorcze skrócenie pobytu — przy przenoszeniu ekipy na inną budowę. -->
    <div v-if="mozeEdytowac && zaznaczone.length" class="flex flex-col items-stretch gap-3 mb-4 p-4 bg-indigo-50 border border-indigo-200 rounded-md sm:flex-row sm:flex-wrap sm:items-end sm:gap-4">
      <div>
        <div class="text-sm font-semibold text-gray-800">Zaznaczono: {{ zaznaczone.length }}</div>
        <div class="text-xs text-gray-500">Ustaw wszystkim ten sam koniec pobytu na tej budowie.</div>
      </div>
      <div>
        <label class="form-label">Koniec pobytu:</label>
        <input v-model="nowaDataKonca" type="date" class="form-input mt-1" />
      </div>
      <button type="button" class="btn-indigo whitespace-nowrap" :disabled="!nowaDataKonca" @click="ustawDateKonca">
        Ustaw datę końca
      </button>
      <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="zaznaczone = []">
        Wyczyść zaznaczenie
      </button>
    </div>

    <!-- Szeroki ekran: tabela -->
    <div class="hidden bg-white rounded-md shadow overflow-x-auto md:block">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left font-bold border-b">
            <th v-if="mozeEdytowac" class="py-4 px-3 w-px">
              <input type="checkbox" class="form-checkbox" :checked="wszystkieZaznaczone" @change="przelaczWszystkie" />
            </th>
            <th class="py-4 px-4">
              <button type="button" class="font-bold hover:text-indigo-600" @click="sortuj('nazwisko')">
                Nazwisko Imię<span class="ml-1 text-xs text-gray-400">{{ strzalka('nazwisko') }}</span>
              </button>
            </th>
            <th class="py-4 px-4">
              <button type="button" class="font-bold hover:text-indigo-600" @click="sortuj('data')">
                Czas pracy<span class="ml-1 text-xs text-gray-400">{{ strzalka('data') }}</span>
              </button>
            </th>
            <th class="py-4 px-4">
              <button type="button" class="font-bold hover:text-indigo-600" @click="sortuj('stanowisko')">
                Stanowisko<span class="ml-1 text-xs text-gray-400">{{ strzalka('stanowisko') }}</span>
              </button>
            </th>
            <th class="py-4 px-4">Na budowie</th>
            <th v-if="mozeEdytowac" class="py-4 px-4" />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="item in contactworkdates.data" :key="item.id" class="hover:bg-gray-50">
            <td v-if="mozeEdytowac" class="px-3 py-3">
              <input v-model="zaznaczone" type="checkbox" class="form-checkbox" :value="item.id" />
            </td>
            <td v-if="item.contact" class="px-4 py-3">
              <Link class="font-medium text-gray-900 hover:text-indigo-600" :href="`/contacts/${item.contact.id}/edit`">
                {{ item.contact.last_name }} {{ item.contact.first_name }}
              </Link>
              <icon v-if="item.deleted_at" name="trash" class="inline ml-1 w-3 h-3 fill-gray-400" />
            </td>
            <td v-if="item.contact" class="px-4 py-3 text-gray-600 tabular-nums">
              <span class="block whitespace-nowrap">od: {{ item.start }}</span>
              <span class="block whitespace-nowrap">do: {{ item.end }}</span>
            </td>
            <td v-if="item.contact" class="px-4 py-3 text-gray-600">
              {{ item.contact.funkcja ? item.contact.funkcja.name : '' }}
            </td>
            <td v-if="item.contact" class="px-4 py-3">
              <span :class="klasaStatusu(item)" class="inline-block px-2.5 py-0.5 text-xs font-medium border rounded-full">
                {{ etykietaStatusu(item) }}
              </span>
            </td>
            <td v-if="item.contact && mozeEdytowac" class="px-4 py-3 text-right whitespace-nowrap">
              <Link class="text-indigo-600 hover:underline" tabindex="-1" :href="`/pracownicy/${organization_id}/edit/${item.id}`">
                Popraw daty
              </Link>
              <button type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(item.id)">
                Usuń
              </button>
            </td>
          </tr>
          <tr v-if="contactworkdates.data.length === 0">
            <td class="px-4 py-6 text-gray-500" colspan="6">Nie znaleziono pracownika</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Wąski ekran: karty zamiast tabeli, żeby nic nie uciekało w bok -->
    <div class="bg-white rounded-md shadow divide-y divide-gray-100 md:hidden">
      <div v-for="item in contactworkdates.data" :key="item.id" class="p-4">
        <div v-if="item.contact" class="flex items-start gap-3">
          <input v-if="mozeEdytowac" v-model="zaznaczone" type="checkbox" class="form-checkbox mt-1" :value="item.id" />
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <Link class="font-medium text-gray-900" :href="`/contacts/${item.contact.id}/edit`">
                {{ item.contact.last_name }} {{ item.contact.first_name }}
              </Link>
              <span :class="klasaStatusu(item)" class="inline-block px-2 py-0.5 text-xs font-medium border rounded-full">
                {{ etykietaStatusu(item) }}
              </span>
            </div>
            <div v-if="item.contact.funkcja" class="mt-0.5 text-xs text-gray-500">
              {{ item.contact.funkcja.name }}
            </div>
            <div class="mt-1 text-sm text-gray-600 tabular-nums">
              od: {{ item.start }} · do: {{ item.end }}
            </div>
            <div v-if="mozeEdytowac" class="mt-2 text-sm">
              <Link class="text-indigo-600" :href="`/pracownicy/${organization_id}/edit/${item.id}`">Popraw daty</Link>
              <button type="button" class="ml-4 text-red-600" @click="destroy(item.id)">Usuń</button>
            </div>
          </div>
        </div>
      </div>
      <p v-if="contactworkdates.data.length === 0" class="p-4 text-sm text-gray-500">Nie znaleziono pracownika</p>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import BudowaNaglowek from '@/Shared/BudowaNaglowek'
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import BudMenu from '@/Shared/BudMenu.vue'
import throttle from 'lodash/throttle'
import pickBy from 'lodash/pickBy'
import mapValues from 'lodash/mapValues'
import SearchFilterNoFiltr from '@/Shared/SearchFilterNoFiltr.vue'


export default {
  components: {
    BudowaNaglowek,
    SearchFilterNoFiltr,
    Head,
    Icon,
    Link,
    BudMenu,
  },
  layout: Layout,
  props: {
    contactworkdates: Object,
    organization_id: Number,
    organization: { type: Object, default: () => ({}) },
    sortowanie: { type: Object, default: () => ({ sort: 'nazwisko', direction: 'asc' }) },
    filters: Object,
    user_owner: Number,
    // contact_work_dates: Object,
  },
  data() {
    return {
      zaznaczone: [],
      nowaDataKonca: '',
      form: {
        search: this.filters.search,
        trashed: this.filters.trashed,
      },
    }
  },
  computed: {
    mozeEdytowac() {
      return this.user_owner !== 3
    },
    wszystkieZaznaczone() {
      return this.contactworkdates.data.length > 0
        && this.zaznaczone.length === this.contactworkdates.data.length
    },
    naBudowie() {
      return this.contactworkdates.data.filter((item) => item.on_site).length
    },
    zakonczone() {
      return this.contactworkdates.data.length - this.naBudowie
    },
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        // Szukanie nie może gubić wybranego sortowania.
        this.$inertia.get(
          '/pracownicy/' + this.organization_id,
          { ...pickBy(this.form), sort: this.sortowanie.sort, direction: this.sortowanie.direction },
          { preserveState: true }
        )
      }, 150),
    },
  },
  methods: {
    // Kliknięcie w tę samą kolumnę odwraca kierunek, w inną — zaczyna od A do Z.
    sortuj(kolumna) {
      const kierunek = this.sortowanie.sort === kolumna && this.sortowanie.direction === 'asc' ? 'desc' : 'asc'

      this.$inertia.get(
        `/pracownicy/${this.organization_id}`,
        { ...pickBy(this.form), sort: kolumna, direction: kierunek },
        { preserveState: true, preserveScroll: true }
      )
    },
    strzalka(kolumna) {
      if (this.sortowanie.sort !== kolumna) return ''
      return this.sortowanie.direction === 'asc' ? '▲' : '▼'
    },
    /** Nieobecność ma pierwszeństwo: pracownik należy do budowy, ale dziś go nie ma. */
    etykietaStatusu(item) {
      if (item.on_site && item.nieobecnosc) {
        return item.nieobecnosc
      }

      return item.on_site ? 'Pracuje' : `Zakończony ${item.end}`
    },
    klasaStatusu(item) {
      if (item.on_site && item.nieobecnosc) {
        return 'text-yellow-800 bg-yellow-100 border-yellow-200'
      }

      return item.on_site
        ? 'text-green-800 bg-green-100 border-green-200'
        : 'text-gray-600 bg-gray-100 border-gray-200'
    },
    przelaczWszystkie(event) {
      this.zaznaczone = event.target.checked
        ? this.contactworkdates.data.map((item) => item.id)
        : []
    },
    ustawDateKonca() {
      const ile = this.zaznaczone.length

      if (!confirm(`Ustawić koniec pobytu na ${this.nowaDataKonca} dla ${ile} ${ile === 1 ? 'osoby' : 'osób'}?`)) {
        return
      }

      this.$inertia.put(`/pracownicy/${this.organization_id}/data-konca`, {
        ids: this.zaznaczone,
        end: this.nowaDataKonca,
      }, {
        preserveScroll: true,
        onSuccess: () => {
          this.zaznaczone = []
          this.nowaDataKonca = ''
        },
      })
    },
    reset() {
      this.form = mapValues(this.form, () => null)
    },
    destroy(worker) {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/pracownicy/${worker}`)
      }
    },
  },
}
</script>
