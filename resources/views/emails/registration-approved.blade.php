<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #10b981; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 30px; }
        .button { display: inline-block; background: #10b981; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .credentials { background: white; border: 2px solid #10b981; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✓ Zahtjev Odobren</h1>
        </div>

        <div class="content">
            <h2>Poštovani/a {{ $registrationRequest->ime }},</h2>

            <p><strong>Čestitamo!</strong> Vaš zahtjev za registraciju je odobren!</p>

            <p>Sada možete pristupiti wizMedik platformi sa sljedećim pristupnim podacima:</p>

            <div class="credentials">
                <strong>Email:</strong> {{ $user->email }}<br>
                <strong>Lozinka:</strong> Vaša lozinka koju ste unijeli prilikom registracije
            </div>

            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="button">Prijavite se</a>
            </div>

            <p><strong>Sljedeći koraci:</strong></p>
            <ul>
                <li>Prijavite se na platformu</li>
                <li>Dopunite vaš profil</li>
                @if($registrationRequest->type === 'doctor')
                    <li>Postavite vaš radni raspored</li>
                    <li>Dodajte usluge koje nudite</li>
                @else
                    <li>Dodajte doktore u vašu kliniku</li>
                    <li>Postavite radne termine</li>
                @endif
                <li>Počnite primati zakazivanja</li>
            </ul>

            <p>Dobrodošli na wizMedik platformu!</p>

            <p>Srdačan pozdrav,<br>wizMedik Tim</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} wizMedik. Sva prava zadržana.</p>
            <p>Za pomoć kontaktirajte nas na: info@wizmedik.ba</p>
        </div>
    </div>
</body>
</html>
