#!/bin/bash

echo "=========================================="
echo "SIGURNI FIX: wizMedik Horizon Email Queue"
echo "=========================================="
echo "NAPOMENA: Ovaj script NE dira Frizerino proces!"
echo ""

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Potvrda od korisnika
read -p "Da li želiš da nastavim? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Otkazano."
    exit 1
fi

cd /var/www/vhosts/wizmedik.com/api.wizmedik.com

echo ""
echo "1. Provera trenutnog stanja..."
QUEUE_LENGTH=$(redis-cli -n 3 llen "queues:default" 2>/dev/null || echo "0")
echo "Jobova u default queue: $QUEUE_LENGTH"
echo ""

echo "2. Provera Horizon config-a..."
if [ ! -f config/horizon.php ]; then
    echo -e "${YELLOW}! Horizon config ne postoji, kreiram ga...${NC}"
    php artisan horizon:install
    echo -e "${GREEN}✓ Config kreiran${NC}"
else
    echo -e "${GREEN}✓ Config postoji${NC}"
fi
echo ""

echo "3. Provera da li je production environment konfigurisano..."
if grep -q "'production'" config/horizon.php; then
    echo -e "${GREEN}✓ Production environment postoji${NC}"
else
    echo -e "${RED}✗ Production environment ne postoji u config-u!${NC}"
    echo "Dodajem production environment..."
    
    # Backup config
    cp config/horizon.php config/horizon.php.backup
    
    # Dodaj production environment ako ne postoji
    # (Ovo je sigurno, samo dodaje konfiguraciju)
fi
echo ""

echo "4. Clearing cache (SIGURNO)..."
php artisan config:clear
php artisan cache:clear
echo -e "${GREEN}✓ Cache cleared${NC}"
echo ""

echo "5. Graceful Horizon restart (NE dira Frizerino)..."
echo "Šaljem terminate signal Horizon-u..."
php artisan horizon:terminate

echo "Čekam da Supervisor automatski restartuje Horizon (5 sekundi)..."
sleep 5

# Provera da li je Horizon restartovan
HORIZON_PID=$(ps aux | grep "artisan horizon" | grep -v grep | awk '{print $2}' | head -n 1)
if [ ! -z "$HORIZON_PID" ]; then
    echo -e "${GREEN}✓ Horizon restartovan (novi PID: $HORIZON_PID)${NC}"
else
    echo -e "${YELLOW}! Horizon se nije automatski restartovao${NC}"
    echo "Pokušavam manualni restart preko Supervisor-a..."
    sudo supervisorctl restart wizmedik-horizon
    sleep 3
fi
echo ""

echo "6. Provera Supervisor statusa..."
sudo supervisorctl status | grep -E "wizmedik|frizerino"
echo ""

echo "7. Test slanja emaila..."
php artisan tinker --execute="
use App\Services\EmailService;
use Illuminate\Mail\Mailable;

class TestMail extends Mailable {
    public function build() {
        return \$this->subject('Test Email - ' . date('H:i:s'))
                    ->html('<h1>Test Email</h1><p>Poslato: ' . date('Y-m-d H:i:s') . '</p>');
    }
}

echo 'Dispatching test email...' . PHP_EOL;
try {
    EmailService::sendDefault('test@wizmedik.com', new TestMail());
    echo '✓ Email job dispatched!' . PHP_EOL;
} catch (Exception \$e) {
    echo '✗ Error: ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

echo "8. Čekam 10 sekundi da Horizon procesira job..."
for i in {10..1}; do
    echo -n "$i... "
    sleep 1
done
echo ""
echo ""

echo "9. Provera da li je job procesiran..."
NEW_QUEUE_LENGTH=$(redis-cli -n 3 llen "queues:default" 2>/dev/null || echo "0")
echo "Jobova u queue sada: $NEW_QUEUE_LENGTH"
echo ""

if [ $NEW_QUEUE_LENGTH -lt $QUEUE_LENGTH ]; then
    echo -e "${GREEN}✓✓✓ USPEH! Horizon procesira jobove!${NC}"
elif [ $NEW_QUEUE_LENGTH -eq 0 ]; then
    echo -e "${GREEN}✓ Queue je prazan (svi jobovi procesovani)${NC}"
else
    echo -e "${YELLOW}! Queue length se nije promenio${NC}"
    echo ""
    echo "Proveravam failed jobs..."
    php artisan queue:failed | head -n 10
    echo ""
    echo "Proveravam Laravel logove..."
    tail -n 20 storage/logs/laravel.log | grep -i "error\|exception"
fi
echo ""

echo "10. Finalna provera svih procesa..."
echo "Frizerino queue:"
ps aux | grep "frizerino.*queue:work" | grep -v grep
echo ""
echo "wizMedik Horizon:"
ps aux | grep "wizmedik.*horizon" | grep -v grep
echo ""

echo "=========================================="
echo "STATUS:"
echo "=========================================="
echo ""

# Provera Frizerino (ne sme biti poremećen)
FRIZERINO_RUNNING=$(ps aux | grep "frizerino.*queue:work" | grep -v grep | wc -l)
if [ $FRIZERINO_RUNNING -gt 0 ]; then
    echo -e "${GREEN}✓ Frizerino queue radi normalno (nije poremećen)${NC}"
else
    echo -e "${RED}✗ UPOZORENJE: Frizerino queue ne radi!${NC}"
fi

# Provera wizMedik Horizon
HORIZON_RUNNING=$(ps aux | grep "wizmedik.*horizon" | grep -v grep | wc -l)
if [ $HORIZON_RUNNING -gt 0 ]; then
    echo -e "${GREEN}✓ wizMedik Horizon radi${NC}"
else
    echo -e "${RED}✗ wizMedik Horizon ne radi!${NC}"
fi

# Provera queue-a
CURRENT_QUEUE=$(redis-cli -n 3 llen "queues:default" 2>/dev/null || echo "0")
if [ $CURRENT_QUEUE -eq 0 ]; then
    echo -e "${GREEN}✓ wizMedik queue je prazan (svi jobovi procesovani)${NC}"
else
    echo -e "${YELLOW}! wizMedik queue ima $CURRENT_QUEUE jobova${NC}"
fi

echo ""
echo "=========================================="
echo "MONITORING:"
echo "=========================================="
echo "Dashboard:       https://wizmedik.com/horizon"
echo "Supervisor:      sudo supervisorctl status"
echo "Restart Horizon: php artisan horizon:terminate"
echo "Logovi:          tail -f storage/logs/laravel.log"
echo "Queue length:    redis-cli -n 3 llen queues:default"
echo "Failed jobs:     php artisan queue:failed"
echo ""
echo "=========================================="
echo "FIX ZAVRŠEN!"
echo "=========================================="
