<template>
  <div>
    <!-- Telefon: karta na koszt. -->
    <div class="sm:hidden space-y-3">
      <div v-for="k in koszty" :key="`k-${k.id}`" class="bg-white rounded-md shadow-sm p-4">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="font-medium text-gray-900">{{ k.typ }}</div>
            <div class="text-xs text-gray-500 tabular-nums">{{ k.data }}<span v-if="pokazKto && kto(k)"> · {{ kto(k) }}</span></div>
          </div>
          <div class="text-right whitespace-nowrap">
            <div class="font-semibold tabular-nums">{{ pln(k.kwota_pln) }}</div>
            <div v-if="k.waluta !== 'PLN'" class="text-xs text-gray-500 tabular-nums">{{ k.kwota.toFixed(2) }} {{ k.waluta }} · {{ k.kurs }}{{ k.kurs_reczny ? ' (ręczny)' : '' }}</div>
          </div>
        </div>
        <p v-if="k.opis" class="mt-1 text-sm text-gray-700">{{ k.opis }}</p>
        <p v-if="k.nocleg" class="mt-1 text-xs text-gray-600">pokój {{ k.od }} – {{ k.do }}, miejsc: {{ k.miejsc }}, zakwaterowanych: {{ k.osoby.length }}</p>
        <p v-else-if="k.dzielony" class="mt-1 text-xs text-gray-600">{{ opisPodzialu(k) }}</p>
        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
          <a v-if="k.plik" :href="k.plik" target="_blank" class="text-indigo-600">skan</a>
          <template v-if="mozeEdytowac">
            <button v-if="pracownicy && (k.nocleg || (k.dzielony && !k.contact_id))" type="button" class="text-indigo-600" @click="przelaczOsoby(k)">
              {{ k.nocleg ? 'Zakwaterowanie' : 'Osoby' }}
            </button>
            <button type="button" class="text-indigo-600" @click="$emit('edytuj', k)">Edytuj</button>
            <button type="button" class="text-red-600" @click="usun(k)">Usuń</button>
          </template>
        </div>
        <panel-osob v-if="otwarte === k.id" :koszt="k" :pracownicy="pracownicy" @zamknij="otwarte = null" />
      </div>
      <p v-if="koszty.length === 0" class="bg-white rounded-md shadow-sm p-4 text-sm text-gray-500">{{ pusto }}</p>
    </div>

    <div class="hidden sm:block bg-white rounded-md shadow-sm overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="naglowek-tabeli">
            <th>Data</th>
            <th>Typ / opis</th>
            <th v-if="pokazKto">{{ kolumnaKto === 'pracownik' ? 'Pracownik' : 'Budowa' }}</th>
            <th class="text-right">Kwota</th>
            <th class="text-right">Kurs</th>
            <th class="text-right">PLN</th>
            <th />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <template v-for="k in koszty" :key="k.id">
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3 tabular-nums whitespace-nowrap">{{ k.data }}</td>
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900">{{ k.typ }}</div>
                <div v-if="k.opis" class="text-xs text-gray-600">{{ k.opis }}</div>
                <div v-if="k.nocleg" class="text-xs text-gray-500">pokój {{ k.od }} – {{ k.do }} · miejsc: {{ k.miejsc }} · zakwaterowanych: {{ k.osoby.length }}</div>
                <div v-else-if="k.dzielony" class="text-xs text-gray-500">{{ opisPodzialu(k) }}</div>
              </td>
              <td v-if="pokazKto" class="px-4 py-3 text-gray-700">{{ kto(k) || '—' }}</td>
              <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap">{{ k.kwota.toFixed(2) }} {{ k.waluta }}</td>
              <td class="px-4 py-3 text-right tabular-nums text-gray-500 whitespace-nowrap">
                <template v-if="k.waluta !== 'PLN'">{{ k.kurs }}<span v-if="k.kurs_reczny" class="text-xs"> (ręczny)</span></template>
                <span v-else>—</span>
              </td>
              <td class="px-4 py-3 text-right tabular-nums font-semibold whitespace-nowrap">{{ pln(k.kwota_pln) }}</td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <a v-if="k.plik" :href="k.plik" target="_blank" class="text-indigo-600 hover:underline mr-3">skan</a>
                <template v-if="mozeEdytowac">
                  <button v-if="pracownicy && (k.nocleg || (k.dzielony && !k.contact_id))" type="button" class="text-indigo-600 hover:underline mr-3" @click="przelaczOsoby(k)">
                    {{ k.nocleg ? 'Zakwaterowanie' : 'Osoby' }}
                  </button>
                  <button type="button" class="text-indigo-600 hover:underline mr-3" @click="$emit('edytuj', k)">Edytuj</button>
                  <button type="button" class="text-red-600 hover:underline" @click="usun(k)">Usuń</button>
                </template>
              </td>
            </tr>
            <tr v-if="otwarte === k.id">
              <td :colspan="pokazKto ? 7 : 6" class="px-4 py-3 bg-indigo-50">
                <panel-osob :koszt="k" :pracownicy="pracownicy" @zamknij="otwarte = null" />
              </td>
            </tr>
          </template>
          <tr v-if="koszty.length === 0">
            <td :colspan="pokazKto ? 7 : 6" class="px-4 py-6 text-gray-500">{{ pusto }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import PanelOsob from '@/Shared/PanelOsobKosztu'

export default {
  components: { PanelOsob },
  props: {
    koszty: { type: Array, required: true },
    // Na budowie pokazujemy pracownika, w karcie osoby — budowę.
    kolumnaKto: { type: String, default: 'pracownik' },
    // W grupie budowy kolumna "kto" jest zbędna — budowa jest w nagłówku grupy.
    pokazKto: { type: Boolean, default: true },
    mozeEdytowac: { type: Boolean, default: false },
    // Lista osób do zakwaterowania — tylko na budowie.
    pracownicy: { type: Array, default: null },
    pusto: { type: String, default: 'Brak kosztów w tym miesiącu.' },
  },
  emits: ['edytuj'],
  data() {
    return { otwarte: null }
  },
  methods: {
    pln(v) {
      return (Number(v) || 0).toFixed(2).replace('.', ',') + ' zł'
    },
    kto(k) {
      return this.kolumnaKto === 'pracownik' ? k.pracownik : k.budowa
    },
    opisPodzialu(k) {
      if (k.osoby && k.osoby.length) return 'dzielony po równo: ' + k.osoby.map((o) => o.nazwa).join(', ')
      return 'dzielony po dniach pobytu na budowie'
    },
    przelaczOsoby(k) {
      this.otwarte = this.otwarte === k.id ? null : k.id
    },
    usun(k) {
      if (!confirm('Usunąć ten koszt?')) return
      this.$inertia.delete(`/koszty/${k.id}`, { preserveScroll: true })
    },
  },
}
</script>
