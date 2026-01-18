<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termin zakazan</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; }
        .info-box { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #0ea5e9; }
        .footer { text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
        .btn { display: inline-block; background: #0ea5e9; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MediBIH</h1>
        @if($recipientType === 'patient')
            <p>Vaš termin je uspješno zakazan!</p>
        @elseif($recipientType === 'doctor')
            <p>Imate novi zakazani termin</p>
        @else
            <p>Novi termin u vašoj klinici</p>
        @endif
    </div>

    <div class="content">
        <div class="info-box">
            <p><strong>Datum i vrijeme:</strong> {{ \Carbon\Carbon::parse($termin->datum_vrijeme)->format('d.m.Y. H:i') }}</p>

            @if($recipientType === 'patient')
                <p><strong>Doktor:</strong> Dr. {{ $termin->doktor->ime }} {{ $termin->doktor->prezime }}</p>
                <p><strong>Specijalnost:</strong> {{ $termin->doktor->specijalnost }}</p>
            @else
                @if($termin->user)
                    <p><strong>Pacijent:</strong> {{ $termin->user->ime }} {{ $termin->user->prezime }}</p>
                    <p><strong>Telefon:</strong> {{ $termin->user->telefon ?? 'Nije naveden' }}</p>
                @else
                    <p><strong>Pacijent:</strong> {{ $termin->guest_ime }} {{ $termin->guest_prezime }}</p>
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
            <p>Molimo vas da dođete 10 minuta prije zakazanog termina.</p>
            <p>Ako trebate otkazati ili premjestiti termin, to možete učiniti putem vaše korisničke stranice.</p>
        @endif
    </div>

    <div class="footer">
        <p>MediBIH - Vaše zdravlje na prvom mjestu</p>
        <p>Ovo je automatska poruka, molimo ne odgovarajte na ovaj email.</p>
    </div>
</body>
</html>
