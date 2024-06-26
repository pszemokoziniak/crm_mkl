<template>
  <div>
    <Head title="Prognoza" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/prognoza">Dodaj liczbę pracowników</Link>
      <span class="text-indigo-400 font-medium">/</span> Dodaj
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.building" :error="form.errors.building" :value="building[0].id" class="pb-8 pr-6 w-full lg:w-3/4" label="Nazwa budowa" disabled/>
          <text-input v-show="false" v-model="form.building_id" :error="form.errors.building_id" class="pb-8 pr-6 w-full lg:w-3/4" hidden/>
          <select-input v-model="form.dates" :error="form.errors.dates" class="pb-8 pr-6 w-full lg:w-3/4" label="Wybierz daty">
            <option value="0" disabled>wybierz</option>
            <option v-for="item in dates" :key="item.id" :value="item.id">{{ item.start }} - {{ item.end }}</option>
          </select-input>
          <text-input v-model="form.workers_count" :error="form.errors.workers_count" class="pb-8 pr-6 w-full lg:w-3/4" label="Ilość pracowników"/>
        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj godziny</loading-button>
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
import SelectInput from '@/Shared/SelectInput.vue'

export default {
  components: {
    SelectInput,
    Head,
    Link,
    LoadingButton,
    TextInput,
  },
  layout: Layout,
  props: {
    dates: Array,
    building: Array,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        dates: '',
        building: this.building[0].nazwaBud,
        workers_count: '',
        building_id: this.building[0].id,
      }),
    }
  },
  methods: {
    store() {
      this.form.post('/prognoza')
    },
  },
}
</script>
