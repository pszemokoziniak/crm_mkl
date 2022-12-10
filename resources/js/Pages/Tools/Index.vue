<template>
  <div>
    <Head title="Narzędzia" />
    <h1 class="mb-8 text-3xl font-bold">Ustawiania</h1>
    <h3 class="mb-8">Dodaj</h3>
    <div class="grid grid-cols-4 gap-4 md:grid-cols-3 sm:grid-cols-2 xs:grid-cols-2">
      <Link class="btn-indigo mr-5" :href="`/funkcja`">
        <span>Stanowisko</span>
      </Link>
      <Link class="btn-indigo mr-5" :href="`/badaniaTyp`">
        <span>Badania Lekarskie</span>
      </Link>
      <Link class="btn-indigo mr-5" :href="`/bhpTyp`">
        <span>Szkolenia BHP</span>
      </Link>
      <Link class="btn-indigo mr-5" :href="`/uprawnieniaTyp`">
        <span>Uprawnienia</span>
      </Link>
      <Link class="btn-indigo mr-5" :href="`/jezykTyp`">
        <span>Języki</span>
      </Link>
      <Link class="btn-indigo mr-5" :href="`/krajTyp`">
        <span>Kraj</span>
      </Link>
      <Link class="btn-indigo mr-5" :href="`/dokumentyTyp`">
        <span>Dokumenty</span>
      </Link>
    </div>
 </div>

</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
// import Icon from '@/Shared/Icon'

import {  } from '@inertiajs/inertia-vue3'

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
