<template>
  <div>
    <Head title="Statystyki" />
    <h1 class="mb-2 text-2xl sm:text-3xl font-bold">Statystyki budów</h1>
    <p class="mb-6 text-sm text-gray-500">
      Liczone z Karty Czasu Pracy. Jeden wpis to jeden dzień jednego pracownika na budowie.
    </p>

    <div class="flex flex-wrap items-center gap-3 mb-6">
      <label class="text-sm text-gray-600" for="rok">Okres:</label>
      <select id="rok" v-model="rok" class="form-select text-sm py-1.5" @change="przeladuj">
        <option value="wszystko">Wszystkie lata</option>
        <option v-for="r in lata" :key="r" :value="String(r)">{{ r }}</option>
      </select>
    </div>

    <div v-if="budowy.length" class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="naglowek-tabeli">
            <th>Budowa</th>
            <th class="text-right">Roboczogodziny</th>
            <th class="text-right">Urlopy</th>
            <th class="text-right">Zwolnienia</th>
            <th class="text-right">Nieobecności</th>
            <th class="text-right">Święta</th>
            <th class="text-right">Inne</th>
            <th class="text-right">Przerwy</th>
            <th class="text-right">Osób</th>
            <th class="text-right">Dniówek</th>
            <th class="text-right">Śr. dniówka</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="b in budowy" :key="b.id" class="hover:bg-gray-50">
            <td class="border-t px-6 py-3 font-medium text-gray-800">{{ b.nazwa }}</td>
            <td class="border-t px-6 py-3 text-right font-semibold tabular-nums">{{ godz(b.godziny.praca) }}</td>
            <td class="border-t px-6 py-3 text-right tabular-nums">{{ godz(b.godziny.urlop) }}</td>
            <td class="border-t px-6 py-3 text-right tabular-nums">{{ godz(b.godziny.zwolnienie) }}</td>
            <td class="border-t px-6 py-3 text-right tabular-nums" :class="b.godziny.nieobecnosc > 0 ? 'text-red-700 font-medium' : ''">
              {{ godz(b.godziny.nieobecnosc) }}
            </td>
            <td class="border-t px-6 py-3 text-right tabular-nums">{{ godz(b.godziny.swieto) }}</td>
            <td class="border-t px-6 py-3 text-right tabular-nums text-gray-500">{{ godz(b.godziny.inne) }}</td>
            <td class="border-t px-6 py-3 text-right tabular-nums text-gray-500">{{ godz(b.przerwy) }}</td>
            <td class="border-t px-6 py-3 text-right tabular-nums">{{ b.pracownikow }}</td>
            <td class="border-t px-6 py-3 text-right tabular-nums">{{ b.dni_pracy }}</td>
            <td class="border-t px-6 py-3 text-right tabular-nums">{{ b.srednia_dniowka !== null ? godz(b.srednia_dniowka) : '—' }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr class="bg-gray-50 font-semibold">
            <td class="border-t px-6 py-3">Razem</td>
            <td class="border-t px-6 py-3 text-right tabular-nums">{{ godz(suma('praca')) }}</td>
            <td class="border-t px-6 py-3 text-right tabular-nums">{{ godz(suma('urlop')) }}</td>
            <td class="border-t px-6 py-3 text-right tabular-nums">{{ godz(suma('zwolnienie')) }}</td>
            <td class="border-t px-6 py-3 text-right tabular-nums">{{ godz(suma('nieobecnosc')) }}</td>
            <td class="border-t px-6 py-3 text-right tabular-nums">{{ godz(suma('swieto')) }}</td>
            <td class="border-t px-6 py-3 text-right tabular-nums">{{ godz(suma('inne')) }}</td>
            <td class="border-t px-6 py-3 text-right tabular-nums">{{ godz(sumaPrzerw) }}</td>
            <td class="border-t px-6 py-3" colspan="3" />
          </tr>
        </tfoot>
      </table>
    </div>
    <p v-else class="bg-white rounded-md shadow px-6 py-6 text-sm text-gray-400">
      Brak wpisów w Karcie Czasu Pracy dla wybranego okresu.
    </p>

    <div class="mt-6 text-sm text-gray-500 space-y-1">
      <p><span class="font-medium text-gray-700">Roboczogodziny</span> — czas efektywny z wpisów bez statusu oraz ze statusów oznaczonych jako praca.</p>
      <p><span class="font-medium text-gray-700">Przerwy</span> — różnica między oknem zmiany a czasem efektywnym w dniach pracy.</p>
      <p v-if="statusyBezKategorii.length">
        <span class="font-medium text-gray-700">Inne</span> — statusy bez przypisanej kategorii:
        {{ statusyBezKategorii.join(', ') }}.
        Przypiszesz je w <Link class="text-indigo-600 hover:underline" href="/shiftStatusTyp">Ustawienia → Godziny Pracy</Link>.
      </p>
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
    budowy: { type: Array, default: () => [] },
    lata: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    statusyBezKategorii: { type: Array, default: () => [] },
  },
  data() {
    return { rok: this.filters.rok || 'wszystko' }
  },
  computed: {
    sumaPrzerw() {
      return this.budowy.reduce((s, b) => s + (b.przerwy || 0), 0)
    },
  },
  methods: {
    // Godziny pokazujemy dziesiętnie, tak jak liczy je KCP (9,5 = 9 h 30 min).
    godz(wartosc) {
      if (!wartosc) return '—'

      return Number(wartosc).toLocaleString('pl-PL', { minimumFractionDigits: 1, maximumFractionDigits: 1 })
    },
    suma(klucz) {
      return this.budowy.reduce((s, b) => s + ((b.godziny && b.godziny[klucz]) || 0), 0)
    },
    przeladuj() {
      this.$inertia.get('/statystyki', { rok: this.rok }, { preserveState: true, replace: true })
    },
  },
}
</script>
