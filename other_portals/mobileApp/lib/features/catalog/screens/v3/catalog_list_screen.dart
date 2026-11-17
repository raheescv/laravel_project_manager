import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/logic/paginated_list_cubit/paginated_list_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/nav_scaffold.dart';

import '../../domain/models/catalog_models.dart';
import '../../logic/catalog_list_cubit.dart';
import '../../logic/catalog_options_cubit/catalog_options_cubit.dart';
import '../../logic/catalog_view_cubit/catalog_view_cubit.dart';
import '../../widgets/v3/catalog_gallery.dart';
import '../../widgets/v3/catalog_hero.dart';
import '../../widgets/v3/catalog_table.dart';
import '../../widgets/v3/catalog_toolbar.dart';

/// Settings → Catalog → Products / Services: KPIs, search and filters over a
/// full table (sortable headers, column visibility) or a photo gallery. Rows
/// open the full-page Add / Edit form.
class CatalogListScreen extends StatelessWidget {
  const CatalogListScreen({super.key, required this.type, this.onBack, this.onOpen, this.revision = 0});

  final CatalogType type;

  /// Set when hosted in the home shell, which already provides the navigation
  /// and keeps this page alive across tabs; back returns to Settings there.
  /// Unset, the page is a pushed route with its own [NavScaffold].
  final VoidCallback? onBack;

  /// In the shell: opens the Add (null) / Edit form as a shell page too, so a
  /// half-filled form survives a trip to another module. Unset, it is pushed.
  final ValueChanged<int?>? onOpen;

  /// Bumped by the shell when its form saved or deleted — the list re-reads.
  final int revision;

  @override
  Widget build(BuildContext context) {
    return MultiBlocProvider(
      providers: [
        BlocProvider(create: (ctx) {
          // The filter chips name their choices from these.
          ctx.read<CatalogOptionsCubit>().ensureLoaded();
          return CatalogViewCubit(type: type, storage: serviceLocator(), userId: ctx.read<AuthCubit>().user?.id);
        }),
        BlocProvider(
          create: (ctx) => CatalogListCubit(type: type, query: () => ctx.read<CatalogViewCubit>().state.query)..load(),
        ),
      ],
      child: onBack == null
          ? NavScaffold(child: _CatalogListBody(type: type))
          : _CatalogListBody(type: type, onBack: onBack, onOpen: onOpen, revision: revision),
    );
  }
}

class _CatalogListBody extends StatefulWidget {
  const _CatalogListBody({required this.type, this.onBack, this.onOpen, this.revision = 0});

  final CatalogType type;
  final VoidCallback? onBack;
  final ValueChanged<int?>? onOpen;
  final int revision;

  @override
  State<_CatalogListBody> createState() => _CatalogListBodyState();
}

class _CatalogListBodyState extends State<_CatalogListBody> {
  CatalogType get type => widget.type;

  @override
  void didUpdateWidget(_CatalogListBody old) {
    super.didUpdateWidget(old);
    if (widget.revision != old.revision) context.read<CatalogListCubit>().load();
  }

  Future<void> _open(BuildContext context, {int? id}) async {
    if (widget.onOpen != null) return widget.onOpen!(id);
    final list = context.read<CatalogListCubit>();
    // Always re-read on the way back: "Save & New" may have added several.
    await context.push<bool>(id == null ? Routes.catalogNew(type.key) : Routes.catalogEdit(type.key, id));
    await list.load();
  }

  List<CatalogKpi> _kpis(Map<String, num> summary) {
    return [
      (label: type.plural, value: '${summary['total'] ?? 0}', tone: null),
      type.isProduct
          ? (label: 'Selling', value: '${summary['selling'] ?? 0}', tone: null)
          : (label: 'Avg time', value: '${(summary['avg_time'] ?? 0).round()}m', tone: null),
      (label: 'Disabled', value: '${summary['disabled'] ?? 0}', tone: null),
    ];
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final canCreate = context.read<AuthCubit>().hasPermission(type.createPermission);
    final pad = context.isTablet ? 22.0 : 14.0;
    return BlocListener<CatalogViewCubit, CatalogViewState>(
      listenWhen: (a, b) => a.query != b.query,
      listener: (context, _) => context.read<CatalogListCubit>().load(),
      child: ColoredBox(
        color: p.canvas,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            BlocSelector<CatalogListCubit, PaginatedListState, Map<String, num>>(
              selector: (s) => s.summary,
              builder: (context, summary) => CatalogHero(
                title: type.plural,
                subtitle: 'Settings › Catalog',
                onBack: widget.onBack ?? () => context.canPop() ? context.pop() : context.go(Routes.homeTab(3)),
                actions: [
                  if (canCreate) HeroIconButton(icon: Icons.add, label: 'New', onTap: () => _open(context)),
                ],
                kpis: _kpis(summary),
              ),
            ),
            Padding(padding: EdgeInsets.fromLTRB(pad, 12, pad, 10), child: const CatalogToolbar()),
            Padding(padding: EdgeInsets.fromLTRB(pad, 0, pad, 10), child: const CatalogFilterBar()),
            Expanded(
              child: Padding(
                padding: EdgeInsets.symmetric(horizontal: pad),
                child: _Results(type: type, onOpen: (row) => _open(context, id: asNum(row['id']).toInt())),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Results extends StatelessWidget {
  const _Results({required this.type, required this.onOpen});

  final CatalogType type;
  final ValueChanged<Map<String, dynamic>> onOpen;

  @override
  Widget build(BuildContext context) {
    final list = context.read<CatalogListCubit>();
    final view = context.watch<CatalogViewCubit>().state;
    // The floating bottom nav covers the end of the body on a phone.
    final inset = MediaQuery.paddingOf(context).bottom + (context.isTablet ? 16 : 8);
    return BlocBuilder<CatalogListCubit, PaginatedListState>(
      builder: (context, state) {
        if (state.isLoading && state.items.isEmpty) return const Center(child: CircularProgressIndicator());
        if (state.isEmptyResult) {
          return SingleChildScrollView(
            padding: EdgeInsets.only(bottom: inset),
            child: EmptyState(
              icon: state.hasFailed ? Icons.cloud_off_outlined : type.icon,
              title: state.hasFailed ? (state.errorMessage ?? 'Could not load') : 'No ${type.plural.toLowerCase()} found',
              message: state.hasFailed ? null : 'Try another search or clear the filters.',
              action: state.hasFailed ? AstraButton(label: 'Try again', icon: Icons.refresh, onTap: list.load) : null,
            ),
          );
        }
        final footer = _Footer(loading: state.loadingMore, shown: state.items.length, total: state.total);
        return view.gallery
            ? CatalogGallery(type: type, rows: state.items, onOpen: onOpen, onNearEnd: list.loadMore, footer: footer, bottomInset: inset)
            : CatalogTable(
                type: type,
                rows: state.items,
                columns: view.shownColumns,
                showImages: view.showImages,
                query: view.query,
                onSort: context.read<CatalogViewCubit>().sortBy,
                onOpen: onOpen,
                onNearEnd: list.loadMore,
                footer: footer,
                bottomInset: inset,
              );
      },
    );
  }
}

class _Footer extends StatelessWidget {
  const _Footer({required this.loading, required this.shown, required this.total});

  final bool loading;
  final int shown;
  final int total;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    if (loading) return const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2));
    return Text('Showing $shown of $total', style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted));
  }
}
