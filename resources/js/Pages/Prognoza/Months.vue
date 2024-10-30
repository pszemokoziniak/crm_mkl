<template>
  <div>

<!--    <div class="bg-white rounded-md shadow flex items-center justify-between p-2 mt-3">-->
<!--      <label class="block font-bold">Wybierz miesiąc</label>-->
<!--    </div>-->

    <div class="bg-white rounded-md shadow p-2 my-0">
      <div class="flex items-center justify-between w-full">
        <div class="bg-white rounded-md shadow flex flex-col p-3 w-full">
          <select-input v-model="selected" class="pr-6 w-full font-bold" label="Wybierz miesiąc" @change="handleChange">
            <option v-for="(item, index) in data" :key="index" :value="index">{{ item }}</option>
          </select-input>
        </div>
      </div>

      <!-- Medium screens -->
<!--      <div class="hidden sm:flex md:hidden items-center justify-between">-->
<!--        <div class="bg-white rounded-md shadow flex flex-col p-3 w-full">-->
<!--          <select-input v-model="selected" class="pr-6 w-full font-bold" label="Wybierz miesiąc" @change="handleSelect()">-->
<!--            <option v-for="(item, index) in data" :key="index">{{ item }}</option>-->
<!--          </select-input>-->
<!--        </div>-->
<!--      </div>-->

<!--      &lt;!&ndash; Large screens &ndash;&gt;-->
<!--      <div class="hidden md:flex lg:hidden items-center justify-between">-->
<!--        <Link v-for="(item, index) in data" :key="index" class="focus:text-indigo-500" label="Wybierz miesiąc" :href="buildHref(index)">-->
<!--          <button class="btn btn-indigo"> {{ item }} </button>-->
<!--        </Link>-->
<!--      </div>-->

<!--      &lt;!&ndash; Extra large screens &ndash;&gt;-->
<!--      <div class="hidden lg:flex items-center justify-between">-->
<!--        <Link v-for="(item, index) in data" :key="index" class="focus:text-indigo-500" label="Wybierz miesiąc" :href="buildHref(index)">-->
<!--          <button class="btn btn-indigo"> {{ item }} </button>-->
<!--        </Link>-->
<!--      </div>-->
    </div>
  </div>
</template>

<script>
import SelectInput from '@/Shared/SelectInput.vue'

export default {
  components: {
    SelectInput,
  },
  props: {
    data: Array,
    month: Number,
  },
  data() {
    return {
      selected: this.month ?? null,
    }
  },
  watch: {
    selected(newSelected) {
      this.updateUrl(newSelected)
    },
  },
  methods: {
    updateUrl(selectedValue) {
      const params = new URLSearchParams(window.location.search)
      params.set('month', selectedValue)

      this.$inertia.visit(`${window.location.pathname}?${params.toString()}`, {
        method: 'get',
        preserveState: false,
        replace: true,
      })
    },
  },
}
</script>
