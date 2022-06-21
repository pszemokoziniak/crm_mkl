<template>
  <div>
    <Head title="Narzędzia" />
    <h1 class="mb-8 text-3xl font-bold">Ustawiania</h1>
    <h3 class="mb-8">Dodaj</h3>

    <Link class="btn-indigo mr-5" :href="`/position`">
        <span>Pozycja</span>
    </Link>
    <Link class="btn-indigo mr-5" :href="`/funkcja`">
        <span>Funkcja</span>
    </Link>
    <Link class="btn-indigo mr-5" :href="`/badaniaTyp`">
      <span>BadaniaBHP</span>
    </Link>
 </div>

</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
// import Icon from '@/Shared/Icon'

// import {  } from '@inertiajs/inertia-vue3'

import Layout from '@/Shared/Layout'

export default {
  components: {
    Head,
    Link,
    // Icon,
  },
  layout: Layout,

}
</script>
