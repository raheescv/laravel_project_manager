<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue'

import { fetchRegistrationForm, submitRegistration } from '@/api/parent'
import '@/assets/register.css'
import RegisterField from '@/components/RegisterField.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import { school } from '@/school'
import { session } from '@/session'

/**
 * The public form a new parent fills in: school, their own details, then each
 * child. Nothing here signs anyone in. Every child waits for the office to
 * approve them, and the parent's portal link comes after that.
 */
const PARENT_FIELDS = ['name', 'qid', 'phone', 'email']
const STUDENT_FIELDS = ['name', 'qid', 'class', 'div', 'reg']
const ORDINALS = ['First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth']

const blankStudent = () => ({ name: '', qid: '', class: '', div: '', reg: '' })

const options = ref(null)
const loadError = ref('')

const step = ref(1)
const reached = ref(1)
const chosenSchool = ref('')
const parent = reactive({ name: '', qid: '', phone: '', email: '' })
const students = ref([blankStudent()])
const open = ref(0)
const removed = ref(null)
const touched = reactive(new Set())
const serverErrors = ref({})
const sending = ref(false)
const alert = ref('')
const done = ref(false)
const botField = ref('')
const panel = ref(null)
const doneCard = ref(null)

const parentPaths = PARENT_FIELDS.map((field) => `parent.${field}`)
const studentPaths = (index) => STUDENT_FIELDS.map((field) => `s.${index}.${field}`)

const goLabel = computed(() => {
  if (sending.value) return 'Sending…'
  if (step.value !== 3) return 'Continue'
  return students.value.length > 1 ? `Submit ${students.value.length} students` : 'Submit registration'
})
const firstName = computed(() => parent.name.trim().split(/\s+/)[0])
/** Kindergarten names on one line, numbered classes on the next. */
const classRows = computed(() => {
  const classes = options.value?.classes ?? []
  return [classes.filter((value) => !/^\d+$/.test(value)), classes.filter((value) => /^\d+$/.test(value))].filter((row) => row.length)
})

async function loadForm() {
  loadError.value = ''
  try {
    options.value = await fetchRegistrationForm()
  } catch (error) {
    loadError.value = error.message
  }
}

onMounted(loadForm)

function read(path) {
  const [group, key, field] = path.split('.')
  return group === 'parent' ? parent[key] : students.value[+key][field]
}

function write(path, value) {
  const [group, key, field] = path.split('.')
  if (group === 'parent') parent[key] = value
  else students.value[+key][field] = value
}

function checkQid(value) {
  if (!value) return 'Enter the QID number.'
  return value.length === 11 ? '' : `A QID has 11 digits. This one has ${value.length}.`
}

function problem(path) {
  if (serverErrors.value[path]) return serverErrors.value[path]
  const value = String(read(path) ?? '').trim()
  const [group, key, field] = path.split('.')

  if (group === 'parent') {
    if (key === 'name') return value.length >= 2 ? '' : 'Enter your full name.'
    if (key === 'qid') return checkQid(value)
    if (key === 'phone') return /^\d{8}$/.test(value) ? '' : 'Enter your 8-digit mobile number.'
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? '' : 'Enter a full email, like name@example.com.'
  }

  const index = +key
  const twin = (name) => students.value.some((student, i) => i !== index && String(student[name]).trim() === value)
  if (field === 'name') return value.length >= 2 ? '' : "Enter your child's full name."
  if (field === 'qid') return checkQid(value) || (twin('qid') ? 'You already added a child with this QID.' : '')
  if (field === 'class') return value ? '' : 'Choose a class.'
  if (field === 'div') return value ? '' : 'Choose a division.'
  if (!value) return 'Enter the registration number.'
  return twin('reg') ? 'You already added a child with this number.' : ''
}

const errorFor = (path) => (touched.has(path) ? problem(path) : '')
const isOk = (path) => read(path) !== '' && !problem(path)
const countFor = (path) => (read(path) ? `${read(path).length}/11` : '')

