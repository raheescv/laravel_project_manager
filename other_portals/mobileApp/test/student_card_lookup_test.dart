import 'dart:async';

import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/features/student_card/domain/repository/student_card_repository.dart';
import 'package:invo/features/student_card/logic/student_card_cubit/student_card_cubit.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

StudentCard _student(int id, String name) => StudentCard.fromJson({
      'account_id': id,
      'name': name,
      'admission_no': 'NR$id',
      'status': 'active',
      'card_uid': 'CARD$id',
      'card_status': 'active',
      'balance': 50,
      'overdraft_limit': 0,
      'available': 50,
    });

/// Each lookup waits on its own completer, so a test decides which tap's answer
/// lands first.
class _ManualStudentCards implements StudentCardRepository {
  final pending = <String, Completer<StudentCard>>{};

  @override
  Future<StudentCard> lookup(String uid) => (pending[uid] = Completer<StudentCard>()).future;

  @override
  Future<List<Map<String, dynamic>>> cardsRaw() async => const [];

  @override
  Future<List<StudentCard>> search(String query) async => const [];

  @override
  Future<StudentCard> link(int accountId, String uid) async => throw UnimplementedError();
}

/// A card tapped while "Checking card…" is still up on the tap sheet. The
/// latest tap is the one the cashier meant: the earlier answer must never be
/// accepted, nor overwrite what the screen says about the newer card.
void main() {
  late _ManualStudentCards repo;
  late StudentCardCubit cubit;

  setUp(() {
    repo = _ManualStudentCards();
    cubit = StudentCardCubit(repo);
  });

  tearDown(() => cubit.close());

  test('a second tap during checking wins even when the first answers last', () async {
    final first = cubit.lookup('CARD1');
    final second = cubit.lookup('CARD2');

    repo.pending['CARD2']!.complete(_student(2, 'Second'));
    expect((await second)?.name, 'Second');

    repo.pending['CARD1']!.complete(_student(1, 'First'));
    expect(await first, isNull);
    expect(cubit.state.card?.name, 'Second');
    expect(cubit.state.status, DataFetchStatus.success);
  });

  test('a superseded tap that fails does not put its error on screen', () async {
    final first = cubit.lookup('CARD1');
    final second = cubit.lookup('CARD2');

    repo.pending['CARD1']!.completeError(ApiException('Card not found.', statusCode: 404));
    expect(await first, isNull);
    expect(cubit.state.errorMessage, isNull);
    expect(cubit.state.status, DataFetchStatus.waiting);

    repo.pending['CARD2']!.complete(_student(2, 'Second'));
    expect((await second)?.name, 'Second');
  });

  test('the latest tap failing still reports its own error', () async {
    final lookup = cubit.lookup('CARD9');

    repo.pending['CARD9']!.completeError(ApiException('Card not found.', statusCode: 404));

    expect(await lookup, isNull);
    expect(cubit.state.errorMessage, 'Card not found.');
  });
}
