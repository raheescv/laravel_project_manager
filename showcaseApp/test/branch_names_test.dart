import 'package:flutter_test/flutter_test.dart';
import 'package:showcase/features/product/screens/product_screen.dart';

/// The availability strip drops a leading word every shop shares, so the names
/// fit on one line without becoming codes. It must only ever do that when it is
/// safe — a wrong trim renames someone's shop.
void main() {
  test('drops a prefix all of them share', () {
    expect(
      shortenBranchNames(const [
        'SIZERUN MALL OF QATAR',
        'SIZERUN GALLERIA MALL',
        'SIZERUN DOHA MALL',
      ]),
      ['MALL OF QATAR', 'GALLERIA MALL', 'DOHA MALL'],
    );
  });

  test('leaves them alone when the prefix is not shared', () {
    // One shop outside the chain is enough to make the prefix meaningful.
    const names = ['SIZERUN DOHA MALL', 'HNM', 'ONLINE'];
    expect(shortenBranchNames(names), names);
  });

  test('never strips a name down to nothing', () {
    // "SIZERUN" alongside "SIZERUN DOHA MALL" shares the whole of one name.
    const names = ['SIZERUN', 'SIZERUN DOHA MALL'];
    expect(shortenBranchNames(names), names);
  });

  test('strips more than one shared word', () {
    expect(
      shortenBranchNames(const ['SIZERUN DOHA MALL', 'SIZERUN DOHA CITY']),
      ['MALL', 'CITY'],
    );
  });

  test('a single shop keeps its full name', () {
    expect(shortenBranchNames(const ['SIZERUN DOHA MALL']), ['SIZERUN DOHA MALL']);
  });

  test('tolerates ragged spacing from the catalogue', () {
    expect(
      shortenBranchNames(const ['SIZERUN  MALL OF QATAR ', ' SIZERUN GALLERIA MALL']),
      ['MALL OF QATAR', 'GALLERIA MALL'],
    );
  });
}
