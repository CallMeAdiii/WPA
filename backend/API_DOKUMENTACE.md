# API Dokumentace – Rezervační systém sportovišť

## Base URL

```
https://demonstrated-grass-cayman-def.trycloudflare.com
```

> ⚠️ URL se může změnit po restartu serveru. Vždy ověř aktuální URL s backend týmem.

---

## Autentizace

Většina endpointů vyžaduje přihlášení. Po úspěšném loginu dostaneš **JWT token**, který musíš posílat v hlavičce každého požadavku:

```
Authorization: Bearer <token>
```

Token je platný **24 hodin**.

---

## Uživatelské role

| Role | Popis |
|------|-------|
| `student` | Základní uživatel – může rezervovat sportoviště |
| `teacher` | Stejná práva jako student |
| `admin` | Může spravovat sportoviště a vidí všechny rezervace |

---

## Endpointy

### 🔐 Autentizace

---

#### `POST /api/auth/register` – Registrace

Vytvoří nový uživatelský účet.

**Tělo požadavku (JSON):**
```json
{
  "name": "Jan Novák",
  "email": "jan@skola.cz",
  "password": "heslo123",
  "role": "student"
}
```

| Pole | Typ | Povinné | Popis |
|------|-----|---------|-------|
| `name` | string | ✅ | Celé jméno (2–100 znaků) |
| `email` | string | ✅ | Platný email |
| `password` | string | ✅ | Heslo (6–100 znaků) |
| `role` | string | ❌ | `student` / `teacher` / `admin` (výchozí: `student`) |

**Odpověď – úspěch `201`:**
```json
{
  "message": "Registrace proběhla úspěšně",
  "userId": 1
}
```

**Možné chyby:**
| Kód | Popis |
|-----|-------|
| `400` | Chybí nebo neplatná pole |
| `409` | Email je již registrován |

---

#### `POST /api/auth/login` – Přihlášení

Přihlásí uživatele a vrátí JWT token.

**Tělo požadavku (JSON):**
```json
{
  "email": "jan@skola.cz",
  "password": "heslo123"
}
```

**Odpověď – úspěch `200`:**
```json
{
  "message": "Přihlášení úspěšné",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "name": "Jan Novák",
    "email": "jan@skola.cz",
    "role": "student"
  }
}
```

> 💡 **Token ulož** (např. do `localStorage`) a posílej ho v hlavičce každého dalšího požadavku.

**Možné chyby:**
| Kód | Popis |
|-----|-------|
| `400` | Chybí email nebo heslo |
| `401` | Nesprávný email nebo heslo |

---

### 🏟️ Sportoviště

Všechny endpointy vyžadují přihlášení (`Authorization: Bearer <token>`).

---

#### `GET /api/facilities` – Seznam sportovišť

Vrátí seznam všech sportovišť. Lze filtrovat pomocí query parametrů.

**Query parametry (volitelné):**
| Parametr | Typ | Popis |
|----------|-----|-------|
| `type` | string | Filtr podle typu (např. `tělocvična`, `posilovna`) |
| `capacity` | number | Minimální kapacita |

**Příklady:**
```
GET /api/facilities
GET /api/facilities?type=tělocvična
GET /api/facilities?capacity=20
GET /api/facilities?type=tělocvična&capacity=10
```

**Odpověď – úspěch `200`:**
```json
[
  {
    "id": 1,
    "name": "Tělocvična 1",
    "type": "tělocvična",
    "description": "Velká tělocvična s parketovou podlahou",
    "capacity": 30,
    "created_at": "2026-04-20T17:57:45.000Z"
  },
  {
    "id": 2,
    "name": "Posilovna",
    "type": "posilovna",
    "description": "Plně vybavená posilovna",
    "capacity": 20,
    "created_at": "2026-04-20T17:57:45.000Z"
  }
]
```

---

#### `GET /api/facilities/:id` – Detail sportoviště

Vrátí detail jednoho sportoviště.

**Příklad:**
```
GET /api/facilities/1
```

**Odpověď – úspěch `200`:**
```json
{
  "id": 1,
  "name": "Tělocvična 1",
  "type": "tělocvična",
  "description": "Velká tělocvična s parketovou podlahou",
  "capacity": 30,
  "created_at": "2026-04-20T17:57:45.000Z"
}
```

**Možné chyby:**
| Kód | Popis |
|-----|-------|
| `404` | Sportoviště nenalezeno |

---

#### `POST /api/facilities` – Přidání sportoviště *(pouze admin)*

**Tělo požadavku (JSON):**
```json
{
  "name": "Tělocvična 3",
  "type": "tělocvična",
  "description": "Nová tělocvična v přízemí",
  "capacity": 25
}
```

| Pole | Typ | Povinné | Popis |
|------|-----|---------|-------|
| `name` | string | ✅ | Název (2–100 znaků) |
| `type` | string | ✅ | Typ (2–50 znaků) |
| `description` | string | ❌ | Popis |
| `capacity` | number | ✅ | Kapacita (1–10000) |

