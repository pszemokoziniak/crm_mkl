// Jedno miejsce z nazwami ról. Wcześniej te same etykiety były wpisane
// w trzech widokach i przy dodaniu roli trzeba było trafić do każdego.
// Wartości odpowiadają App\Enums\Role (kolumna users.owner).
export const ROLE = {
  ADMIN: 1,
  BIURO: 2,
  KIEROWNIK: 3,
  KIEROWNICTWO: 4,
  KIEROWNIK_PROJEKTU: 5,
}

export const ETYKIETY_ROL = {
  [ROLE.ADMIN]: 'Administrator',
  [ROLE.BIURO]: 'Biuro',
  [ROLE.KIEROWNIK]: 'Kierownik budowy',
  [ROLE.KIEROWNICTWO]: 'Kierownictwo',
  [ROLE.KIEROWNIK_PROJEKTU]: 'Kierownik projektu',
}

/** Do list rozwijanych — kolejność jak w formularzu. */
export const OPCJE_ROL = [ROLE.ADMIN, ROLE.BIURO, ROLE.KIEROWNIK, ROLE.KIEROWNIK_PROJEKTU, ROLE.KIEROWNICTWO].map((wartosc) => ({
  wartosc,
  etykieta: ETYKIETY_ROL[wartosc],
}))

/**
 * Role widzące tylko swoje budowy, nie wszystkie jak biuro. Ekrany pytały
 * dotąd wprost o `owner === 3`, więc kierownik projektu wpadał w gałąź biura
 * i dostawał przyciski, których serwer i tak odmawia.
 */
export function prowadziBudowy(owner) {
  return [ROLE.KIEROWNIK, ROLE.KIEROWNIK_PROJEKTU].includes(Number(owner))
}

export function etykietaRoli(owner) {
  return ETYKIETY_ROL[owner] || '—'
}
