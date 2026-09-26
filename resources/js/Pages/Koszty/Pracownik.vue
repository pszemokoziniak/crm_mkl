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

    <!-- Koszty pogrupowane po budowach: pod każdą jego własne wpisy i udział
         w kosztach tej budowy, z sumą budowy. -->
    <div v-for="grupa in po_budowach" :key="grupa.organization_id ?? 'bez'" class="mb-6 bg-white rounded-md shadow overflow-hidden">
      <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 sm:px-6 border-b border-gray-100">
        <Link v-if="grupa.organization_id" :href="`/budowy/${grupa.organization_id}/koszty?miesiac=${miesiac}`" class="font-semibold text-gray-800 hover:text-indigo-600">{{ grupa.budowa }}</Link>
        <span v-else class="font-semibold text-gray-500">{{ grupa.budowa }}</span>
        <span class="font-bold tabular-nums text-indigo-900">{{ pln(grupa.suma_pln) }}</span>
      </div>

      <div class="p-4 sm:px-6 space-y-4">
        <div v-if="grupa.wlasne.length">
          <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Wpisane na tę osobę</div>
          <lista-kosztow :koszty="grupa.wlasne" :pokaz-kto="false" :moze-edytowac="moze_edytowac" @edytuj="formularz = $event" />
        </div>

        <div v-if="grupa.udzialy.length">
          <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Udział w kosztach budowy</div>
          <div class="divide-y divide-gray-100 border rounded-md">
            <div v-for="(u, i) in grupa.udzialy" :key="i" class="px-3 py-2 flex flex-wrap items-start justify-between gap-x-3 gap-y-1 text-sm">
              <div>
                <div class="text-gray-900">{{ u.typ }}<span v-if="u.opis" class="text-gray-600"> · {{ u.opis }}</span></div>
                <div class="text-xs text-gray-500">{{ u.data }} · {{ u.sposob }}</div>
              </div>
              <div class="tabular-nums font-semibold whitespace-nowrap">{{ pln(u.kwota_pln) }} <span class="text-xs font-normal text-gray-400">z {{ pln(u.calosc_pln) }}</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <p v-if="po_budowach.length === 0" class="bg-white rounded-md shadow px-4 py-6 sm:px-6 text-sm text-gray-500">
      Brak kosztów w tym miesiącu.
    </p>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import KosztFormularz from '@/Shared/KosztFormularz'
import Layout from '@/Shared/Layout'
import ListaKosztow from '@/Shared/ListaKosztow'
import MiesiacNawigacja from '@/Shared/MiesiacNawigacja'
import PracownikNaglowek from '@/Shared/PracownikNaglowek'
import WorkerMenu from '@/Shared/WorkerMenu'

export default {
  components: { Head, Link, KosztFormularz, ListaKosztow, MiesiacNawigacja, PracownikNaglowek, WorkerMenu },
  layout: Layout,
  props: {
    pracownik: Object,
    contact: Object,
    userOwner: Number,
    miesiac: String,
    po_budowach: Array,
    sumy: Object,
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
