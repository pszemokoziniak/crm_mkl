<template>
<!--  <Head :title="form.name" />-->
  <BudMenu :budId="budId" />
  <form @submit.prevent="store">
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/budowy">{{organization.name}}</Link>
      <span class="text-indigo-400 font-medium">/</span>
      <p class="text-base">Dodaj pracowników do budowy</p>
    </h1>
    <div class="mt-6 bg-white rounded shadow overflow-x-auto">
      <div class="m-5">
        <tr v-if="contactsFree.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Brak wolnych pracowników</td>
        </tr>
        <tr>
          <text-input v-model="form.start" :error="form.errors.start" type="date" class="pb-8 pr-6 w-full lg:w-1/1" label="Start" />
        </tr>
        <label
          v-for="(item, index) in contactsFree"
          :key="index"
          class="m-3"
        >
          {{ item.first_name }} {{ item.last_name }}
          <input
            type="checkbox"
            :value="item.id"
            v-model="form.checkedValues"
            :error="form.errors.checkedValues"
          />
        </label>
        <div v-if="contactsFree.length !== 0">
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Dodaj</loading-button>
        </div>
      </div>
    </div>
  </form>

  <table class="w-full whitespace-nowrap">
    <tr class="text-left font-bold">
      <th class="pb-4 pt-6 px-6">Nazwisko Imię</th>
      <th class="pb-4 pt-6 px-6">Pozycja</th>
      <th class="pb-4 pt-6 px-6" colspan="2">Telefon</th>
    </tr>
    <tr v-for="contact in contacts.data" :key="contact.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
      <td class="border-t">
        <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${contact.id}/edit`">
          {{ contact.first_name }} {{ contact.last_name }}
          <icon v-if="contact.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
        </Link>
      </td>
      <td class="border-t">
        <Link class="flex items-center px-6 py-4" :href="`/contacts/${contact.id}/edit`" tabindex="-1">
          {{ contact.funkcja.name }}
        </Link>
      </td>
      <td class="border-t">
        <Link class="flex items-center px-6 py-4" :href="`/contacts/${contact.id}/edit`" tabindex="-1">
          {{ contact.phone }}
        </Link>
      </td>
      <td class="w-px border-t">
        <Link class="flex items-center px-4" :href="`/pracownicy/${organization.id}/destroy/${contact.id}`" tabindex="-1">
          <icon name="destroy" class="block w-6 h-6 fill-gray-400" />
        </Link>
<!--        <loading-button :loading="form.processing" class="btn-indigo ml-auto" @click='toggleSeen'>{{button.text}}</loading-button>-->
<!--        <button class="block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" data-modal-toggle="authentication-modal">-->
<!--          Toggle modal-->
<!--        </button>-->
      </td>
    </tr>
    <tr v-if="contacts.data.length === 0">
      <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono pracownika</td>
    </tr>
  </table>

</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'

import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'
import BudMenu from '@/Shared/BudMenu'

export default {
  components: {
    Icon,
    LoadingButton,
    TextInput,
    Link,
    BudMenu,
  },
  // created() {
  //   console.log(this.message) // injected value
  // },
  layout: Layout,
  props: {
    organization: Object,
    contacts: Object,
    contactsFree: Object,
  },
  remember: 'form',
  data() {
    return {
      budId: this.organization.id,
      // checkedValues: [],

      // toggle: true,
      button: {
        text: 'Usuń',
      },
      // start_test: {
      //   start: this.start
      // },

      form: this.$inertia.form({
        start: null,
        checkedValues: [],
      }),
    }
  },
  methods: {
    store() {
      console.log(this.form.checkedValues)
      this.form.post(`/pracownicy/${this.organization.id}`)

    },
    toggleSeen: function() {
      this.toggle = !this.toggle;
      this.button.text = this.toggle ? 'Zamknij' : 'Dodaj pracownika';
    },
  },
  mounted: function() {
    console.log(this.start)
  },
}
</script>
