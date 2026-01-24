<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetovanje lozinke</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; }
        .info-box { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #0ea5e9; text-align: center; }
        .footer { text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
        .btn { display: inline-block; background: #0ea5e9; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>WizMedik</h1>
        <p>Resetovanje lozinke</p>
    </div>

    <div class="content">
        <p>Poštovani,</p>
        <p>Primili smo zahtjev za resetovanje lozinke za vaš račun.</p>

        <div class="info-box">
            <p>Kliknite na dugme ispod kako biste resetovali svoju lozinku:</p>
            <a href="{{ env('APP_FRONTEND_URL', 'http://localhost:5173') }}/reset-password?token={{ $token }}&email={{ urlencode($email) }}" class="btn">Resetuj lozinku</a>
        </div>

        <p>Ovaj link će isteći za 60 minuta.</p>
        <p>Ako niste zatražili resetovanje lozinke, možete ignorisati ovaj email.</p>
    </div>

    <div class="footer">
        <p>WizMedik - Vaše zdravlje na prvom mjestu</p>
        <p>Ovo je automatska poruka, molimo ne odgovarajte na ovaj email.</p>
    </div>
</body>
</html>
