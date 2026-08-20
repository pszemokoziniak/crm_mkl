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
      <form @submit.prevent="store()">
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
            <tr v-for="item in filteredTools" :key="item.id" class="hover:bg-gray-50 transition-colors">
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
      <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
        <loading-button :loading="form.processing" class="btn-indigo" type="submit">
          Dodaj narzędzia na budowę
        </loading-button>
      </div>
      </form>
    </div>
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
      open: false,
      search: '',
      form: this.$inertia.form({
        checkedValues: [],
        ilosc: {},
      }),
    }
  },
  computed: {
    filteredTools() {
      let tools = this.toolsFree
      if (!this.search) return tools
      const searchLower = this.search.toLowerCase()
      return tools.filter(item => item.name.toLowerCase().includes(searchLower))
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
    store() {
      this.form.post(`/budowy/${this.organization.id}/narzedzia`)
    },
  },
}
</script>
