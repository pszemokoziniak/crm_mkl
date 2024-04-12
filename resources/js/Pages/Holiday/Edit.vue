<template>
  <div>
    <Head :title="`${form.first_name} ${form.last_name}`" />
    <div>
      <WorkerMenu :contactId="contactId" />
    </div>
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/contacts">Pracownik</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ contact.first_name }} {{ contact.last_name }}
    </h1>
    <h1 class="mb-8 text-2xl font-bold">Dodaj urlop</h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.start" :error="form.errors.start" type="date" class="pb-8 pr-6 w-full lg:w-1/2" label="Start urlopu" />
          <text-input v-model="form.end" :error="form.errors.end" type="date" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec urlopu" />
        </div>
        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button v-if="!holiday.deleted_at" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
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
import LoadingButton from '@/Shared/LoadingButton'
import WorkerMenu from '@/Shared/WorkerMenu'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    TextInput,
    WorkerMenu,
  },
  layout: Layout,
  props: {
    contact: Object,
    holiday: Object,
  },
  remember: 'form',
  data() {
    return {
      contactId: this.contact.id,
      form: this.$inertia.form({
        id: this.holiday.id,
        start: this.holiday.start,
        end: this.holiday.end,
      }),
    }
  },
  methods: {
    update() {
      this.form.put(`/contacts/${this.contact.id}/holiday/${this.form.id}`)
    },
    destroy() {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/holiday/${this.form.id}`)
      }
    },
  },
}
</script>
