export type Stage = {
  key: string
  label: string
  status: 'done' | 'current' | 'todo'
}

export type MeasurementRow = {
  code: string
  label: string
  unit: string
  dir: 'up' | 'down'
  essai: number
  temoin: number
  weight: number
}

export type Trial = {
  id: string
  code: string
  variety: string
  culture: string
  conduct: string
  status: string
  statusTone: 'success' | 'warning' | 'info' | 'neutral' | 'danger'
  supplier: string
  segment: string
  owner: string
  partner?: string
  site: string
  season: string
  cost?: string
  controls: string[]
  stages: Stage[]
  measures: MeasurementRow[]
}

const lifecycle = (current: string): Stage[] => {
  const all = [
    { key: 'creation', label: 'Création' },
    { key: 'semis', label: 'Semis' },
    { key: 'greffage', label: 'Greffage' },
    { key: 'transplantation', label: 'Transplantation' },
    { key: 'evaluation', label: 'Évaluation' },
    { key: 'resultat', label: 'Résultat' },
    { key: 'decision', label: 'Décision' },
    { key: 'cloture', label: 'Clôture' },
  ]
  const idx = all.findIndex((s) => s.key === current)
  return all.map((s, i) => ({
    ...s,
    status: i < idx ? 'done' : i === idx ? 'current' : 'todo',
  }))
}

const melonMeasures: MeasurementRow[] = [
  { code: 'YLD', label: 'Rendement', unit: 'kg/m²', dir: 'up', essai: 7.8, temoin: 6.9, weight: 30 },
  { code: 'BRIX', label: '°Brix (sucre)', unit: '°Bx', dir: 'up', essai: 13.2, temoin: 12.1, weight: 25 },
  { code: 'PMF', label: 'Poids moyen / fruit', unit: 'g', dir: 'up', essai: 1250, temoin: 1180, weight: 15 },
  { code: 'NBF', label: 'Nb fruits / plant', unit: 'fruits', dir: 'up', essai: 4.2, temoin: 4.5, weight: 10 },
  { code: 'FRM', label: 'Fermeté', unit: '/10', dir: 'up', essai: 7.5, temoin: 7.0, weight: 10 },
  { code: 'CONS', label: 'Conservation', unit: 'jours', dir: 'up', essai: 18, temoin: 15, weight: 10 },
]

export const trials: Trial[] = [
  {
    id: 'p17',
    code: 'P00017',
    variety: 'CLX 7702',
    culture: 'Melon',
    conduct: 'Sous serre',
    status: 'Décision',
    statusTone: 'info',
    supplier: 'Clause',
    segment: 'Melon / Charentais / Charentais jaune',
    owner: 'Assma Benhammou',
    partner: 'Pépinière Souss Plants',
    site: 'Agadir',
    season: '2026',
    cost: '35 000 MAD',
    controls: ['Magenta', 'Novitus', 'Avast'],
    stages: lifecycle('decision'),
    measures: melonMeasures,
  },
  {
    id: 'p02',
    code: 'P00002',
    variety: 'CLX 7702',
    culture: 'Melon',
    conduct: 'Sous serre',
    status: 'Évaluation',
    statusTone: 'warning',
    supplier: 'Clause',
    segment: 'Melon / Charentais / Charentais jaune',
    owner: 'Assma Benhammou',
    site: 'Agadir',
    season: '2026',
    controls: ['Magenta', 'Novitus'],
    stages: lifecycle('evaluation'),
    measures: melonMeasures,
  },
  {
    id: 'p03',
    code: 'P00003',
    variety: 'SYN 3391',
    culture: 'Tomate',
    conduct: 'Plein champ',
    status: 'Greffage',
    statusTone: 'neutral',
    supplier: 'Syngenta',
    segment: 'Tomate / Ronde / Ronde grappe',
    owner: 'Laila Amrani',
    site: 'Souss',
    season: '2026',
    controls: ['Anasta F1'],
    stages: lifecycle('greffage'),
    measures: melonMeasures,
  },
  {
    id: 'p01',
    code: 'P00001',
    variety: 'RZ 24-118',
    culture: 'Melon',
    conduct: 'Sous serre',
    status: 'Clôturé',
    statusTone: 'success',
    supplier: 'Rijk Zwaan',
    segment: 'Melon / Galia / Galia précoce',
    owner: 'Assma Benhammou',
    site: 'Agadir',
    season: '2025',
    cost: '934 MAD',
    controls: ['Magenta'],
    stages: lifecycle('cloture'),
    measures: melonMeasures,
  },
  {
    id: 'p07',
    code: 'P00007',
    variety: 'NUN 8812',
    culture: 'Melon',
    conduct: 'Sous serre',
    status: 'Semis',
    statusTone: 'neutral',
    supplier: 'Nunhems (BASF)',
    segment: 'Melon / Honeydew / Honeydew vert',
    owner: 'Assma Benhammou',
    site: 'Agadir',
    season: '2026',
    controls: ['Magenta', 'Novitus', 'Avast'],
    stages: lifecycle('semis'),
    measures: melonMeasures,
  },
  {
    id: 'p16',
    code: 'P00016',
    variety: 'NUN 8812',
    culture: 'Melon',
    conduct: 'Sous serre',
    status: 'Création',
    statusTone: 'neutral',
    supplier: 'Nunhems (BASF)',
    segment: 'Melon / Honeydew / Honeydew vert',
    owner: 'Assma Benhammou',
    site: 'Agadir',
    season: '2026',
    controls: [],
    stages: lifecycle('creation'),
    measures: melonMeasures,
  },
]

