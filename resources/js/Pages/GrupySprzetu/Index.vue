<template>
  <div>
    <Head title="Grupy sprzętu" />
    <h1 class="mb-2 text-2xl sm:text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/tools">Ustawienia</Link>
      <span class="text-indigo-400 font-medium">/</span> Grupy sprzętu
    </h1>
    <p class="mb-6 text-sm text-gray-500">
      Grupy zbierają modele w magazynie — „Manitou” to grupa, „Manitou MRT 2150” to model.
      Zmiana nazwy grupy przestawia wszystkie jej modele naraz.
    </p>

    <div v-for="grupa in grupy" :key="grupa.nazwa" class="mb-4 bg-white rounded-md shadow overflow-hidden">
      <div class="flex flex-wrap items-center gap-3 px-6 py-4 border-b border-gray-100">
        <span class="font-semibold text-gray-800">{{ grupa.nazwa }}</span>
        <span class="text-sm text-gray-500">{{ grupa.modeli }} {{ odmien(grupa.modeli, 'model', 'modele', 'modeli') }} · {{ grupa.sztuk }} szt.</span>
        <div class="ml-auto flex items-center gap-4 text-sm">
          <button type="button" class="text-indigo-600 hover:underline" @click="zacznijZmiane(grupa)">Zmień nazwę</button>
          <button type="button" class="text-red-600 hover:underline" @click="usun(grupa)">Usuń grupę</button>
          <button type="button" class="text-gray-500 hover:text-gray-800" @click="przelacz(grupa.nazwa)">
            {{ rozwinieta === grupa.nazwa ? 'zwiń' : 'pokaż modele' }}
          </button>
        </div>
      </div>

      <!-- Zmiana nazwy w miejscu: przeniesienie do istniejącej nazwy łączy grupy,
           więc mówimy o tym wprost, zanim ktoś kliknie. -->
      <div v-if="zmieniana === grupa.nazwa" class="px-6 py-4 bg-indigo-50 border-b border-indigo-100">
        <div class="flex flex-wrap items-center gap-3">
          <input v-model="nowaNazwa" type="text" class="form-input max-w-xs" @keyup.enter="zapiszNazwe(grupa)" />
          <button type="button" class="btn-indigo" @click="zapiszNazwe(grupa)">Zapisz</button>
          <button type="button" class="text-sm text-gray-500 hover:text-gray-800" @click="zmieniana = null">Anuluj</button>
        </div>
        <p v-if="nazwaZajeta" class="mt-2 text-sm text-orange-700">
          Grupa „{{ nowaNazwa }}” już istnieje — modele zostaną do niej dołączone, a obie grupy połączą się w jedną.
        </p>
      </div>

      <div v-if="rozwinieta === grupa.nazwa" class="divide-y divide-gray-50">
        <div v-for="model in grupa.modele" :key="model.id" class="flex flex-wrap items-center gap-3 px-6 py-3 text-sm">
          <span class="text-gray-800">{{ model.name }}</span>
          <span class="text-gray-400">{{ model.sztuk }} szt.</span>
          <select class="form-select ml-auto max-w-xs text-sm" :value="grupa.nazwa" @change="przenies(model, $event.target.value)">
            <option v-for="n in nazwyGrup" :key="n" :value="n">{{ n }}</option>
            <option value="">— bez grupy —</option>
          </select>
        </div>
      </div>
    </div>

    <p v-if="!grupy.length" class="mb-4 bg-white rounded-md shadow px-6 py-6 text-sm text-gray-400">
      Nie ma jeszcze żadnej grupy. Grupę tworzy się przy dodawaniu modelu sprzętu.
    </p>

    <!-- Modele poza grupami: stąd najczęściej się je przypisuje. -->
    <div class="bg-white rounded-md shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100">
        <span class="font-semibold text-gray-800">Modele bez grupy</span>
        <span class="ml-2 text-sm text-gray-500">{{ bezGrupy.length }}</span>
      </div>
      <div v-if="bezGrupy.length" class="divide-y divide-gray-50">
        <div v-for="model in bezGrupy" :key="model.id" class="flex flex-wrap items-center gap-3 px-6 py-3 text-sm">
          <span class="text-gray-800">{{ model.name }}</span>
          <span class="text-gray-400">{{ model.sztuk }} szt.</span>
          <select class="form-select ml-auto max-w-xs text-sm" value="" @change="przenies(model, $event.target.value)">
            <option value="">— bez grupy —</option>
            <option v-for="n in nazwyGrup" :key="n" :value="n">{{ n }}</option>
          </select>
        </div>
      </div>
      <p v-else class="px-6 py-4 text-sm text-gray-400">Wszystkie modele są przypisane do grup.</p>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'

export default {
  components: { Head, Link },
  layout: Layout,
  props: {
    grupy: { type: Array, default: () => [] },
    bezGrupy: { type: Array, default: () => [] },
  },
  data() {
    return { rozwinieta: null, zmieniana: null, nowaNazwa: '' }
  },
  computed: {
    nazwyGrup() {
      return this.grupy.map((g) => g.nazwa)
    },
    nazwaZajeta() {
      const n = this.nowaNazwa.trim()

      return n !== '' && n !== this.zmieniana && this.nazwyGrup.includes(n)
    },
  },
  methods: {
    odmien(ile, poj, mn, dop) {
      if (ile === 1) return poj
      const ost = ile % 10
      const dwie = ile % 100

      return ost >= 2 && ost <= 4 && (dwie < 12 || dwie > 14) ? mn : dop
    },
    przelacz(nazwa) {
      this.rozwinieta = this.rozwinieta === nazwa ? null : nazwa
    },
    zacznijZmiane(grupa) {
      this.zmieniana = grupa.nazwa
      this.nowaNazwa = grupa.nazwa
    },
    zapiszNazwe(grupa) {
      const nowa = this.nowaNazwa.trim()
      if (!nowa || nowa === grupa.nazwa) {
        this.zmieniana = null

        return
      }
      if (this.nazwaZajeta && !confirm(`Grupa „${nowa}” już istnieje. Połączyć obie w jedną?`)) return

      this.$inertia.put('/grupy-sprzetu', { stara: grupa.nazwa, nowa }, {
        preserveScroll: true,
        onSuccess: () => { this.zmieniana = null },
      })
    },
    usun(grupa) {
      const pytanie = `Usunąć grupę „${grupa.nazwa}”?\n\n`
        + `${grupa.modeli} ${this.odmien(grupa.modeli, 'model zostanie', 'modele zostaną', 'modeli zostanie')} bez grupy.\n`
        + 'Sprzęt (' + grupa.sztuk + ' szt.) zostaje nietknięty.'
      if (confirm(pytanie)) {
        this.$inertia.delete('/grupy-sprzetu', { data: { nazwa: grupa.nazwa }, preserveScroll: true })
      }
    },
    przenies(model, grupa) {
      this.$inertia.post('/grupy-sprzetu/przypisz', { modele: [model.id], grupa: grupa || null }, { preserveScroll: true })
    },
  },
}
</script>
