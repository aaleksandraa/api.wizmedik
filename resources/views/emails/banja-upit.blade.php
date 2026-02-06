<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novi upit</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .info-row {
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }
        .label {
            font-weight: bold;
            color: #6366f1;
            display: block;
            margin-bottom: 5px;
        }
        .value {
            color: #1f2937;
        }
        .message-box {
            background: white;
            padding: 15px;
            border-left: 4px solid #6366f1;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">📩 Novi upit</h1>
        <p style="margin: 10px 0 0 0;">{{ $banja->naziv }}</p>
    </div>

    <div class="content">
        <p>Poštovani,</p>
        <p>Primili ste novi upit putem WizMedik platforme:</p>

        <div class="info-row">
            <span class="label">👤 Ime i prezime:</span>
            <span class="value">{{ $upit->ime }}</span>
        </div>

        <div class="info-row">
            <span class="label">📧 Email:</span>
            <span class="value">{{ $upit->email }}</span>
        </div>

        @if($upit->telefon)
        <div class="info-row">
            <span class="label">📞 Telefon:</span>
            <span class="value">{{ $upit->telefon }}</span>
        </div>
        @endif

        @if($upit->datum_dolaska)
        <div class="info-row">
            <span class="label">📅 Datum dolaska:</span>
            <span class="value">{{ \Carbon\Carbon::parse($upit->datum_dolaska)->format('d.m.Y') }}</span>
        </div>
        @endif

        @if($upit->broj_osoba)
        <div class="info-row">
            <span class="label">👥 Broj osoba:</span>
            <span class="value">{{ $upit->broj_osoba }}</span>
        </div>
        @endif

        <div class="message-box">
            <span class="label">💬 Poruka:</span>
            <p class="value" style="margin: 10px 0 0 0; white-space: pre-line;">{{ $upit->poruka }}</p>
        </div>

        <p style="margin-top: 30px;">
            <strong>Molimo vas da odgovorite na ovaj upit u najkraćem mogućem roku.</strong>
        </p>

        <p style="margin-top: 20px;">
            Možete odgovoriti direktno na email adresu: <a href="mailto:{{ $upit->email }}">{{ $upit->email }}</a>
            @if($upit->telefon)
            <br>ili kontaktirati telefonom: <a href="tel:{{ $upit->telefon }}">{{ $upit->telefon }}</a>
            @endif
        </p>
    </div>

    <div class="footer">
        <p>Ova poruka je automatski generisana putem WizMedik platforme.</p>
        <p>© {{ date('Y') }} WizMedik - Zdravstveni portal Bosne i Hercegovine</p>
    </div>
</body>
</html>
