<script setup>
/**
 * The plate: the illustration from the Newrest parent site's own sign-in page
 * (svg.canteen-illustration), kept inline so it costs no extra request and needs
 * no asset pipeline. The sign-in pages sink two of these into the background as
 * watermarks, so it is decoration and stays out of the accessibility tree.
 */
</script>

<template>
  <svg class="pp-plate" viewBox="0 0 240 200" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <ellipse cx="125" cy="173" fill="#233d3112" rx="82" ry="13" />
    <circle cx="121" cy="101" fill="#fffdf5" r="81" />
    <circle cx="121" cy="101" r="64" stroke="#e9e2ce" stroke-width="2" />
    <g transform="rotate(-24 120 100)">
      <path d="M78 71 Q73 46 98 45 L147 45 Q170 49 165 72 L147 155 Q123 170 100 150Z" fill="#edbd75" />
      <path d="M86 70 Q73 55 91 49 Q89 34 108 45 Q119 26 130 45 Q151 31 153 51 Q171 50 160 70" fill="#3c8c4d" />
      <path d="M94 61 Q104 40 111 62 M133 61 Q144 40 153 60" stroke="#ed714d" stroke-width="12" />
      <path d="M82 76 L163 75 L146 151 Q125 165 101 150Z" fill="#ffe0a0" />
      <path d="M94 100 L149 92 M101 119 L140 113 M111 139 L132 135" stroke="#cc9750" stroke-linecap="round" stroke-width="5" />
      <path d="M78 89 L161 110 L147 156 Q127 171 99 150Z" fill="#fff8df" />
      <path d="M87 111 L147 127" stroke="#e8dcbc" stroke-width="2" />
    </g>
    <path d="M35 28 V44 M27 36 H43 M199 135 V149 M192 142 H206" stroke="#eaa134" stroke-linecap="round" stroke-width="3" />
    <path d="M190 49 Q203 30 210 46 Q208 58 190 49Z" fill="#5a9764" />
  </svg>
</template>
