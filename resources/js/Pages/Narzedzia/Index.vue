<template>
  <div>
    <Head title="Magazyn Sprzętu" />
    <h1 class="mb-8 text-3xl font-bold text-gray-900">
      Magazyn Sprzętu
      <span class="text-xl font-medium text-gray-400">({{ sztukRazem }} szt. w {{ grupy.length }} rodzajach)</span>
    </h1>

    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
      <search-filter v-model="form.search" class="w-full max-w-md sm:mr-4" @reset="reset">
        <label class="block text-gray-700">Wyświetlaj:</label>
        <select v-model="form.wyswietlaj" class="form-select mt-1 w-full">
          <option :value="null">Wszystkie</option>
          <option value="dostepne">Dostępne</option>
          <option value="na_budowie">Na budowie</option>
        </select>
      </search-filter>
      <div class="flex flex-wrap items-center gap-3">
        <!-- Żeby zobaczyć, gdzie stoi sprzęt, trzeba było rozwijać każdą
             pozycję z osobna. -->
        <button type="button" class="text-sm text-indigo-600 hover:underline whitespace-nowrap" @click="rozwinWszystko">
          Rozwiń wszystko
        </button>
        <button
          v-if="rozwiniete.length"
          type="button"
          class="text-sm text-gray-600 hover:underline whitespace-nowrap"
          @click="zwinWszystko"
        >
          Zwiń wszystko
        </button>
        <Link class="btn-indigo w-full text-center sm:w-auto" href="/narzedzia/create">
          <span>Dodaj nowy sprzęt</span>
        </Link>
      </div>
    </div>

    <!-- Pasek wydania pojawia się dopiero, gdy coś jest zaznaczone. -->
    <div v-if="zaznaczone.length" class="mb-6 p-4 bg-indigo-50 border border-indigo-200 rounded-md">
      <div class="flex flex-wrap items-end gap-4">
        <div class="w-full font-semibold text-indigo-900 sm:w-auto">
          Zaznaczono {{ zaznaczone.length }} {{ zaznaczone.length === 1 ? 'sztukę' : 'szt.' }}
        </div>
        <div class="w-full sm:w-auto">
          <label class="block text-xs text-gray-600">Budowa</label>
          <select v-model="wydanie.organization_id" class="form-select mt-1 w-full sm:w-64">
            <option :value="null">— wybierz —</option>
            <option v-for="b in budowy" :key="b.id" :value="b.id">
              {{ b.nazwaBud }}<span v-if="b.warsztat"> (warsztat)</span>
            </option>
          </select>
        </div>
        <div class="flex-1 sm:flex-none">
          <label class="block text-xs text-gray-600">Od</label>
          <input v-model="wydanie.start" type="date" class="form-input mt-1 w-full sm:w-40" />
        </div>
        <div class="flex-1 sm:flex-none">
          <label class="block text-xs text-gray-600">Do (można zostawić puste)</label>
          <input v-model="wydanie.end" type="date" class="form-input mt-1 w-full sm:w-40" />
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

    <div class="hidden md:block bg-white rounded-md shadow-sm overflow-hidden">
      <table class="w-full">
        <thead>
          <tr class="naglowek-tabeli">
            <th class="w-px whitespace-nowrap">Lp.</th>
            <th>Sprzęt</th>
            <th class="text-center">Sztuk</th>
            <th class="text-center">Dostępne</th>
            <th class="text-center">Na budowach</th>
            <th>Badania</th>
            <th />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <template v-for="(grupa, gi) in grupy" :key="grupa.klucz">
            <!-- Poziom 1: grupa (Kontener, Manitou) albo pojedynczy model.
                 Numeracja jak w Pracownikach; poziomy niżej dostają 2.1, 2.1.3,
                 żeby było widać, do czego sztuka należy. -->
            <tr class="hover:bg-gray-50 transition-colors cursor-pointer" @click="przelacz(grupa.klucz)">
              <td class="px-4 py-4 text-gray-400 tabular-nums">{{ gi + 1 }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center font-medium text-gray-900">
                  <img v-if="grupa.photo" :src="grupa.photo" :alt="grupa.nazwa" class="shrink-0 mr-3 w-12 h-12 object-cover rounded-sm border border-gray-200" />
                  <span v-else class="flex shrink-0 items-center justify-center mr-3 w-12 h-12 bg-gray-50 rounded-sm border border-gray-200">
                    <icon name="sprzet2" class="w-5 h-5 fill-gray-300" />
                  </span>
                  {{ grupa.nazwa }}
                  <span v-if="grupa.ma_modele" class="ml-2 text-xs text-gray-400">{{ grupa.modele.length }} modele</span>
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
                <span v-if="grupa.badania_po_terminie" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200" title="Sztuki, którym minął termin badań technicznych">
                  {{ grupa.badania_po_terminie }} po terminie
                </span>
                <span v-if="grupa.badania_wkrotce" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200" :class="grupa.badania_po_terminie ? 'ml-1' : ''" title="Badania kończą się w ciągu 30 dni">
                  {{ grupa.badania_wkrotce }} kończy się
                </span>
                <span v-if="!grupa.badania_uwaga" class="text-gray-300 text-xs">-</span>
              </td>
              <td class="px-6 py-4 text-right text-gray-400">
                {{ rozwiniete.includes(grupa.klucz) ? 'zwiń' : (grupa.ma_modele ? 'pokaż modele' : 'pokaż sztuki') }}
              </td>
            </tr>

            <!-- Poziom 2: modele i sztuki w tej samej tabeli, żeby daty badań
                 i miejsce pobytu stały pod swoimi nagłówkami. -->
            <template v-if="rozwiniete.includes(grupa.klucz)">
              <template v-for="(model, mi) in grupa.modele" :key="model.klucz">
                <tr v-if="grupa.ma_modele" class="bg-gray-50 cursor-pointer hover:bg-gray-100" @click="przelacz(grupa.klucz + '/' + model.klucz)">
                  <td class="px-4 py-3 text-xs text-gray-400 tabular-nums">{{ gi + 1 }}.{{ mi + 1 }}</td>
                  <td class="pl-16 pr-6 py-3 font-medium text-gray-700">{{ model.nazwa }}</td>
                  <td class="px-6 py-3 text-center text-sm text-gray-700">{{ model.sztuk }}</td>
                  <td class="px-6 py-3 text-center text-sm" :class="model.dostepne > 0 ? 'text-green-700' : 'text-red-700'">
                    {{ model.dostepne }}
                    <button
                      v-if="model.dostepne > 0"
                      type="button"
                      class="ml-2 text-xs text-indigo-600 hover:underline"
                      @click.stop="zaznaczModel(model, { target: { checked: !wszystkieZaznaczone(model) } })"
                    >
                      {{ wszystkieZaznaczone(model) ? 'odznacz' : 'zaznacz' }}
                    </button>
                  </td>
                  <td class="px-6 py-3 text-center text-sm text-gray-700">{{ model.na_budowie || '-' }}</td>
                  <td class="px-6 py-3 text-sm">
                    <span v-if="model.badania_po_terminie" class="text-red-700">{{ model.badania_po_terminie }} po terminie</span>
                    <span v-if="model.badania_wkrotce" class="text-orange-700" :class="model.badania_po_terminie ? 'ml-2' : ''">{{ model.badania_wkrotce }} kończy się</span>
                    <span v-if="!model.badania_uwaga" class="text-gray-300">-</span>
                  </td>
                  <td class="px-6 py-3 text-right text-xs text-gray-400">
                    {{ rozwiniete.includes(grupa.klucz + '/' + model.klucz) ? 'zwiń' : 'pokaż sztuki' }}
                  </td>
                </tr>

                <template v-if="!grupa.ma_modele || rozwiniete.includes(grupa.klucz + '/' + model.klucz)">
                  <tr v-for="(sztuka, si) in model.sztuki" :key="sztuka.id" class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-xs text-gray-400 tabular-nums">{{ grupa.ma_modele ? `${gi + 1}.${mi + 1}.${si + 1}` : `${gi + 1}.${si + 1}` }}</td>
                    <td class="pl-16 pr-6 py-2">
                      <label class="flex items-center" :class="sztuka.budowa ? 'cursor-default' : 'cursor-pointer'">
                        <input
                          v-if="!sztuka.budowa"
                          v-model="zaznaczone"
                          type="checkbox"
                          :value="sztuka.id"
                          class="mr-3"
                        />
                        <span v-else class="inline-block w-4 mr-3" />
                        <span class="font-medium text-gray-800">{{ sztuka.numer_seryjny || '—' }}</span>
                        <span v-if="sztuka.numer_udt" class="ml-2 text-xs text-gray-500">UDT {{ sztuka.numer_udt }}</span>
                      </label>
                    </td>
                    <td class="px-6 py-2" />
                    <td class="px-6 py-2 text-center text-sm">
                      <span v-if="!sztuka.budowa" class="text-green-700">magazyn</span>
                      <span v-else class="text-gray-300">–</span>
                    </td>
                    <td class="px-6 py-2 text-sm">
                      <span v-if="sztuka.budowa">
                        <Link :href="`/budowy/${sztuka.budowa.id}/edit`" class="text-indigo-600 hover:underline">{{ sztuka.budowa.nazwaBud }}</Link>
                        <span v-if="sztuka.budowa.do" class="text-gray-400"> do {{ sztuka.budowa.do }}</span>
                      </span>
                      <span v-else class="text-gray-300">–</span>
                    </td>
                    <td class="px-6 py-2 text-sm">
                      <span :class="klasaBadan(sztuka.badania_status)">{{ sztuka.waznosc_badan || 'brak daty' }}</span>
                    </td>
                    <td class="px-6 py-2 text-right whitespace-nowrap text-sm">
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
                </template>
              </template>
            </template>
          </template>

          <tr v-if="grupy.length === 0">
            <td class="px-6 py-12 text-center text-gray-500" colspan="7">
              <div class="flex flex-col items-center">
                <icon name="office" class="w-12 h-12 fill-gray-200 mb-2" />
                <p>Nie znaleziono żadnego sprzętu w magazynie</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Telefon: siedem kolumn w trzech poziomach nie ma szans; karta na rodzaj,
         w środku modele i sztuki. Rozwijanie dzieli stan z tabelą. -->
    <div class="space-y-3 md:hidden">
      <div v-for="(grupa, gi) in grupy" :key="`k-${grupa.klucz}`" class="bg-white rounded-md shadow-sm overflow-hidden">
        <button type="button" class="w-full p-4 text-left" @click="przelacz(grupa.klucz)">
          <div class="flex items-center gap-3">
            <img v-if="grupa.photo" :src="grupa.photo" :alt="grupa.nazwa" class="shrink-0 w-12 h-12 object-cover rounded-sm border border-gray-200" />
            <span v-else class="flex shrink-0 items-center justify-center w-12 h-12 bg-gray-50 rounded-sm border border-gray-200">
              <icon name="sprzet2" class="w-5 h-5 fill-gray-300" />
            </span>
            <div class="min-w-0 flex-1">
              <div class="font-medium text-gray-900">
                <span class="text-gray-400 tabular-nums mr-1">{{ gi + 1 }}.</span>{{ grupa.nazwa }}
              </div>
              <div v-if="grupa.ma_modele" class="text-xs text-gray-400">{{ grupa.modele.length }} modele</div>
            </div>
            <span class="text-xs text-gray-400 whitespace-nowrap">{{ rozwiniete.includes(grupa.klucz) ? 'zwiń' : 'rozwiń' }}</span>
          </div>
          <div class="mt-3 flex flex-wrap gap-2 text-xs">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium bg-gray-100 text-gray-800 border border-gray-200">{{ grupa.sztuk }} szt.</span>
            <span :class="grupa.dostepne > 0 ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'" class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium border">
              {{ grupa.dostepne }} dostępne
            </span>
            <span v-if="grupa.na_budowie > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium bg-orange-100 text-orange-800 border border-orange-200">
              {{ grupa.na_budowie }} na budowach
            </span>
            <span v-if="grupa.badania_po_terminie" class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium bg-red-100 text-red-800 border border-red-200">
              badania: {{ grupa.badania_po_terminie }} po terminie
            </span>
            <span v-if="grupa.badania_wkrotce" class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium bg-orange-100 text-orange-800 border border-orange-200">
              badania: {{ grupa.badania_wkrotce }} kończy się
            </span>
          </div>
        </button>

        <div v-if="rozwiniete.includes(grupa.klucz)" class="border-t border-gray-100 divide-y divide-gray-100">
          <div v-for="(model, mi) in grupa.modele" :key="`k-${model.klucz}`">
            <!-- Model tylko tam, gdzie rodzaj ma modele; inaczej sztuki leżą wprost pod rodzajem. -->
            <button v-if="grupa.ma_modele" type="button" class="w-full px-4 py-3 text-left bg-gray-50" @click="przelacz(grupa.klucz + '/' + model.klucz)">
              <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400 tabular-nums">{{ gi + 1 }}.{{ mi + 1 }}</span>
                <span class="font-medium text-gray-700 flex-1">{{ model.nazwa }}</span>
                <span class="text-xs text-gray-400">{{ rozwiniete.includes(grupa.klucz + '/' + model.klucz) ? 'zwiń' : 'sztuki' }}</span>
              </div>
              <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-600">
                <span>{{ model.sztuk }} szt.</span>
                <span :class="model.dostepne > 0 ? 'text-green-700' : 'text-red-700'">{{ model.dostepne }} dostępne</span>
                <span v-if="model.na_budowie">{{ model.na_budowie }} na budowach</span>
                <span v-if="model.badania_po_terminie" class="text-red-700">{{ model.badania_po_terminie }} po terminie badań</span>
                <span v-if="model.badania_wkrotce" class="text-orange-700">{{ model.badania_wkrotce }} kończy się</span>
                <span
                  v-if="model.dostepne > 0"
                  class="text-indigo-600 underline"
                  @click.stop="zaznaczModel(model, { target: { checked: !wszystkieZaznaczone(model) } })"
                >
                  {{ wszystkieZaznaczone(model) ? 'odznacz wszystkie' : 'zaznacz wszystkie' }}
                </span>
              </div>
            </button>

            <div v-if="!grupa.ma_modele || rozwiniete.includes(grupa.klucz + '/' + model.klucz)" class="divide-y divide-gray-100">
              <div v-for="(sztuka, si) in model.sztuki" :key="`k-${sztuka.id}`" class="px-4 py-3">
                <label class="flex items-start gap-3" :class="sztuka.budowa ? 'cursor-default' : 'cursor-pointer'">
                  <input v-if="!sztuka.budowa" v-model="zaznaczone" type="checkbox" :value="sztuka.id" class="mt-1" />
                  <span v-else class="inline-block w-4 shrink-0" />
                  <span class="min-w-0 flex-1">
                    <span class="block">
                      <span class="text-xs text-gray-400 tabular-nums mr-1">{{ grupa.ma_modele ? `${gi + 1}.${mi + 1}.${si + 1}` : `${gi + 1}.${si + 1}` }}</span>
                      <span class="font-medium text-gray-800">{{ sztuka.numer_seryjny || '—' }}</span>
                      <span v-if="sztuka.numer_udt" class="ml-2 text-xs text-gray-500">UDT {{ sztuka.numer_udt }}</span>
                    </span>
                    <span class="block mt-0.5 text-sm">
                      <span v-if="sztuka.budowa">
                        <Link :href="`/budowy/${sztuka.budowa.id}/edit`" class="text-indigo-600">{{ sztuka.budowa.nazwaBud }}</Link>
                        <span v-if="sztuka.budowa.do" class="text-gray-400"> do {{ sztuka.budowa.do }}</span>
                      </span>
                      <span v-else class="text-green-700">magazyn</span>
                      <span class="text-gray-400"> · badania: </span>
                      <span :class="klasaBadan(sztuka.badania_status)">{{ sztuka.waznosc_badan || 'brak daty' }}</span>
                    </span>
                  </span>
                </label>
                <div class="mt-2 pl-7 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                  <Link :href="`/narzedzia/${sztuka.id}/edit`" class="text-indigo-600">Karta sprzętu</Link>
                  <Link
                    v-if="sztuka.budowa"
                    :href="`/narzedzia/przypisanie/${sztuka.budowa.przypisanie_id}`"
                    method="delete"
                    as="button"
                    type="button"
                    class="text-gray-500 underline"
                  >
                    Zdejmij z budowy
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <p v-if="grupy.length === 0" class="bg-white rounded-md shadow-sm p-4 text-sm text-gray-500">
        Nie znaleziono żadnego sprzętu w magazynie
      </p>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
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
    // Klucze wszystkich poziomów: kategorie i modele w nich.
    wszystkieKlucze() {
      const klucze = []

      this.grupy.forEach((grupa) => {
        klucze.push(grupa.klucz)

        if (grupa.ma_modele) {
          grupa.modele.forEach((model) => klucze.push(grupa.klucz + '/' + model.klucz))
        }
      })

      return klucze
    },
    rozwinWszystko() {
      this.rozwiniete = this.wszystkieKlucze()
    },
    zwinWszystko() {
      this.rozwiniete = []
    },
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
    dostepneWModelu(model) {
      return model.sztuki.filter((s) => !s.budowa).map((s) => s.id)
    },
    wszystkieZaznaczone(model) {
      const dostepne = this.dostepneWModelu(model)
      return dostepne.length > 0 && dostepne.every((id) => this.zaznaczone.includes(id))
    },
    zaznaczModel(model, event) {
      const dostepne = this.dostepneWModelu(model)
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
