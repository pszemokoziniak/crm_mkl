<template>
  <div class="max-w bg-white rounded-md shadow overflow-hidden my-6 border border-gray-200">
    <div
      class="px-6 py-4 bg-gray-50 flex justify-between items-center cursor-pointer select-none"
      :class="{ 'border-b border-gray-100': open }"
      @click="open = !open"
    >
      <h3 class="font-bold text-lg text-gray-700">Dostępne narzędzia</h3>
      <span class="flex items-center gap-1 text-sm font-medium text-indigo-600">
        {{ open ? 'Ukryj' : 'Pokaż' }}
        <icon :name="open ? 'cheveron-down' : 'cheveron-right'" class="w-5 h-5 fill-current" />
      </span>
    </div>
    <div v-show="open">
      <div class="px-6 py-3 border-b border-gray-100">
        <input
          v-model="search"
          type="text"
          placeholder="Szukaj narzędzia..."
          class="form-input text-sm py-1.5 w-full sm:w-64"
        />
      </div>
      <form @submit.prevent="openConfirm">
      <div class="flex items-center justify-between px-6 py-3 bg-gray-50 border-b border-gray-100">
        <span class="text-sm text-gray-600">Wybrano: <span class="font-semibold text-gray-900">{{ form.checkedValues.length }}</span></span>
        <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj narzędzia na budowę</loading-button>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full whitespace-nowrap">
          <thead>
            <tr class="text-left font-bold bg-gray-50/50">
              <th class="py-3 px-6 text-xs uppercase tracking-wider text-gray-500">Nazwa</th>
              <th class="py-3 px-6 text-xs uppercase tracking-wider text-gray-500 text-center">W magazynie</th>
              <th class="py-3 px-6 text-xs uppercase tracking-wider text-gray-500 text-center w-32">Ilość</th>
              <th class="py-3 px-6" />
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="item in pagedTools" :key="item.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-2">
                <div class="flex items-center">
                  <input
                    :id="'tool-' + item.id"
                    v-model="form.checkedValues"
                    class="mr-3 h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                    type="checkbox"
                    :value="item.id"
                    @change="onToggle(item)"
                  />
                  <label :for="'tool-' + item.id" class="cursor-pointer font-medium text-gray-900">{{ item.name }}</label>
                </div>
              </td>
              <td class="px-6 py-2 text-center text-gray-600 text-sm">
                {{ item.ilosc_magazyn }}
              </td>
              <td class="px-6 py-2">
                <input
                  v-model="form.ilosc[item.id]"
                  type="number"
                  min="1"
                  :max="item.ilosc_magazyn"
                  class="form-input text-center w-20 mx-auto py-1 text-sm"
                  :placeholder="String(item.ilosc_magazyn)"
                  @input="clampQty(item)"
                />
              </td>
              <td class="px-6 py-2 text-right">
                <Link :href="`/narzedzia/${item.id}/edit`" class="text-gray-400 hover:text-indigo-600">
                  <icon name="cheveron-right" class="w-5 h-5 fill-current" />
                </Link>
              </td>
            </tr>
            <tr v-if="filteredTools.length === 0">
              <td class="px-6 py-8 text-center text-gray-500 text-sm" colspan="4">Nie znaleziono narzędzi</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="totalPages > 1" class="flex items-center justify-between px-6 py-3 border-t border-gray-100 text-sm">
        <button type="button" class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed" :disabled="page === 1" @click="page--">Poprzednia</button>
        <span class="text-gray-500">Strona {{ page }} z {{ totalPages }} · {{ filteredTools.length }} narzędzi</span>
        <button type="button" class="px-3 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed" :disabled="page === totalPages" @click="page++">Następna</button>
      </div>
      <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
        <loading-button :loading="form.processing" class="btn-indigo" type="submit">
          Dodaj narzędzia na budowę
        </loading-button>
      </div>
      </form>
    </div>

    <teleport to="body">
      <div v-if="showConfirm" class="fixed inset-0 z-[10000] flex items-center justify-center p-4" @keydown.esc.window="showConfirm = false">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showConfirm = false" />
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Potwierdź pobranie sprzętu</h3>
          </div>
          <div class="px-6 py-4 max-h-72 overflow-y-auto">
            <p class="text-sm text-gray-500 mb-3">Na budowę zostanie pobrany sprzęt:</p>
            <ul class="divide-y divide-gray-100">
              <li v-for="s in selected" :key="s.id" class="flex items-center justify-between py-2 text-sm">
                <span class="text-gray-800 pr-4">{{ s.name }}</span>
                <span class="font-semibold text-gray-900 whitespace-nowrap">× {{ s.qty }}</span>
              </li>
            </ul>
            <div class="flex items-center justify-between pt-3 mt-2 border-t border-gray-200 text-sm">
              <span class="font-medium text-gray-700">Razem</span>
              <span class="font-bold text-gray-900">{{ selectedTotal }} szt.</span>
            </div>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50" @click="showConfirm = false">Anuluj</button>
            <button type="button" class="btn-indigo" :disabled="form.processing" @click="confirmStore">Potwierdź</button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import LoadingButton from '@/Shared/LoadingButton'

export default {
  components: {
    Icon,
    LoadingButton,
    Link,
  },
  layout: Layout,
  props: {
    toolsFree: Array,
    organization: Object,
  },
  data() {
    return {
      open: true,
      showConfirm: false,
      search: '',
      page: 1,
      perPage: 20,
      form: this.$inertia.form({
        checkedValues: [],
        ilosc: {},
      }),
    }
  },
  computed: {
    selected() {
      return this.toolsFree
        .filter((t) => this.form.checkedValues.includes(t.id))
        .map((t) => ({
          id: t.id,
          name: t.name,
          qty: parseInt(this.form.ilosc[t.id], 10) || 1,
        }))
    },
    selectedTotal() {
      return this.selected.reduce((sum, s) => sum + s.qty, 0)
    },
    totalPages() {
      return Math.max(1, Math.ceil(this.filteredTools.length / this.perPage))
    },
    pagedTools() {
      const start = (this.page - 1) * this.perPage
      return this.filteredTools.slice(start, start + this.perPage)
    },
    filteredTools() {
      let tools = this.toolsFree
      if (!this.search) return tools
      const searchLower = this.search.toLowerCase()
      return tools.filter(item => item.name.toLowerCase().includes(searchLower))
    },
  },
  watch: {
    search() {
      this.page = 1
    },
    totalPages(val) {
      if (this.page > val) this.page = val
    },
  },
  methods: {
    onToggle(item) {
      // Zaznaczenie od razu ustawia ilość 1 (domyślnie), odznaczenie ją czyści.
      if (this.form.checkedValues.includes(item.id)) {
        if (!this.form.ilosc[item.id]) this.form.ilosc[item.id] = 1
      } else {
        delete this.form.ilosc[item.id]
      }
    },
    clampQty(item) {
      // Nie pozwalamy wpisać więcej, niż jest w magazynie.
      let v = parseInt(this.form.ilosc[item.id], 10)
      if (isNaN(v)) return
      if (v < 1) v = 1
      if (v > item.ilosc_magazyn) v = item.ilosc_magazyn
      this.form.ilosc[item.id] = v
    },
    openConfirm() {
      // Bez zaznaczenia nie ma czego potwierdzać.
      if (!this.form.checkedValues.length) return
      this.showConfirm = true
    },
    confirmStore() {
      this.showConfirm = false
      this.store()
    },
    store() {
      this.form.post(`/budowy/${this.organization.id}/narzedzia`)
    },
  },
}
</script>
