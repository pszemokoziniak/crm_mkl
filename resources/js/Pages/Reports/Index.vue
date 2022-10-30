<template>
  <div>
    <Head title="Raporty" />
    <h1 class="mb-8 text-3xl font-bold">Raporty</h1>
  </div>
  <RaportMenu/>
</template>

<script>
import { Head } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import RaportMenu from '@/Shared/RaportMenu'

export default {
  components: {
    RaportMenu,
    Head,
  },
  layout: Layout,
  props: {

  },
  data() {
    return {

    }
  }
}
</script>
