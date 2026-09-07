<template>
  <div>
    <!-- Było tu logo z pustym <span> po szablonie startowym (ta fala nad
         zakładkami) i przycisk "hamburger" bez żadnej obsługi kliknięcia.
         Menu i tak zawija się samo, więc jedno i drugie poszło. -->
    <nav class="flex items-center justify-between flex-wrap pb-4">
      <div class="w-full block flex-grow lg:flex lg:items-center lg:w-auto">
        <div class="text-md lg:flex-grow">
          <Link class="block mt-4 md:inline-block lg:mt-3 mr-4" :href="`/contacts/${contactId}/edit`">
            <div :class="isUrl('edit') ? 'text-green-500' : 'text-indigo-300 hover:text-green-500'">Dane osobowe</div>
          </Link>
          <Link class="block mt-4 md:inline-block lg:mt-3 mr-4" :href="`/contacts/${contactId}/badania`">
            <div :class="isUrl('badania') ? 'text-green-500' : 'text-indigo-300 hover:text-green-500'">Badania lekarskie</div>
          </Link>
          <Link class="block mt-4 md:inline-block lg:mt-3 mr-4" :href="`/contacts/${contactId}/bhp`">
            <div :class="isUrl('bhp') ? 'text-green-500' : 'text-indigo-300 hover:text-green-500'">Szkolenia BHP</div>
          </Link>
          <Link class="block mt-4 md:inline-block lg:mt-3 mr-4" :href="`/contacts/${contactId}/pbioz`">
            <div :class="isUrl('pbioz') ? 'text-green-500' : 'text-indigo-300 hover:text-green-500'">PBiOZ</div>
          </Link>
          <Link class="block mt-4 md:inline-block lg:mt-3 mr-4" :href="`/contacts/${contactId}/uprawnienia`">
            <div :class="isUrl('uprawnienia') ? 'text-green-500' : 'text-indigo-300 hover:text-green-500'">Uprawnienia</div>
          </Link>
          <!-- Kierownik ogląda i pobiera dokumenty swoich ludzi — potrzebuje
               ich przy kontroli i przy wejściu inwestora na budowę. -->
          <Link class="block mt-4 md:inline-block lg:mt-3 mr-4" :href="`/contacts/${contactId}/documents`">
            <div :class="isUrl('documents') ? 'text-green-500' : 'text-indigo-300 hover:text-green-500'">Dokumenty</div>
          </Link>
          <Link class="block mt-4 md:inline-block lg:mt-3 mr-4" :href="`/contacts/${contactId}/jezyk`">
            <div :class="isUrl('jezyk') ? 'text-green-500' : 'text-indigo-300 hover:text-green-500'">Języki</div>
          </Link>
          <Link class="block mt-4 md:inline-block lg:mt-3 mr-4" :href="`/contacts/${contactId}/a1`">
            <div :class="isUrl('a1') ? 'text-green-500' : 'text-indigo-300 hover:text-green-500'">A1</div>
          </Link>
          <Link class="block mt-4 md:inline-block lg:mt-3 mr-4" :href="`/contacts/${contactId}/holiday`">
            <div :class="isUrl('holiday') ? 'text-green-500' : 'text-indigo-300 hover:text-green-500'">Nieobecności</div>
          </Link>
          <Link class="block mt-4 md:inline-block lg:mt-3 mr-4" :href="`/contacts/${contactId}/history`">
            <div :class="isUrl('history') ? 'text-green-500' : 'text-indigo-300 hover:text-green-500'">Historia</div>
          </Link>
          <Link v-if="rola !== 3" class="block mt-4 md:inline-block lg:mt-3 mr-4" :href="`/contacts/${contactId}/umowa`">
            <div :class="isUrl('umowa') ? 'text-green-500' : 'text-indigo-300 hover:text-green-500'">Umowa</div>
          </Link>
        </div>
      </div>
    </nav>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'

export default {
  name: 'WorkerMenu',
  components: {
    Link,
  },
  props: {
    contactId:Number,
    uprawnienia: Object,
    userOwner:Number,
  },
  computed: {
    // Rola bywa podana wprost, a bywa, że strona jej nie przekazuje —
    // wtedy bierzemy ją z danych zalogowanego użytkownika, żeby kierownik
    // nie zobaczył pozycji, do których i tak nie ma dostępu.
    rola() {
      return this.userOwner ?? this.$page.props.auth?.user?.owner
    },
  },
  methods: {
    isUrl(...urls) {
      let currentUrl = this.$page.url.substr(1)
      if (urls[0] === '') {
        return currentUrl === ''
      }
      return urls.filter((url) => currentUrl.endsWith(url)).length
    },
  },
}
</script>
