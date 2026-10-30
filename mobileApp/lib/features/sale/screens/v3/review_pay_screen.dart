import 'dart:async';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:invo/features/sale/logic/sale_ops_cubit/sale_ops_cubit.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/features/settings/logic/pos_settings_cubit/pos_settings_cubit.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/qloud_logo.dart';
import 'package:invo/shared/widgets/receipt_printer.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';
import 'package:invo/features/sale/widgets/v3/custom_payment_sheet.dart';
import 'package:invo/features/sale/widgets/v3/cash_tender_card.dart';
import 'package:invo/shared/widgets/astra_snack.dart';
import 'package:invo/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/features/student_card/widgets/tap_card_sheet.dart';

class ReviewPayScreen extends StatefulWidget {
  const ReviewPayScreen({super.key});
  @override
  State<ReviewPayScreen> createState() => _ReviewPayScreenState();
}

class _ReviewPayScreenState extends State<ReviewPayScreen> {
  /// Repository access for this flow (§10).
  final _ops = SaleOpsCubit();
  bool _busy = false;
  // The ghost button beside the primary call to action — "Save Draft" on a new
  // ticket, "Update Draft" when a parked draft is open. Kept separate so the
  // spinner appears on the button that was actually pressed.
  bool _busyDraft = false;
  List<PaymentMethod> _methods = [];

  /// Cash handed over the counter, as keyed on the tender pad. Display only —
  /// see [CashTenderPanel]: cash settles the ticket in full whatever is entered
  /// here, so this never reaches the payload. Held on the screen (not inside the
  /// panel) because the header and the checkout button both name the change.
  double? _tendered;

  /// The sale note. Owned by the screen (not the note sheet) so it is disposed
  /// with it; seeded from the cart so an edited sale or draft shows its note.
  late final TextEditingController _noteCtl;
  final FocusNode _noteFocus = FocusNode();

  @override
  void initState() {
    super.initState();
    _noteCtl = TextEditingController(text: context.read<CartCubit>().notes);
    _loadMethods();
  }

  @override
  void dispose() {
    _noteCtl.dispose();
    _noteFocus.dispose();
    super.dispose();
  }

  /// Best-effort fetch of the configured payment methods for the custom split.
  /// A failure here is non-fatal — Cash/Card/Credit still work without it.
  Future<void> _loadMethods() async {
    try {
      final methods = await _ops.paymentMethods();
      if (mounted) setState(() => _methods = methods);
    } catch (_) {
      // Leave _methods empty; the custom sheet surfaces the "none configured" state.
    }
  }

  /// Commits the ticket: a new sale is created, an open sale is written back.
  ///
  /// [status] moves the sale's status as part of the same write — the checkout
  /// bar sends `completed` to finish a parked draft, which is what makes the
  /// server post its stock movement and journal entry. Omitted, the sale keeps
  /// the status it already has.
  ///
  /// [secondary] spins the ghost button rather than the primary one, so
  /// "Update Draft" reports its own progress next to "Complete".
  Future<void> _charge({String? status, bool secondary = false}) async {
    // The buttons are disabled while a save is in flight, but "disabled" is a
    // build-time decision: two thumbs landing in the same frame both read the
    // state as it was. Completing a sale posts stock and a journal entry, so the
    // guard is repeated here, where it is checked against the live flags.
    if (_busy || _busyDraft) return;
    final cart = context.read<CartCubit>();
    final editingId = cart.editingSaleId;
    if (cart.state.paysByStudentCard &&
        serviceLocator.isRegistered<ConnectivityCubit>() &&
        serviceLocator<ConnectivityCubit>().state.isOffline) {
      _error('Student card payments need a connection. Reconnect, or take cash.');
      return;
    }
    setState(() {
      if (secondary) {
        _busyDraft = true;
      } else {
        _busy = true;
      }
    });
    final pendingUuid = cart.state.editingPendingUuid;
    Sale? saved;
    try {
      if (pendingUuid != null) {
        // A sale still in the queue has no server record to patch — the write
        // goes to the outbox row it is already captured in, under the same key.
        saved = await _saveQueuedCorrection(cart, pendingUuid);
      } else if (editingId == null) {
        // One call mints the idempotency key and both shapes of the ticket, so
        // what is queued offline is exactly what would have been posted.
        final ticket = cart.beginCharge();
        // A student card sale is never queued: the balance and whether the card
        // is blocked are only known to the server, so an unreachable server is
        // an error the cashier sees, not a sale charged blind.
        saved = cart.state.paysByStudentCard
            ? await _ops.createSale(ticket.payload)
            : await _ops.createSale(ticket.payload, offlineSale: ticket.offlineSale);
      } else {
        // Editing a committed sale needs a server id on both ends, so it stays
        // online-only.
        saved = await _ops.updateSale(editingId, cart.toPayload(status: status));
      }
      if (saved != null) cart.clear();
    } on ApiException catch (e) {
      _error(e.message);
    } catch (e) {
      _error(switch ((pendingUuid, editingId, status)) {
        (String _, _, _) => 'Could not update the held sale. Please try again.',
        (_, null, _) => 'Could not save the sale. Please try again.',
        (_, _, 'completed') => 'Could not complete the sale. Please try again.',
        _ => 'Could not update the sale. Please try again.',
      });
    }
    if (!mounted) return;
    // Everything past this point runs on a committed sale — kept out of the
    // try above so a printing hiccup can never be reported as a failed charge.
    if (saved == null) {
      setState(() {
        _busy = false;
        _busyDraft = false;
      });
      return;
    }
    await _afterCharge(saved);
  }

  /// Write a correction back onto the outbox row it belongs to.
  ///
  /// Returns the corrected ticket as a `pending` sale so the invoice screen and
  /// the receipt render it exactly as they did when it was first captured, or null
  /// when the row has already synced — at which point the honest thing is to say
  /// so rather than silently create a second sale.
  Future<Sale?> _saveQueuedCorrection(CartCubit cart, String pendingUuid) async {
    if (!serviceLocator.isRegistered<OfflineSyncCubit>()) {
      _error('Held sales are unavailable on this device.');
      return null;
    }
    final ticket = cart.beginCharge();
    final applied = await serviceLocator<OfflineSyncCubit>().editPending(
      pendingUuid,
      payload: ticket.payload,
      saleJson: ticket.offlineSale,
      soldBefore: cart.state.editingPendingSold,
    );
    if (!applied) {
      _error('This sale has already synced — open it from Sales to edit it.');
      return null;
    }
    // The provisional reference and the Office Bill number already printed are
    // unchanged, so they are read back off the row rather than reprinted as
    // something new.
    final row = await serviceLocator<OutboxRepository>().byUuid(pendingUuid);
    return Sale.fromJson({
      ...(row?.saleJson ?? ticket.offlineSale),
      'invoice_no': row?.displayRef ?? '',
    });
  }

  /// What the primary call to action does on this ticket. A parked draft is
  /// finished off; anything else is saved as it stands.
  void _submit() {
    final completing = context.read<CartCubit>().isEditingDraft;
    _charge(status: completing ? 'completed' : null);
  }

