import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/logic/paginated_list_cubit/paginated_list_cubit.dart';

import '../domain/models/catalog_models.dart';
import '../domain/repository/catalog_repository.dart';

/// The catalog table's rows: the shared infinite-scroll list, fetching with
/// whatever [query] the page's view cubit holds at the time.
class CatalogListCubit extends PaginatedListCubit {
  CatalogListCubit({required CatalogType type, required CatalogQuery Function() query, CatalogRepository? repository})
      : super(
          fetch: (page) => (repository ?? serviceLocator<CatalogRepository>()).list(type, query(), page: page),
          errorMessage: 'Could not load ${type.plural.toLowerCase()}.',
        );
}
