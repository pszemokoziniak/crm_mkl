<template>
  <div>
    <Head title="Narzędzia" />
    <h1 class="mb-8 text-3xl font-bold">Ustawiania</h1>
    <!-- <h3>Pracownik</h3> -->
  </div>
    <Link class="btn-indigo" :href="`/position`">
        <span>Pozycja</span>
    </Link>


</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'

// import {  } from '@inertiajs/inertia-vue3'

import Layout from '@/Shared/Layout'

export default {
  components: {
    Head,
    Link,
    Icon,
  },
  layout: Layout,

}
</script>
