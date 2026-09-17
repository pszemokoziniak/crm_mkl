<template>
  <div>
    <Head title="Nieobecności" />
    <div>
      <WorkerMenu :contactId="contactId" :userOwner="userOwner"/>
      <pracownik-naglowek :pracownik="pracownik" tytul="Nieobecności" />
    </div>
    <h1 class="mb-2 text-2xl font-bold">Nieobecności</h1>
    <p class="mb-6 text-sm text-gray-500">Urlopy, zwolnienia i inne powody, dla których pracownika nie ma na budowie.</p>
    <!-- Kierownik ogląda; nieobecności wstawiają kadry (on zgłasza). -->
    <div v-if="!prowadziBudowy(userOwner)" class="flex items-center justify-between mb-6">
      <Link class="btn-indigo" :href="`/contacts/${contact.id}/holiday/create`">
        <span>Dodaj</span>
      </Link>
    </div>
    <!-- Telefon: karty; kierownik tylko ogląda, więc bez linku do edycji. -->
    <div class="sm:hidden bg-white rounded-md shadow divide-y divide-gray-100">
      <component
        :is="prowadziBudowy(userOwner) ? 'div' : 'Link'"
        v-for="item in holiday"
        :key="`k-${item.id}`"
        :href="prowadziBudowy(userOwner) ? undefined : `/contacts/${contact.id}/holiday/${item.id}/edit`"
        class="flex items-center justify-between gap-3 px-4 py-3"
      >
        <div>
          <span class="inline-flex px-2.5 py-0.5 text-xs font-medium text-yellow-800 bg-yellow-100 border border-yellow-200 rounded-full">{{ item.powod || 'nie podano' }}</span>
          <div class="mt-1 text-sm text-gray-700 tabular-nums">{{ item.start }} – {{ item.end }}<icon v-if="item.deleted_at" name="trash" class="inline ml-2 w-3 h-3 fill-gray-400" /></div>
        </div>
        <icon v-if="!prowadziBudowy(userOwner)" name="cheveron-right" class="w-6 h-6 fill-gray-400" />
      </component>
      <p v-if="holiday.length === 0" class="px-4 py-4 text-sm text-gray-500">Brak wpisanych nieobecności</p>
    </div>

    <div class="hidden sm:block bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="naglowek-tabeli">
          <th>Powód</th>
          <th>Od</th>
          <th>Do</th>
        </tr>
        <tr v-for="item in holiday" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="prowadziBudowy(userOwner) ? '' : `/contacts/${contact.id}/holiday/${item.id}/edit`">
              <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-yellow-800 bg-yellow-100 border border-yellow-200 rounded-full">
                {{ item.powod || 'nie podano' }}
              </span>
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="prowadziBudowy(userOwner) ? '' : `/contacts/${contact.id}/holiday/${item.id}/edit`">
              {{ item.start }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="prowadziBudowy(userOwner) ? '' : `/contacts/${contact.id}/holiday/${item.id}/edit`">
              {{ item.end }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="prowadziBudowy(userOwner) ? '' : `/contacts/${contact.id}/holiday/${item.id}/edit`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <tr v-if="holiday.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Brak wpisanych nieobecności</td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import { prowadziBudowy } from '@/role'
import Icon from '@/Shared/Icon'
import PracownikNaglowek from '@/Shared/PracownikNaglowek'
import Layout from '@/Shared/Layout'
import WorkerMenu from '@/Shared/WorkerMenu'

export default {
  components: {
    Head,
    Icon,
    Link,
    PracownikNaglowek,
    WorkerMenu,
  },
  layout: Layout,
  props: {
    pracownik: { type: Object, default: null },
    holiday: Object,
    contact: Object,
    userOwner: Number,
  },

  data() {
    return {
      contactId: this.contact.id,
    }
  },
  methods: {
    prowadziBudowy,
  },
}
</script>
