#!/usr/bin/env bash
set -euo pipefail

if [[ $EUID -ne 0 ]]; then
  echo "Root sifatida ishga tushiring: sudo bash $0"
  exit 1
fi

CONF=/etc/erp-telegram.env
LIB=/usr/local/lib/erp-telegram
STATE=/var/lib/erp-telegram
PASS=/root/.backup-pass
APP_DIR="${APP_DIR:-/var/www/erp-brainup}"
SOURCES=(/usr/local/bin/erp-healthcheck /root/.backup-telegram)

mask() {
  local v=$1
  if (( ${#v} > 12 )); then echo "${v:0:6}…${v: -4}"; else echo "***"; fi
}

detect_token() {
  grep -shoE '[0-9]{8,12}:[A-Za-z0-9_-]{30,}' "${SOURCES[@]}" /etc/erp-* 2>/dev/null | head -1 || true
}

detect_chat() {
  grep -shoiE 'chat_?id[^0-9-]{1,4}-?[0-9]{5,}' "${SOURCES[@]}" /etc/erp-* 2>/dev/null | grep -oE -- '-?[0-9]{5,}' | head -1 || true
}

env_value() {
  grep -sE "^$1=" "$APP_DIR/.env" | tail -1 | cut -d= -f2- | tr -d '"'"'" || true
}

BOT_TOKEN="" CHAT_ID=""
if [[ -f $CONF ]]; then
  BOT_TOKEN=$(grep -E '^BOT_TOKEN=' "$CONF" | cut -d= -f2- || true)
  CHAT_ID=$(grep -E '^CHAT_ID=' "$CONF" | cut -d= -f2- || true)
fi

if [[ -z $BOT_TOKEN ]]; then
  found=$(detect_token)
  if [[ -n $found ]]; then
    read -rp "Topilgan bot tokeni $(mask "$found") ishlatilsinmi? [Y/n] " answer
    [[ ${answer,,} == n* ]] || BOT_TOKEN=$found
  fi
  if [[ -z $BOT_TOKEN ]]; then
    read -rsp "Bot tokeni (BotFather bergan): " BOT_TOKEN
    echo
  fi
fi

if [[ -z $CHAT_ID ]]; then
  found=$(detect_chat)
  read -rp "Chat ID${found:+ [$found]}: " CHAT_ID
  CHAT_ID=${CHAT_ID:-$found}
fi

if [[ ! $BOT_TOKEN =~ ^[0-9]{6,12}:[A-Za-z0-9_-]{30,}$ ]]; then
  echo "Bot tokeni noto'g'ri ko'rinishda."
  exit 1
fi
if [[ ! $CHAT_ID =~ ^-?[0-9]{5,}$ ]]; then
  echo "Chat ID noto'g'ri: raqam bo'lishi kerak."
  exit 1
fi

if ! printf 'url = "https://api.telegram.org/bot%s/getMe"\n' "$BOT_TOKEN" | curl -fsS --max-time 20 -K - | grep -q '"ok":true'; then
  echo "Telegram tokenni qabul qilmadi (getMe). Tokenni tekshiring."
  exit 1
fi

DB_NAME=$(env_value DB_DATABASE)
SITE_URL=$(env_value APP_URL)

umask 077
cat > "$CONF" <<EOF
BOT_TOKEN=$BOT_TOKEN
CHAT_ID=$CHAT_ID
APP_DIR=$APP_DIR
DB_NAME=${DB_NAME:-erp_brainup}
SITE_URL=${SITE_URL:-https://brain-up.uz}
BACKUP_DIR=/var/backups/erp-brainup
APP_TZ=Asia/Tashkent
EOF
chmod 600 "$CONF"

NEW_PASS=""
if [[ ! -s $PASS ]]; then
  openssl rand -base64 32 > "$PASS"
  chmod 600 "$PASS"
  NEW_PASS=$(cat "$PASS")
fi

umask 022
mkdir -p "$LIB" "$STATE"
chmod 700 "$STATE"

cat > "$LIB/common.sh" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
source /etc/erp-telegram.env
STATE=/var/lib/erp-telegram
mkdir -p "$STATE"
HOST=$(hostname)

esc() {
  sed -e 's/&/\&amp;/g' -e 's/</\&lt;/g' -e 's/>/\&gt;/g'
}

tg() {
  local method=$1
  shift
  if [[ -n ${DRY_RUN:-} ]]; then
    printf '[%s]' "$method"
    printf ' %s' "$@"
    printf '\n'
    return 0
  fi
  printf 'url = "https://api.telegram.org/bot%s/%s"\n' "$BOT_TOKEN" "$method" \
    | curl -fsS --max-time 120 --retry 2 -K - "$@" >/dev/null
}

send() {
  local text=$1
  if (( ${#text} > 4000 )); then
    text="${text:0:4000}…"
  fi
  tg sendMessage \
    --data-urlencode "chat_id=$CHAT_ID" \
    --data-urlencode "parse_mode=HTML" \
    --data-urlencode "disable_web_page_preview=true" \
    --data-urlencode "text=$text"
}

send_file() {
  tg sendDocument \
    --form-string "chat_id=$CHAT_ID" \
    -F "document=@$1;filename=$2" \
    --form-string "caption=$3"
}

alert_state() {
  local file="$STATE/alert-$1"
  if [[ $2 == 1 ]]; then
    if [[ ! -f $file ]]; then
      send "$3"
      touch "$file"
    fi
  elif [[ -f $file ]]; then
    send "$4"
    rm -f "$file"
  fi
}

services() {
  local unit
  for unit in $(systemctl list-units --type=service --all --no-legend --plain 2>/dev/null \
    | awk '{print $1}' \
    | grep -E '^(nginx|mysql|redis-server|php[0-9.]+-fpm|fail2ban|cron)\.service$|pulse|erp|queue|horizon' \
    | sort -u || true); do
    [[ $(systemctl show -p Type --value "$unit" 2>/dev/null) == oneshot ]] && continue
    [[ $(systemctl is-enabled "$unit" 2>/dev/null || true) == enabled ]] || continue
    echo "$unit"
  done
}

human() {
  numfmt --to=iec --suffix=B "$1" 2>/dev/null || echo "$1 B"
}

site_check() {
  local out
  out=$(curl -s -o /dev/null -w '%{http_code} %{time_total}' --max-time 15 "$SITE_URL/up" 2>/dev/null || echo "000 0")
  awk '{printf "%s %.2f\n", $1, $2}' <<<"$out"
}

sql() {
  mysql -N -B "$DB_NAME" -e "$1" 2>/dev/null || echo "?"
}
EOF

cat > /usr/local/bin/erp-tg-send <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
source /usr/local/lib/erp-telegram/common.sh
if [[ $# -eq 0 ]]; then
  send "$(cat | esc)"
else
  send "$(printf '%s' "$*" | esc)"
fi
EOF

cat > /usr/local/bin/erp-tg-backup <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
source /usr/local/lib/erp-telegram/common.sh

DB=$(ls -1t "$BACKUP_DIR"/db-*.sql.gz 2>/dev/null | head -1 || true)
if [[ -z $DB ]] || (( $(date +%s) - $(stat -c %Y "$DB") > 93600 )); then
  send "$(printf '🔴 <b>Zaxira olinmagan</b>\nOxirgi 26 soatda bazaning nusxasi yo‘q (%s).\nServer: %s' "$BACKUP_DIR" "$HOST")"
  exit 1
fi

OUT=$(mktemp)
trap 'rm -f "$OUT"' EXIT
openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass file:/root/.backup-pass -in "$DB" -out "$OUT"
SIZE=$(stat -c %s "$OUT")
NAME="$(basename "$DB").enc"

if (( SIZE > 49000000 )); then
  send "$(printf '🟠 <b>Zaxira Telegram uchun juda katta</b>\n%s — %s (chegara 50 MB). Nusxa serverda saqlandi.' "$NAME" "$(human "$SIZE")")"
else
  send_file "$OUT" "$NAME" "💾 BrainUp baza zaxirasi $(TZ=$APP_TZ date '+%d.%m.%Y') · $(human "$SIZE") · shifrlangan"
fi
EOF

cat > /usr/local/bin/erp-tg-report <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
source /usr/local/lib/erp-telegram/common.sh

YDAY=$(TZ=$APP_TZ date -d yesterday +%F)
TODAY=$(TZ=$APP_TZ date +%F)

read -r CODE TIME <<<"$(site_check)"
if [[ $CODE == 200 ]]; then SITE="✅ ishlayapti (${TIME}s)"; else SITE="❌ javob kodi $CODE"; fi

SVC=""
for unit in $(services); do
  if [[ $(systemctl is-active "$unit" 2>/dev/null || true) == active ]]; then mark="✅"; else mark="❌"; fi
  SVC+="$mark ${unit%.service}  "
done

DISK=$(df -h / | awk 'NR==2{print $5" ("$3" / "$2")"}')
RAM=$(free -m | awk '/^Mem:/{printf "%d%% (%d / %d MB)", ($2-$7)*100/$2, $2-$7, $2}')
LOAD=$(cut -d' ' -f1-3 /proc/loadavg)
UP=$(uptime -p 2>/dev/null | sed 's/^up //' || true)

DOMAIN=$(sed -E 's#^https?://##; s#/.*$##' <<<"$SITE_URL")
CERT=/etc/letsencrypt/live/$DOMAIN/cert.pem
if [[ -f $CERT ]]; then
  END=$(openssl x509 -enddate -noout -in "$CERT" | cut -d= -f2)
  DAYS=$(( ($(date -d "$END" +%s) - $(date +%s)) / 86400 ))
  if (( DAYS < 14 )); then SSL="⚠️ $DAYS kun qoldi"; else SSL="✅ $DAYS kun"; fi
else
  SSL="?"
fi

DB=$(ls -1t "$BACKUP_DIR"/db-*.sql.gz 2>/dev/null | head -1 || true)
if [[ -n $DB ]]; then
  AGE=$(( ($(date +%s) - $(stat -c %Y "$DB")) / 3600 ))
  BACKUP="$(basename "$DB") · $(human "$(stat -c %s "$DB")") · $AGE soat oldin"
  (( AGE < 26 )) || BACKUP="⚠️ $BACKUP"
else
  BACKUP="❌ topilmadi"
fi

SSH_FAIL=$(journalctl -u ssh --since "24 hours ago" --no-pager 2>/dev/null | grep -cE 'Failed password|Invalid user|authentication failure' || true)
SSH_OK=$(journalctl -u ssh --since "24 hours ago" --no-pager 2>/dev/null | grep -oE 'Accepted [a-z-]+ for [^ ]+ from [0-9a-fA-F.:]+' | awk '{print $4" ← "$6}' | sort | uniq -c | awk '{print $2" "$3" "$4" ("$1")"}' | paste -sd ',' - | sed 's/,/, /g' || true)
F2B=$(fail2ban-client status sshd 2>/dev/null | awk -F: '/Currently banned/{c=$2}/Total banned/{t=$2}END{gsub(/[ \t]/,"",c);gsub(/[ \t]/,"",t);if(t!="")print "hozir "c", jami "t; else print "?"}' || true)
UPD=$(apt list --upgradable 2>/dev/null | grep -ci security || true)
REBOOT=""
[[ -f /var/run/reboot-required ]] && REBOOT=$'\n⚠️ Server qayta yuklanishini kutyapti (yadro yangilangan).'

STUDENTS=$(sql "SELECT COUNT(*) FROM users WHERE role='student' AND status='active'")
TEACHERS=$(sql "SELECT COUNT(*) FROM users WHERE role='teacher' AND status='active'")
ATT=$(sql "SELECT CONCAT('keldi ', SUM(status='present'), ', kechikdi ', SUM(status='late'), ', kelmadi ', SUM(status='absent'), ', sababli ', SUM(status='excused')) FROM attendances WHERE lesson_date='$YDAY'")
[[ $ATT == NULL || -z $ATT ]] && ATT="belgilanmagan"
PUBLISHED=$(sql "SELECT COUNT(*) FROM assignments WHERE published_at >= '$YDAY' AND published_at < '$TODAY'")
SUBMITTED=$(sql "SELECT COUNT(*) FROM submissions WHERE submitted_at >= '$YDAY' AND submitted_at < '$TODAY'")
GRADED=$(sql "SELECT COUNT(*) FROM submissions WHERE graded_at >= '$YDAY' AND graded_at < '$TODAY'")
WAITING=$(sql "SELECT COUNT(*) FROM submissions WHERE status='submitted'")
ORDERS=$(sql "SELECT COUNT(*) FROM shop_orders WHERE status='pending'")

ERRORS=0
for f in "$APP_DIR"/storage/logs/laravel*.log; do
  [[ -f $f ]] || continue
  n=$(awk -v since="$(TZ=$APP_TZ date -d '24 hours ago' '+%Y-%m-%d %H:%M:%S')" \
    'substr($0,1,1)=="[" && substr($0,2,19)>=since && $0 ~ /\] [a-z]+\.(ERROR|CRITICAL|ALERT|EMERGENCY):/ {c++} END{print c+0}' "$f")
  ERRORS=$(( ERRORS + n ))
done
if (( ERRORS > 0 )); then ERR="⚠️ $ERRORS ta"; else ERR="✅ yo‘q"; fi

MSG=$(cat <<MSG
📊 <b>BrainUp — kundalik hisobot</b>
$(TZ=$APP_TZ date '+%d.%m.%Y, %H:%M')

<b>Sayt:</b> $SITE
<b>Xizmatlar:</b> $SVC
<b>Disk:</b> $DISK
<b>RAM:</b> $RAM
<b>Yuklama:</b> $LOAD · ishlab turibdi: $UP
<b>SSL:</b> $SSL
<b>Zaxira:</b> $BACKUP

🔒 <b>Xavfsizlik (24 soat)</b>
SSH muvaffaqiyatsiz urinishlar: $SSH_FAIL
SSH kirishlar: ${SSH_OK:-yo‘q}
fail2ban: $F2B
Kutayotgan xavfsizlik yangilanishlari: $UPD$REBOOT

🏫 <b>Markaz (kecha, $YDAY)</b>
Faol o‘quvchilar: $STUDENTS · o‘qituvchilar: $TEACHERS
Davomat: $ATT
E’lon qilingan vazifalar: $PUBLISHED
Topshirilgan ishlar: $SUBMITTED · baholangan: $GRADED
Tekshirilishi kutilayotgan: $WAITING
Do‘kon buyurtmalari (kutilmoqda): $ORDERS
Laravel xatolari: $ERR
MSG
)

send "$MSG"
EOF

cat > /usr/local/bin/erp-tg-watch <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
source /usr/local/lib/erp-telegram/common.sh

FIRST=0
[[ -f $STATE/initialized ]] || FIRST=1

read -r CODE TIME <<<"$(site_check)"
FAILS=$(cat "$STATE/site-fails" 2>/dev/null || echo 0)
if [[ $CODE == 200 ]]; then FAILS=0; else FAILS=$(( FAILS + 1 )); fi
echo "$FAILS" > "$STATE/site-fails"
BAD=0
(( FAILS >= 2 )) && BAD=1
alert_state site "$BAD" \
  "$(printf '🔴 <b>Sayt ishlamayapti</b>\n%s/up → %s (ketma-ket %s marta)' "$SITE_URL" "$CODE" "$FAILS")" \
  "$(printf '🟢 <b>Sayt qayta ishlayapti</b>\n%s/up → 200 (%ss)' "$SITE_URL" "$TIME")"

for unit in $(services); do
  BAD=0
  [[ $(systemctl is-active "$unit" 2>/dev/null || true) == active ]] || BAD=1
  alert_state "svc-${unit%.service}" "$BAD" \
    "$(printf '🔴 <b>Xizmat to‘xtadi:</b> %s\nTekshirish: <code>systemctl status %s</code>' "$unit" "$unit")" \
    "🟢 <b>Xizmat tiklandi:</b> $unit"
done

USE=$(df --output=pcent / | tail -1 | tr -dc '0-9')
BAD=0
(( USE >= 85 )) && BAD=1
alert_state disk "$BAD" "🟠 <b>Disk to‘lib bormoqda:</b> ${USE}%" "🟢 Disk joyi yetarli: ${USE}%"

AVAIL=$(awk '/^MemAvailable:/{a=$2}/^MemTotal:/{t=$2}END{print int(a*100/t)}' /proc/meminfo)
BAD=0
(( AVAIL < 10 )) && BAD=1
alert_state memory "$BAD" "🟠 <b>RAM tugab bormoqda:</b> faqat ${AVAIL}% bo‘sh" "🟢 RAM yetarli: ${AVAIL}% bo‘sh"

for f in "$APP_DIR"/storage/logs/laravel*.log; do
  [[ -f $f ]] || continue
  key=$(basename "$f")
  SIZE=$(stat -c %s "$f")
  OFF=$(cat "$STATE/log-$key" 2>/dev/null || echo -1)
  echo "$SIZE" > "$STATE/log-$key"
  (( OFF < 0 || FIRST == 1 )) && continue
  (( SIZE < OFF )) && OFF=0
  (( SIZE == OFF )) && continue
  NEW=$(tail -c +"$(( OFF + 1 ))" "$f" | head -c 2000000 | grep -E '^\[[0-9-]+ [0-9:]+\] [a-z]+\.(ERROR|CRITICAL|ALERT|EMERGENCY):' || true)
  [[ -z $NEW ]] && continue
  COUNT=$(wc -l <<<"$NEW")
  FIRSTLINE=$(head -1 <<<"$NEW" | cut -c1-400 | esc)
  send "$(printf '⚠️ <b>Laravel xatosi</b> (%s ta)\n<code>%s</code>\nTo‘liq: <code>%s</code>' "$COUNT" "$FIRSTLINE" "$f")"
done

CUR=$STATE/ssh.cursor
if [[ -s $CUR ]]; then
  OUT=$(journalctl -u ssh --after-cursor="$(cat "$CUR")" --show-cursor --no-pager 2>/dev/null || true)
else
  OUT=$(journalctl -u ssh -n 1 --show-cursor --no-pager 2>/dev/null || true)
fi
NEWCUR=$(grep -oP '^-- cursor: \K.*' <<<"$OUT" | tail -1 || true)
[[ -n $NEWCUR ]] && echo "$NEWCUR" > "$CUR"
if (( FIRST == 0 )) && [[ -s $CUR ]]; then
  while read -r method user ip; do
    [[ -n $ip ]] || continue
    send "$(printf '🔐 <b>Serverga SSH orqali kirildi</b>\nFoydalanuvchi: <b>%s</b>\nIP: <code>%s</code> (%s)\nSiz bo‘lmasangiz, darhol tekshiring!' "$user" "$ip" "$method")"
  done < <(grep -oE 'Accepted [a-z-]+ for [^ ]+ from [0-9a-fA-F.:]+' <<<"$OUT" | awk '{print $2, $4, $6}' || true)
fi

if [[ -d $APP_DIR/.git ]]; then
  HEAD=$(git -c safe.directory="$APP_DIR" -C "$APP_DIR" log -1 --format='%h %s' 2>/dev/null || true)
  PREV=$(cat "$STATE/deploy-head" 2>/dev/null || true)
  if [[ -n $HEAD && $HEAD != "$PREV" ]]; then
    echo "$HEAD" > "$STATE/deploy-head"
    if (( FIRST == 0 )) && [[ -n $PREV ]]; then
      send "$(printf '🚀 <b>Saytga yangi versiya chiqdi</b>\n<code>%s</code>' "$(esc <<<"$HEAD")")"
    fi
  fi
fi

touch "$STATE/initialized"
EOF

chmod 755 /usr/local/bin/erp-tg-send /usr/local/bin/erp-tg-backup /usr/local/bin/erp-tg-report /usr/local/bin/erp-tg-watch
chmod 644 "$LIB/common.sh"

REPORT_HOUR=$(date -d 'TZ="Asia/Tashkent" 08:00' +%-H)
cat > /etc/cron.d/erp-telegram <<EOF
*/5 * * * * root /usr/local/bin/erp-tg-watch >>/var/log/erp-telegram.log 2>&1
40 3 * * * root /usr/local/bin/erp-tg-backup >>/var/log/erp-telegram.log 2>&1
0 $REPORT_HOUR * * * root /usr/local/bin/erp-tg-report >>/var/log/erp-telegram.log 2>&1
EOF
chmod 644 /etc/cron.d/erp-telegram

/usr/local/bin/erp-tg-watch || true
/usr/local/bin/erp-tg-send "✅ BrainUp monitoring ulandi ($(hostname)). Kundalik hisobot har kuni 08:00 da, zaxira 03:40 da keladi."

echo
echo "Tayyor. Telegram'ga sinov xabari yuborildi."
echo "  Hozir hisobot olish:  sudo erp-tg-report"
echo "  Hozir zaxira yuborish: sudo erp-tg-backup"
echo "  Xabar yuborish:       echo 'matn' | sudo erp-tg-send"
if [[ -n $NEW_PASS ]]; then
  echo
  echo "================ MUHIM ================"
  echo "Zaxiralarni ochish paroli (serverdan tashqarida saqlang!):"
  echo "  $NEW_PASS"
  echo "Bu parolsiz Telegram'dagi zaxiralarni ochib bo'lmaydi."
  echo "======================================="
fi
