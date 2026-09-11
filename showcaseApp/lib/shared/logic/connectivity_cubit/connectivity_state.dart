part of 'connectivity_cubit.dart';

/// What the app currently believes about reaching its server.
class ConnectivityState extends Equatable {
  const ConnectivityState({this.online = true, this.reconnecting = false});

  /// Whether the last request reached the server. Starts true: claiming
  /// "offline" before a single request has been tried would cry wolf on every
  /// cold start.
  final bool online;

  /// Offline, and the cubit is asking the server on its own whether it is back.
  /// False when there is nothing to ask with — a harness with no probe — so the
  /// banner never promises a reconnect nobody is attempting.
  final bool reconnecting;

  ConnectivityState copyWith({bool? online, bool? reconnecting}) => ConnectivityState(
        online: online ?? this.online,
        reconnecting: reconnecting ?? this.reconnecting,
      );

  @override
  List<Object?> get props => [online, reconnecting];
}
