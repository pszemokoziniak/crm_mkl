<template>
  <div>
    <Head title="Kraje"/>
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/krajTyp">Święto</Link>
      <span class="text-indigo-400 font-medium">/</span> Dodaj
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-3/4" label="Nazwa"/>
          <Datepicker :format="format" v-model="form.date" class="pb-8 pr-6 w-full lg:w-3/4" label="Dzień"/>
        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Zapisz</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import {Head, Link} from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'
import Datepicker from "@vuepic/vue-datepicker"
import '@vuepic/vue-datepicker/dist/main.css'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    TextInput,
    Datepicker,
  },
  layout: Layout,
  remember: 'form',
  props: {

  },
  data() {
    return {
      form: this.$inertia.form({
        name: '',
        date: null,
      }),
    }
  },
  methods: {
    store() {
      this.form.post(`/country/1/feasts`)
    },
    format(date) {
      const day = date.getDate()
      const month = date.getMonth() + 1

      return `${day}/${month}`
    }
  },
}
</script>
