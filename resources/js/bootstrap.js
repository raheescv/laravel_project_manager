import axios from 'axios';

window.axios = axios;

// Always send AJAX header
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Add CSRF Token if present
const token = document.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.warn("CSRF token not found: <meta name='csrf-token'> is missing!");
}
