<template>
<!--  <Head :title="organization.name" />-->
  <BudMenu :budId="budId" />
  <h1 class="mb-8 text-3xl font-bold">
    <Link class="text-indigo-400 hover:text-indigo-600" href="/budowy">{{organization.name}}</Link>
    <span class="text-indigo-400 font-medium">/</span>
    <p class="text-base">Dodaj pracowników do budowy</p>
  </h1>

  <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
    <form @submit.prevent="find()">
      <div class="flex flex-wrap -mb-3 -mr-6 p-8">
        <text-input type="date" v-model="form.start" :error="form.errors.start" class="pb-8 pr-6 w-full lg:w-1/2" label="Początek pracy na budowie" />
        <text-input type="date" v-model="form.end" :error="form.errors.end" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec pracy na budowie" />
        <text-input type="hidden" value="@{{contact_id}}" v-model="form.contact_id" :error="form.errors.contact_id" />
      </div>
      <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
        <loading-button :loading="form.processing" class="btn-indigo" type="submit">Znajdź wolnych pracowników</loading-button>
      </div>
    </form>
  </div>

<!--  {{output}}-->

  <FreeContactsList :contactsFree="contactsFree" :data="org" :dates="form"/>

  <table class="w-full whitespace-nowrap">
    <tr class="text-left font-bold">
      <th class="pb-4 pt-6 px-6">Nazwisko Imię</th>
      <th class="pb-4 pt-6 px-6">Pozycja</th>
      <th class="pb-4 pt-6 px-6" colspan="2">Telefon</th>
    </tr>
    <tr v-for="contact in contacts.data" :key="contact.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
      <td class="border-t">
        <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/pracownicy/${organization.id}/destroy/${contact.id}`">
          {{ contact.first_name }} {{ contact.last_name }}
          <icon v-if="contact.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
        </Link>
      </td>
      <td class="border-t">
        <Link class="flex items-center px-6 py-4" :href="`/pracownicy/${organization.id}/destroy/${contact.id}`" tabindex="-1">
          {{ contact.funkcja.name }}
        </Link>
      </td>
      <td class="border-t">
        <Link class="flex items-center px-6 py-4" :href="`/pracownicy/${organization.id}/destroy/${contact.id}`" tabindex="-1">
          {{ contact.phone }}
        </Link>
      </td>
      <td class="w-px border-t">
        <Link class="flex items-center px-4" :href="`/pracownicy/${organization.id}/destroy/${contact.id}`" tabindex="-1">
          <icon name="destroy" class="block w-6 h-6 fill-gray-400" />
        </Link>
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
// import mapValues from 'lodash/mapValues'
import FreeContactsList from '@/Pages/Pracownicy/FreeContactsList'
import pickBy from 'lodash/pickBy'
import axios from 'axios'

export default {
  components: {
    FreeContactsList,
    Icon,
    LoadingButton,
    TextInput,
    Link,
    BudMenu,
  },
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
    // store() {
    //   console.log(this.checkedValues)
    //   this.form.post(`/pracownicy/${this.organization.id}`)
    //
    // },

    find() {
    //   let currentObj = this
    //   axios.post(`/api/pracownicy/${this.organization.id}/find`,{
    //     start:this.form.start,
    //     end:this.form.end,
    //   })
    //     .then(function(response){
    //       console.log(response.data)
    //       currentObj.output=response.data
    //     })
    //     .catch(function(error){
    //       currentObj.output=error
    //     })
    // },

      console.log(this.form.start);
      this.$inertia.post(`/pracownicy/${this.organization.id}/create`, this.form)

    },
    // toggleSeen: function() {
    //   this.toggle = !this.toggle;
    //   this.button.text = this.toggle ? 'Zamknij' : 'Dodaj pracownika';
    // },
  },
  mounted: function() {
    // console.log(this.start)
  },
}
</script>
