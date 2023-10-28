<template>
  <div>
    <Head title="Nieobecnosci w pracy" />
    <h1 class="mb-8 text-3xl font-bold">Typ nieobecności w pracy</h1>
    <div class="flex items-center justify-between mb-6">
      <Link class="btn-indigo" href="/shiftStatusTyp/create">
        <span>Dodaj</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwa</th>
          <th class="pb-4 pt-6 px-6">Typ</th>
        </tr>
        <tr v-for="item in ShiftStatusTypes" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/shiftStatusTyp/${item.id}/edit`">
              {{ item.title }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/shiftStatusTyp/${item.id}/edit`">
              {{ item.code }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/shiftStatusTyp/${item.id}/edit`" tabindex="-1">
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


export default {
  components: {
    Head,
    Icon,
    Link,
  },
  layout: Layout,
  props: {
    ShiftStatusTypes: Object,
  },
  data() {
    return {
      form: {},
    }
  },
  methods: {
  },
}
</script>
