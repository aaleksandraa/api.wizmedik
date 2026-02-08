<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odgovor na vaše pitanje</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #0891b2; }
        h1 { color: #1e293b; font-size: 22px; margin-bottom: 20px; }
        .question-box { background: #f8fafc; border-left: 4px solid #0891b2; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0; }
        .question-title { font-weight: bold; color: #1e293b; margin-bottom: 5px; }
        .answer-box { background: #f0fdf4; border-left: 4px solid #22c55e; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0; }
        .doctor-name { font-weight: bold; color: #22c55e; margin-bottom: 10px; }
        .btn { display: inline-block; background: #0891b2; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin-top: 20px; }
        .btn:hover { background: #0284c7; }
        .footer { text-align: center; margin-top: 30px; color: #64748b; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">WizMedik</div>
            </div>

            <h1>Dobili ste odgovor na vaše pitanje!</h1>

            <p>Poštovani/a {{ $pitanje->ime_korisnika }},</p>

            <p>Doktor je odgovorio na vaše pitanje:</p>

            <div class="question-box">
                <div class="question-title">{{ $pitanje->naslov }}</div>
                <p style="color: #64748b; font-size: 14px; margin: 0;">{{ Str::limit($pitanje->sadrzaj, 150) }}</p>
            </div>

            <div class="answer-box">
                <div class="doctor-name">{{ $doktorIme }}</div>
                <p style="margin: 0;">{{ Str::limit($odgovor->sadrzaj, 300) }}</p>
            </div>

            <p>Kliknite na dugme ispod da vidite kompletan odgovor:</p>

            <center>
                <a href="{{ config('app.frontend_url') }}/pitanja/{{ $pitanje->slug }}" class="btn">
                    Pogledaj odgovor
                </a>
            </center>

            <div class="footer">
                <p>Hvala što koristite WizMedik platformu.</p>
                <p style="font-size: 12px;">Ovo je automatski generisana poruka. Molimo ne odgovarajte na ovaj email.</p>
            </div>
        </div>
    </div>
</body>
</html>
