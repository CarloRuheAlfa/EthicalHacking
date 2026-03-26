# Logs — veilig beheer (kort overzicht)

Dit bestand bevat korte richtlijnen voor het veilig beheren van de applicatielogs die in deze repository verschijnen (bestand: `logs/log.txt`).

Belangrijk: logs bevatten gevoelige metadata (user ids, IP-adressen, request-URI's). Behandel deze bestanden als vertrouwelijk en zorg dat ze nooit in versiebeheer (git) terechtkomen.

Wat je nu ziet
- `logs/log.txt`: applicatielog met tijdstempel, log-level, user-id (of "guest"), client IP en geredigeerde POST-gegevens.
- De applicatie redigeert bekende gevoelige POST-velden (`password`, `pwd`, `passwd`, `token`, `csrf_token`) automatisch als `[REDACTED]`.

Aanbevolen veiligheidsmaatregelen

1) Negeer logs in git
- Voeg het volgende toe aan je `.gitignore` in de projectroot:

```
/logs/
```

2) Bestandspermissies
- Zorg dat alleen de applicatie/gebruiker die de webserver runt en de systeembeheerders toegang hebben:

  - Eigenaar: webserver user (bv. `www-data` of `www`)
  - Groep: beheerteam of `www-data`
  - Permissies: `640` óf striktere ACLs

Voorbeeld (pas user/group aan naar je systeem):

```bash
# stel eigenaar in op www-data
sudo chown www-data:www-data logs/log.txt
# beperk permissies
sudo chmod 640 logs/log.txt
```

3) Log-rotatie en retention
- Stel logrotatie in zodat logs niet onbegrensd groeien en bewaarbeleid wordt afgedwongen.
- Voor een eenvoudige `logrotate` configuratie maak `/etc/logrotate.d/omanido` met inhoud:

```
/abs/path/to/your/project/logs/log.txt {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        # noop - of stuur signaal naar process
    endscript
}
```

Vervang `/abs/path/to/your/project` door het absolute pad op jouw server.

4) Centraliseer logs (aanbevolen)
- Overweeg logs te sturen naar een centrale logging stack (rsyslog, syslog-ng, ELK/EFK, Splunk, Papertrail, etc.). Dit maakt analyse, alerting en bewaring eenvoudiger.

5) Redactie en gevoelige data
- Hoewel de app standaard wachtwoorden en tokens redacteert, controleer je logging output regelmatig.
- Log nooit volledige wachtwoorden of geheime tokens in productie. Voor gecontroleerde pentests kun je opt-in debug-logs gebruiken buiten productie.

6) Beperk toegang tot logviewer tools
- Als je een webgebaseerde logviewer maakt (admin-only), bescherm deze met extra authenticatie en autorisatie en zorg dat de viewer alleen toegang krijgt tot geautoriseerde admins.

7) Monitoren en alerts
- Stel eenvoudige detectieregels in (bijv. veel mislukte logins per minuut, 'Onvoldoende saldo' fouten etc.) en configureer alerts naar e-mail/Slack/SIEM.

Snelkoppelingen: bekijk / zoek logs

```bash
# realtime volgen
tail -f logs/log.txt

# laatste 200 regels
tail -n 200 logs/log.txt

# zoek op mislukte login
grep -i "Failed login" logs/log.txt | tail -n 200

# tel mislukte logins per user
grep -i "Failed login" logs/log.txt | awk -F"uid:" '{print $2}' | cut -d' ' -f1 | sort | uniq -c | sort -nr
```

Aanvullende opmerkingen
- In productie kun je overwegen om logs naar syslog of een remote endpoint te sturen en `logs/log.txt` lokaal te minimaliseren.
- Houd APP_SECRET en andere geheime waarden veilig (niet in repo). Gebruik environment-variables of een secrets manager.

Als je wilt, kan ik:
- Een eenvoudige admin-only logviewer pagina toevoegen (met pagination en zoek/filter);
- Een `logrotate` configuratiebestand voor je project genereren en instructies toevoegen voor installatie;
- Een CLI-script toevoegen dat logs samenvat (bv. aantal failed logins, meest voorkomende fouten).

Laat weten welke van deze aanvullende stappen je wil.