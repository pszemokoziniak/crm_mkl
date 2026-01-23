<template>
  <div>
    <Head title="Kraje" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/krajTyp">Święta</Link>
      <span class="text-indigo-400 font-medium">/</span> {{ country.name }}
    </h1>
    <div class="flex items-center justify-between mb-6">
      <Link class="btn-indigo" :href="`/country/${countryId}/feasts/create`">
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
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/country/${feast.country_id}/feasts/${feast.id}`">
              {{ feast.date }}
            </Link>
          </td>
          <td class="border-t">
            <Link method="delete" class="flex items-center px-6 py-4 text-red-600 hover:text-red-900 focus:text-indigo-500" :href="`/country/${feast.country_id}/feasts/${feast.id}/delete`">
              Usuń
            </Link>
          </td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'

export default {
  components: {
    Head,
    Link,
  },
  layout: Layout,
  props: {
    feasts: Object,
    countryId: Number,
    country: Object,
  },
}
</script>
