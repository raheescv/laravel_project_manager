import 'dart:async';

import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/logic/paginated_list_cubit/paginated_list_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

PageResult _page(int page, {int lastPage = 3, int perPage = 2}) => PageResult(
      rows: [
        for (var i = 0; i < perPage; i++) {'id': '${(page - 1) * perPage + i}'},
      ],
      currentPage: page,
      lastPage: lastPage,
      total: lastPage * perPage,
    );

void main() {
  test('load fills the first page', () async {
    final c = PaginatedListCubit(fetch: (p) async => _page(p));
    await c.load();
    expect(c.state.status, DataFetchStatus.success);
    expect(c.state.items, hasLength(2));
    expect(c.state.total, 6);
    expect(c.state.hasMore, isTrue);
  });

  test('loadMore appends rather than replacing', () async {
    final c = PaginatedListCubit(fetch: (p) async => _page(p));
    await c.load();
    await c.loadMore();
    expect(c.state.items, hasLength(4));
    expect(c.state.page, 2);
  });

  test('loadMore stops at the last page', () async {
    final c = PaginatedListCubit(fetch: (p) async => _page(p, lastPage: 1));
    await c.load();
    expect(c.state.hasMore, isFalse);
    await c.loadMore();
    expect(c.state.items, hasLength(2));
  });

  test('a failed first page surfaces the error message', () async {
    final c = PaginatedListCubit(
      fetch: (_) async => throw ApiException('server said no', statusCode: 500),
      errorMessage: 'fallback',
    );
    await c.load();
    expect(c.state.hasFailed, isTrue);
    expect(c.state.errorMessage, 'server said no');
  });

  test('a non-API failure falls back to the screen wording', () async {
    final c = PaginatedListCubit(
      fetch: (_) async => throw const FormatException('bad json'),
      errorMessage: 'Could not load sales.',
    );
    await c.load();
    expect(c.state.errorMessage, 'Could not load sales.');
  });

  test('a failed loadMore keeps the rows already shown', () async {
    var calls = 0;
    final c = PaginatedListCubit(fetch: (p) async {
      calls++;
      if (calls > 1) throw ApiException('nope', statusCode: 500);
      return _page(p);
    });
    await c.load();
    await c.loadMore();
    expect(c.state.items, hasLength(2), reason: 'page 1 survives');
    expect(c.state.hasFailed, isFalse, reason: 'only a failed first page is an error state');
    expect(c.state.loadingMore, isFalse);
  });

  // The regression that shipped in three screens at once.
  test('a filter change mid-loadMore does not wedge pagination', () async {
    final gate = Completer<void>();
    var slowFirst = true;
    final c = PaginatedListCubit(fetch: (p) async {
      if (slowFirst && p == 2) {
        await gate.future; // page 2 hangs while the filters change
        return _page(2);
      }
      return _page(p);
    });

    await c.load();
    final stalled = c.loadMore();       // starts page 2, blocks on the gate
    expect(c.state.loadingMore, isTrue);

    await c.load();                     // filters changed — supersedes it
    slowFirst = false;
    gate.complete();
    await stalled;

    expect(c.state.loadingMore, isFalse,
        reason: 'the discarded page must still clear the in-flight flag');

    await c.loadMore();                 // must not be blocked by a stale flag
    expect(c.state.items, hasLength(4), reason: 'pagination still works');
  });

  test('a superseded first page does not clobber newer rows', () async {
    final gate = Completer<void>();
    var first = true;
    final c = PaginatedListCubit(fetch: (p) async {
      if (first) {
        first = false;
        await gate.future;
        return PageResult(rows: [{'id': 'stale'}], currentPage: 1, lastPage: 1);
      }
      return PageResult(rows: [{'id': 'fresh'}], currentPage: 1, lastPage: 1);
    });

    final stale = c.load();
    await c.load();
    gate.complete();
    await stale;

    expect(c.state.items.single['id'], 'fresh');
  });

  test('removeWhere drops a row and decrements the total', () async {
    final c = PaginatedListCubit(fetch: (p) async => _page(p));
    await c.load();
    c.removeWhere((r) => r['id'] == '0');
    expect(c.state.items, hasLength(1));
    expect(c.state.total, 5);
  });

  test('emitting after close is safe', () async {
    final c = PaginatedListCubit(fetch: (p) async => _page(p));
    final pending = c.load();
    await c.close();
    await pending; // must not throw
  });
}
