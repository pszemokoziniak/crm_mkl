<template>
  <Head :title="organization.name" />
  <BudMenu :bud-id="budId" />
  <h1 class="mb-8 text-3xl font-bold">
    <Link class="text-indigo-400 hover:text-indigo-600" href="/budowy">{{ organization.name }}</Link>
    <span class="text-indigo-400 font-medium">/</span>
    <p class="text-base">Zarządzanie pracownikami</p>
  </h1>

  <!-- Tabs Navigation -->
  <div class="mb-6 border-b border-gray-200">
    <nav class="flex -mb-px space-x-8" aria-label="Tabs">
      <button :class="[activeTab === 'add' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm']" @click="activeTab = 'add'">Dodaj pracowników</button>
      <button :class="[activeTab === 'current' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm']" @click="activeTab = 'current'">Pracownicy na budowie</button>
    </nav>
  </div>

  <!-- Tab: Dodaj pracowników -->
  <div v-if="activeTab === 'add'">
    <div class="max-w bg-white rounded-md shadow overflow-hidden">
      <h3 class="p-4 text-xl font-medium">Znajdź wolnego pracownika</h3>
      <form @submit.prevent="find()">
        <div class="flex flex-wrap -mb-3 -mr-6 p-8">
          <date-input v-model="form.start" :error="form.errors.start" class="pb-8 pr-6 w-full lg:w-1/2" label="Początek pracy na budowie" />
          <date-input v-model="form.end" :error="form.errors.end" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec pracy na budowie" />
          <text-input v-model="form.contact_id" type="hidden" value="@{{contact_id}}" :error="form.errors.contact_id" />
        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Znajdź wolnych pracowników</loading-button>
        </div>
      </form>
    </div>
    <div v-if="contactsFree" class="max-w">
      <FreeContactsList :contacts-free="contactsFree" :organization="organization" :start="form.start" :end="form.end" :specialists="specialists" />
    </div>
  </div>

  <!-- Tab: Pracownicy na budowie -->
  <div v-if="activeTab === 'current'">
    <div class="max-w my-5 bg-white rounded-md shadow overflow-hidden">
      <h3 class="p-4 text-xl font-medium">Lista pracowników na budowie</h3>
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwisko</th>
          <th class="pb-4 pt-6 px-6">Daty</th>
          <th class="pb-4 pt-6 px-6">Stanowisko</th>
          <th class="pb-4 pt-6 px-6">Status</th>
        </tr>
        <tr v-for="contact in contacts" :key="contact.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${contact.contact_id}/edit`">
              {{ contact.last_name }} {{ contact.first_name }}
              <icon v-if="contact.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/contacts/${contact.contact_id}/edit`" tabindex="-1"> od: {{ contact.start }} do: {{ contact.end }} </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/contacts/${contact.contact_id}/edit`" tabindex="-1">
              {{ contact.name }}
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/contacts/${contact.contact_id}/edit`" tabindex="-1">
              {{ contact.status_zatrudnienia }}
            </Link>
          </td>
          <td class="w-px border-t">

          </td>
        </tr>
        <tr v-if="contacts === null || contacts.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Brak pracowników na budowie</td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'
import DateInput from '@/Shared/DateInput.vue'
import BudMenu from '@/Shared/BudMenu'
import FreeContactsList from '@/Pages/Pracownicy/FreeContactsList'

export default {
  components: {
    Head,
    FreeContactsList,
    Icon,
    LoadingButton,
    TextInput,
    Link,
    BudMenu,
    DateInput,
  },
  layout: Layout,
  props: {
    organization: Object,
    contacts: Object,
    contactsFree: null,
    specialists: Object,
  },
  remember: 'form',
  data() {
    return {
      activeTab: 'add',
      budId: this.organization.id,
      org: {
        org: this.organization,
      },
      checkedValues: [],
      form: this.$inertia.form({
        start: '',
        end: '',
      }),
      output: '',
    }
  },
  methods: {
    destroy(worker) {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/pracownicy/${worker}`)
      }
    },

    find() {
      this.$inertia.post(`/pracownicy/${this.organization.id}/create`, this.form, {
        onSuccess: () => {
          this.activeTab = 'add'
        },
      })
    },
  },
}
</script>
