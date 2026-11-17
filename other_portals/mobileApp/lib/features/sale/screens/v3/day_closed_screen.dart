import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/features/admin/logic/day_session_cubit/day_session_cubit.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/settings/widgets/v3/branch_sheet.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/day_gate.dart';
import 'package:invo/shared/widgets/astra_snack.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// The sale flow's stop for someone who can't open the day themselves while
/// the branch day is closed (see [DayGate]): says so, tells them to ask an
/// admin, and gets out of the way the moment the day is open.
///
/// It keeps asking the server — on the way in, every [_pollEvery], when the app
/// comes back to the foreground, on a branch switch and on "Check again" — so a
/// cashier waiting at the till is moved on to New Sale without touching it once
/// the day has been opened from the web or another device.
class DayClosedScreen extends StatefulWidget {
  const DayClosedScreen({super.key});

  @override
  State<DayClosedScreen> createState() => _DayClosedScreenState();
}

class _DayClosedScreenState extends State<DayClosedScreen> {
  static const _pollEvery = Duration(seconds: 30);

  Timer? _poll;
  StreamSubscription<int>? _branchSub;
  late final AppLifecycleListener _lifecycle;

  /// The last re-read, and whether the server answered it.
  DateTime? _checkedAt;
  bool _reached = true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _check();
    });
    _poll = Timer.periodic(_pollEvery, (_) => _check());
    _lifecycle = AppLifecycleListener(onResume: _check);
    // A different branch is a different day.
    _branchSub = context.read<BranchCubit>().onBranchChanged.listen((_) {
      if (mounted) _check();
    });
  }

  @override
  void dispose() {
    _poll?.cancel();
    _branchSub?.cancel();
    _lifecycle.dispose();
    super.dispose();
  }

  /// Re-reads the day. [manual] is the "Check again" tap — the only one that
  /// answers out loud when nothing changed.
  Future<void> _check({bool manual = false}) async {
    final snack = manual ? AstraSnack.capture(context) : null;
    final reached = await context.read<DaySessionCubit>().refresh(statusOnly: true);
    if (!mounted) return;
    setState(() {
      _checkedAt = DateTime.now();
      _reached = reached;
    });
    if (context.read<AuthCubit>().user?.dayOpen ?? false) return;
    if (!reached) {
      snack?.error(AppStrings.couldNotReachServer);
    } else {
      snack?.show('The day is still closed.');
    }
  }

  Future<void> _logout() async {
    if (await confirmLogout(context) && mounted) {
      await context.read<AuthCubit>().logout();
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return BlocListener<AuthCubit, AuthState>(
      listenWhen: (a, b) => !(a.user?.dayOpen ?? false) && (b.user?.dayOpen ?? false),
      listener: (context, _) {
        AstraSnack.success(context, 'The day is open.');
        DayGate.toSale(context);
      },
      child: Scaffold(
        backgroundColor: Colors.transparent,
        body: AstraBackground(
          child: SafeArea(
            child: Column(
              children: [
                if (context.canPop())
                  Padding(
                    padding: const EdgeInsets.fromLTRB(8, 4, 8, 0),
                    child: Align(
                      alignment: Alignment.centerLeft,
                      child: IconButton(
                        icon: Icon(Icons.chevron_left, color: p.textSecondary),
                        onPressed: () => context.pop(),
                      ),
                    ),
                  ),
                Expanded(
                  child: LayoutBuilder(
                    builder: (context, box) => SingleChildScrollView(
                      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                      child: ConstrainedBox(
                        constraints: BoxConstraints(minHeight: box.maxHeight - 32),
                        child: Center(
                          child: MaxWidthBox(maxWidth: 440, child: _content()),
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _content() {
    final p = context.astra;
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 72,
          height: 72,
          decoration: BoxDecoration(color: p.dangerTint, borderRadius: BorderRadius.circular(22)),
          child: const Icon(Icons.event_busy_outlined, size: 32, color: AstraPalette.danger),
        ),
        const SizedBox(height: 18),
        Text('DAY NOT OPEN',
            style: ui(size: 10, weight: FontWeight.w800, color: AstraPalette.danger, letterSpacing: 2)),
        const SizedBox(height: 6),
        Text('The day hasn’t been opened',
            textAlign: TextAlign.center, style: serif(size: 24, color: p.ink)),
        const SizedBox(height: 10),
        Text(
          '${AppStrings.dayNotOpenForSales} Ask your admin or manager to open it — '
          'New Sale opens here by itself once they have.',
          textAlign: TextAlign.center,
          style: ui(size: 13, weight: FontWeight.w600, color: p.textSecondary, height: 1.5),
        ),
        const SizedBox(height: 22),
        const _DayFacts(),
        const SizedBox(height: 18),
        BlocSelector<DaySessionCubit, DaySessionState, bool>(
          selector: (s) => s.syncing,
          builder: (context, syncing) => AstraButton(
            label: 'Check again',
            icon: Icons.refresh,
            busy: syncing,
            onTap: () => _check(manual: true),
          ),
        ),
        const SizedBox(height: 10),
        Text(
          _checkedAt == null
              ? 'Checking with the server…'
              : _reached
                  ? 'Checked at ${Dates.time(_checkedAt!)}'
                  : 'Offline — showing the last known status',
          style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted),
        ),
        const SizedBox(height: 14),
        TextButton.icon(
          onPressed: _logout,
          icon: const Icon(Icons.power_settings_new, size: 16),
          label: Text('Log out', style: ui(size: 13, weight: FontWeight.w800, color: p.textSecondary)),
          style: TextButton.styleFrom(foregroundColor: p.textSecondary),
        ),
      ],
    );
  }
}

/// Which branch is closed and since when — and, for someone with more than one
/// branch, the way to the branch that may be open.
class _DayFacts extends StatelessWidget {
  const _DayFacts();

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final branch = context.watch<BranchCubit>();
    final lastClosed = context.select<AuthCubit, String>((c) => c.user?.lastClosedSessionAt ?? '');
    final switchable = branch.branches.length > 1;
    return AstraCard(
      radius: 16,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
      child: Column(
        children: [
          _row(
            context,
            icon: Icons.storefront_outlined,
            label: 'Branch',
            value: branch.selected?.name ?? '—',
            trailing: switchable
                ? GestureDetector(
                    onTap: () => showBranchSheet(context),
                    child: Text('Switch',
                        style: ui(size: 12, weight: FontWeight.w800, color: p.primary)),
                  )
                : null,
          ),
          if (lastClosed.isNotEmpty) ...[
            Divider(height: 1, color: p.hairline),
            _row(
              context,
              icon: Icons.bedtime_outlined,
              label: 'Last closed',
              value: Dates.humanDateTime(lastClosed),
            ),
          ],
        ],
      ),
    );
  }

  Widget _row(BuildContext context,
      {required IconData icon, required String label, required String value, Widget? trailing}) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 11),
      child: Row(
        children: [
          Icon(icon, size: 17, color: p.textMuted),
          const SizedBox(width: 10),
          Text(label, style: ui(size: 12, weight: FontWeight.w700, color: p.textSecondary)),
          const SizedBox(width: 12),
          Expanded(
            child: Text(value,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.right,
                style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
          ),
          if (trailing != null) ...[const SizedBox(width: 12), trailing],
        ],
      ),
    );
  }
}
