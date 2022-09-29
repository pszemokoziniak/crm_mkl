<template>
  <div>{{ something }}</div>
  <form @submit="onSubmit">
    <h2 class="mt-12 text-2xl font-bold">Pracownik</h2>
    <loading-button :loading="form.processing" class="btn-indigo ml-auto" @click='toggleSeen'>{{button.text}}</loading-button>

    <div class="mt-6 bg-white rounded shadow overflow-x-auto">
      <div v-show="toggle" class="m-5">
        <tr v-if="contactsFree.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Brak wolnych pracowników</td>
        </tr>
        <tr>
          <text-input v-model="form.start" type="date" class="pb-8 pr-6 w-full lg:w-1/1" label="Start" />
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
            v-model="checkedValues"
          />
        </label>
        <div v-if="contactsFree.length !== 0">
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Dodaj</loading-button>
        </div>
      </div>

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
            <Link class="flex items-center px-4" :href="`/contacts/${contact.id}/budowa/destroy`" tabindex="-1">
              <icon name="destroy" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <tr v-if="contacts.data.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono pracownika</td>
        </tr>
      </table>
    </div>
  </form>
</template>

<script>
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'

export default {
  components: {
    Icon,
    LoadingButton,
    TextInput,
  },
  inject: ['something'],
  // created() {
  //   console.log(this.message) // injected value
  // },
  layout: Layout,
  props: {
    organization: Object,
    krajTyps: Object,
    kierownikBud: Object,
    contacts: Object,
    contactsFree: Object,
    start: String,
  },
  remember: 'form',
  data() {
    return {
      // budId: this.organization.id,
      checkedValues: [],
      toggle: true,
      button: {
        text: 'Dodaj pracownika',
      },
      // start_test: {
      //   start: this.start
      // },

      form: this.$inertia.form({
        start: this.start,
      }),
    }
  },
  methods: {
    created() {
      console.log(this.form.start)
      this.$inertia.post(`/contacts/${this.organization.id}/addPracownik`, this.start_test)
    },
  },
  mounted: function() {
    console.log(this.something)
  },
}
</script>
