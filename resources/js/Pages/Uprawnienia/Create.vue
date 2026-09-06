<template>
  <div>
    <Head title="Dodaj uprawnienia" />
    <worker-menu :contact-id="contact_id" />
    <pracownik-naglowek :contact-id="pracownik ? pracownik.id : contact_id" :nazwa="pracownik ? pracownik.nazwa : ''" tytul="Uprawnienia — dodaj" />
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store(contact_id)">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <select-input v-model="form.uprawnieniaTyp_id" :error="form.errors.uprawnieniaTyp_id" class="pb-8 pr-6 w-full lg:w-1/1" label="Nazwa">
            <option v-for="item in uprawnieniaTyps" :key="item.id" :value="item.id">{{ item.name }}</option>
          </select-input>
          <text-input type="date" v-model="form.start" :error="form.errors.start" class="pb-8 pr-6 w-full lg:w-1/2" label="Start badań" />
          <text-input type="date" v-model="form.end" :error="form.errors.end" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec badań" />
          <text-input type="hidden" value="@{{contact_id}}" v-model="form.contact_id" :error="form.errors.contact_id" />
        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj uprawnienia</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import PracownikNaglowek from '@/Shared/PracownikNaglowek'
import WorkerMenu from '@/Shared/WorkerMenu'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'
import SelectInput from '@/Shared/SelectInput'

export default {
  components: {
    PracownikNaglowek,
    WorkerMenu,
    Head,
    Link,
    LoadingButton,
    TextInput,
    SelectInput,
  },
  layout: Layout,
  props: {
    pracownik: { type: Object, default: null },
    contact_id: Number,
    uprawnieniaTyps: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        uprawnieniaTyp_id: '',
        start: '',
        end: '',
        contact_id: '',
      }),
    }
  },
  methods: {
    store(contact_id) {
      // console.log(contact_id)
      this.form.post('/uprawnienia/'+contact_id)
    },
  },
}
</script>
