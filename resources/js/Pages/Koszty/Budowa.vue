<template>
  <div>
    <Head title="Koszty budowy" />
    <BudMenu :bud-id="build" />
    <budowa-naglowek :bud-id="buildDetails.id" :nazwa="buildDetails.nazwaBud" tytul="Koszty" />

    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
      <miesiac-nawigacja v-model="wybranyMiesiac" />
      <button v-if="moze_edytowac && !formularz" type="button" class="btn-indigo w-full text-center sm:w-auto" @click="formularz = 'nowy'">Dodaj koszt</button>
    </div>

    <!-- Sumy: PLN po kursach oraz osobno w każdej walucie, bez przeliczania. -->
    <div class="flex flex-wrap gap-3 mb-6">
      <div class="px-4 py-3 rounded-lg border border-indigo-200 bg-indigo-50">
        <div class="text-2xl font-bold text-indigo-900 tabular-nums">{{ pln(sumy.pln) }}</div>
        <div class="text-xs text-indigo-700">razem w miesiącu (PLN po kursie z dnia)</div>
      </div>
      <div v-for="(kwota, waluta) in sumy.waluty" :key="waluta" class="px-4 py-3 rounded-lg border bg-white shadow-sm">
        <div class="text-xl font-semibold tabular-nums">{{ kwota.toFixed(2) }} {{ waluta }}</div>
        <div class="text-xs text-gray-500">w walucie</div>
      </div>
    </div>

    <div v-if="formularz" class="mb-6">
      <koszt-formularz
        :key="formularz === 'nowy' ? 'nowy' : `edycja-${formularz.id}`"
        :typy="typy"
        :waluty="waluty"
        :osoby="pracownicy"
        :koszt="formularz === 'nowy' ? null : formularz"
        :adres="`/budowy/${build}/koszty`"
        @zamknij="formularz = null"
      />
    </div>

    <h2 class="mb-3 text-xl font-bold text-gray-900">Wpisy</h2>
    <lista-kosztow class="mb-8" :koszty="koszty" kolumna-kto="pracownik" :moze-edytowac="moze_edytowac" :pracownicy="pracownicy" @edytuj="formularz = $event" />

    <!-- To, po co ta zakładka jest: ile z kosztów budowy przypada na każdą osobę. -->
    <h2 class="mb-1 text-xl font-bold text-gray-900">Podział na pracowników</h2>
    <p class="mb-3 text-sm text-gray-500">
      Koszt osoby idzie na nią w całości; pokój dzieli się po osobodobach; inny dzielony koszt po wskazanych osobach
      albo po dniach pobytu na budowie w tym miesiącu.
    </p>
    <div class="bg-white rounded-md shadow overflow-hidden">
      <div class="divide-y divide-gray-100">
        <div v-for="w in podzial" :key="w.contact_id ?? 'x'" class="px-4 py-3 sm:px-6">
          <button type="button" class="w-full flex items-center justify-between gap-3 text-left" @click="przelacz(w)">
            <span class="font-medium" :class="w.contact_id ? 'text-gray-900' : 'text-orange-700'">{{ w.pracownik }}</span>
            <span class="font-semibold tabular-nums whitespace-nowrap">{{ pln(w.kwota_pln) }}</span>
          </button>
          <ul v-if="rozwiniete.includes(w.contact_id ?? 'x')" class="mt-2 space-y-1 text-sm text-gray-600">
            <li v-for="(p, i) in w.pozycje" :key="i" class="flex flex-wrap justify-between gap-x-3">
              <span>{{ p.data }} · {{ p.typ }}<span v-if="p.opis"> · {{ p.opis }}</span> <span class="text-xs text-gray-400">({{ p.sposob }})</span></span>
              <span class="tabular-nums">{{ pln(p.kwota_pln) }}<span v-if="p.kwota_pln !== p.calosc_pln" class="text-xs text-gray-400"> z {{ pln(p.calosc_pln) }}</span></span>
            </li>
          </ul>
        </div>
        <p v-if="podzial.length === 0" class="px-4 py-6 sm:px-6 text-sm text-gray-500">Brak kosztów do podziału w tym miesiącu.</p>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/inertia-vue3'
import BudMenu from '@/Shared/BudMenu.vue'
import BudowaNaglowek from '@/Shared/BudowaNaglowek'
import KosztFormularz from '@/Shared/KosztFormularz'
import Layout from '@/Shared/Layout'
import ListaKosztow from '@/Shared/ListaKosztow'
import MiesiacNawigacja from '@/Shared/MiesiacNawigacja'

export default {
  components: { BudMenu, BudowaNaglowek, Head, KosztFormularz, ListaKosztow, MiesiacNawigacja },
  layout: Layout,
  props: {
    build: Number,
    buildDetails: Object,
    miesiac: String,
    koszty: Array,
    sumy: Object,
    podzial: Array,
    typy: Array,
    waluty: Array,
    pracownicy: Array,
    moze_edytowac: Boolean,
  },
  data() {
    return {
      wybranyMiesiac: this.miesiac,
      formularz: null,
      rozwiniete: [],
    }
  },
  watch: {
    wybranyMiesiac(m) {
      if (m && m !== this.miesiac) {
        this.$inertia.get(`/budowy/${this.build}/koszty`, { miesiac: m }, { preserveState: true, preserveScroll: true, replace: true })
      }
    },
    miesiac(m) {
      this.wybranyMiesiac = m
    },
  },
  methods: {
    pln(v) {
      return (Number(v) || 0).toFixed(2).replace('.', ',') + ' zł'
    },
    przelacz(w) {
      const k = w.contact_id ?? 'x'
      const i = this.rozwiniete.indexOf(k)
      i === -1 ? this.rozwiniete.push(k) : this.rozwiniete.splice(i, 1)
    },
  },
}
</script>
