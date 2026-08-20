<template>
  <div>
    <Head title="Narzędzia" />
    <BudMenu :bud-id="organization.id" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/budowy">{{ organization.name }}</Link>
      <span class="text-indigo-400 font-medium"> / </span>
      Narzędzia na budowie
    </h1>
    <div class="flex items-center justify-between mb-6">
      <search-filter-no-filtr v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset" />
      <Link v-if="!$page.props.permissions.kierownik" class="btn-indigo" :href="`/budowy/${organization.id}/narzedzia/create`">
        <span>Dodaj sprzęt</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-hidden">
      <table class="w-full whitespace-nowrap">
        <thead>
          <tr class="text-left font-bold bg-gray-50 border-b border-gray-100">
            <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500">Nazwa sprzętu / Szczegóły</th>
            <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500 text-center">Łączna ilość</th>
            <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500 text-right">Akcje</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <template v-for="group in groupedTools" :key="group.name">
            <!-- Wiersz nagłówkowy grupy -->
            <tr class="bg-gray-50/50">
              <td class="px-6 py-3 font-bold text-gray-900">
                <div class="flex items-center">
                  <icon name="office" class="w-4 h-4 mr-2 fill-gray-400" />
                  {{ group.name }}
                </div>
              </td>
              <td class="px-6 py-3 text-center">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-indigo-600 text-white">
                  {{ group.total_qty }}
                </span>
              </td>
              <td class="px-6 py-3" />
            </tr>
            <!-- Wiersze szczegółowe (poszczególne egzemplarze) -->
            <tr v-for="item in group.items" :key="item.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-12 py-3">
                <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-sm">
                  <span class="text-gray-500">
                    S/N:
                    <span class="font-medium text-gray-800">{{ item.numer_seryjny && item.numer_seryjny !== '-' ? item.numer_seryjny : '—' }}</span>
                  </span>
                  <span class="inline-flex items-center gap-1.5 text-gray-500">
                    Badania:
                    <span v-if="!item.waznosc_badan" class="text-gray-400">brak daty</span>
                    <span
                      v-else
                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                      :class="isExpired(item.waznosc_badan) ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                    >
                      {{ item.waznosc_badan }}
                      <span v-if="isExpired(item.waznosc_badan)" class="ml-1">· po terminie</span>
                    </span>
                  </span>
                </div>
              </td>
              <td class="px-6 py-3 text-center text-sm text-gray-500">
                ilość: {{ item.narzedzia_nb }}
              </td>
              <td class="px-6 py-3 text-right">
                <div class="flex items-center justify-end space-x-3">
                  <Link :href="$page.props.permissions.kierownik ? '' : `/budowy/${organization.id}/narzedzia/${item.id}/edit`" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                    Edytuj
                  </Link>
                  <delete-button
                    :href="`/budowy/${organization.id}/narzedzia/${item.id}/destroy`"
                    confirm="Czy na pewno chcesz usunąć to narzędzie z budowy?"
                  />
                </div>
              </td>
            </tr>
          </template>
          <tr v-if="groupedTools.length === 0">
            <td class="px-6 py-12 text-center text-gray-500" colspan="3">
              <div class="flex flex-col items-center">
                <icon name="office" class="w-12 h-12 fill-gray-200 mb-2" />
                <p>Brak sprzętu przypisanego do tej budowy</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon.vue'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout.vue'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import BudMenu from '@/Shared/BudMenu.vue'
import DeleteButton from '@/Shared/DeleteButton.vue'
import SearchFilterNoFiltr from '@/Shared/SearchFilterNoFiltr.vue'


export default {
  components: {
    BudMenu,
    Head,
    Icon,
    Link,
    DeleteButton,
    SearchFilterNoFiltr,
  },
  layout: Layout,
  props: {
    groupedTools: Array,
    organization: Object,
    filters: Object,
  },
  data() {
    return {
      form: {
        search: this.filters.search,
      },
    }
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get(`/budowy/${this.organization.id}/narzedzia`, pickBy(this.form), { preserveState: true })
      }, 150),
    },
  },
  methods: {
    reset() {
      this.form = mapValues(this.form, () => null)
    },
    isExpired(date) {
      if (!date) return false
      return new Date(date) < new Date()
    },
  },
}
</script>