function digitsOf(value) {
  return value
    .replace(/[٠-٩]/g, (digit) => digit.charCodeAt(0) - 0x660)
    .replace(/[۰-۹]/g, (digit) => digit.charCodeAt(0) - 0x6f0)
    .replace(/\D/g, '')
}

function localMobile(digits) {
  return (digits.length > 8 && digits.startsWith('974') ? digits.slice(3) : digits).slice(0, 8)
}

function initials(name) {
  const parts = String(name).trim().split(/\s+/).filter(Boolean)
  return parts.length ? (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase() : ''
}

function gradeText(student) {
  return /^\d+$/.test(student.class) ? `Grade ${student.class}` : student.class
}

function classText(student) {
  return [gradeText(student), student.div].filter(Boolean).join(' · ')
}

function hasProblems(index) {
  return studentPaths(index).some((path) => problem(path))
}

function rowDetail(student, index) {
  if (hasProblems(index)) return 'Some details are missing. Tap to finish.'
  return [classText(student), student.reg ? `Reg ${student.reg}` : ''].filter(Boolean).join(' · ')
}

function clearServerError(path) {
  if (!serverErrors.value[path]) return
  const { [path]: cleared, ...rest } = serverErrors.value
  serverErrors.value = rest
}

function edit(path, event) {
  const input = event.target
  if (path.endsWith('.qid')) input.value = digitsOf(input.value).slice(0, 11)
  if (path.endsWith('.phone')) input.value = localMobile(digitsOf(input.value))
  write(path, input.value)
  clearServerError(path)
  if (path.endsWith('.qid') && input.value.length === 11) touched.add(path)
  if (path.endsWith('.phone') && input.value.length === 8) touched.add(path)
}

function leave(path) {
  if (String(read(path)).trim() !== '') touched.add(path)
}

function choose(path, value) {
  write(path, value)
  clearServerError(path)
  touched.add(path)
}

function goTo(target) {
  step.value = target
  reached.value = Math.max(reached.value, target)
  window.scrollTo({ top: 0 })
  nextTick(() => panel.value?.focus({ preventScroll: true }))
}

function pickSchool(value) {
  chosenSchool.value = value
  setTimeout(() => goTo(2), 240)
}

/** Shows the errors on these fields and takes the parent to the first. True when any is wrong. */
function failOn(paths) {
  paths.forEach((path) => touched.add(path))
  const first = paths.find((path) => problem(path))
  if (!first) return false

  nextTick(() => {
    const field = document.querySelector(`[data-field="${first}"]`)
    field?.scrollIntoView({ block: 'center', behavior: 'smooth' })
    const input = field?.querySelector('input')
    if (input && input.type !== 'radio') input.focus({ preventScroll: true })
  })
  return true
}

function openStudent(index) {
  open.value = index
  removed.value = null
}

function switchTo(index) {
  if (!failOn(studentPaths(open.value))) openStudent(index)
}

function addStudent() {
  if (failOn(studentPaths(open.value))) return
  students.value.push(blankStudent())
  open.value = students.value.length - 1
  removed.value = null
  nextTick(() => document.querySelector('.srx-open')?.scrollIntoView({ block: 'start', behavior: 'smooth' }))
}

function removeStudent(index) {
  removed.value = { student: students.value[index], at: index }
  students.value.splice(index, 1)
  if (!students.value.length) students.value.push(blankStudent())
  open.value = Math.min(open.value > index ? open.value - 1 : open.value, students.value.length - 1)
}

function undoRemove() {
  students.value.splice(removed.value.at, 0, removed.value.student)
  open.value = removed.value.at
  removed.value = null
}

function next() {
  if (step.value === 2) {
    if (!failOn(parentPaths)) goTo(3)
    return
  }
  if (failOn(studentPaths(open.value))) return
  const wrong = students.value.findIndex((_, index) => hasProblems(index))
  if (wrong >= 0) {
    openStudent(wrong)
    failOn(studentPaths(wrong))
    return
  }
  send()
}

async function send() {
  if (sending.value) return
  sending.value = true
  alert.value = ''
  try {
    await submitRegistration({
      school: chosenSchool.value,
      parent: { ...parent },
      students: students.value,
      contact_ref: botField.value,
    })
    done.value = true
    window.scrollTo({ top: 0 })
    nextTick(() => doneCard.value?.focus({ preventScroll: true }))
  } catch (error) {
    if (error.status === 422 && error.errors) applyServerErrors(error.errors)
    else if (error.status === 429) alert.value = 'Too many tries from this connection. Please wait a minute and press Submit again.'
    else if (error.status === 0) alert.value = 'We could not reach the school. Check your connection and press Submit again. Your details are still here.'
    else alert.value = 'We could not save this just now. Your details are still here, so please try again.'
  } finally {
    sending.value = false
  }
}

/** The server's `students.0.reg` is this form's `s.0.reg`; each error goes on its own field. */
function applyServerErrors(errors) {
  const mapped = {}
  Object.entries(errors).forEach(([key, messages]) => {
    mapped[key.replace(/^students\./, 's.')] = messages[0]
  })

  if (mapped.school) {
    goTo(1)
    alert.value = 'Please choose your school again.'
    return
  }

  serverErrors.value = mapped
  const paths = Object.keys(mapped)
  const parentErrors = paths.filter((path) => path.startsWith('parent.'))
  if (parentErrors.length) {
    goTo(2)
    failOn(parentErrors)
    return
  }

  const student = paths.map((path) => path.split('.')).find((parts) => parts[0] === 's' && parts.length === 3)
  if (!student) {
    alert.value = mapped.students || 'Please check the details and try again.'
    return
  }
  openStudent(+student[1])
  failOn(studentPaths(+student[1]))
}

/** Enter moves to the next box instead of sending the form half-filled. */
function enterMovesOn(event) {
  if (event.target.tagName !== 'INPUT' || event.target.type === 'radio') return
  event.preventDefault()
  const inputs = [...panel.value.querySelectorAll('input[data-path]')]
  const following = inputs[inputs.indexOf(event.target) + 1]
  if (following) following.focus()
  else event.target.blur()
}
</script>

<template>
  <div class="srx">
    <div class="srx-page">
      <header class="srx-head">
        <div class="srx-brand">
          <div class="srx-mark">
            <img v-if="school.logo" :src="school.logo" alt="" />
            <i v-else class="fa fa-cutlery"></i>
          </div>
          <div>
            <strong>{{ school.name || 'School canteen' }}</strong>
            <span>Student registration</span>
          </div>
        </div>
        <nav v-if="options?.schools.length && !done" class="srx-steps" aria-label="Progress">
          <button
            v-for="(label, index) in ['School', 'Parent', 'Students']"
            :key="label"
            type="button"
            class="srx-step"
            :class="{ 'is-on': index + 1 === step, 'is-done': index + 1 < step }"
            :disabled="index + 1 > reached || index + 1 === step"
            :aria-current="index + 1 === step ? 'step' : undefined"
            @click="goTo(index + 1)"
          >
            <i><span v-if="index + 1 < step" class="fa fa-check"></span><template v-else>{{ index + 1 }}</template></i>{{ label }}
          </button>
        </nav>
        <ThemeToggle />
      </header>

      <main class="srx-card" :class="{ 'srx-card--wide': step === 3 && !done && options?.schools.length }">
        <div v-if="loadError" class="srx-closed">
          <i class="fa fa-exclamation-circle"></i>
          <h1>We couldn't open the form</h1>
          <p class="srx-sub">{{ loadError }}</p>
          <button type="button" class="srx-btn srx-btn--back" @click="loadForm">Try again</button>
        </div>

        <div v-else-if="!options" class="srx-closed" role="status" aria-label="Loading">
          <span class="srx-loader" aria-hidden="true"></span>
        </div>

        <div v-else-if="!options.schools.length" class="srx-closed">
          <i class="fa fa-clock-o"></i>
          <h1>Registration isn't open yet</h1>
          <p class="srx-sub">Please check back soon, or contact the school office.</p>
        </div>

        <div v-else-if="done" ref="doneCard" class="srx-done" tabindex="-1">
          <div class="srx-done__ic"><i class="fa fa-check"></i></div>
          <h1>Registration received</h1>
          <p class="srx-sub">Thank you, {{ firstName }}. Your details are with the {{ chosenSchool }} office.</p>
          <div class="srx-slip">
            <div class="srx-slip__row"><span>School</span><b>{{ chosenSchool }}</b></div>
            <div v-for="(student, index) in students" :key="index" class="srx-slip__row">
              <span>{{ classText(student) }}</span><b>{{ student.name }}</b>
            </div>
          </div>
          <p class="srx-next">
            <i class="fa fa-info-circle"></i>
            <span>Once the office has checked them, we'll email <b>{{ parent.email }}</b> a link to set your parent portal password.</span>
          </p>
          <RouterLink class="srx-btn srx-btn--go srx-done__link" :to="{ name: session.signedIn ? 'home' : 'login' }">
            <span>{{ session.signedIn ? 'Back to my children' : 'Go to sign in' }}</span><i class="fa fa-arrow-right"></i>
          </RouterLink>
        </div>

        <template v-else>
          <form novalidate autocomplete="on" @submit.prevent @keydown.enter="enterMovesOn">
            <div class="srx-trap" aria-hidden="true">
              <label for="contact_ref">Leave this empty</label>
              <input id="contact_ref" v-model="botField" type="text" tabindex="-1" autocomplete="off" />
            </div>

            <section :key="step" ref="panel" class="srx-panel" tabindex="-1">
              <template v-if="step === 1">
                <h1>Which school?</h1>
                <p class="srx-sub">Tap your child's school to begin. If your children go to different schools, send one form for each school.</p>
                <div class="srx-schools">
                  <label v-for="tile in options.schools" :key="tile.value" class="srx-school">
                    <input type="radio" name="school" :value="tile.value" :checked="chosenSchool === tile.value" @change="pickSchool(tile.value)" />
                    <span class="srx-school__mono">{{ tile.initial }}</span>
                    <span class="srx-school__tx">
                      <small v-if="tile.group">{{ tile.group }}</small>
                      <b>{{ tile.place }}</b>
                    </span>
                    <span class="srx-school__go"><i class="fa fa-angle-right"></i></span>
                  </label>
                </div>
              </template>

              <template v-else-if="step === 2">
                <h1>Your details</h1>
                <p class="srx-sub">Please fill in every box. Your mobile number will be your sign-in for the parent portal.</p>
                <div class="srx-fields srx-fields--two">
                  <RegisterField id="p_name" path="parent.name" label="Full name" wide :value="parent.name" :error="errorFor('parent.name')" :ok="isOk('parent.name')" autocomplete="name" enterkeyhint="next" maxlength="100" placeholder="As on your QID" @edit="edit('parent.name', $event)" @leave="leave('parent.name')" />
                  <RegisterField id="p_qid" path="parent.qid" label="QID number" dir="ltr" :value="parent.qid" :error="errorFor('parent.qid')" :ok="isOk('parent.qid')" :count="countFor('parent.qid')" inputmode="numeric" autocomplete="off" enterkeyhint="next" maxlength="20" placeholder="11 digits" @edit="edit('parent.qid', $event)" @leave="leave('parent.qid')" />
                  <RegisterField id="p_phone" path="parent.phone" label="Mobile number" prefix="+974" :value="parent.phone" :error="errorFor('parent.phone')" :ok="isOk('parent.phone')" type="tel" inputmode="numeric" autocomplete="tel-national" enterkeyhint="next" maxlength="16" placeholder="5512 3456" @edit="edit('parent.phone', $event)" @leave="leave('parent.phone')" />
                  <RegisterField id="p_email" path="parent.email" label="Email" dir="ltr" wide help="We'll send your portal sign-in link here." :value="parent.email" :error="errorFor('parent.email')" :ok="isOk('parent.email')" type="email" inputmode="email" autocomplete="email" autocapitalize="none" enterkeyhint="done" maxlength="150" placeholder="name@example.com" @edit="edit('parent.email', $event)" @leave="leave('parent.email')" />
                </div>
              </template>

              <template v-else>
                <h1>Your children</h1>
                <p class="srx-sub">Add each child who goes to this school.</p>
                <div class="srx-sum">
                  <button type="button" @click="goTo(1)"><i class="fa fa-map-marker"></i><span>{{ chosenSchool }}</span><em>Change</em></button>
                  <button type="button" @click="goTo(2)"><i class="fa fa-user"></i><span>{{ parent.name }}</span><em>Change</em></button>
                </div>

                <div class="srx-kids">
                  <template v-for="(student, index) in students" :key="index">
                    <div v-if="index === open" class="srx-open">
                      <div v-if="students.length > 1" class="srx-open__top">
                        <b>{{ ORDINALS[index] || `Student ${index + 1}:` }} child</b>
                        <button type="button" class="srx-link" @click="removeStudent(index)">Remove</button>
                      </div>
                      <div class="srx-open__body">
                        <aside class="srx-pass" aria-hidden="true">
                          <div class="srx-pass__top">{{ chosenSchool }}</div>
                          <div class="srx-pass__av">
                            <template v-if="initials(student.name)">{{ initials(student.name) }}</template>
                            <i v-else class="fa fa-graduation-cap"></i>
                          </div>
                          <div class="srx-pass__info">
                            <div class="srx-pass__name" :class="{ 'is-empty': !student.name.trim() }">{{ student.name.trim() || "Your child's name" }}</div>
                          </div>
                          <div class="srx-pass__tiles">
                            <div><small>Class</small><b>{{ gradeText(student) || '—' }}</b></div>
                            <div><small>Division</small><b>{{ student.div || '—' }}</b></div>
                          </div>
                          <div class="srx-pass__bar"></div>
                          <div v-if="student.reg" class="srx-pass__reg">{{ student.reg }}</div>
                        </aside>

                        <div class="srx-fields srx-fields--two">
                          <RegisterField :id="`s_name_${index}`" :path="`s.${index}.name`" label="Student name" wide :value="student.name" :error="errorFor(`s.${index}.name`)" :ok="isOk(`s.${index}.name`)" autocomplete="off" enterkeyhint="next" maxlength="100" placeholder="As on the QID" @edit="edit(`s.${index}.name`, $event)" @leave="leave(`s.${index}.name`)" />
                          <RegisterField :id="`s_qid_${index}`" :path="`s.${index}.qid`" label="QID number" dir="ltr" :value="student.qid" :error="errorFor(`s.${index}.qid`)" :ok="isOk(`s.${index}.qid`)" :count="countFor(`s.${index}.qid`)" inputmode="numeric" autocomplete="off" enterkeyhint="next" maxlength="20" placeholder="11 digits" @edit="edit(`s.${index}.qid`, $event)" @leave="leave(`s.${index}.qid`)" />
                          <RegisterField :id="`s_reg_${index}`" :path="`s.${index}.reg`" label="Registration number" dir="ltr" help="The number the school gave your child." :value="student.reg" :error="errorFor(`s.${index}.reg`)" :ok="isOk(`s.${index}.reg`)" autocomplete="off" autocapitalize="characters" enterkeyhint="done" maxlength="30" placeholder="From the school" @edit="edit(`s.${index}.reg`, $event)" @leave="leave(`s.${index}.reg`)" />

                          <div class="srx-f srx-span" :class="{ 'is-bad': errorFor(`s.${index}.class`) }" :data-field="`s.${index}.class`">
                            <div :id="`lc_${index}`" class="srx-lbl">Class</div>
                            <div class="srx-chip-rows" role="radiogroup" :aria-labelledby="`lc_${index}`">
                              <div v-for="(row, rowIndex) in classRows" :key="rowIndex" class="srx-chips">
                                <label v-for="value in row" :key="value" class="srx-chip">
                                  <input type="radio" :name="`class_${index}`" :value="value" :checked="student.class === value" @change="choose(`s.${index}.class`, value)" />
                                  <span>{{ value }}</span>
                                </label>
                              </div>
                            </div>
                            <p class="srx-err" role="alert"><template v-if="errorFor(`s.${index}.class`)"><i class="fa fa-exclamation-circle"></i><span>{{ errorFor(`s.${index}.class`) }}</span></template></p>
                          </div>

                          <div class="srx-f srx-span" :class="{ 'is-bad': errorFor(`s.${index}.div`) }" :data-field="`s.${index}.div`">
                            <div :id="`ld_${index}`" class="srx-lbl">Division</div>
                            <div class="srx-chips" role="radiogroup" :aria-labelledby="`ld_${index}`">
                              <label v-for="value in options.divisions" :key="value" class="srx-chip">
                                <input type="radio" :name="`div_${index}`" :value="value" :checked="student.div === value" @change="choose(`s.${index}.div`, value)" />
                                <span>{{ value }}</span>
                              </label>
                            </div>
                            <p class="srx-err" role="alert"><template v-if="errorFor(`s.${index}.div`)"><i class="fa fa-exclamation-circle"></i><span>{{ errorFor(`s.${index}.div`) }}</span></template></p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div v-else class="srx-kid" :class="{ 'is-bad': hasProblems(index) }">
                      <button type="button" class="srx-kid__open" @click="switchTo(index)">
                        <span class="srx-kid__av">
                          <template v-if="initials(student.name)">{{ initials(student.name) }}</template>
                          <i v-else class="fa fa-graduation-cap"></i>
                        </span>
                        <span class="srx-kid__tx">
                          <b>{{ student.name.trim() || `Student ${index + 1}` }}</b>
                          <small>{{ rowDetail(student, index) }}</small>
                        </span>
                      </button>
                      <button type="button" class="srx-ico" :aria-label="`Edit ${student.name}`" @click="switchTo(index)"><i class="fa fa-pencil"></i></button>
                      <button type="button" class="srx-ico srx-ico--bad" :aria-label="`Remove ${student.name}`" @click="removeStudent(index)"><i class="fa fa-trash-o"></i></button>
                    </div>
                  </template>

                  <div v-if="removed" class="srx-undo">
                    <span>Removed {{ removed.student.name.trim() || 'student' }}</span>
                    <button type="button" @click="undoRemove"><i class="fa fa-undo"></i> Undo</button>
                  </div>
                </div>

                <button type="button" class="srx-add" @click="addStudent"><i class="fa fa-plus"></i>Add next student</button>
              </template>
            </section>

            <div v-if="alert" class="srx-alert" role="alert"><i class="fa fa-exclamation-circle"></i><span>{{ alert }}</span></div>

            <div v-if="step > 1" class="srx-act">
              <button type="button" class="srx-btn srx-btn--back" aria-label="Back" @click="goTo(step - 1)"><i class="fa fa-angle-left"></i><span>Back</span></button>
              <button type="button" class="srx-btn srx-btn--go" :disabled="sending" @click="next"><span>{{ goLabel }}</span><i class="fa fa-arrow-right"></i></button>
            </div>
          </form>
        </template>
      </main>

      <p v-if="!session.signedIn && !done && options?.schools.length" class="srx-foot">
        Already registered? <RouterLink :to="{ name: 'login' }">Sign in to the parent portal</RouterLink>
      </p>
    </div>
  </div>
</template>
