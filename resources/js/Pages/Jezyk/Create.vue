<template>
  <div>
    <Head title="Dodaj język" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="#">Język</Link>
      <span class="text-indigo-400 font-medium">/</span> Dodaj
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store(contact_id)">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <select-input v-model="form.jezykTyp_id" :error="form.errors.jezykTyp_id" class="pb-8 pr-6 w-full lg:w-1/1" label="Nazwa">
            <option v-for="item in jezykTyps" :key="item.id" :value="item.id">{{ item.name }}</option>
          </select-input>
          <select-input v-model="form.poziom" :error="form.errors.poziom" class="pb-8 pr-6 w-full lg:w-1/1" label="Poziom">
            <option value="poczatkujący">poczatkujący</option>
            <option value="komunikatywny">komunikatywny</option>
            <option value="dobry">dobry</option>
            <option value="bardzo dobry">bardzo dobry</option>
            <option value="płynny">płynny</option>
          </select-input>
          <text-input type="hidden" value="@{{contact_id}}" v-model="form.contact_id" :error="form.errors.contact_id" />
        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj język</loading-button>
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
import SelectInput from '@/Shared/SelectInput'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    TextInput,
    SelectInput,
  },
  layout: Layout,
  props: {
    contact_id: Number,
    jezykTyps: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        jezykTyp_id: '',
        poziom: '',
        contact_id: '',
      }),
    }
  },
  methods: {
    store(contact_id) {
      // console.log(contact_id)
      this.form.post('/jezyk/'+contact_id)
    },
  },
}
</script>
