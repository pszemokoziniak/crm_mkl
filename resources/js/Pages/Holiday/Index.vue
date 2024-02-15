<template>
  <div>
    <Head title="Urlopy" />
    <div>
      <WorkerMenu :contactId="contactId" />
    </div>
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/contacts">Urlopy</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ contact.first_name }} {{ contact.last_name }}
    </h1>
    <div class="flex items-center justify-between mb-6">
      <Link class="btn-indigo" :href="`/contacts/${contact.id}/holiday/create`">
        <span>Dodaj</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Początek urlopu</th>
          <th class="pb-4 pt-6 px-6">Koniec urlopu</th>
        </tr>
        <tr v-for="item in holiday" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${contact.id}/holiday/${item.id}/edit`">
              {{ item.start }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${contact.id}/holiday/${item.id}/edit`">
              {{ item.end }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/contacts/${contact.id}/holiday/${item.id}/edit`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <tr v-if="holiday.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono</td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import WorkerMenu from '@/Shared/WorkerMenu'

export default {
  components: {
    Head,
    Icon,
    Link,
    WorkerMenu,
  },
  layout: Layout,
  props: {
    holiday: Object,
    contact: Object,
  },

  data() {
    return {
      contactId: this.contact.id,
    }
  },
}
</script>
