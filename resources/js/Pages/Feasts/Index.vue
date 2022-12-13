<template>
  <div>
    <Head title="Kraje" />
    <h1 class="mb-8 text-3xl font-bold">Święta</h1>
    <div class="flex items-center justify-between mb-6">
      <Link class="btn-indigo" :href="`/country/1/feasts/create`">
        <span>Dodaj</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwa</th>
          <th class="pb-4 pt-6 px-6">Data</th>
          <th class="pb-4 pt-6 px-6">Akcje</th>
        </tr>
        <tr v-for="feast in feasts" :key="feast.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/country/${feast.country_id}/feasts/${feast.id}`">
              {{ feast.name }}
            </Link>
          </td>
          <td class="border-t">{{ feast.date }}</td>
          <td class="border-t">
            <Link method='delete' class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/country/${feast.country_id}/feasts/${feast.id}/delete`">
              <icon name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'

export default {
  components: {
    Head,
    Icon,
    Link,
  },
  layout: Layout,
  props: {
    feasts: Object,
  },
}
</script>