  /// Everything that happens once the sale is committed: print it on the till's
  /// own printer, then either hand the terminal back to the next cashier
  /// (sign-out mode), start a fresh ticket, or stop on the invoice.
  ///
  /// Printing is best-effort — the sale is already saved, so a printer that's
  /// off costs a toast and the manual Print button, never the sale.
  Future<void> _afterCharge(Sale sale) async {
    final print = context.read<PrintSettingsCubit>();
    final pos = context.read<PosSettingsCubit>();
    final auth = context.read<AuthCubit>();
    // Captured before we navigate: the router and the app-level messenger
    // outlive this screen, `context` does not.
    final router = GoRouter.of(context);
    final snack = AstraSnack.capture(context);
    final invoiceNo = sale.invoiceNo.isEmpty ? '#${sale.id}' : sale.invoiceNo;
    // A queued sale is captured, not committed. Say so everywhere the committed
    // wording would otherwise appear, so nobody goes looking for it on the web.
    final savedWord = sale.pending ? 'held offline' : 'saved';

    var printed = false;
    var printFailed = false;
    if (print.autoPrint) {
      final result = await printReceipt(sale, print.snapshot, target: print.printer);
      printed = result == ReceiptPrintResult.printed;
      // A cancelled dialog was a deliberate "don't print" — not a failure.
      printFailed = !result.ok && result != ReceiptPrintResult.cancelled;
    }

    // Shared till: lock rather than sign out, so the next sale can't be rung
    // under this cashier's name but coming back is a local PIN check instead of
    // a fresh login. The router's auth redirect does the navigating, so we only
    // announce what happened — printing has already finished by this point.
    if (pos.lockAfterSale) {
      if (printFailed) {
        snack.error('$invoiceNo $savedWord — couldn\'t print');
      } else {
        snack.success('$invoiceNo $savedWord');
      }
      await auth.lock();
      return;
    }

    // Printed silently and set to move on: the ticket is done, so the next
    // customer can be rung up with no further taps. The invoice stays one tap
    // away in the snackbar.
    if (printed && print.skipInvoice) {
      router.go(Routes.sale);
      snack.success(
        '$invoiceNo printed',
        duration: const Duration(seconds: 4),
        action: SnackBarAction(
          label: 'View',
          textColor: Colors.white,
          onPressed: () => router.push(Routes.invoice, extra: sale),
        ),
      );
      return;
    }

    unawaited(router.pushReplacement(Routes.invoice, extra: sale));
    if (printFailed) {
      snack.error('Couldn\'t reach the printer — tap Print to try again.');
    }
  }

  /// Parks the sale without completing it — no stock movement or journal entry
  /// is posted until the draft is later reopened and charged.
  ///
  /// Held on the device when the server can't be reached, like a completed sale:
  /// a draft is usually a customer standing at the counter deciding, and losing it
  /// to a dropped connection means retyping the whole ticket in front of them. It
  /// carries an idempotency key for the same reason a sale does — the replay must
  /// not park two copies — but it takes no money and moves no stock, so nothing
  /// downstream treats it as takings.
  Future<void> _saveDraft() async {
    if (_busy || _busyDraft) return; // same-frame double tap — see _charge
    final cart = context.read<CartCubit>();
    setState(() => _busyDraft = true);
    try {
      final ticket = cart.beginCharge(status: 'draft');
      final saved = await _ops.createSale(ticket.payload, offlineSale: ticket.offlineSale);
      cart.clear();
      if (mounted) {
        AstraSnack.success(context, saved.pending ? 'Draft held on this device' : 'Saved as draft',
            duration: const Duration(milliseconds: 900));
        await Future.delayed(const Duration(milliseconds: 500));
        if (mounted) context.go(Routes.sale);
      }
    } on ApiException catch (e) {
      _error(e.message);
    } catch (e) {
      _error('Could not save the draft. Please try again.');
    }
    if (mounted) setState(() => _busyDraft = false);
  }

  Future<void> _openCustom() async {
    final cart = context.read<CartCubit>();
    if (_methods.isEmpty) {
      await _loadMethods();
      if (_methods.isEmpty) {
        _error('No payment methods are configured for this business.');
        return;
      }
    }
    if (!mounted) return;
    final cardMethodId = cart.student?.cardMethodId;
    final result = await showCustomPaymentSheet(
      context,
      total: cart.total,
      // A tapped student card can take part of a split (card + cash). Without
      // one the wallet has nothing to spend, so it stays out of the list — and
      // with one it is listed once, whether or not Payment Methods has it too.
      methods: [
        if (cardMethodId != null) PaymentMethod(id: cardMethodId, name: PayMode.studentCard.label),
        ..._methods.where((m) => !_isWallet(m, cart)),
      ],
      initial: cart.customPayments,
    );
    if (result != null && result.isNotEmpty) {
      cart.setCustomPayments(result);
    }
  }

  /// Card pays what it can spend, cash the rest.
  Future<void> _splitCardAndCash(CartCubit cart) async {
    final student = cart.student;
    final cardMethodId = student?.cardMethodId;
    if (student == null || cardMethodId == null) return;
    if (_methods.isEmpty) await _loadMethods();
    final cash = _methods.where((m) => m.name.toLowerCase().contains('cash')).firstOrNull;
    if (cash == null) {
      _error('No Cash payment method is configured — use Custom to split.');
      return;
    }
    final onCard = student.available.clamp(0, cart.total).toDouble();
    cart.setCustomPayments([
      if (onCard > 0) CustomPayment(methodId: cardMethodId, methodName: PayMode.studentCard.label, amount: round2(onCard)),
      CustomPayment(methodId: cash.id, methodName: cash.name, amount: round2(cart.total - onCard)),
    ]);
    setState(() => _tendered = null);
  }

  void _error(String m) {
    if (!mounted) return;
    AstraSnack.error(context, m);
  }


