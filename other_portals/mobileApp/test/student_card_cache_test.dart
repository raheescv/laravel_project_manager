import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/features/student_card/domain/repository/student_card_repository.dart';
import 'package:invo/features/student_card/domain/services/student_card_cache.dart';
import 'package:invo/features/student_card/logic/student_card_cubit/student_card_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import 'support/offline_harness.dart';

Map<String, dynamic> _row(int id, String name, {String uid = '', double balance = 30}) => {
      'account_id': id,
      'name': name,
      'admission_no': 'NR$id',
      'status': 'active',
      'card_uid': uid.isEmpty ? '04A21B${id}C' : uid,
      'card_status': 'active',
      'balance': balance,
      'overdraft_limit': 10,
      'available': balance + 10,
    };

/// A server whose list, single lookups and reachability a test controls.
class _Cards implements StudentCardRepository {
  List<Map<String, dynamic>> list = [_row(1, 'Sara'), _row(2, 'Omar')];
  int listCalls = 0;
  bool listFails = false;
  int lookupCalls = 0;

  /// What a single lookup answers; null = never answers (a dead link).
  Future<StudentCard> Function(String uid)? onLookup;

  @override
  Future<List<Map<String, dynamic>>> cardsRaw() async {
    listCalls++;
    if (listFails) throw DioException.connectionError(requestOptions: RequestOptions(), reason: 'no route');
    return list;
  }

  @override
  Future<StudentCard> lookup(String uid) {
    lookupCalls++;
    return onLookup?.call(uid) ?? Completer<StudentCard>().future;
  }

  @override
  Future<List<StudentCard>> search(String query) async => const [];

  @override
  Future<StudentCard> link(int accountId, String uid) async => throw UnimplementedError();
}

/// A tap is answered from the card list kept on the device, so the cashier
/// never waits on a round trip — and the server still has the last word.
void main() {
  late _Cards server;
  late StudentCardCache cache;

  setUp(() async {
    await setUpOfflineHarness();
    await registerBranchContext();
    server = _Cards();
    serviceLocator
      ..registerSingleton<CatalogSnapshotRepository>(CatalogSnapshotService())
      ..registerSingleton<StudentCardRepository>(server);
    cache = StudentCardCache();
    serviceLocator.registerSingleton<StudentCardCache>(cache);
    await cache.refresh();
  });

  tearDown(() async {
    await cache.dispose();
    await tearDownOfflineHarness();
  });

  test('a tapped card or a typed admission number is found without the server', () {
    expect(cache.find('04:a2:1b:1c')?.name, 'Sara');
    expect(cache.find('nr2')?.name, 'Omar');
    expect(cache.find('FFFFFFFF'), isNull);
    expect(server.lookupCalls, 0);
  });

  test('the tap lookup answers from the device even when the server never does', () async {
    final cubit = StudentCardCubit(server);
    addTearDown(cubit.close);

    final card = await cubit.lookup('04A21B1C').timeout(const Duration(milliseconds: 200));

    expect(card?.name, 'Sara');
    // Still confirmed with the server, in the background.
    expect(server.lookupCalls, 1);
  });

  test('a card the device does not hold is asked about live, then kept', () async {
    server.onLookup = (uid) async => StudentCard.fromJson(_row(3, 'New Kid', uid: uid));
    final cubit = StudentCardCubit(server);
    addTearDown(cubit.close);

    expect((await cubit.lookup('ABCDEF01'))?.name, 'New Kid');
    expect(cache.find('ABCDEF01')?.name, 'New Kid');
  });

  test('the list survives a restart: it is read back from the device', () async {
    final again = StudentCardCache();
    addTearDown(again.dispose);
    final listCallsBefore = server.listCalls;

    // The fetch fails (offline at boot) — the stored copy still answers.
    server.listFails = true;
    await again.refresh();

    expect(server.listCalls, listCallsBefore + 1);
    expect(again.find('04A21B1C')?.name, 'Sara');
  });

  test('the list is not refetched while it is fresh', () async {
    final calls = server.listCalls;
    await cache.refresh();
    expect(server.listCalls, calls);

    await cache.refresh(force: true);
    expect(server.listCalls, calls + 1);
  });

  group('the server confirms what the device answered', () {
    late CartCubit cart;

    setUp(() => cart = CartCubit());
    tearDown(() => cart.close());

    test('a newer balance replaces the cached one on the ticket', () async {
      final held = cache.find('04A21B1C')!;
      cart.setStudent(held);
      server.onLookup = (_) async => StudentCard.fromJson(_row(1, 'Sara', balance: 5));

      await cache.confirm(held);
      await Future<void>.delayed(Duration.zero);

      expect(cart.student?.balance, 5);
      expect(cache.find('04A21B1C')?.balance, 5);
    });

    test('a card refused since the list was taken comes off the ticket', () async {
      final held = cache.find('04A21B1C')!;
      cart.setStudent(held);
      server.onLookup = (_) async => throw ApiException("Sara's card is blocked.", statusCode: 422);
      final said = <String?>[];
      final sub = cache.confirmations.listen((c) => said.add(c.error));
      addTearDown(sub.cancel);

      await cache.confirm(held);
      await Future<void>.delayed(Duration.zero);

      expect(cart.student, isNull);
      expect(said, ["Sara's card is blocked."]);
      expect(cache.find('04A21B1C'), isNull);
    });

    test('an unreachable server leaves the ticket as it is', () async {
      final held = cache.find('04A21B1C')!;
      cart.setStudent(held);
      server.onLookup = (_) async =>
          throw DioException.connectionError(requestOptions: RequestOptions(), reason: 'no route');

      await cache.confirm(held);
      await Future<void>.delayed(Duration.zero);

      expect(cart.student?.name, 'Sara');
      expect(cache.find('04A21B1C')?.name, 'Sara');
    });
  });
}
