<template>
  <Head title="KCP" />
  <BudMenu :bud-id="build" />
  <h1 class="mb-8 text-3xl font-bold">
    <Link class="text-indigo-400 hover:text-indigo-600" href="/organizations">Budowa</Link>
    <span class="text-indigo-400 font-medium">/</span>
    {{ buildDetails.nazwaBud }}
  </h1>
  <div class="flex items-center justify-between mb-6">
    <h1 class="mb-8 text-3xl font-bold">KCP</h1>
    <a target="_self" :href="excelUrl()" class="btn-indigo inline-flex items-center px-4 py-2 rounded">
      <DocumentDownloadIcon class="text-blue-500 w-5 h-5" />
      <span>Pobierz</span>
    </a>
  </div>
  <div ref="printTable" class="flex grid px-6 py-2 bg-white rounded-lg shadow overflow-auto">
    <div class="flex items-center py-2">
      <button type="button" class="inline-flex items-center p-1 leading-none hover:bg-gray-200 rounded-lg cursor-pointer transition duration-100 ease-in-out" @click="previousMonth()">
        <svg class="inline-flex w-6 h-6 text-gray-500 leading-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      <div class="inline-flex h-6 border-r" />
      <button type="button" class="inline-flex items-center p-1 leading-none hover:bg-gray-200 rounded-lg cursor-pointer transition duration-100 ease-in-out" @click="nextMonth()">
        <svg class="inline-flex w-6 h-6 text-gray-500 leading-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
      <span class="text-gray-800 text-lg font-bold">{{ month.toUpperCase() }}</span>
      <span class="ml-1 text-gray-600 text-lg font-normal">{{ date.getFullYear() }}</span>
    </div>
    <div v-for="timeSheet in sortedByOrder" :key="timeSheet.id" class="flex border-l border-t" :class="Object.keys(timeSheets).length === 1 ? 'border-b' : ''">
      <div class="border-1 relative sticky z-10 left-0 pt-2 px-4 text-gray-500 bg-gray-100 border-r cursor-pointer" style="width: 127px; height: 68px">
        <div class="text-center text-sm">{{ timeSheet[1].name }}</div>
        <div class="text-center text-sm">Suma: {{ formatRangeToDisplay(summarize(timeSheet)) }}</div>
      </div>
      <div v-for="shift in timeSheet" :key="shift.id" :class="shiftBackground(shift)" class="border-1 relative pt-2 px-4 text-gray-500 text-sm hover:bg-gray-200 border-r cursor-pointer" style="width: 127px; height: 68px" @click="showModal(shift)">
        <div class="flex justify-between">
          <div class="inline-flex items-center justify-center text-center text-gray-700 text-sm leading-none rounded-full cursor-pointer">{{ new Date(shift.day).getDate() }}</div>
          <div class="inline-flex items-center justify-center text-center text-gray-700 text-sm leading-none rounded-full cursor-pointer">{{ dayOfWeek(new Date(shift.day)) }}</div>
        </div>
        <div v-if="shift.isBlocked && shift.blockedType === 'feast'" class="mt-1 text-center overflow-y-auto" style="height: 60px">
          {{ shift.status === 8 ? getStatusName(shift.status) : 'Święto' }}
        </div>
        <div v-if="shift.status" class="mt-1 text-center overflow-y-auto" style="height: 60px">
          {{ getStatusName(shift.status) }}
        </div>
        <div v-if="!shift.status" class="mt-1 overflow-y-auto" style="height: 60px">
          {{ formatTimeRange(shift.from) }} - {{ formatTimeRange(shift.to) }} <br />
          <div class="text-center text-sm">{{ shift.work }}</div>
        </div>
      </div>
    </div>

    <div v-if="timeSheets.length > 0" class="border-1 relative pt-2 px-4 text-gray-500 text-sm hover:bg-gray-200 border border-r cursor-pointer" style="width: 127px; height: 68px">
      <div class="mt-1 overflow-y-auto" style="height: 60px">
        <div class="text-center text-sm">Suma:</div>
      </div>
    </div>

    <div v-if="timeSheets.length < 1" class="flex pt-2 px-4">Brak pracowników</div>
  </div>
  <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="relative z-10" @close="open = false">
      <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>
      <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center p-4 min-h-full text-center sm:items-center sm:p-0">
          <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <DialogPanel class="relative text-left bg-white rounded-lg shadow-xl overflow-hidden transform transition-all sm:my-8 sm:w-full sm:max-w-lg">
              <div class="pb-4 pt-5 px-4 bg-white sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                  <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                    <DialogTitle as="h3" class="text-gray-900 text-lg font-medium leading-6"> Wprowadź dane dla dnia: </DialogTitle>
                    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
                      <fieldset :disabled="disabled == 0">
                        <form @submit.prevent="update">
                          <div class="flex flex-wrap -mb-8 -mr-6 p-8">
                            <div class="grid grid-cols-2">
                              <div>
                                <label for="workFrom">Czas pracy od:</label>
                                <Datepicker id="workFrom" v-model="form.from" :disabled="isStatus" :clearable="false" time-picker minutes-increment="30" class="pb-8 pr-6" @update:modelValue="calculateEffectiveTime" />
                              </div>
                              <div>
                                <label for="workTo">Czas pracy do:</label>
                                <Datepicker id="workTo" v-model="form.to" :disabled="isStatus" :clearable="false" time-picker minutes-increment="30" class="pb-8 pr-6" @update:modelValue="calculateEffectiveTime" />
                              </div>
                            </div>

                            <div class="grid grid-cols-2">
                              <div>
                                <label for="workTime">Czas pracy:</label>
                                <Datepicker id="workTime" v-model="form.workTime" :clearable="false" time-picker minutes-increment="30" class="pb-8 pr-6 w-full" />
                              </div>
                              <div>
                                <input id="time-reduce" ref="timeReduce" class="mr-2 mt-7" type="checkbox" @change="wortTimeReduce()" />
                                <label for="time-reduce">Skróć czas o 30 min</label>
                              </div>
                            </div>

                            <select-input v-model="form.status" class="lg:w-1/1 pb-8 pr-6 w-full" label="Powód nieobecności" @change="statusChanged($event)">
                              <option v-for="status in shiftStatuses" :key="status.id" :value="status.id">{{ status.title }}({{ status.code }})</option>
                            </select-input>
                          </div>
                        </form>
                      </fieldset>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="(calculateDiffDays() < 3 && user_owner === 3) || user_owner !== 3" class="px-4 py-3 bg-gray-50 sm:flex sm:flex-row-reverse sm:px-6">
                <button type="button" class="inline-flex justify-center px-4 py-2 w-full text-white text-base font-medium bg-green-600 hover:bg-green-700 border border-transparent rounded-md focus:outline-none shadow-sm focus:ring-2 focus:ring-green-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm" @click="saveHours()">Zapisz</button>
                <button ref="cancelButtonRef" type="button" class="inline-flex justify-center mt-3 px-4 py-2 w-full text-gray-700 text-base font-medium hover:bg-gray-50 bg-white border border-gray-300 rounded-md focus:outline-none shadow-sm focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm" @click="open = false">Anuluj</button>
                <button class="mr-auto text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import Layout from '@/Shared/Layout'
