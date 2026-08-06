import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/features/sale/widgets/v3/cart_widgets.dart';

class CartScreen extends StatelessWidget {
  const CartScreen({super.key});

  @override
  Widget build(BuildContext context) {
    // BlocBuilder rather than a plain watch so this rebuilds on the ticket
    // only — Equatable already drops emits that changed nothing.
    return BlocBuilder<CartCubit, CartState>(builder: (context, _) {
      final cart = context.read<CartCubit>();

      return Scaffold(
        body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(
                  icon: Icons.chevron_left,
                  onTap: () {
                    HapticFeedback.selectionClick();
                    context.pop();
                  }),
              title: 'Cart',
              subtitle: '${cart.customerName} · ${cart.stylistName.isEmpty ? 'Me' : cart.stylistName}',
            ),
            Expanded(
              child: cart.isEmpty
                  ? EmptyState(
                      icon: Icons.shopping_bag_outlined,
                      title: 'Your ticket is empty',
                      message: 'Add services from the New Sale screen.',
                      action: AstraButton(
                          label: 'Add services',
                          icon: Icons.add,
                          expand: false,
                          onTap: () {
                            HapticFeedback.selectionClick();
                            context.pop();
                          }),
                    )
                  : MaxWidthBox(
                      maxWidth: 640,
                      child: ListView(
                        keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
                        padding: const EdgeInsets.fromLTRB(16, 14, 16, 180),
                        children: [
                          for (final line in cart.lines) cartLineCard(context, line),
                          const SizedBox(height: 3),
                          OrderDiscountRow(cart: cart),
                        ],
                      ),
                    ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: cart.isEmpty
          ? null
          : SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
                child: MaxWidthBox(
                  maxWidth: 640,
                  child: cartSummaryCard(context, cart, onCharge: () => context.push(Routes.review)),
                ),
              ),
            ),
      );
    });
  }
}
