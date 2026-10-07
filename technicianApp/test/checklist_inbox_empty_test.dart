// An empty (or failed) Checklists inbox used to throw "LayoutBuilder does not
// support returning intrinsic dimensions" and then "Null check operator used on
// a null value" on every rebuild: its EmptyState sat in a
// SliverFillRemaining(hasScrollBody: false), which measures its child's
// intrinsic height, and EmptyState is built on a LayoutBuilder. The shell keeps
// every tab alive, so the errors kept coming on whichever screen was showing.
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/checklist/domain/models/checklist_models.dart';
import 'package:invo/features/checklist/domain/repository/checklist_repository.dart';
import 'package:invo/features/checklist/logic/checklist_inbox_cubit/checklist_inbox_cubit.dart';
import 'package:invo/features/checklist/screens/v3/checklist_inbox_screen.dart';
import 'package:invo/shared/utils/components/theme/palette.dart';
import 'package:invo/shared/utils/components/theme/theme_manager.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Answers the inbox query with [jobs], or fails it when [error] is set; every
/// other repository call is unused here.
class _InboxRepository implements ChecklistRepository {
  _InboxRepository({this.error});

  final String? error;

  @override
  Future<List<ChecklistJob>> jobs({
    String? search,
    String? phase,
    String? status,
    String? dateBasis,
    String? fromDate,
    String? toDate,
  }) async {
    if (error != null) throw ApiException(error!);
    return const [];
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

Future<void> _pumpInbox(WidgetTester tester, ChecklistRepository repo, Size size) async {
  tester.view.physicalSize = size;
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);

  await tester.pumpWidget(MultiBlocProvider(
    providers: [
      BlocProvider(create: (_) => AuthCubit()),
      BlocProvider(create: (_) => ChecklistInboxCubit(repo)),
    ],
    child: MaterialApp(
      theme: buildAstraTheme(AstraPresets.auroraGlass),
      home: const Scaffold(body: ChecklistInboxScreen()),
    ),
  ));
  await tester.pump();
  await tester.pump(const Duration(milliseconds: 400));
}

void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  for (final (label, size) in [('phone', const Size(390, 844)), ('tablet', const Size(1194, 834))]) {
    testWidgets('an empty inbox shows its empty state without layout errors ($label)', (tester) async {
      await _pumpInbox(tester, _InboxRepository(), size);

      expect(tester.takeException(), isNull);
      expect(find.text('No hand-overs'), findsOneWidget);
    });

    testWidgets('a failed inbox shows its retry state without layout errors ($label)', (tester) async {
      await _pumpInbox(tester, _InboxRepository(error: 'Server unreachable'), size);

      expect(tester.takeException(), isNull);
      expect(find.text('Could not load'), findsOneWidget);
      expect(find.text('Retry'), findsOneWidget);
    });
  }

  testWidgets('EmptyState lays out as a direct ListView child (unbounded height)', (tester) async {
    await tester.pumpWidget(MaterialApp(
      theme: buildAstraTheme(AstraPresets.auroraGlass),
      home: Scaffold(
        body: ListView(children: const [
          Text('Above'),
          EmptyState(icon: Icons.inbox_outlined, title: 'Nothing yet', message: 'Jobs will show up here.'),
          Text('Below'),
        ]),
      ),
    ));
    await tester.pump();

    expect(tester.takeException(), isNull);
    expect(find.text('Nothing yet'), findsOneWidget);
    expect(find.text('Below'), findsOneWidget);
  });
}
