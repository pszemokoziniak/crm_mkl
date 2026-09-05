<template>
<!--  <Head :title="form.name" />-->
  <BudMenu :budId="budId" />
  <form @submit.prevent="store">
    <budowa-naglowek :bud-id="organization.id" :nazwa="organization.nazwaBud" tytul="Usuń pracownika z budowy" />
    <div class="mt-6 bg-white rounded shadow overflow-x-auto">
      <div class="rounded overflow-hidden shadow-lg">
        <div class="px-6 py-4 w-100">
          <div class="font-bold text-xl mb-2">Dane pracownika</div>
          <p class="text-gray-700 text-base">
            Nazwa: {{ contact.last_name }} {{ contact.first_name }}
          </p>
<!--          <hr>-->
<!--          <p class="text-gray-700 text-base mt-3">-->
<!--            Nazwisko: {{ contact.last_name }}-->
<!--          </p>-->
          <hr>
          <p class="text-gray-700 text-base mt-3">
            Stanowisko: {{ contact.funkcja.name }}
          </p>
          <hr>
          <p class="text-gray-700 text-base mt-3">
            Początek pracy na budowie: {{ dates.start }}
          </p>
        </div>
<!--        <div v-show="dates" class="px-6 py-4 w-80">-->
<!--          Budowa: {{ dates.organization.name }}-->
<!--          Początek pracy na budowie: {{ dates.start }}-->
<!--        </div>-->
      </div>
      <div class="m-5">
        <tr>
          <text-input v-model="form.end" :error="form.errors.end" type="date" class="pb-8 pr-6 w-full lg:w-1/1" label="Koniec pracy na budowie" />
        </tr>
        <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Wypisz pracownika z budowy</loading-button>
      </div>
    </div>
  </form>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'
import BudowaNaglowek from '@/Shared/BudowaNaglowek'

import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'
import BudMenu from '@/Shared/BudMenu'

export default {
  components: {
    BudowaNaglowek,
    LoadingButton,
    TextInput,
    Link,
    BudMenu,
  },
  layout: Layout,
  props: {
    organization: Object,
    contact: Object,
    dates: Object,
  },
  remember: 'form',
  data() {
    return {
      budId: this.organization.id,

      form: this.$inertia.form({
        end: null,
        id: this.dates.id,
        contact_id: this.contact.id,
        organization_id: this.organization.id,
      }),
    }
  },
  methods: {
    store() {
      this.form.put(`/pracownicy/destroystore`)
    },
  },
}
</script>
