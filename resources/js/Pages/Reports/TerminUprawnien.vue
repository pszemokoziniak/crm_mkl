<template>
  <div>
    <RaportMenu />
    <Head title="Termin Uprawnień" />
    <h1 class="mb-8 text-3xl font-bold">Termin uprawnień</h1>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
      <a v-for="(count, type) in summary" :key="type" :href="'#' + slugify(type)" class="bg-white p-4 rounded shadow hover:bg-gray-50 transition">
        <h3 class="font-bold text-gray-700">{{ type }}</h3>
        <p class="text-2xl font-bold mt-2" :class="count > 0 ? 'text-red-500' : 'text-green-600'">{{ count }}</p>
        <p class="text-sm text-gray-500">upływa &lt; 30 dni</p>
      </a>
    </div>

    <div class="flex items-center justify-between mb-6">
      <search-filter-no-filtr v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset" />
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwisko Imię</th>
          <th class="pb-4 pt-6 px-6">Nazwa</th>
          <th class="pb-4 pt-6 px-6">Początek</th>
          <th class="pb-4 pt-6 px-6" colspan="2">Koniec</th>
        </tr>
        <template v-for="(items, type) in groupedData" :key="type">
          <tr :id="slugify(type)" class="bg-gray-100">
            <td colspan="5" class="px-6 py-2 font-bold text-indigo-900">{{ type }}</td>
          </tr>
          <tr v-for="(item, index) in items" :key="index" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <td :class="[checkDays(item.end) ? 'text-red-500' : '', 'border-t']">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${item.client_id}/edit`">
                {{ item.last_name }} {{ item.first_name }}
                <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td :class="[checkDays(item.end) ? 'text-red-500' : '', 'border-t']">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${item.client_id}/edit`">
                {{ item.name }}
                <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td :class="[checkDays(item.end) ? 'text-red-500' : '', 'border-t']">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${item.client_id}/edit`">
                {{ item.start }}
                <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td :class="[checkDays(item.end) ? 'text-red-500' : '', 'border-t']">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${item.client_id}/edit`">
                {{ item.end }}
                <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td class="w-px border-t">
              <Link class="flex items-center px-4" :href="`/contacts/${item.client_id}/edit`" tabindex="-1">
                <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
              </Link>
            </td>
          </tr>
        </template>
        <tr v-if="data.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono elementów</td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import RaportMenu from '@/Shared/RaportMenu'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import groupBy from 'lodash/groupBy'
import SearchFilterNoFiltr from '@/Shared/SearchFilterNoFiltr.vue'

export default {
  components: {
    SearchFilterNoFiltr,
    Head,
    Icon,
    Link,
    RaportMenu,
  },
  layout: Layout,
  props: {
    bhps: Object,
    data: Object,
    filters: Object,
    userOwner: Number,
  },
  data() {
    return {
      form: {
        search: this.filters.search,
        trashed: this.filters.trashed,
      },
    }
  },
  computed: {
    groupedData() {
      return groupBy(this.data, 'type')
    },
    summary() {
      const s = {}
      for (const type in this.groupedData) {
        s[type] = this.groupedData[type].filter(item => this.checkDays(item.end)).length
      }
      return s
    },
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/reports/koniecUprawinien', pickBy(this.form), { preserveState: true })
      }, 150),
    },
  },
  methods: {
    checkDays(end_date){
      var dni = Math.round(( new Date(end_date).getTime() - new Date().getTime() ) / (1000*3600*24))
      return dni < 30
    },
    slugify(text) {
      return text.toString().toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w\\-]+/g, '')
        .replace(/\\-\\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '')
    },
    reset() {
      this.form = mapValues(this.form, () => null)
    },
  },
}
</script>
