<template>
  <div>
    <Head title="Raporty" />
    <h1 class="mb-8 text-3xl font-bold">Raporty</h1>
  </div>
  {{bhp}}
</template>

<script>
import { Head } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'

export default {
  components: {
    Head,
  },
  layout: Layout,
  props: {
    bhp: Object
  },
  data() {
    return {

    }
  }
}
</script>
