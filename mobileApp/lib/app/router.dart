import 'package:go_router/go_router.dart';

import '../features/admin/day_session_screen.dart';
import '../features/auth/login_screen.dart';
import '../features/profile/change_password_screen.dart';
import '../features/profile/change_pin_screen.dart';
import '../features/profile/edit_profile_screen.dart';
import '../features/profile/profile_screen.dart';
import '../features/sale/cart_screen.dart';
import '../features/sale/invoice_screen.dart';
import '../features/sale/new_sale_screen.dart';
import '../features/sale/review_pay_screen.dart';
import '../features/sale_return/new_sale_return_screen.dart';
import '../features/sale_return/return_pick_invoice_screen.dart';
import '../features/sale_return/return_receipt_screen.dart';
import '../features/sale_return/return_review_screen.dart';
import '../features/sales/sales_list_screen.dart';
import '../features/sales_returns/sales_returns_list_screen.dart';
import '../features/settings/print_settings_screen.dart';
import '../features/shell/home_shell.dart';
import '../models/models.dart';
import '../state/auth_controller.dart';

GoRouter createRouter(AuthController auth) {
  return GoRouter(
    initialLocation: '/login',
    refreshListenable: auth,
    redirect: (context, state) {
      final loggedIn = auth.status == AuthStatus.signedIn;
      final atLogin = state.matchedLocation == '/login';
      if (auth.status == AuthStatus.unknown) return null;
      if (!loggedIn) return atLogin ? null : '/login';
      if (atLogin) return (auth.user?.isAdmin ?? false) ? '/home' : '/sale';
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(
        path: '/home',
        builder: (_, state) => HomeShell(initialTab: int.tryParse(state.uri.queryParameters['tab'] ?? '') ?? 0),
      ),
      GoRoute(path: '/sale', builder: (_, __) => const NewSaleScreen()),
      GoRoute(path: '/cart', builder: (_, __) => const CartScreen()),
      GoRoute(path: '/review', builder: (_, __) => const ReviewPayScreen()),
      GoRoute(
        path: '/invoice',
        builder: (_, state) => InvoiceScreen(sale: state.extra as Sale),
      ),
      GoRoute(path: '/sales', builder: (_, __) => const SalesListScreen()),
      GoRoute(path: '/sales-returns', builder: (_, __) => const SalesReturnListScreen()),
      GoRoute(path: '/sale-return', builder: (_, __) => const NewSaleReturnScreen()),
      GoRoute(path: '/sale-return/pick', builder: (_, __) => const ReturnPickInvoiceScreen()),
      GoRoute(path: '/sale-return/review', builder: (_, __) => const ReturnReviewScreen()),
      GoRoute(
        path: '/return-receipt',
        builder: (_, state) => ReturnReceiptScreen(saleReturn: state.extra as SaleReturn),
      ),
      GoRoute(path: '/profile', builder: (_, __) => const ProfileScreen()),
      GoRoute(path: '/day-session', builder: (_, __) => const DaySessionScreen()),
      GoRoute(path: '/change-pin', builder: (_, __) => const ChangePinScreen()),
      GoRoute(path: '/change-password', builder: (_, __) => const ChangePasswordScreen()),
      GoRoute(path: '/edit-profile', builder: (_, __) => const EditProfileScreen()),
      GoRoute(path: '/print-settings', builder: (_, __) => const PrintSettingsScreen()),
    ],
  );
}
