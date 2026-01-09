<template>
  <svg v-if="name === 'cheveron-down'" v-bind="$attrs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" /></svg>
  <svg v-else-if="name === 'cheveron-right'" v-bind="$attrs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><polygon points="12.95 10.707 13.657 10 8 4.343 6.586 5.757 10.828 10 6.586 14.243 8 15.657 12.95 10.707" /></svg>
  <svg v-else-if="name === 'dashboard'" v-bind="$attrs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M10 20a10 10 0 1 1 0-20 10 10 0 0 1 0 20zm-5.6-4.29a9.95 9.95 0 0 1 11.2 0 8 8 0 1 0-11.2 0zm6.12-7.64l3.02-3.02 1.41 1.41-3.02 3.02a2 2 0 1 1-1.41-1.41z" /></svg>
  <svg v-else-if="name === 'office'" v-bind="$attrs" width="100" height="100" viewBox="0 0 100 100"><path fill-rule="evenodd" d="M7 0h86v100H57.108V88.418H42.892V100H7V0zm9 64h11v15H16V64zm57 0h11v15H73V64zm-19 0h11v15H54V64zm-19 0h11v15H35V64zM16 37h11v15H16V37zm57 0h11v15H73V37zm-19 0h11v15H54V37zm-19 0h11v15H35V37zM16 11h11v15H16V11zm57 0h11v15H73V11zm-19 0h11v15H54V11zm-19 0h11v15H35V11z" /></svg>
  <svg v-else-if="name === 'printer'" v-bind="$attrs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M4 16H0V6h20v10h-4v4H4v-4zm2-4v6h8v-6H6zM4 0h12v5H4V0zM2 8v2h2V8H2zm4 0v2h2V8H6z" /></svg>
  <svg v-else-if="name === 'trash'" v-bind="$attrs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M6 2l2-2h4l2 2h4v2H2V2h4zM3 6h14l-1 14H4L3 6zm5 2v10h1V8H8zm3 0v10h1V8h-1z" /></svg>
  <svg v-else-if="name === 'users'" v-bind="$attrs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M7 8a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0 1c2.15 0 4.2.4 6.1 1.09L12 16h-1.25L10 20H4l-.75-4H2L.9 10.09A17.93 17.93 0 0 1 7 9zm8.31.17c1.32.18 2.59.48 3.8.92L18 16h-1.25L16 20h-3.96l.37-2h1.25l1.65-8.83zM13 0a4 4 0 1 1-1.33 7.76 5.96 5.96 0 0 0 0-7.52C12.1.1 12.53 0 13 0z" /></svg>
  <svg v-else-if="name === 'tools'" v-bind="$attrs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
    <path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z" />
  </svg>

  <svg v-else-if="name === 'eligibility'" v-bind="$attrs" viewBox="-0.8 -0.8 17.60 17.60" fill="none" xmlns="http://www.w3.org/2000/svg">
    <g id="SVGRepo_bgCarrier" stroke-width="0" />
    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" />
    <g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM7 3V8.41421L10.2929 11.7071L11.7071 10.2929L9 7.58579V3H7Z" fill="#7986CB" /> </g>
  </svg>

  <svg v-else-if="name === 'monthlyReport'" v-bind="$attrs" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
    <path d="M6 2a1 1 0 000 2h8a1 1 0 100-2H6zM3 6a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2H3zm1 9a1 1 0 011-1h2a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 110 2h6a1 1 0 110-2h-6z" />
  </svg>

  <svg v-else-if="name === 'forecast-workers'" v-bind="$attrs" viewBox="0 0 16 16" stroke="currentColor" xmlns="http://www.w3.org/2000/svg">
    <g id="SVGRepo_bgCarrier" stroke-width="0" />
    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke-width="0.44800000000000006"> <path d="M15 1H1V7H3.38197L4.88196 4L7.11803 4L10 9.76393L11.382 7H15V1Z" fill="#7986CB" /> <path d="M15 9H12.618L11.118 12L8.88197 12L6 6.23607L4.61803 9H1V15H15V9Z" fill="#7986CB" /> </g>
    <g id="SVGRepo_iconCarrier"> <path d="M15 1H1V7H3.38197L4.88196 4L7.11803 4L10 9.76393L11.382 7H15V1Z" fill="#7986CB" /> <path d="M15 9H12.618L11.118 12L8.88197 12L6 6.23607L4.61803 9H1V15H15V9Z" fill="#7986CB" /> </g>
  </svg>

  <svg v-else-if="name === 'dot'" v-bind="$attrs" xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-circle-dot" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
    <circle cx="12" cy="12" r="1" />
    <circle cx="12" cy="12" r="9" />
  </svg>

  <svg v-else-if="name === 'sprzet'" v-bind="$attrs" xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-axe" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
    <path d="M13 9l7.383 7.418c.823 .82 .823 2.148 0 2.967a2.11 2.11 0 0 1 -2.976 0l-7.407 -7.385" />
    <path d="M6.66 15.66l-3.32 -3.32a1.25 1.25 0 0 1 .42 -2.044l3.24 -1.296l6 -6l3 3l-6 6l-1.296 3.24a1.25 1.25 0 0 1 -2.044 .42z" />
  </svg>

  <svg v-else-if="name === 'sprzet2'" v-bind="$attrs" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="24" height="24" viewBox="0 0 318.15 318.15" class="text-blue-300 " style="enable-background:new 0 0 318.15 318.15;" xml:space="preserve">
    <path
      d="M232.015,112.306c1-1.226,2.021-2.484,3.088-3.789c-0.992-0.991-1.983-1.983-2.976-2.976
      c3.532-3.567,7.097-7.17,10.716-10.836c-7.638-7.635-11.451-11.449-19.092-19.092c-3.666,3.62-7.268,7.184-10.836,10.716
      c-0.992-0.992-1.984-1.983-2.976-2.976c-1.296,1.059-2.546,2.074-3.763,3.067C148.853,32.809,88.445,0.316,49.803,0l-5.789,5.789
      c38.562,0.309,89.675,41.809,145.591,96.544c-1.217,1.536-2.414,3.17-3.597,4.952l2.89,2.89
      C65.469,233.084,7.173,296.932,0.306,306.13l12.021,12.02c9.202-6.869,73.048-65.164,195.954-188.59l2.89,2.889
      c1.778-1.18,3.409-2.374,4.942-3.589c54.994,56.298,96.318,107.835,95.939,146.199l5.79-5.791
      C318.215,230.829,285.841,170.05,232.015,112.306z"
    />
  </svg>

  <svg v-else-if="name === 'destroy'" v-bind="$attrs" id="emoji" width="72px" height="72px" viewBox="0 0 72 72" xmlns="http://www.w3.org/2000/svg">
    <g id="color">
      <path fill="#FFFFFF" stroke="none" d="M51.7598,17H20.1525v37.65c0,4.0593,3.2907,5.6209,7.35,5.6209h16.9073c4.0593,0,7.35-1.5616,7.35-5.6209 V17z" />
      <polyline fill="#FFFFFF" stroke="none" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2" points="31,16 31,12 41,12 41,16" />
      <polygon fill="#9b9b9a" stroke="none" points="51,37 51,46.5172 51,57.6207 48.3,60 33,60" />
      <rect x="17" y="16" width="38" height="4" fill="#FFFFFF" stroke="none" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2" />
    </g>
    <g id="hair" />
    <g id="skin" />
    <g id="skin-shadow" />
    <g id="line">
      <polyline fill="none" stroke="#000000" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2" points="31,16 31,12 41,12 41,16" />
      <path fill="none" stroke="#000000" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2" d="M51,25v31c0,2.2091-1.7909,4-4,4H25c-2.2091,0-4-1.7909-4-4V25" />
      <rect x="17" y="16" width="38" height="4" fill="none" stroke="#000000" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2" />
      <line x1="41" x2="41" y1="28.25" y2="55" fill="none" stroke="#000000" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2" />
      <line x1="31" x2="31" y1="28.25" y2="55" fill="none" stroke="#000000" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2" />
    </g>
  </svg>
  <svg v-else-if="name === 'zablokuj'" v-bind="$attrs" xmlns="http://www.w3.org/2000/svg" width="11.967" height="11.967" viewBox="0 0 11.967 11.967">
    <path id="Icon_metro-blocked" data-name="Icon metro-blocked" d="M12.785,3.681a5.983,5.983,0,1,0,1.752,4.231,5.944,5.944,0,0,0-1.752-4.231Zm.257,4.231a4.463,4.463,0,0,1-.831,2.6L5.955,4.255a4.485,4.485,0,0,1,7.086,3.657Zm-8.975,0a4.463,4.463,0,0,1,.831-2.6l6.255,6.255A4.486,4.486,0,0,1,4.067,7.911Z" transform="translate(-2.571 -1.928)" fill="#d5233f" />
  </svg>
</template>

<script>
export default {
  props: {
    name: String,
  },
}
</script>
