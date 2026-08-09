part of 'connectivity_cubit.dart';

/// What the app currently believes about reaching its server.
enum NetworkStatus {
  /// Nothing has been observed yet — no banner, because claiming "offline"
  /// before a single request has been tried would cry wolf on every cold start.
  unknown,

  /// A request reached the server, whatever the server then answered.
  online,

  /// Either the device reports no network interface at all, or a request failed
  /// to get any answer.
  offline,
}

class ConnectivityState extends Equatable {
  const ConnectivityState({this.status = NetworkStatus.unknown, this.since, this.hasInterface = true});

  final NetworkStatus status;

  /// When the current status began — drives "offline for 12 min".
  final DateTime? since;

  /// Whether the OS reports any usable network interface. A device in airplane
  /// mode is definitely offline; one on wifi is only *probably* online, which is
  /// why this alone never sets [NetworkStatus.online].
  final bool hasInterface;

  bool get isOffline => status == NetworkStatus.offline;

  ConnectivityState copyWith({NetworkStatus? status, DateTime? since, bool? hasInterface}) =>
      ConnectivityState(
        status: status ?? this.status,
        since: since ?? this.since,
        hasInterface: hasInterface ?? this.hasInterface,
      );

  @override
  List<Object?> get props => [status, since, hasInterface];
}
