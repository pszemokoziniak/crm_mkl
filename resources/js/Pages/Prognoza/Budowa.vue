<template>
  <div>
    <Head title="Prognoza pracowników" />
    <BudMenu :bud-id="build" />
    <budowa-naglowek :bud-id="buildDetails.id" :nazwa="buildDetails.nazwaBud" tytul="Prognoza" />
    <h2 class="mb-8 text-xl font-semibold text-gray-700">Prognoza pracowników — zapotrzebowanie tygodniowe</h2>

    <div v-if="rows.length" class="mb-8 bg-white rounded-md shadow p-4">
      <ChartComponent :key="chartKey" :chartData="chartData" :chartMax="chartMax" />
    </div>

    <!-- Dodawanie tygodnia (tylko biuro/admin) -->
    <div v-if="!flag" class="mb-8 bg-white rounded-md shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-700">Dodaj zapotrzebowanie na tydzień</div>
      <form class="flex flex-wrap items-end gap-4 p-6 pb-12" @submit.prevent="add">
        <div class="w-full sm:w-40">
          <label class="form-label">Rok:</label>
          <select v-model="pick.year" class="form-select mt-1 w-full" @change="reloadWeeks">
            <option :value="null">—</option>
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <div class="w-full sm:w-48">
          <label class="form-label">Miesiąc:</label>
          <select v-model="pick.month" class="form-select mt-1 w-full" @change="reloadWeeks">
            <option :value="null">—</option>
            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
          </select>
        </div>
        <div class="relative w-full sm:w-64">
          <label class="form-label">Tydzień:</label>
          <select v-model="form.prognoza_dates_id" class="form-select mt-1 w-full">
            <option value="">—</option>
            <option v-for="w in freeWeeks" :key="w.id" :value="w.id">{{ w.start }} – {{ w.end }}</option>
          </select>
          <div v-if="form.errors.prognoza_dates_id" class="absolute left-0 top-full mt-1 w-full form-error">{{ form.errors.prognoza_dates_id }}</div>
          <p v-else-if="pick.year && pick.month && !freeWeeks.length" class="absolute left-0 top-full mt-1 w-full text-xs text-yellow-800">
            Wszystkie tygodnie tego miesiąca mają już wpisane zapotrzebowanie.
          </p>
        </div>
        <div class="relative w-full sm:w-40">
          <label class="form-label">Liczba osób:</label>
          <input v-model="form.workers_count" type="number" min="1" class="form-input mt-1 w-full" />
          <div v-if="form.errors.workers_count" class="absolute left-0 top-full mt-1 w-full form-error">{{ form.errors.workers_count }}</div>
        </div>
        <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj</loading-button>
      </form>
    </div>

    <!-- Tabela tydzień po tygodniu -->
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap text-sm">
        <thead>
          <tr class="text-left font-bold border-b">
            <th class="pb-4 pt-6 px-6">Tydzień</th>
            <th class="pb-4 pt-6 px-6">Zapotrzebowanie</th>
            <th class="pb-4 pt-6 px-6">Obsadzeni</th>
            <th class="pb-4 pt-6 px-6">Różnica</th>
            <th v-if="!flag" class="pb-4 pt-6 px-6" />
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.id" class="hover:bg-gray-50">
            <td class="border-t px-6 py-3 font-medium">{{ row.start }} – {{ row.end }}</td>
            <td class="border-t px-6 py-3">
              <template v-if="!flag">
                <input
                  v-model.number="edit[row.id]"
                  type="number"
                  min="1"
                  class="form-input w-24 py-1"
                />
              </template>
              <span v-else>{{ row.workers_count }}</span>
            </td>
            <td class="border-t px-6 py-3">{{ row.assigned }}</td>
            <td class="border-t px-6 py-3">
              <span
                class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded"
                :class="diffClass(row)"
              >
                {{ diffLabel(row) }}
              </span>
            </td>
            <td v-if="!flag" class="border-t px-6 py-3">
              <div class="flex items-center gap-3">
                <button
                  v-if="edit[row.id] !== row.workers_count && edit[row.id] > 0"
                  type="button"
                  class="text-indigo-600 hover:text-indigo-800 font-medium"
                  @click="save(row)"
                >
                  Zapisz
                </button>
                <button
                  type="button"
                  class="text-red-600 hover:text-red-800 font-medium"
                  @click="remove(row)"
                >
                  Usuń
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td class="px-6 py-4 border-t text-gray-500" :colspan="flag ? 4 : 5">
              Brak wpisanego zapotrzebowania dla tej budowy.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import BudowaNaglowek from '@/Shared/BudowaNaglowek'
import Layout from '@/Shared/Layout'
import BudMenu from '@/Shared/BudMenu.vue'
import LoadingButton from '@/Shared/LoadingButton'
import ChartComponent from '@/Pages/Prognoza/ChartComponent.vue'

export default {
  components: {
    BudowaNaglowek,
    Head,
    Link,
    BudMenu,
    LoadingButton,
    ChartComponent,
  },
  layout: Layout,
  props: {
    build: Number,
    buildDetails: Object,
    rows: Array,
    chartData: Object,
    years: Array,
    months: Array,
    freeWeeks: Array,
    filters: Object,
    flag: Boolean,
  },
  data() {
    return {
      pick: {
        year: this.filters.year ? Number(this.filters.year) : null,
        month: this.filters.month ? Number(this.filters.month) : null,
      },
      edit: this.buildEdit(),
      form: this.$inertia.form({
        prognoza_dates_id: '',
        workers_count: '',
      }),
    }
  },
  computed: {
    chartMax() {
      const vals = this.rows.flatMap((r) => [r.workers_count, r.assigned])
      const top = Math.max(5, ...vals)
      return Math.ceil((top * 1.15) / 5) * 5
    },
    chartKey() {
      // Wymusza przemontowanie wykresu po zmianie danych (Chart.js rysuje w mounted).
      return this.rows.map((r) => `${r.id}:${r.workers_count}:${r.assigned}`).join('|')
    },
  },
  watch: {
    rows() {
      this.edit = this.buildEdit()
    },
  },
  methods: {
    buildEdit() {
      const map = {}
      ;(this.rows || []).forEach((r) => { map[r.id] = r.workers_count })
      return map
    },
    diff(row) {
      return row.assigned - row.workers_count
    },
    diffLabel(row) {
      const d = this.diff(row)
      if (d === 0) return 'komplet'
      return d > 0 ? `+${d}` : `${d}`
    },
    diffClass(row) {
      const d = this.diff(row)
      if (d < 0) return 'bg-red-100 text-red-800'
      if (d > 0) return 'bg-yellow-100 text-yellow-800'
      return 'bg-green-100 text-green-800'
    },
    reloadWeeks() {
      this.$inertia.get(
        `/budowy/${this.build}/prognoza`,
        { year: this.pick.year, month: this.pick.month },
        { preserveState: true, preserveScroll: true, replace: true },
      )
    },
    add() {
      this.form.post(`/budowy/${this.build}/prognoza`, {
        preserveScroll: true,
        onSuccess: () => this.form.reset(),
      })
    },
    save(row) {
      this.$inertia.put(
        `/budowy/${this.build}/prognoza/${row.id}`,
        { workers_count: this.edit[row.id] },
        { preserveScroll: true },
      )
    },
    remove(row) {
      if (!confirm('Usunąć zapotrzebowanie na ten tydzień?')) return
      this.$inertia.delete(`/budowy/${this.build}/prognoza/${row.id}`, { preserveScroll: true })
    },
  },
}
</script>
