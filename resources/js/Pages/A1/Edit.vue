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
    <trashed-message v-if="a1.deleted_at" class="mb-6" @restore="restore"> Ten element będzię usunięty</trashed-message>
    <h1 class="mb-8 text-2xl font-bold">A1</h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input type="date" v-model="form.start" :error="form.errors.start" class="pb-8 pr-6 w-full lg:w-1/2" label="Start" />
          <text-input type="date" v-model="form.end" :error="form.errors.end" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec" />
          <select-input v-model="form.kraj_typs_id" :error="form.errors.kraj_typs_id" class="pb-8 pr-6 w-full lg:w-1/2" label="Kraj">
            <option v-for="item in countries" :key="item.id" :value="item.id">{{ item.name }}</option>
          </select-input>
        </div>
        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button v-if="!a1.deleted_at" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
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
import TrashedMessage from '@/Shared/TrashedMessage'
import WorkerMenu from '@/Shared/WorkerMenu'
import SelectInput from '@/Shared/SelectInput.vue'

export default {
  components: {
    SelectInput,
    Head,
    Link,
    LoadingButton,
    TextInput,
    TrashedMessage,
    WorkerMenu,
  },
  layout: Layout,
  props: {
    contact: Object,
    a1: Object,
    countries: Object,
  },
  remember: 'form',
  data() {
    return {
      contactId: this.contact.id,
      form: this.$inertia.form({
        id: this.a1.id,
        start: this.a1.start,
        end: this.a1.end,
        kraj_typs_id: this.a1.kraj_typs_id,
      }),
    }
  },
  methods: {
    update() {
      this.form.put(`/contacts/${this.contact.id}/a1/${this.form.id}`)
    },
    destroy() {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/a1/${this.a1.id}`)
      }
    },
    // restore() {
    //   if (confirm('Chcesz przywrócić?')) {
    //     this.$inertia.put(`/contacts/${this.contact.id}/restore`)
    //   }
    // },
  },
}
</script>