  @override
  Widget build(BuildContext context) {
    final cart = context.watch<CartCubit>();
    final settled = cart.balance.abs() < 0.001;

    if (context.isTablet) return _tabletScaffold(cart, settled);
    return Scaffold(
      // Nothing on this screen takes typing — the note and the split are
      // sheets that lift themselves over the keyboard. Resizing for it only
      // crushed the terminal underneath them.
      resizeToAvoidBottomInset: false,
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(icon: Icons.chevron_left, onTap: () => context.pop()),
              titleWidget: Text(_headline(cart),
                  maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 20, color: Colors.white)),
              trailing: _whoChip(cart),
              bottom: MaxWidthBox(maxWidth: 560, child: _terminal(cart)),
            ),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 560,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
                  child: _fitted(_payPanel(cart, phone: true)),
                ),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: MaxWidthBox(
          maxWidth: 560,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(14, 10, 14, 12),
            child: _checkoutBar(cart, settled),
          ),
        ),
      ),
    );
  }

  /// The pay panel is laid out in whatever height is left under the header —
  /// no scrolling. Only a screen too short to hold it at all (a small phone on
  /// a large text size) gets a scroll view, rather than an overflow.
  Widget _fitted(Widget child) => LayoutBuilder(
        builder: (context, c) => c.maxHeight >= 240
            ? child
            : SingleChildScrollView(child: SizedBox(height: 240, child: child)),
      );

  // ---- The terminal: amount due, how it's paid, and where that leaves it ----

  /// The blue head of the screen. Everything the cashier decides about money
  /// sits on it — the amount, the method, and one status line that says what
  /// happens on Charge — so the panel underneath is only the work of the mode.
  ///
  /// On a [tablet] the amount due is the foot of the receipt on the left, so
  /// the rail drops it and gives the height to the pay panel; a "PAY WITH"
  /// caption heads it instead. On a phone the header bar above already names
  /// the screen and the customer.
  Widget _terminal(CartCubit cart, {bool tablet = false}) {
    final symbol = Money.symbol.trim();
    return Column(
      children: [
        if (tablet) ...[
          Row(
            children: [
              Text('PAY WITH', style: ui(size: 10, weight: FontWeight.w800, color: Colors.white70, letterSpacing: 0.9)),
              const Spacer(),
              _whoChip(cart),
            ],
          ),
          const SizedBox(height: 10),
        ] else ...[
          FittedBox(
            fit: BoxFit.scaleDown,
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (symbol.isNotEmpty) ...[
                  Text(symbol, style: ui(size: 15, weight: FontWeight.w800, color: Colors.white70)),
                  const SizedBox(width: 7),
                ],
                Text(Money.plain(cart.total), style: serif(size: 40, color: Colors.white)),
              ],
            ),
          ),
          Text(_ticketFacts(cart),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(size: 11, weight: FontWeight.w600, color: Colors.white70)),
          const SizedBox(height: 11),
        ],
        _methodGrid(cart, tablet: tablet),
        const SizedBox(height: 9),
        _heroStatus(cart),
      ],
    );
  }

  /// "4 items · 20.00 off · incl. 3.00 tax" — the line under the amount due.
  String _ticketFacts(CartCubit cart) {
    final n = cart.lines.length;
    return [
      '$n item${n == 1 ? '' : 's'}',
      if (cart.totalDiscount > 0) '${Money.plain(cart.totalDiscount)} off',
      if (cart.taxTotal > 0) 'incl. ${Money.plain(cart.taxTotal)} tax',
    ].join(' · ');
  }

  /// Who the ticket is for, as a glass chip on the blue — a tapped student card
  /// shows its contactless mark.
  Widget _whoChip(CartCubit cart) {
    final tapped = cart.student != null;
    return Container(
      constraints: const BoxConstraints(maxWidth: 150),
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.16), borderRadius: BorderRadius.circular(999)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(tapped ? Icons.contactless_outlined : Icons.person_outline_rounded,
              size: 13, color: tapped ? _mint : Colors.white),
          const SizedBox(width: 4),
          Flexible(
            child: Text(cart.customerName,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: ui(size: 10.5, weight: FontWeight.w800, color: Colors.white)),
          ),
        ],
      ),
    );
  }

  /// The success green lifted to read on the blue head.
  static final Color _mint = Color.lerp(AstraPalette.success, Colors.white, 0.6)!;

  // ---- Payment methods ------------------------------------------------------

  /// The icon for a configured payment button, from its system slug or name.
  IconData _buttonIcon(PaymentMethod button) {
    // The icon picked on the web — same keys as `posPaymentButtonIcons()`.
    final picked = switch (button.icon) {
      'money' => Icons.payments_outlined,
      'credit-card' => Icons.credit_card,
      'university' => Icons.account_balance_outlined,
      'exchange' => Icons.swap_horiz,
      'bolt' => Icons.bolt,
      'mobile' => Icons.smartphone,
      'qrcode' => Icons.qr_code_2,
      'google-wallet' => Icons.account_balance_wallet_outlined,
      'paper-plane' => Icons.send_outlined,
      'gift' => Icons.card_giftcard,
      _ => null,
    };
    if (picked != null) return picked;
    final name = '${button.slug ?? ''} ${button.name}'.toLowerCase();
    if (name.contains('cash')) return Icons.payments_outlined;
    if (name.contains('bank') || name.contains('transfer')) return Icons.account_balance_outlined;
    return Icons.credit_card;
  }

  /// Whether [m] is the Student Wallet account — by id once a tapped card has
  /// named it, otherwise by the account's locked name.
  bool _isWallet(PaymentMethod m, CartCubit cart) =>
      m.id == cart.student?.cardMethodId ||
      m.name.trim().toLowerCase() == PayMode.studentCard.label.toLowerCase();

  /// The wallet is offered once a student card has been tapped (on New Sale or
  /// here) — there is nothing to spend before that. A ticket already paying by
  /// wallet keeps its tile, so the selection can never vanish from under the
  /// cashier.
  bool _offersWallet(CartCubit cart) => cart.student != null || cart.payMode == PayMode.studentCard;

  /// The wallet spends a tapped card. With none on the ticket yet, picking it
  /// asks for the tap — the student then becomes the customer, as on New Sale.
  Future<void> _selectWallet(CartCubit cart) async {
    if (cart.student == null) {
      final card = await showTapCardSheet(context);
      if (card == null || !mounted) return;
      cart.setStudent(card);
    } else {
      cart.setPayMode(PayMode.studentCard);
    }
    setState(() => _tendered = null);
  }

  /// The two configured POS Payment Buttons shown beside Cash and Card. Cash
  /// and Card are permanent tiles of their own, and the wallet has its own
  /// tile, so those buttons are skipped. A ticket already paid by a method
  /// past the first two keeps it on screen, in the second slot.
  List<PaymentMethod> _configuredButtons(CartCubit cart) {
    final shown = CartCubit.paymentButtons()
        .where((b) => CartCubit.payFor(b).mode == PayMode.method && !_isWallet(b, cart))
        .take(2)
        .toList();
    final current = cart.payMethod;
    if (cart.payMode == PayMode.method && current != null && !shown.any((b) => b.id == current.id)) {
      return [...shown.take(1), current];
    }
    return shown;
  }

  void _pickMode(CartCubit cart, PayMode mode) {
    cart.setPayMode(mode);
    // The tender pad unmounts with the mode, so what it reported would
    // otherwise linger on the checkout button of a card sale.
    if (mode != PayMode.cash) setState(() => _tendered = null);
  }

  /// Two rows on four columns. The first is how most tickets are paid — Cash,
  /// Card and the two methods configured on the web; the second the ones that
  /// change the shape of the payment — the student's wallet (once a card is
  /// tapped), Credit, and Split.
  Widget _methodGrid(CartCubit cart, {required bool tablet}) {
    final configured = _configuredButtons(cart);
    final tall = tablet ? 52.0 : 46.0;
    final short = tablet ? 38.0 : 32.0;
    final splits = cart.payMode == PayMode.custom ? cart.customPayments.length : 0;
    const gap = 5.0;

    final everyday = <Widget>[
      _MethodTile(
        icon: Icons.payments_outlined,
        label: 'Cash',
        height: tall,
        active: cart.payMode == PayMode.cash,
        onTap: () => _pickMode(cart, PayMode.cash),
      ),
      _MethodTile(
        icon: Icons.credit_card,
        label: 'Card',
        height: tall,
        active: cart.payMode == PayMode.card,
        onTap: () => _pickMode(cart, PayMode.card),
      ),
      for (final button in configured)
        _MethodTile(
          icon: _buttonIcon(button),
          label: button.name,
          height: tall,
          configured: true,
          active: cart.payMode == PayMode.method && cart.payMethod?.id == button.id,
          onTap: () {
            cart.setPayButton(button);
            setState(() => _tendered = null);
          },
        ),
    ];
    final wallet = _MethodTile(
      icon: Icons.school_outlined,
      label: 'Wallet',
      height: short,
      minor: true,
      wallet: true,
      active: cart.payMode == PayMode.studentCard,
      onTap: () => _selectWallet(cart),
    );
    final credit = _MethodTile(
      icon: Icons.description_outlined,
      label: PayMode.credit.label,
      height: short,
      minor: true,
      active: cart.payMode == PayMode.credit,
      onTap: () => _pickMode(cart, PayMode.credit),
    );
    final split = _MethodTile(
      icon: Icons.call_split_rounded,
      label: splits > 0 ? '$splits ways' : 'Split',
      height: short,
      minor: true,
      active: cart.payMode == PayMode.custom,
      onTap: _openCustom,
    );

    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(color: Colors.black.withValues(alpha: 0.16), borderRadius: BorderRadius.circular(14)),
      child: LayoutBuilder(
        builder: (context, c) {
          final column = (c.maxWidth - gap * 3) / 4;
          final double wide = column * 2 + gap;
          final lower = _offersWallet(cart)
              ? [(wallet, wide), (credit, column), (split, column)]
              : [(credit, wide), (split, wide)];
          return Column(
            children: [
              Row(
                children: [
                  for (final (i, tile) in everyday.indexed) ...[
                    if (i > 0) const SizedBox(width: gap),
                    Expanded(child: tile),
                  ],
                ],
              ),
              const SizedBox(height: gap),
              Row(
                children: [
                  for (final (i, (tile, width)) in lower.indexed) ...[
                    if (i > 0) const SizedBox(width: gap),
                    SizedBox(width: width, child: tile),
                  ],
                ],
              ),
            ],
          );
        },
      ),
    );
  }

  /// Where the ticket stands, in one line on the blue: what was received and
  /// what goes back for cash, what stays owed for credit or a short split.
  Widget _heroStatus(CartCubit cart) {
    final (tone, icon, text, value) = _status(cart);
    final p = context.astra;
    final iconColor = switch (tone) {
      _Tone.ok => _mint,
      _Tone.warn => Color.lerp(p.warnText, Colors.white, 0.45)!,
      _Tone.bad => Color.lerp(AstraPalette.danger, Colors.white, 0.55)!,
      _Tone.info => Colors.white,
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
      decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(12)),
      child: Row(
        children: [
          Icon(icon, size: 17, color: iconColor),
          const SizedBox(width: 8),
          Expanded(
            child: Text(text,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: ui(size: 12, weight: FontWeight.w700, color: Colors.white)),
          ),
          if (value != null) ...[
            const SizedBox(width: 8),
            Text(value, style: ui(size: 15, weight: FontWeight.w800, color: Colors.white)),
          ],
        ],
      ),
    );
  }

  (_Tone, IconData, String, String?) _status(CartCubit cart) {
    final settledLine = 'Bal ${Money.plain(0)}';
    switch (cart.payMode) {
      case PayMode.cash:
        final tendered = _tendered;
        if (tendered == null) return (_Tone.ok, Icons.payments_outlined, 'Paid in cash · count the notes', settledLine);
        final change = round2(tendered - cart.total);
        if (change > 0) {
          return (_Tone.ok, Icons.south_west_rounded, 'Received ${Money.plain(tendered)} · give back', Money.plain(change));
        }
        if (change == 0) return (_Tone.ok, Icons.check_circle_rounded, 'Received ${Money.plain(tendered)} · no change due', null);
        return (_Tone.bad, Icons.warning_rounded, 'Received ${Money.plain(tendered)} · short by', Money.plain(-change));
      case PayMode.card:
        return (_Tone.ok, Icons.check_circle_rounded, 'Fully paid by card', settledLine);
      case PayMode.method:
        return (_Tone.ok, Icons.check_circle_rounded, 'Fully paid by ${cart.payMethod?.name ?? 'method'}', settledLine);
      case PayMode.credit:
        return (_Tone.warn, Icons.schedule_rounded, 'On account · due', Money.plain(cart.balance));
      case PayMode.studentCard:
        final spend = _walletSpend(cart);
        if (spend.short > 0.004) return (_Tone.bad, Icons.warning_rounded, 'Student card short by', Money.plain(spend.short));
        if (cart.student == null) return (_Tone.ok, Icons.check_circle_rounded, 'Paid by student card', null);
        return (_Tone.ok, Icons.check_circle_rounded, 'Paid by student card · left', Money.plain(spend.after));
      case PayMode.custom:
        final balance = cart.balance;
        if (cart.customPayments.isEmpty) return (_Tone.info, Icons.call_split_rounded, 'Split · add the methods', null);
        if (balance.abs() < 0.001) {
          final names = cart.customPayments.map((x) => x.methodName).join(' + ');
          return (_Tone.ok, Icons.check_circle_rounded, 'Fully paid · $names', settledLine);
        }
        if (balance > 0) return (_Tone.warn, Icons.schedule_rounded, 'Part paid · still due', Money.plain(balance));
        return (_Tone.warn, Icons.south_rounded, 'Paid over the total by', Money.plain(-balance));
    }
  }

  /// What the tapped card pays on this ticket, what it holds after, and how far
  /// what it pays is beyond what it may spend.
  ({double onCard, double after, double short}) _walletSpend(CartCubit cart) {
    final student = cart.student;
    if (student == null) return (onCard: 0, after: 0, short: 0);
    final onCard = switch (cart.payMode) {
      PayMode.studentCard => cart.total,
      PayMode.custom => round2(cart.customPayments
          .where((x) => x.methodId == student.cardMethodId)
          .fold(0.0, (a, x) => a + x.amount)),
      _ => 0.0,
    };
    return (onCard: onCard, after: round2(student.balance - onCard), short: round2(onCard - student.available));
  }

  // ---- The panel under the terminal: the work of the chosen mode -------------

  /// Cash gets the note pad; every other mode settles at a value the ticket or
  /// the split sheet decided, so it gets a prompt for what the cashier does.
  ///
  /// On a phone the panel ends in a tool row for the ticket and the note — on
  /// a tablet the ticket is the left pane and the note sits in its head.
  Widget _payPanel(CartCubit cart, {required bool phone}) {
    final tools = [
      if (phone) ...[
        PayToolButton(
          icon: Icons.receipt_long_outlined,
          label: '${cart.lines.length} item${cart.lines.length == 1 ? '' : 's'}',
          onTap: () => _openItems(cart),
        ),
        _noteTool(cart),
      ],
    ];
    final total = Money.of(cart.total);

    final Widget body = switch (cart.payMode) {
      PayMode.cash => CashTenderPanel(
          total: cart.total,
          onChanged: (v) => setState(() => _tendered = v),
          maxPadHeight: phone ? 230 : 250,
          tools: [if (phone) _noteTool(cart)],
        ),
      PayMode.card => _PayPrompt(
          tone: _Tone.info,
          icon: Icons.contactless_outlined,
          title: 'Tap or insert on the card machine',
          subtitle: 'Take $total on the terminal, then confirm below once it says Approved.',
        ),
      PayMode.method => _PayPrompt(
          tone: _Tone.ok,
          icon: cart.payMethod == null ? Icons.credit_card : _buttonIcon(cart.payMethod!),
          title: 'Paid by ${cart.payMethod?.name ?? 'this method'}',
          subtitle: '$total is posted to its configured account.',
        ),
      PayMode.credit => const _PayPrompt(
          tone: _Tone.warn,
          icon: Icons.description_outlined,
          title: 'Put the full amount on account',
          subtitle: 'Nothing is taken now. For a part payment, use Split.',
        ),
      PayMode.custom when cart.customPayments.isEmpty => _PayPrompt(
          tone: _Tone.info,
          icon: Icons.call_split_rounded,
          title: 'Split between methods',
          subtitle: 'Tap here to set how much each method pays.',
          onTap: _openCustom,
        ),
      PayMode.custom => _splitPanel(cart),
      PayMode.studentCard => _walletPanel(cart),
    };

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (cart.tipEnabled) ...[
          _tipSelector(cart),
          const SizedBox(height: 10),
        ],
        Expanded(child: body),
        // Cash builds its own tool row, with Reset and Exact in it.
        if (cart.payMode != PayMode.cash && tools.isNotEmpty) ...[
          const SizedBox(height: 10),
          Row(
            children: [
              for (final (i, tool) in tools.indexed) ...[
                if (i > 0) const SizedBox(width: 8),
                Expanded(child: tool),
              ],
            ],
          ),
        ],
      ],
    );
  }

  Widget _noteTool(CartCubit cart) => PayToolButton(
        icon: cart.notes.isEmpty ? Icons.edit_note_rounded : Icons.sticky_note_2_outlined,
        label: cart.notes.isEmpty ? 'Note' : 'Note added',
        lit: cart.notes.isNotEmpty,
        onTap: _openNote,
      );

  /// A split, line by line, with the way back into the split sheet.
  Widget _splitPanel(CartCubit cart) {
    final p = context.astra;
    final walletId = cart.student?.cardMethodId;
    final n = cart.customPayments.length;
    return Container(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
      decoration: BoxDecoration(
        color: p.card,
        borderRadius: BorderRadius.circular(16),
        boxShadow: context.astraTheme.softShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Text('SPLIT · $n METHOD${n == 1 ? '' : 'S'}',
                  style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
              const Spacer(),
              GestureDetector(
                onTap: _openCustom,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(999)),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.edit_rounded, size: 12, color: p.primary),
                      const SizedBox(width: 5),
                      Text('Edit split', style: ui(size: 10.5, weight: FontWeight.w800, color: p.primary)),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 9),
          Expanded(
            child: ListView.separated(
              padding: EdgeInsets.zero,
              itemCount: n,
              separatorBuilder: (_, __) => const SizedBox(height: 7),
              itemBuilder: (_, i) {
                final row = cart.customPayments[i];
                final icon = row.methodId == walletId
                    ? Icons.school_outlined
                    : row.methodName.toLowerCase().contains('cash')
                        ? Icons.payments_outlined
                        : Icons.credit_card;
                return Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
                  decoration: BoxDecoration(
                    color: p.isDark ? Colors.white.withValues(alpha: 0.06) : Colors.black.withValues(alpha: 0.04),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      Icon(icon, size: 17, color: p.primary),
                      const SizedBox(width: 9),
                      Expanded(
                        child: Text(row.methodName,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
                      ),
                      Text(Money.plain(row.amount), style: ui(size: 15, weight: FontWeight.w800, color: p.ink)),
                    ],
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  /// The tapped card, and — when it can't cover the ticket — the card + cash
  /// split that can.
  Widget _walletPanel(CartCubit cart) {
    final p = context.astra;
    final student = cart.student;
    if (student == null) {
      return const _PayPrompt(
        tone: _Tone.ok,
        icon: Icons.school_outlined,
        title: 'Paid by student card',
        subtitle: 'This sale was paid from the student wallet.',
      );
    }
    final spend = _walletSpend(cart);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _StudentWalletCard(card: student, after: spend.onCard > 0 ? spend.after : null),
        if (spend.short > 0.004) ...[
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.fromLTRB(12, 6, 6, 6),
            decoration: BoxDecoration(color: p.warnTint, borderRadius: BorderRadius.circular(12)),
            child: Row(
              children: [
                Icon(Icons.warning_rounded, size: 16, color: p.warnText),
                const SizedBox(width: 8),
                Expanded(
                  child: Text('Card is ${Money.of(spend.short)} short',
                      style: ui(size: 12, weight: FontWeight.w800, color: p.warnText)),
                ),
                GestureDetector(
                  onTap: () => _splitCardAndCash(cart),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                    decoration: BoxDecoration(
                      color: p.card,
                      borderRadius: BorderRadius.circular(9),
                      boxShadow: context.astraTheme.softShadow,
                    ),
                    child: Text('Card + cash', style: ui(size: 11, weight: FontWeight.w800, color: p.primary)),
                  ),
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }

  /// A compact tip row — four chips on one line.
  Widget _tipSelector(CartCubit cart) {
    final p = context.astra;
    final tips = [0.0, 10.0, 15.0, 20.0];
    return Row(
      children: [
        Text('TIP', style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
        const SizedBox(width: 10),
        for (final tip in tips) ...[
          Expanded(
            child: GestureDetector(
              onTap: () => cart.setTip(tip),
              child: Container(
                height: 32,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  gradient: cart.tipPercent == tip ? p.primaryGradient : null,
                  color: cart.tipPercent == tip ? null : p.card,
                  borderRadius: BorderRadius.circular(10),
                  boxShadow: context.astraTheme.softShadow,
                ),
                child: Text(tip == 0 ? 'None' : '${tip.toStringAsFixed(0)}%',
                    style: ui(size: 11.5, weight: FontWeight.w800, color: cart.tipPercent == tip ? Colors.white : p.textSecondary)),
              ),
            ),
          ),
          if (tip != tips.last) const SizedBox(width: 6),
        ],
      ],
    );
  }

  // ---- Ticket and note sheets ------------------------------------------------

  /// The itemised receipt, on a phone — the ticket was reviewed on the cart
  /// screen, so here it is one tap away rather than taking the pay controls'
  /// height.
  Future<void> _openItems(CartCubit cart) {
    final height = MediaQuery.of(context).size.height;
    return showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      barrierColor: Colors.black.withValues(alpha: 0.45),
      builder: (_) => SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
          child: SizedBox(
            height: (300 + cart.lines.length * 44.0).clamp(0, height * 0.75).toDouble(),
            child: _paperTicket(cart),
          ),
        ),
      ),
    );
  }

  /// An optional free-text note saved with the sale ("deliver after 6pm",
  /// "gift wrap"). The screen owns the field's controller and focus node, so
  /// the sheet can close and reopen on the same text.
  Future<void> _openNote() {
    final cart = context.read<CartCubit>();
    return showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      barrierColor: Colors.black.withValues(alpha: 0.45),
      builder: (_) => _NoteSheet(controller: _noteCtl, focusNode: _noteFocus, onChanged: cart.setNotes),
    );
  }

  /// What this screen is doing, so a parked draft doesn't look like an ordinary
  /// edit — the buttons underneath differ.
  String _headline(CartCubit cart) => cart.isEditingDraft
      ? 'Complete Draft'
      : cart.isEditing
          ? 'Edit sale'
          : 'Review & Pay';

  String _staff(CartCubit cart) => cart.stylistName.isEmpty ? 'Me' : cart.stylistName;

  /// The change line under the primary button — only when there is money to
  /// actually hand back.
  String? _changeHint(CartCubit cart) {
    final tendered = _tendered;
    if (cart.payMode != PayMode.cash || tendered == null) return null;
    final change = double.parse((tendered - cart.total).toStringAsFixed(2));
    return change > 0 ? 'Change ${Money.of(change)}' : null;
  }

  // ---- Checkout call to action ---------------------------------------------

  /// The bottom bar, in its three shapes:
  ///
  /// * a new ticket — park it as a draft, or charge it;
  /// * a parked draft — save the changes and leave it parked, or complete it
  ///   (which is what posts the stock movement and the journal entry);
  /// * an already-completed sale — save the changes, nothing else to decide.
  ///
  /// Shared by the phone and tablet layouts so the two can't drift apart.
  Widget _checkoutBar(CartCubit cart, bool settled) {
    final blocked = cart.isEmpty || _busy || _busyDraft;

    if (cart.isEditingDraft) {
      return Row(
        children: [
          _ghostButton(
            label: 'Update Draft',
            onTap: blocked ? null : () => _charge(secondary: true),
            busy: _busyDraft,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: AstraButton(
              label: settled ? 'Complete ${Money.of(cart.total)}' : 'Complete Anyway',
              subtitle: _changeHint(cart),
              gold: true,
              busy: _busy,
              onTap: blocked ? null : _submit,
            ),
          ),
        ],
      );
    }

    if (cart.isEditing) {
      return AstraButton(
        label: 'Update ${Money.of(cart.total)}',
        subtitle: _changeHint(cart),
        gold: true,
        busy: _busy,
        onTap: blocked ? null : _submit,
      );
    }

    return Row(
      children: [
        _ghostButton(
          label: 'Save Draft',
          onTap: blocked ? null : _saveDraft,
          busy: _busyDraft,
        ),
        const SizedBox(width: 10),
        Expanded(
          child: AstraButton(
            label: settled ? 'Charge ${Money.of(cart.total)}' : 'Submit Anyway',
            subtitle: _changeHint(cart),
            gold: true,
            busy: _busy,
            onTap: blocked ? null : _submit,
          ),
        ),
      ],
    );
  }

  /// The quiet outlined button that sits beside the primary call to action.
  Widget _ghostButton({required String label, required VoidCallback? onTap, required bool busy}) {
    final p = context.astra;
    final t = context.astraTheme;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 15),
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(t.rButton),
          border: Border.all(color: p.primaryDark.withValues(alpha: 0.25), width: 1.4),
        ),
        child: busy
            ? SizedBox(
                width: 18,
                height: 18,
                child: CircularProgressIndicator(strokeWidth: 2.4, color: p.primaryDark),
              )
            : Text(label, style: ui(size: 14.5, weight: FontWeight.w800, color: p.primaryDark)),
      ),
    );
  }

  // ---- Tablet checkout: receipt left, terminal rail right --------------------

  /// On a tablet the ticket takes the width as a till-roll receipt and the
  /// terminal docks into a right-hand rail, which is how a counter POS is
  /// actually used.
  ///
  /// The page head belongs to the ticket column only, so the rail runs the full
  /// height of the window — on a short landscape till that height is what lets
  /// the note pad sit above the Charge bar.
  Widget _tabletScaffold(CartCubit cart, bool settled) {
    final p = context.astra;
    return Scaffold(
      backgroundColor: Colors.transparent,
      // The note sheet's keyboard would otherwise squeeze the rail to ~350pt on
      // a landscape till — less than the terminal and the Charge bar need.
      resizeToAvoidBottomInset: false,
      body: AstraBackground(
        child: SafeArea(
          child: LayoutBuilder(
            builder: (ctx, c) => Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Expanded(
                  child: Column(
                    children: [
                      TabletPageHead(
                        leading: TabletIconButton(icon: Icons.chevron_left, tooltip: 'Back', onTap: () => context.pop()),
                        title: _headline(cart),
                        subtitle: '${cart.customerName} · ${_staff(cart)}',
                        actions: [
                          if (cart.student != null)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
                              decoration: BoxDecoration(color: p.successTint, borderRadius: BorderRadius.circular(999)),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.contactless_outlined, size: 14, color: AstraPalette.success),
                                  const SizedBox(width: 4),
                                  Text('Card tapped', style: ui(size: 10.5, weight: FontWeight.w800, color: AstraPalette.success)),
                                ],
                              ),
                            ),
                          TabletIconButton(
                            icon: cart.notes.isEmpty ? Icons.edit_note_rounded : Icons.sticky_note_2_outlined,
                            tooltip: cart.notes.isEmpty ? 'Add a note' : 'Edit the note',
                            onTap: _openNote,
                          ),
                        ],
                      ),
                      Expanded(
                        child: Padding(
                          padding: const EdgeInsets.fromLTRB(22, 18, 22, 20),
                          child: _paperTicket(cart),
                        ),
                      ),
                    ],
                  ),
                ),
                _payRail(cart, settled, (c.maxWidth * 0.38).clamp(340.0, 460.0)),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _payRail(CartCubit cart, bool settled, double width) {
    final p = context.astra;
    return TabletPane(
      width: width,
      edge: PaneEdge.left,
      // The rail is canvas, not card, so the pad's cards and chips stand off it
      // as they do on a phone.
      child: ColoredBox(
        color: p.canvas,
        child: Column(
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(18, 16, 18, 16),
              decoration: BoxDecoration(
                gradient: p.heroGradient,
                borderRadius: const BorderRadius.vertical(bottom: Radius.circular(28)),
              ),
              child: _terminal(cart, tablet: true),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(18, 14, 18, 0),
                child: _fitted(_payPanel(cart, phone: false)),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 12, 18, 16),
              child: _checkoutBar(cart, settled),
            ),
          ],
        ),
      ),
    );
  }

  /// The ticket as a till roll: torn top edge, the QLOUD POS masthead, the
  /// lines in Qty · Item · Price · Amount columns, then the totals. The lines
  /// scroll inside the roll; the masthead and totals stay put.
  Widget _paperTicket(CartCubit cart) {
    final p = context.astra;
    final symbol = Money.symbol.trim();
    final heading = ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8);

    return PhysicalShape(
      clipper: const _TornEdgeClipper(),
      color: p.cardSolid,
      elevation: 3,
      shadowColor: p.primaryDark.withValues(alpha: 0.3),
      child: LayoutBuilder(
        builder: (context, c) {
          // A phone-width roll drops the unit price; the amount says enough.
          final wide = c.maxWidth > 420;
          return Padding(
            padding: const EdgeInsets.fromLTRB(22, 24, 22, 16),
            child: Column(
              children: [
                const QloudLogomark(height: 30),
                const SizedBox(height: 6),
                Text('QLOUD POS', style: ui(size: 11.5, weight: FontWeight.w800, color: p.ink, letterSpacing: 2.6)),
                const SizedBox(height: 3),
                Text('${cart.customerName} · ${_staff(cart)} · ${DateFormat('d MMM yyyy').format(DateTime.now())}',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                _dashed(p),
                Row(
                  children: [
                    SizedBox(width: 46, child: Text('QTY', style: heading)),
                    Expanded(child: Text('ITEM', style: heading)),
                    if (wide) SizedBox(width: 92, child: Text('PRICE', textAlign: TextAlign.right, style: heading)),
                    SizedBox(width: 100, child: Text('AMOUNT', textAlign: TextAlign.right, style: heading)),
                  ],
                ),
                const SizedBox(height: 4),
                Expanded(
                  child: ListView.separated(
                    padding: EdgeInsets.zero,
                    itemCount: cart.lines.length,
                    separatorBuilder: (_, __) => Divider(height: 1, color: p.hairline),
                    itemBuilder: (_, i) {
                      final l = cart.lines[i];
                      return Padding(
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        child: Row(
                          children: [
                            SizedBox(
                              width: 46,
                              child: Align(
                                alignment: Alignment.centerLeft,
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                                  decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(6)),
                                  child: Text('${l.qty.toStringAsFixed(l.qty % 1 == 0 ? 0 : 1)}×',
                                      style: ui(size: 10.5, weight: FontWeight.w800, color: p.primaryDark)),
                                ),
                              ),
                            ),
                            Expanded(
                              child: Text(l.name,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: ui(size: 13, weight: FontWeight.w600, color: p.ink)),
                            ),
                            if (wide)
                              SizedBox(
                                width: 92,
                                child: Text(Money.plain(l.unitPrice),
                                    textAlign: TextAlign.right,
                                    style: ui(size: 12.5, weight: FontWeight.w600, color: p.textMuted)),
                              ),
                            SizedBox(
                              width: 100,
                              child: Text(Money.plain(l.total),
                                  textAlign: TextAlign.right,
                                  style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                ),
                _dashed(p),
                _row('Subtotal', Money.plain(cart.subtotal), p.textSecondary),
                if (cart.totalDiscount > 0) _row('Discount', '− ${Money.plain(cart.totalDiscount)}', p.goldText),
                if (cart.taxTotal > 0) _row('Tax', Money.plain(cart.taxTotal), p.textSecondary),
                if (cart.roundOff != 0) _row('Round Off', Money.plain(cart.roundOff), p.textSecondary),
                if (cart.tipAmount > 0)
                  _row('Tip (${cart.tipPercent.toStringAsFixed(0)}%)', Money.plain(cart.tipAmount), p.textSecondary),
                const SizedBox(height: 8),
                // The number the cashier reads out — the one figure on the
                // tablet that is stated once, here, at the foot of the roll.
                Container(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
                  decoration: BoxDecoration(
                    gradient: p.heroGradient,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('AMOUNT DUE',
                                style: ui(size: 10, weight: FontWeight.w800, color: Colors.white70, letterSpacing: 0.9)),
                            const SizedBox(height: 2),
                            Text(_ticketFacts(cart),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: ui(size: 11, weight: FontWeight.w600, color: Colors.white70)),
                          ],
                        ),
                      ),
                      if (symbol.isNotEmpty) ...[
                        Text(symbol, style: ui(size: 14, weight: FontWeight.w800, color: Colors.white70)),
                        const SizedBox(width: 7),
                      ],
                      Text(Money.plain(cart.total), style: serif(size: 34, color: Colors.white)),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _row(String label, String value, Color color) => Padding(
        padding: const EdgeInsets.only(bottom: 5),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: ui(size: 11.5, weight: FontWeight.w600, color: color)),
            Text(value, style: ui(size: 11.5, weight: FontWeight.w700, color: color)),
          ],
        ),
      );

  Widget _dashed(AstraPalette p) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 11),
        child: LayoutBuilder(
          builder: (context, c) {
            final count = (c.maxWidth / 7).floor();
            return Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: List.generate(count, (_) => Container(width: 3, height: 1, color: p.hairline)),
            );
          },
        ),
      );
}

/// How a status line or a prompt reads: neutral, settled, owed, or wrong.
enum _Tone { info, ok, warn, bad }

/// One payment method on the terminal's blue. An everyday [height] tile is icon
/// over label; a [minor] tile is a shorter outlined pill, icon beside label.
/// [configured] marks a method that comes from the web's POS Payment Buttons.
class _MethodTile extends StatelessWidget {
  const _MethodTile({
    required this.icon,
    required this.label,
    required this.height,
    required this.active,
    required this.onTap,
    this.minor = false,
    this.wallet = false,
    this.configured = false,
  });

  final IconData icon;
  final String label;
  final double height;
  final bool active;
  final VoidCallback onTap;
  final bool minor;
  final bool wallet;
  final bool configured;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final mint = _ReviewPayScreenState._mint;
    final fg = active
        ? p.primary
        : wallet
            ? mint
            : Colors.white.withValues(alpha: minor ? 0.8 : 0.9);
    final Color bg = active
        ? Colors.white
        : wallet
            ? mint.withValues(alpha: 0.16)
            : minor
                ? Colors.transparent
                : Colors.white.withValues(alpha: 0.09);
    final text = FittedBox(
      fit: BoxFit.scaleDown,
      child: Text(label,
          maxLines: 1,
          style: ui(size: minor ? 11 : 10.5, weight: active ? FontWeight.w800 : FontWeight.w700, color: fg)),
    );

    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        curve: Curves.easeOut,
        height: height,
        padding: const EdgeInsets.symmetric(horizontal: 4),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: minor && !active && !wallet ? Colors.white.withValues(alpha: 0.2) : Colors.transparent,
          ),
          boxShadow: active
              ? [BoxShadow(color: Colors.black.withValues(alpha: 0.18), blurRadius: 14, offset: const Offset(0, 6))]
              : null,
        ),
        child: Stack(
          children: [
            Positioned.fill(
              child: minor
                  ? Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(icon, size: 15, color: fg),
                        const SizedBox(width: 6),
                        Flexible(child: text),
                      ],
                    )
                  : Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(icon, size: 19, color: fg),
                        const SizedBox(height: 3),
                        text,
                      ],
                    ),
            ),
            if (configured)
              Positioned(
                top: 4,
                right: 2,
                child: Container(
                  width: 6,
                  height: 6,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: (active ? p.primary : Colors.white).withValues(alpha: 0.5),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// What the cashier does for a mode that needs no pad: one ringed icon, a
/// title and a line under it, centred in the panel. It shrinks with the room
/// it has, dropping the line first.
class _PayPrompt extends StatelessWidget {
  const _PayPrompt({required this.tone, required this.icon, required this.title, required this.subtitle, this.onTap});

  final _Tone tone;
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final (Color tint, Color color) = switch (tone) {
      _Tone.ok => (p.successTint, AstraPalette.success),
      _Tone.warn => (p.warnTint, p.warnText),
      _Tone.bad => (p.dangerTint, AstraPalette.danger),
      _Tone.info => (p.tint, p.primary),
    };
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(16),
          boxShadow: context.astraTheme.softShadow,
        ),
        child: LayoutBuilder(
          builder: (context, c) {
            final ring = (c.maxHeight * 0.36).clamp(36.0, 70.0);
            return Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  width: ring,
                  height: ring,
                  decoration: BoxDecoration(
                    color: tint,
                    shape: BoxShape.circle,
                    boxShadow: [BoxShadow(color: tint.withValues(alpha: 0.5), spreadRadius: ring > 50 ? 10 : 5)],
                  ),
                  child: Icon(icon, size: ring * 0.48, color: color),
                ),
                SizedBox(height: ring > 50 ? 16 : 8),
                Text(title,
                    textAlign: TextAlign.center,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 15, weight: FontWeight.w800, color: p.ink)),
                if (c.maxHeight > 150) ...[
                  const SizedBox(height: 4),
                  Text(subtitle,
                      textAlign: TextAlign.center,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted)),
                ],
              ],
            );
          },
        ),
      ),
    );
  }
}

