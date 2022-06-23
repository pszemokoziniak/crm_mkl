<template>
  <div>
    <Head :title="`${form.first_name} ${form.last_name}`" />
    <div>
      <WorkerMenu :contactId="contactId" />
    </div>
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/contacts">Pracownik</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.first_name }} {{ form.last_name }}
    </h1>
    <trashed-message v-if="badania.deleted_at" class="mb-6" @restore="restore"> Ten pracownik będzię usunięty</trashed-message>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
<!--          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-3/4" label="Nazwa" />-->
          <select-input v-model="form.badaniaTyp_id" :error="form.errors.badaniaTyp_id" class="pb-8 pr-6 w-full lg:w-1/1" label="Nazwa">
            <option v-for="badania in badanias" :key="badania.id" :value="badania.id">{{ badania.id }}</option>
          </select-input>
          <text-input type="date" v-model="form.start" :error="form.errors.start" class="pb-8 pr-6 w-full lg:w-1/2" label="Start badań" />
          <text-input type="date" v-model="form.end" :error="form.errors.end" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec badań" />
        </div>


        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button v-if="!badania.deleted_at" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Popraw</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import SelectInput from '@/Shared/SelectInput'
import LoadingButton from '@/Shared/LoadingButton'
import TrashedMessage from '@/Shared/TrashedMessage'
import WorkerMenu from '@/Shared/WorkerMenu'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
    TrashedMessage,
    WorkerMenu,
  },
  layout: Layout,
  props: {
    contact: Object,
    organizations: Array,
    badanias: Object,
    accounts: Object,
    badanie: Array,
  },
  remember: 'form',
  data() {
    return {
      contactId: this.contact.id,
      form: this.$inertia.form({
        id: this.badania.id,
        badaniaTyp_id: this.badania.badaniaTyp_id,
        start: this.badania.start,
        end: this.badania.end,
      }),
    }
  },
  methods: {
    update() {
      this.form.put(`/contacts/${this.contact.id}`)
    },
    destroy() {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/contacts/${this.contact.id}`)
      }
    },
    restore() {
      if (confirm('Chcesz przywrócić?')) {
        this.$inertia.put(`/contacts/${this.contact.id}/restore`)
      }
    },
  },
}
</script>
