<template>
  <div>
    <Head :title="`${contact.first_name} ${contact.last_name} - Historia`" />
    <div class="mb-8">
      <WorkerMenu :contact-id="contact.id" />
    </div>
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/contacts">Pracownicy</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ contact.first_name }} {{ contact.last_name }}
      <span class="text-indigo-400 font-medium">/</span>
      Historia
    </h1>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Budowa</th>
          <th class="pb-4 pt-6 px-6">Od</th>
          <th class="pb-4 pt-6 px-6">Do</th>
          <th class="pb-4 pt-6 px-6">Suma godzin</th>
        </tr>
        <tr v-for="item in history" :key="item.organization" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t px-6 py-4">
            {{ item.organization }}
          </td>
          <td class="border-t px-6 py-4">
            {{ item.start }}
          </td>
          <td class="border-t px-6 py-4">
            {{ item.end }}
          </td>
          <td class="border-t px-6 py-4">
            {{ item.hours.toFixed(2) }}
          </td>
        </tr>
        <tr v-if="history.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Brak historii pracy na budowach.</td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import WorkerMenu from '@/Shared/WorkerMenu'

export default {
  components: {
    Head,
    Link,
    WorkerMenu,
  },
  layout: Layout,
  props: {
    contact: Object,
    history: Array,
  },
}
</script>
