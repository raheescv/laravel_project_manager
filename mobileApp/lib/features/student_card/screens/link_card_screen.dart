import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:invo/features/student_card/logic/student_card_cubit/student_card_cubit.dart';
import 'package:invo/features/student_card/widgets/student_card_tile.dart';
import 'package:invo/features/student_card/widgets/tap_card_sheet.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_snack.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Link (or replace) a student's NFC card from the till.
///
/// The recommended way to enrol cards: the UID is read by the same device family
/// that reads it at the canteen, so what is stored is exactly what the till will
/// see. Gated on `student card.assign` (router + API).
class LinkCardScreen extends StatefulWidget {
  const LinkCardScreen({super.key});

  @override
  State<LinkCardScreen> createState() => _LinkCardScreenState();
}

class _LinkCardScreenState extends State<LinkCardScreen> {
  late final StudentCardCubit _cubit = serviceLocator<StudentCardCubit>();
  late final TextEditingController _search = TextEditingController();
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _cubit.search('');
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    _cubit.close();
    super.dispose();
  }

  void _onSearch(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () => _cubit.search(value));
  }

  Future<void> _link(int accountId, String name) async {
    final uid = await showReadCardUidSheet(context, studentName: name);
    if (uid == null || !mounted) return;
    final card = await _cubit.link(accountId, uid);
    if (!mounted) return;
    if (card != null) {
      AstraSnack.success(context, 'Card $uid linked to $name');
    } else {
      AstraSnack.error(context, _cubit.state.errorMessage ?? 'Could not link the card.');
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Scaffold(
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(icon: Icons.chevron_left, onTap: () => context.pop()),
              title: 'Link Student Card',
              subtitle: 'Find the student, then tap their card',
              bottom: TextField(
                controller: _search,
                onChanged: _onSearch,
                style: ui(size: 14, weight: FontWeight.w600, color: p.ink),
                decoration: InputDecoration(
                  hintText: 'Name, admission no or parent mobile',
                  prefixIcon: Icon(Icons.search, color: p.textMuted),
                  filled: true,
                  fillColor: p.card,
                  contentPadding: const EdgeInsets.symmetric(vertical: 12),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                ),
              ),
            ),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 640,
                child: BlocBuilder<StudentCardCubit, StudentCardState>(
                  bloc: _cubit,
                  builder: (context, state) {
                    if (state.isBusy && state.results.isEmpty) {
                      return const Center(child: CircularProgressIndicator());
                    }
                    if (state.results.isEmpty) {
                      return Center(
                        child: Padding(
                          padding: const EdgeInsets.all(24),
                          child: Text(state.errorMessage ?? 'No students found.',
                              textAlign: TextAlign.center, style: ui(size: 13, weight: FontWeight.w600, color: p.textMuted)),
                        ),
                      );
                    }
                    return ListView.separated(
                      padding: const EdgeInsets.fromLTRB(16, 14, 16, 24),
                      itemCount: state.results.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 9),
                      itemBuilder: (_, i) {
                        final student = state.results[i];
                        return AstraCard(
                          onTap: state.isBusy ? null : () => _link(student.accountId, student.name),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              StudentCardTile(card: student, trailing: Icon(Icons.nfc, color: p.primary)),
                              const SizedBox(height: 6),
                              Text(
                                student.hasCard
                                    ? 'Card ${student.cardUid}${student.isBlocked ? ' (blocked)' : ''} · tap to replace'
                                    : 'No card linked · tap to link',
                                style: ui(size: 11, weight: FontWeight.w700, color: student.hasCard ? p.textMuted : p.goldText),
                              ),
                            ],
                          ),
                        );
                      },
                    );
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
