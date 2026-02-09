<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikacija Email Adrese - WizMedik</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0891b2; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px 20px; background: #f9f9f9; }
        .button { display: inline-block; padding: 12px 30px; background: #0891b2; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>WizMedik</h1>
            <h2>Verifikacija Email Adrese</h2>
        </div>

        <div class="content">
            <p>Pozdrav {{ $user->name }},</p>

            <p>Hvala vam što ste se registrovali na WizMedik platformu!</p>

            <p>Da biste završili registraciju, molimo vas da verifikujete vašu email adresu klikom na dugme ispod:</p>

            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="button">Verifikuj Email Adresu</a>
            </div>

            <p>Ako ne možete da kliknete na dugme, kopirajte i zalijepite sljedeći link u vaš browser:</p>
            <p style="word-break: break-all; color: #0891b2;">{{ $verificationUrl }}</p>

            <p>Ovaj link je valjan 60 minuta.</p>

            <p>Ako niste kreirali nalog na WizMedik platformi, molimo vas da ignorišete ovaj email.</p>

            <p>Srdačan pozdrav,<br>WizMedik Tim</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} WizMedik. Sva prava zadržana.</p>
            <p>Ovo je automatski generisan email. Molimo ne odgovarajte na ovu poruku.</p>
        </div>
    </div>
</body>
</html>