export const varieties = [
  { ref: 'E00002', name: 'CLX 7702', supplier: 'Clause', culture: 'Melon', origin: 'Espagne', segment: 'Melon / Charentais / Charentais jaune' },
  { ref: 'E00001', name: 'RZ 24-118', supplier: 'Rijk Zwaan', culture: 'Melon', origin: 'Pays-Bas', segment: 'Melon / Galia / Galia précoce' },
  { ref: 'E00004', name: 'NUN 8812', supplier: 'Nunhems (BASF)', culture: 'Melon', origin: 'Pays-Bas', segment: 'Melon / Honeydew / Honeydew vert' },
  { ref: 'E00003', name: 'SYN 3391', supplier: 'Syngenta', culture: 'Tomate', origin: 'États-Unis', segment: 'Tomate / Ronde / Ronde grappe' },
]

export const suppliers = [
  { name: 'Clause', country: 'France', varieties: 8 },
  { name: 'Rijk Zwaan', country: 'Pays-Bas', varieties: 12 },
  { name: 'Nunhems (BASF)', country: 'Allemagne', varieties: 6 },
  { name: 'Syngenta', country: 'Suisse', varieties: 9 },
  { name: 'Enza Zaden', country: 'Pays-Bas', varieties: 4 },
]

export const stockItems = [
  { ref: 'E00002', name: 'CLX 7702', stock: 50, threshold: 20, germination: 96, purity: 99, tone: 'success' as const },
  { ref: 'E00004', name: 'NUN 8812', stock: 0, threshold: 20, germination: 94, purity: 98, tone: 'danger' as const },
  { ref: 'E00001', name: 'RZ 24-118', stock: 0, threshold: 20, germination: 92, purity: 99, tone: 'danger' as const },
  { ref: 'E00003', name: 'SYN 3391', stock: 15, threshold: 20, germination: 90, purity: 97, tone: 'warning' as const },
]

export function scoreRow(m: MeasurementRow): number {
  let dev = (m.essai - m.temoin) / m.temoin
  if (m.dir === 'down') dev = -dev
  return Math.max(0, Math.min(100, 50 + dev * 100 * 2.5))
}

export function devPct(m: MeasurementRow): number {
  let d = ((m.essai - m.temoin) / m.temoin) * 100
  if (m.dir === 'down') d = -d
  return d
}

export function weightedScore(rows: MeasurementRow[]): number {
  const totW = rows.reduce((s, m) => s + m.weight, 0)
  if (totW === 0) return 0
  return rows.reduce((s, m) => s + scoreRow(m) * m.weight, 0) / totW
}
