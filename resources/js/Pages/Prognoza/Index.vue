<template>
  <div>
    <Head title="Prognoza" />
    <h1 class="mb-8 text-3xl font-bold">Prognoza pracowników na budowach</h1>

    <Years :data="years" />

    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Data</th>
          <th class="pb-4 pt-6 px-6 col-2">Ilość pracowników</th>
        </tr>
        <tr v-for="item in data" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-4" :href="`/prognoza/${item.id}/edit`" tabindex="-1">
              {{ item.start }} - {{ item.end }}
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-4" :href="`/prognoza/${item.id}/edit`" tabindex="-1">
              {{ item.workers_count }}
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/prognoza/${item.id}/edit`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
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
import Years from '@/Pages/Prognoza/Years'

export default {
  components: {
    Head,
    Years,
    Icon,
    Link,
  },
  layout: Layout,
  props: {
    years: Array,
    data: Array,
  },
}
</script>
