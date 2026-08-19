import raw from '../data/data.json'

const fix = (s) =>
  typeof s === 'string' ? s.replaceAll('/site-assets/images/', '/assets/') : s

function deepFix(o) {
  if (typeof o === 'string') return fix(o)
  if (Array.isArray(o)) return o.map(deepFix)
  if (o && typeof o === 'object') {
    const n = {}
    for (const k in o) n[k] = deepFix(o[k])
    return n
  }
  return o
}

const data = deepFix(raw)
export default data
export const { site, menu, home, sidebar, footer, pages } = data