import moment from 'moment'
import axios from 'axios'
import SelectInput from '@/Shared/SelectInput'
import Datepicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import BudMenu from '@/Shared/BudMenu.vue'
import { Head, Link } from '@inertiajs/inertia-vue3'
import { DocumentDownloadIcon } from '@heroicons/vue/solid'

const DEFAULT_RANGES = {
  from: { hours: '07', minutes: '00' },
  to: { hours: '17', minutes: '00' },
  shift: { hours: '09', minutes: '30' },
}

export default {
  components: {
    Link,
    DocumentDownloadIcon,
    BudMenu,
    SelectInput,
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
    Datepicker,
    Head,
  },
  layout: Layout,
  props: {
    build: Number,
    timeSheets: {
      type: Object,
      required: true,
    },
    timeSheetsOrder: Array,
    date: Date,
    month: String,
    shiftStatuses: Array,
    diffDays: Number,
    user_owner: Number,
    buildDetails: Object,
  },
  data() {
    return {
      date: new Date(this.date),
      open: false,
      isStatus: false,
      form: this.$inertia.form({
        id: null,
        day: null,
        from: null,
        to: null,
        workTime: null,
        status: null,
        isBlocked: null,
        blockedType: null,
        name: null,
      }),
    }
  },
  computed: {
    sortedByOrder() {
      return this.timeSheetsOrder.map((id) => this.timeSheets[id])
    },
  },
  /**
   *  Calculate worker hour in month
   *
   */
  mounted() {
    this.shiftStatuses.push({
      id: 0,
      title: 'Nie dotyczy',
      code: 'brak',
    })
  },
  methods: {
    printData() {
      var divToPrint = this.$refs.printTable
      var newWin = window.open('')
      newWin.document.write(divToPrint.outerHTML)
      newWin.print()
      newWin.close()
    },
    calculateDiffDays() {
      const today = new Date()
      const day = new Date(this.form.day) // Convert this.form.day to a Date object
      const diffTime = Math.abs(today.getTime() - day.getTime())
      const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24)) // Use Math.floor instead of Math.ceil
      return diffDays
    },
    dayOfWeek(date) {
      return new Intl.DateTimeFormat('pl-PL', { weekday: 'long' }).format(date).slice(0, 3)
    },
    statusChanged(event) {
      this.isStatus = this.isSetStatus(event.target.value)
    },
    isSetStatus(status) {
      return Number(status) !== 0
    },
    getStatusName(statusId) {
      return this.shiftStatuses.find((elem) => elem.id === statusId).code
    },
    summarize(timeShift) {
      const sum = Object.values(timeShift)
        .filter((shift) => !shift.status)
        .filter((shift) => shift.work !== null)
        .map((shift) => shift.work)
        .reduce((agg, elem) => {
          agg += elem ? moment.duration(elem).asMinutes() : 0
          return agg
        }, 0)

      const hours = Math.floor(sum / 60)
      const minutes = sum - 60 * hours

      return {
        hours: hours,
        minutes: minutes,
      }
    },
    previousMonth() {
      const previousMonthNumber = this.getMonthNumber() < 1
      const year = previousMonthNumber ? this.getYear() - 1 : this.getYear()

      this.redirect(this.dateUrl(this.build, year, previousMonthNumber ? 12 : this.getMonthNumber()))
    },
    nextMonth() {
      const nextMonthNumber = this.getMonthNumber() + 2 > 12
      const year = nextMonthNumber ? this.getYear() + 1 : this.getYear()
      this.redirect(this.dateUrl(this.build, year, nextMonthNumber ? '01' : this.getMonthNumber() + 2))
    },
    redirect(url) {
      window.location = url
    },
    dateUrl(build, year, month) {
      return `/building/${this.build}/time-sheet?date=${year}-${month.toString().padStart(2, '0')}`
    },
    excelUrl() {
      return `/building/${this.build}/time-sheet/export?date=${this.getYear()}-${(this.getMonthNumber() + 1).toString().padStart(2, '0')}`
    },
    getMonthNumber() {
      return new Date(this.date).getMonth()
    },
    getYear() {
      return new Date(this.date).getFullYear()
    },
    /**
     *
     * @param time HH:mm e.g. 09:30 -> object
     * @returns {{hours: *, minutes: *}}
     */
    formatTimeToObject(time) {
      if (!time) {
        return null
      }

      return {
        hours: time.split(':').at(0),
        minutes: time.split(':').at(1),
      }
    },
    formatRangeToDisplay(range) {
      return String(range.hours).padStart(2, '0') + ':' + String(range.minutes).padStart(2, '0')
    },
    /**
     * Formatting from date to hh:mm
     * @param time {string}
     * @returns {string}
     */
    formatTimeRange(time) {
      if (time === null) {
        return ''
      }
      return String(new Date(time).getHours()).padStart(2, '0') + ':' + String(new Date(time).getMinutes()).padStart(2, '0')
    },
    /**
     * Formatting from string to object - using by datepicker
     *
     * @param time
     * @returns {string|{hours: string, minutes: string}}
     */
    formatTimeObject(time) {
      if (time === null) {
        return ''
      }
      return {
        hours: String(new Date(time).getHours()).padStart(2, '0'),
        minutes: String(new Date(time).getMinutes()).padStart(2, '0'),
      }
    },
    /**
     * @param time string HH:mm
     */
    criticalTime(time) {
      const criticalShiftWork = 570
      return moment.duration(time).asMinutes() > criticalShiftWork
    },
    shiftBackground(shift) {
      if (this.isSunday(shift)) {
        return 'bg-red-200'
      }

      if (shift.status === 8) {
        return 'bg-red-200'
      }

      if (this.isSaturday(shift)) {
        return 'bg-yellow-200'
      }

      if (this.isSetStatus(shift.status)) {
        return 'bg-green-100'
      }

      if (shift.isBlocked) {
        return 'bg-gray-300'
      }

      if (this.criticalTime(shift.work)) {
        return 'bg-red-300'
      }

      return ''
    },
    isSunday(shift) {
      return new Date(shift.day).getDay() === 0
    },
    isSaturday(shift) {
      return new Date(shift.day).getDay() === 6
    },
    showModal(shift) {
      // exception for feasts
      if (shift.isBlocked && shift.blockedType !== 'feast') {
        return
      }

      this.open = true
      this.form = this.$inertia.form = {
        name: shift.name,
        build: shift.build,
        id: shift.id ?? null,
        day: shift.day,
        from: this.formatTimeObject(shift.from) ? this.formatTimeObject(shift.from) : DEFAULT_RANGES.from,
        to: this.formatTimeObject(shift.to) ? this.formatTimeObject(shift.to) : DEFAULT_RANGES.to,
        workTime: this.formatTimeToObject(shift.work),
        status: shift.status ?? null,
      }

      if (!shift.work) {
        this.calculateEffectiveTime()
      }

      this.isStatus = this.isSetStatus(shift.status)
    },
    /**
     *
     * @param day
     * @param time {hours: Number, minutes: Number}
     * @returns {Date}
     */
    formatModalTimeToDate(day, time) {
      return new Date(day.getFullYear(), day.getMonth(), day.getDate(), time.hours, time.minutes, 0)
    },

    calculateEffectiveTime() {
      const workHours = moment.utc(moment.duration(moment(this.form.to.hours + ':' + this.form.to.minutes, 'HH:mm').diff(moment(this.form.from.hours + ':' + this.form.from.minutes, 'HH:mm'))).asMilliseconds()).format('HH:mm')

      this.form.workTime = {
        hours: workHours.split(':').at(0),
        minutes: workHours.split(':').at(1),
      }
    },
    destroy() {
      if (confirm('Chcesz usunąć?')) {
        try {
          this.$inertia.post(`/building/${this.build}/time-sheet/delete`, this.form)
        } catch (e) {
          console.error('Bład usnięcie godzin pracy.')
          // @TODO display message with error
          throw e
        }

        this.form = this.$inertia.form = {
          id: null,
          day: null,
          from: null,
          to: null,
          workTime: null,
        }
        // display notification
        this.open = false
      }
    },
    wortTimeReduce() {
      const checked = this.$refs.timeReduce.checked
      if (checked) {
        const workHours = moment(this.form.workTime.hours + ':' + this.form.workTime.minutes, 'HH:mm')
          .subtract('30', 'minutes')
          .format('hh:mm')

        this.form.workTime = {
          hours: workHours.split(':').at(0),
          minutes: workHours.split(':').at(1),
        }

        return
      }
      this.calculateEffectiveTime()
    },
    saveHours() {
      try {
        /**
         * Day (int) is an index for worker day!
         */
        const workerId = this.form.id
        const dayIndex = new Date(this.form.day).getDate()

        /**
         * How to work with callback functions on $inertia
         * @see resources/js/Pages/Users/Edit.vue:73
         */
        axios.post(`/building/${this.build}/time-sheet`, this.form)

        this.timeSheets[workerId][dayIndex] = {
          name: this.form.name,
          id: this.form.id ?? null,
          build: this.form.build,
          day: this.form.day,
          from: this.formatModalTimeToDate(new Date(this.form.day), this.form.from).toString(),
          to: this.formatModalTimeToDate(new Date(this.form.day), this.form.to).toString(),
          work: this.form.workTime.hours + ':' + this.form.workTime.minutes,
          status: this.form.status,
        }
      } catch (e) {
        console.error('Something happen while saving data.')
        // @TODO display message with error
        throw e
      }
      this.form = this.$inertia.form = {
        id: null,
        day: null,
        from: null,
        to: null,
        workTime: null,
      }
      // display notification
      this.open = false
    },
  },
}
</script>
