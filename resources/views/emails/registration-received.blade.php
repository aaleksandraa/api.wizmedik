<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0891b2; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 30px; }
        .info-box { background: white; border-left: 4px solid #0891b2; padding: 15px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>wizMedik</h1>
        </div>

        <div class="content">
            <h2>Poštovani/a {{ $registrationRequest->ime }},</h2>

            <p>Hvala na registraciji na wizMedik platformi!</p>

            <p>Vaš zahtjev za registraciju je uspješno primljen.</p>

            <div class="info-box">
                <strong>Tip registracije:</strong> {{ $registrationRequest->type === 'doctor' ? 'Doktor' : 'Klinika' }}<br>
                <strong>Email:</strong> {{ $registrationRequest->email }}<br>
                @if($registrationRequest->type === 'doctor')
                    <strong>Ime i prezime:</strong> {{ $registrationRequest->ime }} {{ $registrationRequest->prezime }}<br>
                @else
                    <strong>Naziv:</strong> {{ $registrationRequest->naziv }}<br>
                @endif
                <strong>Grad:</strong> {{ $registrationRequest->grad }}
            </div>

            @if($isFree)
                <p>Nakon što verifikujete vašu email adresu, vaš profil će biti automatski aktiviran i moći ćete se prijaviti na platformu.</p>
            @else
                <p>Nakon što verifikujete vašu email adresu, vaš zahtjev će biti pregledan od strane našeg tima u najkraćem roku.</p>
                <p>Javićemo vam se sa ponudom i daljim koracima.</p>
            @endif

            <p>Molimo provjerite vaš email za verifikacioni link.</p>

            <p>Srdačan pozdrav,<br>wizMedik Tim</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} wizMedik. Sva prava zadržana.</p>
        </div>
    </div>
</body>
</html>
