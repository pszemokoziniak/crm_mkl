<template>
  <div>
    <Head title="Budowa" />
    <BudMenu :budId="organization_id" />
    <h1 class="mb-8 text-3xl font-bold">Budowa Pracownicy</h1>
    <div class="flex items-center justify-between mb-6">
      <Link class="btn-indigo" :href="`/pracownicy/${organization_id}/create`">
        <span>Dodaj / Usuń</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwisko Imię</th>
          <th class="pb-4 pt-6 px-6">Czas Pracy</th>
          <th class="pb-4 pt-6 px-6">Stanowisko</th>
        </tr>
        <tr v-for="item in contacts" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/pracownicy/${organization_id}/edit/${item.work_id}`">
              {{ item.last_name }} {{ item.first_name }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/pracownicy/${item.organization_id}/edit/${item.work_id}`">
              od: {{ item.start }}  do: {{ item.end }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/pracownicy/${item.organization_id}/edit/${item.work_id}`">
              {{ item.name }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>

          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/pracownicy/${item.id}/edit/${item.work_id}`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <tr v-if="contacts === null">
          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono pracownika</td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import BudMenu from '@/Shared/BudMenu.vue'


export default {
  components: {
    Head,
    Icon,
    Link,
    BudMenu,
  },
  layout: Layout,
  props: {
    contacts: Object,
    organization_id: Number,
    // contact_work_dates: Object,
  },
  data() {
    return {
      form: {
        // search: this.filters.search,
        // trashed: this.filters.trashed,
      },
    }
  },
}
</script>
