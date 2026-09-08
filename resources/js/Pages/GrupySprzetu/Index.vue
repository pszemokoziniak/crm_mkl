<template>
  <div>
    <Head title="Grupy sprzętu" />
    <h1 class="mb-2 text-2xl sm:text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/tools">Ustawienia</Link>
      <span class="text-indigo-400 font-medium">/</span> Grupy sprzętu
    </h1>
    <p class="mb-6 text-sm text-gray-500">
      Grupy zbierają modele w magazynie — „Manitou” to grupa, „Manitou MRT 2150” to model.
      Grupę można założyć pustą i dopiero potem przypisać do niej sprzęt.
    </p>

    <!-- Zakładanie grupy pierwsze, bo od tego zaczyna się porządkowanie magazynu. -->
    <div class="mb-6 bg-white rounded-md shadow overflow-hidden">
      <form class="flex flex-wrap items-end gap-3 px-6 py-4" @submit.prevent="dodaj">
        <div class="flex-1 min-w-[14rem]">
          <label class="form-label" for="nowa-grupa">Nowa grupa</label>
          <input
            id="nowa-grupa"
            v-model="formNowej.nazwa"
            type="text"
            class="form-input"
            placeholder="np. Żuraw, Zagęszczarka, Rusztowanie"
          />
        </div>
        <loading-button :loading="formNowej.processing" class="btn-indigo" type="submit">Utwórz grupę</loading-button>
      </form>
    </div>

    <div v-for="grupa in grupy" :key="grupa.id" class="mb-4 bg-white rounded-md shadow overflow-hidden">
      <div class="flex flex-wrap items-center gap-3 px-6 py-4 border-b border-gray-100">
        <span class="font-semibold text-gray-800">{{ grupa.nazwa }}</span>
        <span class="text-sm text-gray-500">
          {{ grupa.modeli }} {{ odmien(grupa.modeli, 'model', 'modele', 'modeli') }} · {{ grupa.sztuk }} szt.
        </span>
        <div class="ml-auto flex items-center gap-4 text-sm">
          <button type="button" class="text-indigo-600 hover:underline" @click="zacznijZmiane(grupa)">Zmień nazwę</button>
          <button type="button" class="text-red-600 hover:underline" @click="usun(grupa)">Usuń grupę</button>
          <button type="button" class="text-gray-500 hover:text-gray-800" @click="przelacz(grupa.id)">
            {{ rozwinieta === grupa.id ? 'zwiń' : 'pokaż modele' }}
          </button>
        </div>
      </div>

      <!-- Zmiana nazwy w miejscu: przeniesienie do istniejącej nazwy łączy grupy,
           więc mówimy o tym wprost, zanim ktoś kliknie. -->
      <div v-if="zmieniana === grupa.id" class="px-6 py-4 bg-indigo-50 border-b border-indigo-100">
        <div class="flex flex-wrap items-center gap-3">
          <input v-model="nowaNazwa" type="text" class="form-input max-w-xs" @keyup.enter="zapiszNazwe(grupa)" />
          <button type="button" class="btn-indigo" @click="zapiszNazwe(grupa)">Zapisz</button>
          <button type="button" class="text-sm text-gray-500 hover:text-gray-800" @click="zmieniana = null">Anuluj</button>
        </div>
        <p v-if="nazwaZajeta" class="mt-2 text-sm text-orange-700">
          Grupa „{{ nowaNazwa }}” już istnieje — modele zostaną do niej dołączone, a obie grupy połączą się w jedną.
        </p>
      </div>

      <div v-if="rozwinieta === grupa.id" class="divide-y divide-gray-50">
        <div v-for="model in grupa.modele" :key="model.id" class="flex flex-wrap items-center gap-3 px-6 py-3 text-sm">
          <span class="text-gray-800">{{ model.name }}</span>
          <span class="text-gray-400">{{ model.sztuk }} szt.</span>
          <select class="form-select ml-auto max-w-xs text-sm" :value="grupa.id" @change="przenies(model, $event.target.value)">
            <option v-for="g in grupy" :key="g.id" :value="g.id">{{ g.nazwa }}</option>
            <option value="">— bez grupy —</option>
          </select>
        </div>
        <p v-if="!grupa.modele.length" class="px-6 py-3 text-sm text-gray-400">
          Grupa jest pusta — przypisz do niej modele poniżej.
        </p>
      </div>
    </div>

    <p v-if="!grupy.length" class="mb-4 bg-white rounded-md shadow px-6 py-6 text-sm text-gray-400">
      Nie ma jeszcze żadnej grupy. Załóż pierwszą powyżej.
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
            <option v-for="g in grupy" :key="g.id" :value="g.id">{{ g.nazwa }}</option>
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
import LoadingButton from '@/Shared/LoadingButton'

export default {
  components: { Head, Link, LoadingButton },
  layout: Layout,
  props: {
    grupy: { type: Array, default: () => [] },
    bezGrupy: { type: Array, default: () => [] },
  },
  data() {
    return {
      rozwinieta: null,
      zmieniana: null,
      nowaNazwa: '',
      formNowej: this.$inertia.form({ nazwa: '' }),
    }
  },
  computed: {
    nazwaZajeta() {
      const n = this.nowaNazwa.trim().toLowerCase()

      return n !== '' && this.grupy.some((g) => g.id !== this.zmieniana && g.nazwa.toLowerCase() === n)
    },
  },
  methods: {
    odmien(ile, poj, mn, dop) {
      if (ile === 1) return poj
      const ost = ile % 10
      const dwie = ile % 100

      return ost >= 2 && ost <= 4 && (dwie < 12 || dwie > 14) ? mn : dop
    },
    dodaj() {
      if (!this.formNowej.nazwa.trim()) return
      this.formNowej.post('/grupy-sprzetu', {
        preserveScroll: true,
        onSuccess: () => this.formNowej.reset(),
      })
    },
    przelacz(id) {
      this.rozwinieta = this.rozwinieta === id ? null : id
    },
    zacznijZmiane(grupa) {
      this.zmieniana = grupa.id
      this.nowaNazwa = grupa.nazwa
    },
    zapiszNazwe(grupa) {
      const nazwa = this.nowaNazwa.trim()
      if (!nazwa || nazwa === grupa.nazwa) {
        this.zmieniana = null

        return
      }
      if (this.nazwaZajeta && !confirm(`Grupa „${nazwa}” już istnieje. Połączyć obie w jedną?`)) return

      this.$inertia.put(`/grupy-sprzetu/${grupa.id}`, { nazwa }, {
        preserveScroll: true,
        onSuccess: () => { this.zmieniana = null },
      })
    },
    usun(grupa) {
      const pytanie = grupa.modeli
        ? `Usunąć grupę „${grupa.nazwa}”?\n\n`
          + `${grupa.modeli} ${this.odmien(grupa.modeli, 'model zostanie', 'modele zostaną', 'modeli zostanie')} bez grupy.\n`
          + `Sprzęt (${grupa.sztuk} szt.) zostaje nietknięty.`
        : `Usunąć pustą grupę „${grupa.nazwa}”?`

      if (confirm(pytanie)) {
        this.$inertia.delete(`/grupy-sprzetu/${grupa.id}`, { preserveScroll: true })
      }
    },
    przenies(model, grupaId) {
      this.$inertia.post('/grupy-sprzetu/przypisz', {
        modele: [model.id],
        grupa_id: grupaId ? Number(grupaId) : null,
      }, { preserveScroll: true })
    },
  },
}
</script>
