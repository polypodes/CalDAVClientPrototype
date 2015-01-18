# CalDAV Client Prototype

A CalDAV/WebDAV PHP client using Sabre's [vObject](https://github.com/fruux/sabre-vobject) & [Faker](https://github.com/fzaninotto/Faker) to generate fake iCal & vCard objects.

## Install

```bash
make
make install
```

## TODO

See [TODO.md](TODO.md)

## Fakely generates vObjects:

### vCard:

```
 BEGIN:VCARD
 VERSION:3.0
 PRODID:-//Sabre//Sabre VObject 3.3.5//EN
 FN:Jeannine-Olivie Huet
 TEL:0272666909
 EMAIL:leconte.capucine@millet.fr
 TEL;TYPE=fax:+33 (0)3 39 98 94 19
 END:VCARD
 ```

### vCal:
 
 ```
 BEGIN:VCALENDAR
 VERSION:2.0
 PRODID:-//Sabre//Sabre VObject 3.3.5//EN
 CALSCALE:GREGORIAN
 ORGANIZER:mailto:veronique93@brunel.net
 ATTENDEE:mailto:dupre.emmanuelle@sfr.fr
 ATTENDEE:mailto:matthieu.grenier@tele2.fr
 BEGIN:VEVENT
 SUMMARY:Voluptas impedit aut dignissimos non distinctio est sit.
 DTSTART;TZID=Europe/Zurich:20130608T161853
 RRULE:FREQ=YEARLY
 END:VEVENT
 ENDVCALENDAR
 ```
