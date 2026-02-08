<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ocijenite Vaš termin</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #0891b2;">Poštovani/a {{ $ime }},</h2>
        
        <p>Hvala Vam što ste koristili Zdravlje BiH platformu za zakazivanje termina.</p>
        
        <p>Vaš termin kod Dr. {{ $doktor }} zakazan za {{ $datum }} je završen. Vaše mišljenje nam je jako važno!</p>
        
        <p>Molimo Vas da odvojite nekoliko minuta i podijelite svoje iskustvo kako biste pomogli drugim pacijentima pri donošenju odluka.</p>
        
        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.frontend_url') }}/dashboard" 
               style="background-color: #0891b2; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Ostavite recenziju
            </a>
        </p>
        
        <p style="color: #666; font-size: 14px;">
            Vaše iskustvo pomaže drugima da donesu bolje odluke o svom zdravlju.
        </p>
        
        <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
        
        <p style="color: #999; font-size: 12px; text-align: center;">
            © {{ date('Y') }} Zdravlje BiH. Sva prava zadržana.
        </p>
    </div>
</body>
</html>

