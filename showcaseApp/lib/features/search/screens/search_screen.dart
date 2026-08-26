import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/router/routes.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../../../shared/widgets/product_card.dart';
import '../../catalog/logic/product_list_cubit/product_list_cubit.dart';

/// Free search across the catalogue — the way past the funnel when the customer
/// already knows what they want. Reachable from every screen's top bar.
class SearchScreen extends StatelessWidget {
  const SearchScreen({super.key});

  @override
  Widget build(BuildContext context) => BlocProvider<ProductListCubit>(
        create: (_) => ProductListCubit(),
        child: const _SearchView(),
      );
}

class _SearchView extends StatefulWidget {
  const _SearchView();

  @override
  State<_SearchView> createState() => _SearchViewState();
}

class _SearchViewState extends State<_SearchView> {
  final TextEditingController _controller = TextEditingController();
  final FocusNode _focus = FocusNode();
  final ScrollController _scroll = ScrollController();
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _scroll.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) => _focus.requestFocus());
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _controller.dispose();
    _focus.dispose();
    _scroll
      ..removeListener(_onScroll)
      ..dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_scroll.hasClients) return;
    final remaining = _scroll.position.maxScrollExtent - _scroll.position.pixels;
    if (remaining < 900) context.read<ProductListCubit>().loadMore();
  }

  /// Debounced so typing a product code is one request, not eight.
  void _onChanged(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 320), () {
      if (!mounted) return;
      final query = value.trim();
      context.read<ProductListCubit>().apply(
            query.isEmpty
                ? const ProductFilters()
                : ProductFilters(search: query),
          );
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<ProductListCubit>().state;
    final query = _controller.text.trim();

    return ShowcaseScaffold(
      showRail: false,
      topBar: _SearchBar(
        controller: _controller,
        focus: _focus,
        onChanged: _onChanged,
        onClear: () {
          _controller.clear();
          _onChanged('');
        },
      ),
      body: _Body(state: state, query: query, scroll: _scroll),
    );
  }
}

class _SearchBar extends StatelessWidget {
  const _SearchBar({
    required this.controller,
    required this.focus,
    required this.onChanged,
    required this.onClear,
  });

  final TextEditingController controller;
  final FocusNode focus;
  final ValueChanged<String> onChanged;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: p.line))),
      child: Row(
        children: [
          IconSquare(Icons.arrow_back, size: 40, onTap: () => context.pop()),
          const SizedBox(width: 12),
          Expanded(
            child: Container(
              height: 40,
              padding: const EdgeInsets.symmetric(horizontal: 13),
              decoration: BoxDecoration(color: p.surface, border: Border.all(color: p.line)),
              child: Row(
                children: [
                  Icon(Icons.search, size: 16, color: p.faint),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextField(
                      controller: controller,
                      focusNode: focus,
                      onChanged: onChanged,
                      textInputAction: TextInputAction.search,
                      style: PearlText.body(13).copyWith(color: p.ink),
                      cursorColor: p.ink,
                      decoration: InputDecoration(
                        isDense: true,
                        border: InputBorder.none,
                        hintText: 'Name, code or barcode',
                        hintStyle: PearlText.body(13).copyWith(color: p.faint),
                      ),
                    ),
                  ),
                  ValueListenableBuilder<TextEditingValue>(
                    valueListenable: controller,
                    builder: (context, value, _) => value.text.isEmpty
                        ? const SizedBox.shrink()
                        : InkWell(
                            onTap: onClear,
                            child: Icon(Icons.close, size: 16, color: p.faint),
                          ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _Body extends StatelessWidget {
  const _Body({required this.state, required this.query, required this.scroll});

  final ProductListState state;
  final String query;
  final ScrollController scroll;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    if (query.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Text(
            'Type a name, a product code, or scan a barcode.',
            textAlign: TextAlign.center,
            style: PearlText.body(13).copyWith(color: p.faint),
          ),
        ),
      );
    }
    if (state.status.isWaiting && state.items.isEmpty) {
      return Center(
        child: Text('Searching'.toUpperCase(), style: PearlText.micro.copyWith(color: p.faint)),
      );
    }
    if (state.items.isEmpty) {
      return MessageState(
        title: 'Nothing found',
        detail: 'No product matches "$query" in this store.',
      );
    }
    return ProductGrid(
      products: state.items,
      controller: scroll,
      padding: const EdgeInsets.fromLTRB(PearlMetrics.pad, 18, PearlMetrics.pad, 40),
      onTap: (product) => context.push(Routes.productById(product.id)),
    );
  }
}
