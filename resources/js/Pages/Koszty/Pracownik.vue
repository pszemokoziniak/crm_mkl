<template>
  <div>
    <Head title="Koszty pracownika" />
    <WorkerMenu :contact-id="contact.id" :user-owner="userOwner" />
    <pracownik-naglowek :pracownik="pracownik" tytul="Koszty" />

    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
      <miesiac-nawigacja v-model="wybranyMiesiac" />
      <button v-if="moze_edytowac && !formularz" type="button" class="btn-indigo w-full text-center sm:w-auto" @click="formularz = 'nowy'">Dodaj koszt</button>
    </div>

    <div class="flex flex-wrap gap-3 mb-6">
      <div class="px-4 py-3 rounded-lg border bg-white shadow-sm">
        <div class="text-xl font-semibold tabular-nums">{{ pln(sumy.pln) }}</div>
        <div class="text-xs text-gray-500">własne koszty</div>
      </div>
      <div class="px-4 py-3 rounded-lg border bg-white shadow-sm">
        <div class="text-xl font-semibold tabular-nums">{{ pln(suma_udzialow) }}</div>
        <div class="text-xs text-gray-500">udział w kosztach budów</div>
      </div>
      <div class="px-4 py-3 rounded-lg border border-indigo-200 bg-indigo-50">
        <div class="text-2xl font-bold text-indigo-900 tabular-nums">{{ pln(sumy.pln + suma_udzialow) }}</div>
        <div class="text-xs text-indigo-700">razem w miesiącu</div>
      </div>
    </div>

    <div v-if="formularz" class="mb-6">
      <koszt-formularz
        :key="formularz === 'nowy' ? 'nowy' : `edycja-${formularz.id}`"
        :typy="typy"
        :waluty="waluty"
        :budowy="budowy"
        :koszt="formularz === 'nowy' ? null : formularz"
        :adres="`/contacts/${contact.id}/koszty`"
        @zamknij="formularz = null"
      />
    </div>

    <h2 class="mb-3 text-xl font-bold text-gray-900">Własne koszty</h2>
    <lista-kosztow class="mb-8" :koszty="koszty" kolumna-kto="budowa" :moze-edytowac="moze_edytowac" pusto="Brak własnych kosztów w tym miesiącu." @edytuj="formularz = $event" />

    <h2 class="mb-1 text-xl font-bold text-gray-900">Udział w kosztach budów</h2>
    <p class="mb-3 text-sm text-gray-500">Pokoje i inne koszty dzielone, wpisane na budowie — część przypadająca na tę osobę.</p>
    <div class="bg-white rounded-md shadow overflow-hidden">
      <div class="divide-y divide-gray-100">
        <div v-for="(u, i) in udzialy" :key="i" class="px-4 py-3 sm:px-6 flex flex-wrap items-start justify-between gap-x-3 gap-y-1 text-sm">
          <div>
            <div class="text-gray-900">{{ u.typ }}<span v-if="u.opis" class="text-gray-600"> · {{ u.opis }}</span></div>
            <div class="text-xs text-gray-500">{{ u.data }} · {{ u.budowa }} · {{ u.sposob }}</div>
          </div>
          <div class="tabular-nums font-semibold whitespace-nowrap">{{ pln(u.kwota_pln) }} <span class="text-xs font-normal text-gray-400">z {{ pln(u.calosc_pln) }}</span></div>
        </div>
        <p v-if="udzialy.length === 0" class="px-4 py-6 sm:px-6 text-sm text-gray-500">Brak udziałów w tym miesiącu.</p>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/inertia-vue3'
import KosztFormularz from '@/Shared/KosztFormularz'
import Layout from '@/Shared/Layout'
import ListaKosztow from '@/Shared/ListaKosztow'
import MiesiacNawigacja from '@/Shared/MiesiacNawigacja'
import PracownikNaglowek from '@/Shared/PracownikNaglowek'
import WorkerMenu from '@/Shared/WorkerMenu'

export default {
  components: { Head, KosztFormularz, ListaKosztow, MiesiacNawigacja, PracownikNaglowek, WorkerMenu },
  layout: Layout,
  props: {
    pracownik: Object,
    contact: Object,
    userOwner: Number,
    miesiac: String,
    koszty: Array,
    sumy: Object,
    udzialy: Array,
    suma_udzialow: Number,
    typy: Array,
    waluty: Array,
    budowy: Array,
    moze_edytowac: Boolean,
  },
  data() {
    return { wybranyMiesiac: this.miesiac, formularz: null }
  },
  watch: {
    wybranyMiesiac(m) {
      if (m && m !== this.miesiac) {
        this.$inertia.get(`/contacts/${this.contact.id}/koszty`, { miesiac: m }, { preserveState: true, preserveScroll: true, replace: true })
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
  },
}
</script>
