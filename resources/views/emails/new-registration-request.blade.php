<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f59e0b; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 30px; }
        .button { display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .info-table { width: 100%; background: white; border-collapse: collapse; margin: 20px 0; }
        .info-table td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        .info-table td:first-child { font-weight: bold; width: 150px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Novi Zahtjev za Registraciju</h1>
        </div>

        <div class="content">
            <h2>Novi zahtjev čeka pregled</h2>

            <p>Primljen je novi zahtjev za registraciju na wizMedik platformi.</p>

            <table class="info-table">
                <tr>
                    <td>Tip:</td>
                    <td>{{ $registrationRequest->type === 'doctor' ? 'Doktor' : 'Klinika' }}</td>
                </tr>
                @if($registrationRequest->type === 'doctor')
                <tr>
                    <td>Ime i prezime:</td>
                    <td>{{ $registrationRequest->ime }} {{ $registrationRequest->prezime }}</td>
                </tr>
                <tr>
                    <td>Specijalnost:</td>
                    <td>{{ $registrationRequest->specialty->naziv ?? 'N/A' }}</td>
                </tr>
                @else
                <tr>
                    <td>Naziv:</td>
                    <td>{{ $registrationRequest->naziv }}</td>
                </tr>
                <tr>
                    <td>Kontakt osoba:</td>
                    <td>{{ $registrationRequest->ime }}</td>
                </tr>
                @endif
                <tr>
                    <td>Email:</td>
                    <td>{{ $registrationRequest->email }}</td>
                </tr>
                <tr>
                    <td>Telefon:</td>
                    <td>{{ $registrationRequest->telefon }}</td>
                </tr>
                <tr>
                    <td>Grad:</td>
                    <td>{{ $registrationRequest->grad }}</td>
                </tr>
                <tr>
                    <td>Datum:</td>
                    <td>{{ $registrationRequest->created_at->format('d.m.Y H:i') }}</td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td>{{ $registrationRequest->is_verified ? 'Email verifikovan ✓' : 'Čeka verifikaciju' }}</td>
                </tr>
            </table>

            @if($registrationRequest->message)
            <p><strong>Poruka:</strong></p>
            <p style="background: white; padding: 15px; border-left: 4px solid #f59e0b;">
                {{ $registrationRequest->message }}
            </p>
            @endif

            <div style="text-align: center;">
                <a href="{{ $adminUrl }}" class="button">Pregledaj Zahtjev</a>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} wizMedik Admin Panel</p>
        </div>
    </div>
</body>
</html>
