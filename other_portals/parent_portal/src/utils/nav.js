/**
 * Go back like the app bar does: real history when the parent came from inside
 * the portal (keeps tabs, months and scroll), otherwise [fallback].
 */
export function goBackOr(router, fallback) {
  if (window.history.state?.back) router.back()
  else router.replace(fallback)
}
