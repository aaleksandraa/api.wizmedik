<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termin otkazan</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; }
        .info-box { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ef4444; }
        .footer { text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
        .btn { display: inline-block; background: #0ea5e9; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 15px; }
        .cancelled-by { background: #fef2f2; padding: 10px 15px; border-radius: 6px; margin: 15px 0; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>WizMedik</h1>
        @if($recipientType === 'patient')
            <p>Vaš termin je otkazan</p>
        @elseif($recipientType === 'doctor')
            <p>Termin je otkazan</p>
        @else
            <p>Termin u vašoj klinici je otkazan</p>
        @endif
    </div>

    <div class="content">
        <div class="cancelled-by">
            @if($cancelledBy === 'patient')
                <strong>Otkazao:</strong> Pacijent
            @elseif($cancelledBy === 'doctor')
                <strong>Otkazao:</strong> Doktor
            @else
                <strong>Otkazala:</strong> Klinika
            @endif
        </div>

        <div class="info-box">
            <p><strong>Datum i vrijeme:</strong> {{ \Carbon\Carbon::parse($termin->datum_vrijeme)->format('d.m.Y. H:i') }}</p>

            @if($recipientType === 'patient')
                <p><strong>Doktor:</strong> Dr. {{ $termin->doktor->ime }} {{ $termin->doktor->prezime }}</p>
                <p><strong>Specijalnost:</strong> {{ $termin->doktor->specijalnost }}</p>
                @if($termin->doktor->klinika)
                    <p><strong>Klinika:</strong> {{ $termin->doktor->klinika->naziv }}</p>
                @endif
            @else
                @if($termin->user)
                    <p><strong>Pacijent:</strong> {{ $termin->user->ime }} {{ $termin->user->prezime }}</p>
                    <p><strong>Email:</strong> {{ $termin->user->email }}</p>
                    <p><strong>Telefon:</strong> {{ $termin->user->telefon ?? 'Nije naveden' }}</p>
                @else
                    <p><strong>Pacijent:</strong> {{ $termin->guest_ime }} {{ $termin->guest_prezime }}</p>
                    <p><strong>Email:</strong> {{ $termin->guest_email }}</p>
                    <p><strong>Telefon:</strong> {{ $termin->guest_telefon }}</p>
                @endif
            @endif

            @if($termin->usluga)
                <p><strong>Usluga:</strong> {{ $termin->usluga->naziv }}</p>
            @endif

            @if($termin->razlog)
                <p><strong>Razlog posjete:</strong> {{ $termin->razlog }}</p>
            @endif
        </div>

        @if($recipientType === 'patient')
            <p>Žao nam je što je vaš termin otkazan. Možete zakazati novi termin putem naše platforme.</p>
            <p style="text-align: center;">
                <a href="{{ config('app.frontend_url') }}/doktori" class="btn">Zakaži novi termin</a>
            </p>
        @elseif($recipientType === 'doctor')
            <p>Termin slot je sada slobodan za nove rezervacije.</p>
        @else
            <p>Termin slot kod Dr. {{ $termin->doktor->ime }} {{ $termin->doktor->prezime }} je sada slobodan.</p>
        @endif

        <p style="margin-top: 20px; font-size: 13px; color: #64748b;">
            Ako imate pitanja, možete odgovoriti direktno na ovaj email.
        </p>
    </div>

    <div class="footer">
        <p>WizMedik - Vaše zdravlje na prvom mjestu</p>
        <p>Ovo je automatska poruka sa WizMedik platforme.</p>
    </div>
</body>
</html>
