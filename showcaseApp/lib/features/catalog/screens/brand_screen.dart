import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/domain/helpers/responsive.dart';
import '../../../shared/domain/models/index.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/router/routes.dart';
import '../../../shared/widgets/chrome/app_top_bar.dart';
import '../../../shared/widgets/chrome/funnel_column.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../logic/funnel_cubit/funnel_cubit.dart';
import 'funnel_navigation.dart';

/// Step 3 — brand, and the most skippable step in the funnel. "Every brand" is
/// a full-width button rather than a link, because most customers want it.
class BrandScreen extends StatelessWidget {
  const BrandScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final funnel = context.watch<FunnelCubit>();
    final state = funnel.state;

    return ShowcaseScaffold(
      topBar: AppTopBar(
        leading: IconSquare(Icons.arrow_back, size: 38, onTap: () => context.go(Routes.size)),
        title: context.isTablet
            ? null
            : FunnelBreadcrumbs(
                state: state,
                current: FunnelStep.brand,
                onReopen: (step) => reopenFunnelStep(context, step),
              ),
      ),
      leftColumn: _LeftColumn(state: state),
      body: switch (state.brandsStatus) {
        DataFetchStatus.failed => MessageState(
            title: 'Brands did not load',
            detail: state.errorMessage,
            actionLabel: 'Try again',
            onAction: funnel.loadBrands,
          ),
        DataFetchStatus.waiting when state.brands.isEmpty => const _BrandSkeleton(),
        _ => _BrandBody(state: state),
      },
      bottomBar: PinnedBar(
        child: PearlButton(
          label: state.size == null
              ? 'Show every brand'
              : 'Show all ${state.brandTotal} in size ${state.size}',
          icon: Icons.arrow_forward,
          onTap: () {
            funnel.skipBrand();
            context.go(Routes.results);
          },
        ),
      ),
    );
  }
}

class _BrandBody extends StatelessWidget {
  const _BrandBody({required this.state});

  final FunnelState state;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    if (state.brands.isEmpty) {
      return MessageState(
        title: 'No brands in this size',
        detail: 'Nothing in ${state.category?.name ?? 'this category'} is stocked in '
            'size ${state.size ?? '—'} right now.',
        actionLabel: 'Choose another size',
        onAction: () => reopenFunnelStep(context, FunnelStep.size),
      );
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(
          PearlMetrics.pad, 18, PearlMetrics.pad, 30),
      children: [
        Text(
          'Which brand?',
          style: PearlText.display(context.isTablet ? 30 : 26).copyWith(color: p.ink),
        ),
        const SizedBox(height: 18),
        SectionHeading(
          'Available',
          meta: '${state.brands.length} with stock'
              '${state.size == null ? '' : ' in size ${state.size}'}',
        ),
        for (final brand in state.brands)
          _BrandRow(
            brand: brand,
            onTap: () {
              context.read<FunnelCubit>().chooseBrand(brand);
              context.go(Routes.results);
            },
          ),
      ],
    );
  }
}

class _BrandRow extends StatelessWidget {
  const _BrandRow({required this.brand, required this.onTap});

  final BrandOption brand;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 16),
        decoration: BoxDecoration(border: Border(bottom: BorderSide(color: p.line))),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              alignment: Alignment.center,
              decoration: BoxDecoration(border: Border.all(color: p.line)),
              child: Text(
                brand.name.isEmpty ? '·' : brand.name.characters.first.toUpperCase(),
                style: PearlText.display(16).copyWith(color: p.ink),
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Text(
                brand.name.toUpperCase(),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: PearlText.productName(12).copyWith(color: p.ink),
              ),
            ),
            Text(
              '${brand.productCount}'.toUpperCase(),
              style: PearlText.micro.copyWith(color: p.faint),
            ),
            const SizedBox(width: 12),
            Icon(Icons.chevron_right, size: 16, color: p.faint),
          ],
        ),
      ),
    );
  }
}

class _BrandSkeleton extends StatelessWidget {
  const _BrandSkeleton();

  @override
  Widget build(BuildContext context) => ListView.separated(
        padding: const EdgeInsets.all(PearlMetrics.pad),
        itemCount: 6,
        separatorBuilder: (_, __) => const SizedBox(height: 14),
        itemBuilder: (_, __) => const SkeletonBlock(height: 46),
      );
}

class _LeftColumn extends StatelessWidget {
  const _LeftColumn({required this.state});

  final FunnelState state;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(14, 16, 14, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          FunnelColumn(
            state: state,
            current: FunnelStep.brand,
            onReopen: (step) => reopenFunnelStep(context, step),
          ),
          const ColumnHeading('Skip ahead'),
          Hairline(
            filled: true,
            padding: const EdgeInsets.all(15),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${state.brandTotal}',
                  style: PearlText.display(28).copyWith(color: p.ink),
                ),
                const SizedBox(height: 6),
                Text(
                  state.size == null
                      ? 'products across every brand'
                      : 'products across every brand in size ${state.size}',
                  style: PearlText.body(11).copyWith(color: p.muted),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
