<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poziv za gostovanje</title>
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
        <h1>WizMedik</h1>
        <p>Poziv za gostovanje</p>
    </div>

    <div class="content">
        <p>Poštovani Dr. {{ $doktor->ime }} {{ $doktor->prezime }},</p>
        <p>Klinika <strong>{{ $klinika->naziv }}</strong> vas poziva na gostovanje.</p>

        <div class="info-box">
            <p><strong>Klinika:</strong> {{ $klinika->naziv }}</p>
            <p><strong>Adresa:</strong> {{ $klinika->adresa }}, {{ $klinika->grad }}</p>
            <p><strong>Datum:</strong> {{ \Carbon\Carbon::parse($gostovanje->datum)->format('d.m.Y.') }}</p>
            <p><strong>Vrijeme:</strong> {{ substr($gostovanje->vrijeme_od, 0, 5) }} - {{ substr($gostovanje->vrijeme_do, 0, 5) }}</p>
            @if($gostovanje->napomena)
                <p><strong>Napomena:</strong> {{ $gostovanje->napomena }}</p>
            @endif
        </div>

        <p>Prijavite se na svoj dashboard kako biste prihvatili ili odbili ovaj poziv.</p>
    </div>

    <div class="footer">
        <p>WizMedik - Vaše zdravlje na prvom mjestu</p>
        <p>Ovo je automatska poruka, molimo ne odgovarajte na ovaj email.</p>
    </div>
</body>
</html>
