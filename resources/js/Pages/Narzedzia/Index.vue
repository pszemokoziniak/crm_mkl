<template>
  <div>
    <Head title="Magazyn Sprzętu" />
    <h1 class="mb-8 text-3xl font-bold text-gray-900">
      Magazyn Sprzętu
      <span class="text-xl font-medium text-gray-400">({{ sztukRazem }} szt. w {{ grupy.length }} rodzajach)</span>
    </h1>

    <div class="flex items-center justify-between mb-6">
      <search-filter v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
        <label class="block text-gray-700">Wyświetlaj:</label>
        <select v-model="form.wyswietlaj" class="form-select mt-1 w-full">
          <option :value="null">Wszystkie</option>
          <option value="dostepne">Dostępne</option>
          <option value="na_budowie">Na budowie</option>
        </select>
      </search-filter>
      <Link class="btn-indigo" href="/narzedzia/create">
        <span>Dodaj nowy sprzęt</span>
      </Link>
    </div>

    <!-- Pasek wydania pojawia się dopiero, gdy coś jest zaznaczone. -->
    <div v-if="zaznaczone.length" class="mb-6 p-4 bg-indigo-50 border border-indigo-200 rounded-md">
      <div class="flex flex-wrap items-end gap-4">
        <div class="font-semibold text-indigo-900">
          Zaznaczono {{ zaznaczone.length }} {{ zaznaczone.length === 1 ? 'sztukę' : 'szt.' }}
        </div>
        <div>
          <label class="block text-xs text-gray-600">Budowa</label>
          <select v-model="wydanie.organization_id" class="form-select mt-1 w-64">
            <option :value="null">— wybierz —</option>
            <option v-for="b in budowy" :key="b.id" :value="b.id">
              {{ b.nazwaBud }}<span v-if="b.warsztat"> (warsztat)</span>
            </option>
          </select>
        </div>
        <div>
          <label class="block text-xs text-gray-600">Od</label>
          <input v-model="wydanie.start" type="date" class="form-input mt-1 w-40" />
        </div>
        <div>
          <label class="block text-xs text-gray-600">Do (można zostawić puste)</label>
          <input v-model="wydanie.end" type="date" class="form-input mt-1 w-40" />
        </div>
        <button class="btn-indigo" type="button" :disabled="!wydanie.organization_id || !wydanie.start" @click="wydaj">
          Wydaj na budowę
        </button>
        <button class="text-gray-500 hover:text-gray-800 underline" type="button" @click="zaznaczone = []">
          Odznacz
        </button>
      </div>
      <div v-if="bledy" class="mt-2 text-sm text-red-600">{{ bledy }}</div>
    </div>

    <div class="bg-white rounded-md shadow overflow-hidden">
      <table class="w-full">
        <thead>
          <tr class="text-left font-bold bg-gray-50 border-b border-gray-100">
            <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500">Rodzaj sprzętu</th>
            <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500 text-center">Sztuk</th>
            <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500 text-center">Dostępne</th>
            <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500 text-center">Na budowach</th>
            <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500">Badania</th>
            <th class="py-4 px-6" />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <template v-for="grupa in grupy" :key="grupa.klucz">
            <tr class="hover:bg-gray-50 transition-colors cursor-pointer" @click="przelacz(grupa.klucz)">
              <td class="px-6 py-4">
                <div class="flex items-center font-medium text-gray-900">
                  <img v-if="grupa.photo" :src="grupa.photo" :alt="grupa.nazwa" class="flex-shrink-0 mr-3 w-12 h-12 object-cover rounded border border-gray-200" />
                  <span v-else class="flex flex-shrink-0 items-center justify-center mr-3 w-12 h-12 bg-gray-50 rounded border border-gray-200">
                    <icon name="sprzet2" class="w-5 h-5 fill-gray-300" />
                  </span>
                  {{ grupa.nazwa }}
                </div>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                  {{ grupa.sztuk }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border',
                    grupa.dostepne > 0 ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200',
                  ]"
                >
                  {{ grupa.dostepne }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span v-if="grupa.na_budowie > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200">
                  {{ grupa.na_budowie }}
                </span>
                <span v-else class="text-gray-300 text-xs">-</span>
              </td>
              <td class="px-6 py-4">
                <span v-if="grupa.badania_uwaga > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                  {{ grupa.badania_uwaga }} do sprawdzenia
                </span>
                <span v-else class="text-gray-300 text-xs">-</span>
              </td>
              <td class="px-6 py-4 text-right text-gray-400">
                {{ rozwiniete.includes(grupa.klucz) ? 'zwiń' : 'pokaż sztuki' }}
              </td>
            </tr>

            <tr v-if="rozwiniete.includes(grupa.klucz)" :key="grupa.klucz + '-sztuki'">
              <td colspan="6" class="px-6 py-4 bg-gray-50">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                      <th class="pb-2 pr-4 w-8">
                        <input type="checkbox" :checked="wszystkieZaznaczone(grupa)" @change="zaznaczGrupe(grupa, $event)" />
                      </th>
                      <th class="pb-2 pr-4">Numer seryjny</th>
                      <th class="pb-2 pr-4">Badania techniczne</th>
                      <th class="pb-2 pr-4">Gdzie jest</th>
                      <th class="pb-2" />
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="sztuka in grupa.sztuki" :key="sztuka.id" class="border-t border-gray-200">
                      <td class="py-2 pr-4">
                        <input v-if="!sztuka.budowa" v-model="zaznaczone" type="checkbox" :value="sztuka.id" />
                      </td>
                      <td class="py-2 pr-4 font-medium text-gray-800">{{ sztuka.numer_seryjny || '—' }}</td>
                      <td class="py-2 pr-4">
                        <span :class="klasaBadan(sztuka.badania_status)">
                          {{ sztuka.waznosc_badan || 'brak daty' }}
                        </span>
                      </td>
                      <td class="py-2 pr-4">
                        <span v-if="sztuka.budowa">
                          <Link :href="`/budowy/${sztuka.budowa.id}/edit`" class="text-indigo-600 hover:underline">{{ sztuka.budowa.nazwaBud }}</Link>
                          <span v-if="sztuka.budowa.do" class="text-gray-400"> do {{ sztuka.budowa.do }}</span>
                        </span>
                        <span v-else class="text-green-700">magazyn</span>
                      </td>
                      <td class="py-2 text-right whitespace-nowrap">
                        <Link
                          v-if="sztuka.budowa"
                          :href="`/narzedzia/przypisanie/${sztuka.budowa.przypisanie_id}`"
                          method="delete"
                          as="button"
                          type="button"
                          class="text-gray-500 hover:text-gray-800 underline mr-4"
                        >
                          Zdejmij z budowy
                        </Link>
                        <Link :href="`/narzedzia/${sztuka.id}/edit`" class="text-indigo-600 hover:underline">Karta sprzętu</Link>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
          </template>

          <tr v-if="grupy.length === 0">
            <td class="px-6 py-12 text-center text-gray-500" colspan="6">
              <div class="flex flex-col items-center">
                <icon name="office" class="w-12 h-12 fill-gray-200 mb-2" />
                <p>Nie znaleziono żadnego sprzętu w magazynie</p>
              </div>
            </td>
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
import SearchFilter from '@/Shared/SearchFilter.vue'

export default {
  components: {
    SearchFilter,
    Head,
    Icon,
    Link,
  },
  layout: Layout,
  props: {
    filters: Object,
    grupy: Array,
    budowy: Array,
  },
  data() {
    return {
      form: {
        search: this.filters.search,
        wyswietlaj: this.filters.wyswietlaj,
      },
      rozwiniete: [],
      zaznaczone: [],
      bledy: null,
      wydanie: {
        organization_id: null,
        start: new Date().toISOString().slice(0, 10),
        end: null,
      },
    }
  },
  computed: {
    sztukRazem() {
      return this.grupy.reduce((suma, g) => suma + g.sztuk, 0)
    },
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/narzedzia', pickBy(this.form), { preserveState: true })
      }, 150),
    },
  },
  methods: {
    przelacz(klucz) {
      const i = this.rozwiniete.indexOf(klucz)
      if (i === -1) {
        this.rozwiniete.push(klucz)
      } else {
        this.rozwiniete.splice(i, 1)
      }
    },
    // Zaznaczyć da się tylko to, co jest w magazynie — sprzęt z budowy
    // trzeba najpierw zdjąć.
    dostepneWGrupie(grupa) {
      return grupa.sztuki.filter((s) => !s.budowa).map((s) => s.id)
    },
    wszystkieZaznaczone(grupa) {
      const dostepne = this.dostepneWGrupie(grupa)
      return dostepne.length > 0 && dostepne.every((id) => this.zaznaczone.includes(id))
    },
    zaznaczGrupe(grupa, event) {
      const dostepne = this.dostepneWGrupie(grupa)
      if (event.target.checked) {
        this.zaznaczone = [...new Set([...this.zaznaczone, ...dostepne])]
      } else {
        this.zaznaczone = this.zaznaczone.filter((id) => !dostepne.includes(id))
      }
    },
    klasaBadan(status) {
      if (status === 'po_terminie') return 'text-red-700 font-semibold'
      if (status === 'wkrotce') return 'text-orange-700 font-semibold'
      if (status === 'brak') return 'text-gray-400'
      return 'text-gray-700'
    },
    wydaj() {
      this.bledy = null
      this.$inertia.post(
        '/narzedzia/przypisz',
        {
          narzedzia_ids: this.zaznaczone,
          organization_id: this.wydanie.organization_id,
          start: this.wydanie.start,
          end: this.wydanie.end,
        },
        {
          onSuccess: () => {
            this.zaznaczone = []
            this.wydanie.organization_id = null
            this.wydanie.end = null
          },
          onError: (errors) => {
            this.bledy = Object.values(errors).join(' ')
          },
        },
      )
    },
    reset() {
      this.form = mapValues(this.form, () => null)
    },
  },
}
</script>
