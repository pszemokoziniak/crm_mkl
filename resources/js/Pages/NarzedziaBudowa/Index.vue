<template>
  <div>
    <Head title="Narzędzia" />
    <BudMenu :bud-id="organization.id" />
    <budowa-naglowek :bud-id="organization.id" :nazwa="organization.nazwaBud" tytul="Sprzęt na budowie" />
    <div class="flex items-center justify-between mb-6">
      <search-filter-no-filtr v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset" />
      <Link v-if="!$page.props.permissions.kierownik" class="btn-indigo" :href="`/budowy/${organization.id}/narzedzia/create`">
        <span>Dodaj sprzęt</span>
      </Link>
    </div>
    <div class="hidden md:block bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead>
          <tr class="naglowek-tabeli">
            <th>Nazwa sprzętu / Szczegóły</th>
            <th class="text-center">Sztuk</th>
            <th>Badania techniczne</th>
            <th>Komentarz</th>
            <th class="text-right">Akcje</th>
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
              <td class="px-6 py-3">
                <span v-if="doSprawdzenia(group) > 0" class="text-xs font-semibold text-red-700">
                  {{ doSprawdzenia(group) }} do sprawdzenia
                </span>
              </td>
              <td class="px-6 py-3" />
              <td class="px-6 py-3" />
            </tr>
            <!-- Wiersze szczegółowe (poszczególne egzemplarze) -->
            <tr v-for="item in group.items" :key="item.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-12 py-3 text-sm">
                <span class="text-gray-500">S/N:</span>
                <span class="font-medium text-gray-800">{{ item.numer_seryjny && item.numer_seryjny !== '-' ? item.numer_seryjny : '—' }}</span>
                <span class="ml-4 text-gray-500">na budowie:</span>
                <span class="text-gray-700">{{ item.od || '—' }}<span v-if="item.do"> – {{ item.do }}</span></span>
              </td>
              <td class="px-6 py-3 text-center text-sm text-gray-500">1 szt.</td>
              <td class="px-6 py-3 text-sm">
                <!-- Termin badań rzuca się w oczy, gdy minął albo mija. -->
                <span :class="klasaBadan(item.badania_status)">
                  {{ item.waznosc_badan || 'brak daty' }}
                </span>
                <span v-if="item.badania_status === 'po_terminie'" class="ml-1 text-xs font-semibold text-red-700">po terminie</span>
                <span v-else-if="item.badania_status === 'wkrotce'" class="ml-1 text-xs font-semibold text-orange-700">kończy się</span>
              </td>
              <!-- Notatka z wydania. Bez nowrap, bo bywa dłuższa niż kolumna. -->
              <td class="px-6 py-3 text-sm text-gray-600 whitespace-normal max-w-xs">
                {{ item.komentarz || '—' }}
              </td>
              <td class="px-6 py-3 text-right whitespace-nowrap">
                <Link
                  v-if="!$page.props.permissions.kierownik"
                  :href="`/budowy/${organization.id}/narzedzia/${item.id}/edit`"
                  class="text-indigo-600 hover:underline"
                >
                  Popraw daty
                </Link>
                <button
                  v-if="!$page.props.permissions.kierownik"
                  type="button"
                  class="ml-3 text-red-600 hover:underline"
                  @click="usun(item.id)"
                >
                  Usuń
                </button>
                <span v-if="$page.props.permissions.kierownik" class="text-gray-400">—</span>
              </td>
            </tr>
          </template>
          <tr v-if="groupedTools.length === 0">
            <td class="px-6 py-12 text-center text-gray-500" colspan="5">
              <div class="flex flex-col items-center">
                <icon name="office" class="w-12 h-12 fill-gray-200 mb-2" />
                <p>Brak sprzętu przypisanego do tej budowy</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Wąski ekran: karty. Grupa sprzętu jako nagłówek, pod nią egzemplarze. -->
    <div class="space-y-4 md:hidden">
      <div v-for="group in groupedTools" :key="group.name" class="bg-white rounded-md shadow overflow-hidden">
        <div class="flex items-center justify-between gap-2 px-4 py-3 bg-gray-50 border-b">
          <div class="font-bold text-gray-900">{{ group.name }}</div>
          <span class="flex-shrink-0 inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-indigo-600 text-white">
            {{ group.total_qty }}
          </span>
        </div>
        <p v-if="doSprawdzenia(group) > 0" class="px-4 pt-2 text-xs font-semibold text-red-700">
          {{ doSprawdzenia(group) }} do sprawdzenia
        </p>
        <div v-for="item in group.items" :key="item.id" class="px-4 py-3 border-t border-gray-100 text-sm">
          <div>
            <span class="text-gray-500">S/N: </span>
            <span class="font-medium text-gray-800">{{ item.numer_seryjny && item.numer_seryjny !== '-' ? item.numer_seryjny : '—' }}</span>
          </div>
          <div class="mt-0.5 text-gray-600">
            na budowie: {{ item.od || '—' }}<span v-if="item.do"> – {{ item.do }}</span>
          </div>
          <div class="mt-0.5">
            <span class="text-gray-500">badania: </span>
            <span :class="klasaBadan(item.badania_status)">{{ item.waznosc_badan || 'brak daty' }}</span>
            <span v-if="item.badania_status === 'po_terminie'" class="ml-1 text-xs font-semibold text-red-700">po terminie</span>
            <span v-else-if="item.badania_status === 'wkrotce'" class="ml-1 text-xs font-semibold text-orange-700">kończy się</span>
          </div>
          <div v-if="!$page.props.permissions.kierownik" class="mt-2">
            <Link :href="`/budowy/${organization.id}/narzedzia/${item.id}/edit`" class="text-indigo-600">Popraw daty</Link>
            <button type="button" class="ml-4 text-red-600" @click="usun(item.id)">Usuń</button>
          </div>
        </div>
      </div>
      <p v-if="groupedTools.length === 0" class="bg-white rounded-md shadow p-4 text-sm text-gray-500">
        Brak sprzętu przypisanego do tej budowy.
      </p>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import BudowaNaglowek from '@/Shared/BudowaNaglowek'
import Icon from '@/Shared/Icon.vue'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout.vue'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import BudMenu from '@/Shared/BudMenu.vue'
import SearchFilterNoFiltr from '@/Shared/SearchFilterNoFiltr.vue'


export default {
  components: {
    BudowaNaglowek,
    BudMenu,
    Head,
    Icon,
    Link,
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
    doSprawdzenia(group) {
      return group.items.filter((i) => ['po_terminie', 'wkrotce'].includes(i.badania_status)).length
    },
    klasaBadan(status) {
      if (status === 'po_terminie') return 'text-red-700 font-semibold'
      if (status === 'wkrotce') return 'text-orange-700 font-semibold'
      if (status === 'brak') return 'text-gray-400'
      return 'text-gray-700'
    },
    usun(id) {
      if (confirm('Zdjąć ten sprzęt z budowy?')) {
        this.$inertia.delete(`/budowy/${this.organization.id}/narzedzia/${id}/destroy`)
      }
    },
    reset() {
      this.form = mapValues(this.form, () => null)
    },
  },
}
</script>