/// The tapped student card as a card: who, what it may spend, and — once this
/// ticket spends some of it — what it holds [after].
class _StudentWalletCard extends StatelessWidget {
  const _StudentWalletCard({required this.card, this.after});

  final StudentCard card;
  final double? after;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final headers = context.read<AuthCubit>().config.assetHeaders;
    final caption = ui(size: 9.5, weight: FontWeight.w800, color: Colors.white70, letterSpacing: 0.8);
    final initial = Container(
      width: 44,
      height: 44,
      alignment: Alignment.center,
      decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.18), shape: BoxShape.circle),
      child: Text(card.name.isEmpty ? '?' : card.name.characters.first.toUpperCase(),
          style: serif(size: 18, color: Colors.white)),
    );
    final left = after;

    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(gradient: p.heroGradient, borderRadius: BorderRadius.circular(18)),
      child: Stack(
        children: [
          Positioned(
            right: -30,
            bottom: -50,
            child: Container(
              width: 150,
              height: 150,
              decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.08), shape: BoxShape.circle),
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    card.imageUrl.startsWith('http')
                        ? ClipOval(
                            child: Image.network(
                              card.imageUrl,
                              headers: headers,
                              width: 44,
                              height: 44,
                              fit: BoxFit.cover,
                              cacheWidth: decodeWidthFor(context, 44),
                              errorBuilder: (_, __, ___) => initial,
                            ),
                          )
                        : initial,
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(card.name,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: ui(size: 14.5, weight: FontWeight.w800, color: Colors.white)),
                          Text(card.isBlocked ? 'Card blocked' : card.classLabel,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: ui(size: 10.5, weight: FontWeight.w700, color: Colors.white70)),
                        ],
                      ),
                    ),
                    const Icon(Icons.contactless_outlined, color: Colors.white, size: 22),
                  ],
                ),
                const SizedBox(height: 14),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('CAN SPEND', style: caption),
                        Text(Money.plain(card.available), style: serif(size: 22, color: Colors.white)),
                      ],
                    ),
                    const Spacer(),
                    if (left != null)
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text('AFTER SALE', style: caption),
                          Text(Money.plain(left),
                              style: ui(
                                size: 15,
                                weight: FontWeight.w800,
                                color: left < 0 ? Color.lerp(AstraPalette.danger, Colors.white, 0.55) : Colors.white,
                              )),
                        ],
                      ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// The note, in a sheet over the pay screen. The controller and focus node
