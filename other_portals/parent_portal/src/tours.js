/**
 * The "Show me how" tours — every word a parent reads in them lives here, so the
 * school can reword a tip without touching a component.
 *
 * One short tour per page, keyed by route name. It plays the first time a parent
 * lands on that page, and again from the "?" button. Each step points at an
 * element marked `data-tour="…"` in the page; a step whose element is not on the
 * screen (feature switched off, nothing to show yet, phone vs computer layout) is
 * skipped quietly. `target: null` is a centred card with nothing highlighted.
 *
 * `title` / `text` may be functions of the context `{ desktop, query, first }`
 * (`first` = the open child's first name, or "your child"; `First` is the same
 * for the start of a sentence).
 * `only: 'phone' | 'desktop'` limits a step to one layout.
 *
 * Raise TOUR_VERSION after a redesign to show every tour again once.
 */
export const TOUR_VERSION = 1

const help = {
  target: 'help',
  title: 'Help is always here',
  text: 'Tap the question mark at the top whenever you want to see these tips again.',
}

export const tours = {
  home: [
    {
      target: null,
      icon: 'fa-smile-o',
      title: 'Welcome to the Parent Portal',
      text: 'Here you can add money to your child’s canteen card, order meals ahead and see everything they bought. Here’s a quick look around — it takes less than a minute.',
    },
    {
      target: 'cards',
      title: 'Your children’s cards',
      text: ({ desktop }) =>
        desktop
          ? 'Each card is one child, with the money left on it. Click a card to open it.'
          : 'Each card is one child, with the money left on it. Tap a card to open it.',
    },
    {
      target: 'overview',
      only: 'desktop',
      title: 'Everything at a glance',
      text: 'The total on all cards, how many cards are working, and which ones need money added.',
    },
    {
      target: 'children',
      only: 'desktop',
      title: 'Top up or open',
      text: '“Top up” adds money to that card. “Open” shows the bills, the statement and meals.',
    },
    {
      target: 'account',
      title: 'Your account',
      text: 'Your details, changing your password and signing out are all in here.',
    },
    help,
  ],

  student: [
    {
      target: 'balance',
      title: ({ First }) => `${First}’s card`,
      text: 'This is the money on the card right now. It goes down each time your child pays at the canteen.',
    },
    {
      target: 'topup',
      title: 'Add money',
      text: 'Tap “Top up card” to add money with your bank card. It shows on the card as soon as the payment goes through.',
    },
    {
      target: 'preorder',
      title: 'Order meals ahead',
      text: 'Tap “Pre-Order” to choose meals for coming school days — one day at a time, or every week.',
    },
    {
      target: 'bills',
      only: 'phone',
      title: 'Bills',
      text: 'Every purchase at the canteen, with the receipt for each one.',
    },
    {
      target: 'statement',
      only: 'phone',
      title: 'Statement',
      text: 'Money added and money spent, month by month — like a bank statement for the card.',
    },
    {
      target: 'activity-tabs',
      only: 'desktop',
      title: 'Bills and statement',
      text: '“Bills” lists every purchase with its receipt. “Statement” shows money added and spent, month by month.',
    },
    {
      target: 'month',
      only: 'desktop',
      title: 'Pick a month',
      text: 'Use the arrows to look at an earlier month.',
    },
    {
      target: 'lost',
      title: 'Lost the card?',
      text: 'Block it here straight away so nobody else can use it. The money stays safe — the school office can switch it back on or give a new card.',
    },
  ],

  topup: [
    {
      target: 'amount',
      title: ({ query }) => (query.for === 'pre-order' ? 'The amount to pay' : 'How much to add'),
      text: ({ query }) =>
        query.for === 'pre-order'
          ? 'This is the exact price of the meal order. Paying it books the meal.'
          : 'Tap one of the quick amounts, or type your own.',
    },
    {
      target: 'paywith',
      title: 'Your bank card',
      text: 'Choose the type of bank card you will pay with.',
    },
    {
      target: 'pay',
      title: 'Pay safely',
      text: 'Pressing Pay opens your bank’s secure page. Finish the payment there and you come back here to see that it worked. Please don’t close the page half-way.',
    },
  ],

  'pre-orders': [
    {
      target: null,
      icon: 'fa-cutlery',
      title: 'Canteen meals',
      text: ({ first }) => `Order ${first}’s meals before the day. The meal is paid when you order it, and ready for your child at the canteen.`,
    },
    {
      target: 'kid-switch',
      title: 'More than one child?',
      text: 'Tap a name here to order for another child.',
    },
    {
      target: 'weekly',
      title: 'Order every week',
      text: 'Choose the meals and the days once, and they are ordered for you each week. You can pause it any time.',
    },
    {
      target: 'days',
      title: 'Or choose a single day',
      text: ({ desktop }) => `${desktop ? 'Click' : 'Tap'} a day to order for that day only, or to change what is already ordered.`,
    },
    {
      target: 'cutoff',
      title: 'Order before the time',
      text: 'Each day closes at this time. After that the day can’t be ordered, changed or cancelled.',
    },
    {
      target: 'paid',
      title: 'Paid orders',
      text: 'Changed your mind? Cancel before the day closes and the money goes back on the card.',
    },
  ],

  'pre-order-day': [
    {
      target: 'meals',
      title: 'Choose the meals',
      text: ({ desktop }) => `${desktop ? 'Click' : 'Tap'} a meal to add it. Tap again to add one more.`,
    },
    {
      target: 'meal-search',
      title: 'Find a meal quickly',
      text: 'Search by name, or tap a group to see only those meals.',
    },
    {
      target: 'cart',
      title: 'Your order',
      text: 'What you picked and the total. Use + and − to change how many.',
    },
    {
      target: 'order-bar',
      title: 'Pay for the order',
      text: ({ first }) =>
        `Once you’ve added meals, choose how to pay — from ${first}’s card or by bank card — then press Pay. “Save” keeps the order without paying for now.`,
    },
  ],

  'pre-order-weekly': [
    {
      target: 'meals',
      title: 'Choose the meals',
      text: ({ desktop }) => `${desktop ? 'Click' : 'Tap'} a meal to add it. Tap again to add one more.`,
    },
    {
      target: 'weekdays',
      title: 'Choose the days',
      text: 'Tap the school days the meal should be ordered on.',
    },
    {
      target: 'cart',
      title: 'Your order',
      text: 'What you picked and the price for one day. Use + and − to change how many.',
    },
    {
      target: 'order-bar',
      title: 'Save the weekly order',
      text: ({ first }) => `Each day is paid from ${first}’s card when that day’s orders close, so keep some money on the card.`,
    },
  ],

  'student-bills': [
    {
      target: 'kid-switch',
      title: 'Choose the child',
      text: 'Tap a name to see another child’s bills.',
    },
    {
      target: 'month',
      title: 'Pick a month',
      text: 'Use the arrows to look at an earlier month.',
    },
    {
      target: 'activity-list',
      title: 'Every purchase',
      text: 'Tap a bill to see exactly what was bought and what it cost.',
    },
  ],

  'student-statement': [
    {
      target: 'kid-switch',
      title: 'Choose the child',
      text: 'Tap a name to see another child’s statement.',
    },
    {
      target: 'month',
      title: 'Pick a month',
      text: 'Use the arrows to look at an earlier month.',
    },
    {
      target: 'activity-list',
      title: 'Money in and out',
      text: 'Money added to the card shows in green with a +, money spent at the canteen with a −. The balance after each line is on the right.',
    },
  ],

  bill: [
    {
      target: 'bill',
      title: 'The receipt',
      text: 'What was bought, the price of each item and how it was paid — the same as the canteen’s printed receipt.',
    },
  ],
}

/** The words on the tour card's own buttons. */
export const tourLabels = {
  next: 'Next',
  back: 'Back',
  done: 'Got it',
  skip: 'Skip tips',
  help: 'Show me how',
  of: (index, total) => `${index} of ${total}`,
}
