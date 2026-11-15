/**
 * Send the browser to QPay's payment page: an ordinary form POST with the signed
 * fields the API returned. QPay brings the parent back through the API, which
 * forwards to #/topups/{pun}.
 */
export function goToQPay({ url, method = 'POST', fields = {} }) {
  const form = document.createElement('form')
  form.method = method
  form.action = url
  form.style.display = 'none'
  for (const [name, value] of Object.entries(fields)) {
    const input = document.createElement('input')
    input.type = 'hidden'
    input.name = name
    input.value = value ?? ''
    form.appendChild(input)
  }
  document.body.appendChild(form)
  form.submit()
}
