import 'dart:async';

import 'package:flutter/material.dart';
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
  /// panel) because the checkout button names the change on its label.
  double? _tendered;

  /// Whether the itemised receipt is unfolded under the amount-due hero. The
  /// ticket has already been reviewed on the cart screen, so on a phone it
  /// starts folded and the payment controls get the height instead.
  bool _showItems = false;

  @override
  void initState() {
    super.initState();
    _loadMethods();
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
        saved = await _ops.createSale(ticket.payload, offlineSale: ticket.offlineSale);
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
    // The provisional reference the customer already has is unchanged, so it is
    // read back off the row rather than reprinted as something new.
    final row = await serviceLocator<OutboxRepository>().byUuid(pendingUuid);
    return Sale.fromJson({
      ...ticket.offlineSale,
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
      final result = await printReceipt(
        sale,
        print.snapshot,
        printerUrl: print.printerUrl,
        printerName: print.printerName,
      );
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
    final result = await showCustomPaymentSheet(
      context,
      total: cart.total,
      methods: _methods,
      initial: cart.customPayments,
    );
    if (result != null && result.isNotEmpty) {
      cart.setCustomPayments(result);
    }
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
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(icon: Icons.chevron_left, onTap: () => context.pop()),
              title: _headline(cart),
            ),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 560,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 13, 16, 24),
                  children: [
                    _dueHero(cart),
                    if (_showItems) ...[
                      const SizedBox(height: 12),
                      _receiptCard(cart),
                    ],
                    const SizedBox(height: 14),
                    ..._moneySection(cart),
                  ],
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
            padding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
            child: _checkoutBar(cart, settled),
          ),
        ),
      ),
    );
  }

  // ---- Amount due + the money controls -------------------------------------

  /// The number the cashier reads out, stated once and unmissably.
  ///
  /// Tapping it unfolds the itemised receipt underneath; [compact] drops that
  /// affordance for the tablet rail, where the ticket is already the left pane.
  Widget _dueHero(CartCubit cart, {bool compact = false}) {
    final p = context.astra;
    final n = cart.lines.length;
    final extras = [
      if (cart.totalDiscount > 0) '${Money.of(cart.totalDiscount)} off',
      if (cart.taxTotal > 0) 'incl. ${Money.of(cart.taxTotal)} tax',
    ];

    return GestureDetector(
      onTap: compact ? null : () => setState(() => _showItems = !_showItems),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.fromLTRB(16, 14, 16, 15),
        decoration: BoxDecoration(
          gradient: p.heroGradient,
          borderRadius: BorderRadius.circular(context.astraTheme.rCard),
          boxShadow: context.astraTheme.softShadow,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text('AMOUNT DUE',
                    style: ui(size: 10, weight: FontWeight.w800, color: Colors.white70, letterSpacing: 0.9)),
                const Spacer(),
                if (!compact)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.16),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text('$n item${n == 1 ? '' : 's'}',
                            style: ui(size: 10, weight: FontWeight.w800, color: Colors.white)),
                        const SizedBox(width: 3),
                        Icon(_showItems ? Icons.expand_less : Icons.expand_more, size: 13, color: Colors.white),
                      ],
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 2),
            FittedBox(
              fit: BoxFit.scaleDown,
              alignment: Alignment.centerLeft,
              child: Text(Money.of(cart.total), style: serif(size: 34, color: Colors.white)),
            ),
            const SizedBox(height: 3),
            Text(
              [cart.customerName, ...extras].join(' · '),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(size: 10.5, weight: FontWeight.w600, color: Colors.white70),
            ),
          ],
        ),
      ),
    );
  }

  /// The money half of the screen, shared by both layouts.
  ///
  /// Cash is the one mode whose handed-over amount can differ from the ticket —
  /// the drawer takes a note and gives the difference back — so it gets the
  /// tender pad, and the totals/status pair would only restate what the hero and
  /// the change band already say. Every other mode settles at a value the ticket
  /// or the split sheet decided, and that is where a partial or overpaid split
  /// has to be explained, so those keep the pair.
  List<Widget> _moneySection(CartCubit cart) {
    return [
      if (cart.tipEnabled) ...[
        _tipSelector(cart),
        const SizedBox(height: 14),
      ],
      _paymentSection(cart),
      const SizedBox(height: 14),
      if (cart.payMode == PayMode.cash)
        CashTenderPanel(
          total: cart.total,
          onChanged: (v) => setState(() => _tendered = v),
        )
      else ...[
        _summaryCard(cart),
        const SizedBox(height: 12),
        _statusCard(cart),
      ],
    ];
  }

  /// The change line under the primary button — only when there is money to
  /// actually hand back.
  String? _changeHint(CartCubit cart) {
    final tendered = _tendered;
    if (cart.payMode != PayMode.cash || tendered == null) return null;
    final change = double.parse((tendered - cart.total).toStringAsFixed(2));
    return change > 0 ? 'Change ${Money.of(change)}' : null;
  }

  /// What this screen is doing, so a parked draft doesn't look like an ordinary
  /// edit — the buttons underneath differ.
  String _headline(CartCubit cart) => cart.isEditingDraft
      ? 'Complete Draft'
      : cart.isEditing
          ? 'Edit sale'
          : 'Review & Pay';

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

  // ---- Tablet checkout: ticket left, payment rail right ---------------------

  /// A 560pt column centred in a 1200pt window leaves the ticket unreadably far
  /// from the payment controls. On a tablet the ticket takes the width and the
  /// money side — tip, method, totals, CTA — docks into a right-hand pane, which
  /// is how a counter POS is actually used.
  Widget _tabletScaffold(CartCubit cart, bool settled) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: SafeArea(
          child: Column(
            children: [
              TabletPageHead(
                leading: TabletIconButton(icon: Icons.chevron_left, tooltip: 'Back', onTap: () => context.pop()),
                title: _headline(cart),
                subtitle: '${cart.lines.length} item${cart.lines.length == 1 ? '' : 's'} on this ticket',
              ),
              Expanded(
                child: LayoutBuilder(
                  builder: (ctx, c) => Row(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Expanded(
                        child: ListView(
                          padding: const EdgeInsets.fromLTRB(22, 18, 22, 28),
                          children: [_receiptCard(cart)],
                        ),
                      ),
                      _payRail(cart, settled, (c.maxWidth * 0.38).clamp(340.0, 460.0)),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _payRail(CartCubit cart, bool settled, double width) {
    return TabletPane(
      width: width,
      edge: PaneEdge.left,
      child: Column(
        children: [
          Expanded(
            child: ListView(
              padding: const EdgeInsets.fromLTRB(18, 18, 18, 12),
              children: [
                _dueHero(cart, compact: true),
                const SizedBox(height: 14),
                ..._moneySection(cart),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(18, 4, 18, 16),
            child: _checkoutBar(cart, settled),
          ),
        ],
      ),
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

  Widget _receiptCard(CartCubit cart) {
    final p = context.astra;
    return AstraCard(
      child: Column(
        children: [
          Column(
            children: [
              const QloudLogomark(height: 28),
              const SizedBox(height: 6),
              Text('QLOUD POS', style: ui(size: 11, weight: FontWeight.w800, color: p.ink, letterSpacing: 2.6)),
              const SizedBox(height: 3),
              Text('${cart.customerName} · ${cart.stylistName.isEmpty ? 'Me' : cart.stylistName}',
                  style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
            ],
          ),
          _dashed(p),
          for (final l in cart.lines)
            Padding(
              padding: const EdgeInsets.only(bottom: 9),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                    decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(6)),
                    child: Text('${l.qty.toStringAsFixed(l.qty % 1 == 0 ? 0 : 1)}×',
                        style: ui(size: 10.5, weight: FontWeight.w800, color: p.primaryDark)),
                  ),
                  const SizedBox(width: 8),
                  Expanded(child: Text(l.name, style: ui(size: 12, weight: FontWeight.w600, color: p.ink))),
                  Text(Money.of(l.total), style: ui(size: 12, weight: FontWeight.w800, color: p.ink)),
                ],
              ),
            ),
          _dashed(p),
          _row('Subtotal', Money.of(cart.subtotal), p.textSecondary),
          if (cart.totalDiscount > 0) _row('Discount', '− ${Money.of(cart.totalDiscount)}', p.goldText),
          if (cart.taxTotal > 0) _row('Tax', Money.of(cart.taxTotal), p.textSecondary),
          if (cart.tipAmount > 0) _row('Tip (${cart.tipPercent.toStringAsFixed(0)}%)', Money.of(cart.tipAmount), p.textSecondary),
          const SizedBox(height: 4),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Total', style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
              Text(Money.of(cart.total), style: serif(size: 19, color: p.ink)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _tipSelector(CartCubit cart) {
    final p = context.astra;
    final tips = [0.0, 10.0, 15.0, 20.0];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('ADD A TIP', style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
        const SizedBox(height: 7),
        Row(
          children: [
            for (final tip in tips) ...[
              Expanded(
                child: GestureDetector(
                  onTap: () => cart.setTip(tip),
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 11),
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      gradient: cart.tipPercent == tip ? p.primaryGradient : null,
                      color: cart.tipPercent == tip ? null : p.card,
                      borderRadius: BorderRadius.circular(11),
                      boxShadow: context.astraTheme.softShadow,
                    ),
                    child: Text(tip == 0 ? 'None' : '${tip.toStringAsFixed(0)}%',
                        style: ui(size: 11.5, weight: FontWeight.w800, color: cart.tipPercent == tip ? Colors.white : p.textSecondary)),
                  ),
                ),
              ),
              if (tip != tips.last) const SizedBox(width: 7),
            ],
          ],
        ),
      ],
    );
  }

  // ---- Payment method (Cash / Card / Credit / Custom) + WhatsApp toggle ----

  Widget _paymentSection(CartCubit cart) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text('PAYMENT METHOD',
                  style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
            ),
            // _whatsappToggle(cart),
          ],
        ),
        const SizedBox(height: 8),
        Row(
          children: [
            _method(cart, PayMode.cash, Icons.payments_outlined),
            const SizedBox(width: 8),
            _method(cart, PayMode.card, Icons.credit_card),
            const SizedBox(width: 8),
            _method(cart, PayMode.credit, Icons.description_outlined),
            const SizedBox(width: 8),
            _method(cart, PayMode.custom, Icons.tune),
          ],
        ),
      ],
    );
  }

  Widget _method(CartCubit cart, PayMode mode, IconData icon) {
    final p = context.astra;
    final active = cart.payMode == mode;
    final isCustom = mode == PayMode.custom;
    final count = cart.customPayments.length;

    return Expanded(
      child: GestureDetector(
        onTap: () {
          if (isCustom) {
            _openCustom();
            return;
          }
          cart.setPayMode(mode);
          // The tender pad unmounts with the mode, so what it reported would
          // otherwise linger on the checkout button of a card sale.
          if (mode != PayMode.cash) setState(() => _tendered = null);
        },
        child: Container(
          height: 64,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: active ? p.primaryDark : p.card,
            borderRadius: BorderRadius.circular(13),
            boxShadow: active ? null : context.astraTheme.softShadow,
          ),
          child: Stack(
            alignment: Alignment.center,
            children: [
              Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(icon, size: 18, color: active ? p.accent : p.textSecondary),
                  const SizedBox(height: 5),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 4),
                    child: Text(
                      isCustom && count > 0 ? '$count method${count == 1 ? '' : 's'}' : mode.label,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                      style: ui(size: 10.5, weight: FontWeight.w800, color: active ? Colors.white : p.textSecondary),
                    ),
                  ),
                ],
              ),
              if (active)
                Positioned(
                  top: 4,
                  right: 5,
                  child: Icon(Icons.check_circle, size: 13, color: p.accent),
                ),
              if (isCustom && !active && count > 0)
                Positioned(
                  top: 4,
                  right: 5,
                  child: Container(
                    width: 14,
                    height: 14,
                    alignment: Alignment.center,
                    decoration: const BoxDecoration(color: AstraPalette.success, shape: BoxShape.circle),
                    child: Text('$count', style: ui(size: 8, weight: FontWeight.w800, color: Colors.white)),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  // ---- Transaction summary (Grand Total / Paid / Balance) ----

  Widget _summaryCard(CartCubit cart) {
    final p = context.astra;
    final bal = cart.balance;
    final ({Color color, IconData icon, String label}) status = bal.abs() < 0.001
        ? (color: AstraPalette.success, icon: Icons.check_circle, label: 'Balance')
        : bal > 0
            ? (color: AstraPalette.danger, icon: Icons.warning_amber_rounded, label: 'Remaining Balance')
            : (color: const Color(0xFFE08A2B), icon: Icons.south, label: 'Overpaid Amount');

    return AstraCard(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      child: Column(
        children: [
          _sumRow('Grand Total', Money.of(cart.total), p.primary, p),
          Divider(height: 14, color: p.hairline),
          _sumRow('Paid Amount', Money.of(cart.paidAmount), p.ink, p),
          Divider(height: 14, color: p.hairline),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Icon(status.icon, size: 14, color: status.color),
                  const SizedBox(width: 6),
                  Text(status.label, style: ui(size: 12, weight: FontWeight.w800, color: status.color)),
                ],
              ),
              Text(Money.of(bal.abs()), style: ui(size: 14, weight: FontWeight.w800, color: status.color)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _sumRow(String label, String value, Color valueColor, AstraPalette p) => Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: ui(size: 12, weight: FontWeight.w600, color: p.textSecondary)),
          Text(value, style: ui(size: 14, weight: FontWeight.w800, color: valueColor)),
        ],
      );

  // ---- "Ready to submit" status card ----

  Widget _statusCard(CartCubit cart) {
    final p = context.astra;
    final bal = cart.balance;
    final settled = bal.abs() < 0.001;
    final tappable = settled && !_busy && !_busyDraft;

    final (Color tint, Color color, IconData icon, String title, String desc) = settled
        ? (p.successTint, AstraPalette.success, Icons.check_circle, 'Ready to Submit', 'Transaction is fully paid and ready to submit')
        : bal > 0
            ? (p.warnTint, p.warnText, Icons.warning_amber_rounded, 'Partial Payment', 'Transaction has a remaining balance')
            : (p.tint, p.primaryDark, Icons.south, 'Overpaid Transaction', 'Transaction amount exceeds payment');

    return GestureDetector(
      // The shortcut for the primary button, so on a draft it completes the sale
      // rather than quietly re-saving it as a draft.
      onTap: tappable ? _submit : null,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 14),
        decoration: BoxDecoration(
          color: tint,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withValues(alpha: 0.35)),
        ),
        child: Column(
          children: [
            Container(
              width: 30,
              height: 30,
              decoration: BoxDecoration(color: color, shape: BoxShape.circle),
              child: Icon(icon, size: 16, color: Colors.white),
            ),
            const SizedBox(height: 7),
            Text(title, style: ui(size: 13, weight: FontWeight.w800, color: color)),
            const SizedBox(height: 3),
            Text(desc, textAlign: TextAlign.center, style: ui(size: 11, weight: FontWeight.w600, color: color.withValues(alpha: 0.85))),
            if (tappable) ...[
              const SizedBox(height: 5),
              Text(cart.isEditingDraft ? 'Tap to complete' : 'Tap to submit',
                  style: ui(size: 10.5, weight: FontWeight.w700, color: color.withValues(alpha: 0.7))),
            ],
          ],
        ),
      ),
    );
  }

  Widget _row(String label, String value, Color color) => Padding(
        padding: const EdgeInsets.only(bottom: 5),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: ui(size: 11, weight: FontWeight.w600, color: color)),
            Text(value, style: ui(size: 11, weight: FontWeight.w700, color: color)),
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
