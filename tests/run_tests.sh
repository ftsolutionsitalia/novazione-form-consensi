#!/usr/bin/env bash
# Suite di test end-to-end (sviluppo). Richiede: curl, python3, poppler-utils (pdfsig/pdfinfo/pdftotext).
#
#   BASE=http://127.0.0.1:8091/sviluppo STORAGE=/var/www/public_html/sviluppo/storage \
#   MAILDIR=/tmp/mailcap ADMIN_PASS='TestPass!2026' tests/run_tests.sh
#
# Il server di test deve avere config.local.php con trusted_proxies=['127.0.0.1']
# (così ogni test usa un IP diverso via X-Forwarded-For) e min_fill_seconds=1.

set -u
BASE=${BASE:-http://127.0.0.1:8091/sviluppo}
STORAGE=${STORAGE:-/var/www/public_html/sviluppo/storage}
MAILDIR=${MAILDIR:-/tmp/mailcap}
ADMIN_PASS=${ADMIN_PASS:-TestPass!2026}
TMP=$(mktemp -d)
rm -f "$STORAGE"/ratelimit/*.json 2>/dev/null  # ogni esecuzione parte con contatori puliti
PASS=0; FAIL=0

ok()   { PASS=$((PASS+1)); printf '  \033[32mOK\033[0m   %s\n' "$1"; }
ko()   { FAIL=$((FAIL+1)); printf '  \033[31mFAIL\033[0m %s\n' "$1"; [ -n "${2:-}" ] && printf '       %s\n' "$2"; }
check(){ if eval "$2"; then ok "$1"; else ko "$1" "${3:-}"; fi; }
section(){ printf '\n\033[1m%s\033[0m\n' "$1"; }
json() { python3 -c "import sys,json; d=json.load(sys.stdin); print(eval(sys.argv[1]))" "$1"; }
ledger_count() { [ -f "$STORAGE/ledger/consents.jsonl" ] && wc -l < "$STORAGE/ledger/consents.jsonl" | tr -d ' ' || echo 0; }

# Apre il form (nuova sessione) e salva csrf/form_ts per il client $1
open_form() {
  rm -f "$TMP/cj_$1"
  local page; page=$(curl -s -c "$TMP/cj_$1" "$BASE/index.php")
  CSRF=$(printf '%s' "$page" | grep -o 'name="csrf_token" value="[^"]*"' | cut -d'"' -f4)
  TS=$(printf '%s' "$page" | grep -o 'name="form_ts" value="[^"]*"' | cut -d'"' -f4)
}

# submit <client> <ip> [campi extra curl...]  -> stampa "HTTP_CODE BODY"
submit() {
  local c=$1 ip=$2; shift 2
  curl -s -b "$TMP/cj_$c" -c "$TMP/cj_$c" -H 'Accept: application/json' -H "X-Forwarded-For: $ip" \
    -A 'Mozilla/5.0 (TestSuite)' -e "$BASE/index.php" -w '\n%{http_code}' \
    --data-urlencode "csrf_token=$CSRF" --data-urlencode "form_ts=$TS" "$@" "$BASE/process.php"
}
valid_fields=(--data-urlencode "nome=Mario" --data-urlencode "cognome=Rossi" --data-urlencode "email=mario.rossi@example.com"
              --data-urlencode "telefono=333 1234567" --data-urlencode "privacy=1")

# ---------------------------------------------------------------------------
section "1. Pagine e sicurezza HTTP"
for p in "" index.php privacy.php verify.php revoca.php success.php admin.php confirm.php; do
  code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/$p"); check "GET /$p -> 200" '[ "$code" = 200 ]' "ottenuto $code"
done
hdr=$(curl -sI "$BASE/index.php")
check "Header Content-Security-Policy presente" 'printf "%s" "$hdr" | grep -qi "content-security-policy"'
check "Header X-Frame-Options presente" 'printf "%s" "$hdr" | grep -qi "x-frame-options"'
check "Cookie di sessione HttpOnly + SameSite" 'printf "%s" "$hdr" | grep -i "set-cookie" | grep -qi "httponly" && printf "%s" "$hdr" | grep -i set-cookie | grep -qi samesite=lax'
html=$(curl -s "$BASE/index.php")
check "Nessuna risorsa esterna (font/CDN) nella pagina" '! printf "%s" "$html" | grep -Eqi "(src|href)=\"(https?:)?//" && ! printf "%s" "$html" | grep -Eqi "googleapis|gstatic|cdnjs|jsdelivr|unpkg|fontawesome"'
check "Checkbox consensi NON preselezionate" '! printf "%s" "$html" | grep -Eq "id=\"(privacy|marketing)\"[^>]*checked"'

section "2. File privati non accessibili dal web"
# Genera prima i file in storage
open_form a; sleep 1.2; submit a 10.0.0.1 "${valid_fields[@]}" >/dev/null
for p in storage/ storage/ledger/consents.jsonl storage/keys/secret.key storage/keys/sign.key "storage/pdfs/" \
         includes/config.php includes/config.local.php includes/bootstrap.php vendor/autoload.php \
         vendor/tecnickcom/tcpdf/tcpdf.php tests/run_tests.sh deploy/deploy_ftp.php composer.json composer.lock README.md .htaccess .git/config; do
  code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/$p")
  check "Bloccato: /$p ($code)" '[ "$code" = 403 ] || [ "$code" = 404 ]' "ottenuto $code"
done
pdfname=$(ls "$STORAGE/pdfs" | head -1)
code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/storage/pdfs/$pdfname")
check "Bloccato: PDF di un utente indovinando il nome ($code)" '[ "$code" = 403 ] || [ "$code" = 404 ]'

# ---------------------------------------------------------------------------
section "3. Invio completo (simulazione utente reale)"
rm -f "$MAILDIR"/*
before=$(ledger_count)
open_form u; sleep 1.2
out=$(submit u 10.0.0.2 --data-urlencode "nome=Zoë Ana" --data-urlencode "cognome=D'Amico-Bianchi" \
  --data-urlencode "email=Utente.Test@Example.IT" --data-urlencode "telefono=0039 347-123.4567" \
  --data-urlencode "messaggio=Ciao <b>test</b> àèìòù € \"virgolette\"" --data-urlencode "privacy=1" --data-urlencode "marketing=1")
code=$(printf '%s' "$out" | tail -1); body=$(printf '%s' "$out" | head -n -1)
check "Risposta 200 ok:true" '[ "$code" = 200 ] && [ "$(printf "%s" "$body" | json "d[\"ok\"]")" = True ]' "$code $body"
ID=$(printf '%s' "$body" | json 'd.get("id","")')
check "ID pratica nel formato CNS-AAAAMMGG-XXXXXXXX ($ID)" '[[ "$ID" =~ ^CNS-[0-9]{8}-[A-Z0-9]{8}$ ]]'
check "Registro: +2 eventi (consenso + consegna email)" '[ $(( $(ledger_count) - before )) = 2 ]'
succ=$(curl -s -b "$TMP/cj_u" "$BASE/success.php")
check "Pagina di successo mostra l'ID" 'printf "%s" "$succ" | grep -q "$ID"'
succ2=$(curl -s -b "$TMP/cj_u" "$BASE/success.php")
check "Ricaricando la pagina l'ID non si ripete (flash)" '! printf "%s" "$succ2" | grep -q "$ID"'

sleep 0.5
python3 -I - "$MAILDIR" "$TMP" > "$TMP/mails.txt" <<'PY'
import sys, glob, email, hashlib
from email.header import decode_header, make_header
md, tmp = sys.argv[1], sys.argv[2]
for f in sorted(glob.glob(md + '/*.eml')):
    m = email.message_from_bytes(open(f, 'rb').read())
    to = m['To']; kind = 'ADMIN' if 'gmail' in to else 'USER'
    subj = str(make_header(decode_header(m['Subject'])))
    html = ''
    att = None
    for p in m.walk():
        if p.get_filename():
            data = p.get_payload(decode=True); att = hashlib.sha256(data).hexdigest()
            open(f'{tmp}/{kind}.pdf', 'wb').write(data)
        elif p.get_content_type() == 'text/html':
            html = p.get_payload(decode=True).decode()
            open(f'{tmp}/{kind}.html', 'w').write(html)
    print(kind, to, att or '-', m['From'], m['Reply-To'] or '-', subj.replace(' ', '_'), sep='|')
PY
admin_line=$(grep '^ADMIN' "$TMP/mails.txt"); user_line=$(grep '^USER' "$TMP/mails.txt")
check "Email inviata a 73.leonardo.v@gmail.com" 'printf "%s" "$admin_line" | grep -q "|73.leonardo.v@gmail.com|"' "$(cat "$TMP/mails.txt")"
check "Email admin con PDF allegato" '[ -s "$TMP/ADMIN.pdf" ]'
check "Reply-To admin = email dell'utente (rispondi direttamente)" 'printf "%s" "$admin_line" | grep -q "|utente.test@example.it|"'
check "Mittente sul dominio del sito (SPF)" 'printf "%s" "$admin_line" | cut -d"|" -f4 | grep -q "@"'
check "Copia all'utente con PDF allegato" '[ -s "$TMP/USER.pdf" ]'
check "Email utente contiene link double opt-in" 'grep -q "confirm.php?id=$ID" "$TMP/USER.html"'
REC=$(python3 -c "import json,sys;[print(l) for l in open(sys.argv[1]) if sys.argv[2] in l and '\"consent\"' in l]" "$STORAGE/ledger/consents.jsonl" "$ID")
LEDGER_SHA=$(printf '%s' "$REC" | json 'd["pdf_sha256"]')
check "SHA-256 allegato = registro = file su server" '[ "$(sha256sum "$TMP/ADMIN.pdf" | cut -c1-64)" = "$LEDGER_SHA" ] && [ "$(sha256sum "$STORAGE/pdfs/$ID.pdf" | cut -c1-64)" = "$LEDGER_SHA" ]'
check "SHA-256 del PDF riportato nell'email admin" 'grep -q "$LEDGER_SHA" "$TMP/ADMIN.html"'
check "Registro senza dati personali in chiaro" '! printf "%s" "$REC" | grep -Eqi "Amico|utente.test|393471234567|"10\.0\.0\.2""'
recjson="$STORAGE/records/$ID.json"
check "Dati personali salvati nel file pratica" 'grep -q "Zoë Ana" "$recjson" && grep -q "+393471234567" "$recjson"'
check "Email normalizzata minuscola, telefono in formato +39" 'grep -q "utente.test@example.it" "$recjson"'
check "IP registrato (via proxy fidato)" 'grep -q "\"ip\": \"10.0.0.2\"" "$recjson"'
check "Timestamp ISO 8601 con fuso orario" 'grep -Eq "\"timestamp\": \"[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9:]{8}\+0[12]:00\"" "$recjson"'
check "Testo esatto e versione dei consensi salvati" 'grep -q "consent_version" "$recjson" && grep -q "Dichiaro di aver letto" "$recjson"'

section "4. PDF: protezione, firma, contenuto"
sig=$(pdfsig "$TMP/ADMIN.pdf" 2>&1); info=$(pdfinfo "$TMP/ADMIN.pdf" 2>&1); txt=$(pdftotext "$TMP/ADMIN.pdf" - 2>/dev/null)
check "Firma digitale valida" 'printf "%s" "$sig" | grep -q "Signature is Valid"' "$sig"
check "Firma copre l'intero documento" 'printf "%s" "$sig" | grep -q "Total document signed"'
check "Cifratura AES-256" 'printf "%s" "$info" | grep -q "algorithm:AES-256"' "$info"
check "Modifica disabilitata (change:no)" 'printf "%s" "$info" | grep -q "change:no"'
check "Una sola pagina" 'printf "%s" "$info" | grep -Eq "Pages: +1$"'
for needle in "$ID" "Zoë Ana" "D'Amico-Bianchi" "utente.test@example.it" "+393471234567" "10.0.0.2" "Ciao <b>test</b>" "€" "PRESTATO" "HMAC-SHA256" "Dichiaro di aver letto" "Acconsento a ricevere"; do
  check "PDF contiene: $needle" 'printf "%s" "$txt" | grep -qF -- "$needle"'
done

section "5. Manomissione del PDF"
cp "$TMP/ADMIN.pdf" "$TMP/tampered.pdf"
python3 -c "import sys;p=sys.argv[1];b=bytearray(open(p,'rb').read());i=len(b)//3;b[i]=(b[i]+1)%256;open(p,'wb').write(b)" "$TMP/tampered.pdf"
tsig=$(pdfsig "$TMP/tampered.pdf" 2>&1)
check "PDF alterato: firma NON valida" '! printf "%s" "$tsig" | grep -q "Signature is Valid"' "$tsig"
open_form v
vres=$(curl -s -b "$TMP/cj_v" -F "csrf_token=$CSRF" -F "pdf=@$TMP/ADMIN.pdf;type=application/pdf" "$BASE/verify.php")
check "verify.php: originale riconosciuto autentico" 'printf "%s" "$vres" | grep -q "Documento autentico e integro"'
vres=$(curl -s -b "$TMP/cj_v" -F "csrf_token=$CSRF" -F "pdf=@$TMP/tampered.pdf;type=application/pdf" "$BASE/verify.php")
check "verify.php: alterato riconosciuto come NON valido" 'printf "%s" "$vres" | grep -q "Documento non riconosciuto"'
vres=$(curl -s "$BASE/verify.php?id=$ID")
check "verify.php?id: stato senza dati personali" 'printf "%s" "$vres" | grep -q "Pratica trovata" && ! printf "%s" "$vres" | grep -q "Zoë"'
check "verify.php?id: marketing in attesa di conferma" 'printf "%s" "$vres" | grep -q "in attesa di conferma"'

section "6. Double opt-in marketing"
link=$(grep -o "confirm.php?id=$ID&amp;t=[a-f0-9]*" "$TMP/USER.html" | head -1 | sed 's/&amp;/\&/')
rm -f "$TMP/cj_d"
cpage=$(curl -s -c "$TMP/cj_d" "$BASE/$link")
check "Aprire il link NON conferma da solo (anti-scanner)" 'printf "%s" "$cpage" | grep -q "Conferma la tua iscrizione"'
dcsrf=$(printf '%s' "$cpage" | grep -o 'name="csrf_token" value="[^"]*"' | cut -d'"' -f4)
tok=${link##*t=}
cres=$(curl -s -b "$TMP/cj_d" --data-urlencode "id=$ID" --data-urlencode "t=$tok" --data-urlencode "csrf_token=$dcsrf" "$BASE/confirm.php")
check "Click su Confermo registra la conferma" 'printf "%s" "$cres" | grep -q "Iscrizione confermata"'
cres=$(curl -s "$BASE/confirm.php?id=$ID&t=${tok:0:63}0")
check "Token falsificato rifiutato" 'printf "%s" "$cres" | grep -q "Link non valido"'
check "Stato su verify.php: confermato" 'curl -s "$BASE/verify.php?id=$ID" | grep -q "Prestato e confermato"'

section "7. Validazione e attacchi"
open_form e; sleep 1.2
out=$(submit e 10.0.1.1 --data-urlencode "nome=M" --data-urlencode "cognome=" --data-urlencode "email=non-email" --data-urlencode "telefono=12")
code=$(printf '%s' "$out" | tail -1); body=$(printf '%s' "$out" | head -n -1)
check "Dati non validi -> 422 con errori per campo" '[ "$code" = 422 ] && printf "%s" "$body" | json "sorted(d[\"errors\"].keys())" | grep -q "cognome.*email.*nome.*privacy.*telefono"' "$code $body"
n0=$(ledger_count)
open_form e; sleep 1.2
out=$(submit e 10.0.1.2 --data-urlencode "nome=Mario" --data-urlencode "cognome=Rossi" --data-urlencode "email=a@b.it" --data-urlencode "telefono=3331234567")
check "Senza consenso privacy -> rifiutato" '[ "$(printf "%s" "$out" | tail -1)" = 422 ] && printf "%s" "$out" | grep -q privacy'
open_form e; sleep 1.2
out=$(submit e 10.0.1.3 --data-urlencode "nome=Mario" --data-urlencode "cognome=Rossi" --data-urlencode "email=$(printf 'a@b.it\r\nBcc: vittima@example.com')" --data-urlencode "telefono=3331234567" --data-urlencode "privacy=1")
check "Header injection nell'email -> rifiutata" '[ "$(printf "%s" "$out" | tail -1)" = 422 ]'
open_form e; sleep 1.2
out=$(curl -s -b "$TMP/cj_e" -H 'Accept: application/json' -H "X-Forwarded-For: 10.0.1.4" -w '\n%{http_code}' --data-urlencode "csrf_token=sbagliato" --data-urlencode "form_ts=$TS" "${valid_fields[@]}" "$BASE/process.php")
check "CSRF token errato -> 403" '[ "$(printf "%s" "$out" | tail -1)" = 403 ]'
open_form e; sleep 1.2
out=$(submit e 10.0.1.5 "${valid_fields[@]}" --data-urlencode "website=http://spam.example")
check "Honeypot compilato -> finto successo, nulla registrato" '[ "$(printf "%s" "$out" | tail -1)" = 200 ] && [ "$(ledger_count)" = "$n0" ]'
open_form e
out=$(submit e 10.0.1.6 "${valid_fields[@]}")
check "Invio istantaneo (bot) -> finto successo, nulla registrato" '[ "$(printf "%s" "$out" | tail -1)" = 200 ] && [ "$(ledger_count)" = "$n0" ]'
open_form e; sleep 1.2
out=$(submit e 10.0.1.7 "${valid_fields[@]}" --data-urlencode "form_ts=1700000000000.0000000000000000")
check "Token temporale falsificato -> scartato" '[ "$(ledger_count)" = "$n0" ]'
code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/process.php")
check "GET su process.php -> redirect" '[ "$code" = 303 ]'
open_form x; sleep 1.2
out=$(submit x 10.0.1.8 --data-urlencode "nome=<img src=x onerror=alert(1)>" --data-urlencode "cognome=Rossi" --data-urlencode "email=x@y.it" --data-urlencode "telefono=3331234567" --data-urlencode "privacy=1")
check "Nome con HTML/XSS -> rifiutato" '[ "$(printf "%s" "$out" | tail -1)" = 422 ]'
# Fallback senza JavaScript: errori via redirect e campi ripopolati (escapati)
open_form n; sleep 1.2
loc=$(curl -s -o /dev/null -w '%{redirect_url}' -b "$TMP/cj_n" -c "$TMP/cj_n" -H "X-Forwarded-For: 10.0.1.9" --data-urlencode "csrf_token=$CSRF" --data-urlencode "form_ts=$TS" --data-urlencode 'nome="><script>x</script>' --data-urlencode "email=bad" "$BASE/process.php")
back=$(curl -s -b "$TMP/cj_n" "$BASE/index.php")
check "Senza JS: redirect al form con errori" '[[ "$loc" == *index.php* ]] && printf "%s" "$back" | grep -q "Controlla i campi evidenziati"'
check "Senza JS: valori ripopolati e scappati (no XSS)" 'printf "%s" "$back" | grep -q "&quot;&gt;&lt;script&gt;" && ! printf "%s" "$back" | grep -q "\"><script>x"'

section "8. Rate limit (5 invii/ora per IP)"
codes=""
for i in 1 2 3 4 5 6; do open_form r; sleep 1.1; codes="$codes $(submit r 10.9.9.9 "${valid_fields[@]}" | tail -1)"; done
check "6° invio dallo stesso IP -> 429 ($codes)" '[ "$codes" = " 200 200 200 200 200 429" ]'

section "9. Revoca del consenso"
open_form k
rv=$(curl -s -b "$TMP/cj_k" -c "$TMP/cj_k" "$BASE/revoca.php"); rcsrf=$(printf '%s' "$rv" | grep -o 'name="csrf_token" value="[^"]*"' | cut -d'"' -f4)
rm -f "$MAILDIR"/*
rv=$(curl -s -b "$TMP/cj_k" -H "X-Forwarded-For: 10.0.2.1" --data-urlencode "csrf_token=$rcsrf" --data-urlencode "email=UTENTE.test@example.it" --data-urlencode "scope=marketing" "$BASE/revoca.php")
check "Revoca registrata" 'printf "%s" "$rv" | grep -q "Richiesta registrata"'
check "Revoca notificata a titolare e utente" '[ "$(grep -l "gmail" "$MAILDIR"/*.eml | wc -l)" = 1 ] && [ "$(grep -li "utente.test@example.it" "$MAILDIR"/*.eml | wc -l)" -ge 1 ]'
check "verify.php mostra Revocato" 'curl -s "$BASE/verify.php?id=$ID" | grep -q "Revocato il"'
rv=$(curl -s -b "$TMP/cj_k" -H "X-Forwarded-For: 10.0.2.2" --data-urlencode "csrf_token=$rcsrf" --data-urlencode "email=sconosciuto@example.com" "$BASE/revoca.php")
check "Email sconosciuta: stessa risposta (no enumerazione)" 'printf "%s" "$rv" | grep -q "Richiesta registrata"'

section "10. Area admin"
rm -f "$TMP/cj_ad"
lp=$(curl -s -c "$TMP/cj_ad" "$BASE/admin.php"); acsrf=$(printf '%s' "$lp" | grep -o 'name="csrf_token" value="[^"]*"' | cut -d'"' -f4)
r=$(curl -s -b "$TMP/cj_ad" -c "$TMP/cj_ad" -H "X-Forwarded-For: 10.0.3.1" --data-urlencode "csrf_token=$acsrf" --data-urlencode "password=sbagliata" --data-urlencode "login=1" "$BASE/admin.php")
check "Password errata rifiutata" 'printf "%s" "$r" | grep -q "Password errata"'
curl -s -o /dev/null -b "$TMP/cj_ad" -c "$TMP/cj_ad" -H "X-Forwarded-For: 10.0.3.1" --data-urlencode "csrf_token=$acsrf" --data-urlencode "password=$ADMIN_PASS" --data-urlencode "login=1" "$BASE/admin.php"
dash=$(curl -s -b "$TMP/cj_ad" "$BASE/admin.php")
check "Login e registro visibile" 'printf "%s" "$dash" | grep -q "Registro consensi" && printf "%s" "$dash" | grep -q "$ID"'
check "Catena di hash verificata integra" 'printf "%s" "$dash" | grep -q "Registro integro"'
csv=$(curl -s -b "$TMP/cj_ad" "$BASE/admin.php?export=1")
check "Export CSV con la pratica" 'printf "%s" "$csv" | grep -q "$ID"'
curl -s -b "$TMP/cj_ad" -o "$TMP/dl.pdf" "$BASE/admin.php?pdf=$ID"
check "Download PDF dall'admin identico all'originale" '[ "$(sha256sum "$TMP/dl.pdf" | cut -c1-64)" = "$LEDGER_SHA" ]'
check "Senza login niente export" '! curl -s "$BASE/admin.php?export=1" | grep -q "$ID"'
check "Senza login niente PDF" '! curl -s "$BASE/admin.php?pdf=$ID" | head -c 5 | grep -q "%PDF"'
# CSV injection
open_form ci; sleep 1.2
submit ci 10.0.3.9 --data-urlencode "nome=Mario" --data-urlencode "cognome=Rossi" --data-urlencode "email=csv@example.com" --data-urlencode "telefono=3331234567" --data-urlencode "privacy=1" --data-urlencode "messaggio==HYPERLINK(\"http://evil\")" >/dev/null
csv=$(curl -s -b "$TMP/cj_ad" "$BASE/admin.php?export=1")
check "CSV: formule neutralizzate (=HYPERLINK -> '=HYPERLINK)" 'printf "%s" "$csv" | grep -q "'"'"'=HYPERLINK" '
# Cancellazione (art. 17)
dash=$(curl -s -b "$TMP/cj_ad" "$BASE/admin.php"); acsrf=$(printf '%s' "$dash" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | cut -d'"' -f4)
curl -s -o /dev/null -b "$TMP/cj_ad" --data-urlencode "csrf_token=$acsrf" --data-urlencode "erase=$ID" --data-urlencode "confirm=$ID" "$BASE/admin.php"
check "Cancellazione: file dati e PDF rimossi" '[ ! -f "$STORAGE/records/$ID.json" ] && [ ! -f "$STORAGE/pdfs/$ID.pdf" ]'
dash=$(curl -s -b "$TMP/cj_ad" "$BASE/admin.php")
check "Dopo la cancellazione la catena resta integra" 'printf "%s" "$dash" | grep -q "Registro integro"'
vres=$(curl -s -b "$TMP/cj_v" -F "csrf_token=$(curl -s -c "$TMP/cj_v2" "$BASE/verify.php" | grep -o 'name="csrf_token" value="[^"]*"' | cut -d'"' -f4)" -b "$TMP/cj_v2" -F "pdf=@$TMP/ADMIN.pdf;type=application/pdf" "$BASE/verify.php")
check "PDF ancora verificabile dopo la cancellazione dei dati" 'printf "%s" "$vres" | grep -q "Documento autentico e integro"'

section "11. Manomissione del registro"
cp "$STORAGE/ledger/consents.jsonl" "$TMP/ledger.bak"
python3 -c "
import sys,json;p=sys.argv[1];L=open(p).read().splitlines();d=json.loads(L[0]);d['marketing']=not d['marketing'];L[0]=json.dumps(d,ensure_ascii=False,separators=(',',':'));open(p,'w').write('\n'.join(L)+'\n')" "$STORAGE/ledger/consents.jsonl"
dash=$(curl -s -b "$TMP/cj_ad" "$BASE/admin.php")
check "Riga modificata -> 'registro alterato'" 'printf "%s" "$dash" | grep -q "registro alterato"'
cp "$TMP/ledger.bak" "$STORAGE/ledger/consents.jsonl"
sed -i '2d' "$STORAGE/ledger/consents.jsonl"
dash=$(curl -s -b "$TMP/cj_ad" "$BASE/admin.php")
check "Riga cancellata -> 'registro alterato'" 'printf "%s" "$dash" | grep -q "registro alterato"'
cp "$TMP/ledger.bak" "$STORAGE/ledger/consents.jsonl"

section "12. Concorrenza (10 invii simultanei)"
before=$(ledger_count)
for i in $(seq 1 10); do open_form "p$i"; cp "$TMP/cj_p$i" "$TMP/cjc_$i"; echo "$CSRF $TS" > "$TMP/tok_$i"; done
sleep 1.2
for i in $(seq 1 10); do
  read -r c t < "$TMP/tok_$i"
  curl -s -o /dev/null -b "$TMP/cjc_$i" -H 'Accept: application/json' -H "X-Forwarded-For: 10.5.0.$i" -e "$BASE/index.php" \
    --data-urlencode "csrf_token=$c" --data-urlencode "form_ts=$t" "${valid_fields[@]}" "$BASE/process.php" &
done; wait
check "10 invii paralleli -> 20 eventi registrati" '[ $(( $(ledger_count) - before )) = 20 ]' "delta $(( $(ledger_count) - before ))"
dash=$(curl -s -b "$TMP/cj_ad" "$BASE/admin.php")
check "Catena integra dopo invii concorrenti" 'printf "%s" "$dash" | grep -q "Registro integro"'

section "13. Correzioni dalla code review"
rm -f "$MAILDIR"/*
open_form h; sleep 1.2
sid=$(awk '$6=="nvz_sid"{print $7}' "$TMP/cj_h")  # curl non invia il cookie se l'Host è cambiato: lo passiamo a mano
curl -s -o /dev/null -H "Cookie: nvz_sid=$sid" -H 'Accept: application/json' -H "X-Forwarded-For: 10.6.0.1" -H "Host: evil.example" \
  --data-urlencode "csrf_token=$CSRF" --data-urlencode "form_ts=$TS" --data-urlencode "nome=Host" --data-urlencode "cognome=Injection" \
  --data-urlencode "email=host@example.com" --data-urlencode "telefono=3331234567" --data-urlencode "privacy=1" --data-urlencode "marketing=1" "$BASE/process.php"
sleep 0.5
decoded=$(python3 -I -c "
import email,glob,sys
for f in glob.glob(sys.argv[1]+'/*.eml'):
    m=email.message_from_bytes(open(f,'rb').read()); print(m.as_string()[:3000])
    for p in m.walk():
        if p.get_content_maintype()=='text': print(p.get_payload(decode=True).decode())
" "$MAILDIR")
check "Host header falsificato: link e Message-ID usano site_url" '! printf "%s" "$decoded" | grep -q "evil.example" && printf "%s" "$decoded" | grep -q "127.0.0.1:8091/sviluppo/confirm.php"'
open_form s; sleep 1.2
out=$(curl -s -H 'Accept: application/json' -H "X-Forwarded-For: 10.6.0.2" -c "$TMP/cj_s2" -w '\n%{http_code}' \
  --data-urlencode "csrf_token=$CSRF" --data-urlencode "form_ts=$TS" "${valid_fields[@]}" "$BASE/process.php")
check "Sessione scaduta -> 403 con token nuovi" '[ "$(printf "%s" "$out" | tail -1)" = 403 ] && printf "%s" "$out" | head -n -1 | json "d[\"refresh\"][\"csrf_token\"]" | grep -Eq "^[a-f0-9]{64}$"'
nc=$(printf '%s' "$out" | head -n -1 | json 'd["refresh"]["csrf_token"]'); nt=$(printf '%s' "$out" | head -n -1 | json 'd["refresh"]["form_ts"]')
n0=$(ledger_count)
out=$(curl -s -b "$TMP/cj_s2" -H 'Accept: application/json' -H "X-Forwarded-For: 10.6.0.2" -w '\n%{http_code}' \
  --data-urlencode "csrf_token=$nc" --data-urlencode "form_ts=$nt" "${valid_fields[@]}" "$BASE/process.php")
check "Nuovo tentativo automatico con i token nuovi registra il consenso" '[ "$(printf "%s" "$out" | tail -1)" = 200 ] && [ $(( $(ledger_count) - n0 )) = 2 ]'
open_form b; sleep 1.2
out=$(curl -s -b "$TMP/cj_b" -H 'Accept: application/json' -H "X-Forwarded-For: 10.6.0.3" -H $'Referer: http://127.0.0.1:8091/sviluppo/index.php?x=\xc3\x28\xff' \
  --data-urlencode "csrf_token=$CSRF" --data-urlencode "form_ts=$TS" "${valid_fields[@]}" "$BASE/process.php")
bid=$(printf '%s' "$out" | json 'd.get("id","")')
check "Referer con UTF-8 non valido: pratica salvata integra" '[ -n "$bid" ] && python3 -c "import json,sys;d=json.load(open(sys.argv[1]));assert d[\"nome\"]==\"Mario\"" "$STORAGE/records/$bid.json"'
codes=""
for i in 1 2 3 4 5 6; do open_form q; sleep 1.1; codes="$codes $(submit q 10.6.0.4 --data-urlencode "nome=X" | tail -1)"; done
open_form q; sleep 1.1; last=$(submit q 10.6.0.4 "${valid_fields[@]}" | tail -1)
check "6 errori di battitura non bloccano l'invio corretto ($codes -> $last)" '[ "$last" = 200 ]'
dash=$(curl -s -b "$TMP/cj_ad" "$BASE/admin.php?recheck=1")
check "Admin: autoverifica cartella dati protetta (nessun avviso)" '! printf "%s" "$dash" | grep -q "PERICOLO" && ! printf "%s" "$dash" | grep -q "Non è stato possibile verificare"'
chmod 000 "$STORAGE"
page=$(curl -s -w '\n%{http_code}' "$BASE/index.php")
chmod 750 "$STORAGE"
check "Cartella dati non scrivibile: la landing resta online con avviso" '[ "$(printf "%s" "$page" | tail -1)" = 200 ] && printf "%s" "$page" | grep -q "temporaneamente non disponibile" && printf "%s" "$page" | grep -q "Trasformiamo"'

# ---------------------------------------------------------------------------
rm -rf "$TMP"
printf '\n\033[1mRisultato: %d superati, %d falliti\033[0m\n' "$PASS" "$FAIL"
[ "$FAIL" = 0 ]
