<template>
  <div>
    <div v-if="$page.props.permissions.kierownik" class="mb-4">
      <Link class="group flex items-center py-3" href="/">
        <icon name="dashboard" class="mr-2 w-4 h-4" :class="isUrl('') ? 'fill-white' : 'fill-indigo-400 group-hover:fill-white'" />
        <div :class="isUrl('') ? 'text-white' : 'text-indigo-300 group-hover:text-white'">Dashboard</div>
      </Link>
    </div>
    <div v-if="$page.props.permissions.admin || $page.props.permissions.biuro" class="mb-4">
      <Link class="group flex items-center py-3" href="/budowy">
        <icon name="office" class="mr-2 w-4 h-4" :class="isUrl('budowy') ? 'fill-white' : 'fill-indigo-400 group-hover:fill-white'" />
        <div :class="isUrl('budowy') ? 'text-white' : 'text-indigo-300 group-hover:text-white'">Budowy</div>
      </Link>
    </div>
    <div v-if="$page.props.permissions.admin || $page.props.permissions.biuro" class="mb-4">
      <Link class="group flex items-center py-3" href="/narzedzia">
        <icon name="sprzet2" class="mr-2 w-4 h-4" :class="isUrl('narzedzia') ? 'fill-white' : 'fill-indigo-400 group-hover:fill-white'" />
        <div :class="isUrl('narzedzia') ? 'text-white' : 'text-indigo-300 group-hover:text-white'">Sprzęt</div>
      </Link>
    </div>
    <div v-if="$page.props.permissions.admin || $page.props.permissions.biuro" class="mb-4">
      <Link class="group flex items-center py-3" href="/contacts">
        <icon name="users" class="mr-2 w-4 h-4" :class="isUrl('contacts') ? 'fill-white' : 'fill-indigo-400 group-hover:fill-white'" />
        <div :class="isUrl('contacts') ? 'text-white' : 'text-indigo-300 group-hover:text-white'">Pracownicy</div>
      </Link>
    </div>
    <div v-if="$page.props.permissions.admin || $page.props.permissions.biuro" class="mb-4">
      <Link class="group flex items-center py-3" href="/reports/koniecUprawinien">
        <icon name="eligibility" class="mr-2 w-4 h-4" :class="isUrl('reports') ? 'fill-white' : 'fill-indigo-400 group-hover:fill-white'" />
        <div :class="isUrl('reports') ? 'text-white' : 'text-indigo-300 group-hover:text-white'">Termin uprawnień</div>
      </Link>
    </div>
    <div v-if="$page.props.permissions.admin" class="mb-4">
      <Link class="group flex items-center py-3" href="/tools">
        <icon name="tools" class="mr-2 w-4 h-4" :class="isUrl('tools') ? 'fill-white' : 'fill-indigo-400 group-hover:fill-white'" />
        <div :class="isUrl('tools') ? 'text-white' : 'text-indigo-300 group-hover:text-white'">Ustawienia</div>
      </Link>
    </div>
    <div v-if="$page.props.permissions.admin || $page.props.permissions.biuro" class="mb-4">
      <Link class="group flex items-center py-3" href="/prognoza">
        <icon name="forecast-workers" class="mr-2 w-4 h-4" :class="isUrl('prognoza') ? 'fill-white' : 'fill-indigo-400 group-hover:fill-white'" />
        <div :class="isUrl('prognoza') ? 'text-white' : 'text-indigo-300 group-hover:text-white'">Prognoza pracowników</div>
      </Link>
    </div>
    <div v-if="$page.props.permissions.admin || $page.props.permissions.biuro" class="mb-4">
      <Link class="group flex items-center py-3" href="/building/time-sheet/month-report">
        <icon name="monthlyReport" class="mr-2 w-4 h-4" :class="isUrl('month-report') ? 'fill-white' : 'fill-indigo-400 group-hover:fill-white'" />
        <div :class="isUrl('month-report') ? 'text-white' : 'text-indigo-300 group-hover:text-white'">Raport miesięczny</div>
      </Link>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'

export default {
  components: {
    Icon,
    Link,
  },
  props: {
    auth: Object,
  },
  methods: {
    isUrl(...urls) {
      let currentUrl = this.$page.url.substr(1)
      if (urls[0] === '') {
        return currentUrl === ''
      }
      return urls.filter((url) => currentUrl.startsWith(url)).length
    },
  },
}
</script>
