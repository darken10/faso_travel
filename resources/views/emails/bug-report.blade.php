<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapport de bug — LIPTRA</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: #F1F5F9;
            margin: 0;
            padding: 0;
            color: #1E293B;
        }

        .wrapper {
            max-width: 620px;
            margin: 32px auto;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .08);
        }

        .header {
            background: #0D1B3E;
            padding: 32px 36px;
        }

        .header-title {
            color: #fff;
            font-size: 22px;
            font-weight: 700;
            margin: 0;
        }

        .header-sub {
            color: rgba(255, 255, 255, .55);
            font-size: 13px;
            margin: 6px 0 0;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 14px;
        }

        .body {
            padding: 32px 36px;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #64748B;
            margin: 0 0 8px;
        }

        .field {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        .field-label {
            font-size: 11px;
            color: #94A3B8;
            font-weight: 600;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .field-value {
            font-size: 15px;
            color: #1E293B;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .user-row {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }

        .user-field {
            flex: 1;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 14px 16px;
        }

        .screenshot-link {
            display: inline-block;
            margin-top: 8px;
            padding: 10px 20px;
            background: #2563EB;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .footer {
            background: #F8FAFC;
            border-top: 1px solid #E2E8F0;
            padding: 20px 36px;
            font-size: 12px;
            color: #94A3B8;
            text-align: center;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            background: #FEF2F2;
            color: #EF4444;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: #1E293B;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Header -->
        <div class="header">
            <p class="header-title">📋 Nouveau rapport LIPTRA</p>
            <p class="header-sub">Soumis le {{ $report->created_at->format('d/m/Y à H:i') }} UTC</p>
            @php
                $categories = [
                    'bug' => ['label' => '🐛 Bug applicatif', 'bg' => '#FEF2F2', 'color' => '#EF4444'],
                    'payment' => ['label' => '💳 Problème paiement', 'bg' => '#FFF7ED', 'color' => '#F97316'],
                    'ticket' => ['label' => '🎫 Problème ticket', 'bg' => '#F5F3FF', 'color' => '#8B5CF6'],
                    'suggestion' => ['label' => '💡 Suggestion', 'bg' => '#ECFDF5', 'color' => '#10B981'],
                    'other' => ['label' => '📋 Autre', 'bg' => '#F1F5F9', 'color' => '#64748B'],
                ];
                $cat = $categories[$report->category] ?? $categories['other'];
            @endphp
            <span class="badge" style="background:{{ $cat['bg'] }};color:{{ $cat['color'] }};">
                {{ $cat['label'] }}
            </span>
        </div>

        <!-- Body -->
        <div class="body">

            <!-- Rapport info -->
            <p class="section-title">Détails du rapport</p>

            <div class="field">
                <div class="field-label">Titre</div>
                <div class="field-value">{{ $report->title }}</div>
            </div>

            <div class="field">
                <div class="field-label">Description</div>
                <div class="field-value">{{ $report->description }}</div>
            </div>

            @if ($report->screenshot_url)
                <div class="field">
                    <div class="field-label">Capture d'écran</div>
                    <div style="margin-top:8px;">
                        <a href="{{ $report->screenshot_url }}" class="screenshot-link" target="_blank">
                            📷 Voir la capture d'écran
                        </a>
                    </div>
                </div>
            @endif

            <!-- User info -->
            <p class="section-title" style="margin-top:24px;">Utilisateur</p>

            <div class="field">
                <div class="field-label">Nom</div>
                <div class="field-value">{{ $report->user->name ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="field-label">Email</div>
                <div class="field-value">{{ $report->user->email ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="field-label">ID utilisateur</div>
                <div class="field-value">#{{ $report->user_id }}</div>
            </div>

            <!-- Meta -->
            <p class="section-title" style="margin-top:24px;">Métadonnées</p>

            <div class="field">
                <div class="field-label">ID du rapport</div>
                <div class="field-value" style="font-family:monospace;font-size:13px;">{{ $report->id }}</div>
            </div>
            <div class="field">
                <div class="field-label">Statut initial</div>
                <div class="field-value">
                    <span class="status-badge">🔴 {{ strtoupper($report->status) }}</span>
                </div>
            </div>
        </div>

        <div class="footer">
            Ce rapport a été généré automatiquement par l'application <strong>LIPTRA</strong>.<br>
            Répondez directement à l'utilisateur : <a
                href="mailto:{{ $report->user->email ?? '' }}">{{ $report->user->email ?? '—' }}</a>
        </div>
    </div>
</body>

</html>
