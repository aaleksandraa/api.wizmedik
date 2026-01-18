<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0ea5e9; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 30px; }
        .button { display: inline-block; background: #0ea5e9; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .code { font-size: 24px; font-weight: bold; letter-spacing: 5px; color: #0ea5e9; text-align: center; padding: 20px; background: white; border: 2px dashed #0ea5e9; margin: 20px 0; }
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

            <p>Molimo verifikujte vašu email adresu klikom na dugme ispod:</p>

            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="button">Verifikuj Email</a>
            </div>

            <p style="text-align: center; margin: 20px 0;">ILI</p>

            <p>Unesite sljedeći verifikacioni kod:</p>

            <div class="code">{{ $registrationRequest->verification_code }}</div>

            <p><strong>Napomena:</strong> Ovaj link i kod su važeći 7 dana.</p>

            <p>Ako niste vi poslali ovaj zahtjev, molimo ignorišite ovaj email.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} wizMedik. Sva prava zadržana.</p>
            <p>Ovo je automatska poruka, molimo ne odgovarajte na ovaj email.</p>
        </div>
    </div>
</body>
</html>
