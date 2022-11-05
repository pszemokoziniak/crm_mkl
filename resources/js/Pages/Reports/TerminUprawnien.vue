<template>
  <div>
    <Head title="Termin Uprawnień" />
    <h1 class="mb-8 text-3xl font-bold">Termin uprawnień</h1>
    <RaportMenu/>
    <div class="flex items-center justify-between mb-6">
<!--      <Link class="btn-indigo" href="/contacts/create">-->
<!--        <span>Dodaj</span>-->
<!--        <span class="hidden md:inline">&nbsp;Pracownika</span>-->
<!--      </Link>-->
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwisko Imię</th>
          <th class="pb-4 pt-6 px-6">Nazwa</th>
          <th class="pb-4 pt-6 px-6">Start</th>
          <th class="pb-4 pt-6 px-6"  colspan="2">End</th>
<!--          <th class="pb-4 pt-6 px-6" colspan="2">Data Ważności A1</th>-->
        </tr>
        <tr v-for="item in data" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
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

export default {
  components: {
    Head,
    Icon,
    Link,
    RaportMenu,
  },
  layout: Layout,
  props: {
    bhps: Object,
    data: Object,
  },
  data() {
    return {
      form: {
        // search: this.filters.search,
        // trashed: this.filters.trashed,
      },
    }
  },
  // watch: {
  //   form: {
  //     deep: true,
  //     handler: throttle(function () {
  //       this.$inertia.get('/contacts', pickBy(this.form), { preserveState: true })
  //     }, 150),
  //   },
  // },
  methods: {
    checkDays(end_date){
      var dni = Math.round(( new Date(end_date).getTime() - new Date().getTime() ) / (1000*3600*24));
      return dni < 7
    }
    // reset() {
    //   this.form = mapValues(this.form, () => null)
    // },
  },
}
</script>
