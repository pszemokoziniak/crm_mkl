<template>
  <div>
    <Head :title="`${contact.first_name} ${contact.last_name} - Historia`" />
    <div class="mb-8">
      <WorkerMenu :contact-id="contact.id" />
      <pracownik-naglowek :pracownik="pracownik" tytul="Historia" />
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="naglowek-tabeli">
          <th>Budowa</th>
          <th>Od</th>
          <th>Do</th>
          <th>Suma godzin</th>
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
import PracownikNaglowek from '@/Shared/PracownikNaglowek'
import Layout from '@/Shared/Layout'
import WorkerMenu from '@/Shared/WorkerMenu'

export default {
  components: {
    Head,
    Link,
    PracownikNaglowek,
    WorkerMenu,
  },
  layout: Layout,
  props: {
    pracownik: { type: Object, default: null },
    contact: Object,
    history: Array,
  },
}
</script>
