import 'package:go_router/go_router.dart';

import '../../../features/catalog/screens/brand_screen.dart';
import '../../../features/catalog/screens/category_screen.dart';
import '../../../features/catalog/screens/results_screen.dart';
import '../../../features/catalog/screens/size_screen.dart';
import '../../../features/product/screens/product_screen.dart';
import '../../../features/product/screens/spin_viewer_screen.dart';
import '../../../features/scan/screens/scan_screen.dart';
import '../../../features/search/screens/search_screen.dart';
import '../../domain/helpers/formatters.dart';
import '../../domain/models/index.dart';
import 'routes.dart';

/// Where the app opens. Normally the funnel's first step; a build can override
/// it with `--dart-define=START_AT=/product/3` to land straight on a screen
/// while working on it. Empty in every real build.
const String _startAt = String.fromEnvironment('START_AT');

/// The whole app is one read path, so there are no guards and no redirects —
/// every route is reachable without a session.
GoRouter createRouter() => GoRouter(
      initialLocation: _startAt.isEmpty ? Routes.size : _startAt,
      routes: [
        GoRoute(
          path: Routes.size,
          builder: (context, state) => const SizeScreen(),
        ),
        GoRoute(
          path: Routes.category,
          builder: (context, state) => const CategoryScreen(),
        ),
        GoRoute(
          path: Routes.brand,
          builder: (context, state) => const BrandScreen(),
        ),
        GoRoute(
          path: Routes.results,
          builder: (context, state) => const ResultsScreen(),
        ),
        GoRoute(
          path: Routes.search,
          builder: (context, state) => const SearchScreen(),
        ),
        GoRoute(
          path: Routes.scan,
          builder: (context, state) => const ScanScreen(),
        ),
        GoRoute(
          path: Routes.product,
          builder: (context, state) => ProductScreen(
            productId: asInt(state.pathParameters['id']),
          ),
          routes: [
            GoRoute(
              path: Routes.spin,
              builder: (context, state) => SpinViewerScreen(
                productId: asInt(state.pathParameters['id']),
                // The product page hands its already-loaded product over rather
                // than making the viewer fetch it again; a cold deep link has no
                // extra and the viewer loads it itself.
                product: state.extra is Product ? state.extra! as Product : null,
              ),
            ),
          ],
        ),
      ],
    );
