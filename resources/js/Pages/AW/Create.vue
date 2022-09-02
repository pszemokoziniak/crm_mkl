<template>
  <div>
    <Head title="Dodaj A1" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/a1">A1</Link>
      <span class="text-indigo-400 font-medium">/</span> Dodaj
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store(contact_id)">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input type="date" v-model="form.start" :error="form.errors.start" class="pb-8 pr-6 w-full lg:w-1/2" label="Start badań" />
          <text-input type="date" v-model="form.end" :error="form.errors.end" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec badań" />
          <text-input type="hidden" value="@{{contact_id}}" v-model="form.contact_id" :error="form.errors.contact_id" />
        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj A1</loading-button>
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

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    TextInput,
  },
  layout: Layout,
  props: {
    contact_id: Number,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        start: '',
        end: '',
        contact_id: '',
      }),
    }
  },
  methods: {
    store(contact_id) {
      // console.log(contact_id)
      this.form.post('/a1/'+contact_id)
    },
  },
}
</script>