**Odpověď – úspěch `201`:**
```json
{
  "message": "Sportoviště přidáno",
  "id": 4
}
```

**Možné chyby:**
| Kód | Popis |
|-----|-------|
| `400` | Chybí nebo neplatná pole |
| `403` | Nemáš oprávnění (nejsi admin) |

---

#### `DELETE /api/facilities/:id` – Smazání sportoviště *(pouze admin)*

**Příklad:**
```
DELETE /api/facilities/4
```

**Odpověď – úspěch `200`:**
```json
{
  "message": "Sportoviště smazáno"
}
```

**Možné chyby:**
| Kód | Popis |
|-----|-------|
| `403` | Nemáš oprávnění (nejsi admin) |
| `404` | Sportoviště nenalezeno |

---

### 📅 Rezervace

Všechny endpointy vyžadují přihlášení (`Authorization: Bearer <token>`).

---

#### `GET /api/reservations` – Seznam rezervací

- **Běžný uživatel:** vrátí pouze svoje rezervace
- **Admin:** vrátí všechny rezervace všech uživatelů

**Odpověď pro běžného uživatele – úspěch `200`:**
```json
[
  {
    "id": 1,
    "user_id": 1,
    "facility_id": 1,
    "facility_name": "Tělocvična 1",
    "type": "tělocvična",
    "date": "2026-04-25T00:00:00.000Z",
    "time_from": "10:00:00",
    "time_to": "11:00:00",
    "status": "active",
    "created_at": "2026-04-20T18:00:00.000Z"
  }
]
```

**Odpověď pro admina – úspěch `200`:**
```json
[
  {
    "id": 1,
    "user_id": 1,
    "user_name": "Jan Novák",
    "email": "jan@skola.cz",
    "facility_id": 1,
    "facility_name": "Tělocvična 1",
    "date": "2026-04-25T00:00:00.000Z",
    "time_from": "10:00:00",
    "time_to": "11:00:00",
    "status": "active",
    "created_at": "2026-04-20T18:00:00.000Z"
  }
]
```

---

#### `POST /api/reservations` – Vytvoření rezervace

**Tělo požadavku (JSON):**
```json
{
  "facility_id": 1,
  "date": "2026-04-25",
  "time_from": "10:00",
  "time_to": "11:00"
}
```

| Pole | Typ | Povinné | Popis |
|------|-----|---------|-------|
| `facility_id` | number | ✅ | ID sportoviště |
| `date` | string | ✅ | Datum ve formátu `YYYY-MM-DD` |
| `time_from` | string | ✅ | Čas začátku ve formátu `HH:MM` |
| `time_to` | string | ✅ | Čas konce ve formátu `HH:MM` |

**Odpověď – úspěch `201`:**
```json
{
  "message": "Rezervace vytvořena",
  "id": 1
}
```

**Možné chyby:**
| Kód | Popis |
|-----|-------|
| `400` | Chybí pole / špatný formát data nebo času / datum v minulosti / time_from >= time_to |
| `404` | Sportoviště nenalezeno |
| `409` | Sportoviště je v daný čas již rezervováno |

---

#### `DELETE /api/reservations/:id` – Zrušení rezervace

Uživatel může zrušit pouze svoje rezervace. Admin může zrušit jakoukoliv.

**Příklad:**
```
DELETE /api/reservations/1
```

**Odpověď – úspěch `200`:**
```json
{
  "message": "Rezervace zrušena"
}
```

> ℹ️ Rezervace se nesmaže z databáze, pouze se změní její `status` na `cancelled`.

**Možné chyby:**
| Kód | Popis |
|-----|-------|
| `403` | Nemáš oprávnění zrušit tuto rezervaci |
| `404` | Rezervace nenalezena |

---

## Stavové kódy

| Kód | Popis |
|-----|-------|
| `200` | OK |
| `201` | Vytvořeno |
| `400` | Chybný požadavek (špatná data) |
| `401` | Nepřihlášen |
| `403` | Nemáš oprávnění |
| `404` | Nenalezeno |
| `409` | Konflikt (duplicitní email / překrývající se rezervace) |
| `500` | Chyba serveru |

---

## Příklad použití v JavaScriptu

```javascript
const BASE_URL = 'https://demonstrated-grass-cayman-def.trycloudflare.com';

// Přihlášení
const res = await fetch(`${BASE_URL}/api/auth/login`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email: 'jan@skola.cz', password: 'heslo123' })
});
const data = await res.json();
const token = data.token;

// Výpis sportovišť
const facilities = await fetch(`${BASE_URL}/api/facilities`, {
  headers: { 'Authorization': `Bearer ${token}` }
});
const list = await facilities.json();

// Vytvoření rezervace
const reservation = await fetch(`${BASE_URL}/api/reservations`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    facility_id: 1,
    date: '2026-04-25',
    time_from: '10:00',
    time_to: '11:00'
  })
});
```
