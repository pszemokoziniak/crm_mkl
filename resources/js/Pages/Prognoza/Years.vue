<template>
  <div>
<!--    <div class="bg-white rounded-md shadow flex items-center justify-between p-2 mt-3">-->
<!--      <label class="block font-bold">Wybierz rok</label>-->
<!--    </div>-->
    <div class="bg-white rounded-md shadow p-2 my-0">
      <div class="flex items-center justify-between w-full">
        <div class="bg-white rounded-md shadow flex flex-col p-3 w-full">
          <select-input v-model="selectedYear" class="pr-6 w-full font-bold" label="Wybierz rok">
            <option :value="null">Wszystko</option>
            <option v-for="item in data" :key="item" :value="item">{{ item }}</option>
          </select-input>
        </div>
      </div>
    </div>
<!--    <div class="bg-white rounded-md shadow flex items-center justify-between p-3">-->
<!--      <Link-->
<!--        class="focus:text-indigo-500"-->
<!--      >-->
<!--        <button class="btn btn-indigo" @click="handleSelect()"> Wszystkie lata </button>-->
<!--      </Link>-->
<!--      <Link-->
<!--        v-for="item in data"-->
<!--        :key="item"-->
<!--        class="focus:text-indigo-500"-->
<!--        :href="buildHref(item)"-->
<!--      >-->
<!--        <button class="btn btn-indigo"> {{ item }} </button>-->
<!--      </Link>-->
<!--    </div>-->
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
    yearSelected: Number,
  },
  data() {
    return {
      selectedYear: this.yearSelected ?? null,
      // selectedYear: newValue ?? null,
    }
  },
  watch: {
    selectedYear(newValue) {
      console.log('Selected Year changed:', newValue + ' - ' + this.year)
      this.updateUrl(newValue)
    },
  },
  mounted() {
    console.log(this.year)
  },
  methods: {
    // buildHref(year) {
    //   const url = new URL(window.location.href)
    //   url.searchParams.set('year', year)
    //   url.searchParams.delete('month')
    //   return url.pathname + url.search
    // },
    // handleSelect() {
    //   const url = new URL(window.location.href)
    //   url.searchParams.delete('year')
    //   url.searchParams.delete('month')
    //   window.location = url
    // },
    updateUrl(selectedValue) {
      const params = new URLSearchParams(window.location.search)
      params.set('year', selectedValue)

      this.$inertia.visit(`${window.location.pathname}?${params.toString()}`, {
        method: 'get',
        preserveState: false,
        replace: true,
      })
    },
  },
}
</script>