/// belong to the screen, which disposes them.
class _NoteSheet extends StatelessWidget {
  const _NoteSheet({required this.controller, required this.focusNode, required this.onChanged});

  final TextEditingController controller;
  final FocusNode focusNode;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        decoration: BoxDecoration(
          color: p.canvas,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(26)),
        ),
        child: SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(18, 10, 18, 14),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Center(
                  child: Container(
                    width: 38,
                    height: 4,
                    decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(9)),
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Icon(Icons.edit_note_rounded, size: 20, color: p.primary),
                    const SizedBox(width: 8),
                    Expanded(child: Text('Sale note', style: serif(size: 19, color: p.ink))),
                    ValueListenableBuilder<TextEditingValue>(
                      valueListenable: controller,
                      builder: (_, value, __) => value.text.isEmpty
                          ? const SizedBox.shrink()
                          : GestureDetector(
                              onTap: () {
                                controller.clear();
                                onChanged('');
                              },
                              child: Text('Remove', style: ui(size: 11.5, weight: FontWeight.w700, color: p.textMuted)),
                            ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.fromLTRB(13, 11, 13, 9),
                  decoration: BoxDecoration(
                    color: p.card,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: p.primary, width: 1.4),
                  ),
                  child: KeyboardDoneField(
                    focusNode: focusNode,
                    child: TextField(
                      controller: controller,
                      focusNode: focusNode,
                      autofocus: true,
                      minLines: 3,
                      maxLines: 5,
                      maxLength: 1000,
                      textCapitalization: TextCapitalization.sentences,
                      style: ui(size: 13, weight: FontWeight.w600, color: p.ink),
                      cursorColor: p.primary,
                      decoration: InputDecoration(
                        isCollapsed: true,
                        border: InputBorder.none,
                        hintText: 'e.g. Deliver after 6 pm, gift wrap…',
                        hintStyle: ui(size: 13, weight: FontWeight.w500, color: p.textMuted),
                        counterStyle: ui(size: 9.5, weight: FontWeight.w600, color: p.textMuted),
                      ),
                      onChanged: onChanged,
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                AstraButton(label: 'Done', onTap: () => Navigator.of(context).pop()),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// The top edge of a till roll — round bites torn along it — over a card whose
/// bottom corners are rounded.
class _TornEdgeClipper extends CustomClipper<Path> {
  const _TornEdgeClipper();

  static const _bite = 7.0;
  static const _period = 18.0;
  static const _corner = 16.0;

  @override
  Path getClip(Size size) {
    final path = Path()..moveTo(0, 0);
    for (var x = 0.0; x + _period <= size.width; x += _period) {
      final mid = x + _period / 2;
      path
        ..lineTo(mid - _bite, 0)
        ..arcToPoint(Offset(mid + _bite, 0), radius: const Radius.circular(_bite), clockwise: false);
    }
    return path
      ..lineTo(size.width, 0)
      ..lineTo(size.width, size.height - _corner)
      ..quadraticBezierTo(size.width, size.height, size.width - _corner, size.height)
      ..lineTo(_corner, size.height)
      ..quadraticBezierTo(0, size.height, 0, size.height - _corner)
      ..close();
  }

  @override
  bool shouldReclip(covariant CustomClipper<Path> oldClipper) => false;
}
