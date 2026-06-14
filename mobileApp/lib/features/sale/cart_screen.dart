import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/responsive.dart';
import '../../state/cart_controller.dart';
import '../../widgets/astra_widgets.dart';
import 'cart_widgets.dart';

class CartScreen extends StatelessWidget {
  const CartScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final cart = context.watch<CartController>();

    return Scaffold(
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(icon: Icons.chevron_left, onTap: () => context.pop()),
              title: 'Cart',
              subtitle: '${cart.customerName} · ${cart.stylistName.isEmpty ? 'Me' : cart.stylistName}',
            ),
            Expanded(
              child: cart.isEmpty
                  ? EmptyState(
                      icon: Icons.shopping_bag_outlined,
                      title: 'Your ticket is empty',
                      message: 'Add services from the New Sale screen.',
                      action: AstraButton(label: 'Add services', icon: Icons.add, expand: false, onTap: () => context.pop()),
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
                  child: cartSummaryCard(context, cart, onCharge: () => context.push('/review')),
                ),
              ),
            ),
    );
  }
}
