@extends('layout')

@section('title','Notifications')

@php
    use Illuminate\Support\Carbon;

    // type de notification (valeur de TypeNotification) → tonalité visuelle
    $typeTone = [
        // succès (vert)
        'PAYER' => 'success', 'Payer Ticket' => 'success', 'VALIDATED' => 'success',
        'ACTIVE' => 'success', 'RECEIVED' => 'success', 'Recevoir Ticket' => 'success',
        'SENDED' => 'success', 'REDELIVERED' => 'success', 'REGENERATED' => 'success',
        // attention (orange)
        'MISE PAUSE' => 'warning', 'VOYAGE_RETARDE' => 'warning', 'REPORTED' => 'warning',
        'UPDATED' => 'warning', 'Update Ticket' => 'warning', 'Transaction Ticket' => 'warning',
        // danger (rouge)
        'VOYAGE_ANNULE' => 'danger', 'CLOSED' => 'danger',
    ];

    $toneChip = [
        'success' => 'bg-success-100 text-success-600 dark:bg-success-500/15 dark:text-success-500',
        'warning' => 'bg-warning-100 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
        'danger'  => 'bg-danger-100 text-danger-600 dark:bg-danger-500/15 dark:text-danger-500',
        'primary' => 'bg-primary-100 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400',
    ];
    $toneAccent = [
        'success' => 'border-l-success-500',
        'warning' => 'border-l-warning-500',
        'danger'  => 'border-l-danger-500',
        'primary' => 'border-l-primary-500',
    ];
    $toneDot = [
        'success' => 'bg-success-500', 'warning' => 'bg-warning-500',
        'danger'  => 'bg-danger-500',  'primary' => 'bg-primary-500',
    ];
    $toneIcon = [
        'success' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'warning' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
        'danger'  => 'M9.75 9.75l4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'primary' => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0',
    ];

    $bucketOf = function (Carbon $d): string {
        if ($d->isToday())      return "Aujourd'hui";
        if ($d->isYesterday())  return 'Hier';
        if ($d->gte(now()->subDays(7)))       return 'Cette semaine';
        if ($d->gte(now()->startOfMonth()))   return 'Ce mois-ci';
        return 'Plus ancien';
    };

    $unreadCount = $notifications->whereNull('read_at')->count();
    $grouped     = $notifications->groupBy(fn ($n) => $bucketOf($n->created_at));
@endphp

@section('content')
    <div class="max-w-3xl mx-auto">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-bold text-surface-900 dark:text-white">Notifications</h1>
                    @if($unreadCount > 0)
                        <span class="inline-flex items-center justify-center min-w-6 h-6 px-2 rounded-full bg-primary-500 text-white text-xs font-semibold">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </div>
                <p class="text-surface-500 dark:text-surface-400 mt-1">Restez informé de vos voyages et transactions</p>
            </div>

            @if($unreadCount > 0)
                <form action="{{ route('user.notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 hover:bg-primary-100 dark:hover:bg-primary-900/40 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Tout marquer comme lu
                    </button>
                </form>
            @endif
        </div>

        {{-- Notifications --}}
        @forelse($grouped as $bucket => $items)
            <section class="mb-6">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-surface-400 dark:text-surface-500 mb-3 px-1">
                    {{ $bucket }}
                </h2>

                <div class="space-y-2.5">
                    @foreach($items as $notification)
                        @php
                            $rawType = $notification->data['type'] ?? null;
                            $type    = is_array($rawType) ? null : $rawType;
                            $tone    = $typeTone[$type] ?? 'primary';
                            $isUnread = is_null($notification->read_at);
                        @endphp

                        <a href="{{ route('user.notifications.show', $notification->id) }}"
                           @class([
                               'group relative flex items-start gap-4 rounded-2xl border p-4 transition-all duration-300',
                               'hover:shadow-elevated hover:-translate-y-0.5',
                               'bg-white dark:bg-surface-800/60 border-surface-100 dark:border-surface-700' => !$isUnread,
                               'bg-primary-50/40 dark:bg-primary-900/10 border-l-4 '.$toneAccent[$tone].' border-y-transparent border-r-transparent dark:border-y-surface-700/50 dark:border-r-surface-700/50' => $isUnread,
                           ])>
                            {{-- Icône --}}
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 {{ $isUnread ? $toneChip[$tone] : 'bg-surface-100 dark:bg-surface-700/60 text-surface-400' }}">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $toneIcon[$tone] }}" />
                                </svg>
                            </div>

                            {{-- Contenu --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 @class([
                                        'truncate group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors',
                                        'font-semibold text-surface-900 dark:text-white' => $isUnread,
                                        'font-medium text-surface-600 dark:text-surface-300' => !$isUnread,
                                    ])>
                                        {{ $notification->data['title'] ?? 'Notification' }}
                                    </h3>
                                    @if($isUnread)
                                        <span class="mt-1.5 w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $toneDot[$tone] }}"></span>
                                    @endif
                                </div>

                                <p class="text-sm text-surface-600 dark:text-surface-400 line-clamp-2 mt-0.5">
                                    {{ $notification->data['message'] ?? '' }}
                                </p>

                                <div class="flex items-center gap-1.5 mt-2 text-xs text-surface-400 dark:text-surface-500">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span>{{ $notification->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @empty
            {{-- Empty state --}}
            <div class="rounded-2xl border border-dashed border-surface-200 dark:border-surface-700 text-center py-16 px-6">
                <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-surface-100 dark:bg-surface-800 flex items-center justify-center">
                    <svg class="w-10 h-10 text-surface-400 dark:text-surface-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-surface-900 dark:text-white mb-2">Aucune notification</h3>
                <p class="text-surface-500 dark:text-surface-400 max-w-sm mx-auto">
                    Vous n'avez aucune notification pour le moment. Vos alertes de voyage et de paiement s'afficheront ici.
                </p>
            </div>
        @endforelse
    </div>
@endsection
