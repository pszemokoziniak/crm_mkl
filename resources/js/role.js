// Jedno miejsce z nazwami ról. Wcześniej te same etykiety były wpisane
// w trzech widokach i przy dodaniu roli trzeba było trafić do każdego.
// Wartości odpowiadają App\Enums\Role (kolumna users.owner).
export const ROLE = {
  ADMIN: 1,
  BIURO: 2,
  KIEROWNIK: 3,
  KIEROWNICTWO: 4,
}

export const ETYKIETY_ROL = {
  [ROLE.ADMIN]: 'Administrator',
  [ROLE.BIURO]: 'Biuro',
  [ROLE.KIEROWNIK]: 'Kierownik budowy',
  [ROLE.KIEROWNICTWO]: 'Kierownictwo',
}

/** Do list rozwijanych — kolejność jak w formularzu. */
export const OPCJE_ROL = [ROLE.ADMIN, ROLE.BIURO, ROLE.KIEROWNIK, ROLE.KIEROWNICTWO].map((wartosc) => ({
  wartosc,
  etykieta: ETYKIETY_ROL[wartosc],
}))

export function etykietaRoli(owner) {
  return ETYKIETY_ROL[owner] || '—'
}
