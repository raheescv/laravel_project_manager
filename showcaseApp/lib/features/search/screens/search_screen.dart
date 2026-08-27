import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/logic/funnel_cubit/funnel_cubit.dart';
import '../../../shared/logic/product_list_cubit/product_list_cubit.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/router/routes.dart';
import '../../../shared/widgets/chrome/app_top_bar.dart';
import '../../../shared/widgets/chrome/idle_reset.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../../../shared/widgets/product_card.dart';
import '../../../l10n/app_localizations.dart';

/// Free search across the catalogue — the way past the funnel when the customer
/// already knows what they want. Reachable from every screen's top bar.
class SearchScreen extends StatelessWidget {
  const SearchScreen({super.key});

  @override
  Widget build(BuildContext context) => BlocProvider<ProductListCubit>(
        // Search obeys the same stock rule as the rest of the app. It is the
        // one place that used not to, on the grounds that a scan should never
        // come back empty because of a filter set on another screen — so the
        // control is put in this bar too, and the empty state names it.
        create: (_) => ProductListCubit(
          filters: ProductFilters(
            inStockOnly: context.read<FunnelCubit>().state.inStockOnly,
          ),
        ),
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
    // Typing is using the panel, and the idle timer cannot see it: the soft
    // keyboard's taps go to the platform, not through the app's pointers. This
    // is the only place a keystroke is visible, so it is where the clock goes
    // back — otherwise a slow typist is sent home mid-search.
    IdleReset.keepAlive(context);
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 320), () {
      if (!mounted) return;
      final query = value.trim();
      final inStockOnly = context.read<FunnelCubit>().state.inStockOnly;
      context.read<ProductListCubit>().apply(
            query.isEmpty
                ? ProductFilters(inStockOnly: inStockOnly)
                : ProductFilters(search: query, inStockOnly: inStockOnly),
          );
    });
  }

  @override
  Widget build(BuildContext context) {
    // Read, not watched: the bar above the results does not depend on them, and
    // rebuilding a focused text field every time a page of results lands is
    // work done to redraw exactly what was already there.
    final list = context.read<ProductListCubit>();

    return BlocListener<FunnelCubit, FunnelState>(
      listenWhen: (a, b) => a.inStockOnly != b.inStockOnly,
      listener: (context, funnel) => list
          .apply(list.state.filters.copyWith(inStockOnly: funnel.inStockOnly)),
      child: ShowcaseScaffold(
        topBar: _SearchBar(
          controller: _controller,
          focus: _focus,
          onChanged: _onChanged,
          onClear: () {
            _controller.clear();
            _onChanged('');
          },
        ),
        body: BlocBuilder<ProductListCubit, ProductListState>(
          builder: (context, state) => _Body(
            state: state,
            query: _controller.text.trim(),
            scroll: _scroll,
          ),
        ),
      ),
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
          IconSquare(Icons.arrow_back,
              size: 40, prominent: true, onTap: () => context.pop()),
          const SizedBox(width: 10),
          const InStockToggle(),
          const SizedBox(width: 10),
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
                        hintText: L.of(context).searchPrompt,
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
            L.of(context).searchEmpty,
            textAlign: TextAlign.center,
            style: PearlText.body(13).copyWith(color: p.faint),
          ),
        ),
      );
    }
    if (state.status.isWaiting && state.items.isEmpty) {
      return Center(
        child: Text(L.of(context).searching.toUpperCase(), style: PearlText.micro.copyWith(color: p.faint)),
      );
    }
    if (state.items.isEmpty) {
      return MessageState(
        title: L.of(context).nothingFound,
        detail: state.filters.inStockOnly
            ? L.of(context).searchNoMatchInStock(query)
            : L.of(context).searchNoMatch(query),
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
