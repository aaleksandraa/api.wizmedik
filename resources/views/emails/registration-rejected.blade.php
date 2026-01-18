<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ef4444; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 30px; }
        .reason-box { background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Obavještenje o zahtjevu</h1>
        </div>

        <div class="content">
            <h2>Poštovani/a {{ $registrationRequest->ime }},</h2>

            <p>Hvala na interesovanju za wizMedik platformu.</p>

            <p>Nažalost, vaš zahtjev za registraciju nije odobren u ovom trenutku.</p>

            @if($registrationRequest->rejection_reason)
            <div class="reason-box">
                <strong>Razlog:</strong><br>
                {{ $registrationRequest->rejection_reason }}
            </div>
            @endif

            <p>Za dodatne informacije ili pojašnjenja, molimo kontaktirajte nas na:</p>

            <p style="text-align: center;">
                <strong>Email:</strong> {{ $contactEmail }}<br>
                <strong>Telefon:</strong> +387 XX XXX XXX
            </p>

            <p>Naš tim će vam rado pomoći i odgovoriti na sva vaša pitanja.</p>

            <p>Srdačan pozdrav,<br>wizMedik Tim</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} wizMedik. Sva prava zadržana.</p>
        </div>
    </div>
</body>
</html>
